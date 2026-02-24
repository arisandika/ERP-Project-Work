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
            $table->string('name',150);

            $table->string('email',150)->nullable();

            $table->string('phone',30)->nullable();

            $table->text('address')->nullable();

            $table->enum('customer_type',[

                'individual',
                'company'

            ]);

            $table->string('nik',30)->nullable();

            $table->string('npwp',30)->nullable();

            $table->string('pic_name',150)->nullable();

            $table->string('pic_position',100)->nullable();

            $table->string('pic_phone',30)->nullable();

            $table->string('source',100)->nullable();

            $table->enum('status',[
                'active',
                'inactive'
            ])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
