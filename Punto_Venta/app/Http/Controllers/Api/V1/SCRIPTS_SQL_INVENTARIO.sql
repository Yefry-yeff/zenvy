-- =====================================================
-- SCRIPTS SQL PARA INVENTARIO - ZENVY API
-- Ubicación: app/Http/Controllers/Api/V1/
-- Fecha: 2026-02-03
-- =====================================================

-- =====================================================
-- 1. QUERY PRINCIPAL: Productos por Categoría con Unidad de Medida
-- Este query agrupa productos por código de barras + unidad de medida
-- Si un producto tiene la misma unidad y código en diferentes lotes, suma el stock
-- Endpoint: GET /api/v1/inventory/by-category
-- =====================================================

SELECT 
    p.id as producto_id,
    p.nombre as producto_nombre,
    phv.codigo_barra,
    phv.id as precio_venta_id,
    phv.descripcion as presentacion,
    phv.cantidad as cantidad_por_unidad,
    phv.precio,
    um.id as unidad_medida_id,
    um.nombre as unidad_nombre,
    seg.id as categoria_id,
    seg.descripcion as categoria_nombre,
    SUM(rb.cantidad_disponible) as stock_disponible
FROM recibido_bodega as rb
INNER JOIN producto as p ON rb.producto_id = p.id
INNER JOIN precio_has_venta as phv ON rb.precio_venta_id = phv.id
INNER JOIN unidad_medida as um ON phv.unidad_medida_id = um.id
INNER JOIN seccion as sec ON rb.seccion_id = sec.id
INNER JOIN segmento as seg ON sec.segmento_id = seg.id
WHERE rb.estado_id = 1
  AND p.estado_id = 1
  AND phv.estado_id = 1
GROUP BY 
    p.id, p.nombre,
    phv.codigo_barra, phv.id, phv.descripcion, phv.cantidad, phv.precio,
    um.id, um.nombre,
    seg.id, seg.descripcion
HAVING SUM(rb.cantidad_disponible) > 0
ORDER BY seg.descripcion, p.nombre, phv.cantidad;


-- =====================================================
-- 2. QUERY CON TOTALES: Resumen por Categoría
-- Incluye contadores y totales de stock por categoría
-- =====================================================

SELECT 
    seg.id as categoria_id,
    seg.descripcion as categoria_nombre,
    COUNT(DISTINCT CONCAT(p.id, '-', phv.codigo_barra, '-', um.id)) as total_productos,
    SUM(rb.cantidad_disponible) as stock_total
FROM recibido_bodega as rb
INNER JOIN producto as p ON rb.producto_id = p.id
INNER JOIN precio_has_venta as phv ON rb.precio_venta_id = phv.id
INNER JOIN unidad_medida as um ON phv.unidad_medida_id = um.id
INNER JOIN seccion as sec ON rb.seccion_id = sec.id
INNER JOIN segmento as seg ON sec.segmento_id = seg.id
WHERE rb.estado_id = 1
  AND p.estado_id = 1
  AND phv.estado_id = 1
  AND rb.cantidad_disponible > 0
GROUP BY seg.id, seg.descripcion
ORDER BY seg.descripcion;


-- =====================================================
-- 3. QUERY SOLO CATEGORÍAS: Con Productos Disponibles
-- Endpoint: GET /api/v1/inventory/categories
-- =====================================================

SELECT 
    seg.id as categoria_id,
    seg.descripcion as categoria_nombre,
    COUNT(DISTINCT CONCAT(p.id, '-', phv.codigo_barra, '-', um.id)) as total_productos,
    COALESCE(SUM(rb.cantidad_disponible), 0) as stock_total
FROM segmento as seg
INNER JOIN seccion as sec ON seg.id = sec.segmento_id
INNER JOIN recibido_bodega as rb ON sec.id = rb.seccion_id
INNER JOIN producto as p ON rb.producto_id = p.id
INNER JOIN precio_has_venta as phv ON rb.precio_venta_id = phv.id
INNER JOIN unidad_medida as um ON phv.unidad_medida_id = um.id
WHERE rb.estado_id = 1
  AND p.estado_id = 1
  AND phv.estado_id = 1
  AND rb.cantidad_disponible > 0
GROUP BY seg.id, seg.descripcion
HAVING COALESCE(SUM(rb.cantidad_disponible), 0) > 0
ORDER BY seg.descripcion;


-- =====================================================
-- 4. VERIFICACIÓN: Ver Stock por Producto con Presentaciones
-- Útil para debugging y auditoría
-- =====================================================

SELECT 
    p.id as producto_id,
    p.nombre as producto_nombre,
    phv.codigo_barra,
    phv.descripcion as presentacion,
    phv.cantidad as cantidad_por_unidad,
    um.nombre as unidad,
    rb.id as lote_id,
    rb.cantidad_disponible,
    rb.fecha_vencimiento,
    sec.nombre as seccion,
    seg.descripcion as categoria
FROM producto as p
INNER JOIN recibido_bodega as rb ON p.id = rb.producto_id
INNER JOIN precio_has_venta as phv ON rb.precio_venta_id = phv.id
INNER JOIN unidad_medida as um ON phv.unidad_medida_id = um.id
INNER JOIN seccion as sec ON rb.seccion_id = sec.id
INNER JOIN segmento as seg ON sec.segmento_id = seg.id
WHERE p.estado_id = 1
  AND rb.estado_id = 1
  AND phv.estado_id = 1
  AND rb.cantidad_disponible > 0
ORDER BY p.nombre, phv.cantidad, rb.fecha_vencimiento;


-- =====================================================
-- 5. BÚSQUEDA: Producto Específico por Código de Barras
-- =====================================================

SELECT 
    p.id as producto_id,
    p.nombre as producto_nombre,
    phv.codigo_barra,
    phv.descripcion as presentacion,
    phv.cantidad as cantidad_por_unidad,
    um.nombre as unidad,
    seg.descripcion as categoria,
    SUM(rb.cantidad_disponible) as stock_total,
    phv.precio as precio_venta
FROM producto as p
INNER JOIN recibido_bodega as rb ON p.id = rb.producto_id
INNER JOIN precio_has_venta as phv ON rb.precio_venta_id = phv.id
INNER JOIN unidad_medida as um ON phv.unidad_medida_id = um.id
INNER JOIN seccion as sec ON rb.seccion_id = sec.id
INNER JOIN segmento as seg ON sec.segmento_id = seg.id
WHERE phv.codigo_barra = '7501234567890'  -- Cambiar por el código que necesites
  AND rb.estado_id = 1
  AND p.estado_id = 1
  AND phv.estado_id = 1
GROUP BY 
    p.id, p.nombre, phv.codigo_barra, phv.descripcion, 
    phv.cantidad, um.nombre, seg.descripcion, phv.precio;


-- =====================================================
-- 6. BÚSQUEDA: Todas las Presentaciones de un Producto
-- Ver todas las unidades de medida disponibles para un producto
-- =====================================================

SELECT 
    p.id as producto_id,
    p.nombre as producto_nombre,
    phv.id as precio_venta_id,
    phv.codigo_barra,
    phv.descripcion as presentacion,
    phv.cantidad as cantidad_por_unidad,
    um.id as unidad_medida_id,
    um.nombre as unidad_nombre,
    phv.precio,
    SUM(rb.cantidad_disponible) as stock_disponible
FROM producto as p
INNER JOIN precio_has_venta as phv ON p.id = phv.producto_id
INNER JOIN unidad_medida as um ON phv.unidad_medida_id = um.id
LEFT JOIN recibido_bodega as rb ON phv.id = rb.precio_venta_id AND rb.estado_id = 1
WHERE p.id = 123  -- Cambiar por el ID del producto
  AND p.estado_id = 1
  AND phv.estado_id = 1
GROUP BY 
    p.id, p.nombre, phv.id, phv.codigo_barra, 
    phv.descripcion, phv.cantidad, um.id, um.nombre, phv.precio
ORDER BY phv.cantidad;


-- =====================================================
-- 7. RESUMEN GENERAL: Estadísticas del Inventario
-- =====================================================

SELECT 
    COUNT(DISTINCT seg.id) as total_categorias,
    COUNT(DISTINCT CONCAT(p.id, '-', phv.codigo_barra, '-', um.id)) as total_presentaciones,
    COUNT(DISTINCT p.id) as total_productos_unicos,
    SUM(rb.cantidad_disponible) as stock_total_unidades,
    SUM(rb.cantidad_disponible * phv.precio) as valor_inventario_estimado
FROM recibido_bodega as rb
INNER JOIN producto as p ON rb.producto_id = p.id
INNER JOIN precio_has_venta as phv ON rb.precio_venta_id = phv.id
INNER JOIN unidad_medida as um ON phv.unidad_medida_id = um.id
INNER JOIN seccion as sec ON rb.seccion_id = sec.id
INNER JOIN segmento as seg ON sec.segmento_id = seg.id
WHERE rb.estado_id = 1
  AND p.estado_id = 1
  AND phv.estado_id = 1
  AND rb.cantidad_disponible > 0;


-- =====================================================
-- 8. AUDITORÍA: Productos sin Stock
-- No aparecen en la API pero existen en la base de datos
-- =====================================================

SELECT 
    p.id,
    p.nombre,
    phv.codigo_barra,
    phv.descripcion as presentacion,
    um.nombre as unidad,
    seg.descripcion as categoria,
    COALESCE(SUM(rb.cantidad_disponible), 0) as stock
FROM producto as p
INNER JOIN precio_has_venta as phv ON p.id = phv.producto_id
INNER JOIN unidad_medida as um ON phv.unidad_medida_id = um.id
LEFT JOIN recibido_bodega as rb ON phv.id = rb.precio_venta_id AND rb.estado_id = 1
LEFT JOIN seccion as sec ON rb.seccion_id = sec.id
LEFT JOIN segmento as seg ON sec.segmento_id = seg.id
WHERE p.estado_id = 1
  AND phv.estado_id = 1
GROUP BY p.id, p.nombre, phv.codigo_barra, phv.descripcion, um.nombre, seg.descripcion
HAVING COALESCE(SUM(rb.cantidad_disponible), 0) = 0
ORDER BY p.nombre;


-- =====================================================
-- 9. ANÁLISIS: Stock por Lote y Vencimiento
-- Ver detalles de lotes próximos a vencer
-- =====================================================

SELECT 
    p.id as producto_id,
    p.nombre as producto_nombre,
    phv.codigo_barra,
    phv.descripcion as presentacion,
    rb.id as lote_id,
    rb.cantidad_disponible,
    rb.fecha_vencimiento,
    DATEDIFF(rb.fecha_vencimiento, CURDATE()) as dias_para_vencer,
    CASE 
        WHEN DATEDIFF(rb.fecha_vencimiento, CURDATE()) < 0 THEN 'VENCIDO'
        WHEN DATEDIFF(rb.fecha_vencimiento, CURDATE()) <= 30 THEN 'PRÓXIMO A VENCER'
        WHEN DATEDIFF(rb.fecha_vencimiento, CURDATE()) <= 90 THEN 'ALERTA'
        ELSE 'NORMAL'
    END as estado_vencimiento,
    sec.nombre as seccion
FROM recibido_bodega as rb
INNER JOIN producto as p ON rb.producto_id = p.id
INNER JOIN precio_has_venta as phv ON rb.precio_venta_id = phv.id
INNER JOIN seccion as sec ON rb.seccion_id = sec.id
WHERE rb.estado_id = 1
  AND p.estado_id = 1
  AND rb.cantidad_disponible > 0
ORDER BY rb.fecha_vencimiento ASC, p.nombre;


-- =====================================================
-- 10. EJEMPLO: Mismo Producto, Diferentes Presentaciones
-- Ver cómo se agrupan las presentaciones de un mismo producto
-- =====================================================

SELECT 
    p.id,
    p.nombre,
    phv.codigo_barra,
    phv.descripcion as presentacion,
    phv.cantidad as cantidad_por_unidad,
    um.nombre as unidad,
    COUNT(rb.id) as total_lotes,
    SUM(rb.cantidad_disponible) as stock_sumado,
    phv.precio,
    (SUM(rb.cantidad_disponible) * phv.precio) as valor_total
FROM producto as p
INNER JOIN precio_has_venta as phv ON p.id = phv.producto_id
INNER JOIN unidad_medida as um ON phv.unidad_medida_id = um.id
INNER JOIN recibido_bodega as rb ON phv.id = rb.precio_venta_id
WHERE p.estado_id = 1
  AND phv.estado_id = 1
  AND rb.estado_id = 1
  AND rb.cantidad_disponible > 0
GROUP BY 
    p.id, p.nombre, phv.codigo_barra, phv.descripcion, 
    phv.cantidad, um.nombre, phv.precio
ORDER BY p.nombre, phv.cantidad;


-- =====================================================
-- NOTAS IMPORTANTES:
-- =====================================================
-- 1. Los productos se agrupan por código de barras + unidad de medida
-- 2. Si un producto tiene la misma presentación en diferentes lotes, el stock se suma
-- 3. Ejemplo: 2 lotes de "Caja x 12" = 4 cajas en stock total (2+2=4)
-- 4. Cada presentación (unidad de medida) tiene su propio código de barras
-- 5. Solo se muestran productos con stock > 0 y estado activo
-- =====================================================
