<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('nx_products', function (Blueprint $table) {
            $table->boolean('is_serialized')->default(false)->after('image_path');
            $table->boolean('is_web_published')->default(false)->after('is_serialized');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('nx_products', function (Blueprint $table) {
            $table->dropColumn(['is_serialized', 'is_web_published']);
        });
    }

};
