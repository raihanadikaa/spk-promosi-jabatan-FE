<?php

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
        // 1. Percayai proxy Render (untuk HTTPS)
        $middleware->trustProxies(at: '*');
        
        // 2. Daftarkan alias middleware 'role'
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'idle' => \App\Http\Middleware\IdleTimeout::class,
        ]);

        // 3. Header keamanan (CSP, anti-clickjacking, dll) untuk semua halaman web
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
    // dhjahdjkdh