<?php

require_once 'vendor/autoload.php';

// Configurar Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Isv;
use App\Livewire\Inventario\Isv as IsvComponent;
use Illuminate\Support\Facades\DB;

echo "🧪 Probando funcionalidad de ISV...\n\n";

try {
    // 1. Verificar conexión a la base de datos
    echo "1. ✅ Verificando conexión a la base de datos...\n";
    $connection = DB::connection();
    $pdo = $connection->getPdo();
    echo "   ✅ Conexión exitosa\n\n";

    // 2. Verificar tabla ISV
    echo "2. 🔍 Verificando tabla 'isv'...\n";
    $tables = DB::select("SHOW TABLES LIKE 'isv'");
    if (empty($tables)) {
        echo "   ❌ Tabla 'isv' no existe\n";
        exit;
    }
    echo "   ✅ Tabla 'isv' existe\n\n";

    // 3. Verificar estructura de la tabla
    echo "3. 📋 Estructura de la tabla 'isv':\n";
    $columns = DB::select("DESCRIBE isv");
    foreach ($columns as $column) {
        echo "   - {$column->Field}: {$column->Type}\n";
    }
    echo "\n";

    // 4. Verificar datos existentes
    echo "4. 📊 Datos actuales en la tabla 'isv':\n";
    $isvs = Isv::all();
    if ($isvs->count() > 0) {
        foreach ($isvs as $isv) {
            echo "   - ID: {$isv->id}, Cantidad: {$isv->cantidad}%\n";
        }
    } else {
        echo "   ⚠️ No hay datos en la tabla 'isv'\n";
        
        // Crear datos de prueba
        echo "\n5. 🔧 Creando datos de prueba...\n";
        $testIsvs = [
            ['cantidad' => 0.00],
            ['cantidad' => 15.00],
            ['cantidad' => 18.00]
        ];
        
        foreach ($testIsvs as $testIsv) {
            $newIsv = Isv::create($testIsv);
            echo "   ✅ Creado ISV: {$newIsv->cantidad}%\n";
        }
    }
    echo "\n";

    // 5. Verificar modelo Isv
    echo "6. 🏗️ Verificando modelo Isv...\n";
    $testIsv = new Isv();
    $fillable = $testIsv->getFillable();
    echo "   ✅ Campos fillable: " . implode(', ', $fillable) . "\n";
    
    // Verificar relación con productos
    echo "   🔗 Verificando relación con productos...\n";
    $firstIsv = Isv::first();
    if ($firstIsv) {
        $productos = $firstIsv->productos();
        echo "   ✅ Relación con productos configurada\n";
    }
    echo "\n";

    // 6. Verificar componente Livewire
    echo "7. ⚡ Verificando componente Livewire...\n";
    if (class_exists('App\Livewire\Inventario\Isv')) {
        echo "   ✅ Clase del componente existe\n";
        
        // Simular montaje del componente
        $component = new IsvComponent();
        $component->mount();
        echo "   ✅ Componente se monta correctamente\n";
        echo "   📊 ISVs cargados: " . count($component->isvs) . "\n";
    } else {
        echo "   ❌ Clase del componente no encontrada\n";
    }

    echo "\n🎉 ¡Todas las verificaciones completadas exitosamente!\n";
    echo "La gestión de ISV está lista para usar desde el sidebar.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
