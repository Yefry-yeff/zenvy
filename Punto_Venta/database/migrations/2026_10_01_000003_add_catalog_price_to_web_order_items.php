<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos_web_items', function (Blueprint $table): void {
            $table->integer('precio_venta_id')->nullable()->after('producto_id');
            $table->integer('unidad_medida_id')->nullable()->after('precio_venta_id');
            $table->decimal('descuento', 10, 2)->default(0)->after('subtotal');
            $table->foreign('precio_venta_id', 'pedidos_web_items_precio_venta_fk')
                ->references('id')
                ->on('precio_has_venta');
            $table->foreign('unidad_medida_id', 'pedidos_web_items_unidad_medida_fk')
                ->references('id')
                ->on('unidad_medida');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_web_items', function (Blueprint $table): void {
            $table->dropForeign('pedidos_web_items_precio_venta_fk');
            $table->dropForeign('pedidos_web_items_unidad_medida_fk');
            $table->dropColumn(['precio_venta_id', 'unidad_medida_id', 'descuento']);
        });
    }
};