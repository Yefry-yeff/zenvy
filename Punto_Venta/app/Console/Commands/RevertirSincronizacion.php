<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Compra;
use App\Models\CompraHasProducto;
use App\Models\RecibidoBodega;
use App\Models\IdZenvyValencia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RevertirSincronizacion extends Command
{
    protected $signature = 'revertir:sincronizacion-compras
                        {--fecha-desde= : Fecha desde cuando revertir (YYYY-MM-DD)}
                        {--fecha-hasta= : Fecha hasta cuando revertir (YYYY-MM-DD, por defecto hoy)}
                        {--por-created-at : Usar created_at en lugar de fecha_emision para filtrar}
                        {--compra-id= : Revertir una compra específica por ID}
                        {--seccion-id=2 : ID de sección en bodega para revertir}
                        {--dry-run : Simular sin hacer cambios}
                        {--force : Forzar eliminación sin confirmación}
                        {--help-extended : Mostrar ayuda extendida}';
    
    protected $description = 'Revierte la sincronización de compras y eliminación de recibido_bodega';

    private $conexionZenvy;
    private $isDryRun;

    public function handle()
    {
        $this->conexionZenvy = DB::connection();
        $this->isDryRun = $this->option('dry-run');

        $this->info('🔄 REVERTIR SINCRONIZACIÓN DE COMPRAS Y BODEGA');
        $this->info('=============================================');

        if ($this->isDryRun) {
            $this->warn('⚠️  MODO DRY-RUN: No se realizarán cambios en la base de datos');
        }

        // Mostrar ayuda extendida si se solicita
        if ($this->option('help-extended')) {
            return $this->mostrarAyudaExtendida();
        }

        try {
            // Obtener parámetros
            $fechaDesde = $this->option('fecha-desde');
            $fechaHasta = $this->option('fecha-hasta') ?? now()->format('Y-m-d');
            $compraId = $this->option('compra-id');
            $seccionId = $this->option('seccion-id');
            $force = $this->option('force');
            $porCreatedAt = $this->option('por-created-at');

            // Si no se especifica fecha-desde, usar fecha de hoy
            if (!$fechaDesde && !$compraId) {
                $fechaDesde = now()->format('Y-m-d');
                $this->info("📅 Sin fecha especificada, usando fecha de hoy: {$fechaDesde}");
            }

            // Paso 1: Identificar registros a revertir
            $this->info("\n🔍 Paso 1: Identificando registros a revertir...");
            $estadisticas = $this->identificarRegistros($fechaDesde, $fechaHasta, $compraId, $seccionId, $porCreatedAt);

            if ($estadisticas['total'] === 0) {
                $this->warn('⚠️  No se encontraron registros para revertir.');
                return 0;
            }

            // Mostrar estadísticas
            $this->mostrarResumenReversion($estadisticas);

            // Solicitar confirmación
            if (!$force && !$this->isDryRun) {
                if (!$this->confirm('¿Está seguro de que desea revertir estos registros?')) {
                    $this->info('❌ Operación cancelada por el usuario.');
                    return 0;
                }
            }

            // Paso 2: Ejecutar reversión
            $this->info("\n⚙️  Paso 2: Ejecutando reversión...");
            $resultados = $this->ejecutarReversion($estadisticas);

            // Mostrar resultados finales
            $this->mostrarResultadosFinales($resultados);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error durante la reversión: ' . $e->getMessage());
            Log::error('Error en reversión de sincronización', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Identificar registros a revertir
     */
    private function identificarRegistros($fechaDesde, $fechaHasta, $compraId, $seccionId, $porCreatedAt = false)
    {
        $estadisticas = [
            'compras' => [],
            'productos_compra' => 0,
            'productos_bodega' => 0,
            'total' => 0
        ];

        // Construir query base
        $queryCompras = $this->conexionZenvy->table('compra');

        if ($compraId) {
            // Revertir compra específica
            $queryCompras->where('id', $compraId);
            $this->info("🎯 Filtrando por compra ID: {$compraId}");
        } else {
            // Revertir por rango de fechas
            $campoFecha = $porCreatedAt ? 'created_at' : 'fecha_emision';
            $queryCompras->whereBetween($campoFecha, [$fechaDesde, $fechaHasta]);
            $this->info("📅 Filtrando compras entre: {$fechaDesde} y {$fechaHasta} (usando {$campoFecha})");
        }

        // Obtener compras a revertir
        $compras = $queryCompras->get();
        $estadisticas['compras'] = $compras->toArray();

        if ($compras->count() > 0) {
            $compraIds = $compras->pluck('id')->toArray();
            
            // Contar productos en compra_has_producto
            $estadisticas['productos_compra'] = $this->conexionZenvy->table('compra_has_producto')
                ->whereIn('compra_id', $compraIds)
                ->count();

            // Contar productos en recibido_bodega
            $queryBodega = $this->conexionZenvy->table('recibido_bodega')
                ->where('seccion_id', $seccionId);
            
            if ($fechaDesde && $fechaHasta && !$compraId) {
                $queryBodega->whereBetween('fecha_recibido', [$fechaDesde, $fechaHasta]);
            }
            
            $estadisticas['productos_bodega'] = $queryBodega->count();
        }

        $estadisticas['total'] = count($estadisticas['compras']) + $estadisticas['productos_compra'] + $estadisticas['productos_bodega'];

        return $estadisticas;
    }

    /**
     * Ejecutar la reversión
     */
    private function ejecutarReversion($estadisticas)
    {
        $resultados = [
            'compras_eliminadas' => 0,
            'productos_compra_eliminados' => 0,
            'productos_bodega_eliminados' => 0,
            'errores' => 0
        ];

        if (!$this->isDryRun) {
            DB::beginTransaction();
        }

        try {
            $compraIds = collect($estadisticas['compras'])->pluck('id')->toArray();

            if (!empty($compraIds)) {
                // 1. Eliminar productos de compra_has_producto
                $this->info('🗑️  Eliminando productos de compras...');
                if (!$this->isDryRun) {
                    $eliminados = $this->conexionZenvy->table('compra_has_producto')
                        ->whereIn('compra_id', $compraIds)
                        ->delete();
                    $resultados['productos_compra_eliminados'] = $eliminados;
                } else {
                    $resultados['productos_compra_eliminados'] = $estadisticas['productos_compra'];
                }

                // 2. Eliminar compras
                $this->info('🗑️  Eliminando compras...');
                if (!$this->isDryRun) {
                    $eliminados = $this->conexionZenvy->table('compra')
                        ->whereIn('id', $compraIds)
                        ->delete();
                    $resultados['compras_eliminadas'] = $eliminados;
                } else {
                    $resultados['compras_eliminadas'] = count($estadisticas['compras']);
                }
            }

            // 3. Eliminar productos de recibido_bodega
            $seccionId = $this->option('seccion-id');
            $this->info('🗑️  Eliminando productos de bodega...');
            
            $queryBodega = $this->conexionZenvy->table('recibido_bodega')
                ->where('seccion_id', $seccionId);

            $fechaDesde = $this->option('fecha-desde');
            $fechaHasta = $this->option('fecha-hasta') ?? now()->format('Y-m-d');
            $compraId = $this->option('compra-id');

            if ($compraId) {
                // Si es compra específica, usar el comentario que incluye el ID
                $queryBodega->where('comentario', 'like', "%Compra ID: {$compraId}%");
            } else {
                // Si es por fechas
                if (!$fechaDesde) {
                    $fechaDesde = now()->format('Y-m-d');
                }
                $queryBodega->whereBetween('fecha_recibido', [$fechaDesde, $fechaHasta]);
            }

            if (!$this->isDryRun) {
                $eliminados = $queryBodega->delete();
                $resultados['productos_bodega_eliminados'] = $eliminados;
            } else {
                $resultados['productos_bodega_eliminados'] = $estadisticas['productos_bodega'];
            }

            if (!$this->isDryRun) {
                DB::commit();
                $this->info('✅ Transacción completada exitosamente');
            }

        } catch (\Exception $e) {
            if (!$this->isDryRun) {
                DB::rollback();
            }
            $resultados['errores']++;
            $this->error('❌ Error durante la reversión: ' . $e->getMessage());
            Log::error('Error ejecutando reversión', ['error' => $e->getMessage()]);
        }

        return $resultados;
    }

    /**
     * Mostrar resumen de lo que se va a revertir
     */
    private function mostrarResumenReversion($estadisticas)
    {
        $this->info("\n📋 RESUMEN DE REVERSIÓN:");
        $this->info("========================");
        
        $this->table(
            ['Concepto', 'Cantidad'],
            [
                ['Compras a eliminar', count($estadisticas['compras'])],
                ['Productos de compras a eliminar', $estadisticas['productos_compra']],
                ['Productos de bodega a eliminar', $estadisticas['productos_bodega']],
                ['Total de registros', $estadisticas['total']]
            ]
        );

        if (count($estadisticas['compras']) > 0) {
            $this->info("\n🔍 Compras que se eliminarán:");
            foreach ($estadisticas['compras'] as $compra) {
                $compra = (object) $compra;
                $this->line("  • ID: {$compra->id} | Factura: {$compra->numero_factura} | Fecha: {$compra->fecha_emision}");
            }
        }
    }

    /**
     * Mostrar resultados finales
     */
    private function mostrarResultadosFinales($resultados)
    {
        $this->info("\n📊 RESULTADOS DE LA REVERSIÓN:");
        $this->info("==============================");
        
        $this->table(
            ['Concepto', 'Cantidad'],
            [
                ['Compras eliminadas', $resultados['compras_eliminadas']],
                ['Productos de compras eliminados', $resultados['productos_compra_eliminados']],
                ['Productos de bodega eliminados', $resultados['productos_bodega_eliminados']],
                ['Errores', $resultados['errores']]
            ]
        );

        if ($resultados['errores'] == 0) {
            $this->info('✅ REVERSIÓN COMPLETADA EXITOSAMENTE');
        } else {
            $this->warn("⚠️  REVERSIÓN COMPLETADA CON {$resultados['errores']} ERRORES");
        }

        if ($this->isDryRun) {
            $this->info("\n💡 Esto fue una simulación. Use sin --dry-run para ejecutar realmente.");
        }
    }

    /**
     * Mostrar ayuda extendida
     */
    private function mostrarAyudaExtendida()
    {
        $this->info('📖 AYUDA EXTENDIDA - REVERTIR SINCRONIZACIÓN');
        $this->info('============================================');
        $this->info('');
        $this->info('Este comando permite revertir sincronizaciones de compras realizadas.');
        $this->info('');
        $this->info('EJEMPLOS DE USO:');
        $this->info('');
        $this->info('1. Revertir sincronizaciones de hoy (simulación):');
        $this->info('   php artisan revertir:sincronizacion-compras --dry-run');
        $this->info('');
        $this->info('2. Revertir sincronizaciones de una fecha específica:');
        $this->info('   php artisan revertir:sincronizacion-compras --fecha-desde=2025-09-25');
        $this->info('');
        $this->info('3. Revertir sincronizaciones en un rango de fechas:');
        $this->info('   php artisan revertir:sincronizacion-compras --fecha-desde=2025-09-20 --fecha-hasta=2025-09-25');
        $this->info('');
        $this->info('4. Revertir una compra específica:');
        $this->info('   php artisan revertir:sincronizacion-compras --compra-id=123');
        $this->info('');
        $this->info('5. Revertir sin confirmación:');
        $this->info('   php artisan revertir:sincronizacion-compras --force');
        $this->info('');
        $this->info('6. Revertir de sección específica:');
        $this->info('   php artisan revertir:sincronizacion-compras --seccion-id=2');
        $this->info('');
        $this->info('OPCIONES:');
        $this->info('  --fecha-desde    Fecha desde (YYYY-MM-DD)');
        $this->info('  --fecha-hasta    Fecha hasta (YYYY-MM-DD, por defecto hoy)');
        $this->info('  --compra-id      ID de compra específica a revertir');
        $this->info('  --seccion-id     ID de sección en bodega (por defecto: 2)');
        $this->info('  --dry-run        Simular sin hacer cambios');
        $this->info('  --force          No pedir confirmación');
        $this->info('  --help-extended  Mostrar esta ayuda');
        $this->info('');
        $this->info('ADVERTENCIAS:');
        $this->info('⚠️  Esta operación es IRREVERSIBLE');
        $this->info('⚠️  Siempre use --dry-run primero para ver qué se eliminará');
        $this->info('⚠️  Se recomienda hacer backup antes de ejecutar');
        
        return 0;
    }
}