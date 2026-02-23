<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_deals', function (Blueprint $table) {
            // nullable() karena gak semua deal langsung ada file-nya
            $table->json('attachments')->nullable()->after('notes');
            $table->string('lost_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('nx_deals', function (Blueprint $table) {
            $table->dropColumn(['attachments', 'lost_reason']);
        });
    }
};
