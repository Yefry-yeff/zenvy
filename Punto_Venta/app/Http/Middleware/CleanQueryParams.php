<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CleanQueryParams
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Solo aplicar en la ruta dashboard
        if ($request->is('dashboard')) {
            $parametros = $request->query();
            
            // Si hay parámetros en la URL, verificar si hay un componente activo válido
            if (!empty($parametros)) {
                $componenteActual = session('current_component');
                
                // Mapeo de parámetros específicos por componente
                $parametrosEspecificos = [
                    'compra-de-productos' => [
                        'busqueda', 'filtroEstado', 'filtroFecha', 'ordenarPor', 'direccionOrden', 'page'
                    ],
                    'lista-de-productos' => [
                        'filtroProducto', 'filtroBodega', 'filtroEstado', 'filtroMarca', 'ordenarPor', 'direccionOrden', 'page'
                    ]
                ];
                
                // Si no hay componente activo o los parámetros no coinciden con ningún componente
                $parametrosActuales = array_keys($parametros);
                $esValido = false;
                
                foreach ($parametrosEspecificos as $componente => $parametrosPermitidos) {
                    $parametrosNoPermitidos = array_diff($parametrosActuales, $parametrosPermitidos);
                    if (empty($parametrosNoPermitidos)) {
                        $esValido = true;
                        // Actualizar el componente actual si coincide
                        if ($componenteActual !== $componente) {
                            session(['current_component' => $componente]);
                        }
                        break;
                    }
                }
                
                // Si los parámetros no son válidos para ningún componente, limpiar
                if (!$esValido) {
                    session()->forget('current_component');
                    return redirect()->route('dashboard');
                }
            } else {
                // Si no hay parámetros, limpiar el componente actual
                session()->forget('current_component');
            }
        }
        
        return $next($request);
    }
    
    /**
     * Verificar si se deben limpiar los parámetros basado en el componente actual
     */
    private function deberiaLimpiarParametros($parametros, $componenteActual)
    {
        // Definir parámetros válidos por componente
        $parametrosValidos = [
            'compra-de-productos' => ['busqueda', 'filtroEstado', 'filtroFecha', 'ordenarPor', 'direccionOrden', 'page'],
            'lista-de-productos' => ['filtroProducto', 'filtroBodega', 'filtroEstado', 'filtroMarca', 'ordenarPor', 'direccionOrden', 'page']
        ];
        
        if (!isset($parametrosValidos[$componenteActual])) {
            return true; // Limpiar si el componente no está definido
        }
        
        // Verificar si hay parámetros no válidos para este componente
        $parametrosExtras = array_diff(array_keys($parametros), $parametrosValidos[$componenteActual]);
        return !empty($parametrosExtras);
    }
}