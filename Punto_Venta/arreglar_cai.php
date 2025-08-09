<?php

require_once 'vendor/autoload.php';

// Configurar el entorno de Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICAR Y ARREGLAR CAI ===\n\n";

try {
    // 1. Ver información detallada de CAI
    echo "1. Información detallada de CAI:\n";
    $cais = DB::table('cai')->get();
    foreach ($cais as $cai) {
        echo "CAI ID: {$cai->id}\n";
        echo "CAI: {$cai->cai}\n";
        echo "Fecha límite: {$cai->fecha_limite_emision}\n";
        echo "Fecha actual: " . date('Y-m-d') . "\n";
        echo "Vencido: " . ($cai->fecha_limite_emision < date('Y-m-d') ? "SÍ" : "NO") . "\n";
        echo "---\n";
    }

    // 2. Ver gestión CAI
    echo "\n2. Gestión CAI:\n";
    $gestionCais = DB::table('gestion_cai as gc')
        ->join('cai as c', 'gc.cai_id', '=', 'c.id')
        ->join('estado as e', 'gc.estado_id', '=', 'e.id')
        ->select('gc.*', 'c.cai', 'c.fecha_limite_emision', 'e.descripcion as estado')
        ->get();

    foreach ($gestionCais as $gc) {
        echo "Gestión ID: {$gc->id}\n";
        echo "CAI ID: {$gc->cai_id}\n";
        echo "Número base: {$gc->numero_base}\n";
        echo "Número actual: {$gc->numero_actual}\n";
        echo "Cantidad no utilizada: {$gc->cantidad_no_utilizada}\n";
        echo "Estado: {$gc->estado}\n";
        echo "CAI: {$gc->cai}\n";
        echo "Fecha límite: {$gc->fecha_limite_emision}\n";
        echo "---\n";
    }

    // 3. Actualizar fecha límite del CAI para que no esté vencido
    echo "\n3. Actualizando fecha límite de CAI...\n";
    $fechaFutura = date('Y-m-d', strtotime('+1 year'));

    DB::table('cai')->update([
        'fecha_limite_emision' => $fechaFutura
    ]);

    echo "Fecha límite actualizada a: $fechaFutura\n";

    // 4. Reactivar gestión CAI
    echo "\n4. Reactivando gestión CAI...\n";
    DB::table('gestion_cai')->update([
        'estado_id' => 1 // Activo
    ]);

    echo "Gestión CAI reactivada\n";

    // 5. Verificar cambios
    echo "\n5. Verificando cambios:\n";
    $gestionCaisActualizados = DB::table('gestion_cai as gc')
        ->join('cai as c', 'gc.cai_id', '=', 'c.id')
        ->join('estado as e', 'gc.estado_id', '=', 'e.id')
        ->select('gc.*', 'c.cai', 'c.fecha_limite_emision', 'e.descripcion as estado')
        ->get();

    foreach ($gestionCaisActualizados as $gc) {
        echo "Gestión ID: {$gc->id} | Estado: {$gc->estado} | Fecha límite: {$gc->fecha_limite_emision} | Restantes: {$gc->cantidad_no_utilizada}\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN ===\n";
