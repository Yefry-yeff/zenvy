# ✅ Instalación Completada - Flujo Preview + Facturar

## 📋 Resumen de Cambios

### 1. ✅ Base de Datos
Las tablas ya existían en la base de datos:
- ✅ `pedido_web` - Tabla principal de pedidos
- ✅ `pedido_web_item` - Items de cada pedido

### 2. ✅ Código Actualizado
Archivos modificados para usar los campos correctos:

**SalesService.php:**
- ✅ Método `createPreviewOrder()` - Crear pedido preview
- ✅ Método `processOrder()` - Procesar y facturar
- ✅ Campos ajustados: `codigo_pedido`, `nombre_cliente`, `sub_total`, `email_cliente`, etc.

**SalesController.php:**
- ✅ Endpoint `POST /api/v1/sales/preview`
- ✅ Endpoint `POST /api/v1/sales/{id}/process`
- ✅ Respuestas con campos correctos

**PedidoWeb.php (Modelo):**
- ✅ Tabla: `pedido_web`
- ✅ Campos fillable actualizados
- ✅ Métodos de estado ajustados

**routes/api.php:**
- ✅ Nuevas rutas agregadas

### 3. ✅ Archivo Insomnia Actualizado
**Archivo:** `Insomnia_Zenvy_API_Ventas_Preview_Flow.json`

Incluye:
- ✅ Request 1: Obtener Token JWT
- ✅ Request 2: 📝 Crear Pedido Preview (NUEVO)
- ✅ Request 3: ✅ Procesar/Facturar Pedido (NUEVO)
- ✅ Request 4: Crear Venta Directa (DEPRECADO)
- ✅ Request 5: Consultar Venta por ID
- ✅ Request 6: Anular Venta
- ✅ Request 0: Health Check

---

## 🚀 Cómo Usar

### Importar en Insomnia
1. Abrir Insomnia
2. Click en "Application" → "Preferences" → "Data" → "Import Data"
3. Seleccionar: `Insomnia_Zenvy_API_Ventas_Preview_Flow.json`
4. Click "Import"

### Configurar Environment
1. En Insomnia, abrir el workspace importado
2. Click en el dropdown de environments (esquina superior izquierda)
3. Editar "Base Environment"
4. Configurar:
   ```json
   {
     "base_url": "http://127.0.0.1:8000",
     "api_key": "pk_LhkEAssdwNMPjJMc3gqJL2TtIX96mi1s",
     "api_secret": "sk_3fXa0kouB9l0Qiw1mLYoIcZKRX6WbIfLykKCLK5tT2fKKqPOYoQs7mOeS7tP1LcZ",
     "token": "",
     "pedido_id": ""
   }
   ```

### Flujo de Prueba
1. **Ejecutar:** "1. Obtener Token JWT"
   - Copiar el token de la respuesta
   - Pegarlo en la variable `token` del Environment

2. **Ejecutar:** "2. 📝 Crear Pedido Preview"
   - Verificar respuesta exitosa
   - Copiar el `pedido_id` de la respuesta
   - Pegarlo en la variable `pedido_id` del Environment

3. **Ejecutar:** "3. ✅ Procesar/Facturar Pedido"
   - Verificar que se generó la factura
   - El stock se descuenta en este paso

---

## 📊 Verificación en Base de Datos

```sql
-- Ver último pedido creado
SELECT * FROM pedido_web ORDER BY id DESC LIMIT 1;

-- Ver items del pedido
SELECT 
    pw.codigo_pedido,
    pw.nombre_cliente,
    pw.total,
    pw.estado,
    pwi.nombre_producto,
    pwi.cantidad,
    pwi.precio_unidad,
    pwi.total as item_total
FROM pedido_web pw
JOIN pedido_web_item pwi ON pw.id = pwi.pedido_web_id
ORDER BY pw.id DESC, pwi.id ASC
LIMIT 10;

-- Ver pedidos pendientes
SELECT id, codigo_pedido, nombre_cliente, total, estado, fecha_pedido
FROM pedido_web
WHERE estado = 'pendiente'
ORDER BY fecha_pedido DESC;

-- Ver pedidos facturados
SELECT 
    pw.codigo_pedido,
    pw.nombre_cliente,
    pw.total,
    pw.estado,
    f.id as factura_id,
    f.fecha_emision
FROM pedido_web pw
JOIN factura f ON pw.factura_id = f.id
WHERE pw.estado = 'facturado'
ORDER BY pw.fecha_pedido DESC;
```

---

## 🎯 Diferencias Clave

### Flujo ANTIGUO (Deprecado)
```
POST /api/v1/sales
↓
❌ Factura directamente
❌ Error de truncamiento en comentario
❌ No hay preview
```

### Flujo NUEVO ✅
```
POST /api/v1/sales/preview
↓
✅ Crea pedido preview
✅ Valida stock
✅ NO factura

POST /api/v1/sales/{id}/process
↓
✅ Procesa y factura
✅ Descuenta stock
✅ Comentario optimizado (sin error)
```

---

## 🔧 Estructura de Datos

### Request: Crear Preview
```json
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

### Response: Preview Creado
```json
{
  "success": true,
  "data": {
    "pedido_id": 1,
    "codigo_pedido": "PW-2026-0001",
    "estado": "pendiente",
    "cliente": "Johann Ruiz",
    "email": "johann_ruiz14@hotmail.com",
    "telefono": "+50497525987",
    "total": 1667.5,
    "subtotal": 1450.0,
    "isv": 217.5,
    "descuento": 0.0,
    "items_count": 1,
    "fecha_pedido": "2026-01-14T10:30:00-06:00"
  },
  "message": "Pedido creado exitosamente. Use el endpoint /process para facturar."
}
```

### Response: Pedido Procesado
```json
{
  "success": true,
  "data": {
    "factura_id": 1264,
    "pedido_id": 1,
    "cliente": "Johann Ruiz",
    "total": 1667.5,
    "subtotal": 1450.0,
    "isv": 217.5,
    "fecha_emision": "2026-01-14 10:35:20",
    "estado": 1
  },
  "message": "Pedido facturado exitosamente"
}
```

---

## 📝 Campos de la Tabla pedido_web

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | ID único |
| codigo_pedido | VARCHAR(50) | Código único (PW-YYYY-XXXX) |
| external_order_id | VARCHAR(100) | ID del e-commerce |
| api_client_id | INT | ID del cliente API |
| nombre_cliente | VARCHAR(255) | Nombre del cliente |
| email_cliente | VARCHAR(255) | Email |
| telefono_cliente | VARCHAR(50) | Teléfono |
| rtn_cliente | VARCHAR(50) | RTN (opcional) |
| sub_total | DECIMAL(12,2) | Subtotal |
| isv | DECIMAL(12,2) | Impuesto (15%) |
| monto_descuento | DECIMAL(12,2) | Descuento |
| total | DECIMAL(12,2) | Total a pagar |
| tipo_entrega | ENUM | retiro_tienda, domicilio |
| direccion_envio | TEXT | Dirección completa |
| metodo_pago | VARCHAR(100) | Método de pago |
| notas_cliente | VARCHAR(500) | Notas del cliente |
| comentario_interno | TEXT | Comentarios internos |
| estado | ENUM | pendiente, procesando, facturado, cancelado |
| factura_id | INT | ID de factura (NULL si no procesado) |
| fecha_pedido | DATETIME | Fecha de creación |
| fecha_procesado | DATETIME | Fecha de procesamiento |
| users_id | INT | Usuario que procesa |

---

## ✅ Checklist Final

- [x] Tablas de BD verificadas
- [x] Código actualizado con campos correctos
- [x] Archivo Insomnia creado y actualizado
- [x] Documentación completa
- [x] Ejemplos de uso incluidos
- [x] Consultas SQL de verificación

---

## 🎉 ¡Listo para Usar!

Todo está configurado y listo. Ahora puedes:
1. ✅ Importar la colección en Insomnia
2. ✅ Configurar el environment
3. ✅ Probar el nuevo flujo
4. ✅ Integrar en tu e-commerce

**Archivos importantes:**
- `Insomnia_Zenvy_API_Ventas_Preview_Flow.json` - Colección actualizada de Insomnia
- `test_preview_flow.html` - Cliente HTML para pruebas
- `DOCUMENTACION_FLUJO_VENTAS_PREVIEW.md` - Documentación completa
- `INSTALACION_PREVIEW_FLOW.md` - Guía de instalación

---

**Fecha de instalación:** 14 de enero de 2026
