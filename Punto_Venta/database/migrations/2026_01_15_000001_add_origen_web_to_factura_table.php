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
        Schema::table('factura', function (Blueprint $table) {
            $table->boolean('origen_web')->default(false)->after('users_id')->comment('Indica si la factura fue generada desde pedido web');
            $table->index('origen_web');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factura', function (Blueprint $table) {
            $table->dropIndex(['origen_web']);
            $table->dropColumn('origen_web');
        });
    }
};
