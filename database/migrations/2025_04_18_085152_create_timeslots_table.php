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
        Schema::create('timeslots', function (Blueprint $table) {
            $table->id();

            // تمثيل اليوم (0 = الأحد, 1 = الإثنين, ..., 6 = السبت) أو استخدام enum
            // $table->tinyInteger('day_of_week')->unsigned();
            $table->enum('day', ['Sunday', 'Monday']);
            $table->time('start_time'); // وقت بداية الفترة
            $table->time('end_time'); // وقت نهاية الفترة

            // هل هذه الفترة مخصصة لنوع معين من المحاضرات (نظري/عملي)؟ (اختياري)
            // $table->enum('slot_type', ['Theory', 'Practical', 'Any'])->default('Any');

            // هل هذه الفترة متاحة للجميع أم مقيدة (مثل فترات الصباح فقط)؟ (اختياري)
            // $table->boolean('is_generally_available')->default(true);


            // لضمان عدم تكرار نفس الفترة الزمنية بالضبط
            $table->unique(['day', 'start_time', 'end_time']);

            // يمكن إضافة قيود للتحقق من أن end_time > start_time
            // DB::statement('ALTER TABLE timeslots ADD CONSTRAINT chk_time_order CHECK (end_time > start_time);');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timeslots');
    }
};
