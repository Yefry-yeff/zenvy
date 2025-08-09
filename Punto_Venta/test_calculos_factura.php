<?php

// Simular productos en la factura para verificar cálculos
$productosFactura = [
    [
        'id' => 1,
        'nombre' => 'Producto Test 1',
        'codigo' => 'TEST001',
        'precio' => 50.00,
        'cantidad' => 2,
        'isv' => 15
    ],
    [
        'id' => 2,
        'nombre' => 'Producto Test 2',
        'codigo' => 'TEST002',
        'precio' => 12.00,
        'cantidad' => 1,
        'isv' => 15
    ]
];

echo "=== CÁLCULO POR PRODUCTO ===" . PHP_EOL;
$subtotalGeneral = 0;
$totalIsvGeneral = 0;
$isvPorTasa = [];

foreach ($productosFactura as $index => $producto) {
    echo PHP_EOL . "Producto " . ($index + 1) . ": " . $producto['nombre'] . PHP_EOL;
    echo "Precio unitario: L. " . number_format($producto['precio'], 2) . PHP_EOL;
    echo "Cantidad: " . $producto['cantidad'] . PHP_EOL;
    echo "Tasa ISV: " . $producto['isv'] . "%" . PHP_EOL;
    
    // Cálculo individual del producto
    $subtotalProducto = $producto['precio'] * $producto['cantidad'];
    $isvProducto = $subtotalProducto * ($producto['isv'] / 100);
    $totalProducto = $subtotalProducto + $isvProducto;
    
    echo "Subtotal producto: L. " . number_format($subtotalProducto, 2) . PHP_EOL;
    echo "ISV producto: L. " . number_format($isvProducto, 2) . PHP_EOL;
    echo "Total producto: L. " . number_format($totalProducto, 2) . PHP_EOL;
    
    // Acumular para totales generales
    $subtotalGeneral += $subtotalProducto;
    $totalIsvGeneral += $isvProducto;
    
    // Agrupar ISV por tasa
    $tasaIsv = $producto['isv'];
    if (!isset($isvPorTasa[$tasaIsv])) {
        $isvPorTasa[$tasaIsv] = 0;
    }
    $isvPorTasa[$tasaIsv] += $isvProducto;
}

$totalGeneral = $subtotalGeneral + $totalIsvGeneral;

echo PHP_EOL . "=== TOTALES GENERALES ===" . PHP_EOL;
echo "Subtotal: L. " . number_format($subtotalGeneral, 2) . PHP_EOL;
echo "Total ISV: L. " . number_format($totalIsvGeneral, 2) . PHP_EOL;
echo "Total: L. " . number_format($totalGeneral, 2) . PHP_EOL;

echo PHP_EOL . "=== ISV POR TASA ===" . PHP_EOL;
foreach ($isvPorTasa as $tasa => $montoIsv) {
    echo "ISV (" . $tasa . "%): L. " . number_format($montoIsv, 2) . PHP_EOL;
}

echo PHP_EOL . "=== VERIFICACIÓN ===" . PHP_EOL;
echo "Subtotal + Total ISV = " . number_format($subtotalGeneral, 2) . " + " . number_format($totalIsvGeneral, 2) . " = " . number_format($subtotalGeneral + $totalIsvGeneral, 2) . PHP_EOL;
echo "¿Coincide con Total? " . ($totalGeneral == ($subtotalGeneral + $totalIsvGeneral) ? "✓ SÍ" : "✗ NO") . PHP_EOL;
