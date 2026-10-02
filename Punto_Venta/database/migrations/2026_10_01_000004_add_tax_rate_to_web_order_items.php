<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos_web_items', function (Blueprint $table): void {
            $table->decimal('tasa_isv', 5, 2)->default(0)->after('descuento');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_web_items', function (Blueprint $table): void {
            $table->dropColumn('tasa_isv');
        });
    }
};