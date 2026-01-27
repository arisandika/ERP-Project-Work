<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_quotation_items', function (Blueprint $table) {
            $table->decimal('cost_price', 15, 2)->default(0)->after('unit_price')
                ->comment('Snapshot harga beli saat quotation dibuat');
        });
    }

    public function down(): void
    {
        Schema::table('nx_quotation_items', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
