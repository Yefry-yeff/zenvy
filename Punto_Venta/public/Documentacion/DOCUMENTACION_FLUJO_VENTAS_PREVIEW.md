# Documentación: Flujo de Ventas con Preview (API E-commerce)

## 📋 Resumen

Este documento describe el **nuevo flujo de 2 pasos** para crear ventas desde la API de e-commerce:

1. **Crear Pedido Preview** - Validar stock sin descontar ni facturar
2. **Procesar/Facturar** - Descontar stock y generar factura en Zenvy

Este flujo resuelve:
- ✅ Error de truncamiento en campo `comentario` de factura
- ✅ Separación entre "carrito confirmado" y "pago procesado"
- ✅ Permite revisar pedido antes de facturar
- ✅ Evita descuento de stock prematuro

---

## 🔄 Flujo Completo

### Paso 1: Crear Pedido Preview

**Endpoint:** `POST /api/v1/sales/preview`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "customer_name": "Johann Ruiz",
  "customer_email": "johann_ruiz14@hotmail.com",
  "customer_phone": "+50497525987",
  "customer_rtn": "",
  "delivery_type": "domicilio",
  "delivery_address": "Oficina Francisco Morazán, Tegucigalpa",
  "payment_method": "Efectivo",
  "notes": "Dejar afuera",
  "subtotal": 1450.00,
  "tax": 217.50,
  "discount": 0,
  "total": 1667.50,
  "items": [
    {
      "product_id": 123,
      "quantity": 2,
      "price": 725.00,
      "discount": 0
    }
  ]
}
```

**Respuesta Exitosa (201):**
```json
{
  "success": true,
  "data": {
    "pedido_id": 45,
    "numero_pedido": "PW-2026-0045",
    "estado": "pendiente",
    "cliente": "Johann Ruiz",
    "email": "johann_ruiz14@hotmail.com",
    "telefono": "+50497525987",
    "total": 1667.50,
    "subtotal": 1450.00,
    "isv": 217.50,
    "descuento": 0.00,
    "items_count": 1,
    "fecha_pedido": "2026-01-14T10:30:00-06:00"
  },
  "message": "Pedido creado exitosamente. Use el endpoint /process para facturar."
}
```

**Características del Preview:**
- ✅ Valida que los productos existan
- ✅ Valida que haya stock disponible
- ✅ NO descuenta stock
- ✅ NO genera factura
- ✅ Guarda información completa del pedido
- ✅ Genera número de pedido único (PW-YYYY-XXXX)

---

### Paso 2: Procesar/Facturar Pedido

**Endpoint:** `POST /api/v1/sales/{pedido_id}/process`

**Headers:**
```
Authorization: Bearer {token}
```

**Ejemplo:**
```
POST /api/v1/sales/45/process
```

**Respuesta Exitosa (200):**
```json
{
  "success": true,
  "data": {
    "factura_id": 1264,
    "pedido_id": 45,
    "cliente": "Johann Ruiz",
    "total": 1667.50,
    "subtotal": 1450.00,
    "isv": 217.50,
    "fecha_emision": "2026-01-14 10:35:20",
    "estado": 1
  },
  "message": "Pedido facturado exitosamente"
}
```

**Características del Proceso:**
- ✅ Vuelve a validar stock (por si cambió desde el preview)
- ✅ Descuenta stock usando FIFO
- ✅ Genera factura en Zenvy
- ✅ Actualiza estado del pedido a "facturado"
- ✅ Crea transacción de caja
- ✅ Comentario de factura optimizado (corto)

---

## 🚫 Errores Comunes

### Error: Stock Insuficiente (422)
```json
{
  "success": false,
  "error": {
    "code": "INSUFFICIENT_STOCK",
    "message": "Stock insuficiente para completar el pedido",
    "details": [
      {
        "product_id": 123,
        "product_name": "Papel Bond",
        "requested": 10,
        "available": 5
      }
    ]
  }
}
```

### Error: Pedido No Encontrado (404)
```json
{
  "success": false,
  "error": {
    "code": "ORDER_NOT_FOUND",
    "message": "Pedido no encontrado"
  }
}
```

### Error: Pedido Ya Procesado (500)
```json
{
  "success": false,
  "error": {
    "code": "ORDER_PROCESSING_FAILED",
    "message": "El pedido PW-2026-0045 ya fue procesado (Estado: facturado)"
  }
}
```

---

## 🗄️ Base de Datos

### Tabla: `pedidos_web`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | ID único del pedido |
| numero_pedido | VARCHAR(50) | Código único (PW-YYYY-XXXX) |
| estado | ENUM | pendiente, procesando, facturado, rechazado |
| cliente_nombre | VARCHAR(255) | Nombre del cliente |
| cliente_email | VARCHAR(255) | Email del cliente |
| cliente_telefono | VARCHAR(50) | Teléfono del cliente |
| cliente_rtn | VARCHAR(50) | RTN del cliente (opcional) |
| cliente_direccion | TEXT | Dirección de envío completa |
| subtotal | DECIMAL(12,2) | Subtotal antes de ISV |
| descuento | DECIMAL(12,2) | Monto de descuento |
| isv | DECIMAL(12,2) | Impuesto sobre ventas (15%) |
| total | DECIMAL(12,2) | Total a pagar |
| metodo_pago | VARCHAR(100) | Método de pago seleccionado |
| notas | VARCHAR(500) | Notas del cliente (max 500 chars) |
| metadata | JSON | Metadata adicional (delivery_type, api_client, etc.) |
| factura_id | INT | ID de la factura (NULL si no procesado) |
| fecha_procesado | DATETIME | Fecha de procesamiento |
| fecha_facturado | DATETIME | Fecha de facturación |
| leido | BOOLEAN | Si fue leído en la bandeja |

### Tabla: `pedido_web_item`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | ID único del item |
| pedido_web_id | INT | ID del pedido padre |
| producto_id | INT | ID del producto |
| nombre_producto | VARCHAR(255) | Nombre al momento del pedido |
| cantidad | DECIMAL(10,2) | Cantidad solicitada |
| precio_unidad | DECIMAL(12,2) | Precio unitario |
| subtotal | DECIMAL(12,2) | Subtotal del item |
| descuento | DECIMAL(12,2) | Descuento aplicado |
| isv | DECIMAL(12,2) | ISV del item |
| total | DECIMAL(12,2) | Total del item |

---

## 📝 Ejemplo de Uso Completo

### JavaScript (Fetch API)

```javascript
// 1. Crear pedido preview
async function crearPedido(datosCarrito) {
  const response = await fetch('https://zenvy.com/api/v1/sales/preview', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer YOUR_TOKEN',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(datosCarrito)
  });
  
  const result = await response.json();
  
  if (result.success) {
    console.log('Pedido creado:', result.data.numero_pedido);
    return result.data.pedido_id;
  } else {
    console.error('Error:', result.error);
    throw new Error(result.error.message);
  }
}

// 2. Procesar/Facturar pedido (después de confirmar pago)
async function facturarPedido(pedidoId) {
  const response = await fetch(`https://zenvy.com/api/v1/sales/${pedidoId}/process`, {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer YOUR_TOKEN'
    }
  });
  
  const result = await response.json();
  
  if (result.success) {
    console.log('Factura generada:', result.data.factura_id);
    return result.data;
  } else {
    console.error('Error:', result.error);
    throw new Error(result.error.message);
  }
}

// Uso
try {
  // Paso 1: Cliente confirma su orden
  const pedidoId = await crearPedido({
    customer_name: "Johann Ruiz",
    customer_email: "johann_ruiz14@hotmail.com",
    customer_phone: "+50497525987",
    delivery_type: "domicilio",
    delivery_address: "Tegucigalpa, Francisco Morazán",
    payment_method: "Efectivo",
    subtotal: 1450.00,
    tax: 217.50,
    total: 1667.50,
    items: [
      { product_id: 123, quantity: 2, price: 725.00, discount: 0 }
    ]
  });
  
  console.log(`Pedido ${pedidoId} creado. Esperando confirmación de pago...`);
  
  // Paso 2: Después de confirmar pago
  const factura = await facturarPedido(pedidoId);
  console.log(`Factura ${factura.factura_id} generada exitosamente`);
  
} catch (error) {
  console.error('Error en el proceso:', error);
}
```

### PHP (Guzzle)

```php
<?php
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://zenvy.com/api/v1/',
    'headers' => [
        'Authorization' => 'Bearer YOUR_TOKEN',
        'Content-Type' => 'application/json'
    ]
]);

// 1. Crear pedido preview
$datosCarrito = [
    'customer_name' => 'Johann Ruiz',
    'customer_email' => 'johann_ruiz14@hotmail.com',
    'customer_phone' => '+50497525987',
    'delivery_type' => 'domicilio',
    'delivery_address' => 'Tegucigalpa, Francisco Morazán',
    'payment_method' => 'Efectivo',
    'subtotal' => 1450.00,
    'tax' => 217.50,
    'total' => 1667.50,
    'items' => [
        [
            'product_id' => 123,
            'quantity' => 2,
            'price' => 725.00,
            'discount' => 0
        ]
    ]
];

$response = $client->post('sales/preview', [
    'json' => $datosCarrito
]);

$result = json_decode($response->getBody(), true);

if ($result['success']) {
    $pedidoId = $result['data']['pedido_id'];
    echo "Pedido creado: {$result['data']['numero_pedido']}\n";
    
    // 2. Procesar/Facturar (después de confirmar pago)
    $response = $client->post("sales/{$pedidoId}/process");
    $factura = json_decode($response->getBody(), true);
    
    if ($factura['success']) {
        echo "Factura generada: {$factura['data']['factura_id']}\n";
    }
}
?>
```

---

## ⚙️ Configuración de Base de Datos

Para implementar este flujo, ejecutar el siguiente script SQL:

```sql
-- Crear tablas si no existen
SOURCE /path/to/crear_pedidos_web.sql;

-- Verificar tablas
SHOW TABLES LIKE 'pedidos_web%';

-- Ver estructura
DESCRIBE pedidos_web;
DESCRIBE pedido_web_item;
```

---

## 🔍 Consultas Útiles

### Ver pedidos pendientes
```sql
SELECT id, numero_pedido, cliente_nombre, total, estado, created_at
FROM pedidos_web
WHERE estado = 'pendiente'
ORDER BY created_at DESC;
```

### Ver pedidos no leídos
```sql
SELECT id, numero_pedido, cliente_nombre, total
FROM pedidos_web
WHERE estado = 'pendiente' AND leido = FALSE;
```

### Ver pedidos con su factura
```sql
SELECT 
    pw.numero_pedido,
    pw.cliente_nombre,
    pw.total,
    pw.estado,
    f.id as factura_id,
    f.fecha_emision
FROM pedidos_web pw
LEFT JOIN factura f ON pw.factura_id = f.id
WHERE pw.estado = 'facturado';
```

---

## 🚀 Migración desde Flujo Antiguo

Si estabas usando el endpoint directo `POST /api/v1/sales`, puedes:

### Opción 1: Migrar al nuevo flujo (Recomendado)
1. Cambiar `POST /api/v1/sales` por `POST /api/v1/sales/preview`
2. Agregar llamada a `POST /api/v1/sales/{id}/process` después de confirmar pago

### Opción 2: Mantener compatibilidad
El endpoint antiguo sigue funcionando pero está deprecado. Se recomienda migrar al nuevo flujo.

---

## 📊 Estados del Pedido

| Estado | Descripción |
|--------|-------------|
| **pendiente** | Pedido creado, esperando procesamiento |
| **procesando** | Pedido en proceso de facturación |
| **facturado** | Pedido facturado exitosamente |
| **rechazado** | Pedido rechazado por falta de stock u otro motivo |

---

## 💡 Ventajas del Nuevo Flujo

1. **Comentarios optimizados**: Evita error de truncamiento en DB
2. **Stock validado dos veces**: Al crear y al procesar
3. **Mayor control**: Permite cancelar antes de descontar stock
4. **Mejor UX**: Cliente ve pedido confirmado antes de facturar
5. **Auditoría**: Registro completo del flujo en `pedidos_web`
6. **Flexibilidad**: Permite diferentes métodos de pago y confirmaciones

---

## 📞 Soporte

Para preguntas o problemas, contactar al equipo de desarrollo.

**Última actualización:** 14 de enero de 2026
