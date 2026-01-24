# 🚀 Integración Completa - Página Web → Zenvy POS

## 📋 Configuración

**Zenvy POS API:** `http://127.0.0.1:8000`  
**Tu Página Web:** `http://127.0.0.1:8001`  
**Token actual:** `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzeXN0ZW1hIjoiWmVudnktUE9TIiwiYXBpLXYiOjEsInNjb3BlIjoicHJvZHVjdG8taW52ZW50YXJpbyJ9`

---

## 📦 Archivo 1: `zenvy-client.php`

Copia este archivo en tu página web:

```php
<?php
/**
 * Cliente PHP para API de Zenvy POS
 * Coloca este archivo en tu página web (puerto 8001)
 */

class ZenvyClient {
    private $baseUrl = 'http://127.0.0.1:8000';
    private $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzeXN0ZW1hIjoiWmVudnktUE9TIiwiYXBpLXYiOjEsInNjb3BlIjoicHJvZHVjdG8taW52ZW50YXJpbyJ9';
    
    /**
     * Obtener inventario completo por categorías
     */
    public function getInventory() {
        $url = $this->baseUrl . '/api/v1/inventory/by-category';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return ['success' => false, 'error' => 'Error conectando con Zenvy'];
    }
    
    /**
     * Buscar producto por código de barras
     */
    public function buscarPorCodigo($codigoBarra) {
        $url = $this->baseUrl . '/api/v1/inventory/barcode/' . urlencode($codigoBarra);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return ['success' => false, 'error' => 'Producto no encontrado'];
    }
    
    /**
     * Validar si hay stock disponible
     */
    public function validarStock($items) {
        $url = $this->baseUrl . '/api/v1/inventory/validate-stock';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['items' => $items]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return ['success' => false, 'available' => false];
    }
}
?>
```

---

## 📦 Archivo 2: `ejemplo-mostrar-productos.php`

Ejemplo de cómo mostrar productos en tu página web:

```php
<?php
require_once 'zenvy-client.php';

$zenvy = new ZenvyClient();
$response = $zenvy->getInventory();

if (!$response['success']) {
    die('Error: No se pudo conectar con Zenvy POS');
}

$categorias = $response['data'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Importados desde Zenvy</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .categoria { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .categoria h2 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .productos { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-top: 15px; }
        .producto { background: #fafafa; padding: 15px; border-radius: 5px; border-left: 4px solid #4CAF50; }
        .producto h3 { margin: 0 0 10px 0; font-size: 16px; color: #333; }
        .producto .info { font-size: 14px; color: #666; margin: 5px 0; }
        .producto .precio { font-size: 18px; font-weight: bold; color: #4CAF50; margin-top: 10px; }
        .producto .stock { display: inline-block; padding: 5px 10px; background: #2196F3; color: white; border-radius: 3px; font-size: 12px; }
        .header { text-align: center; padding: 20px; background: white; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏪 Catálogo de Productos</h1>
        <p>Inventario en tiempo real desde Zenvy POS</p>
    </div>

    <?php foreach ($categorias as $categoria): ?>
        <div class="categoria">
            <h2><?php echo htmlspecialchars($categoria['categoria_nombre']); ?></h2>
            <p><strong>Stock Total:</strong> <?php echo $categoria['stock_total']; ?> unidades | 
               <strong>Productos:</strong> <?php echo $categoria['total_productos']; ?></p>
            
            <div class="productos">
                <?php foreach ($categoria['productos'] as $producto): ?>
                    <div class="producto">
                        <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                        <div class="info">
                            <strong>Código:</strong> <?php echo htmlspecialchars($producto['codigo_barra']); ?>
                        </div>
                        <div class="precio">
                            $<?php echo number_format($producto['precio_venta'], 2); ?>
                        </div>
                        <div style="margin-top: 10px;">
                            <span class="stock">Stock: <?php echo $producto['stock']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</body>
</html>
```

---

## 🌐 Archivo 3: `zenvy-client.js` (JavaScript)

Para usar desde el frontend con JavaScript:

```javascript
/**
 * Cliente JavaScript para API de Zenvy POS
 */
class ZenvyClient {
    constructor() {
        this.baseUrl = 'http://127.0.0.1:8000';
        this.token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzeXN0ZW1hIjoiWmVudnktUE9TIiwiYXBpLXYiOjEsInNjb3BlIjoicHJvZHVjdG8taW52ZW50YXJpbyJ9';
    }
    
    async getInventory() {
        const response = await fetch(`${this.baseUrl}/api/v1/inventory/by-category`, {
            headers: {
                'Authorization': `Bearer ${this->token}`,
                'Content-Type': 'application/json'
            }
        });
        return await response.json();
    }
    
    async buscarPorCodigo(codigoBarra) {
        const response = await fetch(`${this.baseUrl}/api/v1/inventory/barcode/${codigoBarra}`, {
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            }
        });
        return await response.json();
    }
}

// Uso:
const zenvy = new ZenvyClient();
zenvy.getInventory().then(data => console.log(data));
```

---

## 📊 Estructura de Datos

### Inventario

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
          "codigo_estatal": "CUA-100-NORMA",
          "codigo_barra": "760573020163",
          "nombre": "Cuaderno Universitario Norma",
          "stock": 45,
          "precio_venta": 40.00
        }
      ]
    }
  ]
}
```

---

## 🚀 Instalación Rápida

1. Copia `zenvy-client.php` a tu página web
2. Copia `ejemplo-mostrar-productos.php`
3. Abre: `http://127.0.0.1:8001/ejemplo-mostrar-productos.php`

¡Listo! 🎉
