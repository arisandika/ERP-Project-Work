<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_delivery_order_items', function (Blueprint $table) {
            $table->text('scanned_sns')->nullable()->after('item_name');
        });
    }

    public function down(): void
    {
        Schema::table('nx_delivery_order_items', function (Blueprint $table) {
            $table->dropColumn('scanned_sns');
        });
    }
};
