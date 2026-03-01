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
        Schema::create('nx_serial_number', function (Blueprint $table) {
            $table->id();

            // 1. Relasi Utama (Wajib Ada)
            $table->foreignId('product_id')->constrained('nx_products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('nx_warehouses');

            // 2. Data Inti Serial Number
            $table->string('serial_number')->unique();
            $table->string('status')->default('AVAILABLE');

            // 3. Relasi Jejak (Tracking) - Boleh Null di awal
            $table->foreignId('supplier_id')->nullable()->constrained('nx_suppliers');
            $table->foreignId('customer_id')->nullable()->constrained('nx_customers');

            // 4. Tanggal Penting untuk Garansi & Audit
            $table->date('inbound_date')->nullable();
            $table->date('warranty_expired_at')->nullable();

            $table->timestamps();

            // 5. Indexing
            // Mempercepat query saat Admin Gudang melakukan Scan SN (mencari SN di gudang tertentu dengan status tertentu)
            $table->index(['serial_number', 'warehouse_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_serial_number');
    }
};
