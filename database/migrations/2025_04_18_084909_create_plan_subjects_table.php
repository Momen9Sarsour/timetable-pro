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
        Schema::create('plan_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plan_id') // ربط بالخطة الدراسية
                  ->constrained('plans')
                  ->onUpdate('cascade')
                  ->onDelete('cascade'); // إذا حذفت الخطة، تحذف موادها من هذا الجدول

            $table->foreignId('subject_id') // ربط بالمادة الدراسية
                  ->constrained('subjects')
                  ->onUpdate('cascade')
                  ->onDelete('cascade'); // إذا حذفت المادة، تحذف من الخطط المرتبطة بها

            $table->unsignedTinyInteger('plan_level'); // المستوى الدراسي (مثل 1 للسنة الأولى، 2 للسنة الثانية)
            $table->unsignedTinyInteger('plan_semester'); // الفصل الدراسي (مثل 1 للفصل الأول، 2 للفصل الثاني، 3 للصيفي إن وجد)

            // يمكن إضافة حقل لتحديد ما إذا كانت المادة متطلب سابق لمادة أخرى في نفس الخطة (Prerequisite)
            // $table->unsignedBigInteger('prerequisite_plan_subject_id')->nullable();
            // $table->foreign('prerequisite_plan_subject_id')->references('id')->on('plan_subjects')->onDelete('set null');

            // لضمان عدم تكرار نفس المادة بنفس المستوى والفصل في نفس الخطة
            $table->unique(['plan_id', 'subject_id', 'plan_level', 'plan_semester'], 'plan_subject_level_semester_unique');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_subjects');
    }
};
