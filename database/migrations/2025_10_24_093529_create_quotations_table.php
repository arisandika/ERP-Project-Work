<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_quotations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('approved_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->foreignId('nx_customer_id')
                ->constrained('nx_customers')->cascadeOnDelete();

            $table->foreignId('nx_sales_people_id')
                ->constrained('nx_sales_people')->cascadeOnDelete();

            $table->string('quotation_number')->unique();
            $table->date('quotation_date')->nullable();
            $table->date('valid_until')->nullable();

            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected'])->default('draft');
            $table->text('notes')->nullable();

            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['quotation_number', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_quotations');
    }
};
