<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The current enum only allows repaired/replaced/rejected.
     * We need to add 'refund' to match the 4 supplier decision choices.
     * Using ENUM modification for MySQL compatibility.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('nx_rma_requests', 'resolution_type')) {
            return;
        }

        // SQLite tidak mendukung ALTER ... MODIFY; skema type sudah string.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        // Change to a plain string so new values (refund) can be stored without
        // re-altering enum every time the workflow grows.
        DB::statement("ALTER TABLE nx_rma_requests MODIFY resolution_type VARCHAR(50) NULL");
    }

    public function down(): void
    {
        if (Schema::hasColumn('nx_rma_requests', 'resolution_type')
            && Schema::getConnection()->getDriverName() !== 'sqlite') {
            // Revert to enum (best-effort; if data contains new values, this will fail)
            DB::statement("ALTER TABLE nx_rma_requests MODIFY resolution_type ENUM('repaired','replaced','rejected') NULL");
        }
    }
};
