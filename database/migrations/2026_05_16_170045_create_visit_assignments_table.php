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
        Schema::create('nx_visit_assignments', function (Blueprint $table) {
            $table->id();

            // Relasi ke Deal
            $table->foreignId('nx_deal_id')
                ->constrained('nx_deals')
                ->cascadeOnDelete();

            // Polymorphic assignee (Employee atau SalesPerson)
            $table->morphs('assigned_to'); // membuat assigned_to_id + assigned_to_type

            // Admin yang membuat tugas
            $table->foreignId('assigned_by')
                ->constrained('nx_employees')
                ->restrictOnDelete();

            // Info kunjungan
            $table->date('visit_date');
            $table->time('visit_time')->nullable();
            $table->date('deadline_date')->nullable();

            $table->enum('purpose', [
                'presentation',
                'follow_up',
                'survey',
                'negotiation',
                'closing',
                'other',
            ])->default('follow_up');

            $table->text('notes')->nullable()->comment('Briefing dari admin untuk pegawai');

            $table->enum('status', [
                'pending',
                'in_progress',
                'completed',
                'cancelled',
            ])->default('pending');

            $table->softDeletes();
            $table->timestamps();

            // Index untuk query umum
            $table->index('nx_deal_id');
            // $table->index(['assigned_to_type', 'assigned_to_id']);
            $table->index('status');
            $table->index('visit_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_visit_assignments');
    }
};
