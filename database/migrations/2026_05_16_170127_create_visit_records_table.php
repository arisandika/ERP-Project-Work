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
        Schema::create('nx_visit_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('nx_visit_assignment_id')
                ->constrained('nx_visit_assignments')
                ->cascadeOnDelete();

            // Waktu kunjungan direkam
            $table->timestamp('visited_at');

            // Koordinat GPS
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('location_address')->nullable()->comment('Dari reverse geocode atau diisi manual');

            // Isi rekaman
            $table->text('description')->comment('Deskripsi kegiatan kunjungan');

            $table->enum('visit_result', [
                'pending',
                'interested',
                'need_followup',
                'not_interested',
                'deal_progressed',
                'failed',
            ])->default('pending');

            $table->date('next_followup_date')->nullable();
            $table->text('followup_notes')->nullable();

            // Urutan kunjungan ke-1, ke-2, dst dalam satu assignment
            $table->unsignedSmallInteger('visit_order')->default(1);

            $table->softDeletes();
            $table->timestamps();

            // Index untuk query umum
            $table->index('nx_visit_assignment_id');
            $table->index('visited_at');
            $table->index('visit_result');

            // Kombinasi untuk query "kunjungan ke-N dari assignment ini"
            $table->unique(['nx_visit_assignment_id', 'visit_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_visit_records');
    }
};
