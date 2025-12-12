<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_delivery_order_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('nx_delivery_order_id')->index();

            // mirror dari sales_order_items
            $table->string('item_type', 255)->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('item_code', 255)->nullable();
            $table->string('item_name', 255)->nullable();

            // kuantitas: berapa yg dikirim
            $table->integer('qty')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // foreign key ke DO
            $table->foreign('nx_delivery_order_id')
                ->references('id')
                ->on('nx_delivery_orders')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_delivery_order_items');
    }
};
