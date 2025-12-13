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
        // 1. Buat tabel master promo dulu
        Schema::create('nx_promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['fixed', 'percentage']);
            $table->decimal('value', 15, 2);

            // Validasi Status
            $table->boolean('is_active')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Validasi Kuota
            $table->integer('usage_limit')->nullable();
            $table->integer('times_used')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Baru alter tabel quotations
        Schema::table('nx_quotations', function (Blueprint $table) {
            $table->foreignId('promo_code_id')
                  ->nullable()
                  ->constrained('nx_promo_codes')
                  ->nullOnDelete();

            $table->decimal('discount_amount', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_quotations', function (Blueprint $table) {
            $table->dropForeign(['promo_code_id']);
            $table->dropColumn(['promo_code_id', 'discount_amount']);
        });

        Schema::dropIfExists('nx_promo_codes');
    }
};
