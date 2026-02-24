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
        Schema::create('nx_sliders', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            
            // Konten Teks
            $table->string('title')->nullable(); // Heading utama
            $table->text('description')->nullable(); // Sub-heading
            
            // Konten Visual (Responsive)
            $table->string('image_desktop'); // Wajib
            $table->string('image_mobile')->nullable(); // Opsional, fallback ke desktop jika kosong
            
            // Call To Action (Tombol)
            $table->string('cta_text')->nullable(); // Tulisan di tombol, misal "Beli Sekarang"
            $table->string('cta_url')->nullable();  // Link tujuan
            $table->boolean('open_in_new_tab')->default(false);

            // Pengaturan
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_sliders');
    }
};
