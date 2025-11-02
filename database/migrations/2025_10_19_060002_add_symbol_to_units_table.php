<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_units', function (Blueprint $table) {
            $table->string('symbol', 10)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('nx_units', function (Blueprint $table) {
            $table->dropColumn('symbol');
        });
    }
};

