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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();

            $table->string('department_no')->unique(); // رقم القسم (يفضل أن يكون فريداً)
            $table->string('department_name'); // اسم القسم
            // يمكن إضافة حقول أخرى لاحقاً إذا لزم الأمر (مثل رئيس القسم كـ user_id)
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
