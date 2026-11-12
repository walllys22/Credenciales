<?php

namespace App\Http\Controllers;

use App\Models\{Evento, Grado, Inscripcion, Persona};

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('dashboard', [
            'personas' => Persona::count(), 'grados' => Grado::count(),
            'eventos' => Evento::count(), 'inscripciones' => Inscripcion::count(),
            'proximos' => Evento::withCount('inscripciones')->whereDate('fecha_final', '>=', today())->orderBy('fecha_inicio')->limit(5)->get(),
        ]);
    }
}
