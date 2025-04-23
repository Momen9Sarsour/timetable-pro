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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // المستخدم الذي قام بالفعل
            $table->string('action'); // وصف الفعل (e.g., 'updated_schedule', 'created_instructor', 'deleted_room')
            $table->morphs('loggable'); // لربط السجل بالشيء الذي تم التعديل عليه (Schedule, Instructor, Room...)
            $table->text('details')->nullable(); // تفاصيل إضافية (مثل القيم القديمة والجديدة) - JSON
            $table->ipAddress('ip_address')->nullable(); // عنوان IP للمستخدم
            $table->text('user_agent')->nullable(); // معلومات المتصفح
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
