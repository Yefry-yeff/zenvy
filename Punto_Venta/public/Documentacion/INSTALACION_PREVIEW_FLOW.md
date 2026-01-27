# ⚡ Instalación Rápida - Flujo Preview + Facturar

## 🎯 Objetivo

Implementar el nuevo flujo de ventas en 2 pasos que soluciona el error de truncamiento en el campo `comentario`.

---

## 📋 Requisitos Previos

- ✅ Base de datos `punto_venta` activa
- ✅ Laravel funcionando correctamente
- ✅ Acceso a MySQL/MariaDB

---

## 🚀 Instalación en 3 Pasos

### Paso 1: Verificar si las tablas ya existen

```bash
# Conectarse a MySQL
mysql -u root -p punto_venta
```

```sql
-- Verificar si las tablas existen
SHOW TABLES LIKE 'pedidos_web%';

-- Si muestran resultados, las tablas ya existen
-- Si no muestran nada, continuar al Paso 2
```

### Paso 2: Ejecutar Script SQL (solo si las tablas NO existen)

**Opción A: Desde MySQL CLI**
```bash
mysql -u root -p punto_venta < "Bases de datos/Script/crear_pedidos_web.sql"
```

**Opción B: Desde phpMyAdmin**
1. Abrir phpMyAdmin
2. Seleccionar base de datos `punto_venta`
3. Ir a pestaña "SQL"
4. Copiar contenido de `Bases de datos/Script/crear_pedidos_web.sql`
5. Click en "Ejecutar"

**Opción C: Desde MySQL Workbench**
1. Abrir MySQL Workbench
2. Conectar a la base de datos
3. File → Open SQL Script
4. Seleccionar `Bases de datos/Script/crear_pedidos_web.sql`
5. Ejecutar

### Paso 3: Probar el Flujo

**Opción A: Con HTML Test Client**
1. Abrir `test_preview_flow.html` en el navegador
2. Ingresar tu token de API
3. Completar formulario
4. Click "Crear Pedido Preview"
5. Click "Procesar y Facturar"

**Opción B: Con Postman/Insomnia**

1. **Crear Preview:**
```
POST http://localhost:8000/api/v1/sales/preview
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "customer_name": "Johann Ruiz",
  "customer_email": "johann_ruiz14@hotmail.com",
  "customer_phone": "+50497525987",
  "delivery_type": "domicilio",
  "delivery_address": "Tegucigalpa, Honduras",
  "payment_method": "Efectivo",
  "notes": "Dejar afuera",
  "subtotal": 1450.00,
  "tax": 217.50,
  "discount": 0,
  "total": 1667.50,
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "price": 725.00,
      "discount": 0
    }
  ]
}
```

2. **Procesar/Facturar:**
```
POST http://localhost:8000/api/v1/sales/{pedido_id}/process
Authorization: Bearer YOUR_TOKEN
```

---

## ✅ Verificación

### Consultas SQL para Verificar

```sql
-- Ver último pedido creado
SELECT * FROM pedidos_web ORDER BY id DESC LIMIT 1;

-- Ver items del último pedido
SELECT pw.numero_pedido, pwi.* 
FROM pedidos_web pw
JOIN pedido_web_item pwi ON pw.id = pwi.pedido_web_id
ORDER BY pw.id DESC LIMIT 10;

-- Ver pedidos pendientes
SELECT id, numero_pedido, cliente_nombre, total, estado
FROM pedidos_web
WHERE estado = 'pendiente';

-- Ver pedidos facturados con su factura
SELECT 
    pw.numero_pedido,
    pw.cliente_nombre,
    pw.total,
    pw.estado,
    f.id as factura_id
FROM pedidos_web pw
LEFT JOIN factura f ON pw.factura_id = f.id
WHERE pw.estado = 'facturado';
```

---

## 🔧 Troubleshooting

### Error: "Table pedidos_web doesn't exist"
**Solución:** Ejecutar el script SQL del Paso 2

### Error: "SQLSTATE[22001]: String data, right truncated"
**Solución:** Este error ya NO debería aparecer con el nuevo flujo. Si aparece, verificar que estés usando los endpoints correctos:
- ✅ `POST /api/v1/sales/preview` (nuevo)
- ❌ `POST /api/v1/sales` (antiguo, deprecado)

### Error: "Insufficient Stock"
**Solución:** Verificar que haya stock disponible en `recibido_bodega`:
```sql
SELECT producto_id, SUM(cantidad_disponible) as stock
FROM recibido_bodega
WHERE estado_id = 1
GROUP BY producto_id;
```

### Error: "Order not found"
**Solución:** Verificar que el `pedido_id` sea correcto:
```sql
SELECT id, numero_pedido, estado FROM pedidos_web WHERE id = ?;
```

---

## 📊 Monitoreo

### Logs de Laravel

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Buscar logs de API
grep "API:" storage/logs/laravel.log | tail -20
```

### Logs Importantes

```
[INFO] API: Pedido web creado (preview)
[INFO] API: Pedido web facturado exitosamente
[INFO] Stock descontado de bodega
```

---

## 🔄 Migración desde Flujo Antiguo

Si ya tienes código usando `POST /api/v1/sales`:

**Antes:**
```javascript
const response = await fetch('/api/v1/sales', {
  method: 'POST',
  body: JSON.stringify(datos)
});
```

**Después:**
```javascript
// 1. Crear preview
const preview = await fetch('/api/v1/sales/preview', {
  method: 'POST',
  body: JSON.stringify(datos)
});
const { pedido_id } = await preview.json();

// 2. Procesar (después de confirmar pago)
await fetch(`/api/v1/sales/${pedido_id}/process`, {
  method: 'POST'
});
```

---

## 📚 Documentación Completa

Ver archivos:
- `DOCUMENTACION_FLUJO_VENTAS_PREVIEW.md` - Documentación detallada
- `RESUMEN_SOLUCION_PREVIEW_FLOW.md` - Resumen de cambios
- `test_preview_flow.html` - Cliente de prueba interactivo

---

## ✅ Checklist Final

- [ ] Script SQL ejecutado
- [ ] Tablas creadas correctamente
- [ ] Probado con HTML test client
- [ ] Probado con Postman/Insomnia
- [ ] Verificado en base de datos
- [ ] Logs revisados
- [ ] Documentación leída

---

## 🎉 ¡Listo!

El nuevo flujo está instalado y funcionando. Ahora puedes:
1. ✅ Crear pedidos preview sin errores de truncamiento
2. ✅ Revisar pedidos antes de facturar
3. ✅ Procesar y facturar cuando estés listo
4. ✅ Descontar stock solo al facturar

---

**Última actualización:** 14 de enero de 2026
