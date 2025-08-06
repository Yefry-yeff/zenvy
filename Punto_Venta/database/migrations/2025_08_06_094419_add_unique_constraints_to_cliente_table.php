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
        Schema::table('cliente', function (Blueprint $table) {
            // Agregar índices únicos para identidad y RTN
            // Solo si no están vacíos (permitir múltiples valores NULL)
            $table->unique('identidad', 'cliente_identidad_unique')->where('identidad', '!=', '');
            $table->unique('rtn', 'cliente_rtn_unique')->where('rtn', '!=', '');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cliente', function (Blueprint $table) {
            // Eliminar los índices únicos
            $table->dropUnique('cliente_identidad_unique');
            $table->dropUnique('cliente_rtn_unique');
        });
    }
};
