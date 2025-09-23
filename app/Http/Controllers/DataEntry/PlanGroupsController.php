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
            'section.planSubject.subject:id,subject_no,subject_name,subject_hours', // تحديث الحقول
            'subject:id,subject_no,subject_name,subject_hours' // إضافة العلاقة المباشرة
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

        // فلترة حسب المادة (جديد)
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
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
            
            // تجميع حسب المادة (استخدام subject_id المباشر أو من خلال الشعبة)
            $subjectGroups = $contextGroups->groupBy(function($item) {
                // استخدام subject_id المباشر إذا كان موجود، وإلا استخدم من خلال الشعبة
                return $item->subject_id ?? $item->section->planSubject->subject_id;
            });

            foreach ($subjectGroups as $subjectId => $subjectGroupItems) {
                $firstItem = $subjectGroupItems->first();
                
                // الحصول على المادة من العلاقة المباشرة أو من خلال الشعبة
                $subject = $firstItem->subject ?? $firstItem->section->planSubject->subject;

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
                    'subject_id' => $subjectId, // إضافة subject_id صراحة
                    'total_students' => $totalStudents,
                    'theory_groups' => $theoryGroups->pluck('group_no')->unique()->sort()->values(),
                    'practical_groups' => $practicalGroups->pluck('group_no')->unique()->sort()->values(),
                    'distribution_type' => $distributionType,
                    'theory_sections_count' => $theoryGroups->pluck('section_id')->unique()->count(),
                    'practical_sections_count' => $practicalGroups->pluck('section_id')->unique()->count(),
                    // إضافة معلومات إضافية عن المجموعات
                    'groups_details' => $subjectGroupItems->map(function($item) {
                        return [
                            'group_no' => $item->group_no,
                            'section_id' => $item->section_id,
                            'activity_type' => $item->section->activity_type,
                            'section_number' => $item->section->section_number,
                            'group_size' => $item->group_size,
                            'gender' => $item->gender,
                        ];
                    })->values(),
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
     * عرض تفاصيل مجموعة معينة
     */
    public function show($groupId)
    {
        $planGroup = PlanGroup::with([
            'plan:id,plan_no,plan_name,department_id',
            'plan.department:id,department_name',
            'section:id,plan_subject_id,activity_type,section_number,student_count',
            'section.planSubject:id,subject_id,plan_level,plan_semester',
            'section.planSubject.subject:id,subject_no,subject_name,subject_hours',
            'subject:id,subject_no,subject_name,subject_hours'
        ])->findOrFail($groupId);

        return view('dashboard.data-entry.plan-groups.show', compact('planGroup'));
    }

    /**
     * عرض كل المجموعات لسياق معين
     */
    public function showContext(Request $request)
    {
        $planId = $request->get('plan_id');
        $planLevel = $request->get('plan_level');
        $academicYear = $request->get('academic_year');
        $semester = $request->get('semester');
        $branch = $request->get('branch');

        if (!$planId || !$planLevel || !$academicYear || !$semester) {
            return redirect()->route('data-entry.plan-groups.index')
                ->with('error', 'Missing required context parameters.');
        }

        $planGroups = PlanGroup::byContext($planId, $planLevel, $academicYear, $semester, $branch)
            ->with([
                'plan:id,plan_no,plan_name,department_id',
                'plan.department:id,department_name',
                'section:id,plan_subject_id,activity_type,section_number,student_count',
                'section.planSubject:id,subject_id,plan_level,plan_semester',
                'section.planSubject.subject:id,subject_no,subject_name,subject_hours',
                'subject:id,subject_no,subject_name,subject_hours'
            ])
            ->orderBy('group_no')
            ->orderBy('subject_id')
            ->get();

        $contextInfo = [
            'plan_id' => $planId,
            'plan_level' => $planLevel,
            'academic_year' => $academicYear,
            'semester' => $semester,
            'branch' => $branch,
        ];

        return view('dashboard.data-entry.plan-groups.context', compact('planGroups', 'contextInfo'));
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

    /**
     * API: جلب المجموعات حسب السياق
     */
    public function apiGetGroupsByContext(Request $request)
    {
        $planId = $request->get('plan_id');
        $planLevel = $request->get('plan_level');
        $academicYear = $request->get('academic_year');
        $semester = $request->get('semester');
        $branch = $request->get('branch');

        if (!$planId || !$planLevel || !$academicYear || !$semester) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required parameters'
            ], 400);
        }

        $planGroups = PlanGroup::byContext($planId, $planLevel, $academicYear, $semester, $branch)
            ->with([
                'section:id,activity_type,section_number,student_count',
                'subject:id,subject_no,subject_name'
            ])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $planGroups
        ]);
    }

    /**
     * API: جلب المجموعات حسب المادة
     */
    public function apiGetGroupsBySubject(Request $request)
    {
        $subjectId = $request->get('subject_id');
        $planId = $request->get('plan_id');
        $planLevel = $request->get('plan_level');
        $academicYear = $request->get('academic_year');
        $semester = $request->get('semester');
        $branch = $request->get('branch');

        if (!$subjectId || !$planId || !$academicYear || !$semester) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required parameters'
            ], 400);
        }

        $planGroups = PlanGroup::byContext($planId, $planLevel, $academicYear, $semester, $branch)
            ->bySubjectId($subjectId)
            ->with([
                'section:id,activity_type,section_number,student_count',
                'subject:id,subject_no,subject_name'
            ])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $planGroups
        ]);
    }

    /**
     * API: إحصائيات المجموعات
     */
    public function apiGetGroupsStats(Request $request)
    {
        $query = PlanGroup::query();

        // تطبيق الفلاتر إذا وجدت
        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        $stats = [
            'total_groups' => $query->count(),
            'unique_contexts' => $query->distinct()->count(DB::raw('CONCAT(plan_id, plan_level, academic_year, semester, COALESCE(branch, ""))')),
            'groups_by_activity' => $query->with('section:id,activity_type')
                ->get()
                ->groupBy('section.activity_type')
                ->map->count(),
            'groups_by_gender' => $query->select('gender', DB::raw('count(*) as count'))
                ->groupBy('gender')
                ->get()
                ->pluck('count', 'gender'),
            'average_group_size' => round($query->whereNotNull('group_size')->avg('group_size'), 2),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}