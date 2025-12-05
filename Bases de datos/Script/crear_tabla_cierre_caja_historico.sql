-- =============================================
-- Script: Crear tabla cierre_caja_historico
-- Descripción: Nueva tabla para registrar histórico de cierres de caja
--              sin dependencia de jornadas
-- Fecha: 2025-12-05
-- Sistema: ZENVY POS - Eliminación de Jornadas
-- =============================================

-- Crear tabla para histórico de cierres
CREATE TABLE IF NOT EXISTS `cierre_caja_historico` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'Usuario que realizó el cierre',
    `tienda_id` BIGINT UNSIGNED NOT NULL COMMENT 'Tienda donde se realizó el cierre',
    `fecha_cierre` DATETIME NOT NULL COMMENT 'Fecha y hora del cierre',
    `periodo_inicio` DATETIME NULL COMMENT 'Inicio del período (último cierre)',
    `periodo_fin` DATETIME NULL COMMENT 'Fin del período (este cierre)',
    `total_efectivo_sistema` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total efectivo según sistema',
    `total_efectivo_contado` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total efectivo contado físicamente',
    `diferencia` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Diferencia (contado - sistema)',
    `total_tarjeta` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total pagos con tarjeta',
    `total_transferencia` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total pagos con transferencia',
    `total_cheque` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total pagos con cheque',
    `total_general` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total general de ventas',
    `cantidad_facturas` INT DEFAULT 0 COMMENT 'Cantidad de facturas en el período',
    `observaciones` TEXT NULL COMMENT 'Observaciones del cierre',
    `desglose_billetes` JSON NULL COMMENT 'Desglose de billetes y monedas en formato JSON',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_tienda` (`user_id`, `tienda_id`),
    INDEX `idx_fecha_cierre` (`fecha_cierre`),
    INDEX `idx_tienda_fecha` (`tienda_id`, `fecha_cierre`),
    INDEX `idx_periodo` (`periodo_inicio`, `periodo_fin`),
    CONSTRAINT `fk_cierre_usuario` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_cierre_tienda` FOREIGN KEY (`tienda_id`) REFERENCES `tienda`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Histórico de cierres de caja sin dependencia de jornadas';

-- Verificar que se creó correctamente
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME,
    TABLE_COMMENT
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'cierre_caja_historico';

-- =============================================
-- Script: Actualizar tabla caja para nuevo sistema
-- =============================================

-- Actualizar cajas existentes que estén cerradas
UPDATE `caja`
SET 
    `estado_caja` = 1,
    `balance` = 2000.00,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `estado_caja` = 2;

-- Verificar actualización
SELECT 
    COUNT(*) as total_cajas,
    SUM(CASE WHEN estado_caja = 1 THEN 1 ELSE 0 END) as cajas_activas,
    SUM(CASE WHEN balance = 2000.00 THEN 1 ELSE 0 END) as cajas_con_saldo_correcto
FROM `caja`;

-- =============================================
-- NOTAS IMPORTANTES:
-- =============================================
-- 1. Las tablas "jornada" NO se eliminan para mantener histórico
-- 2. Los datos antiguos permanecen intactos
-- 3. El nuevo sistema trabaja independientemente
-- =============================================

COMMIT;
