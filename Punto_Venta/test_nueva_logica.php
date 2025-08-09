<?php
$pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

echo "=== Test de la nueva lógica de stock ===" . PHP_EOL;

// Obtener stock total para el producto con código 7421206000039
$stmt = $pdo->prepare('
    SELECT SUM(rb.cantidad_disponible) as stock_total
    FROM recibido_bodega rb
    JOIN seccion s ON rb.seccion_id = s.id
    JOIN segmento seg ON s.segmento_id = seg.id
    JOIN bodega b ON seg.bodega_id = b.id
    WHERE b.tienda_id = 1
    AND b.principal = 1
    AND b.estado_id = 1
    AND rb.producto_id = (SELECT id FROM producto WHERE codigo_barra = ?)
    AND rb.estado_id = 1
');
$stmt->execute(['7421206000039']);
$result = $stmt->fetch();
$stockTotal = $result['stock_total'] ?? 0;

echo "Stock total disponible: {$stockTotal}" . PHP_EOL;
echo PHP_EOL . "=== Simulaciones con nueva lógica ===" . PHP_EOL;

// Función para simular validación
function simularValidacion($stockTotal, $cantidadEnCarrito, $cantidadSolicitada) {
    $nuevaCantidadTotal = $cantidadEnCarrito + $cantidadSolicitada;
    $esValido = $nuevaCantidadTotal <= $stockTotal;

    return [
        'valido' => $esValido,
        'nueva_cantidad_total' => $nuevaCantidadTotal,
        'stock_disponible_mostrar' => max(0, $stockTotal - $cantidadEnCarrito)
    ];
}

// Escenarios de prueba
$escenarios = [
    ['carrito' => 0, 'solicitud' => 1, 'descripcion' => 'Carrito vacío, agregar 1'],
    ['carrito' => 1, 'solicitud' => 1, 'descripcion' => 'Ya hay 1 en carrito, agregar 1 más'],
    ['carrito' => 2, 'solicitud' => 1, 'descripcion' => 'Ya hay 2 en carrito, agregar 1 más'],
    ['carrito' => 3, 'solicitud' => 1, 'descripcion' => 'Ya hay 3 en carrito, agregar 1 más'],
    ['carrito' => 0, 'solicitud' => 3, 'descripcion' => 'Carrito vacío, agregar 3 de una vez'],
    ['carrito' => 0, 'solicitud' => 4, 'descripcion' => 'Carrito vacío, agregar 4 (excede stock)'],
];

foreach ($escenarios as $i => $escenario) {
    $resultado = simularValidacion($stockTotal, $escenario['carrito'], $escenario['solicitud']);
    $estado = $resultado['valido'] ? "✓ PERMITIDO" : "✗ DENEGADO";

    echo "Escenario " . ($i + 1) . " - {$escenario['descripcion']}: {$estado}" . PHP_EOL;
    echo "  - Cantidad en carrito: {$escenario['carrito']}" . PHP_EOL;
    echo "  - Cantidad solicitada: {$escenario['solicitud']}" . PHP_EOL;
    echo "  - Nueva cantidad total: {$resultado['nueva_cantidad_total']}" . PHP_EOL;
    echo "  - Stock disponible para mostrar: {$resultado['stock_disponible_mostrar']}" . PHP_EOL;
    echo PHP_EOL;
}

echo "=== Resumen de la nueva lógica ===" . PHP_EOL;
echo "1. Stock total disponible: {$stockTotal}" . PHP_EOL;
echo "2. Validación: (cantidad_en_carrito + cantidad_solicitada) <= stock_total" . PHP_EOL;
echo "3. Stock para mostrar en UI: max(0, stock_total - cantidad_en_carrito)" . PHP_EOL;
echo "4. Permite facturar hasta el stock total completo" . PHP_EOL;
echo "5. Muestra stock disponible en tiempo real" . PHP_EOL;
?>
