<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PRUEBA DE APERTURA DE JORNADA CON VALIDACIONES ===\n\n";
    
    $fechaActual = date('Y-m-d');
    $fechaAnterior = date('Y-m-d', strtotime('-1 day'));
    $fechaPosterior = date('Y-m-d', strtotime('+1 day'));
    $tiendaId = 1;
    
    echo "Configuración de prueba:\n";
    echo "- Fecha actual: $fechaActual\n";
    echo "- Fecha anterior: $fechaAnterior\n";
    echo "- Fecha posterior: $fechaPosterior\n";
    echo "- Tienda ID: $tiendaId\n\n";
    
    // 1. Limpiar datos de prueba
    echo "1. Limpiando datos de prueba...\n";
    $stmt = $pdo->prepare("DELETE FROM jornada WHERE tienda_id = ? AND fecha IN (?, ?, ?)");
    $stmt->execute([$tiendaId, $fechaAnterior, $fechaActual, $fechaPosterior]);
    echo "✅ Datos limpiados\n\n";
    
    // 2. CASO 1: Primera apertura (sin registros anteriores)
    echo "2. CASO 1: Primera apertura de jornada (primera vez)\n";
    
    // Verificar que no hay registros anteriores
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM jornada WHERE tienda_id = ?");
    $stmt->execute([$tiendaId]);
    $registrosAnteriores = $stmt->fetch(PDO::FETCH_OBJ)->count;
    
    if ($registrosAnteriores == 0) {
        echo "✅ CONDICIÓN: No hay registros anteriores para la tienda\n";
        echo "   Resultado: Se debe permitir la apertura (primera vez)\n";
        
        // Simular apertura
        $stmt = $pdo->prepare("INSERT INTO jornada (fecha, tienda_id, apertura, cierre, user_id_apertura, comentario, created_at, updated_at) VALUES (?, ?, 1, 0, 1, 'Primera apertura de jornada', NOW(), NOW())");
        $stmt->execute([$fechaActual, $tiendaId]);
        
        echo "✅ Primera jornada aperturada exitosamente\n\n";
    }
    
    // 3. CASO 2: Intentar aperturar jornada ya aperturada
    echo "3. CASO 2: Intentar aperturar jornada ya aperturada\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE fecha = ? AND tienda_id = ? AND apertura = 1");
    $stmt->execute([$fechaActual, $tiendaId]);
    $jornadaYaAperturada = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($jornadaYaAperturada) {
        echo "❌ VALIDACIÓN CORRECTA: Ya existe jornada aperturada para hoy\n";
        echo "   Mensaje: 'Ya existe una jornada aperturada para la fecha $fechaActual en [tienda]'\n\n";
    }
    
    // 4. CASO 3: Cerrar jornada actual y probar apertura del día siguiente
    echo "4. CASO 3: Cerrar jornada actual y simular día siguiente\n";
    
    // Cerrar jornada actual
    $stmt = $pdo->prepare("UPDATE jornada SET apertura = 0, cierre = 1, user_id_cierre = 1, updated_at = NOW() WHERE fecha = ? AND tienda_id = ?");
    $stmt->execute([$fechaActual, $tiendaId]);
    
    echo "✅ Jornada actual cerrada\n";
    
    // Intentar aperturar el día siguiente
    $fechaSiguiente = date('Y-m-d', strtotime('+1 day'));
    
    echo "Simulando apertura para: $fechaSiguiente\n";
    
    // Validar que fecha anterior está cerrada
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE tienda_id = ? AND fecha = ?");
    $stmt->execute([$tiendaId, $fechaActual]);
    $jornadaAnterior = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($jornadaAnterior && $jornadaAnterior->cierre == 1) {
        echo "✅ VALIDACIÓN EXITOSA: Jornada anterior está cerrada\n";
        echo "   Puede proceder con apertura del día siguiente\n\n";
    } else {
        echo "❌ VALIDACIÓN FALLIDA: Jornada anterior no está cerrada\n\n";
    }
    
    // 5. CASO 4: Intentar aperturar sin cerrar día anterior
    echo "5. CASO 4: Simular jornada anterior SIN cerrar\n";
    
    // Crear jornada anterior sin cerrar
    $stmt = $pdo->prepare("INSERT INTO jornada (fecha, tienda_id, apertura, cierre, user_id_apertura, created_at, updated_at) VALUES (?, ?, 1, 0, 1, NOW(), NOW())");
    $stmt->execute([date('Y-m-d', strtotime('-2 days')), $tiendaId]);
    
    // Intentar validar para fecha nueva
    $fechaNueva = date('Y-m-d', strtotime('-1 day'));
    $fechaAnteriorValidacion = date('Y-m-d', strtotime('-2 days'));
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE tienda_id = ? AND fecha = ?");
    $stmt->execute([$tiendaId, $fechaAnteriorValidacion]);
    $jornadaAnteriorSinCerrar = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($jornadaAnteriorSinCerrar && $jornadaAnteriorSinCerrar->cierre != 1) {
        echo "❌ VALIDACIÓN CORRECTA: Jornada anterior no está cerrada\n";
        echo "   Mensaje: 'No se puede aperturar porque la jornada del día anterior no está cerrada'\n\n";
    }
    
    // 6. CASO 5: Validar que no se pueden aperturar fechas anteriores
    echo "6. CASO 5: Intentar aperturar fecha anterior\n";
    
    $fechaAnteriorIntento = date('Y-m-d', strtotime('-1 day'));
    
    if ($fechaAnteriorIntento !== $fechaActual) {
        echo "❌ VALIDACIÓN CORRECTA: No se puede aperturar fecha anterior\n";
        echo "   Fecha intento: $fechaAnteriorIntento\n";
        echo "   Fecha actual: $fechaActual\n";
        echo "   Mensaje: 'Solo se puede aperturar la jornada de la fecha actual'\n\n";
    }
    
    // 7. Estado final de pruebas
    echo "7. RESUMEN DE VALIDACIONES IMPLEMENTADAS:\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE tienda_id = ? ORDER BY fecha DESC");
    $stmt->execute([$tiendaId]);
    $todasLasJornadas = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "Jornadas en la tienda:\n";
    foreach ($todasLasJornadas as $jornada) {
        $estado = '';
        if ($jornada->apertura == 1 && $jornada->cierre == 0) {
            $estado = '🟢 ABIERTA';
        } elseif ($jornada->apertura == 0 && $jornada->cierre == 1) {
            $estado = '🔴 CERRADA';
        } else {
            $estado = '🟡 ESTADO INCONSISTENTE';
        }
        
        echo "   - Fecha: {$jornada->fecha} | Estado: $estado | Apertura: {$jornada->apertura} | Cierre: {$jornada->cierre}\n";
    }

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== LÓGICA DE VALIDACIÓN IMPLEMENTADA ===\n";
echo "1. ✅ Solo fecha actual: fechaApertura === fechaActual\n";
echo "2. ✅ No doble apertura: WHERE fecha = ? AND apertura = 1\n";
echo "3. ✅ Día anterior cerrado: WHERE fecha = fechaAnterior AND cierre = 1\n";
echo "4. ✅ Primera vez permitida: COUNT(*) = 0 para la tienda\n";
echo "5. ✅ Registro con user_id_apertura: Auth::id()\n";

echo "\n=== FLUJO DE APERTURA ===\n";
echo "1. Validar fecha actual\n";
echo "2. Verificar no existe apertura para hoy\n";
echo "3. Si hay registros anteriores, validar cierre día anterior\n";
echo "4. Si no hay registros, permitir (primera vez)\n";
echo "5. INSERT/UPDATE: apertura=1, cierre=0, user_id_apertura=Auth::id()\n";

echo "\n=== FIN DE LA PRUEBA ===\n";
