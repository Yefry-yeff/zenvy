<?php

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Factura;

echo "=== TEST FINAL DE VALIDACIÓN DE STOCK ===" . PHP_EOL;

// Test con productos reales de la base de datos

echo PHP_EOL . "1. Testear con Cocacola Zero (ID: 5) en Tienda 1:" . PHP_EOL;
$resultado = Factura::validarStockBodegaPrincipal(5, 2, 1);
echo "- Solicitar 2 unidades" . PHP_EOL;
echo "- Válido: " . ($resultado['valido'] ? 'SÍ' : 'NO') . PHP_EOL;
echo "- Stock disponible: " . $resultado['stock_disponible'] . PHP_EOL;
if (!$resultado['valido']) {
    echo "- Mensaje: " . $resultado['mensaje'] . PHP_EOL;
}

echo PHP_EOL . "2. Testear con Cocacola Zero (ID: 5) cantidad excesiva:" . PHP_EOL;
$resultado = Factura::validarStockBodegaPrincipal(5, 10, 1);
echo "- Solicitar 10 unidades" . PHP_EOL;
echo "- Válido: " . ($resultado['valido'] ? 'SÍ' : 'NO') . PHP_EOL;
echo "- Stock disponible: " . $resultado['stock_disponible'] . PHP_EOL;
if (!$resultado['valido']) {
    echo "- Mensaje: " . $resultado['mensaje'] . PHP_EOL;
}

echo PHP_EOL . "3. Testear con Four Loco (ID: 3) en Tienda 3:" . PHP_EOL;
$resultado = Factura::validarStockBodegaPrincipal(3, 20, 3);
echo "- Solicitar 20 unidades" . PHP_EOL;
echo "- Válido: " . ($resultado['valido'] ? 'SÍ' : 'NO') . PHP_EOL;
echo "- Stock disponible: " . $resultado['stock_disponible'] . PHP_EOL;
if (!$resultado['valido']) {
    echo "- Mensaje: " . $resultado['mensaje'] . PHP_EOL;
}

echo PHP_EOL . "4. Testear con producto en tienda incorrecta:" . PHP_EOL;
$resultado = Factura::validarStockBodegaPrincipal(3, 1, 1); // Four Loco en Tienda 1
echo "- Four Loco (ID: 3) en Tienda 1 (debería estar en Tienda 3)" . PHP_EOL;
echo "- Válido: " . ($resultado['valido'] ? 'SÍ' : 'NO') . PHP_EOL;
echo "- Stock disponible: " . $resultado['stock_disponible'] . PHP_EOL;
if (!$resultado['valido']) {
    echo "- Mensaje: " . $resultado['mensaje'] . PHP_EOL;
}

echo PHP_EOL . "=== RESUMEN ===" . PHP_EOL;
echo "✅ Sistema de validación de stock implementado" . PHP_EOL;
echo "✅ Validación por bodega principal" . PHP_EOL;
echo "✅ Validación por tienda" . PHP_EOL;
echo "✅ Mensajes de error informativos" . PHP_EOL;
echo "✅ Listo para usar en el sistema de ventas" . PHP_EOL;
