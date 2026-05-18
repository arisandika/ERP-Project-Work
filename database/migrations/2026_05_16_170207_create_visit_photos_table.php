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
        Schema::create('nx_visit_photos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('nx_visit_record_id')
                ->constrained('nx_visit_records')
                ->cascadeOnDelete();

            $table->string('file_path')->comment('Path file di storage');

            $table->enum('photo_type', [
                'documentation',
                'evidence',
                'location',
                'other',
            ])->default('documentation');

            // Koordinat GPS saat foto diambil (bisa beda dengan koordinat record)
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Waktu foto diambil (dari server time saat upload, bukan EXIF agar konsisten)
            $table->timestamp('taken_at')->nullable();

            $table->string('caption')->nullable();

            $table->timestamps();

            $table->index('nx_visit_record_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_visit_photos');
    }
};
