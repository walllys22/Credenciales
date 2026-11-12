<?php

namespace App\Http\Controllers;

use App\Models\Pais;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Administración del catálogo de países ordenado alfabéticamente.
class PaisController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $paises = Pais::when($q !== '', fn ($query) => $query->where('nombre', 'like', "%{$q}%"))
            ->orderBy('nombre')->paginate(12)->withQueryString();

        return view('paises.index', compact('paises', 'q'));
    }

    public function create()
    {
        return view('paises.form', ['pais' => new Pais]);
    }

    public function edit(Pais $pais)
    {
        return view('paises.form', compact('pais'));
    }

    private function validated(Request $request, ?Pais $pais = null): array
    {
        $request->merge(['nombre' => trim((string) $request->input('nombre', ''))]);
        return $request->validate(['nombre' => ['required', 'string', 'max:255', Rule::unique('paises', 'nombre')->ignore($pais)]]);
    }

    public function store(Request $request)
    {
        Pais::create($this->validated($request));
        return to_route('paises.index')->with('success', 'País agregado.');
    }

    public function update(Request $request, Pais $pais)
    {
        $pais->update($this->validated($request, $pais));
        return to_route('paises.index')->with('success', 'País actualizado.');
    }

    public function destroy(Pais $pais)
    {
        $pais->delete();
        return to_route('paises.index')->with('success', 'País eliminado.');
    }
}
