<?php

namespace App\Http\Controllers\Algorithm;

use App\Http\Controllers\Controller;
use App\Models\Population;
use App\Models\Chromosome;
use App\Models\Timeslot;
use App\Services\ConflictCheckerService;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class TimetableResultController extends Controller
{
    /**
     * Display a list of generation runs and top chromosomes for a selected run.
     */
    // public function index(Request $request)
    // {
    //     try {
    //         // جلب كل عمليات التشغيل (Populations) لعرضها في قائمة منسدلة
    //         $allRuns = Population::orderBy('start_time', 'desc')->get();

    //         $selectedRun = null;
    //         $topChromosomes = collect(); // مجموعة فارغة افتراضياً

    //         // التحقق إذا كان المستخدم قد اختار عملية تشغيل معينة لعرضها
    //         if ($request->has('run_id') && $request->run_id != '') {
    //             $selectedRun = Population::find($request->run_id);
    //             if ($selectedRun) {
    //                 // جلب أفضل 5 كروموسومات لهذه العملية مرتبة حسب الجودة
    //                 $topChromosomes = $selectedRun->chromosomes()
    //                     ->orderBy('penalty_value', 'asc')
    //                     ->take(5)
    //                     ->get();
    //             }
    //         }

    //         return view('dashboard.timetable-result.index', compact(
    //             'allRuns',
    //             'selectedRun',
    //             'topChromosomes'
    //         ));
    //     } catch (Exception $e) {
    //         Log::error("Error loading timetable results index: " . $e->getMessage());
    //         return redirect()->route('dashboard.index')->with('error', 'Could not load the results page.');
    //     }
    // }
    public function index() // لم نعد بحاجة لـ $request هنا مبدئياً
    {
        try {
            // 1. جلب آخر عملية تشغيل مكتملة (latest successful run)
            $latestSuccessfulRun = Population::where('status', 'completed')
                ->orderBy('end_time', 'desc') // الأحدث حسب وقت الانتهاء
                ->first();

            $topChromosomes = collect(); // مجموعة فارغة افتراضياً

            // 2. إذا وجدنا عملية تشغيل ناجحة، جلب أفضل 5 كروموسومات منها
            if ($latestSuccessfulRun) {
                $topChromosomes = $latestSuccessfulRun->chromosomes()
                    ->orderBy('penalty_value', 'asc') // الأقل عقوبة (الأفضل) في الأعلى
                    ->take(5) // جلب أفضل 5 فقط
                    ->get();
            }

            // 3. تمرير البيانات للـ View
            return view('dashboard.algorithm.timetable-result.index', compact(
                'latestSuccessfulRun', // تمرير معلومات عملية التشغيل نفسها
                'topChromosomes'
            ));
        } catch (Exception $e) {
            Log::error('Error loading timetable results index: ' . $e->getMessage());
            return redirect()->route('dashboard.index')->with('error', 'Could not load the results page.');
        }
    }

    /**
     * Display the best timetable for a given CHROMSOME.
     */
    public function show(Chromosome $chromosome) // استخدام Route Model Binding لـ Chromosome
    {
        try {
            // تحميل المعلومات المرتبطة بالكروموسوم (عملية التشغيل)
            $chromosome->load('population');

            // جلب كل الجينات (المحاضرات) مع كل تفاصيلها اللازمة للعرض
            $genes = $chromosome->genes()->with([
                'section.planSubject.subject',
                'instructor.user',
                'room.roomType',
                'timeslot',
            ])->get();

            // استخدام Service لفحص التعارضات وتحديدها
            $conflictChecker = new ConflictCheckerService($genes);
            $conflicts = $conflictChecker->getConflicts();
            $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

            // جلب كل الفترات الزمنية المتاحة لعرض هيكل الجدول
            $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');

            // تحضير بيانات الجدول للعرض
            $scheduleData = [];
            foreach ($genes as $gene) {
                if ($gene->timeslot) {
                    $scheduleData[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene;
                }
            }

            return view('dashboard.algorithm.timetable-result.show', compact(
                'chromosome', // تم تغيير اسم المتغير
                'scheduleData',
                'timeslots',
                'conflicts',
                'conflictingGeneIds'
            ));
        } catch (Exception $e) {
            Log::error("Error showing timetable result for Chromosome ID {$chromosome->chromosome_id}: " . $e->getMessage());
            return redirect()->route('dashboard.timetable.results.index')->with('error', 'Could not display the schedule result.');
        }
    }
}
