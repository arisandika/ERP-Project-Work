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
        // Benahi schema drift: create 2026_02_20_222735 sudah punya kolom
        // status (& timestamps). Ulang-add tanpa guard → duplicate column pada
        // fresh migrate (SQLite). Kolom lain di bawah asli dari refactor ini.
        Schema::table('nx_deals', function (Blueprint $table) {
            if (Schema::hasColumn('nx_deals', 'nx_customer_id')) {
                return;
            }

            $table->unsignedBigInteger('nx_customer_id');

            $table->unsignedBigInteger('nx_lead_id')
                ->nullable();

            $table->unsignedBigInteger('nx_deal_stage_id');

            $table->string('deal_number', 50)
                ->unique();

            $table->dateTime('deal_date');

            $table->decimal('estimated_value', 15, 2)
                ->default(0);

            // status sudah dibuat create migration (varchar default 'proposal');
            // DI SINI DI-SKIP agar tidak duplikat. Kolom varchar tidak punya
            // check constraint sehingga nilainya fleksibel di semua DB engine.
            if (! Schema::hasColumn('nx_deals', 'status')) {
                $table->enum('status', ['open', 'won', 'lost'])->default('open');
            }

            $table->dateTime('close_date')
                ->nullable();

            if (! Schema::hasColumn('nx_deals', 'created_at')) {
                $table->timestamps();
            }

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
