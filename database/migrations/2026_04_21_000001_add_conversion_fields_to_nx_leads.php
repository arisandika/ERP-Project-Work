<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nx_leads', function (Blueprint $table) {
            $table->foreignId('created_by')
                ->nullable()
                ->after('notes');

            $table->foreign('created_by', 'fk_leads_created_by_employee')
                ->references('id')
                ->on('nx_employees')
                ->nullOnDelete();

            $table->foreignId('converted_by')
                ->nullable()
                ->after('converted_customer_id');

            $table->foreign('converted_by', 'fk_leads_converted_by_employee')
                ->references('id')
                ->on('nx_employees')
                ->nullOnDelete();

            $table->timestamp('converted_at')
                ->nullable()
                ->after('converted_by');
        });
    }

    public function down(): void
    {
        Schema::table('nx_leads', function (Blueprint $table) {
            try {
                $table->dropForeign('fk_leads_created_by_employee');
            } catch (\Throwable $e) {
            }

            try {
                $table->dropForeign('fk_leads_converted_by_employee');
            } catch (\Throwable $e) {
            }

            $table->dropColumn(['created_by', 'converted_by', 'converted_at']);
        });
    }
};
