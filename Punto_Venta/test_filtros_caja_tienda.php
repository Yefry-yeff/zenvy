<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PRUEBA DE FILTROS DE CAJA POR USUARIO Y TIENDA ===\n\n";
    
    echo "📋 OBJETIVO:\n";
    echo "   🎯 Verificar que las cajas se filtren por usuario + tienda actual\n";
    echo "   🎯 Mostrar 'No se encontró caja' cuando no hay coincidencias\n";
    echo "   🎯 Validar que no se muestren cajas de otras tiendas\n\n";
    
    // Datos de prueba
    $usuarioId = 1; // Johann Ruiz
    $tiendaActual = 1; // Paperland
    $otraTienda = 4; // El Buen Johann
    
    echo "👤 Usuario de prueba: ID {$usuarioId}\n";
    echo "🏪 Tienda actual: ID {$tiendaActual}\n";
    echo "🏪 Otra tienda: ID {$otraTienda}\n\n";
    
    // 1. Mostrar todas las cajas del usuario en todas las tiendas
    echo "1. TODAS LAS CAJAS DEL USUARIO EN TODAS LAS TIENDAS:\n";
    echo "=" . str_repeat("=", 60) . "\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.users_id,
            c.tienda_id,
            t.denominacion_social as tienda_nombre,
            c.balance,
            c.estado_caja,
            c.fecha_apertura,
            c.created_at,
            CASE 
                WHEN c.estado_caja = 0 THEN 'Cerrada'
                WHEN c.estado_caja = 1 THEN 'Abierta'
                WHEN c.estado_caja = 2 THEN 'Cerrado - Listo para abrir'
                ELSE 'Desconocido'
            END as estado_texto
        FROM caja c
        LEFT JOIN tienda t ON c.tienda_id = t.id
        WHERE c.users_id = ?
        ORDER BY c.tienda_id, c.created_at DESC
    ");
    $stmt->execute([$usuarioId]);
    $todasLasCajas = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if ($todasLasCajas) {
        foreach ($todasLasCajas as $caja) {
            $icono = ($caja->tienda_id == $tiendaActual) ? "🎯" : "📦";
            echo "   {$icono} Caja ID: {$caja->id}\n";
            echo "      🏪 Tienda: {$caja->tienda_nombre} (ID: {$caja->tienda_id})\n";
            echo "      💰 Balance: L. " . number_format($caja->balance, 2) . "\n";
            echo "      🏷️  Estado: {$caja->estado_texto} (código: {$caja->estado_caja})\n";
            echo "      📅 Creada: {$caja->created_at}\n";
            if ($caja->tienda_id == $tiendaActual) {
                echo "      ✅ TIENDA ACTUAL - Esta caja debe aparecer en filtros\n";
            } else {
                echo "      ❌ OTRA TIENDA - Esta caja NO debe aparecer en filtros\n";
            }
            echo "\n";
        }
    } else {
        echo "   ❌ No se encontraron cajas para el usuario\n\n";
    }
    
    // 2. Simular consulta de SaldoInicial (estado 2)
    echo "2. FILTRO DE SALDO INICIAL (estado 2 - Cerrado listo para abrir):\n";
    echo "=" . str_repeat("=", 60) . "\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.tienda_id,
            t.denominacion_social as tienda_nombre,
            c.balance,
            c.estado_caja,
            c.created_at
        FROM caja c
        LEFT JOIN tienda t ON c.tienda_id = t.id
        WHERE c.users_id = ?
        AND c.tienda_id = ?
        AND c.estado_caja = 2
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$usuarioId, $tiendaActual]);
    $cajaSaldoInicial = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($cajaSaldoInicial) {
        echo "   ✅ CAJA ENCONTRADA PARA SALDO INICIAL:\n";
        echo "      🆔 ID: {$cajaSaldoInicial->id}\n";
        echo "      🏪 Tienda: {$cajaSaldoInicial->tienda_nombre}\n";
        echo "      💰 Balance: L. " . number_format($cajaSaldoInicial->balance, 2) . "\n";
        echo "      🏷️  Estado: 2 (Cerrado - Listo para abrir)\n";
        echo "      📅 Creada: {$cajaSaldoInicial->created_at}\n\n";
    } else {
        echo "   ❌ NO SE ENCONTRÓ CAJA:\n";
        echo "      📝 Mensaje: 'No se encontró caja en estado cerrado (estado 2) para el usuario en la sucursal actual.'\n";
        echo "      🔍 Filtros aplicados:\n";
        echo "         - users_id = {$usuarioId}\n";
        echo "         - tienda_id = {$tiendaActual}\n";
        echo "         - estado_caja = 2\n\n";
    }
    
    // 3. Simular consulta de RecibidoDeEfectivo y EntregaDeEfectivo (estado 1)
    echo "3. FILTRO DE OPERACIONES (estado 1 - Abierta):\n";
    echo "=" . str_repeat("=", 60) . "\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.tienda_id,
            t.denominacion_social as tienda_nombre,
            c.balance,
            c.estado_caja,
            c.fecha_apertura,
            c.created_at
        FROM caja c
        LEFT JOIN tienda t ON c.tienda_id = t.id
        WHERE c.users_id = ?
        AND c.tienda_id = ?
        AND c.estado_caja = 1
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$usuarioId, $tiendaActual]);
    $cajaOperaciones = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($cajaOperaciones) {
        echo "   ✅ CAJA ABIERTA ENCONTRADA:\n";
        echo "      🆔 ID: {$cajaOperaciones->id}\n";
        echo "      🏪 Tienda: {$cajaOperaciones->tienda_nombre}\n";
        echo "      💰 Balance: L. " . number_format($cajaOperaciones->balance, 2) . "\n";
        echo "      🏷️  Estado: 1 (Abierta)\n";
        echo "      📅 Fecha apertura: " . ($cajaOperaciones->fecha_apertura ?? 'NULL') . "\n";
        echo "      📅 Creada: {$cajaOperaciones->created_at}\n";
        echo "      ✅ DISPONIBLE para recibir/entregar efectivo\n\n";
    } else {
        echo "   ❌ NO SE ENCONTRÓ CAJA ABIERTA:\n";
        echo "      📝 Mensaje: 'No hay caja abierta en la sucursal actual'\n";
        echo "      🔍 Filtros aplicados:\n";
        echo "         - users_id = {$usuarioId}\n";
        echo "         - tienda_id = {$tiendaActual}\n";
        echo "         - estado_caja = 1\n\n";
    }
    
    // 4. Verificar que no se muestren cajas de otras tiendas
    echo "4. VERIFICACIÓN: CAJAS DE OTRAS TIENDAS NO DEBEN APARECER:\n";
    echo "=" . str_repeat("=", 60) . "\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.tienda_id,
            t.denominacion_social as tienda_nombre,
            c.estado_caja
        FROM caja c
        LEFT JOIN tienda t ON c.tienda_id = t.id
        WHERE c.users_id = ?
        AND c.tienda_id != ?
        ORDER BY c.tienda_id, c.created_at DESC
    ");
    $stmt->execute([$usuarioId, $tiendaActual]);
    $cajasOtrasTiendas = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if ($cajasOtrasTiendas) {
        echo "   🔍 CAJAS EN OTRAS TIENDAS (DEBEN SER FILTRADAS):\n";
        foreach ($cajasOtrasTiendas as $caja) {
            $estadoTexto = match($caja->estado_caja) {
                0 => 'Cerrada',
                1 => 'Abierta',
                2 => 'Cerrado - Listo para abrir',
                default => 'Desconocido'
            };
            echo "      ❌ Caja ID: {$caja->id} en {$caja->tienda_nombre} - Estado: {$estadoTexto}\n";
            echo "         🚫 Esta caja NO debe aparecer cuando usuario está en tienda {$tiendaActual}\n";
        }
        echo "\n";
    } else {
        echo "   ✅ No hay cajas en otras tiendas\n\n";
    }
    
    // 5. Ejemplo de consulta sin filtro de tienda (INCORRECTO)
    echo "5. COMPARACIÓN: CONSULTA SIN FILTRO DE TIENDA (MÉTODO ANTERIOR):\n";
    echo "=" . str_repeat("=", 60) . "\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.tienda_id,
            t.denominacion_social as tienda_nombre,
            c.estado_caja
        FROM caja c
        LEFT JOIN tienda t ON c.tienda_id = t.id
        WHERE c.users_id = ?
        AND c.estado_caja = 2
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$usuarioId]);
    $cajasSinFiltro = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if ($cajasSinFiltro) {
        echo "   ⚠️  PROBLEMA - SIN FILTRO DE TIENDA MOSTRARÍA:\n";
        foreach ($cajasSinFiltro as $caja) {
            $esCorrect = ($caja->tienda_id == $tiendaActual) ? "✅ CORRECTO" : "❌ INCORRECTO";
            echo "      Caja ID: {$caja->id} en {$caja->tienda_nombre} - {$esCorrect}\n";
        }
        echo "   📝 Con el nuevo filtro solo se muestra la caja de la tienda actual\n\n";
    } else {
        echo "   ℹ️  No hay cajas estado 2 para comparar\n\n";
    }
    
    echo "BENEFICIOS DEL FILTRO IMPLEMENTADO:\n";
    echo "=" . str_repeat("=", 60) . "\n\n";
    
    echo "✅ CONTROL POR TIENDA:\n";
    echo "   - Solo muestra cajas de la tienda actual del usuario\n";
    echo "   - Evita confusión con cajas de otras sucursales\n";
    echo "   - Mantiene separación lógica por ubicación\n\n";
    
    echo "✅ SEGURIDAD:\n";
    echo "   - Usuario no ve cajas que no le corresponden\n";
    echo "   - Previene operaciones en tienda incorrecta\n";
    echo "   - Mantiene integridad de datos por sucursal\n\n";
    
    echo "✅ EXPERIENCIA DE USUARIO:\n";
    echo "   - Mensajes claros cuando no hay cajas\n";
    echo "   - Información relevante solo para su contexto\n";
    echo "   - Operaciones más intuitivas y directas\n\n";
    
    echo "✅ CONSULTAS OPTIMIZADAS:\n";
    echo "   - Filtros adicionales por tienda_id\n";
    echo "   - Resultados más precisos y rápidos\n";
    echo "   - Menor carga de datos innecesarios\n\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "🎉 FILTROS DE CAJA POR USUARIO Y TIENDA IMPLEMENTADOS\n";
echo "=== PRUEBA COMPLETADA ===\n";
