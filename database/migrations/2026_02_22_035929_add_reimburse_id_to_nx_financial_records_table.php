<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nx_financial_records', function (Blueprint $table) {

            $table->foreignId('reimburse_id')
                ->nullable()
                ->after('id')
                ->constrained('nx_reimbursements')
                ->nullOnDelete();

        });
    }

    public function down(): void
    {
        Schema::table('nx_financial_records', function (Blueprint $table) {

            $table->dropForeign(['reimburse_id']);
            $table->dropColumn('reimburse_id');

        });
    }
};
