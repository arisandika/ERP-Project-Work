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
        Schema::create('nx_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->date('date');
            $table->string('note')->nullable();
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();

            // Location data
            $table->decimal('latitude_in', 10, 7)->nullable();
            $table->decimal('longitude_in', 10, 7)->nullable();
            $table->decimal('latitude_out', 10, 7)->nullable();
            $table->decimal('longitude_out', 10, 7)->nullable();

            // Face recognition
            $table->string('face_snapshot_in')->nullable();   // path photo clock-in
            $table->string('face_snapshot_out')->nullable();  // path photo clock-out
            $table->boolean('face_verified_in')->default(false);
            $table->boolean('face_verified_out')->default(false);
            $table->float('face_similarity_in')->nullable();  // similarity score when verifying (0.0 - 1.0)
            $table->float('face_similarity_out')->nullable(); // similarity score when verifying

            // Status and shift
            $table->enum('status', ['present', 'late', 'presensit', 'leave'])->default('present');

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('nx_employees')->onDelete('cascade');
            $table->foreign('shift_id')->references('id')->on('nx_shifts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_attendances');
    }
};
