# API de Ventas - E-commerce

## Descripción General

Esta API permite crear facturas en Zenvy directamente desde la página web de e-commerce. La API recibe información completa del cliente, productos con precios y descuentos, datos de entrega y genera una factura en el sistema de Zenvy con descuento automático de stock.

## Endpoint Principal

**POST** `/api/v1/sales`

### Headers Requeridos

```
Content-Type: application/json
Authorization: Bearer {token}
X-API-Key: {your-api-key}
```

## Estructura de la Petición

### Datos del Cliente

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `customer_name` | string | Sí | Nombre completo del cliente | "Juan Pérez" |
| `customer_email` | string | Sí | Email del cliente | "juan@example.com" |
| `customer_phone` | string | Sí | Teléfono en formato +504XXXXXXXX | "+50499887766" |
| `customer_rtn` | string | No | RTN del cliente (opcional) | "08011990123456" |

### Datos de los Productos

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `items` | array | Sí | Lista de productos a facturar | Ver estructura abajo |
| `items[].product_id` | integer | Sí | ID del producto en Zenvy | 15 |
| `items[].quantity` | integer | Sí | Cantidad a rebajar del stock | 2 |
| `items[].price` | decimal | Sí | Precio unitario del producto | 250.50 |
| `items[].discount` | decimal | No | Porcentaje de descuento (0-100) | 10 |

### Datos de Entrega

| Campo | Tipo | Requerido | Descripción | Valores |
|-------|------|-----------|-------------|---------|
| `delivery_type` | string | Sí | Tipo de entrega | "retiro_tienda" o "domicilio" |
| `delivery_address` | string | Condicional | Dirección (requerido si es domicilio) | "Col. Kennedy, Casa 123" |

### Datos de Pago

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `payment_method` | string | Sí | Método de pago | "Tarjeta", "Efectivo", "Transferencia" |
| `notes` | string | No | Notas adicionales del pedido | "Entregar en horario de oficina" |

### Totales

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `subtotal` | decimal | Sí | Subtotal sin impuestos | 500.00 |
| `discount` | decimal | No | Descuento general aplicado | 50.00 |
| `tax` | decimal | Sí | ISV (15%) | 75.00 |
| `total` | decimal | Sí | Total a pagar | 525.00 |

## Ejemplo Completo de Petición

### Caso 1: Entrega a Domicilio

```json
{
  "customer_name": "María González",
  "customer_email": "maria.gonzalez@example.com",
  "customer_phone": "+50498765432",
  "customer_rtn": "08011985654321",
  
  "items": [
    {
      "product_id": 25,
      "quantity": 2,
      "price": 150.00,
      "discount": 10
    },
    {
      "product_id": 42,
      "quantity": 1,
      "price": 300.00,
      "discount": 0
    }
  ],
  
  "delivery_type": "domicilio",
  "delivery_address": "Colonia Trejo, Bloque F, Casa 25, Tegucigalpa",
  
  "payment_method": "Tarjeta de Crédito",
  "notes": "Por favor llamar antes de llegar",
  
  "subtotal": 570.00,
  "discount": 30.00,
  "tax": 85.50,
  "total": 625.50
}
```

### Caso 2: Retiro en Tienda

```json
{
  "customer_name": "Carlos Martínez",
  "customer_email": "carlos@example.com",
  "customer_phone": "+50499123456",
  
  "items": [
    {
      "product_id": 10,
      "quantity": 5,
      "price": 75.00,
      "discount": 5
    }
  ],
  
  "delivery_type": "retiro_tienda",
  
  "payment_method": "Efectivo",
  "notes": "Retirar en horario de tarde",
  
  "subtotal": 375.00,
  "discount": 18.75,
  "tax": 56.25,
  "total": 412.50
}
```

## Respuestas

### Respuesta Exitosa (201 Created)

```json
{
  "success": true,
  "data": {
    "factura_id": 1523,
    "cliente": "María González",
    "total": 625.50,
    "subtotal": 570.00,
    "isv": 85.50,
    "fecha_emision": "2026-01-13T15:30:00.000000Z",
    "estado": 1
  },
  "message": "Venta registrada exitosamente"
}
```

### Error: Stock Insuficiente (422)

```json
{
  "success": false,
  "error": {
    "code": "INSUFFICIENT_STOCK",
    "message": "Stock insuficiente para completar la venta",
    "details": [
      {
        "product_id": 25,
        "product_name": "Producto X",
        "requested": 5,
        "available": 2
      }
    ]
  }
}
```

### Error: Producto No Encontrado (404)

```json
{
  "success": false,
  "error": {
    "code": "PRODUCT_NOT_FOUND",
    "message": "Producto ID 999 no encontrado"
  }
}
```

### Error: Validación (422)

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Los datos enviados no son válidos",
    "details": {
      "customer_phone": [
        "El teléfono debe estar en formato +504XXXXXXXX"
      ],
      "delivery_address": [
        "La dirección de envío es requerida para entregas a domicilio"
      ]
    }
  }
}
```

## Flujo de Facturación

1. **Recepción de datos**: La API recibe los datos del pedido web
2. **Validación**: Se validan todos los campos según las reglas definidas
3. **Verificación de stock**: Se verifica que haya stock disponible para todos los productos
4. **Creación de transacción**: Se crea el registro de transacción en caja
5. **Creación de factura**: Se genera la factura en Zenvy con todos los datos
6. **Registro de items**: Se crean los items de la factura con precios y descuentos
7. **Descuento de stock**: Se descuenta el stock usando el método FIFO
8. **Respuesta**: Se retorna la confirmación con el ID de la factura

## Información Almacenada en Zenvy

La factura generada incluye:

- Datos completos del cliente (nombre, email, teléfono)
- Información del tipo de entrega
- Dirección de envío (si aplica)
- Método de pago
- Notas del pedido
- Todos los productos con sus precios y descuentos individuales
- Cálculo automático de ISV (15%)
- Descuento de stock automático

Todo esto se almacena en el campo `comentario` de la factura para referencia futura.

## Ejemplo de PHP (Cliente)

```php
<?php

$apiUrl = 'https://tu-dominio.com/api/v1/sales';
$apiKey = 'tu-api-key-aqui';

$data = [
    'customer_name' => 'María González',
    'customer_email' => 'maria@example.com',
    'customer_phone' => '+50498765432',
    
    'items' => [
        [
            'product_id' => 25,
            'quantity' => 2,
            'price' => 150.00,
            'discount' => 10
        ]
    ],
    
    'delivery_type' => 'domicilio',
    'delivery_address' => 'Colonia Trejo, Casa 25',
    
    'payment_method' => 'Tarjeta',
    'notes' => 'Llamar antes de entregar',
    
    'subtotal' => 270.00,
    'discount' => 30.00,
    'tax' => 40.50,
    'total' => 280.50
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: ' . $apiKey
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 201) {
    echo "Factura creada exitosamente: ID " . $result['data']['factura_id'];
} else {
    echo "Error: " . $result['error']['message'];
}
```

## Notas Importantes

1. **Formato de teléfono**: Debe seguir estrictamente el formato `+504XXXXXXXX` (código de Honduras + 8 dígitos)
2. **Product ID**: Se usa el ID del producto directamente, no el SKU
3. **Descuentos**: Se pueden aplicar descuentos tanto a nivel de item (porcentaje) como a nivel general (monto)
4. **Stock**: El sistema usa FIFO (First In, First Out) para descontar el stock
5. **Validación de stock**: La venta NO se procesa si algún producto no tiene stock suficiente
6. **Transacciones**: Todo el proceso es transaccional, si algo falla se revierte completamente

## Consultar Estado de Venta

**GET** `/api/v1/sales/{factura_id}`

Retorna el estado actual de una factura creada.

## Anular Venta

**PUT** `/api/v1/sales/{factura_id}/cancel`

Permite anular una venta y restaurar el stock.

```json
{
  "motivo": "Cliente solicitó cancelación"
}
```
