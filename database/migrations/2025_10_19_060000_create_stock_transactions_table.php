<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_stock_transactions', function (Blueprint $table) {
            $table->id('id');

            // Foreign Key ke nx_products
            $table->foreignId('product_id')
                ->constrained('nx_products', 'id')
                ->cascadeOnDelete();

            $table->date('transaction_date');

            $table->enum('type', ['masuk', 'keluar']);

            $table->integer('quantity');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_stock_transactions');
    }
};

