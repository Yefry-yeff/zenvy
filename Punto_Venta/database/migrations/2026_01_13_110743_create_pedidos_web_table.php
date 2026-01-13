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
        Schema::create('pedidos_web', function (Blueprint $table) {
            $table->id();
            $table->string('numero_pedido', 50)->unique()->comment('Número de orden desde web');
            $table->unsignedBigInteger('factura_id')->nullable()->comment('ID factura si ya fue procesado');
            $table->string('estado', 20)->default('pendiente')->comment('pendiente, procesando, facturado, rechazado');
            
            // Datos del cliente
            $table->string('cliente_nombre', 200);
            $table->string('cliente_email', 100)->nullable();
            $table->string('cliente_telefono', 20)->nullable();
            $table->string('cliente_rtn', 20)->nullable();
            $table->text('cliente_direccion')->nullable();
            
            // Montos
            $table->decimal('subtotal', 10, 2);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('isv', 10, 2);
            $table->decimal('total', 10, 2);
            
            // Información adicional
            $table->string('metodo_pago', 50)->nullable();
            $table->text('notas')->nullable();
            $table->json('metadata')->nullable()->comment('Datos adicionales de la web');
            
            // Auditoría
            $table->unsignedBigInteger('procesado_por')->nullable()->comment('Usuario que procesó');
            $table->timestamp('fecha_procesado')->nullable();
            $table->timestamp('fecha_facturado')->nullable();
            $table->boolean('leido')->default(false)->comment('Para notificaciones');
            $table->timestamps();
            
            $table->index('estado');
            $table->index('leido');
            $table->index(['estado', 'leido']);
        });
        
        Schema::create('pedidos_web_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_web_id');
            $table->unsignedBigInteger('producto_id');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('isv', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
            
            $table->foreign('pedido_web_id')->references('id')->on('pedidos_web')->onDelete('cascade');
            // Sin FK a producto por posibles problemas de estructura
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos_web_items');
        Schema::dropIfExists('pedidos_web');
    }
};
