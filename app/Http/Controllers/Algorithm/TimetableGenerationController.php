<?php

namespace App\Http\Controllers\Algorithm;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateTimetableJob;
use App\Models\Chromosome;
use Illuminate\Http\Request;
use App\Models\Population; // موديل POPULATIONS
use App\Models\Crossover;
use App\Models\SelectionType;
use App\Models\Timeslot;
use App\Services\ConflictCheckerService;
use App\Services\GeneticAlgorithmService; // الـ Service الذي أنشأناه
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TimetableGenerationController extends Controller
{
    /**
     * Start the timetable generation process.
     * تبدأ عملية توليد الجدول بناءً على الإعدادات من المودال
     */
    // public function start(Request $request)
    // {
    //     // 1. التحقق من صحة الإعدادات القادمة من المودال
    //     $validatedSettings = $request->validate([
    //         'population_size' => 'required|integer|min:10|max:500',
    //         'max_generations' => 'required|integer|min:10|max:10000',
    //         'mutation_rate' => 'required|numeric|min:0|max:1',
    //         'crossover_type_id' => ['required', 'integer', Rule::exists('crossover_types', 'crossover_id')->where('is_active', true)],
    //         'selection_type_id' => ['required', 'integer', Rule::exists('selection_types', 'selection_type_id')->where('is_active', true)],
    //         'stop_at_first_valid' => 'nullable|boolean',
    //     ]);
    //     // dd([
    //     //     '$request->all()' => $request->all(),
    //     //     'validatedSettings' => $validatedSettings,
    //     // ]);

    //     try {
    //         // 2. إنشاء سجل جديد لعملية التشغيل في جدول POPULATIONS
    //         $populationRun = Population::create([
    //             'population_size' => $validatedSettings['population_size'],
    //             'crossover_id' => $validatedSettings['crossover_type_id'],
    //             'selection_id' => $validatedSettings['selection_type_id'],
    //             'mutation_rate' => $validatedSettings['mutation_rate'],
    //             'generations_count' => $validatedSettings['max_generations'], // يمكن استخدام هذا كحد أقصى
    //             'status' => 'running', // تبدأ كـ "قيد الانتظار"
    //         ]);

    //         Log::info("New Population Run created with ID: {$populationRun->population_id}. Starting GA Service.");

    //         // 3. إنشاء وتشغيل الـ Service
    //         // **ملاحظة هامة:** الآن سنقوم بتشغيلها مباشرة. في التطبيق الحقيقي،
    //         // يجب تحويل هذا إلى Job يعمل في الخلفية لتجنب تجميد المتصفح.
    //         // dispatch(new GenerateTimetableJob($validatedSettings, $populationRun));
    //         // $gaService = new GeneticAlgorithmService($validatedSettings, $populationRun);
    //         // $gaService->run(); // بدء التنفيذ

    //         // 3. *** إرسال المهمة (Job) إلى الطابور ***
    //         GenerateTimetableJob::dispatch($validatedSettings, $populationRun);

    //         // 4. إعادة التوجيه مع رسالة نجاح
    //         return redirect()->route('dashboard.index') // أو صفحة عرض حالة الخوارزمية
    //             ->with('success', 'Timetable generation process has started successfully! Population Run ID: ' . $populationRun->population_id);
    //     } catch (\Exception $e) {
    //         Log::error('Failed to start timetable generation process: ' . $e->getMessage());
    //         return redirect()->back()
    //             ->with('error', 'Could not start the generation process. Error: ' . $e->getMessage())
    //             ->withInput();
    //     }
    // }

    // public function start(Request $request)
    // {
    //     $validatedSettings = $request->validate([
    //         'population_size' => 'required|integer|min:10|max:500',
    //         'max_generations' => 'required|integer|min:10|max:10000',
    //         'mutation_rate' => 'required|numeric|min:0|max:1',
    //         'crossover_type_id' => ['required', 'integer', Rule::exists('crossover_types', 'crossover_id')->where('is_active', true)],
    //         'selection_type_id' => ['required', 'integer', Rule::exists('selection_types', 'selection_type_id')->where('is_active', true)],
    //         'stop_at_first_valid' => 'nullable|boolean',
    //     ]);

    //     try {
    //         // إنشاء سجل عملية التشغيل
    //         $populationRun = Population::create([
    //             'population_size' => $validatedSettings['population_size'],
    //             'crossover_id' => $validatedSettings['crossover_type_id'],
    //             'selection_id' => $validatedSettings['selection_type_id'],
    //             'mutation_rate' => $validatedSettings['mutation_rate'],
    //             'generations_count' => $validatedSettings['max_generations'],
    //             'status' => 'running',
    //         ]);

    //         Log::info("New Population Run created with ID: {$populationRun->population_id}. Dispatching job to queue.");

    //         // إرسال المهمة (Job) إلى الطابور
    //         GenerateTimetableJob::dispatch($validatedSettings, $populationRun);

    //         return redirect()->route('dashboard.index')
    //             ->with('success', 'The timetable generation process has been started in the background. Run ID: ' . $populationRun->population_id);
    //     } catch (\Exception $e) {
    //         Log::error('Failed to dispatch timetable generation job: ' . $e->getMessage());
    //         return redirect()->back()
    //             ->with('error', 'Could not start the generation process: ' . $e->getMessage())
    //             ->withInput();
    //     }
    // }
    public function start(Request $request)
    {
        $validatedSettings = $request->validate([
            'academic_year' => 'required|integer|digits:4',
            'semester' => 'required|integer|in:1,2,3',
            'population_size' => 'required|integer|min:10|max:500',
            'max_generations' => 'required|integer|min:10|max:10000',
            'mutation_rate' => 'required|numeric|min:0|max:1',
            'crossover_type_id' => ['required', 'integer', Rule::exists('crossover_types', 'crossover_id')->where('is_active', true)],
            'selection_type_id' => ['required', 'integer', Rule::exists('selection_types', 'selection_type_id')->where('is_active', true)],
            'stop_at_first_valid' => 'nullable|boolean',
        ]);
        // تحويل boolean
        $validatedSettings['stop_at_first_valid'] = $request->has('stop_at_first_valid');

        try {
            $populationRun = Population::create([
                'population_size' => $validatedSettings['population_size'],
                'crossover_id' => $validatedSettings['crossover_type_id'],
                'selection_id' => $validatedSettings['selection_type_id'],
                'mutation_rate' => $validatedSettings['mutation_rate'],
                'generations_count' => $validatedSettings['max_generations'],
                'status' => 'running',
            ]);

            Log::info("New Population Run created with ID: {$populationRun->population_id}. Dispatching job to queue.");

            // $gaService = new GeneticAlgorithmService($validatedSettings, $populationRun);
            // $gaService->run(); // بدء التنفيذ

            GenerateTimetableJob::dispatch($validatedSettings, $populationRun);

            return redirect()->route('dashboard.index')
                ->with('success', "Timetable generation started for Year: {$validatedSettings['academic_year']}, Semester: {$validatedSettings['semester']}. Run ID: " . $populationRun->population_id);
            // return redirect()->route('dashboard.index')
            //     ->with('success', 'The timetable generation process has been started in the background. Run ID: ' . $populationRun->population_id);
        } catch (Exception $e) {
            Log::error('Failed to dispatch timetable generation job: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Could not start the generation process: ' . $e->getMessage())
                ->withInput();
        }
    }



    // public function show(Population $population)
    // {
    //     try {
    //         // جلب أفضل كروموسوم (جدول) لهذه العملية
    //         // إذا كان best_chromosome_id مسجلاً، استخدمه. وإلا، ابحث عن الأفضل.
    //         if ($population->best_chromosome_id) {
    //             $bestChromosome = Chromosome::find($population->best_chromosome_id);
    //         } else {
    //             $bestChromosome = $population->chromosomes()->orderBy('penalty_value', 'asc')->first();
    //         }

    //         if (!$bestChromosome) {
    //             return redirect()->route('dashboard.index')->with('error', 'No valid schedule found for this generation run yet.');
    //         }

    //         // جلب كل الجينات (المحاضرات) مع كل تفاصيلها اللازمة للعرض
    //         $genes = $bestChromosome->genes()->with([
    //             'section.planSubject.subject',
    //             'instructor.user',
    //             'room',
    //             'timeslot'
    //         ])->get();

    //         // *** استخدام Service جديد لفحص التعارضات وتحديدها ***
    //         $conflictChecker = new ConflictCheckerService($genes);
    //         $conflicts = $conflictChecker->getConflicts(); // مصفوفة بتفاصيل التعارضات
    //         $conflictingGeneIds = $conflictChecker->getConflictingGeneIds(); // مصفوفة بـ IDs الجينات المتعارضة

    //         // جلب كل الفترات الزمنية المتاحة لعرض الجدول بشكل صحيح
    //         $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');

    //         // تحضير بيانات الجدول للعرض
    //         $scheduleData = [];
    //         foreach ($genes as $gene) {
    //             $scheduleData[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene;
    //         }

    //         return view('dashboard.timetable-result.show', compact(
    //             'population',
    //             'bestChromosome',
    //             'scheduleData',
    //             'timeslots',
    //             'conflicts',
    //             'conflictingGeneIds'
    //         ));
    //     } catch (Exception $e) {
    //         Log::error("Error showing timetable result: " . $e->getMessage());
    //         return redirect()->route('dashboard.index')->with('error', 'Could not display the schedule result.');
    //     }
    // }
}
