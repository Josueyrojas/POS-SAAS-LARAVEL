<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->role === UserRole::SUPER_ADMIN || ! $user->business_id) {
            abort(403);
        }

        // Fija el inquilino activo: desde aquí, TODA consulta de un modelo con
        // BelongsToBusiness queda filtrada por este business_id.
        app(TenantContext::class)->set($user->business_id);

        return $next($request);
    }
}
