<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

    echo "=== SIMULACIÓN: 23 en efectivo, resto en tarjeta ===\n\n";

    // Simular una factura de ejemplo con total de 100
    $totalFactura = 100.00;
    $efectivo = 23.00;
    $tarjeta = $totalFactura - $efectivo; // 77.00

    echo "Total de factura: L. $totalFactura\n";
    echo "Efectivo: L. $efectivo\n";
    echo "Tarjeta: L. $tarjeta\n\n";

    // Verificar los métodos de pago disponibles
    echo "=== MÉTODOS DE PAGO DISPONIBLES ===\n";
    $result = $pdo->query('SELECT id, nombre FROM tipo_pago ORDER BY id');
    $tiposPago = [];
    foreach($result as $row) {
        $tiposPago[$row['id']] = $row['nombre'];
        echo "ID {$row['id']}: {$row['nombre']}\n";
    }

    // Simular el array montosPorMetodo que debería llegar desde el modal
    $montosPorMetodo = [];
    foreach($tiposPago as $id => $nombre) {
        if ($nombre === 'Efectivo') {
            $montosPorMetodo[$id] = $efectivo;
        } elseif ($nombre === 'Tarjeta') {
            $montosPorMetodo[$id] = $tarjeta;
        } else {
            $montosPorMetodo[$id] = 0;
        }
    }

    echo "\n=== SIMULACIÓN DEL ARRAY montosPorMetodo ===\n";
    foreach($montosPorMetodo as $tipoId => $monto) {
        $nombreTipo = $tiposPago[$tipoId];
        echo "tipo_pago_id $tipoId ($nombreTipo): L. $monto\n";
    }

    // Simular la lógica de filtrado (solo métodos con monto > 0)
    echo "\n=== MÉTODOS QUE DEBERÍAN GUARDARSE (monto > 0) ===\n";
    $metodosParaGuardar = [];
    foreach($montosPorMetodo as $tipoId => $monto) {
        if ($monto > 0) {
            $metodosParaGuardar[] = [
                'id' => $tipoId,
                'nombre' => $tiposPago[$tipoId],
                'monto' => $monto
            ];
            echo "✓ {$tiposPago[$tipoId]}: L. $monto\n";
        } else {
            echo "✗ {$tiposPago[$tipoId]}: L. $monto (NO se guarda)\n";
        }
    }

    echo "\n=== RESULTADOS ESPERADOS EN factura_has_pago ===\n";
    $facturaId = 999; // ID de ejemplo
    foreach($metodosParaGuardar as $metodo) {
        echo "INSERT: factura_id=$facturaId, tipo_pago_id={$metodo['id']}, ";
        echo "total_factura=$totalFactura, pago_recibido={$metodo['monto']}, cambio=0\n";
    }

    // Verificar registros reales de la factura 20
    echo "\n=== REGISTROS REALES DE FACTURA 20 ===\n";
    $stmt = $pdo->prepare('
        SELECT
            f.numero_factura,
            tp.nombre as tipo_pago,
            p.pago_recibido,
            p.total_factura
        FROM factura f
        INNER JOIN factura_has_pago p ON p.factura_id = f.id
        INNER JOIN tipo_pago tp ON tp.id = p.tipo_pago_id
        WHERE f.id = 20
        ORDER BY p.id
    ');
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        foreach($stmt->fetchAll() as $row) {
            echo "Factura: {$row['numero_factura']}, ";
            echo "Tipo: {$row['tipo_pago']}, ";
            echo "Pago: L. {$row['pago_recibido']}, ";
            echo "Total: L. {$row['total_factura']}\n";
        }
    } else {
        echo "No se encontraron registros para factura 20\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
