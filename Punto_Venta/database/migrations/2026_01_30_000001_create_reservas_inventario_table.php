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
        Schema::create('reservas_inventario', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_web_id')->comment('ID del pedido web');
            $table->unsignedBigInteger('producto_id')->comment('ID del producto');
            $table->integer('cantidad_reservada')->comment('Cantidad reservada');
            $table->enum('estado', ['activa', 'liberada', 'consumida'])->default('activa')->comment('Estado de la reserva');
            $table->text('motivo_liberacion')->nullable()->comment('Razón por la cual se liberó');
            $table->timestamp('fecha_liberacion')->nullable();
            $table->timestamp('fecha_consumo')->nullable();
            $table->unsignedBigInteger('liberado_por')->nullable()->comment('Usuario que liberó');
            $table->unsignedBigInteger('consumido_por')->nullable()->comment('Usuario que consumió');
            $table->timestamps();
            
            // Índices
            $table->index('pedido_web_id');
            $table->index('producto_id');
            $table->index('estado');
            $table->index(['producto_id', 'estado']);
            
            // Foreign keys (sin onDelete cascade para evitar problemas)
            $table->foreign('pedido_web_id')->references('id')->on('pedidos_web');
            // No agregamos FK a producto por posibles incompatibilidades de tipos de datos
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas_inventario');
    }
};
