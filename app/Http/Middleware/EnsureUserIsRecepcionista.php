<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsRecepcionista
{
    /**
     * Handle an incoming request.
     * Permite acceso a recepcionistas y administradores.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->esRecepcionistaOAdministrador()) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }

}
