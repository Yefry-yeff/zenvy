<?php
$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== ESTRUCTURA DE DATOS PARA PDF ===\n\n";

    // Verificar estructura de empresa
    echo "1. Estructura tabla empresa:\n";
    $stmt = $pdo->query('DESCRIBE empresa');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   {$row['Field']} ({$row['Type']})\n";
    }

    // Verificar estructura de tienda
    echo "\n2. Estructura tabla tienda:\n";
    $stmt = $pdo->query('DESCRIBE tienda');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   {$row['Field']} ({$row['Type']})\n";
    }

    // Verificar estructura de direccion
    echo "\n3. Estructura tabla direccion:\n";
    $stmt = $pdo->query('DESCRIBE direccion');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   {$row['Field']} ({$row['Type']})\n";
    }

    // Verificar datos reales de empresa con tienda y direccion
    echo "\n4. Datos reales de empresa (primera empresa):\n";
    $stmt = $pdo->query('
        SELECT e.*, t.*, d.*
        FROM empresa e
        LEFT JOIN tienda t ON e.id = t.empresa_id
        LEFT JOIN direccion d ON t.direccion_sucursal_id = d.id
        LIMIT 1
    ');
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($empresa) {
        foreach ($empresa as $campo => $valor) {
            echo "   $campo: $valor\n";
        }
    }

    // Verificar qué datos está enviando el controlador
    echo "\n5. Simulando query del controlador para factura ID 32:\n";
    $stmt = $pdo->prepare('
        SELECT f.*, e.*, t.denominacion_social as tienda_nombre, t.telefono as tienda_telefono,
               d.direccion, d.ciudad, d.departamento, d.pais,
               c.numero_cai, c.fecha_limite, c.rango_inicial, c.rango_final
        FROM factura f
        LEFT JOIN users u ON f.users_id = u.id
        LEFT JOIN tienda t ON u.tienda_id = t.id
        LEFT JOIN empresa e ON t.empresa_id = e.id
        LEFT JOIN direccion d ON t.direccion_sucursal_id = d.id
        LEFT JOIN cai c ON f.cai_id = c.id
        WHERE f.id = ?
    ');
    $stmt->execute([32]);
    $facturaData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($facturaData) {
        echo "   Campos disponibles en la consulta:\n";
        foreach ($facturaData as $campo => $valor) {
            echo "     $campo: $valor\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
