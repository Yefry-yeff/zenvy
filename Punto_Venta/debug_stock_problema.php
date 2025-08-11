<?php
$pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

echo "=== Debug del problema específico ===" . PHP_EOL;

// Pedir al usuario el código de barra del producto problemático
echo "Ingrese el código de barra del producto con el problema: ";
$codigoBarra = trim(fgets(STDIN));

if (empty($codigoBarra)) {
    $codigoBarra = '7421206000039'; // valor por defecto para testing
    echo "Usando código por defecto: {$codigoBarra}" . PHP_EOL;
}

echo PHP_EOL . "=== Analizando producto: {$codigoBarra} ===" . PHP_EOL;

// 1. Verificar si el producto existe
$stmt = $pdo->prepare('SELECT id, nombre, codigo_barra FROM producto WHERE codigo_barra = ?');
$stmt->execute([$codigoBarra]);
$producto = $stmt->fetch();

if (!$producto) {
    echo "❌ ERROR: No se encontró producto con código {$codigoBarra}" . PHP_EOL;
    exit;
}

echo "✓ Producto encontrado: {$producto['nombre']} (ID: {$producto['id']})" . PHP_EOL;

// 2. Verificar stock detallado
echo PHP_EOL . "=== Stock detallado ===" . PHP_EOL;
$stmt = $pdo->prepare('
    SELECT
        rb.id,
        rb.cantidad_disponible,
        rb.cantidad_inicial_seccion,
        s.descripcion as seccion,
        seg.descripcion as segmento,
        b.nombre as bodega,
        b.principal,
        b.tienda_id,
        b.estado_id as bodega_estado,
        rb.estado_id as stock_estado
    FROM recibido_bodega rb
    JOIN seccion s ON rb.seccion_id = s.id
    JOIN segmento seg ON s.segmento_id = seg.id
    JOIN bodega b ON seg.bodega_id = b.id
    WHERE rb.producto_id = ?
    ORDER BY b.principal DESC, b.tienda_id, seg.descripcion, s.descripcion
');
$stmt->execute([$producto['id']]);
$stocks = $stmt->fetchAll();

$stockTotalGeneral = 0;
$stockBodegaPrincipalTienda1 = 0;

foreach ($stocks as $stock) {
    $estado = ($stock['stock_estado'] == 1) ? "Activo" : "Inactivo";
    $estadoBodega = ($stock['bodega_estado'] == 1) ? "Activa" : "Inactiva";
    $principal = ($stock['principal'] == 1) ? "PRINCIPAL" : "Secundaria";

    echo "- {$stock['bodega']} ({$principal}) - Tienda {$stock['tienda_id']} ({$estadoBodega})" . PHP_EOL;
    echo "  └─ {$stock['segmento']} > {$stock['seccion']}" . PHP_EOL;
    echo "     └─ Cantidad disponible: {$stock['cantidad_disponible']} | Inicial: {$stock['cantidad_inicial_seccion']} | Estado: {$estado}" . PHP_EOL;

    if ($stock['stock_estado'] == 1) {
        $stockTotalGeneral += $stock['cantidad_disponible'];

        if ($stock['principal'] == 1 && $stock['tienda_id'] == 1 && $stock['bodega_estado'] == 1) {
            $stockBodegaPrincipalTienda1 += $stock['cantidad_disponible'];
        }
    }
    echo PHP_EOL;
}

echo "📊 RESUMEN:" . PHP_EOL;
echo "- Stock total general (todos los productos activos): {$stockTotalGeneral}" . PHP_EOL;
echo "- Stock en bodega principal tienda 1: {$stockBodegaPrincipalTienda1}" . PHP_EOL;

// 3. Probar la query exacta que usa el sistema
echo PHP_EOL . "=== Query del sistema (bodega principal tienda 1) ===" . PHP_EOL;
$stmt = $pdo->prepare('
    SELECT SUM(rb.cantidad_disponible) as stock_total
    FROM recibido_bodega rb
    JOIN seccion s ON rb.seccion_id = s.id
    JOIN segmento seg ON s.segmento_id = seg.id
    JOIN bodega b ON seg.bodega_id = b.id
    WHERE b.tienda_id = 1
    AND b.principal = 1
    AND b.estado_id = 1
    AND rb.producto_id = ?
    AND rb.estado_id = 1
');
$stmt->execute([$producto['id']]);
$result = $stmt->fetch();
$stockSistema = $result['stock_total'] ?? 0;

echo "Stock calculado por el sistema: {$stockSistema}" . PHP_EOL;

// 4. Simular el escenario problemático
echo PHP_EOL . "=== Simulación del problema ===" . PHP_EOL;
echo "Según reportas:" . PHP_EOL;
echo "- Stock total: 1" . PHP_EOL;
echo "- Ya en factura: 2" . PHP_EOL;
echo "- Quieres agregar: 1" . PHP_EOL;
echo PHP_EOL;

$cantidadEnCarrito = 2;
$cantidadSolicitada = 1;
$nuevaCantidadTotal = $cantidadEnCarrito + $cantidadSolicitada;

echo "Nueva cantidad total sería: {$cantidadEnCarrito} + {$cantidadSolicitada} = {$nuevaCantidadTotal}" . PHP_EOL;
echo "Stock disponible según sistema: {$stockSistema}" . PHP_EOL;

if ($nuevaCantidadTotal <= $stockSistema) {
    echo "✅ DEBERÍA PERMITIR (nueva cantidad total <= stock disponible)" . PHP_EOL;
} else {
    echo "❌ SISTEMA DENIEGA (nueva cantidad total > stock disponible)" . PHP_EOL;
    echo "💡 Problema: Ya tienes más productos en el carrito ({$cantidadEnCarrito}) de los que hay en stock ({$stockSistema})" . PHP_EOL;
}

echo PHP_EOL . "=== Posibles causas ===" . PHP_EOL;
echo "1. El stock reportado (1) vs el stock real ({$stockSistema}) no coinciden" . PHP_EOL;
echo "2. Ya hay productos en el carrito que exceden el stock disponible" . PHP_EOL;
echo "3. Los datos de stock en la base de datos no están actualizados" . PHP_EOL;
echo "4. Hay diferencia entre lo que muestra la UI y lo que calcula el backend" . PHP_EOL;
?>
