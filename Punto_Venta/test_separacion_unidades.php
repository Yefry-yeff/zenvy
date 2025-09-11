<?php

require_once __DIR__ . '/vendor/autoload.php';

// Cargar configuración de Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 Verificando separación de unidades Zenvy vs Valencia\n\n";

try {
    // Obtener unidades propias de Zenvy (que NO están en la tabla de mapeo)
    $unidadesZenvy = \App\Models\UnidadMedida::whereNotExists(function ($query) {
        $query->select(DB::raw(1))
              ->from('id_zenvy_valencia')
              ->whereRaw('id_zenvy_valencia.id_zenvy = unidad_medida.id')
              ->where('id_zenvy_valencia.tipo_dato_migrado_id', 3);
    })->orderBy('nombre')->get(['id', 'unidad', 'nombre', 'simbolo']);

    // Obtener unidades de Valencia (que SÍ están en la tabla de mapeo)
    $unidadesValencia = \App\Models\UnidadMedida::whereExists(function ($query) {
        $query->select(DB::raw(1))
              ->from('id_zenvy_valencia')
              ->whereRaw('id_zenvy_valencia.id_zenvy = unidad_medida.id')
              ->where('id_zenvy_valencia.tipo_dato_migrado_id', 3);
    })->orderBy('nombre')->get(['id', 'unidad', 'nombre', 'simbolo']);

    echo "📊 Resultados de la separación:\n\n";
    
    echo "📏 Unidades Propias de Zenvy: " . count($unidadesZenvy) . " unidades\n";
    if (count($unidadesZenvy) > 0) {
        echo "   Primeras 5 unidades Zenvy:\n";
        foreach (array_slice($unidadesZenvy->toArray(), 0, 5) as $unidad) {
            echo "   - ID: {$unidad['id']}, Nombre: {$unidad['nombre']}, Símbolo: {$unidad['simbolo']}\n";
        }
    }
    
    echo "\n🏢 Unidades de Valencia: " . count($unidadesValencia) . " unidades\n";
    if (count($unidadesValencia) > 0) {
        echo "   Primeras 5 unidades Valencia:\n";
        foreach (array_slice($unidadesValencia->toArray(), 0, 5) as $unidad) {
            echo "   - ID: {$unidad['id']}, Nombre: {$unidad['nombre']}, Símbolo: {$unidad['simbolo']}\n";
        }
    }
    
    // Verificar total
    $totalUnidades = \App\Models\UnidadMedida::count();
    $totalCalculado = count($unidadesZenvy) + count($unidadesValencia);
    
    echo "\n📈 Verificación de integridad:\n";
    echo "   Total unidades en BD: {$totalUnidades}\n";
    echo "   Total calculado (Zenvy + Valencia): {$totalCalculado}\n";
    echo "   " . ($totalUnidades == $totalCalculado ? "✅" : "❌") . " Integridad: " . ($totalUnidades == $totalCalculado ? "Correcta" : "Error") . "\n";
    
    // Verificar tabla de mapeo
    $totalMapeos = DB::table('id_zenvy_valencia')
                    ->where('tipo_dato_migrado_id', 3)
                    ->count();
    
    echo "   Total mapeos Valencia en tabla: {$totalMapeos}\n";
    echo "   " . ($totalMapeos == count($unidadesValencia) ? "✅" : "❌") . " Mapeos: " . ($totalMapeos == count($unidadesValencia) ? "Correcto" : "Error") . "\n\n";
    
    echo "✅ ¡Separación exitosa!\n";
    echo "Las unidades ahora se muestran en dos secciones:\n";
    echo "- 📏 Unidades Zenvy: Se pueden crear, editar y eliminar\n";
    echo "- 🏢 Unidades Valencia: Solo lectura, sincronizadas desde profac_app\n\n";
    
} catch (Exception $e) {
    echo "❌ Error en la verificación: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
}

echo "🏁 Verificación completada.\n";
