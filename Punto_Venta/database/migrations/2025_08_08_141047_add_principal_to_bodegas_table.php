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
        Schema::table('bodega', function (Blueprint $table) {
            $table->tinyInteger('principal')->default(0)->after('estado_id')->comment('1=Principal, 0=Secundaria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bodega', function (Blueprint $table) {
            $table->dropColumn('principal');
        });
    }
};
