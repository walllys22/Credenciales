<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ProjectAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless($user && $user->active, 403);
        if (! $user->isSuperAdmin()) {
            $lote = $request->routeIs('inscripciones.credenciales-lote') && $request->isMethod('POST');
            abort_unless($user->role === 'impresiones' && ($lote || ($request->routeIs(
                'inscripciones.index', 'inscripciones.credencial', 'inscripciones.certificado',
                'personas.foto', 'eventos.imagen'
            ) && $request->isMethod('GET'))), 403);
        }
        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
