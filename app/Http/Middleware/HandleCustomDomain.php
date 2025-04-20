<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\CustomDomain;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;

class HandleCustomDomain
{
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();
        $mainDomain = config('app.main_domain', 'app.treggio.co');

        // Ignorar para el dominio principal y localhost
        if ($host === $mainDomain || str_contains($host, 'localhost')) {
            return $next($request);
        }

        // Buscar usuario asociado al dominio
        $user = Cache::remember("user_for_domain:{$host}", 3600, function() use ($host) {
            return CustomDomain::with('user')
                ->verified()
                ->where('domain', $host)
                ->first()
                ?->user;
        });

        if (!$user) {
            // Redirigir al dominio principal con mensaje
            return redirect()->to("https://{$mainDomain}")->with('error', 'Dominio no configurado correctamente');
        }

        // Configurar el contexto para la solicitud
        $request->merge([
            'current_store_owner' => $user,
            'is_custom_domain' => true,
            'store_slug' => $user->slug
        ]);

        View::share([
            'current_store_owner' => $user,
            'is_custom_domain' => true,
            'store_slug' => $user->slug
        ]);

        // Forzar URLs para que Laravel genere enlaces correctamente
        URL::forceRootUrl("https://{$host}");
        URL::forceScheme('https');

        // Modificar parámetros de ruta para /tienda/{slug}
        if ($request->is('tienda/*')) {
            $request->route()->setParameter('slug', $user->slug);
        }

        return $next($request);
    }
}
