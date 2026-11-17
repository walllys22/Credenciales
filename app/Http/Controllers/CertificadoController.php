<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;

class CertificadoController extends Controller
{
    public function show(Inscripcion $inscripcion)
    {
        $inscripcion->load(['persona.grado', 'evento']);
        $persona = $inscripcion->persona;
        $evento = $inscripcion->evento;
        $esDan = $persona->grado->esDan();
        $nombreCertificado = ($esDan ? 'Sensei ' : '').$persona->nombre_completo;

        return response()->view('inscripciones.vercert', compact('inscripcion', 'persona', 'evento', 'nombreCertificado'))
            ->header('Cache-Control', 'private, no-store');
    }
}
