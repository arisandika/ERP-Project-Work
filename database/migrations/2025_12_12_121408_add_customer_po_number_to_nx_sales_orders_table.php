<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('nx_sales_orders', function (Blueprint $table) {
            // Tambahkan kolom PO Customer setelah order_number (agar rapi)
            // Nullable karena order via WA tidak punya PO
            $table->string('customer_po_number', 50)->nullable()->after('order_number');
        });
    }

    public function down()
    {
        Schema::table('nx_sales_orders', function (Blueprint $table) {
            $table->dropColumn('customer_po_number');
        });
    }

};
