<?php

namespace App\Support;

use App\Models\Persona;
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
            'grado_id' => 'required|exists:grados,id',
            'pais_id' => 'sometimes|nullable|exists:paises,id',
            'foto' => [$persona ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
        ]);
        if ($persona && $persona->grado_id != $data['grado_id'] && $persona->inscripciones()->exists()) {
            throw ValidationException::withMessages(['grado_id' => 'El grado está asociado a inscripciones existentes. No se puede cambiar para conservar sus tarifas.']);
        }
        return $data;
    }
}
