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
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('evento', 100)->index();
            $table->string('direccion')->default('outgoing'); // outgoing, incoming
            $table->string('url')->nullable();
            $table->text('payload')->nullable();
            $table->text('headers')->nullable();
            $table->integer('status_code')->nullable();
            $table->text('response')->nullable();
            $table->boolean('exitoso')->default(false)->index();
            $table->integer('intentos')->default(1);
            $table->float('tiempo_respuesta')->nullable(); // en segundos
            $table->text('mensaje_error')->nullable();
            $table->unsignedBigInteger('relacionado_id')->nullable(); // ID del producto, factura, etc
            $table->string('relacionado_tipo')->nullable(); // producto, factura, compra, etc
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();
            
            // Índices para optimizar búsquedas
            $table->index(['relacionado_tipo', 'relacionado_id']);
            $table->index('created_at');
            $table->index(['exitoso', 'created_at']);
            
            // Foreign key si existe tabla de usuarios
            $table->foreign('usuario_id')
                  ->references('idusuario')
                  ->on('usuario')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
