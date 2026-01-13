-- ============================================
-- Migración: Crear tabla api_clients
-- Descripción: Tabla para almacenar clientes del API
-- ============================================

CREATE TABLE IF NOT EXISTS `api_clients` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT 'Nombre del cliente API (ej: E-commerce)',
    `api_key` VARCHAR(64) UNIQUE NOT NULL COMMENT 'API Key pública',
    `api_secret` VARCHAR(255) NOT NULL COMMENT 'API Secret hasheado',
    `is_active` BOOLEAN DEFAULT TRUE COMMENT 'Estado del cliente',
    `ip_whitelist` TEXT NULL COMMENT 'JSON array de IPs permitidas',
    `rate_limit_per_minute` INT DEFAULT 60 COMMENT 'Límite de requests por minuto',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_api_key` (`api_key`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Clientes autorizados para usar el API REST';

-- ============================================
-- Migración: Crear tabla api_logs
-- Descripción: Logs de auditoría de requests del API
-- ============================================

CREATE TABLE IF NOT EXISTS `api_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT UNSIGNED NULL COMMENT 'ID del cliente que hizo el request',
    `method` VARCHAR(10) NOT NULL COMMENT 'Método HTTP (GET, POST, etc)',
    `endpoint` VARCHAR(255) NOT NULL COMMENT 'Ruta del endpoint',
    `request_body` JSON NULL COMMENT 'Cuerpo del request',
    `response_status` INT NOT NULL COMMENT 'Código HTTP de respuesta',
    `response_body` JSON NULL COMMENT 'Cuerpo de la respuesta',
    `ip_address` VARCHAR(45) NOT NULL COMMENT 'IP del cliente',
    `user_agent` TEXT NULL COMMENT 'User agent del cliente',
    `duration_ms` DECIMAL(10,2) NULL COMMENT 'Duración en milisegundos',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_client_created` (`client_id`, `created_at`),
    INDEX `idx_endpoint` (`endpoint`),
    INDEX `idx_status` (`response_status`),
    INDEX `idx_created` (`created_at`),
    
    FOREIGN KEY (`client_id`) REFERENCES `api_clients`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de auditoría de requests del API';

-- ============================================
-- Agregar campos a la tabla facturas
-- Descripción: Campos adicionales para integración con API
-- ============================================

ALTER TABLE `factura` 
ADD COLUMN IF NOT EXISTS `external_order_id` VARCHAR(100) NULL COMMENT 'ID del pedido en el sistema externo (e-commerce)' AFTER `id`,
ADD COLUMN IF NOT EXISTS `estado` VARCHAR(50) DEFAULT 'completada' COMMENT 'Estado de la venta (completada, anulada, etc)' AFTER `total`,
ADD COLUMN IF NOT EXISTS `metadatos` JSON NULL COMMENT 'Metadatos adicionales del pedido' AFTER `estado`,
ADD COLUMN IF NOT EXISTS `motivo_anulacion` VARCHAR(500) NULL COMMENT 'Motivo de anulación' AFTER `metadatos`,
ADD COLUMN IF NOT EXISTS `anulada_at` TIMESTAMP NULL COMMENT 'Fecha de anulación' AFTER `motivo_anulacion`,
ADD COLUMN IF NOT EXISTS `api_client_id` INT UNSIGNED NULL COMMENT 'Cliente API que creó la venta' AFTER `anulada_at`,
ADD COLUMN IF NOT EXISTS `anulada_por_api_client_id` INT UNSIGNED NULL COMMENT 'Cliente API que anuló la venta' AFTER `api_client_id`,
ADD COLUMN IF NOT EXISTS `cliente_email` VARCHAR(255) NULL COMMENT 'Email del cliente' AFTER `cliente_nombre`,
ADD COLUMN IF NOT EXISTS `cliente_telefono` VARCHAR(50) NULL COMMENT 'Teléfono del cliente' AFTER `cliente_email`,
ADD COLUMN IF NOT EXISTS `cliente_direccion` VARCHAR(500) NULL COMMENT 'Dirección del cliente' AFTER `cliente_telefono`;

-- Agregar índices
ALTER TABLE `factura`
ADD INDEX IF NOT EXISTS `idx_external_order` (`external_order_id`),
ADD INDEX IF NOT EXISTS `idx_estado` (`estado`),
ADD INDEX IF NOT EXISTS `idx_api_client` (`api_client_id`);

-- Agregar foreign keys
ALTER TABLE `factura`
ADD CONSTRAINT `fk_factura_api_client` 
    FOREIGN KEY (`api_client_id`) REFERENCES `api_clients`(`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_factura_anulada_api_client` 
    FOREIGN KEY (`anulada_por_api_client_id`) REFERENCES `api_clients`(`id`) ON DELETE SET NULL;

-- ============================================
-- Insertar cliente de prueba
-- ============================================

-- NOTA: En producción, usar el servicio ApiAuthService para crear clientes
-- Este es solo un ejemplo para pruebas

-- INSERT INTO `api_clients` (`name`, `api_key`, `api_secret`, `is_active`, `rate_limit_per_minute`)
-- VALUES (
--     'E-commerce Test',
--     'pk_test_1234567890abcdef',
--     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Hash de "secret123"
--     TRUE,
--     60
-- );

-- ============================================
-- Verificación
-- ============================================

-- Verificar tablas creadas
SELECT 
    TABLE_NAME, 
    TABLE_COMMENT,
    CREATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('api_clients', 'api_logs');

-- Verificar columnas agregadas a factura
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    COLUMN_COMMENT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'factura'
AND COLUMN_NAME IN (
    'external_order_id',
    'estado',
    'metadatos',
    'api_client_id',
    'cliente_email',
    'cliente_telefono',
    'cliente_direccion'
);
