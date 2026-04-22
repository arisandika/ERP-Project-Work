<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nx_deals', function (Blueprint $table) {
            $table->foreignId('created_by')
                ->nullable()
                ->after('close_date');

            $table->foreign('created_by', 'fk_deals_created_by_employee')
                ->references('id')
                ->on('nx_employees')
                ->nullOnDelete();

            $table->timestamp('closed_at')
                ->nullable()
                ->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('nx_deals', function (Blueprint $table) {
            try {
                $table->dropForeign('fk_deals_created_by_employee');
            } catch (\Throwable $e) {
            }

            $table->dropColumn(['created_by', 'closed_at']);
        });
    }
};
