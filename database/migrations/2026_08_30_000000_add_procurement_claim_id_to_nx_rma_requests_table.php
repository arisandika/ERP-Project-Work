<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_rma_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('nx_rma_requests', 'procurement_claim_id')) {
                $table->foreignId('procurement_claim_id')
                    ->nullable()
                    ->after('purchase_return_id')
                    ->constrained('nx_purchase_orders')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('nx_rma_requests', function (Blueprint $table) {
            if (Schema::hasColumn('nx_rma_requests', 'procurement_claim_id')) {
                $table->dropForeign(['procurement_claim_id']);
                $table->dropColumn(['procurement_claim_id']);
            }
        });
    }
};
