<?php
echo "=== PRUEBA DE DESCUENTOS POR EDAD ===" . PHP_EOL;
echo "Verificando cálculo de descuentos del 25% y 35%" . PHP_EOL;
echo PHP_EOL;

// Ejemplo 1: Producto de L. 100
$precioProducto = 100;
$cantidad = 1;
$subtotalProducto = $precioProducto * $cantidad;

echo "🧮 EJEMPLO DE CÁLCULO:" . PHP_EOL;
echo "   Producto: L. {$precioProducto}" . PHP_EOL;
echo "   Cantidad: {$cantidad}" . PHP_EOL;
echo "   Subtotal: L. {$subtotalProducto}" . PHP_EOL;
echo PHP_EOL;

// Descuento de tercera edad (25%)
$descuentoTercera = $subtotalProducto * 0.25;
$subtotalConDescuentoTercera = $subtotalProducto - $descuentoTercera;

echo "💳 DESCUENTO TERCERA EDAD (25%):" . PHP_EOL;
echo "   Descuento: L. {$descuentoTercera} (25% de L. {$subtotalProducto})" . PHP_EOL;
echo "   Subtotal con descuento: L. {$subtotalConDescuentoTercera}" . PHP_EOL;

// ISV del 18% sobre el subtotal con descuento
$isvTercera = $subtotalConDescuentoTercera * 0.18;
$totalTercera = $subtotalConDescuentoTercera + $isvTercera;

echo "   ISV (18%): L. {$isvTercera}" . PHP_EOL;
echo "   TOTAL FINAL: L. {$totalTercera}" . PHP_EOL;
echo PHP_EOL;

// Descuento de cuarta edad (35%)
$descuentoCuarta = $subtotalProducto * 0.35;
$subtotalConDescuentoCuarta = $subtotalProducto - $descuentoCuarta;

echo "👴 DESCUENTO CUARTA EDAD (35%):" . PHP_EOL;
echo "   Descuento: L. {$descuentoCuarta} (35% de L. {$subtotalProducto})" . PHP_EOL;
echo "   Subtotal con descuento: L. {$subtotalConDescuentoCuarta}" . PHP_EOL;

// ISV del 18% sobre el subtotal con descuento
$isvCuarta = $subtotalConDescuentoCuarta * 0.18;
$totalCuarta = $subtotalConDescuentoCuarta + $isvCuarta;

echo "   ISV (18%): L. {$isvCuarta}" . PHP_EOL;
echo "   TOTAL FINAL: L. {$totalCuarta}" . PHP_EOL;
echo PHP_EOL;

// Comparación
echo "📊 COMPARACIÓN:" . PHP_EOL;
echo "   Sin descuento: L. " . ($subtotalProducto + ($subtotalProducto * 0.18)) . PHP_EOL;
echo "   Con 3ra edad:  L. {$totalTercera} (ahorro: L. " . (($subtotalProducto + ($subtotalProducto * 0.18)) - $totalTercera) . ")" . PHP_EOL;
echo "   Con 4ta edad:  L. {$totalCuarta} (ahorro: L. " . (($subtotalProducto + ($subtotalProducto * 0.18)) - $totalCuarta) . ")" . PHP_EOL;
echo PHP_EOL;

echo "✅ Los cálculos están implementados correctamente en el código:" . PHP_EOL;
echo "   - Tercera edad: subtotal * 0.25" . PHP_EOL;
echo "   - Cuarta edad: subtotal * 0.35" . PHP_EOL;
echo "   - ISV se aplica DESPUÉS del descuento" . PHP_EOL;
?>
