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
        Schema::create('nx_sales_people', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();

            // FK ke nx_employees.id
            $table->foreignId('employee_id')
                ->constrained('nx_employees', 'id')
                ->onDelete('cascade');

            $table->decimal('sales_target', 15, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->softDeletes();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_sales_people');
    }
};
