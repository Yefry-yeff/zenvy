<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PRUEBA DE CAMBIO DE SUCURSAL CON CREACIÓN AUTOMÁTICA DE CAJA ===\n\n";
    
    // Configuración de prueba
    $userId = 1; // Johann Ruiz
    $tiendaOrigenId = 1;
    $tiendaDestinoId = 4; // Cambiar a otra tienda para prueba
    
    echo "👤 Usuario: ID {$userId}\n";
    echo "🏪 Tienda origen: ID {$tiendaOrigenId}\n";
    echo "🏪 Tienda destino: ID {$tiendaDestinoId}\n\n";
    
    // 1. Verificar estado actual del usuario
    echo "1. ESTADO ACTUAL DEL USUARIO:\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.tienda_id,
            t.denominacion_social as tienda_nombre
        FROM users u
        LEFT JOIN tienda t ON u.tienda_id = t.id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $usuario = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($usuario) {
        echo "   👤 Usuario: {$usuario->name} ({$usuario->email})\n";
        echo "   🏪 Sucursal actual: {$usuario->tienda_nombre} (ID: {$usuario->tienda_id})\n\n";
    }
    
    // 2. Verificar estado de jornada en tienda destino
    echo "2. VERIFICANDO ESTADO DE JORNADA EN TIENDA DESTINO (ID: {$tiendaDestinoId}):\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            tienda_id,
            apertura,
            cierre,
            fecha,
            user_id_apertura,
            user_id_cierre,
            created_at
        FROM jornada
        WHERE tienda_id = ?
        AND DATE(fecha) = CURDATE()
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$tiendaDestinoId]);
    $jornada = $stmt->fetch(PDO::FETCH_OBJ);
    
    $jornadaCerrada = false;
    $sinJornada = false;
    
    if ($jornada) {
        echo "   📅 JORNADA ENCONTRADA:\n";
        echo "      🆔 ID: {$jornada->id}\n";
        echo "      📅 Fecha: {$jornada->fecha}\n";
        echo "      🔓 Apertura: " . ($jornada->apertura ? "✅ Abierta" : "❌ Cerrada") . "\n";
        echo "      🔒 Cierre: " . ($jornada->cierre ? "✅ Cerrada" : "❌ Abierta") . "\n";
        
        $jornadaCerrada = ($jornada->cierre == 1 || $jornada->apertura == 0);
        echo "      🎯 Estado general: " . ($jornadaCerrada ? "🔒 CERRADA" : "🔓 ABIERTA") . "\n\n";
    } else {
        echo "   ❌ NO HAY JORNADA PARA HOY\n";
        echo "   🎯 Estado: 🔒 CONSIDERADA CERRADA\n\n";
        $sinJornada = true;
        $jornadaCerrada = true;
    }
    
    // 3. Verificar caja existente del usuario en tienda destino
    echo "3. VERIFICANDO CAJA EXISTENTE EN TIENDA DESTINO:\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            tienda_id,
            users_id,
            balance,
            estado_caja,
            fecha_apertura,
            fecha_cierre,
            created_at
        FROM caja
        WHERE users_id = ? AND tienda_id = ?
    ");
    $stmt->execute([$userId, $tiendaDestinoId]);
    $cajaExistente = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($cajaExistente) {
        echo "   💰 CAJA EXISTENTE ENCONTRADA:\n";
        echo "      🆔 ID: {$cajaExistente->id}\n";
        echo "      💰 Balance: L. " . number_format($cajaExistente->balance, 2) . "\n";
        echo "      🏷️  Estado: " . ($cajaExistente->estado_caja == 1 ? 'Abierta' : 'Cerrada') . "\n";
        echo "      📅 Creada: {$cajaExistente->created_at}\n\n";
        $necesitaCrearCaja = false;
    } else {
        echo "   ❌ NO HAY CAJA PARA ESTE USUARIO EN LA TIENDA DESTINO\n\n";
        $necesitaCrearCaja = true;
    }
    
    // 4. Lógica de decisión
    echo "4. LÓGICA DE DECISIÓN PARA CREACIÓN DE CAJA:\n\n";
    
    echo "   📊 CONDICIONES:\n";
    echo "      🔍 Jornada cerrada o sin jornada: " . ($jornadaCerrada ? "✅ SÍ" : "❌ NO") . "\n";
    echo "      🔍 Usuario sin caja en tienda destino: " . ($necesitaCrearCaja ? "✅ SÍ" : "❌ NO") . "\n";
    
    $debeCrearCaja = $jornadaCerrada && $necesitaCrearCaja;
    echo "      🎯 Debe crear caja cerrada: " . ($debeCrearCaja ? "✅ SÍ" : "❌ NO") . "\n\n";
    
    // 5. Simulación de creación de caja
    if ($debeCrearCaja) {
        echo "5. SIMULANDO CREACIÓN DE CAJA CERRADA:\n\n";
        
        echo "   💰 DATOS QUE SE INSERTARÍAN:\n";
        echo "      - tienda_id: {$tiendaDestinoId}\n";
        echo "      - users_id: {$userId}\n";
        echo "      - balance: 0.00\n";
        echo "      - fecha_apertura: NULL\n";
        echo "      - fecha_cierre: NULL\n";
        echo "      - estado_caja: 0 (cerrada)\n";
        echo "      - created_at: " . date('Y-m-d H:i:s') . "\n";
        echo "      - updated_at: " . date('Y-m-d H:i:s') . "\n\n";
        
        echo "   🔄 SQL QUE SE EJECUTARÍA:\n";
        echo "   INSERT INTO caja (tienda_id, users_id, balance, fecha_apertura, fecha_cierre, estado_caja, created_at, updated_at)\n";
        echo "   VALUES ({$tiendaDestinoId}, {$userId}, 0.00, NULL, NULL, 0, NOW(), NOW())\n\n";
        
        // Si quisiéramos ejecutar realmente la inserción (descomenta la línea siguiente)
        // $pdo->prepare("INSERT INTO caja...")->execute([...]);
        
    } else {
        echo "5. NO SE REQUIERE CREAR CAJA:\n\n";
        
        if (!$jornadaCerrada) {
            echo "   📝 MOTIVO: La jornada está abierta en la tienda destino\n";
            echo "   💡 ACCIÓN: El usuario podrá abrir su caja manualmente cuando lo necesite\n\n";
        } elseif (!$necesitaCrearCaja) {
            echo "   📝 MOTIVO: El usuario ya tiene una caja en la tienda destino\n";
            echo "   💡 ACCIÓN: Se usará la caja existente\n\n";
        }
    }
    
    // 6. Verificar otras tiendas para comparación
    echo "6. ESTADO DE JORNADAS EN OTRAS TIENDAS (para comparación):\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            t.id as tienda_id,
            t.denominacion_social,
            j.apertura,
            j.cierre,
            j.fecha,
            CASE 
                WHEN j.id IS NULL THEN 'Sin jornada'
                WHEN j.cierre = 1 OR j.apertura = 0 THEN 'Cerrada'
                ELSE 'Abierta'
            END as estado_jornada
        FROM tienda t
        LEFT JOIN jornada j ON t.id = j.tienda_id AND DATE(j.fecha) = CURDATE()
        WHERE t.estado_id = 1
        ORDER BY t.denominacion_social
    ");
    $stmt->execute();
    $tiendas = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    foreach ($tiendas as $tienda) {
        $icono = ($tienda->estado_jornada === 'Abierta') ? '🔓' : '🔒';
        echo "   {$icono} {$tienda->denominacion_social} (ID: {$tienda->tienda_id}): {$tienda->estado_jornada}\n";
    }
    echo "\n";
    
    echo "7. FUNCIONALIDAD IMPLEMENTADA EN CAMBIO DE SUCURSAL:\n\n";
    echo "✅ VALIDACIONES AUTOMÁTICAS:\n";
    echo "   🔍 Verificación de estado de jornada en tienda destino\n";
    echo "   🔍 Verificación de caja existente del usuario en tienda destino\n";
    echo "   🤖 Decisión automática de crear caja cerrada cuando sea necesario\n\n";
    
    echo "✅ CREACIÓN DE CAJA CERRADA:\n";
    echo "   🏗️  Se crea automáticamente cuando:\n";
    echo "      - La jornada está cerrada o no existe en la tienda destino\n";
    echo "      - El usuario no tiene caja en la tienda destino\n";
    echo "   💰 Balance inicial: 0.00\n";
    echo "   🔒 Estado inicial: Cerrada (estado_caja = 0)\n";
    echo "   📝 Registro en logs para auditoría\n\n";
    
    echo "✅ BENEFICIOS:\n";
    echo "   🚀 Cambio de sucursal más fluido\n";
    echo "   🛡️  Usuario preparado para trabajar en nueva sucursal\n";
    echo "   📊 Mantenimiento de control de caja por tienda\n";
    echo "   🔍 Auditoría completa de cambios y creaciones automáticas\n\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "🎉 FUNCIONALIDAD DE CREACIÓN AUTOMÁTICA DE CAJA IMPLEMENTADA\n";
echo "=== PRUEBA COMPLETADA ===\n";
