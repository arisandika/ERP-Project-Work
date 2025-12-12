<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_quotations', function (Blueprint $table) {
            // Hapus kolom discount dan tax
            $table->dropColumn(['discount', 'tax']);
        });
    }

    public function down(): void
    {
        Schema::table('nx_quotations', function (Blueprint $table) {
            // Jika rollback, tambahkan lagi kolom discount dan tax
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
        });
    }
};
