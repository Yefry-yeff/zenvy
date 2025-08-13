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
        Schema::create('descuentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('factura_id');
            $table->unsignedInteger('producto_id');
            $table->decimal('monto_unidad', 16, 2)->nullable();
            $table->decimal('monto_total', 16, 2)->nullable();
            $table->unsignedBigInteger('users_id');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Índices
            $table->index('factura_id', 'fk_descuentos_factura1_idx');
            $table->index('producto_id', 'fk_descuentos_producto1_idx');
            $table->index('users_id', 'fk_descuentos_users1_idx');

            // Foreign keys
            $table->foreign('users_id', 'fk_descuentos_users1')
                ->references('id')->on('users')
                ->onDelete('no action')
                ->onUpdate('no action');
            
            $table->foreign('factura_id', 'fk_descuentos_factura1')
                ->references('id')->on('factura')
                ->onDelete('no action')
                ->onUpdate('no action');
                
            $table->foreign('producto_id', 'fk_descuentos_producto1')
                ->references('id')->on('producto')
                ->onDelete('no action')
                ->onUpdate('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('descuentos');
    }
};
