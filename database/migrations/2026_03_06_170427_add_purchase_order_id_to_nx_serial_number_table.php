<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration.
     */
    public function up(): void
    {
        Schema::table('nx_serial_number', function (Blueprint $table) {
            // 1. Tambahkan kolom setelah supplier_id
            $table->unsignedBigInteger('purchase_order_id')->nullable()->after('supplier_id');

            // 2. Buat relasi Foreign Key ke tabel PO untuk menjaga integritas data (Referential Integrity)
            $table->foreign('purchase_order_id')
                  ->references('id')
                  ->on('nx_purchase_orders')
                  ->nullOnDelete(); // Jika PO dihapus, field ini jadi NULL (SN tidak ikut terhapus)
        });
    }


    public function down(): void
    {
        Schema::table('nx_serial_number', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropColumn('purchase_order_id');
        });
    }
};