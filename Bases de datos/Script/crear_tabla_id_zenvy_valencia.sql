-- Crear tabla id_zenvy_valencia para mapeo de IDs entre Valencia y Zenvy
-- Esta tabla relaciona los IDs de registros sincronizados entre ambas bases de datos

CREATE TABLE IF NOT EXISTS `id_zenvy_valencia` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_zenvy` BIGINT UNSIGNED NOT NULL COMMENT 'ID del registro en la base de datos Zenvy (Paperland)',
    `id_valencia` BIGINT UNSIGNED NOT NULL COMMENT 'ID del registro en la base de datos Valencia',
    `tipo_dato_migrado_id` TINYINT UNSIGNED NOT NULL COMMENT '1=Producto, 2=Marca, 3=Categoria, 4=Subcategoria, 5=UnidadMedida, 8=Compra, 9=Traslado',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    
    -- Índice único compuesto para evitar duplicados
    UNIQUE KEY `idx_valencia_tipo_unique` (`id_valencia`, `tipo_dato_migrado_id`),
    
    -- Índice compuesto para búsquedas por Zenvy y tipo
    KEY `idx_zenvy_tipo` (`id_zenvy`, `tipo_dato_migrado_id`),
    
    -- Índices individuales para consultas frecuentes
    KEY `idx_id_valencia` (`id_valencia`),
    KEY `idx_id_zenvy` (`id_zenvy`),
    KEY `idx_tipo_dato` (`tipo_dato_migrado_id`)
    
    -- Opción: Agregar FK a tabla producto (solo para tipo_dato_migrado_id = 1)
    -- CONSTRAINT `fk_zenvy_producto` FOREIGN KEY (`id_zenvy`) REFERENCES `producto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla de mapeo entre IDs de Valencia y Zenvy para sincronización';

-- Crear tabla tipo_dato_migrado para referencia de tipos (opcional)
CREATE TABLE IF NOT EXISTS `tipo_dato_migrado` (
    `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(50) NOT NULL,
    `descripcion` VARCHAR(255) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar tipos de datos
INSERT INTO `tipo_dato_migrado` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Producto', 'Productos sincronizados desde Valencia'),
(2, 'Marca', 'Marcas sincronizadas desde Valencia'),
(3, 'Categoria', 'Categorías sincronizadas desde Valencia'),
(4, 'Subcategoria', 'Subcategorías sincronizadas desde Valencia'),
(5, 'UnidadMedida', 'Unidades de medida sincronizadas desde Valencia'),
(8, 'Compra', 'Compras sincronizadas desde Valencia'),
(9, 'Traslado', 'Traslados sincronizados desde Valencia')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Agregar FK a id_zenvy_valencia para tipo_dato_migrado (opcional)
-- ALTER TABLE `id_zenvy_valencia`
-- ADD CONSTRAINT `fk_tipo_dato_migrado` 
-- FOREIGN KEY (`tipo_dato_migrado_id`) REFERENCES `tipo_dato_migrado` (`id`) 
-- ON DELETE RESTRICT ON UPDATE CASCADE;
