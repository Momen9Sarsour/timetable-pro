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
        Schema::create('generated_schedules', function (Blueprint $table) {
            $table->id();

            // --- ماذا سيُدرس؟ (الشعبة والمادة) ---
            $table->foreignId('section_id') // ربط بالشعبة الدراسية المحددة
                ->constrained('sections')
                ->onUpdate('cascade')
                ->onDelete('cascade'); // إذا حذفت الشعبة، يحذف جدولها

            // --- من سيُدرّس؟ ---
            $table->foreignId('instructor_id') // ربط بالمدرس الذي سيقوم بالتدريس
                ->constrained('instructors')
                ->onUpdate('cascade')
                ->onDelete('cascade'); // إذا حذف المدرس، قد نحتاج لحذف جدوله أو وضع علامة؟ (Cascade قد يكون خطيراً هنا)
            // ->onDelete('restrict'); // أو منع حذف المدرس إذا كان لديه جدول
            // ->onDelete('set null'); // أو جعل المدرس فارغاً (يتطلب تعديل يدوي) اختر الأنسب!

            // --- أين سيُدرّس؟ ---
            $table->foreignId('room_id') // ربط بالقاعة المستخدمة
                ->constrained('rooms')
                ->onUpdate('cascade')
                ->onDelete('cascade'); // نفس ملاحظة instructor_id، Cascade قد يكون خطيراً
            // ->onDelete('restrict');
            // ->onDelete('set null');

            // --- متى سيُدرّس؟ ---
            $table->foreignId('timeslot_id') // ربط بالفترة الزمنية المحددة
                ->constrained('timeslots')
                ->onUpdate('cascade')
                ->onDelete('cascade'); // إذا حذفت فترة زمنية من النظام، تحذف المحاضرات فيها

            // --- معلومات إضافية للسياق ---
            // يمكن إضافة السنة الأكاديمية والفصل هنا لتسهيل الاستعلامات
            $table->year('academic_year');
            $table->unsignedTinyInteger('semester'); // (1=فصل أول, 2=فصل ثاني, 3=صيفي)

            // حقل لتحديد نوع المحاضرة في هذه الفترة (نظري أم عملي) - مهم إذا كانت المادة مقسمة
            $table->enum('lecture_type', ['Theoretical', 'Practical']);

            // يمكن إضافة حقل لتمييز الجداول المختلفة التي تم إنشاؤها (إذا أردنا حفظ نسخ متعددة)
            // $table->unsignedBigInteger('schedule_version_id')->nullable();

            // --- قيود لمنع التعارضات على مستوى قاعدة البيانات (اختياري لكن مفيد) ---
            // قيد: لا يمكن لنفس المدرس أن يكون في مكانين بنفس الفترة الزمنية
            $table->unique(['instructor_id', 'timeslot_id', 'academic_year', 'semester'], 'schedule_instructor_time_unique');

            // قيد: لا يمكن لنفس القاعة أن تكون مشغولة بدرسين بنفس الفترة الزمنية
            $table->unique(['room_id', 'timeslot_id', 'academic_year', 'semester'], 'schedule_room_time_unique');

            // قيد: لا يمكن لنفس الشعبة أن تحضر درسين بنفس الفترة الزمنية
            $table->unique(['section_id', 'timeslot_id', 'academic_year', 'semester'], 'schedule_section_time_unique');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_schedules');
    }
};
