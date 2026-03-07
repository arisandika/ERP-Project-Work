<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_serial_number', function (Blueprint $table) {
            $table->string('status', 50)->default('AVAILABLE')->change();
        });
    }

    public function down(): void
    {
        Schema::table('nx_serial_number', function (Blueprint $table) {
            $table->string('status', 255)->change();
        });
    }
};