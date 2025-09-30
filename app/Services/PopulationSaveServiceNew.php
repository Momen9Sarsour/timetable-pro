<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PopulationSaveServiceNew
{
    // public function savePopulation($populationId, $population)
    // {
    //     try {
    //         Log::info("Starting population save for Population ID: {$populationId}");

    //         DB::transaction(function () use ($populationId, $population) {
    //             $currentIds = [
    //                 'population_id' => $this->getMaxId('populations', 'population_id'),
    //                 'chromosome_id' => $this->getMaxId('chromosomes', 'chromosome_id'),
    //                 'gene_id' => $this->getMaxId('genes', 'gene_id'),
    //                 'timeslot_id' => $this->getMaxId('timeslots', 'timeslot_id'),
    //             ];

    //             foreach ($population as &$chromosome) {
    //                 $chromosome['chromosome_id'] = ++$currentIds['chromosome_id'];

    //                 foreach ($chromosome['genes'] as &$gene) {
    //                     $gene['gene_id'] = ++$currentIds['gene_id'];
    //                     $gene['chromosome_id'] = $chromosome['chromosome_id'];

    //                     foreach ($gene['timeslots'] as &$slot) {
    //                         $slot['timeslot_id'] = ++$currentIds['timeslot_id'];
    //                         $slot['gene_id'] = $gene['gene_id'];
    //                     }
    //                 }
    //             }

    //             $newPopulationId = ++$currentIds['population_id'];

    //             $chromosomesData = array_map(function($ch) {
    //                 return [
    //                     'chromosome_id' => $ch['chromosome_id'],
    //                     'population_id' => $ch['population_id'],
    //                     'generation_number' => 1,
    //                     'fitness_value' => $ch['fitness'],
    //                     'is_best_of_generation' => $ch['is_fittest'],
    //                     'student_conflict_penalty' => 0,
    //                     'teacher_conflict_penalty' => 0,
    //                     'room_conflict_penalty' => 0,
    //                     'capacity_conflict_penalty' => 0,
    //                     'room_type_conflict_penalty' => 0,
    //                     'teacher_eligibility_conflict_penalty' => 0,
    //                     'penalty_value' => 0,
    //                     'created_at' => now(),
    //                     'updated_at' => now()
    //                 ];
    //             }, $population);

    //             $genesData = [];
    //             $timeslotsData = [];

    //             foreach ($population as $ch) {
    //                 foreach ($ch['genes'] as $g) {
    //                     $genesData[] = [
    //                         'gene_id' => $g['gene_id'],
    //                         'chromosome_id' => $g['chromosome_id'],
    //                         'lecture_unique_id' => 'gene_' . $g['gene_id'],
    //                         'section_id' => $g['section_id'],
    //                         'instructor_id' => $g['instructor_id'],
    //                         'room_id' => $g['room_id'],
    //                         'timeslot_ids' => json_encode(array_column($g['timeslots'], 'timeslot_id')),
    //                         'block_type' => $this->determineBlockType($g),
    //                         'block_duration' => $this->calculateBlockDuration($g),
    //                         'is_continuous' => 1,
    //                         'student_group_id' => json_encode([]),
    //                         'created_at' => now(),
    //                         'updated_at' => now()
    //                     ];

    //                     foreach ($g['timeslots'] as $slot) {
    //                         $timeslotsData[] = [
    //                             'timeslot_id' => $slot['timeslot_id'],
    //                             'gene_id' => $slot['gene_id'],
    //                             'timeslot_day' => $slot['timeslot_day'],
    //                             'start_time' => $slot['start_time'],
    //                             'end_time' => $slot['end_time'],
    //                             'duration_hours' => $slot['duration_hours'],
    //                             'session_no' => $slot['session_no'],
    //                             'created_at' => now(),
    //                             'updated_at' => now()
    //                         ];
    //                     }
    //                 }
    //             }

    //             DB::insert("
    //                 INSERT INTO populations (
    //                     population_id, academic_year, semester, theory_credit_to_slots, practical_credit_to_slots,
    //                     population_size, crossover_id, selection_id, mutation_rate, max_generations,
    //                     elitism_count, elite_chromosome_ids, crossover_rate, selection_size,
    //                     mutation_id, stop_at_first_valid, status, created_at, updated_at
    //                 )
    //                 SELECT ?, academic_year, semester, theory_credit_to_slots, practical_credit_to_slots,
    //                        population_size, crossover_id, selection_id, mutation_rate, max_generations,
    //                        elitism_count, elite_chromosome_ids, crossover_rate, selection_size,
    //                        mutation_id, stop_at_first_valid, status, ?, ?
    //                 FROM populations WHERE population_id = ?
    //             ", [$newPopulationId, now(), now(), $populationId]);

    //             foreach (array_chunk($chromosomesData, 100) as $chunk) {
    //                 DB::table('chromosomes')->insert($chunk);
    //             }

    //             foreach (array_chunk($genesData, 100) as $chunk) {
    //                 DB::table('genes')->insert($chunk);
    //             }

    //             foreach (array_chunk($timeslotsData, 100) as $chunk) {
    //                 DB::table('timeslots')->insert($chunk);
    //             }

    //             Log::info("Population {$newPopulationId} saved: " .
    //                      count($chromosomesData) . " chromosomes, " .
    //                      count($genesData) . " genes, " .
    //                      count($timeslotsData) . " timeslots.");
    //         });

    //         Log::info("Population save completed successfully");

    //     } catch (Exception $e) {
    //         Log::error("Error saving population: " . $e->getMessage());
    //         throw new Exception('Error saving population: ' . $e->getMessage());
    //     }
    // }
    public function savePopulation($populationId, $population)
    {
        try {
            Log::info("Starting population save for Population ID: {$populationId}");

            DB::transaction(function () use ($populationId, $population) {
                // 1️⃣ Get current max IDs
                $currentIds = [
                    'population_id' => $this->getMaxId('populations', 'population_id'),
                    'chromosome_id' => $this->getMaxId('chromosomes', 'chromosome_id'),
                    'gene_id' => $this->getMaxId('genes', 'gene_id'),
                    'timeslot_id' => $this->getMaxId('timeslots', 'timeslot_id'),
                ];

                Log::info("Starting IDs - Chromosome: {$currentIds['chromosome_id']}, Gene: {$currentIds['gene_id']}, Timeslot: {$currentIds['timeslot_id']}");

                // 2️⃣ Assign new IDs to all nested structures
                foreach ($population as &$chromosome) {
                    $chromosome['chromosome_id'] = ++$currentIds['chromosome_id'];

                    foreach ($chromosome['genes'] as &$gene) {
                        $gene['gene_id'] = ++$currentIds['gene_id'];
                        $gene['chromosome_id'] = $chromosome['chromosome_id'];

                        // ✅ Critical: Increment timeslot_id for EACH timeslot
                        foreach ($gene['timeslots'] as &$slot) {
                            $slot['timeslot_id'] = ++$currentIds['timeslot_id'];
                            $slot['gene_id'] = $gene['gene_id'];
                        }
                        unset($slot); // Break reference
                    }
                    unset($gene); // Break reference
                }
                unset($chromosome); // Break reference

                $newPopulationId = ++$currentIds['population_id'];

                Log::info("Ending IDs - Chromosome: {$currentIds['chromosome_id']}, Gene: {$currentIds['gene_id']}, Timeslot: {$currentIds['timeslot_id']}");

                // 3️⃣ Prepare data for inserts
                $chromosomesData = array_map(function ($ch) {
                    return [
                        'chromosome_id' => $ch['chromosome_id'],
                        'population_id' => $ch['population_id'],
                        'generation_number' => 1,
                        'fitness_value' => $ch['fitness'],
                        'is_best_of_generation' => $ch['is_fittest'],
                        'student_conflict_penalty' => 0,
                        'teacher_conflict_penalty' => 0,
                        'room_conflict_penalty' => 0,
                        'capacity_conflict_penalty' => 0,
                        'room_type_conflict_penalty' => 0,
                        'teacher_eligibility_conflict_penalty' => 0,
                        'penalty_value' => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }, $population);

                $genesData = [];
                $timeslotsData = [];

                foreach ($population as $ch) {
                    foreach ($ch['genes'] as $g) {
                        $genesData[] = [
                            'gene_id' => $g['gene_id'],
                            'chromosome_id' => $g['chromosome_id'],
                            'lecture_unique_id' => 'gene_' . $g['gene_id'],
                            'section_id' => $g['section_id'],
                            'instructor_id' => $g['instructor_id'],
                            'room_id' => $g['room_id'],
                            'timeslot_ids' => json_encode(array_column($g['timeslots'], 'timeslot_id')),
                            'block_type' => $this->determineBlockType($g),
                            'block_duration' => $this->calculateBlockDuration($g),
                            'is_continuous' => 1,
                            'student_group_id' => json_encode([]),
                            'created_at' => now(),
                            'updated_at' => now()
                        ];

                        foreach ($g['timeslots'] as $slot) {
                            $timeslotsData[] = [
                                'timeslot_id' => $slot['timeslot_id'],
                                'gene_id' => $slot['gene_id'],
                                'timeslot_day' => $slot['timeslot_day'],
                                'start_time' => $slot['start_time'],
                                'end_time' => $slot['end_time'],
                                'duration_hours' => $slot['duration_hours'],
                                'session_no' => $slot['session_no'],
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                        }
                    }
                }

                // 4️⃣ Clone population row
          //      DB::insert("
           //     INSERT INTO populations (
         //           population_id, academic_year, semester, theory_credit_to_slots, practical_credit_to_slots,
         //           population_size, crossover_id, selection_id, mutation_rate, max_generations,
        //            elitism_count, elite_chromosome_ids, crossover_rate, selection_size,
         //           mutation_id, stop_at_first_valid, status, created_at, updated_at
                )
       //         SELECT ?, academic_year, semester, theory_credit_to_slots, practical_credit_to_slots,
          //             population_size, crossover_id, selection_id, mutation_rate, max_generations,
         //              elitism_count, elite_chromosome_ids, crossover_rate, selection_size,
        //               mutation_id, stop_at_first_valid, status, ?, ?
        //        FROM populations WHERE population_id = ?
         //   ", [$newPopulationId, now(), now(), $populationId]);

                // 5️⃣ Insert chromosomes
                foreach (array_chunk($chromosomesData, 100) as $chunk) {
                    DB::table('chromosomes')->insert($chunk);
                }

                // 6️⃣ Insert genes
                foreach (array_chunk($genesData, 100) as $chunk) {
                    DB::table('genes')->insert($chunk);
                }

                // 7️⃣ Insert timeslots
                foreach (array_chunk($timeslotsData, 100) as $chunk) {
                    DB::table('timeslots')->insert($chunk);
                }

                Log::info("Population {$newPopulationId} saved: " .
                    count($chromosomesData) . " chromosomes, " .
                    count($genesData) . " genes, " .
                    count($timeslotsData) . " timeslots.");
            });

            Log::info("Population save completed successfully");
        } catch (Exception $e) {
            Log::error("Error saving population: " . $e->getMessage());
            throw new Exception('Error saving population: ' . $e->getMessage());
        }
    }

    private function getMaxId($table, $pkColumn)
    {
        $result = DB::selectOne("SELECT COALESCE(MAX({$pkColumn}), 0) as max_id FROM {$table}");
        return $result->max_id;
    }

    private function determineBlockType($gene)
    {
        if (isset($gene['subject_category_id'])) {
            return $gene['subject_category_id'] == 1 ? 'theory' : 'practical';
        }
        return 'theory';
    }

    private function calculateBlockDuration($gene)
    {
        if (empty($gene['timeslots'])) {
            return 0;
        }

        $totalHours = array_sum(array_column($gene['timeslots'], 'duration_hours'));
        return $totalHours * 60;
    }

    public function updatePopulationFitness($populationId, $population)
    {
        try {
            Log::info("Updating fitness for Population ID: {$populationId}");

            DB::transaction(function () use ($populationId, $population) {
                foreach ($population as $chromosome) {
                    DB::table('chromosomes')
                        ->where('chromosome_id', $chromosome['chromosome_id'])
                        ->where('population_id', $populationId)
                        ->update([
                            'fitness_value' => $chromosome['fitness'],
                            'is_best_of_generation' => $chromosome['is_fittest'] ?? 0,
                            'updated_at' => now()
                        ]);
                }
            });

            Log::info("Fitness values updated for " . count($population) . " chromosomes");
        } catch (Exception $e) {
            Log::error("Error updating population fitness: " . $e->getMessage());
            throw new Exception('Error updating population fitness: ' . $e->getMessage());
        }
    }

    public function getPopulationStats($populationId)
    {
        return [
            'chromosomes_count' => DB::table('chromosomes')->where('population_id', $populationId)->count(),
            'genes_count' => DB::table('genes')
                ->join('chromosomes', 'genes.chromosome_id', '=', 'chromosomes.chromosome_id')
                ->where('chromosomes.population_id', $populationId)
                ->count(),
            'timeslots_count' => DB::table('timeslots')
                ->join('genes', 'timeslots.gene_id', '=', 'genes.gene_id')
                ->join('chromosomes', 'genes.chromosome_id', '=', 'chromosomes.chromosome_id')
                ->where('chromosomes.population_id', $populationId)
                ->count(),
            'best_fitness' => DB::table('chromosomes')
                ->where('population_id', $populationId)
                ->max('fitness_value'),
            'average_fitness' => DB::table('chromosomes')
                ->where('population_id', $populationId)
                ->avg('fitness_value')
        ];
    }
}
