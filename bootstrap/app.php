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
    ->withMiddleware(function (Middleware $middleware): void {
        // Percaya pada proxy (cloudflared tunnel) agar skema HTTPS & IP klien terbaca benar
        $middleware->trustProxies(at: '*');
        // Alias middleware hak akses (spatie permission, nama Indonesia)
        $middleware->alias([
            'hak_akses' => \App\Http\Middleware\HakAkses::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
