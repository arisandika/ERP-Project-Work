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
        Schema::table('nx_leaves', function (Blueprint $table) {
            $table->boolean('is_female_only')
                ->default(false)
                ->after('days_count')
                ->comment('Cuti khusus wanita');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_leaves', function (Blueprint $table) {
            $table->dropColumn('is_female_only');
        });
    }
};
