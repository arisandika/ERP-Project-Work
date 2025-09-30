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
        // Employees (connected to users and departments)
        Schema::create('nx_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');                   // FK to users
            $table->unsignedBigInteger('department_id')->nullable(); // FK to nx_departments

            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone_number')->unique();
            $table->string('photo')->nullable(); // Profile photo path
            $table->string('address')->nullable();

            $table->string('position'); // Employee job title
            $table->enum('contract_type', ['permanent', 'contract', 'intern'])->default('contract');
            $table->enum('status', ['active', 'resigned', 'terminated'])->default('active');

            // Face recognition attributes (future-proof for OpenCV)
            $table->json('face_embeddings')->nullable();       // store serialized embedding vector directly
            $table->string('face_embedding_path')->nullable(); // store path to external file (.npy)
            $table->json('face_landmarks')->nullable();        // facial landmarks for alignment (optional)

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('nx_departments')->onDelete('set null');
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
