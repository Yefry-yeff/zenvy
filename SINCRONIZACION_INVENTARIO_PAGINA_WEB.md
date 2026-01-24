# Sincronización de Inventario con Página Web

## 📋 Descripción

Este sistema sincroniza automáticamente los cambios de inventario de Zenvy con tu página web en tiempo real. Se ejecuta sincronización cuando:

1. **Se ingresa un producto (Compra)** - `RecibirEnBodega.php`
2. **Se factura un producto (Venta)** - `Ventas.php`
3. **Se anula una factura** - Restaura stock

---

## ⚙️ Configuración

### 1. Variables de Entorno (`.env`)

Agrega estas variables a tu archivo `.env`:

```env
WEBHOOK_URL=https://tuwebsite.com/api/webhook/inventory
WEBHOOK_TOKEN=tu_token_secreto_super_seguro
```

**Dónde configurar:**
- `WEBHOOK_URL`: URL del endpoint en tu página web que recibirá los cambios
- `WEBHOOK_TOKEN`: Token de autenticación para validar que los webhooks vienen de Zenvy

### 2. Archivo de Configuración (`config/app.php`)

El sistema lee automáticamente desde `.env`, pero si necesitas personalizar:

```php
'webhook_url' => env('WEBHOOK_URL', null),
'webhook_token' => env('WEBHOOK_TOKEN', null),
```

---

## 📡 Eventos que se Sincronizan

### 1️⃣ Compra Recibida (Ingreso de Stock)

**Cuándo ocurre:** Cuando se registra un producto en `RecibirEnBodega`

**Payload enviado:**
```json
{
  "evento": "inventario.compra_recibida",
  "timestamp": "2025-01-23T10:30:45Z",
  "producto": {
    "id": 1,
    "nombre": "Producto A",
    "cantidad_ingresada": 50
  },
  "detalles": {
    "fecha_recibido": "2025-01-23",
    "seccion_id": 1,
    "comentario": "Ingreso normal"
  }
}
```

---

### 2️⃣ Venta Realizada (Facturación)

**Cuándo ocurre:** Cuando se factura un producto (descuento de stock)

**Payload enviado:**
```json
{
  "evento": "inventario.venta_realizada",
  "timestamp": "2025-01-23T11:15:20Z",
  "factura": {
    "id": 123,
    "total": 1500.00,
    "cantidad_items": 2
  },
  "productos_vendidos": [
    {
      "id": 1,
      "nombre": "Producto A",
      "cantidad": 5,
      "precio": 150.00
    }
  ]
}
```

---

### 3️⃣ Cambio de Stock

**Cuándo ocurre:** Cuando se modifica `cantidad_disponible` en `recibido_bodega`

**Payload enviado:**
```json
{
  "evento": "inventario.stock_actualizado",
  "timestamp": "2025-01-23T12:00:00Z",
  "producto": {
    "id": 1,
    "nombre": "Producto A",
    "stock_anterior": 100,
    "stock_actual": 95,
    "cambio": -5,
    "razon": "factura"
  },
  "detalles": {
    "cantidad_vendida": 5,
    "unidad_medida_id": 1
  }
}
```

---

### 4️⃣ Anulación de Factura

**Cuándo ocurre:** Cuando se anula una factura (restaura stock)

**Payload enviado:**
```json
{
  "evento": "inventario.factura_anulada",
  "timestamp": "2025-01-23T13:45:10Z",
  "factura": {
    "id": 123,
    "cantidad_items_restaurados": 2
  },
  "productos_restaurados": [
    {
      "id": 1,
      "nombre": "Producto A",
      "cantidad": 5
    }
  ]
}
```

---

### 5️⃣ Sincronización Completa (Bajo Demanda)

**Cuándo ocurre:** Cuando llamas a `/api/v1/inventory/sync/force`

**Payload enviado:**
```json
{
  "evento": "inventario.sincronizacion_completa",
  "timestamp": "2025-01-23T14:00:00Z",
  "total_productos": 250,
  "productos": [
    {
      "categoria_id": 1,
      "categoria_nombre": "Electrónica",
      "total_productos": 50,
      "stock_total": 500,
      "productos": [
        {
          "id": 1,
          "sku": "SKU001",
          "nombre": "Producto A",
          "stock": 45,
          "precio_venta": 150.00
        }
      ]
    }
  ]
}
```

---

## 🔌 Implementación en tu Página Web

### Headers de Autenticación

Cada webhook incluye estos headers:

```
Authorization: Bearer {WEBHOOK_TOKEN}
Content-Type: application/json
X-Event-Type: {nombre_del_evento}
X-Timestamp: {timestamp_iso8601}
```

### Ejemplo de Receptor en PHP

```php
<?php
// routes/webhook.php or controller

Route::post('/api/webhook/inventory', function (Request $request) {
    // 1. Validar token
    $token = str_replace('Bearer ', '', $request->header('Authorization'));
    if ($token !== config('app.webhook_token')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    // 2. Obtener evento
    $evento = $request->input('evento');
    $timestamp = $request->input('timestamp');
    $payload = $request->input();
    
    // 3. Procesar por tipo de evento
    switch ($evento) {
        case 'inventario.compra_recibida':
            actualizarInventarioCompra($payload);
            break;
            
        case 'inventario.venta_realizada':
            actualizarInventarioVenta($payload);
            break;
            
        case 'inventario.stock_actualizado':
            actualizarStock($payload);
            break;
            
        case 'inventario.factura_anulada':
            restaurarStockAnulacion($payload);
            break;
            
        case 'inventario.sincronizacion_completa':
            sincronizacionCompleta($payload);
            break;
    }
    
    // 4. Responder exitosamente
    return response()->json(['success' => true]);
});
```

### Ejemplo de Receptor en JavaScript/Node.js

```javascript
// Express.js ejemplo
app.post('/api/webhook/inventory', (req, res) => {
    // 1. Validar token
    const token = req.headers.authorization?.replace('Bearer ', '');
    if (token !== process.env.WEBHOOK_TOKEN) {
        return res.status(401).json({ error: 'Unauthorized' });
    }
    
    // 2. Obtener evento
    const { evento, timestamp, producto, factura, productos_vendidos } = req.body;
    
    // 3. Actualizar tu base de datos/frontend
    switch (evento) {
        case 'inventario.compra_recibida':
            console.log(`Producto ${producto.nombre}: +${producto.cantidad_ingresada}`);
            actualizarEnBaseDatos(producto);
            break;
            
        case 'inventario.venta_realizada':
            console.log(`Venta #${factura.id}: ${productos_vendidos.length} productos`);
            descontarDelInventario(productos_vendidos);
            break;
    }
    
    res.json({ success: true });
});
```

---

## 🚀 Endpoint para Sincronizar Manualmente

Si necesitas forzar una sincronización completa del inventario:

**Request:**
```bash
POST /api/v1/inventory/sync/force
Authorization: Bearer {API_TOKEN}
Content-Type: application/json
```

**Response:**
```json
{
  "success": true,
  "data": {
    "synced_at": "2025-01-23T14:00:00Z",
    "total_categories": 10,
    "total_products": 250,
    "total_stock": 5000,
    "sync_status": "enviado",
    "webhook_url": "https://tuwebsite.com/api/webhook/inventory"
  }
}
```

---

## 🔍 Debugging

### Ver logs de sincronización

```bash
tail -f storage/logs/laravel.log | grep "webhook\|sincronizar\|Sync"
```

### Verificar si webhook está configurado

```bash
php artisan tinker

>>> $service = app(\App\Services\WebInventorySyncService::class);
>>> $service->estaConfigurado();  // true/false
>>> $service->obtenerWebhookUrl();
```

---

## ⚠️ Puntos Importantes

1. **El sistema NO sincroniza si no está configurado** - No habrá errores, solo logs informativos
2. **Evita duplicados** - El sistema usa cache de 30 segundos para evitar webhooks duplicados
3. **FIFO en facturación** - Se toma el stock más antiguo primero (gestión FIFO)
4. **Estado_id**: 
   - `1` = Activo (hay stock)
   - `2` = Inactivo (agotado)

---

## 📊 Tabla `recibido_bodega`

Esta es la tabla que toca el sistema:

| Campo | Descripción |
|-------|-------------|
| `cantidad_compra_lote` | Cantidad original comprada |
| `cantidad_inicial_seccion` | Cantidad inicial en sección |
| `cantidad_disponible` | ⭐ **STOCK ACTUAL** - Lo que se sincroniza |
| `estado_id` | 1=activo, 2=inactivo (agotado) |
| `fecha_recibido` | Para FIFO (primero en entrar) |

---

## 🆘 Troubleshooting

### El webhook no se envía
- ✅ Verifica que `WEBHOOK_URL` y `WEBHOOK_TOKEN` estén configurados en `.env`
- ✅ Asegúrate que tu servidor tiene conectividad HTTP outbound
- ✅ Revisa los logs: `storage/logs/laravel.log`

### El endpoint de sincronización forzada devuelve error
- ✅ Verifica que estés usando autenticación API válida
- ✅ Confirma que webhook está configurado
- ✅ Prueba con Postman o cURL

### Stock no se sincroniza en tiempo real
- ✅ Los cambios se sincronizan desde `RecibirEnBodega.php` y `Ventas.php`
- ✅ Si modificas directamente la BD, no hay sincronización
- ✅ Usa el endpoint `/api/v1/inventory/sync/force` para forzar sincronización

---

## 📞 Soporte

Para problemas o preguntas sobre la sincronización, revisa:
- `app/Services/WebInventorySyncService.php` - Lógica de sincronización
- `app/Livewire/Inventario/RecibirEnBodega.php` - Sincronización de compras
- `app/Livewire/SalaDeVentas/Ventas.php` - Sincronización de ventas
- `app/Http/Controllers/Api/V1/InventoryController.php` - Endpoint de sincronización forzada
