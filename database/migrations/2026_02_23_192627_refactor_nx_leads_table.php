<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Benahi schema drift: hampir semua kolom sudah dibuat oleh
     * 2026_02_23_023753_create_leads_table. Ulang-add tanpa guard →
     * "duplicate column name" pada fresh migrate (SQLite). Hanya
     * converted_customer_id yang benar-benar baru.
     */
    public function up(): void
    {
        $add = function (string $col, callable $build) {
            if (! Schema::hasColumn('nx_leads', $col)) {
                Schema::table('nx_leads', fn (Blueprint $table) => $build($table));
            }
        };

        $add('name', fn ($t) => $t->string('name', 150));
        $add('email', fn ($t) => $t->string('email', 150)->nullable());
        $add('phone', fn ($t) => $t->string('phone', 30)->nullable());
        $add('address', fn ($t) => $t->text('address')->nullable());
        $add('customer_type', fn ($t) => $t->enum('customer_type', ['individual', 'company']));
        $add('source', fn ($t) => $t->string('source', 100)->nullable());
        $add('status', fn ($t) => $t->enum('status', ['new', 'contacted', 'qualified', 'converted', 'lost'])->default('new'));
        $add('notes', fn ($t) => $t->text('notes')->nullable());
        $add('converted_customer_id', fn ($t) => $t->unsignedBigInteger('converted_customer_id')->nullable());

        if (! Schema::hasColumn('nx_leads', 'created_at')) {
            Schema::table('nx_leads', fn (Blueprint $table) => $table->timestamps());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
