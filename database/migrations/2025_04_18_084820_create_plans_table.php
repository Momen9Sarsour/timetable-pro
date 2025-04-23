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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            $table->string('plan_no')->unique(); // رقم الخطة أو رمزها (فريد)
            $table->string('plan_name'); // اسم الخطة الوصفي (مثل 'دبلوم برمجة وقواعد بيانات - 2023')
            $table->year('year'); // سنة اعتماد الخطة
            $table->unsignedSmallInteger('plan_hours'); // إجمالي الساعات المعتمدة للخطة بالكامل
            $table->boolean('is_active')->default(true); // هل الخطة الدراسية فعالة حالياً؟

            // يمكن ربط الخطة بقسم معين إذا كانت كل خطة تتبع قسماً واحداً
            $table->foreignId('department_id')
                  ->nullable() // أو اجعلها required إذا كانت دائماً مرتبطة بقسم
                  ->constrained('departments')
                  ->onUpdate('cascade')
                  ->onDelete('set null'); // أو restrict

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
