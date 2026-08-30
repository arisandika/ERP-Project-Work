<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds supervisor_id to nx_employees to support Employee Performance feature.
 * A supervisor can view all employees reporting to them (transitively via department default).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('nx_employees', function (Blueprint $table) {
            $table->foreignId('supervisor_id')
                ->nullable()
                ->constrained('nx_employees')
                ->nullOnDelete()
                ->after('is_banned');

            $table->index('supervisor_id');
        });
    }

    public function down(): void
    {
        Schema::table('nx_employees', function (Blueprint $table) {
            $table->dropIndex(['supervisor_id']);
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn('supervisor_id');
        });
    }
};
