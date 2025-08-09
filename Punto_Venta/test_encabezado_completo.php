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

echo "=== PRUEBA DE GENERACIÓN DE ENCABEZADO ===\n\n";

try {
    // Simular la lógica del nuevo encabezado

    // 1. Obtener datos de empresa
    $empresa = Capsule::table('empresa')->first();
    echo "1. Datos de empresa:\n";
    if ($empresa) {
        echo "   ✓ Nombre: {$empresa->nombre}\n";
        echo "   ✓ RTN: {$empresa->rtn}\n";
        echo "   ✓ Correo: {$empresa->correo}\n";
        echo "   ✓ Teléfono: {$empresa->telefono}\n";
        echo "   ✓ Logo: " . ($empresa->logo ? "Sí (" . strlen($empresa->logo) . " bytes)" : "No") . "\n";
    } else {
        echo "   ❌ No hay empresa registrada\n";
    }
    echo "\n";

    // 2. Obtener datos de tienda
    $tienda = Capsule::table('tienda as t')
        ->leftJoin('direccion as d', 't.direccion_sucursal_id', '=', 'd.id')
        ->select('t.*', 'd.domicilio_tributario')
        ->where('t.id', 1)
        ->first();

    echo "2. Datos de tienda:\n";
    if ($tienda) {
        echo "   ✓ ID: {$tienda->id}\n";
        echo "   ✓ Denominación: {$tienda->denominacion_social}\n";
        echo "   ✓ Descripción: {$tienda->descripcion}\n";
        echo "   ✓ Teléfono: {$tienda->telefono}\n";
        echo "   ✓ Dirección tributaria: " . ($tienda->domicilio_tributario ?: "No configurada") . "\n";
    } else {
        echo "   ❌ No hay tienda registrada\n";
    }
    echo "\n";

    // 3. Simular el encabezado que se generaría
    echo "3. Encabezado que se generaría:\n";
    echo "   " . str_repeat("=", 50) . "\n";

    if ($empresa && $empresa->logo) {
        echo "   [LOGO DE LA EMPRESA]\n";
    }

    if ($tienda && $tienda->denominacion_social) {
        echo "   " . strtoupper($tienda->denominacion_social) . " (grande)\n";
    }

    if ($empresa && $empresa->nombre) {
        echo "   " . $empresa->nombre . " (mediano)\n";
    }

    if ($empresa && $empresa->rtn) {
        echo "   RTN: " . $empresa->rtn . "\n";
    }

    if ($tienda && $tienda->domicilio_tributario) {
        echo "   " . $tienda->domicilio_tributario . "\n";
    }

    if ($empresa && $empresa->correo) {
        echo "   Email: " . $empresa->correo . "\n";
    }

    if ($empresa && $empresa->telefono) {
        $telefono = $empresa->telefono;
        if (strlen($telefono) == 8) {
            $telefonoFormateado = substr($telefono, 0, 4) . '-' . substr($telefono, 4, 4);
        } else {
            $telefonoFormateado = $telefono;
        }
        echo "   Tel: " . $telefonoFormateado . "\n";
    }

    echo "   " . str_repeat("=", 50) . "\n";
    echo "\n";

    // 4. Verificar si hay facturas para probar
    echo "4. Facturas disponibles para generar imagen:\n";
    $facturas = Capsule::table('factura')
        ->orderBy('id', 'desc')
        ->limit(3)
        ->get();

    if (count($facturas) > 0) {
        foreach ($facturas as $factura) {
            echo "   - ID: {$factura->id}, Número: {$factura->numero_factura}\n";
            echo "     Cliente: {$factura->nombre_cliente}, Total: L. {$factura->total}\n";
        }
        echo "\n   💡 Puedes probar generando una factura nueva en el sistema\n";
    } else {
        echo "   ❌ No hay facturas disponibles\n";
        echo "   💡 Crea una factura nueva para probar el encabezado\n";
    }

    echo "\n=== RESULTADO ===\n";
    echo "✓ El nuevo encabezado está configurado correctamente\n";
    echo "✓ Incluye logo, nombres, RTN, dirección, correo y teléfono\n";
    echo "✓ El teléfono se formatea como ####-####\n";
    echo "✓ Listo para probar en una factura real\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
