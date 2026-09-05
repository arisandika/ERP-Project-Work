<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * received_date awalnya NOT NULL karena dulu status awal 'received'
     * (barang selalu sudah diterima). Sekarang status awal 'submitted' —
     * barang belum diterima, jadi received_date boleh kosong sampai
     * aksi "receive from client" dijalankan.
     */
    public function up(): void
    {
        Schema::table('nx_rma_requests', function (Blueprint $table) {
            $table->date('received_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('nx_rma_requests', function (Blueprint $table) {
            $table->date('received_date')->nullable(false)->change();
        });
    }
};