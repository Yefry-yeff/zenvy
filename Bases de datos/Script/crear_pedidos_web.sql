-- Script para crear tabla de pedidos web y sus items
-- Este script implementa un flujo de preview antes de facturar

-- Tabla principal de pedidos web (preview)
CREATE TABLE IF NOT EXISTS `pedido_web` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `codigo_pedido` VARCHAR(50) UNIQUE NOT NULL COMMENT 'Código único del pedido (ej: PW-2026-0001)',
  `external_order_id` VARCHAR(100) NULL COMMENT 'ID del pedido en el sistema externo (e-commerce)',
  `api_client_id` INT UNSIGNED NULL COMMENT 'Cliente API que creó el pedido',
  
  -- Datos del cliente
  `nombre_cliente` VARCHAR(255) NOT NULL,
  `email_cliente` VARCHAR(255) NULL,
  `telefono_cliente` VARCHAR(50) NULL,
  `rtn_cliente` VARCHAR(50) NULL,
  
  -- Montos
  `sub_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `isv` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `monto_descuento` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  
  -- Información de entrega
  `tipo_entrega` ENUM('retiro_tienda', 'domicilio') NOT NULL DEFAULT 'retiro_tienda',
  `direccion_envio` TEXT NULL COMMENT 'Dirección completa de envío',
  `metodo_pago` VARCHAR(100) NULL COMMENT 'Método de pago seleccionado',
  
  -- Notas y comentarios (limitado para evitar truncamiento)
  `notas_cliente` VARCHAR(500) NULL COMMENT 'Notas del cliente sobre el pedido',
  `comentario_interno` TEXT NULL COMMENT 'Comentarios internos del sistema',
  
  -- Estado y facturación
  `estado` ENUM('pendiente', 'procesando', 'facturado', 'cancelado') NOT NULL DEFAULT 'pendiente',
  `factura_id` INT UNSIGNED NULL COMMENT 'ID de la factura si ya fue procesada',
  
  -- Fechas
  `fecha_pedido` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_procesado` DATETIME NULL COMMENT 'Fecha en que se procesó/facturó',
  `fecha_cancelado` DATETIME NULL,
  `motivo_cancelacion` TEXT NULL,
  
  -- Auditoría
  `users_id` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Usuario que procesa el pedido',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_codigo_pedido` (`codigo_pedido`),
  INDEX `idx_external_order_id` (`external_order_id`),
  INDEX `idx_estado` (`estado`),
  INDEX `idx_fecha_pedido` (`fecha_pedido`),
  INDEX `idx_factura_id` (`factura_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pedidos web (preview antes de facturar)';

-- Tabla de items del pedido web
CREATE TABLE IF NOT EXISTS `pedido_web_item` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `pedido_web_id` INT UNSIGNED NOT NULL,
  `producto_id` INT UNSIGNED NOT NULL,
  
  -- Información del producto al momento del pedido
  `nombre_producto` VARCHAR(255) NOT NULL COMMENT 'Nombre del producto al crear el pedido',
  `codigo_producto` VARCHAR(100) NULL,
  
  -- Cantidades y precios
  `cantidad` DECIMAL(10,2) NOT NULL,
  `precio_unidad` DECIMAL(12,2) NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  `descuento` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `isv` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL,
  
  -- Auditoría
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_pedido_web_id` (`pedido_web_id`),
  INDEX `idx_producto_id` (`producto_id`),
  
  FOREIGN KEY (`pedido_web_id`) REFERENCES `pedido_web`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Items de pedidos web';

-- Insertar registro de ejemplo (opcional)
-- INSERT INTO `pedido_web` (codigo_pedido, nombre_cliente, email_cliente, telefono_cliente, sub_total, isv, total, tipo_entrega, metodo_pago, estado)
-- VALUES ('PW-2026-0001', 'Cliente Ejemplo', 'cliente@example.com', '+50412345678', 1000.00, 150.00, 1150.00, 'domicilio', 'Efectivo', 'pendiente');
