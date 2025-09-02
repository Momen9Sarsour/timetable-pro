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
        Schema::create('genes', function (Blueprint $table) {
            // $table->id();
            $table->id('gene_id');
            $table->foreignId('chromosome_id')->constrained('chromosomes', 'chromosome_id')->onDelete('cascade');
            $table->string('lecture_unique_id')->nullable();
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('instructors')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            // لاحظ أن timeslot_id هنا هو FK للجدول الرئيسي timeslots، وليس للجدول الجديد
            // $table->foreignId('timeslot_id')->constrained('timeslots')->onDelete('cascade');
            // سنزيل القيد الأجنبي (Foreign Key) لأننا سنخزن مصفوفة
            $table->json('timeslot_ids'); // تغيير النوع إلى JSON واسم الحقل

            $table->string('block_type')->nullable(); // 'theory' أو 'practical'
            $table->integer('block_duration')->nullable(); // مدة البلوك بالساعات
            $table->boolean('is_continuous')->default(true); // هل البلوك متصل
            // $table->integer('student_group_id')->nullable(); // رقم مجموعة الطلاب
            $table->json('student_group_id'); // تغيير النوع إلى JSON واسم الحقل

            $table->index('lecture_unique_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('genes');
    }
};
