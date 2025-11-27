-- Script para agregar columnas faltantes en la tabla descuentos
-- Fecha: 2025-11-27

USE punto_venta;

-- Agregar columna indice_factura_has_producto
ALTER TABLE descuentos 
ADD COLUMN indice_factura_has_producto INT UNSIGNED NULL AFTER producto_id,
ADD INDEX idx_indice_factura_has_producto (indice_factura_has_producto);

-- Agregar columna Tipo_descuento si no existe
ALTER TABLE descuentos 
ADD COLUMN Tipo_descuento VARCHAR(50) NULL AFTER producto_id,
ADD INDEX idx_tipo_descuento (Tipo_descuento);

-- Verificar la estructura de la tabla
DESCRIBE descuentos;
