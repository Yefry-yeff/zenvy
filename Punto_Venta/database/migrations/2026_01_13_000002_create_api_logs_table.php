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
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('api_clients')->nullOnDelete();
            $table->string('method', 10)->comment('Método HTTP');
            $table->string('endpoint', 255)->comment('Ruta del endpoint');
            $table->json('request_body')->nullable()->comment('Cuerpo del request');
            $table->integer('response_status')->comment('Código HTTP de respuesta');
            $table->json('response_body')->nullable()->comment('Cuerpo de la respuesta');
            $table->string('ip_address', 45)->comment('IP del cliente');
            $table->text('user_agent')->nullable()->comment('User agent');
            $table->decimal('duration_ms', 10, 2)->nullable()->comment('Duración en milisegundos');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['client_id', 'created_at']);
            $table->index('endpoint');
            $table->index('response_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
