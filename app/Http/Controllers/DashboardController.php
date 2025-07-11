<?php

namespace App\Http\Controllers;

use App\Models\Timeslot;
use App\Models\Chromosome;
use App\Models\Population;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ConflictCheckerService;

class DashboardController extends Controller
{
    public function index()
    {
        // return view('dashboard.index');
        // جلب آخر عملية تشغيل مكتملة
        $lastSuccessfulRun = Population::where('status', 'completed')
            ->orderBy('end_time', 'desc')
            ->first();

        $bestChromosome = null;
        $scheduleData = [];
        $conflicts = [];
        $conflictingGeneIds = [];
        $timeslots = collect();

        if ($lastSuccessfulRun && $lastSuccessfulRun->best_chromosome_id) {
            // جلب أفضل جدول من هذه العملية
            $bestChromosome = Chromosome::find($lastSuccessfulRun->best_chromosome_id);

            if ($bestChromosome) {
                // جلب بيانات هذا الجدول للعرض
                $genes = $bestChromosome->genes()->with([
                    'section.planSubject.subject',
                    'instructor.user',
                    'room',
                    'timeslot'
                ])->get();

                // فحص التعارضات
                $conflictChecker = new ConflictCheckerService($genes);
                $conflicts = $conflictChecker->getConflicts();
                $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

                // تحضير بيانات الجدول للعرض
                foreach ($genes as $gene) {
                    if ($gene->timeslot) { // تحقق من وجود الفترة الزمنية
                        $scheduleData[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene;
                    }
                }
            }
        }

        // جلب كل الفترات الزمنية المتاحة لعرض هيكل الجدول
        $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');


        // تمرير البيانات للـ view
        return view('dashboard.index', compact(
            'lastSuccessfulRun',
            'bestChromosome',
            'scheduleData',
            'timeslots',
            'conflicts',
            'conflictingGeneIds'
            // ... يمكنك تمرير إحصائيات أخرى هنا (Total Courses, Instructors, etc.)
        ));
    }

    public function dataEntry()
    {
        return view('dashboard.data-entry');
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('success', 'Lecture saved!');
    }
}
