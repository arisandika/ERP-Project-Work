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
        // Departments (example: HR, IT, Finance)
        Schema::create('nx_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');      // Department name
            $table->string('code')->unique(); // Short code, e.g., "HR"
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_departments');
    }
};
