# Migración Masiva de Precios a Tabla precio_has_venta

## 📋 Descripción

Este comando migra masivamente los precios base (`precio_base`) de todos los productos activos a la tabla `precio_has_venta`, creando una entrada con unidad de medida base (Unidad, ID 1).

## 🎯 Propósito

- **Problema**: Los productos existentes solo tienen `precio_base` en la tabla `producto`, pero el sistema ahora requiere que los precios estén en `precio_has_venta` para soportar múltiples unidades de venta.
- **Solución**: Este comando crea automáticamente un registro en `precio_has_venta` por cada producto activo, usando su `precio_base` actual.

## 🚀 Uso del Comando

### Migración Normal (Producción)

```bash
php artisan productos:migrar-precios-venta
```

Esto migrará todos los productos que:
- Estén activos (`estado_id = 1`)
- Tengan `precio_base` definido (> 0)
- **NO** tengan ya un precio en `precio_has_venta`

### Opciones Disponibles

#### 1. Modo Simulación (Dry Run) - RECOMENDADO PRIMERO

```bash
php artisan productos:migrar-precios-venta --dry-run
```

**¿Qué hace?**
- Simula la migración sin guardar cambios en la base de datos
- Muestra cuántos productos serían migrados
- Identifica posibles errores sin afectar datos reales

**Ejemplo de salida:**
```
========================================
MIGRACIÓN MASIVA DE PRECIOS A TABLA VENTA
========================================

⚠️  MODO SIMULACIÓN - No se guardarán cambios

🎯 Migrando todos los productos activos
📦 Productos encontrados: 1250

📏 Unidad de medida base: Unidad (ID: 1)

Progress: [████████████████████████████████] 100%

========================================
RESUMEN DE MIGRACIÓN
========================================
✅ Productos procesados: 1250
🔍 Productos que serían insertados: 980
⏭️  Productos omitidos (ya tienen precio): 270
```

#### 2. Migración con Sobrescritura (Force)

```bash
php artisan productos:migrar-precios-venta --force
```

**¿Qué hace?**
- Migra TODOS los productos, incluso los que ya tienen precios
- Desactiva (`estado_id = 2`) los precios existentes antes de insertar
- Útil para resetear precios o corregir migraciones previas

**⚠️ ADVERTENCIA**: Usa con precaución en producción.

#### 3. Migrar un Producto Específico

```bash
php artisan productos:migrar-precios-venta --producto-id=123
```

**¿Qué hace?**
- Migra solo el producto con ID especificado
- Útil para pruebas o correcciones puntuales

#### 4. Combinaciones

```bash
# Simular migración forzada de un producto
php artisan productos:migrar-precios-venta --producto-id=123 --force --dry-run

# Migrar forzadamente un producto específico
php artisan productos:migrar-precios-venta --producto-id=123 --force
```

## 📊 Proceso Detallado

### Lo que hace el comando:

1. **Identificación**:
   - Busca productos activos con `precio_base > 0`
   - Filtra por producto específico si se usa `--producto-id`

2. **Verificación**:
   - Comprueba si ya existe un `precio_has_venta` activo para cada producto
   - Omite productos que ya tienen precios (a menos que uses `--force`)

3. **Migración**:
   - Crea un registro en `precio_has_venta` con:
     - `producto_id`: ID del producto
     - `unidad_medida_id`: 1 (Unidad base)
     - `cantidad`: 1 (una unidad)
     - `precio`: Valor de `precio_base`
     - `estado_id`: 1 (Activo)
     - `users_id`: 1 (Usuario sistema)

4. **Registro**:
   - Guarda logs detallados en `storage/logs/laravel.log`
   - Muestra barra de progreso en consola
   - Genera resumen al finalizar

## 🔍 Validaciones

El comando validará:
- ✅ Que exista la unidad de medida base (ID 1)
- ✅ Que los productos tengan `precio_base` válido
- ✅ Que la tabla `precio_has_venta` exista
- ✅ Que no haya duplicados (a menos que uses `--force`)

## 📝 Logs

Cada migración queda registrada en:
- **Archivo**: `storage/logs/laravel.log`
- **Información guardada**:
  - ID del producto migrado
  - Nombre del producto
  - Precio base original
  - ID del nuevo registro en `precio_has_venta`

Ejemplo de log:
```php
[2025-01-15 10:30:45] local.INFO: Precio migrado {
    "producto_id": 123,
    "nombre": "Coca Cola 2L",
    "precio_base": 35.00,
    "precio_has_venta_id": 456
}
```

## 🛠️ Flujo de Trabajo Recomendado

### Para Producción (Paperland):

```bash
# Paso 1: Simular migración
php artisan productos:migrar-precios-venta --dry-run

# Paso 2: Revisar resultados de simulación
# Si todo se ve bien, proceder

# Paso 3: Ejecutar migración real
php artisan productos:migrar-precios-venta

# Paso 4: Verificar en base de datos
# SELECT COUNT(*) FROM precio_has_venta WHERE estado_id = 1;
```

### Para Desarrollo/Pruebas:

```bash
# Probar con un solo producto
php artisan productos:migrar-precios-venta --producto-id=1 --dry-run

# Si funciona, ejecutar real
php artisan productos:migrar-precios-venta --producto-id=1

# Verificar en base de datos o en la interfaz
```

## ⚠️ Consideraciones Importantes

### 1. **Backup de Base de Datos**
Antes de ejecutar en producción:
```bash
# Desde MySQL
mysqldump -u usuario -p nombre_bd > backup_antes_migracion.sql
```

### 2. **Productos Sin precio_base**
- El comando omite productos con `precio_base = 0` o `NULL`
- Estos productos deberán configurarse manualmente

### 3. **Múltiples Unidades**
- Este comando solo crea la unidad base (Unidad, cantidad 1)
- Si necesitas cajas, six packs, etc., agrégalos manualmente después

### 4. **Productos Ya Migrados**
- Por defecto, omite productos que ya tienen `precio_has_venta`
- Usa `--force` solo si necesitas sobrescribir

### 5. **Performance**
- Para bases de datos grandes (+10,000 productos), considera ejecutar en horarios de baja actividad
- El comando incluye barra de progreso para monitorear avance

## 🔄 Rollback (Deshacer Migración)

Si necesitas revertir la migración:

```sql
-- Desactivar todos los precios migrados (creados por el comando)
UPDATE precio_has_venta 
SET estado_id = 2 
WHERE users_id = 1 
AND created_at >= '2025-01-15 00:00:00';  -- Fecha de migración

-- O eliminar completamente (no recomendado)
DELETE FROM precio_has_venta 
WHERE users_id = 1 
AND created_at >= '2025-01-15 00:00:00';
```

## 📈 Ejemplo Completo

### Antes de la Migración:

**Tabla: producto**
| id  | nombre         | precio_base | estado_id |
|-----|----------------|-------------|-----------|
| 1   | Coca Cola 2L   | 35.00       | 1         |
| 2   | Pepsi 3L       | 45.00       | 1         |
| 3   | Agua Purificada| 0.00        | 1         |

**Tabla: precio_has_venta**
| id | producto_id | unidad_medida_id | cantidad | precio | estado_id |
|----|-------------|------------------|----------|--------|-----------|
| (vacía) | - | - | - | - | - |

### Ejecutar Comando:
```bash
php artisan productos:migrar-precios-venta
```

### Después de la Migración:

**Tabla: precio_has_venta**
| id | producto_id | unidad_medida_id | cantidad | precio | estado_id | users_id |
|----|-------------|------------------|----------|--------|-----------|----------|
| 1  | 1           | 1 (Unidad)       | 1        | 35.00  | 1         | 1        |
| 2  | 2           | 1 (Unidad)       | 1        | 45.00  | 1         | 1        |

**Nota**: El producto ID 3 no se migró porque su `precio_base = 0`

## 🆘 Solución de Problemas

### Error: "No se encontró la unidad de medida base (ID 1)"

**Solución**:
```sql
-- Verificar unidades de medida existentes
SELECT * FROM unidad_medida WHERE estado_id = 1;

-- Si no existe ID 1, insertar
INSERT INTO unidad_medida (id, nombre, estado_id, created_at, updated_at) 
VALUES (1, 'Unidad', 1, NOW(), NOW());
```

### Error: "Duplicate entry" o "Integrity constraint violation"

**Causa**: Ya existe un precio para ese producto.

**Solución**: Usa `--force` o revisa manualmente el producto.

### Performance lento

**Optimización**:
```sql
-- Asegurar índices en precio_has_venta
CREATE INDEX idx_producto_estado ON precio_has_venta(producto_id, estado_id);
```

## 📞 Soporte

Para problemas o consultas, revisar:
- Logs: `storage/logs/laravel.log`
- Documentación del sistema
- Contactar al equipo de desarrollo

---

**Última actualización**: Enero 2025  
**Versión del sistema**: Zenvy v4.0.3
