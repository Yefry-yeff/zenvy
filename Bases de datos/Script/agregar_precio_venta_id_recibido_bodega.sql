-- =============================================
-- Script: Agregar campo precio_venta_id a recibido_bodega
-- Descripción: Permite identificar la presentación específica (código de barras) 
--              del producto al momento de recibir en bodega
-- Fecha: 2025-12-04
-- =============================================

-- Agregar columna precio_venta_id
ALTER TABLE `recibido_bodega` 
ADD COLUMN `precio_venta_id` INT(11) NULL AFTER `unidad_compra_id`,
ADD INDEX `fk_recibido_bodega_precio_venta_idx` (`precio_venta_id`);

-- Agregar foreign key constraint
ALTER TABLE `recibido_bodega`
ADD CONSTRAINT `fk_recibido_bodega_precio_venta` 
FOREIGN KEY (`precio_venta_id`) 
REFERENCES `precio_has_venta` (`id`) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- Comentario sobre el campo
ALTER TABLE `recibido_bodega` 
MODIFY COLUMN `precio_venta_id` INT(11) NULL 
COMMENT 'ID de precio_has_venta para identificar la presentación específica con su código de barras';

-- Verificar la estructura
DESCRIBE recibido_bodega;
