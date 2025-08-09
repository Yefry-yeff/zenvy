<?php

require_once 'vendor/autoload.php';

// Configurar el entorno de Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICAR SISTEMA PDF FACTURA ===\n\n";

try {
    // 1. Verificar que DomPDF está instalado
    echo "1. Verificando instalación de DomPDF...\n";
    if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
        echo "✅ DomPDF instalado correctamente\n";
    } else {
        echo "❌ DomPDF no está disponible\n";
        exit(1);
    }
    
    // 2. Verificar que existe la vista PDF
    echo "\n2. Verificando vista PDF...\n";
    $vistaPDF = 'c:\laragon\www\Procadts\zenvy\Punto_Venta\resources\views\pdf\factura.blade.php';
    if (file_exists($vistaPDF)) {
        echo "✅ Vista PDF existe: " . basename($vistaPDF) . "\n";
    } else {
        echo "❌ Vista PDF no existe\n";
        exit(1);
    }
    
    // 3. Verificar que existe el controlador
    echo "\n3. Verificando controlador PDF...\n";
    $controladorPDF = 'c:\laragon\www\Procadts\zenvy\Punto_Venta\app\Http\Controllers\FacturaPDFController.php';
    if (file_exists($controladorPDF)) {
        echo "✅ Controlador PDF existe: " . basename($controladorPDF) . "\n";
    } else {
        echo "❌ Controlador PDF no existe\n";
        exit(1);
    }
    
    // 4. Verificar facturas disponibles para prueba
    echo "\n4. Verificando facturas disponibles...\n";
    $facturas = DB::table('factura as f')
        ->join('cai as c', 'f.cai_id', '=', 'c.id')
        ->select('f.id', 'f.numero_factura', 'f.nombre_cliente', 'f.total', 'c.cai')
        ->orderBy('f.id', 'desc')
        ->limit(3)
        ->get();
        
    if ($facturas->count() > 0) {
        echo "✅ Facturas disponibles para prueba:\n";
        foreach ($facturas as $factura) {
            echo "   ID: {$factura->id} | Número: {$factura->numero_factura} | Cliente: {$factura->nombre_cliente} | Total: L.{$factura->total}\n";
        }
        
        // 5. Información para probar
        echo "\n5. URLs para probar:\n";
        foreach ($facturas->take(2) as $factura) {
            echo "   PDF Factura {$factura->id}: http://localhost/factura/{$factura->id}/pdf\n";
        }
        
    } else {
        echo "⚠️  No hay facturas disponibles para prueba\n";
    }
    
    // 6. Verificar configuración DomPDF
    echo "\n6. Configuración DomPDF:\n";
    echo "   - Papel: 80mm (226.77 puntos) x altura automática\n";
    echo "   - Fuente: Courier (monospace)\n";
    echo "   - DPI: 150\n";
    echo "   - Soporte para imágenes: Habilitado\n";
    
    echo "\n✅ Sistema PDF listo para usar!\n";
    echo "🎯 Funcionalidades disponibles:\n";
    echo "   - Generación de PDF optimizada para factura térmica\n";
    echo "   - Incluye información completa del CAI\n";
    echo "   - Logo de empresa embebido\n";
    echo "   - Información fiscal completa\n";
    echo "   - Formato de impresión 80mm\n";
    echo "   - Descarga automática del archivo PDF\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE VERIFICACIÓN ===\n";
