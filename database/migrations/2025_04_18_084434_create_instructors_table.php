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

            $table->string('instructor_no')->unique();
            $table->string('instructor_name');
            $table->string('academic_degree')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')
                ->onUpdate('cascade')->onDelete('set null');

            $table->foreignId('department_id')->nullable()->constrained('departments')
                ->onUpdate('cascade')->onDelete('set null');

            $table->json('availability_preferences')->nullable();
            // مثال للـ JSON: {"preferred": ["Sunday 8-10", "Monday 10-12"], "unavailable": ["Wednesday afternoon"]}

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
