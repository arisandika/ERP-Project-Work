<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RMA refinement — pisahkan lifecycle InternalRepair dan VendorClaim
     * dari status ReturnRequest (spec poin 7 & 8). ReturnRequest hanya
     * menampung status induk (INTERNAL_REPAIR / SENT_TO_VENDOR); detail
     * lifecycle dipecah ke tabel terpisah.
     *
     * Sekaligus tambahkan refund_status di ReturnRequest: resolution=REFUND
     * bergerak REFUND_PENDING → (finance selesai) → READY_FOR_RETURN.
     */
    public function up(): void
    {
        Schema::create('nx_internal_repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rma_id')->constrained('nx_rma_requests')->cascadeOnDelete();
            $table->foreignId('technician_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending'); // pending / in_progress / completed
            $table->string('resolution_type', 30)->nullable(); // repair_and_return / replacement / no_fault_found
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('rma_id');
            $table->index('status');
        });

        Schema::create('nx_vendor_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rma_id')->constrained('nx_rma_requests')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('nx_suppliers')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('nx_purchase_orders')->nullOnDelete();
            $table->string('status', 20)->default('sent'); // sent / completed / rejected
            $table->string('resolution_type', 30)->nullable(); // repair_and_return / replacement
            $table->text('vendor_notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('rma_id');
            $table->index('status');
        });

        Schema::table('nx_rma_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('nx_rma_requests', 'refund_status')) {
                $table->string('refund_status', 30)->nullable()->after('warranty_decision')
                    ->comment('Lifecycle refund: pending (Finance belum selesai) / completed. Hanya relevan untuk resolution=refund');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nx_rma_requests', function (Blueprint $table) {
            if (Schema::hasColumn('nx_rma_requests', 'refund_status')) {
                $table->dropColumn('refund_status');
            }
        });

        Schema::dropIfExists('nx_vendor_claims');
        Schema::dropIfExists('nx_internal_repairs');
    }
};