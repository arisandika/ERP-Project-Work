<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_quotations', function (Blueprint $table) {
            // Tambahkan nullable karena bisa jadi Quotation dibuat langsung tanpa lewat CRM
            $table->foreignId('nx_deal_id')->nullable()->constrained('nx_deals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('nx_quotations', function (Blueprint $table) {
            $table->dropForeign(['nx_deal_id']);
            $table->dropColumn('nx_deal_id');
        });
    }
};
