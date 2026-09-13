<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Tabel nx_deals sudah dibuat oleh
     * 2026_02_20_222735_create_nx_deals_table dan lebih lanjut di-refactor
     * oleh 2026_02_23_193058_refactor_nx_deals_table. Migrasi ini = salinan
     * duplikat yang menimpa create → crash pada fresh migrate (SQLite).
     * Skema final nx_deals memakai kolom dari refactor (nx_lead_id,
     * nx_customer_id, nx_deal_stage_id, deal_number, dsb), bukan nx_quotation_id.
     */
    public function up(): void
    {
        if (Schema::hasTable('nx_deals')) {
            return;
        }

        Schema::create('nx_deals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('nx_lead_id')
                ->constrained('nx_leads')
                ->cascadeOnDelete();

            $table->foreignId('nx_quotation_id')
                ->constrained('nx_quotations')
                ->cascadeOnDelete();

            $table->string('status')->default('open'); // open, won, lost, converted

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_deals');
    }
};
