<?php
namespace App\Http\Controllers;
use App\Models\{Persona, Grado};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class PersonaController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $personas = Persona::with('grado')->when($q !== '', function ($query) use ($q) {
            $query->where(function ($query) use ($q) {
                foreach (['primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'documento_identidad'] as $field) $query->orWhere($field, 'like', "%{$q}%");
            });
        })->latest('id')->paginate(12)->withQueryString();
        return view('personas.index', compact('personas', 'q'));
    }
    public function create() { return view('personas.form', ['persona' => new Persona, 'grados' => Grado::orderBy('nombre')->get()]); }
    public function edit(Persona $persona) { return view('personas.form', ['persona' => $persona, 'grados' => Grado::orderBy('nombre')->get()]); }
    private function validated(Request $request, ?Persona $persona = null): array
    {
        $data = $request->validate([
            'primer_nombre' => 'required|string|max:100', 'segundo_nombre' => 'nullable|string|max:100',
            'primer_apellido' => 'required|string|max:100', 'segundo_apellido' => 'nullable|string|max:100',
            'documento_identidad' => ['nullable', 'string', 'max:50', Rule::unique('personas')->ignore($persona)],
            'sexo' => ['required', Rule::in(['Masculino', 'Femenino', 'Otro'])],
            'fecha_nacimiento' => 'nullable|date|before_or_equal:today', 'telefono' => 'nullable|string|max:30',
            'grado_id' => 'required|exists:grados,id',
            'foto' => [ $persona ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120', 'dimensions:max_width=6000,max_height=6000' ],
        ]);
        if ($persona && $persona->grado_id != $data['grado_id'] && $persona->inscripciones()->exists()) {
            throw ValidationException::withMessages(['grado_id' => 'Retira primero las inscripciones de esta persona antes de cambiar su grado.']);
        }
        return $data;
    }
    private function save(Request $request, Persona $persona): void
    {
        $data = $this->validated($request, $persona->exists ? $persona : null);
        unset($data['foto']);
        $fotoAnterior = $persona->foto;
        $fotoNueva = null;
        try {
            if ($request->hasFile('foto')) {
                $fotoNueva = $request->file('foto')->store('personas', 'local');
                if (!$fotoNueva) throw new \RuntimeException('No se pudo guardar la foto de la persona.');
                $data['foto'] = $fotoNueva;
            }
            $persona->fill($data)->save();
        } catch (\Throwable $exception) {
            if ($fotoNueva) Storage::disk('local')->delete($fotoNueva);
            throw $exception;
        }
        if ($fotoAnterior && $fotoAnterior !== $persona->foto) Storage::disk('local')->delete($fotoAnterior);
    }
    public function store(Request $request) { $this->save($request, new Persona); return to_route('personas.index')->with('success', 'Persona registrada correctamente.'); }
    public function update(Request $request, Persona $persona) { $this->save($request, $persona); return to_route('personas.index')->with('success', 'Persona actualizada.'); }
    public function destroy(Persona $persona)
    {
        if ($persona->inscripciones()->exists()) return back()->with('error', 'No se puede eliminar una persona con inscripciones. Elimina primero sus inscripciones.');
        $foto = $persona->foto;
        $persona->delete();
        if ($foto) Storage::disk('local')->delete($foto);
        return to_route('personas.index')->with('success', 'Persona eliminada.');
    }
    public function foto(Persona $persona)
    {
        if (!$persona->foto || !Storage::disk('local')->exists($persona->foto)) {
            return response()->file(public_path('images/persona-default.svg'), ['X-Content-Type-Options' => 'nosniff']);
        }
        return Storage::disk('local')->response($persona->foto, null, ['X-Content-Type-Options' => 'nosniff'], 'inline');
    }
}
