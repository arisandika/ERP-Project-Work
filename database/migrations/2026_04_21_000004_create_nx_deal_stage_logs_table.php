<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
      public function up(): void
      {
            Schema::create('nx_deal_stage_logs', function (Blueprint $table) {
                  $table->id();

                  $table->foreignId('nx_deal_id')
                        ->constrained('nx_deals')
                        ->cascadeOnDelete();

                  $table->foreignId('from_stage_id')
                        ->nullable()
                        ->constrained('nx_deal_stages')
                        ->nullOnDelete()
                        ->comment('NULL jika ini adalah stage pertama saat deal dibuat');

                  $table->foreignId('to_stage_id')
                        ->constrained('nx_deal_stages')
                        ->cascadeOnDelete();

                  $table->unsignedBigInteger('changed_by')->nullable();

                  $table->foreign('changed_by', 'fk_logs_changed_by_employee')
                        ->references('id')
                        ->on('nx_employees')
                        ->nullOnDelete();

                  $table->integer('time_in_stage_days')
                        ->nullable()
                        ->comment('Berapa hari deal berada di from_stage sebelum pindah');

                  $table->text('note')->nullable();

                  $table->timestamps();
            });
      }

      public function down(): void
      {
            Schema::dropIfExists('nx_deal_stage_logs');
      }
};
