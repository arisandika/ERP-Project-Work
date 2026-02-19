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
        Schema::create('nx_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable()->unique();
            $table->foreignId('project_id')->constrained('nx_projects')->cascadeOnDelete();
            $table->foreignId('ticket_status_id')->constrained('nx_ticket_statuses')->cascadeOnDelete();
            $table->foreignId('priority_id')->nullable()->constrained('nx_ticket_priorities')->nullOnDelete();
            $table->foreignId('epic_id')->nullable()->constrained('nx_epics')->nullOnDelete();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('created_by')->nullable()
                ->constrained('nx_employees')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'ticket_status_id']);
            $table->index(['ticket_status_id', 'created_at']);
            $table->index(['project_id', 'created_at']);
            $table->index(['project_id', 'updated_at']);
            $table->index('due_date');
            $table->index('priority_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_tickets');
    }
};
