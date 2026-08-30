<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_customer_portal_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('nx_invoices')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('nx_customers')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->integer('max_uses')->default(5);
            $table->integer('usage_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_customer_portal_tokens');
    }
};
