<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap de Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Producto;

echo "=== Prueba de validación de código de barras ===\n";

// Probar si existe el código "760573020163"
$codigoTest = "760573020163";
$existe = Producto::where('codigo_barra', $codigoTest)->exists();

echo "Código a probar: $codigoTest\n";
echo "¿Existe en la base de datos? " . ($existe ? "SÍ" : "NO") . "\n";

if ($existe) {
    $producto = Producto::where('codigo_barra', $codigoTest)->first();
    echo "Producto encontrado: ID {$producto->id}, Nombre: {$producto->nombre}\n";
}

echo "\n=== Fin de la prueba ===\n";
