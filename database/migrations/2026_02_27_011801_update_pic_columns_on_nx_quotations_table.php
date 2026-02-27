<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Rename kolom lama
        Schema::table('nx_quotations', function (Blueprint $table) {
            $table->renameColumn('nx_employee_id', 'internal_pic_id');
        });

        // Step 2: Tambahkan kolom baru
        Schema::table('nx_quotations', function (Blueprint $table) {
            $table->unsignedBigInteger('field_staff_pic_id')->nullable()->after('internal_pic_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_quotations', function (Blueprint $table) {
            $table->dropColumn('field_staff_pic_id');
        });

        Schema::table('nx_quotations', function (Blueprint $table) {
            $table->renameColumn('internal_pic_id', 'nx_employee_id');
        });
    }
};
