<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== DIAGNÓSTICO DEL PROBLEMA DE GESTIÓN DE DIFERENCIAS ===\n\n";
    
    // 1. Verificar todas las diferencias y sus gestiones
    echo "1. VERIFICANDO ESTADO ACTUAL DE DIFERENCIAS:\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            cdc.id as cierre_id,
            cdc.diferencia_efectivo as diferencia_registrada_en_bd,
            cdc.total_efectivo,
            cdc.conteo_efectivo,
            cdc.created_at as fecha_cierre,
            c.users_id,
            u.name as usuario_nombre,
            COALESCE(SUM(gd.monto), 0) as total_ajustes_realizados,
            COUNT(gd.id) as numero_ajustes,
            (cdc.total_efectivo - cdc.conteo_efectivo) as diferencia_original_calculada
        FROM cierre_de_caja cdc
        JOIN caja c ON cdc.caja_id = c.id
        JOIN users u ON c.users_id = u.id
        LEFT JOIN gestion_diferencia gd ON cdc.id = gd.cierre_de_caja_id
        WHERE ABS(cdc.total_efectivo - cdc.conteo_efectivo) > 0.01
        GROUP BY cdc.id, cdc.diferencia_efectivo, cdc.total_efectivo, cdc.conteo_efectivo, cdc.created_at, c.users_id, u.name
        ORDER BY cdc.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $diferencias = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if (count($diferencias) > 0) {
        foreach ($diferencias as $index => $diff) {
            $numero = $index + 1;
            $diferencia_calculada = $diff->total_efectivo - $diff->conteo_efectivo;
            $diferencia_pendiente = $diferencia_calculada - $diff->total_ajustes_realizados;
            
            echo "   {$numero}. CIERRE #{$diff->cierre_id} - {$diff->usuario_nombre}\n";
            echo "      📅 Fecha: {$diff->fecha_cierre}\n";
            echo "      💰 Total esperado: L. " . number_format($diff->total_efectivo, 2) . "\n";
            echo "      🧮 Total contado: L. " . number_format($diff->conteo_efectivo, 2) . "\n";
            echo "      📊 Diferencia CALCULADA: L. " . number_format($diferencia_calculada, 2) . "\n";
            echo "      💾 Diferencia en BD: L. " . number_format($diff->diferencia_registrada_en_bd, 2) . "\n";
            echo "      🔧 Total ajustes: L. " . number_format($diff->total_ajustes_realizados, 2) . " ({$diff->numero_ajustes} ajustes)\n";
            echo "      ⏳ Diferencia pendiente: L. " . number_format($diferencia_pendiente, 2) . "\n";
            echo "      🏷️  ¿Debería aparecer?: " . (abs($diferencia_pendiente) >= 0.01 ? "✅ SÍ" : "❌ NO") . "\n";
            
            if ($diff->diferencia_registrada_en_bd != $diferencia_calculada) {
                echo "      ⚠️  ERROR: La diferencia en BD no coincide con la calculada!\n";
            }
            echo "\n";
        }
        
        // 2. Verificar los ajustes específicos del problema
        echo "2. DETALLES DE AJUSTES REALIZADOS:\n\n";
        
        foreach ($diferencias as $diff) {
            if ($diff->numero_ajustes > 0) {
                echo "📦 CIERRE #{$diff->cierre_id}:\n";
                
                $stmt = $pdo->prepare("
                    SELECT 
                        gd.*,
                        u.name as gestor_nombre
                    FROM gestion_diferencia gd
                    JOIN users u ON gd.users_id = u.id
                    WHERE gd.cierre_de_caja_id = ?
                    ORDER BY gd.created_at ASC
                ");
                $stmt->execute([$diff->cierre_id]);
                $ajustes = $stmt->fetchAll(PDO::FETCH_OBJ);
                
                foreach ($ajustes as $i => $ajuste) {
                    $num = $i + 1;
                    echo "   {$num}. " . ($ajuste->monto > 0 ? '+' : '') . "L. " . number_format($ajuste->monto, 2);
                    echo " | {$ajuste->gestor_nombre} | " . date('d/m/Y H:i', strtotime($ajuste->created_at)) . "\n";
                    echo "      📝 {$ajuste->descripcion}\n";
                }
                echo "\n";
            }
        }
        
        // 3. Simular la query del componente Livewire
        echo "3. SIMULANDO QUERY DEL COMPONENTE LIVEWIRE:\n\n";
        
        $stmt = $pdo->prepare("
            SELECT 
                cc.id as cierre_id,
                c.id as caja_id,
                c.users_id,
                u.name as nombre_usuario,
                cc.diferencia_efectivo,
                cc.total_efectivo,
                cc.conteo_efectivo,
                cc.created_at,
                COALESCE(SUM(gd.monto), 0) as total_gestionado,
                (cc.diferencia_efectivo - COALESCE(SUM(gd.monto), 0)) as diferencia_pendiente,
                COUNT(gd.id) as gestiones_realizadas
            FROM cierre_de_caja as cc
            JOIN caja as c ON cc.caja_id = c.id
            JOIN users as u ON c.users_id = u.id
            LEFT JOIN gestion_diferencia as gd ON cc.id = gd.cierre_de_caja_id
            WHERE cc.diferencia_efectivo != 0
            GROUP BY cc.id, c.id, c.users_id, u.name, cc.diferencia_efectivo, cc.total_efectivo, cc.conteo_efectivo, cc.created_at
            HAVING ABS(cc.diferencia_efectivo - COALESCE(SUM(gd.monto), 0)) >= 0.01
            ORDER BY cc.created_at DESC
        ");
        $stmt->execute();
        $resultados_livewire = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        echo "🔍 RESULTADOS QUE DEVUELVE EL COMPONENTE:\n";
        if (count($resultados_livewire) > 0) {
            foreach ($resultados_livewire as $index => $resultado) {
                $numero = $index + 1;
                echo "   {$numero}. Cierre #{$resultado->cierre_id} - {$resultado->nombre_usuario}\n";
                echo "      💰 Diferencia BD: L. " . number_format($resultado->diferencia_efectivo, 2) . "\n";
                echo "      🔧 Total gestionado: L. " . number_format($resultado->total_gestionado, 2) . "\n";
                echo "      ⏳ Diferencia pendiente: L. " . number_format($resultado->diferencia_pendiente, 2) . "\n\n";
            }
        } else {
            echo "   ❌ NO HAY RESULTADOS - Por eso aparece 'No hay diferencias pendientes'\n\n";
        }
        
    } else {
        echo "ℹ️  No se encontraron diferencias en el sistema.\n\n";
    }
    
    echo "4. DIAGNÓSTICO DEL PROBLEMA:\n\n";
    echo "🔍 POSIBLES CAUSAS:\n";
    echo "   1. La columna 'diferencia_efectivo' se está modificando incorrectamente\n";
    echo "   2. Debería mantenerse como diferencia ORIGINAL\n";
    echo "   3. El cálculo debe ser: diferencia_original - suma_ajustes\n";
    echo "   4. NO se debe actualizar 'diferencia_efectivo' en la tabla\n\n";
    
    echo "✅ SOLUCIÓN RECOMENDADA:\n";
    echo "   - Eliminar la actualización de 'diferencia_efectivo' en el método gestionarDiferencia()\n";
    echo "   - Mantener la diferencia original intacta\n";
    echo "   - El filtrado debe usar el cálculo dinámico\n\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== FIN DEL DIAGNÓSTICO ===\n";
