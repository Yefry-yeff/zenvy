-- Script para agregar columnas de descuento e ISV aplicado a factura_has_producto
-- Fecha: 2025-08-09
-- Descripción: Agrega columnas para rastrear descuentos por edad e ISV aplicado por producto

USE db_zenvy;

-- Verificar si las columnas ya existen antes de agregarlas
SET @db_name = 'db_zenvy';
SET @table_name = 'factura_has_producto';

-- Agregar columna descuento si no existe
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @db_name 
    AND TABLE_NAME = @table_name 
    AND COLUMN_NAME = 'descuento'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE `db_zenvy`.`factura_has_producto` ADD COLUMN `descuento` DECIMAL(16,2) NULL DEFAULT 0.00 COMMENT "Monto del descuento aplicado al producto"',
    'SELECT "La columna descuento ya existe" as mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar columna isv_aplicado si no existe
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @db_name 
    AND TABLE_NAME = @table_name 
    AND COLUMN_NAME = 'isv_aplicado'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE `db_zenvy`.`factura_has_producto` ADD COLUMN `isv_aplicado` DECIMAL(16,2) NULL DEFAULT 0.00 COMMENT "Porcentaje de ISV aplicado al producto (15%, 18%, etc.)"',
    'SELECT "La columna isv_aplicado ya existe" as mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar que las columnas se agregaron correctamente
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'db_zenvy' 
    AND TABLE_NAME = 'factura_has_producto' 
    AND COLUMN_NAME IN ('descuento', 'isv_aplicado')
ORDER BY ORDINAL_POSITION;

-- Mostrar estructura actualizada de la tabla
DESCRIBE `db_zenvy`.`factura_has_producto`;
