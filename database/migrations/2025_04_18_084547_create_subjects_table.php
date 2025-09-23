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

            $table->string('subject_no')->unique();
            $table->string('subject_name');
            $table->unsignedTinyInteger('subject_hours');
            $table->unsignedTinyInteger('subject_load');
            $table->foreignId('subject_type_id')->nullable()->constrained('subjects_types')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('subject_category_id')->nullable()->constrained('subjects_categories')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('required_room_type_id')->nullable()->constrained('rooms_types');

            // $table->unsignedTinyInteger('theoretical_hours')->default(0);
            // $table->unsignedTinyInteger('practical_hours')->default(0);

            // *** الحقول الجديدة لسعة الشعب ***
            // $table->unsignedInteger('load_theoretical_section')->nullable()->default(50);
            // $table->unsignedInteger('load_practical_section')->nullable()->default(25);

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
