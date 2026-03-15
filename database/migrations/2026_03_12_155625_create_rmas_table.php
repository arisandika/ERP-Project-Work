<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_rmas', function (Blueprint $table) {
            $table->id();
            // Sesuaikan nama tabel referensi dengan yang ada di database (nx_serial_number)
            $table->foreignId('nx_serial_number_id')->constrained('nx_serial_number');
            $table->string('status');
            $table->text('client_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Sesuaikan nama tabel yang akan di-drop
        Schema::dropIfExists('nx_rmas');
    }
};
