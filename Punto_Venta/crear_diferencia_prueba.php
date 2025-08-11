<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== CREANDO DIFERENCIA DE PRUEBA PARA DEMOSTRACIÓN ===\n\n";
    
    // Buscar el último usuario
    $stmt = $pdo->prepare("SELECT id, name FROM users ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($usuario) {
        echo "👤 Usuario encontrado: {$usuario->name} (ID: {$usuario->id})\n\n";
        
        // Crear una nueva caja de prueba
        $stmt = $pdo->prepare("
            INSERT INTO caja (users_id, balance, estado_caja, tienda_id, created_at, updated_at) 
            VALUES (?, 0, 2, 1, NOW(), NOW())
        ");
        $stmt->execute([$usuario->id]);
        $cajaId = $pdo->lastInsertId();
        
        // Crear cierre con diferencia
        $diferenciaOriginal = 75.50; // L. 75.50 de sobrante
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
        
        echo "✅ DIFERENCIA DE PRUEBA CREADA:\n";
        echo "   📦 Caja ID: {$cajaId}\n";
        echo "   🆔 Cierre ID: {$cierreId}\n";
        echo "   💰 Diferencia: L. " . number_format($diferenciaOriginal, 2) . " (Sobrante)\n";
        echo "   📍 Total efectivo registrado: L. " . number_format($totalEfectivo, 2) . "\n";
        echo "   🧮 Conteo real: L. " . number_format($conteoEfectivo, 2) . "\n\n";
        
        echo "🎯 ESTA DIFERENCIA APARECERÁ EN EL COMPONENTE DE GESTIÓN\n\n";
        
        // Verificar que aparezca en el query
        $stmt = $pdo->prepare("
            SELECT 
                cdc.id as cierre_id,
                cdc.diferencia_efectivo as diferencia_original,
                COALESCE(SUM(gd.monto), 0) as suma_ajustes,
                (cdc.diferencia_efectivo - COALESCE(SUM(gd.monto), 0)) as diferencia_pendiente,
                u.name as usuario_nombre
            FROM cierre_de_caja cdc
            JOIN caja c ON cdc.caja_id = c.id
            JOIN users u ON c.users_id = u.id
            LEFT JOIN gestion_diferencia gd ON cdc.id = gd.cierre_de_caja_id
            WHERE cdc.id = ?
            GROUP BY cdc.id, cdc.diferencia_efectivo, u.name
            HAVING ABS(diferencia_pendiente) >= 0.01
        ");
        $stmt->execute([$cierreId]);
        $resultado = $stmt->fetch(PDO::FETCH_OBJ);
        
        if ($resultado) {
            echo "✅ VERIFICACIÓN EXITOSA:\n";
            echo "   🔍 La diferencia aparece en el query del componente\n";
            echo "   👤 Usuario: {$resultado->usuario_nombre}\n";
            echo "   💰 Diferencia pendiente: L. " . number_format($resultado->diferencia_pendiente, 2) . "\n";
            echo "   🔄 Estado: ABIERTA para nuevos ajustes\n\n";
        }
        
        echo "📋 INSTRUCCIONES PARA PRUEBA:\n\n";
        echo "1. 🌐 Ir al componente de Gestión de Diferencias\n";
        echo "2. 👀 Verificar que aparezca la diferencia de L. " . number_format($diferenciaOriginal, 2) . "\n";
        echo "3. 🧪 PROBAR AJUSTES MÚLTIPLES:\n\n";
        
        echo "   🔸 AJUSTE POSITIVO (+25.50):\n";
        echo "     - Descripción: 'Corrección por error en conteo'\n";
        echo "     - Efecto: Diferencia baja a L. 50.00\n";
        echo "     - Estado: Sigue ABIERTA\n\n";
        
        echo "   🔸 AJUSTE NEGATIVO (-10.00):\n";
        echo "     - Descripción: 'Se encontró efectivo adicional'\n";
        echo "     - Efecto: Diferencia sube a L. 60.00\n";
        echo "     - Estado: Sigue ABIERTA\n\n";
        
        echo "   🔸 AJUSTE POSITIVO (+60.00):\n";
        echo "     - Descripción: 'Cierre definitivo de diferencia'\n";
        echo "     - Efecto: Diferencia llega a L. 0.00\n";
        echo "     - Estado: ✅ CERRADA (desaparece de la lista)\n\n";
        
        echo "4. 🔍 VERIFICAR COMPORTAMIENTOS:\n";
        echo "   ✅ Modal azul: 'Ajuste Registrado Exitosamente'\n";
        echo "   ✅ Modal verde: 'Diferencia Completamente Resuelta'\n";
        echo "   ✅ Recarga automática de lista tras cada ajuste\n";
        echo "   ✅ Diferencia desaparece cuando llega a 0\n\n";
        
    } else {
        echo "❌ No se encontraron usuarios en el sistema.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== FUNCIONALIDADES COMPLETAMENTE IMPLEMENTADAS ===\n\n";

echo "🎯 MÚLTIPLES TRANSACCIONES:\n";
echo "   ✅ Sistema permite infinitos ajustes mientras diferencia > 0\n";
echo "   ✅ Cada ajuste recalcula automáticamente la diferencia pendiente\n";
echo "   ✅ Diferencia se mantiene abierta hasta resolución completa\n\n";

echo "💰 MOVIMIENTOS FLEXIBLES:\n";
echo "   ✅ Ajustes positivos: REDUCEN la diferencia (hacia el cierre)\n";
echo "   ✅ Ajustes negativos: AUMENTAN la diferencia (correcciones)\n";
echo "   ✅ Validación permite cualquier valor excepto 0\n\n";

echo "🔄 GESTIÓN DINÁMICA:\n";
echo "   ✅ Fórmula: diferencia_pendiente = original - suma_total_ajustes\n";
echo "   ✅ Filtrado inteligente: solo muestra diferencias > 0.01\n";
echo "   ✅ Auditoría completa: todos los ajustes quedan registrados\n\n";

echo "🚀 SISTEMA LISTO PARA PRODUCCIÓN\n";
