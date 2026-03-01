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
        Schema::table('nx_product_stock', function (Blueprint $table) {

            // Rename qty lama jadi qty_available
            $table->renameColumn('qty', 'qty_available');
        });

        Schema::table('nx_product_stock', function (Blueprint $table) {

            // Hapus status
            $table->dropColumn('status');

            // Tambah kolom baru
            $table->integer('qty_reserved')->default(0)->after('qty_available');
            $table->integer('qty_on_delivery')->default(0)->after('qty_reserved');
        });
    }

    /**
     * Reverse the migrations.
     */

    public function down()
    {
        Schema::table('nx_product_stock', function (Blueprint $table) {

            $table->renameColumn('qty_available', 'qty');

            $table->enum('status', ['available','reserved','out_of_stock'])->default('available');

            $table->dropColumn([
                'qty_reserved',
                'qty_on_delivery'
            ]);
        });
    }

};
