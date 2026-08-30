<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_rma_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('nx_rma_requests', 'purchase_return_id')) {
                $table->foreignId('purchase_return_id')
                    ->nullable()
                    ->after('new_serial_number_id')
                    ->constrained('nx_purchase_returns')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('nx_rma_requests', function (Blueprint $table) {
            $table->dropForeign(['purchase_return_id']);
            $table->dropColumn(['purchase_return_id']);
        });
    }
};