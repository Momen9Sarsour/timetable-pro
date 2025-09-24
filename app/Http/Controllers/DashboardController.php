<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Room;
use App\Models\User;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Timeslot;
use App\Models\Chromosome;
use App\Models\Population;
use App\Models\Instructor;
use App\Models\Department;
use App\Models\PlanExpectedCount;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        // الإحصائيات العامة
        $stats = [
            'total_subjects' => Subject::count(),
            'total_plans' => Plan::count(),
            'total_instructors' => Instructor::count(),
            'total_rooms' => Room::count(),
            'total_sections' => Section::count(),
            'total_departments' => Department::count(),
            'active_plans' => Plan::where('is_active', true)->count(),
            'total_populations' => Population::count(),
            'completed_populations' => Population::where('status', 'completed')->count(),
            'total_chromosomes' => Chromosome::count(),
        ];

        // آخر 5 عناصر من كل نوع
        $recentData = [
            'subjects' => Subject::with(['department', 'subjectType', 'subjectCategory'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
                
            'plans' => Plan::with('department')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
                
            'instructors' => Instructor::with(['department', 'user'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
                
            'rooms' => Room::with('roomType')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
                
            'sections' => Section::with(['planSubject.subject', 'planSubject.plan'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
                
            'departments' => Department::withCount(['instructors', 'subjects', 'plans'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
                
            'populations' => Population::with(['crossover', 'selectionType', 'mutationType'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
                
            'chromosomes' => Chromosome::with('population')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
                
            'expected_counts' => PlanExpectedCount::with('plan')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
                
            'timeslots' => Timeslot::orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        // آخر عملية تشغيل مكتملة للمعلومات الإضافية
        $lastSuccessfulRun = Population::where('status', 'completed')
            ->orderBy('end_time', 'desc')
            ->first();

        $bestChromosome = null;
        if ($lastSuccessfulRun && $lastSuccessfulRun->best_chromosome_id) {
            $bestChromosome = Chromosome::find($lastSuccessfulRun->best_chromosome_id);
        }

        return view('dashboard.index', compact(
            'stats',
            'recentData',
            'lastSuccessfulRun',
            'bestChromosome'
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