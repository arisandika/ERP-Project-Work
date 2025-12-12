<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_quotation_items', function (Blueprint $table) {
            // Tambahkan kolom ini jika belum ada
            if (!Schema::hasColumn('nx_quotation_items', 'item_name')) {
                $table->string('item_name')->after('item_id');
            }
            if (!Schema::hasColumn('nx_quotation_items', 'item_code')) {
                $table->string('item_code')->nullable()->after('item_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nx_quotation_items', function (Blueprint $table) {
            $table->dropColumn(['item_name', 'item_code']);
        });
    }
};
