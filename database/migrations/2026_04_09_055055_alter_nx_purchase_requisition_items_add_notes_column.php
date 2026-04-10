<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_purchase_requisition_items', function (Blueprint $table) {
            if (!Schema::hasColumn('nx_purchase_requisition_items', 'notes')) {
                $table->text('notes')->nullable()->after('estimated_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nx_purchase_requisition_items', function (Blueprint $table) {
            if (Schema::hasColumn('nx_purchase_requisition_items', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
