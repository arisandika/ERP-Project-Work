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

            // Harga beli per unit saat transaksi
            $table->decimal('price', 15, 2)
                ->after('quantity');

            // Total harga transaksi
            $table->decimal('total_price', 15, 2)
                ->after('price');

            // Audit Stock
            $table->integer('stock_before')
                ->nullable()
                ->after('total_price');

            $table->integer('stock_after')
                ->nullable()
                ->after('stock_before');

            // Referensi transaksi (PO, Adjustment, Opname)
            $table->string('reference', 100)
                ->nullable()
                ->after('stock_after');

            // User input
            $table->foreignId('created_by')
                ->nullable()
                ->after('reference')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_stock_transactions', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'price',
                'total_price',
                'stock_before',
                'stock_after',
                'reference',
                'created_by',
            ]);
        });
    }
};
