-- Agregar campo tipo_cierre a la tabla cierre_de_caja
-- 1 = Cierre de Caja
-- 2 = Cierre de Jornada

ALTER TABLE `cierre_de_caja` 
ADD COLUMN `tipo_cierre` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Cierre de Caja, 2=Cierre de Jornada' AFTER `diferencia_efectivo`;

-- Actualizar registros existentes: por defecto marcar como cierre de caja
UPDATE `cierre_de_caja` SET `tipo_cierre` = 1 WHERE `tipo_cierre` IS NULL OR `tipo_cierre` = 0;
