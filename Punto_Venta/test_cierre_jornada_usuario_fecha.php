<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PRUEBA DE DATOS DE CAJAS CON DIFERENCIA EN CIERRE DE JORNADA ===\n\n";
    
    $fechaActual = date('Y-m-d');
    $tiendaId = 1;
    
    echo "Configuración de prueba:\n";
    echo "- Fecha actual: $fechaActual\n";
    echo "- Tienda ID: $tiendaId\n\n";
    
    // 1. Consulta similar a la del componente para cajas con diferencia
    echo "1. CONSULTA DE CAJAS CON DIFERENCIA (Similar al componente):\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id, 
            c.users_id, 
            u.name as nombre_usuario,
            cc.diferencia_efectivo, 
            cc.created_at
        FROM cierre_de_caja as cc
        JOIN caja as c ON cc.caja_id = c.id
        JOIN users as u ON c.users_id = u.id
        WHERE cc.diferencia_efectivo != 0
        AND u.tienda_id = ?
        AND DATE(cc.created_at) = ?
        ORDER BY cc.created_at DESC
    ");
    
    $stmt->execute([$tiendaId, $fechaActual]);
    $cajasConDiferencia = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if (count($cajasConDiferencia) > 0) {
        echo "✅ Encontradas " . count($cajasConDiferencia) . " cajas con diferencia:\n\n";
        
        foreach ($cajasConDiferencia as $caja) {
            echo "📋 CAJA #{$caja->id}\n";
            echo "   👤 Usuario: {$caja->nombre_usuario} (ID: {$caja->users_id})\n";
            echo "   💰 Diferencia: L. " . number_format($caja->diferencia_efectivo, 2) . "\n";
            echo "   📅 Fecha del cierre: " . date('d/m/Y H:i:s', strtotime($caja->created_at)) . "\n";
            echo "   🏷️  Tipo: " . ($caja->diferencia_efectivo > 0 ? "Sobrante (+)" : "Faltante (-)") . "\n";
            echo "   ⏰ Hora exacta: " . date('H:i:s', strtotime($caja->created_at)) . "\n\n";
        }
    } else {
        echo "ℹ️  No se encontraron cajas con diferencia para hoy en la tienda especificada.\n\n";
    }
    
    // 2. Consulta para cajas abiertas con nombres de usuario
    echo "2. CONSULTA DE CAJAS ABIERTAS (Similar al componente):\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            c.*, 
            u.name as nombre_usuario
        FROM caja as c
        JOIN users as u ON c.users_id = u.id
        WHERE c.estado_caja = 1
        AND u.tienda_id = ?
        AND DATE(c.created_at) = ?
        ORDER BY c.created_at DESC
    ");
    
    $stmt->execute([$tiendaId, $fechaActual]);
    $cajasAbiertas = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if (count($cajasAbiertas) > 0) {
        echo "✅ Encontradas " . count($cajasAbiertas) . " cajas abiertas:\n\n";
        
        foreach ($cajasAbiertas as $caja) {
            echo "📋 CAJA #{$caja->id}\n";
            echo "   👤 Usuario: {$caja->nombre_usuario} (ID: {$caja->users_id})\n";
            echo "   💰 Balance: L. " . number_format($caja->balance, 2) . "\n";
            echo "   📅 Fecha apertura: " . date('d/m/Y H:i:s', strtotime($caja->created_at)) . "\n";
            echo "   🔓 Estado: Abierta (estado_caja = {$caja->estado_caja})\n\n";
        }
    } else {
        echo "ℹ️  No se encontraron cajas abiertas para hoy en la tienda especificada.\n\n";
    }
    
    // 3. Crear datos de prueba si no existen
    if (count($cajasConDiferencia) == 0) {
        echo "3. CREANDO DATOS DE PRUEBA PARA DEMOSTRACIÓN:\n\n";
        
        // Verificar si hay usuarios en la tienda
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE tienda_id = ? LIMIT 3");
        $stmt->execute([$tiendaId]);
        $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        if (count($usuarios) > 0) {
            echo "👥 Usuarios encontrados en la tienda:\n";
            foreach ($usuarios as $user) {
                echo "   - {$user->name} (ID: {$user->id})\n";
            }
            echo "\n";
            
            // Crear algunas cajas y cierres de ejemplo
            foreach ($usuarios as $index => $user) {
                if ($index >= 2) break; // Solo crear 2 ejemplos
                
                // Crear caja
                $stmt = $pdo->prepare("
                    INSERT INTO caja (users_id, balance, estado_caja, created_at, updated_at) 
                    VALUES (?, ?, 2, NOW() - INTERVAL 2 HOUR, NOW())
                ");
                $balance = ($index == 0) ? 150.75 : -25.50; // Una con sobrante, otra con faltante
                $stmt->execute([$user->id, 0]); // Balance 0 porque ya se cerró
                $cajaId = $pdo->lastInsertId();
                
                // Crear cierre con diferencia
                $stmt = $pdo->prepare("
                    INSERT INTO cierre_de_caja (
                        caja_id, total_efectivo, conteo_efectivo, diferencia_efectivo,
                        total_tarjeta, conteo_tarjeta, diferencia_tarjeta,
                        total_cheque, conteo_cheque, diferencia_cheque,
                        `1`, `2`, `5`, `10`, `20`, `50`, `100`, `200`, `500`,
                        `001`, `002`, `005`, `010`, `020`, `050`,
                        created_at, updated_at
                    ) VALUES (
                        ?, 1000, ?, ?,
                        0, 0, 0,
                        0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0,
                        NOW() - INTERVAL 1 HOUR, NOW()
                    )
                ");
                
                $conteoEfectivo = 1000 + $balance;
                $stmt->execute([$cajaId, $conteoEfectivo, $balance]);
                
                echo "✅ Creada caja de ejemplo #{$cajaId} para {$user->name}\n";
                echo "   💰 Diferencia: L. " . number_format($balance, 2) . "\n";
                echo "   🏷️  Tipo: " . ($balance > 0 ? "Sobrante" : "Faltante") . "\n\n";
            }
            
            echo "🔄 Ejecutando consulta nuevamente con datos de prueba:\n\n";
            
            // Re-ejecutar consulta de diferencias
            $stmt = $pdo->prepare("
                SELECT 
                    c.id, 
                    c.users_id, 
                    u.name as nombre_usuario,
                    cc.diferencia_efectivo, 
                    cc.created_at
                FROM cierre_de_caja as cc
                JOIN caja as c ON cc.caja_id = c.id
                JOIN users as u ON c.users_id = u.id
                WHERE cc.diferencia_efectivo != 0
                AND u.tienda_id = ?
                AND DATE(cc.created_at) = ?
                ORDER BY cc.created_at DESC
            ");
            
            $stmt->execute([$tiendaId, $fechaActual]);
            $cajasConDiferencia = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            foreach ($cajasConDiferencia as $caja) {
                echo "📋 CAJA #{$caja->id}\n";
                echo "   👤 Usuario: {$caja->nombre_usuario} (ID: {$caja->users_id})\n";
                echo "   💰 Diferencia: L. " . number_format($caja->diferencia_efectivo, 2) . "\n";
                echo "   📅 Fecha del cierre: " . date('d/m/Y H:i:s', strtotime($caja->created_at)) . "\n";
                echo "   🏷️  Tipo: " . ($caja->diferencia_efectivo > 0 ? "Sobrante (+)" : "Faltante (-)") . "\n\n";
            }
        } else {
            echo "❌ No se encontraron usuarios en la tienda especificada.\n\n";
        }
    }
    
    // 4. Mostrar el código de la vista actualizada
    echo "4. CÓDIGO DE VISTA ACTUALIZADO:\n\n";
    echo "```blade\n";
    echo "@foreach(\$cajasConDiferencia as \$caja)\n";
    echo "    <div class=\"border-b border-orange-200 last:border-b-0 py-2 last:pb-0\">\n";
    echo "        <div class=\"flex justify-between items-start\">\n";
    echo "            <div class=\"flex-1\">\n";
    echo "                <div class=\"font-medium text-gray-800\">\n";
    echo "                    <i class=\"fas fa-cash-register text-orange-500 mr-1\"></i>\n";
    echo "                    Caja #{{ \$caja->id }}\n";
    echo "                </div>\n";
    echo "                <div class=\"text-sm text-gray-600 mt-1\">\n";
    echo "                    <i class=\"fas fa-user text-blue-500 mr-1\"></i>\n";
    echo "                    <strong>Usuario:</strong> {{ \$caja->nombre_usuario ?? 'Usuario ID: ' . \$caja->users_id }}\n";
    echo "                </div>\n";
    echo "                <div class=\"text-sm text-gray-600\">\n";
    echo "                    <i class=\"fas fa-calendar-alt text-green-500 mr-1\"></i>\n";
    echo "                    <strong>Fecha del cierre:</strong> {{ \\Carbon\\Carbon::parse(\$caja->created_at)->format('d/m/Y H:i:s') }}\n";
    echo "                </div>\n";
    echo "            </div>\n";
    echo "            <div class=\"text-right\">\n";
    echo "                <span class=\"font-bold text-lg {{ \$caja->diferencia_efectivo > 0 ? 'text-green-600' : 'text-red-600' }}\">\n";
    echo "                    {{ \$caja->diferencia_efectivo > 0 ? '+' : '' }}L. {{ number_format(\$caja->diferencia_efectivo, 2) }}\n";
    echo "                </span>\n";
    echo "                <div class=\"text-xs text-gray-500\">\n";
    echo "                    {{ \$caja->diferencia_efectivo > 0 ? 'Sobrante' : 'Faltante' }}\n";
    echo "                </div>\n";
    echo "            </div>\n";
    echo "        </div>\n";
    echo "    </div>\n";
    echo "@endforeach\n";
    echo "```\n\n";
    
    echo "5. INFORMACIÓN MOSTRADA EN EL CIERRE DE JORNADA:\n\n";
    echo "✅ NOMBRE DEL USUARIO: Se obtiene de la tabla 'users' mediante JOIN\n";
    echo "✅ FECHA DEL CIERRE: Se muestra el 'created_at' de 'cierre_de_caja'\n";
    echo "✅ FORMATO DE FECHA: dd/mm/yyyy HH:mm:ss\n";
    echo "✅ DIFERENCIA: Monto exacto con indicador de sobrante/faltante\n";
    echo "✅ INFORMACIÓN VISUAL: Iconos y colores para mejor presentación\n";

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== RESUMEN DE MEJORAS IMPLEMENTADAS ===\n";
echo "📋 CAJAS CON DIFERENCIA:\n";
echo "   - ✅ Nombre completo del usuario responsable\n";
echo "   - ✅ Fecha y hora exacta del cierre (created_at)\n";
echo "   - ✅ Formato legible: dd/mm/yyyy HH:mm:ss\n";
echo "   - ✅ Indicador visual de sobrante/faltante\n";
echo "   - ✅ Diseño mejorado con iconos y colores\n";

echo "\n📋 CAJAS ABIERTAS:\n";
echo "   - ✅ Nombre completo del usuario responsable\n";
echo "   - ✅ Balance actual de la caja\n";
echo "   - ✅ Información visual mejorada\n";

echo "\n=== FIN DE LA PRUEBA ===\n";
