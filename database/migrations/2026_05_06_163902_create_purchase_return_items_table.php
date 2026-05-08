<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_purchase_return_items', function (Blueprint $table) {
            $table->id();

            // Relasi ke Header. Gunakan cascadeOnDelete agar jika dokumen header dihapus, itemnya ikut terhapus.
            $table->foreignId('purchase_return_id')->constrained('nx_purchase_returns')->cascadeOnDelete();

            // Relasi ke Produk
            $table->foreignId('product_id')->constrained('nx_products')->restrictOnDelete();

            // Relasi ke Serial Number (Opsional, jika barang tersebut memiliki SN)
            $table->foreignId('serial_number_id')->nullable()->constrained('nx_serial_number')->nullOnDelete();

            $table->integer('quantity');

            // Menyimpan harga satuan saat retur terjadi (Historical Record)
            $table->decimal('unit_price', 15, 2);

            $table->string('reason'); // Alasan retur spesifik per item (misal: "Cacat fisik", "Mati total")

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_purchase_return_items');
    }
};
