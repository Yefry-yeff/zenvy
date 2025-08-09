<?php

// Script para verificar la estructura y datos de las tablas de pago

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== VERIFICACIÓN DE TABLAS DE PAGO ===" . PHP_EOL;
    echo PHP_EOL;

    // Verificar estructura de tipo_pago
    echo "1. ESTRUCTURA DE TABLA tipo_pago:" . PHP_EOL;
    echo str_repeat("-", 50) . PHP_EOL;
    $result = $pdo->query('DESCRIBE tipo_pago');
    foreach($result as $row) {
        echo sprintf("%-20s %-15s %-10s %-10s %-10s %-10s", 
            $row['Field'], $row['Type'], $row['Null'], 
            $row['Key'], $row['Default'], $row['Extra']) . PHP_EOL;
    }
    echo PHP_EOL;

    // Verificar datos de tipo_pago
    echo "2. DATOS EN TABLA tipo_pago:" . PHP_EOL;
    echo str_repeat("-", 50) . PHP_EOL;
    $result = $pdo->query('SELECT * FROM tipo_pago');
    $tiposPago = $result->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tiposPago)) {
        echo "   No hay tipos de pago registrados" . PHP_EOL;
        echo "   Insertando tipos de pago básicos..." . PHP_EOL;
        
        $pdo->exec("INSERT INTO tipo_pago (nombre, created_at) VALUES 
                   ('Efectivo', NOW()),
                   ('Tarjeta de Crédito', NOW()),
                   ('Tarjeta de Débito', NOW()),
                   ('Cheque', NOW())");
        
        echo "   Tipos de pago insertados exitosamente" . PHP_EOL;
        
        // Obtener los datos insertados
        $result = $pdo->query('SELECT * FROM tipo_pago');
        $tiposPago = $result->fetchAll(PDO::FETCH_ASSOC);
    }
    
    foreach($tiposPago as $tipo) {
        echo sprintf("   ID: %-3s | Nombre: %-20s | Creado: %s", 
            $tipo['id'], $tipo['nombre'], $tipo['created_at'] ?? 'N/A') . PHP_EOL;
    }
    echo PHP_EOL;

    // Verificar estructura de factura_has_pago
    echo "3. ESTRUCTURA DE TABLA factura_has_pago:" . PHP_EOL;
    echo str_repeat("-", 50) . PHP_EOL;
    $result = $pdo->query('DESCRIBE factura_has_pago');
    foreach($result as $row) {
        echo sprintf("%-20s %-15s %-10s %-10s %-10s %-10s", 
            $row['Field'], $row['Type'], $row['Null'], 
            $row['Key'], $row['Default'], $row['Extra']) . PHP_EOL;
    }
    echo PHP_EOL;

    // Verificar datos recientes de factura_has_pago
    echo "4. ÚLTIMOS REGISTROS EN factura_has_pago:" . PHP_EOL;
    echo str_repeat("-", 50) . PHP_EOL;
    $result = $pdo->query('SELECT fhp.*, tp.nombre as tipo_pago_nombre, f.numero_factura 
                          FROM factura_has_pago fhp 
                          LEFT JOIN tipo_pago tp ON fhp.tipo_pago_id = tp.id 
                          LEFT JOIN factura f ON fhp.factura_id = f.id 
                          ORDER BY fhp.id DESC LIMIT 10');
    $pagos = $result->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pagos)) {
        echo "   No hay registros de pagos" . PHP_EOL;
    } else {
        echo sprintf("%-5s %-15s %-15s %-12s %-12s %-12s %-15s", 
            'ID', 'Factura', 'Tipo Pago', 'Total Fact.', 'Pago Recib.', 'Cambio', 'Fecha') . PHP_EOL;
        echo str_repeat("-", 90) . PHP_EOL;
        
        foreach($pagos as $pago) {
            echo sprintf("%-5s %-15s %-15s %-12s %-12s %-12s", 
                $pago['id'], 
                $pago['numero_factura'] ?? 'N/A',
                $pago['tipo_pago_nombre'] ?? 'N/A',
                'L. ' . number_format($pago['total_factura'] ?? 0, 2),
                'L. ' . number_format($pago['pago_recibido'] ?? 0, 2),
                'L. ' . number_format($pago['cambio'] ?? 0, 2)
            ) . PHP_EOL;
        }
    }
    echo PHP_EOL;

    // Verificar facturas recientes
    echo "5. FACTURAS RECIENTES:" . PHP_EOL;
    echo str_repeat("-", 50) . PHP_EOL;
    $result = $pdo->query('SELECT id, numero_factura, nombre_cliente, total, fecha_emision 
                          FROM factura 
                          ORDER BY id DESC LIMIT 5');
    $facturas = $result->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($facturas)) {
        echo "   No hay facturas registradas" . PHP_EOL;
    } else {
        foreach($facturas as $factura) {
            echo sprintf("   ID: %-3s | No.: %-10s | Cliente: %-20s | Total: L. %-10s | Fecha: %s", 
                $factura['id'], 
                $factura['numero_factura'] ?? 'N/A',
                substr($factura['nombre_cliente'] ?? 'N/A', 0, 20),
                number_format($factura['total'] ?? 0, 2),
                $factura['fecha_emision'] ?? 'N/A'
            ) . PHP_EOL;
        }
    }

    echo PHP_EOL;
    echo "=== VERIFICACIÓN COMPLETADA ===" . PHP_EOL;

} catch (PDOException $e) {
    echo "ERROR DE CONEXIÓN: " . $e->getMessage() . PHP_EOL;
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
?>
