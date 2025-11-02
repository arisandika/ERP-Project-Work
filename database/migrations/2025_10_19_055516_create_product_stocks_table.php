<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_product_stock', function (Blueprint $table) {
            $table->id('id_stock'); // PK: id_stock

            // FK ke nx_products
            $table->foreignId('id_product')
                  ->constrained('nx_products', 'id_product') // Menunjuk ke kolom id_product di tabel nx_products
                  ->cascadeOnDelete();

            // FK ke nx_warehouses
            $table->foreignId('id_warehouse')
                  ->constrained('nx_warehouses', 'id_warehouse') // Menunjuk ke kolom id_warehouse di tabel nx_warehouses
                  ->cascadeOnDelete();

            $table->integer('qty')->default(0);
            
            // Kolom status (sesuai ERD awal)
            $table->enum('status', ['available', 'reserved', 'out_of_stock'])->default('available');

            $table->timestamps();

            // Penting: Pastikan kombinasi Product dan Warehouse bersifat unik
            $table->unique(['id_product', 'id_warehouse']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_product_stock');
    }
};