<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_financial_records', function (Blueprint $table) {
            // Menambahkan reference_number (Misal: PO-2026-001)
            $table->string('reference_number')->nullable()->after('category');

            // MAGIC: Ini akan otomatis membuat 2 kolom: reference_type (varchar) & reference_id (bigint)
            $table->nullableMorphs('reference');
        });
    }

    public function down(): void
    {
        Schema::table('nx_financial_records', function (Blueprint $table) {
            $table->dropColumn('reference_number');
            $table->dropMorphs('reference');
        });
    }
};
