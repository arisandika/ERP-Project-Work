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
        Schema::table('nx_deals', function (Blueprint $table) {
            $table->unsignedBigInteger('nx_customer_id');

            $table->unsignedBigInteger('nx_lead_id')
                ->nullable();

            $table->unsignedBigInteger('nx_deal_stage_id');

            $table->string('deal_number', 50)
                ->unique();

            $table->dateTime('deal_date');

            $table->decimal('estimated_value', 15, 2)
                ->default(0);

            $table->enum('status', [
                'open',
                'won',
                'lost'
            ])->default('open');

            $table->dateTime('close_date')
                ->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('nx_customer_id')
                ->references('id')
                ->on('nx_customers');

            $table->foreign('nx_lead_id')
                ->references('id')
                ->on('nx_leads');

            $table->foreign('nx_deal_stage_id')
                ->references('id')
                ->on('nx_deal_stages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
