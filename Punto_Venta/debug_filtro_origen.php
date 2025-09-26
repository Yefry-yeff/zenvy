<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Producto;

// Configurar Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DEBUG FILTRO DE ORIGEN ===\n\n";

// Estadísticas generales
$totalProductos = Producto::where('estado_id', 1)->count();
$productosValencia = Producto::where('estado_id', 1)->where('producto_valencia', 1)->count();
$productosLocales = Producto::where('estado_id', 1)->where('producto_valencia', 0)->count();

echo "DISTRIBUCIÓN DE PRODUCTOS:\n";
echo "- Total activos: $totalProductos\n";
echo "- Productos Valencia (producto_valencia=1): $productosValencia\n";
echo "- Productos Locales (producto_valencia=0): $productosLocales\n\n";

// Probar diferentes filtros
$filtros = ['todos', 'valencia', 'paperland', 'zenvy', 'PAPERLAND', 'Valencia'];

foreach ($filtros as $filtro) {
    echo "PROBANDO FILTRO: '$filtro'\n";
    
    $query = Producto::where('estado_id', 1);
    
    if ($filtro !== 'todos') {
        $filtroLower = strtolower(trim($filtro));
        
        if ($filtroLower === 'valencia') {
            $query->where('producto_valencia', '=', 1);
            echo "  Condición aplicada: producto_valencia = 1\n";
        } elseif ($filtroLower === 'paperland' || $filtroLower === 'zenvy') {
            $query->where('producto_valencia', '=', 0);
            echo "  Condición aplicada: producto_valencia = 0\n";
        } else {
            echo "  ⚠️  Filtro desconocido, sin condición aplicada\n";
        }
    }
    
    $count = $query->count();
    echo "  Resultado: $count productos encontrados\n";
    
    // Mostrar SQL generado
    $sql = str_replace('?', "'%s'", $query->toSql());
    $sql = vsprintf($sql, $query->getBindings());
    echo "  SQL: $sql\n\n";
}

echo "=== MUESTRAS DE PRODUCTOS ===\n";

// Mostrar algunos productos de cada tipo
$muestraValencia = Producto::where('estado_id', 1)->where('producto_valencia', 1)->limit(5)->get(['id', 'nombre', 'producto_valencia']);
$muestraLocal = Producto::where('estado_id', 1)->where('producto_valencia', 0)->limit(5)->get(['id', 'nombre', 'producto_valencia']);

echo "PRODUCTOS VALENCIA (producto_valencia=1):\n";
foreach ($muestraValencia as $producto) {
    echo "  ID: {$producto->id} | Nombre: {$producto->nombre} | Valencia: {$producto->producto_valencia}\n";
}

echo "\nPRODUCTOS LOCALES (producto_valencia=0):\n";
foreach ($muestraLocal as $producto) {
    echo "  ID: {$producto->id} | Nombre: {$producto->nombre} | Valencia: {$producto->producto_valencia}\n";
}

echo "\n=== FIN DEBUG ===\n";