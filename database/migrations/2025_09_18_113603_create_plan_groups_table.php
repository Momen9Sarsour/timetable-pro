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
        Schema::create('plan_groups', function (Blueprint $table) {
            $table->id('group_id'); // Primary Key

            // معلومات السياق الأساسية
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->unsignedTinyInteger('plan_level'); // مستوى الخطة (1,2,3,4...)
            $table->year('academic_year'); // السنة الأكاديمية
            $table->unsignedTinyInteger('semester'); // الفصل
            $table->string('branch')->nullable(); // الفرع (إن وجد)

            // ربط مع الشعبة
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');

            // معلومات المجموعة
            $table->unsignedTinyInteger('group_no'); // رقم المجموعة (1,2,3...)
            $table->unsignedSmallInteger('group_size')->nullable(); // حجم المجموعة (اختياري)
            $table->enum('gender', ['Male', 'Female', 'Mixed'])->default('Mixed'); // جنس المجموعة

            // فهارس للأداء
            $table->index(['plan_id', 'plan_level', 'academic_year', 'semester', 'branch'], 'context_index');
            $table->index(['section_id', 'group_no'], 'section_group_index');

            // ضمان عدم التكرار للمجموعة الواحدة في نفس الشعبة
            $table->unique(['section_id', 'group_no'], 'unique_section_group');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_groups');
    }
};
