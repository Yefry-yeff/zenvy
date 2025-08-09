<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

    echo "=== PRUEBA DE MÉTODOS DE PAGO MÚLTIPLES ===\n\n";

    echo "Últimos registros de factura_has_pago (para ver distribución de pagos):\n";
    $result = $pdo->query('
        SELECT
            fhp.id,
            fhp.factura_id,
            tp.nombre as tipo_pago,
            fhp.total_factura,
            fhp.pago_recibido,
            fhp.cambio
        FROM factura_has_pago fhp
        JOIN tipo_pago tp ON tp.id = fhp.tipo_pago_id
        ORDER BY fhp.factura_id DESC, fhp.id DESC
        LIMIT 10
    ');

    $facturas = [];
    foreach($result as $row) {
        if (!isset($facturas[$row['factura_id']])) {
            $facturas[$row['factura_id']] = [];
        }
        $facturas[$row['factura_id']][] = $row;
    }

    foreach($facturas as $facturaId => $pagos) {
        echo "\n--- FACTURA #$facturaId ---\n";
        $totalFactura = 0;
        $totalPagado = 0;
        $totalCambio = 0;

        foreach($pagos as $pago) {
            $totalFactura = $pago['total_factura'];
            $totalPagado += $pago['pago_recibido'];
            $totalCambio += $pago['cambio'];

            echo "  • {$pago['tipo_pago']}: L. {$pago['pago_recibido']}";
            if ($pago['cambio'] > 0) {
                echo " (Cambio: L. {$pago['cambio']})";
            }
            echo "\n";
        }

        echo "  Total Factura: L. $totalFactura\n";
        echo "  Total Pagado: L. $totalPagado\n";
        echo "  Total Cambio: L. $totalCambio\n";

        $diferencia = $totalPagado - $totalFactura;
        if ($diferencia > 0) {
            echo "  ✓ Pago completo con exceso de: L. $diferencia\n";
        } elseif ($diferencia < 0) {
            echo "  ✗ Pago incompleto, falta: L. " . abs($diferencia) . "\n";
        } else {
            echo "  ✓ Pago exacto\n";
        }
    }

    echo "\n=== TIPOS DE PAGO DISPONIBLES ===\n";
    $result = $pdo->query('SELECT id, nombre FROM tipo_pago ORDER BY id');
    foreach($result as $row) {
        echo "ID {$row['id']}: {$row['nombre']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
