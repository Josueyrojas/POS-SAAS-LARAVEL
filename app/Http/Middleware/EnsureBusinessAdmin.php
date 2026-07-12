<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Se aplica DESPUÉS de `business` (que ya validó la sesión y fijó el
 * TenantContext). Solo exige que el rol sea BUSINESS_ADMIN, para las
 * secciones administrativas del POS (proveedores, compras, empleados,
 * reportes, escritura de catálogo).
 */
class EnsureBusinessAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== UserRole::BUSINESS_ADMIN) {
            abort(403);
        }

        return $next($request);
    }
}
