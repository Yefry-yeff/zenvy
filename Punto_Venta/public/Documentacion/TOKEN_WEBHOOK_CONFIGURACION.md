# 🔑 Configuración del Token para Webhooks de Inventario

## 📋 Token Actual

```
WmVudnktUE9TLTIwMjYwMTIzMjMzNTMxLTFjQTZHOWI4UlRTVVZMQ0Qwcm01d096ZHRmam9CYU1J
```

**Este es el token que debes usar en AMBOS sistemas.**

---

## 🔧 Configuración en Zenvy (POS)

### Ubicación
Archivo: `Punto_Venta/.env`

### Configuración
```env
WEBHOOK_TOKEN=WmVudnktUE9TLTIwMjYwMTIzMjMzNTMxLTFjQTZHOWI4UlRTVVZMQ0Qwcm01d096ZHRmam9CYU1J
```

✅ **Ya está configurado correctamente**

---

## 🌐 Configuración en el Ecommerce

### Ubicación
Archivo: `.env` del proyecto ecommerce

### Configuración Requerida

Agrega esta línea en tu `.env` del ecommerce:

```env
# Token para validar webhooks de inventario desde el POS
WEBHOOK_INVENTORY_TOKEN=WmVudnktUE9TLTIwMjYwMTIzMjMzNTMxLTFjQTZHOWI4UlRTVVZMQ0Qwcm01d096ZHRmam9CYU1J
```

O si ya tienes una variable diferente, actualízala con este token.

### Verificar en el Controller

En tu controlador que recibe el webhook (ejemplo: `WebhookInventoryController.php`), debe validar así:

```php
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class WebhookInventoryController extends Controller
{
    public function handle(Request $request)
    {
        // VALIDAR TOKEN
        $token = $request->bearerToken();
        $tokenEsperado = env('WEBHOOK_INVENTORY_TOKEN');
        
        if ($token !== $tokenEsperado) {
            Log::warning('⚠️ Webhook rechazado - Token inválido', [
                'token_recibido' => substr($token, 0, 20) . '...',
                'ip' => $request->ip()
            ]);
            
            return response()->json([
                'error' => 'Token inválido'
            ], 401);
        }
        
        // TOKEN VÁLIDO - Procesar webhook
        $evento = $request->input('evento');
        $producto = $request->input('producto');
        
        Log::info('✅ Webhook recibido', [
            'evento' => $evento,
            'producto_id' => $producto['id'] ?? null,
            'ip' => $request->ip()
        ]);
        
        // Aquí va tu lógica para actualizar el inventario
        // Por ejemplo:
        if ($evento === 'inventario.stock_actualizado') {
            $this->actualizarStock($producto);
        }
        
        return response()->json([
            'success' => true,
            'evento' => $evento
        ]);
    }
    
    protected function actualizarStock($producto)
    {
        // Actualizar el stock en tu base de datos del ecommerce
        \DB::table('productos')
            ->where('zenvy_id', $producto['id'])
            ->update([
                'stock' => $producto['stock_actual'],
                'updated_at' => now()
            ]);
    }
}
```

---

## 🎯 Pasos para Corregir el Error "Token Inválido"

### 1. Copiar el token exacto

```
WmVudnktUE9TLTIwMjYwMTIzMjMzNTMxLTFjQTZHOWI4UlRTVVZMQ0Qwcm01d096ZHRmam9CYU1J
```

### 2. Configurar en tu ecommerce

Abre el archivo `.env` de tu proyecto ecommerce y agrega/actualiza:

```env
WEBHOOK_INVENTORY_TOKEN=WmVudnktUE9TLTIwMjYwMTIzMjMzNTMxLTFjQTZHOWI4UlRTVVZMQ0Qwcm01d096ZHRmam9CYU1J
```

### 3. Limpiar cache (importante)

```bash
cd /ruta/de/tu/ecommerce
php artisan config:cache
php artisan config:clear
```

### 4. Verificar en el código

Asegúrate de que tu controlador valide el token correctamente:

```php
$token = $request->bearerToken();
$tokenEsperado = env('WEBHOOK_INVENTORY_TOKEN');

if ($token !== $tokenEsperado) {
    return response()->json(['error' => 'Token inválido'], 401);
}
```

### 5. Probar nuevamente

Ejecuta una venta en Zenvy o usa el comando de prueba:

```bash
cd Punto_Venta
php artisan webhook:test-inventory stock
```

---

## 🔐 ¿Qué es este Token?

Este token es una cadena única de 84 caracteres codificada en Base64 que funciona como:

- **Autenticación**: Verifica que el webhook viene desde tu sistema POS
- **Seguridad**: Evita que terceros envíen webhooks falsos
- **Identificación**: Confirma que la petición es legítima

### Decodificado (información)
```
Zenvy-POS-20260123233531-1cA6G9b8RTSUVLCDorm5wOzdtfjoBaMI
```

Contiene:
- Sistema: Zenvy-POS
- Timestamp: 20260123233531
- Código aleatorio: 1cA6G9b8RTSUVLCDorm5wOzdtfjoBaMI

---

## 🔄 ¿Cada Cuánto se Actualiza?

### ⏰ Respuesta: **NUNCA automáticamente**

Este token:
- ✅ **NO caduca**
- ✅ **NO se renueva solo**
- ✅ **Puedes usarlo indefinidamente**
- ⚠️ Solo debes cambiarlo si:
  - Crees que fue comprometido
  - Quieres mejorar la seguridad
  - Necesitas regenerarlo por alguna razón

### Si necesitas generar un nuevo token:

```bash
# Opción 1: Usar base64 de PHP
echo -n "Zenvy-POS-$(date +%Y%m%d%H%M%S)-$(openssl rand -hex 16)" | base64

# Opción 2: PowerShell
$texto = "Zenvy-POS-$(Get-Date -Format 'yyyyMMddHHmmss')-$([guid]::NewGuid().ToString().Replace('-','').Substring(0,32))"
[Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($texto))

# Opción 3: Online
# Visita: https://www.base64encode.org/
# Codifica algo como: Zenvy-POS-20260126-tucodigorandom12345678
```

---

## ✅ Verificación Rápida

### Comando para verificar que el token está configurado:

**En Zenvy (POS):**
```powershell
cd Punto_Venta
Get-Content .env | Select-String "WEBHOOK_TOKEN"
```

**En Ecommerce:**
```bash
cat .env | grep WEBHOOK
```

### Deben mostrar el MISMO token:
```
WmVudnktUE9TLTIwMjYwMTIzMjMzNTMxLTFjQTZHOWI4UlRTVVZMQ0Qwcm01d096ZHRmam9CYU1J
```

---

## 🐛 Troubleshooting

### Error: "Token inválido"

**Causas comunes:**
1. ❌ Token diferente en POS y ecommerce
2. ❌ Espacios antes/después del token
3. ❌ Token no está en el `.env` del ecommerce
4. ❌ No se limpió el cache de configuración
5. ❌ El controller no valida correctamente

**Solución:**
1. Verificar que el token sea idéntico en ambos `.env`
2. Limpiar cache: `php artisan config:clear`
3. Revisar el código de validación en el controller
4. Ver logs del ecommerce: `tail -f storage/logs/laravel.log`

### Error: "Webhook no llega"

**Causas comunes:**
1. ❌ URL incorrecta en `WEBHOOK_URL`
2. ❌ Servidor del ecommerce no está corriendo
3. ❌ Firewall bloqueando la conexión

**Solución:**
1. Verificar que el ecommerce esté corriendo: `http://127.0.0.1:8001`
2. Probar manualmente con el script: `.\test-webhook.ps1`
3. Revisar logs de ambos sistemas

---

## 📊 Ejemplo de Logs Correctos

### En Zenvy (POS):
```
[2026-01-26 20:00:00] local.INFO: 📦 WEBHOOK INVENTARIO - Preparando envío
[2026-01-26 20:00:00] local.INFO: 🚀 Enviando webhook en modo SYNC
[2026-01-26 20:00:00] local.INFO: ✅ Webhook enviado via socket
```

### En Ecommerce (debe aparecer):
```
[2026-01-26 20:00:00] local.INFO: ✅ Webhook recibido {"evento":"inventario.stock_actualizado"}
```

Si **NO** aparece el log del ecommerce = el token está mal configurado o el endpoint tiene un error.

---

## 💡 Recomendaciones

1. ✅ **Usa el mismo token** en ambos sistemas
2. ✅ **Guarda una copia** del token en un lugar seguro
3. ✅ **No compartas** el token públicamente
4. ✅ **Usa HTTPS** en producción
5. ✅ **Monitorea los logs** regularmente
6. ⚠️ **Cambia el token** solo si es absolutamente necesario

---

## 📚 Referencias

- [Configuración completa de webhooks](CONFIGURACION_WEBHOOKS_INVENTARIO.md)
- [Guía de monitoreo](MONITOREAR_WEBHOOKS.md)
- [Comando de prueba](Punto_Venta/app/Console/Commands/TestInventoryWebhook.php)
- [Script de prueba manual](test-webhook.ps1)

---

**Token Actual a Usar:**
```
WmVudnktUE9TLTIwMjYwMTIzMjMzNTMxLTFjQTZHOWI4UlRTVVZMQ0Qwcm01d096ZHRmam9CYU1J
```

**Configura este token EXACTAMENTE en tu ecommerce y el problema estará resuelto.** 🎯
