<?php
/**
 * Página de ejemplo para mostrar productos sincronizados
 */

// Conexión a base de datos
$mysqli = new mysqli('localhost', 'root', '', 'tu_base_datos');

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");

// Obtener categorías con productos
$sql = "SELECT c.id, c.nombre, 
        COUNT(p.id) as total_productos,
        SUM(p.stock_disponible) as stock_total
        FROM categorias c
        LEFT JOIN productos p ON c.id = p.categoria_id AND p.estado = 'activo'
        GROUP BY c.id, c.nombre
        HAVING total_productos > 0
        ORDER BY c.nombre";

$categorias = $mysqli->query($sql);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Sincronizados desde Zenvy</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 30px; }
        .categoria { background: white; margin-bottom: 30px; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .categoria h2 { color: #2563eb; margin-bottom: 10px; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
        .stats { color: #666; font-size: 14px; margin-bottom: 20px; }
        .productos { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
        .producto { border: 1px solid #e5e5e5; padding: 15px; border-radius: 4px; background: #fafafa; }
        .producto-nombre { font-weight: bold; color: #333; margin-bottom: 5px; }
        .producto-codigo { color: #666; font-size: 12px; margin-bottom: 10px; }
        .producto-info { display: flex; justify-content: space-between; align-items: center; }
        .producto-stock { color: #16a34a; font-weight: bold; }
        .producto-precio { color: #dc2626; font-weight: bold; font-size: 18px; }
        .sin-stock { color: #ef4444; }
        .sync-info { background: #dbeafe; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; color: #1e40af; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛍️ Productos Sincronizados desde Zenvy POS</h1>
        
        <div class="sync-info">
            ✅ Inventario actualizado en tiempo real desde Zenvy POS
        </div>
        
        <?php while ($cat = $categorias->fetch_assoc()): ?>
            <div class="categoria">
                <h2>📦 <?php echo htmlspecialchars($cat['nombre']); ?></h2>
                <div class="stats">
                    Total productos: <?php echo $cat['total_productos']; ?> | 
                    Stock total: <?php echo $cat['stock_total']; ?> unidades
                </div>
                
                <div class="productos">
                    <?php
                    // Obtener productos de la categoría
                    $catId = $cat['id'];
                    $sqlProductos = "SELECT * FROM productos 
                                    WHERE categoria_id = $catId AND estado = 'activo'
                                    ORDER BY nombre";
                    $productos = $mysqli->query($sqlProductos);
                    
                    while ($prod = $productos->fetch_assoc()):
                    ?>
                        <div class="producto">
                            <div class="producto-nombre">
                                <?php echo htmlspecialchars($prod['nombre']); ?>
                            </div>
                            <div class="producto-codigo">
                                📊 <?php echo htmlspecialchars($prod['codigo_barra']); ?>
                            </div>
                            <div class="producto-info">
                                <span class="producto-stock <?php echo $prod['stock_disponible'] <= 0 ? 'sin-stock' : ''; ?>">
                                    Stock: <?php echo $prod['stock_disponible']; ?>
                                </span>
                                <span class="producto-precio">
                                    $<?php echo number_format($prod['precio_venta'], 2); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>
<?php $mysqli->close(); ?>
