<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrarPreciosATablaVenta extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'productos:migrar-precios-venta 
                            {--force : Forzar migración incluso si ya existen precios}
                            {--producto-id= : Migrar solo un producto específico}
                            {--dry-run : Simular la migración sin guardar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra masivamente los precios base de todos los productos a la tabla precio_has_venta';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('========================================');
        $this->info('MIGRACIÓN MASIVA DE PRECIOS A TABLA VENTA');
        $this->info('========================================');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $productoId = $this->option('producto-id');

        if ($dryRun) {
            $this->warn('⚠️  MODO SIMULACIÓN - No se guardarán cambios');
            $this->newLine();
        }

        try {
            // Construir query base
            $query = DB::table('producto')
                ->where('estado_id', 1)
                ->whereNotNull('precio_base')
                ->where('precio_base', '>', 0);

            // Filtrar por producto específico si se proporciona
            if ($productoId) {
                $query->where('id', $productoId);
                $this->info("🎯 Migrando solo producto ID: {$productoId}");
            } else {
                $this->info("🎯 Migrando todos los productos activos");
            }

            $productos = $query->get();

            if ($productos->isEmpty()) {
                $this->warn('⚠️  No se encontraron productos para migrar');
                return Command::SUCCESS;
            }

            $this->info("📦 Productos encontrados: {$productos->count()}");
            $this->newLine();

            // Obtener la unidad de medida base (ID 1 = Unidad)
            $unidadBase = DB::table('unidad_medida')
                ->where('id', 1)
                ->first();

            if (!$unidadBase) {
                $this->error('❌ No se encontró la unidad de medida base (ID 1)');
                return Command::FAILURE;
            }

            $this->info("📏 Unidad de medida base: {$unidadBase->nombre} (ID: {$unidadBase->id})");
            $this->newLine();

            // Contadores
            $procesados = 0;
            $insertados = 0;
            $omitidos = 0;
            $errores = 0;

            // Barra de progreso
            $bar = $this->output->createProgressBar($productos->count());
            $bar->start();

            foreach ($productos as $producto) {
                $procesados++;

                try {
                    // Verificar si ya existe un precio_has_venta para este producto
                    $precioExistente = DB::table('precio_has_venta')
                        ->where('producto_id', $producto->id)
                        ->where('estado_id', 1)
                        ->exists();

                    if ($precioExistente && !$force) {
                        $omitidos++;
                        $bar->advance();
                        continue;
                    }

                    if (!$dryRun) {
                        // Si existe y se usa --force, desactivar precios existentes
                        if ($precioExistente && $force) {
                            DB::table('precio_has_venta')
                                ->where('producto_id', $producto->id)
                                ->update(['estado_id' => 2]);
                        }

                        // Insertar el nuevo precio en precio_has_venta
                        $insertId = DB::table('precio_has_venta')->insertGetId([
                            'producto_id' => $producto->id,
                            'unidad_medida_id' => $unidadBase->id,
                            'cantidad' => 1, // 1 unidad base
                            'precio' => $producto->precio_base,
                            'users_id' => 1, // Usuario sistema
                            'estado_id' => 1, // Activo
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        Log::info('Precio migrado', [
                            'producto_id' => $producto->id,
                            'nombre' => $producto->nombre,
                            'precio_base' => $producto->precio_base,
                            'precio_has_venta_id' => $insertId
                        ]);
                    }

                    $insertados++;
                } catch (\Exception $e) {
                    $errores++;
                    Log::error('Error al migrar precio', [
                        'producto_id' => $producto->id,
                        'error' => $e->getMessage()
                    ]);
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Resumen
            $this->info('========================================');
            $this->info('RESUMEN DE MIGRACIÓN');
            $this->info('========================================');
            $this->info("✅ Productos procesados: {$procesados}");
            
            if ($dryRun) {
                $this->info("🔍 Productos que serían insertados: {$insertados}");
            } else {
                $this->info("✅ Precios insertados: {$insertados}");
            }
            
            $this->info("⏭️  Productos omitidos (ya tienen precio): {$omitidos}");
            
            if ($errores > 0) {
                $this->error("❌ Errores encontrados: {$errores}");
            }
            
            $this->newLine();

            if ($dryRun) {
                $this->warn('⚠️  Esta fue una simulación. Para ejecutar la migración real, elimina la opción --dry-run');
            } else {
                $this->info('✅ Migración completada exitosamente');
            }

            // Sugerencias
            if ($omitidos > 0 && !$force) {
                $this->newLine();
                $this->comment("💡 Tip: Usa --force para sobrescribir productos que ya tienen precios");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error fatal durante la migración');
            $this->error($e->getMessage());
            Log::error('Error fatal en migración de precios', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
