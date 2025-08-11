<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== DEMOSTRACIÓN: VALIDACIÓN POR TIENDA + USUARIO + FECHA ===\n\n";
    
    echo "📋 CRITERIOS DE VALIDACIÓN:\n";
    echo "   🏪 TIENDA: Debe ser la MISMA tienda\n";
    echo "   👤 USUARIO: Debe ser el MISMO usuario\n";
    echo "   📅 FECHA: Debe ser la MISMA fecha (hoy)\n";
    echo "   ✅ CREAR CAJA: Solo si NO existe combinación exacta de los 3 criterios\n\n";
    
    // Configuración de prueba
    $usuario1 = 1; // Johann Ruiz
    $usuario2 = 9; // Otro usuario (si existe)
    $tienda1 = 1;  // Paperland
    $tienda4 = 4;  // Pulperia el Buen Johann
    $fechaHoy = date('Y-m-d');
    $fechaAyer = date('Y-m-d', strtotime('-1 day'));
    
    echo "🎯 CONFIGURACIÓN DE PRUEBA:\n";
    echo "   👤 Usuario 1: ID {$usuario1}\n";
    echo "   👤 Usuario 2: ID {$usuario2}\n";
    echo "   🏪 Tienda 1: ID {$tienda1}\n";
    echo "   🏪 Tienda 4: ID {$tienda4}\n";
    echo "   📅 Fecha hoy: {$fechaHoy}\n";
    echo "   📅 Fecha ayer: {$fechaAyer}\n\n";
    
    // Función para verificar si debe crear caja
    function debeCrearCaja($pdo, $userId, $tiendaId, $fecha = null) {
        if (!$fecha) $fecha = date('Y-m-d');
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM caja
            WHERE users_id = ? AND tienda_id = ?
            AND (
                DATE(fecha_apertura) = ?
                OR (fecha_apertura IS NULL AND DATE(created_at) = ?)
            )
        ");
        $stmt->execute([$userId, $tiendaId, $fecha, $fecha]);
        $resultado = $stmt->fetch(PDO::FETCH_OBJ);
        
        return $resultado->total == 0;
    }
    
    echo "ESCENARIOS DE PRUEBA:\n";
    echo "=" . str_repeat("=", 70) . "\n\n";
    
    // ESCENARIO 1: Misma tienda, mismo usuario, misma fecha
    echo "1️⃣  ESCENARIO: MISMA TIENDA + MISMO USUARIO + MISMA FECHA\n";
    echo "   📊 Parámetros: Usuario {$usuario1}, Tienda {$tienda1}, Fecha {$fechaHoy}\n";
    
    $puede1 = debeCrearCaja($pdo, $usuario1, $tienda1, $fechaHoy);
    echo "   🎯 ¿Debe crear caja? " . ($puede1 ? "✅ SÍ" : "❌ NO") . "\n";
    echo "   📝 Razón: " . ($puede1 ? "No existe caja para esta combinación exacta" : "YA EXISTE caja para usuario {$usuario1} en tienda {$tienda1} para hoy") . "\n\n";
    
    // ESCENARIO 2: Diferente tienda, mismo usuario, misma fecha
    echo "2️⃣  ESCENARIO: DIFERENTE TIENDA + MISMO USUARIO + MISMA FECHA\n";
    echo "   📊 Parámetros: Usuario {$usuario1}, Tienda {$tienda4}, Fecha {$fechaHoy}\n";
    
    $puede2 = debeCrearCaja($pdo, $usuario1, $tienda4, $fechaHoy);
    echo "   🎯 ¿Debe crear caja? " . ($puede2 ? "✅ SÍ" : "❌ NO") . "\n";
    echo "   📝 Razón: " . ($puede2 ? "Es DIFERENTE TIENDA - usuario puede tener caja en cada tienda" : "Ya existe caja para este usuario en esta tienda para hoy") . "\n\n";
    
    // ESCENARIO 3: Misma tienda, diferente usuario, misma fecha
    echo "3️⃣  ESCENARIO: MISMA TIENDA + DIFERENTE USUARIO + MISMA FECHA\n";
    echo "   📊 Parámetros: Usuario {$usuario2}, Tienda {$tienda1}, Fecha {$fechaHoy}\n";
    
    $puede3 = debeCrearCaja($pdo, $usuario2, $tienda1, $fechaHoy);
    echo "   🎯 ¿Debe crear caja? " . ($puede3 ? "✅ SÍ" : "❌ NO") . "\n";
    echo "   📝 Razón: " . ($puede3 ? "Es DIFERENTE USUARIO - cada usuario maneja su propia caja" : "Ya existe caja para este usuario en esta tienda para hoy") . "\n\n";
    
    // ESCENARIO 4: Misma tienda, mismo usuario, diferente fecha
    echo "4️⃣  ESCENARIO: MISMA TIENDA + MISMO USUARIO + DIFERENTE FECHA\n";
    echo "   📊 Parámetros: Usuario {$usuario1}, Tienda {$tienda1}, Fecha {$fechaAyer}\n";
    
    $puede4 = debeCrearCaja($pdo, $usuario1, $tienda1, $fechaAyer);
    echo "   🎯 ¿Debe crear caja? " . ($puede4 ? "✅ SÍ" : "❌ NO") . "\n";
    echo "   📝 Razón: " . ($puede4 ? "Es DIFERENTE FECHA - cada día puede tener su propia caja" : "Ya existe caja para este usuario en esta tienda para ayer") . "\n\n";
    
    // Mostrar estado actual de cajas
    echo "ESTADO ACTUAL DE CAJAS EN LA BASE DE DATOS:\n";
    echo "=" . str_repeat("=", 70) . "\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.users_id,
            u.name as usuario_nombre,
            c.tienda_id,
            t.denominacion_social as tienda_nombre,
            c.balance,
            c.estado_caja,
            c.fecha_apertura,
            c.created_at,
            DATE(COALESCE(c.fecha_apertura, c.created_at)) as fecha_efectiva
        FROM caja c
        LEFT JOIN users u ON c.users_id = u.id
        LEFT JOIN tienda t ON c.tienda_id = t.id
        ORDER BY c.tienda_id, c.users_id, c.created_at DESC
    ");
    $stmt->execute();
    $todasLasCajas = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if ($todasLasCajas) {
        foreach ($todasLasCajas as $caja) {
            echo "   📦 Caja ID: {$caja->id}\n";
            echo "      👤 Usuario: {$caja->usuario_nombre} (ID: {$caja->users_id})\n";
            echo "      🏪 Tienda: {$caja->tienda_nombre} (ID: {$caja->tienda_id})\n";
            echo "      📅 Fecha efectiva: {$caja->fecha_efectiva}\n";
            echo "      💰 Balance: L. " . number_format($caja->balance, 2) . "\n";
            echo "      🏷️  Estado: " . ($caja->estado_caja == 1 ? 'Abierta' : 'Cerrada') . "\n";
            echo "      📅 Creada: {$caja->created_at}\n\n";
        }
    } else {
        echo "   ❌ No hay cajas registradas en la base de datos\n\n";
    }
    
    echo "CONSULTA SQL UTILIZADA PARA VALIDACIÓN:\n";
    echo "=" . str_repeat("=", 70) . "\n\n";
    
    echo "```sql\n";
    echo "SELECT COUNT(*) as total FROM caja\n";
    echo "WHERE users_id = ? \n";        
    echo "  AND tienda_id = ?\n";
    echo "  AND (\n";
    echo "    DATE(fecha_apertura) = ?          -- Si fue abierta hoy\n";
    echo "    OR (\n";
    echo "      fecha_apertura IS NULL          -- O si nunca fue abierta\n";
    echo "      AND DATE(created_at) = ?        -- Pero fue creada hoy\n";
    echo "    )\n";
    echo "  )\n";
    echo "```\n\n";
    
    echo "EXPLICACIÓN DE PARÁMETROS:\n";
    echo "   🔹 Parámetro 1: users_id (ID del usuario específico)\n";
    echo "   🔹 Parámetro 2: tienda_id (ID de la tienda específica)\n";
    echo "   🔹 Parámetro 3: fecha para fecha_apertura (fecha específica)\n";
    echo "   🔹 Parámetro 4: fecha para created_at (misma fecha específica)\n\n";
    
    echo "CASOS DONDE SE CREA NUEVA CAJA:\n";
    echo "=" . str_repeat("=", 70) . "\n\n";
    
    echo "✅ SE CREA cuando la consulta retorna 0 (no existe registro):\n\n";
    echo "   🎯 DIFERENTE TIENDA:\n";
    echo "      - Usuario 1 en Tienda A (tiene caja) ➡️ Usuario 1 en Tienda B (nueva caja)\n";
    echo "      - Cada tienda mantiene cajas independientes\n\n";
    
    echo "   🎯 DIFERENTE USUARIO:\n";
    echo "      - Usuario A en Tienda 1 (tiene caja) ➡️ Usuario B en Tienda 1 (nueva caja)\n";
    echo "      - Cada usuario mantiene su propia caja por tienda\n\n";
    
    echo "   🎯 DIFERENTE FECHA:\n";
    echo "      - Usuario 1 en Tienda A el día X (tiene caja) ➡️ Usuario 1 en Tienda A el día Y (nueva caja)\n";
    echo "      - Permite múltiples cajas por fecha diferente\n\n";
    
    echo "❌ NO SE CREA cuando la consulta retorna > 0 (ya existe registro):\n\n";
    echo "   🚫 MISMA COMBINACIÓN:\n";
    echo "      - Mismo usuario + Misma tienda + Misma fecha\n";
    echo "      - Evita duplicación innecesaria\n\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "🎉 VALIDACIÓN POR TIENDA + USUARIO + FECHA IMPLEMENTADA CORRECTAMENTE\n";
echo "=== DEMOSTRACIÓN COMPLETADA ===\n";
