-- ============================================
-- SQL para crear tablas en tu página web
-- Ejecuta este script en tu base de datos
-- ============================================

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` INT(11) NOT NULL PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de productos
CREATE TABLE IF NOT EXISTS `productos` (
  `id` INT(11) NOT NULL PRIMARY KEY,
  `nombre` VARCHAR(255) NOT NULL,
  `codigo_barra` VARCHAR(100) DEFAULT NULL,
  `codigo_estatal` VARCHAR(100) DEFAULT NULL,
  `stock_disponible` INT(11) NOT NULL DEFAULT 0,
  `precio_venta` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `categoria_id` INT(11) DEFAULT NULL,
  `estado` ENUM('activo','inactivo') DEFAULT 'activo',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_codigo_barra` (`codigo_barra`),
  INDEX `idx_codigo_estatal` (`codigo_estatal`),
  INDEX `idx_nombre` (`nombre`),
  INDEX `idx_categoria` (`categoria_id`),
  INDEX `idx_estado` (`estado`),
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de logs de sincronización
CREATE TABLE IF NOT EXISTS `sync_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tipo` VARCHAR(50) NOT NULL,
  `mensaje` TEXT,
  `datos` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_tipo` (`tipo`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
