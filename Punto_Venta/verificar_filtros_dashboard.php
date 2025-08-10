<?php
require_once 'vendor/autoload.php';

// Configurar la aplicación Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "=== VERIFICACIÓN DE FILTROS DE DASHBOARD ===\n\n";
    
    // Obtener usuarios y sus tiendas
    echo "📋 USUARIOS Y SUS TIENDAS:\n";
    $usuarios = DB::table('users as u')
        ->leftJoin('tienda as t', 'u.tienda_id', '=', 't.id')
        ->join('roles as r', 'u.roles_id', '=', 'r.id')
        ->select('u.id', 'u.name', 'u.tienda_id', 't.denominacion_social', 'r.txt_nombre as rol')
        ->get();
    
    foreach ($usuarios as $user) {
        echo "  - {$user->name} (ID: {$user->id}) - Rol: {$user->rol}\n";
        echo "    Tienda ID: " . ($user->tienda_id ?: 'NULL') . " - " . ($user->denominacion_social ?: 'Sin tienda') . "\n\n";
    }
    
    echo "🏭 BODEGAS POR TIENDA:\n";
    $bodegas = DB::table('bodega as b')
        ->leftJoin('tienda as t', 'b.tienda_id', '=', 't.id')
        ->select('b.id', 'b.nombre as bodega', 'b.tienda_id', 't.denominacion_social')
        ->orderBy('b.tienda_id')
        ->get();
    
    $bodegasPorTienda = $bodegas->groupBy('tienda_id');
    foreach ($bodegasPorTienda as $tiendaId => $bodegas) {
        $nombreTienda = $bodegas->first()->denominacion_social ?: 'Sin tienda';
        echo "  Tienda ID {$tiendaId} ({$nombreTienda}):\n";
        foreach ($bodegas as $bodega) {
            echo "    - {$bodega->bodega} (ID: {$bodega->id})\n";
        }
        echo "\n";
    }
    
    echo "📦 PRODUCTOS CON STOCK BAJO POR TIENDA:\n";
    
    // Simular para cada usuario no admin
    $usuariosNoAdmin = $usuarios->filter(function($user) {
        return !in_array($user->rol, ['Admin', 'Administrador']);
    });
    
    foreach ($usuariosNoAdmin as $user) {
        echo "\n  Usuario: {$user->name} (Tienda ID: {$user->tienda_id})\n";
        
        if ($user->tienda_id) {
            $stockBajo = DB::table('recibido_bodega as rb')
                ->join('producto as p', 'rb.producto_id', '=', 'p.id')
                ->join('seccion as s', 'rb.seccion_id', '=', 's.id')
                ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
                ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
                ->where('rb.cantidad_disponible', '<', 10)
                ->where('rb.cantidad_disponible', '>', 0)
                ->where('b.tienda_id', $user->tienda_id)
                ->select('p.nombre', 'rb.cantidad_disponible', 'b.nombre as bodega')
                ->get();
            
            if ($stockBajo->count() > 0) {
                foreach ($stockBajo as $item) {
                    echo "    - {$item->nombre}: {$item->cantidad_disponible} unidades ({$item->bodega})\n";
                }
            } else {
                echo "    ✅ No hay productos con stock bajo en esta tienda\n";
            }
        } else {
            echo "    ❌ Usuario sin tienda asignada\n";
        }
    }
    
    echo "\n� RECEPCIONES DEL MES POR USUARIO:\n";
    
    foreach ($usuariosNoAdmin as $user) {
        $recepcionesMes = DB::table('recibido_bodega')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('users_registro_id', $user->id)
            ->count();
        
        echo "  - {$user->name}: {$recepcionesMes} recepciones este mes\n";
    }
    
    echo "\n✅ FILTROS IMPLEMENTADOS:\n";
    echo "  📍 Stock bajo: Solo bodegas de la tienda del usuario (excepto Admin)\n";
    echo "  � Recepciones del mes: Solo recepciones realizadas por el usuario (excepto Admin)\n";
    echo "  👑 Admin: Ve todos los datos sin filtros\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
