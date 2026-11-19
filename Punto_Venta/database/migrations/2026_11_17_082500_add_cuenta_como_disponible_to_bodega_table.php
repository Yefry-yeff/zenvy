<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('bodega', 'cuenta_como_disponible')) {
            return;
        }

        Schema::table('bodega', function (Blueprint $table) {
            $table->boolean('cuenta_como_disponible')
                ->default(true)
                ->after('principal')
                ->comment('Indica si la bodega cuenta como inventario disponible para venta');
        });

        DB::table('bodega')
            ->where(function ($query) {
                $query->where('estado_id', '!=', 1)
                    ->orWhere('id', 2);
            })
            ->update(['cuenta_como_disponible' => false]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('bodega', 'cuenta_como_disponible')) {
            return;
        }

        Schema::table('bodega', function (Blueprint $table) {
            $table->dropColumn('cuenta_como_disponible');
        });
    }
};
