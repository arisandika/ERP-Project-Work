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
        Schema::table('nx_customers', function (Blueprint $table) {

            $table->string('nik', 16)->nullable()->after('email');

            $table->string('npwp', 30)->nullable()->after('nik');

            // Data PIC
            $table->string('pic_name')->nullable()->after('address');
            $table->string('pic_position')->nullable()->after('pic_name');
            $table->string('pic_phone', 20)->nullable()->after('pic_position');

            $table->string('phone', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nx_customers', function (Blueprint $table) {
            $table->dropColumn(['nik', 'npwp', 'pic_name', 'pic_position', 'pic_phone']);

            // Kembalikan phone jadi tidak nullable (jika perlu)
            $table->string('phone', 20)->nullable(false)->change();
        });
    }
};
