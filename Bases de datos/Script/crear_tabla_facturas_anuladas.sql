-- =====================================================
-- Script: Creación de tabla facturas_anuladas
-- Propósito: Registrar todas las anulaciones de facturas
--            con su impacto en inventario y flujo de caja
-- Fecha: 2025-12-07
-- =====================================================

CREATE TABLE IF NOT EXISTS `facturas_anuladas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `factura_id` INT(11) NOT NULL COMMENT 'ID de la factura anulada',
  `numero_factura` VARCHAR(45) NOT NULL COMMENT 'Número de factura anulado',
  `nombre_cliente` VARCHAR(150) DEFAULT NULL COMMENT 'Nombre del cliente',
  `rtn` VARCHAR(45) DEFAULT NULL COMMENT 'RTN del cliente',
  `sub_total` DECIMAL(60,2) NOT NULL COMMENT 'Subtotal de la factura anulada',
  `isv` DECIMAL(60,2) NOT NULL COMMENT 'ISV de la factura anulada',
  `total` DECIMAL(60,2) NOT NULL COMMENT 'Total de la factura anulada',
  `fecha_emision_factura` DATE NOT NULL COMMENT 'Fecha de emisión original de la factura',
  `fecha_anulacion` DATETIME NOT NULL COMMENT 'Fecha y hora de anulación',
  `motivo_anulacion` TEXT NOT NULL COMMENT 'Motivo de la anulación',
  `users_id_anulo` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Usuario que anuló la factura',
  `users_id_vendedor` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Usuario vendedor original',
  `productos_devueltos` JSON DEFAULT NULL COMMENT 'Detalle de productos que regresaron a inventario',
  `impacto_flujo_caja` DECIMAL(60,2) DEFAULT NULL COMMENT 'Monto que afectó el flujo de caja (si aplica)',
  `metodo_devolucion` VARCHAR(100) DEFAULT NULL COMMENT 'Efectivo, transferencia, nota de crédito, etc.',
  `observaciones` TEXT DEFAULT NULL COMMENT 'Observaciones adicionales',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  
  -- Foreign Keys
  CONSTRAINT `fk_facturas_anuladas_factura`
    FOREIGN KEY (`factura_id`)
    REFERENCES `factura` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
    
  CONSTRAINT `fk_facturas_anuladas_user_anulo`
    FOREIGN KEY (`users_id_anulo`)
    REFERENCES `users` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
    
  CONSTRAINT `fk_facturas_anuladas_user_vendedor`
    FOREIGN KEY (`users_id_vendedor`)
    REFERENCES `users` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
    
  -- Índices para búsquedas
  INDEX `idx_factura_id` (`factura_id`),
  INDEX `idx_numero_factura` (`numero_factura`),
  INDEX `idx_fecha_anulacion` (`fecha_anulacion`),
  INDEX `idx_user_anulo` (`users_id_anulo`),
  INDEX `idx_user_vendedor` (`users_id_vendedor`),
  INDEX `idx_fecha_emision` (`fecha_emision_factura`)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro histórico de facturas anuladas con impacto en inventario y flujo de caja';
