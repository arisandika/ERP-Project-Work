<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_holidays', function (Blueprint $table) {
            // Menghapus aturan unique agar tanggal yang sama bisa diinput
            $table->dropUnique('nx_holidays_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('nx_holidays', function (Blueprint $table) {
            // Mengembalikan aturan unique jika migrasi di-rollback
            $table->unique('start_date', 'nx_holidays_date_unique');
        });
    }
};