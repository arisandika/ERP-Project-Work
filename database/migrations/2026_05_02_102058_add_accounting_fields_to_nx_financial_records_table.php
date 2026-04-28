<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_financial_records', function (Blueprint $table) {
            if (! Schema::hasColumn('nx_financial_records', 'account_type')) {
                $table->string('account_type')->nullable()->after('category')->index();
            }

            if (! Schema::hasColumn('nx_financial_records', 'cash_flow_activity')) {
                $table->string('cash_flow_activity')->nullable()->after('account_type')->index();
            }

            if (! Schema::hasColumn('nx_financial_records', 'normal_balance')) {
                $table->string('normal_balance')->nullable()->after('cash_flow_activity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nx_financial_records', function (Blueprint $table) {
            if (Schema::hasColumn('nx_financial_records', 'normal_balance')) {
                $table->dropColumn('normal_balance');
            }

            if (Schema::hasColumn('nx_financial_records', 'cash_flow_activity')) {
                $table->dropColumn('cash_flow_activity');
            }

            if (Schema::hasColumn('nx_financial_records', 'account_type')) {
                $table->dropColumn('account_type');
            }
        });
    }
};
