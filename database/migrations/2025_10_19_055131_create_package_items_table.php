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
        Schema::create('nx_package_items', function (Blueprint $table) {
    $table->id('id');
    $table->foreignId('package_id')->constrained('nx_packages');
    $table->integer('qty');

    // Polymorphic columns: item_id (INT) dan item_type (VARCHAR)
    $table->morphs('item');
    $table->timestamps();
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
