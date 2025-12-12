<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_delivery_orders', function (Blueprint $table) {
            $table->id();

            // relasi ke sales order & customer & employee
            $table->unsignedBigInteger('nx_sales_order_id')->nullable()->index();
            $table->unsignedBigInteger('nx_customer_id')->nullable()->index();
            $table->unsignedBigInteger('nx_employee_id')->nullable()->index();

            // info utama DO
            $table->string('do_number', 255)->nullable()->unique();
            $table->date('do_date')->nullable();

            // status DO
            $table->string('status', 50)->default('draft');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // foreign key (optional, kalau mau strict)
            // sesuaikan nama tabel & kolom dengan yang ada
            $table->foreign('nx_sales_order_id')
                ->references('id')
                ->on('nx_sales_orders')
                ->onDelete('set null');

            $table->foreign('nx_customer_id')
                ->references('id')
                ->on('nx_customers')
                ->onDelete('set null');

            $table->foreign('nx_employee_id')
                ->references('id')
                ->on('nx_employees')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_delivery_orders');
    }
};
