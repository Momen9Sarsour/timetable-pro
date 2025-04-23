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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();

            $table->string('room_no')->unique(); // رقم القاعة أو اسمها المختصر (فريد)
            $table->string('room_name')->nullable(); // اسم القاعة الوصفي (اختياري)
            $table->unsignedSmallInteger('room_size'); // سعة القاعة (عدد المقاعد)

            // تخصيص القاعة حسب الجنس (إذا كانت الكلية تفصل)
            $table->enum('room_gender', ['Male', 'Female', 'Mixed'])->default('Mixed');

            // فرع الكلية (إذا كان هناك أكثر من فرع جغرافي)
            $table->string('room_branch')->nullable();

            $table->foreignId('room_type_id') // نوع القاعة (محاضرات، مختبر..)
                ->constrained('rooms_types') // الربط بجدول أنواع القاعات
                ->onUpdate('cascade')
                ->onDelete('cascade'); // إذا حذف نوع القاعة، قد يكون من المنطقي حذف القاعات التابعة له؟ أو restrict

            // $table->json('equipment')->nullable(); // لتخزين قائمة المعدات كـ JSON, e.g., ["projector", "whiteboard", "computers"]

            // هل القاعة متاحة طوال الوقت أم لها أوقات محددة؟ (اختياري)
            // $table->json('availability_schedule')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
