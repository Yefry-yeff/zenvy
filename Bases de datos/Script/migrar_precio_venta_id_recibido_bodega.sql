-- =============================================
-- Script: Migrar precio_venta_id en recibido_bodega
-- Descripción: Actualiza los registros de recibido_bodega que no tienen precio_venta_id
--              asignándoles el precio_venta_id correcto basándose en producto_id y unidad_medida_id
-- Problema: Los productos aparecen en lista de productos pero no están disponibles para venta
-- Fecha: 2025-12-04
-- =============================================

-- Verificar cantidad de registros sin precio_venta_id
SELECT 
    COUNT(*) as registros_sin_precio_venta_id,
    SUM(rb.cantidad) as cantidad_total_stock
FROM recibido_bodega rb
WHERE rb.precio_venta_id IS NULL
  AND rb.cantidad > 0;

-- Ver muestra de registros que se van a actualizar
SELECT 
    rb.id,
    rb.producto_id,
    p.nombre as producto_nombre,
    rb.unidad_medida_id,
    um.nombre as unidad_medida,
    rb.cantidad as stock,
    rb.precio_venta_id as precio_venta_id_actual,
    rb.codigo_barra as codigo_barra_recibido
FROM recibido_bodega rb
LEFT JOIN producto p ON rb.producto_id = p.id
LEFT JOIN unidad_medida um ON rb.unidad_medida_id = um.id
WHERE rb.precio_venta_id IS NULL
  AND rb.cantidad > 0
ORDER BY rb.id DESC
LIMIT 20;

-- Verificar si hay coincidencias en precio_has_venta
SELECT 
    rb.id as recibido_bodega_id,
    rb.producto_id,
    p.nombre as producto_nombre,
    rb.unidad_medida_id,
    um.nombre as unidad_medida,
    rb.cantidad as stock,
    phv.id as precio_venta_id_encontrado,
    phv.descripcion,
    phv.codigo_barra as codigo_barra_precio_venta
FROM recibido_bodega rb
INNER JOIN producto p ON rb.producto_id = p.id
INNER JOIN unidad_medida um ON rb.unidad_medida_id = um.id
INNER JOIN precio_has_venta phv ON (
    phv.producto_id = rb.producto_id 
    AND phv.unidad_medida_id = rb.unidad_medida_id
)
WHERE rb.precio_venta_id IS NULL
  AND rb.cantidad > 0
LIMIT 20;

-- =============================================
-- MIGRACIÓN AUTOMÁTICA
-- =============================================
-- Actualizar registros sin precio_venta_id
-- Busca el precio_venta_id correspondiente usando producto_id y unidad_medida_id
UPDATE recibido_bodega rb
INNER JOIN precio_has_venta phv ON (
    phv.producto_id = rb.producto_id 
    AND phv.unidad_medida_id = rb.unidad_medida_id
)
SET rb.precio_venta_id = phv.id
WHERE rb.precio_venta_id IS NULL;

-- =============================================
-- VERIFICACIÓN POST-MIGRACIÓN
-- =============================================

-- Verificar resultados después de la actualización
SELECT 
    COUNT(*) as registros_con_precio_venta_id,
    SUM(rb.cantidad) as stock_total
FROM recibido_bodega rb
WHERE rb.precio_venta_id IS NOT NULL
  AND rb.cantidad > 0;

SELECT 
    COUNT(*) as registros_sin_precio_venta_id,
    SUM(COALESCE(rb.cantidad, 0)) as stock_sin_precio
FROM recibido_bodega rb
WHERE rb.precio_venta_id IS NULL
  AND rb.cantidad > 0;

-- Ver registros que NO se pudieron actualizar (si los hay)
SELECT 
    rb.id,
    rb.producto_id,
    p.nombre as producto_nombre,
    rb.unidad_medida_id,
    um.nombre as unidad_medida,
    rb.cantidad as stock,
    rb.precio_venta_id,
    rb.codigo_barra
FROM recibido_bodega rb
LEFT JOIN producto p ON rb.producto_id = p.id
LEFT JOIN unidad_medida um ON rb.unidad_medida_id = um.id
WHERE rb.precio_venta_id IS NULL
  AND rb.cantidad > 0;

-- Verificar si hay productos con múltiples presentaciones
SELECT 
    phv.producto_id,
    p.nombre as producto_nombre,
    phv.unidad_medida_id,
    um.nombre as unidad_medida,
    COUNT(*) as cantidad_presentaciones,
    GROUP_CONCAT(phv.id ORDER BY phv.id) as precio_venta_ids,
    GROUP_CONCAT(phv.descripcion ORDER BY phv.id SEPARATOR ' | ') as descripciones,
    GROUP_CONCAT(phv.codigo_barra ORDER BY phv.id SEPARATOR ' | ') as codigos_barra
FROM precio_has_venta phv
INNER JOIN producto p ON phv.producto_id = p.id
INNER JOIN unidad_medida um ON phv.unidad_medida_id = um.id
WHERE EXISTS (
    SELECT 1 FROM recibido_bodega rb 
    WHERE rb.producto_id = phv.producto_id 
    AND rb.unidad_medida_id = phv.unidad_medida_id
    AND rb.cantidad > 0
)
GROUP BY phv.producto_id, phv.unidad_medida_id
HAVING COUNT(*) > 1
ORDER BY cantidad_presentaciones DESC;

-- =============================================
-- VALIDACIÓN FINAL: Stock disponible para venta
-- =============================================
-- Esta consulta muestra cómo queda el stock después de la migración
SELECT 
    p.id as producto_id,
    p.nombre as producto_nombre,
    phv.id as precio_venta_id,
    phv.descripcion as presentacion,
    phv.codigo_barra,
    um.nombre as unidad_medida,
    SUM(rb.cantidad) as stock_total,
    COUNT(rb.id) as cantidad_recepciones
FROM recibido_bodega rb
INNER JOIN producto p ON rb.producto_id = p.id
INNER JOIN precio_has_venta phv ON rb.precio_venta_id = phv.id
INNER JOIN unidad_medida um ON phv.unidad_medida_id = um.id
WHERE rb.cantidad > 0
GROUP BY p.id, phv.id, um.id
ORDER BY p.nombre, phv.descripcion;

-- =============================================
-- NOTAS IMPORTANTES:
-- =============================================
-- 1. Si un producto tiene múltiples presentaciones con la misma unidad_medida_id,
--    este script asignará UNA de ellas (la que MySQL encuentre primero).
--    
-- 2. Para casos con múltiples presentaciones, se recomienda:
--    - Revisar el código de barras original de la recepción
--    - Asignar manualmente el precio_venta_id correcto
--
-- 3. Los registros que no tengan coincidencia en precio_has_venta quedarán
--    con precio_venta_id NULL y deben ser revisados.
--
-- 4. Después de esta migración, el stock debería aparecer correctamente
--    en las ventas porque ahora está vinculado al precio_venta_id específico.
-- =============================================
