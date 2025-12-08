<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla específica para mapeo de productos entre Valencia y Zenvy
     * Optimizada para consultas rápidas por código de producto
     */
    public function up(): void
    {
        Schema::create('producto_valencia_zenvy', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id_zenvy')->comment('ID del producto en Zenvy (Paperland)');
            $table->unsignedBigInteger('producto_id_valencia')->comment('ID del producto en Valencia');
            $table->string('codigo_producto_valencia', 50)->nullable()->comment('Código del producto en Valencia para búsquedas rápidas');
            $table->string('codigo_barra', 100)->nullable()->comment('Código de barras del producto');
            $table->boolean('sincronizado')->default(true)->comment('Estado de sincronización');
            $table->timestamp('ultima_sincronizacion')->nullable()->comment('Fecha de última sincronización');
            $table->timestamps();

            // Llave primaria ya definida con id()
            
            // Índice único para producto_id_zenvy (un producto de Zenvy solo puede tener un origen Valencia)
            $table->unique('producto_id_zenvy', 'idx_producto_zenvy_unique');
            
            // Índice único para producto_id_valencia (un producto de Valencia solo puede estar sincronizado una vez)
            $table->unique('producto_id_valencia', 'idx_producto_valencia_unique');
            
            // Índice para código de producto Valencia (búsquedas rápidas)
            $table->index('codigo_producto_valencia', 'idx_codigo_producto_valencia');
            
            // Índice para código de barras (búsquedas por código de barras)
            $table->index('codigo_barra', 'idx_codigo_barra');
            
            // Índice para estado de sincronización
            $table->index('sincronizado', 'idx_sincronizado');
            
            // Índice compuesto para búsquedas combinadas
            $table->index(['producto_id_valencia', 'codigo_producto_valencia'], 'idx_valencia_codigo');

            // Llave foránea a tabla producto de Zenvy
            $table->foreign('producto_id_zenvy')
                ->references('id')
                ->on('producto')
                ->onDelete('cascade')
                ->onUpdate('cascade')
                ->name('fk_producto_zenvy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_valencia_zenvy');
    }
};
