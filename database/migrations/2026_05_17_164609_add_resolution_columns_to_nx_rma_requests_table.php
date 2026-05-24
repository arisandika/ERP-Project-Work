<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_rma_requests', function (Blueprint $table) {

            // Pengecekan agar tidak error "Column already exists" jika sebelumnya nyangkut
            if (!Schema::hasColumn('nx_rma_requests', 'resolution_type')) {
                $table->enum('resolution_type', ['repaired', 'replaced', 'rejected'])
                      ->nullable()
                      ->after('returned_to_client_date')
                      ->comment('Hasil keputusan vendor/service center');
            }

            if (!Schema::hasColumn('nx_rma_requests', 'new_serial_number_id')) {

                // PENTING: Cek phpMyAdmin Anda. Jika tabel nx_serial_numbers menggunakan
                // tipe data INT untuk id-nya, gunakan ->unsignedInteger() di bawah ini.
                // Jika menggunakan BIGINT, gunakan ->unsignedBigInteger()

                $table->unsignedBigInteger('new_serial_number_id') // <-- Ubah ke unsignedInteger jika error lagi
                      ->nullable()
                      ->after('resolution_type');

                // Kita buat foreign key secara eksplisit (manual)
                $table->foreign('new_serial_number_id')
                      ->references('id')
                      ->on('nx_serial_numbers') // <-- PASTIKAN NAMA TABEL INI BENAR (Apakah pakai nx_ atau tidak?)
                      ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nx_rma_requests', function (Blueprint $table) {
            if (Schema::hasColumn('nx_rma_requests', 'new_serial_number_id')) {
                $table->dropForeign(['new_serial_number_id']);
                $table->dropColumn('new_serial_number_id');
            }

            if (Schema::hasColumn('nx_rma_requests', 'resolution_type')) {
                $table->dropColumn('resolution_type');
            }
        });
    }
};
