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
        Schema::table('nx_quotations', function (Blueprint $table) {

            // Tambah FK lead
            $table->foreignId('nx_lead_id')
                ->after('id')
                ->constrained('nx_leads')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_quotations', function (Blueprint $table) {
            // Drop FK and column
            // Hapus FK hanya kalau column ada
            if (Schema::hasColumn('nx_quotations', 'nx_lead_id')) {

                try {
                    $table->dropForeign(['nx_lead_id']);
                } catch (\Exception $e) {
                    // ignore kalau FK tidak ada
                }

                $table->dropColumn('nx_lead_id');
            }

        });
    }
};
