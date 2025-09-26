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
        // Middleware para forzar zona horaria de Honduras
        $middleware->web(append: [
            \App\Http\Middleware\TimezoneMiddleware::class,
        ]);
        
        // Middleware deshabilitado - componentes dinámicos manejan su propia limpieza
        // $middleware->web(append: [
        //     \App\Http\Middleware\CleanQueryParams::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
