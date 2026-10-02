# Fase 0: respaldo y linea base

Fecha de corte: 2026-10-01.

## Respaldo verificable

- Origen: `paperland_produccion` (MySQL 8.4.3).
- Archivo local: `storage/app/backups/fase-0/paperland_produccion_20261001.sql`.
- Tamano: 253,364,736 bytes.
- SHA-256: `7791f5f7c10b26dab396bfa0d91eb83979416a76400bda1b64cf9995a8a007b4`.
- Clon aislado: `paperland_fase0_20261001`.

El respaldo se genero con `--single-transaction`, `--quick`, rutinas, triggers,
eventos y blobs en hexadecimal. No contiene una instruccion para crear o
seleccionar la base de produccion.

Para verificar el archivo:

```powershell
Get-FileHash storage/app/backups/fase-0/paperland_produccion_20261001.sql -Algorithm SHA256
```

## Validacion de restauracion

Origen y clon coinciden en 81 tablas y en todos los controles siguientes:

| Control | Resultado |
| --- | ---: |
| Productos | 8,911 |
| Precios de venta | 9,487 |
| Lotes de inventario | 8,368 |
| Facturas | 4,295 |
| Lineas de factura | 13,907 |
| Pagos de factura | 4,318 |
| Facturas anuladas | 30 |
| Total facturado | L 1,506,529.46 |
| Existencias disponibles | 28,112 |

Ambas bases reportaron cero existencias negativas, numeros de factura
duplicados, anulaciones duplicadas, facturas anuladas sin detalle y lineas de
factura huerfanas.

## Estado inicial de pruebas

El comando `php artisan test` produjo 24 fallos y una prueba aprobada. La causa
principal es que la configuracion SQLite en memoria no crea el esquema legado,
empezando por la tabla `cliente`. Esta es la linea base previa a construir las
pruebas de invariantes.

Todas las migraciones de la aplicacion aparecen pendientes frente a la base
existente. No se debe ejecutar `php artisan migrate` sobre produccion hasta
reconciliar el historial de migraciones.

## Medicion de solicitudes

El monitor esta desactivado por defecto. Para capturar tiempos, consultas y
memoria en un entorno controlado:

```dotenv
PERFORMANCE_MONITORING_ENABLED=true
```

Las mediciones se escriben en `storage/logs/performance-*.log`. No se registran
bindings SQL. Antes de comparar cambios, medir al menos cinco repeticiones de:

1. Abrir facturacion.
2. Buscar un producto.
3. Agregar y retirar una linea.
4. Abrir la lista de facturas.
5. Previsualizar una factura.
6. Abrir inventario, compras y cierre de caja.

Las pruebas que escriban datos deben apuntar exclusivamente al clon o a una
base temporal derivada de este. Nunca deben usar `paperland_produccion`.

## Protecciones implementadas

Las etapas posteriores se validan con:

```powershell
php vendor/phpunit/phpunit/phpunit -c phpunit.critical.xml
```

Estado al 2026-10-01: 10 pruebas y 45 aserciones correctas. La suite cubre
invariantes actuales, correlativo CAI, FIFO, restauracion exacta por lote,
restricciones unicas, precios API, pagos y calculos de cierre de caja.

Migraciones aplicadas individualmente al clon y luego a produccion:

1. `2026_10_01_000001_add_critical_unique_constraints.php`.
2. `2026_10_01_000002_add_restoration_tracking_to_invoice_lots.php`.
3. `2026_10_01_000003_add_catalog_price_to_web_order_items.php`.
4. `2026_10_01_000004_add_tax_rate_to_web_order_items.php`.

Las ventas nuevas registran los lotes consumidos en `detalle_factura_lote` y
pueden devolver exactamente esos lotes una sola vez. Las facturas historicas
no tienen esa trazabilidad; para anular una de ellas se debe desmarcar la opcion
de afectar inventario. El sistema no intenta inferir un lote y evita devolver
existencias a una presentacion incorrecta.

La API acepta `items.*.price_id` y recalcula precio, descuento, ISV y totales
desde el catalogo. La compatibilidad sin `price_id` solo funciona cuando el
precio enviado identifica una unica presentacion activa.