<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_invoice_items', function (Blueprint $table) {
            $table->id();

            // relasi ke header invoice
            $table->unsignedBigInteger('nx_invoice_id')->index();

            // mirror dari sales_order_items
            $table->string('item_type', 255)->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('item_code', 255)->nullable();
            $table->string('item_name', 255)->nullable();

            $table->integer('qty')->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('nx_invoice_id')
                ->references('id')
                ->on('nx_invoices')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_invoice_items');
    }
};
