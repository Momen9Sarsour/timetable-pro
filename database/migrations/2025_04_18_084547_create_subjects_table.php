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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();

            $table->string('subject_no')->unique(); // رقم المادة أو رمزها (فريد)
            $table->string('subject_name'); // اسم المادة
            $table->unsignedTinyInteger('subject_load'); // العبء الدراسي للمادة (إجمالي الساعات المعتمدة)

            // ساعات نظرية وعملية منفصلة (مهم للجدولة وتحديد نوع القاعة)
            $table->unsignedTinyInteger('theoretical_hours')->default(0); // عدد الساعات النظرية الأسبوعية
            $table->unsignedTinyInteger('practical_hours')->default(0); // عدد الساعات العملية الأسبوعية

            // $table->integer('load_theoretical_section')->nullable(); // عدد الشعب النظرية
            // $table->Integer('load_practical_section')->nullable(); // عدد الشعب العملية
            // *** الحقول الجديدة لسعة الشعب ***
            $table->unsignedInteger('load_theoretical_section')->nullable()->default(50)->comment('Default/Max students in one theoretical section');
            $table->unsignedInteger('load_practical_section')->nullable()->default(25)->comment('Default/Max students in one practical section');

            $table->foreignId('subject_type_id') // نوع المادة (إجبارية، اختيارية..)
                ->nullable()
                ->constrained('subjects_types')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->foreignId('subject_category_id') // فئة المادة (نظرية، عملية..)
                ->nullable()
                ->constrained('subjects_categories')
                ->onUpdate('cascade')
                ->onDelete('set null');

            // هل المادة تتبع قسماً معيناً بشكل أساسي؟ (حتى لو كانت تدرس في أقسام أخرى كمتطلب)
            $table->foreignId('department_id') // القسم الذي يقدم المادة بشكل أساسي
                ->nullable() // قد تكون مادة مشتركة (متطلب جامعة مثلاً) لا تتبع قسماً محدداً
                ->constrained('departments')
                ->onUpdate('cascade')
                ->onDelete('set null');

            // هل تحتاج المادة لنوع قاعة معين؟ (مثل مختبر حاسوب) - اختياري، يمكن تحديده عند الجدولة
            $table->foreignId('required_room_type_id')->nullable()->constrained('rooms_types');

            // تأكيد أن مجموع الساعات النظرية والعملية منطقي (يمكن إضافته كـ Check Constraint إذا كانت قاعدة البيانات تدعم)
            // أو يتم التحقق منه في الكود
            // DB::statement('ALTER TABLE subjects ADD CONSTRAINT chk_hours CHECK (theoretical_hours + practical_hours > 0);');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
