-- Crear tabla producto_valencia_zenvy para mapeo específico de productos
-- Optimizada para consultas rápidas por código de producto y código de barras

CREATE TABLE IF NOT EXISTS `producto_valencia_zenvy` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `producto_id_zenvy` BIGINT UNSIGNED NOT NULL COMMENT 'ID del producto en Zenvy (Paperland)',
    `producto_id_valencia` BIGINT UNSIGNED NOT NULL COMMENT 'ID del producto en Valencia',
    `codigo_producto_valencia` VARCHAR(50) NULL COMMENT 'Código del producto en Valencia para búsquedas rápidas',
    `codigo_barra` VARCHAR(100) NULL COMMENT 'Código de barras del producto',
    `sincronizado` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Estado de sincronización',
    `ultima_sincronizacion` TIMESTAMP NULL COMMENT 'Fecha de última sincronización',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    
    -- Índice único para producto_id_zenvy (un producto de Zenvy solo puede tener un origen Valencia)
    UNIQUE KEY `idx_producto_zenvy_unique` (`producto_id_zenvy`),
    
    -- Índice único para producto_id_valencia (un producto de Valencia solo puede estar sincronizado una vez)
    UNIQUE KEY `idx_producto_valencia_unique` (`producto_id_valencia`),
    
    -- Índice para código de producto Valencia (búsquedas rápidas)
    KEY `idx_codigo_producto_valencia` (`codigo_producto_valencia`),
    
    -- Índice para código de barras (búsquedas por código de barras)
    KEY `idx_codigo_barra` (`codigo_barra`),
    
    -- Índice para estado de sincronización
    KEY `idx_sincronizado` (`sincronizado`),
    
    -- Índice compuesto para búsquedas combinadas
    KEY `idx_valencia_codigo` (`producto_id_valencia`, `codigo_producto_valencia`),
    
    -- Llave foránea a tabla producto de Zenvy
    CONSTRAINT `fk_producto_zenvy` 
        FOREIGN KEY (`producto_id_zenvy`) 
        REFERENCES `producto` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Tabla de mapeo específico para productos entre Valencia y Zenvy - Optimizada para consultas rápidas';

-- Consultas de ejemplo para verificar el rendimiento:

-- Buscar producto por ID de Valencia (muy rápido con índice único)
-- SELECT * FROM producto_valencia_zenvy WHERE producto_id_valencia = 123;

-- Buscar producto por código de Valencia (rápido con índice)
-- SELECT * FROM producto_valencia_zenvy WHERE codigo_producto_valencia = 'PROD001';

-- Buscar producto por código de barras (rápido con índice)
-- SELECT * FROM producto_valencia_zenvy WHERE codigo_barra = '1234567890123';

-- Obtener ID de Zenvy desde ID de Valencia
-- SELECT producto_id_zenvy FROM producto_valencia_zenvy WHERE producto_id_valencia = 123 AND sincronizado = 1;

-- Obtener todos los productos sincronizados
-- SELECT * FROM producto_valencia_zenvy WHERE sincronizado = 1;

-- Estadísticas de sincronización
-- SELECT 
--     COUNT(*) as total_productos,
--     SUM(CASE WHEN sincronizado = 1 THEN 1 ELSE 0 END) as sincronizados,
--     MAX(ultima_sincronizacion) as ultima_actualizacion
-- FROM producto_valencia_zenvy;
