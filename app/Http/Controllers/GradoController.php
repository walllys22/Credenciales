<?php
namespace App\Http\Controllers;
use App\Models\Grado;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GradoController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $grados = Grado::when($q !== '', fn ($query) => $query->where('nombre', 'like', "%{$q}%"))
            ->orderBy('id')->paginate(25)->withQueryString();

        return view('grados.index', compact('grados', 'q'));
    }

    public function create()
    {
        return view('grados.form', ['grado' => new Grado]);
    }

    public function edit(Grado $grado)
    {
        return view('grados.form', compact('grado'));
    }

    public function store(Request $request)
    {
        Grado::create($request->validate(['nombre' => 'required|string|max:255|unique:grados,nombre']));

        return to_route('grados.index')->with('success', 'Grado agregado.');
    }

    public function update(Request $request, Grado $grado)
    {
        $grado->update($request->validate(['nombre' => ['required', 'string', 'max:255', Rule::unique('grados')->ignore($grado)]]));

        return to_route('grados.index')->with('success', 'Grado actualizado.');
    }
    public function destroy(Grado $grado)
    {
        if ($grado->personas()->exists() || $grado->eventos()->exists()) return back()->with('error', 'El grado está en uso por personas o tarifas de eventos.');
        $grado->delete(); return to_route('grados.index')->with('success', 'Grado eliminado.');
    }
}
