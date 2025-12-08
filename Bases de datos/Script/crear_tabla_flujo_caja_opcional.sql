-- =====================================================
-- Script: Creación de tabla flujo_caja (OPCIONAL)
-- Propósito: Registrar movimientos de caja por anulaciones
-- Nota: Solo ejecutar si desea registrar impacto en flujo
-- Fecha: 2025-12-07
-- =====================================================

CREATE TABLE IF NOT EXISTS `flujo_caja` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tipo_movimiento` ENUM('entrada', 'salida') NOT NULL COMMENT 'Tipo de movimiento',
  `monto` DECIMAL(60,2) NOT NULL COMMENT 'Monto del movimiento',
  `descripcion` TEXT DEFAULT NULL COMMENT 'Descripción del movimiento',
  `metodo_pago` VARCHAR(50) DEFAULT NULL COMMENT 'efectivo, transferencia, nota_credito, etc.',
  `factura_id` INT(11) DEFAULT NULL COMMENT 'Relación con factura (si aplica)',
  `users_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Usuario que registró el movimiento',
  `fecha_movimiento` DATETIME NOT NULL COMMENT 'Fecha y hora del movimiento',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  
  -- Foreign Keys
  CONSTRAINT `fk_flujo_caja_factura`
    FOREIGN KEY (`factura_id`)
    REFERENCES `factura` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
    
  CONSTRAINT `fk_flujo_caja_user`
    FOREIGN KEY (`users_id`)
    REFERENCES `users` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
    
  -- Índices
  INDEX `idx_tipo_movimiento` (`tipo_movimiento`),
  INDEX `idx_fecha_movimiento` (`fecha_movimiento`),
  INDEX `idx_factura_id` (`factura_id`),
  INDEX `idx_users_id` (`users_id`)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de movimientos de flujo de caja (entradas y salidas)';
