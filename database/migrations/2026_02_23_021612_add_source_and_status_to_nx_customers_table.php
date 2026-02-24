<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nx_customers', function (Blueprint $table) {
            $table->string('source')
                ->after('customer_type')
                ->nullable();

            $table->string('status')
                ->after('source')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_customers', function (Blueprint $table) {
            $table->dropColumn(['source', 'status']);
        });
    }
};
