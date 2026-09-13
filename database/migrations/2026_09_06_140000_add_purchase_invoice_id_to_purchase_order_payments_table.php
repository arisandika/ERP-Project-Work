<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyambungkan pembayaran ke Purchase Invoice (hutang) yang dibayar.
     *
     * Tabel nx_purchase_order_payments sebelumnya HANYA punya purchase_order_id;
     * model dan PaymentsRelationManager (PurchaseInvoice) menautkan via
     * purchase_invoice_id — kolom itu tidak pernah ada (schema drift).
     * Akibatnya: create payment untuk invoices akan gagal insert / selalu null,
     * dan hook created (yang memosting FinancialRecord) tidak pernah berjalan.
     *
     * payment_number TIDAK boleh dipakai sebagai identity hutang/piutang
     * (lihat audit Phase 2). Identity utang adalah PurchaseInvoice itu sendiri;
     * posting FinancialRecord harus menunjuk ke PAYMENT id (unik), bukan invoice.
     */
    public function up(): void
    {
        Schema::table('nx_purchase_order_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('nx_purchase_order_payments', 'purchase_invoice_id')) {
                $table->foreignId('purchase_invoice_id')
                    ->nullable()
                    ->after('purchase_order_id')
                    ->constrained('nx_purchase_invoices')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('nx_purchase_order_payments', function (Blueprint $table) {
            if (Schema::hasColumn('nx_purchase_order_payments', 'purchase_invoice_id')) {
                $table->dropForeign(['purchase_invoice_id']);
                $table->dropColumn('purchase_invoice_id');
            }
        });
    }
};