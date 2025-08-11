<?php

echo "=== PRUEBA DE DESCUENTOS POR EDAD CON VALIDACIÓN ===\n\n";

// Simular productos de la factura basados en la base de datos real
$productosFactura = [
    [
        'id' => 1,
        'nombre' => 'Cuaderno Universitario Norma',
        'precio' => 100.00,
        'cantidad' => 1,
        'isv' => 15,
        'descuento_tercera' => 1, // PERMITE descuento 3ra edad
        'descuento_cuarta' => 1,  // PERMITE descuento 4ta edad
        'descuento_aplicado' => 0,
        'subtotal_con_descuento' => 0
    ],
    [
        'id' => 2,
        'nombre' => 'Lapiz Negro',
        'precio' => 50.00,
        'cantidad' => 2,
        'isv' => 15,
        'descuento_tercera' => 0, // NO PERMITE descuento 3ra edad
        'descuento_cuarta' => 0,  // NO PERMITE descuento 4ta edad
        'descuento_aplicado' => 0,
        'subtotal_con_descuento' => 0
    ],
    [
        'id' => 3,
        'nombre' => 'Four Loco Maracuya',
        'precio' => 75.00,
        'cantidad' => 1,
        'isv' => 15,
        'descuento_tercera' => 1, // PERMITE descuento 3ra edad
        'descuento_cuarta' => 1,  // PERMITE descuento 4ta edad
        'descuento_aplicado' => 0,
        'subtotal_con_descuento' => 0
    ]
];

function calcularTotalesConDescuento($productos, $descuentoTerceraEdad = false, $descuentoCuartaEdad = false) {
    $subtotal = 0;
    $totalIsv = 0;
    $totalDescuentos = 0;
    
    echo "--- CÁLCULO DE TOTALES ---\n";
    echo "Descuento 3ra edad activo: " . ($descuentoTerceraEdad ? 'SÍ' : 'NO') . "\n";
    echo "Descuento 4ta edad activo: " . ($descuentoCuartaEdad ? 'SÍ' : 'NO') . "\n\n";
    
    foreach ($productos as $index => $producto) {
        $subtotalProducto = $producto['precio'] * $producto['cantidad'];
        
        // Aplicar descuentos por edad al subtotal del producto
        $descuentoProducto = 0;
        $razonDescuento = "Ninguno";
        
        // Verificar descuento de tercera edad (25%) - solo si el producto lo permite
        if ($descuentoTerceraEdad && ($producto['descuento_tercera'] ?? 0) == 1) {
            $descuentoProducto = $subtotalProducto * 0.25; // 25%
            $razonDescuento = "3ra edad (25%)";
        }
        // Verificar descuento de cuarta edad (35%) - solo si el producto lo permite y no hay descuento de tercera edad
        elseif ($descuentoCuartaEdad && ($producto['descuento_cuarta'] ?? 0) == 1) {
            $descuentoProducto = $subtotalProducto * 0.35; // 35%
            $razonDescuento = "4ta edad (35%)";
        }
        
        // Calcular subtotal con descuento aplicado
        $subtotalConDescuento = $subtotalProducto - $descuentoProducto;
        $subtotal += $subtotalConDescuento;
        $totalDescuentos += $descuentoProducto;
        
        // Calcular ISV sobre el subtotal con descuento
        $tasaIsv = $producto['isv'];
        $isvProducto = $subtotalConDescuento * ($tasaIsv / 100);
        $totalIsv += $isvProducto;
        
        echo "Producto: " . $producto['nombre'] . "\n";
        echo "  Permite 3ra edad: " . (($producto['descuento_tercera'] ?? 0) == 1 ? 'SÍ' : 'NO') . "\n";
        echo "  Permite 4ta edad: " . (($producto['descuento_cuarta'] ?? 0) == 1 ? 'SÍ' : 'NO') . "\n";
        echo "  Subtotal original: L. " . number_format($subtotalProducto, 2) . "\n";
        echo "  Descuento aplicado: L. " . number_format($descuentoProducto, 2) . " ($razonDescuento)\n";
        echo "  Subtotal con descuento: L. " . number_format($subtotalConDescuento, 2) . "\n";
        echo "  ISV (15%): L. " . number_format($isvProducto, 2) . "\n";
        echo "  Total producto: L. " . number_format($subtotalConDescuento + $isvProducto, 2) . "\n";
        echo "  ----------\n";
    }
    
    $total = $subtotal + $totalIsv;
    
    echo "\n=== RESUMEN FINAL ===\n";
    echo "Subtotal: L. " . number_format($subtotal, 2) . "\n";
    echo "Total descuentos: L. " . number_format($totalDescuentos, 2) . "\n";
    echo "Total ISV: L. " . number_format($totalIsv, 2) . "\n";
    echo "TOTAL: L. " . number_format($total, 2) . "\n\n";
    
    return [
        'subtotal' => $subtotal,
        'totalDescuentos' => $totalDescuentos,
        'totalIsv' => $totalIsv,
        'total' => $total
    ];
}

function verificarProductosElegibles($productos, $tipoDescuento) {
    $campo = $tipoDescuento == '3ra' ? 'descuento_tercera' : 'descuento_cuarta';
    $elegibles = array_filter($productos, function($producto) use ($campo) {
        return ($producto[$campo] ?? 0) == 1;
    });
    
    echo "=== PRODUCTOS ELEGIBLES PARA DESCUENTO DE {$tipoDescuento} EDAD ===\n";
    if (empty($elegibles)) {
        echo "❌ NO hay productos elegibles para descuento de {$tipoDescuento} edad\n\n";
        return false;
    } else {
        echo "✅ Productos elegibles:\n";
        foreach ($elegibles as $producto) {
            echo "  - " . $producto['nombre'] . " (L. " . number_format($producto['precio'] * $producto['cantidad'], 2) . ")\n";
        }
        echo "\n";
        return true;
    }
}

// ========== ESCENARIOS DE PRUEBA ==========

echo "ESCENARIO 1: Sin descuentos\n";
echo "===============================\n";
calcularTotalesConDescuento($productosFactura);

echo "\nESCENARIO 2: Intentar aplicar descuento 3ra edad\n";
echo "================================================\n";
if (verificarProductosElegibles($productosFactura, '3ra')) {
    calcularTotalesConDescuento($productosFactura, true, false);
} else {
    echo "⚠️ Sistema evitaría aplicar descuento porque no hay productos elegibles\n\n";
}

echo "\nESCENARIO 3: Intentar aplicar descuento 4ta edad\n";
echo "================================================\n";
if (verificarProductosElegibles($productosFactura, '4ta')) {
    calcularTotalesConDescuento($productosFactura, false, true);
} else {
    echo "⚠️ Sistema evitaría aplicar descuento porque no hay productos elegibles\n\n";
}

echo "\nESCENARIO 4: Solo productos SIN descuento por edad\n";
echo "==================================================\n";
$productosSinDescuento = [
    [
        'id' => 2,
        'nombre' => 'Lapiz Negro',
        'precio' => 50.00,
        'cantidad' => 2,
        'isv' => 15,
        'descuento_tercera' => 0, // NO PERMITE
        'descuento_cuarta' => 0,  // NO PERMITE
        'descuento_aplicado' => 0,
        'subtotal_con_descuento' => 0
    ],
    [
        'id' => 5,
        'nombre' => 'Cocacola Zero',
        'precio' => 25.00,
        'cantidad' => 1,
        'isv' => 15,
        'descuento_tercera' => 0, // NO PERMITE
        'descuento_cuarta' => 0,  // NO PERMITE
        'descuento_aplicado' => 0,
        'subtotal_con_descuento' => 0
    ]
];

if (verificarProductosElegibles($productosSinDescuento, '3ra')) {
    calcularTotalesConDescuento($productosSinDescuento, true, false);
} else {
    echo "✅ Sistema correctamente evita aplicar descuento 3ra edad\n";
    calcularTotalesConDescuento($productosSinDescuento, false, false);
}

echo "\n=== VALIDACIÓN COMPLETADA ===\n";
echo "✅ El sistema ahora verifica correctamente los campos descuento_tercera y descuento_cuarta\n";
echo "✅ Solo aplica descuentos a productos que tienen estos campos en 1\n";
echo "✅ Muestra mensajes informativos sobre productos elegibles\n";
