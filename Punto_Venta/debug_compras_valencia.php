<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$valencia = DB::connection('profac_app');
$sql = "
SELECT  
    COALESCE(C.translado_id, A.compra_id) AS numero_factura,
    NULL AS fec_vecimiento,
    A.created_at AS fecha_emision,
    'fecha que se sincroniza' AS fecha_recepcion,
    NOW() AS created_at,
    NULL AS updated_at,
    1 AS estado_id,
    1 AS cliente_id,
    COALESCE(chp.precio_unidad, B.precio_unidad) AS precio,
    A.cantidad_inicial_seccion AS cantidad_ingresada,
    A.cantidad_disponible AS cantidad_sin_asignar,
    A.fecha_expiracion AS fecha_expiracion,
    (COALESCE(chp.precio_unidad, B.precio_unidad) * A.cantidad_inicial_seccion) AS sub_total_producto,
    ((COALESCE(chp.precio_unidad, B.precio_unidad) * A.cantidad_inicial_seccion) * (P.isv / 100.0)) AS isv,
    ((COALESCE(chp.precio_unidad, B.precio_unidad) * A.cantidad_inicial_seccion) + 
     ((COALESCE(chp.precio_unidad, B.precio_unidad) * A.cantidad_inicial_seccion) * (P.isv / 100.0))) AS precio_total,
    'El id_compra insertado en zenvy' AS compra_id,
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
  AND A.created_at > '2025-09-10'
";

echo "=== DEBUGGING COMPRAS VALENCIA ===" . PHP_EOL;
echo "Ejecutando query..." . PHP_EOL;

try {
    $resultados = $valencia->select($sql);
    echo 'Compras encontradas en Valencia: ' . count($resultados) . PHP_EOL . PHP_EOL;

    if (count($resultados) > 0) {
        echo 'Facturas únicas encontradas:' . PHP_EOL;
        $facturas = collect($resultados)->groupBy('numero_factura');
        foreach ($facturas as $numeroFactura => $productos) {
            echo "- Factura: {$numeroFactura} (productos: " . count($productos) . ")" . PHP_EOL;
        }
        echo PHP_EOL;

        echo 'Primeros 3 resultados detallados:' . PHP_EOL;
        foreach (array_slice($resultados, 0, 3) as $index => $resultado) {
            echo '--- Resultado ' . ($index + 1) . ' ---' . PHP_EOL;
            foreach ($resultado as $campo => $valor) {
                echo $campo . ': ' . $valor . PHP_EOL;
            }
            echo PHP_EOL;
        }
    } else {
        echo 'No se encontraron resultados.' . PHP_EOL;
        echo 'Verificando datos en recibido_bodega...' . PHP_EOL;
        
        $verificacion = $valencia->select("
            SELECT COUNT(*) as total, 
                   MIN(created_at) as fecha_min, 
                   MAX(created_at) as fecha_max,
                   COUNT(DISTINCT A.compra_id) as compras_distintas
            FROM recibido_bodega A 
            WHERE A.seccion_id = 433
        ");
        
        echo "Total registros en recibido_bodega con seccion_id=433: " . $verificacion[0]->total . PHP_EOL;
        echo "Fecha mínima: " . $verificacion[0]->fecha_min . PHP_EOL;
        echo "Fecha máxima: " . $verificacion[0]->fecha_max . PHP_EOL;
        echo "Compras distintas: " . $verificacion[0]->compras_distintas . PHP_EOL;
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== FIN DEBUG ===" . PHP_EOL;