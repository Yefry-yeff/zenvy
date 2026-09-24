<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración agrega una relación foránea específica para productos
     * sincronizados desde Valencia a la tabla producto de Zenvy.
     * 
     * NOTA: Solo aplica para registros donde tipo_dato_migrado_id = 1 (productos)
     */
    public function up(): void
    {
        // Agregar relación foránea condicional para productos
        // Esta FK asegura la integridad referencial para productos sincronizados
        Schema::table('id_zenvy_valencia', function (Blueprint $table) {
            // Opción 1: FK directa a tabla producto (solo si todos los registros con tipo 1 existen en producto)
            // $table->foreign('id_zenvy')
            //     ->references('id')
            //     ->on('producto')
            //     ->onDelete('cascade')
            //     ->onUpdate('cascade')
            //     ->name('fk_id_zenvy_producto');
            
            // Opción 2: No agregar FK directa y manejar integridad a nivel de aplicación
            // Esta es la opción recomendada si id_zenvy puede referenciar múltiples tablas
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('id_zenvy_valencia', function (Blueprint $table) {
            // $table->dropForeign('fk_id_zenvy_producto');
        });
    }
};
