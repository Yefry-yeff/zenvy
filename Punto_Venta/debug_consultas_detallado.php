<?php
require_once 'vendor/autoload.php';

// Configurar Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Debug específico para verificar las consultas de cierre de jornada
echo "=== DEBUG ESPECÍFICO - CONSULTAS CIERRE DE JORNADA ===\n\n";

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
    
    // 2. Mostrar todas las cajas del día con sus tiendas
    echo "=== TODAS LAS CAJAS DEL DÍA ===\n";
    $todasCajas = DB::table('caja as c')
        ->join('users as u', 'c.users_id', '=', 'u.id')
        ->whereDate('c.created_at', $fechaCierre)
        ->select('c.id', 'c.balance', 'c.estado_caja', 'u.name as usuario_nombre', 'u.tienda_id')
        ->orderBy('c.id')
        ->get();
    
    foreach ($todasCajas as $caja) {
        $estado = $caja->estado_caja == 1 ? "ABIERTA" : "CERRADA";
        $balance = number_format($caja->balance, 2);
        echo "  Caja #{$caja->id}: {$caja->usuario_nombre} | Tienda: {$caja->tienda_id} | Estado: {$estado} | Balance: L. {$balance}\n";
    }
    
    // 3. Ejecutar la consulta exacta del componente para cajas abiertas
    echo "\n=== CONSULTA DEL COMPONENTE - CAJAS ABIERTAS ===\n";
    echo "Consulta SQL que usa el componente:\n";
    
    $sql = "SELECT c.*, u.name as nombre_usuario, u.tienda_id as user_tienda_id 
            FROM caja as c 
            JOIN users as u ON c.users_id = u.id 
            WHERE c.estado_caja = 1 
            AND u.tienda_id = ? 
            AND DATE(c.created_at) = ?";
    
    echo "SQL: $sql\n";
    echo "Parámetros: tienda_id = {$usuario->tienda_id}, fecha = {$fechaCierre}\n\n";
    
    $cajasAbiertas = DB::table('caja as c')
        ->join('users as u', 'c.users_id', '=', 'u.id')
        ->where('c.estado_caja', 1)
        ->where('u.tienda_id', $usuario->tienda_id)
        ->whereDate('c.created_at', $fechaCierre)
        ->select('c.*', 'u.name as nombre_usuario', 'u.tienda_id as user_tienda_id')
        ->get();
    
    echo "Resultados de la consulta:\n";
    if ($cajasAbiertas->count() > 0) {
        foreach ($cajasAbiertas as $caja) {
            echo "  ✅ Caja #{$caja->id}: {$caja->nombre_usuario} | Usuario tienda: {$caja->user_tienda_id} | Balance: L. " . number_format($caja->balance, 2) . "\n";
        }
    } else {
        echo "  ❌ No se encontraron cajas abiertas\n";
    }
    
    // 4. Verificar si hay un problema con la tabla de relaciones
    echo "\n=== VERIFICACIÓN DE RELACIONES ===\n";
    echo "Verificando si existe inconsistencia en los datos...\n\n";
    
    // Verificar cajas que podrían estar mal relacionadas
    $cajasProblematicas = DB::select("
        SELECT c.id as caja_id, 
               c.users_id, 
               u.name as usuario_nombre,
               u.tienda_id as usuario_tienda,
               c.created_at as caja_fecha
        FROM caja c
        JOIN users u ON c.users_id = u.id
        WHERE c.id IN (1, 2, 5)
        ORDER BY c.id
    ");
    
    foreach ($cajasProblematicas as $caja) {
        echo "  Caja #{$caja->caja_id}: Usuario {$caja->usuario_nombre} (ID: {$caja->users_id}) | Tienda: {$caja->usuario_tienda} | Fecha: {$caja->caja_fecha}\n";
    }
    
    // 5. Verificar si el usuario cambió de tienda recientemente
    echo "\n=== VERIFICACIÓN HISTÓRICA DEL USUARIO ===\n";
    $historialUsuario = DB::table('users')
        ->where('id', $userId)
        ->select('id', 'name', 'tienda_id', 'created_at', 'updated_at')
        ->first();
    
    echo "Usuario actual: {$historialUsuario->name}\n";
    echo "Tienda actual: {$historialUsuario->tienda_id}\n";
    echo "Cuenta creada: {$historialUsuario->created_at}\n";
    echo "Última actualización: {$historialUsuario->updated_at}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
}
