<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE nx_financial_records
            MODIFY COLUMN type ENUM('pemasukan', 'pengeluaran', 'piutang') NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE nx_financial_records
            MODIFY COLUMN type ENUM('pemasukan', 'pengeluaran') NOT NULL
        ");
    }
};
