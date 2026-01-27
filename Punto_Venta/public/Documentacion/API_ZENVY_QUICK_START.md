# 🚀 Acceso Rápido a la API de Zenvy POS

## ✅ Respuesta Directa

**¿Zenvy tiene API?** → SÍ ✅
**¿URL?** → `http://127.0.0.1:8001/api/v1/...`
**¿Token?** → Generado por ti con credenciales

---

## 📋 Quick Start (5 minutos)

### Paso 1: Obtener Token

```bash
curl -X POST http://127.0.0.1:8001/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@zenvy.local",
    "password": "password",
    "name": "Mi Página Web",
    "scope": "producto-inventario"
  }'
```

**Respuesta:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_in": 3600,
  "token_type": "Bearer"
}
```

Guarda el token en variable:
```bash
TOKEN="tu_token_aqui"
```

### Paso 2: Obtener Inventario por Categoría (Recomendado)

```bash
curl -X GET "http://127.0.0.1:8001/api/v1/inventory/by-category" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

**Resultado:**
```json
{
  "success": true,
  "data": [
    {
      "categoria_id": 2,
      "categoria_nombre": "Escolar",
      "total_productos": 15,
      "stock_total": 250,
      "productos": [
        {
          "id": 1,
          "nombre": "Cuaderno Universitario Norma",
          "codigo_barra": "760573020163",
          "stock": 45,
          "precio_venta": 40.00
        }
      ]
    }
  ]
}
```

### Paso 3: Buscar Producto por Código de Barras

```bash
curl -X GET "http://127.0.0.1:8001/api/v1/inventory/barcode/760573020163" \
  -H "Authorization: Bearer $TOKEN"
```

---

## 🎯 Endpoints Principales

| Lo que necesitas | Endpoint | Método |
|------------------|----------|--------|
| Todos los productos | `/api/v1/inventory` | GET |
| **Productos por categoría** ⭐ | `/api/v1/inventory/by-category` | GET |
| Categorías | `/api/v1/inventory/categories` | GET |
| Buscar por código de barras | `/api/v1/inventory/barcode/{código}` | GET |
| Stock bajo | `/api/v1/inventory/low-stock` | GET |
| Validar stock antes de vender | `/api/v1/inventory/validate-stock` | POST |
| Forzar sincronización | `/api/v1/inventory/sync/force` | POST |

---

## 💡 Ejemplos Prácticos

### En tu Página Web - Mostrar Inventario (PHP)

```php
<?php

// Función para obtener token
function getZenvyToken() {
    $ch = curl_init('http://127.0.0.1:8001/api/v1/auth/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => 'admin@zenvy.local',
        'password' => 'password',
        'name' => 'Mi Página Web',
        'scope' => 'producto-inventario'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $response['token'] ?? null;
}

// Función para obtener inventario
function getZenvyInventory($token) {
    $ch = curl_init('http://127.0.0.1:8001/api/v1/inventory/by-category');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $response['data'] ?? [];
}

// Obtener y mostrar
$token = getZenvyToken();
$categorias = getZenvyInventory($token);

?>

<h1>Productos en Stock</h1>

<?php foreach ($categorias as $cat) { ?>
    <h2><?php echo $cat['categoria_nombre']; ?></h2>
    <p><strong>Stock Total: <?php echo $cat['stock_total']; ?> unidades</strong></p>
    
    <table border="1" cellpadding="10">
        <tr>
            <th>Producto</th>
            <th>Código</th>
            <th>Stock</th>
            <th>Precio</th>
        </tr>
        <?php foreach ($cat['productos'] as $prod) { ?>
            <tr>
                <td><?php echo $prod['nombre']; ?></td>
                <td><?php echo $prod['codigo_barra']; ?></td>
                <td><?php echo $prod['stock']; ?></td>
                <td>$<?php echo number_format($prod['precio_venta'], 2); ?></td>
            </tr>
        <?php } ?>
    </table>
<?php } ?>
```

### En tu Página Web - Mostrar Inventario (JavaScript)

```javascript
// Obtener token
async function getZenvyToken() {
    const response = await fetch('http://127.0.0.1:8001/api/v1/auth/token', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            email: 'admin@zenvy.local',
            password: 'password',
            name: 'Mi Página Web',
            scope: 'producto-inventario'
        })
    });
    const data = await response.json();
    return data.token;
}

// Obtener inventario
async function getZenvyInventory(token) {
    const response = await fetch('http://127.0.0.1:8001/api/v1/inventory/by-category', {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        }
    });
    return await response.json();
}

// Usar
async function loadInventory() {
    const token = await getZenvyToken();
    const data = await getZenvyInventory(token);
    
    console.log('Categorías:', data.data);
    
    // Renderizar en tu página
    data.data.forEach(categoria => {
        console.log(`${categoria.categoria_nombre}: ${categoria.stock_total} unidades`);
        
        categoria.productos.forEach(producto => {
            console.log(`  - ${producto.nombre}: $${producto.precio_venta} (${producto.stock} en stock)`);
        });
    });
}

loadInventory();
```

---

## 🔐 Credenciales

**Para obtener token necesitas:**
- Email: `admin@zenvy.local`
- Password: `password` (o la que hayas configurado)
- Nombre App: `Mi Página Web` (personaliza este)
- Scope: `producto-inventario` (recomendado para lectura)

---

## 🌍 URLs

**Desarrollo:**
```
http://127.0.0.1:8001
```

**Producción (cuando desplegues):**
```
https://tudominio.com/zenvy-pos
```

---

## 📦 Estructura de Datos - Lo Que Recibirás

Cada **categoría** contiene:
```json
{
  "categoria_id": 2,
  "categoria_nombre": "Escolar",           // Nombre del segmento
  "total_productos": 15,                  // Cuántos productos tiene
  "stock_total": 250,                     // Stock total de la categoría
  "productos": [                          // Array de productos
    {
      "id": 1,                           // ID único
      "codigo_estatal": "CUA-100-NORMA", // Código de identificación
      "codigo_barra": "760573020163",    // Código de barras
      "nombre": "Cuaderno Universitario",// Nombre
      "stock": 45,                       // Stock disponible
      "precio_venta": 40.00              // Precio de venta
    }
  ]
}
```

---

## ✨ Características

✅ **Actualización en tiempo real** - Los cambios en Zenvy aparecen inmediatamente
✅ **Búsqueda por código de barras** - Integración con escáneres
✅ **Validación de stock** - Antes de procesar compras
✅ **Filtros avanzados** - Por categoría, precio, stock
✅ **Paginación** - Para no sobrecargar la página
✅ **Caché interno** - API cachea 5 minutos para optimizar

---

## 🆘 Troubleshooting

**Error: "Unauthorized"**
→ Token expiró o es inválido. Obtén uno nuevo.

**Error: "Connection refused"**
→ Zenvy POS no está corriendo. Inicia el servidor.

**Datos vacíos**
→ No hay productos en Zenvy. Crea algunos primero.

**Lentitud**
→ Usa paginación: `?per_page=50` en lugar de traer todo.

---

## 📞 Documentación Completa

Ver: `DOCUMENTACION_API_REST_ZENVY.md`

Contiene:
- Todos los endpoints disponibles
- Parámetros detallados
- Ejemplos completos
- Códigos de error
- Testing con Postman

---

**¡API Lista para usar! 🚀**
