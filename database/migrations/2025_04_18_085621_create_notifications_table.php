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
        Schema::create('notifications', function (Blueprint $table) {
            // $table->id();

            $table->uuid('id')->primary(); // استخدام UUID أفضل للتنبيهات
            $table->string('type'); // نوع كلاس التنبيه (مثل App\Notifications\ScheduleChanged)
            $table->morphs('notifiable'); // لربط التنبيه بالمستخدم (User) أو أي موديل آخر
            $table->text('data'); // بيانات التنبيه (مثل رسالة التنبيه، رابط للجدول) - بصيغة JSON
            $table->timestamp('read_at')->nullable(); // متى تم قراءة التنبيه

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
