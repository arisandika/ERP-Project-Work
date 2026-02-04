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
        Schema::table('nx_stock_transactions', function (Blueprint $table) {
            $table->string('transaction_code', 50)
                ->nullable()
                ->after('id');
            $table->string('no_reference', 50)
                ->nullable()
                ->after('transaction_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_stock_transactions', function (Blueprint $table) {
            $table->dropColumn('transaction_code');
            $table->dropColumn('no_reference');
        });
    }
};
