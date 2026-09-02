<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The PerformanceEvaluation model uses SoftDeletes but the original
 * create-table migration omitted the deleted_at column, causing queries
 * against nx_performance_evaluations to fail with "Unknown column
 * nx_performance_evaluations.deleted_at". Adds the column to match the model.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('nx_performance_evaluations', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('nx_performance_evaluations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
