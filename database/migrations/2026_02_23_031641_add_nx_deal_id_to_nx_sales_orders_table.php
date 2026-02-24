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
        Schema::table('nx_sales_orders', function (Blueprint $table) {

            // Tambah FK deal
            $table->foreignId('nx_deal_id')
                ->after('id')
                ->constrained('nx_deals')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_sales_orders', function (Blueprint $table) {
            // Drop FK and column
            if (Schema::hasColumn('nx_sales_orders', 'nx_deal_id')) {

                try {
                    $table->dropForeign(['nx_deal_id']);
                } catch (\Exception $e) {
                    // ignore kalau FK tidak ada
                }

                $table->dropColumn('nx_deal_id');
            }

        });
    }
};
