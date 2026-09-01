<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds per-criterion ratings (1-5) to nx_performance_evaluations so
 * supervisors can rate Quality, Teamwork, Communication, Problem Solving
 * in addition to the overall rating. All nullable — existing rows/scores
 * remain valid.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('nx_performance_evaluations', function (Blueprint $table) {
            $table->unsignedTinyInteger('quality')
                ->nullable()
                ->comment('1=Very Poor .. 5=Outstanding, Quality of Work')
                ->after('rating');

            $table->unsignedTinyInteger('teamwork')
                ->nullable()
                ->comment('1=Very Poor .. 5=Outstanding')
                ->after('quality');

            $table->unsignedTinyInteger('communication')
                ->nullable()
                ->comment('1=Very Poor .. 5=Outstanding')
                ->after('teamwork');

            $table->unsignedTinyInteger('problem_solving')
                ->nullable()
                ->comment('1=Very Poor .. 5=Outstanding')
                ->after('communication');
        });
    }

    public function down(): void
    {
        Schema::table('nx_performance_evaluations', function (Blueprint $table) {
            $table->dropColumn(['quality', 'teamwork', 'communication', 'problem_solving']);
        });
    }
};
