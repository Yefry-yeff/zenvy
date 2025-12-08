# Documentación: Sistema de Mapeo Dual para Productos Valencia-Zenvy

## Descripción General

El sistema mantiene **DOS tablas de mapeo** para productos sincronizados entre Valencia y Zenvy (Paperland):

### 1. `id_zenvy_valencia` - Tabla General
- **Propósito**: Mapeo genérico para todos los tipos de datos (productos, marcas, categorías, etc.)
- **Uso**: Mantiene la compatibilidad con el sistema existente
- **Campos clave**: 
  - `id_zenvy`: ID en Zenvy
  - `id_valencia`: ID en Valencia
  - `tipo_dato_migrado_id`: Tipo de entidad (1=Producto, 2=Marca, etc.)

### 2. `producto_valencia_zenvy` - Tabla Específica para Productos
- **Propósito**: Mapeo optimizado exclusivamente para productos
- **Ventaja**: Consultas ultra-rápidas por código de producto y código de barras
- **Campos clave**:
  - `producto_id_zenvy`: ID del producto en Zenvy
  - `producto_id_valencia`: ID del producto en Valencia
  - `codigo_producto_valencia`: Código del producto (indexado)
  - `codigo_barra`: Código de barras (indexado)
  - `sincronizado`: Estado de sincronización
  - `ultima_sincronizacion`: Timestamp de última sync

## Índices Optimizados en `producto_valencia_zenvy`

```sql
-- Índices únicos (previenen duplicados)
UNIQUE: producto_id_zenvy
UNIQUE: producto_id_valencia

-- Índices de búsqueda rápida
INDEX: codigo_producto_valencia
INDEX: codigo_barra
INDEX: sincronizado
INDEX COMPUESTO: (producto_id_valencia, codigo_producto_valencia)

-- Llave foránea
FOREIGN KEY: producto_id_zenvy -> producto(id) ON DELETE CASCADE
```

## Flujo de Sincronización

Cuando se sincroniza un producto:

1. **Inserción/Actualización en `producto` (Zenvy)**
   - Se crea o actualiza el producto en la tabla `producto`
   - Se obtiene el `id` del producto en Zenvy

2. **Registro en `id_zenvy_valencia`** (tabla general)
   - Se inserta el mapeo genérico
   - `id_zenvy` = ID del producto en Zenvy
   - `id_valencia` = ID del producto en Valencia
   - `tipo_dato_migrado_id` = 1 (productos)

3. **Registro en `producto_valencia_zenvy`** (tabla específica)
   - Se inserta/actualiza el mapeo específico de producto
   - Incluye códigos para búsquedas rápidas
   - Se marca como `sincronizado = true`
   - Se registra `ultima_sincronizacion`

## Métodos de Consulta Rápida

### En el Modelo `ProductoValenciaZenvy`:

```php
// Buscar por ID de Valencia
ProductoValenciaZenvy::buscarPorIdValencia($idValencia);

// Buscar por ID de Zenvy
ProductoValenciaZenvy::buscarPorIdZenvy($idZenvy);

// Buscar por código de producto Valencia (RÁPIDO - indexado)
ProductoValenciaZenvy::buscarPorCodigoValencia($codigo);

// Buscar por código de barras (RÁPIDO - indexado)
ProductoValenciaZenvy::buscarPorCodigoBarra($codigoBarra);

// Obtener solo el ID de Zenvy
ProductoValenciaZenvy::obtenerIdZenvy($idValencia);

// Obtener solo el ID de Valencia
ProductoValenciaZenvy::obtenerIdValencia($idZenvy);

// Verificar si está sincronizado
ProductoValenciaZenvy::estaSincronizado($idValencia);

// Crear o actualizar mapeo
ProductoValenciaZenvy::crearOActualizar($idZenvy, $idValencia, $codigo, $codigoBarra);
```

### En el Servicio `SincronizacionProductosService`:

```php
$servicio = new SincronizacionProductosService();

// Métodos rápidos (usan índices optimizados)
$idZenvy = $servicio->obtenerIdZenvyRapido($idValencia);
$idValencia = $servicio->obtenerIdValenciaRapido($idZenvy);

// Búsquedas por código (ULTRA-RÁPIDAS)
$producto = $servicio->buscarPorCodigoValencia('PROD001');
$producto = $servicio->buscarPorCodigoBarra('1234567890123');

// Verificación de sincronización
$sincronizado = $servicio->estaProductoSincronizadoRapido($idValencia);

// Estadísticas detalladas
$stats = $servicio->obtenerEstadisticasSincronizacionProductos();
// Retorna: total_valencia, total_sincronizados, pendientes, 
//          porcentaje_sincronizado, ultima_sincronizacion
```

## Comparación de Rendimiento

### Antes (solo `id_zenvy_valencia`):
```sql
-- Buscar producto por código: requiere JOIN
SELECT p.* 
FROM producto p
JOIN id_zenvy_valencia m ON p.id = m.id_zenvy
WHERE m.tipo_dato_migrado_id = 1
AND p.codigo_estatal = 'PROD001';
```

### Ahora (con `producto_valencia_zenvy`):
```sql
-- Búsqueda directa con índice
SELECT producto_id_zenvy 
FROM producto_valencia_zenvy 
WHERE codigo_producto_valencia = 'PROD001';
-- ⚡ MUCHO MÁS RÁPIDO - índice directo
```

## Casos de Uso

### 1. Consulta por Código de Producto (común en punto de venta)
```php
// Rápido: usa índice en producto_valencia_zenvy
$producto = $servicio->buscarPorCodigoValencia('ABC123');
```

### 2. Consulta por Código de Barras (escaneo en caja)
```php
// Ultra-rápido: índice en codigo_barra
$producto = $servicio->buscarPorCodigoBarra('7501234567890');
```

### 3. Verificar Estado de Sincronización
```php
// Rápido: índice único en producto_id_valencia
if (ProductoValenciaZenvy::estaSincronizado($idValencia)) {
    // Producto ya sincronizado
}
```

### 4. Obtener Estadísticas
```php
$stats = $servicio->obtenerEstadisticasSincronizacionProductos();
// {
//   "total_valencia": 5000,
//   "total_sincronizados": 4500,
//   "pendientes": 500,
//   "porcentaje_sincronizado": 90.00,
//   "ultima_sincronizacion": "2025-12-08 14:30:00"
// }
```

## Migración

### Ejecutar Migración:
```bash
cd Punto_Venta
php artisan migrate
```

### O ejecutar SQL directamente:
```bash
# Ubicación del script
Bases de datos/Script/crear_tabla_producto_valencia_zenvy.sql
```

## Mantenimiento

### Actualizar Timestamp de Sincronización:
```php
$mapeo = ProductoValenciaZenvy::buscarPorIdValencia($idValencia);
$mapeo->actualizarSincronizacion();
```

### Marcar como No Sincronizado:
```php
$mapeo = ProductoValenciaZenvy::buscarPorIdValencia($idValencia);
$mapeo->marcarComoNoSincronizado();
```

## Ventajas del Sistema Dual

1. ✅ **Compatibilidad**: Mantiene `id_zenvy_valencia` para otros tipos de datos
2. ✅ **Rendimiento**: `producto_valencia_zenvy` optimizada para productos
3. ✅ **Búsquedas Rápidas**: Índices específicos para códigos
4. ✅ **Trazabilidad**: Timestamp de última sincronización
5. ✅ **Integridad**: Foreign key a tabla `producto`
6. ✅ **Escalabilidad**: Consultas eficientes incluso con miles de productos

## Notas Importantes

- Ambas tablas se actualizan **automáticamente** durante la sincronización
- La tabla `producto_valencia_zenvy` incluye **índices únicos** para prevenir duplicados
- Las búsquedas por código usan índices **B-Tree** para máximo rendimiento
- La foreign key asegura que si se elimina un producto en Zenvy, el mapeo también se elimina
