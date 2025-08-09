<?php

// Script para verificar si todos los métodos de pago se están guardando correctamente

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== VERIFICACIÓN DE MÉTODOS DE PAGO MÚLTIPLES ===" . PHP_EOL;
    echo PHP_EOL;

    // 1. Verificar tipos de pago disponibles
    echo "1. TIPOS DE PAGO DISPONIBLES:" . PHP_EOL;
    echo str_repeat("-", 40) . PHP_EOL;
    $result = $pdo->query('SELECT * FROM tipo_pago ORDER BY id');
    $tiposPago = $result->fetchAll(PDO::FETCH_ASSOC);

    foreach($tiposPago as $tipo) {
        echo sprintf("   ID: %-3s | Nombre: %-20s",
            $tipo['id'], $tipo['nombre']) . PHP_EOL;
    }
    echo PHP_EOL;

    // 2. Verificar facturas recientes
    echo "2. FACTURAS RECIENTES:" . PHP_EOL;
    echo str_repeat("-", 40) . PHP_EOL;
    $result = $pdo->query('SELECT id, numero_factura, total, fecha_emision
                          FROM factura
                          ORDER BY id DESC LIMIT 5');
    $facturas = $result->fetchAll(PDO::FETCH_ASSOC);

    foreach($facturas as $factura) {
        echo sprintf("   ID: %-3s | No: %-12s | Total: L.%-8s | Fecha: %s",
            $factura['id'],
            $factura['numero_factura'],
            number_format($factura['total'], 2),
            $factura['fecha_emision']
        ) . PHP_EOL;
    }
    echo PHP_EOL;

    // 3. Verificar todos los métodos de pago de las últimas facturas
    echo "3. MÉTODOS DE PAGO POR FACTURA:" . PHP_EOL;
    echo str_repeat("-", 60) . PHP_EOL;

    foreach($facturas as $factura) {
        echo "📄 Factura {$factura['numero_factura']} (Total: L." . number_format($factura['total'], 2) . "):" . PHP_EOL;

        $result = $pdo->prepare('SELECT fhp.*, tp.nombre as tipo_pago_nombre
                                FROM factura_has_pago fhp
                                LEFT JOIN tipo_pago tp ON fhp.tipo_pago_id = tp.id
                                WHERE fhp.factura_id = ?
                                ORDER BY fhp.tipo_pago_id');
        $result->execute([$factura['id']]);
        $pagos = $result->fetchAll(PDO::FETCH_ASSOC);

        if (empty($pagos)) {
            echo "   ❌ Sin métodos de pago registrados" . PHP_EOL;
        } else {
            foreach($pagos as $pago) {
                $estado = $pago['pago_recibido'] > 0 ? "✅" : "⚪";
                echo sprintf("   %s %-15s | Recibido: L.%-8s | Cambio: L.%-8s",
                    $estado,
                    $pago['tipo_pago_nombre'] ?? 'N/A',
                    number_format($pago['pago_recibido'] ?? 0, 2),
                    number_format($pago['cambio'] ?? 0, 2)
                ) . PHP_EOL;
            }
        }
        echo PHP_EOL;
    }

    // 4. Resumen por tipo de pago
    echo "4. RESUMEN POR TIPO DE PAGO (ÚLTIMAS 10 FACTURAS):" . PHP_EOL;
    echo str_repeat("-", 50) . PHP_EOL;

    $result = $pdo->query('SELECT tp.nombre as tipo_pago,
                                 COUNT(*) as total_usos,
                                 SUM(fhp.pago_recibido) as total_recibido,
                                 AVG(fhp.pago_recibido) as promedio_recibido
                          FROM factura_has_pago fhp
                          LEFT JOIN tipo_pago tp ON fhp.tipo_pago_id = tp.id
                          LEFT JOIN factura f ON fhp.factura_id = f.id
                          WHERE f.id >= (SELECT MAX(id) - 9 FROM factura)
                          GROUP BY tp.id, tp.nombre
                          ORDER BY total_usos DESC');
    $resumen = $result->fetchAll(PDO::FETCH_ASSOC);

    if (empty($resumen)) {
        echo "   📊 No hay datos de métodos de pago" . PHP_EOL;
    } else {
        echo sprintf("   %-15s | %-8s | %-12s | %-12s",
            'Tipo', 'Usos', 'Total Recib.', 'Promedio') . PHP_EOL;
        echo str_repeat("-", 55) . PHP_EOL;

        foreach($resumen as $item) {
            echo sprintf("   %-15s | %-8s | L.%-10s | L.%-10s",
                $item['tipo_pago'],
                $item['total_usos'],
                number_format($item['total_recibido'], 2),
                number_format($item['promedio_recibido'], 2)
            ) . PHP_EOL;
        }
    }

    echo PHP_EOL;
    echo "=== INSTRUCCIONES PARA PRUEBA ===" . PHP_EOL;
    echo "🎯 Para probar correctamente:" . PHP_EOL;
    echo "   1. Crea una nueva factura" . PHP_EOL;
    echo "   2. En el modal de pagos, distribuye el monto entre varios métodos:" . PHP_EOL;
    echo "      • Efectivo: L.50.00" . PHP_EOL;
    echo "      • Tarjeta: L.50.00" . PHP_EOL;
    echo "      • Cheque: L.12.00 (resto)" . PHP_EOL;
    echo "   3. Verifica que se guarden los 3 registros" . PHP_EOL;
    echo PHP_EOL;
    echo "📝 Si solo aparece efectivo, revisar los logs en:" . PHP_EOL;
    echo "   storage/logs/laravel.log" . PHP_EOL;

} catch (PDOException $e) {
    echo "❌ ERROR DE CONEXIÓN: " . $e->getMessage() . PHP_EOL;
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . PHP_EOL;
}
?>
