<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$valencia = DB::connection('profac_app');

echo "=== VERIFICANDO FECHAS DISPONIBLES ===" . PHP_EOL;

try {
    // Obtener las fechas más recientes
    $fechasRecientes = $valencia->select("
        SELECT DATE(created_at) as fecha, COUNT(*) as registros
        FROM recibido_bodega A 
        WHERE A.seccion_id = 433
        GROUP BY DATE(created_at)
        ORDER BY fecha DESC
        LIMIT 10
    ");
    
    echo "Últimas 10 fechas con registros:" . PHP_EOL;
    foreach ($fechasRecientes as $fecha) {
        echo "- {$fecha->fecha}: {$fecha->registros} registros" . PHP_EOL;
    }
    
    echo PHP_EOL;
    
    // Probar con una fecha más antigua para ver las compras
    echo "Probando con fecha más antigua (2025-06-01)..." . PHP_EOL;
    
    $sql = "
    SELECT  
        COALESCE(C.translado_id, A.compra_id) AS numero_factura,
        A.created_at AS fecha_emision,
        A.producto_id AS producto_id_valencia,
        IFNULL(A.unidad_compra_id, 1) AS unidad_medida_id,
        CASE WHEN C.translado_id IS NOT NULL THEN 'TRASLADO' ELSE 'COMPRA' END AS tipo_origen
    FROM recibido_bodega A
    LEFT JOIN log_translado C ON C.destino = A.id   
    LEFT JOIN producto P ON P.id = A.producto_id
    LEFT JOIN compra_has_producto B ON B.compra_id = A.compra_id AND B.producto_id = A.producto_id
    LEFT JOIN (
        SELECT producto_id, precio_unidad
        FROM compra_has_producto chp1
        WHERE created_at = (
            SELECT MAX(created_at)
            FROM compra_has_producto chp2
            WHERE chp2.producto_id = chp1.producto_id
        )
    ) chp ON chp.producto_id = A.producto_id
    WHERE A.seccion_id = 433 
      AND A.created_at > '2025-06-01'
    LIMIT 10
    ";

    $resultados = $valencia->select($sql);
    echo 'Compras encontradas con fecha > 2025-06-01: ' . count($resultados) . PHP_EOL;

    if (count($resultados) > 0) {
        echo PHP_EOL . 'Facturas encontradas:' . PHP_EOL;
        $facturas = collect($resultados)->groupBy('numero_factura');
        foreach ($facturas as $numeroFactura => $productos) {
            $fecha = $productos->first()->fecha_emision;
            echo "- Factura: {$numeroFactura} (fecha: {$fecha}, productos: " . count($productos) . ")" . PHP_EOL;
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== FIN VERIFICACIÓN ===" . PHP_EOL;