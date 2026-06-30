<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_product_stock', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                  ->constrained('nx_products')
                  ->cascadeOnDelete();

            $table->foreignId('warehouse_id')
                  ->constrained('nx_warehouses')
                  ->cascadeOnDelete();

            $table->integer('qty')->default(0);
            
            $table->enum('status', ['available', 'reserved', 'out_of_stock'])->default('available');

            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_product_stock');
    }
};