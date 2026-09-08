<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Traceability item retur ke Goods Receipt Item (dari PO item).
     * Memungkinkan validasi quantity retur ≤ quantity yang diterima, dan
     * melihat SN yang diretur untuk produk berseri.
     */
    public function up(): void
    {
        Schema::table('nx_purchase_return_items', function (Blueprint $table) {
            if (!Schema::hasColumn('nx_purchase_return_items', 'goods_receipt_item_id')) {
                $table->foreignId('goods_receipt_item_id')
                    ->nullable()
                    ->after('serial_number_id')
                    ->constrained('nx_goods_receipt_items')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_purchase_return_items', function (Blueprint $table) {
            $table->dropForeign(['goods_receipt_item_id']);
            $table->dropColumn('goods_receipt_item_id');
        });
    }
};