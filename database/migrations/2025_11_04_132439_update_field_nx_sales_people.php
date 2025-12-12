<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Ubah kolom employee_id jadi nullable (drop FK dulu jika perlu)
        Schema::table('nx_sales_people', function (Blueprint $table) {
            // Jika FK bernama otomatis, biasanya: nx_sales_people_employee_id_foreign
            $table->dropForeign(['employee_id']);
        });

        Schema::table('nx_sales_people', function (Blueprint $table) {
            // Jadikan nullable
            $table->foreignId('employee_id')->nullable()->change();
        });

        Schema::table('nx_sales_people', function (Blueprint $table) {
            // Tambah lagi constraint FK dengan nullOnDelete (opsional: cascade)
            $table->foreign('employee_id')->references('id')->on('nx_employees')->nullOnDelete();

            // 2) Tambah tipe identitas
            $table->enum('type', ['internal', 'external'])->default('internal')->after('id');

            // 3) Tambah kolom identitas lokal (dipakai untuk external atau tampilan cepat)
            $table->string('full_name', 150)->nullable()->after('employee_id');
            $table->string('email', 150)->nullable()->index();
            $table->string('phone', 50)->nullable();

            // 4) Index pendukung
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('nx_sales_people', function (Blueprint $table) {
            // Rollback tambahan
            $table->dropIndex(['type', 'status']);
            $table->dropColumn(['type', 'full_name', 'email', 'phone']);

            // Kembalikan constraint employee_id non-nullable (hati-hati jika ada data NULL)
            $table->dropForeign(['employee_id']);
        });

        Schema::table('nx_sales_people', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable(false)->change();
        });

        Schema::table('nx_sales_people', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('nx_employees')->onDelete('cascade');
        });
    }
};
