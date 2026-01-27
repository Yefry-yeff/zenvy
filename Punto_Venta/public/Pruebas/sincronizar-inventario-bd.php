<?php
/**
 * Script para sincronizar inventario de Zenvy con tu base de datos
 * 
 * USO:
 * 1. Ejecutar manualmente: php sincronizar-inventario-bd.php
 * 2. Configurar cron: */5 * * * * php /ruta/sincronizar-inventario-bd.php
 */

require_once __DIR__ . '/zenvy-api-client.php';

// ============================================
// CONFIGURACIÓN
// ============================================

// Credenciales Zenvy API
define('ZENVY_URL', 'http://127.0.0.1:8000');
define('ZENVY_EMAIL', 'admin@zenvy.local');
define('ZENVY_PASSWORD', 'password');

// Conexión a tu base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tu_base_datos');

// ============================================
// CONECTAR A BASE DE DATOS
// ============================================

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_error) {
    die("❌ Error de conexión: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");

echo "✅ Conectado a base de datos\n";

// ============================================
// CONECTAR A ZENVY API
// ============================================

$zenvy = new ZenvyApiClient(ZENVY_URL);
$token = $zenvy->authenticate(ZENVY_EMAIL, ZENVY_PASSWORD);

if (!$token) {
    die("❌ Error al autenticar con Zenvy API\n");
}

echo "✅ Autenticado en Zenvy API\n";

// ============================================
// OBTENER INVENTARIO DESDE ZENVY
// ============================================

$inventory = $zenvy->getInventoryByCategory();

if (!$inventory['success']) {
    die("❌ Error al obtener inventario: " . ($inventory['error'] ?? 'Unknown') . "\n");
}

echo "✅ Inventario obtenido de Zenvy\n";
echo "📊 Total categorías: " . count($inventory['data']) . "\n\n";

// ============================================
// SINCRONIZAR CON TU BASE DE DATOS
// ============================================

$totalProductos = 0;
$totalActualizados = 0;
$totalInsertados = 0;
$errores = 0;

// Iniciar transacción
$mysqli->begin_transaction();

try {
    foreach ($inventory['data'] as $categoria) {
        
        // 1. Insertar o actualizar categoría
        $categoriaNombre = $mysqli->real_escape_string($categoria['categoria_nombre']);
        $categoriaId = $categoria['categoria_id'];
        
        $sqlCategoria = "INSERT INTO categorias (id, nombre, actualizado_en) 
                        VALUES ($categoriaId, '$categoriaNombre', NOW())
                        ON DUPLICATE KEY UPDATE 
                        nombre = '$categoriaNombre',
                        actualizado_en = NOW()";
        
        $mysqli->query($sqlCategoria);
        
        echo "📁 Categoría: {$categoria['categoria_nombre']} ({$categoria['total_productos']} productos)\n";
        
        // 2. Sincronizar productos
        foreach ($categoria['productos'] as $producto) {
            $totalProductos++;
            
            $id = (int)$producto['id'];
            $nombre = $mysqli->real_escape_string($producto['nombre']);
            $codigoBarra = $mysqli->real_escape_string($producto['codigo_barra']);
            $codigoEstatal = $mysqli->real_escape_string($producto['codigo_estatal']);
            $stock = (int)$producto['stock'];
            $precio = (float)$producto['precio_venta'];
            
            // Verificar si existe
            $checkSql = "SELECT id FROM productos WHERE id = $id";
            $result = $mysqli->query($checkSql);
            
            if ($result->num_rows > 0) {
                // UPDATE - Producto existe
                $updateSql = "UPDATE productos SET 
                            nombre = '$nombre',
                            codigo_barra = '$codigoBarra',
                            codigo_estatal = '$codigoEstatal',
                            stock_disponible = $stock,
                            precio_venta = $precio,
                            categoria_id = $categoriaId,
                            actualizado_en = NOW()
                            WHERE id = $id";
                
                if ($mysqli->query($updateSql)) {
                    $totalActualizados++;
                    echo "   ✏️  Actualizado: $nombre (Stock: $stock)\n";
                } else {
                    $errores++;
                    echo "   ❌ Error UPDATE: " . $mysqli->error . "\n";
                }
                
            } else {
                // INSERT - Producto nuevo
                $insertSql = "INSERT INTO productos 
                            (id, nombre, codigo_barra, codigo_estatal, stock_disponible, precio_venta, categoria_id, creado_en, actualizado_en)
                            VALUES 
                            ($id, '$nombre', '$codigoBarra', '$codigoEstatal', $stock, $precio, $categoriaId, NOW(), NOW())";
                
                if ($mysqli->query($insertSql)) {
                    $totalInsertados++;
                    echo "   ➕ Insertado: $nombre (Stock: $stock)\n";
                } else {
                    $errores++;
                    echo "   ❌ Error INSERT: " . $mysqli->error . "\n";
                }
            }
        }
        
        echo "\n";
    }
    
    // Commit transacción
    $mysqli->commit();
    
    echo "=====================================\n";
    echo "✅ SINCRONIZACIÓN COMPLETADA\n";
    echo "=====================================\n";
    echo "Total productos procesados: $totalProductos\n";
    echo "Productos actualizados: $totalActualizados\n";
    echo "Productos insertados: $totalInsertados\n";
    echo "Errores: $errores\n";
    echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    // Rollback en caso de error
    $mysqli->rollback();
    echo "❌ Error en transacción: " . $e->getMessage() . "\n";
}

// Cerrar conexión
$mysqli->close();

?>
