<?php
namespace App\Http\Controllers;
use App\Models\{Inscripcion, Evento, Persona, Pago};
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InscripcionController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', '')); $eventoId = $request->query('evento_id');
        $inscripciones = Inscripcion::with(['evento.grados', 'persona.grado'])->withSum('pagos', 'monto')->withSum('pagos', 'descuento')->when($eventoId, fn ($query) => $query->where('evento_id', $eventoId))
            ->when($q !== '', fn ($query) => $query->whereHas('persona', fn ($query) => $query->where(fn ($query) => $query->where('primer_nombre', 'like', "%{$q}%")->orWhere('primer_apellido', 'like', "%{$q}%")->orWhere('documento_identidad', 'like', "%{$q}%"))))
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
    public function imprimir()
    {
        $inscripciones = Inscripcion::with(['persona.grado', 'evento.grados'])->withSum('pagos', 'monto')->withSum('pagos', 'descuento')
            ->withSum(['pagos as pagado_bs' => fn ($query) => $query->where('moneda', 'BOB')], 'monto')
            ->withSum(['pagos as pagado_usd' => fn ($query) => $query->where('moneda', 'USD')], 'monto_original')->get()
            ->sortBy(fn ($inscripcion) => mb_strtolower($inscripcion->persona->nombre_completo))->values();
        $inscripciones->each(fn ($inscripcion) => $this->calcularTotales($inscripcion));

        $totales = Pago::whereHas('inscripcion')->selectRaw(
            "SUM(CASE WHEN moneda = 'BOB' THEN monto ELSE 0 END) AS bolivianos, " .
            "SUM(CASE WHEN moneda = 'USD' THEN monto_original ELSE 0 END) AS dolares"
        )->first();
        $totalPagadoBs = (string) BigDecimal::of((string) ($totales->bolivianos ?? 0))->toScale(2, RoundingMode::HalfUp);
        $totalPagadoUsd = (string) BigDecimal::of((string) ($totales->dolares ?? 0))->toScale(2, RoundingMode::HalfUp);

        return view('inscripciones.imprimir', compact('inscripciones', 'totalPagadoBs', 'totalPagadoUsd'));
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
        $todasPersonas = Persona::with(['grado', 'inscripciones:id,persona_id,evento_id'])->orderBy('primer_apellido')->get();
        $opcionesPersonas = $todasPersonas->map(fn ($persona) => [
            'id' => $persona->id,
            'grado_id' => $persona->grado_id,
            'nombre' => $persona->nombre_completo.' · CI '.$persona->documento_identidad.' · '.$persona->grado->nombre,
            'eventos_inscritos' => $persona->inscripciones->where('id', '!=', $inscripcion->id)->pluck('evento_id')->values(),
        ]);
        $personas = $todasPersonas->filter(fn ($persona) => $eventoSeleccionado && !$persona->inscripciones
            ->where('id', '!=', $inscripcion->id)->contains('evento_id', $eventoSeleccionado));

        return view('inscripciones.form', [
            'inscripcion' => $inscripcion,
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
        ], ['persona_id.unique' => 'Esta persona ya está inscrita en el evento.']);
        $persona = Persona::findOrFail($data['persona_id']);
        $data['jefe_dojo'] = $request->has('jefe_dojo') ? $request->boolean('jefe_dojo') : ($inscripcion?->jefe_dojo ?? false);
        if ($inscripcion && $inscripcion->pagos()->exists()
            && ($inscripcion->persona_id != $data['persona_id'] || $inscripcion->evento_id != $data['evento_id'])) {
            throw ValidationException::withMessages(['persona_id' => 'Esta inscripción tiene pagos registrados. No se puede cambiar la persona ni el evento.']);
        }
        $evento = Evento::findOrFail($data['evento_id']);
        if ($data['jefe_dojo'] && (!$evento->tipo_cambio || $evento->tipo_cambio <= 0)) {
            throw ValidationException::withMessages(['jefe_dojo' => 'Configura el tipo de cambio del evento para aplicar la tarifa de Jefe de Dojo de $us 100.']);
        }
        if (!$data['jefe_dojo'] && !$evento->grados()->where('grados.id', $persona->grado_id)->exists()) {
            throw ValidationException::withMessages(['persona_id' => 'Este evento no tiene una tarifa para el grado de la persona. Configúrala en Evento.']);
        }
        return $data;
    }
    public function store(Request $request) { Inscripcion::create($this->validated($request)); return to_route('inscripciones.index')->with('success', 'Inscripción registrada correctamente.'); }
    public function update(Request $request, Inscripcion $inscripcion) { $inscripcion->update($this->validated($request, $inscripcion)); return to_route('inscripciones.index')->with('success', 'Inscripción actualizada.'); }
    public function destroy(Inscripcion $inscripcion)
    {
        if ($inscripcion->pagos()->exists()) {
            return to_route('inscripciones.index')->with('error', 'No se puede eliminar una inscripción con pagos registrados.');
        }
        $inscripcion->delete();
        return to_route('inscripciones.index')->with('success', 'Inscripción eliminada.');
    }
}
