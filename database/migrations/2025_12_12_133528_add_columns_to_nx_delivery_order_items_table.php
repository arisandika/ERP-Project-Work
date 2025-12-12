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
        Schema::table('nx_delivery_order_items', function (Blueprint $table) {
            // Tambahkan kolom qty_ordered dan qty_remaining
            $table->decimal('qty_ordered', 10, 2)->default(0)->after('item_name');
            $table->decimal('qty_remaining', 10, 2)->default(0)->after('qty');
        });
    }

    public function down(): void
    {
        Schema::table('nx_delivery_order_items', function (Blueprint $table) {
            $table->dropColumn(['qty_ordered', 'qty_remaining']);
        });
    }
};
