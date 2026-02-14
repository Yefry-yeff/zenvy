# 📦 Configuración de Webhooks de Inventario - Zenvy Ecommerce

## 📋 Descripción General

El sistema de webhooks de inventario permite sincronizar en tiempo real los cambios de stock entre tu sistema punto de venta (Zenvy) y tu tienda ecommerce externa.

### Eventos soportados:

- ✅ **Stock actualizado**: Cambios en cantidad disponible
- ✅ **Compra recibida**: Ingreso de productos a bodega
- ✅ **Venta realizada**: Facturación y descuento de stock
- ✅ **Factura anulada**: Restauración de stock
- ✅ **Sincronización completa**: Inventario completo por categorías

---

## 🔧 Configuración Paso a Paso

### Paso 1: Configurar Variables de Entorno

Edita tu archivo `.env` y agrega las siguientes variables:

```env
# Webhooks de Inventario
WEBHOOK_URL=https://tu-ecommerce.com/api/webhooks/inventario
WEBHOOK_TOKEN=tu_token_secreto_aqui
```

**Explicación:**
- `WEBHOOK_URL`: URL completa del endpoint de tu ecommerce que recibirá las notificaciones
- `WEBHOOK_TOKEN`: Token de autenticación (Bearer) para validar las peticiones

### Paso 2: Configurar Cola de Trabajos (Opcional pero Recomendado)

Para mejor rendimiento, configura una cola real en lugar de `sync`:

```env
QUEUE_CONNECTION=database
```

Luego ejecuta las migraciones y el worker:

```bash
php artisan queue:table
php artisan migrate
php artisan queue:work
```

---

## 🌐 Implementar Endpoint en tu Ecommerce

Tu tienda ecommerce debe tener un endpoint que reciba los webhooks.

### Ejemplo en Node.js/Express:

```javascript
const express = require('express');
const app = express();

app.use(express.json());

// Middleware de autenticación
function validateWebhook(req, res, next) {
  const token = req.headers.authorization?.replace('Bearer ', '');
  
  if (token !== process.env.WEBHOOK_TOKEN) {
    return res.status(401).json({ error: 'No autorizado' });
  }
  
  next();
}

// Endpoint que recibe los webhooks
app.post('/api/webhooks/inventario', validateWebhook, async (req, res) => {
  const { evento, timestamp, producto, productos_vendidos, categorias } = req.body;
  
  console.log('Webhook recibido:', evento);
  
  try {
    switch(evento) {
      case 'inventario.stock_actualizado':
        await actualizarStockProducto(producto);
        break;
        
      case 'inventario.compra_recibida':
        await procesarCompraRecibida(producto);
        break;
        
      case 'inventario.venta_realizada':
        await procesarVenta(productos_vendidos);
        break;
        
      case 'inventario.factura_anulada':
        await restaurarStock(productos_vendidos);
        break;
        
      case 'inventario.sincronizacion_completa':
        await sincronizarInventarioCompleto(categorias);
        break;
        
      default:
        console.log('Evento desconocido:', evento);
    }
    
    // Responder rápido (el webhook es fire-and-forget)
    res.status(200).json({ success: true });
    
  } catch (error) {
    console.error('Error procesando webhook:', error);
    res.status(200).json({ success: false, error: error.message });
  }
});

// Funciones de procesamiento
async function actualizarStockProducto(producto) {
  // Actualizar stock en tu base de datos
  await db.products.update(
    { zenvy_id: producto.id },
    { stock: producto.stock_actual }
  );
}

async function procesarVenta(productos) {
  // Reducir stock de múltiples productos
  for (const item of productos) {
    await db.products.update(
      { zenvy_id: item.producto_id },
      { $inc: { stock: -item.cantidad } }
    );
  }
}

async function sincronizarInventarioCompleto(categorias) {
  // Sincronizar todo el inventario
  for (const categoria of categorias) {
    for (const producto of categoria.productos) {
      await db.products.update(
        { zenvy_id: producto.id },
        { stock: producto.stock_disponible }
      );
    }
  }
}

app.listen(3000);
```

### Ejemplo en PHP/Laravel:

```php
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InventarioWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Validar token
        $token = $request->bearerToken();
        if ($token !== config('app.webhook_token')) {
            return response()->json(['error' => 'No autorizado'], 401);
        }
        
        $evento = $request->input('evento');
        
        try {
            match($evento) {
                'inventario.stock_actualizado' => 
                    $this->actualizarStock($request->input('producto')),
                    
                'inventario.compra_recibida' => 
                    $this->procesarCompra($request->input('producto')),
                    
                'inventario.venta_realizada' => 
                    $this->procesarVenta($request->input('productos_vendidos')),
                    
                'inventario.factura_anulada' => 
                    $this->restaurarStock($request->input('productos_restaurados')),
                    
                'inventario.sincronizacion_completa' => 
                    $this->sincronizarCompleto($request->input('categorias')),
                    
                default => \Log::info("Evento desconocido: {$evento}")
            };
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            \Log::error('Error en webhook inventario', [
                'error' => $e->getMessage(),
                'evento' => $evento
            ]);
            
            return response()->json(['success' => false], 200);
        }
    }
    
    protected function actualizarStock($producto)
    {
        \DB::table('productos')
            ->where('zenvy_id', $producto['id'])
            ->update(['stock' => $producto['stock_actual']]);
    }
}
```

---

## 📊 Estructura de Payloads

### 1. Stock Actualizado

```json
{
  "evento": "inventario.stock_actualizado",
  "timestamp": "2026-01-26T10:30:00.000000Z",
  "producto": {
    "id": 123,
    "nombre": "Laptop Dell XPS 15",
    "stock_anterior": 10,
    "stock_actual": 8,
    "cambio": -2,
    "razon": "factura"
  },
  "detalles": {
    "factura_id": 456,
    "usuario": "vendedor1"
  }
}
```

### 2. Compra Recibida

```json
{
  "evento": "inventario.compra_recibida",
  "timestamp": "2026-01-26T10:30:00.000000Z",
  "producto": {
    "id": 123,
    "nombre": "Laptop Dell XPS 15",
    "cantidad_ingresada": 20
  },
  "detalles": {
    "numero_compra": "C-2026-001",
    "proveedor": "Dell Colombia"
  }
}
```

### 3. Venta Realizada

```json
{
  "evento": "inventario.venta_realizada",
  "timestamp": "2026-01-26T10:30:00.000000Z",
  "factura": {
    "id": 456,
    "total": 2500000,
    "cantidad_items": 2
  },
  "productos_vendidos": [
    {
      "producto_id": 123,
      "nombre": "Laptop Dell XPS 15",
      "cantidad": 1,
      "precio": 2000000
    },
    {
      "producto_id": 124,
      "nombre": "Mouse Logitech MX Master",
      "cantidad": 1,
      "precio": 500000
    }
  ],
  "detalles": {
    "cliente_id": 789,
    "vendedor": "vendedor1"
  }
}
```

### 4. Factura Anulada

```json
{
  "evento": "inventario.factura_anulada",
  "timestamp": "2026-01-26T11:00:00.000000Z",
  "factura": {
    "id": 456,
    "cantidad_items_restaurados": 2
  },
  "productos_restaurados": [
    {
      "producto_id": 123,
      "nombre": "Laptop Dell XPS 15",
      "cantidad": 1
    },
    {
      "producto_id": 124,
      "nombre": "Mouse Logitech MX Master",
      "cantidad": 1
    }
  ],
  "detalles": {
    "motivo": "Cliente devolvió productos",
    "usuario": "admin"
  }
}
```

### 5. Sincronización Completa

```json
{
  "evento": "inventario.sincronizacion_completa",
  "timestamp": "2026-01-26T12:00:00.000000Z",
  "total_categorias": 5,
  "total_productos": 150,
  "total_stock": 1250,
  "categorias": [
    {
      "id": 1,
      "nombre": "Laptops",
      "total_productos": 15,
      "stock_total": 75,
      "productos": [
        {
          "id": 123,
          "nombre": "Laptop Dell XPS 15",
          "codigo_barras": "7891234567890",
          "stock_disponible": 8,
          "precio_venta": 2000000
        }
      ]
    }
  ]
}
```

---

## 🔒 Seguridad

### Headers de Autenticación

Todas las peticiones incluyen:

```
Authorization: Bearer tu_token_secreto_aqui
Content-Type: application/json
X-Event-Type: inventario.stock_actualizado
X-Timestamp: 2026-01-26T10:30:00.000000Z
```

### Recomendaciones:

1. ✅ Usa HTTPS en producción
2. ✅ Valida el token Bearer en cada request
3. ✅ Registra los eventos recibidos (logging)
4. ✅ Implementa rate limiting si es necesario
5. ✅ Responde rápido (200 OK) para evitar timeouts

---

## 🧪 Probar Webhooks

### Método 1: Usar el Cliente JavaScript incluido

```javascript
const client = new ZenvyInventoryClient('http://localhost:8000', 'tu-token-api');

// El cliente se conecta automáticamente al API REST
// Los webhooks se disparan automáticamente desde el backend
```

### Método 2: Trigger Manual desde Laravel Tinker

```bash
php artisan tinker
```

```php
use App\Services\WebInventorySyncService;

$service = app(WebInventorySyncService::class);

// Probar cambio de stock
$service->sincronizarCambioStock(
    productoId: 123,
    nombreProducto: 'Producto de Prueba',
    stockAnterior: 10,
    stockActual: 8,
    razon: 'prueba',
    detalles: ['test' => true]
);
```

### Método 3: Usar ngrok para desarrollo local

Si tu ecommerce está en local y el POS en servidor:

```bash
ngrok http 3000
```

Usa la URL de ngrok en `WEBHOOK_URL`:
```env
WEBHOOK_URL=https://abc123.ngrok.io/api/webhooks/inventario
```

---

## 🐛 Troubleshooting

### Webhooks no se envían

**Verificar:**

1. Variables de entorno configuradas:
   ```bash
   php artisan config:cache
   php artisan config:clear
   ```

2. Ver logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. Verificar que el servicio está inyectado correctamente donde se necesite

### Webhooks duplicados

El sistema tiene protección anti-duplicados con cache de 30 segundos. Si aún ves duplicados, verifica:

- Redis/cache esté funcionando
- No hay múltiples workers procesando el mismo job

### Timeout en webhooks

Los webhooks usan timeout de 1 segundo (fire-and-forget). Si tu endpoint es lento:

1. Responde 200 OK inmediatamente
2. Procesa el webhook en background (cola)
3. No hagas operaciones pesadas en el endpoint

---

## 📈 Monitoreo

### Ver jobs en cola

```bash
php artisan queue:work --verbose
```

### Ver estadísticas

```bash
php artisan queue:monitor SendInventoryWebhook
```

### Logs importantes

```php
// Webhook enviado exitosamente
[INFO] Webhook de inventario enviado | evento: inventario.stock_actualizado

// Webhook no configurado
[DEBUG] Webhook de inventario no configurado | producto_id: 123

// Error al enviar
[ERROR] Error al despachar webhook | exception: ...
```

---

## 🚀 Ejemplo Completo de Integración

### 1. Configurar .env
```env
WEBHOOK_URL=https://mi-tienda.com/api/webhooks/inventario
WEBHOOK_TOKEN=super_secreto_12345
QUEUE_CONNECTION=database
```

### 2. Ejecutar worker
```bash
php artisan queue:work --queue=default --tries=3
```

### 3. El sistema dispara webhooks automáticamente cuando:
- Se recibe una compra en bodega
- Se factura un producto
- Se anula una factura
- Se hace un ajuste de inventario
- Se solicita sincronización completa via API

### 4. Tu ecommerce recibe y procesa
```javascript
// Tu ecommerce actualiza stock en tiempo real
console.log('Stock actualizado automáticamente');
```

---

## 📚 Referencias

- Servicio principal: [app/Services/WebInventorySyncService.php](Punto_Venta/app/Services/WebInventorySyncService.php)
- Job de envío: [app/Jobs/SendInventoryWebhook.php](Punto_Venta/app/Jobs/SendInventoryWebhook.php)
- Cliente JavaScript: [zenvy-inventory-client.js](zenvy-inventory-client.js)
- Cliente Ecommerce: [zenvy-ecommerce-api-client.js](zenvy-ecommerce-api-client.js)

---

## ✅ Checklist de Configuración

- [ ] Variables `WEBHOOK_URL` y `WEBHOOK_TOKEN` configuradas en `.env`
- [ ] Endpoint de webhook implementado en tu ecommerce
- [ ] Validación de token implementada
- [ ] HTTPS configurado en producción
- [ ] Cola de trabajos funcionando (`php artisan queue:work`)
- [ ] Logging habilitado para debugging
- [ ] Webhooks probados manualmente
- [ ] Respuesta rápida del endpoint (< 1 segundo)

---

## 💡 Consejos Pro

1. **Usa sincronización completa** al iniciar tu ecommerce por primera vez
2. **Implementa retry logic** en tu ecommerce por si falla el procesamiento
3. **Guarda los webhooks recibidos** en una tabla de auditoría
4. **Usa eventos en tu ecommerce** para notificar cambios a otros servicios
5. **Monitorea los tiempos de respuesta** de tu endpoint

---

¿Necesitas ayuda? Revisa los logs en `storage/logs/laravel.log` 🔍
