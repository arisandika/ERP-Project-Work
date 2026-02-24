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
        Schema::create('nx_deal_stages', function (Blueprint $table) {
            $table->id();

            $table->string('name',100);

            $table->unsignedInteger('order');

            $table->unsignedTinyInteger('probability');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_deal_stages');
    }
};
