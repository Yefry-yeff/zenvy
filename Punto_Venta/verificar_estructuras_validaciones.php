<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== VERIFICANDO ESTRUCTURAS DE TABLAS ===\n\n";
    
    // Verificar estructura de tabla jornada
    echo "1. ESTRUCTURA DE TABLA 'jornada':\n\n";
    
    $stmt = $pdo->prepare("DESCRIBE jornada");
    $stmt->execute();
    $columnasJornada = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "📋 COLUMNAS DE TABLA 'jornada':\n";
    foreach ($columnasJornada as $columna) {
        echo "   • {$columna->Field} ({$columna->Type}) - " . ($columna->Null === 'YES' ? 'Nullable' : 'Not Null') . "\n";
    }
    echo "\n";
    
    // Verificar contenido actual de jornada
    echo "2. CONTENIDO ACTUAL DE TABLA 'jornada':\n\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada ORDER BY created_at DESC LIMIT 3");
    $stmt->execute();
    $jornadas = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if ($jornadas) {
        foreach ($jornadas as $index => $jornada) {
            echo "   JORNADA " . ($index + 1) . ":\n";
            foreach ($jornada as $campo => $valor) {
                echo "      {$campo}: {$valor}\n";
            }
            echo "\n";
        }
    } else {
        echo "   ❌ No hay jornadas registradas\n\n";
    }
    
    // Verificar estructura de tabla caja
    echo "3. ESTRUCTURA DE TABLA 'caja':\n\n";
    
    $stmt = $pdo->prepare("DESCRIBE caja");
    $stmt->execute();
    $columnasCaja = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "📋 COLUMNAS DE TABLA 'caja':\n";
    foreach ($columnasCaja as $columna) {
        echo "   • {$columna->Field} ({$columna->Type}) - " . ($columna->Null === 'YES' ? 'Nullable' : 'Not Null') . "\n";
    }
    echo "\n";
    
    // Verificar contenido actual de caja
    echo "4. CONTENIDO ACTUAL DE TABLA 'caja':\n\n";
    
    $stmt = $pdo->prepare("SELECT * FROM caja ORDER BY created_at DESC LIMIT 3");
    $stmt->execute();
    $cajas = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if ($cajas) {
        foreach ($cajas as $index => $caja) {
            echo "   CAJA " . ($index + 1) . ":\n";
            foreach ($caja as $campo => $valor) {
                echo "      {$campo}: {$valor}\n";
            }
            echo "\n";
        }
    } else {
        echo "   ❌ No hay cajas registradas\n\n";
    }
    
    // Verificar estructura de tabla transaccion
    echo "5. ESTRUCTURA DE TABLA 'transaccion':\n\n";
    
    $stmt = $pdo->prepare("DESCRIBE transaccion");
    $stmt->execute();
    $columnasTransaccion = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "📋 COLUMNAS DE TABLA 'transaccion':\n";
    foreach ($columnasTransaccion as $columna) {
        echo "   • {$columna->Field} ({$columna->Type}) - " . ($columna->Null === 'YES' ? 'Nullable' : 'Not Null') . "\n";
    }
    echo "\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== VERIFICACIÓN COMPLETADA ===\n";
