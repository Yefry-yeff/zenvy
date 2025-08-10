<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== VERIFICANDO ESTRUCTURA ACTUALIZADA DE TABLA JORNADA ===\n\n";
    
    // Verificar estructura actual de la tabla jornada
    $stmt = $pdo->prepare("DESCRIBE jornada");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "Columnas actuales en la tabla jornada:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type}) - Null: {$column->Null}, Default: {$column->Default}\n";
    }
    
    // Verificar si la columna comentario existe
    $comentarioExists = false;
    foreach ($columns as $column) {
        if ($column->Field === 'comentario') {
            $comentarioExists = true;
            echo "\n✅ CONFIRMADO: La columna 'comentario' existe en la tabla\n";
            echo "   Tipo: {$column->Type}\n";
            echo "   Permite NULL: {$column->Null}\n";
            break;
        }
    }
    
    if (!$comentarioExists) {
        echo "\n❌ La columna 'comentario' NO existe en la tabla\n";
    }

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE VERIFICACIÓN ===\n";
