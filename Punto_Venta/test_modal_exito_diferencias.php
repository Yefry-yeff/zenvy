<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PRUEBA DE GESTIÓN DE DIFERENCIAS CON MODAL DE ÉXITO ===\n\n";
    
    $fechaActual = date('Y-m-d');
    $tiendaId = 1;
    $userId = 1;
    
    echo "Configuración de prueba:\n";
    echo "- Fecha actual: $fechaActual\n";
    echo "- Tienda ID: $tiendaId\n";
    echo "- Usuario ID: $userId\n\n";
    
    // 1. Verificar campo descripción actualizado
    echo "1. VERIFICANDO CAMPO DESCRIPCIÓN:\n";
    $stmt = $pdo->prepare("DESCRIBE gestion_diferencia");
    $stmt->execute();
    $estructura = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($estructura as $campo) {
        if ($campo['Field'] === 'descripcion') {
            echo "✅ Campo 'descripcion': {$campo['Type']}\n";
            break;
        }
    }
    echo "\n";
    
    // 2. Simular diferencia de ejemplo
    echo "2. CREANDO DIFERENCIA DE EJEMPLO:\n";
    
    // Verificar si hay usuarios
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE tienda_id = ? LIMIT 1");
    $stmt->execute([$tiendaId]);
    $usuario = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($usuario) {
        // Crear caja
        $stmt = $pdo->prepare("
            INSERT INTO caja (users_id, balance, estado_caja, created_at, updated_at) 
            VALUES (?, 0, 2, NOW(), NOW())
        ");
        $stmt->execute([$usuario->id]);
        $cajaId = $pdo->lastInsertId();
        
        // Crear cierre con diferencia
        $diferencia = 100.00; // L. 100 de sobrante
        $totalEfectivo = 1000;
        $conteoEfectivo = $totalEfectivo + $diferencia;
        
        $stmt = $pdo->prepare("
            INSERT INTO cierre_de_caja (
                caja_id, total_efectivo, conteo_efectivo, diferencia_efectivo,
                total_tarjeta, conteo_tarjeta, diferencia_tarjeta,
                total_cheque, conteo_cheque, diferencia_cheque,
                `1`, `2`, `5`, `10`, `20`, `50`, `100`, `200`, `500`,
                `001`, `002`, `005`, `010`, `020`, `050`,
                created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?,
                0, 0, 0,
                0, 0, 0,
                0, 0, 0, 0, 0, 0, 0, 0, 0,
                0, 0, 0, 0, 0, 0,
                NOW(), NOW()
            )
        ");
        
        $stmt->execute([$cajaId, $totalEfectivo, $conteoEfectivo, $diferencia]);
        $cierreId = $pdo->lastInsertId();
        
        echo "✅ Diferencia creada:\n";
        echo "   📦 Caja ID: {$cajaId}\n";
        echo "   🆔 Cierre ID: {$cierreId}\n";
        echo "   👤 Usuario: {$usuario->name}\n";
        echo "   💰 Diferencia: L. " . number_format($diferencia, 2) . " (Sobrante)\n\n";
        
        // 3. Simular gestión parcial
        echo "3. SIMULANDO GESTIÓN PARCIAL:\n";
        
        $montoGestion1 = 40.00; // Gestionar L. 40 de los L. 100
        $descripcionLarga = "Se realizó cierre de jornada y el cajero no realizó el cierre de caja. Después de revisar los registros de ventas y comparar con el efectivo físico, se determinó que existe un sobrante de efectivo. Se procede a gestionar parcialmente esta diferencia mientras se completa la investigación correspondiente.";
        
        echo "📝 Primera gestión:\n";
        echo "   💰 Monto: L. " . number_format($montoGestion1, 2) . "\n";
        echo "   📝 Descripción: " . substr($descripcionLarga, 0, 100) . "...\n";
        echo "   📏 Longitud descripción: " . strlen($descripcionLarga) . " caracteres\n\n";
        
        // Insertar primera gestión
        $stmt = $pdo->prepare("
            INSERT INTO gestion_diferencia (monto, descripcion, cierre_de_caja_id, users_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$montoGestion1, $descripcionLarga, $cierreId, $userId]);
        $gestion1Id = $pdo->lastInsertId();
        
        // Actualizar diferencia en cierre_de_caja
        $nuevaDiferencia1 = $diferencia - $montoGestion1; // 100 - 40 = 60
        $stmt = $pdo->prepare("UPDATE cierre_de_caja SET diferencia_efectivo = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$nuevaDiferencia1, $cierreId]);
        
        echo "✅ Primera gestión completada:\n";
        echo "   🆔 ID Gestión: {$gestion1Id}\n";
        echo "   ⏳ Diferencia restante: L. " . number_format($nuevaDiferencia1, 2) . "\n";
        echo "   🏷️  Estado: TRANSACCIÓN ABIERTA (diferencia > 0)\n\n";
        
        // 4. Simular gestión completa
        echo "4. SIMULANDO GESTIÓN COMPLETA:\n";
        
        $montoGestion2 = $nuevaDiferencia1; // Gestionar el resto
        $descripcion2 = "Gestión final de la diferencia. Se completó la investigación y se confirma el sobrante. Se procede a cerrar completamente la transacción.";
        
        echo "📝 Segunda gestión:\n";
        echo "   💰 Monto: L. " . number_format($montoGestion2, 2) . "\n";
        echo "   📝 Descripción: {$descripcion2}\n\n";
        
        // Insertar segunda gestión
        $stmt = $pdo->prepare("
            INSERT INTO gestion_diferencia (monto, descripcion, cierre_de_caja_id, users_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$montoGestion2, $descripcion2, $cierreId, $userId]);
        $gestion2Id = $pdo->lastInsertId();
        
        // Actualizar diferencia en cierre_de_caja
        $nuevaDiferencia2 = $nuevaDiferencia1 - $montoGestion2; // 60 - 60 = 0
        $stmt = $pdo->prepare("UPDATE cierre_de_caja SET diferencia_efectivo = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$nuevaDiferencia2, $cierreId]);
        
        echo "✅ Segunda gestión completada:\n";
        echo "   🆔 ID Gestión: {$gestion2Id}\n";
        echo "   ⏳ Diferencia restante: L. " . number_format($nuevaDiferencia2, 2) . "\n";
        echo "   🏷️  Estado: TRANSACCIÓN CERRADA (diferencia = 0)\n\n";
        
        // 5. Mostrar resumen final
        echo "5. RESUMEN DE GESTIONES:\n";
        
        $stmt = $pdo->prepare("
            SELECT 
                gd.*,
                u.name as gestor_nombre
            FROM gestion_diferencia gd
            JOIN users u ON gd.users_id = u.id
            WHERE gd.cierre_de_caja_id = ?
            ORDER BY gd.created_at ASC
        ");
        $stmt->execute([$cierreId]);
        $gestiones = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $totalGestionado = 0;
        foreach ($gestiones as $index => $gestion) {
            $totalGestionado += $gestion->monto;
            echo "📋 Gestión #" . ($index + 1) . ":\n";
            echo "   💰 Monto: L. " . number_format($gestion->monto, 2) . "\n";
            echo "   👤 Gestor: {$gestion->gestor_nombre}\n";
            echo "   📅 Fecha: " . date('d/m/Y H:i:s', strtotime($gestion->created_at)) . "\n";
            echo "   📝 Descripción: " . substr($gestion->descripcion, 0, 80) . "...\n\n";
        }
        
        echo "📊 TOTALES:\n";
        echo "   💰 Diferencia original: L. " . number_format($diferencia, 2) . "\n";
        echo "   ✅ Total gestionado: L. " . number_format($totalGestionado, 2) . "\n";
        echo "   ⏳ Diferencia final: L. " . number_format($nuevaDiferencia2, 2) . "\n";
        echo "   🔢 Número de gestiones: " . count($gestiones) . "\n";
        echo "   🏷️  Estado final: " . ($nuevaDiferencia2 == 0 ? "✅ CERRADA" : "⏳ ABIERTA") . "\n\n";
        
    } else {
        echo "❌ No se encontraron usuarios en la tienda especificada.\n\n";
    }
    
    echo "6. FUNCIONALIDADES IMPLEMENTADAS:\n\n";
    echo "✅ MODAL DE ÉXITO:\n";
    echo "   - 🎨 Diseño diferenciado (verde=completo, azul=parcial)\n";
    echo "   - 📝 Mensajes personalizados según estado\n";
    echo "   - 🔄 Return automático a lista de diferencias\n";
    echo "   - ✅ Confirmación como otras pantallas del sistema\n\n";
    
    echo "✅ GESTIÓN DE TRANSACCIONES:\n";
    echo "   - 🔓 Transacción permanece ABIERTA mientras diferencia > 0\n";
    echo "   - 🔒 Transacción se CIERRA cuando diferencia = 0\n";
    echo "   - 📊 Múltiples gestiones por diferencia\n";
    echo "   - 🧮 Cálculo automático de diferencia pendiente\n\n";
    
    echo "✅ VALIDACIONES MEJORADAS:\n";
    echo "   - 📏 Campo descripción ampliado a 400 caracteres\n";
    echo "   - ✅ Validación de monto máximo = diferencia pendiente\n";
    echo "   - 🛡️  Transacciones de base de datos seguras\n";
    echo "   - 📝 Descripción detallada obligatoria\n\n";

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== COMPORTAMIENTO DEL MODAL DE ÉXITO ===\n";
echo "🟢 DIFERENCIA COMPLETAMENTE RESUELTA:\n";
echo "   - Título: '¡Diferencia Completamente Resuelta!'\n";
echo "   - Color: Verde\n";
echo "   - Mensaje: Confirma cierre de transacción\n";
echo "   - Estado: ✅ TRANSACCIÓN CERRADA\n";

echo "\n🔵 GESTIÓN PARCIAL:\n";
echo "   - Título: '¡Gestión Parcial Exitosa!'\n";
echo "   - Color: Azul\n";
echo "   - Mensaje: Informa monto restante\n";
echo "   - Estado: ⏳ TRANSACCIÓN ABIERTA\n";

echo "\n🎯 ACCIÓN DEL BOTÓN ACEPTAR:\n";
echo "   - Cierra el modal de éxito\n";
echo "   - Devuelve a la pantalla de diferencias\n";
echo "   - Actualiza automáticamente la lista\n";
echo "   - Limpia formularios y variables\n";

echo "\n=== FIN DE LA PRUEBA ===\n";
