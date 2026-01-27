# 📚 Documentación Completa - API REST Sistema POS

**Sistema POS Zenvy**  
**Versión API:** v1  
**Fecha:** 13 de Enero, 2026  
**Autor:** Yefry Yeff

---

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Instalación y Configuración](#instalación-y-configuración)
4. [Autenticación](#autenticación)
5. [Endpoints del API](#endpoints-del-api)
6. [Modelos de Datos](#modelos-de-datos)
7. [Estados de Venta](#estados-de-venta)
8. [Flujos de Integración](#flujos-de-integración)
9. [Manejo de Errores](#manejo-de-errores)
10. [Seguridad](#seguridad)
11. [Testing](#testing)
12. [Deployment](#deployment)
13. [Troubleshooting](#troubleshooting)

---

## 🎯 Introducción

Este API REST permite la integración entre el sistema POS Zenvy y plataformas e-commerce externas. El POS es la **única fuente de verdad** para inventario y facturación.

### Características Principales

✅ **Arquitectura REST** con JSON  
✅ **Autenticación JWT** segura  
✅ **Control de concurrencia** para inventario  
✅ **Auditoría completa** de transacciones  
✅ **Rate limiting** por cliente  
✅ **Validación robusta** de datos  
✅ **Manejo de errores** estandarizado  
✅ **Alta disponibilidad** preparada  

### URLs Base

- **Desarrollo:** `http://localhost/api/v1`
- **Producción:** `https://pos.tudominio.com/api/v1`

---

## 🏗️ Arquitectura del Sistema

### Diagrama de Arquitectura

```
┌─────────────────────────────────────────┐
│         E-commerce (Cliente)            │
└─────────────────┬───────────────────────┘
                  │ HTTPS/JWT
┌─────────────────▼───────────────────────┐
│           API Gateway / Router          │
│  - Autenticación JWT                    │
│  - Rate Limiting                        │
│  - Request Validation                   │
│  - Auditoría                            │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────▼───────────────────────┐
│         Capa de Servicios               │
│  - InventoryService                     │
│  - SalesService                         │
│  - ApiAuthService                       │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────▼───────────────────────┐
│      Repositorios / Modelos             │
│  - ProductRepository                    │
│  - SaleRepository                       │
│  - DB Transacciones                     │
│  - Cache (Redis opcional)               │
└─────────────────────────────────────────┘
```

### Estructura de Carpetas

```
Punto_Venta/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── V1/
│   │   │           ├── AuthController.php
│   │   │           ├── InventoryController.php
│   │   │           └── SalesController.php
│   │   ├── Middleware/
│   │   │   └── Api/
│   │   │       ├── ApiKeyAuth.php
│   │   │       ├── AuditApiRequest.php
│   │   │       └── RateLimitApi.php
│   │   ├── Requests/
│   │   │   └── Api/
│   │   │       ├── CreateSaleRequest.php
│   │   │       └── ValidateStockRequest.php
│   │   └── Resources/
│   │       └── Api/
│   │           ├── ProductResource.php
│   │           ├── SaleResource.php
│   │           └── SaleItemResource.php
│   ├── Services/
│   │   ├── Api/
│   │   │   ├── InventoryService.php
│   │   │   └── SalesService.php
│   │   └── Auth/
│   │       └── ApiAuthService.php
│   ├── Repositories/
│   │   ├── ProductRepository.php
│   │   └── SaleRepository.php
│   ├── Models/
│   │   ├── ApiClient.php
│   │   ├── ApiLog.php
│   │   └── SaleStatus.php
│   └── Exceptions/
│       └── Api/
│           ├── InsufficientStockException.php
│           ├── ProductNotFoundException.php
│           └── ApiAuthException.php
├── routes/
│   └── api.php
├── config/
│   └── api.php
└── database/
    └── migrations/
        ├── 2026_01_13_000001_create_api_clients_table.php
        └── 2026_01_13_000002_create_api_logs_table.php
```

---

## ⚙️ Instalación y Configuración

### 1. Requisitos Previos

- PHP 8.1 o superior
- MySQL 8.0 o superior
- Composer
- Laravel 11.x
- Extensión PHP JWT: `composer require firebase/php-jwt`

### 2. Instalación

```bash
# 1. Navegar al directorio del proyecto
cd C:\laragon\www\Procadts\zenvy\Punto_Venta

# 2. Instalar dependencia JWT
composer require firebase/php-jwt

# 3. Ejecutar migraciones
php artisan migrate

# O ejecutar el script SQL directamente
mysql -u root -p punto_venta < "../Bases de datos/Script/crear_tablas_api.sql"
```

### 3. Configuración del Archivo .env

```env
# API Configuration
API_AUTH_DRIVER=jwt
API_JWT_SECRET=tu-clave-secreta-muy-segura-aqui
API_TOKEN_TTL=3600
API_RATE_LIMIT=60

# API Audit
API_AUDIT_ENABLED=true

# CORS (para e-commerce)
API_ALLOWED_ORIGINS=https://tutienda.com,https://www.tutienda.com
```

### 4. Publicar Configuración

El archivo `config/api.php` ya está creado con todas las configuraciones necesarias.

### 5. Crear Cliente API

```php
php artisan tinker

use App\Services\Auth\ApiAuthService;

$authService = app(ApiAuthService::class);

$client = $authService->createClient('Mi E-commerce', [
    'rate_limit_per_minute' => 100,
    'ip_whitelist' => ['192.168.1.100', '10.0.0.50']
]);

// IMPORTANTE: Guardar estos datos de forma segura
echo "API Key: " . $client->api_key . "\n";
echo "API Secret: " . $client->plain_secret . "\n";
```

**⚠️ IMPORTANTE:** El `api_secret` solo se muestra una vez. Guárdalo de forma segura.

---

## 🔐 Autenticación

### Flujo de Autenticación JWT

```
Cliente                      API
  │                           │
  │  1. POST /api/v1/auth/token │
  │  {api_key, api_secret}    │
  ├──────────────────────────►│
  │                           │
  │  2. JWT Token             │
  │◄──────────────────────────┤
  │                           │
  │  3. GET /api/v1/inventory │
  │  Authorization: Bearer {token}
  ├──────────────────────────►│
  │                           │
  │  4. Datos solicitados     │
  │◄──────────────────────────┤
```

### Obtener Token

**Endpoint:** `POST /api/v1/auth/token`

**Request:**
```json
{
  "api_key": "pk_1234567890abcdef",
  "api_secret": "sk_abcdefghijklmnopqrstuvwxyz"
}
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "client": {
      "id": 1,
      "name": "Mi E-commerce"
    }
  }
}
```

### Usar Token en Requests

Incluir en el header de todas las peticiones:

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Ejemplo con cURL:**

```bash
curl -X GET "https://pos.tudominio.com/api/v1/inventory" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Content-Type: application/json"
```

**Ejemplo con PHP:**

```php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://pos.tudominio.com/api/v1/inventory");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
```

---

## 📡 Endpoints del API

### 1. Salud del API

#### GET `/api/health`

Verificar estado del API (sin autenticación).

**Response 200:**
```json
{
  "success": true,
  "message": "API funcionando correctamente",
  "version": "v1",
  "timestamp": "2026-01-13T10:30:00Z"
}
```

---

### 2. Inventario

#### GET `/api/v1/inventory`

Obtener inventario completo o filtrado.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `category_id` (opcional): ID de categoría
- `available` (opcional): `true` para solo productos con stock
- `search` (opcional): Búsqueda por nombre, SKU o código de barras
- `min_stock` (opcional): Stock mínimo
- `per_page` (opcional): Items por página (default: 50, max: 200)
- `page` (opcional): Número de página

**Ejemplo:**
```
GET /api/v1/inventory?available=true&per_page=20&page=1
```

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sku": "PROD-001",
      "codigo_barra": "7501234567890",
      "nombre": "Producto Ejemplo",
      "descripcion": "Descripción del producto",
      "precio_venta": 150.00,
      "stock_actual": 25,
      "stock_minimo": 5,
      "categoria": {
        "id": 1,
        "nombre": "Categoría A"
      },
      "activo": true,
      "created_at": "2025-01-01T00:00:00Z",
      "updated_at": "2026-01-13T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 250,
    "last_page": 13,
    "from": 1,
    "to": 20
  }
}
```

---

#### GET `/api/v1/inventory/{sku}`

Obtener producto específico por SKU.

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "sku": "PROD-001",
    "codigo_barra": "7501234567890",
    "nombre": "Producto Ejemplo",
    "descripcion": "Descripción completa",
    "precio_venta": 150.00,
    "stock_actual": 25,
    "stock_minimo": 5,
    "categoria": {
      "id": 1,
      "nombre": "Categoría A"
    },
    "activo": true,
    "created_at": "2025-01-01T00:00:00Z",
    "updated_at": "2026-01-13T10:30:00Z"
  }
}
```

**Response 404:**
```json
{
  "success": false,
  "error": {
    "code": "PRODUCT_NOT_FOUND",
    "message": "Producto con SKU PROD-001 no encontrado"
  }
}
```

---

#### GET `/api/v1/inventory/barcode/{barcode}`

Obtener producto por código de barras.

**Response:** Igual que `/inventory/{sku}`

---

#### POST `/api/v1/inventory/validate-stock`

Validar disponibilidad de stock para múltiples productos.

**Request:**
```json
{
  "items": [
    {
      "sku": "PROD-001",
      "quantity": 5
    },
    {
      "sku": "PROD-002",
      "quantity": 3
    }
  ]
}
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "available": false,
    "items": [
      {
        "sku": "PROD-001",
        "product_id": 1,
        "product_name": "Producto Ejemplo",
        "requested": 5,
        "available": 25,
        "is_available": true
      },
      {
        "sku": "PROD-002",
        "product_id": 2,
        "product_name": "Producto 2",
        "requested": 3,
        "available": 2,
        "is_available": false
      }
    ]
  }
}
```

---

#### GET `/api/v1/inventory/low-stock`

Obtener productos con stock bajo.

**Query Parameters:**
- `threshold` (opcional): Umbral de stock bajo (default: 5)

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "sku": "PROD-005",
      "nombre": "Producto con Stock Bajo",
      "stock_actual": 2,
      "stock_minimo": 10,
      "categoria": "Categoría B"
    }
  ],
  "meta": {
    "count": 1,
    "threshold": 5
  }
}
```

---

### 3. Ventas / Facturación

#### POST `/api/v1/sales`

Registrar una venta / facturar pedido.

**Request:**
```json
{
  "external_order_id": "WEB-12345",
  "cliente": {
    "documento": "1234567890",
    "nombre": "Juan Pérez",
    "email": "juan@example.com",
    "telefono": "+504 9876-5432",
    "direccion": "Tegucigalpa, Honduras"
  },
  "items": [
    {
      "sku": "PROD-001",
      "cantidad": 2,
      "precio_unitario": 150.00
    },
    {
      "sku": "PROD-002",
      "cantidad": 1,
      "precio_unitario": 200.00
    }
  ],
  "subtotal": 500.00,
  "descuento": 50.00,
  "impuestos": 85.50,
  "total": 535.50,
  "forma_pago": "tarjeta_credito",
  "metadatos": {
    "shipping_method": "express",
    "tracking_number": "TRACK-123",
    "notes": "Entregar en horario de oficina"
  }
}
```

**Response 201:**
```json
{
  "success": true,
  "data": {
    "factura_id": 12345,
    "numero_factura": "FAC-2026-00123",
    "external_order_id": "WEB-12345",
    "estado": "completada",
    "cliente": {
      "documento": "1234567890",
      "nombre": "Juan Pérez",
      "email": "juan@example.com",
      "telefono": "+504 9876-5432",
      "direccion": "Tegucigalpa, Honduras"
    },
    "items": [
      {
        "producto_id": 1,
        "sku": "PROD-001",
        "nombre": "Producto Ejemplo",
        "cantidad": 2,
        "precio_unitario": 150.00,
        "subtotal": 300.00
      },
      {
        "producto_id": 2,
        "sku": "PROD-002",
        "nombre": "Producto 2",
        "cantidad": 1,
        "precio_unitario": 200.00,
        "subtotal": 200.00
      }
    ],
    "subtotal": 500.00,
    "descuento": 50.00,
    "impuestos": 85.50,
    "total": 535.50,
    "forma_pago": "tarjeta_credito",
    "metadatos": {
      "shipping_method": "express",
      "tracking_number": "TRACK-123"
    },
    "created_at": "2026-01-13T10:30:00Z",
    "updated_at": "2026-01-13T10:30:00Z"
  },
  "message": "Venta registrada exitosamente"
}
```

**Response 422 (Stock Insuficiente):**
```json
{
  "success": false,
  "error": {
    "code": "INSUFFICIENT_STOCK",
    "message": "Stock insuficiente para completar la venta",
    "details": {
      "items": [
        {
          "sku": "PROD-002",
          "product_name": "Producto 2",
          "requested": 1,
          "available": 0
        }
      ]
    }
  }
}
```

**Response 404 (Producto No Encontrado):**
```json
{
  "success": false,
  "error": {
    "code": "PRODUCT_NOT_FOUND",
    "message": "Producto con SKU PROD-999 no encontrado"
  }
}
```

---

#### GET `/api/v1/sales/{id}`

Consultar estado de una venta.

**Response 200:**
```json
{
  "success": true,
  "data": {
    "factura_id": 12345,
    "numero_factura": "FAC-2026-00123",
    "external_order_id": "WEB-12345",
    "estado": "completada",
    "cliente": {
      "documento": "1234567890",
      "nombre": "Juan Pérez",
      "email": "juan@example.com",
      "telefono": "+504 9876-5432",
      "direccion": "Tegucigalpa, Honduras"
    },
    "items": [...],
    "subtotal": 500.00,
    "descuento": 50.00,
    "impuestos": 85.50,
    "total": 535.50,
    "forma_pago": "tarjeta_credito",
    "created_at": "2026-01-13T10:30:00Z",
    "updated_at": "2026-01-13T10:30:00Z"
  }
}
```

**Response 404:**
```json
{
  "success": false,
  "error": {
    "code": "SALE_NOT_FOUND",
    "message": "Venta no encontrada"
  }
}
```

---

#### PUT `/api/v1/sales/{id}/cancel`

Anular una venta.

**Request:**
```json
{
  "motivo": "Solicitud del cliente - Cambió de opinión",
  "external_order_id": "WEB-12345"
}
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "factura_id": 12345,
    "numero_factura": "FAC-2026-00123",
    "estado": "anulada",
    "motivo_anulacion": "Solicitud del cliente - Cambió de opinión",
    "anulada_at": "2026-01-13T11:00:00Z"
  },
  "message": "Venta anulada exitosamente"
}
```

**Response 422:**
```json
{
  "success": false,
  "error": {
    "code": "CANCELLATION_FAILED",
    "message": "La venta ya está anulada"
  }
}
```

---

## 📊 Modelos de Datos

### Producto

```json
{
  "id": 1,
  "sku": "PROD-001",
  "codigo_barra": "7501234567890",
  "nombre": "Producto Ejemplo",
  "descripcion": "Descripción del producto",
  "precio_venta": 150.00,
  "stock_actual": 25,
  "stock_minimo": 5,
  "categoria": {
    "id": 1,
    "nombre": "Categoría A"
  },
  "activo": true,
  "created_at": "2025-01-01T00:00:00Z",
  "updated_at": "2026-01-13T10:30:00Z"
}
```

### Venta

```json
{
  "factura_id": 12345,
  "numero_factura": "FAC-2026-00123",
  "external_order_id": "WEB-12345",
  "estado": "completada",
  "cliente": {
    "documento": "1234567890",
    "nombre": "Juan Pérez",
    "email": "juan@example.com",
    "telefono": "+504 9876-5432",
    "direccion": "Tegucigalpa, Honduras"
  },
  "items": [
    {
      "producto_id": 1,
      "sku": "PROD-001",
      "nombre": "Producto Ejemplo",
      "cantidad": 2,
      "precio_unitario": 150.00,
      "subtotal": 300.00
    }
  ],
  "subtotal": 500.00,
  "descuento": 50.00,
  "impuestos": 85.50,
  "total": 535.50,
  "forma_pago": "tarjeta_credito",
  "metadatos": {},
  "created_at": "2026-01-13T10:30:00Z",
  "updated_at": "2026-01-13T10:30:00Z"
}
```

---

## 🔄 Estados de Venta

### Estados Disponibles

| Estado | Valor | Descripción | Color |
|--------|-------|-------------|-------|
| Pendiente | `pendiente` | Venta iniciada, pendiente de confirmación | warning |
| Procesando | `procesando` | Venta en proceso de validación | info |
| **Completada** | `completada` | Venta completada exitosamente | success |
| Anulada | `anulada` | Venta anulada | secondary |
| Fallida | `fallida` | Venta fallida por error | danger |
| Reembolsada | `reembolsada` | Venta reembolsada completamente | primary |
| Reembolso Parcial | `reembolso_parcial` | Venta con reembolso parcial | primary |

### Transiciones de Estados

```
┌──────────┐
│ PENDING  │
└────┬─────┘
     │
     ▼
┌──────────────┐     ┌─────────┐
│  PROCESSING  ├────►│ FAILED  │
└──────┬───────┘     └─────────┘
       │
       ▼
┌──────────────┐     ┌──────────┐
│  COMPLETED   ├────►│ CANCELLED│
└──────┬───────┘     └──────────┘
       │
       ├────► REFUNDED
       │
       └────► PARTIAL_REFUND
```

---

## 🔀 Flujos de Integración

### Flujo Completo: E-commerce → POS

```
E-COMMERCE                        POS API
    │                               │
    │ 1. Usuario agrega productos   │
    │    al carrito                 │
    │                               │
    │ 2. Validar Stock              │
    ├──────────────────────────────►│
    │   POST /api/v1/inventory/     │
    │   validate-stock               │
    │                               │
    │                               │ - Consulta BD con locks
    │                               │ - Valida disponibilidad
    │                               │
    │◄──────────────────────────────┤
    │   200: Stock Available         │
    │   {available: true, items:...} │
    │                               │
    │ 3. Usuario confirma compra    │
    │    y paga                     │
    │                               │
    │ 4. Registrar Venta            │
    ├──────────────────────────────►│
    │   POST /api/v1/sales          │
    │   {external_order_id: "WEB-1"}│
    │                               │
    │                               │ 5. BEGIN TRANSACTION
    │                               │ 6. Lock productos (FOR UPDATE)
    │                               │ 7. Validar stock nuevamente
    │                               │ 8. Crear factura
    │                               │ 9. Crear items de factura
    │                               │ 10. Descontar inventario
    │                               │ 11. Registrar auditoría
    │                               │ 12. COMMIT
    │                               │
    │◄──────────────────────────────┤
    │   201: Factura creada          │
    │   {factura_id: 12345, ...}    │
    │                               │
    │ 13. Enviar email confirmación │
    │ 14. Procesar envío            │
    │                               │
    │ 15. Webhook: Estado actualizado│
    │    (Opcional)                 │
    │                               │
```

### Flujo de Anulación

```
E-COMMERCE                        POS API
    │                               │
    │ 1. Cliente solicita anulación │
    │                               │
    │ 2. Anular Venta               │
    ├──────────────────────────────►│
    │   PUT /api/v1/sales/12345/    │
    │   cancel                      │
    │   {motivo: "..."}             │
    │                               │
    │                               │ 3. BEGIN TRANSACTION
    │                               │ 4. Verificar estado
    │                               │ 5. Restaurar inventario
    │                               │ 6. Actualizar estado
    │                               │ 7. Registrar motivo
    │                               │ 8. Auditoría
    │                               │ 9. COMMIT
    │                               │
    │◄──────────────────────────────┤
    │   200: Venta anulada          │
    │   {estado: "anulada"}         │
    │                               │
    │ 10. Procesar reembolso        │
    │ 11. Notificar cliente         │
```

---

## ⚠️ Manejo de Errores

### Estructura de Error Estándar

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Mensaje descriptivo del error",
    "details": {
      // Detalles adicionales (opcional)
    }
  }
}
```

### Códigos de Error HTTP

| Código | Significado | Cuándo se usa |
|--------|-------------|---------------|
| 200 | OK | Operación exitosa |
| 201 | Created | Recurso creado (venta) |
| 400 | Bad Request | Request malformado |
| 401 | Unauthorized | Token inválido/expirado |
| 404 | Not Found | Recurso no encontrado |
| 422 | Unprocessable Entity | Validación fallida |
| 429 | Too Many Requests | Rate limit excedido |
| 500 | Internal Server Error | Error del servidor |

### Códigos de Error Personalizados

| Código | Descripción |
|--------|-------------|
| `UNAUTHORIZED` | Token no proporcionado o inválido |
| `INVALID_TOKEN` | Token JWT inválido o expirado |
| `AUTH_FAILED` | Credenciales incorrectas |
| `VALIDATION_ERROR` | Datos de entrada inválidos |
| `PRODUCT_NOT_FOUND` | Producto no encontrado |
| `INSUFFICIENT_STOCK` | Stock insuficiente |
| `SALE_NOT_FOUND` | Venta no encontrada |
| `SALE_CREATION_FAILED` | Error al crear venta |
| `CANCELLATION_FAILED` | Error al anular venta |
| `RATE_LIMIT_EXCEEDED` | Límite de requests excedido |
| `INTERNAL_ERROR` | Error interno del servidor |
| `ROUTE_NOT_FOUND` | Endpoint no existe |

### Ejemplos de Errores

**Token Expirado (401):**
```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Token expirado"
  }
}
```

**Validación Fallida (422):**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Los datos enviados no son válidos",
    "details": {
      "items.0.sku": ["El SKU del producto es requerido"],
      "total": ["El total es requerido"]
    }
  }
}
```

**Rate Limit (429):**
```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Demasiadas peticiones. Intente nuevamente en 30 segundos",
    "retry_after": 30
  }
}
```

---

## 🔒 Seguridad

### 1. Autenticación JWT

- **Tokens con expiración:** 1 hora por defecto
- **Secret seguro:** Mínimo 32 caracteres
- **Algoritmo:** HS256
- **Payload mínimo:** Solo datos necesarios

### 2. Rate Limiting

- **Por cliente:** Configurable (default: 60/min)
- **Headers de respuesta:**
  - `X-RateLimit-Limit`: Límite total
  - `X-RateLimit-Remaining`: Requests restantes
  - `X-RateLimit-Reset`: Timestamp de reset

### 3. IP Whitelist

Configurar IPs permitidas por cliente:

```php
$authService->createClient('E-commerce', [
    'ip_whitelist' => ['192.168.1.100', '203.0.113.50']
]);
```

### 4. HTTPS Obligatorio

En producción, forzar HTTPS:

```php
// En config/api.php o middleware
if (!request()->secure() && app()->environment('production')) {
    abort(403, 'HTTPS requerido');
}
```

### 5. Validación de Datos

- **Sanitización automática** de campos sensibles en logs
- **Validación estricta** con FormRequests
- **Límites de tamaño:**
  - Max 100 items por request
  - Max 10,000 de cantidad por item
  - Max 999,999,999 en valores monetarios

### 6. Auditoría Completa

Todos los requests se registran en `api_logs`:
- Método, endpoint, IP
- Request y response (sanitizados)
- Duración, timestamp
- Cliente que realizó la petición

### 7. Sanitización de Logs

Campos ocultos automáticamente:
- `password`
- `api_secret`
- `token`
- `credit_card`
- `cvv`
- `card_number`

### 8. Control de Concurrencia

- **Locks distribuidos** con Cache/Redis
- **Bloqueo pesimista** en BD (`FOR UPDATE`)
- **Transacciones atómicas** para ventas

### 9. Protección SQL Injection

- **Eloquent ORM** exclusivamente
- **Prepared statements** automáticos
- **Validación de tipos** en inputs

### 10. Buenas Prácticas

✅ **Nunca exponer** API secrets en código  
✅ **Rotar secrets** regularmente  
✅ **Monitorear** logs de errores 4xx/5xx  
✅ **Alertas** para comportamiento anómalo  
✅ **Backups** regulares de `api_logs`  
✅ **CORS restrictivo** solo a dominios autorizados  

---

## 🧪 Testing

### 1. Pruebas con cURL

**Obtener Token:**
```bash
curl -X POST "http://localhost/api/v1/auth/token" \
  -H "Content-Type: application/json" \
  -d '{
    "api_key": "pk_test_123",
    "api_secret": "sk_test_456"
  }'
```

**Consultar Inventario:**
```bash
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."

curl -X GET "http://localhost/api/v1/inventory?per_page=5" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

**Crear Venta:**
```bash
curl -X POST "http://localhost/api/v1/sales" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "external_order_id": "TEST-001",
    "cliente": {
      "nombre": "Juan Test",
      "email": "juan@test.com"
    },
    "items": [
      {
        "sku": "PROD-001",
        "cantidad": 2,
        "precio_unitario": 100.00
      }
    ],
    "subtotal": 200.00,
    "total": 200.00
  }'
```

### 2. Pruebas con Postman

**Colección disponible:** [Descargar aquí](#)

1. Importar colección en Postman
2. Configurar variables:
   - `base_url`: `http://localhost/api/v1`
   - `api_key`: Tu API key
   - `api_secret`: Tu API secret
3. Ejecutar "Generar Token"
4. Token se guarda automáticamente
5. Ejecutar otros endpoints

### 3. Pruebas Unitarias (PHPUnit)

```bash
# Ejecutar todos los tests
php artisan test

# Test específico
php artisan test --filter=InventoryTest

# Con coverage
php artisan test --coverage
```

**Ejemplo de Test:**

```php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\ApiClient;
use App\Models\Producto;

class InventoryTest extends TestCase
{
    public function test_can_get_inventory_with_valid_token()
    {
        $client = ApiClient::factory()->create();
        $token = $this->generateToken($client);
        
        Producto::factory()->count(5)->create();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/inventory');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'sku',
                        'nombre',
                        'stock_actual'
                    ]
                ],
                'meta'
            ]);
    }
    
    public function test_cannot_access_without_token()
    {
        $response = $this->getJson('/api/v1/inventory');
        
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED'
                ]
            ]);
    }
}
```

---

## 🚀 Deployment

### 1. Checklist Pre-Deployment

- [ ] Configurar `.env` de producción
- [ ] Generar `API_JWT_SECRET` seguro
- [ ] Configurar `API_ALLOWED_ORIGINS`
- [ ] Habilitar `API_AUDIT_ENABLED=true`
- [ ] Ejecutar migraciones
- [ ] Crear clientes API
- [ ] Configurar HTTPS/SSL
- [ ] Configurar firewall
- [ ] Probar endpoints en staging

### 2. Configuración de Producción

**.env:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pos.tudominio.com

API_JWT_SECRET=clave-super-segura-de-32-caracteres-minimo
API_TOKEN_TTL=3600
API_RATE_LIMIT=100
API_AUDIT_ENABLED=true
API_ALLOWED_ORIGINS=https://tienda.com,https://www.tienda.com

DB_CONNECTION=mysql
DB_HOST=servidor-db-produccion
DB_PORT=3306
DB_DATABASE=pos_produccion
DB_USERNAME=user_api
DB_PASSWORD=contraseña-segura

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 3. Optimizaciones

```bash
# Cache de configuración
php artisan config:cache

# Cache de rutas
php artisan route:cache

# Cache de vistas
php artisan view:cache

# Optimizar autoload
composer install --optimize-autoloader --no-dev

# Limpiar cachés de desarrollo
php artisan cache:clear
php artisan view:clear
```

### 4. Configuración Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name pos.tudominio.com;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    root /var/www/zenvy/Punto_Venta/public;
    index index.php;
    
    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=100r/m;
    
    location /api/ {
        limit_req zone=api burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 5. Monitoreo

**Logs importantes:**
- `storage/logs/laravel.log`
- Tabla `api_logs`
- Logs de Nginx/Apache
- Logs de MySQL

**Métricas a monitorear:**
- Requests por minuto
- Tiempo de respuesta
- Tasa de errores (4xx, 5xx)
- Uso de memoria/CPU
- Conexiones a BD

### 6. Backup

```bash
# Backup de base de datos
mysqldump -u root -p punto_venta > backup_$(date +%Y%m%d).sql

# Backup de api_logs (últimos 90 días)
mysqldump -u root -p punto_venta api_logs \
  --where="created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)" \
  > api_logs_backup_$(date +%Y%m%d).sql
```

---

## 🔧 Troubleshooting

### Problema: "Token inválido o expirado"

**Causa:** Token JWT expirado o secret incorrecto

**Solución:**
1. Regenerar token con `/api/v1/auth/token`
2. Verificar `API_JWT_SECRET` en `.env`
3. Verificar sincronización de hora del servidor

### Problema: "429 Too Many Requests"

**Causa:** Rate limit excedido

**Solución:**
1. Esperar tiempo indicado en `Retry-After` header
2. Aumentar `rate_limit_per_minute` del cliente
3. Implementar exponential backoff en el cliente

### Problema: "Stock insuficiente" inconsistente

**Causa:** Requests concurrentes

**Solución:**
- El sistema ya maneja esto con locks
- Siempre validar stock antes de confirmar
- No cachear resultados de validación

### Problema: "Producto no encontrado" con SKU correcto

**Causa:** Producto inactivo o eliminado

**Solución:**
1. Verificar `activo = true` en producto
2. Consultar directamente en BD
3. Revisar logs de cambios

### Problema: Performance lento

**Soluciones:**
1. Activar cache de Redis
2. Optimizar índices de BD
3. Aumentar `cache_ttl` en config
4. Usar eager loading en relaciones

### Problema: Logs creciendo mucho

**Solución:**
```sql
-- Eliminar logs antiguos (>90 días)
DELETE FROM api_logs 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Optimizar tabla
OPTIMIZE TABLE api_logs;
```

### Debug Útil

**Verificar conectividad:**
```bash
curl -X GET "http://localhost/api/health"
```

**Ver últimos logs:**
```bash
tail -f storage/logs/laravel.log
```

**Verificar cliente API:**
```sql
SELECT * FROM api_clients WHERE api_key = 'pk_...';
```

**Ver últimos requests:**
```sql
SELECT 
    method,
    endpoint,
    response_status,
    duration_ms,
    created_at
FROM api_logs
ORDER BY created_at DESC
LIMIT 20;
```

---

## 📞 Soporte y Contacto

**Desarrollador:** Yefry Yeff  
**Email:** yefry@example.com  
**Repositorio:** [github.com/Yefry-yeff/zenvy](https://github.com/Yefry-yeff/zenvy)

---

## 📝 Changelog

### v1.0.0 (2026-01-13)
- ✅ Implementación inicial del API REST
- ✅ Autenticación JWT
- ✅ Endpoints de inventario
- ✅ Endpoints de ventas
- ✅ Control de concurrencia
- ✅ Auditoría completa
- ✅ Rate limiting
- ✅ Documentación completa

---

## 📜 Licencia

Este API es propiedad de Zenvy POS. Todos los derechos reservados.

---

**✨ ¡Gracias por usar el API REST de Zenvy POS!**
