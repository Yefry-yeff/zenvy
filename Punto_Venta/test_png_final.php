<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

    echo "=== PRUEBA FINAL DE PNG ===\n\n";

    // Encontrar la última factura con imagen
    $result = $pdo->query('
        SELECT
            id,
            numero_factura,
            LENGTH(factura_imagen) as tamano_bytes
        FROM factura
        WHERE factura_imagen IS NOT NULL
        ORDER BY id DESC
        LIMIT 1
    ');

    if ($result->rowCount() > 0) {
        $factura = $result->fetch();
        echo "📋 Factura encontrada: {$factura['numero_factura']}\n";
        echo "📏 Tamaño: {$factura['tamano_bytes']} bytes\n\n";

        // Obtener la imagen
        $stmt = $pdo->prepare('SELECT factura_imagen FROM factura WHERE id = ?');
        $stmt->execute([$factura['id']]);
        $imagenData = $stmt->fetchColumn();

        if ($imagenData) {
            // Verificar signature PNG
            $signature = bin2hex(substr($imagenData, 0, 8));
            echo "🔍 PNG Signature: $signature\n";

            if ($signature === '89504e470d0a1a0a') {
                echo "✅ PNG válido confirmado\n";

                // Guardar como archivo para prueba
                $nombreArchivo = "test_factura_{$factura['numero_factura']}.png";
                file_put_contents($nombreArchivo, $imagenData);
                echo "💾 Guardado como: $nombreArchivo\n";

                // Verificar que el archivo se puede leer como imagen
                if (function_exists('getimagesize')) {
                    $info = getimagesize($nombreArchivo);
                    if ($info) {
                        echo "🖼️ Dimensiones: {$info[0]}x{$info[1]} px\n";
                        echo "🎨 Tipo MIME: {$info['mime']}\n";
                        echo "✅ Archivo PNG válido y legible\n";
                    } else {
                        echo "❌ El archivo no se puede leer como imagen\n";
                    }
                } else {
                    echo "⚠️ getimagesize() no disponible para verificación\n";
                }

                // Simular headers HTTP que se enviarían
                echo "\n📤 Headers HTTP que se enviarían:\n";
                echo "   Content-Type: image/png\n";
                echo "   Content-Length: " . strlen($imagenData) . "\n";
                echo "   Content-Disposition: inline; filename=\"factura_{$factura['numero_factura']}.png\"\n";

                echo "\n🌐 URLs disponibles (después de iniciar servidor):\n";
                echo "   Ver: http://localhost:8000/factura/{$factura['id']}/imagen\n";
                echo "   Descargar: http://localhost:8000/factura/{$factura['id']}/imagen/descargar\n";

            } else {
                echo "❌ PNG inválido - signature incorrecta\n";
            }
        } else {
            echo "❌ No se pudo obtener la imagen de la base de datos\n";
        }
    } else {
        echo "⚠️ No hay facturas con imagen para probar\n";
        echo "Crea una nueva venta para generar una imagen automáticamente\n";
    }

    echo "\n🔧 DIAGNÓSTICO DEL SISTEMA:\n";

    // Verificar extensión GD
    if (extension_loaded('gd')) {
        echo "✅ GD: Disponible\n";
        $gd_info = gd_info();
        echo "   📌 Versión: {$gd_info['GD Version']}\n";
        echo "   📌 PNG Support: " . ($gd_info['PNG Support'] ? 'Sí' : 'No') . "\n";
    } else {
        echo "❌ GD: NO disponible\n";
    }

    // Verificar función imagecreatetruecolor
    if (function_exists('imagecreatetruecolor')) {
        echo "✅ imagecreatetruecolor: Disponible\n";
    } else {
        echo "❌ imagecreatetruecolor: NO disponible\n";
    }

    // Verificar función imagepng
    if (function_exists('imagepng')) {
        echo "✅ imagepng: Disponible\n";
    } else {
        echo "❌ imagepng: NO disponible\n";
    }

    echo "\n💡 SOLUCIÓN APLICADA:\n";
    echo "✓ Usar imagecreatetruecolor() en lugar de imagecreate()\n";
    echo "✓ Headers HTTP mejorados con Content-Length\n";
    echo "✓ Verificación de PNG signature\n";
    echo "✓ Compresión PNG optimizada (nivel 0)\n";
    echo "✓ Content-Disposition inline para visualización\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
