<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Compra;
use App\Models\CompraHasProducto;
use App\Models\Producto;
use App\Models\RecibidoBodega;
use App\Models\Bodega;
use App\Models\Segmento;
use App\Models\Seccion;
use App\Models\Bitacora;
use App\Services\WebInventorySyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DistribuirCompraExcel extends Command
{
    protected $signature = 'compra:distribuir-excel 
                            {archivo : Ruta del archivo Excel con las distribuciones} 
                            {--compra_id= : ID de la compra a distribuir}
                            {--usuario_id=1 : ID del usuario que realiza la distribución}
                            {--fecha= : Fecha de recepción (YYYY-MM-DD, por defecto hoy)}';

    protected $description = 'Distribuye masivamente productos de una compra usando un archivo Excel';

    public function handle()
    {
        $archivoPath = $this->argument('archivo');
        $compraId = $this->option('compra_id');
        $usuarioId = $this->option('usuario_id');
        $fechaRecepcion = $this->option('fecha') ?? now()->format('Y-m-d');

        // Validar que el archivo existe
        if (!file_exists($archivoPath)) {
            $this->error("El archivo no existe: {$archivoPath}");
            return 1;
        }

        // Validar que se proporcionó compra_id
        if (!$compraId) {
            $this->error("Debe proporcionar --compra_id");
            return 1;
        }

        // Validar que la compra existe
        $compra = Compra::find($compraId);
        if (!$compra) {
            $this->error("La compra con ID {$compraId} no existe");
            return 1;
        }

        $this->info("Leyendo archivo Excel...");

        try {
            // Leer el archivo Excel
            $spreadsheet = IOFactory::load($archivoPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // La primera fila son los encabezados
            $headers = array_shift($rows);
            
            $this->info("Columnas encontradas: " . implode(', ', $headers));
            $this->info("Total de filas: " . count($rows));

            // Mapear índices de columnas (case-insensitive)
            $colMap = [];
            foreach ($headers as $index => $header) {
                $colMap[strtolower(trim($header))] = $index;
            }

            // Validar columnas requeridas
            $columnasRequeridas = ['producto_id', 'cantidad_distribuir', 'cantidad_stock', 'bodega_id', 'segmento_id', 'seccion_id', 'unidad_medida_id'];
            foreach ($columnasRequeridas as $columna) {
                if (!isset($colMap[$columna])) {
                    $this->error("Falta la columna requerida: {$columna}");
                    return 1;
                }
            }

            DB::beginTransaction();

            $productosDistribuidos = 0;
            $productosParaWebhook = []; // Para enviar webhooks después del commit
            $errores = [];

            // Procesar cada fila
            foreach ($rows as $rowIndex => $row) {
                $lineaNum = $rowIndex + 2; // +2 porque Excel empieza en 1 y quitamos header

                try {
                    $productoId = $row[$colMap['producto_id']] ?? null;
                    $cantidadDistribuir = $row[$colMap['cantidad_distribuir']] ?? null;
                    $cantidadStock = $row[$colMap['cantidad_stock']] ?? null;
                    $bodegaId = $row[$colMap['bodega_id']] ?? null;
                    $segmentoId = $row[$colMap['segmento_id']] ?? null;
                    $seccionId = $row[$colMap['seccion_id']] ?? null;
                    $unidadMedidaId = $row[$colMap['unidad_medida_id']] ?? null;
                    $fechaExpiracion = isset($colMap['fecha_expiracion']) ? $row[$colMap['fecha_expiracion']] : null;
                    $comentario = isset($colMap['comentario']) ? $row[$colMap['comentario']] : '';

                    // Validaciones básicas
                    if (empty($productoId)) {
                        $errores[] = "Línea {$lineaNum}: producto_id vacío";
                        continue;
                    }

                    if (empty($cantidadDistribuir) || $cantidadDistribuir <= 0) {
                        $errores[] = "Línea {$lineaNum}: cantidad_distribuir inválida";
                        continue;
                    }

                    if (empty($cantidadStock) || $cantidadStock <= 0) {
                        $errores[] = "Línea {$lineaNum}: cantidad_stock inválida";
                        continue;
                    }

                    if (empty($bodegaId)) {
                        $errores[] = "Línea {$lineaNum}: bodega_id vacío";
                        continue;
                    }

                    if (empty($segmentoId)) {
                        $errores[] = "Línea {$lineaNum}: segmento_id vacío";
                        continue;
                    }

                    if (empty($seccionId)) {
                        $errores[] = "Línea {$lineaNum}: seccion_id vacío";
                        continue;
                    }

                    if (empty($unidadMedidaId)) {
                        $errores[] = "Línea {$lineaNum}: unidad_medida_id vacío";
                        continue;
                    }

                    // Verificar que el producto existe
                    $producto = Producto::find($productoId);
                    if (!$producto) {
                        $errores[] = "Línea {$lineaNum}: Producto ID {$productoId} no existe";
                        continue;
                    }

                    // Verificar que la bodega existe
                    $bodega = Bodega::find($bodegaId);
                    if (!$bodega) {
                        $errores[] = "Línea {$lineaNum}: Bodega ID {$bodegaId} no existe";
                        continue;
                    }

                    // Verificar que el segmento existe
                    $segmento = Segmento::find($segmentoId);
                    if (!$segmento) {
                        $errores[] = "Línea {$lineaNum}: Segmento ID {$segmentoId} no existe";
                        continue;
                    }

                    // Verificar que la sección existe
                    $seccion = Seccion::find($seccionId);
                    if (!$seccion) {
                        $errores[] = "Línea {$lineaNum}: Sección ID {$seccionId} no existe";
                        continue;
                    }

                    // Buscar el detalle de compra
                    $detalleCompra = CompraHasProducto::where('compra_id', $compraId)
                        ->where('producto_id', $productoId)
                        ->first();

                    if (!$detalleCompra) {
                        $errores[] = "Línea {$lineaNum}: Producto ID {$productoId} no está en la compra {$compraId}";
                        continue;
                    }

                    // Verificar que hay cantidad disponible
                    if ($detalleCompra->cantidad_sin_asignar < $cantidadDistribuir) {
                        $errores[] = "Línea {$lineaNum}: Solo hay {$detalleCompra->cantidad_sin_asignar} unidades disponibles de {$producto->nombre}";
                        continue;
                    }

                    // Actualizar la unidad de medida de venta del producto si es diferente
                    if ($producto->unidad_medida_venta_id != $unidadMedidaId) {
                        $unidadAnterior = $producto->unidad_medida_venta_id;
                        $producto->unidad_medida_venta_id = $unidadMedidaId;
                        $producto->save();
                        
                        $this->info("Línea {$lineaNum}: Actualizada unidad de medida de venta para {$producto->nombre}");
                    }

                    // Crear registro en recibido_bodega
                    $recibidoBodega = RecibidoBodega::create([
                        'producto_id' => $productoId,
                        'seccion_id' => $seccionId,
                        'cantidad_compra_lote' => $cantidadDistribuir,
                        'cantidad_inicial_seccion' => $cantidadStock,
                        'cantidad_disponible' => $cantidadStock,
                        'fecha_recibido' => $fechaRecepcion,
                        'fecha_expiracion' => $fechaExpiracion ? \Carbon\Carbon::parse($fechaExpiracion) : null,
                        'comentario' => $comentario,
                        'unidades_compra' => $cantidadDistribuir,
                        'unidad_medida_id' => $unidadMedidaId,
                        'users_registro_id' => $usuarioId,
                        'estado_id' => 1
                    ]);

                    // Actualizar cantidad sin asignar en compra_has_producto
                    $detalleCompra->cantidad_sin_asignar -= $cantidadDistribuir;
                    $detalleCompra->save();

                    $this->info("Línea {$lineaNum}: ✓ {$producto->nombre} distribuido - {$cantidadDistribuir} unidades a {$bodega->nombre}/{$segmento->descripcion}/{$seccion->nombre}");
                    $productosDistribuidos++;

                    // Guardar datos para webhook
                    $productosParaWebhook[] = [
                        'producto_id' => $productoId,
                        'nombre' => $producto->nombre,
                        'cantidad' => $cantidadStock,
                        'seccion_id' => $seccionId,
                        'fecha_recibido' => $fechaRecepcion,
                        'comentario' => $comentario,
                    ];

                } catch (\Exception $e) {
                    $errores[] = "Línea {$lineaNum}: Error - " . $e->getMessage();
                }
            }

            DB::commit();

            // Sincronizar con pagina web via webhooks
            if (count($productosParaWebhook) > 0) {
                $this->info("\n📡 Enviando webhooks de sincronizacion...");
                try {
                    $syncService = app(WebInventorySyncService::class);
                    foreach ($productosParaWebhook as $prod) {
                        $syncService->sincronizarCompraRecibida(
                            $prod['producto_id'],
                            $prod['nombre'],
                            (int) $prod['cantidad'],
                            [
                                'fecha_recibido' => $prod['fecha_recibido'],
                                'compra_id' => $compraId,
                                'numero_factura' => $compra->numero_factura,
                                'seccion_id' => $prod['seccion_id'],
                                'comentario' => $prod['comentario'],
                                'origen' => 'distribucion_excel',
                            ]
                        );
                    }
                    $this->info("✅ Webhooks enviados exitosamente");
                } catch (\Exception $e) {
                    $this->warn("⚠️  Error al enviar webhooks: " . $e->getMessage());
                }
            }

            // Mostrar resumen
            $this->info("\n=== RESUMEN DE DISTRIBUCIÓN ===");
            $this->info("Productos distribuidos exitosamente: {$productosDistribuidos}");

            if (count($errores) > 0) {
                $this->warn("\nErrores encontrados:");
                foreach ($errores as $error) {
                    $this->warn("  - {$error}");
                }
            }

            $this->info("\n¡Distribución completada!");
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error al procesar distribución: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
