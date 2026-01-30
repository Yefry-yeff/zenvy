# 📊 Guía para Monitorear Webhooks de Inventario

## 🎯 Logs Mejorados con Emojis

Todos los logs de webhooks ahora incluyen emojis para fácil identificación visual:

### Eventos Principales:
- 📦 **WEBHOOK INVENTARIO** - Cuando se prepara un webhook para enviar
- 🔄 **JOB WEBHOOK** - Cuando la cola procesa el webhook
- ✅ **Enviado exitosamente** - Confirmación de envío
- ⚠️ **Webhook NO configurado** - Falta configuración en .env
- ❌ **Error** - Problemas en el envío

### Tipos de Eventos:
- 📦 Stock actualizado
- 📥 Compra recibida
- 💰 Venta realizada
- ❌ Factura anulada
- 🔄 Sincronización completa

---

## 🔍 Ver Logs en Tiempo Real

### Opción 1: Tail del Log (Recomendado)

En PowerShell:
```powershell
Get-Content storage/logs/laravel.log -Wait -Tail 50
```

En Git Bash / Linux:
```bash
tail -f storage/logs/laravel.log
```

### Opción 2: Filtrar Solo Webhooks

En PowerShell:
```powershell
Get-Content storage/logs/laravel.log -Wait -Tail 100 | Select-String "WEBHOOK"
```

En Git Bash / Linux:
```bash
tail -f storage/logs/laravel.log | grep "WEBHOOK"
```

### Opción 3: Ver Últimos Webhooks Enviados

En PowerShell:
```powershell
Get-Content storage/logs/laravel.log | Select-String "WEBHOOK INVENTARIO" | Select-Object -Last 20
```

En Git Bash / Linux:
```bash
grep "WEBHOOK INVENTARIO" storage/logs/laravel.log | tail -20
```

---

## 🧪 Probar Webhooks

### Comando de Prueba Rápida

```bash
# Probar cambio de stock (default)
php artisan webhook:test-inventory

# Probar compra recibida
php artisan webhook:test-inventory compra

# Probar venta
php artisan webhook:test-inventory venta

# Probar anulación
php artisan webhook:test-inventory anulacion

# Probar sincronización completa
php artisan webhook:test-inventory completo
```

### Ejemplo de Uso:

```bash
cd Punto_Venta
php artisan webhook:test-inventory stock
```

**Salida esperada:**
```
🔧 Probando Webhooks de Inventario...

✅ Webhook configurado: https://tu-ecommerce.com/api/webhooks/inventario

📦 Enviando webhook de prueba: CAMBIO DE STOCK
✅ Webhook despachado correctamente

📋 Revisa los logs para ver el resultado:
   tail -f storage/logs/laravel.log
```

---

## 📖 Ejemplos de Logs

### ✅ Webhook Configurado y Enviado Correctamente

```
[2026-01-26 10:30:15] local.INFO: 📦 WEBHOOK INVENTARIO - Preparando envío  
{
  "evento":"stock_actualizado",
  "producto_id":123,
  "producto":"Laptop Dell XPS 15",
  "stock_anterior":10,
  "stock_actual":8,
  "cambio":"-2",
  "razon":"factura",
  "webhook_url":"https://mi-tienda.com/api/webhooks/inventario"
}

[2026-01-26 10:30:15] local.INFO: ✅ Job de webhook despachado a la cola  
{
  "evento":"inventario.stock_actualizado",
  "cache_key":"stock_producto_123_factura",
  "queue":"database"
}

[2026-01-26 10:30:16] local.INFO: 🔄 JOB WEBHOOK - Procesando  
{
  "evento":"inventario.stock_actualizado",
  "cache_key":"stock_producto_123_factura",
  "url":"https://mi-tienda.com/api/webhooks/inventario"
}

[2026-01-26 10:30:16] local.INFO: ✅ JOB WEBHOOK - Enviado exitosamente  
{
  "evento":"inventario.stock_actualizado",
  "status":200,
  "duration_ms":145.23,
  "cache_key":"stock_producto_123_factura"
}
```

### ⚠️ Webhook NO Configurado

```
[2026-01-26 10:35:20] local.WARNING: ⚠️ Webhook NO configurado - WEBHOOK_URL o WEBHOOK_TOKEN faltante en .env  
{
  "producto_id":123,
  "producto":"Laptop Dell XPS 15",
  "razon":"factura",
  "stock_actual":8
}
```

### 🚀 Modo Fire-and-Forget (QUEUE_CONNECTION=sync)

```
[2026-01-26 10:40:10] local.INFO: 📦 WEBHOOK INVENTARIO - Preparando envío  
{
  "evento":"venta_realizada",
  "factura_id":456,
  "cantidad_items":2,
  "total":"2,500,000.00",
  "productos":"Laptop Dell XPS 15, Mouse Logitech"
}

[2026-01-26 10:40:10] local.INFO: 🚀 Enviando webhook en modo SYNC (fire-and-forget)  
{
  "evento":"inventario.venta_realizada",
  "metodo":"socket directo",
  "cache_key":"venta_factura_456"
}

[2026-01-26 10:40:10] local.INFO: ✅ Webhook enviado via socket (fire-and-forget)  
{
  "evento":"inventario.venta_realizada",
  "host":"mi-tienda.com",
  "port":443,
  "cache_key":"venta_factura_456"
}
```

### ❌ Error de Conexión

```
[2026-01-26 10:45:30] local.ERROR: ❌ No se pudo abrir socket para webhook  
{
  "host":"mi-tienda.com",
  "port":443,
  "errno":110,
  "error":"Connection timed out"
}
```

---

## 🔧 Verificar Configuración

### Comando en Tinker

```bash
php artisan tinker
```

```php
// Verificar configuración
config('app.webhook_url')
// Debe retornar: "https://tu-ecommerce.com/api/webhooks/inventario"

config('app.webhook_token')
// Debe retornar: "tu_token_secreto"

// Verificar servicio
$service = app(\App\Services\WebInventorySyncService::class);
$service->estaConfigurado()
// Debe retornar: true

$service->obtenerWebhookUrl()
// Debe retornar la URL configurada
```

---

## 📈 Monitorear Cola de Trabajos

### Ver trabajos en cola

```bash
# Ver trabajos pendientes
php artisan queue:monitor SendInventoryWebhook

# Procesar cola manualmente (modo verbose)
php artisan queue:work --verbose

# Ver trabajos fallidos
php artisan queue:failed

# Reintentar trabajos fallidos
php artisan queue:retry all
```

### Ejemplo de salida verbose:

```
[2026-01-26 10:50:15][1] Processing: App\Jobs\SendInventoryWebhook
[2026-01-26 10:50:15][1] Processed:  App\Jobs\SendInventoryWebhook
```

---

## 🐛 Troubleshooting

### Problema: No veo logs de webhook

**Solución:**
1. Verificar configuración:
   ```bash
   php artisan config:cache
   php artisan config:clear
   ```

2. Verificar nivel de log en `config/logging.php`:
   ```php
   'level' => env('LOG_LEVEL', 'info'), // Debe ser 'info' o 'debug'
   ```

3. Verificar que el servicio se esté llamando en tu código

---

### Problema: Webhooks duplicados

**Solución:**
- El sistema tiene protección anti-duplicados con cache de 30 segundos
- Verifica que Redis/cache esté funcionando:
  ```bash
  php artisan cache:clear
  ```

---

### Problema: La cola no procesa trabajos

**Solución:**
1. Verificar que el worker esté corriendo:
   ```bash
   php artisan queue:work
   ```

2. Verificar tabla de jobs:
   ```bash
   php artisan queue:table
   php artisan migrate
   ```

3. Ver trabajos fallidos:
   ```bash
   php artisan queue:failed
   ```

---

## 📊 Estadísticas de Webhooks

### Contar webhooks enviados hoy

En PowerShell:
```powershell
$logs = Get-Content storage/logs/laravel.log | Select-String "WEBHOOK INVENTARIO - Preparando envío"
$today = Get-Date -Format "yyyy-MM-dd"
($logs | Select-String $today).Count
```

En Linux/Git Bash:
```bash
grep "WEBHOOK INVENTARIO - Preparando envío" storage/logs/laravel.log | grep $(date +%Y-%m-%d) | wc -l
```

### Ver eventos por tipo

En PowerShell:
```powershell
Get-Content storage/logs/laravel.log | Select-String "evento" | Select-String "stock_actualizado|compra_recibida|venta_realizada" | Group-Object
```

---

## 💡 Tips Pro

### 1. Log Viewer en Tiempo Real

Instala un paquete de visualización de logs:
```bash
composer require rap2hpoutre/laravel-log-viewer
```

### 2. Notificaciones en Slack/Discord

Configura notificaciones para errores críticos:
```php
// config/logging.php
'slack' => [
    'driver' => 'slack',
    'url' => env('LOG_SLACK_WEBHOOK_URL'),
    'username' => 'Laravel Log',
    'emoji' => ':boom:',
    'level' => 'error',
],
```

### 3. Búsqueda Avanzada

Buscar por producto específico:
```bash
grep "producto_id\":123" storage/logs/laravel.log | grep WEBHOOK
```

Buscar por factura:
```bash
grep "factura_id\":456" storage/logs/laravel.log
```

Buscar errores de webhook:
```bash
grep "WEBHOOK" storage/logs/laravel.log | grep "ERROR\|WARNING"
```

---

## ✅ Checklist de Verificación

- [ ] Variables `WEBHOOK_URL` y `WEBHOOK_TOKEN` configuradas
- [ ] Nivel de log en 'info' o 'debug'
- [ ] Cola de trabajos funcionando (`php artisan queue:work`)
- [ ] Logs se están generando en `storage/logs/laravel.log`
- [ ] Webhook de prueba ejecutado exitosamente
- [ ] Endpoint del ecommerce respondiendo

---

## 📚 Referencias

- [Comando de prueba](Punto_Venta/app/Console/Commands/TestInventoryWebhook.php)
- [Servicio de webhooks](Punto_Venta/app/Services/WebInventorySyncService.php)
- [Job de envío](Punto_Venta/app/Jobs/SendInventoryWebhook.php)
- [Configuración completa](CONFIGURACION_WEBHOOKS_INVENTARIO.md)

---

¿Problemas? Busca en los logs: `📦 WEBHOOK INVENTARIO` 🔍
