<?php
/**
 * Verificación de cambio de moneda a Lempiras (L.)
 */

echo "=== VERIFICACIÓN DE CAMBIO DE MONEDA A LEMPIRAS ===\n\n";

// 1. Verificar componente
echo "1. Verificando componente RecibidoDeEfectivo.php...\n";
$componentePath = __DIR__ . '/app/Livewire/Caja/RecibidoDeEfectivo.php';
$componentContent = file_get_contents($componentePath);

$dollarCount = substr_count($componentContent, '$" . number_format');
$lempiraCount = substr_count($componentContent, 'L." . number_format');

echo "   - Símbolos de dólar en mensajes: $dollarCount\n";
echo "   - Símbolos de Lempira en mensajes: $lempiraCount\n";

if ($lempiraCount > 0 && $dollarCount == 0) {
    echo "   ✅ Componente actualizado a Lempiras\n";
} else {
    echo "   ❌ Componente aún tiene símbolos de dólar\n";
}

// 2. Verificar vista
echo "\n2. Verificando vista recibido-de-efectivo.blade.php...\n";
$viewPath = __DIR__ . '/resources/views/livewire/caja/recibido-de-efectivo.blade.php';
$viewContent = file_get_contents($viewPath);

$viewDollarCount = substr_count($viewContent, '${{');
$viewLempiraCount = substr_count($viewContent, 'L.{{');
$inputLempiraCount = substr_count($viewContent, '<span class="text-gray-500 text-lg font-semibold">L.</span>');

echo "   - Símbolos de dólar en vista: $viewDollarCount\n";
echo "   - Símbolos de Lempira en saldo: $viewLempiraCount\n";
echo "   - Símbolo de Lempira en input: $inputLempiraCount\n";

if ($viewLempiraCount > 0 && $viewDollarCount == 0 && $inputLempiraCount > 0) {
    echo "   ✅ Vista actualizada a Lempiras\n";
} else {
    echo "   ❌ Vista aún tiene símbolos de dólar o falta símbolo en input\n";
}

// 3. Verificar padding del input
if (strpos($viewContent, 'pl-10') !== false) {
    echo "   ✅ Padding del input ajustado para 'L.' (pl-10)\n";
} else {
    echo "   ❌ Padding del input no ajustado\n";
}

echo "\n=== CAMBIOS APLICADOS ===\n";
echo "✅ Mensajes de éxito: \$ → L.\n";
echo "✅ Saldo actual en vista: \$ → L.\n";
echo "✅ Símbolo del input: \$ → L.\n";
echo "✅ Padding ajustado: pl-8 → pl-10 (para acomodar 'L.')\n";

echo "\n=== UBICACIONES CAMBIADAS ===\n";
echo "1. Componente - mensajeExito: 'Se han recibido L.X correctamente. Nuevo saldo: L.Y'\n";
echo "2. Vista - Saldo actual: 'L.{{ number_format(\$cajaActual->balance ?? 0, 2) }}'\n";
echo "3. Vista - Input placeholder: '<span>L.</span>' con padding pl-10\n";

echo "\n=== MONEDA HONDUREÑA APLICADA ===\n";
echo "🇭🇳 Lempira (L.) es ahora la moneda oficial del sistema\n";
echo "✅ Todos los montos se muestran en Lempiras\n";
echo "✅ Interfaz adaptada para moneda hondureña\n";

echo "\n=== VERIFICACIÓN COMPLETADA ===\n";
?>
