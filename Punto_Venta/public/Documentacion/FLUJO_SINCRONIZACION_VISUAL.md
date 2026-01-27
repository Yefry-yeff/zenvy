# 🔄 FLUJO COMPLETO DE SINCRONIZACIÓN

## DIAGRAMA DE FLUJO

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    ZENVY (PUNTO DE VENTA)                              │
└─────────────────────────────────────────────────────────────────────────┘
                              ↓
                    ┌──────────────────────┐
                    │ ¿Qué evento ocurre?  │
                    └──────────────────────┘
                    /        |        |        \
                   /         |        |         \
                  ↙          ↙        ↙          ↙
            [Compra]    [Factura]  [Ajuste]  [Anulación]
              |            |         |          |
              ↓            ↓         ↓          ↓
        RecibirEnBodega  Ventas.php  Stock.php  CancelSale
        (línea 520+)     (línea 3100)(manual)   (manual)
              |            |         |          |
              └────────────┴─────────┴──────────┘
                           ↓
        Tabla recibido_bodega: cantidad_disponible
                    ↓ (se descuenta/incrementa)
                    ↓
    app/Services/WebInventorySyncService.php
                    ↓
            Prepara JSON del evento
                    ↓
        HTTP POST a WEBHOOK_URL
                    ↓
         Headers:
         - Authorization: Bearer {TOKEN}
         - X-Event-Type: inventario.compra_recibida
         - X-Timestamp: 2025-01-23T...
                    ↓
         ┌──────────────────────────────┐
         │   TU PÁGINA WEB              │
         │ POST /api/webhook/inventory  │
         └──────────────────────────────┘
                    ↓
         Valida token Authorization
                    ↓
    ┌─ Procesa evento según tipo ─┐
    │                              │
    ├─→ compra_recibida ───→ +stock
    │
    ├─→ venta_realizada ───→ -stock
    │
    ├─→ stock_actualizado ──→ Actualiza
    │
    ├─→ factura_anulada ────→ Restaura
    │
    └─→ sync_completa ──────→ Reemplaza todo
                    ↓
        Actualiza tu base de datos
                    ↓
         Responde HTTP 200
```

---

## 📍 EVENTOS DETALLADOS

### 1. COMPRA RECIBIDA

```
ZENVY: Usuario ingresa 50 unidades de "Monitor 24""
           ↓
RecibirEnBodega.php (ejecutarRecibido)
           ↓
INSERT INTO recibido_bodega (cantidad_disponible = 50)
           ↓
WebInventorySyncService::sincronizarCompraRecibida()
           ↓
POST {WEBHOOK_URL}
{
  "evento": "inventario.compra_recibida",
  "producto": {"id": 1, "nombre": "Monitor 24", "cantidad_ingresada": 50}
}
           ↓
TU PÁGINA WEB: UPDATE productos SET stock = 50 WHERE id = 1
```

### 2. VENTA/FACTURACIÓN

```
ZENVY: Usuario factura 5 monitores
           ↓
Ventas.php (guardarProductoConDistribucionSecciones)
           ↓
UPDATE recibido_bodega SET cantidad_disponible = cantidad_disponible - 5
           ↓
SELECT SUM(cantidad_disponible) → 45 (stock nuevo)
           ↓
WebInventorySyncService::sincronizarCambioStock()
           ↓
POST {WEBHOOK_URL}
{
  "evento": "inventario.venta_realizada",
  "factura": {"id": 123, "total": 750},
  "productos_vendidos": [{"id": 1, "cantidad": 5}]
}
           ↓
TU PÁGINA WEB: UPDATE productos SET stock = 45 WHERE id = 1
```

### 3. ANULACIÓN DE FACTURA

```
ZENVY: Usuario anula factura #123
           ↓
Modelo Factura o Ventas.php (cancelSale)
           ↓
UPDATE recibido_bodega SET cantidad_disponible = cantidad_disponible + 5
UPDATE recibido_bodega SET estado_id = 1
           ↓
WebInventorySyncService::sincronizarAnulacionFactura()
           ↓
POST {WEBHOOK_URL}
{
  "evento": "inventario.factura_anulada",
  "factura": {"id": 123},
  "productos_restaurados": [{"id": 1, "cantidad": 5}]
}
           ↓
TU PÁGINA WEB: UPDATE productos SET stock = 50 WHERE id = 1
```

---

## 🔐 PROCESO DE VALIDACIÓN DE WEBHOOK

```
TU PÁGINA WEB recibe POST /api/webhook/inventory

┌─ PASO 1: Validar Token ─────────────────┐
│                                          │
│  $token = request->header('Authorization')
│                   ↓
│  str_replace('Bearer ', '', $token)
│                   ↓
│  Comparar con: env('WEBHOOK_TOKEN')
│                   ↓
│  if ($token !== config('zenvy.webhook_token')) {
│      return 401 Unauthorized
│  }
│
└──────────────────────────────────────────┘
                   ↓
┌─ PASO 2: Extraer Evento ────────────────┐
│                                          │
│  $evento = $request->input('evento')
│  // 'inventario.compra_recibida'
│  // 'inventario.venta_realizada'
│  // etc
│
└──────────────────────────────────────────┘
                   ↓
┌─ PASO 3: Procesar por Tipo ─────────────┐
│                                          │
│  switch($evento) {
│    case 'inventario.compra_recibida':
│        DB::table('productos')
│          ->where('zenvy_id', $id)
│          ->increment('stock', $cantidad);
│        break;
│    case 'inventario.venta_realizada':
│        DB::table('productos')
│          ->where('zenvy_id', $id)
│          ->decrement('stock', $cantidad);
│        break;
│  }
│
└──────────────────────────────────────────┘
                   ↓
┌─ PASO 4: Responder ─────────────────────┐
│                                          │
│  return response()->json([
│    'success' => true,
│    'evento' => $evento
│  ]);
│
└──────────────────────────────────────────┘
```

---

## 🔄 CICLO DE VIDA DE UN STOCK

```
ESTADO INICIAL
Stock: 0

     EVENTO: Ingreso de Compra (RecibirEnBodega)
     ↓
INSERT recibido_bodega (cantidad_disponible: 100, estado: 1)
     ↓
Stock: 100 ✅

     EVENTO: Factura 1 (Venta de 30 unidades)
     ↓
UPDATE recibido_bodega SET cantidad_disponible = 70
     ↓
Stock: 70

     EVENTO: Factura 2 (Venta de 40 unidades)
     ↓
UPDATE recibido_bodega SET cantidad_disponible = 30
     ↓
Stock: 30

     EVENTO: Factura 3 (Venta de 30 unidades)
     ↓
UPDATE recibido_bodega SET cantidad_disponible = 0
UPDATE recibido_bodega SET estado_id = 2 (INACTIVO)
     ↓
Stock: 0 ❌ (Agotado)

     EVENTO: Anulación de Factura 3
     ↓
UPDATE recibido_bodega SET cantidad_disponible = 30
UPDATE recibido_bodega SET estado_id = 1 (ACTIVO)
     ↓
Stock: 30 ✅ (Restaurado)
```

---

## 📋 TABLA DE RESPONSABILIDADES

| Componente | Responsabilidad | Archivo |
|-----------|-----------------|---------|
| **RecibirEnBodega** | Detectar ingreso de compra | `app/Livewire/Inventario/RecibirEnBodega.php` |
| **Ventas** | Detectar facturación y descuento | `app/Livewire/SalaDeVentas/Ventas.php` |
| **WebInventorySyncService** | Serializar datos + enviar HTTP | `app/Services/WebInventorySyncService.php` |
| **Tu Página Web** | Recibir webhook + actualizar stock | Tu archivo de webhook |

---

## 🚨 PUNTOS CRÍTICOS

### 1. Token debe coincidir
```
Zenvy .env:      WEBHOOK_TOKEN=abc123
Tu página .env:  WEBHOOK_TOKEN=abc123

Si no coinciden → 401 Unauthorized
```

### 2. URL debe ser alcanzable
```
Zenvy debe poder hacer POST a https://tuwebsite.com/api/webhook/inventory

Si no es alcanzable:
- Zenvy registra en logs
- Tu stock NO se actualiza
- Zenvy sigue funcionando normal
```

### 3. FIFO en Facturación
```
Lote 1: fecha_recibido = 2025-01-10, cantidad = 30
Lote 2: fecha_recibido = 2025-01-20, cantidad = 50

Cuando se factura 40:
- Toma 30 del Lote 1 (más antiguo)
- Toma 10 del Lote 2 (más nuevo)

Resultado:
- Lote 1: cantidad_disponible = 0 (estado_id = 2)
- Lote 2: cantidad_disponible = 40 (estado_id = 1)
```

### 4. Cache evita duplicados
```
Si el mismo webhook se dispara 2 veces en 30 segundos:
- Primer intento: Se envía ✅
- Segundo intento: Descartado por cache

Cache key: webhook_sent_{evento}_{timestamp}
Cache TTL: 30 segundos
```

---

## 📊 EJEMPLO COMPLETO

### En Zenvy

1. Usuario ingresa **50 Monitor LG 24"**
   - Click en "RecibirEnBodega"
   - Select producto: "Monitor LG 24""
   - Cantidad: 50
   - Click "Guardar"

2. Sistema crea registro en `recibido_bodega`
   - `producto_id`: 1
   - `cantidad_disponible`: 50
   - `estado_id`: 1
   - `fecha_recibido`: 2025-01-23

3. Dispara webhook automáticamente

### En Tu Página Web

1. Recibe POST a `/api/webhook/inventory`
   ```json
   {
     "evento": "inventario.compra_recibida",
     "timestamp": "2025-01-23T10:30:45Z",
     "producto": {
       "id": 1,
       "nombre": "Monitor LG 24"",
       "cantidad_ingresada": 50
     }
   }
   ```

2. Procesa con tu controlador
   ```php
   $producto = $request->input('producto');
   DB::table('productos')
     ->where('zenvy_id', $producto['id'])
     ->update(['stock' => $producto['cantidad_ingresada']]);
   ```

3. Tu página web ahora muestra: **Stock: 50 ✅**

---

## ✅ VERIFICACIÓN VISUAL

```
ANTES:
Zenvy:     Stock = 0
Tu Web:    Stock = 0
(desincronizados)

INGRESO EN ZENVY:
Zenvy:     Stock = +50 → Stock = 50 ✅
           Dispara webhook...

EN TU WEB:
Tu Web:    Recibe webhook
           Stock = 50 ✅
           
RESULTADO:
Zenvy:     Stock = 50 ✅
Tu Web:    Stock = 50 ✅
(sincronizados perfectamente)
```

---

**¡El sistema funciona automáticamente una vez configurado!** 🎉
