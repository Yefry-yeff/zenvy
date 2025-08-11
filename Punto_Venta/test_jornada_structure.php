<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== VERIFICACIÓN DE ESTRUCTURA DE TABLA JORNADA ===\n\n";
    
    // Verificar estructura de la tabla jornada
    $stmt = $pdo->prepare("DESCRIBE jornada");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "Columnas en la tabla jornada:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type}) - Null: {$column->Null}, Default: {$column->Default}\n";
    }
    
    echo "\n=== CREANDO JORNADA DE PRUEBA SIMPLIFICADA ===\n";
    
    $fechaPrueba = '2025-01-24';
    $tiendaId = 1;
    
    // Verificar si ya existe
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE DATE(fecha) = ? AND tienda_id = ?");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    $existe = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($existe) {
        echo "Ya existe una jornada: ID {$existe->id}, Apertura: {$existe->apertura}, Cierre: {$existe->cierre}\n";
    } else {
        // Crear con columnas que sabemos que existen
        $stmt = $pdo->prepare("INSERT INTO jornada (fecha, tienda_id, apertura, cierre, comentario, created_at, updated_at) VALUES (?, ?, 1, 0, 'Jornada de prueba', NOW(), NOW())");
        $stmt->execute([$fechaPrueba, $tiendaId]);
        
        $jornadaId = $pdo->lastInsertId();
        echo "✅ Jornada creada con ID: $jornadaId\n";
    }
    
    echo "\n=== PROBANDO VALIDACIÓN FINAL ===\n";
    
    // Probar la validación que implementamos
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE DATE(fecha) = ? AND tienda_id = ? AND apertura = 1");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    $jornadaAperturada = $stmt->fetch(PDO::FETCH_OBJ);
    
    if (!$jornadaAperturada) {
        echo "❌ ERROR: No se ha aperturado la jornada para la fecha $fechaPrueba de Paperland\n";
    } else {
        echo "✅ ÉXITO: Jornada aperturada encontrada (ID: {$jornadaAperturada->id})\n";
        
        if ($jornadaAperturada->cierre == 1) {
            echo "❌ La jornada ya está cerrada\n";
        } else {
            echo "✅ La jornada puede ser cerrada\n";
        }
    }

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN ===\n";
