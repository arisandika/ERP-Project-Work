<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds role column to nx_project_members pivot to support
 * per-project Role/PIC tracking for Employee Performance.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('nx_project_members', function (Blueprint $table) {
            $table->string('role', 64)
                ->nullable()
                ->default(null)
                ->comment('e.g. PIC, Developer, Designer, QA, Support')
                ->after('employee_id');

            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('nx_project_members', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
