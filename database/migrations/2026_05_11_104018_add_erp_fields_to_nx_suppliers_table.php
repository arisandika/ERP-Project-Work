<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_suppliers', function (Blueprint $table) {
            // Menambahkan kode supplier unik setelah kolom ID
            $table->string('code')->unique()->nullable()->after('id');

            // Flag untuk membedakan badan usaha atau individu
            $table->boolean('is_company')->default(true)->after('name');

            // NPWP / Tax ID untuk keperluan modul Akuntansi
            $table->string('tax_id')->nullable()->after('email');

            // Status untuk kontrol transaksi (hanya yang active yang bisa buat PO)
            $table->enum('status', ['active', 'inactive', 'blacklisted'])
                  ->default('active')
                  ->after('address');

            // SoftDeletes agar data tidak benar-benar hilang saat dihapus (Audit Trail)
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('nx_suppliers', function (Blueprint $table) {
            $table->dropColumn(['code', 'is_company', 'tax_id', 'status']);
            $table->dropSoftDeletes();
        });
    }
};
