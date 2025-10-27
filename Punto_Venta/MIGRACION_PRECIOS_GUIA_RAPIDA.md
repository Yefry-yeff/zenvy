# 🚀 GUÍA RÁPIDA: Migración de Precios a precio_has_venta

## ⚡ Inicio Rápido (3 Pasos)

### 1️⃣ Verificar (Simulación)
```bash
php artisan productos:migrar-precios-venta --dry-run
```

### 2️⃣ Ejecutar Migración
```bash
php artisan productos:migrar-precios-venta
```

### 3️⃣ Verificar Resultados
```bash
# En la consola verás un resumen como:
# ✅ Productos procesados: 1250
# ✅ Precios insertados: 980
# ⏭️  Productos omitidos: 270
```

---

## 🎯 ¿Qué Hace Este Comando?

Migra el campo `precio_base` de todos los productos activos a la tabla `precio_has_venta`, creando un registro con:
- **Unidad de medida**: Unidad (ID 1)
- **Cantidad**: 1
- **Precio**: El valor de `precio_base`

---

## 📋 Opciones Disponibles

| Comando | Descripción |
|---------|-------------|
| `--dry-run` | **Simula** sin guardar (RECOMENDADO PRIMERO) |
| `--force` | Sobrescribe precios existentes |
| `--producto-id=123` | Migra solo un producto específico |

### Ejemplos:

```bash
# Simular migración de un producto
php artisan productos:migrar-precios-venta --producto-id=5 --dry-run

# Migrar solo un producto
php artisan productos:migrar-precios-venta --producto-id=5

# Forzar migración completa (sobrescribir todo)
php artisan productos:migrar-precios-venta --force

# Simular migración forzada
php artisan productos:migrar-precios-venta --force --dry-run
```

---

## ✅ Checklist Pre-Migración

- [ ] Hacer **backup** de la base de datos
- [ ] Ejecutar con `--dry-run` primero
- [ ] Verificar que exista la unidad de medida ID 1
- [ ] Confirmar horario de baja actividad (si hay muchos productos)

### Backup rápido:
```bash
# Windows (desde cmd)
"C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe" -u root -p nombre_bd > backup_precios.sql

# O desde phpMyAdmin: Exportar → SQL
```

---

## 🔍 Verificaciones Post-Migración

### En SQL (phpMyAdmin o HeidiSQL):

```sql
-- Ver cuántos precios se crearon hoy
SELECT COUNT(*) FROM precio_has_venta 
WHERE estado_id = 1 AND DATE(created_at) = CURDATE();

-- Ver productos sin precio_has_venta
SELECT p.id, p.nombre, p.precio_base
FROM producto p
WHERE p.estado_id = 1
AND NOT EXISTS (
    SELECT 1 FROM precio_has_venta phv 
    WHERE phv.producto_id = p.id AND phv.estado_id = 1
)
LIMIT 20;

-- Ver últimos precios migrados
SELECT p.nombre, phv.precio, phv.created_at
FROM precio_has_venta phv
INNER JOIN producto p ON phv.producto_id = p.id
WHERE DATE(phv.created_at) = CURDATE()
ORDER BY phv.created_at DESC
LIMIT 20;
```

### En la Aplicación:

1. Ir a **Inventario → Productos**
2. Editar cualquier producto
3. Verificar que aparezca en la sección **"Precios de Venta"**
4. Debe mostrar: **Unidad | Cantidad: 1 | Precio: L. XX.XX**

---

## ⚠️ Problemas Comunes

### Error: "No se encontró la unidad de medida base"

**Solución**: Crear la unidad en SQL:
```sql
INSERT INTO unidad_medida (id, nombre, estado_id, created_at, updated_at) 
VALUES (1, 'Unidad', 1, NOW(), NOW());
```

### No migra algunos productos

**Razones**:
- `precio_base` es 0 o NULL → Configurar manualmente
- Ya tienen `precio_has_venta` → Usar `--force` si necesitas sobrescribir

### Migración muy lenta

**Solución**: Ejecutar en horario de baja actividad (madrugada)

---

## 🔄 Deshacer Migración (Rollback)

Si necesitas revertir:

```sql
-- Desactivar precios migrados hoy (SOFT DELETE)
UPDATE precio_has_venta
SET estado_id = 2
WHERE users_id = 1
  AND DATE(created_at) = CURDATE();
```

---

## 📞 Ayuda

- **Documentación completa**: `MIGRACION_PRECIOS_VENTA.md`
- **Script SQL alternativo**: `Bases de datos/Script/migracion_masiva_precios_venta.sql`
- **Logs**: `storage/logs/laravel.log`

---

## 🎓 Ejemplo Completo

```bash
# Paso 1: Navegar al directorio del proyecto
cd C:\laragon\www\Procadts\zenvy\Punto_Venta

# Paso 2: Simular migración
php artisan productos:migrar-precios-venta --dry-run

# Salida esperada:
# 🎯 Migrando todos los productos activos
# 📦 Productos encontrados: 1250
# [████████████████████████████] 100%
# ✅ Productos procesados: 1250
# 🔍 Productos que serían insertados: 980

# Paso 3: Si todo se ve bien, ejecutar
php artisan productos:migrar-precios-venta

# Salida:
# ✅ Productos procesados: 1250
# ✅ Precios insertados: 980
# ⏭️  Productos omitidos: 270
# ✅ Migración completada exitosamente

# Paso 4: Verificar en SQL
# (ejecutar las consultas de verificación)

# ✅ LISTO - Migración completada
```

---

## 🌟 Recomendaciones Finales

1. **Siempre ejecuta `--dry-run` primero**
2. **Haz backup antes de ejecutar en producción**
3. **Revisa los logs** después de la migración
4. **Verifica algunos productos** manualmente en la app
5. **Configura manualmente** los productos que no se migraron

---

**Fecha creación**: Enero 2025  
**Versión**: Zenvy v4.0.3  
**Comando**: `productos:migrar-precios-venta`
