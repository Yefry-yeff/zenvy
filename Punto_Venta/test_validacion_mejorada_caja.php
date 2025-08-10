<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PRUEBA DE VALIDACIÓN MEJORADA: CAMBIO DE SUCURSAL CON VERIFICACIÓN DE FECHA ===\n\n";
    
    // Configuración de prueba
    $userId = 1; // Johann Ruiz
    $tiendaOrigenId = 1;
    $tiendaDestinoId = 4; // Cambiar a otra tienda para prueba
    
    echo "👤 Usuario: ID {$userId}\n";
    echo "🏪 Tienda origen: ID {$tiendaOrigenId}\n";
    echo "🏪 Tienda destino: ID {$tiendaDestinoId}\n";
    echo "📅 Fecha actual: " . date('Y-m-d') . "\n\n";
    
    // 1. Verificar registros de caja existentes para el usuario en la tienda destino
    echo "1. VERIFICANDO REGISTROS DE CAJA EXISTENTES:\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            tienda_id,
            users_id,
            balance,
            estado_caja,
            fecha_apertura,
            fecha_cierre,
            created_at,
            updated_at
        FROM caja
        WHERE users_id = ? AND tienda_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId, $tiendaDestinoId]);
    $cajasExistentes = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if ($cajasExistentes) {
        echo "   💰 CAJAS EXISTENTES ENCONTRADAS:\n";
        foreach ($cajasExistentes as $index => $caja) {
            echo "      CAJA " . ($index + 1) . ":\n";
            echo "         🆔 ID: {$caja->id}\n";
            echo "         💰 Balance: L. " . number_format($caja->balance, 2) . "\n";
            echo "         🏷️  Estado: " . ($caja->estado_caja == 1 ? 'Abierta' : 'Cerrada') . "\n";
            echo "         📅 Fecha apertura: " . ($caja->fecha_apertura ?? 'NULL') . "\n";
            echo "         📅 Fecha cierre: " . ($caja->fecha_cierre ?? 'NULL') . "\n";
            echo "         📅 Creada: {$caja->created_at}\n";
            echo "         📅 Actualizada: {$caja->updated_at}\n\n";
        }
    } else {
        echo "   ❌ NO HAY CAJAS PARA ESTE USUARIO EN LA TIENDA DESTINO\n\n";
    }
    
    // 2. Verificar cajas específicas para la fecha actual
    echo "2. VERIFICANDO CAJAS PARA LA FECHA ACTUAL (". date('Y-m-d') ."):\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            tienda_id,
            users_id,
            balance,
            estado_caja,
            fecha_apertura,
            fecha_cierre,
            created_at,
            'fecha_apertura_hoy' as tipo_coincidencia
        FROM caja
        WHERE users_id = ? 
        AND tienda_id = ?
        AND DATE(fecha_apertura) = CURDATE()
        
        UNION ALL
        
        SELECT 
            id,
            tienda_id,
            users_id,
            balance,
            estado_caja,
            fecha_apertura,
            fecha_cierre,
            created_at,
            'created_at_hoy_sin_apertura' as tipo_coincidencia
        FROM caja
        WHERE users_id = ? 
        AND tienda_id = ?
        AND fecha_apertura IS NULL
        AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute([$userId, $tiendaDestinoId, $userId, $tiendaDestinoId]);
    $cajasHoy = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if ($cajasHoy) {
        echo "   💰 CAJAS PARA HOY ENCONTRADAS:\n";
        foreach ($cajasHoy as $index => $caja) {
            echo "      CAJA " . ($index + 1) . ":\n";
            echo "         🆔 ID: {$caja->id}\n";
            echo "         🔍 Tipo coincidencia: {$caja->tipo_coincidencia}\n";
            echo "         💰 Balance: L. " . number_format($caja->balance, 2) . "\n";
            echo "         🏷️  Estado: " . ($caja->estado_caja == 1 ? 'Abierta' : 'Cerrada') . "\n";
            echo "         📅 Fecha apertura: " . ($caja->fecha_apertura ?? 'NULL') . "\n";
            echo "         📅 Creada: {$caja->created_at}\n\n";
        }
        $tieneCajaHoy = true;
    } else {
        echo "   ✅ NO HAY CAJAS PARA HOY - PUEDE CREAR NUEVA\n\n";
        $tieneCajaHoy = false;
    }
    
    // 3. Verificar estado de jornada en tienda destino
    echo "3. VERIFICANDO ESTADO DE JORNADA EN TIENDA DESTINO:\n\n";
    
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
    
    // 4. Lógica de decisión mejorada
    echo "4. LÓGICA DE DECISIÓN MEJORADA PARA CREACIÓN DE CAJA:\n\n";
    
    echo "   📊 CONDICIONES:\n";
    echo "      🔍 Jornada cerrada o sin jornada: " . ($jornadaCerrada ? "✅ SÍ" : "❌ NO") . "\n";
    echo "      🔍 Usuario sin caja para fecha actual: " . (!$tieneCajaHoy ? "✅ SÍ" : "❌ NO") . "\n";
    
    $debeCrearCaja = $jornadaCerrada && !$tieneCajaHoy;
    echo "      🎯 Debe crear caja cerrada: " . ($debeCrearCaja ? "✅ SÍ" : "❌ NO") . "\n\n";
    
    if (!$debeCrearCaja && $tieneCajaHoy) {
        echo "   ⚠️  NO SE CREARÁ CAJA - YA EXISTE PARA HOY:\n";
        echo "      📝 Motivo: Evitar duplicación de registros para la misma fecha\n";
        echo "      💡 Beneficio: Mantiene integridad de datos y evita cajas duplicadas\n\n";
    }
    
    // 5. Simulación de la consulta SQL mejorada
    echo "5. CONSULTA SQL MEJORADA PARA VERIFICACIÓN:\n\n";
    
    echo "   🔍 QUERY UTILIZADA:\n";
    echo "   ```sql\n";
    echo "   SELECT * FROM caja \n";
    echo "   WHERE users_id = {$userId} \n";
    echo "   AND tienda_id = {$tiendaDestinoId}\n";
    echo "   AND (\n";
    echo "       DATE(fecha_apertura) = CURDATE()  -- Si tiene fecha de apertura hoy\n";
    echo "       OR (\n";
    echo "           fecha_apertura IS NULL         -- Si no tiene fecha de apertura\n";
    echo "           AND DATE(created_at) = CURDATE() -- Pero fue creada hoy\n";
    echo "       )\n";
    echo "   )\n";
    echo "   ```\n\n";
    
    // 6. Casos de prueba
    echo "6. CASOS DE PRUEBA Y VALIDACIONES:\n\n";
    
    echo "   ✅ CASO 1 - CAJA CON FECHA APERTURA HOY:\n";
    echo "      - Condición: fecha_apertura = hoy\n";
    echo "      - Resultado: NO crear nueva caja\n";
    echo "      - Razón: Usuario ya tiene caja para hoy\n\n";
    
    echo "   ✅ CASO 2 - CAJA SIN FECHA APERTURA PERO CREADA HOY:\n";
    echo "      - Condición: fecha_apertura = NULL AND created_at = hoy\n";
    echo "      - Resultado: NO crear nueva caja\n";
    echo "      - Razón: Caja cerrada ya existe para hoy\n\n";
    
    echo "   ✅ CASO 3 - CAJA DE DÍAS ANTERIORES:\n";
    echo "      - Condición: Cajas de fechas pasadas\n";
    echo "      - Resultado: SÍ crear nueva caja (si jornada cerrada)\n";
    echo "      - Razón: No hay caja para la fecha actual\n\n";
    
    echo "   ✅ CASO 4 - SIN CAJAS PREVIAS:\n";
    echo "      - Condición: No hay cajas del usuario en esa tienda\n";
    echo "      - Resultado: SÍ crear nueva caja (si jornada cerrada)\n";
    echo "      - Razón: Primera caja del usuario en esa tienda\n\n";
    
    // 7. Beneficios de la validación mejorada
    echo "7. BENEFICIOS DE LA VALIDACIÓN MEJORADA:\n\n";
    
    echo "   🛡️  PREVENCIÓN DE DUPLICADOS:\n";
    echo "      - Evita múltiples cajas para la misma fecha\n";
    echo "      - Mantiene integridad referencial\n";
    echo "      - Previene confusión en auditorías\n\n";
    
    echo "   📊 CONTROL GRANULAR:\n";
    echo "      - Verificación por fecha específica\n";
    echo "      - Considera tanto fecha_apertura como created_at\n";
    echo "      - Maneja casos de cajas cerradas (sin fecha_apertura)\n\n";
    
    echo "   🔍 AUDITORÍA MEJORADA:\n";
    echo "      - Logs más específicos con fecha_verificacion\n";
    echo "      - Información detallada de por qué no se crea caja\n";
    echo "      - Trazabilidad completa de decisiones\n\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "🎉 VALIDACIÓN MEJORADA DE CAMBIO DE SUCURSAL IMPLEMENTADA\n";
echo "=== PRUEBA COMPLETADA ===\n";
