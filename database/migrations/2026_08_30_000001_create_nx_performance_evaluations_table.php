<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nx_performance_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('nx_employees')
                ->cascadeOnDelete();
            $table->foreignId('evaluator_id')
                ->constrained('nx_employees')
                ->cascadeOnDelete();
            $table->string('period', 32)
                ->comment('e.g. 2025-Q4, 2025-12');
            $table->unsignedTinyInteger('rating')
                ->comment('1=Very Poor .. 5=Outstanding');
            $table->text('feedback')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'period', 'evaluator_id'], 'pe_unique');
            $table->index(['evaluator_id', 'period']);
            $table->index(['employee_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_performance_evaluations');
    }
};
