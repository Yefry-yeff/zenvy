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
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Nombre del cliente API');
            $table->string('api_key', 64)->unique()->comment('API Key pública');
            $table->string('api_secret', 255)->comment('API Secret hasheado');
            $table->boolean('is_active')->default(true)->comment('Estado del cliente');
            $table->text('ip_whitelist')->nullable()->comment('JSON array de IPs permitidas');
            $table->integer('rate_limit_per_minute')->default(60)->comment('Límite de requests por minuto');
            $table->timestamps();

            $table->index('api_key');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
