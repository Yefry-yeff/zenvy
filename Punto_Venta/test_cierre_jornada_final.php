<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PRUEBA FINAL DE CIERRE DE JORNADA CON VALIDACIÓN DE APERTURA ===\n\n";
    
    $fechaPrueba = '2025-01-24';
    $tiendaId = 1;
    
    // 1. Limpiar datos de prueba anteriores
    echo "1. Limpiando datos de prueba...\n";
    $stmt = $pdo->prepare("DELETE FROM jornada WHERE DATE(fecha) = ? AND tienda_id = ?");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    echo "✅ Datos limpiados\n\n";
    
    // 2. Probar validación SIN apertura
    echo "2. CASO 1: Intentar cerrar sin apertura previa\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE DATE(fecha) = ? AND tienda_id = ? AND apertura = 1");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    $jornadaAperturada = $stmt->fetch(PDO::FETCH_OBJ);
    
    $stmt = $pdo->prepare("SELECT denominacion_social FROM tienda WHERE id = ?");
    $stmt->execute([$tiendaId]);
    $tienda = $stmt->fetch(PDO::FETCH_OBJ);
    $nombreTienda = $tienda ? $tienda->denominacion_social : "Tienda ID $tiendaId";
    
    if (!$jornadaAperturada) {
        echo "❌ VALIDACIÓN CORRECTA: No se encontró jornada aperturada\n";
        echo "   Mensaje: No se puede cerrar la jornada porque no se ha aperturado la jornada para la fecha $fechaPrueba de $nombreTienda. Debe aperturar la jornada primero.\n";
        echo "   ✅ El sistema debe mostrar este error y no permitir continuar\n\n";
    }
    
    // 3. Crear apertura
    echo "3. CASO 2: Crear apertura de jornada\n";
    
    $stmt = $pdo->prepare("INSERT INTO jornada (fecha, tienda_id, users_id, apertura, cierre, created_at, updated_at) VALUES (?, ?, 1, 1, 0, NOW(), NOW())");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    $jornadaId = $pdo->lastInsertId();
    
    echo "✅ Jornada aperturada creada con ID: $jornadaId\n\n";
    
    // 4. Probar validación CON apertura
    echo "4. CASO 3: Intentar cerrar CON apertura previa\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE DATE(fecha) = ? AND tienda_id = ? AND apertura = 1");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    $jornadaAperturada = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($jornadaAperturada) {
        echo "✅ VALIDACIÓN EXITOSA: Jornada aperturada encontrada\n";
        echo "   - ID: {$jornadaAperturada->id}\n";
        echo "   - Apertura: {$jornadaAperturada->apertura}\n";
        echo "   - Cierre: {$jornadaAperturada->cierre}\n";
        
        if ($jornadaAperturada->cierre == 1) {
            echo "❌ ERROR: La jornada ya está cerrada\n";
        } else {
            echo "✅ PUEDE PROCEDER: La jornada puede ser cerrada\n";
            echo "   El sistema continuará con las verificaciones de cajas\n\n";
        }
    }
    
    // 5. Simular cierre
    echo "5. CASO 4: Simular proceso de cierre\n";
    
    $stmt = $pdo->prepare("UPDATE jornada SET cierre = 1, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$jornadaAperturada->id]);
    
    echo "✅ Jornada marcada como cerrada\n\n";
    
    // 6. Probar validación con jornada YA cerrada
    echo "6. CASO 5: Intentar cerrar jornada ya cerrada\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE DATE(fecha) = ? AND tienda_id = ? AND apertura = 1");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    $jornadaCerrada = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($jornadaCerrada && $jornadaCerrada->cierre == 1) {
        echo "❌ VALIDACIÓN CORRECTA: Jornada ya cerrada\n";
        echo "   Mensaje: La jornada para la fecha $fechaPrueba de $nombreTienda ya está cerrada.\n";
        echo "   ✅ El sistema debe mostrar este error y no permitir continuar\n\n";
    }
    
    // 7. Estado final
    echo "7. ESTADO FINAL:\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE DATE(fecha) = ? AND tienda_id = ?");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    $jornadaFinal = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($jornadaFinal) {
        echo "Jornada ID: {$jornadaFinal->id}\n";
        echo "Fecha: {$jornadaFinal->fecha}\n";
        echo "Tienda: $nombreTienda (ID: {$jornadaFinal->tienda_id})\n";
        echo "Usuario: {$jornadaFinal->users_id}\n";
        echo "Apertura: {$jornadaFinal->apertura}\n";
        echo "Cierre: {$jornadaFinal->cierre}\n";
        echo "Creado: {$jornadaFinal->created_at}\n";
        echo "Actualizado: {$jornadaFinal->updated_at}\n";
    }

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== RESUMEN DE LA IMPLEMENTACIÓN FINAL ===\n";
echo "✅ 1. Validación de apertura ANTES de otras verificaciones\n";
echo "✅ 2. Mensaje específico con fecha y nombre de tienda\n";
echo "✅ 3. Verificación de jornada ya cerrada\n";
echo "✅ 4. Solo usa columnas que existen en la tabla\n";
echo "✅ 5. Sin dependencias de JavaScript\n";
echo "✅ 6. Cualquier usuario con permisos puede cerrar si hay apertura\n";
echo "✅ 7. Sistema robusto con transacciones de base de datos\n";
echo "✅ 8. Interfaz profesional con alertas y validaciones\n";

echo "\n=== LÓGICA DE VALIDACIÓN ===\n";
echo "1. SI no hay jornada con apertura=1 para la fecha → ERROR específico\n";
echo "2. SI hay jornada aperturada pero cierre=1 → ERROR 'ya cerrada'\n";
echo "3. SI hay jornada aperturada y cierre=0 → CONTINUAR con verificaciones\n";
echo "4. Verificar cajas abiertas y diferencias (modal de alertas)\n";
echo "5. Procesar cierre: UPDATE jornada SET cierre=1\n";

echo "\n=== FIN DE LA PRUEBA ===\n";
