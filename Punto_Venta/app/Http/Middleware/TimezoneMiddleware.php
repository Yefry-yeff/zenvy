<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class TimezoneMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Forzar zona horaria de Honduras en cada request
        $timezone = config('app.timezone', 'America/Tegucigalpa');
        
        // Configurar PHP
        date_default_timezone_set($timezone);
        
        // Configurar Carbon globalmente
        Carbon::setTestNow();
        
        // Procesar el request
        $response = $next($request);
        
        return $response;
    }
}