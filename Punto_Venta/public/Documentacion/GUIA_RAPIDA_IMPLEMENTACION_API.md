# 🚀 Guía Rápida de Implementación - API Ventas E-commerce

## ⏱️ Tiempo estimado: 15-30 minutos

Esta guía te llevará paso a paso para poner en funcionamiento la API de ventas.

---

## 📋 Pre-requisitos

- ✅ Laravel funcionando correctamente
- ✅ Base de datos de Zenvy configurada
- ✅ Tabla `producto` con productos activos
- ✅ Tabla `recibido_bodega` con stock disponible
- ✅ Sistema de autenticación API configurado (API Key)

---

## 🔧 PASO 1: Verificar Archivos Actualizados (2 min)

Los siguientes archivos ya fueron modificados:

```
✅ Punto_Venta/app/Http/Requests/Api/CreateSaleRequest.php
✅ Punto_Venta/app/Services/Api/SalesService.php
```

Verifica que existan y estén actualizados.

---

## 🔧 PASO 2: Configurar Valores en el Servicio (5 min)

Abre: `Punto_Venta/app/Services/Api/SalesService.php`

### 2.1 Configurar CAI (línea ~43)

```php
'cai_id' => 1, // ⚠️ CAMBIAR por tu CAI activo
```

**Para encontrar tu CAI:**
```sql
SELECT id, numero_cai, estado FROM cai WHERE estado = 1;
```

### 2.2 Configurar Usuario del Sistema (línea ~62)

```php
'users_id' => 1, // ⚠️ CAMBIAR por ID del usuario que registra ventas API
```

**Para ver usuarios:**
```sql
SELECT id, name, email FROM users;
```

### 2.3 Configurar Caja (línea ~39)

```php
'caja_id' => 1, // ⚠️ CAMBIAR por tu caja para API
```

**Para ver cajas:**
```sql
SELECT id, nombre FROM caja WHERE estado_id = 1;
```

---

## 🔧 PASO 3: Verificar Ruta de la API (2 min)

Abre: `Punto_Venta/routes/api.php`

Verifica que exista:

```php
Route::middleware(['api', 'api_auth'])->prefix('v1')->group(function () {
    Route::post('/sales', [SalesController::class, 'store']);
    Route::get('/sales/{id}', [SalesController::class, 'show']);
    Route::put('/sales/{id}/cancel', [SalesController::class, 'cancel']);
});
```

Si no existe, agrégala.

---

## 🔧 PASO 4: Crear/Configurar API Key (3 min)

### Opción A: Si tienes tabla `api_clients`

```sql
INSERT INTO api_clients (name, api_key, is_active, created_at)
VALUES ('E-commerce Web', 'tu-api-key-segura-aqui', 1, NOW());
```

### Opción B: Usar middleware de autenticación existente

Verifica tu middleware de autenticación API y asegúrate de tener un API Key válido.

---

## 🧪 PASO 5: Probar con el Script PHP (5 min)

### 5.1 Editar el archivo de prueba

Abre: `test_api_ventas_ecommerce.php`

**Configurar:**
```php
$apiUrl = 'http://localhost:8000/api/v1/sales'; // Tu URL
$apiKey = 'tu-api-key-aqui'; // Tu API Key
```

### 5.2 Obtener IDs de productos válidos

```sql
SELECT id, nombre FROM producto WHERE estado_id = 1 LIMIT 5;
```

### 5.3 Actualizar product_id en los tests

En el script, cambiar:
```php
'product_id' => 1, // ⚠️ Usar IDs reales de tu BD
```

### 5.4 Ejecutar el test

```bash
cd c:\laragon\www\Procadts\zenvy
php test_api_ventas_ecommerce.php
```

**Resultado esperado:**
```
✅ ÉXITO - HTTP 201: Test 1: Venta con entrega a domicilio
✅ ÉXITO - HTTP 201: Test 2: Venta con retiro en tienda
✅ ÉXITO - HTTP 201: Test 3: Venta múltiple con descuentos
⚠️  VALIDACIÓN - HTTP 422: Test 4: Error teléfono inválido
⚠️  VALIDACIÓN - HTTP 422: Test 5: Error falta dirección
```

---

## 🌐 PASO 6: Integrar en el Frontend (10 min)

### 6.1 Copiar el cliente JavaScript

Copia `zenvy-ecommerce-api-client.js` a tu proyecto web.

### 6.2 Incluir en tu HTML

```html
<script src="path/to/zenvy-ecommerce-api-client.js"></script>
```

### 6.3 Inicializar en tu código

```javascript
const api = new ZenvyEcommerceAPI(
    'https://tu-api.com', // URL de tu API
    'tu-api-key-aqui'      // Tu API Key
);
```

### 6.4 Usar en el checkout

```javascript
// Al confirmar pedido
const saleData = {
    customer_name: 'Cliente',
    customer_email: 'cliente@example.com',
    customer_phone: '+50499887766',
    
    items: [
        {
            product_id: 25,
            quantity: 2,
            price: 150.00,
            discount: 10
        }
    ],
    
    delivery_type: 'domicilio',
    delivery_address: 'Dirección completa',
    
    payment_method: 'Tarjeta',
    notes: 'Notas del pedido',
    
    subtotal: 270.00,
    discount: 30.00,
    tax: 40.50,
    total: 280.50
};

try {
    const response = await api.createSale(saleData);
    console.log('Factura creada:', response.data.factura_id);
    // Mostrar confirmación al usuario
} catch (error) {
    console.error('Error:', error.getUserFriendlyMessage());
}
```

---

## ✅ PASO 7: Verificación Final (3 min)

### 7.1 Verificar en la BD

Después de una venta exitosa, verificar:

```sql
-- Ver última factura
SELECT * FROM factura ORDER BY id DESC LIMIT 1;

-- Ver items de la factura
SELECT * FROM factura_has_producto WHERE factura_id = [ID_FACTURA];

-- Verificar descuento de stock
SELECT * FROM recibido_bodega WHERE producto_id = [ID_PRODUCTO];
```

### 7.2 Verificar logs

```bash
tail -f storage/logs/laravel.log
```

Buscar:
```
API: Venta creada exitosamente
```

---

## 🎯 Checklist de Implementación

Marca cada item cuando esté completo:

- [ ] Archivos actualizados verificados
- [ ] CAI configurado correctamente
- [ ] Usuario del sistema configurado
- [ ] Caja configurada
- [ ] Ruta de API verificada
- [ ] API Key creado/configurado
- [ ] Script de prueba ejecutado con éxito
- [ ] Cliente JavaScript integrado
- [ ] Primera venta de prueba exitosa
- [ ] Verificación en BD correcta
- [ ] Stock descontado correctamente

---

## 📞 Endpoints Disponibles

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `POST` | `/api/v1/sales` | Crear venta |
| `GET` | `/api/v1/sales/{id}` | Consultar venta |
| `PUT` | `/api/v1/sales/{id}/cancel` | Anular venta |

---

## 🚨 Solución de Problemas Comunes

### Error: "Producto no encontrado"
**Solución:** Verifica que el `product_id` exista en la tabla `producto`.

```sql
SELECT id, nombre FROM producto WHERE id = [ID];
```

### Error: "Stock insuficiente"
**Solución:** Verifica stock disponible:

```sql
SELECT SUM(cantidad_disponible) as stock 
FROM recibido_bodega 
WHERE producto_id = [ID] AND estado_id = 1;
```

### Error: "Teléfono inválido"
**Solución:** Debe ser exactamente: `+504XXXXXXXX` (8 dígitos).

### Error: "CAI no encontrado"
**Solución:** Verifica que el CAI esté activo:

```sql
SELECT * FROM cai WHERE id = [ID] AND estado = 1;
```

### Error de autenticación
**Solución:** Verifica que el API Key sea correcto y esté activo.

---

## 📚 Documentación Completa

Para más información detallada, consulta:

- 📄 `DOCUMENTACION_API_VENTAS_ECOMMERCE.md` - Documentación completa de la API
- 📄 `RESUMEN_API_VENTAS_ECOMMERCE.md` - Resumen de implementación
- 💻 `ejemplo_checkout_frontend.html` - Ejemplo visual completo

---

## 🎉 ¡Listo!

Si todos los pasos están completos, tu API de ventas está funcionando y lista para recibir pedidos desde tu e-commerce.

### Próximos pasos sugeridos:

1. **Implementar en producción** con configuraciones reales
2. **Monitorear logs** durante los primeros días
3. **Configurar notificaciones** para ventas exitosas
4. **Implementar webhooks** si es necesario
5. **Optimizar rendimiento** según el volumen de ventas

---

**¿Problemas?** Revisa los logs en `storage/logs/laravel.log`

**Fecha:** 13 de enero de 2026  
**Versión:** 1.0
