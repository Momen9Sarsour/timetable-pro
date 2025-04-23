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
        Schema::create('plan_expected_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id') // ربط بالخطة الدراسية
                  ->constrained('plans')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');

            $table->unsignedTinyInteger('plan_level'); // المستوى الدراسي
            $table->unsignedTinyInteger('plan_semester'); // الفصل الدراسي

            // تمييز الأعداد حسب الجنس (إذا كانت الشعب ستفصل)
            $table->unsignedSmallInteger('male_count')->default(0); // عدد الطلاب الذكور المتوقع
            $table->unsignedSmallInteger('female_count')->default(0); // عدد الطلاب الإناث المتوقع

            // تمييز الأعداد حسب الفرع (إذا كانت هناك فروع)
            $table->string('branch')->nullable(); // اسم الفرع (يجب أن يتطابق مع القيم في جدول rooms.room_branch)

            // يمكن إضافة حقل للسنة الأكاديمية التي ينطبق عليها هذا العدد المتوقع
            $table->year('academic_year');

            // لضمان عدم تكرار إدخال العدد لنفس المجموعة (خطة، مستوى، فصل، فرع، سنة)
            $table->unique(['plan_id', 'plan_level', 'plan_semester', 'branch', 'academic_year'], 'plan_expected_counts_unique');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_expected_counts');
    }
};
