<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_factura_lote', function (Blueprint $table): void {
            $table->timestamp('restored_at')->nullable()->after('updated_at');
            $table->unsignedBigInteger('restored_by')->nullable()->after('restored_at');
            $table->index(['factura_id', 'restored_at'], 'detalle_factura_lote_restore_idx');
            $table->foreign('restored_by', 'detalle_factura_lote_restored_by_fk')
                ->references('id')
                ->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_factura_lote', function (Blueprint $table): void {
            $table->dropForeign('detalle_factura_lote_restored_by_fk');
            $table->dropIndex('detalle_factura_lote_restore_idx');
            $table->dropColumn(['restored_at', 'restored_by']);
        });
    }
};