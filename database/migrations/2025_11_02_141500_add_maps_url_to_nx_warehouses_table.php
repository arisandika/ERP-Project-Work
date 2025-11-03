<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_warehouses', function (Blueprint $table) {
            $table->string('maps_url')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('nx_warehouses', function (Blueprint $table) {
            $table->dropColumn('maps_url');
        });
    }
};

