<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mendefinisikan nama tabel dengan prefix nx_ sesuai standar database Anda
        Schema::create('nx_purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();

            // Relasi ke Supplier
            $table->foreignId('supplier_id')->constrained('nx_suppliers')->restrictOnDelete();

            // Referensi opsional ke penerimaan barang (Goods Receipt)
            $table->foreignId('goods_receipt_id')->nullable()->constrained('nx_goods_receipts')->nullOnDelete();

            $table->date('return_date');

            // State Machine untuk alur persetujuan dan pengiriman retur
            $table->enum('status', ['draft', 'approved', 'shipped', 'completed', 'cancelled'])->default('draft');

            // Logic krusial dari perusahaan: Resolusi retur
            $table->enum('resolution_type', ['credit_note', 'refund'])->nullable()->comment('credit_note = Potong Hutang, refund = Kembali Dana');

            $table->text('notes')->nullable();

            // Jejak audit (Audit Trail)
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes(); // Best practice: Gunakan soft deletes untuk data transaksional ERP
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_purchase_returns');
    }
};
