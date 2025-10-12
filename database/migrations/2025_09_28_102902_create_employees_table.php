<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nx_employees', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->unsignedBigInteger('user_id');                   // FK to users
            $table->unsignedBigInteger('department_id')->nullable(); // FK to nx_departments
            $table->unsignedBigInteger('office_id')->nullable();     // FK to nx_offices

            // Identification
            $table->string('national_id')->nullable();     // NIK
            $table->string('identity_number')->nullable(); // No. KTP

            // Personal Information
            $table->string('full_name');
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('education_level')->nullable();
            $table->date('join_date')->nullable();

            // Contact
            $table->string('email')->unique();
            $table->string('phone_number')->unique();
            $table->string('address')->nullable();
            $table->string('photo')->nullable();

            // Employment
            $table->string('position');
            $table->string('contract_type')->nullable();
            $table->string('status')->nullable();

            // Permissions / Toggles
            $table->boolean('can_wfa')->default(false);          // Work From Anywhere permission
            $table->boolean('can_unlock_shift')->default(false); // Unlock shift permission

            // Common Columns
            $table->softDeletes();
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('nx_departments')->onDelete('set null');
            $table->foreign('office_id')->references('id')->on('nx_offices')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_employees');
    }
};
