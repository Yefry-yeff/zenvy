<?php
require_once __DIR__ . '/vendor/autoload.php';

echo "=== PRUEBA DEL SISTEMA DE SALDO INICIAL ===\n\n";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Verificar si hay una caja cerrada (estado_caja = 0)
    echo "1. Verificando cajas cerradas disponibles...\n";
    $stmt = $pdo->prepare("SELECT * FROM caja WHERE estado_caja = 0 ORDER BY created_at DESC LIMIT 1");
    $stmt->execute();
    $cajaCerrada = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cajaCerrada) {
        echo "✓ Caja cerrada encontrada: ID {$cajaCerrada['id']}, Balance: L. " . number_format($cajaCerrada['balance'], 2) . "\n";
        
        // 2. Simular establecimiento de saldo inicial
        $montoInicial = 1000.00;
        $descripcion = "Saldo inicial de prueba - " . date('Y-m-d H:i:s');
        
        echo "\n2. Estableciendo saldo inicial de L. " . number_format($montoInicial, 2) . "...\n";
        
        $pdo->beginTransaction();
        
        // Actualizar la caja
        $stmt = $pdo->prepare("UPDATE caja SET balance = ?, estado_caja = 1, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$montoInicial, $cajaCerrada['id']]);
        
        // Registrar transacción
        $stmt = $pdo->prepare("INSERT INTO transaccion (caja_id, efectivo, tipo, descripcion, created_at, updated_at) VALUES (?, ?, 'saldo_inicial', ?, NOW(), NOW())");
        $stmt->execute([$cajaCerrada['id'], $montoInicial, $descripcion]);
        
        $pdo->commit();
        
        echo "✓ Saldo inicial establecido correctamente\n";
        
        // 3. Verificar el resultado
        echo "\n3. Verificando resultado...\n";
        $stmt = $pdo->prepare("SELECT * FROM caja WHERE id = ?");
        $stmt->execute([$cajaCerrada['id']]);
        $cajaActualizada = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "   - Estado de la caja: " . ($cajaActualizada['estado_caja'] == 1 ? "Abierta ✓" : "Cerrada ✗") . "\n";
        echo "   - Balance actual: L. " . number_format($cajaActualizada['balance'], 2) . "\n";
        
        // Verificar transacción
        $stmt = $pdo->prepare("SELECT * FROM transaccion WHERE caja_id = ? AND tipo = 'saldo_inicial' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$cajaCerrada['id']]);
        $transaccion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaccion) {
            echo "   - Transacción registrada: L. " . number_format($transaccion['efectivo'], 2) . "\n";
            echo "   - Descripción: " . $transaccion['descripcion'] . "\n";
        }
        
        echo "\n✅ PRUEBA COMPLETADA EXITOSAMENTE\n";
        
    } else {
        echo "⚠️  No se encontró ninguna caja cerrada para probar el saldo inicial\n";
        
        // Crear una caja de prueba cerrada
        echo "\n2. Creando caja de prueba...\n";
        $stmt = $pdo->prepare("INSERT INTO caja (users_id, balance, estado_caja, created_at, updated_at) VALUES (1, 0, 0, NOW(), NOW())");
        $stmt->execute();
        $nuevaCajaId = $pdo->lastInsertId();
        
        echo "✓ Caja de prueba creada con ID: $nuevaCajaId\n";
        echo "   Puedes ejecutar esta prueba nuevamente para probar el saldo inicial\n";
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>
