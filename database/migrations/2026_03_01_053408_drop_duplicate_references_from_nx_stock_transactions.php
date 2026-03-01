<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_stock_transactions', function (Blueprint $table) {
            // Menghapus kolom yang fungsinya ganda
            $table->dropColumn(['no_reference', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::table('nx_stock_transactions', function (Blueprint $table) {
            $table->string('no_reference', 50)->nullable();
            $table->string('reference', 100)->nullable();
        });
    }
};
