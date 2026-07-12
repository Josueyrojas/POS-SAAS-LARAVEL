<?php

namespace App\Http\Middleware;

use App\Enums\CashSessionStatus;
use App\Models\CashSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Se aplica después de `business` (ya fijó el TenantContext). Exige que el
 * usuario tenga un turno de caja abierto antes de poder vender — igual que
 * un POS físico: no se cobra sin abrir la caja primero.
 */
class EnsureOpenCashSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $hasOpenSession = CashSession::where('user_id', $request->user()->id)
            ->where('status', CashSessionStatus::OPEN->value)
            ->exists();

        if (! $hasOpenSession) {
            return redirect()->route('pos.cash-sessions.create')
                ->with('status', 'Abre tu turno de caja para empezar a vender.');
        }

        return $next($request);
    }
}
