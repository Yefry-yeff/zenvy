# RESUMEN: API de Ventas E-commerce - Implementación Completa

## 📋 Descripción General

Se ha implementado y actualizado completamente la API de ventas para permitir que la página web de e-commerce envíe pedidos y se facturen automáticamente en el sistema Zenvy, con descuento de inventario en tiempo real.

## ✅ Cambios Implementados

### 1. **Actualización del Request de Validación** 
   📄 `app/Http/Requests/Api/CreateSaleRequest.php`

   **Campos Nuevos/Actualizados:**
   - ✅ `customer_name` - Requerido
   - ✅ `customer_email` - Requerido, validado como email
   - ✅ `customer_phone` - Requerido, formato `+504XXXXXXXX`
   - ✅ `customer_rtn` - Opcional
   - ✅ `items[].product_id` - Ahora usa ID del producto en lugar de SKU
   - ✅ `items[].quantity` - Cantidad a rebajar del stock
   - ✅ `items[].price` - Precio del producto
   - ✅ `items[].discount` - Descuento porcentual (0-100)
   - ✅ `delivery_type` - Requerido: "retiro_tienda" o "domicilio"
   - ✅ `delivery_address` - Requerido si es entrega a domicilio
   - ✅ `payment_method` - Requerido
   - ✅ `notes` - Notas adicionales del pedido (opcional)

### 2. **Actualización del Servicio de Ventas**
   📄 `app/Services/Api/SalesService.php`

   **Mejoras Implementadas:**
   - ✅ Búsqueda de productos por `product_id` directamente
   - ✅ Aplicación de descuentos individuales por producto
   - ✅ Cálculo correcto de ISV (15%) después de descuentos
   - ✅ Almacenamiento de información completa del cliente
   - ✅ Registro de tipo de entrega y dirección
   - ✅ Registro de método de pago
   - ✅ Todo almacenado en el campo `comentario` de la factura
   - ✅ Validación de stock mejorada con product_id
   - ✅ Logs detallados con información del cliente

### 3. **Documentación Creada**
   📄 `DOCUMENTACION_API_VENTAS_ECOMMERCE.md`

   **Contenido:**
   - Descripción completa de la API
   - Estructura de peticiones y respuestas
   - Ejemplos de uso para diferentes casos:
     - Entrega a domicilio
     - Retiro en tienda
     - Ventas con descuentos
   - Códigos de error y manejo
   - Ejemplo de implementación en PHP

### 4. **Script de Prueba en PHP**
   📄 `test_api_ventas_ecommerce.php`

   **Incluye 5 tests:**
   1. ✅ Venta con entrega a domicilio y descuentos
   2. ✅ Venta con retiro en tienda
   3. ✅ Venta múltiple con varios productos
   4. ⚠️ Error de validación: teléfono inválido
   5. ⚠️ Error de validación: falta dirección

### 5. **Cliente JavaScript para Frontend**
   📄 `zenvy-ecommerce-api-client.js`

   **Características:**
   - Clase `ZenvyEcommerceAPI` para interactuar con la API
   - Métodos para crear, consultar y anular ventas
   - Validación del lado del cliente
   - Cálculo automático de totales
   - Manejo robusto de errores
   - Ejemplos de uso con React y Vanilla JS
   - Utilidades para formateo de datos

## 📊 Flujo de Facturación

```
Web Frontend
    ↓
[1] Recolecta datos del cliente
    ↓
[2] Selecciona productos y cantidades
    ↓
[3] Elige método de entrega
    ↓
[4] Selecciona método de pago
    ↓
POST /api/v1/sales
    ↓
API Zenvy
    ↓
[5] Valida datos de entrada
    ↓
[6] Verifica stock disponible
    ↓
[7] Crea transacción en caja
    ↓
[8] Genera factura con todos los datos
    ↓
[9] Crea items con precios y descuentos
    ↓
[10] Descuenta stock (FIFO)
    ↓
[11] Retorna confirmación
    ↓
Web muestra confirmación al cliente
```

## 🎯 Estructura de Datos que Recibe la API

```json
{
  "customer_name": "María González",
  "customer_email": "maria@example.com",
  "customer_phone": "+50498765432",
  "customer_rtn": "08011985654321",
  
  "items": [
    {
      "product_id": 25,
      "quantity": 2,
      "price": 150.00,
      "discount": 10
    }
  ],
  
  "delivery_type": "domicilio",
  "delivery_address": "Colonia Trejo, Casa 25",
  
  "payment_method": "Tarjeta de Crédito",
  "notes": "Entregar en horario de tarde",
  
  "subtotal": 270.00,
  "discount": 30.00,
  "tax": 40.50,
  "total": 280.50
}
```

## 💾 Información Almacenada en Zenvy

**Tabla: `factura`**
- `nombre_cliente`: Nombre del cliente
- `rtn`: RTN del cliente (si lo proporciona)
- `sub_total`: Subtotal de la venta
- `isv`: Impuesto ISV calculado (15%)
- `total`: Total a pagar
- `monto_descuento`: Descuento total aplicado
- `comentario`: Incluye toda la información detallada:
  - Email del cliente
  - Teléfono del cliente
  - Método de pago
  - Tipo de entrega
  - Dirección de envío (si aplica)
  - Notas del pedido

**Tabla: `factura_has_producto`**
- `producto_id`: ID del producto
- `cantidad`: Cantidad vendida
- `precio_unidad`: Precio unitario
- `descuento`: Monto de descuento aplicado
- `subtotal`: Subtotal del item
- `isv`: ISV del item
- `total`: Total del item

**Tabla: `recibido_bodega`**
- Se descuenta automáticamente el stock usando FIFO

## 🔧 Configuración Necesaria

### Backend (Laravel)

1. **Verificar que exista la ruta:**
   ```php
   Route::post('/sales', [SalesController::class, 'store']);
   ```

2. **Configurar CAI por defecto:**
   En `SalesService.php` línea 44:
   ```php
   'cai_id' => 1, // Ajustar según tu configuración
   ```

3. **Configurar usuario del sistema:**
   En `SalesService.php` línea 63:
   ```php
   'users_id' => 1, // Usuario que registra ventas de API
   ```

### Frontend (JavaScript)

1. **Inicializar cliente API:**
   ```javascript
   const api = new ZenvyEcommerceAPI(
       'https://api.mitienda.com',
       'tu-api-key-aqui'
   );
   ```

2. **Obtener product_id:**
   - La página web debe conocer el `product_id` de cada producto
   - Se puede obtener mediante un endpoint de productos o almacenar en la BD del e-commerce

## 🧪 Cómo Probar

### Opción 1: Script PHP

```bash
# Editar el archivo test_api_ventas_ecommerce.php
# - Configurar $apiUrl
# - Configurar $apiKey
# - Ajustar product_id con IDs válidos

php test_api_ventas_ecommerce.php
```

### Opción 2: Postman / Insomnia

1. Crear nueva petición POST
2. URL: `http://tu-dominio.com/api/v1/sales`
3. Headers:
   - `Content-Type: application/json`
   - `X-API-Key: tu-api-key`
4. Body: Usar ejemplos de la documentación

### Opción 3: cURL

```bash
curl -X POST http://tu-dominio.com/api/v1/sales \
  -H "Content-Type: application/json" \
  -H "X-API-Key: tu-api-key" \
  -d '{
    "customer_name": "Test Cliente",
    "customer_email": "test@example.com",
    "customer_phone": "+50499887766",
    "items": [
      {
        "product_id": 1,
        "quantity": 1,
        "price": 100,
        "discount": 0
      }
    ],
    "delivery_type": "retiro_tienda",
    "payment_method": "Efectivo",
    "subtotal": 100,
    "discount": 0,
    "tax": 15,
    "total": 115
  }'
```

## ⚠️ Consideraciones Importantes

1. **Formato de teléfono**: DEBE ser exactamente `+504XXXXXXXX` (8 dígitos después de +504)

2. **Product ID**: La web debe enviar el ID exacto del producto de Zenvy, no el SKU

3. **Stock**: Si no hay stock suficiente, la venta NO se procesa y se retorna error 422

4. **Descuentos**: Se calculan correctamente:
   - Descuento individual por producto (porcentaje)
   - Se aplica antes del ISV
   - ISV se calcula sobre el subtotal después del descuento

5. **Dirección**: Solo es requerida cuando `delivery_type = "domicilio"`

6. **Transaccionalidad**: Si algo falla, TODO se revierte automáticamente

7. **FIFO**: El stock se descuenta del lote más antiguo primero

## 📦 Archivos Modificados/Creados

```
✏️  Modificados:
    - Punto_Venta/app/Http/Requests/Api/CreateSaleRequest.php
    - Punto_Venta/app/Services/Api/SalesService.php

📄 Creados:
    - DOCUMENTACION_API_VENTAS_ECOMMERCE.md
    - test_api_ventas_ecommerce.php
    - zenvy-ecommerce-api-client.js
    - RESUMEN_API_VENTAS_ECOMMERCE.md (este archivo)
```

## 🚀 Próximos Pasos

1. **Configurar en producción:**
   - Ajustar el CAI por defecto
   - Configurar el usuario del sistema
   - Configurar la caja para API

2. **Integrar en el frontend:**
   - Importar `zenvy-ecommerce-api-client.js`
   - Configurar API Key
   - Implementar en el proceso de checkout

3. **Probar exhaustivamente:**
   - Usar el script de prueba
   - Verificar stock en Zenvy después de cada venta
   - Validar que las facturas se crean correctamente

4. **Monitorear:**
   - Revisar logs de Laravel
   - Verificar transacciones
   - Monitorear stock y facturas

## 📞 Endpoint de la API

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/v1/sales` | Crear nueva venta/factura |
| GET | `/api/v1/sales/{id}` | Consultar estado de venta |
| PUT | `/api/v1/sales/{id}/cancel` | Anular venta y restaurar stock |

## ✨ Beneficios

- ✅ Facturación automática desde e-commerce
- ✅ Control de stock en tiempo real
- ✅ Información completa del cliente almacenada
- ✅ Trazabilidad de entregas y pagos
- ✅ Manejo robusto de errores
- ✅ Validación exhaustiva de datos
- ✅ Transacciones seguras
- ✅ Sistema FIFO para control de inventario

---

**Fecha de implementación:** 13 de enero de 2026  
**Estado:** ✅ Implementado y documentado  
**Versión:** 1.0
