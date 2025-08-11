<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

    echo "=== VERIFICACIÓN DE TABLA EMPRESA ===\n\n";

    // Verificar si la tabla existe
    $result = $pdo->query("SHOW TABLES LIKE 'empresa'");

    if ($result->rowCount() > 0) {
        echo "✅ Tabla 'empresa' existe\n\n";

        // Mostrar estructura
        echo "📋 ESTRUCTURA DE LA TABLA:\n";
        $result = $pdo->query('DESCRIBE empresa');

        foreach($result as $row) {
            echo "   {$row['Field']} - {$row['Type']} - " . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL');
            if ($row['Default'] !== null) {
                echo " - Default: {$row['Default']}";
            }
            echo "\n";
        }

        // Verificar datos existentes
        echo "\n📊 DATOS EXISTENTES:\n";
        $result = $pdo->query('SELECT COUNT(*) as total FROM empresa');
        $total = $result->fetch()['total'];

        if ($total > 0) {
            echo "   Total de empresas: $total\n";

            $result = $pdo->query('SELECT id, nombre, rtn, correo, telefono FROM empresa LIMIT 3');
            foreach($result as $row) {
                echo "   ID: {$row['id']}, Nombre: {$row['nombre']}, RTN: {$row['rtn']}\n";
            }
        } else {
            echo "   No hay empresas registradas\n";
        }

    } else {
        echo "❌ Tabla 'empresa' NO existe\n";
        echo "Creando tabla...\n\n";

        $sql = "CREATE TABLE IF NOT EXISTS `db_zenvy`.`empresa` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `nombre` VARCHAR(60) NULL,
            `rtn` VARCHAR(45) NULL,
            `correo` VARCHAR(45) NULL,
            `telefono` INT NULL,
            `logo` BLOB NULL,
            PRIMARY KEY (`id`)
        ) ENGINE = InnoDB";

        $pdo->exec($sql);
        echo "✅ Tabla 'empresa' creada exitosamente\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
