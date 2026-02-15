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
        Schema::create('nx_project_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('nx_projects')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('nx_employees')->cascadeOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->date('note_date');
            $table->timestamps();

            $table->index(['project_id', 'note_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_project_notes');
    }
};
