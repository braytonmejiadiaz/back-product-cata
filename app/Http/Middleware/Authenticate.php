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
        // Para rutas API (/api/*), siempre retorna null (no redirige)
        if ($request->is('api/*')) {
            return null; // Laravel automáticamente devolverá un 401 JSON
        }

        return $request->expectsJson() ? null : route('login');
    }
}
