# IMPLEMENTACIÓN: Sincronización de Inventario Zenvy ↔ Página Web

**Fecha:** 23 de Enero 2025  
**Estado:** ✅ COMPLETADO  
**Objetivo:** Mantener inventario actualizado en tiempo real entre Zenvy y página web

---

## 📦 ARCHIVOS CREADOS/MODIFICADOS

### ✨ NUEVOS

| Archivo | Descripción |
|---------|-------------|
| `app/Services/WebInventorySyncService.php` | Servicio central de sincronización |
| `SINCRONIZACION_INVENTARIO_PAGINA_WEB.md` | Documentación completa |
| `EJEMPLO_WEBHOOK_RECEPTOR.php` | Ejemplo de cómo recibir webhooks |

### 🔧 MODIFICADOS

| Archivo | Cambios |
|---------|---------|
| `app/Livewire/Inventario/RecibirEnBodega.php` | Agrega sincronización al ingresar compra |
| `app/Livewire/SalaDeVentas/Ventas.php` | Agrega sincronización al facturar |
| `app/Http/Controllers/Api/V1/InventoryController.php` | Nuevo endpoint `/api/v1/inventory/sync/force` |

---

## 🎯 CÓMO FUNCIONA

### Flujo Automático

```
Zenvy (Punto de Venta)
    ↓
    [Se ingresa compra / Se factura producto]
    ↓
Verifica `recibido_bodega.cantidad_disponible`
    ↓
    [Cambio detectado]
    ↓
WebInventorySyncService
    ↓
    [Prepara JSON con evento]
    ↓
HTTP POST a WEBHOOK_URL
    ↓
Tu Página Web recibe evento
    ↓
Actualiza inventario en tiempo real
```

### Eventos Disparados

| Evento | Cuándo | Qué sincroniza |
|--------|--------|----------------|
| **compra_recibida** | Se ingresa producto en `RecibirEnBodega` | +stock |
| **venta_realizada** | Se factura producto en `Ventas` | -stock |
| **stock_actualizado** | Se descuenta `cantidad_disponible` | Cambio exacto |
| **factura_anulada** | Se anula factura | +stock (restaura) |
| **sincronizacion_completa** | Llamar `/api/v1/inventory/sync/force` | Todo el inventario |

---

## ⚙️ PASOS PARA ACTIVAR

### 1. Configurar Variables de Entorno

Edita `.env` en Zenvy:

```env
# Agregar estas líneas:
WEBHOOK_URL=https://tuwebsite.com/api/webhook/inventory
WEBHOOK_TOKEN=tu_token_secreto_muy_seguro_12345
```

**Dónde obtener:**
- `WEBHOOK_URL`: La URL de tu página web donde recibas webhooks
- `WEBHOOK_TOKEN`: Un token secreto que compartas entre Zenvy y tu página web

### 2. Crear Endpoint en tu Página Web

En tu página web, crea una ruta que reciba el webhook:

```php
// routes/api.php (tu página web)
Route::post('/webhook/inventory', [InventoryWebhookController::class, 'handle'])
    ->name('webhook.inventory')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
```

### 3. Implementar Receptor

Copia el código de `EJEMPLO_WEBHOOK_RECEPTOR.php` a tu página web y adáptalo a tu base de datos.

### 4. Probar la Sincronización

**Opción A: Manual (recomendado para probar)**
```bash
curl -X POST https://zenvy.local/api/v1/inventory/sync/force \
  -H "Authorization: Bearer {API_TOKEN}" \
  -H "Content-Type: application/json"
```

**Opción B: Automática**
- Ingresa un producto en Zenvy → Se sincroniza automáticamente
- Factura un producto en Zenvy → Se sincroniza automáticamente

---

## 📊 ESTRUCTURA DE DATOS

### Tabla `recibido_bodega` (Core del Sistema)

```
+-------------------+-------+------+----------+
| Campo             | Valor | ⚠️   | Toca Sync |
+-------------------+-------+------+----------+
| cantidad_compra_lote | 100  |      | NO      |
| cantidad_inicial_seccion | 100 |      | NO      |
| cantidad_disponible | 95   | ⭐   | YES     |
| estado_id         | 1     |      | Indirecto|
| fecha_recibido    | 2025-01-23 |  | NO (FIFO)|
+-------------------+-------+------+----------+
```

**Nota:** Solo `cantidad_disponible` es lo que se sincroniza a la página web.

---

## 🔐 SEGURIDAD

### Validación de Webhooks

Cada webhook incluye:

```
Authorization: Bearer {WEBHOOK_TOKEN}
X-Event-Type: inventario.compra_recibida
X-Timestamp: 2025-01-23T10:30:45Z
```

**Tu página web DEBE:**
1. ✅ Validar el header `Authorization` con tu `WEBHOOK_TOKEN`
2. ✅ Validar que el `X-Timestamp` no sea muy antiguo (evitar replay attacks)
3. ✅ Responder con HTTP 200 si procesó correctamente

---

## 📋 EJEMPLOS DE PAYLOADS

### Compra Recibida
```json
{
  "evento": "inventario.compra_recibida",
  "timestamp": "2025-01-23T10:30:45Z",
  "producto": {
    "id": 1,
    "nombre": "Monitor 24 pulgadas",
    "cantidad_ingresada": 50
  },
  "detalles": {
    "fecha_recibido": "2025-01-23",
    "seccion_id": 5
  }
}
```

### Venta Realizada
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
      "nombre": "Monitor 24 pulgadas",
      "cantidad": 5,
      "precio": 150.00
    }
  ]
}
```

### Stock Actualizado
```json
{
  "evento": "inventario.stock_actualizado",
  "timestamp": "2025-01-23T12:00:00Z",
  "producto": {
    "id": 1,
    "nombre": "Monitor 24 pulgadas",
    "stock_anterior": 100,
    "stock_actual": 95,
    "cambio": -5,
    "razon": "factura"
  }
}
```

---

## 🚀 ENDPOINT DE SINCRONIZACIÓN FORZADA

Si necesitas sincronizar TODO el inventario de golpe:

```bash
POST /api/v1/inventory/sync/force
Authorization: Bearer {API_TOKEN}
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
    "sync_status": "enviado"
  }
}
```

---

## 🐛 DEBUGGING

### Ver logs
```bash
# En servidor Zenvy
tail -f storage/logs/laravel.log | grep -i "webhook\|sync"
```

### Probar sincronización
```bash
php artisan tinker

>>> $service = app(\App\Services\WebInventorySyncService::class);
>>> $service->estaConfigurado();  // true o false
>>> $service->obtenerWebhookUrl();
```

### Verificar payload con curl
```bash
# Simular webhook recibido
curl -X POST https://tuwebsite.com/api/webhook/inventory \
  -H "Authorization: Bearer tu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "evento": "inventario.compra_recibida",
    "timestamp": "2025-01-23T10:30:45Z",
    "producto": {"id": 1, "nombre": "Producto", "cantidad_ingresada": 10}
  }'
```

---

## ⚠️ CASOS IMPORTANTES

### 1. Si webhook NO está configurado
- **Resultado:** Nada se sincroniza, solo logs de debug
- **No hay error** - El sistema funciona normal en Zenvy
- **Solución:** Agregar `WEBHOOK_URL` y `WEBHOOK_TOKEN` a `.env`

### 2. Si webhook falla
- **Resultado:** Se registra en `storage/logs/laravel.log`
- **Zenvy sigue funcionando** - Facturación no se interrumpe
- **Cache evita duplicados** - Próximo intento después de 30 segundos

### 3. FIFO en Facturación
- Zenvy SIEMPRE toma stock por fecha más antigua primero
- Esto aplica a todos los lotes del producto
- Cuando se agota, `estado_id` cambia de 1 a 2

### 4. Anulación de Facturas
- Cuando se anula, stock se restaura
- El evento `factura_anulada` se dispara automáticamente
- Tu página web debe incrementar stock

---

## 📞 REFERENCIAS DE CÓDIGO

| Componente | Ubicación |
|-----------|-----------|
| **Servicio de Sync** | `app/Services/WebInventorySyncService.php` |
| **Compra (Ingreso)** | `app/Livewire/Inventario/RecibirEnBodega.php:520-540` |
| **Facturación (Venta)** | `app/Livewire/SalaDeVentas/Ventas.php:3100-3150` |
| **Endpoint Sync** | `app/Http/Controllers/Api/V1/InventoryController.php` |
| **Modelo Recibido** | `app/Models/RecibidoBodega.php` |

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

- [x] Servicio `WebInventorySyncService.php` creado
- [x] Sincronización integrada en `RecibirEnBodega.php` (compras)
- [x] Sincronización integrada en `Ventas.php` (facturación)
- [x] Endpoint `/api/v1/inventory/sync/force` agregado
- [x] Documentación `SINCRONIZACION_INVENTARIO_PAGINA_WEB.md` creada
- [x] Ejemplo de receptor `EJEMPLO_WEBHOOK_RECEPTOR.php` creado
- [ ] ✏️ **PENDIENTE:** Configurar `WEBHOOK_URL` y `WEBHOOK_TOKEN` en `.env`
- [ ] ✏️ **PENDIENTE:** Crear endpoint en tu página web
- [ ] ✏️ **PENDIENTE:** Probar con primero ingreso de compra
- [ ] ✏️ **PENDIENTE:** Probar con primera factura

---

## 📬 PRÓXIMOS PASOS

1. **En tu servidor Zenvy (.env):**
   ```env
   WEBHOOK_URL=https://tuwebsite.com/api/webhook/inventory
   WEBHOOK_TOKEN=tu_token_secreto
   ```

2. **En tu página web:**
   - Crea endpoint POST `/api/webhook/inventory`
   - Implementa el código de `EJEMPLO_WEBHOOK_RECEPTOR.php`
   - Adapta a tu estructura de base de datos

3. **Prueba:**
   - Ingresa un producto en Zenvy
   - Verifica que tu página web reciba el webhook
   - Revisa logs en ambos servidores

---

**¡Sistema completamente implementado y listo para usar!** 🎉
