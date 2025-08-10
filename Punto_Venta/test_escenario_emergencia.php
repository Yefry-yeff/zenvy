<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== SIMULACIÓN DEL ESCENARIO: EMERGENCIA CON CAMBIO DE SUCURSAL ===\n\n";
    
    echo "📋 ESCENARIO DESCRITO:\n";
    echo "   🏪 Tienda actual: Paperland (ID: 1)\n";
    echo "   💰 Caja actual: Abierta con L. 400.00 de balance\n";
    echo "   🚨 Situación: Emergencia - necesita ir a tienda El Buen Johann (ID: 4)\n";
    echo "   ❌ Problema: No hizo cierre de caja en Paperland\n";
    echo "   ✅ Condición: El Buen Johann ya tiene jornada iniciada\n";
    echo "   🎯 Objetivo: Crear caja con estado 2 (cerrado) en nueva tienda\n\n";
    
    $johannUserId = 1;
    $tiendaPaperlandId = 1;
    $tiendaBuenJohannId = 4;
    
    echo "👤 Usuario: Johann Ruiz (ID: {$johannUserId})\n";
    echo "🏪 Tienda origen: Paperland (ID: {$tiendaPaperlandId})\n";
    echo "🏪 Tienda destino: El Buen Johann (ID: {$tiendaBuenJohannId})\n";
    echo "📅 Fecha: " . date('Y-m-d') . "\n\n";
    
    // 1. Verificar estado actual en Paperland
    echo "1. ESTADO ACTUAL EN PAPERLAND:\n";
    echo "=" . str_repeat("=", 50) . "\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            balance,
            estado_caja,
            fecha_apertura,
            created_at
        FROM caja
        WHERE users_id = ? AND tienda_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$johannUserId, $tiendaPaperlandId]);
    $cajaPaperland = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($cajaPaperland) {
        echo "   💰 CAJA EN PAPERLAND:\n";
        echo "      🆔 ID: {$cajaPaperland->id}\n";
        echo "      💰 Balance: L. " . number_format($cajaPaperland->balance, 2) . "\n";
        $estadoTexto = match($cajaPaperland->estado_caja) {
            0 => 'Cerrada',
            1 => 'Abierta',
            2 => 'Cerrado (listo para abrir)',
            default => "Desconocido ({$cajaPaperland->estado_caja})"
        };
        echo "      🏷️  Estado: {$estadoTexto} (código: {$cajaPaperland->estado_caja})\n";
        echo "      📅 Fecha apertura: " . ($cajaPaperland->fecha_apertura ?? 'NULL') . "\n";
        echo "      📅 Creada: {$cajaPaperland->created_at}\n\n";
        
        if ($cajaPaperland->estado_caja == 1 && $cajaPaperland->balance > 0) {
            echo "   ⚠️  SITUACIÓN DETECTADA:\n";
            echo "      🔓 Caja abierta con dinero\n";
            echo "      🚨 No se realizó cierre antes de emergencia\n";
            echo "      💼 Necesita traspaso de emergencia\n\n";
        }
    } else {
        echo "   ❌ No se encontró caja en Paperland\n\n";
    }
    
    // 2. Verificar estado de jornada en El Buen Johann
    echo "2. ESTADO DE JORNADA EN EL BUEN JOHANN:\n";
    echo "=" . str_repeat("=", 50) . "\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            apertura,
            cierre,
            fecha,
            user_id_apertura,
            created_at
        FROM jornada
        WHERE tienda_id = ? AND DATE(fecha) = CURDATE()
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$tiendaBuenJohannId]);
    $jornadaBuenJohann = $stmt->fetch(PDO::FETCH_OBJ);
    
    $jornadaAbierta = false;
    
    if ($jornadaBuenJohann) {
        echo "   📅 JORNADA EN EL BUEN JOHANN:\n";
        echo "      🆔 ID: {$jornadaBuenJohann->id}\n";
        echo "      📅 Fecha: {$jornadaBuenJohann->fecha}\n";
        echo "      🔓 Apertura: " . ($jornadaBuenJohann->apertura ? "✅ Abierta" : "❌ Cerrada") . "\n";
        echo "      🔒 Cierre: " . ($jornadaBuenJohann->cierre ? "✅ Cerrada" : "❌ Abierta") . "\n";
        echo "      👤 Usuario apertura: {$jornadaBuenJohann->user_id_apertura}\n";
        echo "      📅 Creada: {$jornadaBuenJohann->created_at}\n";
        
        $jornadaAbierta = ($jornadaBuenJohann->apertura == 1 && $jornadaBuenJohann->cierre == 0);
        echo "      🎯 Estado: " . ($jornadaAbierta ? "🔓 JORNADA ABIERTA" : "🔒 JORNADA CERRADA") . "\n\n";
        
        if ($jornadaAbierta) {
            echo "   ✅ CONDICIÓN CUMPLIDA:\n";
            echo "      🏪 La tienda El Buen Johann ya inició jornada\n";
            echo "      👤 Johann puede ser transferido de emergencia\n";
            echo "      🔧 Se debe crear caja con estado 2 (cerrado)\n\n";
        }
    } else {
        echo "   ❌ NO HAY JORNADA PARA HOY EN EL BUEN JOHANN\n";
        echo "   🎯 Estado: Sin jornada iniciada\n\n";
    }
    
    // 3. Verificar caja existente en El Buen Johann
    echo "3. VERIFICANDO CAJA EXISTENTE EN EL BUEN JOHANN:\n";
    echo "=" . str_repeat("=", 50) . "\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            balance,
            estado_caja,
            fecha_apertura,
            created_at
        FROM caja
        WHERE users_id = ? AND tienda_id = ?
        AND (
            DATE(fecha_apertura) = CURDATE()
            OR (fecha_apertura IS NULL AND DATE(created_at) = CURDATE())
        )
    ");
    $stmt->execute([$johannUserId, $tiendaBuenJohannId]);
    $cajaBuenJohann = $stmt->fetch(PDO::FETCH_OBJ);
    
    $tieneCajaHoy = false;
    
    if ($cajaBuenJohann) {
        echo "   💰 CAJA EXISTENTE ENCONTRADA:\n";
        echo "      🆔 ID: {$cajaBuenJohann->id}\n";
        echo "      💰 Balance: L. " . number_format($cajaBuenJohann->balance, 2) . "\n";
        $estadoTexto = match($cajaBuenJohann->estado_caja) {
            0 => 'Cerrada',
            1 => 'Abierta', 
            2 => 'Cerrado (listo para abrir)',
            default => "Desconocido ({$cajaBuenJohann->estado_caja})"
        };
        echo "      🏷️  Estado: {$estadoTexto}\n";
        echo "      📅 Fecha apertura: " . ($cajaBuenJohann->fecha_apertura ?? 'NULL') . "\n";
        echo "      📅 Creada: {$cajaBuenJohann->created_at}\n\n";
        $tieneCajaHoy = true;
    } else {
        echo "   ❌ NO HAY CAJA PARA JOHANN EN EL BUEN JOHANN PARA HOY\n";
        echo "   ✅ PUEDE CREAR NUEVA CAJA\n\n";
        $tieneCajaHoy = false;
    }
    
    // 4. Lógica de decisión para el escenario
    echo "4. LÓGICA DE DECISIÓN PARA EMERGENCIA:\n";
    echo "=" . str_repeat("=", 50) . "\n\n";
    
    echo "   📊 CONDICIONES EVALUADAS:\n";
    echo "      🔍 Jornada abierta en destino: " . ($jornadaAbierta ? "✅ SÍ" : "❌ NO") . "\n";
    echo "      🔍 Usuario sin caja para hoy en destino: " . (!$tieneCajaHoy ? "✅ SÍ" : "❌ NO") . "\n";
    
    $debeCrearCaja = !$tieneCajaHoy;
    $estadoCajaACrear = $jornadaAbierta ? 2 : 0;
    $motivoCreacion = $jornadaAbierta ? 'Jornada abierta - caja lista para apertura manual' : 'Jornada cerrada';
    
    echo "      🎯 Debe crear caja: " . ($debeCrearCaja ? "✅ SÍ" : "❌ NO") . "\n";
    if ($debeCrearCaja) {
        echo "      🏷️  Estado a asignar: {$estadoCajaACrear} (" . ($estadoCajaACrear == 2 ? "Cerrado - listo para abrir" : "Cerrada") . ")\n";
        echo "      📝 Motivo: {$motivoCreacion}\n";
    }
    echo "\n";
    
    // 5. Simulación de creación
    if ($debeCrearCaja) {
        echo "5. SIMULANDO CREACIÓN DE CAJA:\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        echo "   💰 DATOS DE LA NUEVA CAJA:\n";
        echo "      - tienda_id: {$tiendaBuenJohannId} (El Buen Johann)\n";
        echo "      - users_id: {$johannUserId} (Johann Ruiz)\n";
        echo "      - balance: 0.00 (balance inicial)\n";
        echo "      - fecha_apertura: NULL (no abierta aún)\n";
        echo "      - fecha_cierre: NULL (no cerrada aún)\n";
        echo "      - estado_caja: {$estadoCajaACrear} (" . ($estadoCajaACrear == 2 ? "Cerrado - listo para abrir" : "Cerrada") . ")\n";
        echo "      - created_at: " . date('Y-m-d H:i:s') . "\n";
        echo "      - updated_at: " . date('Y-m-d H:i:s') . "\n\n";
        
        echo "   🔄 SQL QUE SE EJECUTARÍA:\n";
        echo "   ```sql\n";
        echo "   INSERT INTO caja (\n";
        echo "       tienda_id, users_id, balance, fecha_apertura, \n";
        echo "       fecha_cierre, estado_caja, created_at, updated_at\n";
        echo "   ) VALUES (\n";
        echo "       {$tiendaBuenJohannId}, {$johannUserId}, 0.00, NULL,\n";
        echo "       NULL, {$estadoCajaACrear}, NOW(), NOW()\n";
        echo "   )\n";
        echo "   ```\n\n";
        
        echo "   ✅ RESULTADO ESPERADO:\n";
        echo "      🎯 Johann tendrá caja lista en El Buen Johann\n";
        echo "      🔒 Estado 2 = Necesita abrir caja manualmente\n";
        echo "      💰 Balance inicial de 0.00\n";
        echo "      🔐 No podrá vender hasta abrir su caja\n";
        echo "      📊 Se mantiene control de efectivo por usuario\n\n";
    } else {
        echo "5. NO SE REQUIERE CREAR CAJA:\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        echo "   📝 MOTIVO: Johann ya tiene caja en El Buen Johann para hoy\n\n";
    }
    
    // 6. Estados de caja explicados
    echo "6. ESTADOS DE CAJA EXPLICADOS:\n";
    echo "=" . str_repeat("=", 50) . "\n\n";
    
    echo "   📋 CÓDIGOS DE ESTADO:\n";
    echo "      0️⃣  ESTADO 0 (Cerrada):\n";
    echo "          - Caja completamente cerrada\n";
    echo "          - Jornada no iniciada o ya cerrada\n";
    echo "          - No puede realizar operaciones\n\n";
    
    echo "      1️⃣  ESTADO 1 (Abierta):\n";
    echo "          - Caja operativa y funcional\n";
    echo "          - Puede procesar ventas y transacciones\n";
    echo "          - Fecha de apertura registrada\n\n";
    
    echo "      2️⃣  ESTADO 2 (Cerrado - Listo para abrir):\n";
    echo "          - Caja creada pero no abierta\n";
    echo "          - Jornada está activa en la tienda\n";
    echo "          - Usuario debe abrir manualmente\n";
    echo "          - Perfecto para transferencias de emergencia\n\n";
    
    echo "7. FLUJO COMPLETO DEL ESCENARIO:\n";
    echo "=" . str_repeat("=", 50) . "\n\n";
    
    echo "   🎬 SECUENCIA DE EVENTOS:\n";
    echo "      1. 🏪 Johann trabajando en Paperland con caja abierta\n";
    echo "      2. 🚨 Emergencia requiere ir a El Buen Johann\n";
    echo "      3. ❌ No hay tiempo para cierre formal de caja\n";
    echo "      4. 🔄 Se ejecuta cambio de sucursal\n";
    echo "      5. 🔍 Sistema detecta jornada abierta en destino\n";
    echo "      6. 💰 Sistema crea caja con estado 2\n";
    echo "      7. ✅ Johann llega y puede abrir su caja cuando esté listo\n";
    echo "      8. 🔐 Control de efectivo mantenido por usuario\n\n";
    
    echo "   🎯 BENEFICIOS DEL ESTADO 2:\n";
    echo "      ✅ No bloquea el cambio de sucursal\n";
    echo "      ✅ Mantiene control de usuario sobre apertura\n";
    echo "      ✅ Evita cajas automáticamente abiertas\n";
    echo "      ✅ Permite validación de efectivo inicial\n";
    echo "      ✅ Respeta flujo de trabajo de emergencia\n\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "🎉 ESCENARIO DE EMERGENCIA CON ESTADO 2 IMPLEMENTADO\n";
echo "=== SIMULACIÓN COMPLETADA ===\n";
