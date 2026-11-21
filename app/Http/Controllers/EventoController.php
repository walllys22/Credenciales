<?php
namespace App\Http\Controllers;
use App\Models\{Evento, Grado};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class EventoController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $filters = $request->validate([
            'estado' => ['nullable', Rule::in(['proximo', 'en_curso', 'finalizado'])],
            'per_page' => ['nullable', Rule::in([10, 25, 50, 100])],
        ]);
        $estado = $filters['estado'] ?? '';
        $perPage = (int) ($filters['per_page'] ?? 10);
        $eventos = Evento::with('grados')->withCount('inscripciones')
            ->when($q !== '', fn ($query) => $query->where(fn ($query) => $query->where('nombre', 'like', "%{$q}%")->orWhere('ciudad', 'like', "%{$q}%")->orWhere('lugar', 'like', "%{$q}%")))
            ->when($estado === 'proximo', fn ($query) => $query->whereDate('fecha_inicio', '>', today()))
            ->when($estado === 'en_curso', fn ($query) => $query->whereDate('fecha_inicio', '<=', today())->whereDate('fecha_final', '>=', today()))
            ->when($estado === 'finalizado', fn ($query) => $query->whereDate('fecha_final', '<', today()))
            ->orderByDesc('fecha_inicio')->orderByDesc('id')->paginate($perPage)->withQueryString();
        return view('eventos.index', compact('eventos', 'q', 'estado', 'perPage'));
    }
    public function create() { return view('eventos.form', ['evento' => new Evento, 'grados' => Grado::ordenados()->get()]); }
    public function edit(Evento $evento) { $evento->load('grados'); return view('eventos.form', ['evento' => $evento, 'grados' => Grado::ordenados()->get()]); }
    private function save(Request $request, Evento $evento): void
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255', 'fecha_inicio' => 'required|date',
            'fecha_final' => 'required|date|after_or_equal:fecha_inicio', 'lugar' => 'required|string|max:255', 'ciudad' => 'required|string|max:255',
            'tipo_cambio' => [(!$evento->exists || $evento->tipo_cambio !== null) ? 'required' : 'nullable', 'numeric', 'gt:0', 'max:999999.99', 'decimal:0,2'],
            'costos_usd' => 'required|array', 'costos_usd.*' => 'nullable|numeric|min:0|max:9999999999.99|decimal:0,2',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:10240|dimensions:max_width=12000,max_height=12000',
            'quitar_imagen' => 'sometimes|boolean',
        ]);
        $tarifas = [];
        $conservarAnteriores = $evento->exists && $evento->tipo_cambio === null && empty($data['tipo_cambio'])
            && collect($data['costos_usd'])->every(fn ($costo) => $costo === null || $costo === '');
        if ($conservarAnteriores) {
            $tarifas = $evento->grados->mapWithKeys(fn ($grado) => [$grado->id => ['costo' => $grado->pivot->costo, 'costo_usd' => null]])->all();
        }
        foreach ($data['costos_usd'] as $id => $costo) {
            if ($costo !== null && $costo !== '') {
                if (!Grado::whereKey($id)->exists()) throw ValidationException::withMessages(['costos_usd' => 'El grado seleccionado no existe.']);
                if (empty($data['tipo_cambio'])) throw ValidationException::withMessages(['tipo_cambio' => 'Ingresa el tipo de cambio para calcular el costo en bolivianos.']);
                $bolivianos = BigDecimal::of((string) $costo)->multipliedBy((string) $data['tipo_cambio'])->toScale(2, RoundingMode::HalfUp);
                if ($bolivianos->isGreaterThan('9999999999.99')) throw ValidationException::withMessages(['costos_usd.'.$id => 'El importe convertido a bolivianos supera el máximo permitido.']);
                $tarifas[$id] = ['costo' => (string) $bolivianos, 'costo_usd' => (string) BigDecimal::of((string) $costo)->toScale(2)];
            }
        }
        if (!empty($data['tipo_cambio'])) {
            foreach (Grado::whereIn('nombre', Grado::DETALLADOS)->get() as $grado) {
                $usd = $grado->costoBaseUsd();
                $tarifas[$grado->id] = ['costo_usd' => $usd, 'costo' => (string) BigDecimal::of($usd)->multipliedBy((string) $data['tipo_cambio'])->toScale(2, RoundingMode::HalfUp)];
            }
        }
        if (!$tarifas) throw ValidationException::withMessages(['costos_usd' => 'Define el costo en dólares de al menos un grado. Puedes usar 0 para acceso gratuito.']);
        if ($evento->exists && $evento->inscripciones()->whereHas('persona', fn ($q) => $q->whereNotIn('grado_id', array_keys($tarifas)))->exists()) {
            throw ValidationException::withMessages(['costos_usd' => 'No puedes retirar la tarifa de un grado que tiene personas inscritas.']);
        }
        $imagenAnterior = $evento->imagen;
        $imagenNueva = null;
        unset($data['costos_usd'], $data['imagen'], $data['quitar_imagen']);
        try {
            if ($request->hasFile('imagen')) {
                $imagenNueva = $request->file('imagen')->store('eventos', 'local');
                if (!$imagenNueva) throw new \RuntimeException('No se pudo guardar la imagen del evento.');
                $data['imagen'] = $imagenNueva;
            } elseif ($request->boolean('quitar_imagen')) {
                $data['imagen'] = null;
            }
            DB::transaction(function () use ($evento, $data, $tarifas) {
                $evento->fill($data)->save();
                $evento->grados()->sync($tarifas);
            });
        } catch (\Throwable $exception) {
            if ($imagenNueva) Storage::disk('local')->delete($imagenNueva);
            throw $exception;
        }
        if ($imagenAnterior && $imagenAnterior !== $evento->imagen) Storage::disk('local')->delete($imagenAnterior);
    }
    public function store(Request $request) { $this->save($request, new Evento); return to_route('eventos.index')->with('success', 'Evento creado correctamente.'); }
    public function update(Request $request, Evento $evento) { $this->save($request, $evento); return to_route('eventos.index')->with('success', 'Evento actualizado.'); }
    public function destroy(Evento $evento)
    {
        if ($evento->inscripciones()->exists()) return back()->with('error', 'El evento tiene inscripciones. Elimínalas antes de borrar el evento.');
        $imagen = $evento->imagen;
        $evento->delete();
        if ($imagen) Storage::disk('local')->delete($imagen);
        return to_route('eventos.index')->with('success', 'Evento eliminado.');
    }
    public function imagen(Evento $evento)
    {
        if (!$evento->imagen || !Storage::disk('local')->exists($evento->imagen)) {
            return response()->file(public_path('images/evento-default.svg'), ['X-Content-Type-Options' => 'nosniff']);
        }
        return Storage::disk('local')->response($evento->imagen, null, ['X-Content-Type-Options' => 'nosniff'], 'inline');
    }
}
