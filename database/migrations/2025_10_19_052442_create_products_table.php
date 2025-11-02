<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_products', function (Blueprint $table) {
            $table->id('id_product'); // PK, INT, akan otomatis menjadi auto-increment

            $table->string('product_name', 100);

            // Foreign Key untuk Category (menggantikan VARCHAR category di ERD awal)
            // Asumsi tabel kategori disebut 'nx_categories'
            $table->foreignId('category_id')
                  ->constrained('nx_categories')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();

            $table->enum('label', ['product', 'service']);

            // Foreign Key untuk Unit (menggantikan VARCHAR unit di ERD awal)
            // Asumsi tabel satuan disebut 'nx_units'
            $table->foreignId('unit_id')
                  ->constrained('nx_units')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();

            $table->integer('min_stock')->default(0);
            $table->decimal('price', 15, 2);

            $table->timestamps(); // kolom created_at dan updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_products');
    }
};