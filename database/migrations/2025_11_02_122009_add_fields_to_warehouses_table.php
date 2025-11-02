<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_warehouses', function (Blueprint $table) {
            $table->string('manager_name', 100)->nullable()->after('location');
            $table->string('phone', 20)->nullable()->after('manager_name');
            $table->boolean('is_active')->default(true)->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('nx_warehouses', function (Blueprint $table) {
            $table->dropColumn(['manager_name', 'phone', 'is_active']);
        });
    }
};
