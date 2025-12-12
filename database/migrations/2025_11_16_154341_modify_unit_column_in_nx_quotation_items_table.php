<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_quotation_items', function (Blueprint $table) {
            // Hapus kolom 'unit' yang lama (jika sudah ada)
            // Jika kolom ini belum dibuat, hapus baris ini
            $table->dropColumn('unit');

            // Tambahkan kolom 'unit_id' yang baru sebagai foreign key
            $table->foreignId('unit_id')
                  ->nullable()
                  ->after('qty') // Posisikan setelah kolom qty
                  ->constrained('nx_units') // Membuat relasi ke tabel nx_units
                  ->onDelete('set null'); // Jika satuan dihapus, jadikan null
        });
    }

    public function down(): void
    {
        Schema::table('nx_quotation_items', function (Blueprint $table) {
            // Urutan drop harus: foreign key dulu, baru kolom
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');

            // Kembalikan kolom 'unit' yang lama jika di-rollback
            $table->string('unit')->nullable()->after('qty');
        });
    }
};
