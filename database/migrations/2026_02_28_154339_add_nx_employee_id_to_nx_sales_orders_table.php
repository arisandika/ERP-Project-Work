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
            // nx_employee_id sudah dibuat create migration 2025_11_08 — guard
            // mencegah "duplicate column" pada fresh migrate.
            if (Schema::hasColumn('nx_sales_orders', 'nx_employee_id')) {
                return;
            }

            // Gunakan constrained() agar integritas data terjaga di level database
            $table->foreignId('nx_employee_id')
                ->nullable() // atau constrained() jika wajib
                ->after('nx_customer_id')
                ->constrained('nx_employees')
                ->onDelete('restrict');
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
