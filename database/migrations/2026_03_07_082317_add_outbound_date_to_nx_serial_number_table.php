<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nx_serial_number', function (Blueprint $table) {
            $table->date('outbound_date')->nullable()->after('inbound_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_serial_number', function (Blueprint $table) {
            $table->dropColumn('outbound_date');
        });
    }
};
