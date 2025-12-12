<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nx_quotations', function (Blueprint $table) {
            // 1) Hapus kolom lama salesperson jika masih ada
            if (Schema::hasColumn('nx_quotations', 'nx_sales_people_id')) {
                
                try { $table->dropForeign(['nx_sales_people_id']); } catch (\Throwable $e) {}
                $table->dropColumn('nx_sales_people_id');
            }

            // 2) Tambah relasi ke karyawan pembuat
            if (!Schema::hasColumn('nx_quotations', 'nx_employee_id')) {
                $table->foreignId('nx_employee_id')->nullable()
                    ->constrained('nx_employees', 'id')->nullOnDelete()
                    ->after('nx_customer_id');
            }

            // 3) Audit pembuat
            if (!Schema::hasColumn('nx_quotations', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()
                    ->constrained('users', 'id')->nullOnDelete()
                    ->after('nx_employee_id');
            }
            if (!Schema::hasColumn('nx_quotations', 'created_by_employee_id')) {
                $table->foreignId('created_by_employee_id')->nullable()
                    ->constrained('nx_employees', 'id')->nullOnDelete();
            }

            // 4) Approval
            if (!Schema::hasColumn('nx_quotations', 'approved_by_user_id')) {
                $table->foreignId('approved_by_user_id')->nullable()
                    ->constrained('users', 'id')->nullOnDelete();
            }
            if (!Schema::hasColumn('nx_quotations', 'approved_by_employee_id')) {
                $table->foreignId('approved_by_employee_id')->nullable()
                    ->constrained('nx_employees', 'id')->nullOnDelete();
            }
            if (!Schema::hasColumn('nx_quotations', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }

            // 5) Total
            if (!Schema::hasColumn('nx_quotations', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('nx_quotations', 'discount')) {
                $table->decimal('discount', 5, 2)->default(0); // persen
            }
            if (!Schema::hasColumn('nx_quotations', 'tax')) {
                $table->decimal('tax', 5, 2)->default(0); // persen
            }
            if (!Schema::hasColumn('nx_quotations', 'grand_total')) {
                $table->decimal('grand_total', 15, 2)->default(0);
            }

            // Index opsional
            $table->index(['status', 'quotation_date']);
        });
    }

    public function down(): void
    {
        Schema::table('nx_quotations', function (Blueprint $table) {
            // Drop index opsional
            try { $table->dropIndex(['status', 'quotation_date']); } catch (\Throwable $e) {}

            // Drop kolom baru
            foreach ([
                'grand_total','tax','discount','subtotal',
                'approved_at','approved_by_employee_id','approved_by_user_id',
                'created_by_employee_id','created_by_user_id',
                'nx_employee_id',
            ] as $col) {
                if (Schema::hasColumn('nx_quotations', $col)) {
                    // Drop FK sebelum kolom
                    if (in_array($col, ['nx_employee_id','created_by_employee_id','approved_by_employee_id','created_by_user_id','approved_by_user_id'])) {
                        try { $table->dropForeign([$col]); } catch (\Throwable $e) {}
                    }
                    $table->dropColumn($col);
                }
            }

        });
    }
};
