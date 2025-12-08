-- =====================================================
-- SCRIPT: Crear tabla ajuste_inventario
-- DESCRIPCIÓN: Tabla dedicada para registrar ajustes de cantidades en inventario
-- FECHA: 2025-12-07
-- =====================================================

CREATE TABLE IF NOT EXISTS `ajuste_inventario` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recibido_bodega_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID del registro en recibido_bodega',
  `producto_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID del producto ajustado',
  `bodega_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID de la bodega',
  `seccion_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID de la sección',
  `tipo_ajuste` ENUM('aumentar', 'disminuir') NOT NULL COMMENT 'Tipo de ajuste realizado',
  `cantidad_anterior` DECIMAL(10,2) NOT NULL COMMENT 'Cantidad disponible antes del ajuste',
  `cantidad_ajustada` DECIMAL(10,2) NOT NULL COMMENT 'Cantidad que se aumentó o disminuyó',
  `cantidad_nueva` DECIMAL(10,2) NOT NULL COMMENT 'Cantidad disponible después del ajuste',
  `unidad_medida_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID de la unidad de medida',
  `motivo` TEXT NOT NULL COMMENT 'Motivo del ajuste',
  `users_id` BIGINT UNSIGNED NOT NULL COMMENT 'Usuario que realizó el ajuste',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_recibido_bodega` (`recibido_bodega_id`),
  INDEX `idx_producto` (`producto_id`),
  INDEX `idx_bodega` (`bodega_id`),
  INDEX `idx_seccion` (`seccion_id`),
  INDEX `idx_usuario` (`users_id`),
  INDEX `idx_fecha` (`created_at`),
  INDEX `idx_tipo_ajuste` (`tipo_ajuste`),
  CONSTRAINT `fk_ajuste_recibido_bodega` FOREIGN KEY (`recibido_bodega_id`) REFERENCES `recibido_bodega`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ajuste_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ajuste_bodega` FOREIGN KEY (`bodega_id`) REFERENCES `bodega`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ajuste_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `seccion`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ajuste_unidad_medida` FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ajuste_usuario` FOREIGN KEY (`users_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de ajustes de inventario';

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZAR CONSULTAS
-- =====================================================

-- Índice compuesto para filtros comunes en reportes
CREATE INDEX `idx_ajuste_fecha_tipo` ON `ajuste_inventario` (`created_at`, `tipo_ajuste`);
CREATE INDEX `idx_ajuste_producto_fecha` ON `ajuste_inventario` (`producto_id`, `created_at`);
CREATE INDEX `idx_ajuste_bodega_fecha` ON `ajuste_inventario` (`bodega_id`, `created_at`);

-- =====================================================
-- COMENTARIOS ADICIONALES
-- =====================================================

/*
NOTAS:
1. Esta tabla registra todos los ajustes de inventario realizados
2. Permite trazabilidad completa de cambios en cantidades
3. Los campos cantidad_anterior, cantidad_ajustada y cantidad_nueva permiten auditoría
4. El motivo es obligatorio para justificar cada ajuste
5. La relación con users_id permite saber quién hizo el cambio
6. Los índices optimizan las consultas de reportes por fecha, producto, bodega, etc.

EJEMPLOS DE USO:
- Reporte de ajustes por rango de fechas
- Auditoría de cambios por usuario
- Historial de ajustes por producto específico
- Seguimiento de ajustes por bodega/sección
*/
