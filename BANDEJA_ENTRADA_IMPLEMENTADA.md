# 📬 SISTEMA DE BANDEJA DE ENTRADA - PEDIDOS WEB

## ✅ IMPLEMENTACIÓN COMPLETA

### 🎯 Funcionalidad Implementada

El sistema ahora incluye una **bandeja de entrada** en el header de Zenvy que muestra notificaciones de pedidos web en tiempo real.

### 📍 Ubicación en la Interfaz

**Header Principal:**
- 🔔 Icono de campana junto al perfil de usuario
- Badge rojo con contador de pedidos no leídos
- Dropdown con últimos 10 pedidos pendientes
- Actualización automática cada 30 segundos

### 🗂️ Archivos Creados/Modificados

#### 1. **Base de Datos**
```
✓ Tablas: pedidos_web, pedidos_web_items
✓ Migración: 2026_01_13_110743_create_pedidos_web_table.php
```

#### 2. **Modelos**
```php
✓ app/Models/PedidoWeb.php
✓ app/Models/PedidoWebItem.php
```

#### 3. **Servicios**
```php
✓ app/Services/Api/OrderService.php
  - createOrder() - Recibe pedido web
  - convertirPedidoAFactura() - Procesa y factura
  - getPedidosPendientes() - Lista bandeja
  - contarPedidosNoLeidos() - Contador notificaciones
```

#### 4. **Controladores**
```php
✓ app/Http/Controllers/PedidosWebController.php
  - index() - Vista bandeja
  - show() - Detalle pedido
  - process() - Aprobar y facturar
  - reject() - Rechazar pedido
  - apiPendientes() - API dropdown

✓ app/Http/Controllers/Api/V1/OrdersController.php (API REST)
```

#### 5. **Vistas**
```blade
✓ resources/views/pedidos-web/index.blade.php - Bandeja completa
✓ resources/views/pedidos-web/show.blade.php - Detalle pedido
✓ resources/views/layouts/navigation.blade.php - Header con campana
✓ resources/views/layouts/app.blade.php - Scripts Alpine.js
```

#### 6. **Rutas**
```php
✓ routes/web.php
  GET  /pedidos-web              - Bandeja de entrada
  GET  /pedidos-web/{id}         - Ver detalle
  POST /pedidos-web/{id}/process - Procesar y facturar
  POST /pedidos-web/{id}/reject  - Rechazar
  GET  /pedidos-web/api/pendientes - API dropdown

✓ routes/api.php
  POST   /api/v1/orders              - Web envía pedido
  GET    /api/v1/orders/pending      - Listar pendientes
  GET    /api/v1/orders/unread/count - Contador
  GET    /api/v1/orders/{id}         - Ver detalle
  POST   /api/v1/orders/{id}/process - Procesar
```

### 🚀 Cómo Usar el Sistema

#### **PASO 1: Web envía pedido**
```bash
POST http://127.0.0.1:8000/api/v1/orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "order_number": "WEB-20260113-001",
  "customer_name": "María García",
  "customer_email": "maria@example.com",
  "customer_phone": "+504 9999-8888",
  "items": [
    {
      "sku": "7116",
      "quantity": 2,
      "price": 150.00
    }
  ],
  "subtotal": 300.00,
  "tax": 45.00,
  "total": 345.00,
  "payment_method": "tarjeta_credito",
  "notes": "Cliente VIP"
}
```

#### **PASO 2: Notificación en Zenvy**
- 🔔 Aparece badge rojo en campana
- Pedido visible en dropdown del header
- Estado: **Pendiente** (no leído)

#### **PASO 3: Usuario revisa pedido**
1. Click en el pedido del dropdown O
2. Acceder a `/pedidos-web`
3. Ver detalles completos
4. Al abrir → marcado como "leído"

#### **PASO 4: Procesar pedido**
- Click en botón "✓ Procesar y Facturar"
- Sistema automáticamente:
  1. Valida stock disponible
  2. Crea transacción
  3. Crea factura en Zenvy
  4. Decrementa stock usando FIFO
  5. Actualiza estado a "facturado"

### 📊 Estados de Pedidos

| Estado | Color | Descripción |
|--------|-------|-------------|
| `pendiente` | 🟡 Amarillo | Recién recibido, esperando revisión |
| `procesando` | 🔵 Azul | En revisión por usuario Zenvy |
| `facturado` | 🟢 Verde | Procesado y facturado exitosamente |
| `rechazado` | 🔴 Rojo | Rechazado por falta stock u otro motivo |

### 🧪 Datos de Prueba Insertados

**3 pedidos de ejemplo:**
```
✓ WEB-20260113-001 - Juan Carlos Martínez (no leído) 🔔
  • 3 productos variados
  • Cliente frecuente

✓ WEB-20260113-002 - María Elena Rodríguez (no leído) 🔔
  • 1 producto
  • Necesita factura con RTN

✓ WEB-20260113-003 - Roberto Sánchez (leído)
  • 3 productos
  • Recoger en tienda
```

### 🔄 Flujo Completo Implementado

```
┌─────────────┐
│   WEB       │  1. Cliente hace checkout
│  E-COMMERCE │  2. Envía JSON a API
└──────┬──────┘
       │ POST /api/v1/orders
       ▼
┌─────────────────────┐
│  API ZENVY          │  3. Valida datos
│  OrdersController   │  4. Crea registro pedido_web
└──────┬──────────────┘
       │ Estado: pendiente
       ▼
┌──────────────────────┐
│  BANDEJA ENTRADA 🔔  │  5. Aparece notificación
│  Header Zenvy        │  6. Usuario hace click
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│  DETALLE PEDIDO      │  7. Revisa productos
│  /pedidos-web/{id}   │  8. Verifica cliente
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│  PROCESAR ✓          │  9. Click "Procesar y Facturar"
│  OrderService        │  10. Valida stock
│                      │  11. Crea factura
│                      │  12. Decrementa inventario
└──────┬───────────────┘
       │ Estado: facturado
       ▼
┌──────────────────────┐
│  FACTURA CREADA ✅   │  13. Listo para imprimir
│  factura.id          │  14. Stock actualizado
└──────────────────────┘
```

### 📍 Accesos Directos

**Interfaz Usuario:**
- Bandeja entrada: http://127.0.0.1:8000/pedidos-web
- Header campana: Visible en cualquier página autenticada

**API REST:**
```bash
# Obtener token
POST http://127.0.0.1:8000/api/v1/auth/token

# Enviar pedido
POST http://127.0.0.1:8000/api/v1/orders

# Ver pendientes
GET http://127.0.0.1:8000/api/v1/orders/pending

# Contador no leídos
GET http://127.0.0.1:8000/api/v1/orders/unread/count
```

### 🎨 Características de la UI

**Bandeja de Entrada:**
- ✅ Tabla responsive con paginación
- ✅ Badge visual para no leídos (fondo azul claro)
- ✅ Estados con colores (pendiente, procesando, facturado)
- ✅ Filtros y búsqueda
- ✅ Contador de items y totales

**Vista Detalle:**
- ✅ Información completa del cliente
- ✅ Lista de productos solicitados
- ✅ Cálculo de impuestos y totales
- ✅ Botones de acción (Procesar / Rechazar)
- ✅ Vinculación a factura creada

**Dropdown Header:**
- ✅ Icono campana con badge de notificaciones
- ✅ Últimos 10 pedidos pendientes
- ✅ Actualización automática cada 30 seg
- ✅ Vista previa con cliente y monto
- ✅ Link directo a detalle

### 🔐 Seguridad

- ✅ Autenticación requerida para vistas web
- ✅ API con JWT token
- ✅ Rate limiting en API
- ✅ Validación de datos entrada
- ✅ CSRF protection
- ✅ Auditoría en api_logs

### 🧪 Testing

**Demo completo disponible:**
```bash
php demo_pedidos_web.php
```

**Script de datos ejemplo:**
```bash
php insertar_pedidos_ejemplo.php
```

### 📝 Próximos Pasos Sugeridos

1. **Notificaciones Push:** WebSockets para notificaciones real-time
2. **Filtros avanzados:** Por fecha, estado, cliente
3. **Exportación:** PDF/Excel de pedidos
4. **Estadísticas:** Dashboard con métricas
5. **Integración Email:** Enviar confirmaciones automáticas
6. **Webhooks:** Notificar a la web cuando se procesa

### ✅ Estado Actual

```
✓ Bandeja de entrada funcional
✓ Notificaciones en header
✓ Sistema completo de procesamiento
✓ 3 pedidos de ejemplo insertados
✓ Integración API completa
✓ Stock management con FIFO
✓ UI responsive y moderna
```

**🎉 EL SISTEMA ESTÁ COMPLETAMENTE OPERATIVO**

Accede ahora a: **http://127.0.0.1:8000/pedidos-web**
