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
        Schema::table('nx_stock_transactions', function (Blueprint $table) {
            // Tambah kolom referensi
            $table->string('reference_number')->nullable()->after('id');

            // Tambah tipe mutasi
            $table->enum('mutation_type', [
                'stock_in',      // Masuk dari supplier
                'reserve',       // Dipesan/Reserved
                'delivery',      // Dikirim
                'complete',      // Selesai/Diterima
                'cancel',        // Batal
                'adjustment'     // Penyesuaian/Koreksi
            ])->default('stock_in')->after('reference_number');
        });
    }

    /**
    * Reverse the migrations.
    */

    public function down(): void
    {
        Schema::table('nx_stock_transactions', function (Blueprint $table) {
            $table->dropColumn(['reference_number', 'mutation_type']);
        });
    }

};
