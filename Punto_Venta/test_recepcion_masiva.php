<?php

require_once 'vendor/autoload.php';

// Script de prueba para la funcionalidad de recepción masiva
// Este script simula la preparación de datos para el modal

use App\Models\UnidadMedida;
use App\Models\Bodega;

echo "=== PRUEBA DE RECEPCIÓN MASIVA ===\n\n";

// Simular productos con cantidad pendiente
$detallesCompra = [
    [
        'id' => 1,
        'producto_id' => 101,
        'nombre_producto' => 'Papel Bond A4',
        'cantidad_sin_asignar' => 10.0,
        'unidad_medida' => 'Resma',
        'unidad_medida_id' => 1,
        'fecha_expiracion' => '2025-12-31'
    ],
    [
        'id' => 2,
        'producto_id' => 102,
        'nombre_producto' => 'Tinta Negra HP',
        'cantidad_sin_asignar' => 5.0,
        'unidad_medida' => 'Unidad',
        'unidad_medida_id' => 2,
        'fecha_expiracion' => null
    ],
    [
        'id' => 3,
        'producto_id' => 103,
        'nombre_producto' => 'Clips Metálicos',
        'cantidad_sin_asignar' => 20.0,
        'unidad_medida' => 'Caja',
        'unidad_medida_id' => 3,
        'fecha_expiracion' => null
    ]
];

echo "PRODUCTOS PREPARADOS PARA RECEPCIÓN MASIVA:\n";
echo "==========================================\n";

$productosRecepcionMasiva = [];

foreach ($detallesCompra as $detalle) {
    if ($detalle['cantidad_sin_asignar'] > 0) {
        
        // Simular unidades disponibles para el producto
        $unidadesProducto = [
            ['id' => $detalle['unidad_medida_id'], 'nombre' => $detalle['unidad_medida'], 'simbolo' => substr($detalle['unidad_medida'], 0, 3)],
            ['id' => 99, 'nombre' => 'Unidad', 'simbolo' => 'Un'],
            ['id' => 98, 'nombre' => 'Pieza', 'simbolo' => 'Pz']
        ];

        $producto = [
            'id' => $detalle['id'],
            'producto_id' => $detalle['producto_id'],
            'nombre_producto' => $detalle['nombre_producto'],
            'cantidad_pendiente' => $detalle['cantidad_sin_asignar'],
            'cantidad_distribuir' => $detalle['cantidad_sin_asignar'], // Por defecto toda la cantidad
            'unidad_medida_compra' => $detalle['unidad_medida'],
            'unidad_medida_id' => $detalle['unidad_medida_id'],
            'cantidad_stock' => $detalle['cantidad_sin_asignar'], // Por defecto la misma cantidad
            'unidades_disponibles' => $unidadesProducto,
            'fecha_expiracion' => $detalle['fecha_expiracion']
        ];

        $productosRecepcionMasiva[] = $producto;

        echo "📦 Producto: {$producto['nombre_producto']}\n";
        echo "   - ID: {$producto['producto_id']}\n";
        echo "   - Cantidad pendiente: {$producto['cantidad_pendiente']} {$producto['unidad_medida_compra']}\n";
        echo "   - Cantidad a distribuir: {$producto['cantidad_distribuir']}\n";
        echo "   - Cantidad para stock: {$producto['cantidad_stock']}\n";
        echo "   - Unidades disponibles: " . count($producto['unidades_disponibles']) . "\n";
        echo "   - Fecha expiración: " . ($producto['fecha_expiracion'] ?: 'N/A') . "\n";
        echo "\n";
    }
}

echo "RESUMEN:\n";
echo "========\n";
echo "Total productos para recepción: " . count($productosRecepcionMasiva) . "\n";
echo "Cantidad total a distribuir: " . array_sum(array_column($productosRecepcionMasiva, 'cantidad_distribuir')) . "\n";

// Simular configuración por defecto
echo "\nCONFIGURACIÓN POR DEFECTO:\n";
echo "=========================\n";
echo "Fecha de recepción: " . date('Y-m-d') . "\n";
echo "Bodega: Paperland (ID: 1)\n";
echo "Segmento: Almacén Principal (ID: 1)\n";
echo "Sección: Área A (ID: 1)\n";

echo "\n✅ Preparación de datos completada exitosamente!\n";
echo "✅ Modal de recepción masiva listo para mostrar\n";
echo "✅ Formulario con " . count($productosRecepcionMasiva) . " productos configurado\n";

?>