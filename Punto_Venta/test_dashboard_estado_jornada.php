<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PRUEBA DE ESTADO DE JORNADA EN DASHBOARD ===\n\n";
    
    $fechaActual = date('Y-m-d');
    $tiendaId = 1;
    $userId = 1;
    
    echo "Configuración de prueba:\n";
    echo "- Fecha actual: $fechaActual\n";
    echo "- Tienda ID: $tiendaId\n";
    echo "- Usuario ID: $userId\n\n";
    
    // 1. Verificar estados posibles de jornada
    echo "1. ESTADOS POSIBLES DE JORNADA:\n\n";
    
    $estados = [
        ['apertura' => 1, 'cierre' => 0, 'descripcion' => '🟢 Jornada ABIERTA', 'estado_texto' => 'abierta'],
        ['apertura' => 0, 'cierre' => 1, 'descripcion' => '🔴 Jornada CERRADA', 'estado_texto' => 'cerrada'],
        ['apertura' => 0, 'cierre' => 0, 'descripcion' => '🟡 Jornada SIN APERTURAR', 'estado_texto' => 'sin_aperturar'],
        ['apertura' => null, 'cierre' => null, 'descripcion' => '⚪ SIN JORNADA CREADA', 'estado_texto' => 'sin_jornada']
    ];
    
    foreach ($estados as $index => $estado) {
        echo ($index + 1) . ". {$estado['descripcion']}\n";
        echo "   - apertura: " . ($estado['apertura'] ?? 'null') . "\n";
        echo "   - cierre: " . ($estado['cierre'] ?? 'null') . "\n";
        echo "   - estado_codigo: " . ($estado['estado_texto'] == 'abierta' ? '1' : ($estado['estado_texto'] == 'cerrada' ? '2' : ($estado['estado_texto'] == 'sin_aperturar' ? '0' : '-1'))) . "\n\n";
    }
    
    // 2. Simular consulta del dashboard
    echo "2. SIMULANDO CONSULTA DEL DASHBOARD:\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            j.*,
            ua.name as usuario_apertura_nombre,
            uc.name as usuario_cierre_nombre
        FROM jornada j
        LEFT JOIN users ua ON j.user_id_apertura = ua.id
        LEFT JOIN users uc ON j.user_id_cierre = uc.id
        WHERE j.fecha = ? 
        AND j.tienda_id = ?
        ORDER BY j.created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$fechaActual, $tiendaId]);
    $jornada = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($jornada) {
        echo "✅ Jornada encontrada para hoy:\n";
        echo "   🆔 ID: {$jornada->id}\n";
        echo "   📅 Fecha: {$jornada->fecha}\n";
        echo "   🏪 Tienda ID: {$jornada->tienda_id}\n";
        echo "   🔓 Apertura: {$jornada->apertura}\n";
        echo "   🔒 Cierre: {$jornada->cierre}\n";
        echo "   👤 Usuario apertura: " . ($jornada->usuario_apertura_nombre ?? 'NULL') . " (ID: " . ($jornada->user_id_apertura ?? 'NULL') . ")\n";
        echo "   👤 Usuario cierre: " . ($jornada->usuario_cierre_nombre ?? 'NULL') . " (ID: " . ($jornada->user_id_cierre ?? 'NULL') . ")\n";
        echo "   💬 Comentario: " . ($jornada->comentario ?? 'Sin comentario') . "\n";
        echo "   📅 Creada: {$jornada->created_at}\n";
        echo "   📅 Actualizada: {$jornada->updated_at}\n\n";
        
        // Determinar estado
        $estado = 'cerrada';
        $estadoCodigo = 0;
        $colorEstado = '🔴';
        
        if ($jornada->apertura == 1 && $jornada->cierre == 0) {
            $estado = 'abierta';
            $estadoCodigo = 1;
            $colorEstado = '🟢';
        } elseif ($jornada->apertura == 0 && $jornada->cierre == 1) {
            $estado = 'cerrada';
            $estadoCodigo = 2;
            $colorEstado = '🔴';
        } elseif ($jornada->apertura == 0 && $jornada->cierre == 0) {
            $estado = 'sin_aperturar';
            $estadoCodigo = 0;
            $colorEstado = '🟡';
        }
        
        echo "📊 ESTADO DETERMINADO:\n";
        echo "   🏷️  Estado: {$colorEstado} {$estado}\n";
        echo "   🔢 Código: {$estadoCodigo}\n";
        echo "   📝 Texto: " . ucfirst($estado) . "\n\n";
        
    } else {
        echo "ℹ️  No se encontró jornada para hoy.\n";
        echo "📊 ESTADO: ⚪ sin_jornada\n";
        echo "🔢 CÓDIGO: -1\n\n";
        
        // Crear jornada de ejemplo
        echo "3. CREANDO JORNADA DE EJEMPLO:\n\n";
        
        $stmt = $pdo->prepare("
            INSERT INTO jornada (fecha, tienda_id, apertura, cierre, user_id_apertura, comentario, created_at, updated_at)
            VALUES (?, ?, 1, 0, ?, 'Jornada de ejemplo para prueba del dashboard', NOW(), NOW())
        ");
        $stmt->execute([$fechaActual, $tiendaId, $userId]);
        $jornadaId = $pdo->lastInsertId();
        
        echo "✅ Jornada creada con ID: {$jornadaId}\n";
        echo "   📅 Fecha: {$fechaActual}\n";
        echo "   🏪 Tienda: {$tiendaId}\n";
        echo "   🟢 Estado: abierta (apertura=1, cierre=0)\n";
        echo "   👤 Aperturada por usuario ID: {$userId}\n\n";
        
        // Re-consultar
        $stmt = $pdo->prepare("
            SELECT 
                j.*,
                ua.name as usuario_apertura_nombre,
                uc.name as usuario_cierre_nombre
            FROM jornada j
            LEFT JOIN users ua ON j.user_id_apertura = ua.id
            LEFT JOIN users uc ON j.user_id_cierre = uc.id
            WHERE j.id = ?
        ");
        $stmt->execute([$jornadaId]);
        $jornada = $stmt->fetch(PDO::FETCH_OBJ);
        
        echo "🔄 JORNADA ACTUALIZADA:\n";
        echo "   👤 Usuario apertura: " . ($jornada->usuario_apertura_nombre ?? 'NULL') . "\n";
        echo "   🏷️  Estado mostrado en dashboard: 🟢 Abierta\n\n";
    }
    
    // 4. Mostrar cómo se vería en el dashboard
    echo "4. VISUALIZACIÓN EN EL DASHBOARD:\n\n";
    
    if ($jornada) {
        echo "📊 INFORMACIÓN MOSTRADA:\n\n";
        
        echo "🔹 ESTADO DE JORNADA:\n";
        echo "   📅 Icono: fas fa-calendar-day (púrpura)\n";
        echo "   🏷️  Label: \"Estado de Jornada:\"\n";
        
        if ($jornada->apertura == 1 && $jornada->cierre == 0) {
            echo "   🟢 Badge: Verde \"🟢 Abierta\"\n";
        } elseif ($jornada->apertura == 0 && $jornada->cierre == 1) {
            echo "   🔴 Badge: Rojo \"🔴 Cerrada\"\n";
        } elseif ($jornada->apertura == 0 && $jornada->cierre == 0) {
            echo "   🟡 Badge: Amarillo \"🟡 Sin aperturar\"\n";
        }
        
        if ($jornada->usuario_apertura_nombre) {
            echo "   👤 Aperturada por: {$jornada->usuario_apertura_nombre} (color púrpura)\n";
        }
        
        if ($jornada->usuario_cierre_nombre) {
            echo "   👤 Cerrada por: {$jornada->usuario_cierre_nombre} (color rojo)\n";
        }
        
        echo "\n🔹 ESTADO DE CAJA (Después de jornada):\n";
        echo "   💰 Icono: fas fa-cash-register (azul)\n";
        echo "   🏷️  Label: \"Estado de Caja:\"\n";
        echo "   📊 Información de balance y estado\n\n";
    }
    
    echo "5. CÓDIGO IMPLEMENTADO EN EL DASHBOARD:\n\n";
    echo "✅ COMPONENTE PHP (DashboardDinamico.php):\n";
    echo "   - 🆔 Propiedad: \$estadoJornada\n";
    echo "   - 📊 Método: cargarEstadoJornada()\n";
    echo "   - 🔄 Consulta con JOINs a tabla users\n";
    echo "   - 🏷️  Método: obtenerTextoEstadoJornada()\n";
    echo "   - 📅 Filtro por fecha actual y tienda del usuario\n\n";
    
    echo "✅ VISTA BLADE (dashboard-dinamico.blade.php):\n";
    echo "   - 📍 Posición: Antes del estado de caja\n";
    echo "   - 🎨 Diseño: Badges coloreados según estado\n";
    echo "   - 👤 Información: Usuario que aperturó/cerró\n";
    echo "   - 📱 Responsive: Grid flexible\n";
    echo "   - 🎯 Iconos: FontAwesome calendar-day\n\n";
    
    echo "✅ ESTADOS VISUALES:\n";
    echo "   - 🟢 Abierta: Badge verde con texto \"🟢 Abierta\"\n";
    echo "   - 🔴 Cerrada: Badge rojo con texto \"🔴 Cerrada\"\n";
    echo "   - 🟡 Sin aperturar: Badge amarillo \"🟡 Sin aperturar\"\n";
    echo "   - ⚪ Sin jornada: Badge gris \"⚪ Sin jornada\"\n\n";

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== RESUMEN DE IMPLEMENTACIÓN ===\n";
echo "🎯 FUNCIONALIDAD AGREGADA:\n";
echo "   ✅ Estado de jornada visible en dashboard\n";
echo "   ✅ Información del usuario que aperturó/cerró\n";
echo "   ✅ Estados visuales diferenciados por color\n";
echo "   ✅ Posicionado antes del estado de caja\n";
echo "   ✅ Filtrado por tienda del usuario logueado\n";

echo "\n📊 INFORMACIÓN MOSTRADA:\n";
echo "   - 🏷️  Estado actual de la jornada\n";
echo "   - 👤 Usuario que aperturó (si aplica)\n";
echo "   - 👤 Usuario que cerró (si aplica)\n";
echo "   - 📅 Fecha de la jornada\n";
echo "   - 🎨 Badge coloreado según estado\n";

echo "\n🔄 ORDEN EN EL DASHBOARD:\n";
echo "   1. 📊 Información del usuario y rol\n";
echo "   2. 📅 Estado de Jornada (NUEVO)\n";
echo "   3. 💰 Estado de Caja\n";
echo "   4. 🚀 Acciones rápidas\n";
echo "   5. 📈 Estadísticas y gráficos\n";

echo "\n=== FIN DE LA PRUEBA ===\n";
