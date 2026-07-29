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
        Schema::table('nx_holidays', function (Blueprint $table) {
            // Tambahkan kolom 'end_date' setelah 'start_date'
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_holidays', function (Blueprint $table) {
            $table->dropColumn('end_date');
        });
    }
};
