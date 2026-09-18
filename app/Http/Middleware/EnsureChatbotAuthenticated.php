<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureChatbotAuthenticated
{
    /**
     * El chatbot es exclusivo para usuarios registrados con una sesión activa.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->guest(route('register'));
        }

        return $next($request);
    }
}
