<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_quotations', function (Blueprint $table) {
            $table->boolean('is_primary')
                  ->default(false)
                  ->after('status')
                  ->comment('Hanya 1 quotation yang boleh is_primary per deal — di-enforce di application layer');

            $table->text('rejected_reason')
                  ->nullable()
                  ->after('is_primary')
                  ->comment('Diisi saat status = rejected');

            $table->timestamp('accepted_at')
                  ->nullable()
                  ->after('rejected_reason')
                  ->comment('Diisi otomatis saat status = accepted');
        });
    }

    public function down(): void
    {
        Schema::table('nx_quotations', function (Blueprint $table) {
            $table->dropColumn(['is_primary', 'rejected_reason', 'accepted_at']);
        });
    }
};
