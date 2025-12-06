-- =============================================
-- Script: Agregar columnas de denominaciones a cierre_caja_historico
-- Descripción: Agrega columnas individuales para cada denominación
--              de billetes y monedas para el recibo de cierre
-- Fecha: 2025-12-05
-- Sistema: ZENVY POS - Recibo de Cierre de Caja
-- =============================================

ALTER TABLE `cierre_caja_historico`
ADD COLUMN `billetes_500` INT DEFAULT 0 COMMENT 'Cantidad de billetes de L. 500' AFTER `observaciones`,
ADD COLUMN `billetes_200` INT DEFAULT 0 COMMENT 'Cantidad de billetes de L. 200' AFTER `billetes_500`,
ADD COLUMN `billetes_100` INT DEFAULT 0 COMMENT 'Cantidad de billetes de L. 100' AFTER `billetes_200`,
ADD COLUMN `billetes_50` INT DEFAULT 0 COMMENT 'Cantidad de billetes de L. 50' AFTER `billetes_100`,
ADD COLUMN `billetes_20` INT DEFAULT 0 COMMENT 'Cantidad de billetes de L. 20' AFTER `billetes_50`,
ADD COLUMN `billetes_10` INT DEFAULT 0 COMMENT 'Cantidad de billetes de L. 10' AFTER `billetes_20`,
ADD COLUMN `billetes_5` INT DEFAULT 0 COMMENT 'Cantidad de billetes de L. 5' AFTER `billetes_10`,
ADD COLUMN `billetes_2` INT DEFAULT 0 COMMENT 'Cantidad de billetes de L. 2' AFTER `billetes_5`,
ADD COLUMN `billetes_1` INT DEFAULT 0 COMMENT 'Cantidad de billetes de L. 1' AFTER `billetes_2`,
ADD COLUMN `monedas_0_50` INT DEFAULT 0 COMMENT 'Cantidad de monedas de L. 0.50' AFTER `billetes_1`,
ADD COLUMN `monedas_0_20` INT DEFAULT 0 COMMENT 'Cantidad de monedas de L. 0.20' AFTER `monedas_0_50`,
ADD COLUMN `monedas_0_10` INT DEFAULT 0 COMMENT 'Cantidad de monedas de L. 0.10' AFTER `monedas_0_20`,
ADD COLUMN `monedas_0_05` INT DEFAULT 0 COMMENT 'Cantidad de monedas de L. 0.05' AFTER `monedas_0_10`;

COMMIT;
