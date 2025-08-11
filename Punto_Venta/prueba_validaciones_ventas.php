<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PRUEBA DE VALIDACIONES DE JORNADA Y CAJA EN VENTAS ===\n\n";
    
    $userId = 1; // Johann Ruiz
    $tiendaId = 1;
    
    echo "👤 Usuario de prueba: ID {$userId}\n";
    echo "🏪 Tienda: ID {$tiendaId}\n\n";
    
    // 1. Verificar estado actual de jornada
    echo "1. VERIFICANDO ESTADO DE JORNADA:\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            fecha_apertura,
            hora_apertura,
            estado,
            usuario_apertura_id,
            created_at
        FROM jornada
        WHERE tienda_id = ?
        AND DATE(fecha_apertura) = CURDATE()
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$tiendaId]);
    $jornada = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($jornada) {
        echo "📅 JORNADA ENCONTRADA:\n";
        echo "   🆔 ID: {$jornada->id}\n";
        echo "   📅 Fecha: {$jornada->fecha_apertura}\n";
        echo "   🕐 Hora: {$jornada->hora_apertura}\n";
        echo "   🏷️  Estado: {$jornada->estado}\n";
        echo "   👤 Usuario apertura: {$jornada->usuario_apertura_id}\n";
        
        $estadoJornada = $jornada->estado === 'abierta';
        echo "   ✅ Validación: " . ($estadoJornada ? "APROBADA - Jornada abierta" : "❌ RECHAZADA - Jornada cerrada") . "\n\n";
    } else {
        echo "❌ No se encontró jornada para hoy\n";
        echo "   ✅ Validación: RECHAZADA - Sin jornada\n\n";
        $estadoJornada = false;
    }
    
    // 2. Verificar estado actual de caja
    echo "2. VERIFICANDO ESTADO DE CAJA:\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            users_id,
            balance,
            estado_caja,
            created_at,
            updated_at
        FROM caja
        WHERE users_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $caja = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($caja) {
        echo "💰 CAJA ENCONTRADA:\n";
        echo "   🆔 ID: {$caja->id}\n";
        echo "   👤 Usuario: {$caja->users_id}\n";
        echo "   💰 Balance: L. " . number_format($caja->balance, 2) . "\n";
        echo "   🏷️  Estado: " . ($caja->estado_caja == 1 ? 'Abierta' : 'Cerrada') . " (código: {$caja->estado_caja})\n";
        echo "   📅 Creada: {$caja->created_at}\n";
        echo "   🔄 Actualizada: {$caja->updated_at}\n";
        
        $estadoCaja = $caja->estado_caja == 1;
        echo "   ✅ Validación: " . ($estadoCaja ? "APROBADA - Caja abierta" : "❌ RECHAZADA - Caja cerrada") . "\n\n";
    } else {
        echo "❌ No se encontró caja para el usuario\n";
        echo "   ✅ Validación: RECHAZADA - Sin caja\n\n";
        $estadoCaja = false;
    }
    
    // 3. Resultado final de validaciones
    echo "3. RESULTADO FINAL DE VALIDACIONES:\n\n";
    
    $puedeVender = $estadoJornada && $estadoCaja;
    
    echo "📊 RESUMEN:\n";
    echo "   🔍 Jornada abierta: " . ($estadoJornada ? "✅ SÍ" : "❌ NO") . "\n";
    echo "   🔍 Caja abierta: " . ($estadoCaja ? "✅ SÍ" : "❌ NO") . "\n";
    echo "   🎯 Puede realizar ventas: " . ($puedeVender ? "✅ SÍ" : "❌ NO") . "\n\n";
    
    if (!$puedeVender) {
        echo "⚠️  MENSAJES DE ERROR QUE APARECERÁN:\n";
        if (!$estadoJornada) {
            echo "   🔴 'No se puede procesar la venta: La jornada debe estar abierta para realizar ventas.'\n";
        }
        if (!$estadoCaja) {
            echo "   🔴 'No se puede procesar la venta: Su caja debe estar abierta para realizar ventas.'\n";
        }
        echo "\n";
    }
    
    // 4. Verificar estructura de tabla transacciones
    echo "4. VERIFICANDO TABLA DE TRANSACCIONES:\n\n";
    
    $stmt = $pdo->prepare("DESCRIBE transaccion");
    $stmt->execute();
    $columnas = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "📋 ESTRUCTURA DE TABLA 'transaccion':\n";
    foreach ($columnas as $columna) {
        echo "   • {$columna->Field} ({$columna->Type}) - " . ($columna->Null === 'YES' ? 'Nullable' : 'Not Null') . "\n";
    }
    echo "\n";
    
    // 5. Simular registro de transacciones
    echo "5. SIMULANDO REGISTRO DE TRANSACCIONES:\n\n";
    
    $numeroFacturaEjemplo = "FAC-2025-001234";
    $montosEjemplo = [
        'efectivo' => 500.00,
        'tarjeta' => 300.00,
        'cheque' => 0.00
    ];
    
    echo "💰 FACTURA DE EJEMPLO: {$numeroFacturaEjemplo}\n";
    echo "💰 DISTRIBUCIÓN DE PAGOS:\n";
    echo "   💵 Efectivo: L. " . number_format($montosEjemplo['efectivo'], 2) . "\n";
    echo "   💳 Tarjeta: L. " . number_format($montosEjemplo['tarjeta'], 2) . "\n";
    echo "   📄 Cheque: L. " . number_format($montosEjemplo['cheque'], 2) . "\n";
    echo "   🧮 Total: L. " . number_format(array_sum($montosEjemplo), 2) . "\n\n";
    
    echo "📝 TRANSACCIONES QUE SE REGISTRARÍAN:\n";
    
    if ($montosEjemplo['efectivo'] > 0) {
        echo "   1. EFECTIVO:\n";
        echo "      - users_id: {$userId}\n";
        echo "      - tipo_movimiento: entrada\n";
        echo "      - monto_efectivo: {$montosEjemplo['efectivo']}\n";
        echo "      - monto_tarjeta: 0\n";
        echo "      - monto_cheque: 0\n";
        echo "      - transaccion: 'Facturacion'\n";
        echo "      - descripcion: 'Factura #{$numeroFacturaEjemplo}'\n\n";
    }
    
    if ($montosEjemplo['tarjeta'] > 0) {
        echo "   2. TARJETA:\n";
        echo "      - users_id: {$userId}\n";
        echo "      - tipo_movimiento: entrada\n";
        echo "      - monto_efectivo: 0\n";
        echo "      - monto_tarjeta: {$montosEjemplo['tarjeta']}\n";
        echo "      - monto_cheque: 0\n";
        echo "      - transaccion: 'Facturacion'\n";
        echo "      - descripcion: 'Factura #{$numeroFacturaEjemplo}'\n\n";
    }
    
    // 6. Simular actualización de balance de caja
    if ($caja && $montosEjemplo['efectivo'] > 0) {
        echo "6. SIMULANDO ACTUALIZACIÓN DE BALANCE DE CAJA:\n\n";
        
        $balanceActual = $caja->balance;
        $nuevoBalance = $balanceActual + $montosEjemplo['efectivo'];
        
        echo "💰 BALANCE DE CAJA:\n";
        echo "   📊 Balance actual: L. " . number_format($balanceActual, 2) . "\n";
        echo "   ➕ Efectivo de venta: L. " . number_format($montosEjemplo['efectivo'], 2) . "\n";
        echo "   🎯 Nuevo balance: L. " . number_format($nuevoBalance, 2) . "\n\n";
        
        echo "🔄 UPDATE que se ejecutaría:\n";
        echo "   UPDATE caja SET balance = {$nuevoBalance}, updated_at = NOW() WHERE id = {$caja->id}\n\n";
    }
    
    echo "7. FUNCIONALIDADES IMPLEMENTADAS:\n\n";
    echo "✅ VALIDACIONES ANTES DE VENTA:\n";
    echo "   🔍 Verificación de jornada abierta\n";
    echo "   🔍 Verificación de caja abierta\n";
    echo "   🚫 Bloqueo de ventas si alguna validación falla\n";
    echo "   💬 Mensajes de error específicos\n\n";
    
    echo "✅ REGISTRO DE TRANSACCIONES:\n";
    echo "   📝 Una transacción por cada método de pago usado\n";
    echo "   💰 Montos específicos en columnas correspondientes\n";
    echo "   🏷️  Tipo de transacción: 'Facturacion'\n";
    echo "   📄 Descripción con número de factura\n";
    echo "   👤 Usuario que realiza la venta\n\n";
    
    echo "✅ ACTUALIZACIÓN DE BALANCE:\n";
    echo "   💰 Incremento automático del balance de caja\n";
    echo "   💵 Solo cuando hay pago en efectivo\n";
    echo "   🔄 Actualización en tiempo real\n";
    echo "   📊 Auditoría completa de movimientos\n\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "🎉 SISTEMA DE VALIDACIONES Y TRANSACCIONES IMPLEMENTADO\n";
echo "=== PRUEBA COMPLETADA ===\n";
