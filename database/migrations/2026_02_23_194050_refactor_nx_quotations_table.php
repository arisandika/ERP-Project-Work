<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('nx_quotations', function (Blueprint $table) {

            if (!Schema::hasColumn('nx_quotations', 'nx_deal_id'))
                $table->unsignedBigInteger('nx_deal_id')->nullable()->after('id');

            if (!Schema::hasColumn('nx_quotations', 'promo_code_id'))
                $table->unsignedBigInteger('promo_code_id')->nullable()->after('grand_total');

            if (!Schema::hasColumn('nx_quotations', 'created_by'))
                $table->unsignedBigInteger('created_by')->nullable()->after('nx_employee_id');

            if (!Schema::hasColumn('nx_quotations', 'approved_by'))
                $table->unsignedBigInteger('approved_by')->nullable()->after('created_by');

            if (!Schema::hasColumn('nx_quotations', 'approved_at'))
                $table->timestamp('approved_at')->nullable()->after('approved_by');

            if (!Schema::hasColumn('nx_quotations', 'deleted_at'))
                $table->softDeletes();

            if (Schema::hasColumn('nx_quotations', 'created_by_employee_id'))
                $table->dropColumn('created_by_employee_id');

            if (Schema::hasColumn('nx_quotations', 'approved_by_employee_id'))
                $table->dropColumn('approved_by_employee_id');

        });

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            Schema::table('nx_quotations', fn(Blueprint $t) => $t->dropForeign(['promo_code_id']));
        } catch (Exception $e) {
        }
        try {
            Schema::table('nx_quotations', fn(Blueprint $t) => $t->dropForeign(['nx_deal_id']));
        } catch (Exception $e) {
        }
        try {
            Schema::table('nx_quotations', fn(Blueprint $t) => $t->dropForeign(['created_by']));
        } catch (Exception $e) {
        }
        try {
            Schema::table('nx_quotations', fn(Blueprint $t) => $t->dropForeign(['approved_by']));
        } catch (Exception $e) {
        }
        try {
            Schema::table('nx_quotations', fn(Blueprint $t) => $t->dropForeign(['nx_employee_id']));
        } catch (Exception $e) {
        }

        DB::statement("ALTER TABLE nx_quotations MODIFY quotation_number VARCHAR(50) AFTER nx_deal_id");
        DB::statement("ALTER TABLE nx_quotations MODIFY quotation_date DATETIME AFTER quotation_number");
        DB::statement("ALTER TABLE nx_quotations MODIFY valid_until DATETIME AFTER quotation_date");
        DB::statement("ALTER TABLE nx_quotations MODIFY status VARCHAR(50) AFTER valid_until");

        DB::statement("ALTER TABLE nx_quotations MODIFY subtotal DECIMAL(15,2) AFTER status");
        DB::statement("ALTER TABLE nx_quotations MODIFY tax DECIMAL(15,2) AFTER subtotal");
        DB::statement("ALTER TABLE nx_quotations MODIFY discount_amount DECIMAL(15,2) AFTER tax");
        DB::statement("ALTER TABLE nx_quotations MODIFY grand_total DECIMAL(15,2) AFTER discount_amount");

        DB::statement("ALTER TABLE nx_quotations MODIFY promo_code_id BIGINT UNSIGNED NULL AFTER grand_total");
        DB::statement("ALTER TABLE nx_quotations MODIFY notes TEXT AFTER promo_code_id");

        DB::statement("ALTER TABLE nx_quotations MODIFY nx_employee_id BIGINT UNSIGNED AFTER notes");
        DB::statement("ALTER TABLE nx_quotations MODIFY created_by BIGINT UNSIGNED AFTER nx_employee_id");
        DB::statement("ALTER TABLE nx_quotations MODIFY approved_by BIGINT UNSIGNED AFTER created_by");
        DB::statement("ALTER TABLE nx_quotations MODIFY approved_at TIMESTAMP NULL AFTER approved_by");

        DB::statement("ALTER TABLE nx_quotations MODIFY created_at TIMESTAMP NULL AFTER approved_at");
        DB::statement("ALTER TABLE nx_quotations MODIFY updated_at TIMESTAMP NULL AFTER created_at");
        DB::statement("ALTER TABLE nx_quotations MODIFY deleted_at TIMESTAMP NULL AFTER updated_at");

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Schema::table('nx_quotations', function (Blueprint $table) {

            try {
                $table->foreign('nx_deal_id')
                    ->references('id')
                    ->on('nx_deals')
                    ->cascadeOnDelete();
            } catch (Exception $e) {
            }

            try {
                $table->foreign('promo_code_id')
                    ->references('id')
                    ->on('nx_promo_codes')
                    ->nullOnDelete();
            } catch (Exception $e) {
            }

            try {
                $table->foreign('nx_employee_id')
                    ->references('id')
                    ->on('nx_employees')
                    ->nullOnDelete();
            } catch (Exception $e) {
            }

            try {
                $table->foreign('created_by')
                    ->references('id')
                    ->on('nx_employees')
                    ->nullOnDelete();
            } catch (Exception $e) {
            }

            try {
                $table->foreign('approved_by')
                    ->references('id')
                    ->on('nx_employees')
                    ->nullOnDelete();
            } catch (Exception $e) {
            }

        });

    }

    public function down(): void
    {

        Schema::table('nx_quotations', function (Blueprint $table) {

            try {
                $table->dropForeign(['nx_deal_id']);
            } catch (Exception $e) {
            }
            try {
                $table->dropForeign(['promo_code_id']);
            } catch (Exception $e) {
            }
            try {
                $table->dropForeign(['created_by']);
            } catch (Exception $e) {
            }
            try {
                $table->dropForeign(['approved_by']);
            } catch (Exception $e) {
            }

        });

    }

};