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
        // Benahi schema drift: kolom ini (name, email, phone, address, timestamps)
        // sudah dibuat create migration 2025_10_12_142658_create_customers_table.
        // Ulang-add tanpa guard → "duplicate column name" pada fresh migrate (SQLite).
        // Guard ini membuat migrasi idempotent tanpa mengubah skema pada DB
        // yang kolomnya belum ada.
        $columns = [
            'name'          => fn () => $this->addString('name', 150, false),
            'email'         => fn () => $this->addString('email', 150, true),
            'phone'         => fn () => $this->addString('phone', 30, true),
            'address'       => fn () => $this->addText('address'),
            'customer_type' => fn () => $this->addEnum('customer_type', ['individual', 'company']),
            'nik'           => fn () => $this->addString('nik', 30, true),
            'npwp'          => fn () => $this->addString('npwp', 30, true),
            'pic_name'      => fn () => $this->addString('pic_name', 150, true),
            'pic_position'  => fn () => $this->addString('pic_position', 100, true),
            'pic_phone'     => fn () => $this->addString('pic_phone', 30, true),
            'source'        => fn () => $this->addString('source', 100, true),
            'status'        => fn () => $this->addEnum('status', ['active', 'inactive'], 'active'),
        ];

        foreach ($columns as $col => $add) {
            if (! Schema::hasColumn('nx_customers', $col)) {
                Schema::table('nx_customers', fn (Blueprint $table) => $add());
            }
        }

        if (! Schema::hasColumn('nx_customers', 'created_at')) {
            Schema::table('nx_customers', fn (Blueprint $table) => $table->timestamps());
        }
    }

    private function addString(string $col, int $len, bool $nullable): void
    {
        $b = new Blueprint('nx_customers');
        $b->string($col, $len)->nullable($nullable);
        $b->build();
    }

    private function addText(string $col): void
    {
        $b = new Blueprint('nx_customers');
        $b->text($col)->nullable();
        $b->build();
    }

    private function addEnum(string $col, array $values, string $default = ''): void
    {
        $b = new Blueprint('nx_customers');
        $b->enum($col, $values)->default($default);
        $b->build();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
