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
        Schema::create('nx_purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->string('vendor_invoice_number');
            $table->foreignId('purchase_order_id')->constrained('nx_purchase_orders')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('nx_suppliers');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('remaining_balance', 15, 2)->storedAs('grand_total - total_paid');
            $table->string('status')->default('unpaid');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('nx_purchase_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('nx_purchase_invoices')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained('nx_purchase_order_items');
            $table->foreignId('product_id')->constrained('nx_products');
            $table->integer('quantity_billed');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_purchase_invoice_items');
        Schema::dropIfExists('nx_purchase_invoices');
    }

};
