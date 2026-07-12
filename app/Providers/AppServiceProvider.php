<?php

namespace App\Providers;

use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Un único TenantContext por petición: lo comparten el middleware
        // (que lo fija) y el BusinessScope (que lo lee).
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        // Gates de grano grueso para ocultar botones/columnas dentro de páginas
        // que ven ambos roles (admin/empleado). El bloqueo real de rutas
        // administrativas completas lo hace el middleware `business.admin`;
        // estos Gates son la segunda capa (defensa en profundidad en la vista).
        Gate::define('manage-products', fn (User $user) => $user->isAdmin());
        Gate::define('view-cost-price', fn (User $user) => $user->isAdmin());
        Gate::define('void-sale', fn (User $user) => $user->isAdmin());
        Gate::define('refund-sale', fn (User $user) => $user->isAdmin());

        // 5 intentos/minuto por email+IP: el login no tenía ningún freno a
        // fuerza bruta. Se limita por combinación (no solo IP) para que un
        // atacante con IPs rotativas no pueda evitarlo probando muchos
        // emails, ni un usuario legítimo bloquee a otros en la misma IP.
        RateLimiter::for('login', function ($request) {
            $key = strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });
    }
}
