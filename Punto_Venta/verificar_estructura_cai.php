<?php

require_once 'vendor/autoload.php';

// Configurar el entorno de Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICAR ESTRUCTURA DE TABLA CAI ===\n\n";

try {
    // Ver estructura de tabla cai
    echo "Estructura de tabla 'cai':\n";
    $columns = DB::select("DESCRIBE cai");
    foreach ($columns as $column) {
        echo "Campo: {$column->Field} | Tipo: {$column->Type} | Null: {$column->Null} | Default: {$column->Default}\n";
    }

    echo "\n";

    // Ver datos existentes
    echo "Datos existentes:\n";
    $datos = DB::table('cai')->get();
    foreach ($datos as $dato) {
        echo "ID: {$dato->id}\n";
        foreach ((array)$dato as $campo => $valor) {
            echo "  $campo: $valor\n";
        }
        echo "---\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN ===\n";
