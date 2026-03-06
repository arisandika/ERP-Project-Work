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
        Schema::create('nx_project_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('nx_project_id')
                ->constrained('nx_projects')
                ->cascadeOnDelete();

            $table->string('document_name');
            $table->string('file_path');

            $table->enum('document_type', [
                'contract',
                'bast',
                'technical',
                'other'
            ])->default('other');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nx_project_documents');
    }
};
