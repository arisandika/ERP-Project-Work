<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expand RMA status from a rigid ENUM to a flexible VARCHAR(50)
     * so new workflow states (submitted, internal_repair, ready_for_return)
     * can be stored without re-altering the enum every time the workflow grows.
     *
     * Previous ENUM: ('received','sent_to_vendor','ready_from_vendor','returned_to_client','rejected')
     * This also retro-fixes a latent mismatch: the app already writes
     * 'ready_for_return' / 'internal_repair' but the ENUM never allowed them.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('nx_rma_requests', 'status')) {
            return;
        }

        DB::statement('ALTER TABLE nx_rma_requests MODIFY status VARCHAR(50) NOT NULL DEFAULT \'submitted\'');
    }

    public function down(): void
    {
        if (!Schema::hasColumn('nx_rma_requests', 'status')) {
            return;
        }

        // Best-effort revert; will fail if rows contain new status values.
        DB::statement("ALTER TABLE nx_rma_requests MODIFY status ENUM('received','sent_to_vendor','ready_from_vendor','returned_to_client','rejected') NOT NULL DEFAULT 'received'");
    }
};