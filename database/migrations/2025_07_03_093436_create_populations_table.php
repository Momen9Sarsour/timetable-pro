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
        Schema::create('populations', function (Blueprint $table) {
            // $table->id();
            $table->id('population_id'); // اسم الحقل PK
            $table->unsignedInteger('population_size');
            $table->foreignId('crossover_id')->nullable()->constrained('crossover_types', 'crossover_id')->onDelete('set null');
            $table->foreignId('selection_id')->nullable()->constrained('selection_types', 'selection_type_id')->onDelete('set null');
            $table->decimal('mutation_rate', 5, 4); // (e.g., 0.0100)
            $table->unsignedInteger('generations_count');
            $table->foreignId('best_chromosome_id')->nullable()->constrained('chromosomes', 'chromosome_id')->onDelete('set null');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->enum('status', ['running', 'completed', 'stopped', 'failed'])->default('running');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('populations');
    }
};
