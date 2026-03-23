<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Header (Surat Jalan Penerimaan)
        Schema::create('nx_goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('gr_number')->unique();
            $table->foreignId('purchase_order_id')->constrained('nx_purchase_orders')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('nx_suppliers');
            $table->foreignId('warehouse_id')->constrained('nx_warehouses');
            $table->date('receipt_date');
            $table->string('delivery_note_number')->nullable(); // No. Surat Jalan dari Supplier
            $table->string('status')->default('draft'); // draft, completed, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->constrained('users'); // Orang gudang yang menerima
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabel Detail (Barang Fisik yang Diterima)
        Schema::create('nx_goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('nx_goods_receipts')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained('nx_purchase_order_items');
            $table->foreignId('product_id')->constrained('nx_products');
            $table->integer('quantity_received')->default(0);
            $table->text('scanned_sns')->nullable(); // Menyimpan SN yang di-scan sebagai log bukti
            $table->text('notes')->nullable(); // Keterangan jika barang cacat dll
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_goods_receipt_items');
        Schema::dropIfExists('nx_goods_receipts');
    }
};
