<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

class ClearCacheController extends Controller
{
    public function __invoke()
    {
        try {
            if (Artisan::call('optimize:clear') !== 0) {
                return back()->with('error', 'No se pudo completar la limpieza de caché. Inténtalo nuevamente.');
            }
        } catch (\Throwable $exception) {
            report($exception);
            return back()->with('error', 'No se pudo limpiar la caché. Revisa los permisos de storage y bootstrap/cache.');
        }

        return back()->with('success', 'Caché de la aplicación, configuración, rutas y vistas limpiada correctamente.');
    }
}
