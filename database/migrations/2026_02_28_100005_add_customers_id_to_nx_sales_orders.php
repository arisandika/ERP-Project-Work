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
        Schema::table('nx_sales_orders', function (Blueprint $table) {
            // nx_customer_id sudah dibuat create migration 2025_11_08 — guard
            // mencegah "duplicate column" pada fresh migrate.
            if (Schema::hasColumn('nx_sales_orders', 'nx_customer_id')) {
                return;
            }

            // Letakkan setelah nx_quotation_id agar struktur tabel tetap rapi
            $table->foreignId('nx_customer_id')
                ->nullable()
                ->after('nx_quotation_id')
                ->constrained('nx_customers')
                ->onDelete('set null');
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_sales_orders', function (Blueprint $table) {
            //
        });
    }
};
