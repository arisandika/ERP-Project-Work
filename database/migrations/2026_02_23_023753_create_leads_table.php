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
        Schema::create('nx_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();

            $table->enum('customer_type', ['individual','company']);
            
            $table->string('nik', 16)->nullable();

            $table->string('npwp', 30)->nullable();

            // Data PIC
            $table->string('pic_name')->nullable();
            $table->string('pic_position')->nullable();
            $table->string('pic_phone', 20)->nullable();

            $table->string('source')->nullable(); // website, manual, event
            $table->string('status')->default('new'); // new, contacted, qualified, converted, lost

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_leads');
    }
};
