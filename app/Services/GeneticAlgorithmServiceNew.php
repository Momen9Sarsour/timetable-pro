<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Room;
use Exception;

class GeneticAlgorithmServiceNew
{
    private $roomCapacitiesMap = [];
    private $sectionLoadsMap = [];
    private $planGroupsSections = [];
    private $fitnessCalculator;

    public function __construct()
    {
        $this->fitnessCalculator = new FitnessCalculatorServiceNew();
    }

    public function applyGA($populationId)
    {
        try {
            Log::info("Starting Genetic Algorithm for Population ID: {$populationId}");

            $populationInfo = $this->getPopulationById($populationId);
            $this->loadRequiredData();

            if (!$populationInfo) {
                throw new Exception('Population not found!');
            }

            $populationData = $populationInfo[0];
            $population_id = $populationData->population_id;
            $population_size = $populationData->population_size;
            $max_generations = $populationData->max_generations;
            $crossover_rate = $populationData->crossover_rate;
            $mutation_rate = $populationData->mutation_rate;
            $elitism_count = $populationData->elitism_count;
            $selection_size = $populationData->selection_size;

            $population = $this->loadPopulation($population_id);

            foreach ($population as &$chromosome) {
                $chromosome['fitness'] = $this->fitnessCalculator->calculateFitness($chromosome);
            }

            $bestFitness = max(array_map(fn($c) => $c['fitness'], $population));
            $generation = 0;

            Log::info("Initial population loaded. Best fitness: " . number_format($bestFitness, 6));

            while ($generation < $max_generations && $bestFitness < 1) {
                Log::info("Generation " . ($generation + 1) . ", Best Fitness: " . number_format($bestFitness, 6));

                $eliteIds = collect($population)
                    ->map(fn($ind) => ['id' => $ind['chromosome_id'], 'fitness' => $ind['fitness']])
                    ->sortByDesc('fitness')
                    ->take($elitism_count)
                    ->pluck('id')
                    ->toArray();

                $sectionsRoomsList = $this->getSectionsRoomsList();

                $newPopulation = $this->applyCrossoverToPopulation($population, [
                    'crossover_rate' => $crossover_rate,
                    'elitism_count' => $elitism_count,
                    'selection_size' => $selection_size,
                    'sectionsRoomsList' => $sectionsRoomsList,
                    'eliteIds' => $eliteIds
                ]);

                $newPopulation = array_map(function($c) use ($eliteIds, $mutation_rate, $sectionsRoomsList) {
                    return in_array($c['chromosome_id'], $eliteIds)
                        ? $c
                        : $this->applyMutation($c, $mutation_rate, $sectionsRoomsList);
                }, $newPopulation);

                foreach ($newPopulation as &$chromosome) {
                    $chromosome['fitness'] = $this->fitnessCalculator->calculateFitness($chromosome);
                }

                $bestFitness = max(array_map(fn($c) => $c['fitness'], $newPopulation));
                $population = $newPopulation;
                $generation++;

                $this->updatePopulationProgress($population_id, $generation, $bestFitness, $population);

                if ($bestFitness >= 1) {
                    Log::info("Optimal solution found with fitness 1 at generation $generation!");
                    break;
                }
            }

            $this->savePopulation($population_id, $population);
            $this->finalizePopulation($population_id, $population);

            Log::info("Final population saved after $generation generations with best fitness: " . number_format($bestFitness, 6));

            return $population;

        } catch (Exception $e) {
            DB::table('populations')->where('population_id', $populationId)->update([
                'status' => 'failed',
                'end_time' => now()
            ]);

            Log::error("Error in Genetic Algorithm: " . $e->getMessage());
            throw new Exception('Error applying Genetic Algorithm: ' . $e->getMessage());
        }
    }

    private function updatePopulationProgress($populationId, $generation, $bestFitness, $population)
    {
        $bestChromosome = collect($population)
            ->sortByDesc('fitness')
            ->first();

        DB::table('populations')->where('population_id', $populationId)->update([
            'best_chromosome_id' => $bestChromosome['chromosome_id'] ?? null,
            'updated_at' => now()
        ]);
    }

    private function finalizePopulation($populationId, $population)
    {
        $bestChromosome = collect($population)
            ->sortByDesc('fitness')
            ->first();

        DB::table('populations')->where('population_id', $populationId)->update([
            'status' => 'completed',
            'end_time' => now(),
            'best_chromosome_id' => $bestChromosome['chromosome_id'] ?? null,
            'updated_at' => now()
        ]);
    }

    private function loadRequiredData()
    {
        $roomRows = Room::all();
        $sectionsRows = $this->getSectionsList();
        $this->planGroupsSections = $this->getPlanGroupsSectionsList();

        foreach ($roomRows as $r) {
            $this->roomCapacitiesMap[$r->id] = $r->room_size;
        }

        foreach ($sectionsRows as $c) {
            $this->sectionLoadsMap[$c->id] = $c->student_count;
        }
    }

    private function getPopulationById($id)
    {
        return DB::table('populations')->where('population_id', $id)->get()->toArray();
    }

    private function getSectionsList()
    {
        return DB::table('sections as s')
            ->join('plan_subjects as ps', 's.plan_subject_id', '=', 'ps.id')
            ->join('subjects as sub', 'ps.subject_id', '=', 'sub.id')
            ->select('s.*', 'sub.subject_category_id','sub.subject_hours')
            ->get();
    }

    private function getSectionsRoomsList()
    {
        $rooms = Room::all();
        $sectionsList = $this->getSectionsList();
        $sectionsRoomsList = [];

        foreach ($sectionsList as $section) {
            // ✅ التعديل الأساسي: استخدام room_category_id بدلاً من room_size
            $filteredRooms = $rooms->filter(function($room) use ($section) {
                return $room->room_category_id == $section->subject_category_id;
            });

            $sectionArray = (array)$section;
            $sectionArray['rooms'] = $filteredRooms->values()->toArray();
            $sectionsRoomsList[] = $sectionArray;
        }

        return $sectionsRoomsList;
    }

    private function getPlanGroupsSectionsList()
    {
        return DB::table('plan_groups as pg')
            ->select(
                'pg.plan_id',
                'pg.plan_level',
                'pg.semester as plan_semester',
                'pg.group_no',
                DB::raw('GROUP_CONCAT(pg.section_id) as plan_sections'),
                DB::raw('COUNT(pg.section_id) as plan_sections_count')
            )
            ->groupBy('pg.plan_id', 'pg.plan_level', 'pg.semester', 'pg.group_no')
            ->get()
            ->toArray();
    }

    private function getPopulationDetails($populationId)
    {
        return DB::table('genes as g')
            ->join('chromosomes as c', 'g.chromosome_id', '=', 'c.chromosome_id')
            ->join('timeslots as t', 'g.gene_id', '=', 't.gene_id')
            ->join('sections as s', 'g.section_id', '=', 's.id')
            ->join('plan_subjects as ps', 's.plan_subject_id', '=', 'ps.id')
            ->join('subjects as sub', 'ps.subject_id', '=', 'sub.id')
            ->where('c.population_id', $populationId)
            ->select(
                'c.population_id', 'c.chromosome_id', 'c.fitness_value', 'c.is_best_of_generation',
                'g.gene_id', 'g.section_id', 'g.instructor_id', 'g.room_id',
                't.timeslot_id', 't.timeslot_day', 't.start_time', 't.end_time',
                't.duration_hours', 't.session_no',
                'sub.subject_hours', 'sub.subject_category_id'
            )
            ->get()
            ->toArray();
    }

    public function loadPopulation($populationId)
    {
        $population_details = $this->getPopulationDetails($populationId);
        $populationMap = [];

        foreach ($population_details as $row) {
            $row = (array)$row;

            if (!isset($populationMap[$row['chromosome_id']])) {
                $populationMap[$row['chromosome_id']] = [
                    'population_id' => $row['population_id'],
                    'chromosome_id' => $row['chromosome_id'],
                    'fitness' => $row['fitness_value'],
                    'is_fittest' => $row['is_best_of_generation'],
                    'genes' => []
                ];
            }

            $chromosome = &$populationMap[$row['chromosome_id']];

            if (!isset($chromosome['genes'][$row['gene_id']])) {
                $chromosome['genes'][$row['gene_id']] = [
                    'gene_id' => $row['gene_id'],
                    'chromosome_id' => $row['chromosome_id'],
                    'section_id' => $row['section_id'],
                    'instructor_id' => $row['instructor_id'],
                    'room_id' => $row['room_id'],
                    'subject_hours' => $row['subject_hours'],
                    'subject_category_id' => $row['subject_category_id'],
                    'timeslots' => []
                ];
            }

            if ($row['timeslot_id']) {
                $chromosome['genes'][$row['gene_id']]['timeslots'][] = [
                    'timeslot_day' => (int)$row['timeslot_day'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'duration_hours' => $row['duration_hours'],
                    'session_no' => $row['session_no'],
                    'gene_id' => $row['gene_id']
                ];
            }
        }

        return array_map(function($chromosome) {
            $chromosome['genes'] = array_values($chromosome['genes']);
            return $chromosome;
        }, array_values($populationMap));
    }

    private function applyCrossoverToPopulation($population, $config)
    {
        $crossover_rate = $config['crossover_rate'] ?? 0.87;
        $elitism_count = $config['elitism_count'] ?? 3;
        $selection_size = $config['selection_size'] ?? 5;
        $sectionsRoomsList = $config['sectionsRoomsList'] ?? [];
        $eliteIds = $config['eliteIds'] ?? [];

        $newPopulation = [];

        foreach ($population as $i => $firstParent) {
            if (in_array($firstParent['chromosome_id'], $eliteIds)) {
                $newPopulation[] = $firstParent;
                continue;
            }

            if (mt_rand() / mt_getrandmax() < $crossover_rate) {
                $secondParent = $this->tournamentSelection($population, $selection_size);
                $crossoverType = $this->pickRandom(['one_point', 'two_point', 'uniform']);
                $offspring = $this->applyCrossover($firstParent, $secondParent, $crossoverType, ['swapTimeslots' => true, 'swapRoom' => true]);
                $newPopulation[] = $offspring;
            } else {
                $newPopulation[] = $firstParent;
            }
        }
        return $newPopulation;
    }

    private function applyCrossover($parent1, $parent2, $crossoverType, $options = [])
    {
        switch ($crossoverType) {
            case 'one_point':
                return $this->onePointCrossover($parent1, $parent2, $options);
            case 'two_point':
                return $this->twoPointCrossover($parent1, $parent2, $options);
            case 'uniform':
                return $this->uniformCrossover($parent1, $parent2, $options);
            default:
                throw new Exception("Unknown crossover type: {$crossoverType}");
        }
    }

    private function onePointCrossover($parent1, $parent2, $options)
    {
        $crossoverPoint = rand(0, count($parent1['genes']) - 1);
        $applyGeneOptionsFlag = ($options['swapTimeslots'] ?? false) || ($options['swapRoom'] ?? false);

        $offspringGenes = array_map(function($gene, $i) use ($crossoverPoint, $applyGeneOptionsFlag, $parent2, $options) {
            if ($i < $crossoverPoint || !$applyGeneOptionsFlag) {
                return array_merge([], $gene);
            } else {
                return $this->applyGeneOptions($gene, $parent2['genes'][$i], $options);
            }
        }, $parent1['genes'], array_keys($parent1['genes']));

        return [
            'population_id' => $parent1['population_id'],
            'chromosome_id' => $parent1['chromosome_id'],
            'fitness' => 0,
            'is_fittest' => -1,
            'genes' => $offspringGenes,
        ];
    }

    private function twoPointCrossover($parent1, $parent2, $options = [])
    {
        $size = count($parent1['genes']);
        $point1 = rand(0, $size - 1);
        $point2 = rand(0, $size - 1);

        if ($point1 > $point2) {
            list($point1, $point2) = [$point2, $point1];
        }

        $applyGeneOptionsFlag = ($options['swapTimeslots'] ?? false) || ($options['swapRoom'] ?? false);

        $offspringGenes = array_map(function($gene, $i) use ($point1, $point2, $applyGeneOptionsFlag, $parent2, $options) {
            if ($i >= $point1 && $i < $point2) {
                return $applyGeneOptionsFlag
                    ? $this->applyGeneOptions($gene, $parent2['genes'][$i], $options)
                    : array_merge([], $parent2['genes'][$i]);
            } else {
                return array_merge([], $gene);
            }
        }, $parent1['genes'], array_keys($parent1['genes']));

        return [
            'population_id' => $parent1['population_id'],
            'chromosome_id' => $parent1['chromosome_id'],
            'genes' => $offspringGenes,
            'fitness' => 0,
            'is_fittest' => -1,
        ];
    }

    private function uniformCrossover($parent1, $parent2, $options = [])
    {
        $applyGeneOptionsFlag = ($options['swapTimeslots'] ?? false) || ($options['swapRoom'] ?? false);

        $offspringGenes = array_map(function($gene, $i) use ($applyGeneOptionsFlag, $parent2, $options) {
            $swap = (mt_rand() / mt_getrandmax()) < 0.5;

            if ($swap) {
                return $applyGeneOptionsFlag
                    ? $this->applyGeneOptions($gene, $parent2['genes'][$i], $options)
                    : array_merge([], $parent2['genes'][$i]);
            } else {
                return array_merge([], $gene);
            }
        }, $parent1['genes'], array_keys($parent1['genes']));

        return [
            'population_id' => $parent1['population_id'],
            'chromosome_id' => $parent1['chromosome_id'],
            'genes' => $offspringGenes,
            'fitness' => 0,
            'is_fittest' => -1,
        ];
    }

    private function applyGeneOptions($parentGene, $donorGene, $options)
    {
        $newGene = array_merge([], $parentGene);

        if ($options['swapTimeslots'] ?? false) {
            $newGene['timeslots'] = array_map(function($timeslot) use ($parentGene) {
                return array_merge($timeslot, ['gene_id' => $parentGene['gene_id']]);
            }, $donorGene['timeslots']);
        }

        if ($options['swapRoom'] ?? false) {
            $newGene['room_id'] = $donorGene['room_id'];
        }

        return $newGene;
    }

    private function applyMutation($chromosome, $mutationRate, $sectionsRoomsList)
    {
        $sectionInfoMap = [];
        foreach ($sectionsRoomsList as $sectionInfo) {
            $sectionInfoMap[$sectionInfo['id']] = $sectionInfo;
        }

        $mutated = array_merge([], $chromosome);
        $mutated['genes'] = array_map(function($gene) use ($mutationRate, $sectionInfoMap) {
            $sectionInfo = $sectionInfoMap[$gene['section_id']] ?? null;

            if ((mt_rand() / mt_getrandmax()) < $mutationRate && $sectionInfo) {
                $mutationType = $this->pickRandom(['room', 'timeslot']);

                if ($mutationType === 'room') {
                    return array_merge($gene, [
                        'room_id' => $this->getRandomRoomFromSection($sectionInfo)
                    ]);
                } else {
                    return array_merge($gene, [
                        'timeslots' => $this->getRandomTimeSlots(
                            $sectionInfo['subject_hours'],
                            $sectionInfo['subject_category_id'],
                            $gene['gene_id']
                        )
                    ]);
                }
            }
            return $gene;
        }, $chromosome['genes']);

        return $mutated;
    }

    private function getRandomRoomFromSection($sectionInfo)
    {
        $rooms = $sectionInfo['rooms'] ?? [];
        if (empty($rooms)) {
            return $sectionInfo['room_id'] ?? 0;
        }
        $randomRoom = $rooms[array_rand($rooms)];
        return $randomRoom->id ?? $randomRoom['id'] ?? 0;
    }

    private function tournamentSelection($population, $selection_size)
    {
        $bestChromosome = null;
        for ($i = 0; $i < $selection_size; $i++) {
            $randomIndex = rand(0, count($population) - 1);
            $candidate = $population[$randomIndex];
            if (!$bestChromosome || $candidate['fitness'] > $bestChromosome['fitness']) {
                $bestChromosome = $candidate;
            }
        }
        return $bestChromosome;
    }

    private function pickRandom($array)
    {
        return $array[array_rand($array)];
    }

    private function getRandomTimeSlots($subject_hours = 0, $subject_category_id = 0, $gene_id = 0)
    {
        $generator = new PopulationGeneratorServiceNew();
        return $generator->getRandomTimeSlots($subject_hours, $subject_category_id, $gene_id);
    }

    private function savePopulation($populationId, $population)
    {
        $saveService = new PopulationSaveServiceNew();
        $saveService->savePopulation($populationId, $population);
    }

    public function getGAStats($populationId)
    {
        $population = $this->loadPopulation($populationId);

        return [
            'population_size' => count($population),
            'total_genes' => array_sum(array_map(fn($c) => count($c['genes']), $population)),
            'fitness_stats' => $this->fitnessCalculator->getPopulationFitnessStats($population)
        ];
    }
}
