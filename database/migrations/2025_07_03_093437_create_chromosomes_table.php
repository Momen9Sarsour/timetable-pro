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
        Schema::create('chromosomes', function (Blueprint $table) {
            // $table->id();
            $table->id('chromosome_id');
            $table->foreignId('population_id')->constrained('populations', 'population_id')->onDelete('cascade');
            $table->unsignedInteger('generation_number');

            // الأعمدة الجديدة للعقوبات
            $table->integer('student_conflict_penalty')->default(0);
            $table->integer('teacher_conflict_penalty')->default(0);
            $table->integer('room_conflict_penalty')->default(0);
            $table->integer('capacity_conflict_penalty')->default(0);
            $table->integer('room_type_conflict_penalty')->default(0);
            $table->integer('teacher_eligibility_conflict_penalty')->default(0);

            // الأعمدة الإجمالية
            $table->integer('penalty_value')->default(-1);
            $table->double('fitness_value')->default(0.0);

            $table->boolean('is_best_of_generation')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chromosomes');
    }
};
