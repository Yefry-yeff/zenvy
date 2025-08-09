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

echo "=== PRUEBA DEL NUEVO ENCABEZADO DE FACTURA ===\n\n";

try {
    // 1. Verificar datos de empresa
    echo "1. Verificando datos de empresa...\n";
    $empresa = Capsule::table('empresa')->first();
    if ($empresa) {
        echo "   ✓ Empresa encontrada:\n";
        echo "     - Nombre: {$empresa->nombre}\n";
        echo "     - RTN: {$empresa->rtn}\n";
        echo "     - Correo: {$empresa->correo}\n";
        echo "     - Teléfono: {$empresa->telefono}\n";
        
        if ($empresa->logo) {
            echo "     - Logo: Sí (tamaño: " . strlen($empresa->logo) . " bytes)\n";
        } else {
            echo "     - Logo: No\n";
        }
    } else {
        echo "   ❌ No hay empresa registrada\n";
        echo "   📝 Para probar, ejecuta:\n";
        echo "      INSERT INTO empresa (nombre, rtn, correo, telefono) VALUES \n";
        echo "      ('Mi Empresa', '08011234567890', 'empresa@email.com', '22345678');\n";
    }
    echo "\n";

    // 2. Verificar sucursales y direcciones
    echo "2. Verificando sucursales y direcciones...\n";
    $sucursales = Capsule::table('sucursales as s')
        ->leftJoin('direccion as d', 's.direccion_sucursal_id', '=', 'd.id')
        ->select('s.id', 's.nombre', 's.direccion_sucursal_id', 'd.direccion_tributaria')
        ->get();
    
    if (count($sucursales) > 0) {
        echo "   ✓ Sucursales encontradas:\n";
        foreach ($sucursales as $sucursal) {
            echo "     - ID: {$sucursal->id}, Nombre: {$sucursal->nombre}\n";
            if ($sucursal->direccion_tributaria) {
                echo "       Dirección: {$sucursal->direccion_tributaria}\n";
            } else {
                echo "       Dirección: No configurada\n";
            }
        }
    } else {
        echo "   ❌ No hay sucursales registradas\n";
    }
    echo "\n";

    // 3. Formateo de teléfono
    echo "3. Probando formateo de teléfono...\n";
    if ($empresa && $empresa->telefono) {
        $telefono = $empresa->telefono;
        if (strlen($telefono) == 8) {
            $telefonoFormateado = substr($telefono, 0, 4) . '-' . substr($telefono, 4, 4);
            echo "   ✓ Teléfono original: {$telefono}\n";
            echo "   ✓ Teléfono formateado: {$telefonoFormateado}\n";
        } else {
            echo "   ⚠️  Teléfono no tiene 8 dígitos: {$telefono}\n";
        }
    }
    echo "\n";

    // 4. Verificar facturas existentes
    echo "4. Verificando facturas para prueba...\n";
    $facturas = Capsule::table('factura')->orderBy('id', 'desc')->limit(3)->get();
    if (count($facturas) > 0) {
        echo "   ✓ Facturas disponibles para prueba:\n";
        foreach ($facturas as $factura) {
            echo "     - ID: {$factura->id}, Número: {$factura->numero_factura}, Cliente: {$factura->nombre_cliente}\n";
        }
    } else {
        echo "   ❌ No hay facturas para probar\n";
    }
    echo "\n";

    // 5. Verificar extensión GD para manejo de imágenes
    echo "5. Verificando extensión GD...\n";
    if (extension_loaded('gd')) {
        echo "   ✓ Extensión GD está disponible\n";
        $gdInfo = gd_info();
        echo "   ✓ Versión: {$gdInfo['GD Version']}\n";
        if ($gdInfo['PNG Support']) {
            echo "   ✓ Soporte PNG: Sí\n";
        }
        if ($gdInfo['JPEG Support']) {
            echo "   ✓ Soporte JPEG: Sí\n";
        }
    } else {
        echo "   ❌ Extensión GD no está disponible\n";
    }
    echo "\n";

    echo "=== RESUMEN ===\n";
    echo "✓ El nuevo encabezado incluirá:\n";
    echo "  1. Logo de la empresa (si existe)\n";
    echo "  2. Nombre de la tienda (grande)\n";
    echo "  3. Nombre de la empresa (mediano)\n";
    echo "  4. RTN de la empresa\n";
    echo "  5. Dirección tributaria de la sucursal\n";
    echo "  6. Correo de la empresa\n";
    echo "  7. Teléfono formateado (####-####)\n\n";
    
    echo "PRÓXIMOS PASOS:\n";
    echo "1. Asegurar que hay datos de empresa registrados\n";
    echo "2. Configurar direcciones tributarias en las sucursales\n";
    echo "3. Probar generación de factura con el nuevo encabezado\n";
    echo "4. Verificar que el logo se muestre correctamente\n";

} catch (Exception $e) {
    echo "❌ Error durante la verificación: " . $e->getMessage() . "\n";
}
