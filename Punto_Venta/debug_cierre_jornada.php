<?php
require_once 'vendor/autoload.php';

// Configurar Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// Debug del cierre de jornada - verificar filtrado por tienda
echo "=== DEBUG CIERRE DE JORNADA - FILTRADO POR TIENDA ===\n\n";

try {
    // Simular usuario autenticado (cambiar ID según necesites)
    $userId = 1; // Cambiar por el ID del usuario que está teniendo el problema
    
    // Obtener datos del usuario
    $usuario = DB::table('users as u')
        ->leftJoin('tienda as t', 'u.tienda_id', '=', 't.id')
        ->where('u.id', $userId)
        ->select('u.id', 'u.name', 'u.tienda_id', 't.denominacion_social')
        ->first();
    
    if (!$usuario) {
        echo "❌ No se encontró el usuario con ID: $userId\n";
        exit;
    }
    
    echo "👤 Usuario: {$usuario->name} (ID: {$usuario->id})\n";
    echo "🏪 Tienda: {$usuario->denominacion_social} (ID: {$usuario->tienda_id})\n\n";
    
    $fechaCierre = date('Y-m-d'); // Hoy
    
    // 1. Verificar cajas abiertas como lo hace el componente
    echo "=== CAJAS ABIERTAS (Estado = 1) ===\n";
    $cajasAbiertas = DB::table('caja as c')
        ->join('users as u', 'c.users_id', '=', 'u.id')
        ->where('c.estado_caja', 1)
        ->where('u.tienda_id', $usuario->tienda_id)
        ->whereDate('c.created_at', $fechaCierre)
        ->select('c.*', 'u.name as nombre_usuario', 'u.tienda_id')
        ->get();
    
    if ($cajasAbiertas->count() > 0) {
        foreach ($cajasAbiertas as $caja) {
            echo "  📦 Caja ID: {$caja->id} | Usuario: {$caja->nombre_usuario} | Tienda: {$caja->tienda_id} | Estado: {$caja->estado_caja}\n";
        }
    } else {
        echo "  ✅ No hay cajas abiertas en esta tienda\n";
    }
    
    // 2. Verificar cajas con diferencias
    echo "\n=== CAJAS CON DIFERENCIAS ===\n";
    $cajasConDiferencia = DB::table('cierre_de_caja as cc')
        ->join('caja as c', 'cc.caja_id', '=', 'c.id')
        ->join('users as u', 'c.users_id', '=', 'u.id')
        ->where('cc.diferencia_efectivo', '!=', 0)
        ->where('u.tienda_id', $usuario->tienda_id)
        ->whereDate('cc.created_at', $fechaCierre)
        ->select(
            'c.id', 
            'c.users_id', 
            'u.name as nombre_usuario',
            'u.tienda_id',
            'cc.diferencia_efectivo', 
            'cc.created_at'
        )
        ->get();
    
    if ($cajasConDiferencia->count() > 0) {
        foreach ($cajasConDiferencia as $caja) {
            echo "  💰 Caja ID: {$caja->id} | Usuario: {$caja->nombre_usuario} | Tienda: {$caja->tienda_id} | Diferencia: {$caja->diferencia_efectivo}\n";
        }
    } else {
        echo "  ✅ No hay cajas con diferencias en esta tienda\n";
    }
    
    // 3. Verificar si hay datos de otras tiendas que podrían estar apareciendo
    echo "\n=== VERIFICACIÓN: TODAS LAS CAJAS DEL DÍA (TODAS LAS TIENDAS) ===\n";
    $todasLasCajas = DB::table('caja as c')
        ->join('users as u', 'c.users_id', '=', 'u.id')
        ->whereDate('c.created_at', $fechaCierre)
        ->select('c.id', 'c.estado_caja', 'u.name as nombre_usuario', 'u.tienda_id')
        ->orderBy('u.tienda_id')
        ->get();
    
    $tiendas = [];
    foreach ($todasLasCajas as $caja) {
        $tiendas[$caja->tienda_id][] = $caja;
    }
    
    foreach ($tiendas as $tiendaId => $cajas) {
        $count = count($cajas);
        $esTiendaActual = ($tiendaId == $usuario->tienda_id) ? "⭐ (ACTUAL)" : "";
        echo "  🏪 Tienda $tiendaId $esTiendaActual: $count cajas\n";
        
        foreach ($cajas as $caja) {
            $estado = $caja->estado_caja == 1 ? "ABIERTA" : "CERRADA";
            echo "    - Caja {$caja->id}: {$caja->nombre_usuario} ({$estado})\n";
        }
    }
    
    // 4. Verificar si hay algún problema en la consulta o la lógica
    echo "\n=== VERIFICACIÓN ADICIONAL ===\n";
    echo "Fecha de cierre utilizada: $fechaCierre\n";
    echo "Tienda del usuario: {$usuario->tienda_id}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
}
