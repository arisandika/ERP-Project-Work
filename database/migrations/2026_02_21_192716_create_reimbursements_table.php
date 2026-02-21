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
        Schema::create('nx_reimbursements', function (Blueprint $table) {
            $table->id();

            // Relasi ke karyawan
            $table->foreignId('employee_id')
                ->constrained('nx_employees')
                ->cascadeOnDelete();

            // Tanggal transaksi
            $table->date('date');

            // Jenis reimburse (flexible)
            $table->string('type', 100);

            // Nominal
            $table->decimal('amount', 12, 2);

            // Deskripsi tambahan
            $table->text('description')->nullable();

            // Upload bukti transaksi
            $table->string('receipt');

            // Status approval
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Approval
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('nx_employees')
                ->nullOnDelete();

            $table->timestamp('approved_at')
                ->nullable();

            // Relasi ke finance
            $table->foreignId('financial_record_id')
                ->nullable()
                ->constrained('nx_financial_records')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_reimbursements');
    }
};
