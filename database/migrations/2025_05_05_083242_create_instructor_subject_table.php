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
        Schema::create('instructor_subject', function (Blueprint $table) {
            // $table->id();

            // المفتاح الأجنبي لجدول المدرسين
            $table->foreignId('instructor_id')
                  ->constrained('instructors') // يربط بجدول instructors
                  ->onDelete('cascade'); // إذا حذف المدرس، احذف ارتباطاته بالمواد

            // المفتاح الأجنبي لجدول المواد
             $table->foreignId('subject_id')
                  ->constrained('subjects') // يربط بجدول subjects
                  ->onDelete('cascade'); // إذا حذفت المادة، احذف ارتباطاتها بالمدرسين

            // المفتاح الأساسي المركب لضمان عدم تكرار نفس المدرس مع نفس المادة
             $table->primary(['instructor_id', 'subject_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructor_subject');
    }
};
