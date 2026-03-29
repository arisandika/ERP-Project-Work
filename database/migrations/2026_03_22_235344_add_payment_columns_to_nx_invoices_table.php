<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_invoices', function (Blueprint $table) {
            // 1. Kolom fisik untuk menyimpan akumulasi pembayaran
            $table->decimal('total_paid', 15, 2)->default(0)->after('grand_total');

            // 2. Kolom virtual yang otomatis dihitung oleh engine MySQL (sangat optimal!)
            $table->decimal('remaining_balance', 15, 2)
                  ->storedAs('grand_total - total_paid')
                  ->after('total_paid');
        });
    }

    public function down(): void
    {
        Schema::table('nx_invoices', function (Blueprint $table) {
            $table->dropColumn(['total_paid', 'remaining_balance']);
        });
    }
};
