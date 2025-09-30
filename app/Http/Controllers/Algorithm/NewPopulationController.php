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

                // Load operator types for create modal
            $crossoverTypes = CrossoverType::where('is_active', true)->get();
            $selectionTypes = SelectionType::where('is_active', true)->get();
            $mutationTypes = MutationType::where('is_active', true)->get();

            return view('algorithm.new-system.populations.index', compact('populations',
            'crossoverTypes',
                'selectionTypes',
                'mutationTypes'));

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

            return redirect()->route('new-algorithm.populations.results', $id)
                ->with('success', 'Genetic Algorithm completed successfully');

        } catch (Exception $e) {
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

            return view('algorithm.new-system.populations.results', compact(
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
}
