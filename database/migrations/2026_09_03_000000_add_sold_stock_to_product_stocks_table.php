<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_product_stock', function (Blueprint $table) {
            $table->integer('sold_stock')->default(0)->after('qty_on_delivery');
        });
    }

    public function down(): void
    {
        Schema::table('nx_product_stock', function (Blueprint $table) {
            $table->dropColumn('sold_stock');
        });
    }
};