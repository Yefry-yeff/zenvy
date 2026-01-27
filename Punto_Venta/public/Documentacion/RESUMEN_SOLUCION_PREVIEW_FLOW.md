# 🎯 Resumen de Cambios: Flujo Preview + Facturar

**Fecha:** 14 de enero de 2026  
**Branch:** PAPERLAND-MANTENIMIENTO

---

## ❌ Problema Original

```json
{
  "success": false,
  "error": {
    "code": "SALE_CREATION_FAILED",
    "message": "SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'comentario' at row 1"
  }
}
```

**Causas:**
1. Campo `comentario` de la tabla `factura` muy pequeño
2. Se intentaba guardar todo (cliente, email, teléfono, dirección, notas) en un solo campo
3. Se facturaba directamente sin preview, sin posibilidad de revisar antes

---

## ✅ Solución Implementada

### Flujo Anterior (1 paso)
```
POST /api/v1/sales → Factura directa
```

### Nuevo Flujo (2 pasos)
```
1. POST /api/v1/sales/preview → Pedido Preview (sin facturar)
2. POST /api/v1/sales/{id}/process → Facturar
```

---

## 📁 Archivos Creados/Modificados

### ✅ Creados

1. **`Bases de datos/Script/crear_pedidos_web.sql`**
   - Script SQL para crear tablas `pedido_web` y `pedido_web_item`
   - Optimizado para evitar truncamiento de datos

2. **`DOCUMENTACION_FLUJO_VENTAS_PREVIEW.md`**
   - Documentación completa del nuevo flujo
   - Ejemplos en JavaScript y PHP
   - Tabla de errores y soluciones

3. **`test_preview_flow.html`**
   - Cliente HTML interactivo para probar el flujo
   - Formulario para crear preview
   - Botón para procesar/facturar

### 🔧 Modificados

1. **`Punto_Venta/app/Services/Api/SalesService.php`**
   - Agregado: `createPreviewOrder()` - Crea pedido sin facturar
   - Agregado: `processOrder()` - Factura pedido existente
   - Agregado: `generarNumeroPedido()` - Genera código único PW-YYYY-XXXX
   - Optimizado: Comentarios cortos para evitar truncamiento

2. **`Punto_Venta/app/Http/Controllers/Api/V1/SalesController.php`**
   - Agregado: `createPreview()` - Endpoint para preview
   - Agregado: `processPedido()` - Endpoint para facturar
   - Mantenido: `store()` marcado como deprecado

3. **`Punto_Venta/routes/api.php`**
   - Nueva ruta: `POST /api/v1/sales/preview`
   - Nueva ruta: `POST /api/v1/sales/{pedidoId}/process`

---

## 🗄️ Base de Datos

### Tablas Utilizadas

#### `pedidos_web` (ya existía)
- Almacena pedidos en estado preview
- Estados: pendiente, procesando, facturado, rechazado
- Guarda información completa del cliente y entrega

#### `pedido_web_item` (ya existía)
- Items del pedido
- Precios y cantidades al momento del pedido

---

## 🚀 Cómo Usar

### Paso 1: Ejecutar Script SQL
```bash
# En MySQL
SOURCE Bases\ de\ datos/Script/crear_pedidos_web.sql;

# O manualmente
mysql -u root -p punto_venta < "Bases de datos/Script/crear_pedidos_web.sql"
```

### Paso 2: Probar con HTML
1. Abrir `test_preview_flow.html` en el navegador
2. Ingresar token de API
3. Completar formulario
4. Click en "Crear Pedido Preview"
5. Click en "Procesar y Facturar"

### Paso 3: Integrar en E-commerce

#### JavaScript
```javascript
// 1. Cliente confirma orden
const pedido = await fetch('/api/v1/sales/preview', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(datosCarrito)
});

const { pedido_id } = await pedido.json();

// 2. Después de confirmar pago
await fetch(`/api/v1/sales/${pedido_id}/process`, {
  method: 'POST',
  headers: { 'Authorization': 'Bearer TOKEN' }
});
```

---

## 📊 Ventajas del Nuevo Flujo

| Ventaja | Descripción |
|---------|-------------|
| ✅ Sin truncamiento | Comentarios optimizados para el tamaño de la columna |
| ✅ Preview antes de facturar | Cliente puede ver su pedido confirmado |
| ✅ No descuenta stock prematuro | Stock solo se descuenta al facturar |
| ✅ Validación doble | Stock validado al crear y al procesar |
| ✅ Mayor control | Permite cancelar antes de facturar |
| ✅ Mejor UX | Separación clara entre "pedido" y "factura" |

---

## 🔍 Testing

### Verificar Tablas
```sql
SHOW TABLES LIKE 'pedidos_web%';
DESCRIBE pedidos_web;
DESCRIBE pedido_web_item;
```

### Ver Pedidos Creados
```sql
SELECT id, numero_pedido, cliente_nombre, estado, total
FROM pedidos_web
ORDER BY id DESC
LIMIT 10;
```

### Ver Pedidos Pendientes
```sql
SELECT * FROM pedidos_web WHERE estado = 'pendiente';
```

---

## 📝 Endpoints API

### 1. Crear Preview
```
POST /api/v1/sales/preview
Authorization: Bearer {token}
Content-Type: application/json

Body: {datos del pedido}
Response: {pedido_id, numero_pedido, estado, ...}
```

### 2. Procesar/Facturar
```
POST /api/v1/sales/{pedido_id}/process
Authorization: Bearer {token}

Response: {factura_id, pedido_id, ...}
```

---

## ⚠️ Notas Importantes

1. **Compatibilidad**: El endpoint antiguo `POST /api/v1/sales` sigue funcionando pero está marcado como deprecado

2. **Migración**: Se recomienda actualizar integraciones para usar el nuevo flujo de 2 pasos

3. **Validación de Stock**: Se realiza dos veces:
   - Al crear el preview (verifica disponibilidad)
   - Al procesar (descuenta realmente)

4. **Comentarios**: Ahora se guardan de forma optimizada:
   - Preview: Usa campos separados en `pedidos_web`
   - Factura: Comentario corto en formato: `Pedido Web: PW-2026-0001 | Cliente: Nombre`

---

## 🐛 Solución del Error Original

**Antes:**
```php
$comentario = "Venta desde API - E-commerce\nCliente: Johann Ruiz\nEmail: ..."; // 300+ caracteres
```

**Después:**
```php
// En preview: Datos completos en tabla pedidos_web (campos separados)
// En factura: Comentario corto
$comentario = sprintf('Pedido Web: %s | Cliente: %s', $numeroPedido, $clienteNombre); // ~50 caracteres
```

---

## ✅ Checklist de Implementación

- [x] Script SQL creado
- [x] Modelos ya existían (PedidoWeb, PedidoWebItem)
- [x] SalesService actualizado
- [x] SalesController actualizado
- [x] Rutas API agregadas
- [x] Documentación completa
- [x] Cliente de prueba HTML
- [ ] Ejecutar script SQL en base de datos
- [ ] Probar flujo completo
- [ ] Actualizar integraciones existentes

---

## 📞 Próximos Pasos

1. ✅ Ejecutar `crear_pedidos_web.sql` en la base de datos
2. ✅ Probar con `test_preview_flow.html`
3. ✅ Actualizar integraciones del e-commerce
4. ✅ Revisar logs de errores
5. ✅ Monitorear rendimiento

---

**Autor:** GitHub Copilot  
**Documentación Completa:** Ver `DOCUMENTACION_FLUJO_VENTAS_PREVIEW.md`
