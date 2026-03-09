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
        Schema::create('nx_web_contents', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Judul internal untuk admin

            // Konfigurasi Tampilan
            $table->enum('type', ['image', 'text'])->default('image');
            $table->string('image_path')->nullable(); // Jika type = image
            $table->text('content_text')->nullable(); // Jika type = text

            // Call to Action (Opsional, jika banner diklik)
            $table->string('cta_url')->nullable();
            $table->string('cta_text')->nullable();

            // Jadwal & Status
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->boolean('is_active')->default(true); // Switch on/off manual

            // Tracking sederhana
            $table->integer('click_count')->default(0);
            $table->integer('view_count')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_web_contents');
    }
};
