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
        Schema::table('nx_leads', function (Blueprint $table) {
            $table->string('name', 150);
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();

            $table->text('address')->nullable();

            $table->enum('customer_type', [
                'individual',
                'company'
            ]);

            $table->string('source', 100)->nullable();

            $table->enum('status', [
                'new',
                'contacted',
                'qualified',
                'converted',
                'lost'
            ])->default('new');

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('converted_customer_id')
                ->nullable();

            $table->timestamps();
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
