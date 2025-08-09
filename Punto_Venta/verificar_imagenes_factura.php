<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    
    echo "=== VERIFICACIÓN DE IMÁGENES DE FACTURAS ===\n\n";
    
    // Verificar facturas con y sin imagen
    $result = $pdo->query('
        SELECT 
            id,
            numero_factura,
            total,
            nombre_cliente,
            fecha_emision,
            CASE 
                WHEN factura_imagen IS NOT NULL THEN CONCAT("Sí (", LENGTH(factura_imagen), " bytes)")
                ELSE "No"
            END as tiene_imagen
        FROM factura 
        ORDER BY id DESC 
        LIMIT 10
    ');
    
    echo "Últimas 10 facturas:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-5s %-12s %-10s %-20s %-12s %-15s\n", "ID", "Factura", "Total", "Cliente", "Fecha", "Imagen");
    echo str_repeat("-", 80) . "\n";
    
    foreach($result as $row) {
        $cliente = substr($row['nombre_cliente'] ?: 'Consumidor Final', 0, 18);
        printf("%-5s %-12s %-10s %-20s %-12s %-15s\n", 
            $row['id'], 
            $row['numero_factura'], 
            'L.' . $row['total'], 
            $cliente, 
            $row['fecha_emision'], 
            $row['tiene_imagen']
        );
    }
    
    // Contar facturas con imagen
    $result = $pdo->query('SELECT COUNT(*) as total FROM factura WHERE factura_imagen IS NOT NULL');
    $conImagen = $result->fetch()['total'];
    
    $result = $pdo->query('SELECT COUNT(*) as total FROM factura');
    $totalFacturas = $result->fetch()['total'];
    
    echo "\n=== RESUMEN ===\n";
    echo "Total de facturas: $totalFacturas\n";
    echo "Facturas con imagen: $conImagen\n";
    echo "Facturas sin imagen: " . ($totalFacturas - $conImagen) . "\n";
    
    if ($conImagen > 0) {
        echo "\n=== DETALLES DE IMÁGENES ===\n";
        $result = $pdo->query('
            SELECT 
                id,
                numero_factura,
                LENGTH(factura_imagen) as tamaño_bytes
            FROM factura 
            WHERE factura_imagen IS NOT NULL 
            ORDER BY id DESC
        ');
        
        foreach($result as $row) {
            $tamaño_kb = round($row['tamaño_bytes'] / 1024, 2);
            echo "Factura {$row['numero_factura']}: {$row['tamaño_bytes']} bytes ({$tamaño_kb} KB)\n";
        }
    }
    
    echo "\n=== VERIFICACIÓN DE EXTENSIÓN GD ===\n";
    if (extension_loaded('gd')) {
        echo "✅ Extensión GD está disponible\n";
        $info = gd_info();
        echo "Versión GD: " . $info['GD Version'] . "\n";
        echo "PNG Support: " . ($info['PNG Support'] ? 'Sí' : 'No') . "\n";
    } else {
        echo "❌ Extensión GD NO está disponible\n";
        echo "La generación de imágenes no funcionará sin la extensión GD\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
