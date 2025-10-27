-- ============================================
-- MIGRACIÓN MASIVA DE PRECIOS A precio_has_venta
-- ============================================
-- Descripción: Migra los precios base de todos los productos activos
--              a la tabla precio_has_venta con unidad de medida base (Unidad)
-- 
-- IMPORTANTE: 
-- 1. Hacer BACKUP de la base de datos antes de ejecutar
-- 2. Revisar los resultados con las consultas de verificación
-- 3. Este script es SEGURO - no sobrescribe precios existentes
-- ============================================

-- ============================================
-- PASO 1: VERIFICACIÓN PREVIA
-- ============================================

-- Ver cuántos productos serían migrados
SELECT 
    COUNT(*) AS total_productos_a_migrar,
    SUM(precio_base) AS suma_total_precios
FROM producto p
WHERE p.estado_id = 1
  AND p.precio_base IS NOT NULL
  AND p.precio_base > 0
  AND NOT EXISTS (
      SELECT 1 
      FROM precio_has_venta phv 
      WHERE phv.producto_id = p.id 
        AND phv.estado_id = 1
  );

-- Ver detalle de productos a migrar (primeros 20)
SELECT 
    p.id,
    p.nombre,
    p.codigo_barra,
    p.precio_base,
    'No tiene precio_has_venta' AS estado
FROM producto p
WHERE p.estado_id = 1
  AND p.precio_base IS NOT NULL
  AND p.precio_base > 0
  AND NOT EXISTS (
      SELECT 1 
      FROM precio_has_venta phv 
      WHERE phv.producto_id = p.id 
        AND phv.estado_id = 1
  )
LIMIT 20;

-- Verificar que existe la unidad de medida base
SELECT * FROM unidad_medida WHERE id = 1;

-- Si no existe, crear la unidad base (descomentar si es necesario)
-- INSERT INTO unidad_medida (id, nombre, estado_id, created_at, updated_at) 
-- VALUES (1, 'Unidad', 1, NOW(), NOW());

-- ============================================
-- PASO 2: MIGRACIÓN (Opción A - Inserción Simple)
-- ============================================
-- Esta opción es segura: NO sobrescribe precios existentes

INSERT INTO precio_has_venta (
    producto_id,
    unidad_medida_id,
    cantidad,
    precio,
    users_id,
    estado_id,
    created_at,
    updated_at
)
SELECT 
    p.id AS producto_id,
    1 AS unidad_medida_id,  -- Unidad base
    1 AS cantidad,
    p.precio_base AS precio,
    1 AS users_id,  -- Usuario sistema
    1 AS estado_id,  -- Activo
    NOW() AS created_at,
    NOW() AS updated_at
FROM producto p
WHERE p.estado_id = 1
  AND p.precio_base IS NOT NULL
  AND p.precio_base > 0
  AND NOT EXISTS (
      SELECT 1 
      FROM precio_has_venta phv 
      WHERE phv.producto_id = p.id 
        AND phv.estado_id = 1
  );

-- Ver cuántos registros se insertaron
SELECT ROW_COUNT() AS registros_insertados;

-- ============================================
-- PASO 2 (ALTERNATIVA): MIGRACIÓN CON SOBRESCRITURA
-- ============================================
-- ⚠️ USAR SOLO SI NECESITAS RESETEAR PRECIOS EXISTENTES
-- Esta opción DESACTIVA precios anteriores y crea nuevos

/*
-- Desactivar precios existentes para productos que serán migrados
UPDATE precio_has_venta phv
INNER JOIN producto p ON phv.producto_id = p.id
SET phv.estado_id = 2,
    phv.updated_at = NOW()
WHERE p.estado_id = 1
  AND p.precio_base IS NOT NULL
  AND p.precio_base > 0
  AND phv.estado_id = 1;

-- Insertar nuevos precios (incluye productos que ya tenían precios)
INSERT INTO precio_has_venta (
    producto_id,
    unidad_medida_id,
    cantidad,
    precio,
    users_id,
    estado_id,
    created_at,
    updated_at
)
SELECT 
    p.id AS producto_id,
    1 AS unidad_medida_id,
    1 AS cantidad,
    p.precio_base AS precio,
    1 AS users_id,
    1 AS estado_id,
    NOW() AS created_at,
    NOW() AS updated_at
FROM producto p
WHERE p.estado_id = 1
  AND p.precio_base IS NOT NULL
  AND p.precio_base > 0;
*/

-- ============================================
-- PASO 3: VERIFICACIÓN POST-MIGRACIÓN
-- ============================================

-- Verificar total de precios migrados
SELECT 
    COUNT(*) AS total_precios_creados
FROM precio_has_venta
WHERE estado_id = 1
  AND users_id = 1
  AND DATE(created_at) = CURDATE();

-- Comparar productos con y sin precio_has_venta
SELECT 
    COUNT(DISTINCT p.id) AS total_productos_activos,
    COUNT(DISTINCT phv.producto_id) AS productos_con_precio,
    COUNT(DISTINCT p.id) - COUNT(DISTINCT phv.producto_id) AS productos_sin_precio
FROM producto p
LEFT JOIN precio_has_venta phv ON p.id = phv.producto_id AND phv.estado_id = 1
WHERE p.estado_id = 1;

-- Ver productos que aún no tienen precio_has_venta
SELECT 
    p.id,
    p.nombre,
    p.codigo_barra,
    p.precio_base,
    CASE 
        WHEN p.precio_base IS NULL OR p.precio_base = 0 THEN 'Sin precio_base definido'
        ELSE 'Revisar manualmente'
    END AS razon
FROM producto p
WHERE p.estado_id = 1
  AND NOT EXISTS (
      SELECT 1 
      FROM precio_has_venta phv 
      WHERE phv.producto_id = p.id 
        AND phv.estado_id = 1
  )
LIMIT 50;

-- Verificar integridad: productos con múltiples precios activos
SELECT 
    producto_id,
    COUNT(*) AS cantidad_precios
FROM precio_has_venta
WHERE estado_id = 1
GROUP BY producto_id
HAVING COUNT(*) > 1
ORDER BY cantidad_precios DESC;

-- Ver detalle de los últimos precios migrados
SELECT 
    phv.id AS precio_id,
    p.id AS producto_id,
    p.nombre,
    p.codigo_barra,
    phv.precio,
    um.nombre AS unidad_medida,
    phv.cantidad,
    phv.created_at
FROM precio_has_venta phv
INNER JOIN producto p ON phv.producto_id = p.id
INNER JOIN unidad_medida um ON phv.unidad_medida_id = um.id
WHERE phv.estado_id = 1
  AND DATE(phv.created_at) = CURDATE()
ORDER BY phv.created_at DESC
LIMIT 20;

-- ============================================
-- PASO 4: CONSULTAS ÚTILES POST-MIGRACIÓN
-- ============================================

-- Estadísticas generales
SELECT 
    'Total productos activos' AS concepto,
    COUNT(*) AS cantidad
FROM producto
WHERE estado_id = 1

UNION ALL

SELECT 
    'Productos con precio_has_venta',
    COUNT(DISTINCT producto_id)
FROM precio_has_venta
WHERE estado_id = 1

UNION ALL

SELECT 
    'Total registros en precio_has_venta',
    COUNT(*)
FROM precio_has_venta
WHERE estado_id = 1

UNION ALL

SELECT 
    'Migrados hoy',
    COUNT(*)
FROM precio_has_venta
WHERE estado_id = 1
  AND DATE(created_at) = CURDATE();

-- Productos con precio_base diferente al precio_has_venta
SELECT 
    p.id,
    p.nombre,
    p.precio_base AS precio_en_producto,
    phv.precio AS precio_en_venta,
    (phv.precio - p.precio_base) AS diferencia
FROM producto p
INNER JOIN precio_has_venta phv ON p.id = phv.producto_id
WHERE p.estado_id = 1
  AND phv.estado_id = 1
  AND phv.unidad_medida_id = 1
  AND phv.cantidad = 1
  AND ABS(p.precio_base - phv.precio) > 0.01
LIMIT 50;

-- ============================================
-- PASO 5: ROLLBACK (Si necesitas deshacer)
-- ============================================
-- ⚠️ SOLO ejecutar si necesitas revertir la migración

/*
-- Ver qué se eliminaría (simulación)
SELECT 
    phv.id,
    p.nombre,
    phv.precio,
    phv.created_at
FROM precio_has_venta phv
INNER JOIN producto p ON phv.producto_id = p.id
WHERE phv.estado_id = 1
  AND phv.users_id = 1
  AND DATE(phv.created_at) = CURDATE();

-- Desactivar precios migrados hoy (soft delete)
UPDATE precio_has_venta
SET estado_id = 2,
    updated_at = NOW()
WHERE estado_id = 1
  AND users_id = 1
  AND DATE(created_at) = CURDATE();

-- O eliminar permanentemente (NO RECOMENDADO)
-- DELETE FROM precio_has_venta
-- WHERE estado_id = 1
--   AND users_id = 1
--   AND DATE(created_at) = CURDATE();
*/

-- ============================================
-- NOTAS IMPORTANTES
-- ============================================
/*

1. BACKUP: Siempre hacer backup antes de ejecutar
   mysqldump -u usuario -p nombre_bd > backup_pre_migracion.sql

2. EJECUCIÓN POR PASOS:
   - Ejecutar PASO 1 para verificar
   - Ejecutar PASO 2 para migrar
   - Ejecutar PASO 3 para verificar resultados

3. PRODUCTOS SIN PRECIO:
   - Productos con precio_base = 0 o NULL no se migrarán
   - Configurarlos manualmente después

4. MÚLTIPLES UNIDADES:
   - Este script solo crea la unidad base (Unidad, cantidad 1)
   - Para cajas, packs, etc., agregarlos manualmente

5. ÍNDICES (si hay performance issues):
   CREATE INDEX idx_producto_estado ON precio_has_venta(producto_id, estado_id);
   CREATE INDEX idx_estado_fecha ON precio_has_venta(estado_id, created_at);

6. TRANSACCIONES (para bases de datos grandes):
   START TRANSACTION;
   -- Ejecutar PASO 2
   -- Verificar resultados
   COMMIT;  -- o ROLLBACK; si algo salió mal

*/
