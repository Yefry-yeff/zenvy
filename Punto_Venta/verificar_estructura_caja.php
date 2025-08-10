<?php
/**
 * Verificación de estructura de tablas caja y transaccion
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== VERIFICACIÓN DE ESTRUCTURA DE TABLAS ===\n\n";

    // Verificar tabla caja
    echo "1. TABLA CAJA:\n";
    try {
        $result = $pdo->query('DESCRIBE caja');
        echo "   ✅ Tabla 'caja' existe\n";
        echo "   Columnas:\n";
        foreach($result as $row) {
            echo "   - " . $row['Field'] . " (" . $row['Type'] . ")" . 
                 ($row['Null'] == 'NO' ? ' NOT NULL' : ' NULL') . 
                 ($row['Key'] ? ' [' . $row['Key'] . ']' : '') . "\n";
        }
    } catch(Exception $e) {
        echo "   ❌ Error con tabla 'caja': " . $e->getMessage() . "\n";
    }

    echo "\n";

    // Verificar tabla transaccion
    echo "2. TABLA TRANSACCION:\n";
    try {
        $result = $pdo->query('DESCRIBE transaccion');
        echo "   ✅ Tabla 'transaccion' existe\n";
        echo "   Columnas:\n";
        foreach($result as $row) {
            echo "   - " . $row['Field'] . " (" . $row['Type'] . ")" . 
                 ($row['Null'] == 'NO' ? ' NOT NULL' : ' NULL') . 
                 ($row['Key'] ? ' [' . $row['Key'] . ']' : '') . "\n";
        }
    } catch(Exception $e) {
        echo "   ❌ Error con tabla 'transaccion': " . $e->getMessage() . "\n";
        
        // Si no existe, verificar alternativas
        echo "   Buscando tablas similares...\n";
        $result = $pdo->query("SHOW TABLES LIKE '%transac%'");
        foreach($result as $row) {
            echo "   - Encontrada: " . implode('', $row) . "\n";
        }
    }

    echo "\n";

    // Verificar tabla users para relaciones
    echo "3. TABLA USERS (para referencia):\n";
    try {
        $result = $pdo->query('DESCRIBE users');
        echo "   ✅ Tabla 'users' existe\n";
        echo "   Columnas relevantes:\n";
        foreach($result as $row) {
            if (in_array($row['Field'], ['id', 'name', 'email', 'created_at'])) {
                echo "   - " . $row['Field'] . " (" . $row['Type'] . ")\n";
            }
        }
    } catch(Exception $e) {
        echo "   ❌ Error con tabla 'users': " . $e->getMessage() . "\n";
    }

    echo "\n";

    // Verificar datos de ejemplo en caja
    echo "4. DATOS DE EJEMPLO EN CAJA:\n";
    try {
        $result = $pdo->query('SELECT * FROM caja LIMIT 3');
        $count = 0;
        foreach($result as $row) {
            echo "   - ID: " . $row['id'] . ", Users_ID: " . $row['users_id'] . 
                 ", Balance: " . $row['balance'] . ", Estado: " . $row['estado_caja'] . "\n";
            $count++;
        }
        if ($count == 0) {
            echo "   (No hay datos en la tabla caja)\n";
        }
    } catch(Exception $e) {
        echo "   ❌ Error consultando datos de caja: " . $e->getMessage() . "\n";
    }

    echo "\n=== VERIFICACIÓN COMPLETADA ===\n";

} catch(Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
}
?>
