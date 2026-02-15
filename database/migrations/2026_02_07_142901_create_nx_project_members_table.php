<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nx_project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('nx_projects')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('nx_employees')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'employee_id']);
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_project_members');
    }
};
