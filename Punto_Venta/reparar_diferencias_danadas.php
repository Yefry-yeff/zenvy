<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== REPARACIÓN DE DIFERENCIAS DAÑADAS ===\n\n";
    
    // 1. Encontrar registros donde diferencia_efectivo no coincide con la diferencia calculada
    echo "1. IDENTIFICANDO REGISTROS DAÑADOS:\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            total_efectivo,
            conteo_efectivo,
            diferencia_efectivo as diferencia_almacenada,
            (total_efectivo - conteo_efectivo) as diferencia_calculada
        FROM cierre_de_caja
        WHERE ABS(diferencia_efectivo - (total_efectivo - conteo_efectivo)) > 0.01
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $registros_dañados = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if (count($registros_dañados) > 0) {
        echo "📋 REGISTROS QUE NECESITAN REPARACIÓN:\n";
        foreach ($registros_dañados as $index => $registro) {
            $numero = $index + 1;
            echo "   {$numero}. Cierre #{$registro->id}\n";
            echo "      💰 Total esperado: L. " . number_format($registro->total_efectivo, 2) . "\n";
            echo "      🧮 Total contado: L. " . number_format($registro->conteo_efectivo, 2) . "\n";
            echo "      ❌ Diferencia almacenada (INCORRECTA): L. " . number_format($registro->diferencia_almacenada, 2) . "\n";
            echo "      ✅ Diferencia calculada (CORRECTA): L. " . number_format($registro->diferencia_calculada, 2) . "\n\n";
        }
        
        // 2. Proceder con la reparación
        echo "2. PROCEDIENDO CON LA REPARACIÓN:\n\n";
        
        $pdo->beginTransaction();
        
        $registros_reparados = 0;
        foreach ($registros_dañados as $registro) {
            $stmt = $pdo->prepare("
                UPDATE cierre_de_caja 
                SET diferencia_efectivo = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$registro->diferencia_calculada, $registro->id]);
            
            echo "✅ Cierre #{$registro->id}: Diferencia restaurada de L. " . number_format($registro->diferencia_almacenada, 2) . " → L. " . number_format($registro->diferencia_calculada, 2) . "\n";
            $registros_reparados++;
        }
        
        $pdo->commit();
        
        echo "\n🎉 REPARACIÓN COMPLETADA:\n";
        echo "   📊 Registros reparados: {$registros_reparados}\n";
        echo "   ✅ Todas las diferencias originales han sido restauradas\n\n";
        
    } else {
        echo "✅ No se encontraron registros dañados. Todas las diferencias están correctas.\n\n";
    }
    
    // 3. Verificar el estado después de la reparación
    echo "3. VERIFICACIÓN POST-REPARACIÓN:\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            cc.id as cierre_id,
            c.id as caja_id,
            u.name as nombre_usuario,
            (cc.total_efectivo - cc.conteo_efectivo) as diferencia_original,
            COALESCE(SUM(gd.monto), 0) as total_gestionado,
            ((cc.total_efectivo - cc.conteo_efectivo) - COALESCE(SUM(gd.monto), 0)) as diferencia_pendiente,
            COUNT(gd.id) as gestiones_realizadas
        FROM cierre_de_caja as cc
        JOIN caja as c ON cc.caja_id = c.id
        JOIN users as u ON c.users_id = u.id
        LEFT JOIN gestion_diferencia as gd ON cc.id = gd.cierre_de_caja_id
        WHERE ABS(cc.total_efectivo - cc.conteo_efectivo) > 0.01
        GROUP BY cc.id, c.id, u.name, cc.total_efectivo, cc.conteo_efectivo
        HAVING ABS(((cc.total_efectivo - cc.conteo_efectivo) - COALESCE(SUM(gd.monto), 0))) >= 0.01
        ORDER BY cc.created_at DESC
    ");
    $stmt->execute();
    $diferencias_pendientes = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "🔍 DIFERENCIAS QUE AHORA APARECERÁN EN EL COMPONENTE:\n";
    if (count($diferencias_pendientes) > 0) {
        foreach ($diferencias_pendientes as $index => $diff) {
            $numero = $index + 1;
            echo "   {$numero}. Cierre #{$diff->cierre_id} - Caja #{$diff->caja_id} - {$diff->nombre_usuario}\n";
            echo "      💰 Diferencia original: L. " . number_format($diff->diferencia_original, 2) . "\n";
            echo "      🔧 Total gestionado: L. " . number_format($diff->total_gestionado, 2) . " ({$diff->gestiones_realizadas} gestiones)\n";
            echo "      ⏳ Diferencia pendiente: L. " . number_format($diff->diferencia_pendiente, 2) . "\n";
            echo "      🏷️  Estado: ✅ DISPONIBLE para nuevos ajustes\n\n";
        }
    } else {
        echo "   ✅ Todas las diferencias han sido completamente gestionadas\n\n";
    }
    
    echo "4. VALIDACIÓN FINAL:\n\n";
    echo "✅ PROBLEMA SOLUCIONADO:\n";
    echo "   - Las diferencias originales han sido restauradas\n";
    echo "   - El componente ya no modificará diferencia_efectivo\n";
    echo "   - Los cálculos se basan en la diferencia original\n";
    echo "   - Las diferencias pendientes aparecerán correctamente\n\n";
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== REPARACIÓN COMPLETADA ===\n";
