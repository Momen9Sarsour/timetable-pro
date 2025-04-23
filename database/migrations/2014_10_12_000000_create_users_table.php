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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            $table->foreignId('role_id') // المفتاح الأجنبي لجدول roles
                  ->nullable() // قد نسمح بأن يكون فارغاً مؤقتاً أو للمستخدمين غير النشطين
                  ->constrained('roles') // يربط بجدول roles ويتحقق من وجود الـ id
                  ->onUpdate('cascade') // إذا تغير id الدور، يتغير هنا
                  ->onDelete('set null'); // إذا حذف الدور، اجعل role_id هنا NULL (أو restrict لمنع الحذف)

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
