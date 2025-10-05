<?php

namespace App\Http\Controllers\Algorithm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\PopulationGeneratorServiceNew;
use App\Services\GeneticAlgorithmServiceNew;
use App\Services\PopulationSaveServiceNew;
use App\Services\FitnessCalculatorServiceNew;
use App\Models\Population;
use App\Models\Chromosome;
use App\Models\CrossoverType;
use App\Models\SelectionType;
use App\Models\MutationType;
use Exception;
use Illuminate\Support\Facades\DB;

class NewPopulationController extends Controller
{
    public function index()
    {
        try {
            $populations = Population::with(['crossoverType', 'selectionType', 'mutationType'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            $crossoverTypes = CrossoverType::where('is_active', true)->get();
            $selectionTypes = SelectionType::where('is_active', true)->get();
            $mutationTypes = MutationType::where('is_active', true)->get();

            return view('algorithm.new-system.populations.index', compact(
                'populations',
                'crossoverTypes',
                'selectionTypes',
                'mutationTypes'
            ));
        } catch (Exception $e) {
            Log::error("Error loading populations page: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading populations page: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $crossoverTypes = CrossoverType::where('is_active', true)->get();
            $selectionTypes = SelectionType::where('is_active', true)->get();
            $mutationTypes = MutationType::where('is_active', true)->get();

            $populationGenerator = new PopulationGeneratorServiceNew();
            $validation = $populationGenerator->validateGenerationParams(50);

            return view('algorithm.new-system.populations.create', compact(
                'crossoverTypes',
                'selectionTypes',
                'mutationTypes',
                'validation'
            ));
        } catch (Exception $e) {
            Log::error("Error loading create population page: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading create population page: ' . $e->getMessage());
        }
    }

    public function generatePopulation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'academic_year' => 'required|integer|min:2020|max:2030',
                'semester' => 'required|integer|min:1|max:2',
                'population_size' => 'required|integer|min:10|max:1000',
                'max_generations' => 'required|integer|min:1|max:1000',
                'crossover_rate' => 'required|numeric|min:0|max:1',
                'mutation_rate' => 'required|numeric|min:0|max:1',
                'elitism_count' => 'required|integer|min:1|max:50',
                'selection_size' => 'required|integer|min:2|max:20',
                'crossover_id' => 'required|exists:crossover_types,crossover_id',
                'selection_id' => 'required|exists:selection_types,selection_type_id',
                'mutation_id' => 'required|exists:mutation_types,mutation_id',
                'theory_credit_to_slots' => 'required|integer|min:1|max:5',
                'practical_credit_to_slots' => 'required|integer|min:1|max:8',
                'stop_at_first_valid' => 'boolean'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', 'Validation failed');
            }

            $populationGenerator = new PopulationGeneratorServiceNew();
            $validation = $populationGenerator->validateGenerationParams(
                $request->population_size,
                $request->max_generations
            );

            if (!$validation['valid']) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'System requirements not met: ' . implode(', ', $validation['errors']));
            }

            $populationConfig = [
                'academic_year' => $request->academic_year,
                'semester' => $request->semester,
                'theory_credit_to_slots' => $request->theory_credit_to_slots,
                'practical_credit_to_slots' => $request->practical_credit_to_slots,
                'population_size' => $request->population_size,
                'crossover_id' => $request->crossover_id,
                'selection_id' => $request->selection_id,
                'mutation_rate' => $request->mutation_rate,
                'max_generations' => $request->max_generations,
                'elitism_count' => $request->elitism_count,
                'crossover_rate' => $request->crossover_rate,
                'selection_size' => $request->selection_size,
                'mutation_id' => $request->mutation_id,
                'stop_at_first_valid' => $request->boolean('stop_at_first_valid'),
                'status' => 'running',
                'start_time' => now()
            ];

            set_time_limit(600);
            $population = $populationGenerator->generateAndSavePopulation($populationConfig);

            return redirect()->route('new-algorithm.populations.index')
                ->with('success', "Population {$population->population_id} generated successfully with {$request->population_size} chromosomes");
        } catch (Exception $e) {
            Log::error("Error generating population: " . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error generating population: ' . $e->getMessage());
        }
    }

    public function runGA(Request $request, $id)
    {
        try {
            $population = Population::findOrFail($id);

            if ($population->status === 'running') {
                return redirect()->back()->with('warning', 'Population is already running');
            }

            set_time_limit(1800);
            $gaService = new GeneticAlgorithmServiceNew();
            $result = $gaService->applyGA($id);

            // ✅ بعد انتهاء GA، نحدّث الحالة وأفضل كروموسوم
            $bestChromosome = Chromosome::where('population_id', $id)
                ->orderByDesc('fitness_value')
                ->orderBy('penalty_value') // أقل عقوبة أولًا لو نفس الـ fitness
                ->first();

            $updateData = [
                'status' => 'completed',
                'end_time' => now()
            ];

            if ($bestChromosome) {
                $updateData['best_chromosome_id'] = $bestChromosome->chromosome_id;
            }

            Population::where('population_id', $id)->update($updateData);

            return redirect()->route('new-algorithm.populations.results', $id)
                ->with('success', 'Genetic Algorithm completed successfully');
        } catch (Exception $e) {
            // في حال فشل GA، نحدّث الحالة إلى 'failed'
            Population::where('population_id', $id)->update([
                'status' => 'failed',
                'end_time' => now()
            ]);

            Log::error("Error running GA: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error running Genetic Algorithm: ' . $e->getMessage());
        }
    }

    public function showResults($id)
    {
        try {
            $population = Population::with(['bestChromosome'])->findOrFail($id);

            $gaService = new GeneticAlgorithmServiceNew();
            $stats = $gaService->getGAStats($id);

            $bestChromosomes = Chromosome::where('population_id', $id)
                ->orderBy('penalty_value', 'asc')
                ->orderByDesc('fitness_value')
                ->take(10)
                ->get();

            $saveService = new PopulationSaveServiceNew();
            $saveStats = $saveService->getPopulationStats($id);

            // return view('algorithm.new-system.populations.results', compact(
            return view('algorithm.new-system.populations.best-chromosomes', compact(
                'population',
                'stats',
                'bestChromosomes',
                'saveStats'
            ));
        } catch (Exception $e) {
            Log::error("Error loading results: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading results: ' . $e->getMessage());
        }
    }

    public function calculateFitness(Request $request, $id)
    {
        try {
            $population = Population::findOrFail($id);

            set_time_limit(300);
            $this->performFitnessCalculation($id);

            return redirect()->back()->with('success', 'Fitness calculation completed successfully');
        } catch (Exception $e) {
            Log::error("Error calculating fitness: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error calculating fitness: ' . $e->getMessage());
        }
    }

    private function performFitnessCalculation($populationId)
    {
        $gaService = new GeneticAlgorithmServiceNew();
        $population = $gaService->loadPopulation($populationId);

        $fitnessCalculator = new FitnessCalculatorServiceNew();

        foreach ($population as &$chromosome) {
            $chromosome['fitness'] = $fitnessCalculator->calculateFitness($chromosome);
        }

        $saveService = new PopulationSaveServiceNew();
        $saveService->updatePopulationFitness($populationId, $population);
    }

    public function getPopulationStats($id)
    {
        try {
            $saveService = new PopulationSaveServiceNew();
            $stats = $saveService->getPopulationStats($id);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getGenerationStats(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'population_size' => 'required|integer|min:1',
                'sections_count' => 'integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $populationGenerator = new PopulationGeneratorServiceNew();
            $sectionsCount = $request->sections_count ?? DB::table('sections')->count();

            $stats = $populationGenerator->getGenerationStats(
                $request->population_size,
                $sectionsCount
            );

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function validateParams(Request $request)
    {
        try {
            $populationSize = $request->population_size ?? 50;
            $maxGenerations = $request->max_generations ?? 100;

            $populationGenerator = new PopulationGeneratorServiceNew();
            $validation = $populationGenerator->validateGenerationParams($populationSize, $maxGenerations);

            return response()->json([
                'success' => $validation['valid'],
                'data' => $validation
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function showBestChromosomes($id)
    {
        try {
            $population = Population::findOrFail($id);

            // جلب أفضل 10 كروموسومات
            $bestChromosomes = Chromosome::where('population_id', $id)
                ->orderByDesc('fitness_value')
                ->orderBy('penalty_value', 'asc')
                ->take(10)
                ->get();

            return view('algorithm.new-system.populations.best-chromosomes', compact(
                'population',
                'bestChromosomes'
            ));
        } catch (Exception $e) {
            Log::error("Error loading best chromosomes: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading best chromosomes: ' . $e->getMessage());
        }
    }

    public function showChromosomeSchedule($populationId, $chromosomeId)
    {
        try {
            $population = Population::findOrFail($populationId);
            $chromosome = Chromosome::where('population_id', $populationId)
                ->where('chromosome_id', $chromosomeId)
                ->firstOrFail();

            $sessions = DB::table('genes as g')
                ->join('chromosomes as c', 'g.chromosome_id', '=', 'c.chromosome_id')
                ->leftJoin('timeslots as t', 'g.gene_id', '=', 't.gene_id')
                ->join('sections as s', 'g.section_id', '=', 's.id')
                ->join('plan_subjects as ps', 's.plan_subject_id', '=', 'ps.id')
                ->join('subjects as sub', 'ps.subject_id', '=', 'sub.id')
                ->join('instructors as i', 'g.instructor_id', '=', 'i.id')
                ->join('rooms as r', 'g.room_id', '=', 'r.id')
                ->leftJoin('plan_groups as pg', function ($join) {
                    $join->on('pg.section_id', '=', 's.id')
                        ->on('pg.plan_id', '=', 'ps.plan_id')
                        ->on('pg.plan_level', '=', 'ps.plan_level')
                        ->on('pg.semester', '=', 'ps.plan_semester');
                })
                ->leftJoin('plans as p', 'ps.plan_id', '=', 'p.id')
                ->where('c.chromosome_id', $chromosomeId)
                ->where('c.population_id', $populationId)
                ->select(
                    'pg.group_no',
                    'pg.plan_id',
                    'pg.plan_level',
                    'pg.semester as plan_semester',
                    'p.plan_name',
                    'sub.subject_name',
                    'sub.subject_no',
                    'i.instructor_name',
                    'r.room_no',
                    'r.room_name',
                    DB::raw('MIN(t.timeslot_day) as timeslot_day'),
                    DB::raw('MIN(t.start_time) as start_time'),
                    DB::raw('MAX(t.end_time) as end_time'),
                    DB::raw('SUM(t.duration_hours) as duration_hours'),
                    's.activity_type',
                    's.student_count',
                    'g.gene_id'
                )
                ->groupBy(
                    'g.gene_id',
                    'pg.group_no',
                    'pg.plan_id',
                    'pg.plan_level',
                    'pg.semester',
                    'p.plan_name',
                    'sub.subject_name',
                    'sub.subject_no',
                    'i.instructor_name',
                    'r.room_no',
                    'r.room_name',
                    's.activity_type',
                    's.student_count'
                )
                ->orderBy('pg.plan_id')
                ->orderBy('pg.plan_level')
                ->orderBy('pg.group_no')
                ->get();

            $groups = $sessions->groupBy(function ($item) {
                return "{$item->plan_id}_{$item->plan_level}_{$item->group_no}";
            })->map(function ($groupSessions) {
                $first = $groupSessions->first();
                return [
                    'name' => "{$first->plan_name} - Level {$first->plan_level} - Group {$first->group_no}",
                    'students' => $first->student_count,
                    'sessions' => $groupSessions
                ];
            });

            $days = [
                // 0 => 'Saturday',
                1 => 'Sunday',
                2 => 'Monday',
                3 => 'Tuesday',
                4 => 'Wednesday',
                5 => 'Thursday',
                // 6 => 'Friday'
            ];

            return view('algorithm.new-system.populations.chromosome-schedule', compact(
                'population',
                'chromosome',
                'groups',
                'days'
            ));
        } catch (Exception $e) {
            Log::error("Error loading chromosome schedule: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading chromosome schedule: ' . $e->getMessage());
        }
    }

    public function deletePopulation($id)
    {
        try {
            DB::beginTransaction();

            $population = Population::findOrFail($id);

            // التحقق من أن Population ليس قيد التشغيل
            if ($population->status === 'running') {
                return redirect()->back()->with('error', 'Cannot delete a running population. Please wait until it completes or fails.');
            }

            Log::info("Starting deletion of population {$id}");

            // حذف Timeslots أولاً
            $timeslotsDeleted = DB::table('timeslots')
                ->whereIn('gene_id', function ($query) use ($id) {
                    $query->select('gene_id')
                        ->from('genes')
                        ->whereIn('chromosome_id', function ($subQuery) use ($id) {
                            $subQuery->select('chromosome_id')
                                ->from('chromosomes')
                                ->where('population_id', $id);
                        });
                })
                ->delete();

            Log::info("Deleted {$timeslotsDeleted} timeslots");

            // حذف Genes
            $genesDeleted = DB::table('genes')
                ->whereIn('chromosome_id', function ($query) use ($id) {
                    $query->select('chromosome_id')
                        ->from('chromosomes')
                        ->where('population_id', $id);
                })
                ->delete();

            Log::info("Deleted {$genesDeleted} genes");

            // حذف Chromosomes
            $chromosomesDeleted = DB::table('chromosomes')
                ->where('population_id', $id)
                ->delete();

            Log::info("Deleted {$chromosomesDeleted} chromosomes");

            // حذف Population
            $population->delete();

            DB::commit();

            Log::info("Population {$id} and all its dependencies deleted successfully");

            return redirect()->route('new-algorithm.populations.index')
                ->with('success', "Population #{$id} deleted successfully. Removed: {$chromosomesDeleted} chromosomes, {$genesDeleted} genes, {$timeslotsDeleted} timeslots.");
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error deleting population {$id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error deleting population: ' . $e->getMessage());
        }
    }
}
