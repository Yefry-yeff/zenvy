<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\IdZenvyValencia;
use App\Models\ProductoValenciaZenvy;

class MigrarProductosANuevaTabla extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'productos:migrar-a-nueva-tabla 
                            {--dry-run : Ejecutar en modo de prueba sin insertar datos}
                            {--force : Forzar migración incluso si ya existen datos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra productos existentes de id_zenvy_valencia a producto_valencia_zenvy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('=================================================');
        $this->info('  Migración de Productos a Tabla Nueva');
        $this->info('=================================================');
        
        if ($dryRun) {
            $this->warn('⚠️  MODO DRY-RUN: No se insertarán datos reales');
        }

        // Verificar si ya existen datos
        $existentes = ProductoValenciaZenvy::count();
        if ($existentes > 0 && !$force) {
            $this->error("❌ Ya existen {$existentes} productos en la tabla producto_valencia_zenvy");
            $this->info("💡 Usa --force para migrar de todas formas (omitiendo duplicados)");
            return 1;
        }

        if ($existentes > 0) {
            $this->warn("⚠️  Tabla ya tiene {$existentes} registros. Continuando con --force...");
        }

        // Obtener productos sincronizados desde id_zenvy_valencia
        $this->info('📊 Contando productos a migrar...');
        
        $productosSincronizados = IdZenvyValencia::where('tipo_dato_migrado_id', 1)
            ->with(['productoZenvy' => function($query) {
                // No es necesario eager loading aquí
            }])
            ->get();

        $total = $productosSincronizados->count();
        $this->info("✅ Encontrados {$total} productos sincronizados en id_zenvy_valencia");

        if ($total === 0) {
            $this->warn('⚠️  No hay productos para migrar');
            return 0;
        }

        if ($dryRun) {
            $this->table(
                ['ID Valencia', 'ID Zenvy', 'Código Estatal', 'Código Barra'],
                $productosSincronizados->take(5)->map(function($item) {
                    $producto = DB::table('producto')->where('id', $item->id_zenvy)->first();
                    $codigoBarra = DB::table('precio_has_venta')
                        ->where('producto_id', $item->id_zenvy)
                        ->where('estado_id', 1)
                        ->value('codigo_barra');
                    
                    return [
                        $item->id_valencia,
                        $item->id_zenvy,
                        $producto->codigo_estatal ?? 'N/A',
                        $codigoBarra ?? 'N/A'
                    ];
                })->toArray()
            );
            $this->info("... y " . ($total - 5) . " productos más");
            return 0;
        }

        // Confirmar migración
        if (!$this->confirm("¿Deseas migrar {$total} productos?", true)) {
            $this->info('❌ Migración cancelada');
            return 0;
        }

        // Ejecutar migración
        $this->info('🚀 Iniciando migración...');
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $migrados = 0;
        $omitidos = 0;
        $errores = 0;

        foreach ($productosSincronizados as $sync) {
            try {
                // Verificar si ya existe
                $existe = ProductoValenciaZenvy::where('producto_id_valencia', $sync->id_valencia)->exists();
                
                if ($existe) {
                    $omitidos++;
                    $bar->advance();
                    continue;
                }

                // Obtener datos del producto
                $producto = DB::table('producto')->where('id', $sync->id_zenvy)->first();
                
                if (!$producto) {
                    $this->newLine();
                    $this->error("❌ Producto Zenvy ID {$sync->id_zenvy} no encontrado");
                    $errores++;
                    $bar->advance();
                    continue;
                }

                // Obtener código de barras
                $codigoBarra = DB::table('precio_has_venta')
                    ->where('producto_id', $sync->id_zenvy)
                    ->where('estado_id', 1)
                    ->value('codigo_barra');

                // Crear registro
                ProductoValenciaZenvy::create([
                    'producto_id_zenvy' => $sync->id_zenvy,
                    'producto_id_valencia' => $sync->id_valencia,
                    'codigo_producto_valencia' => $producto->codigo_estatal,
                    'codigo_barra' => $codigoBarra,
                    'sincronizado' => true,
                    'ultima_sincronizacion' => now(),
                    'created_at' => $sync->created_at,
                    'updated_at' => $sync->updated_at,
                ]);

                $migrados++;
                
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Error migrando producto Valencia ID {$sync->id_valencia}: {$e->getMessage()}");
                $errores++;
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Resumen
        $this->info('=================================================');
        $this->info('  RESUMEN DE MIGRACIÓN');
        $this->info('=================================================');
        $this->info("✅ Migrados exitosamente: {$migrados}");
        
        if ($omitidos > 0) {
            $this->warn("⚠️  Omitidos (ya existían): {$omitidos}");
        }
        
        if ($errores > 0) {
            $this->error("❌ Errores: {$errores}");
        }

        // Verificación final
        $totalFinal = ProductoValenciaZenvy::count();
        $this->info("📊 Total en producto_valencia_zenvy: {$totalFinal}");
        
        $this->info('=================================================');
        $this->info('✅ Migración completada');
        
        return 0;
    }
}
