<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

    echo "=== ANÁLISIS DE IMÁGENES GENERADAS ===\n\n";

    // Verificar facturas con imagen
    $result = $pdo->query('
        SELECT
            id,
            numero_factura,
            LENGTH(factura_imagen) as tamano_bytes,
            LEFT(HEX(factura_imagen), 20) as primeros_bytes_hex
        FROM factura
        WHERE factura_imagen IS NOT NULL
        ORDER BY id DESC
        LIMIT 5
    ');

    if ($result->rowCount() > 0) {
        echo "Facturas con imagen encontradas:\n";
        echo str_repeat("-", 70) . "\n";

        foreach($result as $row) {
            echo "Factura: {$row['numero_factura']}\n";
            echo "Tamaño: {$row['tamano_bytes']} bytes\n";
            echo "Primeros bytes (HEX): {$row['primeros_bytes_hex']}\n";

            // Verificar si empieza con signature PNG
            $signature = substr($row['primeros_bytes_hex'], 0, 16);
            if ($signature === '89504E470D0A1A0A') {
                echo "✅ Formato: PNG válido\n";
            } else {
                echo "❌ Formato: NO es PNG válido\n";
                echo "   Signature encontrada: $signature\n";
                echo "   Signature PNG esperada: 89504E470D0A1A0A\n";
            }
            echo str_repeat("-", 70) . "\n";
        }
    } else {
        echo "No se encontraron facturas con imagen.\n";
    }

    // Verificar si GD está generando PNG correctamente
    echo "\n=== PRUEBA DE GENERACIÓN PNG ===\n";

    // Crear imagen de prueba
    $imagen = imagecreate(200, 100);
    $blanco = imagecolorallocate($imagen, 255, 255, 255);
    $negro = imagecolorallocate($imagen, 0, 0, 0);
    imagefill($imagen, 0, 0, $blanco);
    imagestring($imagen, 3, 50, 40, "Prueba PNG", $negro);

    // Capturar output
    ob_start();
    imagepng($imagen);
    $pngData = ob_get_clean();
    imagedestroy($imagen);

    // Verificar signature
    $hexSignature = bin2hex(substr($pngData, 0, 8));
    echo "Signature PNG generada: $hexSignature\n";

    if ($hexSignature === '89504e470d0a1a0a') {
        echo "✅ GD genera PNG correctamente\n";
        echo "Tamaño del PNG de prueba: " . strlen($pngData) . " bytes\n";
    } else {
        echo "❌ GD NO genera PNG correctamente\n";
    }

    // Guardar imagen de prueba para verificación
    file_put_contents('prueba_png.png', $pngData);
    echo "💾 Imagen de prueba guardada como 'prueba_png.png'\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
