<?php

use App\Http\Middleware\EnsureBusinessAdmin;
use App\Http\Middleware\EnsureBusinessUser;
use App\Http\Middleware\EnsureOpenCashSession;
use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Render (y la mayoría de PaaS) termina el HTTPS en su balanceador y
        // reenvía la petición al contenedor por HTTP plano, marcando el
        // esquema original en X-Forwarded-Proto. Sin confiar en ese header,
        // Laravel genera todas sus URLs (incluida la acción de los <form>)
        // como http://, lo que el navegador marca como envío inseguro. El
        // contenedor solo es alcanzable a través del balanceador de Render,
        // así que confiar en cualquier proxy aquí es seguro.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'superadmin' => EnsureSuperAdmin::class,
            'business' => EnsureBusinessUser::class,
            'business.admin' => EnsureBusinessAdmin::class,
            'cash.session' => EnsureOpenCashSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
