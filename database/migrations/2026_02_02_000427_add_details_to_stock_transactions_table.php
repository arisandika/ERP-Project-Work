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
        Schema::table('nx_stock_transactions', function (Blueprint $table) {

            // 1. Tambah Kolom Transaction Code (ST-OUT/...)
            if (!Schema::hasColumn('nx_stock_transactions', 'transaction_code')) {
                $table->string('transaction_code', 50)->nullable()->after('id')->index();
            }

            // 2. Tambah Audit Trail (Stock Sebelum & Sesudah)
            if (!Schema::hasColumn('nx_stock_transactions', 'stock_before')) {
                $table->integer('stock_before')->default(0)->after('total_price');
            }
            if (!Schema::hasColumn('nx_stock_transactions', 'stock_after')) {
                $table->integer('stock_after')->default(0)->after('stock_before');
            }

            // 3. Tambah Relasi Polymorphic (Untuk link ke Sales Order, dll)
            // Ini otomatis membuat kolom 'reference_id' (bigInt) dan 'reference_type' (string)
            if (!Schema::hasColumn('nx_stock_transactions', 'reference_id')) {
                $table->nullableMorphs('reference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_stock_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'transaction_code',
                'stock_before',
                'stock_after',
                'reference_id',
                'reference_type'
            ]);
        });
    }
};
