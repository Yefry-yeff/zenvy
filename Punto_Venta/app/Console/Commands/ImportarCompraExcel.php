<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Compra;
use App\Models\CompraHasProducto;
use App\Models\Producto;
use App\Models\UnidadMedida;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportarCompraExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compra:importar-excel 
                            {archivo : Ruta del archivo Excel} 
                            {--cliente_id= : ID del cliente/proveedor}
                            {--usuario_id= : ID del usuario que registra}
                            {--bodega_id= : ID de la bodega (opcional)}
                            {--crear-compra : Crear una nueva compra en lugar de agregar a una existente}
                            {--compra_id= : ID de la compra existente (si no se crea nueva)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa productos de un archivo Excel y los agrega a una compra';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $archivoPath = $this->argument('archivo');
        $clienteId = $this->option('cliente_id');
        $usuarioId = $this->option('usuario_id');
        $bodegaId = $this->option('bodega_id');
        $crearCompra = $this->option('crear-compra');
        $compraId = $this->option('compra_id');

        // Validar que el archivo existe
        if (!file_exists($archivoPath)) {
            $this->error("El archivo no existe: {$archivoPath}");
            return 1;
        }

        // Si no se va a crear compra, debe proporcionar compra_id
        if (!$crearCompra && !$compraId) {
            $this->error("Debe proporcionar --compra_id o usar --crear-compra");
            return 1;
        }

        // Si va a crear compra, necesita cliente_id y usuario_id
        if ($crearCompra && (!$clienteId || !$usuarioId)) {
            $this->error("Para crear una compra nueva, debe proporcionar --cliente_id y --usuario_id");
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

            // Mapear índices de columnas
            $colMap = [];
            foreach ($headers as $index => $header) {
                $colMap[strtolower(trim($header))] = $index;
            }

            DB::beginTransaction();

            // Crear o usar compra existente
            if ($crearCompra) {
                $numeroFactura = 'Migracion1234';
                $fechaEmision = now();
                
                // Si hay datos en la primera fila, intentar usar esos valores
                if (isset($colMap['numero_factura']) && !empty($rows[0][$colMap['numero_factura']])) {
                    $numeroFactura = $rows[0][$colMap['numero_factura']];
                }
                
                if (isset($colMap['fecha_compra']) && !empty($rows[0][$colMap['fecha_compra']])) {
                    try {
                        $fechaEmision = \Carbon\Carbon::parse($rows[0][$colMap['fecha_compra']]);
                    } catch (\Exception $e) {
                        // Si no se puede parsear, usar fecha actual
                        $fechaEmision = now();
                    }
                }
                
                $compra = new Compra();
                $compra->cliente_id = $clienteId;
                $compra->numero_factura = $numeroFactura;
                $compra->fecha_emision = $fechaEmision;
                $compra->fecha_recepcion = now();
                $compra->estado_id = 1; // Estado por defecto
                $compra->save();

                $this->info("Compra creada con ID: {$compra->id}");
            } else {
                $compra = Compra::findOrFail($compraId);
                $this->info("Usando compra existente ID: {$compra->id}");
            }

            $productosImportados = 0;
            $errores = [];

            // Procesar cada fila
            foreach ($rows as $rowIndex => $row) {
                $lineaNum = $rowIndex + 2; // +2 porque Excel empieza en 1 y quitamos header

                try {
                    $productoId = $row[$colMap['producto_id']] ?? null;
                    $cantidad = $row[$colMap['cantidad']] ?? null;
                    $precioUnitario = $row[$colMap['precio unitario']] ?? null;
                    $unidadMedidaId = $row[$colMap['unidad_medida_id']] ?? null;

                    // Validaciones básicas
                    if (empty($productoId)) {
                        $errores[] = "Línea {$lineaNum}: producto_id vacío";
                        continue;
                    }

                    if (empty($cantidad) || $cantidad <= 0) {
                        $errores[] = "Línea {$lineaNum}: cantidad inválida";
                        continue;
                    }

                    if (empty($precioUnitario) || $precioUnitario <= 0) {
                        $errores[] = "Línea {$lineaNum}: precio unitario inválido";
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

                    // Verificar que la unidad de medida existe
                    $unidadMedida = UnidadMedida::find($unidadMedidaId);
                    if (!$unidadMedida) {
                        $errores[] = "Línea {$lineaNum}: Unidad de medida ID {$unidadMedidaId} no existe";
                        continue;
                    }

                    // Verificar si ya existe este producto en la compra
                    $detalleExistente = CompraHasProducto::where('compra_id', $compra->id)
                        ->where('producto_id', $productoId)
                        ->where('unidad_medida_id', $unidadMedidaId)
                        ->first();

                    if ($detalleExistente) {
                        // Actualizar cantidad y precio
                        $detalleExistente->cantidad_ingresada += $cantidad;
                        $detalleExistente->cantidad_sin_asignar += $cantidad;
                        $detalleExistente->precio = $precioUnitario;
                        $detalleExistente->sub_total_producto = $detalleExistente->cantidad_ingresada * $precioUnitario;
                        $detalleExistente->precio_total = $detalleExistente->sub_total_producto;
                        $detalleExistente->save();
                        
                        $this->info("Línea {$lineaNum}: Actualizado producto {$producto->nombre} (cantidad acumulada: {$detalleExistente->cantidad_ingresada})");
                    } else {
                        // Crear nuevo detalle
                        $detalle = new CompraHasProducto();
                        $detalle->compra_id = $compra->id;
                        $detalle->producto_id = $productoId;
                        $detalle->cantidad_ingresada = $cantidad;
                        $detalle->cantidad_sin_asignar = $cantidad;
                        $detalle->precio = $precioUnitario;
                        $detalle->sub_total_producto = $cantidad * $precioUnitario;
                        $detalle->isv = 0;
                        $detalle->precio_total = $detalle->sub_total_producto;
                        $detalle->unidad_medida_id = $unidadMedidaId;
                        $detalle->save();

                        $this->info("Línea {$lineaNum}: Importado {$producto->nombre} - Cantidad: {$cantidad}");
                    }

                    $productosImportados++;

                } catch (\Exception $e) {
                    $errores[] = "Línea {$lineaNum}: Error - " . $e->getMessage();
                }
            }

            DB::commit();

            // Mostrar resumen
            $this->info("\n=== RESUMEN DE IMPORTACIÓN ===");
            $this->info("Productos importados exitosamente: {$productosImportados}");

            if (count($errores) > 0) {
                $this->warn("\nErrores encontrados:");
                foreach ($errores as $error) {
                    $this->warn("  - {$error}");
                }
            }

            $this->info("\n¡Importación completada!");
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error al importar: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
