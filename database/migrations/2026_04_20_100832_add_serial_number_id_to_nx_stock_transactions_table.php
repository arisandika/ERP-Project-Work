<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_stock_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('serial_number_id')
                ->nullable()
                ->after('warehouse_id');

            $table->foreign('serial_number_id', 'nx_stock_transactions_serial_number_id_foreign')
                ->references('id')
                ->on('nx_serial_number')
                ->nullOnDelete();

            $table->index('serial_number_id', 'nx_stock_transactions_serial_number_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('nx_stock_transactions', function (Blueprint $table) {
            $table->dropIndex('nx_stock_transactions_serial_number_id_index');
            $table->dropForeign('nx_stock_transactions_serial_number_id_foreign');
            $table->dropColumn('serial_number_id');
        });
    }
};
