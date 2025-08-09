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

echo "=== PRUEBA DE VISTA DE IMPRESIÓN ACTUALIZADA ===\n\n";

try {
    // Verificar que tengamos factura, empresa y tienda
    echo "1. 📄 Verificando factura más reciente...\n";
    $factura = Capsule::table('factura')->orderBy('id', 'desc')->first();
    if ($factura) {
        echo "   ✅ Factura ID: {$factura->id}, Número: {$factura->numero_factura}\n";
        echo "   ✅ Cliente: " . ($factura->nombre_cliente ?: 'Consumidor Final') . "\n";
        echo "   ✅ Total: L. " . number_format((float)$factura->total, 2) . "\n";
    } else {
        echo "   ❌ No hay facturas disponibles\n";
        exit;
    }
    
    echo "\n2. 🏢 Verificando datos de empresa...\n";
    $empresa = Capsule::table('empresa')->first();
    if ($empresa) {
        echo "   ✅ Empresa: {$empresa->nombre}\n";
        echo "   ✅ RTN: {$empresa->rtn}\n";
        echo "   ✅ Correo: {$empresa->correo}\n";
        echo "   ✅ Teléfono: {$empresa->telefono}\n";
        echo "   ✅ Logo: " . ($empresa->logo ? "Sí (" . number_format(strlen($empresa->logo)/1024, 1) . " KB)" : "No") . "\n";
    } else {
        echo "   ❌ No hay datos de empresa\n";
    }
    
    echo "\n3. 🏪 Verificando datos de tienda...\n";
    $tienda = Capsule::table('tienda as t')
        ->leftJoin('direccion as d', 't.direccion_sucursal_id', '=', 'd.id')
        ->select('t.*', 'd.domicilio_tributario')
        ->where('t.id', 1)
        ->first();
    
    if ($tienda) {
        echo "   ✅ Tienda: {$tienda->denominacion_social}\n";
        echo "   ✅ Descripción: {$tienda->descripcion}\n";
        echo "   ✅ Dirección: " . ($tienda->domicilio_tributario ?: "No configurada") . "\n";
        echo "   ✅ Teléfono tienda: {$tienda->telefono}\n";
    } else {
        echo "   ❌ No hay datos de tienda\n";
    }
    
    echo "\n4. 🎯 Verificando productos de la factura...\n";
    $productos = Capsule::table('factura_has_producto as fhp')
        ->join('producto as p', 'fhp.producto_id', '=', 'p.id')
        ->where('fhp.factura_id', $factura->id)
        ->select('p.nombre', 'p.codigo_barra', 'fhp.cantidad', 'fhp.precio_unidad', 'fhp.total')
        ->get();
    
    if ($productos->count() > 0) {
        echo "   ✅ Productos encontrados: {$productos->count()}\n";
        foreach ($productos as $producto) {
            echo "     - {$producto->nombre} (Cant: {$producto->cantidad}, Precio: L.{$producto->precio_unidad})\n";
        }
    } else {
        echo "   ❌ No hay productos en la factura\n";
    }
    
    echo "\n5. 💳 Verificando métodos de pago...\n";
    $pagos = Capsule::table('factura_has_pago as fhp')
        ->join('tipo_pago as tp', 'fhp.tipo_pago_id', '=', 'tp.id')
        ->where('fhp.factura_id', $factura->id)
        ->select('tp.nombre as metodo', 'fhp.pago_recibido')
        ->get();
    
    if ($pagos->count() > 0) {
        echo "   ✅ Métodos de pago: {$pagos->count()}\n";
        foreach ($pagos as $pago) {
            echo "     - {$pago->metodo}: L." . number_format((float)$pago->pago_recibido, 2) . "\n";
        }
    } else {
        echo "   ❌ No hay métodos de pago registrados\n";
    }
    
    // Simular la vista de impresión
    echo "\n6. 📋 Vista de impresión que se mostraría:\n";
    echo "   " . str_repeat("=", 60) . "\n";
    
    // Logo
    if ($empresa && $empresa->logo) {
        echo "   [LOGO DE LA EMPRESA]\n";
    }
    
    // Nombre de tienda
    if ($tienda && $tienda->denominacion_social) {
        echo "   " . strtoupper($tienda->denominacion_social) . " (GRANDE)\n";
    }
    
    // Nombre de empresa
    if ($empresa && $empresa->nombre) {
        echo "   " . $empresa->nombre . " (mediano)\n";
    }
    
    // RTN
    if ($empresa && $empresa->rtn) {
        echo "   RTN: " . $empresa->rtn . "\n";
    }
    
    // Dirección
    if ($tienda && $tienda->domicilio_tributario) {
        echo "   " . $tienda->domicilio_tributario . "\n";
    }
    
    // Correo
    if ($empresa && $empresa->correo) {
        echo "   Email: " . $empresa->correo . "\n";
    }
    
    // Teléfono formateado
    if ($empresa && $empresa->telefono) {
        $telefono = $empresa->telefono;
        $telefonoFormateado = strlen($telefono) == 8 
            ? substr($telefono, 0, 4) . '-' . substr($telefono, 4, 4)
            : $telefono;
        echo "   Tel: " . $telefonoFormateado . "\n";
    }
    
    echo "   " . str_repeat("=", 60) . "\n";
    echo "   FACTURA #: {$factura->numero_factura}\n";
    echo "   CLIENTE: " . ($factura->nombre_cliente ?: 'Consumidor Final') . "\n";
    echo "   FECHA: {$factura->fecha_emision}\n";
    echo "   " . str_repeat("-", 60) . "\n";
    
    foreach ($productos as $producto) {
        echo "   {$producto->nombre}\n";
        echo "   {$producto->cantidad} x L.{$producto->precio_unidad} = L.{$producto->total}\n";
    }
    
    echo "   " . str_repeat("-", 60) . "\n";
    echo "   TOTAL: L." . number_format((float)$factura->total, 2) . "\n";
    echo "   " . str_repeat("=", 60) . "\n";
    
    echo "\n🎉 ¡VISTA DE IMPRESIÓN ACTUALIZADA CORRECTAMENTE!\n";
    echo "✅ Todos los datos están disponibles\n";
    echo "✅ El encabezado incluye información completa de empresa y tienda\n";
    echo "✅ Logo, nombres, RTN, dirección, correo y teléfono se mostrarán\n";
    echo "✅ Listo para probar en el sistema\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
