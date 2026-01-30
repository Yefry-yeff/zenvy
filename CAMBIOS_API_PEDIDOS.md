# Cambios en la API de Pedidos Web

## Resumen
Se ha actualizado la API de pedidos web para soportar información adicional de envío, costos de envío y detalles de transferencias bancarias.

## Cambios Implementados

### 1. CreateOrderRequest.php
**Archivo**: `app/Http/Requests/Api/CreateOrderRequest.php`

**Nuevos campos validados:**
- `items.*.product_id` - ID del producto (alternativa a SKU)
- `items.*.discount` - Descuento individual por item
- `shipping_cost` - Costo de envío
- `delivery_type` - Tipo de entrega (`recoger` o `domicilio`)
- `delivery_address` - Dirección de entrega
- `transfer_info` - Objeto con información de transferencia bancaria:
  - `account_bank` - Nombre del banco
  - `account_type` - Tipo de cuenta (ahorro/corriente)
  - `account_number` - Número de cuenta
  - `account_holder` - Titular de la cuenta
  - `transfer_date` - Fecha de la transferencia

### 2. OrderService.php
**Archivo**: `app/Services/Api/OrderService.php`

**Cambios:**
- Guardado de `delivery_type`, `delivery_address` y `shipping_cost` en metadata
- Guardado de `transfer_info` en metadata cuando se proporciona
- Soporte para búsqueda de productos por `product_id` además de `sku`
- El campo `cliente_direccion` ahora puede tomar el valor de `delivery_address` si `customer_address` no está presente

### 3. detalle-pedido.blade.php
**Archivo**: `resources/views/livewire/detalle-pedido.blade.php`

**Mejoras visuales:**
- Nueva sección mostrando tipo de entrega (Domicilio/Recoger en Tienda)
- Visualización de dirección de envío si es diferente a la dirección del cliente
- Costo de envío mostrado en el desglose del total
- Información completa de transferencia bancaria cuando aplica

## Ejemplo de JSON

```json
{
  "customer_name": "Johann Sebastian Ruiz Quiroz",
  "customer_email": "johann_ruiz14@hotmail.com",
  "customer_phone": "+50497525987",
  "customer_rtn": "",
  "items": [
    {
      "product_id": 1788,
      "quantity": 1,
      "price": 21.74,
      "discount": 0
    }
  ],
  "delivery_type": "domicilio",
  "delivery_address": "El centro de AMDC",
  "payment_method": "Transferencia Bancaria",
  "notes": "",
  "subtotal": 21.74,
  "discount": 0,
  "shipping_cost": 60,
  "tax": 3.26,
  "total": 85,
  "transfer_info": {
    "account_bank": "BAC",
    "account_type": "ahorro",
    "account_number": "7777725",
    "account_holder": "Paperland",
    "transfer_date": "2026-01-01"
  }
}
```

## Estructura de Metadata

Ahora el campo `metadata` en la tabla `pedidos_web` contiene:

```php
[
  'api_client' => 'nombre_cliente_api',
  'stock_disponible_al_crear' => true/false,
  'ip' => '192.168.1.1',
  'user_agent' => 'Mozilla/5.0...',
  'delivery_type' => 'domicilio', // o 'recoger'
  'delivery_address' => 'El centro de AMDC',
  'shipping_cost' => 60.00,
  'transfer_info' => [
    'account_bank' => 'BAC',
    'account_type' => 'ahorro',
    'account_number' => '7777725',
    'account_holder' => 'Paperland',
    'transfer_date' => '2026-01-01'
  ]
]
```

## Endpoint de la API

**POST** `/api/v1/orders`

**Headers:**
```
Content-Type: application/json
X-API-Key: tu_api_key
```

**Body:** Ver ejemplo JSON arriba

## Visualización en el Sistema

### Detalle del Pedido
1. **Información del Cliente**: Incluye tipo de entrega con iconos visuales
2. **Dirección de Envío**: Se muestra separada si es diferente a la dirección del cliente
3. **Método de Pago**: Muestra información completa de transferencia bancaria en una tarjeta destacada
4. **Total a Pagar**: Incluye el costo de envío en el desglose antes del ISV

### Sección de Transferencia Bancaria
Si el método de pago es "Transferencia Bancaria", se muestra una sección adicional con:
- Banco
- Tipo de cuenta
- Número de cuenta
- Titular de la cuenta
- Fecha de transferencia

## Compatibilidad

Los cambios son **retrocompatibles**. Los pedidos antiguos que no incluyan estos campos seguirán funcionando normalmente. Los nuevos campos son opcionales (`nullable`).

## Pruebas Recomendadas

1. **Pedido con entrega a domicilio y transferencia:**
```bash
curl -X POST https://tu-dominio.com/api/v1/orders \
  -H "Content-Type: application/json" \
  -H "X-API-Key: tu_api_key" \
  -d @pedido-completo.json
```

2. **Pedido para recoger en tienda:**
```json
{
  "delivery_type": "recoger",
  "shipping_cost": 0,
  ...
}
```

3. **Verificar visualización:**
   - Navegar a Bandeja de Pedidos
   - Abrir el pedido creado
   - Verificar que toda la información se muestre correctamente

## Notas Importantes

- El campo `items.*.product_id` es una alternativa a `items.*.sku`. Solo se necesita uno de los dos.
- El `shipping_cost` debe ser incluido en el cálculo del `total` final.
- La fecha en `transfer_date` debe estar en formato ISO 8601 (YYYY-MM-DD).
- El `delivery_type` acepta solo dos valores: `"recoger"` o `"domicilio"`.

## Fecha de Implementación
29 de enero de 2026
