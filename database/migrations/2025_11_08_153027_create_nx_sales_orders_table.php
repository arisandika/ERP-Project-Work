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
        Schema::create('nx_sales_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_quotation_id')->nullable()->constrained('nx_quotations')->onDelete('set null');
            $table->foreignId('nx_customer_id')->constrained('nx_customers');
            $table->foreignId('nx_employee_id')->constrained('nx_employees');

            $table->string('order_number')->unique()->nullable(); 
            $table->date('order_date');
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_sales_orders');
    }
};
