<?php

// Script para verificar la nueva implementación de facturación con distribución por secciones

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== VERIFICACIÓN DE NUEVA IMPLEMENTACIÓN FIFO POR SECCIONES ===" . PHP_EOL;
    echo PHP_EOL;

    // 1. Verificar el stock actual del producto de prueba
    echo "1. STOCK ACTUAL POR SECCIONES (Producto 721310002057):" . PHP_EOL;
    echo str_repeat("-", 70) . PHP_EOL;
    
    $sql = "SELECT t.denominacion_social as 'TIENDA', 
                   b.nombre AS 'BODEGA',
                   s.descripcion AS 'SEGMENTO',
                   sc.id as 'seccion_id',
                   sc.descripcion AS 'SECCION',
                   p.id as 'producto_id',
                   p.nombre AS 'PRODUCTO',
                   p.precio_base AS 'PRECIO',
                   p.isv AS 'ISV', 
                   rb.cantidad_disponible,
                   rb.id as recibido_bodega_id
            FROM users u 
            INNER JOIN tienda t ON t.id = u.tienda_id
            INNER JOIN bodega b ON b.tienda_id = t.id
            INNER JOIN segmento s ON s.bodega_id = b.id
            INNER JOIN seccion sc ON sc.segmento_id = s.id
            INNER JOIN recibido_bodega rb ON rb.seccion_id = sc.id
            INNER JOIN producto p ON p.id = rb.producto_id
            WHERE p.codigo_barra='721310002057' 
              AND b.principal=1
              AND rb.cantidad_disponible > 0
            ORDER BY rb.cantidad_disponible DESC";
    
    $result = $pdo->query($sql);
    $secciones = $result->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($secciones)) {
        echo "   ❌ No hay stock disponible para el producto 721310002057" . PHP_EOL;
    } else {
        foreach($secciones as $seccion) {
            echo sprintf("   🏪 Tienda: %-20s | Sección ID: %-3s | %-15s | Stock: %-3s | Precio: L.%-6s | ISV: %-4s%%", 
                $seccion['TIENDA'], 
                $seccion['seccion_id'],
                $seccion['SECCION'], 
                $seccion['cantidad_disponible'],
                number_format($seccion['PRECIO'], 2),
                $seccion['ISV']
            ) . PHP_EOL;
        }
    }
    echo PHP_EOL;

    // 2. Verificar total disponible
    $totalSql = "SELECT SUM(rb.cantidad_disponible) AS total_disponible
                 FROM tienda t
                 INNER JOIN bodega b ON b.tienda_id = t.id
                 INNER JOIN segmento s ON s.bodega_id = b.id
                 INNER JOIN seccion sc ON sc.segmento_id = s.id
                 INNER JOIN recibido_bodega rb ON rb.seccion_id = sc.id
                 INNER JOIN producto p ON p.id = rb.producto_id
                 WHERE t.id = 1
                   AND p.codigo_barra = '721310002057'
                   AND b.principal = 1";
    
    $result = $pdo->query($totalSql);
    $total = $result->fetch(PDO::FETCH_ASSOC);
    
    echo "2. TOTAL STOCK DISPONIBLE:" . PHP_EOL;
    echo str_repeat("-", 30) . PHP_EOL;
    echo "   📦 Total disponible: " . ($total['total_disponible'] ?? 0) . " unidades" . PHP_EOL;
    echo PHP_EOL;

    // 3. Verificar estructura de factura_has_producto
    echo "3. ESTRUCTURA DE TABLA factura_has_producto:" . PHP_EOL;
    echo str_repeat("-", 50) . PHP_EOL;
    $result = $pdo->query('DESCRIBE factura_has_producto');
    foreach($result as $row) {
        echo sprintf("   %-35s %-20s %-10s", 
            $row['Field'], $row['Type'], $row['Null']) . PHP_EOL;
    }
    echo PHP_EOL;

    // 4. Verificar últimas facturas con productos
    echo "4. ÚLTIMAS FACTURAS CON PRODUCTOS:" . PHP_EOL;
    echo str_repeat("-", 50) . PHP_EOL;
    $result = $pdo->query('SELECT fhp.*, f.numero_factura, p.nombre as producto_nombre, sc.descripcion as seccion_nombre 
                          FROM factura_has_producto fhp 
                          LEFT JOIN factura f ON fhp.factura_id = f.id 
                          LEFT JOIN producto p ON fhp.producto_id = p.id
                          LEFT JOIN seccion sc ON fhp.seccion_id = sc.id
                          ORDER BY fhp.factura_id DESC LIMIT 10');
    $productos = $result->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($productos)) {
        echo "   📋 No hay productos facturados" . PHP_EOL;
    } else {
        echo sprintf("   %-10s %-15s %-20s %-10s %-8s %-12s", 
            'Factura', 'Producto', 'Sección', 'Cant.', 'Precio', 'Total') . PHP_EOL;
        echo str_repeat("-", 80) . PHP_EOL;
        
        foreach($productos as $prod) {
            echo sprintf("   %-10s %-15s %-20s %-10s L.%-6s L.%-10s", 
                $prod['numero_factura'] ?? 'N/A',
                substr($prod['producto_nombre'] ?? 'N/A', 0, 15),
                substr($prod['seccion_nombre'] ?? 'N/A', 0, 20),
                $prod['numero_unidades_resta_inventario'] ?? 0,
                number_format($prod['precio_unidad'] ?? 0, 2),
                number_format($prod['total'] ?? 0, 2)
            ) . PHP_EOL;
        }
    }
    echo PHP_EOL;

    // 5. Verificar últimos pagos
    echo "5. ÚLTIMOS MÉTODOS DE PAGO:" . PHP_EOL;
    echo str_repeat("-", 50) . PHP_EOL;
    $result = $pdo->query('SELECT fhp.*, tp.nombre as tipo_pago_nombre, f.numero_factura 
                          FROM factura_has_pago fhp 
                          LEFT JOIN tipo_pago tp ON fhp.tipo_pago_id = tp.id 
                          LEFT JOIN factura f ON fhp.factura_id = f.id 
                          ORDER BY fhp.id DESC LIMIT 10');
    $pagos = $result->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pagos)) {
        echo "   💳 No hay métodos de pago registrados" . PHP_EOL;
    } else {
        echo sprintf("   %-10s %-15s %-12s %-12s %-12s", 
            'Factura', 'Método', 'Total Fact.', 'Pago Recib.', 'Cambio') . PHP_EOL;
        echo str_repeat("-", 70) . PHP_EOL;
        
        foreach($pagos as $pago) {
            echo sprintf("   %-10s %-15s L.%-10s L.%-10s L.%-10s", 
                $pago['numero_factura'] ?? 'N/A',
                $pago['tipo_pago_nombre'] ?? 'N/A',
                number_format($pago['total_factura'] ?? 0, 2),
                number_format($pago['pago_recibido'] ?? 0, 2),
                number_format($pago['cambio'] ?? 0, 2)
            ) . PHP_EOL;
        }
    }

    echo PHP_EOL;
    echo "=== LISTO PARA PROBAR LA NUEVA IMPLEMENTACIÓN ===" . PHP_EOL;
    echo "📌 Crea una nueva factura para verificar:" . PHP_EOL;
    echo "   ✅ Distribución FIFO por secciones" . PHP_EOL;
    echo "   ✅ Múltiples registros en factura_has_producto" . PHP_EOL;
    echo "   ✅ Actualización correcta de stock" . PHP_EOL;
    echo "   ✅ Registro de todos los métodos de pago" . PHP_EOL;

} catch (PDOException $e) {
    echo "❌ ERROR DE CONEXIÓN: " . $e->getMessage() . PHP_EOL;
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . PHP_EOL;
}
?>
