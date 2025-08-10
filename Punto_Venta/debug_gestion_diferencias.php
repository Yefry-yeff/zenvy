<?php
require_once 'vendor/autoload.php';

// Configurar Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Debug del sistema de gestión de diferencias
echo "=== DEBUG GESTIÓN DE DIFERENCIAS - ACTUALIZACIÓN DIRECTA ===\n\n";

try {
    // 1. Verificar estructura de tabla cierre_de_caja
    echo "=== ESTRUCTURA TABLA CIERRE_DE_CAJA ===\n";
    $columns = DB::select("DESCRIBE cierre_de_caja");
    foreach ($columns as $column) {
        if (strpos($column->Field, 'diferencia') !== false || strpos($column->Field, 'efectivo') !== false) {
            echo "  {$column->Field}: {$column->Type} | Null: {$column->Null} | Default: {$column->Default}\n";
        }
    }
    
    // 2. Mostrar cierres con diferencias actuales
    echo "\n=== CIERRES CON DIFERENCIAS ACTUALES ===\n";
    $cierresConDiferencias = DB::table('cierre_de_caja as cc')
        ->join('caja as c', 'cc.caja_id', '=', 'c.id')
        ->join('users as u', 'c.users_id', '=', 'u.id')
        ->whereRaw('ABS(cc.diferencia_efectivo) >= 0.01')
        ->select(
            'cc.id as cierre_id',
            'c.id as caja_id',
            'u.name as usuario',
            'u.tienda_id',
            'cc.diferencia_efectivo',
            'cc.total_efectivo',
            'cc.conteo_efectivo',
            'cc.created_at'
        )
        ->orderBy('cc.created_at', 'desc')
        ->get();
    
    if ($cierresConDiferencias->count() > 0) {
        foreach ($cierresConDiferencias as $cierre) {
            $fecha = date('d/m/Y', strtotime($cierre->created_at));
            $diferencia = number_format($cierre->diferencia_efectivo, 2);
            $tipo = $cierre->diferencia_efectivo > 0 ? "SOBRANTE" : "FALTANTE";
            echo "  Cierre #{$cierre->cierre_id} | Caja #{$cierre->caja_id} | {$cierre->usuario} (Tienda {$cierre->tienda_id})\n";
            echo "    Diferencia: L. {$diferencia} ({$tipo}) | Fecha: {$fecha}\n";
            echo "    Total: L. " . number_format($cierre->total_efectivo, 2) . " | Conteo: L. " . number_format($cierre->conteo_efectivo, 2) . "\n\n";
        }
    } else {
        echo "  ✅ No hay cierres con diferencias pendientes\n";
    }
    
    // 3. Mostrar gestiones realizadas
    echo "\n=== GESTIONES DE DIFERENCIAS REALIZADAS ===\n";
    $gestiones = DB::table('gestion_diferencia as gd')
        ->join('cierre_de_caja as cc', 'gd.cierre_de_caja_id', '=', 'cc.id')
        ->join('caja as c', 'cc.caja_id', '=', 'c.id')
        ->join('users as u_caja', 'c.users_id', '=', 'u_caja.id')
        ->join('users as u_gestion', 'gd.users_id', '=', 'u_gestion.id')
        ->select(
            'gd.id as gestion_id',
            'gd.monto',
            'gd.descripcion',
            'cc.id as cierre_id',
            'c.id as caja_id',
            'u_caja.name as usuario_caja',
            'u_gestion.name as usuario_gestion',
            'gd.created_at'
        )
        ->orderBy('gd.created_at', 'desc')
        ->limit(10)
        ->get();
    
    if ($gestiones->count() > 0) {
        foreach ($gestiones as $gestion) {
            $fecha = date('d/m/Y H:i', strtotime($gestion->created_at));
            $monto = number_format($gestion->monto, 2);
            $tipo = $gestion->monto > 0 ? "AJUSTE POSITIVO" : "AJUSTE NEGATIVO";
            echo "  Gestión #{$gestion->gestion_id} | Cierre #{$gestion->cierre_id} | Caja #{$gestion->caja_id}\n";
            echo "    Monto: L. {$monto} ({$tipo}) | Fecha: {$fecha}\n";
            echo "    Usuario Caja: {$gestion->usuario_caja} | Gestor: {$gestion->usuario_gestion}\n";
            echo "    Descripción: {$gestion->descripcion}\n\n";
        }
    } else {
        echo "  ℹ️ No hay gestiones de diferencias registradas\n";
    }
    
    // 4. Simulación de cómo funciona ahora el sistema
    if ($cierresConDiferencias->count() > 0) {
        $primerCierre = $cierresConDiferencias->first();
        echo "\n=== SIMULACIÓN NUEVA LÓGICA ===\n";
        echo "Ejemplo con Cierre #{$primerCierre->cierre_id}:\n";
        echo "  Diferencia actual: L. " . number_format($primerCierre->diferencia_efectivo, 2) . "\n";
        echo "  Si se aplica un ajuste de L. 100.00:\n";
        echo "    Nueva diferencia = {$primerCierre->diferencia_efectivo} - 100 = " . ($primerCierre->diferencia_efectivo - 100) . "\n";
        echo "    El campo 'diferencia_efectivo' se actualizará directamente en la BD\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
}
