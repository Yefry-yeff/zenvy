<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PRUEBA DE AJUSTES MÚLTIPLES POSITIVOS Y NEGATIVOS ===\n\n";
    
    $fechaActual = date('Y-m-d');
    $tiendaId = 1;
    $userId = 1;
    
    echo "Configuración de prueba:\n";
    echo "- Fecha actual: $fechaActual\n";
    echo "- Tienda ID: $tiendaId\n";
    echo "- Usuario ID: $userId\n\n";
    
    // 1. Crear diferencia de ejemplo
    echo "1. CREANDO DIFERENCIA DE EJEMPLO:\n";
    
    // Verificar usuario
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
        
        // Crear cierre con diferencia sobrante
        $diferenciaOriginal = 150.00; // L. 150 de sobrante
        $totalEfectivo = 1000;
        $conteoEfectivo = $totalEfectivo + $diferenciaOriginal;
        
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
        
        $stmt->execute([$cajaId, $totalEfectivo, $conteoEfectivo, $diferenciaOriginal]);
        $cierreId = $pdo->lastInsertId();
        
        echo "✅ Diferencia creada:\n";
        echo "   📦 Caja ID: {$cajaId}\n";
        echo "   🆔 Cierre ID: {$cierreId}\n";
        echo "   👤 Usuario: {$usuario->name}\n";
        echo "   💰 Diferencia original: L. " . number_format($diferenciaOriginal, 2) . " (Sobrante)\n\n";
        
        // 2. Simular múltiples ajustes
        echo "2. SIMULANDO MÚLTIPLES AJUSTES:\n\n";
        
        $ajustes = [
            ['monto' => 50.00, 'descripcion' => 'Ajuste positivo: Corrección por error en conteo inicial - Reduciendo sobrante'],
            ['monto' => -20.00, 'descripcion' => 'Ajuste negativo: Se encontró efectivo adicional no contabilizado - Aumentando sobrante'],
            ['monto' => 30.00, 'descripcion' => 'Ajuste positivo: Validación de billetes falsos encontrados - Reduciendo sobrante'],
            ['monto' => -10.00, 'descripcion' => 'Ajuste negativo: Corrección por recálculo de denominaciones - Aumentando sobrante'],
            ['monto' => 100.00, 'descripcion' => 'Ajuste positivo final: Cierre definitivo de diferencia tras investigación completa']
        ];
        
        $diferenciaActual = $diferenciaOriginal;
        
        foreach ($ajustes as $index => $ajuste) {
            $numeroAjuste = $index + 1;
            echo "📝 AJUSTE #{$numeroAjuste}:\n";
            echo "   💰 Monto: " . ($ajuste['monto'] > 0 ? '+' : '') . "L. " . number_format($ajuste['monto'], 2) . "\n";
            echo "   📝 Descripción: {$ajuste['descripcion']}\n";
            echo "   🔢 Tipo: " . ($ajuste['monto'] > 0 ? 'Positivo (reduce diferencia)' : 'Negativo (aumenta diferencia)') . "\n";
            
            // Insertar ajuste
            $stmt = $pdo->prepare("
                INSERT INTO gestion_diferencia (monto, descripcion, cierre_de_caja_id, users_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$ajuste['monto'], $ajuste['descripcion'], $cierreId, $userId]);
            $ajusteId = $pdo->lastInsertId();
            
            // Calcular nueva diferencia
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(monto), 0) as total_ajustes 
                FROM gestion_diferencia 
                WHERE cierre_de_caja_id = ?
            ");
            $stmt->execute([$cierreId]);
            $totalAjustes = $stmt->fetch(PDO::FETCH_OBJ)->total_ajustes;
            
            $nuevaDiferencia = $diferenciaOriginal - $totalAjustes;
            
            // Actualizar cierre_de_caja
            $stmt = $pdo->prepare("
                UPDATE cierre_de_caja 
                SET diferencia_efectivo = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$nuevaDiferencia, $cierreId]);
            
            echo "   🆔 ID Ajuste: {$ajusteId}\n";
            echo "   📊 Diferencia antes: L. " . number_format($diferenciaActual, 2) . "\n";
            echo "   📊 Diferencia después: L. " . number_format($nuevaDiferencia, 2) . "\n";
            echo "   🏷️  Estado: " . (abs($nuevaDiferencia) < 0.01 ? "✅ CERRADA" : "⏳ ABIERTA") . "\n\n";
            
            $diferenciaActual = $nuevaDiferencia;
            
            // Si la diferencia se cerró, parar
            if (abs($nuevaDiferencia) < 0.01) {
                echo "🎉 ¡DIFERENCIA COMPLETAMENTE RESUELTA!\n\n";
                break;
            }
        }
        
        // 3. Mostrar resumen final
        echo "3. RESUMEN FINAL DE AJUSTES:\n\n";
        
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
        $todosAjustes = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $totalPositivos = 0;
        $totalNegativos = 0;
        $totalAjustes = 0;
        
        echo "📋 LISTA DE AJUSTES REALIZADOS:\n";
        foreach ($todosAjustes as $index => $ajusteDb) {
            $numero = $index + 1;
            echo "   {$numero}. " . ($ajusteDb->monto > 0 ? '+' : '') . "L. " . number_format($ajusteDb->monto, 2) . 
                 " | " . substr($ajusteDb->descripcion, 0, 50) . "...\n";
            
            if ($ajusteDb->monto > 0) {
                $totalPositivos += $ajusteDb->monto;
            } else {
                $totalNegativos += abs($ajusteDb->monto);
            }
            $totalAjustes += $ajusteDb->monto;
        }
        
        echo "\n📊 TOTALES:\n";
        echo "   💰 Diferencia original: L. " . number_format($diferenciaOriginal, 2) . "\n";
        echo "   ⬆️  Total ajustes positivos: L. " . number_format($totalPositivos, 2) . "\n";
        echo "   ⬇️  Total ajustes negativos: L. " . number_format($totalNegativos, 2) . "\n";
        echo "   🧮 Suma algebraica de ajustes: " . ($totalAjustes >= 0 ? '+' : '') . "L. " . number_format($totalAjustes, 2) . "\n";
        echo "   ⏳ Diferencia final: L. " . number_format($diferenciaActual, 2) . "\n";
        echo "   🔢 Número de ajustes: " . count($todosAjustes) . "\n";
        echo "   🏷️  Estado final: " . (abs($diferenciaActual) < 0.01 ? "✅ CERRADA" : "⏳ ABIERTA") . "\n\n";
        
    } else {
        echo "❌ No se encontraron usuarios en la tienda especificada.\n\n";
    }
    
    echo "4. FUNCIONALIDADES IMPLEMENTADAS:\n\n";
    echo "✅ AJUSTES MÚLTIPLES:\n";
    echo "   - 🔄 Permite múltiples transacciones mientras exista diferencia\n";
    echo "   - ⏳ Transacción permanece abierta hasta diferencia = 0\n";
    echo "   - 📊 Cálculo dinámico de diferencia pendiente\n";
    echo "   - 🔄 Recarga automática de datos tras cada ajuste\n\n";
    
    echo "✅ MOVIMIENTOS POSITIVOS Y NEGATIVOS:\n";
    echo "   - ➕ Monto positivo: REDUCE la diferencia\n";
    echo "   - ➖ Monto negativo: AUMENTA la diferencia\n";
    echo "   - 🧮 Suma algebraica: diferencia_original - suma_total_ajustes\n";
    echo "   - 📈 Flexibilidad total para correcciones\n\n";
    
    echo "✅ VALIDACIONES MEJORADAS:\n";
    echo "   - ❌ No permite monto = 0\n";
    echo "   - ✅ Permite montos positivos y negativos\n";
    echo "   - 📝 Descripción obligatoria para auditoría\n";
    echo "   - 🛡️  Control de jornada abierta\n\n";

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== FÓRMULA DE CÁLCULO ===\n";
echo "📐 DIFERENCIA_PENDIENTE = DIFERENCIA_ORIGINAL - SUMA_TOTAL_AJUSTES\n\n";
echo "📝 EJEMPLOS:\n";
echo "   Diferencia original: +L. 150 (sobrante)\n";
echo "   ├── Ajuste +50: Diferencia = 150 - 50 = L. 100\n";
echo "   ├── Ajuste -20: Diferencia = 150 - 50 - (-20) = L. 120\n";
echo "   ├── Ajuste +30: Diferencia = 150 - 50 + 20 - 30 = L. 90\n";
echo "   ├── Ajuste -10: Diferencia = 150 - 50 + 20 - 30 + 10 = L. 100\n";
echo "   └── Ajuste +100: Diferencia = 150 - 50 + 20 - 30 + 10 - 100 = L. 0 ✅\n";

echo "\n=== COMPORTAMIENTO DEL SISTEMA ===\n";
echo "🔓 TRANSACCIÓN ABIERTA (diferencia ≠ 0):\n";
echo "   - Permite nuevos ajustes\n";
echo "   - Se muestra en lista de diferencias\n";
echo "   - Modal azul: 'Ajuste Registrado Exitosamente'\n";

echo "\n🔒 TRANSACCIÓN CERRADA (diferencia = 0):\n";
echo "   - No aparece en lista (filtrada por HAVING)\n";
echo "   - Modal verde: 'Diferencia Completamente Resuelta'\n";
echo "   - Auditoría completa mantenida\n";

echo "\n=== FIN DE LA PRUEBA ===\n";
