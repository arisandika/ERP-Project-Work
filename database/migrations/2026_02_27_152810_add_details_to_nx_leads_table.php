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
        Schema::table('nx_leads', function (Blueprint $table) {
            $table->string('pic_name')->nullable()->after('name');
            $table->string('pic_phone')->nullable()->after('phone');
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_leads', function (Blueprint $table) {
            $table->dropColumn(['pic_name', 'pic_phone']);
        });
    }
};
