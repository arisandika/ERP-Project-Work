<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nx_visit_records', function (Blueprint $table) {
            $table->timestamp('check_in_at')->nullable()->after('visited_at');
            $table->timestamp('check_out_at')->nullable()->after('check_in_at');
            $table->integer('duration_minutes')->nullable()->after('check_out_at');
            // Make description nullable so check-in can be saved with an empty description
            $table->text('description')->nullable()->change();
            // Contextual tracking fields
            $table->string('visit_purpose', 100)->nullable()->after('location_address');
            $table->unsignedBigInteger('nx_project_id')->nullable()->after('visit_purpose');
            $table->text('internal_note')->nullable()->after('followup_notes');
        });
    }

    public function down(): void
    {
        Schema::table('nx_visit_records', function (Blueprint $table) {
            $table->dropColumn(['check_in_at', 'check_out_at', 'duration_minutes']);
        });
    }
};
