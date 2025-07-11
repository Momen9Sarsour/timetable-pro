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
        Schema::create('chromosomes', function (Blueprint $table) {
            // $table->id();
            $table->id('chromosome_id');
            $table->foreignId('population_id')->constrained('populations', 'population_id')->onDelete('cascade');
            $table->double('penalty_value');
            $table->unsignedInteger('generation_number');
            $table->boolean('is_best_of_generation')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chromosomes');
    }
};
