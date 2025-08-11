<?php
require_once 'vendor/autoload.php';

// Configurar Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Verificar las tiendas disponibles
echo "=== VERIFICACIÓN TIENDAS DISPONIBLES ===\n\n";

try {
    // 1. Verificar estructura de tabla tienda
    echo "=== ESTRUCTURA TABLA TIENDA ===\n";
    $columns = DB::select("DESCRIBE tienda");
    foreach ($columns as $column) {
        echo "  {$column->Field}: {$column->Type}\n";
    }
    
    // 2. Mostrar tiendas activas disponibles
    echo "\n=== TIENDAS ACTIVAS DISPONIBLES ===\n";
    $tiendas = DB::table('tienda')
        ->where('estado_id', 1)
        ->select('id', 'denominacion_social', 'estado_id')
        ->orderBy('denominacion_social')
        ->get();
    
    if ($tiendas->count() > 0) {
        foreach ($tiendas as $tienda) {
            echo "  ID: {$tienda->id} | Nombre: {$tienda->denominacion_social} | Estado: {$tienda->estado_id}\n";
        }
    } else {
        echo "  ❌ No hay tiendas activas disponibles\n";
    }
    
    // 3. Verificar estructura de tabla users
    echo "\n=== ESTRUCTURA TABLA USERS ===\n";
    $userColumns = DB::select("DESCRIBE users");
    foreach ($userColumns as $column) {
        echo "  {$column->Field}: {$column->Type} | Null: {$column->Null} | Default: {$column->Default}\n";
    }
    
    // 4. Verificar si hay usuarios sin tienda_id
    echo "\n=== USUARIOS SIN TIENDA ASIGNADA ===\n";
    $usuariosSinTienda = DB::table('users')
        ->whereNull('tienda_id')
        ->select('id', 'name', 'email', 'tienda_id')
        ->get();
    
    if ($usuariosSinTienda->count() > 0) {
        echo "  ⚠️ Usuarios que necesitan tienda asignada:\n";
        foreach ($usuariosSinTienda as $user) {
            echo "    - ID: {$user->id} | Nombre: {$user->name} | Email: {$user->email}\n";
        }
    } else {
        echo "  ✅ Todos los usuarios tienen tienda asignada\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
}
