-- =============================================
-- Script: Migrar precio_id en factura_has_producto
-- Descripción: Actualiza los registros de factura_has_producto que no tienen precio_id
--              asignándoles el precio_id correcto basándose en producto_id y unidad_medida_id
-- Fecha: 2025-12-04
-- =============================================

-- Verificar cantidad de registros sin precio_id
SELECT 
    COUNT(*) as registros_sin_precio_id
FROM factura_has_producto
WHERE precio_id IS NULL;

-- Ver muestra de registros que se van a actualizar
SELECT 
    fhp.id,
    fhp.factura_id,
    fhp.producto_id,
    p.nombre as producto_nombre,
    fhp.unidad_medida_id,
    um.nombre as unidad_medida,
    fhp.precio_id as precio_id_actual
FROM factura_has_producto fhp
LEFT JOIN producto p ON fhp.producto_id = p.id
LEFT JOIN unidad_medida um ON fhp.unidad_medida_id = um.id
WHERE fhp.precio_id IS NULL
LIMIT 20;

-- Actualizar registros sin precio_id
-- Busca el precio_id correspondiente en precio_has_venta usando producto_id y unidad_medida_id
UPDATE factura_has_producto fhp
INNER JOIN precio_has_venta phv ON (
    phv.producto_id = fhp.producto_id 
    AND phv.unidad_medida_id = fhp.unidad_medida_id
)
SET fhp.precio_id = phv.id
WHERE fhp.precio_id IS NULL;

-- Verificar resultados después de la actualización
SELECT 
    COUNT(*) as registros_actualizados
FROM factura_has_producto
WHERE precio_id IS NOT NULL;

SELECT 
    COUNT(*) as registros_sin_precio_id
FROM factura_has_producto
WHERE precio_id IS NULL;

-- Ver registros que NO se pudieron actualizar (si los hay)
-- Estos son casos donde no existe una coincidencia en precio_has_venta
SELECT 
    fhp.id,
    fhp.factura_id,
    fhp.producto_id,
    p.nombre as producto_nombre,
    fhp.unidad_medida_id,
    um.nombre as unidad_medida,
    fhp.precio_id
FROM factura_has_producto fhp
LEFT JOIN producto p ON fhp.producto_id = p.id
LEFT JOIN unidad_medida um ON fhp.unidad_medida_id = um.id
WHERE fhp.precio_id IS NULL;

-- Si hay múltiples presentaciones con el mismo producto_id y unidad_medida_id,
-- este script muestra cuáles productos tienen esta situación:
SELECT 
    phv.producto_id,
    p.nombre as producto_nombre,
    phv.unidad_medida_id,
    um.nombre as unidad_medida,
    COUNT(*) as cantidad_presentaciones,
    GROUP_CONCAT(phv.id ORDER BY phv.id) as precio_ids,
    GROUP_CONCAT(phv.descripcion ORDER BY phv.id SEPARATOR ' | ') as descripciones,
    GROUP_CONCAT(phv.codigo_barra ORDER BY phv.id SEPARATOR ' | ') as codigos_barra
FROM precio_has_venta phv
INNER JOIN producto p ON phv.producto_id = p.id
INNER JOIN unidad_medida um ON phv.unidad_medida_id = um.id
GROUP BY phv.producto_id, phv.unidad_medida_id
HAVING COUNT(*) > 1
ORDER BY cantidad_presentaciones DESC;

-- =============================================
-- NOTAS IMPORTANTES:
-- =============================================
-- 1. Si un producto tiene múltiples presentaciones con la misma unidad_medida_id,
--    este script asignará UNA de ellas (la primera que encuentre MySQL).
--    
-- 2. Para casos con múltiples presentaciones, se recomienda revisar manualmente
--    y asignar el precio_id correcto basándose en el código de barras original
--    de la factura.
--
-- 3. Los registros que no tengan coincidencia en precio_has_venta quedarán
--    con precio_id NULL y deben ser revisados manualmente.
-- =============================================
