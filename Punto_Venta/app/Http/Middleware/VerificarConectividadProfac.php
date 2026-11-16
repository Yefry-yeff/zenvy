<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\SincronizacionMarcasService;
use Illuminate\Support\Facades\Cache;

class VerificarConectividadProfac
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Solo verificar en rutas relacionadas con productos/marcas
        if ($this->debeVerificarConectividad($request)) {
            $conectividad = Cache::remember('profac_conectividad', 60, function () {
                $service = new SincronizacionMarcasService();
                return $service->verificarConectividad();
            });

            if (!$conectividad['status']) {
                // Agregar alerta a la sesión si no hay conectividad
                session()->flash('warning', 
                    '⚠️ Sin conexión con el sistema externo de marcas. Usando datos locales.'
                );
            }
        }

        return $next($request);
    }

    /**
     * Determina si debe verificar la conectividad para esta ruta
     */
    private function debeVerificarConectividad(Request $request): bool
    {
        $rutasAVerificar = [
            'productos',
            'inventario',
            'marcas'
        ];

        foreach ($rutasAVerificar as $ruta) {
            if (str_contains($request->path(), $ruta)) {
                return true;
            }
        }

        return false;
    }
}
