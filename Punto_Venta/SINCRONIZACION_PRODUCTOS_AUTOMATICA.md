# Sincronización Automática de Productos

## Descripción
Sistema de sincronización automática de productos desde Valencia que se ejecuta en horarios programados para mantener la base de datos actualizada sin intervención manual.

## Comando de Sincronización

### Comando Principal
```bash
php artisan sincronizar:productos
```

### Descripción del Comando
- **Nombre**: `sincronizar:productos`
- **Descripción**: Sincroniza productos desde Valencia automáticamente
- **Funcionalidad**: Ejecuta una sincronización completa de todos los productos desde la base de datos Valencia hacia Zenvy

## Horarios Programados

### Sincronización Diaria Principal
- **Horario**: Todos los días a las 3:00 AM
- **Propósito**: Sincronización diaria completa de productos
- **Configuración**: 
  - Ejecuta en segundo plano (`runInBackground()`)
  - Previene solapamiento (`withoutOverlapping()`)
  - Registra errores y éxitos en logs

### Sincronización Semanal de Respaldo
- **Horario**: Domingos a la 1:00 AM
- **Propósito**: Sincronización semanal adicional como respaldo
- **Configuración**: Similar a la diaria pero con frecuencia semanal

## Funcionalidades del Sistema

### Estadísticas de Sincronización
El comando proporciona información detallada:
- 🆕 **Productos creados**: Nuevos productos agregados
- 🔄 **Productos actualizados**: Productos existentes modificados
- ✅ **Productos sincronizados**: Total de productos procesados exitosamente
- ❌ **Errores**: Productos que no pudieron sincronizarse
- ⏱️ **Tiempo de ejecución**: Duración del proceso

### Logging Automático
- **Éxitos**: Se registran en logs con estadísticas completas
- **Errores**: Se registran automáticamente para seguimiento
- **Ubicación**: Logs estándar de Laravel (`storage/logs/laravel.log`)

## Ejecución Manual

### Para Testing
```bash
# Ejecutar sincronización manual
php artisan sincronizar:productos
```

### Para Verificar Horarios
```bash
# Ver todos los comandos programados
php artisan schedule:list
```

### Para Ejecutar el Scheduler (en producción)
```bash
# Debe ejecutarse cada minuto en crontab
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Configuración en Kernel.php

```php
// Sincronización automática de productos en la madrugada (3:00 AM)
$schedule->command('sincronizar:productos')
        ->dailyAt('03:00')
        ->runInBackground()
        ->withoutOverlapping()
        ->onFailure(function () {
            Log::error('Fallo en sincronización automática de productos');
        })
        ->onSuccess(function () {
            Log::info('Sincronización automática de productos completada exitosamente');
        });

// Sincronización adicional de productos los domingos a las 1:00 AM (respaldo semanal)
$schedule->command('sincronizar:productos')
        ->weeklyOn(0, '01:00') // Domingo a la 1:00 AM
        ->runInBackground()
        ->withoutOverlapping()
        ->onFailure(function () {
            Log::error('Fallo en sincronización semanal de productos');
        });
```

## Ventajas del Sistema

1. **Automatización Completa**: No requiere intervención manual
2. **Horarios Optimizados**: Se ejecuta en horas de menor tráfico
3. **Respaldo Semanal**: Doble verificación para mayor confiabilidad
4. **Logging Detallado**: Seguimiento completo de operaciones
5. **Prevención de Conflictos**: Sistema de bloqueo para evitar solapamientos
6. **Estadísticas Claras**: Información detallada de cada sincronización

## Monitoreo

### Verificar Logs
```bash
# Ver últimas sincronizaciones
tail -f storage/logs/laravel.log | grep "sincronización automática de productos"
```

### Verificar Estado
```bash
# Ver estado del scheduler
php artisan schedule:list
```

## Notas Importantes

- El sistema mantiene la lógica de exclusión de campos implementada para Valencia
- Los productos existentes se actualizan respetando las reglas de negocio
- La sincronización es segura y no afecta datos locales críticos
- En caso de errores, el sistema continúa con los siguientes productos

---

**Desarrollado para**: Sistema Zenvy - Punto de Venta  
**Fecha**: Septiembre 2025  
**Autor**: Sistema Automático de Sincronización