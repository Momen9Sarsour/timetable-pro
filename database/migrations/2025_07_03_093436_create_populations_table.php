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

            $table->year('academic_year')->default(2025);
            $table->unsignedTinyInteger('semester')->default(1);
            $table->unsignedTinyInteger('theory_credit_to_slots')->default(1);
            $table->unsignedTinyInteger('practical_credit_to_slots')->default(2);

            $table->unsignedInteger('population_size');
            $table->foreignId('crossover_id')->nullable()->constrained('crossover_types', 'crossover_id')->onDelete('set null');
            $table->foreignId('selection_id')->nullable()->constrained('selection_types', 'selection_type_id')->onDelete('set null');
            $table->decimal('mutation_rate', 5, 4); // (e.g., 0.0100)
            $table->unsignedInteger('max_generations')->default(10);
            $table->json('elite_chromosome_ids')->nullable();

            $table->decimal('crossover_rate', 5, 4)->default(0.95);
            $table->unsignedInteger('selection_size')->default(5);
            // $table->enum('mutation_type', ['random', 'smart', 'swap', 'smart_swap', 'adaptive'])
            //     ->default('random');
            $table->foreignId('mutation_id')->nullable()->constrained('mutation_types', 'mutation_id');

            $table->foreignId('best_chromosome_id')->nullable()->constrained('chromosomes', 'chromosome_id')->onDelete('set null');
            $table->boolean('stop_at_first_valid')->default(true);
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
