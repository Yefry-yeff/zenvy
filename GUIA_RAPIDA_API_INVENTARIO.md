# 📦 API Inventario - Guía Rápida

## 🎯 Qué se implementó

Se creó un **sistema de sincronización de inventario en tiempo real** que agrupa productos por categorías/segmentos.

---

## 📂 Archivos Entregados

### 📋 Archivos de Configuración

```
✅ Insomnia_Inventory_API.json
   └─ Colección de Insomnia con todos los endpoints
   └─ Variables de entorno preconfiguradas
   └─ Ejemplos de respuestas JSON

✅ INSOMNIA_INVENTORY_GUIA.md
   └─ Guía completa en Markdown
   └─ Instrucciones paso a paso
   └─ Documentación de cada endpoint

✅ RESUMEN_API_INVENTARIO.md
   └─ Resumen técnico de la implementación
   └─ Estructura de base de datos
   └─ Recomendaciones de sincronización
```

### 💻 Código JavaScript

```
✅ zenvy-inventory-client.js
   └─ Cliente reutilizable para JavaScript
   └─ 7 métodos para diferentes casos de uso
   └─ Sincronización automática con callbacks
   └─ Ejemplos de uso incluidos
```

### 🌐 Ejemplo HTML

```
✅ ejemplo_inventario_realtime.html
   └─ Página de ejemplo completa
   └─ Interfaz con Tailwind CSS
   └─ Sincronización en tiempo real
   └─ Filtrado por categoría
   └─ Indicadores visuales de stock
```

### 🔧 Código Backend

```
✅ app/Services/Api/InventoryService.php (MODIFICADO)
   └─ getProductsByCategory() - Productos por categoría
   └─ getCategoriesWithStock() - Solo categorías

✅ app/Http/Controllers/Api/V1/InventoryController.php (MODIFICADO)
   └─ byCategory() - Endpoint productos por categoría
   └─ categories() - Endpoint categorías

✅ routes/api.php (MODIFICADO)
   └─ Rutas registradas para los 2 nuevos endpoints
```

---

## 🚀 Inicio Rápido

### 1️⃣ Importar en Insomnia

```
1. Abre Insomnia
2. File → Import → From File
3. Selecciona: Insomnia_Inventory_API.json
4. ✅ Workspace creado
```

### 2️⃣ Configurar Token

```
1. En Insomnia, abre Environment: "Inventory Environment"
2. Modifica:
   - base_url: tu servidor
   - api_key: tu clave
   - api_secret: tu secreto
3. Ejecuta: "1. Obtener Token JWT"
4. Copia el token a la variable "token"
```

### 3️⃣ Probar Endpoints

```
1. GET /api/v1/inventory/by-category
   → Ver productos agrupados por categoría

2. GET /api/v1/inventory/categories
   → Ver solo categorías con stock
```

### 4️⃣ Usar en Frontend

**Opción A: Con el cliente JavaScript**
```html
<script src="zenvy-inventory-client.js"></script>
<script>
  const inventory = new ZenvyInventoryClient(
    'http://localhost:8000',
    'tu_token'
  );
  
  // Sincronizar cada 30 segundos
  inventory.startProductsCategorySync((error, data) => {
    if (!error) {
      console.log('Productos:', data.data);
      renderUI(data.data);
    }
  }, 30000);
</script>
```

**Opción B: Con fetch directo**
```javascript
setInterval(() => {
  fetch('/api/v1/inventory/by-category', {
    headers: {
      'Authorization': 'Bearer ' + token,
      'Accept': 'application/json'
    }
  })
  .then(r => r.json())
  .then(data => renderUI(data.data));
}, 30000);
```

### 5️⃣ Ver Ejemplo

```
1. Abre: ejemplo_inventario_realtime.html
2. Configura el TOKEN en localStorage:
   localStorage.setItem('inventory_token', 'tu_token');
3. Click en "Iniciar Sincronización"
4. ✅ Verás actualizaciones cada 30 segundos
```

---

## 🔄 Flujo de Datos

```
┌─────────────────────────────────────────────┐
│  Frontend (HTML/JS)                         │
│  ┌──────────────────────────────────────┐   │
│  │ Sincronización cada 30-60 segundos   │   │
│  └──────────────────────────────────────┘   │
└──────────────┬────────────────────────────────┘
               │
               ↓
        /api/v1/inventory/by-category
        /api/v1/inventory/categories
               │
               ↓
┌──────────────────────────────────────────────┐
│  Backend (Laravel)                           │
│  ┌──────────────────────────────────────┐   │
│  │ InventoryController                  │   │
│  │ ├─ byCategory()                      │   │
│  │ └─ categories()                      │   │
│  └──────────────────────────────────────┘   │
│  ┌──────────────────────────────────────┐   │
│  │ InventoryService                     │   │
│  │ ├─ getProductsByCategory()           │   │
│  │ │  └─ Cache 5 minutos               │   │
│  │ └─ getCategoriesWithStock()          │   │
│  │    └─ Cache 5 minutos               │   │
│  └──────────────────────────────────────┘   │
│  ┌──────────────────────────────────────┐   │
│  │ Base de Datos                        │   │
│  │ ├─ producto                          │   │
│  │ ├─ categoria                         │   │
│  │ └─ recibido_bodega                   │   │
│  └──────────────────────────────────────┘   │
└──────────────────────────────────────────────┘
```

---

## 📊 Estructura de Respuesta

### Productos por Categoría

```json
{
  "success": true,
  "data": [
    {
      "categoria_id": 1,
      "categoria_nombre": "Agendas",
      "categoria_descripcion": "...",
      "total_productos": 3,
      "stock_total": 45,
      "productos": [
        {
          "id": 10,
          "sku": "AGD-001",
          "codigo_barra": "7891234567890",
          "nombre": "Agenda 10x15...",
          "stock": 15,
          "precio_venta": 725.00,
          "precio_compra": 450.00,
          "margen": 61.11
        }
      ]
    }
  ],
  "meta": {
    "total_categories": 1,
    "total_products": 3,
    "total_stock": 45,
    "timestamp": "2026-01-22T22:30:00Z"
  }
}
```

### Solo Categorías

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Agendas",
      "descripcion": "Productos de agenda",
      "total_productos": 3,
      "stock_total": 45
    }
  ],
  "meta": {
    "total_categories": 1,
    "total_products": 3,
    "total_stock": 45,
    "timestamp": "2026-01-22T22:30:00Z"
  }
}
```

---

## ⚙️ Sincronización Recomendada

| Endpoint | Frecuencia | Uso |
|----------|-----------|-----|
| `/by-category` | 30-60 seg | Actualizar lista completa |
| `/categories` | 5 min | Actualizar dropdowns |
| `/low-stock` | 5 min | Alertas stock bajo |
| `/validate-stock` | Al comprar | Validar carrito |

---

## 🔐 Variables de Entorno

```json
{
  "base_url": "http://127.0.0.1:8000",
  "api_key": "pk_LhkEAssdwNMPjJMc3gqJL2TtIX96mi1s",
  "api_secret": "sk_3fXa0kouB9l0Qiw1mLYoIcZKRX6WbIfLykKCLK5tT2fKKqPOYoQs7mOeS7tP1LcZ",
  "token": ""
}
```

**Donde obtener:**
- Consultar al administrador del sistema
- Están en `.env` del servidor

---

## 📝 Métodos Disponibles (JavaScript)

### Obtener datos

```javascript
// Productos por categoría
await inventory.getProductsByCategory()

// Solo categorías
await inventory.getCategories()

// Inventario paginado
await inventory.getInventory({ per_page: 20, page: 1 })

// Stock bajo
await inventory.getLowStockProducts(10)

// Producto por SKU
await inventory.getProductBySku('AGD-001')

// Producto por código de barras
await inventory.getProductByBarcode('7891234567890')

// Validar stock
await inventory.validateStock([
  { sku: 'AGD-001', quantity: 5 },
  { sku: 'CUA-001', quantity: 2 }
])
```

### Sincronización

```javascript
// Iniciar
inventory.startProductsCategorySync(callback, 30000)
inventory.startCategoriesSync(callback, 300000)

// Detener
inventory.stopProductsCategorySync()
inventory.stopCategoriesSync()
inventory.stopAllSync()
```

---

## 🐛 Solución de Problemas

| Problema | Solución |
|----------|----------|
| **Error 401 - No Autorizado** | Token expirado. Obtén uno nuevo |
| **Error 404 - No Encontrado** | Endpoint o producto no existe |
| **Error 500 - Servidor** | Revisar logs en `storage/logs/` |
| **Datos no se actualizan** | Verificar frecuencia de sincronización |
| **Caché antigua** | Ejecutar `Cache::flush()` en artisan tinker |

---

## ✨ Características

✅ Productos agrupados por categoría/segmento  
✅ Stock en tiempo real  
✅ Caché inteligente (5 minutos)  
✅ API REST estándar  
✅ Manejo de errores robusto  
✅ Cliente JavaScript reutilizable  
✅ Sincronización automática  
✅ Ejemplo HTML completo  

---

## 📞 Archivos de Documentación

- **INSOMNIA_INVENTORY_GUIA.md** - Guía completa de Insomnia
- **RESUMEN_API_INVENTARIO.md** - Resumen técnico
- **zenvy-inventory-client.js** - Ejemplos de código
- **ejemplo_inventario_realtime.html** - Demo en vivo

---

**Creado:** 22 Enero 2026  
**Versión:** 1.0.0  
**Estado:** ✅ Producción
