<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Configurar la conexión a la base de datos
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'db_zenvy',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== GENERACIÓN DE IMAGEN DE FACTURA CON NUEVO ENCABEZADO ===\n\n";

try {
    // Obtener la factura más reciente
    $factura = Capsule::table('factura')->orderBy('id', 'desc')->first();

    if (!$factura) {
        echo "❌ No hay facturas disponibles\n";
        exit;
    }

    echo "📄 Generando imagen para factura ID: {$factura->id}, Número: {$factura->numero_factura}\n\n";

    // Obtener información de la empresa
    $empresa = Capsule::table('empresa')->first();

    // Obtener información de la tienda con dirección
    $tienda = Capsule::table('tienda as t')
        ->leftJoin('direccion as d', 't.direccion_sucursal_id', '=', 'd.id')
        ->select('t.*', 'd.domicilio_tributario')
        ->where('t.id', 1)
        ->first();

    // Crear imagen de prueba
    $ancho = 600;
    $alto = 900;
    $imagen = imagecreatetruecolor($ancho, $alto);

    // Habilitar alpha blending y guardar alpha
    imagealphablending($imagen, false);
    imagesavealpha($imagen, true);

    // Definir colores
    $blanco = imagecolorallocate($imagen, 255, 255, 255);
    $negro = imagecolorallocate($imagen, 0, 0, 0);
    $gris = imagecolorallocate($imagen, 128, 128, 128);
    $azul = imagecolorallocate($imagen, 0, 100, 200);

    // Fondo blanco
    imagefill($imagen, 0, 0, $blanco);

    $y = 20;

    echo "🎨 Generando encabezado...\n";

    // LOGO DE LA EMPRESA (si existe)
    if ($empresa && $empresa->logo) {
        try {
            echo "  📷 Procesando logo...\n";
            $logoTemporal = imagecreatefromstring($empresa->logo);
            if ($logoTemporal) {
                $logoAncho = imagesx($logoTemporal);
                $logoAlto = imagesy($logoTemporal);

                $maxTamano = 80;
                $escala = min($maxTamano / $logoAncho, $maxTamano / $logoAlto);
                $nuevoAncho = (int)($logoAncho * $escala);
                $nuevoAlto = (int)($logoAlto * $escala);

                $logoX = ($ancho - $nuevoAncho) / 2;

                imagecopyresampled($imagen, $logoTemporal, $logoX, $y, 0, 0,
                                 $nuevoAncho, $nuevoAlto, $logoAncho, $logoAlto);

                imagedestroy($logoTemporal);
                $y += $nuevoAlto + 15;
                echo "    ✓ Logo agregado ({$nuevoAncho}x{$nuevoAlto})\n";
            }
        } catch (Exception $e) {
            echo "    ⚠️ Error al procesar logo: " . $e->getMessage() . "\n";
        }
    }

    // NOMBRE DE LA TIENDA (grande)
    if ($tienda && $tienda->denominacion_social) {
        $nombreTienda = strtoupper($tienda->denominacion_social);
        $textoAncho = strlen($nombreTienda) * 12;
        $textoX = ($ancho - $textoAncho) / 2;
        imagestring($imagen, 5, max(20, $textoX), $y, $nombreTienda, $azul);
        $y += 30;
        echo "  ✓ Nombre de tienda: {$nombreTienda}\n";
    }

    // NOMBRE DE LA EMPRESA (mediano)
    if ($empresa && $empresa->nombre) {
        $nombreEmpresa = $empresa->nombre;
        $textoAncho = strlen($nombreEmpresa) * 8;
        $textoX = ($ancho - $textoAncho) / 2;
        imagestring($imagen, 3, max(20, $textoX), $y, $nombreEmpresa, $negro);
        $y += 25;
        echo "  ✓ Nombre de empresa: {$nombreEmpresa}\n";
    }

    // RTN DE LA EMPRESA
    if ($empresa && $empresa->rtn) {
        $rtnTexto = "RTN: " . $empresa->rtn;
        $textoAncho = strlen($rtnTexto) * 8;
        $textoX = ($ancho - $textoAncho) / 2;
        imagestring($imagen, 3, max(20, $textoX), $y, $rtnTexto, $negro);
        $y += 20;
        echo "  ✓ RTN: {$empresa->rtn}\n";
    }

    // DIRECCIÓN DE LA SUCURSAL
    if ($tienda && $tienda->domicilio_tributario) {
        $direccion = $tienda->domicilio_tributario;
        $textoAncho = strlen($direccion) * 6;
        $textoX = ($ancho - $textoAncho) / 2;
        imagestring($imagen, 2, max(20, $textoX), $y, $direccion, $gris);
        $y += 15;
        echo "  ✓ Dirección: {$direccion}\n";
    }

    // CORREO DE LA EMPRESA
    if ($empresa && $empresa->correo) {
        $correoTexto = "Email: " . $empresa->correo;
        $textoAncho = strlen($correoTexto) * 6;
        $textoX = ($ancho - $textoAncho) / 2;
        imagestring($imagen, 2, max(20, $textoX), $y, $correoTexto, $gris);
        $y += 15;
        echo "  ✓ Correo: {$empresa->correo}\n";
    }

    // TELÉFONO FORMATEADO
    if ($empresa && $empresa->telefono) {
        $telefono = $empresa->telefono;
        if (strlen($telefono) == 8) {
            $telefonoFormateado = substr($telefono, 0, 4) . '-' . substr($telefono, 4, 4);
        } else {
            $telefonoFormateado = $telefono;
        }
        $telefonoTexto = "Tel: " . $telefonoFormateado;
        $textoAncho = strlen($telefonoTexto) * 6;
        $textoX = ($ancho - $textoAncho) / 2;
        imagestring($imagen, 2, max(20, $textoX), $y, $telefonoTexto, $gris);
        $y += 25;
        echo "  ✓ Teléfono: {$telefonoFormateado}\n";
    }

    // Línea separadora
    imageline($imagen, 20, $y, $ancho-20, $y, $gris);
    $y += 30;

    echo "\n📋 Agregando información de factura...\n";

    // Información de la factura
    imagestring($imagen, 4, 30, $y, "FACTURA: " . $factura->numero_factura, $negro);
    $y += 25;
    imagestring($imagen, 3, 30, $y, "Cliente: " . ($factura->nombre_cliente ?: 'Consumidor Final'), $negro);
    $y += 20;
    imagestring($imagen, 3, 30, $y, "Fecha: " . $factura->fecha_emision, $negro);
    $y += 20;
    if ($factura->rtn) {
        imagestring($imagen, 3, 30, $y, "RTN Cliente: " . $factura->rtn, $negro);
        $y += 20;
    }
    $y += 10;

    // Línea separadora
    imageline($imagen, 20, $y, $ancho-20, $y, $gris);
    $y += 20;

    // Totales de ejemplo
    imagestring($imagen, 3, 350, $y, "Subtotal:", $negro);
    imagestring($imagen, 3, 470, $y, "L. " . number_format((float)$factura->sub_total, 2), $negro);
    $y += 20;
    imagestring($imagen, 3, 350, $y, "ISV:", $negro);
    imagestring($imagen, 3, 470, $y, "L. " . number_format((float)$factura->isv, 2), $negro);
    $y += 20;
    imagestring($imagen, 4, 350, $y, "TOTAL:", $azul);
    imagestring($imagen, 4, 470, $y, "L. " . number_format((float)$factura->total, 2), $azul);

    // Guardar imagen
    $nombreArchivo = "test_factura_{$factura->numero_factura}.png";

    if (imagepng($imagen, $nombreArchivo)) {
        echo "\n✅ Imagen generada exitosamente: {$nombreArchivo}\n";
        echo "📏 Dimensiones: {$ancho}x{$alto} píxeles\n";
        echo "💾 Tamaño del archivo: " . number_format(filesize($nombreArchivo) / 1024, 2) . " KB\n";
    } else {
        echo "\n❌ Error al guardar la imagen\n";
    }

    imagedestroy($imagen);

    echo "\n🎉 Proceso completado!\n";
    echo "💡 Puedes abrir el archivo '{$nombreArchivo}' para ver el resultado\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
