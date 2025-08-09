<?php

require_once 'vendor/autoload.php';

// Configurar el entorno de Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== AGREGAR NUEVO CAI CORRECTO ===\n\n";

try {
    // 1. Crear nuevo CAI con campos correctos
    echo "1. Creando nuevo CAI...\n";
    $caiId = DB::table('cai')->insertGetId([
        'cai' => 'B2C3-D4E5-F6G7-H8I9-J0K1-L234',
        'fecha_limite_emision' => date('Y-m-d', strtotime('+2 years')),
        'fecha_solicitud' => date('Y-m-d'),
        'punto_emision' => 'Punto de Venta Sistema',
        'tipo_documento_fiscal_id' => 1,
        'cantidad_solicitada' => 1000,
        'cantidad_otorgada' => 1000,
        'rango_inicio' => '000-002-02-00000001',
        'rango_final' => '000-002-02-00001000',
        'tienda_id' => 1,
        'users_registro_id' => 2,
        'estado_id' => 1,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    echo "CAI creado con ID: $caiId\n";

    // 2. Crear gestión CAI
    echo "\n2. Creando gestión CAI...\n";
    $gestionId = DB::table('gestion_cai')->insertGetId([
        'cai_id' => $caiId,
        'numero_base' => '000-002-02-',
        'numero_actual' => 1,
        'cantidad_no_utilizada' => 1000,
        'estado_id' => 1,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    echo "Gestión CAI creada con ID: $gestionId\n";

    // 3. Verificar información
    echo "\n3. Información de CAIs disponibles:\n";
    $informacion = DB::table('gestion_cai as gc')
        ->join('cai as c', 'gc.cai_id', '=', 'c.id')
        ->join('estado as e', 'gc.estado_id', '=', 'e.id')
        ->select('gc.*', 'c.cai', 'c.fecha_limite_emision', 'e.descripcion as estado')
        ->where('gc.estado_id', 1)
        ->where('gc.cantidad_no_utilizada', '>', 0)
        ->orderBy('gc.id', 'asc')
        ->get();

    foreach ($informacion as $cai) {
        echo "Gestión ID: {$cai->id} | CAI ID: {$cai->cai_id} | Base: {$cai->numero_base} | Actual: {$cai->numero_actual} | Restantes: {$cai->cantidad_no_utilizada} | Estado: {$cai->estado}\n";
        echo "CAI: {$cai->cai} | Fecha límite: {$cai->fecha_limite_emision}\n";
        echo "---\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN ===\n";
