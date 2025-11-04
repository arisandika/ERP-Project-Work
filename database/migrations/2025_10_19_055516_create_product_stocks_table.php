<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_product_stock', function (Blueprint $table) {
            $table->id('id'); // PK: id

            // FK ke nx_products
            $table->foreignId('id')
                  ->constrained('nx_products', 'id') // Menunjuk ke kolom id di tabel nx_products
                  ->cascadeOnDelete();

            // FK ke nx_warehouses
            $table->foreignId('id')
                  ->constrained('nx_warehouses', 'id') // Menunjuk ke kolom id di tabel nx_warehouses
                  ->cascadeOnDelete();

            $table->integer('qty')->default(0);
            
            // Kolom status (sesuai ERD awal)
            $table->enum('status', ['available', 'reserved', 'out_of_stock'])->default('available');

            $table->timestamps();

            // Penting: Pastikan kombinasi Product dan Warehouse bersifat unik
            $table->unique(['id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_product_stock');
    }
};