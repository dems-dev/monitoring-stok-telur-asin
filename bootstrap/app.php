<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        // Diperiksa di setiap request web, bukan hanya saat login.
        $middleware->web(append: [
            EnsureUserIsActive::class,
        ]);

        $middleware->redirectGuestsTo('/login');

        // Hosting seperti Railway meneruskan HTTPS lewat reverse proxy; percayai
        // header X-Forwarded-* agar URL aset & redirect tetap memakai https.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
