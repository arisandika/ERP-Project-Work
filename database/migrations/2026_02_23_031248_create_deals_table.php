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
        Schema::create('nx_deals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('nx_lead_id')
                ->constrained('nx_leads')
                ->cascadeOnDelete();

            $table->foreignId('nx_quotation_id')
                ->constrained('nx_quotations')
                ->cascadeOnDelete();
                
            $table->string('status')->default('open'); // open, won, lost, converted

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_deals');
    }
};
