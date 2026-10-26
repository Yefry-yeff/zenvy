<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bodega', function (Blueprint $table): void {
            $table->string('tipo_bodega', 30)->default('fisica')->after('principal');
            $table->boolean('cuenta_como_disponible')->default(true)->after('tipo_bodega');
            $table->string('sistema_externo', 30)->nullable()->after('cuenta_como_disponible');
            $table->index(['tipo_bodega', 'cuenta_como_disponible'], 'bodega_tipo_disponible_idx');
        });

        Schema::create('traslado', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('codigo', 40)->unique();
            $table->string('direccion', 30);
            $table->string('sistema_origen', 30);
            $table->string('referencia_externa', 100)->nullable();
            $table->integer('bodega_origen_id')->nullable();
            $table->integer('bodega_destino_id')->nullable();
            $table->string('estado', 30)->default('borrador');
            $table->dateTime('fecha_solicitud');
            $table->dateTime('fecha_despacho')->nullable();
            $table->dateTime('fecha_recepcion')->nullable();
            $table->unsignedBigInteger('usuario_creacion_id')->nullable();
            $table->unsignedBigInteger('usuario_despacho_id')->nullable();
            $table->unsignedBigInteger('usuario_recepcion_id')->nullable();
            $table->string('comentario', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['sistema_origen', 'referencia_externa'],
                'traslado_origen_referencia_unique'
            );
            $table->index(['direccion', 'estado'], 'traslado_direccion_estado_idx');
            $table->foreign('bodega_origen_id')->references('id')->on('bodega');
            $table->foreign('bodega_destino_id')->references('id')->on('bodega');
            $table->foreign('usuario_creacion_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('usuario_despacho_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('usuario_recepcion_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('traslado_detalle', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('traslado_id')->constrained('traslado')->cascadeOnDelete();
            $table->integer('producto_id');
            $table->unsignedBigInteger('producto_externo_id')->nullable();
            $table->integer('precio_venta_id')->nullable();
            $table->integer('unidad_medida_id');
            $table->unsignedInteger('cantidad_solicitada');
            $table->unsignedInteger('cantidad_enviada')->default(0);
            $table->unsignedInteger('cantidad_recibida')->default(0);
            $table->unsignedInteger('cantidad_rechazada')->default(0);
            $table->string('observacion', 255)->nullable();
            $table->timestamps();

            $table->unique(['traslado_id', 'producto_id', 'precio_venta_id'], 'traslado_detalle_presentacion_unique');
            $table->foreign('producto_id')->references('id')->on('producto');
            $table->foreign('precio_venta_id')->references('id')->on('precio_has_venta')->nullOnDelete();
            $table->foreign('unidad_medida_id')->references('id')->on('unidad_medida');
        });

        Schema::create('traslado_lote', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('traslado_detalle_id')->constrained('traslado_detalle')->cascadeOnDelete();
            $table->integer('recibido_bodega_id');
            $table->unsignedInteger('cantidad');
            $table->timestamps();

            $table->unique(['traslado_detalle_id', 'recibido_bodega_id'], 'traslado_lote_detalle_lote_unique');
            $table->foreign('recibido_bodega_id')->references('id')->on('recibido_bodega');
        });

        Schema::create('movimiento_inventario', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('traslado_id')->nullable()->constrained('traslado')->nullOnDelete();
            $table->foreignId('traslado_detalle_id')->nullable()->constrained('traslado_detalle')->nullOnDelete();
            $table->integer('recibido_bodega_id')->nullable();
            $table->integer('producto_id');
            $table->integer('bodega_id')->nullable();
            $table->string('tipo', 40);
            $table->integer('cantidad');
            $table->integer('saldo_anterior')->nullable();
            $table->integer('saldo_nuevo')->nullable();
            $table->string('referencia', 100)->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['producto_id', 'created_at'], 'movimiento_producto_fecha_idx');
            $table->index(['tipo', 'referencia'], 'movimiento_tipo_referencia_idx');
            $table->foreign('recibido_bodega_id')->references('id')->on('recibido_bodega')->nullOnDelete();
            $table->foreign('producto_id')->references('id')->on('producto');
            $table->foreign('bodega_id')->references('id')->on('bodega')->nullOnDelete();
            $table->foreign('usuario_id')->references('id')->on('users')->nullOnDelete();
        });

        $this->crearBodegasTransito();
        $this->crearMenuTraslados();
    }

    private function crearBodegasTransito(): void
    {
        $bodegasFisicas = DB::table('bodega')
            ->where('estado_id', 1)
            ->where('tipo_bodega', 'fisica')
            ->get();

        foreach ($bodegasFisicas as $bodegaFisica) {
            foreach ([
                ['nombre' => 'En tránsito desde Valencia', 'tipo' => 'transito_entrada'],
                ['nombre' => 'En tránsito hacia Valencia', 'tipo' => 'transito_salida'],
            ] as $configuracion) {
                $bodegaId = DB::table('bodega')->insertGetId([
                    'nombre' => $configuracion['nombre'],
                    'principal' => 0,
                    'tipo_bodega' => $configuracion['tipo'],
                    'cuenta_como_disponible' => 0,
                    'sistema_externo' => 'valencia',
                    'direccion_id' => $bodegaFisica->direccion_id,
                    'estado_id' => 1,
                    'tienda_id' => $bodegaFisica->tienda_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $segmentoId = DB::table('segmento')->insertGetId([
                    'descripcion' => 'Tránsito',
                    'bodega_id' => $bodegaId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('seccion')->insert([
                    'descripcion' => $configuracion['nombre'],
                    'numeracion' => 'TR',
                    'estado_id' => 1,
                    'segmento_id' => $segmentoId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function crearMenuTraslados(): void
    {
        $menuId = DB::table('menu')->insertGetId([
            'txt_comentario' => 'Envíos a Valencia',
            'parent_id' => 2,
            'route' => 'Inventario.Traslados',
            'orden' => 3,
            'icon' => '↔️',
            'estado_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roles = DB::table('roles')
            ->whereIn('txt_nombre', ['Administrador', 'Admin', 'Inventario', 'Supervisor'])
            ->pluck('id');

        foreach ($roles as $rolId) {
            DB::table('rol_permiso')->insert([
                'rol_id' => $rolId,
                'menu_id' => $menuId,
                'estado' => 1,
                'created_at' => now(),
                'created_user' => 1,
            ]);
        }
    }

    public function down(): void
    {
        $menuId = DB::table('menu')->where('route', 'Inventario.Traslados')->value('id');
        if ($menuId) {
            DB::table('rol_permiso')->where('menu_id', $menuId)->delete();
            DB::table('menu')->where('id', $menuId)->delete();
        }

        Schema::dropIfExists('movimiento_inventario');
        Schema::dropIfExists('traslado_lote');
        Schema::dropIfExists('traslado_detalle');
        Schema::dropIfExists('traslado');

        DB::table('seccion')
            ->whereIn('segmento_id', function ($query): void {
                $query->select('id')->from('segmento')->whereIn('bodega_id', function ($subQuery): void {
                    $subQuery->select('id')->from('bodega')->whereIn('tipo_bodega', ['transito_entrada', 'transito_salida']);
                });
            })
            ->delete();
        DB::table('segmento')
            ->whereIn('bodega_id', function ($query): void {
                $query->select('id')->from('bodega')->whereIn('tipo_bodega', ['transito_entrada', 'transito_salida']);
            })
            ->delete();
        DB::table('bodega')->whereIn('tipo_bodega', ['transito_entrada', 'transito_salida'])->delete();

        Schema::table('bodega', function (Blueprint $table): void {
            $table->dropIndex('bodega_tipo_disponible_idx');
            $table->dropColumn(['tipo_bodega', 'cuenta_como_disponible', 'sistema_externo']);
        });
    }
};
