<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Traceability retur pembelian ke Purchase Order yang menciptakan barang.
     * Header hanya punya goods_receipt_id sebelumnya; tambahkan purchase_order_id
     * supaya retur bisa ditelusuri ke PO asal bahkan tanpa GR (jika GR belum dipilih).
     */
    public function up(): void
    {
        Schema::table('nx_purchase_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('nx_purchase_returns', 'purchase_order_id')) {
                $table->foreignId('purchase_order_id')
                    ->nullable()
                    ->after('goods_receipt_id')
                    ->constrained('nx_purchase_orders')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_purchase_returns', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropColumn('purchase_order_id');
        });
    }
};