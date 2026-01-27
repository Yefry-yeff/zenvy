# 📡 API REST de Zenvy POS - Guía Completa

## 🎯 Overview

**Sí, Zenvy tiene una API REST completa** para que tu página web acceda a:
- ✅ Inventario de productos en tiempo real
- ✅ Búsqueda por categoría
- ✅ Búsqueda por código de barras
- ✅ Validación de stock
- ✅ Gestión de pedidos web
- ✅ Procesamiento de ventas

---

## 🔐 Autenticación

### 1. Obtener Token JWT

**Endpoint:**
```
POST http://127.0.0.1:8001/api/v1/auth/token
```

**Headers:**
```
Content-Type: application/json
```

**Body (ejemplo):**
```json
{
  "email": "admin@zenvy.local",
  "password": "password",
  "name": "Mi Página Web",
  "scope": "producto-inventario"
}
```

**Respuesta:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_in": 3600,
  "token_type": "Bearer"
}
```

### 2. Usar Token en Requests

Todos los endpoints requieren este header:

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json
```

---

## 📚 Endpoints Disponibles

### 🏥 Health Check (Sin autenticación)

```
GET http://127.0.0.1:8001/api/health
```

**Respuesta:**
```json
{
  "success": true,
  "message": "API funcionando correctamente",
  "version": "1.0.0",
  "timestamp": "2026-01-23T21:48:33-06:00"
}
```

---

## 📦 Inventario

### 1. Obtener Todos los Productos

```
GET http://127.0.0.1:8001/api/v1/inventory
```

**Parámetros Query (opcionales):**
```
?per_page=20                    # Productos por página (default: 15)
?category_id=2                  # Filtrar por categoría
?search=cuaderno                # Buscar por nombre
?min_stock=10                   # Solo productos con stock >= 10
?available=true                 # Solo productos disponibles
?order_by=nombre                # Ordenar por: nombre, stock, precio
?order_direction=asc            # asc o desc
```

**Ejemplo:**
```bash
curl "http://127.0.0.1:8001/api/v1/inventory?per_page=50&available=true" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Cuaderno Universitario Norma",
      "codigo_barra": "760573020163",
      "codigo_estatal": "CUA-100-NORMA",
      "descripcion": "Cuaderno de 100 hojas",
      "stock": 45,
      "precio_venta": 40.00,
      "precio_compra": 25.00,
      "estado": "activo"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  }
}
```

### 2. Obtener Productos por Categoría

```
GET http://127.0.0.1:8001/api/v1/inventory/by-category
```

**Parámetros Query:**
```
?per_page=20        # Opcional
?search=texto       # Opcional: buscar dentro de categoría
```

**Ejemplo:**
```bash
curl "http://127.0.0.1:8001/api/v1/inventory/by-category?per_page=100" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "categoria_id": 2,
      "categoria_nombre": "Escolar",
      "total_productos": 15,
      "stock_total": 250,
      "productos": [
        {
          "id": 1,
          "codigo_estatal": "CUA-100-NORMA",
          "codigo_barra": "760573020163",
          "nombre": "Cuaderno Universitario Norma",
          "stock": 45,
          "precio_venta": 40.00
        }
      ]
    },
    {
      "categoria_id": 4,
      "categoria_nombre": "Bebidas Alcoholicas",
      "total_productos": 8,
      "stock_total": 120,
      "productos": [...]
    }
  ]
}
```

### 3. Obtener Categorías

```
GET http://127.0.0.1:8001/api/v1/inventory/categories
```

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "nombre": "Escolar",
      "total_productos": 15,
      "stock_total": 250
    },
    {
      "id": 4,
      "nombre": "Bebidas Alcoholicas",
      "total_productos": 8,
      "stock_total": 120
    }
  ]
}
```

### 4. Buscar Producto por Código de Barras

```
GET http://127.0.0.1:8001/api/v1/inventory/barcode/{codigo_barra}
```

**Ejemplo:**
```bash
curl "http://127.0.0.1:8001/api/v1/inventory/barcode/760573020163" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Cuaderno Universitario Norma",
    "codigo_barra": "760573020163",
    "codigo_estatal": "CUA-100-NORMA",
    "stock": 45,
    "precio_venta": 40.00,
    "precio_compra": 25.00,
    "descripcion": "Cuaderno de 100 hojas",
    "marca": "Norma",
    "estado": "activo"
  }
}
```

### 5. Productos con Stock Bajo

```
GET http://127.0.0.1:8001/api/v1/inventory/low-stock
```

**Parámetros Query (opcionales):**
```
?threshold=10       # Stock mínimo a considerar como "bajo"
?limit=20          # Cantidad de productos a retornar
```

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "nombre": "Cocacola Zero",
      "stock": 3,
      "stock_minimo": 10,
      "diferencia": -7
    }
  ]
}
```

### 6. Validar Stock Disponible

```
POST http://127.0.0.1:8001/api/v1/inventory/validate-stock
```

**Body:**
```json
{
  "items": [
    {
      "producto_id": 1,
      "cantidad_solicitada": 5
    },
    {
      "producto_id": 5,
      "cantidad_solicitada": 2
    }
  ]
}
```

**Respuesta:**
```json
{
  "success": true,
  "available": true,
  "items": [
    {
      "producto_id": 1,
      "cantidad_solicitada": 5,
      "stock_disponible": 45,
      "disponible": true
    },
    {
      "producto_id": 5,
      "cantidad_solicitada": 2,
      "stock_disponible": 3,
      "disponible": true
    }
  ],
  "timestamp": "2026-01-23T21:48:33-06:00"
}
```

### 7. Forzar Sincronización Completa

```
POST http://127.0.0.1:8001/api/v1/inventory/sync/force
```

**Body:** (vacío o {})
```json
{}
```

**Respuesta:**
```json
{
  "success": true,
  "synced_at": "2026-01-23T21:48:33-06:00",
  "total_categories": 5,
  "total_products": 45,
  "total_stock": 1250,
  "sync_status": "enviado",
  "webhook_url": "http://127.0.0.1:8001/api/webhook/inventory"
}
```

---

## 🛒 Pedidos Web

### 1. Crear Pedido desde Página Web

```
POST http://127.0.0.1:8001/api/v1/orders
```

**Body:**
```json
{
  "cliente_nombre": "Juan Pérez",
  "cliente_email": "juan@email.com",
  "cliente_telefono": "12345678",
  "cliente_direccion": "Calle Principal 123",
  "items": [
    {
      "producto_id": 1,
      "cantidad": 5,
      "precio_unitario": 40.00
    },
    {
      "producto_id": 5,
      "cantidad": 2,
      "precio_unitario": 100.00
    }
  ],
  "total": 300.00,
  "metodo_pago": "efectivo",
  "notas": "Entrega en horario de oficina"
}
```

**Respuesta:**
```json
{
  "success": true,
  "pedido_id": 12345,
  "numero_referencia": "PED-2026-01-001",
  "estado": "pendiente",
  "total": 300.00,
  "created_at": "2026-01-23T21:48:33-06:00"
}
```

### 2. Obtener Pedidos Pendientes

```
GET http://127.0.0.1:8001/api/v1/orders/pending
```

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 12345,
      "numero_referencia": "PED-2026-01-001",
      "cliente_nombre": "Juan Pérez",
      "total": 300.00,
      "estado": "pendiente",
      "items_count": 2,
      "created_at": "2026-01-23T21:48:33-06:00"
    }
  ]
}
```

### 3. Obtener Detalles de Pedido

```
GET http://127.0.0.1:8001/api/v1/orders/{id}
```

**Ejemplo:**
```bash
curl "http://127.0.0.1:8001/api/v1/orders/12345" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 4. Procesar Pedido (Convertir a Factura)

```
POST http://127.0.0.1:8001/api/v1/orders/{id}/process
```

**Body:**
```json
{
  "metodo_pago": "tarjeta",
  "numero_factura": "FAC-001"
}
```

---

## 💰 Ventas / Facturación

### 1. Crear Venta (Flujo Directo - Simple)

```
POST http://127.0.0.1:8001/api/v1/sales
```

**Body:**
```json
{
  "cliente_id": 1,
  "items": [
    {
      "producto_id": 1,
      "cantidad": 5
    },
    {
      "producto_id": 5,
      "cantidad": 2
    }
  ],
  "descuento": 0,
  "metodo_pago": "efectivo"
}
```

**Respuesta:**
```json
{
  "success": true,
  "factura_id": 500,
  "numero_factura": "FAC-2026-00500",
  "total": 300.00,
  "estado": "confirmada"
}
```

### 2. Preview de Venta (Recomendado - Verifica Stock Primero)

```
POST http://127.0.0.1:8001/api/v1/sales/preview
```

**Body:**
```json
{
  "cliente_id": 1,
  "items": [
    {
      "producto_id": 1,
      "cantidad": 5
    }
  ]
}
```

**Respuesta:**
```json
{
  "success": true,
  "preview_id": "preview_abc123",
  "items": [
    {
      "producto_id": 1,
      "nombre": "Cuaderno Universitario Norma",
      "cantidad": 5,
      "precio_unitario": 40.00,
      "subtotal": 200.00,
      "stock_disponible": 45,
      "disponible": true
    }
  ],
  "subtotal": 200.00,
  "isv": 30.00,
  "total": 230.00,
  "valido": true
}
```

### 3. Procesar Venta (Después del Preview)

```
POST http://127.0.0.1:8001/api/v1/sales/{preview_id}/process
```

**Body:**
```json
{
  "metodo_pago": "efectivo"
}
```

---

## 🧪 Ejemplos de Código

### JavaScript / Fetch API

```javascript
// 1. Obtener Token
const getToken = async () => {
  const response = await fetch('http://127.0.0.1:8001/api/v1/auth/token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      email: 'admin@zenvy.local',
      password: 'password',
      name: 'Mi Página Web',
      scope: 'producto-inventario'
    })
  });
  const data = await response.json();
  return data.token;
};

// 2. Obtener Inventario
const getInventory = async (token) => {
  const response = await fetch('http://127.0.0.1:8001/api/v1/inventory/by-category', {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  return await response.json();
};

// 3. Usar
(async () => {
  const token = await getToken();
  const inventory = await getInventory(token);
  console.log(inventory);
})();
```

### PHP / cURL

```php
<?php

// 1. Obtener Token
function getZenvyToken() {
    $url = 'http://127.0.0.1:8001/api/v1/auth/token';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => 'admin@zenvy.local',
        'password' => 'password',
        'name' => 'Mi Página Web',
        'scope' => 'producto-inventario'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return $data['token'] ?? null;
}

// 2. Obtener Inventario
function getZenvyInventory($token) {
    $url = 'http://127.0.0.1:8001/api/v1/inventory/by-category';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// 3. Usar
$token = getZenvyToken();
$inventory = getZenvyInventory($token);

echo '<h1>Inventario desde Zenvy</h1>';
foreach ($inventory['data'] as $categoria) {
    echo '<h2>' . $categoria['categoria_nombre'] . '</h2>';
    echo '<p>Stock Total: ' . $categoria['stock_total'] . '</p>';
    
    foreach ($categoria['productos'] as $producto) {
        echo '<div>';
        echo 'Producto: ' . $producto['nombre'] . '<br>';
        echo 'Stock: ' . $producto['stock'] . '<br>';
        echo 'Precio: $' . $producto['precio_venta'] . '<br>';
        echo '</div>';
    }
}
?>
```

---

## 🔧 Configuración de Producción

### Cambiar URL Base

Para producción, en tu código reemplaza:
```
http://127.0.0.1:8001 → https://tudominio-zenvy.com
```

### Seguridad Recomendada

1. ✅ **HTTPS obligatorio** en producción
2. ✅ **CORS configurado** en Zenvy si es dominio diferente
3. ✅ **Token con expiración** (15-60 minutos)
4. ✅ **Rate limiting** (máximo X requests por minuto)
5. ✅ **Validar entrada** (sanitizar parámetros)

---

## 📊 Referencia Rápida

| Endpoint | Método | Descripción |
|----------|--------|-----------|
| `/api/health` | GET | Verificar API activa |
| `/api/v1/auth/token` | POST | Obtener token JWT |
| `/api/v1/inventory` | GET | Todos los productos |
| `/api/v1/inventory/by-category` | GET | Productos por categoría |
| `/api/v1/inventory/categories` | GET | Listar categorías |
| `/api/v1/inventory/barcode/{code}` | GET | Buscar por código de barras |
| `/api/v1/inventory/low-stock` | GET | Productos con stock bajo |
| `/api/v1/inventory/validate-stock` | POST | Validar disponibilidad |
| `/api/v1/inventory/sync/force` | POST | Forzar sincronización |
| `/api/v1/orders` | POST | Crear pedido |
| `/api/v1/orders/pending` | GET | Pedidos pendientes |
| `/api/v1/sales` | POST | Crear venta directa |
| `/api/v1/sales/preview` | POST | Preview de venta |

---

## ❓ Preguntas Frecuentes

**P: ¿El token expira?**
R: Por defecto sí (1 hora). Si necesitas no-expiración, contacta soporte.

**P: ¿Puedo cachear el inventario?**
R: Sí, la API cachea 5 minutos internamente. Tu página web puede cachear también.

**P: ¿Qué pasa si falla la conexión?**
R: La API devuelve `success: false` con descripción del error.

**P: ¿Soporta CORS?**
R: Sí, está configurado para dominios permitidos.

**P: ¿Puedo filtrar por múltiples categorías?**
R: Actualmente no. Obtén todas y filtra en tu código.

---

## 📞 Testing Rápido

### Con Postman

1. **Import colección** desde: `Insomnia_Zenvy_API_Ventas.json`
2. **Configurar variable** `{{base_url}}` = `http://127.0.0.1:8001`
3. **Ejecutar request** `/api/health` para verificar
4. **Obtener token** y guardar en variable `{{token}}`
5. **Hacer requests** a endpoints protegidos

### Con cURL

```bash
# Verificar API
curl http://127.0.0.1:8001/api/health

# Obtener inventario (necesita token)
TOKEN="tu_token_aqui"
curl "http://127.0.0.1:8001/api/v1/inventory/by-category" \
  -H "Authorization: Bearer $TOKEN"
```

---

¡Tu API está lista! 🚀 Cualquier duda, avísame.
