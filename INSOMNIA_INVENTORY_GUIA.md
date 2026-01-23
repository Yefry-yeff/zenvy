# 📦 Zenvy API - Inventario

Configuración de Insomnia para los endpoints de Inventario.

## 📥 Cómo Importar

### Opción 1: Importar desde Archivo
1. Abre **Insomnia**
2. Ve a `File` → `Import` → `From File`
3. Selecciona `Insomnia_Inventory_API.json`
4. Se creará un nuevo workspace llamado "Zenvy API - Inventario"

### Opción 2: Importar desde URL
1. Ve a `File` → `Import` → `From URL`
2. Pega la URL del archivo raw de GitHub (si está sincronizado)

---

## 🔐 Configuración Inicial

### Paso 1: Configurar Variables de Entorno

1. En Insomnia, abre el tab **"Inventory Environment"**
2. Modifica estas variables:

```json
{
  "base_url": "http://127.0.0.1:8000",
  "api_key": "tu_api_key_aqui",
  "api_secret": "tu_api_secret_aqui",
  "token": ""
}
```

**Donde obtener las credenciales:**
- Consulta con el administrador del sistema
- Están en el archivo `.env` del servidor

### Paso 2: Obtener Token JWT

1. Ejecuta el request: **`1. Obtener Token JWT`**
2. En la respuesta, copia el valor de `data.token`
3. Pégalo en la variable `token` del Environment
4. Guarda el Environment (Ctrl+S)

---

## 📋 Endpoints Disponibles

### 🔐 Autenticación

#### `POST /api/v1/auth/token`
Obtiene un token JWT para autenticación.

**Body:**
```json
{
  "api_key": "tu_api_key",
  "api_secret": "tu_api_secret"
}
```

---

### 📦 Inventario - Productos y Categorías

#### 1️⃣ `GET /api/v1/inventory/by-category`
**Productos agrupados por categoría con stock**

Retorna todos los productos con stock disponible, organizados por categoría/segmento.

**Campos en respuesta:**
- `id`: ID del producto
- `sku`: Código único del producto
- `codigo_barra`: Código de barras
- `nombre`: Nombre del producto
- `stock`: Stock disponible
- `precio_venta`: Precio de venta
- `precio_compra`: Precio de compra
- `margen`: Margen de ganancia (%)

**Meta:**
- `total_categories`: Total de categorías
- `total_products`: Total de productos
- `total_stock`: Stock total
- `timestamp`: Hora de consulta

**Caché:** 5 minutos

**Uso recomendado:** Sincronizar inventario completo cada 30-60 segundos en el frontend

---

#### 2️⃣ `GET /api/v1/inventory/categories`
**Solo categorías con stock disponible**

Más ligero que `by-category`, ideal para selectores y filtros.

**Campos en respuesta:**
- `id`: ID de categoría
- `nombre`: Nombre de la categoría
- `descripcion`: Descripción
- `total_productos`: Cantidad de productos en la categoría
- `stock_total`: Stock total de la categoría

**Caché:** 5 minutos

**Uso recomendado:** Actualizar dropdowns de filtros

---

#### 3️⃣ `GET /api/v1/inventory?per_page=20&page=1`
**Inventario completo (paginado)**

Obtiene todos los productos con opciones de filtrado y paginación.

**Parámetros opcionales:**
- `per_page`: Items por página (default: 15, máximo: 100)
- `page`: Número de página
- `category_id`: Filtrar por categoría (ej: 1)
- `available`: Solo disponibles (true/false)
- `search`: Buscar por nombre
- `min_stock`: Stock mínimo
- `order_by`: Campo para ordenar (nombre, sku, stock)
- `order_direction`: 'asc' o 'desc'

**Ejemplo:**
```
?category_id=1&per_page=50&order_by=nombre&order_direction=asc
```

---

#### 4️⃣ `GET /api/v1/inventory/low-stock?threshold=10`
**Productos con stock bajo**

Obtiene productos con stock por debajo del umbral.

**Parámetros:**
- `threshold`: Cantidad mínima (default: stock_minimo del producto)

---

#### 5️⃣ `GET /api/v1/inventory/{sku}`
**Producto específico por SKU**

**Ejemplo:** `GET /api/v1/inventory/AGD-001`

---

#### 6️⃣ `GET /api/v1/inventory/barcode/{barcode}`
**Producto por código de barras**

**Ejemplo:** `GET /api/v1/inventory/barcode/7891234567890`

---

#### 7️⃣ `POST /api/v1/inventory/validate-stock`
**Validar stock de múltiples productos**

Verifica si hay suficiente stock para una lista de productos.

**Body:**
```json
{
  "items": [
    {
      "sku": "AGD-001",
      "quantity": 5
    },
    {
      "sku": "CUA-001",
      "quantity": 10
    }
  ]
}
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "available": false,
    "items": [
      {
        "sku": "AGD-001",
        "product_id": 10,
        "product_name": "Agenda 10x15 2 Días",
        "requested": 5,
        "available": 15,
        "is_available": true
      },
      {
        "sku": "CUA-001",
        "product_id": 20,
        "product_name": "Cuaderno 100 Hojas",
        "requested": 10,
        "available": 5,
        "is_available": false
      }
    ]
  }
}
```

---

## 💡 Ejemplo de Respuesta

### `GET /api/v1/inventory/by-category`

```json
{
  "success": true,
  "data": [
    {
      "categoria_id": 1,
      "categoria_nombre": "Agendas",
      "categoria_descripcion": "Productos de agenda y planeación",
      "total_productos": 3,
      "stock_total": 45,
      "productos": [
        {
          "id": 10,
          "sku": "AGD-001",
          "codigo_barra": "7891234567890",
          "nombre": "Agenda 10x15 2 Días por Página Spring C18",
          "stock": 15,
          "precio_venta": 725.00,
          "precio_compra": 450.00,
          "margen": 61.11
        },
        {
          "id": 11,
          "sku": "AGD-002",
          "codigo_barra": "7891234567891",
          "nombre": "Agenda Ejecutiva A4",
          "stock": 20,
          "precio_venta": 850.00,
          "precio_compra": 500.00,
          "margen": 70.00
        }
      ]
    },
    {
      "categoria_id": 2,
      "categoria_nombre": "Cuadernos",
      "categoria_descripcion": "Cuadernos y libros de notas",
      "total_productos": 2,
      "stock_total": 120,
      "productos": [
        {
          "id": 20,
          "sku": "CUA-001",
          "codigo_barra": "7891234567900",
          "nombre": "Cuaderno 100 Hojas Rayado",
          "stock": 80,
          "precio_venta": 85.00,
          "precio_compra": 45.00,
          "margen": 88.89
        }
      ]
    }
  ],
  "meta": {
    "total_categories": 2,
    "total_products": 5,
    "total_stock": 165,
    "timestamp": "2026-01-22T22:30:00Z"
  }
}
```

---

## 🚀 Uso desde Frontend (JavaScript)

### Sincronización cada 30 segundos:

```javascript
// Obtener productos por categoría
setInterval(() => {
  fetch('/api/v1/inventory/by-category', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  })
  .then(r => r.json())
  .then(data => {
    console.log('Inventario actualizado:', data.data);
    console.log('Stock total:', data.meta.total_stock);
    
    // Actualizar UI aquí
    actualizarProductos(data.data);
  })
  .catch(err => console.error('Error:', err));
}, 30000); // 30 segundos
```

### Obtener solo categorías (más ligero):

```javascript
fetch('/api/v1/inventory/categories', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
.then(r => r.json())
.then(data => {
  console.log('Categorías:', data.data);
  
  // Llenar dropdown
  const select = document.getElementById('category-filter');
  data.data.forEach(cat => {
    const option = document.createElement('option');
    option.value = cat.id;
    option.textContent = `${cat.nombre} (${cat.stock_total} en stock)`;
    select.appendChild(option);
  });
})
.catch(err => console.error('Error:', err));
```

---

## 🔄 Sincronización Recomendada

| Endpoint | Frecuencia | Uso |
|----------|-----------|-----|
| `/by-category` | Cada 30-60s | Actualizar inventario completo |
| `/categories` | Cada 5m | Actualizar selectores |
| `/low-stock` | Cada 5m | Alertas de stock bajo |
| `/validate-stock` | Al agregar items | Validar carrito |

---

## ⚠️ Manejo de Errores

### Error 401 - No Autorizado
- Token expirado o inválido
- Solución: Obtén un nuevo token

### Error 404 - No Encontrado
- Producto/SKU no existe
- Verificar que el SKU sea correcto

### Error 500 - Error del Servidor
- Error interno
- Revisar logs del servidor

---

## 📚 Información Adicional

- **Autenticación:** Bearer Token (JWT)
- **Caché:** 5 minutos por defecto
- **Rate Limit:** Según configuración del servidor
- **Timeout:** 30 segundos

Para preguntas o problemas, contacta al administrador del sistema.
