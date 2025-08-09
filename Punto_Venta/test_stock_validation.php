<?php

require_once 'vendor/autoload.php';

// Cargar la configuración de Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Factura;
use App\Models\Producto;
use App\Models\Bodega;
use App\Models\Tienda;

echo "=== PRUEBA DE VALIDACIÓN DE STOCK ===\n\n";

// Obtener una tienda con bodega principal
echo "1. Buscando tiendas con bodega principal...\n";
$tienda = \DB::table('tienda as t')
    ->join('bodega as b', 't.id', '=', 'b.tienda_id')
    ->where('b.principal', 1)
    ->where('b.estado_id', 1)
    ->select('t.*', 'b.nombre as bodega_nombre', 'b.id as bodega_id')
    ->first();

if (!$tienda) {
    echo "❌ No se encontró ninguna tienda con bodega principal.\n";
    echo "Creando datos de prueba...\n";

    // Crear tienda de prueba
    $tiendaId = \DB::table('tienda')->insertGetId([
        'denominacion_social' => 'Tienda Prueba',
        'telefono' => '12345678',
        'estado_id' => 1,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // Crear bodega principal
    \DB::table('bodega')->insert([
        'nombre' => 'Bodega Principal Prueba',
        'tienda_id' => $tiendaId,
        'principal' => 1,
        'estado_id' => 1,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    echo "✅ Datos de prueba creados.\n";
    $tienda = (object) ['id' => $tiendaId, 'denominacion_social' => 'Tienda Prueba'];
} else {
    echo "✅ Tienda encontrada: {$tienda->denominacion_social} (Bodega: {$tienda->bodega_nombre})\n";
}

// Obtener un producto de prueba
echo "\n2. Buscando productos...\n";
$producto = \DB::table('producto')->where('estado_id', 1)->first();

if (!$producto) {
    echo "❌ No se encontraron productos activos.\n";
    exit;
}

echo "✅ Producto encontrado: {$producto->nombre} (ID: {$producto->id})\n";

// Probar la validación de stock
echo "\n3. Probando validación de stock...\n";

// Caso 1: Cantidad válida (1 unidad)
echo "\nCaso 1: Validando 1 unidad...\n";
$validacion1 = Factura::validarStockBodegaPrincipal($producto->id, 1, $tienda->id);
echo $validacion1['valido'] ? "✅ " : "❌ ";
echo $validacion1['mensaje'] . "\n";

// Caso 2: Cantidad excesiva (1000 unidades)
echo "\nCaso 2: Validando 1000 unidades...\n";
$validacion2 = Factura::validarStockBodegaPrincipal($producto->id, 1000, $tienda->id);
echo $validacion2['valido'] ? "✅ " : "❌ ";
echo $validacion2['mensaje'] . "\n";

// Mostrar stock actual
echo "\n4. Consultando stock actual...\n";
$stockActual = \DB::table('distribucion_stock as ds')
    ->join('seccion as s', 'ds.seccion_id', '=', 's.id')
    ->join('segmento as seg', 's.segmento_id', '=', 'seg.id')
    ->join('bodega as b', 'seg.bodega_id', '=', 'b.id')
    ->where('b.tienda_id', $tienda->id)
    ->where('b.principal', 1)
    ->where('ds.producto_id', $producto->id)
    ->where('ds.estado', 'recibido')
    ->sum('ds.cantidad');

echo "Stock actual del producto {$producto->nombre} en bodega principal: {$stockActual} unidades\n";

echo "\n=== FIN DE LA PRUEBA ===\n";
