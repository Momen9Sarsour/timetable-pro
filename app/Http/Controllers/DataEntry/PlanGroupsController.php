<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\PlanGroup;
use App\Models\Plan;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanGroupsController extends Controller
{
    /**
     * عرض صفحة المجموعات مع إمكانية الفلترة
     */
    public function index(Request $request)
    {
        // إعداد الفلاتر
        $query = PlanGroup::with([
            'plan:id,plan_no,plan_name,department_id',
            'plan.department:id,department_name',
            'section:id,plan_subject_id,activity_type,section_number,student_count',
            'section.planSubject:id,subject_id,plan_level,plan_semester',
            'section.planSubject.subject:id,subject_no,subject_name,theoretical_hours,practical_hours'
        ]);

        // فلترة حسب القسم
        if ($request->filled('department_id')) {
            $query->whereHas('plan.department', function($q) use ($request) {
                $q->where('id', $request->department_id);
            });
        }

        // فلترة حسب الخطة
        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        // فلترة حسب السنة الأكاديمية
        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        // فلترة حسب الفصل
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        // فلترة حسب المستوى
        if ($request->filled('plan_level')) {
            $query->where('plan_level', $request->plan_level);
        }

        // جلب البيانات مع التجميع
        $planGroups = $query->orderBy('plan_id')
            ->orderBy('plan_level')
            ->orderBy('academic_year', 'desc')
            ->orderBy('semester')
            ->orderBy('group_no')
            ->get();

        // تحليل البيانات وتجميعها للعرض
        $groupedData = $this->analyzeGroupsData($planGroups);

        // بيانات الفلاتر
        $departments = Department::orderBy('department_name')->get(['id', 'department_name']);
        $plans = Plan::where('is_active', true)->orderBy('plan_name')->get(['id', 'plan_no', 'plan_name', 'department_id']);
        $academicYears = PlanGroup::distinct()->orderBy('academic_year', 'desc')->pluck('academic_year');
        $levels = PlanGroup::distinct()->orderBy('plan_level')->pluck('plan_level');

        return view('dashboard.data-entry.plan-groups.index', compact(
            'groupedData',
            'departments',
            'plans',
            'academicYears',
            'levels',
            'request'
        ));
    }

    /**
     * تحليل بيانات المجموعات للعرض
     */
    private function analyzeGroupsData($planGroups)
    {
        $result = [];

        // تجميع البيانات حسب السياق (خطة + مستوى + سنة + فصل)
        $groupedByContext = $planGroups->groupBy(function ($item) {
            return implode('|', [
                $item->plan_id,
                $item->plan_level,
                $item->academic_year,
                $item->semester,
                $item->branch ?? 'default'
            ]);
        });

        foreach ($groupedByContext as $contextKey => $contextGroups) {
            $firstGroup = $contextGroups->first();

            // معلومات السياق الأساسية
            $contextInfo = [
                'plan' => $firstGroup->plan,
                'plan_level' => $firstGroup->plan_level,
                'academic_year' => $firstGroup->academic_year,
                'semester' => $firstGroup->semester,
                'branch' => $firstGroup->branch,
                'total_groups' => $contextGroups->pluck('group_no')->unique()->count(),
            ];

            // تحليل المواد والمجموعات
            $subjects = [];
            $subjectGroups = $contextGroups->groupBy('section.planSubject.subject_id');

            foreach ($subjectGroups as $subjectId => $subjectGroupItems) {
                $firstItem = $subjectGroupItems->first();
                $subject = $firstItem->section->planSubject->subject;

                // تحليل نوع توزيع المجموعات للمادة
                $theoryGroups = $subjectGroupItems->filter(function($item) {
                    return $item->section->activity_type === 'Theory';
                });

                $practicalGroups = $subjectGroupItems->filter(function($item) {
                    return $item->section->activity_type === 'Practical';
                });

                // حساب إجمالي الطلاب
                $totalStudents = $subjectGroupItems->groupBy('section_id')
                    ->map(function($sectionGroups) {
                        return $sectionGroups->first()->section->student_count;
                    })->sum();

                // تحديد نوع التوزيع
                $distributionType = 'unknown';
                if ($theoryGroups->isNotEmpty() && $practicalGroups->isNotEmpty()) {
                    // مادة مشتركة (نظري + عملي)
                    if ($theoryGroups->pluck('group_no')->unique()->count() > 1 &&
                        $practicalGroups->pluck('group_no')->unique()->count() > 1) {
                        $distributionType = 'mixed'; // نظري مشترك + عملي منفصل
                    } else {
                        $distributionType = 'combined'; // نظري وعملي
                    }
                } elseif ($theoryGroups->isNotEmpty()) {
                    $distributionType = $theoryGroups->pluck('group_no')->unique()->count() > 1 ? 'theory_shared' : 'theory_single';
                } elseif ($practicalGroups->isNotEmpty()) {
                    $distributionType = $practicalGroups->pluck('group_no')->unique()->count() > 1 ? 'practical_separate' : 'practical_single';
                }

                $subjects[] = [
                    'subject' => $subject,
                    'total_students' => $totalStudents,
                    'theory_groups' => $theoryGroups->pluck('group_no')->unique()->sort()->values(),
                    'practical_groups' => $practicalGroups->pluck('group_no')->unique()->sort()->values(),
                    'distribution_type' => $distributionType,
                    'theory_sections_count' => $theoryGroups->pluck('section_id')->unique()->count(),
                    'practical_sections_count' => $practicalGroups->pluck('section_id')->unique()->count(),
                ];
            }

            $result[] = [
                'context' => $contextInfo,
                'subjects' => $subjects
            ];
        }

        return collect($result);
    }

    /**
     * API: جلب الخطط حسب القسم
     */
    public function getPlans(Request $request)
    {
        $plans = Plan::where('is_active', true);

        if ($request->filled('department_id')) {
            $plans->where('department_id', $request->department_id);
        }

        return response()->json($plans->orderBy('plan_name')->get(['id', 'plan_no', 'plan_name']));
    }
}
