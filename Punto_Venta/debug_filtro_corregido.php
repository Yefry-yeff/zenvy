<?php
require_once 'vendor/autoload.php';

// Configurar Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Debug del filtro corregido
echo "=== DEBUG FILTRO CORREGIDO - POR c.tienda_id ===\n\n";

try {
    $userId = 1; // Usuario Johann Ruiz
    $fechaCierre = '2025-08-10'; // Fecha actual
    
    // 1. Verificar datos del usuario
    $usuario = DB::table('users as u')
        ->leftJoin('tienda as t', 'u.tienda_id', '=', 't.id')
        ->where('u.id', $userId)
        ->select('u.id', 'u.name', 'u.tienda_id', 't.denominacion_social')
        ->first();
    
    echo "👤 Usuario: {$usuario->name} (ID: {$usuario->id})\n";
    echo "🏪 Tienda del usuario: {$usuario->tienda_id} ({$usuario->denominacion_social})\n\n";
    
    // 2. Verificar estructura de la tabla caja
    echo "=== ESTRUCTURA TABLA CAJA ===\n";
    $columns = DB::select("DESCRIBE caja");
    foreach ($columns as $column) {
        echo "  {$column->Field}: {$column->Type}\n";
    }
    
    // 3. Mostrar todas las cajas con su tienda_id
    echo "\n=== TODAS LAS CAJAS CON TIENDA_ID ===\n";
    $todasCajas = DB::table('caja as c')
        ->join('users as u', 'c.users_id', '=', 'u.id')
        ->whereDate('c.created_at', $fechaCierre)
        ->select('c.id', 'c.tienda_id as caja_tienda', 'c.balance', 'c.estado_caja', 'u.name as usuario_nombre', 'u.tienda_id as usuario_tienda')
        ->orderBy('c.id')
        ->get();
    
    foreach ($todasCajas as $caja) {
        $estado = $caja->estado_caja == 1 ? "ABIERTA" : "CERRADA";
        $balance = number_format($caja->balance, 2);
        echo "  Caja #{$caja->id}: {$caja->usuario_nombre} | Caja Tienda: {$caja->caja_tienda} | Usuario Tienda: {$caja->usuario_tienda} | Estado: {$estado} | Balance: L. {$balance}\n";
    }
    
    // 4. Ejecutar la consulta CORREGIDA para cajas abiertas
    echo "\n=== CONSULTA CORREGIDA - FILTRO POR c.tienda_id ===\n";
    echo "Filtrar donde c.tienda_id = {$usuario->tienda_id}\n\n";
    
    $cajasAbiertas = DB::table('caja as c')
        ->join('users as u', 'c.users_id', '=', 'u.id')
        ->where('c.estado_caja', 1)
        ->where('c.tienda_id', $usuario->tienda_id)
        ->whereDate('c.created_at', $fechaCierre)
        ->select('c.*', 'u.name as nombre_usuario')
        ->get();
    
    echo "Resultados de la consulta corregida:\n";
    if ($cajasAbiertas->count() > 0) {
        foreach ($cajasAbiertas as $caja) {
            echo "  ✅ Caja #{$caja->id}: {$caja->nombre_usuario} | Caja Tienda: {$caja->tienda_id} | Balance: L. " . number_format($caja->balance, 2) . "\n";
        }
    } else {
        echo "  ❌ No se encontraron cajas abiertas para la tienda {$usuario->tienda_id}\n";
    }
    
    // 5. Comparar con la consulta anterior (por u.tienda_id)
    echo "\n=== COMPARACIÓN - CONSULTA ANTERIOR (u.tienda_id) ===\n";
    $cajasAbiertasAnterior = DB::table('caja as c')
        ->join('users as u', 'c.users_id', '=', 'u.id')
        ->where('c.estado_caja', 1)
        ->where('u.tienda_id', $usuario->tienda_id)
        ->whereDate('c.created_at', $fechaCierre)
        ->select('c.*', 'u.name as nombre_usuario')
        ->get();
    
    echo "Resultados de la consulta anterior (u.tienda_id):\n";
    if ($cajasAbiertasAnterior->count() > 0) {
        foreach ($cajasAbiertasAnterior as $caja) {
            echo "  📊 Caja #{$caja->id}: {$caja->nombre_usuario} | Caja Tienda: {$caja->tienda_id} | Balance: L. " . number_format($caja->balance, 2) . "\n";
        }
    } else {
        echo "  ❌ No se encontraron cajas abiertas con consulta anterior\n";
    }
    
    echo "\n=== CONCLUSIÓN ===\n";
    echo "Consulta CORREGIDA (c.tienda_id): " . $cajasAbiertas->count() . " cajas\n";
    echo "Consulta ANTERIOR (u.tienda_id): " . $cajasAbiertasAnterior->count() . " cajas\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
}
