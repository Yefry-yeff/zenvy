-- =====================================================
-- SCRIPT: Crear tabla cambio_unidad
-- DESCRIPCIÓN: Tabla dedicada para registrar cambios/conversiones de unidades de medida en inventario
-- FECHA: 2025-12-07
-- =====================================================

CREATE TABLE IF NOT EXISTS `cambio_unidad` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recibido_bodega_id_original` BIGINT UNSIGNED NOT NULL COMMENT 'ID del registro original que se rebajó',
  `recibido_bodega_id_nuevo` BIGINT UNSIGNED NOT NULL COMMENT 'ID del nuevo registro creado con la nueva unidad',
  `producto_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID del producto convertido',
  `bodega_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID de la bodega',
  `seccion_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID de la sección',
  `unidad_medida_id_original` BIGINT UNSIGNED NOT NULL COMMENT 'ID de la unidad de medida original',
  `unidad_medida_id_nueva` BIGINT UNSIGNED NOT NULL COMMENT 'ID de la nueva unidad de medida',
  `cantidad_rebajada` DECIMAL(10,2) NOT NULL COMMENT 'Cantidad rebajada de la unidad original',
  `cantidad_convertida` DECIMAL(10,2) NOT NULL COMMENT 'Cantidad generada en la nueva unidad',
  `factor_conversion` DECIMAL(10,4) NULL COMMENT 'Factor de conversión aplicado (opcional)',
  `motivo` TEXT NULL COMMENT 'Motivo o comentario del cambio',
  `users_id` BIGINT UNSIGNED NOT NULL COMMENT 'Usuario que realizó el cambio',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_recibido_original` (`recibido_bodega_id_original`),
  INDEX `idx_recibido_nuevo` (`recibido_bodega_id_nuevo`),
  INDEX `idx_producto` (`producto_id`),
  INDEX `idx_bodega` (`bodega_id`),
  INDEX `idx_seccion` (`seccion_id`),
  INDEX `idx_usuario` (`users_id`),
  INDEX `idx_fecha` (`created_at`),
  INDEX `idx_unidad_original` (`unidad_medida_id_original`),
  INDEX `idx_unidad_nueva` (`unidad_medida_id_nueva`),
  CONSTRAINT `fk_cambio_recibido_original` FOREIGN KEY (`recibido_bodega_id_original`) REFERENCES `recibido_bodega`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cambio_recibido_nuevo` FOREIGN KEY (`recibido_bodega_id_nuevo`) REFERENCES `recibido_bodega`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cambio_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cambio_bodega` FOREIGN KEY (`bodega_id`) REFERENCES `bodega`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cambio_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `seccion`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cambio_unidad_original` FOREIGN KEY (`unidad_medida_id_original`) REFERENCES `unidad_medida`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cambio_unidad_nueva` FOREIGN KEY (`unidad_medida_id_nueva`) REFERENCES `unidad_medida`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cambio_usuario` FOREIGN KEY (`users_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de cambios y conversiones de unidades de medida';

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZAR CONSULTAS
-- =====================================================

-- Índice compuesto para filtros comunes en reportes
CREATE INDEX `idx_cambio_fecha_producto` ON `cambio_unidad` (`created_at`, `producto_id`);
CREATE INDEX `idx_cambio_bodega_fecha` ON `cambio_unidad` (`bodega_id`, `created_at`);
CREATE INDEX `idx_cambio_producto_fecha` ON `cambio_unidad` (`producto_id`, `created_at`);

-- =====================================================
-- COMENTARIOS ADICIONALES
-- =====================================================

/*
NOTAS:
1. Esta tabla registra todos los cambios de unidades de medida realizados
2. Permite trazabilidad completa de conversiones
3. Mantiene referencia tanto al registro original como al nuevo
4. El factor_conversion es opcional y puede calcularse: cantidad_convertida / cantidad_rebajada
5. La relación con users_id permite saber quién hizo el cambio
6. Los índices optimizan las consultas de reportes por fecha, producto, bodega, etc.

EJEMPLOS DE USO:
- Reporte de conversiones por rango de fechas
- Auditoría de cambios por usuario
- Historial de conversiones por producto específico
- Seguimiento de conversiones por bodega/sección
- Análisis de factores de conversión utilizados
*/
