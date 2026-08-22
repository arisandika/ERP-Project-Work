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
        Schema::table('nx_customers', function (Blueprint $table) {
            $table->unsignedTinyInteger('portal_login_attempts')->default(0)->after('status');
            $table->timestamp('portal_locked_until')->nullable()->after('portal_login_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('nx_customers', function (Blueprint $table) {
            $table->dropColumn(['portal_login_attempts', 'portal_locked_until']);
        });
    }
};
