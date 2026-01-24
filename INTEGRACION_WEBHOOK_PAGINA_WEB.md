# Integración del Webhook de Sincronización de Inventario en la Página Web

## 📋 Resumen

La página web debe recibir webhooks automáticos cada vez que ocurran cambios de inventario en Zenvy POS. Estos cambios incluyen:

- ✅ **Compras Recibidas** - Cuando se ingresa una compra en "Recibir en Bodega"
- ✅ **Cambios de Stock** - Cuando se factura un producto (descontar stock)
- ✅ **Ajustes de Cantidades** - Cuando se hacen ajustes manuales de inventario
- ✅ **Sincronización Completa** - Cada 5 minutos como backup

---

## 🔧 Configuración del Servidor

### 1. Requisitos Previos

- Servidor web con PHP 7.4+ (recomendado 8.0+)
- Acceso a tu base de datos (la misma que Zenvy)
- Conexión a `localhost:8001` o el puerto donde corra Zenvy POS

### 2. Crear el Receptor del Webhook

Crea un archivo llamado `webhook-inventory.php` en la raíz de tu página web:

```php
<?php
/**
 * Receptor de Webhooks de Sincronización de Inventario
 * Recibe actualizaciones en tiempo real desde Zenvy POS
 */

// Configuración
define('WEBHOOK_TOKEN', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzeXN0ZW1hIjoiWmVudnktUE9TIiwiYXBpLXYiOjEsInNjb3BlIjoicHJvZHVjdG8taW52ZW50YXJpbyJ9');
define('LOG_FILE', __DIR__ . '/logs/webhook.log');

// Crear directorio de logs si no existe
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Función para loguear
function logWebhook($data) {
    $message = date('Y-m-d H:i:s') . ' - ' . json_encode($data) . "\n";
    file_put_contents(LOG_FILE, $message, FILE_APPEND);
}

// Validar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Obtener headers
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

// Validar token
$token = str_replace('Bearer ', '', $authHeader);
if ($token !== WEBHOOK_TOKEN) {
    logWebhook(['error' => 'Invalid token', 'received' => substr($token, 0, 20)]);
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Obtener payload JSON
$payload = json_decode(file_get_contents('php://input'), true);

if (!$payload) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Loguear webhook recibido
logWebhook([
    'evento' => $payload['evento'] ?? 'unknown',
    'timestamp' => $payload['timestamp'] ?? null,
    'data' => $payload
]);

// Procesar según el tipo de evento
$evento = $payload['evento'] ?? null;

try {
    switch ($evento) {
        case 'inventario.sincronizacion_completa':
            procesarSincronizacionCompleta($payload);
            break;
            
        case 'inventario.compra_recibida':
            procesarCompraRecibida($payload);
            break;
            
        case 'inventario.cambio_stock':
            procesarCambioStock($payload);
            break;
            
        case 'inventario.ajuste_cantidad':
            procesarAjusteStock($payload);
            break;
            
        default:
            logWebhook(['warning' => 'Unknown event type: ' . $evento]);
    }
    
    // Responder exitosamente
    http_response_code(200);
    echo json_encode(['success' => true, 'evento' => $evento]);
    
} catch (\Exception $e) {
    logWebhook(['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// ============================================
// Funciones de Procesamiento
// ============================================

/**
 * Procesar sincronización completa de inventario
 * Se ejecuta cada 5 minutos como backup
 */
function procesarSincronizacionCompleta($payload) {
    logWebhook([
        'action' => 'sync_completa',
        'total_categorias' => count($payload['categorias'] ?? []),
        'total_productos' => $payload['total_productos'] ?? 0,
        'stock_total' => $payload['stock_total'] ?? 0
    ]);
    
    // TODO: Implementar lógica de sincronización
    // Ejemplo: Actualizar tabla de productos en tu BD
    // actualizarProductosDesdeWebhook($payload['categorias']);
}

/**
 * Procesar compra recibida
 * Se ejecuta cuando se ingresa una compra en Zenvy
 */
function procesarCompraRecibida($payload) {
    $productoId = $payload['producto_id'] ?? null;
    $cantidad = $payload['cantidad'] ?? 0;
    
    logWebhook([
        'action' => 'compra_recibida',
        'producto_id' => $productoId,
        'cantidad' => $cantidad
    ]);
    
    // TODO: Incrementar stock del producto
    // Ejemplo: UPDATE productos SET stock_disponible = stock_disponible + ? WHERE id = ?
}

/**
 * Procesar cambio de stock (factura/venta)
 * Se ejecuta cuando se factura un producto
 */
function procesarCambioStock($payload) {
    $productoId = $payload['producto_id'] ?? null;
    $cantidadAnterior = $payload['cantidad_anterior'] ?? 0;
    $cantidadActual = $payload['cantidad_actual'] ?? 0;
    $razon = $payload['razon'] ?? 'venta';
    
    logWebhook([
        'action' => 'cambio_stock',
        'producto_id' => $productoId,
        'cantidad_anterior' => $cantidadAnterior,
        'cantidad_actual' => $cantidadActual,
        'razon' => $razon
    ]);
    
    // TODO: Actualizar stock del producto
    // Ejemplo: UPDATE productos SET stock_disponible = ? WHERE id = ?
}

/**
 * Procesar ajuste de cantidad
 * Se ejecuta cuando hay ajustes manuales
 */
function procesarAjusteStock($payload) {
    $productoId = $payload['producto_id'] ?? null;
    $ajuste = $payload['ajuste'] ?? 0;
    $stockNuevo = $payload['stock_nuevo'] ?? 0;
    
    logWebhook([
        'action' => 'ajuste_stock',
        'producto_id' => $productoId,
        'ajuste' => $ajuste,
        'stock_nuevo' => $stockNuevo
    ]);
    
    // TODO: Actualizar stock del producto
    // Ejemplo: UPDATE productos SET stock_disponible = ? WHERE id = ?
}

?>
```

---

## 📡 Tipos de Webhook que Recibirás

### 1. Sincronización Completa (Cada 5 minutos o manual)

```json
{
  "evento": "inventario.sincronizacion_completa",
  "timestamp": "2026-01-23T21:48:33-06:00",
  "categorias": [
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
    }
  ],
  "total_productos": 45,
  "stock_total": 1250,
  "sync_status": "enviado",
  "webhook_url": "http://localhost:8000/api/webhook/inventory"
}
```

### 2. Compra Recibida

```json
{
  "evento": "inventario.compra_recibida",
  "timestamp": "2026-01-23T21:48:33-06:00",
  "producto_id": 5,
  "codigo_barra": "721310002057",
  "codigo_estatal": "BEB-01-Cocacola",
  "nombre": "Cocacola Zero",
  "cantidad": 100,
  "cantidad_anterior": 50,
  "cantidad_nueva": 150,
  "precio_compra": 75.00
}
```

### 3. Cambio de Stock (Factura/Venta)

```json
{
  "evento": "inventario.cambio_stock",
  "timestamp": "2026-01-23T21:48:33-06:00",
  "producto_id": 5,
  "codigo_barra": "721310002057",
  "codigo_estatal": "BEB-01-Cocacola",
  "nombre": "Cocacola Zero",
  "cantidad_anterior": 150,
  "cantidad_actual": 145,
  "razon": "factura",
  "factura_id": 1234,
  "precio_venta": 100.00
}
```

### 4. Ajuste de Cantidad

```json
{
  "evento": "inventario.ajuste_cantidad",
  "timestamp": "2026-01-23T21:48:33-06:00",
  "producto_id": 5,
  "codigo_barra": "721310002057",
  "nombre": "Cocacola Zero",
  "stock_anterior": 145,
  "stock_nuevo": 142,
  "ajuste": -3,
  "razon": "ajuste_manual"
}
```

---

## 🚀 Configuración en tu Página Web

### Paso 1: Crear Directorio de Logs

```bash
mkdir -p /ruta/pagina-web/logs
chmod 755 /ruta/pagina-web/logs
```

### Paso 2: Configurar tu Servidor Web

Si usas **Apache**, agrega esto a tu `.htaccess`:

```apache
# Permitir acceso al webhook sin autenticación Laravel
<Files "webhook-inventory.php">
    Allow from all
</Files>
```

Si usas **Nginx**, asegúrate que la ruta esté accesible públicamente (sin middleware de autenticación).

### Paso 3: Verificar la Integración

Usa cURL o Postman para probar:

```bash
curl -X POST http://localhost:8000/api/v1/inventory/sync/force \
  -H "Authorization: Bearer tu_token_jwt_aqui" \
  -H "Content-Type: application/json"
```

Debería ver logs en `logs/webhook.log`:

```
2026-01-23 21:48:33 - {"evento":"inventario.sincronizacion_completa","timestamp":"2026-01-23T21:48:33-06:00","data":{...}}
```

---

## 💾 Ejemplo: Integración con Base de Datos

Si tienes una tabla `productos` en tu BD, aquí está la integración:

```php
<?php
// webhook-inventory.php - Versión con BD

// Conexión a BD
$mysqli = new mysqli("localhost", "usuario", "password", "tu_base_datos");

if ($mysqli->connect_error) {
    logWebhook(['error' => 'DB Connection failed: ' . $mysqli->connect_error]);
    http_response_code(500);
    exit;
}

function procesarSincronizacionCompleta($payload) {
    global $mysqli;
    
    foreach ($payload['categorias'] ?? [] as $categoria) {
        foreach ($categoria['productos'] ?? [] as $producto) {
            $id = $producto['id'];
            $stock = $producto['stock'];
            $precio = $producto['precio_venta'];
            $nombre = $mysqli->real_escape_string($producto['nombre']);
            $codigo = $mysqli->real_escape_string($producto['codigo_barra']);
            
            // INSERT o UPDATE
            $sql = "INSERT INTO productos (id, nombre, codigo_barra, stock_disponible, precio_venta, actualizado_en)
                    VALUES ($id, '$nombre', '$codigo', $stock, $precio, NOW())
                    ON DUPLICATE KEY UPDATE 
                    stock_disponible = $stock, 
                    precio_venta = $precio,
                    actualizado_en = NOW()";
            
            if (!$mysqli->query($sql)) {
                logWebhook(['error' => 'SQL Error: ' . $mysqli->error]);
            }
        }
    }
}

function procesarCambioStock($payload) {
    global $mysqli;
    
    $productoId = (int)$payload['producto_id'];
    $stockNuevo = (int)$payload['cantidad_actual'];
    
    $sql = "UPDATE productos SET stock_disponible = $stockNuevo, actualizado_en = NOW() WHERE id = $productoId";
    
    if (!$mysqli->query($sql)) {
        logWebhook(['error' => 'SQL Error: ' . $mysqli->error]);
    }
}

?>
```

---

## 🔐 Token de Webhook

**Token actual:**
```
eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzeXN0ZW1hIjoiWmVudnktUE9TIiwiYXBpLXYiOjEsInNjb3BlIjoicHJvZHVjdG8taW52ZW50YXJpbyJ9
```

**URL del Webhook (Tu página web):**
```
http://127.0.0.1:8001/api/webhook/inventory
```

En producción cambiar a:
```
https://tudominio.com/webhook-inventory.php
```

Si necesitas generar un token nuevo:

```php
<?php
// En Zenvy POS - Punto_Venta/crear_credenciales_api.php

$token = [
    'sub' => 'zenvy-dev-token',
    'iat' => time(),
    'exp' => 9999999999 // Nunca expira
];

$encoded = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']))
    . '.'
    . base64_encode(json_encode($token));

$signature = hash_hmac('sha256', $encoded, 'your-secret-key', true);
$signature = base64_encode($signature);

echo $encoded . '.' . $signature;
?>
```

---

## 📊 Validar que Funciona

### 1. Hacer una Compra en Zenvy
- Ir a **Inventario > Recibir en Bodega**
- Ingresar una compra
- Guardar

### 2. Revisar Logs
```bash
tail -f logs/webhook.log
```

Deberías ver:
```
2026-01-23 21:48:33 - {"action":"compra_recibida","producto_id":5,"cantidad":100}
```

### 3. Hacer una Venta en Zenvy
- Ir a **Sala de Ventas > Facturación**
- Crear una factura
- Confirmar

### 4. Revisar Logs Nuevamente
Deberías ver el evento de cambio de stock inmediatamente.

---

## ❓ Preguntas Frecuentes

**P: ¿El webhook siempre actualiza?**
R: Sí, siempre que:
- ✅ Se recibe una compra en Zenvy
- ✅ Se factura un producto
- ✅ Se hace un ajuste manual
- ✅ Se ejecuta sincronización forzada

**P: ¿Qué pasa si mi servidor está offline?**
R: El webhook se intenta enviar una vez. Si falla, se loguea pero no se reintenta (fire-and-forget).

**P: ¿Puedo cambiar la URL del webhook?**
R: Sí, en Zenvy POS edita el `.env`:
```
WEBHOOK_URL=http://tu-dominio.com/webhook-inventory.php
WEBHOOK_TOKEN=tu_token_aqui
```

**P: ¿Cómo manejo múltiples webhooks?**
R: El endpoint `/api/webhook/inventory` recibe todos los eventos. Diferencia por el campo `evento`.

---

## 📞 Soporte

Si tienes problemas:

1. Verifica que `logs/webhook.log` se esté escribiendo
2. Confirma que `WEBHOOK_TOKEN` coincide en ambos servidores
3. Prueba manualmente desde Postman:
   ```
   POST http://tu-dominio.com/webhook-inventory.php
   Authorization: Bearer [TOKEN]
   Content-Type: application/json
   Body: {"evento":"inventario.sincronizacion_completa","timestamp":"2026-01-23T21:48:33-06:00","categorias":[]}
   ```
4. Revisa los logs de Apache/Nginx si hay errores HTTP

---

## 🎯 Próximos Pasos

1. ✅ Copiar `webhook-inventory.php` a tu servidor web
2. ✅ Crear directorio `/logs` con permisos 755
3. ✅ Implementar funciones de actualización de BD
4. ✅ Probar con una compra en Zenvy
5. ✅ Verificar logs en `logs/webhook.log`
6. ✅ Implementar frontend para mostrar actualizaciones en tiempo real (JavaScript/WebSockets)

¡Tu sistema está listo para sincronización en tiempo real! 🚀
