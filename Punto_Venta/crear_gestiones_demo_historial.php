<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== AGREGANDO GESTIONES DE PRUEBA PARA DEMOSTRAR HISTORIAL ===\n\n";
    
    $cierre_id = 5; // Cierre que tiene L. 500.00 pendientes
    $user_id = 1;   // Johann Ruiz
    
    echo "🎯 Agregando gestiones variadas al Cierre #{$cierre_id}:\n\n";
    
    $gestiones_prueba = [
        [
            'monto' => -50.00,
            'descripcion' => 'Se encontró efectivo adicional en el cajón que no fue contabilizado inicialmente. Aumento de diferencia por corrección.'
        ],
        [
            'monto' => 25.00,
            'descripcion' => 'Validación de billetes: Se confirmó que un billete de L.50 era falso. Reducción de diferencia.'
        ],
        [
            'monto' => -15.00,
            'descripcion' => 'Error en conteo de monedas: Se encontraron L.15 adicionales en monedas de L.1. Aumento de diferencia.'
        ],
        [
            'monto' => 100.00,
            'descripcion' => 'Cierre parcial: Gestión de diferencia encontrada tras revisión con supervisor. Reducción programada.'
        ]
    ];
    
    foreach ($gestiones_prueba as $index => $gestion) {
        $numero = $index + 1;
        
        // Esperar 1 segundo entre cada inserción para tener timestamps diferentes
        if ($index > 0) sleep(1);
        
        $stmt = $pdo->prepare("
            INSERT INTO gestion_diferencia (monto, descripcion, cierre_de_caja_id, users_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$gestion['monto'], $gestion['descripcion'], $cierre_id, $user_id]);
        
        $tipo = $gestion['monto'] > 0 ? 'POSITIVO' : 'NEGATIVO';
        $efecto = $gestion['monto'] > 0 ? 'reduce' : 'aumenta';
        
        echo "   {$numero}. ✅ " . ($gestion['monto'] > 0 ? '+' : '') . "L. " . number_format($gestion['monto'], 2) . " ({$tipo} - {$efecto} diferencia)\n";
        echo "      📝 " . substr($gestion['descripcion'], 0, 60) . "...\n\n";
    }
    
    // Calcular nuevo estado
    echo "📊 ESTADO ACTUALIZADO:\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            cc.total_efectivo - cc.conteo_efectivo as diferencia_original,
            COALESCE(SUM(gd.monto), 0) as total_gestionado,
            COUNT(gd.id) as total_gestiones,
            ((cc.total_efectivo - cc.conteo_efectivo) - COALESCE(SUM(gd.monto), 0)) as diferencia_pendiente
        FROM cierre_de_caja cc
        LEFT JOIN gestion_diferencia gd ON cc.id = gd.cierre_de_caja_id
        WHERE cc.id = ?
        GROUP BY cc.id, cc.total_efectivo, cc.conteo_efectivo
    ");
    $stmt->execute([$cierre_id]);
    $estado = $stmt->fetch(PDO::FETCH_OBJ);
    
    echo "   💰 Diferencia original: L. " . number_format($estado->diferencia_original, 2) . "\n";
    echo "   🔧 Total gestionado: L. " . number_format($estado->total_gestionado, 2) . "\n";
    echo "   📊 Gestiones realizadas: {$estado->total_gestiones}\n";
    echo "   ⏳ Diferencia pendiente: L. " . number_format($estado->diferencia_pendiente, 2) . "\n\n";
    
    // Mostrar historial completo
    echo "📋 HISTORIAL COMPLETO (como aparecerá en el modal):\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            gd.monto,
            gd.descripcion,
            gd.created_at,
            u.name as gestor_nombre
        FROM gestion_diferencia gd
        JOIN users u ON gd.users_id = u.id
        WHERE gd.cierre_de_caja_id = ?
        ORDER BY gd.created_at DESC
    ");
    $stmt->execute([$cierre_id]);
    $historial_completo = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    foreach ($historial_completo as $index => $gestion) {
        $numero = $index + 1;
        $tipo_badge = $gestion->monto > 0 ? '🔵 POSITIVO' : '🟠 NEGATIVO';
        $efecto = $gestion->monto > 0 ? 'Reduce diferencia' : 'Aumenta diferencia';
        
        echo "   {$numero}. {$tipo_badge} | " . ($gestion->monto > 0 ? '+' : '') . "L. " . number_format($gestion->monto, 2) . "\n";
        echo "      📝 {$gestion->descripcion}\n";
        echo "      👤 {$gestion->gestor_nombre} | 📅 " . date('d/m/Y H:i:s', strtotime($gestion->created_at)) . "\n";
        echo "      💡 {$efecto}\n\n";
    }
    
    echo "🎉 HISTORIAL DE PRUEBA CREADO EXITOSAMENTE\n\n";
    
    echo "📱 INSTRUCCIONES PARA VER EL HISTORIAL:\n";
    echo "   1. 🌐 Ir al componente de Gestión de Diferencias\n";
    echo "   2. 👆 Hacer clic en la fila del Cierre #{$cierre_id}\n";
    echo "   3. 👀 Observar la nueva sección 'Historial de Gestiones'\n";
    echo "   4. 🔄 Scroll para ver todas las gestiones si son muchas\n";
    echo "   5. ✨ Probar agregar una nueva gestión para ver actualización en tiempo real\n\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== DEMOSTRACIÓN DEL HISTORIAL LISTA ===\n";
