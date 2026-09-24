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
        Schema::create('id_zenvy_valencia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_zenvy')->comment('ID del registro en la base de datos Zenvy (Paperland)');
            $table->unsignedBigInteger('id_valencia')->comment('ID del registro en la base de datos Valencia');
            $table->unsignedTinyInteger('tipo_dato_migrado_id')->comment('1=Producto, 2=Marca, 3=Categoria, 4=Subcategoria, 5=UnidadMedida, 8=Compra, 9=Traslado');
            $table->timestamps();

            // Índice compuesto único para evitar duplicados
            $table->unique(['id_valencia', 'tipo_dato_migrado_id'], 'idx_valencia_tipo_unique');
            
            // Índice compuesto para búsquedas por Zenvy
            $table->index(['id_zenvy', 'tipo_dato_migrado_id'], 'idx_zenvy_tipo');
            
            // Índice individual para id_valencia (consultas frecuentes)
            $table->index('id_valencia', 'idx_id_valencia');
            
            // Índice individual para id_zenvy (consultas frecuentes)
            $table->index('id_zenvy', 'idx_id_zenvy');
            
            // Índice para tipo_dato_migrado_id
            $table->index('tipo_dato_migrado_id', 'idx_tipo_dato');

            // Llave foránea a la tabla producto de Zenvy (solo para tipo_dato_migrado_id = 1)
            // NOTA: La llave foránea se aplica a nivel lógico, pero dado que id_zenvy puede 
            // referenciar diferentes tablas según tipo_dato_migrado_id, no se puede hacer
            // una FK directa. Se maneja mediante validaciones en la aplicación.
            
            // Si se desea agregar FK específica para productos, descomentar:
            // $table->foreign('id_zenvy')->references('id')->on('producto')->onDelete('cascade')->name('fk_zenvy_producto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('id_zenvy_valencia');
    }
};
