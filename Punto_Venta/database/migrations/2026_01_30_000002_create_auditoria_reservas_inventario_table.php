<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auditoria_reservas_inventario', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reserva_id')->comment('ID de la reserva');
            $table->unsignedBigInteger('pedido_web_id')->comment('ID del pedido web');
            $table->unsignedBigInteger('producto_id')->comment('ID del producto');
            $table->string('numero_pedido', 50)->comment('Número de pedido');
            $table->string('nombre_producto')->comment('Nombre del producto al momento del registro');
            $table->string('accion', 50)->comment('crear, liberar, consumir, actualizar');
            $table->enum('estado_anterior', ['activa', 'liberada', 'consumida'])->nullable();
            $table->enum('estado_nuevo', ['activa', 'liberada', 'consumida'])->nullable();
            $table->integer('cantidad_anterior')->nullable();
            $table->integer('cantidad_nueva')->nullable();
            $table->integer('stock_disponible_antes')->comment('Stock disponible antes de la acción');
            $table->integer('stock_disponible_despues')->comment('Stock disponible después de la acción');
            $table->unsignedBigInteger('usuario_id')->nullable()->comment('Usuario que ejecutó la acción');
            $table->string('usuario_nombre')->nullable()->comment('Nombre del usuario');
            $table->text('motivo')->nullable()->comment('Motivo/descripción de la acción');
            $table->json('metadata')->nullable()->comment('Datos adicionales del contexto');
            $table->string('ip', 45)->nullable();
            $table->timestamp('fecha_accion')->useCurrent();
            
            // Índices
            $table->index('reserva_id');
            $table->index('pedido_web_id');
            $table->index('producto_id');
            $table->index('accion');
            $table->index('fecha_accion');
            $table->index(['pedido_web_id', 'accion']);
            
            // Foreign key
            $table->foreign('reserva_id')->references('id')->on('reservas_inventario')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_reservas_inventario');
    }
};
