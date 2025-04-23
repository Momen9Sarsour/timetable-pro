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
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();

            $table->string('instructor_no')->unique(); // الرقم الوظيفي للمدرس (فريد)
            $table->string('instructor_name'); // اسم المدرس
            $table->string('academic_degree')->nullable(); // الدرجة العلمية (مثل 'دكتور', 'ماجستير', 'بكالوريوس') - مهم لتحديد النصاب

            $table->foreignId('user_id')
                ->nullable() // نسمح بأن يكون فارغاً في البداية أو إذا كان المدرس لا يملك حساب دخول
                ->constrained('users') // يربط بجدول users
                ->onUpdate('cascade')
                ->onDelete('set null'); // إذا حذف اليوزر، يصبح هذا الحقل null

            $table->foreignId('department_id') // المفتاح الأجنبي لقسم المدرس
                ->nullable() // قد يكون هناك مدرسون غير مرتبطين بقسم مؤقتاً؟ أو نجعله required؟
                ->constrained('departments') // يربط بجدول departments
                ->onUpdate('cascade')
                ->onDelete('set null'); // إذا حذف القسم، اجعل department_id هنا NULL (أو restrict)

            // حقل لتخزين تفضيلات الوقت (اختياري في البداية، يمكن جعله JSON أو جدول منفصل لاحقاً)
            $table->json('availability_preferences')->nullable();
            // مثال للـ JSON: {"preferred": ["Sunday 8-10", "Monday 10-12"], "unavailable": ["Wednesday afternoon"]}

            // حقل لتحديد الحد الأقصى للساعات الأسبوعية (النصاب) - يمكن حسابه من الدرجة العلمية أو وضعه مباشرة
            $table->unsignedTinyInteger('max_weekly_hours')->nullable();

            // $table->string('office_location')->nullable(); // موقع المكتب
            // $table->text('office_hours')->nullable(); // الساعات المكتبية (كنص لوصفها)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};
