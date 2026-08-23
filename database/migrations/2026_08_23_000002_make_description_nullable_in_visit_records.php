<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // description sudah nullable di migration 2026_08_22, tapi
        // DB yang sudah pernah dijalankan tidak ikut. Fix di sini.
        Schema::table('nx_visit_records', function (Blueprint $table) {
            if (Schema::hasColumn('nx_visit_records', 'description')) {
                $table->text('description')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('nx_visit_records', function (Blueprint $table) {
            $table->text('description')->nullable(false)->change();
        });
    }
};
