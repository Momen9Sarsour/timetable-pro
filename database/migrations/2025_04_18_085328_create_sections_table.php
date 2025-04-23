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
            $table->foreignId('plan_subject_id')
                  ->constrained('plan_subjects')
                  ->onUpdate('cascade')
                  ->onDelete('cascade'); // إذا حذفت المادة من الخطة، تحذف شعبها

            // الطريقة 2: (بديلة) تخزين تفاصيل المادة والخطة هنا - قد يكون فيه تكرار
            // $table->foreignId('plan_id')->constrained('plans');
            // $table->foreignId('subject_id')->constrained('subjects');
            // $table->unsignedTinyInteger('plan_level');
            // $table->unsignedTinyInteger('plan_semester');

            $table->unsignedTinyInteger('section_number'); // رقم الشعبة (1, 2, 3...) لنفس المادة في نفس المستوى/الفصل
            $table->unsignedSmallInteger('student_count'); // العدد الفعلي أو المخصص للطلاب في هذه الشعبة
            $table->enum('section_gender', ['Male', 'Female', 'Mixed'])->default('Mixed'); // جنس الطلاب في الشعبة
            $table->string('branch')->nullable(); // فرع الشعبة (إذا كانت الأعداد مقسمة حسب الفرع)

            // يمكن إضافة حقل للسنة الأكاديمية والفصل الدراسي لهذه الشعبة
            $table->year('academic_year');
            $table->unsignedTinyInteger('semester'); // (1=فصل أول, 2=فصل ثاني, 3=صيفي)

            // يمكن إضافة حقل لربط الشعبة بمدرس معين إذا تم التعيين مسبقاً (اختياري)
            // $table->foreignId('assigned_instructor_id')->nullable()->constrained('instructors')->onDelete('set null');

            // لضمان عدم تكرار نفس رقم الشعبة لنفس المادة في نفس السياق
            // (يعتمد على الطريقة المستخدمة لربط المادة - plan_subject_id أو التفاصيل)
            $table->unique(['plan_subject_id', 'section_number', 'academic_year', 'semester'], 'section_unique');
             // أو إذا استخدمت الطريقة 2:
            // $table->unique(['plan_id', 'subject_id', 'plan_level', 'plan_semester', 'section_number', 'branch', 'academic_year', 'semester'], 'section_details_unique');

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
