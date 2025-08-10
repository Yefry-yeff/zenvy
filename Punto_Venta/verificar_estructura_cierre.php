<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    
    echo "=== ESTRUCTURA ACTUAL DE LA TABLA cierre_de_caja ===\n";
    $stmt = $pdo->query('DESCRIBE cierre_de_caja');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Columna: " . $row['Field'] . " - Tipo: " . $row['Type'] . "\n";
    }
    
    echo "\n=== VERIFICANDO SI LA TABLA EXISTE ===\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'cierre_de_caja'");
    if ($stmt->rowCount() > 0) {
        echo "✓ La tabla cierre_de_caja existe\n";
    } else {
        echo "✗ La tabla cierre_de_caja NO existe\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
