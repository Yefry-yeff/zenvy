-- Script de migración de datos existentes desde id_zenvy_valencia a producto_valencia_zenvy
-- Ejecutar DESPUÉS de crear la tabla producto_valencia_zenvy

-- Poblar tabla producto_valencia_zenvy con productos ya sincronizados
INSERT INTO `producto_valencia_zenvy` (
    `producto_id_zenvy`,
    `producto_id_valencia`,
    `codigo_producto_valencia`,
    `codigo_barra`,
    `sincronizado`,
    `ultima_sincronizacion`,
    `created_at`,
    `updated_at`
)
SELECT 
    izv.id_zenvy as producto_id_zenvy,
    izv.id_valencia as producto_id_valencia,
    p.codigo_estatal as codigo_producto_valencia,
    (
        SELECT phv.codigo_barra 
        FROM precio_has_venta phv 
        WHERE phv.producto_id = p.id 
        AND phv.estado_id = 1 
        LIMIT 1
    ) as codigo_barra,
    1 as sincronizado,
    NOW() as ultima_sincronizacion,
    izv.created_at,
    izv.updated_at
FROM 
    id_zenvy_valencia izv
INNER JOIN 
    producto p ON p.id = izv.id_zenvy
WHERE 
    izv.tipo_dato_migrado_id = 1  -- Solo productos
    AND NOT EXISTS (
        -- Evitar duplicados si ya existe en producto_valencia_zenvy
        SELECT 1 FROM producto_valencia_zenvy pvz 
        WHERE pvz.producto_id_valencia = izv.id_valencia
    )
ORDER BY 
    izv.id_valencia;

-- Verificar resultados de la migración
SELECT 
    'Migración completada' as mensaje,
    COUNT(*) as total_productos_migrados,
    MIN(created_at) as primer_producto,
    MAX(created_at) as ultimo_producto
FROM 
    producto_valencia_zenvy;

-- Comparar con la tabla original
SELECT 
    'id_zenvy_valencia' as tabla,
    COUNT(*) as total
FROM 
    id_zenvy_valencia 
WHERE 
    tipo_dato_migrado_id = 1

UNION ALL

SELECT 
    'producto_valencia_zenvy' as tabla,
    COUNT(*) as total
FROM 
    producto_valencia_zenvy;

-- Ver productos que NO se migraron (si hay alguno)
SELECT 
    izv.id_valencia,
    izv.id_zenvy,
    'No encontrado en tabla producto' as motivo
FROM 
    id_zenvy_valencia izv
LEFT JOIN 
    producto p ON p.id = izv.id_zenvy
WHERE 
    izv.tipo_dato_migrado_id = 1
    AND p.id IS NULL;
