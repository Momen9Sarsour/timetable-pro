<?php

namespace App\Http\Controllers\Algorithm;

use Exception;
use App\Models\Gene;
use App\Models\Timeslot;
use App\Models\Crossover;
use App\Models\Chromosome;
use Illuminate\Http\Request;
use App\Models\SelectionType;
use Illuminate\Validation\Rule;
use App\Jobs\GenerateInitialPopulationJob;
use App\Jobs\GenerateTimetableJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Jobs\ContinueEvolutionJob;
use App\Services\ConflictCheckerService;
use App\Services\InitialPopulationService;
use App\Models\Population; // موديل POPULATIONS
use App\Services\GeneticAlgorithmService; // الـ Service الذي أنشأناه

class TimetableGenerationController extends Controller
{
    /**
     * Start the timetable generation process.
     * تبدأ عملية توليد الجدول بناءً على الإعدادات من المودال
     */
    public function start(Request $request)
    {
        $validatedSettings = $request->validate([
            'academic_year' => 'required|integer|digits:4',
            'semester' => 'required|integer|in:1,2,3',
            'population_size' => 'required|integer|min:10|max:500',
            'max_generations' => 'required|integer|min:10|max:10000',
            'elitism_count_chromosomes' => 'required|integer|min:1|max:100',
            'mutation_rate' => 'required|numeric|min:0|max:1',
            'crossover_type_id' => ['required', 'integer', Rule::exists('crossover_types', 'crossover_id')->where('is_active', true)],
            'selection_type_id' => ['required', 'integer', Rule::exists('selection_types', 'selection_type_id')->where('is_active', true)],
            'stop_at_first_valid' => 'nullable|boolean',
            'theory_credit_to_slots' => 'required|integer|min:1|max:4',
            'practical_credit_to_slots' => 'required|integer|min:1|max:4',
            'crossover_rate' => 'required|numeric|min:0|max:1',
            'mutation_type_id' => ['required', 'integer', Rule::exists('mutation_types', 'mutation_id')->where('is_active', true)],
            'selection_size' => 'required|integer|min:2|max:20',
        ]);

        // تحويل boolean
        $validatedSettings['stop_at_first_valid'] = $request->has('stop_at_first_valid');

        try {
            $populationRun = Population::create([
                'academic_year' => $validatedSettings['academic_year'],
                'semester' => $validatedSettings['semester'],
                'theory_credit_to_slots' => $validatedSettings['theory_credit_to_slots'],
                'practical_credit_to_slots' => $validatedSettings['practical_credit_to_slots'],
                'population_size' => $validatedSettings['population_size'],
                'crossover_id' => $validatedSettings['crossover_type_id'],
                'selection_id' => $validatedSettings['selection_type_id'],
                'mutation_rate' => $validatedSettings['mutation_rate'],
                'max_generations' => $validatedSettings['max_generations'],
                'elitism_count' => $validatedSettings['elitism_count_chromosomes'],
                'elite_chromosome_ids' => [],
                'crossover_rate' => $validatedSettings['crossover_rate'],
                'mutation_id' => $validatedSettings['mutation_type_id'],
                'selection_size' => $validatedSettings['selection_size'],
                'stop_at_first_valid' => $validatedSettings['stop_at_first_valid'],
                'status' => 'running',
            ]);

            Log::info("New Population Run created with ID: {$populationRun->population_id}. Dispatching job to queue.");

            GenerateTimetableJob::dispatch($validatedSettings, $populationRun->population_id);

            return redirect()->route('dashboard.index')
                ->with('success', "Timetable generation started for Year: {$validatedSettings['academic_year']}, Semester: {$validatedSettings['semester']}. Run ID: " . $populationRun->population_id);

        } catch (Exception $e) {
            Log::error('Failed to dispatch timetable generation job: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Could not start the generation process: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * عرض صفحة إدارة Populations
     */
    public function populationIndex()
    {
        $populations = Population::with(['parent', 'children', 'bestChromosome', 'selectionType', 'crossover', 'mutationType'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('dashboard.algorithm.population.index', compact('populations'));
    }

    /**
     * إنشاء الجيل الأول فقط (بدون إكمال الخوارزمية)
     */
    public function generateInitial(Request $request)
    {
        $validatedSettings = $request->validate([
            'academic_year' => 'required|integer|digits:4',
            'semester' => 'required|integer|in:1,2,3',
            'population_size' => 'required|integer|min:10|max:500',
            'max_generations' => 'required|integer|min:10|max:10000',
            'elitism_count_chromosomes' => 'required|integer|min:1|max:100',
            'mutation_rate' => 'required|numeric|min:0|max:1',
            'crossover_type_id' => ['required', 'integer', Rule::exists('crossover_types', 'crossover_id')->where('is_active', true)],
            'selection_type_id' => ['required', 'integer', Rule::exists('selection_types', 'selection_type_id')->where('is_active', true)],
            'stop_at_first_valid' => 'nullable|boolean',
            'theory_credit_to_slots' => 'required|integer|min:1|max:4',
            'practical_credit_to_slots' => 'required|integer|min:1|max:4',
            'crossover_rate' => 'required|numeric|min:0|max:1',
            'mutation_type_id' => ['required', 'integer', Rule::exists('mutation_types', 'mutation_id')->where('is_active', true)],
            'selection_size' => 'required|integer|min:2|max:20',
        ]);

        $validatedSettings['stop_at_first_valid'] = $request->has('stop_at_first_valid');

        try {
            $populationRun = Population::create([
                'parent_id' => null, // الجيل الأول ليس له أب
                'academic_year' => $validatedSettings['academic_year'],
                'semester' => $validatedSettings['semester'],
                'theory_credit_to_slots' => $validatedSettings['theory_credit_to_slots'],
                'practical_credit_to_slots' => $validatedSettings['practical_credit_to_slots'],
                'population_size' => $validatedSettings['population_size'],
                'crossover_id' => $validatedSettings['crossover_type_id'],
                'selection_id' => $validatedSettings['selection_type_id'],
                'mutation_rate' => $validatedSettings['mutation_rate'],
                'max_generations' => $validatedSettings['max_generations'],
                'elitism_count' => $validatedSettings['elitism_count_chromosomes'],
                'elite_chromosome_ids' => [],
                'crossover_rate' => $validatedSettings['crossover_rate'],
                'mutation_id' => $validatedSettings['mutation_type_id'],
                'selection_size' => $validatedSettings['selection_size'],
                'stop_at_first_valid' => $validatedSettings['stop_at_first_valid'],
                'status' => 'running',
            ]);

            Log::info("Initial Population created with ID: {$populationRun->population_id}");

            GenerateInitialPopulationJob::dispatch($validatedSettings, $populationRun->population_id);

            return redirect()->route('algorithm-control.populations.index')
                ->with('success', "Initial population created successfully! ID: {$populationRun->population_id}");
                
        } catch (Exception $e) {
            Log::error('Failed to create initial population: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Could not create initial population: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * إكمال الخوارزمية من population موجود
     */
    public function continueEvolution(Request $request, Population $population)
    {
        $validatedSettings = $request->validate([
            'academic_year' => 'required|integer|digits:4',
            'semester' => 'required|integer|in:1,2,3',
            'population_size' => 'required|integer|min:10|max:500',
            'max_generations' => 'required|integer|min:10|max:10000',
            'elitism_count_chromosomes' => 'required|integer|min:1|max:100',
            'mutation_rate' => 'required|numeric|min:0|max:1',
            'crossover_type_id' => ['required', 'integer', Rule::exists('crossover_types', 'crossover_id')->where('is_active', true)],
            'selection_type_id' => ['required', 'integer', Rule::exists('selection_types', 'selection_type_id')->where('is_active', true)],
            'stop_at_first_valid' => 'nullable|boolean',
            'theory_credit_to_slots' => 'required|integer|min:1|max:4',
            'practical_credit_to_slots' => 'required|integer|min:1|max:4',
            'crossover_rate' => 'required|numeric|min:0|max:1',
            'mutation_type_id' => ['required', 'integer', Rule::exists('mutation_types', 'mutation_id')->where('is_active', true)],
            'selection_size' => 'required|integer|min:2|max:20',
        ]);

        $validatedSettings['stop_at_first_valid'] = $request->has('stop_at_first_valid');

        try {
            $newPopulation = Population::create([
                'parent_id' => $population->population_id, // هذا مشتق من Population موجود
                'academic_year' => $validatedSettings['academic_year'],
                'semester' => $validatedSettings['semester'],
                'theory_credit_to_slots' => $validatedSettings['theory_credit_to_slots'],
                'practical_credit_to_slots' => $validatedSettings['practical_credit_to_slots'],
                'population_size' => $validatedSettings['population_size'],
                'crossover_id' => $validatedSettings['crossover_type_id'],
                'selection_id' => $validatedSettings['selection_type_id'],
                'mutation_rate' => $validatedSettings['mutation_rate'],
                'max_generations' => $validatedSettings['max_generations'],
                'elitism_count' => $validatedSettings['elitism_count_chromosomes'],
                'elite_chromosome_ids' => [],
                'crossover_rate' => $validatedSettings['crossover_rate'],
                'mutation_id' => $validatedSettings['mutation_type_id'],
                'selection_size' => $validatedSettings['selection_size'],
                'stop_at_first_valid' => $validatedSettings['stop_at_first_valid'],
                'status' => 'running',
            ]);

            Log::info("Continue Evolution Population created with ID: {$newPopulation->population_id}, Parent: {$population->population_id}");

            ContinueEvolutionJob::dispatch($validatedSettings, $newPopulation->population_id, $population->population_id);

            return redirect()->route('algorithm-control.populations.index')
                ->with('success', "Evolution continued! New Population ID: {$newPopulation->population_id}");
                
        } catch (Exception $e) {
            Log::error('Failed to continue evolution: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Could not continue evolution: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * حذف Population مع كل ما يتعلق به
     */
    public function destroyPopulation(Population $population)
    {
        try {
            DB::transaction(function () use ($population) {
                // حذف كل الجينات المرتبطة بالكروموسومات التابعة لهذا Population
                $chromosomeIds = $population->chromosomes()->pluck('chromosome_id');
                if ($chromosomeIds->isNotEmpty()) {
                    Gene::whereIn('chromosome_id', $chromosomeIds)->delete();
                }

                // حذف الكروموسومات
                $population->chromosomes()->delete();

                // حذف الـ Population نفسه
                $population->delete();
            });

            Log::info("Population {$population->population_id} deleted successfully with all related data");

            return redirect()->route('algorithm-control.populations.index')
                ->with('success', "Population #{$population->population_id} and all related data deleted successfully!");
                
        } catch (Exception $e) {
            Log::error("Failed to delete population {$population->population_id}: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Could not delete population: ' . $e->getMessage());
        }
    }

    /**
     * عرض تفاصيل Population (الكروموسومات التابعة له)
     */
    public function showPopulationDetails(Population $population)
    {
        $chromosomes = $population->chromosomes()
            ->with(['genes.section.planSubject.subject', 'genes.instructor', 'genes.room'])
            ->orderBy('fitness_value', 'desc')
            ->paginate(20);

        return view('dashboard.algorithm.population.details', compact('population', 'chromosomes'));
    }

    /**
     * عرض تفاصيل كروموسوم معين (الجينات التابعة له)
     */
    public function showChromosomeDetails(Chromosome $chromosome)
    {
        $genes = $chromosome->genes()
            ->with([
                'section.planSubject.subject',
                'section.instructor', // تحديث: المدرس مباشرة من sections
                'instructor',
                'room.roomType'
            ])
            ->orderBy('lecture_unique_id')
            ->get();

        return view('dashboard.algorithm.chromosome.details', compact('chromosome', 'genes'));
    }

    /**
     * عرض الجدول النهائي لكروموسوم معين
     */
    public function showTimetable(Chromosome $chromosome)
    {
        // جلب كل الجينات مع العلاقات المطلوبة
        $genes = $chromosome->genes()
            ->with([
                'section.planSubject.subject',
                'section.instructor', // تحديث: المدرس مباشرة من sections
                'instructor',
                'room.roomType'
            ])
            ->get();

        // جلب كل الـ timeslots وترتيبها
        $timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
            ->orderBy('start_time')
            ->get();

        // بناء الجدول
        $timetableData = $this->buildTimetableMatrix($genes, $timeslots);

        return view('dashboard.algorithm.timetable.show', compact('chromosome', 'timetableData', 'timeslots'));
    }

    /**
     * بناء مصفوفة الجدول للعرض
     */
    private function buildTimetableMatrix($genes, $timeslots)
    {
        $matrix = [];
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        // تهيئة المصفوفة
        foreach ($days as $day) {
            $matrix[$day] = [];
            foreach ($timeslots->where('day', $day) as $timeslot) {
                $matrix[$day][$timeslot->id] = [];
            }
        }

        // ملء المصفوفة بالجينات
        foreach ($genes as $gene) {
            $timeslotIds = is_string($gene->timeslot_ids) 
                ? json_decode($gene->timeslot_ids, true) 
                : $gene->timeslot_ids;

            foreach ($timeslotIds as $timeslotId) {
                $timeslot = $timeslots->find($timeslotId);
                if ($timeslot) {
                    $matrix[$timeslot->day][$timeslotId][] = [
                        'gene' => $gene,
                        'subject' => $gene->section->planSubject->subject ?? null,
                        'section' => $gene->section,
                        'instructor' => $gene->instructor ?? $gene->section->instructor, // fallback للمدرس
                        'room' => $gene->room,
                        'block_type' => $gene->block_type,
                        'student_groups' => is_string($gene->student_group_id) 
                            ? json_decode($gene->student_group_id, true) 
                            : $gene->student_group_id,
                    ];
                }
            }
        }

        return $matrix;
    }

    /**
     * تصدير الجدول إلى Excel
     */
    public function exportTimetable(Chromosome $chromosome)
    {
        // يمكن إضافة هذه الميزة لاحقاً
        return redirect()->back()->with('info', 'Export feature coming soon!');
    }

    /**
     * فحص التعارضات في كروموسوم
     */
    // public function checkConflicts(Chromosome $chromosome)
    // {
    //     try {
    //         $conflictChecker = new ConflictCheckerService();
    //         $conflicts = $conflictChecker->analyzeChromosome($chromosome);

    //         return view('dashboard.algorithm.conflicts.show', compact('chromosome', 'conflicts'));
            
    //     } catch (Exception $e) {
    //         Log::error("Failed to check conflicts for chromosome {$chromosome->chromosome_id}: " . $e->getMessage());
    //         return redirect()->back()
    //             ->with('error', 'Could not analyze conflicts: ' . $e->getMessage());
    //     }
    // }

    /**
     * مقارنة كروموسومين
     */
    public function compareChromosomes(Request $request)
    {
        $request->validate([
            'chromosome1_id' => 'required|exists:chromosomes,chromosome_id',
            'chromosome2_id' => 'required|exists:chromosomes,chromosome_id|different:chromosome1_id',
        ]);

        $chromosome1 = Chromosome::findOrFail($request->chromosome1_id);
        $chromosome2 = Chromosome::findOrFail($request->chromosome2_id);

        // مقارنة الـ fitness والعقوبات
        $comparison = [
            'chromosome1' => $chromosome1,
            'chromosome2' => $chromosome2,
            'fitness_diff' => $chromosome1->fitness_value - $chromosome2->fitness_value,
            'penalty_diff' => $chromosome1->penalty_value - $chromosome2->penalty_value,
            'conflicts_comparison' => [
                'student_conflicts' => [
                    'chr1' => $chromosome1->student_conflict_penalty ?? 0,
                    'chr2' => $chromosome2->student_conflict_penalty ?? 0,
                ],
                'teacher_conflicts' => [
                    'chr1' => $chromosome1->teacher_conflict_penalty ?? 0,
                    'chr2' => $chromosome2->teacher_conflict_penalty ?? 0,
                ],
                'room_conflicts' => [
                    'chr1' => $chromosome1->room_conflict_penalty ?? 0,
                    'chr2' => $chromosome2->room_conflict_penalty ?? 0,
                ],
                'capacity_conflicts' => [
                    'chr1' => $chromosome1->capacity_conflict_penalty ?? 0,
                    'chr2' => $chromosome2->capacity_conflict_penalty ?? 0,
                ],
                'room_type_conflicts' => [
                    'chr1' => $chromosome1->room_type_conflict_penalty ?? 0,
                    'chr2' => $chromosome2->room_type_conflict_penalty ?? 0,
                ],
                'teacher_eligibility_conflicts' => [
                    'chr1' => $chromosome1->teacher_eligibility_conflict_penalty ?? 0,
                    'chr2' => $chromosome2->teacher_eligibility_conflict_penalty ?? 0,
                ],
            ]
        ];

        return view('dashboard.algorithm.compare.show', compact('comparison'));
    }
}