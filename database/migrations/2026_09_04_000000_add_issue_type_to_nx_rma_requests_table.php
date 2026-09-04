<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_rma_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('nx_rma_requests', 'issue_type')) {
                $table->string('issue_type', 50)->nullable()->after('issue_description')
                    ->comment('Customer-facing problem category: damaged, not_working, wrong_item, damaged_shipping, incomplete, other');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nx_rma_requests', function (Blueprint $table) {
            if (Schema::hasColumn('nx_rma_requests', 'issue_type')) {
                $table->dropColumn(['issue_type']);
            }
        });
    }
};
