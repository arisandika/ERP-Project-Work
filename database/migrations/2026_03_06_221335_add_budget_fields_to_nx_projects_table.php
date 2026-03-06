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
        Schema::table('nx_projects', function (Blueprint $table) {

            // Estimasi biaya project
            $table->decimal('estimated_cost', 15, 2)
                ->default(0)
                ->after('end_date');

            // Pengeluaran aktual
            $table->decimal('actual_cost', 15, 2)
                ->default(0)
                ->after('estimated_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_projects', function (Blueprint $table) {
            $table->dropColumn([
                'estimated_cost',
                'actual_cost'
            ]);
        });
    }
};
