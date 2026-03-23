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
        // Tabel Induk PR
        Schema::create('nx_purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number')->unique();
            $table->date('request_date');
            $table->date('required_date')->nullable(); // Kapan barang ini paling lambat dibutuhkan
            $table->foreignId('requested_by')->constrained('users'); // Karyawan yang minta
            $table->foreignId('department_id')->nullable(); // Departemen yang minta anggaran
            $table->string('status')->default('draft'); // draft, pending, approved, rejected, completed
            $table->text('purpose')->nullable(); // Tujuan pembelian (misal: "Untuk operasional kantor")
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabel Item PR
        Schema::create('nx_purchase_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained('nx_purchase_requisitions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('nx_products');
            $table->integer('quantity');
            $table->decimal('estimated_price', 15, 2)->default(0); // Harga perkiraan, bukan harga final
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_purchase_requisitions');
        Schema::dropIfExists('nx_purchase_requisition_items');
    }
};
