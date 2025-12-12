<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nx_quotation_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('nx_quotation_id')
                ->constrained('nx_quotations')
                ->cascadeOnDelete();

            // morphs kolom: item_type, item_id untuk polymorphic relation
            $table->morphs('item');

            $table->string('description')->nullable();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->string('unit')->nullable();

            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);

            $table->unsignedInteger('sort')->default(0)->index();

            $table->json('extra_attributes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['nx_quotation_id', 'sort']);
            // morphs sudah membuat index gabungan item_type + item_id
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_quotation_items');
    }
};
