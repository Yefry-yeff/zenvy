<?php
require_once __DIR__ . '/vendor/autoload.php';

echo "=== PRUEBA DEL ESTADO DE CAJA EN DASHBOARD ===\n\n";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Verificar usuarios y sus roles
    echo "1. Usuarios del sistema y sus roles:\n";
    $stmt = $pdo->query("
        SELECT u.id, u.name, r.txt_nombre as rol_nombre 
        FROM users u 
        LEFT JOIN roles r ON u.rol_id = r.id 
        ORDER BY u.id
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   - Usuario ID: {$row['id']}, Nombre: {$row['name']}, Rol: " . ($row['rol_nombre'] ?? 'Sin rol') . "\n";
    }

    // 2. Verificar cajas por usuario
    echo "\n2. Estado de cajas por usuario:\n";
    $stmt = $pdo->query("
        SELECT c.*, u.name as usuario_nombre 
        FROM caja c 
        LEFT JOIN users u ON c.users_id = u.id 
        ORDER BY c.users_id, c.created_at DESC
    ");
    
    $cajasPorUsuario = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $estadoTexto = match($row['estado_caja']) {
            0 => 'Sin usar',
            1 => 'Abierta',
            2 => 'Cerrada',
            default => 'Desconocido'
        };
        
        if (!isset($cajasPorUsuario[$row['users_id']])) {
            $cajasPorUsuario[$row['users_id']] = [];
        }
        
        $cajasPorUsuario[$row['users_id']][] = [
            'id' => $row['id'],
            'estado' => $row['estado_caja'],
            'estado_texto' => $estadoTexto,
            'balance' => $row['balance'],
            'fecha' => $row['created_at']
        ];
    }

    foreach ($cajasPorUsuario as $userId => $cajas) {
        $primeraCaja = $cajas[0]; // La más reciente
        echo "   - Usuario ID: $userId, Caja más reciente: ID {$primeraCaja['id']}, Estado: {$primeraCaja['estado']} ({$primeraCaja['estado_texto']}), Balance: L. " . number_format($primeraCaja['balance'], 2) . "\n";
    }

    // 3. Simular lógica del dashboard
    echo "\n3. Simulación de lógica del dashboard:\n";
    $rolesCaja = ['Cajero', 'Facturador', 'Admin', 'Administrador'];
    
    $stmt = $pdo->query("
        SELECT u.id, u.name, r.txt_nombre as rol_nombre 
        FROM users u 
        LEFT JOIN roles r ON u.rol_id = r.id 
        WHERE r.txt_nombre IN ('" . implode("','", $rolesCaja) . "')
        ORDER BY u.id
    ");
    
    while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Usuario: {$user['name']} (Rol: {$user['rol_nombre']})\n";
        
        // Buscar caja más reciente
        $stmtCaja = $pdo->prepare("
            SELECT * FROM caja 
            WHERE users_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmtCaja->execute([$user['id']]);
        $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
        
        if ($caja) {
            $estadoTexto = match($caja['estado_caja']) {
                0 => 'Sin usar',
                1 => 'Abierta',
                2 => 'Cerrada',
                default => 'Desconocido'
            };
            echo "     ✓ Caja encontrada: ID {$caja['id']}, Estado: {$caja['estado_caja']} ($estadoTexto), Balance: L. " . number_format($caja['balance'], 2) . "\n";
        } else {
            echo "     ✗ No tiene caja asignada\n";
        }
    }

    echo "\n✅ PRUEBA COMPLETADA\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
