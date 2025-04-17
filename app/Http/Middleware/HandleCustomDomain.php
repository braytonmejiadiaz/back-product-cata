<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Models\CustomDomain;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;

class HandleCustomDomain
{
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();
        $mainDomain = 'app.treggio.co';

        // Si es un dominio personalizado
        if ($host !== $mainDomain) {
            $user = Cache::remember("user_for_domain:$host", 3600, function() use ($host) {
                return CustomDomain::where('domain', $host)
                    ->where('is_verified', true)
                    ->first()
                    ?->user;
            });

            if (!$user) abort(404);

            $request->merge(['current_store_owner' => $user]);
            View::share('current_store_owner', $user);
        }

        return $next($request);
    }
}
