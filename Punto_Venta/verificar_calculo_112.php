<?php

echo "=== VERIFICACIÓN DE CÁLCULOS DE FACTURA ===" . PHP_EOL;
echo "Ejemplo con productos comunes:" . PHP_EOL . PHP_EOL;

// Ejemplo 1: Producto de L. 50 x 2 unidades con 15% ISV
echo "PRODUCTO 1:" . PHP_EOL;
echo "Precio unitario: L. 50.00" . PHP_EOL;
echo "Cantidad: 2" . PHP_EOL;
echo "Subtotal: L. 50.00 × 2 = L. 100.00" . PHP_EOL;
echo "ISV (15%): L. 100.00 × 0.15 = L. 15.00" . PHP_EOL;
echo "Total producto: L. 100.00 + L. 15.00 = L. 115.00" . PHP_EOL . PHP_EOL;

// Ejemplo 2: Producto de L. 10.43 x 1 unidad con 15% ISV (para llegar a 112 total)
echo "PRODUCTO 2:" . PHP_EOL;
echo "Precio unitario: L. 10.43" . PHP_EOL;
echo "Cantidad: 1" . PHP_EOL;
echo "Subtotal: L. 10.43 × 1 = L. 10.43" . PHP_EOL;
echo "ISV (15%): L. 10.43 × 0.15 = L. 1.56" . PHP_EOL;
echo "Total producto: L. 10.43 + L. 1.56 = L. 11.99" . PHP_EOL . PHP_EOL;

echo "TOTALES GENERALES:" . PHP_EOL;
echo "Subtotal total: L. 100.00 + L. 10.43 = L. 110.43" . PHP_EOL;
echo "ISV total: L. 15.00 + L. 1.56 = L. 16.56" . PHP_EOL;
echo "Total final: L. 110.43 + L. 16.56 = L. 126.99" . PHP_EOL . PHP_EOL;

echo "Para obtener exactamente L. 112.00 como total, necesitaríamos:" . PHP_EOL;
$totalDeseado = 112.00;
$subtotalCalculado = $totalDeseado / 1.15; // 112 / 1.15 = subtotal sin ISV
$isvCalculado = $subtotalCalculado * 0.15;

echo "Subtotal sin ISV: L. " . number_format($subtotalCalculado, 2) . PHP_EOL;
echo "ISV (15%): L. " . number_format($isvCalculado, 2) . PHP_EOL;
echo "Total: L. " . number_format($subtotalCalculado + $isvCalculado, 2) . PHP_EOL . PHP_EOL;

echo "Esto significa que si tu total es L. 112.00:" . PHP_EOL;
echo "- Subtotal debería ser: L. " . number_format($subtotalCalculado, 2) . PHP_EOL;
echo "- ISV debería ser: L. " . number_format($isvCalculado, 2) . PHP_EOL;
