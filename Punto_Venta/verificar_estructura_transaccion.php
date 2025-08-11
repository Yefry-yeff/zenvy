<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    
    echo "=== ESTRUCTURA DE LA TABLA transaccion ===\n";
    $stmt = $pdo->query('DESCRIBE transaccion');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Columna: " . $row['Field'] . " - Tipo: " . $row['Type'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
