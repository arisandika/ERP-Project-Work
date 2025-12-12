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
        Schema::create('nx_sales_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('nx_sales_order_id')->constrained('nx_sales_orders')->cascadeOnDelete();

            $table->string('item_type'); 
            $table->unsignedBigInteger('item_id');
            $table->string('item_code')->nullable();
            $table->string('item_name');
            $table->integer('qty');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_sales_order_items');
    }
};
