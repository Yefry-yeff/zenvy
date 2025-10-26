# DOCUMENTACIÓN TÉCNICA: INTERFAZ DE COMUNICACIÓN CON SISTEMA VALENCIA

## 1. INFORMACIÓN GENERAL

### 1.1 Nombre del Sistema Relacionado
**Sistema Valencia (profac_app)** - Sistema de gestión de inventarios y facturación de Distribuciones Valencia

### 1.2 Objetivo y Descripción del Proceso de la Interface
La interfaz tiene como objetivo sincronizar de forma bidireccional los datos maestros de productos, marcas, categorías, subcategorías y unidades de medida entre el sistema Zenvy (Punto de Venta) y el sistema Valencia (ERP principal).

**Propósitos principales:**
- Mantener consistencia de datos entre ambos sistemas
- Permitir que las actualizaciones de Valencia se reflejen automáticamente en Zenvy
- Centralizar la administración de datos maestros en Valencia
- Evitar duplicidad de esfuerzos en mantenimiento de catálogos

## 2. FORMAS DE COMUNICACIÓN

### 2.1 Tipo de Comunicación
**Conexión Directa a Base de Datos MySQL**
- Protocolo: MySQL TCP/IP
- Tipo: Conexión directa punto a punto
- Método: Lectura directa de tablas mediante queries SQL

### 2.2 Configuración de Conexión

#### Base de Datos Principal (Zenvy)
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_zenvy
DB_USERNAME=root
DB_PASSWORD=
```

#### Base de Datos Externa (Valencia)
```env
PROFAC_DB_HOST=127.0.0.1
PROFAC_DB_PORT=3306
PROFAC_DB_DATABASE=profac_app
PROFAC_DB_USERNAME=root
PROFAC_DB_PASSWORD=
```

### 2.3 Configuración en Laravel
```php
// config/database.php
'profac_app' => [
    'driver' => 'mysql',
    'host' => env('PROFAC_DB_HOST', '127.0.0.1'),
    'port' => env('PROFAC_DB_PORT', '3306'),
    'database' => env('PROFAC_DB_DATABASE', 'profac_app'),
    'username' => env('PROFAC_DB_USERNAME', 'root'),
    'password' => env('PROFAC_DB_PASSWORD', ''),
    'unix_socket' => env('PROFAC_DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
],
```

## 3. DIAGRAMA DE ARQUITECTURA

```
┌─────────────────────┐         ┌─────────────────────┐
│   SISTEMA ZENVY     │         │   SISTEMA VALENCIA  │
│   (Punto de Venta)  │         │   (profac_app)      │
│                     │         │                     │
│ ┌─────────────────┐ │         │ ┌─────────────────┐ │
│ │   db_zenvy      │ │         │ │   profac_app    │ │
│ │                 │ │         │ │                 │ │
│ │ • producto      │ │ ◄─────► │ │ • producto      │ │
│ │ • marca         │ │         │ │ • marca         │ │
│ │ • categoria     │ │         │ │ • categoria     │ │
│ │ • subcategoria  │ │         │ │ • sub_categoria │ │
│ │ • unidad_medida │ │         │ │ • unidad_medida │ │
│ └─────────────────┘ │         │ └─────────────────┘ │
│                     │         │                     │
│ ┌─────────────────┐ │         │                     │
│ │id_zenvy_valencia│ │         │                     │
│ │ (Tabla Mapeo)   │ │         │                     │
│ └─────────────────┘ │         │                     │
└─────────────────────┘         └─────────────────────┘
           │                               │
           └───────── TCP/IP MySQL ────────┘
                    Puerto 3306
```

## 4. ESTRUCTURA DE DATOS (TRAMAS)

### 4.1 Tabla de Mapeo Central
```sql
-- Tabla: id_zenvy_valencia
CREATE TABLE id_zenvy_valencia (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tipo_dato_migrado_id INT NOT NULL,
    id_zenvy BIGINT NOT NULL,
    id_valencia BIGINT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY unique_mapping (tipo_dato_migrado_id, id_valencia)
);

-- Tipos de datos:
-- 1 = Productos
-- 2 = Marcas  
-- 3 = Categorías
-- 4 = Subcategorías
-- 5 = Unidades de Medida
-- 8 = Compras
-- 9 = Traslados
```

### 4.2 Estructura de Datos de Productos

#### Valencia (profac_app.producto)
```sql
SELECT 
    id,
    nombre,
    descripcion,
    isv,                          -- 0, 15, 18
    precio_base,
    ultimo_costo_compra,
    costo_promedio,
    codigo_barra,
    codigo_estatal,
    marca_id,
    unidad_medida_compra_id,
    estado_producto_id,
    sub_categoria_id,
    precio1,
    precio2,
    precio3,
    precio4
FROM producto;
```

#### Zenvy (db_zenvy.producto)
```sql
INSERT INTO producto (
    nombre,
    descripcion,
    isv_id,                       -- Convertido: 0→5, 15→1, 18→2
    precio_base,
    ultimo_costo_compra,
    costo_promedio,
    codigo_barra,
    codigo_estatal,
    marca_id,                     -- Mapeado via id_zenvy_valencia
    unidad_medida_venta_id,       -- Mapeado via id_zenvy_valencia
    estado_id,
    subcategoria_id,              -- Mapeado via id_zenvy_valencia
    precio1,
    precio2,
    precio3,
    precio4,
    descuento_unitario,           -- Default: 0
    descuento_tercera,            -- Default: 1
    descuento_cuarta,             -- Default: 1
    producto_valencia,            -- Default: 1
    users_id,                     -- Default: 4
    created_at,
    updated_at
);
```

### 4.3 Reglas de Transformación

#### ISV (Impuesto Sobre Ventas)
```php
// Conversión Valencia → Zenvy
switch ($isvValencia) {
    case 0:  return 5;  // Sin ISV
    case 15: return 1;  // ISV 15%
    case 18: return 2;  // ISV 18%
    default: return 1;  // Por defecto
}
```

#### Precio Base Especial
```php
// Regla especial para productos Valencia
if ($precio4Valencia > $precioBaseActual) {
    $nuevoPrecioBase = $precio4Valencia;
    // Auto-actualizar precio_base al precio4 si es mayor
}
```

## 5. SERVICIOS Y COMPONENTES

### 5.1 Servicio Principal
**Archivo**: `app/Services/SincronizacionProductosService.php`

**Métodos principales:**
- `obtenerProductosValencia()` - Obtiene todos los productos de Valencia
- `sincronizarProducto($id)` - Sincroniza un producto específico
- `sincronizarTodosLosProductos()` - Sincronización masiva
- `obtenerProductosValenciaConEstado()` - Productos con estado de sincronización
- `actualizarProductoValencia($id)` - Actualización con reglas especiales

### 5.2 Modelo de Mapeo
**Archivo**: `app/Models/IdZenvyValencia.php`

**Constantes:**
```php
const TIPO_MARCA = 2;
const TIPO_PRODUCTO = 1;
const TIPO_CATEGORIA = 3;
const TIPO_SUBCATEGORIA = 4;
const TIPO_UNIDAD_MEDIDA = 5;
const TIPO_COMPRA = 8;
const TIPO_TRASLADO = 9;
```

**Métodos principales:**
- `buscarPorValencia($idValencia, $tipoDato)`
- `buscarPorZenvy($idZenvy, $tipoDato)`
- `crearMapeo($idZenvy, $idValencia, $tipoDato)`

### 5.3 Comandos Artisan
**Archivo**: `app/Console/Commands/SincronizarProductos.php`

```bash
# Comando principal
php artisan sincronizar:productos

# Otros comandos disponibles
php artisan marcas:sincronizar
php artisan marcas:sincronizar --force
```

## 6. CÓMO BAJAR Y SUBIR LA INTERFACE

### 6.1 Para Detener la Interface

#### Desactivar Tareas Programadas
```php
// En app/Console/Kernel.php - Comentar o eliminar:
/*
$schedule->command('sincronizar:productos')
        ->dailyAt('03:00')
        ->runInBackground()
        ->withoutOverlapping();

$schedule->command('marcas:sincronizar')
        ->everyThirtyMinutes()
        ->runInBackground()
        ->withoutOverlapping();
*/
```

#### Deshabilitar Conexión Valencia
```env
# En .env - Cambiar credenciales inválidas:
PROFAC_DB_HOST=disabled
PROFAC_DB_DATABASE=disabled
PROFAC_DB_USERNAME=disabled
PROFAC_DB_PASSWORD=disabled
```

#### Método de Emergencia
```php
// En SincronizacionProductosService.php - Agregar al constructor:
if (env('VALENCIA_SYNC_DISABLED', false)) {
    throw new \Exception('Sincronización con Valencia deshabilitada');
}
```

### 6.2 Para Activar la Interface

#### Verificar Conexión
```bash
# Probar conexión a Valencia
php artisan tinker
>>> DB::connection('profac_app')->select('SELECT 1 as test');
```

#### Reactivar Configuración
```env
# Restaurar credenciales correctas en .env
PROFAC_DB_HOST=127.0.0.1
PROFAC_DB_PORT=3306
PROFAC_DB_DATABASE=profac_app
PROFAC_DB_USERNAME=root
PROFAC_DB_PASSWORD=
```

#### Restaurar Tareas Programadas
```php
// Descomentar en app/Console/Kernel.php
$schedule->command('sincronizar:productos')
        ->dailyAt('03:00')
        ->runInBackground()
        ->withoutOverlapping();
```

#### Verificar Scheduler
```bash
# Verificar que el scheduler esté activo
php artisan schedule:list
```

## 7. CÓMO PROBAR SU ADECUADO FUNCIONAMIENTO

### 7.1 Pruebas de Conexión

#### Test Conexión Base
```bash
php artisan tinker
>>> DB::connection('profac_app')->select('SELECT COUNT(*) as total FROM producto');
>>> DB::connection('mysql')->select('SELECT COUNT(*) as total FROM producto');
```

#### Test Sincronización Individual
```bash
# Sincronizar un producto específico
php artisan tinker
>>> $service = app(\App\Services\SincronizacionProductosService::class);
>>> $resultado = $service->sincronizarProducto(1);
>>> dump($resultado);
```

### 7.2 Pruebas de Sincronización Completa

#### Ejecución Manual Completa
```bash
# Ejecutar sincronización completa
php artisan sincronizar:productos

# Verificar logs
tail -f storage/logs/laravel.log | grep "sincronización"
```

#### Verificar Estadísticas
```bash
php artisan tinker
>>> $service = app(\App\Services\SincronizacionProductosService::class);
>>> $stats = $service->obtenerEstadisticasSincronizacion();
>>> dump($stats);
```

### 7.3 Pruebas de Mapeo

#### Verificar Mapeos Existentes
```sql
-- Contar mapeos por tipo
SELECT 
    tipo_dato_migrado_id,
    COUNT(*) as total
FROM id_zenvy_valencia 
GROUP BY tipo_dato_migrado_id;

-- Verificar mapeos de productos
SELECT * FROM id_zenvy_valencia 
WHERE tipo_dato_migrado_id = 1 
LIMIT 10;
```

#### Test de Integridad
```bash
php artisan tinker
>>> $mapeos = \App\Models\IdZenvyValencia::where('tipo_dato_migrado_id', 1)->get();
>>> foreach($mapeos as $mapeo) {
>>>     $zenvy = DB::table('producto')->where('id', $mapeo->id_zenvy)->exists();
>>>     $valencia = DB::connection('profac_app')->table('producto')->where('id', $mapeo->id_valencia)->exists();
>>>     if (!$zenvy || !$valencia) {
>>>         echo "Mapeo roto: Zenvy ID {$mapeo->id_zenvy}, Valencia ID {$mapeo->id_valencia}\n";
>>>     }
>>> }
```

### 7.4 Pruebas de Scheduler

#### Verificar Tareas Programadas
```bash
# Ver todas las tareas programadas
php artisan schedule:list

# Ejecutar scheduler manualmente
php artisan schedule:run
```

#### Simular Ejecución Automática
```bash
# Forzar ejecución de comando específico
php artisan schedule:run --verbose

# Ver próxima ejecución
php artisan schedule:list | grep sincronizar
```

### 7.5 Pruebas de Integridad de Datos

#### Comparar Totales
```sql
-- Valencia
SELECT COUNT(*) as productos_valencia 
FROM profac_app.producto;

-- Zenvy (productos de Valencia)
SELECT COUNT(*) as productos_zenvy_valencia
FROM db_zenvy.producto 
WHERE producto_valencia = 1;

-- Mapeos
SELECT COUNT(*) as mapeos_productos
FROM db_zenvy.id_zenvy_valencia 
WHERE tipo_dato_migrado_id = 1;
```

#### Verificar Consistencia de Precios
```sql
-- Comparar precios entre sistemas
SELECT 
    z.id as zenvy_id,
    z.nombre,
    z.precio_base as precio_zenvy,
    v.precio4 as precio_valencia,
    (z.precio_base - v.precio4) as diferencia
FROM db_zenvy.producto z
JOIN db_zenvy.id_zenvy_valencia m ON z.id = m.id_zenvy
JOIN profac_app.producto v ON m.id_valencia = v.id
WHERE m.tipo_dato_migrado_id = 1
AND ABS(z.precio_base - v.precio4) > 0.01;
```

## 8. MONITOREO Y LOGGING

### 8.1 Ubicaciones de Logs
- **Laravel**: `storage/logs/laravel.log`
- **Scheduler**: Logs automáticos cada ejecución
- **Database**: Tabla `bitacora` para auditoría

### 8.2 Eventos Registrados
- Sincronizaciones exitosas
- Errores de conexión
- Productos creados/actualizados
- Fallos de mapeo
- Estadísticas de rendimiento

### 8.3 Métricas de Salud
```bash
# Verificar salud de la interfaz
php artisan tinker
>>> $service = app(\App\Services\SincronizacionProductosService::class);
>>> $health = [
>>>     'conexion_valencia' => DB::connection('profac_app')->getPdo() ? 'OK' : 'FAIL',
>>>     'conexion_zenvy' => DB::connection('mysql')->getPdo() ? 'OK' : 'FAIL',
>>>     'estadisticas' => $service->obtenerEstadisticasSincronizacion()
>>> ];
>>> dump($health);
```

---

**Documento generado para**: Sistema Zenvy - Punto de Venta  
**Fecha**: Octubre 2025  
**Versión**: 1.0  
**Autor**: Sistema de Documentación Técnica