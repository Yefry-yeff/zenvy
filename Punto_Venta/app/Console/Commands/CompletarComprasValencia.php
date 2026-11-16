<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Compra;
use App\Models\CompraHasProducto;
use App\Models\IdZenvyValencia;
use App\Models\Estado;
use App\Services\SincronizacionProductosService;
use Illuminate\Support\Facades\DB;

class CompletarComprasValencia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compras:completar-valencia 
                            {fecha_inicio : Fecha de inicio (YYYY-MM-DD)}
                            {fecha_fin : Fecha de fin (YYYY-MM-DD)}
                            {--dry-run : Simular sin hacer cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Completa compras sincronizadas de Valencia agregando productos faltantes en un rango de fechas';

    private $conexionValencia;
    private $conexionZenvy;
    private $sincronizacionProductos;

    public function __construct()
    {
        parent::__construct();
        $this->conexionValencia = DB::connection('profac_app');
        $this->conexionZenvy = DB::connection();
        $this->sincronizacionProductos = new SincronizacionProductosService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fechaInicio = $this->argument('fecha_inicio');
        $fechaFin = $this->argument('fecha_fin');
        $dryRun = $this->option('dry-run');

        // Validar fechas
        if (!$this->validarFecha($fechaInicio) || !$this->validarFecha($fechaFin)) {
            $this->error('Formato de fecha inválido. Use YYYY-MM-DD');
            return 1;
        }

        if ($fechaInicio > $fechaFin) {
            $this->error('La fecha de inicio debe ser anterior a la fecha de fin');
            return 1;
        }

        $this->info("================================================");
        $this->info("COMPLETAR COMPRAS DE VALENCIA");
        $this->info("================================================");
        $this->info("Rango: {$fechaInicio} hasta {$fechaFin}");
        $this->info("Modo: " . ($dryRun ? 'SIMULACIÓN (no se harán cambios)' : 'PRODUCCIÓN'));
        $this->info("================================================\n");

        // Obtener compras de Valencia en el rango de fechas
        $comprasValencia = $this->obtenerComprasValenciaRango($fechaInicio, $fechaFin);

        if (empty($comprasValencia)) {
            $this->warn('No se encontraron compras de Valencia en el rango especificado');
            return 0;
        }

        // Agrupar por número de factura
        $comprasAgrupadas = collect($comprasValencia)->groupBy('numero_factura');
        $this->info("Total de compras encontradas: " . $comprasAgrupadas->count() . "\n");

        // Obtener ID del estado "anulado" para excluir compras anuladas
        $estadoAnulado = Estado::whereRaw('LOWER(descripcion) = ?', ['anulado'])->first();
        $estadoAnuladoId = $estadoAnulado ? $estadoAnulado->id : null;
        
        // Obtener ID del estado "pendiente" para actualizar compras al agregar productos
        $estadoPendiente = Estado::whereRaw('LOWER(descripcion) = ?', ['pendiente'])->first();
        $estadoPendienteId = $estadoPendiente ? $estadoPendiente->id : null;
        
        if ($estadoAnuladoId) {
            $this->info("ℹ️  Se excluirán compras con estado 'Anulado'\n");
        }
        
        if ($estadoPendienteId) {
            $this->info("ℹ️  Las compras modificadas cambiarán a estado 'Pendiente'\n");
        }

        $estadisticas = [
            'compras_procesadas' => 0,
            'compras_completadas' => 0,
            'compras_anuladas_excluidas' => 0,
            'compras_estado_actualizado' => 0,
            'productos_agregados' => 0,
            'productos_migrados' => 0,
            'productos_fallidos' => 0,
            'errores' => []
        ];

        $progressBar = $this->output->createProgressBar($comprasAgrupadas->count());
        $progressBar->start();

        foreach ($comprasAgrupadas as $numeroFactura => $productosValencia) {
            try {
                // Buscar la compra en Zenvy
                $compraZenvy = Compra::where('numero_factura', $numeroFactura)->first();

                if (!$compraZenvy) {
                    $this->newLine();
                    $this->warn("  ⚠ Compra {$numeroFactura} no existe en Zenvy, omitiendo...");
                    $progressBar->advance();
                    continue;
                }

                // Excluir compras anuladas
                if ($estadoAnuladoId && $compraZenvy->estado_id == $estadoAnuladoId) {
                    $estadisticas['compras_anuladas_excluidas']++;
                    $progressBar->advance();
                    continue;
                }

                // Obtener productos actuales de la compra en Zenvy
                $productosActualesZenvy = CompraHasProducto::where('compra_id', $compraZenvy->id)
                    ->pluck('producto_id')
                    ->toArray();

                $productosFaltantes = [];
                $productosParaAgregar = [];

                // Verificar qué productos de Valencia faltan en Zenvy
                foreach ($productosValencia as $productoValencia) {
                    $idProductoZenvy = $this->obtenerIdProductoZenvy($productoValencia->producto_id_valencia);

                    // Si el producto no existe en Zenvy, intentar migrarlo
                    if (!$idProductoZenvy) {
                        $this->newLine();
                        $this->line("  🔄 Migrando producto Valencia ID {$productoValencia->producto_id_valencia}...");
                        
                        try {
                            $resultado = $this->sincronizacionProductos->sincronizarProducto($productoValencia->producto_id_valencia);
                            
                            if ($resultado['success']) {
                                $idProductoZenvy = $resultado['id_zenvy'];
                                $estadisticas['productos_migrados']++;
                                $this->line("  ✓ Producto migrado: Zenvy ID {$idProductoZenvy}");
                            } else {
                                $productosFaltantes[] = $productoValencia->producto_id_valencia;
                                $estadisticas['productos_fallidos']++;
                                $this->error("  ✗ Error al migrar: {$resultado['mensaje']}");
                                continue;
                            }
                        } catch (\Exception $e) {
                            $productosFaltantes[] = $productoValencia->producto_id_valencia;
                            $estadisticas['productos_fallidos']++;
                            $this->error("  ✗ Excepción al migrar: {$e->getMessage()}");
                            continue;
                        }
                    }

                    // Verificar si el producto ya existe en la compra
                    if (!in_array($idProductoZenvy, $productosActualesZenvy)) {
                        $productosParaAgregar[] = [
                            'datos_producto' => $productoValencia,
                            'id_zenvy' => $idProductoZenvy
                        ];
                    }
                }

                // Agregar productos faltantes
                if (!empty($productosParaAgregar)) {
                    $this->newLine();
                    $this->info("  📦 Compra {$numeroFactura}: Agregando {count: " . count($productosParaAgregar) . " productos...");

                    $productosAgregadosExitosos = 0;
                    foreach ($productosParaAgregar as $productoData) {
                        if (!$dryRun) {
                            $resultado = $this->agregarProductoACompra(
                                $compraZenvy->id,
                                $productoData['datos_producto'],
                                $productoData['id_zenvy']
                            );
                            
                            if ($resultado) {
                                $productosAgregadosExitosos++;
                                $estadisticas['productos_agregados']++;
                            } else {
                                $this->warn("    ⚠ Producto Valencia ID {$productoData['datos_producto']->producto_id_valencia} omitido (ver bitácora)");
                            }
                        } else {
                            $estadisticas['productos_agregados']++;
                        }
                    }

                    if (!$dryRun && $productosAgregadosExitosos > 0) {
                        $estadisticas['compras_completadas']++;
                        
                        // Actualizar estado a Pendiente si se agregaron productos
                        if ($estadoPendienteId && $compraZenvy->estado_id != $estadoPendienteId) {
                            $estadoAnterior = $compraZenvy->estado_id;
                            $compraZenvy->estado_id = $estadoPendienteId;
                            $compraZenvy->save();
                            
                            // Registrar cambio de estado en bitácora
                            DB::table('bitacora')->insert([
                                'tablaReferencia' => 'compra',
                                'accion' => 'ACTUALIZAR_ESTADO_PENDIENTE',
                                'idReferencia' => $compraZenvy->id,
                                'datosAnteriores' => json_encode(['estado_id' => $estadoAnterior]),
                                'datosNuevos' => json_encode(['estado_id' => $estadoPendienteId, 'razon' => 'productos_agregados_completar_compras']),
                                'users_id' => 1,
                                'created_at' => now()
                            ]);
                            
                            $estadisticas['compras_estado_actualizado']++;
                            $this->line("  ✓ Completada ({$productosAgregadosExitosos} productos agregados, estado → Pendiente)");
                        } else {
                            $this->line("  ✓ Completada ({$productosAgregadosExitosos} productos agregados)");
                        }
                    } elseif (!$dryRun) {
                        $this->warn("  ⚠ No se agregaron productos (ver errores en bitácora)");
                    } else {
                        $estadisticas['compras_completadas']++;
                        $this->line("  ✓ Completada");
                    }
                }

                $estadisticas['compras_procesadas']++;

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("  ✗ Error en compra {$numeroFactura}: " . $e->getMessage());
                $estadisticas['errores'][] = [
                    'compra' => $numeroFactura,
                    'error' => $e->getMessage()
                ];
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Mostrar estadísticas
        $this->info("================================================");
        $this->info("RESUMEN");
        $this->info("================================================");
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Compras procesadas', $estadisticas['compras_procesadas']],
                ['Compras completadas', $estadisticas['compras_completadas']],
                ['Compras anuladas excluidas', $estadisticas['compras_anuladas_excluidas']],
                ['Estados actualizados a Pendiente', $estadisticas['compras_estado_actualizado']],
                ['Productos agregados', $estadisticas['productos_agregados']],
                ['Productos migrados', $estadisticas['productos_migrados']],
                ['Productos fallidos', $estadisticas['productos_fallidos']],
                ['Errores', count($estadisticas['errores'])]
            ]
        );

        if (!empty($estadisticas['errores'])) {
            $this->newLine();
            $this->error("Errores encontrados:");
            foreach ($estadisticas['errores'] as $error) {
                $this->line("  • Compra {$error['compra']}: {$error['error']}");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠ MODO SIMULACIÓN: No se realizaron cambios en la base de datos');
        }

        return 0;
    }

    /**
     * Validar formato de fecha
     */
    private function validarFecha($fecha)
    {
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }

    /**
     * Obtener compras de Valencia en un rango de fechas
     */
    private function obtenerComprasValenciaRango($fechaInicio, $fechaFin)
    {
        $sql = "
            SELECT
                -- IDs de origen en Valencia para traceabilidad
                A.id AS recibido_bodega_id,
                A.compra_id AS compra_id_valencia,
                C.translado_id AS translado_id_valencia,

                -- llenado de Tabla Compra
                COALESCE(C.translado_id, A.compra_id) AS numero_factura,
                NULL AS fec_vecimiento,
                A.created_at AS fecha_emision,
                'fecha que se sincroniza' AS fecha_recepcion,
                NOW() AS created_at,
                NULL AS updated_at,
                1 AS estado_id,
                1 AS cliente_id,
                -- llenado de la tabla compra_has_producto
                -- precio: si es traslado usa último de compra_has_producto, si no usa el de la compra
                COALESCE(chp.precio_unidad, B.precio_unidad) AS precio,
                A.cantidad_inicial_seccion AS cantidad_ingresada,
                A.cantidad_disponible AS cantidad_sin_asignar,
                A.fecha_expiracion AS fecha_expiracion,

                -- subtotal, isv y total calculados en base al precio real
                (COALESCE(chp.precio_unidad, B.precio_unidad) * A.cantidad_inicial_seccion) AS sub_total_producto,
                ((COALESCE(chp.precio_unidad, B.precio_unidad) * A.cantidad_inicial_seccion) * (P.isv / 100.0)) AS isv,
                ((COALESCE(chp.precio_unidad, B.precio_unidad) * A.cantidad_inicial_seccion) +
                 ((COALESCE(chp.precio_unidad, B.precio_unidad) * A.cantidad_inicial_seccion) * (P.isv / 100.0))) AS precio_total,

                A.compra_id AS compra_id,
                A.producto_id AS producto_id_valencia,
                IFNULL(A.unidad_compra_id, 1) AS unidad_medida_id,

                -- indicador de origen
                CASE WHEN C.translado_id IS NOT NULL THEN 'TRASLADO' ELSE 'COMPRA' END AS tipo_origen

            FROM recibido_bodega A
            LEFT JOIN log_translado C ON C.destino = A.id
            LEFT JOIN producto P ON P.id = A.producto_id
            LEFT JOIN compra_has_producto B ON B.compra_id = A.compra_id AND B.producto_id = A.producto_id
            LEFT JOIN (
                SELECT producto_id, precio_unidad
                FROM compra_has_producto chp1
                WHERE created_at = (
                    SELECT MAX(created_at)
                    FROM compra_has_producto chp2
                    WHERE chp2.producto_id = chp1.producto_id
                )
            ) chp ON chp.producto_id = A.producto_id
            WHERE A.seccion_id = 433
              AND DATE(A.created_at) BETWEEN ? AND ?
            ORDER BY A.created_at ASC
        ";

        return $this->conexionValencia->select($sql, [$fechaInicio, $fechaFin]);
    }

    /**
     * Obtener ID de producto en Zenvy
     */
    private function obtenerIdProductoZenvy($idProductoValencia)
    {
        $mapeo = IdZenvyValencia::where('id_valencia', $idProductoValencia)
            ->where('tipo_dato_migrado_id', IdZenvyValencia::TIPO_PRODUCTO)
            ->first();

        return $mapeo ? $mapeo->id_zenvy : null;
    }

    /**
     * Obtener ID de unidad de medida en Zenvy
     */
    private function obtenerIdUnidadMedidaZenvy($idUnidadValencia)
    {
        $mapeo = IdZenvyValencia::where('id_valencia', $idUnidadValencia)
            ->where('tipo_dato_migrado_id', IdZenvyValencia::TIPO_UNIDAD_MEDIDA)
            ->first();

        return $mapeo ? $mapeo->id_zenvy : null;
    }

    /**
     * Agregar producto a compra
     */
    private function agregarProductoACompra($compraId, $datosProducto, $idProductoZenvy)
    {
        try {
            // Obtener ID de unidad de medida en Zenvy
            $idUnidadMedidaZenvy = $this->obtenerIdUnidadMedidaZenvy($datosProducto->unidad_medida_id);

            if (!$idUnidadMedidaZenvy) {
                // Registrar error en bitácora y retornar false
                DB::table('bitacora')->insert([
                    'tablaReferencia' => 'completar_compras_valencia',
                    'accion' => 'ERROR_UNIDAD_MEDIDA_NO_ENCONTRADA',
                    'idReferencia' => $compraId,
                    'datosAnteriores' => null,
                    'datosNuevos' => json_encode([
                        'compra_id' => $compraId,
                        'producto_id_valencia' => $datosProducto->producto_id_valencia ?? null,
                        'producto_id_zenvy' => $idProductoZenvy,
                        'unidad_medida_valencia_id' => $datosProducto->unidad_medida_id,
                        'error' => "No se encontró mapeo para unidad de medida Valencia ID: {$datosProducto->unidad_medida_id}"
                    ]),
                    'users_id' => 1,
                    'created_at' => now()
                ]);
                
                return false;
            }

            // Validar precio
            if (!isset($datosProducto->precio) || $datosProducto->precio === null || $datosProducto->precio === '') {
                // Registrar error en bitácora y retornar false
                DB::table('bitacora')->insert([
                    'tablaReferencia' => 'completar_compras_valencia',
                    'accion' => 'ERROR_PRECIO_NULO',
                    'idReferencia' => $compraId,
                    'datosAnteriores' => null,
                    'datosNuevos' => json_encode([
                        'compra_id' => $compraId,
                        'producto_id_valencia' => $datosProducto->producto_id_valencia ?? null,
                        'producto_id_zenvy' => $idProductoZenvy,
                        'error' => "Precio nulo o vacío para producto Valencia ID {$datosProducto->producto_id_valencia}"
                    ]),
                    'users_id' => 1,
                    'created_at' => now()
                ]);
                
                return false;
            }

            // Crear registro en compra_has_producto
            CompraHasProducto::create([
                'compra_id' => $compraId,
                'producto_id' => $idProductoZenvy,
                'precio' => $datosProducto->precio,
                'cantidad_ingresada' => $datosProducto->cantidad_ingresada,
                'cantidad_sin_asignar' => $datosProducto->cantidad_sin_asignar,
                'fecha_expiracion' => $datosProducto->fecha_expiracion,
                'sub_total_producto' => $datosProducto->sub_total_producto,
                'isv' => $datosProducto->isv,
                'precio_total' => $datosProducto->precio_total,
                'unidad_medida_id' => $idUnidadMedidaZenvy
            ]);

            return true;

        } catch (\Exception $e) {
            DB::table('bitacora')->insert([
                'tablaReferencia' => 'completar_compras_valencia',
                'accion' => 'ERROR_AGREGAR_PRODUCTO',
                'idReferencia' => $compraId,
                'datosAnteriores' => null,
                'datosNuevos' => json_encode([
                    'compra_id' => $compraId,
                    'producto_id_valencia' => $datosProducto->producto_id_valencia ?? null,
                    'producto_id_zenvy' => $idProductoZenvy,
                    'error' => $e->getMessage()
                ]),
                'users_id' => 1, // Sistema
                'created_at' => now()
            ]);

            return false;
        }
    }
}
