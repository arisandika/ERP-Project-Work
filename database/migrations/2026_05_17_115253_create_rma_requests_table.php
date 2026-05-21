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
        Schema::create('nx_rma_requests', function (Blueprint $table) {
            $table->id();
            $table->string('rma_number')->unique();
            $table->foreignId('customer_id')->constrained('nx_customers');
            $table->foreignId('serial_number_id')->constrained('nx_serial_number');

            // Status RMA (Sesuai 4 Step lu)
            $table->enum('status', ['received', 'sent_to_vendor', 'ready_from_vendor', 'returned_to_client', 'rejected'])->default('received');

            $table->text('issue_description'); // Keluhan pelanggan
            $table->text('vendor_notes')->nullable(); // Catatan dari Service Center

            // Tracking Tanggal tiap Step
            $table->date('received_date');
            $table->date('sent_to_vendor_date')->nullable();
            $table->date('back_from_vendor_date')->nullable();
            $table->date('returned_to_client_date')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rma_requests');
    }
};
