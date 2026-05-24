<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_rma_requests', function (Blueprint $table) {
            // 1. Tambah tipe garansi
            if (!Schema::hasColumn('nx_rma_requests', 'warranty_type')) {
                $table->string('warranty_type')->default('supplier')->after('issue_description')->comment('supplier atau store');
            }

            // 2. Tambah kolom resolusi & notes internal
            if (!Schema::hasColumn('nx_rma_requests', 'resolution_type')) {
                $table->string('resolution_type')->nullable()->after('status')->comment('repaired, replaced, rejected');
            }
            if (!Schema::hasColumn('nx_rma_requests', 'internal_notes')) {
                $table->text('internal_notes')->nullable()->after('vendor_notes')->comment('Catatan jika garansi toko');
            }

            if (!Schema::hasColumn('nx_rma_requests', 'new_serial_number_id')) {

                $table->unsignedBigInteger('new_serial_number_id')->nullable()->after('resolution_type');

                $table->foreign('new_serial_number_id')
                      ->references('id')
                      ->on('nx_serial_numbers')
                      ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nx_rma_requests', function (Blueprint $table) {
            $table->dropForeign(['new_serial_number_id']);
            $table->dropColumn(['warranty_type', 'resolution_type', 'internal_notes', 'new_serial_number_id']);
        });
    }
};
