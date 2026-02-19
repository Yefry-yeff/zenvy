-- ================================================================
-- SCRIPT: Completar Compras de Valencia con Productos Faltantes
-- Descripción: Agrega productos faltantes a compras sincronizadas
-- Fecha: 2026-02-17
-- ================================================================

-- PARÁMETROS (Modificar estas fechas según necesidad)
SET @fecha_inicio = '2025-09-10';
SET @fecha_fin = '2026-02-17';

-- ================================================================
-- PASO 1: Identificar productos faltantes en compras
-- ================================================================

DROP TEMPORARY TABLE IF EXISTS temp_productos_faltantes;
CREATE TEMPORARY TABLE temp_productos_faltantes AS
SELECT 
    c.id AS compra_id_zenvy,
    c.numero_factura,
    rb.producto_id AS producto_id_valencia,
    pv.id_zenvy AS producto_id_zenvy,
    COALESCE(chp_v.precio_unidad, B.precio_unidad) AS precio,
    rb.cantidad_inicial_seccion AS cantidad_ingresada,
    rb.cantidad_disponible AS cantidad_sin_asignar,
    rb.fecha_expiracion,
    (COALESCE(chp_v.precio_unidad, B.precio_unidad) * rb.cantidad_inicial_seccion) AS sub_total_producto,
    ((COALESCE(chp_v.precio_unidad, B.precio_unidad) * rb.cantidad_inicial_seccion) * (P.isv / 100.0)) AS isv,
    ((COALESCE(chp_v.precio_unidad, B.precio_unidad) * rb.cantidad_inicial_seccion) +
     ((COALESCE(chp_v.precio_unidad, B.precio_unidad) * rb.cantidad_inicial_seccion) * (P.isv / 100.0))) AS precio_total,
    IFNULL(rb.unidad_compra_id, 1) AS unidad_medida_id_valencia,
    um.id_zenvy AS unidad_medida_id_zenvy
FROM 
    profac_app.recibido_bodega rb
    INNER JOIN profac_app.producto P ON P.id = rb.producto_id
    LEFT JOIN profac_app.log_translado lt ON lt.destino = rb.id
    LEFT JOIN profac_app.compra_has_producto B ON B.compra_id = rb.compra_id AND B.producto_id = rb.producto_id
    LEFT JOIN (
        SELECT producto_id, precio_unidad
        FROM profac_app.compra_has_producto chp1
        WHERE created_at = (
            SELECT MAX(created_at)
            FROM profac_app.compra_has_producto chp2
            WHERE chp2.producto_id = chp1.producto_id
        )
    ) chp_v ON chp_v.producto_id = rb.producto_id
    -- Mapeo de producto
    INNER JOIN id_zenvy_valencia pv ON pv.id_valencia = rb.producto_id AND pv.tipo_dato_migrado_id = 1
    -- Mapeo de unidad de medida
    INNER JOIN id_zenvy_valencia um ON um.id_valencia = IFNULL(rb.unidad_compra_id, 1) AND um.tipo_dato_migrado_id = 5
    -- Compra en Zenvy
    INNER JOIN compra c ON c.numero_factura = COALESCE(lt.translado_id, rb.compra_id)
    -- Verificar que el producto NO existe en la compra de Zenvy
    LEFT JOIN compra_has_producto chp_zenvy ON chp_zenvy.compra_id = c.id AND chp_zenvy.producto_id = pv.id_zenvy
WHERE 
    rb.seccion_id = 433
    AND DATE(rb.created_at) BETWEEN @fecha_inicio AND @fecha_fin
    AND chp_zenvy.id IS NULL  -- Solo productos que NO están en la compra
    AND pv.id_zenvy IS NOT NULL  -- Solo productos que existen en Zenvy
    AND um.id_zenvy IS NOT NULL  -- Solo si la unidad existe en Zenvy
    AND COALESCE(chp_v.precio_unidad, B.precio_unidad) IS NOT NULL  -- Debe tener precio
;

-- ================================================================
-- PASO 2: Mostrar resumen de lo que se agregará
-- ================================================================

SELECT 
    '=== RESUMEN DE PRODUCTOS A AGREGAR ===' AS titulo;

SELECT 
    COUNT(DISTINCT compra_id_zenvy) AS compras_a_completar,
    COUNT(*) AS total_productos_a_agregar,
    SUM(precio_total) AS valor_total_agregado
FROM temp_productos_faltantes;

SELECT 
    '=== DETALLE POR COMPRA ===' AS titulo;

SELECT 
    numero_factura AS 'Compra #',
    COUNT(*) AS 'Productos a agregar',
    ROUND(SUM(precio_total), 2) AS 'Valor total'
FROM temp_productos_faltantes
GROUP BY numero_factura
ORDER BY numero_factura;

-- ================================================================
-- PASO 3: Insertar productos faltantes (DESCOMENTE PARA EJECUTAR)
-- ================================================================

-- PRECAUCIÓN: Verifique el resumen antes de ejecutar este INSERT
-- Descomente las siguientes líneas para ejecutar la inserción:

/*
INSERT INTO compra_has_producto (
    compra_id,
    producto_id,
    precio,
    cantidad_ingresada,
    cantidad_sin_asignar,
    fecha_expiracion,
    sub_total_producto,
    isv,
    precio_total,
    unidad_medida_id,
    created_at,
    updated_at
)
SELECT 
    compra_id_zenvy,
    producto_id_zenvy,
    precio,
    cantidad_ingresada,
    cantidad_sin_asignar,
    fecha_expiracion,
    sub_total_producto,
    isv,
    precio_total,
    unidad_medida_id_zenvy,
    NOW(),
    NOW()
FROM temp_productos_faltantes;

-- Registrar en bitácora
INSERT INTO bitacora (
    tablaReferencia,
    accion,
    idReferencia,
    datosAnteriores,
    datosNuevos,
    users_id,
    created_at,
    updated_at
)
SELECT 
    'compra_has_producto' AS tablaReferencia,
    'COMPLETAR_COMPRA_VALENCIA' AS accion,
    compra_id_zenvy AS idReferencia,
    NULL AS datosAnteriores,
    JSON_OBJECT(
        'numero_factura', numero_factura,
        'productos_agregados', COUNT(*),
        'fecha_inicio', @fecha_inicio,
        'fecha_fin', @fecha_fin
    ) AS datosNuevos,
    1 AS users_id,
    NOW() AS created_at,
    NULL AS updated_at
FROM temp_productos_faltantes
GROUP BY compra_id_zenvy, numero_factura;

SELECT 
    ROW_COUNT() AS productos_insertados,
    '✓ Compras completadas exitosamente' AS mensaje;
*/

-- ================================================================
-- PASO 4: Verificar productos que NO se pueden agregar (falta mapeo)
-- ================================================================

SELECT 
    '=== PRODUCTOS QUE NO SE PUEDEN AGREGAR (FALTAN MAPEOS) ===' AS titulo;

SELECT 
    COALESCE(lt.translado_id, rb.compra_id) AS numero_factura,
    rb.producto_id AS producto_id_valencia,
    P.nombre AS nombre_producto,
    CASE 
        WHEN pv.id_zenvy IS NULL THEN 'Producto no mapeado'
        WHEN um.id_zenvy IS NULL THEN 'Unidad de medida no mapeada'
        ELSE 'Error desconocido'
    END AS razon
FROM 
    profac_app.recibido_bodega rb
    LEFT JOIN profac_app.log_translado lt ON lt.destino = rb.id
    LEFT JOIN profac_app.producto P ON P.id = rb.producto_id
    LEFT JOIN id_zenvy_valencia pv ON pv.id_valencia = rb.producto_id AND pv.tipo_dato_migrado_id = 1
    LEFT JOIN id_zenvy_valencia um ON um.id_valencia = IFNULL(rb.unidad_compra_id, 1) AND um.tipo_dato_migrado_id = 5
WHERE 
    rb.seccion_id = 433
    AND DATE(rb.created_at) BETWEEN @fecha_inicio AND @fecha_fin
    AND (pv.id_zenvy IS NULL OR um.id_zenvy IS NULL)
ORDER BY numero_factura, rb.producto_id;

-- ================================================================
-- PASO 5: Limpieza
-- ================================================================

DROP TEMPORARY TABLE IF EXISTS temp_productos_faltantes;

SELECT 
    '=== SCRIPT COMPLETADO ===' AS mensaje,
    'Para ejecutar la inserción, descomente la sección PASO 3' AS nota;
