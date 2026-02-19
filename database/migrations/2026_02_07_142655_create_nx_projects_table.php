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
        Schema::create('nx_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('ticket_prefix');
            $table->string('color', 7)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('pinned_date')->nullable();

            // invoice relation
            $table->unsignedBigInteger('nx_invoice_id')->nullable()->index();
            $table->string('sales_invoice_number', 255)->nullable();

            // foreign keys
            $table->foreign('nx_invoice_id')
                ->references('id')
                ->on('nx_invoices')
                ->onDelete('set null');

            $table->timestamps();
            
            $table->index('pinned_date');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_projects');
    }
};
