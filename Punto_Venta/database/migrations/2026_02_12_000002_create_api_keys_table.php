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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('key', 255)->unique();
            $table->boolean('activo')->default(true)->index();
            $table->timestamp('ultimo_uso')->nullable();
            $table->integer('total_requests')->default(0);
            $table->json('permisos')->nullable(); // array de permisos: ['read_products', 'write_inventory', etc]
            $table->string('ip_permitidas')->nullable(); // IPs separadas por coma
            $table->timestamp('expira_en')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('activo');
            $table->index('expira_en');
            
            // Foreign key
            $table->foreign('usuario_id')
                  ->references('idusuario')
                  ->on('usuario')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
