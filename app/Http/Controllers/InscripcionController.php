<?php
namespace App\Http\Controllers;
use App\Models\{Inscripcion, Evento, Persona, Pago, Grado, Pais};
use App\Support\DatosPersona;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InscripcionController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', '')); $eventoId = $request->integer('evento_id');
        $inscripciones = Inscripcion::with(['evento.grados', 'persona.grado', 'persona.pais'])->withSum('pagos', 'monto')->withSum('pagos', 'descuento')->where('evento_id', $eventoId)
            ->withSum(['pagos as pagado_bs' => fn ($query) => $query->where('moneda', 'BOB')], 'monto')
            ->withSum(['pagos as pagado_usd' => fn ($query) => $query->where('moneda', 'USD')], 'monto_original')
            ->when($q !== '', fn ($query) => $query->whereHas('persona', fn ($query) => $query->where(function ($query) use ($q) {
                foreach (['primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'documento_identidad'] as $field) $query->orWhere($field, 'like', "%{$q}%");
            })))
            // Orden alfabético del participante antes de paginar, también con filtros.
            ->orderBy(Persona::selectRaw('LOWER(primer_nombre)')->whereColumn('personas.id', 'inscripciones.persona_id'))
            ->orderBy(Persona::selectRaw("LOWER(COALESCE(segundo_nombre, ''))")->whereColumn('personas.id', 'inscripciones.persona_id'))
            ->orderBy(Persona::selectRaw('LOWER(primer_apellido)')->whereColumn('personas.id', 'inscripciones.persona_id'))
            ->orderBy(Persona::selectRaw("LOWER(COALESCE(segundo_apellido, ''))")->whereColumn('personas.id', 'inscripciones.persona_id'))
            ->orderBy('id')->paginate(12)->withQueryString();
        $inscripciones->getCollection()->each(fn ($inscripcion) => $this->calcularTotales($inscripcion));
        return view('inscripciones.index', ['inscripciones' => $inscripciones, 'eventos' => Evento::orderBy('nombre')->get(), 'q' => $q]);
    }
    // Lista todas las inscripciones sin paginación ni filtros de la pantalla de registro.
    public function imprimir(Request $request)
    {
        $eventoId = $request->query('evento_id');
        $inscripciones = Inscripcion::with(['persona.grado', 'evento.grados'])->when($eventoId, fn ($query) => $query->where('evento_id', $eventoId))->withSum('pagos', 'monto')->withSum('pagos', 'descuento')
            ->withSum(['pagos as pagado_bs' => fn ($query) => $query->where('moneda', 'BOB')], 'monto')
            ->withSum(['pagos as pagado_usd' => fn ($query) => $query->where('moneda', 'USD')], 'monto_original')->get()
            ->sortBy(fn ($inscripcion) => mb_strtolower($inscripcion->persona->nombre_completo))->values();
        $inscripciones->each(fn ($inscripcion) => $this->calcularTotales($inscripcion));

        $totales = Pago::whereHas('inscripcion', fn ($query) => $query->when($eventoId, fn ($query) => $query->where('evento_id', $eventoId)))->selectRaw(
            "SUM(CASE WHEN moneda = 'BOB' THEN monto ELSE 0 END) AS bolivianos, " .
            "SUM(CASE WHEN moneda = 'USD' THEN monto_original ELSE 0 END) AS dolares"
        )->first();
        $totalPagadoBs = (string) BigDecimal::of((string) ($totales->bolivianos ?? 0))->toScale(2, RoundingMode::HalfUp);
        $totalPagadoUsd = (string) BigDecimal::of((string) ($totales->dolares ?? 0))->toScale(2, RoundingMode::HalfUp);

        $pendientes = $inscripciones->filter(fn ($inscripcion) => BigDecimal::of($inscripcion->saldo_pendiente)->isPositive());
        $participantesConDeuda = $pendientes->unique('persona_id')->count();
        $deudaBs = BigDecimal::zero();
        $deudaUsd = BigDecimal::zero();
        $conversionCompleta = true;
        foreach ($pendientes->groupBy('evento_id') as $grupo) {
            $subtotal = BigDecimal::zero();
            foreach ($grupo as $inscripcion) $subtotal = $subtotal->plus($inscripcion->saldo_pendiente);
            $deudaBs = $deudaBs->plus($subtotal);
            $tipoCambio = $grupo->first()->evento->tipo_cambio;
            if ($tipoCambio && BigDecimal::of($tipoCambio)->isPositive()) {
                $deudaUsd = $deudaUsd->plus($subtotal->dividedBy($tipoCambio, 12, RoundingMode::HalfUp));
            } else {
                $conversionCompleta = false;
            }
        }
        $deudaTotalBs = (string) $deudaBs->toScale(2, RoundingMode::HalfUp);
        $deudaTotalUsd = $conversionCompleta ? (string) $deudaUsd->toScale(2, RoundingMode::HalfUp) : null;
        $tiposCambioReporte = $eventoId ? Evento::whereKey($eventoId)->get() : $inscripciones->pluck('evento')->unique('id')->values();

        return view('inscripciones.imprimir', compact('inscripciones', 'totalPagadoBs', 'totalPagadoUsd', 'participantesConDeuda', 'deudaTotalBs', 'deudaTotalUsd', 'tiposCambioReporte'));
    }

    private function calcularTotales(Inscripcion $inscripcion): void
    {
        $tarifa = $inscripcion->tarifa();
        $pagado = BigDecimal::of((string) ($inscripcion->pagos_sum_monto ?? 0))->toScale(2, RoundingMode::HalfUp);
        $descuento = BigDecimal::of((string) ($inscripcion->pagos_sum_descuento ?? 0))->toScale(2, RoundingMode::HalfUp);
        $saldo = BigDecimal::of((string) $tarifa)->minus($pagado)->minus($descuento)->toScale(2);
        $inscripcion->tarifa_vigente = (string) $tarifa;
        $inscripcion->total_pagado = (string) $pagado;
        $inscripcion->saldo_pendiente = $saldo->isNegative() ? '0.00' : (string) $saldo;
    }

    private function form(Inscripcion $inscripcion)
    {
        $eventoSeleccionado = old('evento_id', $inscripcion->evento_id ?? request('evento_id'));
        $personaSeleccionada = old('persona_id', $inscripcion->persona_id ?? request('persona_id'));
        $personaFormulario = Persona::find($personaSeleccionada) ?? new Persona;
        $todasPersonas = Persona::with(['grado', 'inscripciones:id,persona_id,evento_id'])->orderBy('primer_apellido')->get();
        $opcionesPersonas = $todasPersonas->map(fn ($persona) => [
            'id' => $persona->id,
            'grado_id' => $persona->grado_id,
            'nombre' => $persona->nombre_completo.' · CI '.$persona->documento_identidad.' · '.$persona->grado->nombre,
            'eventos_inscritos' => $persona->inscripciones->where('id', '!=', $inscripcion->id)->pluck('evento_id')->values(),
            'datos' => $persona->only(['primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'documento_identidad', 'sexo', 'telefono', 'grado_id', 'pais_id', 'organizacion']),
            'foto_url' => $persona->foto_url,
        ]);
        $personas = $todasPersonas->filter(fn ($persona) => $eventoSeleccionado && !$persona->inscripciones
            ->where('id', '!=', $inscripcion->id)->contains('evento_id', $eventoSeleccionado));

        return view('inscripciones.form', [
            'inscripcion' => $inscripcion,
            'persona' => $personaFormulario, 'personaSeleccionada' => $personaSeleccionada,
            'grados' => Grado::ordenados()->get(),
            'paises' => Pais::orderBy('nombre')->get(),
            'eventos' => Evento::with('grados')->orderBy('nombre')->get(),
            'personas' => $personas, 'opcionesPersonas' => $opcionesPersonas,
            'eventoSeleccionado' => $eventoSeleccionado, 'hayPersonas' => $todasPersonas->isNotEmpty(),
        ]);
    }
    public function create() { return $this->form(new Inscripcion); }
    public function edit(Inscripcion $inscripcion) { return $this->form($inscripcion); }
    private function validated(Request $request, ?Inscripcion $inscripcion = null): array
    {
        $data = $request->validate([
            'evento_id' => 'required|exists:eventos,id',
            'persona_id' => ['required', 'exists:personas,id', Rule::unique('inscripciones')->where('evento_id', $request->input('evento_id'))->ignore($inscripcion)],
            'cargo' => 'required|string|max:120',
            'jefe_dojo' => 'sometimes|required|boolean',
            'invitado_sin_costo' => 'sometimes|required|boolean',
        ], ['persona_id.unique' => 'Esta persona ya está inscrita en el evento.']);
        $persona = Persona::findOrFail($data['persona_id']);
        $data['jefe_dojo'] = $request->has('jefe_dojo') ? $request->boolean('jefe_dojo') : ($inscripcion?->jefe_dojo ?? false);
        $data['invitado_sin_costo'] = $request->has('invitado_sin_costo') ? $request->boolean('invitado_sin_costo') : ($inscripcion?->invitado_sin_costo ?? false);
        $data['observaciones'] = $data['invitado_sin_costo'] ? 'invitado sin costo' : null;
        if ($inscripcion && $inscripcion->pagos()->exists()
            && ($inscripcion->persona_id != $data['persona_id'] || $inscripcion->evento_id != $data['evento_id'])) {
            throw ValidationException::withMessages(['persona_id' => 'Esta inscripción tiene pagos registrados. No se puede cambiar la persona ni el evento.']);
        }
        $evento = Evento::findOrFail($data['evento_id']);
        $tarifaJefe = $data['jefe_dojo'] && $persona->grado->esDan();
        if (!$data['invitado_sin_costo'] && in_array($persona->grado->nombre, Grado::DETALLADOS, true) && (!$evento->tipo_cambio || $evento->tipo_cambio <= 0)) {
            throw ValidationException::withMessages(['evento_id' => 'Configura el tipo de cambio del evento para calcular la tarifa en bolivianos.']);
        }
        if (!$data['invitado_sin_costo'] && $tarifaJefe && (!$evento->tipo_cambio || $evento->tipo_cambio <= 0)) {
            throw ValidationException::withMessages(['jefe_dojo' => 'Configura el tipo de cambio del evento para aplicar la tarifa de Jefe de Dojo de $us 100.']);
        }
        if (!$data['invitado_sin_costo'] && !$tarifaJefe && !$evento->grados()->where('grados.id', $persona->grado_id)->exists()) {
            throw ValidationException::withMessages(['persona_id' => 'Este evento no tiene una tarifa para el grado de la persona. Configúrala en Evento.']);
        }
        return $data;
    }
    private function guardar(Request $request, Inscripcion $inscripcion): void
    {
        // Compatibilidad con integraciones anteriores que envían sólo persona_id.
        if (!$request->boolean('registro_integrado')) {
            $inscripcion->fill($this->validated($request, $inscripcion->exists ? $inscripcion : null))->save();
            return;
        }
        $request->validate(['persona_id' => 'nullable|exists:personas,id']);
        if ($inscripcion->exists && $request->input('persona_id') != $inscripcion->persona_id) {
            throw ValidationException::withMessages(['persona_id' => 'La edición conserva al participante de esta inscripción.']);
        }
        $persona = $request->filled('persona_id') ? Persona::findOrFail($request->input('persona_id')) : new Persona;
        $fotoNueva = null;
        $fotoAnterior = $persona->foto;
        try {
            DB::transaction(function () use ($request, $inscripcion, &$persona, &$fotoNueva) {
                if ($persona->exists) $persona = Persona::whereKey($persona->id)->lockForUpdate()->firstOrFail();
                $data = DatosPersona::validated($request, $persona->exists ? $persona : null);
                unset($data['foto']);
                $archivoFoto = $request->file('foto');
                if (!$persona->exists && !$archivoFoto) $archivoFoto = new \Illuminate\Http\File(public_path('images/default.jpg'));
                if ($archivoFoto) {
                    $fotoNueva = Storage::disk('local')->putFile('personas', $archivoFoto);
                    if (!$fotoNueva) throw new \RuntimeException('No se pudo guardar la foto.');
                    $data['foto'] = $fotoNueva;
                }
                $persona->fill($data)->save();
                $inscripcionRequest = clone $request;
                $inscripcionRequest->merge(['persona_id' => $persona->id]);
                $inscripcion->fill($this->validated($inscripcionRequest, $inscripcion->exists ? $inscripcion : null))->save();
            });
        } catch (\Throwable $exception) {
            if ($fotoNueva) Storage::disk('local')->delete($fotoNueva);
            throw $exception;
        }
        if ($fotoNueva && $fotoAnterior && $fotoAnterior !== $fotoNueva) Storage::disk('local')->delete($fotoAnterior);
    }
    public function store(Request $request) { $this->guardar($request, new Inscripcion); return to_route('inscripciones.index')->with('success', 'Inscripción registrada correctamente.'); }
    public function update(Request $request, Inscripcion $inscripcion) { $this->guardar($request, $inscripcion); return to_route('inscripciones.index')->with('success', 'Inscripción actualizada.'); }
    public function destroy(Inscripcion $inscripcion)
    {
        if ($inscripcion->pagos()->exists()) {
            return to_route('inscripciones.index')->with('error', 'No se puede eliminar una inscripción con pagos registrados.');
        }
        $inscripcion->delete();
        return to_route('inscripciones.index')->with('success', 'Inscripción eliminada.');
    }
}
