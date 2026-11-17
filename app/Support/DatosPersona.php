<?php

namespace App\Support;

use App\Models\{Persona, Grado};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DatosPersona
{
    public static function validated(Request $request, ?Persona $persona = null): array
    {
        $data = $request->validate([
            'primer_nombre' => 'required|string|max:100', 'segundo_nombre' => 'nullable|string|max:100',
            'primer_apellido' => 'required|string|max:100', 'segundo_apellido' => 'nullable|string|max:100',
            'documento_identidad' => ['nullable', 'string', 'max:50', Rule::unique('personas')->ignore($persona)],
            'sexo' => ['required', Rule::in(['Masculino', 'Femenino', 'Otro'])],
            'fecha_nacimiento' => 'nullable|date|before_or_equal:today', 'telefono' => 'nullable|string|max:30',
            'grado_id' => ['required', $request->boolean('registro_integrado') ? Rule::exists('grados', 'id')->whereIn('nombre', Grado::DETALLADOS) : 'exists:grados,id'],
            'pais_id' => 'sometimes|nullable|exists:paises,id',
            'organizacion' => 'sometimes|nullable|string|max:255',
            'foto' => [$persona ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
        ]);
        if ($persona && $persona->grado_id != $data['grado_id'] && $persona->inscripciones()->exists()) {
            $nuevo = Grado::findOrFail($data['grado_id']);
            if (!$persona->grado->categoria() || $persona->grado->categoria() !== $nuevo->categoria()) {
                throw ValidationException::withMessages(['grado_id' => 'No se puede cambiar la categoría de un participante con inscripciones existentes para conservar sus tarifas.']);
            }
        }
        return $data;
    }
}
