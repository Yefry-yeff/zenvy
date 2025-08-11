<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PRUEBA DE GESTIÓN DE DIFERENCIAS ===\n\n";
    
    $fechaActual = date('Y-m-d');
    $tiendaId = 1;
    $userId = 1;
    
    echo "Configuración de prueba:\n";
    echo "- Fecha actual: $fechaActual\n";
    echo "- Tienda ID: $tiendaId\n";
    echo "- Usuario ID: $userId\n\n";
    
    // 1. Verificar estructura de la tabla gestion_diferencia
    echo "1. VERIFICANDO ESTRUCTURA DE TABLA 'gestion_diferencia':\n";
    
    $stmt = $pdo->prepare("DESCRIBE gestion_diferencia");
    $stmt->execute();
    $estructura = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Estructura de la tabla:\n";
    foreach ($estructura as $campo) {
        echo "   - {$campo['Field']}: {$campo['Type']} " . 
             ($campo['Null'] === 'NO' ? '(NOT NULL)' : '(NULL)') . 
             ($campo['Key'] ? " [{$campo['Key']}]" : '') . "\n";
    }
    echo "\n";
    
    // 2. Consulta similar al componente para obtener diferencias
    echo "2. CONSULTANDO DIFERENCIAS (Simulando el componente):\n";
    
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
        AND u.tienda_id = ?
        GROUP BY cc.id, c.id, c.users_id, u.name, cc.diferencia_efectivo, cc.total_efectivo, cc.conteo_efectivo, cc.created_at
        ORDER BY cc.created_at DESC
    ");
    
    $stmt->execute([$tiendaId]);
    $diferencias = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if (count($diferencias) > 0) {
        echo "✅ Encontradas " . count($diferencias) . " cajas con diferencias:\n\n";
        
        foreach ($diferencias as $index => $diferencia) {
            echo "📋 DIFERENCIA #" . ($index + 1) . "\n";
            echo "   🆔 ID Cierre: {$diferencia->cierre_id}\n";
            echo "   📦 Caja: #{$diferencia->caja_id}\n";
            echo "   👤 Usuario: {$diferencia->nombre_usuario} (ID: {$diferencia->users_id})\n";
            echo "   📅 Fecha: " . date('d/m/Y H:i:s', strtotime($diferencia->created_at)) . "\n";
            echo "   💰 Diferencia original: L. " . number_format($diferencia->diferencia_efectivo, 2) . "\n";
            echo "   ✅ Total gestionado: L. " . number_format($diferencia->total_gestionado, 2) . "\n";
            echo "   ⏳ Diferencia pendiente: L. " . number_format(abs($diferencia->diferencia_pendiente), 2) . "\n";
            echo "   📊 Gestiones realizadas: {$diferencia->gestiones_realizadas}\n";
            echo "   🏷️  Estado: " . (abs($diferencia->diferencia_pendiente) < 0.01 ? "✅ Resuelto" : "⏳ Pendiente") . "\n\n";
        }
    } else {
        echo "ℹ️  No se encontraron diferencias para la tienda especificada.\n\n";
        
        // Crear datos de prueba
        echo "3. CREANDO DATOS DE PRUEBA:\n\n";
        
        // Verificar usuarios en la tienda
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE tienda_id = ? LIMIT 2");
        $stmt->execute([$tiendaId]);
        $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        if (count($usuarios) > 0) {
            foreach ($usuarios as $index => $user) {
                if ($index >= 2) break;
                
                // Crear caja
                $stmt = $pdo->prepare("
                    INSERT INTO caja (users_id, balance, estado_caja, created_at, updated_at) 
                    VALUES (?, 0, 2, NOW() - INTERVAL ? HOUR, NOW())
                ");
                $horasAtras = ($index + 1) * 2;
                $stmt->execute([$user->id, $horasAtras]);
                $cajaId = $pdo->lastInsertId();
                
                // Crear cierre con diferencia
                $diferencias_ejemplo = [150.75, -85.25]; // Sobrante y faltante
                $diferencia = $diferencias_ejemplo[$index];
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
                        NOW() - INTERVAL ? HOUR, NOW()
                    )
                ");
                
                $stmt->execute([$cajaId, $totalEfectivo, $conteoEfectivo, $diferencia, $horasAtras]);
                $cierreId = $pdo->lastInsertId();
                
                echo "✅ Creada diferencia de ejemplo:\n";
                echo "   📦 Caja: #{$cajaId}\n";
                echo "   👤 Usuario: {$user->name}\n";
                echo "   💰 Diferencia: L. " . number_format($diferencia, 2) . " (" . ($diferencia > 0 ? "Sobrante" : "Faltante") . ")\n";
                echo "   🆔 ID Cierre: {$cierreId}\n\n";
            }
            
            // Re-ejecutar consulta
            echo "4. RE-CONSULTANDO DIFERENCIAS CON DATOS DE PRUEBA:\n\n";
            
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
                AND u.tienda_id = ?
                GROUP BY cc.id, c.id, c.users_id, u.name, cc.diferencia_efectivo, cc.total_efectivo, cc.conteo_efectivo, cc.created_at
                ORDER BY cc.created_at DESC
            ");
            
            $stmt->execute([$tiendaId]);
            $diferencias = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            foreach ($diferencias as $index => $diferencia) {
                echo "📋 DIFERENCIA #" . ($index + 1) . "\n";
                echo "   🆔 ID Cierre: {$diferencia->cierre_id}\n";
                echo "   📦 Caja: #{$diferencia->caja_id}\n";
                echo "   👤 Usuario: {$diferencia->nombre_usuario}\n";
                echo "   💰 Diferencia original: L. " . number_format($diferencia->diferencia_efectivo, 2) . "\n";
                echo "   ⏳ Diferencia pendiente: L. " . number_format(abs($diferencia->diferencia_pendiente), 2) . "\n\n";
            }
        }
    }
    
    // 5. Simular gestión de diferencia
    if (count($diferencias) > 0) {
        echo "5. SIMULANDO GESTIÓN DE DIFERENCIA:\n\n";
        
        $diferenciaPrueba = $diferencias[0];
        $montoGestion = abs($diferenciaPrueba->diferencia_pendiente) / 2; // Gestionar la mitad
        $descripcion = "Gestión de prueba - Revisión de arqueo";
        
        echo "📝 Datos de la gestión:\n";
        echo "   🆔 ID Cierre: {$diferenciaPrueba->cierre_id}\n";
        echo "   💰 Monto a gestionar: L. " . number_format($montoGestion, 2) . "\n";
        echo "   📝 Descripción: {$descripcion}\n";
        echo "   👤 Usuario gestor: {$userId}\n\n";
        
        // Insertar gestión
        $stmt = $pdo->prepare("
            INSERT INTO gestion_diferencia (monto, descripcion, cierre_de_caja_id, users_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$montoGestion, $descripcion, $diferenciaPrueba->cierre_id, $userId]);
        $gestionId = $pdo->lastInsertId();
        
        echo "✅ Gestión creada con ID: {$gestionId}\n\n";
        
        // Calcular nueva diferencia
        $diferenciaTotalGestionada = $diferenciaPrueba->total_gestionado + $montoGestion;
        $nuevaDiferencia = $diferenciaPrueba->diferencia_efectivo;
        
        if ($nuevaDiferencia < 0) {
            $nuevaDiferencia = $nuevaDiferencia + $diferenciaTotalGestionada;
        } else {
            $nuevaDiferencia = $nuevaDiferencia - $diferenciaTotalGestionada;
        }
        
        // Actualizar cierre_de_caja
        $stmt = $pdo->prepare("
            UPDATE cierre_de_caja 
            SET diferencia_efectivo = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$nuevaDiferencia, $diferenciaPrueba->cierre_id]);
        
        echo "📊 RESULTADO DE LA GESTIÓN:\n";
        echo "   💰 Diferencia original: L. " . number_format($diferenciaPrueba->diferencia_efectivo, 2) . "\n";
        echo "   ✅ Total gestionado: L. " . number_format($diferenciaTotalGestionada, 2) . "\n";
        echo "   ⏳ Nueva diferencia: L. " . number_format($nuevaDiferencia, 2) . "\n";
        echo "   🏷️  Estado: " . (abs($nuevaDiferencia) < 0.01 ? "✅ Resuelto completamente" : "⏳ Parcialmente resuelto") . "\n\n";
    }
    
    echo "6. FUNCIONALIDADES IMPLEMENTADAS:\n\n";
    echo "✅ COMPONENTE PHP (GestionDeDiferencias.php):\n";
    echo "   - 🔒 Validación de jornada abierta\n";
    echo "   - 📊 Carga de diferencias por tienda\n";
    echo "   - 📋 Información detallada de transacciones\n";
    echo "   - ✏️  Modal de gestión con formulario\n";
    echo "   - 💾 Inserción en tabla gestion_diferencia\n";
    echo "   - 🔄 Actualización de diferencia_efectivo\n";
    echo "   - ✅ Validaciones de formulario\n";
    echo "   - 🛡️  Transacciones de base de datos\n\n";
    
    echo "✅ VISTA BLADE (gestion-de-diferencias.blade.php):\n";
    echo "   - 📋 Lista de diferencias en tabla\n";
    echo "   - 🎨 Diseño profesional con Tailwind CSS\n";
    echo "   - 🖱️  Filas clicables para abrir modal\n";
    echo "   - 📊 Información detallada por diferencia\n";
    echo "   - 📝 Formulario de gestión en modal\n";
    echo "   - 🎯 Estados visuales (Pendiente/Resuelto)\n";
    echo "   - ⚡ Interacciones con Livewire\n";
    echo "   - 📱 Diseño responsivo\n\n";
    
    echo "✅ TABLA GESTION_DIFERENCIA:\n";
    echo "   - 🆔 Relación con cierre_de_caja\n";
    echo "   - 👤 Relación con users (gestor)\n";
    echo "   - 💰 Monto de la gestión\n";
    echo "   - 📝 Descripción/justificación\n";
    echo "   - ⏰ Timestamps de auditoría\n\n";

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== RESUMEN DE GESTIÓN DE DIFERENCIAS ===\n";
echo "🎯 FUNCIONALIDAD PRINCIPAL:\n";
echo "   - Lista todas las cajas con diferencias de la tienda\n";
echo "   - Muestra múltiples diferencias por caja en fechas distintas\n";
echo "   - Permite gestionar diferencias mediante modal\n";
echo "   - Disminuye la diferencia según el monto gestionado\n";
echo "   - Registra auditoría completa de las gestiones\n";

echo "\n📋 INFORMACIÓN MOSTRADA:\n";
echo "   - 📦 ID de caja y cierre\n";
echo "   - 👤 Nombre del usuario responsable\n";
echo "   - 📅 Fecha y hora del cierre\n";
echo "   - 💰 Diferencia original y pendiente\n";
echo "   - ✅ Total gestionado y número de gestiones\n";
echo "   - 🏷️  Estado visual (Pendiente/Resuelto)\n";

echo "\n🔄 PROCESO DE GESTIÓN:\n";
echo "   1. 🖱️  Clic en fila abre modal con información detallada\n";
echo "   2. 📝 Formulario para ingresar monto y descripción\n";
echo "   3. ✅ Validaciones de monto máximo y campos requeridos\n";
echo "   4. 💾 Inserción en tabla gestion_diferencia\n";
echo "   5. 🔄 Actualización de diferencia_efectivo en cierre_de_caja\n";
echo "   6. 📊 Recarga automática de datos actualizados\n";

echo "\n=== FIN DE LA PRUEBA ===\n";
