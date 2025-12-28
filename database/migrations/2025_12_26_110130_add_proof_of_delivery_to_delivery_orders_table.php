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
        Schema::table('nx_delivery_orders', function (Blueprint $table) {
            $table->string('proof_image')->nullable()->after('notes');
            $table->text('proof_notes')->nullable()->after('proof_image');
        });
    }

    public function down(): void
    {
        Schema::table('nx_delivery_orders', function (Blueprint $table) {
            $table->dropColumn(['proof_image', 'proof_notes']);
        });
    }

};
