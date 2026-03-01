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
        // 1. Tabel Induk (Header) PO
        Schema::create('nx_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique()->comment('Contoh: PO/NEX/III/2026/001');
            $table->foreignId('supplier_id')->constrained('nx_suppliers')->onDelete('restrict');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();

            // Status PO sangat penting untuk alur kerja (Workflow)
            $table->enum('status', [
                'draft',      // Baru dibuat, belum dikirim ke supplier
                'sent',       // Sudah dikirim/dipesan ke supplier
                'partial',    // Barang datang tapi baru sebagian
                'completed',  // Semua barang sudah datang dan masuk gudang (Close)
                'cancelled'   // PO dibatalkan
            ])->default('draft');

            // Komponen Harga
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0)->comment('Pajak PPN');
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Tabel Detail (Items) PO
        Schema::create('nx_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('nx_purchase_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('nx_products')->onDelete('restrict');

            $table->integer('quantity');
            $table->integer('quantity_received')->default(0)->comment('Jumlah yang sudah benar-benar masuk ke gudang');

            $table->decimal('unit_price', 15, 2)->comment('Harga beli per unit saat itu');
            $table->decimal('total_price', 15, 2)->comment('quantity * unit_price');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Harus drop tabel items (detail) dulu karena ada foreign key ke tabel induk
        Schema::dropIfExists('nx_purchase_order_items');
        Schema::dropIfExists('nx_purchase_orders');
    }
};
