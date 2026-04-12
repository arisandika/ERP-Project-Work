<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_purchase_requisitions', function (Blueprint $table) {
            if (!Schema::hasColumn('nx_purchase_requisitions', 'status')) {
                $table->string('status')->default('draft')->after('pr_number');
            }

            if (!Schema::hasColumn('nx_purchase_requisitions', 'requested_by')) {
                $table->foreignId('requested_by')
                    ->nullable()
                    ->after('purpose')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('nx_purchase_requisitions', 'submitted_by')) {
                $table->foreignId('submitted_by')
                    ->nullable()
                    ->after('requested_by')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('nx_purchase_requisitions', 'submitted_at')) {
                $table->timestamp('submitted_at')
                    ->nullable()
                    ->after('submitted_by');
            }

            if (!Schema::hasColumn('nx_purchase_requisitions', 'approved_by')) {
                $table->foreignId('approved_by')
                    ->nullable()
                    ->after('submitted_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('nx_purchase_requisitions', 'approved_at')) {
                $table->timestamp('approved_at')
                    ->nullable()
                    ->after('approved_by');
            }

            if (!Schema::hasColumn('nx_purchase_requisitions', 'rejection_note')) {
                $table->text('rejection_note')
                    ->nullable()
                    ->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nx_purchase_requisitions', function (Blueprint $table) {
            if (Schema::hasColumn('nx_purchase_requisitions', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }

            if (Schema::hasColumn('nx_purchase_requisitions', 'submitted_by')) {
                $table->dropConstrainedForeignId('submitted_by');
            }

            if (Schema::hasColumn('nx_purchase_requisitions', 'requested_by')) {
                $table->dropConstrainedForeignId('requested_by');
            }

            if (Schema::hasColumn('nx_purchase_requisitions', 'rejection_note')) {
                $table->dropColumn('rejection_note');
            }

            if (Schema::hasColumn('nx_purchase_requisitions', 'approved_at')) {
                $table->dropColumn('approved_at');
            }

            if (Schema::hasColumn('nx_purchase_requisitions', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }

            if (Schema::hasColumn('nx_purchase_requisitions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
