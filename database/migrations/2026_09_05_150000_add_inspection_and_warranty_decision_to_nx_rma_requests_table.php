<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pisahkan hasil inspeksi (inspection_result) dari keputusan garansi (warranty_decision).
     * Barang rusak belum tentu masuk garansi; barang bisa tidak rusak (NO_FAULT_FOUND).
     * resolution_type tetap menampung penyelesaian final (repair/replacement/refund/no_fault_found).
     */
    public function up(): void
    {
        Schema::table('nx_rma_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('nx_rma_requests', 'inspection_result')) {
                $table->string('inspection_result', 30)->nullable()->after('internal_notes')
                    ->comment('Hasil inspeksi: damaged, no_fault_found, user_error, physical_damage');
            }
            if (! Schema::hasColumn('nx_rma_requests', 'warranty_decision')) {
                $table->string('warranty_decision', 30)->nullable()->after('inspection_result')
                    ->comment('Keputusan garansi: pending, approved, rejected');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nx_rma_requests', function (Blueprint $table) {
            $table->dropColumn(['inspection_result', 'warranty_decision']);
        });
    }
};