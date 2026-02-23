<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_deals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained('nx_customers')
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('status')->default('proposal');
            $table->decimal('value', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Optional: kalau sering filter reminder by date
            $table->index(['status', 'next_follow_up_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_deals');
    }
};
