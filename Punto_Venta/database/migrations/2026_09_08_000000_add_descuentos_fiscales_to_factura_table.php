<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factura', function (Blueprint $table) {
            $table->decimal('descuento_gravado', 16, 2)->default(0)->after('monto_descuento');
            $table->decimal('descuento_exento', 16, 2)->default(0)->after('descuento_gravado');
        });
    }

    public function down(): void
    {
        Schema::table('factura', function (Blueprint $table) {
            $table->dropColumn(['descuento_gravado', 'descuento_exento']);
        });
    }
};