<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nx_visit_records', function (Blueprint $table) {
            if (! Schema::hasColumn('nx_visit_records', 'visit_purpose')) {
                $table->string('visit_purpose', 100)->nullable()->after('location_address');
            }
            if (! Schema::hasColumn('nx_visit_records', 'nx_project_id')) {
                $table->unsignedBigInteger('nx_project_id')->nullable()->after('visit_purpose');
            }
            if (! Schema::hasColumn('nx_visit_records', 'internal_note')) {
                $table->text('internal_note')->nullable()->after('followup_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nx_visit_records', function (Blueprint $table) {
            $table->dropColumn(['visit_purpose', 'nx_project_id', 'internal_note']);
        });
    }
};
