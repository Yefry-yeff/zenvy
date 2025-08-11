<?php

require_once 'vendor/autoload.php';

// Configurar el entorno de Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\CAIService;

echo "=== PRUEBA DEL SISTEMA CAI ===\n\n";

try {
    $caiService = new CAIService();

    // 1. Verificar disponibilidad de CAI
    echo "1. Verificando disponibilidad de CAI...\n";
    $disponible = $caiService->verificarDisponibilidadCAI();
    echo "CAI disponible: " . ($disponible ? "SÍ" : "NO") . "\n\n";

    // 2. Obtener información de CAIs
    echo "2. Información de CAIs:\n";
    $informacion = $caiService->obtenerInformacionCAIs();
    foreach ($informacion as $cai) {
        echo "ID: {$cai->id} | Base: {$cai->numero_base} | Actual: {$cai->numero_actual} | Restantes: {$cai->cantidad_no_utilizada} | Estado: {$cai->estado}\n";
    }
    echo "\n";

    // 3. Desactivar CAIs vencidos
    echo "3. Desactivando CAIs vencidos...\n";
    $desactivados = $caiService->desactivarCAIsVencidos();
    echo "CAIs desactivados por vencimiento: $desactivados\n\n";

    // 4. Generar varios números de factura
    echo "4. Generando números de factura:\n";
    for ($i = 1; $i <= 5; $i++) {
        try {
            $resultado = $caiService->obtenerSiguienteNumeroFactura();
            echo "Factura $i:\n";
            echo "  Número: {$resultado['numero_factura']}\n";
            echo "  CAI ID: {$resultado['cai_id']}\n";
            echo "  CAI: {$resultado['cai']}\n";
            echo "  Secuencia: {$resultado['numero_secuencia']}\n";
            echo "  Restantes: {$resultado['cantidad_restante']}\n";
            echo "  Agotado: " . ($resultado['cai_agotado'] ? "SÍ" : "NO") . "\n";
            echo "  ---\n";

            if ($resultado['cai_agotado']) {
                echo "  ¡CAI AGOTADO! No se pueden generar más facturas con este CAI.\n";
                break;
            }
        } catch (Exception $e) {
            echo "  ERROR: " . $e->getMessage() . "\n";
            break;
        }
    }

    // 5. Estado final de CAIs
    echo "\n5. Estado final de CAIs:\n";
    $informacionFinal = $caiService->obtenerInformacionCAIs();
    foreach ($informacionFinal as $cai) {
        echo "ID: {$cai->id} | Base: {$cai->numero_base} | Actual: {$cai->numero_actual} | Restantes: {$cai->cantidad_no_utilizada} | Estado: {$cai->estado}\n";
    }

} catch (Exception $e) {
    echo "ERROR GENERAL: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
