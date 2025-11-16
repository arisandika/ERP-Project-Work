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
        Schema::create('nx_packages', function (Blueprint $table) {
            $table->id('id');
            $table->string('package_name', 100);
            $table->text('description')->nullable();
            $table->decimal('total_price', 15, 2)->default(0); // total harga seluruh item dalam paket
            $table->boolean('is_active')->default(true);
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_packages');
    }
};
