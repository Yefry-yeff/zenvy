# 🚀 Guía de Configuración - Insomnia 12.2.0

## 📥 Paso 1: Importar la Colección

1. Abre **Insomnia 12.2.0**
2. En la pantalla principal, click en el botón **Import** (o usa `Ctrl+O`)
3. Selecciona **From File**
4. Busca y selecciona: `Insomnia_Zenvy_API_Ventas.json`
5. Click en **Scan** y luego en **Import**

✅ Se creará una colección llamada **"Zenvy API - Ventas E-commerce"** con 5 requests.

---

## ⚙️ Paso 2: Configurar el Environment

1. En Insomnia, abre la colección **"Zenvy API - Ventas E-commerce"**
2. En la barra lateral izquierda, busca la sección **"Environments"**
3. Click en **"Base Environment"** (ya viene incluido)
4. Verás las variables ya configuradas con las credenciales correctas:

```json
{
  "base_url": "http://127.0.0.1:8000",
  "api_key": "pk_LhkEAssdwNMPjJMc3gqJL2TtIX96mi1s",
  "api_secret": "sk_3fXa0kouB9l0Qiw1mLYoIcZKRX6WbIfLykKCLK5tT2fKKqPOYoQs7mOeS7tP1LcZ",
  "token": ""
}
```

✅ **Las credenciales ya están configuradas** - No necesitas cambiar nada.

### 🔄 Si Necesitas Nuevas Credenciales

Si por alguna razón necesitas generar nuevas credenciales, ejecuta:

```bash
cd c:\laragon\www\Procadts\zenvy
php crear_credenciales_api.php
```

Selecciona la opción que necesites y copia las nuevas credenciales al Environment.

---

## 🧪 Paso 3: Probar la API

### Request 0: Health Check (No requiere auth)

1. Selecciona **"0. Health Check (Sin Auth)"**
2. Click en **Send**
3. Deberías ver:
   ```json
   {
     "success": true,
     "message": "API funcionando correctamente",
     "version": "1.0",
     "timestamp": "..."
   }
   ```

✅ Si ves esto, la API está corriendo correctamente.

---

### Request 1: Obtener Token JWT

1. Asegúrate de tener `api_key` y `api_secret` configurados en el Environment
2. Selecciona **"1. Obtener Token JWT"**
3. Revisa el body del request (debe usar las variables del environment)
4. Click en **Send**

**Respuesta esperada (HTTP 200):**
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "client": {
      "id": 1,
      "name": "E-commerce Principal"
    }
  }
}
```

5. **IMPORTANTE:** Copia el valor del `token`
6. Ve al Environment (⚙️) y pégalo en la variable `token`:
   ```json
   {
     "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
   }
   ```
7. Guarda el Environment

✅ Ahora tienes un token válido por 1 hora.

---

### Request 2: Crear Venta - Johann Ruiz

Este es el request principal para crear la venta.

**Antes de ejecutar:**

1. Verifica que los productos existan en tu BD:
   ```sql
   SELECT id, nombre FROM producto WHERE id IN (7463, 7462, 7461);
   ```

2. Si no existen, edita el body del request con IDs válidos
3. Asegúrate de tener el token en el Environment

**Ejecutar:**

1. Selecciona **"2. Crear Venta - Johann Ruiz"**
2. Revisa el body del request
3. Click en **Send**

**Respuesta esperada (HTTP 201):**
```json
{
  "success": true,
  "data": {
    "factura_id": 1523,
    "cliente": "Johann Ruiz",
    "total": 1667.5,
    "subtotal": 1450,
    "isv": 217.5,
    "fecha_emision": "2026-01-13T15:30:00.000000Z",
    "estado": 1
  },
  "message": "Venta registrada exitosamente"
}
```

✅ ¡Venta creada! Anota el `factura_id` para los siguientes requests.

---

### Request 3: Consultar Venta por ID

1. Selecciona **"3. Consultar Venta por ID"**
2. Edita la URL y cambia el `1` por el ID de tu factura:
   ```
   {{ _.base_url }}/api/v1/sales/1523
   ```
3. Click en **Send**

**Respuesta esperada (HTTP 200):**
```json
{
  "success": true,
  "data": {
    "factura_id": 1523,
    "numero_factura": "001-001-0001523",
    "cliente": "Johann Ruiz",
    "productos": [...],
    "estado": "completada",
    ...
  }
}
```

---

### Request 4: Anular Venta

⚠️ **CUIDADO:** Esta acción restaura el stock.

1. Selecciona **"4. Anular Venta"**
2. Edita la URL con el ID de la factura a anular
3. Edita el motivo en el body si lo deseas
4. Click en **Send**

**Respuesta esperada (HTTP 200):**
```json
{
  "success": true,
  "data": {
    "factura_id": 1523,
    "estado": "anulada",
    "motivo_anulacion": "Cliente solicitó cancelación del pedido"
  },
  "message": "Venta anulada exitosamente"
}
```

---

## 🔄 Datos del Pedido de Johann Ruiz

```json
{
  "customer_name": "Johann Ruiz",
  "customer_email": "johann_ruiz14@hotmail.com",
  "customer_phone": "+50497525987",
  
  "items": [
    {
      "product_id": 7463,
      "quantity": 1,
      "price": 1000.00,
      "discount": 10
    },
    {
      "product_id": 7462,
      "quantity": 1,
      "price": 500.00,
      "discount": 0
    },
    {
      "product_id": 7461,
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
}
```

**Cálculo de Totales:**
- Producto 7463: L. 1,000 - 10% = L. 900
- Producto 7462: L. 500 = L. 500
- Envío: L. 50 = L. 50
- **Subtotal:** L. 1,450
- **ISV (15%):** L. 217.50
- **TOTAL:** L. 1,667.50

---

## 🔧 Variables del Environment

| Variable | Descripción | Ejemplo |
|----------|-------------|---------|
| `base_url` | URL base del servidor | `http://127.0.0.1:8000` |
| `api_key` | API Key del cliente | `pk_rwKtALSl...` |
| `api_secret` | API Secret sin hashear | `sk_abc123...` |
| `token` | Token JWT (obtener con Request 1) | `eyJ0eXAiOiJKV1Q...` |

---

## ❌ Solución de Problemas

### Error: "Token de autenticación no proporcionado"
**Solución:** Asegúrate de tener el token en el Environment y que el request tenga el header `Authorization: Bearer {{ _.token }}`

### Error: "Token expirado"
**Solución:** El token expira en 1 hora. Ejecuta nuevamente el Request 1 para obtener uno nuevo.

### Error: "Producto ID XXX no encontrado"
**Solución:** Verifica que los productos existan en tu BD o edita el request con IDs válidos.

### Error: "Stock insuficiente"
**Solución:** Agrega stock al producto en la tabla `recibido_bodega`.

### Error: "Credenciales inválidas"
**Solución:** 
1. Verifica que `api_key` y `api_secret` sean correctos
2. Asegúrate de usar el secret **sin hashear**
3. Si es necesario, crea un nuevo cliente API

### Error 404 en cualquier endpoint
**Solución:** Verifica que el servidor esté corriendo:
```bash
cd c:\laragon\www\Procadts\zenvy\Punto_Venta
php artisan serve --port=8000
```

---

## 📝 Notas Importantes

1. **Token JWT:** Se debe renovar cada hora
2. **Product IDs:** Deben existir en la tabla `producto`
3. **Stock:** Debe haber stock disponible en `recibido_bodega`
4. **Teléfono:** Formato estricto `+504XXXXXXXX`
5. **Dirección:** Requerida solo si `delivery_type = "domicilio"`

---

## 🎯 Orden de Ejecución

```
1. Health Check (verificar API)
   ↓
2. Obtener Token JWT
   ↓
3. Copiar token al Environment
   ↓
4. Crear Venta
   ↓
5. Consultar Venta (opcional)
   ↓
6. Anular Venta (opcional)
```

---

## 📊 Endpoints Disponibles

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/api/health` | ❌ No | Verificar API |
| POST | `/api/v1/auth/token` | ❌ No | Obtener token |
| POST | `/api/v1/sales` | ✅ Sí | Crear venta |
| GET | `/api/v1/sales/{id}` | ✅ Sí | Consultar venta |
| PUT | `/api/v1/sales/{id}/cancel` | ✅ Sí | Anular venta |

---

**Fecha:** 13 de enero de 2026  
**Servidor:** http://127.0.0.1:8000  
**Cliente:** Johann Ruiz  
**Total Pedido:** L. 1,667.50

¡Listo para probar! 🚀
