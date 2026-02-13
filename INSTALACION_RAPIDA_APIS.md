# 🚀 Instalación Rápida - Módulo APIs y Webhooks

Sigue estos pasos para poner en marcha el módulo de APIs y Webhooks en tu sistema Zenvy POS.

## ⚡ Pasos de Instalación

### 1. Ejecutar Migraciones

```bash
cd Punto_Venta
php artisan migrate
```

Esto creará las tablas:
- `webhook_logs` - Para registrar todos los webhooks
- `api_keys` - Para gestionar claves de API

### 2. Configurar Variables de Entorno

Copia las variables del archivo `.env.apis.example` a tu `.env`:

```bash
# Mínimo requerido para empezar
WEBHOOK_ENABLED=true
WEBHOOK_URL=https://tu-ecommerce.com/api/webhooks/inventario
WEBHOOK_TOKEN=tu_token_secreto_aqui
```

**Genera un token seguro:**
```bash
php artisan tinker
>>> echo base64_encode('Zenvy-POS-' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(16)));
```

### 3. Configurar Cola de Trabajos (Opcional pero Recomendado)

```bash
# Crear tabla de trabajos
php artisan queue:table
php artisan migrate

# Cambiar en .env
QUEUE_CONNECTION=database

# Iniciar worker (mantener corriendo)
php artisan queue:work --queue=webhooks,default
```

### 4. Probar el Sistema

```bash
# Probar conexión del webhook
php artisan webhooks:test

# Si funciona, verás: ✅ Webhook exitoso!
```

### 5. Acceder al Panel

Navega a: `http://tu-dominio.com/configuracion/apis`

O agrega al menú en `resources/views/components/menu.blade.php`:

```blade
<li>
    <a href="{{ route('configuracion.apis') }}" 
       class="nav-link">
        <i class="fas fa-code"></i>
        <span>APIs y Webhooks</span>
    </a>
</li>
```

## ✅ Verificación Post-Instalación

### Verificar Configuración

```bash
php artisan tinker
>>> config('app.webhook_url')
=> "https://tu-ecommerce.com/api/webhooks"

>>> config('app.webhook_token')  
=> "WmVudnktUE9TLTIwMjY..."

>>> \App\Models\WebhookLog::count()
=> 0
```

### Probar Envío Manual

```php
// En tinker o en tu código
use App\Services\WebInventorySyncService;

$service = app(WebInventorySyncService::class);

$service->sincronizarCambioStock(
    productoId: 1,
    nombreProducto: 'Producto de Prueba',
    stockAnterior: 100,
    stockActual: 95,
    razon: 'venta'
);
```

### Verificar Logs

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log | grep WEBHOOK

# Ver en base de datos
php artisan tinker
>>> \App\Models\WebhookLog::latest()->first()
```

## 🔧 Configuración Avanzada

### Para Producción

```env
# .env
WEBHOOK_ENABLED=true
WEBHOOK_URL=https://produccion.com/api/webhooks/inventario
WEBHOOK_TOKEN=token_super_secreto_produccion
WEBHOOK_TIMEOUT=10
WEBHOOK_RETRY_ATTEMPTS=3
QUEUE_CONNECTION=database
```

### Supervisor (Linux/Producción)

Crear archivo `/etc/supervisor/conf.d/zenvy-queue.conf`:

```ini
[program:zenvy-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/a/Punto_Venta/artisan queue:work --queue=webhooks,default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/zenvy-queue-worker.log
stopwaitsecs=3600
```

Luego:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start zenvy-queue-worker:*
```

### Tarea Cron para Limpieza

Agregar a crontab:

```bash
# Limpiar logs de webhooks cada semana
0 2 * * 0 cd /ruta/a/Punto_Venta && php artisan webhooks:clean-logs --days=30 --force
```

## 🐛 Solución de Problemas Comunes

### Problema: "Webhook NO configurado"

**Solución:**
```bash
# Limpiar cache de configuración
php artisan config:clear
php artisan config:cache

# Verificar que las variables estén en .env
cat .env | grep WEBHOOK
```

### Problema: "Error de conexión al webhook"

**Solución:**
```bash
# Verificar URL es accesible
curl -I https://tu-webhook-url.com

# Probar con comando
php artisan webhooks:test --url=https://tu-url.com --token=tu-token
```

### Problema: "Cola no procesa trabajos"

**Solución:**
```bash
# Reiniciar cola
php artisan queue:restart

# Verificar trabajos pendientes
php artisan queue:work --once

# Ver trabajos fallidos
php artisan queue:failed
```

### Problema: "Permisos de escritura en logs"

**Solución:**
```bash
# Linux/Mac
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage

# O para desarrollo
sudo chmod -R 777 storage
```

## 📚 Siguientes Pasos

1. ✅ Lee la documentación completa: `MODULO_APIS_WEBHOOKS.md`
2. ✅ Configura tu servicio externo para recibir webhooks
3. ✅ Implementa validación del token en tu endpoint
4. ✅ Monitorea los logs en el panel de administración
5. ✅ Configura alertas para webhooks fallidos

## 🆘 Soporte

Si tienes problemas:

1. Revisa los logs: `storage/logs/laravel.log`
2. Verifica la configuración: `php artisan config:show`
3. Consulta la tabla de logs: `SELECT * FROM webhook_logs ORDER BY created_at DESC LIMIT 10`
4. Contacta al equipo de desarrollo

---

**¡Listo!** Tu sistema de APIs y Webhooks está configurado. 🎉
