-- =============================================
-- Script: Agregar columnas para totales contados
-- Descripción: Agregar campos para registrar totales contados manualmente
--              de tarjeta y cheque, y sus diferencias
-- Fecha: 2025-12-05
-- Sistema: ZENVY POS
-- =============================================

-- Agregar columnas para tarjeta contada y diferencia
ALTER TABLE `cierre_caja_historico`
ADD COLUMN `total_tarjeta_contado` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total tarjeta contado manualmente' AFTER `total_tarjeta`,
ADD COLUMN `diferencia_tarjeta` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Diferencia tarjeta (contado - sistema)' AFTER `total_tarjeta_contado`;

-- Agregar columnas para transferencia contada y diferencia
ALTER TABLE `cierre_caja_historico`
ADD COLUMN `total_transferencia_contado` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total transferencia contado manualmente' AFTER `total_transferencia`,
ADD COLUMN `diferencia_transferencia` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Diferencia transferencia (contado - sistema)' AFTER `total_transferencia_contado`;

-- Agregar columnas para cheque contado y diferencia
ALTER TABLE `cierre_caja_historico`
ADD COLUMN `total_cheque_contado` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total cheque contado manualmente' AFTER `total_cheque`,
ADD COLUMN `diferencia_cheque` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Diferencia cheque (contado - sistema)' AFTER `total_cheque_contado`;

-- Verificar las nuevas columnas
DESCRIBE `cierre_caja_historico`;
