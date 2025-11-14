# Reporte de Conversión de Unidades

## Consultas SQL para obtener reportes de conversión de unidades

### 1. **Todas las conversiones de unidades (Completa)**
```sql
SELECT 
    cu.id as conversion_id,
    p.nombre as producto,
    p.codigo_barra,
    rb_orig.id as registro_original,
    rb_nuevo.id as registro_nuevo,
    cu.cantidad_rebajada,
    cu.cantidad_convertir,
    um1.nombre as unidad_original,
    um2.nombre as unidad_nueva,
    u.name as usuario,
    cu.created_at as fecha_conversion,
    sec.descripcion as seccion
FROM cambio_unidades cu
JOIN recibido_bodega rb_orig ON cu.recibido_bodega_id_original = rb_orig.id
JOIN recibido_bodega rb_nuevo ON cu.recibido_bodega_id_cambio = rb_nuevo.id
JOIN producto p ON rb_orig.producto_id = p.id
JOIN users u ON cu.users_id = u.id
LEFT JOIN unidad_medida um1 ON rb_orig.unidad_compra_id = um1.id
LEFT JOIN unidad_medida um2 ON rb_nuevo.unidad_compra_id = um2.id
LEFT JOIN seccion sec ON rb_orig.seccion_id = sec.id
ORDER BY cu.created_at DESC;
```

### 2. **Conversiones por usuario**
```sql
SELECT 
    u.name as usuario,
    COUNT(*) as total_conversiones,
    SUM(cu.cantidad_rebajada) as cantidad_rebajada_total,
    SUM(cu.cantidad_convertir) as cantidad_convertida_total,
    MAX(cu.created_at) as ultima_conversion
FROM cambio_unidades cu
JOIN users u ON cu.users_id = u.id
GROUP BY cu.users_id, u.name
ORDER BY total_conversiones DESC;
```

### 3. **Conversiones por producto**
```sql
SELECT 
    p.nombre as producto,
    p.codigo_barra,
    COUNT(*) as total_conversiones,
    SUM(cu.cantidad_rebajada) as cantidad_rebajada_total,
    SUM(cu.cantidad_convertir) as cantidad_convertida_total,
    MAX(cu.created_at) as ultima_conversion
FROM cambio_unidades cu
JOIN recibido_bodega rb ON cu.recibido_bodega_id_original = rb.id
JOIN producto p ON rb.producto_id = p.id
GROUP BY p.id, p.nombre, p.codigo_barra
ORDER BY total_conversiones DESC;
```

### 4. **Conversiones en un rango de fechas**
```sql
SELECT 
    DATE(cu.created_at) as fecha,
    COUNT(*) as total_conversiones,
    COUNT(DISTINCT cu.users_id) as usuarios_involucrados,
    COUNT(DISTINCT p.id) as productos_diferentes
FROM cambio_unidades cu
JOIN recibido_bodega rb ON cu.recibido_bodega_id_original = rb.id
JOIN producto p ON rb.producto_id = p.id
WHERE DATE(cu.created_at) BETWEEN '2025-11-01' AND CURDATE()
GROUP BY DATE(cu.created_at)
ORDER BY fecha DESC;
```

### 5. **Desde la tabla BITACORA - Conversiones de unidades**
```sql
SELECT 
    b.id as bitacora_id,
    u.name as usuario,
    b.accion,
    b.descripcion,
    b.datosAnteriores,
    b.datosNuevos,
    b.created_at as fecha
FROM bitacora b
JOIN users u ON b.users_id = u.id
WHERE b.accion = 'conversion_unidad' 
   OR b.tablaReferencia = 'recibido_bodega'
   OR b.descripcion LIKE '%conversión%'
ORDER BY b.created_at DESC;
```

### 6. **Resumen de cambios por módulo desde BITACORA**
```sql
SELECT 
    b.modulo,
    b.accion,
    COUNT(*) as total_acciones,
    COUNT(DISTINCT b.users_id) as usuarios,
    MAX(b.created_at) as ultima_accion
FROM bitacora b
GROUP BY b.modulo, b.accion
ORDER BY total_acciones DESC;
```

### 7. **Historial completo de conversiones con detalles de bitácora**
```sql
SELECT 
    cu.id as cambio_id,
    p.nombre as producto,
    cu.cantidad_rebajada,
    cu.cantidad_convertir,
    u.name as usuario,
    cu.created_at,
    b.accion as accion_bitacora,
    b.descripcion
FROM cambio_unidades cu
JOIN recibido_bodega rb ON cu.recibido_bodega_id_original = rb.id
JOIN producto p ON rb.producto_id = p.id
JOIN users u ON cu.users_id = u.id
LEFT JOIN bitacora b ON b.idReferencia = cu.id 
    AND b.tablaReferencia = 'recibido_bodega'
    AND b.accion = 'conversion_unidad'
ORDER BY cu.created_at DESC;
```

---

## Cómo usar con la IA

Puedes hacer preguntas como:

- **"Muéstrame todas las conversiones de unidades realizadas"**
- **"¿Cuántas conversiones de unidades se han hecho por cada usuario?"**
- **"¿Qué productos han tenido más conversiones de unidades?"**
- **"Conversiones de unidades en los últimos 30 días"**
- **"Historial de cambios en el inventario desde la bitácora"**
- **"Acciones realizadas en recibido_bodega"**

La IA automáticamente generará la consulta SQL adecuada basándose en tu pregunta.
