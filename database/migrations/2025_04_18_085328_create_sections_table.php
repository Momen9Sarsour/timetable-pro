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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();

            // ربط الشعبة بالمادة المحددة في الخطة الدراسية
            // الطريقة 1: الربط بـ plan_subjects.id
            $table->foreignId('plan_subject_id')->constrained('plan_subjects')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->year('academic_year');
            $table->unsignedTinyInteger('semester');
            $table->enum('activity_type', ['Theory', 'Practical'])->default('Theory');

            $table->foreignId('instructor_id')->nullable()->constrained('instructors')->onUpdate('cascade')->onDelete('set null');
            $table->unsignedTinyInteger('section_number');
            $table->unsignedSmallInteger('student_count');
            $table->enum('section_gender', ['Male', 'Female', 'Mixed'])->default('Mixed');
            $table->string('branch')->nullable();


            $table->unique(['plan_subject_id', 'section_number', 'academic_year', 'semester'], 'section_unique');

            $table->unique([
                'plan_subject_id',
                'academic_year',
                'semester',
                'activity_type',
                'section_number',
                'branch'
            ], 'sections_unique_constraint');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
