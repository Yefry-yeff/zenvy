# 🚀 Módulo de APIs y Webhooks - Zenvy POS

Sistema completo de gestión de APIs y Webhooks para sincronización con servicios externos y ecommerce.

## 📋 Características

- ✅ **Gestión de Webhooks Outgoing** - Sincronización de inventario con tiendas online
- ✅ **Webhooks Incoming** - Recepción de pedidos y actualizaciones desde servicios externos
- ✅ **Sistema de Logs** - Registro completo de todas las operaciones de webhook
- ✅ **Gestión de API Keys** - Control de acceso mediante claves de API
- ✅ **Panel de Administración** - Interface Livewire completa para configuración
- ✅ **Estadísticas en Tiempo Real** - Monitoreo de webhooks y rendimiento
- ✅ **Sistema de Reintentos** - Manejo automático de fallos con reintentos
- ✅ **Cola de Trabajos** - Envío asincrónico de webhooks
- ✅ **Comandos de Artisan** - Herramientas de línea de comandos

## 🛠️ Instalación

### 1. Ejecutar Migraciones

```bash
php artisan migrate
```

Esto creará las siguientes tablas:
- `webhook_logs` - Registro de todos los webhooks enviados y recibidos
- `api_keys` - Claves de API para autenticación

### 2. Configurar Variables de Entorno

Agrega estas variables a tu archivo `.env`:

```env
# Webhooks Outgoing (Enviar datos a servicios externos)
WEBHOOK_ENABLED=true
WEBHOOK_URL=https://tu-ecommerce.com/api/webhooks/inventario
WEBHOOK_TOKEN=tu_token_secreto_aqui
WEBHOOK_TIMEOUT=5
WEBHOOK_RETRY_ATTEMPTS=3

# Cola de Trabajos (Recomendado para producción)
QUEUE_CONNECTION=database
```

### 3. Configurar Cola de Trabajos (Opcional pero Recomendado)

Para mejor rendimiento en producción:

```bash
# Crear tabla de trabajos
php artisan queue:table
php artisan migrate

# Iniciar worker (en producción usar supervisor)
php artisan queue:work --queue=webhooks,default
```

### 4. Acceder al Panel de Administración

Navega a: `/configuracion/apis`

O agrega un enlace en tu menú:
```blade
<a href="{{ route('configuracion.apis') }}">
    APIs y Webhooks
</a>
```

## 📡 Webhooks Outgoing (Salida)

### Eventos Disponibles

El sistema envía webhooks automáticamente cuando ocurren estos eventos:

#### 1. Stock Actualizado
```json
{
  "evento": "inventario.stock_actualizado",
  "timestamp": "2026-02-12T10:30:00Z",
  "producto": {
    "id": 123,
    "nombre": "Producto Ejemplo",
    "stock_anterior": 50,
    "stock_actual": 45,
    "cambio": -5,
    "razon": "venta"
  },
  "detalles": {}
}
```

#### 2. Compra Recibida
```json
{
  "evento": "inventario.compra_recibida",
  "timestamp": "2026-02-12T10:30:00Z",
  "producto": {
    "id": 123,
    "nombre": "Producto Ejemplo",
    "cantidad_ingresada": 100
  },
  "detalles": {
    "numero_compra": "C-001",
    "proveedor": "Proveedor ABC"
  }
}
```

#### 3. Venta Realizada
```json
{
  "evento": "inventario.venta_realizada",
  "timestamp": "2026-02-12T10:30:00Z",
  "factura": {
    "id": 456,
    "total": 150.00,
    "cantidad_items": 3
  },
  "productos_vendidos": [
    {
      "id": 123,
      "nombre": "Producto 1",
      "cantidad": 2,
      "precio": 50.00
    }
  ],
  "detalles": {}
}
```

#### 4. Factura Anulada
```json
{
  "evento": "inventario.factura_anulada",
  "timestamp": "2026-02-12T10:30:00Z",
  "factura": {
    "id": 456,
    "cantidad_items_restaurados": 3
  },
  "productos_restaurados": [],
  "detalles": {}
}
```

### Uso en Código

```php
use App\Services\WebInventorySyncService;

$syncService = app(WebInventorySyncService::class);

// Sincronizar cambio de stock
$syncService->sincronizarCambioStock(
    productoId: 123,
    nombreProducto: 'Producto Ejemplo',
    stockAnterior: 100,
    stockActual: 95,
    razon: 'venta',
    detalles: ['factura_id' => 456]
);

// Sincronizar compra recibida
$syncService->sincronizarCompraRecibida(
    productoId: 123,
    nombreProducto: 'Producto Ejemplo',
    cantidadRecibida: 100,
    detalles: ['numero_compra' => 'C-001']
);

// Sincronizar venta
$syncService->sincronizarVenta(
    facturaId: 456,
    items: [
        ['id' => 123, 'nombre' => 'Producto 1', 'cantidad' => 2]
    ],
    total: 150.00,
    detalles: []
);
```

## 📥 Webhooks Incoming (Entrada)

### Endpoints Disponibles

#### 1. Recibir Pedido
**POST** `/api/webhooks/order`

Headers:
```
Authorization: Bearer {TU_TOKEN}
Content-Type: application/json
```

Payload:
```json
{
  "pedido_id": "PED-001",
  "cliente": {
    "nombre": "Juan Pérez",
    "email": "juan@example.com"
  },
  "items": [
    {
      "producto_id": 123,
      "cantidad": 2,
      "precio": 50.00
    }
  ],
  "total": 100.00
}
```

#### 2. Sincronizar Producto
**POST** `/api/webhooks/product-sync`

Payload:
```json
{
  "action": "update",
  "producto": {
    "id": 123,
    "nombre": "Producto Actualizado",
    "precio": 75.00
  }
}
```

#### 3. Webhook Genérico
**POST** `/api/webhooks/generic`

Acepta cualquier payload JSON válido.

## 🔑 Gestión de API Keys

### Crear API Key

```php
use App\Models\ApiKey;

$apiKey = ApiKey::generar(
    nombre: 'API Key Ecommerce',
    usuarioId: auth()->id(),
    permisos: ['read_products', 'write_inventory'],
    descripcion: 'API key para integración con ecommerce'
);
```

### Validar API Key

```php
$apiKey = ApiKey::verificar($key);

if ($apiKey && $apiKey->tienePermiso('write_inventory')) {
    // Procesar acción
}
```

### Usar API Key en Requests

```bash
curl -X GET https://tu-dominio.com/api/v1/inventory \
  -H "X-API-Key: zenvy_tu_api_key_aqui"
```

O con Bearer token:
```bash
curl -X GET https://tu-dominio.com/api/v1/inventory \
  -H "Authorization: Bearer zenvy_tu_api_key_aqui"
```

## 📊 Panel de Administración

El panel de administración permite:

### Tab: Webhooks
- Configurar URL y token
- Activar/desactivar webhooks
- Configurar timeout y reintentos
- Generar tokens de seguridad
- Probar conexión

### Tab: Estadísticas
- Total de webhooks enviados
- Webhooks exitosos y fallidos
- Actividad de la última hora
- Tiempo promedio de respuesta
- Tasa de éxito

### Tab: Logs
- Ver logs en tiempo real
- Filtrar por tipo (exitoso, error, warning)
- Limpiar logs antiguos
- Exportar logs

### Tab: Documentación
- Guía rápida de uso
- Ejemplos de payload
- Formatos de autenticación

## 🛠️ Comandos de Artisan

### Probar Webhook

```bash
# Probar con configuración del .env
php artisan webhooks:test

# Probar con URL personalizada
php artisan webhooks:test --url=https://test.com/webhook --token=mi_token

# Probar evento específico
php artisan webhooks:test --evento=test.custom
```

### Limpiar Logs Antiguos

```bash
# Limpiar logs mayores a 30 días (por defecto)
php artisan webhooks:clean-logs

# Limpiar logs mayores a 7 días
php artisan webhooks:clean-logs --days=7

# Forzar sin confirmación
php artisan webhooks:clean-logs --days=7 --force
```

### Trabajar Cola de Webhooks

```bash
# Procesar cola de webhooks
php artisan queue:work --queue=webhooks

# Con timeout y reintentos
php artisan queue:work --queue=webhooks --timeout=60 --tries=3
```

## 🔒 Seguridad

### Autenticación de Webhooks Outgoing
- Usa token Bearer para autenticación
- Los tokens deben ser secretos y únicos
- Rota tokens periódicamente

### Validación de Webhooks Incoming
- Valida el token en cada request
- Registra intentos de acceso no autorizado
- Implementa rate limiting

### Buenas Prácticas
```php
// ✅ Correcto: Token en variable de entorno
WEBHOOK_TOKEN=WmVudnktUE9TLTIwMjYwMTIzMjMzNTMxLTFjQTZHOWI4UlRTVVZMQ0Qwcm01d096ZHRmam9CYU1J

// ❌ Incorrecto: Token hardcodeado
$token = 'mi_token_secreto';
```

## 📈 Monitoreo y Logs

### Ver Logs en Tiempo Real

```bash
# Laravel logs
tail -f storage/logs/laravel.log | grep WEBHOOK

# Cola de trabajos
tail -f storage/logs/laravel.log | grep "Job de webhook"
```

### Logs en Base de Datos

Todos los webhooks se registran en la tabla `webhook_logs`:

```php
use App\Models\WebhookLog;

// Logs recientes
$logs = WebhookLog::recientes(24)->get();

// Logs exitosos
$exitosos = WebhookLog::exitosos()->count();

// Logs fallidos
$fallidos = WebhookLog::fallidos()->count();

// Estadísticas
$stats = WebhookLog::estadisticas(7); // Últimos 7 días
```

## 🔧 Troubleshooting

### Webhook no se envía

1. Verificar configuración en `.env`:
   ```bash
   php artisan tinker
   >>> config('app.webhook_url')
   >>> config('app.webhook_token')
   ```

2. Verificar logs:
   ```bash
   tail -f storage/logs/laravel.log | grep "WEBHOOK\|webhook"
   ```

3. Probar manualmente:
   ```bash
   php artisan webhooks:test
   ```

### Cola no procesa trabajos

1. Verificar configuración de cola:
   ```bash
   php artisan config:cache
   php artisan queue:restart
   ```

2. Iniciar worker:
   ```bash
   php artisan queue:work --queue=webhooks
   ```

### Webhooks duplicados

El sistema usa cache para prevenir duplicados. Si persiste:

```bash
php artisan cache:clear
```

## 📚 Recursos Adicionales

- [Documentación de Laravel Queues](https://laravel.com/docs/queues)
- [Documentación de HTTP Client](https://laravel.com/docs/http-client)
- [Livewire Documentation](https://livewire.laravel.com)

## 🤝 Soporte

Para bugs o mejoras, contacta al equipo de desarrollo.

---

**Versión:** 1.0.0  
**Última actualización:** 2026-02-12  
**Desarrollado para:** Zenvy POS
