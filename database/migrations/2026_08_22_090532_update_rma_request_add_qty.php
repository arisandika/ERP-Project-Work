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
        Schema::table('nx_rma_requests', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('customer_id')->constrained('nx_invoices')->nullOnDelete();
            $table->foreignId('invoice_item_id')->nullable()->after('invoice_id')->constrained('nx_invoice_items')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->after('serial_number_id')->constrained('nx_products')->nullOnDelete();
            $table->unsignedInteger('qty')->nullable()->after('product_id');        // dipakai kalau produk non-SN
            $table->json('evidence_files')->nullable()->after('issue_description'); // path file bukti kerusakan
            $table->string('source', 20)->default('internal')->after('created_by'); // 'internal' vs 'customer_portal'
        });
    }

    public function down(): void
    {
        Schema::table('nx_rma_requests', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['invoice_item_id']);
            $table->dropForeign(['product_id']);
            $table->dropColumn(['invoice_id', 'invoice_item_id', 'product_id', 'qty', 'evidence_files', 'source']);
        });
    }
};
