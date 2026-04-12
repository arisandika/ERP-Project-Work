<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('nx_payments', 'status')) {
                $table->string('status')->default('paid')->nullable();
            }

            if (!Schema::hasColumn('nx_payments', 'gateway_provider')) {
                $table->string('gateway_provider')->nullable();
            }

            if (!Schema::hasColumn('nx_payments', 'gateway_transaction_id')) {
                $table->string('gateway_transaction_id')->nullable();
            }

            if (!Schema::hasColumn('nx_payments', 'gateway_reference')) {
                $table->string('gateway_reference')->nullable();
            }

            if (!Schema::hasColumn('nx_payments', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }

            if (!Schema::hasColumn('nx_payments', 'paid_by_name')) {
                $table->string('paid_by_name')->nullable();
            }

            if (!Schema::hasColumn('nx_payments', 'paid_by_email')) {
                $table->string('paid_by_email')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('nx_payments', function (Blueprint $table) {
            $columns = [
                'status',
                'gateway_provider',
                'gateway_transaction_id',
                'gateway_reference',
                'created_by',
                'paid_by_name',
                'paid_by_email',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('nx_payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
