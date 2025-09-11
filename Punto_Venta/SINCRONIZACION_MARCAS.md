# 🔄 Sistema de Sincronización de Marcas - CON MAPEO DE IDs

Este sistema permite mantener las marcas sincronizadas **en tiempo real** desde `profac_app` hacia `db_zenvy` usando una **tabla de mapeo** para evitar duplicados y manejar actualizaciones precisas.

## 📋 Características Principales

### ✅ Sistema de Mapeo Inteligente
- **Tabla de Mapeo**: `id_zenvy_valencia` mapea IDs entre sistemas
- **Evita Duplicados**: Usa IDs de Valencia para identificar marcas únicas
- **Actualizaciones Precisas**: Actualiza la marca correcta sin crear duplicados
- **Mapeos Retroactivos**: Crea mapeos para marcas ya sincronizadas

### 🔄 Flujo de Sincronización Mejorado

```
profac_app.marca → MarcaExterna → SincronizacionMarcasService 
                                        ↓
                              Tabla de Mapeo (id_zenvy_valencia)
                                        ↓
                              db_zenvy.marca (sin duplicados)
```

## �️ **Sistema de Mapeo:**

### Estructura de `id_zenvy_valencia`:
- `tipo_dato_migrado_id`: 2 (para marcas)
- `id_zenvy`: ID de la marca en db_zenvy 
- `id_valencia`: ID de la marca en profac_app
- `created_at`/`updated_at`: Control de fechas

### Lógica de Sincronización:
1. **Busca mapeo por ID Valencia** → Si existe, actualiza marca local
2. **Si no hay mapeo** → Busca marca por nombre y crea mapeo
3. **Si no existe** → Crea nueva marca + mapeo

## 🛠️ Comandos Actualizados

### Comandos Principales:
```bash
# Sincronización normal con mapeos
php artisan marcas:sincronizar

# Forzar sincronización completa
php artisan marcas:sincronizar --force

# Ver estadísticas detalladas
php artisan marcas:sincronizar --stats

# 🆕 NUEVO: Crear mapeos retroactivos
php artisan marcas:sincronizar --mapeos

# Limpiar marcas órfanas (usando mapeos)
php artisan marcas:limpiar-orfanas
```

## ⚙️ Configuración

### 1. Variables de Entorno

Agregar al archivo `.env`:

```env
# Configuración para base de datos externa profac_app
PROFAC_DB_HOST=127.0.0.1
PROFAC_DB_PORT=3306
PROFAC_DB_DATABASE=profac_app
PROFAC_DB_USERNAME=root
PROFAC_DB_PASSWORD=tu_password
```

### 2. Configuración de Base de Datos

La configuración ya está agregada en `config/database.php` con la conexión `profac_app`.

### 3. Tareas Programadas

Las tareas se ejecutan automáticamente:
- **Cada 30 minutos**: Sincronización normal
- **Diario a las 2:00 AM**: Sincronización forzada

Para activar el cron de Laravel:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🚀 Uso del Sistema

### Desde la Interface Web

1. **Formulario de Productos**:
   - Botón de refrescar (🔄): Actualiza marcas desde cache
   - Botón de sincronizar (⬇️): Fuerza sincronización desde sistema externo

2. **Panel de Administración**:
   - Acceder a `/admin/sincronizacion-marcas`
   - Ver estadísticas en tiempo real
   - Ejecutar sincronizaciones manuales
   - Limpiar cache

### Desde Línea de Comandos

```bash
# Sincronización normal (usa cache si está disponible)
php artisan marcas:sincronizar

# Forzar sincronización (ignora cache)
php artisan marcas:sincronizar --force

# Ver estadísticas
php artisan marcas:sincronizar --stats
```

## 🔧 Flujo de Funcionamiento

### 1. **Carga Normal de Marcas**
```
ProductoForm → SincronizacionMarcasService → Cache (5 min) → MarcaExterna → profac_app
                                         ↘ Fallback → Marca local
```

### 2. **Sincronización Programada**
```
Cron Schedule → marcas:sincronizar → SincronizacionMarcasService → 
MarcaExterna::sincronizarMarcas() → Crear/Actualizar marcas locales
```

### 3. **Cache Inteligente**
- **Marcas directas**: Cache de 1 minuto
- **Sincronización**: Cache de 5 minutos
- **Conectividad**: Cache de 1 minuto

## 🛡️ Manejo de Errores

### Conectividad
- Si no hay conexión con `profac_app`, usa marcas locales
- Logs de errores en `storage/logs/laravel.log`
- Notificaciones visuales en la interface

### Fallbacks
1. **Cache** → Si falla, usa cache anterior
2. **Base Externa** → Si falla, usa marcas locales
3. **Sincronización** → Reintenta en siguiente programación

## 📊 Monitoreo

### Logs
Todos los eventos se registran en logs:
- Errores de conectividad
- Sincronizaciones exitosas
- Estadísticas de marcas sincronizadas

### Métricas Disponibles
- Total de marcas locales vs externas
- Estado de conectividad
- Último cache activo
- Marcas sincronizadas recientemente

## 🔄 API Endpoints

```php
// Estadísticas en tiempo real
GET /api/sincronizacion/estadisticas

// Estado del sistema
GET /api/sincronizacion/estado

// Sincronización manual
POST /admin/sincronizacion/normal
POST /admin/sincronizacion/forzar

// Limpiar cache
POST /admin/sincronizacion/limpiar-cache
```

## 🚨 Troubleshooting

### Problema: No se conecta a profac_app
1. Verificar variables de entorno
2. Comprobar credenciales de base de datos
3. Revisar logs: `tail -f storage/logs/laravel.log`

### Problema: Marcas no se actualizan
1. Limpiar cache manualmente
2. Ejecutar sincronización forzada
3. Verificar estructura de tabla `marca` en `profac_app`

### Problema: Performance lenta
1. Ajustar tiempos de cache en `SincronizacionMarcasService`
2. Reducir frecuencia de sincronización programada
3. Optimizar consultas en `MarcaExterna`

## 🎯 Ventajas del Sistema

- ✅ **Tiempo Real**: Marcas siempre actualizadas
- ✅ **Resistente**: Funciona aunque falle la conexión externa
- ✅ **Eficiente**: Sistema de cache inteligente
- ✅ **Monitoreable**: Interface de administración completa
- ✅ **Automatizado**: Sincronización sin intervención manual
- ✅ **Escalable**: Fácil de extender a otras entidades

## 📝 Próximas Mejoras

- [ ] Sincronización bidireccional
- [ ] Sincronización de otras entidades (categorías, subcategorías)
- [ ] Notificaciones por email en caso de errores
- [ ] Dashboard con gráficos de sincronización
- [ ] API REST completa para integración externa
