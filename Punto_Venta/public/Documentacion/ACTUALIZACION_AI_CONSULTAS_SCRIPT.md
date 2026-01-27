# Actualización de Tabla ai_consultas - Campo Script

## 🎯 Objetivo
Agregar el campo `script` a la tabla `ai_consultas` para guardar las consultas SQL generadas por la IA.

## 📋 Pasos para Aplicar la Actualización

### Opción 1: Usando phpMyAdmin o MySQL Workbench
1. Abre tu herramienta de administración de base de datos
2. Selecciona la base de datos de tu proyecto
3. Ejecuta el siguiente script:

```sql
-- Agregar campo 'script' a la tabla ai_consultas
ALTER TABLE ai_consultas 
ADD COLUMN IF NOT EXISTS script TEXT NULL 
COMMENT 'Consulta SQL generada por la IA' 
AFTER respuesta;
```

### Opción 2: Usando Terminal/CMD
```bash
# En Laragon o XAMPP
mysql -u root -p nombre_base_datos < "Bases de datos/Script/agregar_campo_script_ai_consultas.sql"
```

### Opción 3: Si la tabla NO existe
Si la tabla `ai_consultas` no existe, ejecuta el script completo:

```sql
CREATE TABLE IF NOT EXISTS ai_consultas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL,
    pregunta TEXT NOT NULL,
    respuesta TEXT NOT NULL,
    script TEXT NULL COMMENT 'Consulta SQL generada por la IA',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## ✅ Verificación

Después de ejecutar el script, verifica que el campo se haya agregado:

```sql
DESCRIBE ai_consultas;
```

Deberías ver algo como:

| Field      | Type              | Null | Key | Default | Extra          |
|------------|-------------------|------|-----|---------|----------------|
| id         | bigint unsigned   | NO   | PRI | NULL    | auto_increment |
| usuario_id | bigint unsigned   | NO   | MUL | NULL    |                |
| pregunta   | text              | NO   |     | NULL    |                |
| respuesta  | text              | NO   |     | NULL    |                |
| script     | text              | YES  |     | NULL    |                | ⬅️ NUEVO
| created_at | timestamp         | YES  | MUL | NULL    |                |
| updated_at | timestamp         | YES  |     | NULL    |                |

## 🚀 Funcionalidades Nuevas

Con este cambio, ahora:

1. ✅ **Se guarda el SQL generado**: Cada vez que la IA genera un reporte, se guarda la consulta SQL
2. ✅ **Historial mejorado**: El historial muestra un ícono cuando hay SQL disponible
3. ✅ **Reutilización inteligente**: Al hacer clic en el historial, se ejecuta automáticamente el SQL guardado
4. ✅ **Auto-scroll**: Cuando se genera la tabla, la página se desplaza automáticamente hacia ella

## 📊 Ejemplo de Uso

### Antes:
- Usuario: "Muestra ventas del día"
- Sistema: Genera y muestra tabla
- Historial: Solo guarda pregunta y respuesta

### Ahora:
- Usuario: "Muestra ventas del día"
- Sistema: Genera tabla + **guarda SQL**
- Historial: Guarda pregunta, respuesta **Y el SQL**
- Reutilización: Al hacer clic en historial → ejecuta SQL automáticamente

## 🔧 Rollback (Si necesitas revertir)

Si por alguna razón necesitas eliminar el campo:

```sql
ALTER TABLE ai_consultas 
DROP COLUMN script;
```

---

**Fecha**: Noviembre 2025
**Versión**: 2.1
**Archivo SQL**: `Bases de datos/Script/agregar_campo_script_ai_consultas.sql`
