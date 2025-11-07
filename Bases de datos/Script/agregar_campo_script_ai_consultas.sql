-- Agregar campo 'script' a la tabla ai_consultas
-- Este campo guardará la consulta SQL generada por la IA

-- Verificar si la tabla existe, si no crearla
CREATE TABLE IF NOT EXISTS ai_consultas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL,
    pregunta TEXT NOT NULL,
    respuesta TEXT NOT NULL,
    script TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si la tabla ya existe, agregar solo la columna script
ALTER TABLE ai_consultas 
ADD COLUMN IF NOT EXISTS script TEXT NULL 
COMMENT 'Consulta SQL generada por la IA' 
AFTER respuesta;

-- Mensaje de confirmación
SELECT 'Campo script agregado exitosamente a ai_consultas' AS mensaje;
