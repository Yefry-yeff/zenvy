# ✅ Solución al Problema de Webhooks

## 🐛 Problema Identificado

Los webhooks de inventario **NO se estaban enviando** a la página web debido a un **bug en el sistema de caché**.

### Síntoma:
```
[2026-02-12 22:02:48] local.DEBUG: Webhook duplicado descartado {"cache_key":"stock_producto_2173_factura"}
```

Los webhooks se preparaban correctamente pero se descartaban inmediatamente como "duplicados".

---

## 🔧 Causa del Problema

En el archivo `Punto_Venta/app/Services/WebInventorySyncService.php`, el método `enviarWebhookFireAndForget()` tenía una **verificación de caché duplicada**:

### Flujo anterior (INCORRECTO):
1. `enviarWebhook()` verifica caché → no existe
2. `enviarWebhook()` guarda en caché
3. Llama a `enviarWebhookFireAndForget()`
4. `enviarWebhookFireAndForget()` **verifica caché nuevamente** → ¡ahora SÍ existe!
5. **Descarta el webhook** pensando que es duplicado ❌

---

## ✅ Solución Aplicada

Se **eliminó la verificación de caché duplicada** en el método `enviarWebhookFireAndForget()`:

```php
// ANTES (INCORRECTO)
private function enviarWebhookFireAndForget(array $payload, string $cacheKey): void
{
    try {
        // Evitar duplicados
        if (Cache::has("webhook_sent_{$cacheKey}")) {
            Log::debug('Webhook duplicado descartado', ['cache_key' => $cacheKey]);
            return; // ❌ ESTO DESCARTABA TODOS LOS WEBHOOKS
        }
        
        Cache::put("webhook_sent_{$cacheKey}", true, now()->addSeconds(30));
        
        // ... resto del código
    }
}

// AHORA (CORRECTO)
private function enviarWebhookFireAndForget(array $payload, string $cacheKey): void
{
    try {
        // NO chequear cache aquí - ya se chequeó en enviarWebhook()
        
        // Parsear URL y enviar...
    }
}
```

---

## 🧪 Verificación

### 1. Limpiar Caché
```bash
cd Punto_Venta
php artisan cache:clear
```

### 2. Probar Webhook
```bash
php artisan webhook:test-inventory stock
```

### 3. Verificar Logs
```bash
# Ver últimos webhooks
Get-Content storage/logs/laravel.log -Tail 50 | Select-String "WEBHOOK" | Select-Object -Last 10
```

**Resultado esperado:**
```
[2026-02-12 22:27:21] local.INFO: ✅ Webhook enviado via socket (fire-and-forget) 
{"evento":"inventario.stock_actualizado","host":"127.0.0.1","port":8001,"cache_key":"stock_producto_999_prueba"}
```

---

## ⚙️ Configuración Actual

Tu sistema está configurado para usar webhooks en modo **SYNC (fire-and-forget)**:

```env
QUEUE_CONNECTION=sync
WEBHOOK_URL=http://127.0.0.1:8001/api/webhook/inventory
WEBHOOK_TOKEN=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### Esto significa:
- Los webhooks se envían **inmediatamente** sin esperar respuesta
- No bloquean el proceso principal
- Se usa conexión directa por socket al puerto **8001**

---

## 🚨 Verificar Servidor Web de Ecommerce

**IMPORTANTE:** Asegúrate de que tu servidor de ecommerce esté corriendo y escuchando en el puerto **8001**.

### Verificar si el puerto está abierto:
```powershell
Test-NetConnection -ComputerName 127.0.0.1 -Port 8001
```

### Si el servidor NO está corriendo:
1. Inicia tu aplicación de ecommerce
2. Verifica que escuche en `http://127.0.0.1:8001`
3. Asegúrate de que el endpoint `/api/webhook/inventory` exista
4. Valida que el token JWT esté configurado correctamente

---

## 🔍 Monitorear Webhooks en Tiempo Real

```powershell
# Ver logs en tiempo real
Get-Content storage/logs/laravel.log -Wait -Tail 50 | Select-String "WEBHOOK"
```

---

## 📊 Eventos de Webhook Soportados

| Evento | Descripción | Cuándo se dispara |
|--------|-------------|-------------------|
| `inventario.stock_actualizado` | Cambio en stock de producto | Compras, ventas, ajustes |
| `inventario.compra_recibida` | Ingreso de mercancía | Al recibir compra en bodega |
| `inventario.venta_realizada` | Productos vendidos | Al facturar |
| `inventario.factura_anulada` | Restauración de stock | Al anular factura |
| `inventario.sincronizacion_completa` | Todo el inventario | Sincronización manual |

---

## ✅ Estado Actual

- ✅ **Bug corregido** en `WebInventorySyncService.php`
- ✅ **Caché limpiado**
- ✅ **Webhooks probados** y funcionando
- ⚠️ **Verificar que el servidor de ecommerce esté corriendo en puerto 8001**

---

## 🆘 Si los Webhooks Siguen Sin Llegar

1. **Verifica que el servidor de ecommerce esté corriendo**
   ```powershell
   Test-NetConnection -ComputerName 127.0.0.1 -Port 8001
   ```

2. **Revisa los logs del servidor de ecommerce**
   - Busca errores de autenticación (token inválido)
   - Verifica que el endpoint `/api/webhook/inventory` exista

3. **Prueba envío manual**
   ```powershell
   .\test-webhook.ps1
   ```

4. **Verifica el token**
   - El token en el `.env` del POS debe coincidir con el del ecommerce
   - Token actual: `eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...`

---

## 📝 Notas Adicionales

- El sistema usa **fire-and-forget** para no bloquear las operaciones del POS
- No espera respuesta del servidor de ecommerce
- Los errores de conexión se registran en los logs pero no detienen el POS
- Se implementa sistema anti-duplicados con caché de 30 segundos

---

**Fecha de corrección:** 2026-02-12
**Archivo modificado:** `Punto_Venta/app/Services/WebInventorySyncService.php`
