# Logging del Reporte AI

## Ubicación de los logs

Los logs se guardan en: `storage/logs/laravel.log`

## Niveles de logging

El sistema registra 4 niveles diferentes de logs para la generación de reportes:

### 1. **Info** - Éxito ✅
Se registra cuando un reporte se genera exitosamente.

```
Log::info('Reporte AI generado exitosamente', [
    'usuario_id' => ...,
    'prompt' => 'Tu consulta',
    'intentos' => 1,
    'registros' => 10
]);
```

**Ubicación en log:**
```
[2025-11-14 12:30:45] local.INFO: Reporte AI generado exitosamente {"usuario_id":1,"prompt":"...","intentos":1,"registros":10}
```

---

### 2. **Warning** - Error en intento 🟡
Se registra cuando un intento falla pero hay más intentos disponibles (la IA intenta corregir).

```
Log::warning('Error en intento de generar reporte', [
    'usuario_id' => ...,
    'prompt' => 'Tu consulta',
    'intento' => 1,
    'error' => 'Error desconocido...',
    'sql' => 'SELECT ...'
]);
```

**Ubicación en log:**
```
[2025-11-14 12:30:45] local.WARNING: Error en intento de generar reporte {"usuario_id":1,...,"error":"Unknown column..."}
```

---

### 3. **Error** - Error en excepción o fallo final 🔴

#### 3a. Error en excepción durante intento:
```
Log::error('Excepción durante intento de generar reporte', [
    'usuario_id' => ...,
    'prompt' => 'Tu consulta',
    'intento' => 2,
    'error' => 'Mensaje de error',
    'archivo' => '/ruta/archivo.php',
    'linea' => 123
]);
```

#### 3b. Error final después de todos los intentos:
```
Log::error('Falló generación de reporte después de todos los intentos', [
    'usuario_id' => 1,
    'prompt' => 'Tu consulta',
    'total_intentos' => 3,
    'ultimo_error' => 'Tipo de error final'
]);
```

**Ubicación en log:**
```
[2025-11-14 12:30:45] local.ERROR: Falló generación de reporte después de todos los intentos {"usuario_id":1,...,"total_intentos":3}
```

---

### 4. **Critical** - Error crítico del sistema 🔴🔴
Se registra cuando hay una excepción no controlada en el proceso general.

```
Log::critical('Error crítico en generación de reporte', [
    'usuario_id' => 1,
    'usuario_nombre' => 'Johann Ruiz',
    'prompt' => 'Tu consulta',
    'error' => 'Mensaje de error',
    'archivo' => '/ruta/archivo.php',
    'linea' => 123,
    'traza' => 'Stack trace completo...'
]);
```

**Ubicación en log:**
```
[2025-11-14 12:30:45] local.CRITICAL: Error crítico en generación de reporte {"usuario_id":1,...,"traza":"..."}
```

---

## Cómo ver los logs

### Opción 1: Desde la terminal
```bash
# Ver últimas 50 líneas
tail -50 storage/logs/laravel.log

# Ver en tiempo real (monitoreo)
tail -f storage/logs/laravel.log

# Buscar errores específicos
grep "ERROR" storage/logs/laravel.log
grep "reporte" storage/logs/laravel.log -i
```

### Opción 2: Desde VS Code / Editor
Abre el archivo: `storage/logs/laravel.log`

### Opción 3: Desde la aplicación web
Puedes acceder al archivo de logs directamente si tienes un panel de administración.

---

## Información que se registra

### Para reportes exitosos (INFO):
- ID del usuario
- Texto de la consulta (prompt)
- Número de intentos que tomó
- Cantidad de registros devueltos

### Para errores (WARNING/ERROR):
- ID del usuario
- Texto de la consulta (prompt)
- Número de intento
- Mensaje de error exacto
- SQL que falló (cuando aplica)
- Archivo y línea donde ocurrió el error (en ERROR/CRITICAL)
- Stack trace completo (en CRITICAL)

---

## Ejemplo de lectura de un error

Si ves esto en el log:

```
[2025-11-14 12:30:45] local.ERROR: Falló generación de reporte después de todos los intentos 
{
  "usuario_id": 1,
  "prompt": "muéstrame las conversiones de unidades",
  "total_intentos": 3,
  "ultimo_error": "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'cambio_unidades' in 'from' clause"
}
```

**Significa:**
- El usuario 1 intentó 3 veces generar el reporte
- La IA no pudo encontrar la tabla correcta
- Necesita que se corrija el esquema de base de datos

---

## Filtrar por tipo de error en el log

```bash
# Solo Info (exitosos)
grep "INFO.*Reporte AI generado" storage/logs/laravel.log

# Solo Warnings (errores recuperables)
grep "WARNING.*Error en intento" storage/logs/laravel.log

# Solo Errors (fallos finales)
grep "ERROR.*Falló generación" storage/logs/laravel.log

# Errores críticos
grep "CRITICAL.*Error crítico" storage/logs/laravel.log
```

---

## Rotación de logs

Laravel automáticamente rota los logs cuando alcanzan cierto tamaño. 
Los logs antiguos se guardan con extensión `.log` seguida de un número:
- `laravel.log` - Log actual
- `laravel-2025-11-14.log` - Log del 14 de noviembre
- `laravel-2025-11-13.log` - Log del 13 de noviembre
- etc.

Puedes limpiar logs antiguos manualmente si es necesario.
