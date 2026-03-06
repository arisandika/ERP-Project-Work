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
        Schema::table('nx_projects', function (Blueprint $table) {
            $table->foreignId('nx_sales_order_id')
                ->nullable()
                ->after('description')
                ->constrained('nx_sales_orders')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_projects', function (Blueprint $table) {
            $table->dropForeign(['nx_sales_order_id']);
            $table->dropColumn('nx_sales_order_id');
        });
    }
};
