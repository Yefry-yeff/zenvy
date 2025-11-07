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
        // Crear tabla si no existe
        if (!Schema::hasTable('ai_consultas')) {
            Schema::create('ai_consultas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('usuario_id');
                $table->text('pregunta');
                $table->text('respuesta');
                $table->text('script')->nullable()->comment('Consulta SQL generada por la IA');
                $table->timestamps();

                $table->index('usuario_id');
                $table->index('created_at');
            });
        } else {
            // Si la tabla existe, solo agregar la columna script si no existe
            Schema::table('ai_consultas', function (Blueprint $table) {
                if (!Schema::hasColumn('ai_consultas', 'script')) {
                    $table->text('script')->nullable()->comment('Consulta SQL generada por la IA')->after('respuesta');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ai_consultas') && Schema::hasColumn('ai_consultas', 'script')) {
            Schema::table('ai_consultas', function (Blueprint $table) {
                $table->dropColumn('script');
            });
        }
    }
};
