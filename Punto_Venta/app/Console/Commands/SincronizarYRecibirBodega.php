<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Compra;
use App\Models\CompraHasProducto;
use App\Models\RecibidoBodega;
use App\Models\IdZenvyValencia;
use App\Models\Producto;
use App\Services\WebInventorySyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SincronizarYRecibirBodega extends Command
{
    protected $signature = 'sincronizar:compras-y-bodega
                        {--seccion-id=433 : ID de la sección en Valencia}
                        {--dry-run : Simular sin hacer cambios}
                        {--test-connection : Solo probar conexión}
                        {--skip-valencia : Procesar solo datos locales existentes}
                        {--help-extended : Mostrar ayuda extendida}';
    protected $description = 'Sincroniza compras desde Valencia y procesa recepción a bodega (ID 1, segmento 12, sección 2)';

    private $conexionValencia;
    private $conexionZenvy;
    private $isDryRun;

    public function handle()
    {
        $this->conexionZenvy = DB::connection();
        $this->isDryRun = $this->option('dry-run');
        $seccionId = $this->option('seccion-id');

        $this->info('🚀 SINCRONIZACIÓN DE COMPRAS Y RECEPCIÓN A BODEGA');
        $this->info('================================================');

        if ($this->isDryRun) {
            $this->warn('⚠️  MODO DRY-RUN: No se realizarán cambios en la base de datos');
        }

        // Mostrar ayuda extendida si se solicita
        if ($this->option('help-extended')) {
            return $this->mostrarAyudaExtendida();
        }

        // Probar conexión si se solicita
        if ($this->option('test-connection')) {
            return $this->probarConexion();
        }

        $this->info("📊 Procesando sección ID: {$seccionId}");

        try {
            // Verificar conexión Valencia (si no se salta)
            if (!$this->option('skip-valencia')) {
                $this->conexionValencia = $this->verificarConexionValencia();
                if (!$this->conexionValencia) {
                    $this->error('❌ No se puede conectar a Valencia. Use --skip-valencia para procesar solo datos locales');
                    return Command::FAILURE;
                }
            }

            // Paso 1: Obtener datos desde Valencia o locales
            $this->info("\n🔍 Paso 1: Obteniendo datos...");

            if ($this->option('skip-valencia')) {
                $this->info('📋 Procesando datos existentes en Zenvy...');
                $datosValencia = $this->obtenerDatosLocales();
            } else {
                $this->info('🌐 Obteniendo datos desde Valencia...');
                $datosValencia = $this->obtenerDatosDesdeValencia($seccionId);
            }

            if (empty($datosValencia)) {
                $this->warn('❌ No se encontraron datos para procesar');
                return Command::FAILURE;
            }

            $this->info("✅ Se encontraron " . count($datosValencia) . " registros para procesar");

            // Paso 2: Procesar y sincronizar datos
            $this->info("\n⚙️  Paso 2: Procesando sincronización...");
            $estadisticas = $this->procesarSincronizacion($datosValencia);

            // Paso 3: Mostrar resultados
            $this->mostrarEstadisticas($estadisticas);

            if (!$this->isDryRun) {
                $this->info("\n✅ PROCESO COMPLETADO EXITOSAMENTE");
            } else {
                $this->info("\n✅ SIMULACIÓN COMPLETADA - Usar sin --dry-run para ejecutar");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("\n❌ ERROR EN EL PROCESO: " . $e->getMessage());
            Log::error('Error en sincronizar:compras-y-bodega: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Mostrar ayuda extendida del comando
     */
    private function mostrarAyudaExtendida()
    {
        $this->info('📋 AYUDA EXTENDIDA - SINCRONIZAR COMPRAS Y BODEGA');
        $this->info('===============================================');
        $this->info('');
        $this->info('Este comando realiza la sincronización de compras desde Valencia');
        $this->info('y procesa automáticamente la recepción a bodega en Zenvy.');
        $this->info('');
        $this->info('🔧 OPCIONES DISPONIBLES:');
        $this->info('');
        $this->info('  --seccion-id=433    ID de la sección en Valencia (default: 433)');
        $this->info('  --dry-run           Simular proceso sin hacer cambios reales');
        $this->info('  --test-connection   Solo probar la conexión a Valencia');
        $this->info('  --skip-valencia     Usar datos locales simulados (para testing)');
        $this->info('  --help-extended     Mostrar esta ayuda detallada');
        $this->info('');
        $this->info('📊 EJEMPLOS DE USO:');
        $this->info('');
        $this->info('  # Probar conexión a Valencia:');
        $this->info('  php artisan sincronizar:compras-y-bodega --test-connection');
        $this->info('');
        $this->info('  # Simular proceso completo:');
        $this->info('  php artisan sincronizar:compras-y-bodega --dry-run');
        $this->info('');
        $this->info('  # Ejecutar con datos de otra sección:');
        $this->info('  php artisan sincronizar:compras-y-bodega --seccion-id=500');
        $this->info('');
        $this->info('  # Testing con datos locales:');
        $this->info('  php artisan sincronizar:compras-y-bodega --skip-valencia --dry-run');
        $this->info('');
        $this->info('  # Ejecutar proceso real (¡CUIDADO!):');
        $this->info('  php artisan sincronizar:compras-y-bodega');
        $this->info('');
        $this->info('⚙️  PROCESO QUE REALIZA:');
        $this->info('');
        $this->info('  1. Conecta a la base profac_app (Valencia)');
        $this->info('  2. Ejecuta consulta SQL para obtener datos de recibido_bodega');
        $this->info('  3. Crea compras en Zenvy si no existen');
        $this->info('  4. Sincroniza productos de las compras');
        $this->info('  5. Procesa recepción automática a:');
        $this->info('     • Bodega ID: 1');
        $this->info('     • Segmento ID: 12');
        $this->info('     • Sección ID: 2');
        $this->info('');
        $this->info('⚠️  REQUISITOS:');
        $this->info('');
        $this->info('  • Conexión activa a profac_app configurada');
        $this->info('  • Productos de Valencia ya sincronizados en Zenvy');
        $this->info('  • Permisos de escritura en base de datos Zenvy');
        $this->info('');

        return Command::SUCCESS;
    }

    /**
     * Probar conexión a Valencia
     */
    private function probarConexion()
    {
        $this->info('🔍 PROBANDO CONEXIÓN A VALENCIA...');

        try {
            $conexion = DB::connection('profac_app');
            $resultado = $conexion->select('SELECT 1 as test');

            if (!empty($resultado)) {
                $this->info('✅ Conexión a Valencia exitosa');
                return Command::SUCCESS;
            } else {
                $this->error('❌ Conexión fallida - No se obtuvieron datos');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error de conexión: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Verificar conexión a Valencia
     */
    private function verificarConexionValencia()
    {
        try {
            $conexion = DB::connection('profac_app');
            $conexion->select('SELECT 1');
            $this->info('✅ Conexión a Valencia establecida');
            return $conexion;
        } catch (\Exception $e) {
            $this->error('❌ No se puede conectar a Valencia: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener datos locales existentes para procesar (modo skip-valencia)
     */
    private function obtenerDatosLocales()
    {
        // Crear datos de ejemplo para testing o usar datos existentes de compras
        $this->info('📊 Simulando datos locales para testing...');

        $datosSimulados = [
            (object)[
                'numero_factura' => 'TEST-001',
                'fec_vecimiento' => null,
                'fecha_emision' => now()->subDays(1),
                'fecha_recepcion' => now(),
                'created_at' => now(),
                'updated_at' => null,
                'estado_id' => 1,
                'cliente_id' => 1,
                'precio' => 25.50,
                'cantidad_ingresada' => 10,
                'cantidad_sin_asignar' => 10,
                'fecha_expiracion' => now()->addMonths(6),
                'sub_total_producto' => 255.00,
                'isv' => 38.25,
                'precio_total' => 293.25,
                'producto_id_valencia' => 17, // Producto que sabemos existe
                'unidad_medida_id' => 1,
                'recibido_bodega_id' => 1,
                'tipo_origen' => 'COMPRA',
                'nombre_producto' => 'ALCOHOL ISOPROPILICO',
                'isv_porcentaje' => 15
            ]
        ];

        return $datosSimulados;
    }

    /**
     * Obtener datos desde Valencia usando la consulta proporcionada
     */
    private function obtenerDatosDesdeValencia($seccionId)
    {
        $sql = "
            SELECT
                -- llenado de Tabla Compra
                COALESCE(C.translado_id, A.compra_id) AS numero_factura,
                NULL AS fec_vecimiento,
                A.created_at AS fecha_emision,
                'fecha que se sincroniza' AS fecha_recepcion,
                NOW() AS created_at,
                NULL AS updated_at,
                3 AS estado_id, -- Estado 3 = Distribuido
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

                'El id_compra insertado en zenvy' AS compra_id,
                A.producto_id AS producto_id_valencia,
                IFNULL(A.unidad_compra_id, 1) AS unidad_medida_id,

                -- indicador de origen
                CASE WHEN C.translado_id IS NOT NULL THEN 'TRASLADO' ELSE 'COMPRA' END AS tipo_origen,

                -- datos del producto para verificación (agregados para el comando)
                P.nombre AS nombre_producto,
                IFNULL(P.isv, 0) AS isv_porcentaje,
                A.id AS recibido_bodega_id

            FROM recibido_bodega A
            LEFT JOIN log_translado C ON C.destino = A.id   -- si existe traslado, lo detectamos
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
            WHERE A.seccion_id = ? AND A.cantidad_disponible > 0 AND A.created_at < '2025-09-10'
            ORDER BY A.created_at DESC
        ";

        return $this->conexionValencia->select($sql, [$seccionId]);
    }

    /**
     * Procesar la sincronización de datos
     */
    private function procesarSincronizacion($datosValencia)
    {
        $estadisticas = [
            'compras_procesadas' => 0,
            'productos_sincronizados' => 0,
            'recibidos_bodega' => 0,
            'errores' => 0,
            'productos_no_encontrados' => 0
        ];

        $comprasCreadas = [];
        $progressBar = $this->output->createProgressBar(count($datosValencia));
        $progressBar->start();

        foreach ($datosValencia as $registro) {
            try {
                // Verificar si el producto existe en Zenvy
                $productoZenvy = $this->obtenerProductoZenvy($registro->producto_id_valencia);

                if (!$productoZenvy) {
                    $this->warn("\n⚠️  Producto Valencia ID {$registro->producto_id_valencia} no encontrado en Zenvy: {$registro->nombre_producto}");
                    $estadisticas['productos_no_encontrados']++;
                    $progressBar->advance();
                    continue;
                }

                // Procesar compra (agrupar por numero_factura)
                $compraId = $this->procesarCompra($registro, $comprasCreadas);

                if ($compraId) {
                    // Procesar producto de la compra
                    $this->procesarProductoCompra($compraId, $registro, $productoZenvy->id);
                    $estadisticas['productos_sincronizados']++;

                    // Procesar recepción a bodega
                    $this->procesarRecepcionBodega($registro, $productoZenvy->id, $compraId);
                    $estadisticas['recibidos_bodega']++;
                }

            } catch (\Exception $e) {
                $this->error("\n❌ Error procesando registro: " . $e->getMessage());
                $estadisticas['errores']++;
                Log::error('Error procesando registro de sincronización', [
                    'registro' => $registro,
                    'error' => $e->getMessage()
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $estadisticas['compras_procesadas'] = count($comprasCreadas);

        return $estadisticas;
    }

    /**
     * Obtener producto de Zenvy por ID de Valencia
     */
    private function obtenerProductoZenvy($productoIdValencia)
    {
        // Buscar en tabla de mapeo
        $mapeo = IdZenvyValencia::where('id_valencia', $productoIdValencia)
            ->where('tipo_dato_migrado_id', 1) // 1 = productos
            ->first();

        if ($mapeo) {
            return $this->conexionZenvy->table('producto')
                ->where('id', $mapeo->id_zenvy)
                ->where('estado_id', 1)
                ->first();
        }

        return null;
    }

    /**
     * Procesar compra (crear si no existe)
     */
    private function procesarCompra($registro, &$comprasCreadas)
    {
        $numeroFactura = $registro->numero_factura;

        // Si ya procesamos esta compra, retornar el ID
        if (isset($comprasCreadas[$numeroFactura])) {
            return $comprasCreadas[$numeroFactura];
        }

        // Verificar si ya existe la compra en Zenvy
        $compraExistente = $this->conexionZenvy->table('compra')
            ->where('numero_factura', $numeroFactura)
            ->first();

        if ($compraExistente) {
            $comprasCreadas[$numeroFactura] = $compraExistente->id;
            return $compraExistente->id;
        }

        // Crear nueva compra (sin fec_vecimiento que no existe en la tabla)
        if (!$this->isDryRun) {
            $compraId = $this->conexionZenvy->table('compra')->insertGetId([
                'numero_factura' => $numeroFactura,
                'fecha_emision' => $registro->fecha_emision,
                'fecha_recepcion' => now(), // Usar fecha actual en lugar del string
                'created_at' => $registro->created_at,
                'updated_at' => $registro->updated_at,
                'estado_id' => 3, // Estado 3 = Distribuido
                'cliente_id' => $registro->cliente_id
            ]);

            $comprasCreadas[$numeroFactura] = $compraId;
            return $compraId;
        } else {
            // En modo dry-run, simular ID
            $compraId = 99999 + count($comprasCreadas);
            $comprasCreadas[$numeroFactura] = $compraId;
            return $compraId;
        }
    }

    /**
     * Procesar producto de compra
     */
    private function procesarProductoCompra($compraId, $registro, $productoZenvyId)
    {
        if (!$this->isDryRun) {
            $this->conexionZenvy->table('compra_has_producto')->insert([
                'precio' => $registro->precio,
                'cantidad_ingresada' => $registro->cantidad_ingresada,
                'cantidad_sin_asignar' => 0, // Todos los productos deben estar asignados
                'fecha_expiracion' => $registro->fecha_expiracion,
                'sub_total_producto' => $registro->sub_total_producto,
                'isv' => $registro->isv,
                'precio_total' => $registro->precio_total,
                'compra_id' => $compraId,
                'producto_id' => $productoZenvyId,
                'unidad_medida_id' => $registro->unidad_medida_id
            ]);
        }
    }

    /**
     * Procesar recepción a bodega
     */
    private function procesarRecepcionBodega($registro, $productoZenvyId, $compraId)
    {
        if (!$this->isDryRun) {
            $this->conexionZenvy->table('recibido_bodega')->insert([
                'producto_id' => $productoZenvyId,
                'seccion_id' => 2, // Sección ID 2 como solicitado
                'cantidad_compra_lote' => $registro->cantidad_ingresada,
                'cantidad_inicial_seccion' => $registro->cantidad_ingresada,
                'cantidad_disponible' => $registro->cantidad_ingresada, // Cantidad disponible = cantidad_comprada_lote
                'fecha_recibido' => now()->format('Y-m-d'),
                'fecha_expiracion' => $registro->fecha_expiracion,
                'comentario' => "Sincronización automática desde Valencia - Compra ID: {$compraId}",
                'unidad_medida_id' => $registro->unidad_medida_id,
                'users_registro_id' => 1, // Usuario sistema
                'estado_id' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Sincronizar con página web vía webhook
            try {
                $producto = Producto::find($productoZenvyId);
                if ($producto) {
                    $syncService = app(WebInventorySyncService::class);
                    $syncService->sincronizarCompraRecibida(
                        $productoZenvyId,
                        $producto->nombre,
                        (int) $registro->cantidad_ingresada,
                        [
                            'fecha_recibido' => now()->format('Y-m-d'),
                            'compra_id' => $compraId,
                            'seccion_id' => 2,
                            'origen' => 'sincronizacion_valencia',
                            'comentario' => "Sincronización automática desde Valencia - Compra ID: {$compraId}",
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::warning('Error al enviar webhook de compra recibida (sincronización Valencia)', [
                    'producto_id' => $productoZenvyId,
                    'compra_id' => $compraId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Mostrar estadísticas del proceso
     */
    private function mostrarEstadisticas($estadisticas)
    {
        $this->info("\n📊 ESTADÍSTICAS DEL PROCESO:");
        $this->info("============================");
        $this->table(
            ['Concepto', 'Cantidad'],
            [
                ['Compras procesadas', $estadisticas['compras_procesadas']],
                ['Productos sincronizados', $estadisticas['productos_sincronizados']],
                ['Productos recibidos en bodega', $estadisticas['recibidos_bodega']],
                ['Productos no encontrados', $estadisticas['productos_no_encontrados']],
                ['Errores', $estadisticas['errores']]
            ]
        );

        if ($estadisticas['errores'] > 0) {
            $this->warn("⚠️  Se encontraron {$estadisticas['errores']} errores. Revise los logs para más detalles.");
        }

        if ($estadisticas['productos_no_encontrados'] > 0) {
            $this->warn("⚠️  {$estadisticas['productos_no_encontrados']} productos de Valencia no se encontraron en Zenvy.");
            $this->info("💡 Considere ejecutar primero la sincronización de productos.");
        }
    }
}
