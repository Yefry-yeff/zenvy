# Pedido de Johann Ruiz - Instrucciones de Prueba

## 📦 Información del Pedido

**Cliente:** Johann Ruiz  
**Email:** johann_ruiz14@hotmail.com  
**Teléfono:** +50497525987

### 📍 Datos de Entrega
- **Tipo:** Envío a Domicilio
- **Dirección:** Oficina Francisco Morazán, Tegucigalpa, El centro de AMDC, Frente AMDC
- **Notas:** Dejar afuera

### 💰 Datos de Pago
- **Método:** Efectivo
- **Precio de Envío:** L. 50.00

### 🛒 Productos

| ID | Cantidad | Precio Unit. | Descuento | Subtotal |
|----|----------|--------------|-----------|----------|
| 1633 | 1 | L. 1,000.00 | 10% | L. 900.00 |
| 1634 | 1 | L. 500.00 | 0% | L. 500.00 |
| 9999 (Envío) | 1 | L. 50.00 | 0% | L. 50.00 |

### 💵 Totales

```
Subtotal:        L. 1,450.00
Descuento:      -L.   100.00
ISV (15%):       L.   217.50
─────────────────────────────
TOTAL:           L. 1,667.50
```

---

## 🚀 Opción 1: Probar con Script PHP

### Paso 1: Configurar el script

Edita `test_api_ventas_ecommerce.php`:

```php
$apiUrl = 'http://localhost:8000/api/v1/sales'; // Tu URL
$apiKey = 'tu-api-key-real'; // Tu API Key
```

### Paso 2: Verificar productos en la BD

**IMPORTANTE:** Antes de ejecutar, verifica que los productos existan:

```sql
-- Verificar producto 1633
SELECT id, nombre, precio FROM producto WHERE id = 1633;

-- Verificar producto 1634
SELECT id, nombre, precio FROM producto WHERE id = 1634;

-- Verificar/crear producto de envío (opcional)
SELECT id, nombre FROM producto WHERE id = 9999;

-- Si no existe el producto de envío, puedes crearlo:
INSERT INTO producto (id, nombre, precio, estado_id, unidad_medida_venta_id)
VALUES (9999, 'Costo de Envío', 50.00, 1, 1);
```

**Alternativa:** Si no quieres crear el producto de envío, edita el script y:
1. Remueve el tercer item del array
2. Ajusta los totales:
   - subtotal: 1400.00
   - discount: 100.00
   - tax: 210.00
   - total: 1610.00

### Paso 3: Ejecutar

```bash
cd c:\laragon\www\Procadts\zenvy
php test_api_ventas_ecommerce.php
```

### Resultado Esperado

```
========================================
TEST: Pedido Real - Johann Ruiz (Envío a Domicilio)
========================================
HTTP Code: 201

Respuesta:
{
    "success": true,
    "data": {
        "factura_id": 1234,
        "cliente": "Johann Ruiz",
        "total": 1667.5,
        "subtotal": 1450,
        "isv": 217.5,
        "fecha_emision": "2026-01-13T...",
        "estado": 1
    },
    "message": "Venta registrada exitosamente"
}
```

---

## 🚀 Opción 2: Probar con Postman

### Paso 1: Importar colección

1. Abre Postman
2. Click en "Import"
3. Selecciona el archivo `Postman_Johann_Ruiz_Pedido.json`

### Paso 2: Configurar variables

En la colección importada, edita:
- URL base: `http://localhost:8000` (o tu URL)
- X-API-Key: `tu-api-key-real`

### Paso 3: Ejecutar request

1. Selecciona el request "Pedido Real - Johann Ruiz"
2. Verifica que los product_id existan en tu BD
3. Click en "Send"

---

## 🚀 Opción 3: Probar con cURL

Copia y ejecuta en tu terminal:

```bash
curl -X POST http://localhost:8000/api/v1/sales \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: tu-api-key-aqui" \
  -d '{
    "customer_name": "Johann Ruiz",
    "customer_email": "johann_ruiz14@hotmail.com",
    "customer_phone": "+50497525987",
    
    "items": [
      {
        "product_id": 1633,
        "quantity": 1,
        "price": 1000.00,
        "discount": 10
      },
      {
        "product_id": 1634,
        "quantity": 1,
        "price": 500.00,
        "discount": 0
      },
      {
        "product_id": 9999,
        "quantity": 1,
        "price": 50.00,
        "discount": 0
      }
    ],
    
    "delivery_type": "domicilio",
    "delivery_address": "Oficina Francisco Morazán, Tegucigalpa, El centro de AMDC, Frente AMDC",
    
    "payment_method": "Efectivo",
    "notes": "Dejar afuera",
    
    "subtotal": 1450.00,
    "discount": 100.00,
    "tax": 217.50,
    "total": 1667.50
  }'
```

---

## ✅ Verificación en Base de Datos

Después de crear la venta exitosamente, verifica:

### Ver la factura creada

```sql
SELECT 
    id,
    nombre_cliente,
    total,
    sub_total,
    isv,
    monto_descuento,
    comentario,
    fecha_emision
FROM factura 
WHERE nombre_cliente = 'Johann Ruiz'
ORDER BY id DESC 
LIMIT 1;
```

### Ver items de la factura

```sql
-- Reemplaza [FACTURA_ID] con el ID obtenido
SELECT 
    fhp.id,
    p.nombre as producto,
    fhp.cantidad,
    fhp.precio_unidad,
    fhp.descuento,
    fhp.subtotal,
    fhp.isv,
    fhp.total
FROM factura_has_producto fhp
INNER JOIN producto p ON p.id = fhp.producto_id
WHERE fhp.factura_id = [FACTURA_ID];
```

### Verificar descuento de stock

```sql
-- Verificar stock del producto 1633
SELECT 
    id,
    producto_id,
    cantidad_disponible,
    fecha_recibido
FROM recibido_bodega 
WHERE producto_id = 1633 
    AND estado_id = 1
ORDER BY fecha_recibido ASC;

-- Verificar stock del producto 1634
SELECT 
    id,
    producto_id,
    cantidad_disponible,
    fecha_recibido
FROM recibido_bodega 
WHERE producto_id = 1634 
    AND estado_id = 1
ORDER BY fecha_recibido ASC;
```

---

## 🔍 Verificar el Comentario de la Factura

El comentario debe contener toda la información:

```sql
SELECT comentario 
FROM factura 
WHERE nombre_cliente = 'Johann Ruiz' 
ORDER BY id DESC 
LIMIT 1;
```

**Contenido esperado:**
```
Venta desde API - E-commerce
Cliente: Johann Ruiz
Email: johann_ruiz14@hotmail.com
Teléfono: +50497525987
Método de pago: Efectivo
Tipo de entrega: Entrega a domicilio
Dirección de envío: Oficina Francisco Morazán, Tegucigalpa, El centro de AMDC, Frente AMDC
Notas: Dejar afuera
```

---

## ⚠️ Problemas Comunes y Soluciones

### Error: "Producto ID 1633 no encontrado"

**Solución:** El producto no existe. Verifica:
```sql
SELECT id, nombre FROM producto WHERE id IN (1633, 1634, 9999);
```

Si no existen, cámbialos por IDs válidos en el script/request.

### Error: "Stock insuficiente"

**Solución:** El producto no tiene stock. Verifica:
```sql
SELECT SUM(cantidad_disponible) as stock_total
FROM recibido_bodega 
WHERE producto_id IN (1633, 1634) 
    AND estado_id = 1;
```

Si no hay stock, agrégalo:
```sql
INSERT INTO recibido_bodega 
(producto_id, cantidad_compra_lote, cantidad_inicial_seccion, cantidad_disponible, fecha_recibido, estado_id, seccion_id, unidad_medida_id, users_registro_id)
VALUES 
(1633, 10, 10, 10, NOW(), 1, 1, 1, 1),
(1634, 10, 10, 10, NOW(), 1, 1, 1, 1);
```

### Error: "Unauthorized" o 401

**Solución:** El API Key es incorrecto. Verifica tu configuración de autenticación.

### Error: "Teléfono debe estar en formato +504XXXXXXXX"

**Solución:** El teléfono debe ser exactamente:
- Formato: `+504` seguido de 8 dígitos
- Ejemplo correcto: `+50497525987`
- Ejemplo incorrecto: `97525987` o `50497525987`

---

## 📊 Desglose de Cálculos

### Producto 1 (ID: 1633)
- Precio: L. 1,000.00
- Cantidad: 1
- Descuento: 10%
- **Cálculo:** 1,000 × 1 = 1,000 → 1,000 - 100 (10%) = **L. 900.00**

### Producto 2 (ID: 1634)
- Precio: L. 500.00
- Cantidad: 1
- Descuento: 0%
- **Cálculo:** 500 × 1 = **L. 500.00**

### Envío (ID: 9999)
- Precio: L. 50.00
- Cantidad: 1
- Descuento: 0%
- **Cálculo:** 50 × 1 = **L. 50.00**

### Totales
- **Subtotal:** 900 + 500 + 50 = L. 1,450.00
- **Descuento Total:** L. 100.00
- **ISV (15%):** 1,450 × 0.15 = L. 217.50
- **Total Final:** 1,450 + 217.50 = **L. 1,667.50**

---

## 🎯 Checklist de Prueba

- [ ] Productos 1633 y 1634 existen en la BD
- [ ] Productos tienen stock disponible
- [ ] API Key configurado correctamente
- [ ] URL de la API es correcta
- [ ] Script/Postman configurado
- [ ] Ejecutado con éxito (HTTP 201)
- [ ] Factura creada en BD
- [ ] Items de factura registrados
- [ ] Stock descontado correctamente
- [ ] Comentario de factura tiene toda la info

---

**Fecha:** 13 de enero de 2026  
**Cliente:** Johann Ruiz  
**Total:** L. 1,667.50
