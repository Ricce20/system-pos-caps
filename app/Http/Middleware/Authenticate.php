<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // ESTA ES LA LÓGICA CLAVE:
        // Si la solicitud es para la API (espera una respuesta JSON) y falla,
        // no la redirigimos a ningún lado.
        if ($request->expectsJson()) {
            return null;
        }

        // Si es una solicitud web normal, la redirigimos a la ruta 'login'.
        return route('login');
    }
}