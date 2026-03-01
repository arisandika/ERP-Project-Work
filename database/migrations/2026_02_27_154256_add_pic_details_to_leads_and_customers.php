<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menggunakan pola Defensive Migration untuk mencegah error duplikasi kolom.
     */
    public function up(): void
    {
        // 1. Update tabel nx_leads
        Schema::table('nx_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('nx_leads', 'pic_name')) {
                $table->string('pic_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('nx_leads', 'pic_email')) {
                $table->string('pic_email')->nullable()->after('pic_name');
            }
            if (!Schema::hasColumn('nx_leads', 'pic_phone')) {
                $table->string('pic_phone')->nullable()->after('pic_email');
            }
            if (!Schema::hasColumn('nx_leads', 'pic_position')) {
                $table->string('pic_position')->nullable()->after('pic_phone');
            }
        });

        // 2. Update tabel nx_customers agar sinkron
        Schema::table('nx_customers', function (Blueprint $table) {
            if (!Schema::hasColumn('nx_customers', 'pic_name')) {
                $table->string('pic_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('nx_customers', 'pic_email')) {
                $table->string('pic_email')->nullable()->after('pic_name');
            }
            if (!Schema::hasColumn('nx_customers', 'pic_phone')) {
                $table->string('pic_phone')->nullable()->after('pic_email');
            }

            if (!Schema::hasColumn('nx_customers', 'pic_position')) {
                $table->string('pic_position')->nullable()->after('pic_phone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_leads', function (Blueprint $table) {
            $columns = ['pic_name', 'pic_email', 'pic_phone', 'pic_position'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('nx_leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('nx_customers', function (Blueprint $table) {
            $columns = ['pic_name', 'pic_email', 'pic_phone', 'pic_position'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('nx_customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
