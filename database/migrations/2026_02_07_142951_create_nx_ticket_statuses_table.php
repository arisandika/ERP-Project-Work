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
        Schema::create('nx_ticket_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('nx_projects')->cascadeOnDelete();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->string('color')->default('#3490dc');
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
            $table->index(['project_id', 'is_completed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_ticket_statuses');
    }
};
