<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nx_visit_records', function (Blueprint $table) {
            if (Schema::hasColumn('nx_visit_records', 'internal_note')) {
                $table->dropColumn('internal_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nx_visit_records', function (Blueprint $table) {
            if (! Schema::hasColumn('nx_visit_records', 'internal_note')) {
                $table->text('internal_note')->nullable()->after('followup_notes');
            }
        });
    }
};
