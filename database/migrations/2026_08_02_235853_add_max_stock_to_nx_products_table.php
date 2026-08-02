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
        Schema::table('nx_products', function (Blueprint $table) {
            $table->integer('max_stock')->nullable()->after('min_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_products', function (Blueprint $table) {
            $table->dropColumn('max_stock');
        });
    }
};
