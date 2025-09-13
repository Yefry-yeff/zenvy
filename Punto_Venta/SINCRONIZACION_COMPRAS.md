# 📋 Sistema de Sincronización de Compras Valencia

## 🎯 Descripción
Sistema que sincroniza las compras nuevas desde Valencia (profac_app) hacia Zenvy (db_zenvy), aplicando la regla de **solo nuevas compras** - no modifica compras ya sincronizadas.

## 🔄 Tipos de Sincronización

### 1. Sincronización Manual
- **Acceso**: Botón "Sincronizar Valencia" en la vista de compras
- **Características**:
  - Efectos visuales de carga (spinner, barra de progreso)
  - Modal con estadísticas detalladas
  - Feedback inmediato al usuario

### 2. Sincronización Automática
- **Horarios Programados**:
  - **Diario**: 4:00 AM (sincronización principal)
  - **Martes**: 6:00 AM (respaldo semanal)
  - **Viernes**: 6:00 AM (respaldo semanal)

## 🗂️ Estructura de Datos

### Script SQL de Valencia
El sistema utiliza este query para obtener compras desde `profac_app`:

```sql
SELECT  
    -- Datos para tabla compra
    COALESCE(C.translado_id, A.compra_id) AS numero_factura,
    A.created_at AS fecha_emision,
    -- Datos para tabla compra_has_producto  
    COALESCE(chp.precio_unidad, B.precio_unidad) AS precio,
    A.cantidad_inicial_seccion AS cantidad_ingresada,
    A.cantidad_disponible AS cantidad_sin_asignar,
    A.fecha_expiracion AS fecha_expiracion,
    -- Cálculos financieros
    (COALESCE(chp.precio_unidad, B.precio_unidad) * A.cantidad_inicial_seccion) AS sub_total_producto,
    ((COALESCE(chp.precio_unidad, B.precio_unidad) * A.cantidad_inicial_seccion) * (P.isv / 100.0)) AS isv,
    A.producto_id AS producto_id_valencia,
    CASE WHEN C.translado_id IS NOT NULL THEN 'TRASLADO' ELSE 'COMPRA' END AS tipo_origen
FROM recibido_bodega A
WHERE A.seccion_id = 433 AND A.created_at > '2025-09-10'
```

### Mapeo de Datos

| Campo Valencia | Campo Zenvy | Descripción |
|----------------|-------------|-------------|
| `COALESCE(C.translado_id, A.compra_id)` | `numero_factura` | Número único de factura |
| `A.created_at` | `fecha_emision` | Fecha de emisión |
| `NOW()` | `fecha_recepcion` | Fecha de sincronización |
| `A.producto_id` | Mapeo via `id_zenvy_valencia` | ID del producto mapeado |
| Cálculos automáticos | `sub_total_producto`, `isv`, `precio_total` | Valores financieros |

## 🛡️ Reglas de Negocio

### ✅ Compras que SE sincronizan:
- Compras nuevas no existentes en Zenvy
- Productos que tienen mapeo en `id_zenvy_valencia`
- Compras de la sección 433 (Valencia)
- Compras posteriores al 2025-09-10

### ❌ Compras que NO se sincronizan:
- Compras ya existentes (mismo `numero_factura`)
- Productos sin mapeo en Zenvy
- Productos ya sincronizados en la misma compra

## 📊 Estadísticas de Sincronización

### Métricas Reportadas:
- **compras_nuevas**: Compras creadas
- **productos_sincronizados**: Productos agregados
- **total_procesadas**: Total de compras procesadas
- **errores**: Número de errores encontrados

### Logs del Sistema:
```bash
# Ver logs de sincronización
tail -f storage/logs/laravel.log | grep "compras"

# Ver solo errores
tail -f storage/logs/laravel.log | grep "ERROR.*compras"
```

## 🔧 Comandos de Administración

### Sincronización Manual:
```bash
# Sincronización básica
php artisan sincronizar:compras

# Con límite personalizado
php artisan sincronizar:compras --limite=50
```

### Ver Programación:
```bash
# Ver todos los comandos programados
php artisan schedule:list

# Probar programación manualmente
php artisan schedule:run
```

## 🚨 Monitoreo y Troubleshooting

### Verificaciones Importantes:

1. **Conexión a Valencia**:
   ```php
   DB::connection('profac_app')->getPdo()
   ```

2. **Mapeos de Productos**:
   ```sql
   SELECT COUNT(*) FROM id_zenvy_valencia WHERE tipo_dato_migrado_id = 1
   ```

3. **Compras Recientes**:
   ```sql
   SELECT * FROM compra ORDER BY id DESC LIMIT 10
   ```

### Errores Comunes:

| Error | Causa | Solución |
|-------|-------|----------|
| "Producto no encontrado" | Falta mapeo | Sincronizar productos primero |
| "Compra ya existe" | Duplicado | Normal, se omite automáticamente |
| "Error de conexión" | BD Valencia inaccesible | Verificar conexión de red |

## 📈 Rendimiento

### Optimizaciones Aplicadas:
- Agrupación de compras por `numero_factura`
- Verificación previa de existencia
- Transacciones por compra individual
- Manejo de errores granular

### Recomendaciones:
- Monitorear logs durante las primeras sincronizaciones
- Verificar que el mapeo de productos esté actualizado
- Ejecutar sincronización manual antes de producción

## 🔄 Flujo de Trabajo

1. **Usuario inicia sincronización** (manual o automática)
2. **Sistema consulta Valencia** con el script SQL
3. **Agrupa por numero_factura** para crear compras
4. **Verifica existencia** en Zenvy
5. **Crea compra nueva** si no existe
6. **Mapea productos** usando `id_zenvy_valencia`
7. **Agrega productos** a la compra
8. **Reporta estadísticas** y logs

---

**Fecha de creación**: September 2025  
**Versión**: 1.0  
**Mantenedor**: Equipo Zenvy