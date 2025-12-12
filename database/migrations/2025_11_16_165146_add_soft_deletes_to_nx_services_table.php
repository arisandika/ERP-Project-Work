<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_services', function (Blueprint $table) {
            // Perintah ini akan menambahkan kolom 'deleted_at'
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('nx_services', function (Blueprint $table) {
            // Perintah ini akan menghapus kolom 'deleted_at' jika di-rollback
            $table->dropSoftDeletes();
        });
    }
};
