<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factura', function (Blueprint $table): void {
            $table->unique('numero_factura', 'factura_numero_factura_unique');
        });

        Schema::table('facturas_anuladas', function (Blueprint $table): void {
            $table->unique('factura_id', 'facturas_anuladas_factura_id_unique');
        });

        Schema::table('id_zenvy_valencia', function (Blueprint $table): void {
            $table->unique(
                ['tipo_dato_migrado_id', 'id_valencia'],
                'id_zenvy_valencia_tipo_valencia_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('id_zenvy_valencia', function (Blueprint $table): void {
            $table->dropUnique('id_zenvy_valencia_tipo_valencia_unique');
        });

        Schema::table('facturas_anuladas', function (Blueprint $table): void {
            $table->dropUnique('facturas_anuladas_factura_id_unique');
        });

        Schema::table('factura', function (Blueprint $table): void {
            $table->dropUnique('factura_numero_factura_unique');
        });
    }
};