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
        // Drift perbaikan: tidak ada migrasi yang pernah menambahkan kolom
        // product_code, padahal model Product meng-generate-nya di booted creating.
        // Pada fresh migrate (mis. SQLite in-memory untuk test) kolom ini tidak ada
        // sehingga INSERT produk gagal & index unik menunjuk ke kolom yang hilang.
        if (! Schema::hasColumn('nx_products', 'product_code')) {
            Schema::table('nx_products', function (Blueprint $table) {
                $table->string('product_code', 255)->nullable()->after('product_name');
            });
        }

        // MySQL memvalidasi index saat CREATE → prod pasti sudah punya kolom ini,
        // sehingga guard tidak merusak apapun; SQLite fresh baru dibuat di sini.
        if (Schema::hasColumn('nx_products', 'product_code')) {
            Schema::table('nx_products', function (Blueprint $table) {
                $table->unique('product_code', 'nx_products_product_code_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_products', function (Blueprint $table) {
            if (Schema::hasColumn('nx_products', 'product_code')) {
                $table->dropUnique('nx_products_product_code_unique');
            }
        });

        Schema::table('nx_products', function (Blueprint $table) {
            if (Schema::hasColumn('nx_products', 'product_code')) {
                $table->dropColumn('product_code');
            }
        });
    }
};
