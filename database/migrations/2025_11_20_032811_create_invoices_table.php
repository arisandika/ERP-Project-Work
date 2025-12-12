<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_invoices', function (Blueprint $table) {
            $table->id();

            // relasi ke sales order & customer & employee
            $table->unsignedBigInteger('nx_sales_order_id')->nullable()->index();
            $table->unsignedBigInteger('nx_customer_id')->nullable()->index();
            $table->unsignedBigInteger('nx_employee_id')->nullable()->index();

            // info utama invoice
            $table->string('invoice_number', 255)->nullable()->unique();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();

            // status invoice (boleh disesuaikan)
            $table->string('status', 50)->default('draft');
            $table->text('notes')->nullable();

            // angka perhitungan (mirror dari SO)
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            // foreign key optional – sesuaikan nama tabel
            $table->foreign('nx_sales_order_id')
                ->references('id')
                ->on('nx_sales_orders')
                ->onDelete('set null');

            $table->foreign('nx_customer_id')
                ->references('id')
                ->on('nx_customers')   // ganti kalau nama tabelnya beda
                ->onDelete('set null');

            $table->foreign('nx_employee_id')
                ->references('id')
                ->on('nx_employees')    // ganti kalau nama tabelnya beda
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_invoices');
    }
};
