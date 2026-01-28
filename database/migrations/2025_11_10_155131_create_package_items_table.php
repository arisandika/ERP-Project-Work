<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nx_package_items', function (Blueprint $table) {
            $table->id('id');

            // Relasi ke paket utama
            $table->foreignId('package_id')
                ->constrained('nx_packages')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Bisa berisi product atau service
            $table->enum('item_type', ['product', 'service']);
            $table->unsignedBigInteger('item_id'); // ID Product atau layanan

            $table->integer('quantity')->default(1);
            $table->decimal('price', 15, 2)->default(0); // harga per item saat di-bundle

            $table->softDeletes();
            $table->timestamps();

            // (Opsional) Index kombinasi supaya cepat saat query
            $table->index(['item_type', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_package_items');
    }
};
