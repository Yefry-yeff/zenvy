# Sistema de Apartados de Inventario para Pedidos Web

## Descripción General

Este sistema implementa un mecanismo de reservas de inventario que garantiza que cuando un pedido web está en estado "pendiente", el stock de los productos no esté disponible para otras ventas.

## Flujo del Sistema

### 1. **Creación de Pedido Web (Estado: Pendiente)**
- Se crea el pedido web
- Se crean automáticamente **reservas de inventario** para cada producto
- El stock queda **apartado** (no disponible para otras ventas)
- Se registra la auditoría de la creación de reservas

### 2. **Cambio a Rechazado**
- Se liberan todas las reservas activas
- El stock vuelve a estar disponible
- Se registra el motivo del rechazo
- Se registra la auditoría de liberación

### 3. **Cambio a Facturado**
- Se descuenta el stock real del inventario
- Las reservas se marcan como "consumidas"
- Se registra la auditoría del consumo

## Estructura de Base de Datos

### Tabla: `reservas_inventario`

```sql
- id (PK)
- pedido_web_id (FK → pedidos_web)
- producto_id (FK → producto)
- cantidad_reservada
- estado (enum: 'activa', 'liberada', 'consumida')
- motivo_liberacion (nullable)
- fecha_liberacion (nullable)
- fecha_consumo (nullable)
- liberado_por (FK → users, nullable)
- consumido_por (FK → users, nullable)
- created_at
- updated_at
```

**Estados:**
- **activa**: La reserva está vigente, el stock está apartado
- **liberada**: La reserva fue liberada (pedido rechazado), stock disponible
- **consumida**: La reserva fue consumida (pedido facturado), stock descontado

### Tabla: `auditoria_reservas_inventario`

```sql
- id (PK)
- reserva_id (FK → reservas_inventario)
- pedido_web_id (FK → pedidos_web)
- producto_id (FK → producto)
- numero_pedido
- nombre_producto
- accion (crear, liberar, consumir, actualizar)
- estado_anterior
- estado_nuevo
- cantidad_anterior
- cantidad_nueva
- stock_disponible_antes
- stock_disponible_despues
- usuario_id (FK → users, nullable)
- usuario_nombre
- motivo
- metadata (JSON)
- ip
- fecha_accion
```

**Acciones registradas:**
- **crear**: Se crea una nueva reserva al recibir el pedido web
- **liberar**: Se libera una reserva cuando se rechaza el pedido
- **consumir**: Se consume una reserva cuando se factura el pedido
- **actualizar**: Se modifica la cantidad o estado de una reserva

## Clases y Servicios

### 1. **Modelos**

#### `ReservaInventario.php`
- Modelo principal para gestionar reservas
- Relaciones: pedidoWeb, producto, auditorias, liberadoPor, consumidoPor
- Scopes: activas(), porProducto()
- Método estático: cantidadReservadaProducto()

#### `AuditoriaReservaInventario.php`
- Modelo para registros de auditoría
- Relaciones: reserva, pedidoWeb, producto
- No tiene timestamps (usa fecha_accion)

### 2. **Servicio: `ReservaInventarioService.php`**

#### Métodos principales:

**`crearReservas(PedidoWeb $pedido, ?int $usuarioId = null)`**
- Crea reservas para todos los items del pedido
- Valida que haya stock disponible
- Registra auditoría
- Retorna: `['success' => bool, 'reservas' => array, 'errores' => array]`

**`liberarReservas(PedidoWeb $pedido, string $motivo, ?int $usuarioId = null)`**
- Libera todas las reservas activas de un pedido
- Marca estado como 'liberada'
- Registra motivo y usuario que liberó
- Registra auditoría

**`consumirReservas(PedidoWeb $pedido, ?int $usuarioId = null)`**
- Consume las reservas al facturar
- Descuenta el stock real del producto
- Marca estado como 'consumida'
- Registra auditoría

**`getStockDisponible(int $productoId)`**
- Calcula: Stock Real - Reservas Activas
- Este es el stock verdaderamente disponible para vender

### 3. **Integración en `SalesService.php`**

**Modificaciones:**
- Constructor inyecta `ReservaInventarioService`
- `createPreviewOrder()`: Crea reservas después de crear el pedido
- `validateStock()`: Valida stock considerando reservas activas
- `processOrder()`: Consume reservas al facturar

### 4. **Modificación en `PedidoWeb.php`**

**Método `rechazar()`:**
```php
public function rechazar(string $motivo = 'Pedido rechazado', ?int $usuarioId = null): void
{
    $reservaService = app(\App\Services\ReservaInventarioService::class);
    $reservaService->liberarReservas($this, $motivo, $usuarioId);
    
    $this->update(['estado' => 'rechazado']);
}
```

## Instalación

### 1. Ejecutar Migraciones

```bash
cd Punto_Venta
php artisan migrate
```

Las migraciones son:
- `2026_01_30_000001_create_reservas_inventario_table.php`
- `2026_01_30_000002_create_auditoria_reservas_inventario_table.php`

### 2. Verificar Instalación

```bash
# Verificar que las tablas fueron creadas
php artisan tinker
>>> \DB::table('reservas_inventario')->count();
>>> \DB::table('auditoria_reservas_inventario')->count();
```

## Consultas Útiles

### Ver reservas activas de un producto

```sql
SELECT r.*, p.nombre_producto, pw.numero_pedido
FROM reservas_inventario r
JOIN producto p ON r.producto_id = p.id
JOIN pedidos_web pw ON r.pedido_web_id = pw.id
WHERE r.producto_id = 1788
  AND r.estado = 'activa';
```

### Ver stock disponible vs stock real

```sql
SELECT 
    p.id,
    p.nombre_producto,
    p.existencia_actual as stock_real,
    COALESCE(SUM(CASE WHEN r.estado = 'activa' THEN r.cantidad_reservada ELSE 0 END), 0) as reservado,
    p.existencia_actual - COALESCE(SUM(CASE WHEN r.estado = 'activa' THEN r.cantidad_reservada ELSE 0 END), 0) as disponible
FROM producto p
LEFT JOIN reservas_inventario r ON p.id = r.producto_id
WHERE p.id = 1788
GROUP BY p.id, p.nombre_producto, p.existencia_actual;
```

### Ver auditoría de un pedido

```sql
SELECT *
FROM auditoria_reservas_inventario
WHERE pedido_web_id = 45
ORDER BY fecha_accion DESC;
```

### Ver pedidos con reservas activas

```sql
SELECT 
    pw.id,
    pw.numero_pedido,
    pw.estado,
    pw.cliente_nombre,
    COUNT(r.id) as total_reservas,
    SUM(r.cantidad_reservada) as cantidad_total_reservada
FROM pedidos_web pw
JOIN reservas_inventario r ON pw.id = r.pedido_web_id
WHERE r.estado = 'activa'
GROUP BY pw.id, pw.numero_pedido, pw.estado, pw.cliente_nombre
ORDER BY pw.created_at DESC;
```

## Logs

El sistema registra información en `storage/logs/laravel.log`:

- **Creación de reserva**: `Reserva de inventario creada`
- **Liberación de reserva**: `Reserva de inventario liberada`
- **Consumo de reserva**: `Reserva de inventario consumida (stock descontado)`

## Notas Importantes

1. **Stock Disponible**: El sistema ahora calcula el stock disponible como `Stock Real - Reservas Activas`

2. **Validación**: Antes de crear un pedido web, se valida que haya stock disponible (considerando reservas)

3. **Transacciones**: Todas las operaciones críticas están envueltas en transacciones de base de datos

4. **Auditoría Completa**: Cada acción sobre una reserva queda registrada con:
   - Usuario que ejecutó la acción
   - IP desde donde se ejecutó
   - Stock antes y después
   - Estado anterior y nuevo
   - Motivo de la acción

5. **Concurrencia**: El sistema usa locks en la base de datos para evitar problemas de concurrencia

6. **Retrocompatibilidad**: Los pedidos creados antes de implementar este sistema no tienen reservas, pero el sistema funciona correctamente con ellos
