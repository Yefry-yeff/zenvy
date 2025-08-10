<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== VERIFICACIÓN DE ESTADOS DE CAJA ===\n\n";

    // Mostrar todas las cajas
    echo "1. Todas las cajas en el sistema:\n";
    $stmt = $pdo->query("SELECT id, users_id, balance, estado_caja, created_at FROM caja ORDER BY created_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $estadoTexto = match($row['estado_caja']) {
            0 => 'Sin usar',
            1 => 'Abierta',
            2 => 'Cerrada',
            default => 'Desconocido'
        };
        echo "   - ID: {$row['id']}, Usuario: {$row['users_id']}, Balance: L. " . number_format($row['balance'], 2) . ", Estado: {$row['estado_caja']} ($estadoTexto), Fecha: {$row['created_at']}\n";
    }

    echo "\n2. Cajas con estado 2 (Cerradas):\n";
    $stmt = $pdo->query("SELECT id, users_id, balance, estado_caja, created_at FROM caja WHERE estado_caja = 2 ORDER BY created_at DESC");
    $cajasEstado2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($cajasEstado2) > 0) {
        foreach ($cajasEstado2 as $caja) {
            echo "   ✓ ID: {$caja['id']}, Usuario: {$caja['users_id']}, Balance: L. " . number_format($caja['balance'], 2) . ", Fecha: {$caja['created_at']}\n";
        }
    } else {
        echo "   ⚠️  No hay cajas con estado 2 (Cerradas)\n";
    }

    echo "\n3. Usuarios en el sistema:\n";
    $stmt = $pdo->query("SELECT id, name, email FROM users ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   - ID: {$row['id']}, Nombre: {$row['name']}, Email: {$row['email']}\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
