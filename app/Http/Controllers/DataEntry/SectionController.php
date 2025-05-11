<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Plan;
use App\Models\Department;
use App\Models\PlanSubject;
use App\Models\PlanExpectedCount;
use App\Models\Subject; // لاستخدامه في الـ Service
use App\Models\Room; // لجلب سعات القاعات (مستقبلاً)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Exception;

class SectionController extends Controller
{
    const DEFAULT_THEORY_CAPACITY = 1000; // افترض سعة كبيرة جداً للنظري المشترك
    const DEFAULT_LAB_CAPACITY = 25;    // سعة افتراضية للمختبر

    public function index(Request $request)
    {
        // ... (نفس دالة index السابقة لعرض الشعب مع الفلاتر) ...
        // سنضيف زر "Manage Sections" في هذا الـ view لاحقاً
        // ... (الكود من الرد السابق) ...
        try {
            $query = Section::with([
                'planSubject.plan.department:id,department_name',
                'planSubject.subject:id,subject_no,subject_name'
            ]);
            if ($request->filled('academic_year')) {
                $query->where('academic_year', $request->academic_year);
            }
            if ($request->filled('semester')) {
                $query->where('semester', $request->semester);
            }
            if ($request->filled('department_id')) {
                $query->whereHas('planSubject.plan.department', fn($q) => $q->where('id', $request->department_id));
            }
            if ($request->filled('plan_id')) {
                $query->whereHas('planSubject.plan', fn($q) => $q->where('id', $request->plan_id));
            }
            if ($request->filled('plan_level')) {
                $query->whereHas('planSubject', fn($q) => $q->where('plan_level', $request->plan_level));
            }
            if ($request->filled('subject_id')) {
                $query->whereHas('planSubject.subject', fn($q) => $q->where('id', $request->subject_id));
            }
            if ($request->filled('branch')) {
                $query->where('branch', $request->branch == 'none' ? null : $request->branch);
            }

            $sections = $query->orderBy('academic_year', 'desc')->orderBy('semester')
                ->orderBy(fn($q) => $q->from('plan_subjects')->whereColumn('plan_subjects.id', 'sections.plan_subject_id')->join('plans', 'plan_subjects.plan_id', '=', 'plans.id')->selectRaw('plans.plan_no'))
                ->orderBy(fn($q) => $q->from('plan_subjects')->whereColumn('plan_subjects.id', 'sections.plan_subject_id')->selectRaw('plan_level'))
                ->orderBy(fn($q) => $q->from('plan_subjects')->whereColumn('plan_subjects.id', 'sections.plan_subject_id')->join('subjects', 'plan_subjects.subject_id', '=', 'subjects.id')->selectRaw('subjects.subject_no'))
                ->orderBy('section_number')->paginate(20);
            $academicYears = Section::distinct()->orderBy('academic_year', 'desc')->pluck('academic_year');
            $departments = Department::orderBy('department_name')->get(['id', 'department_name']);
            $plans = Plan::where('is_active', true)->orderBy('plan_name')->get(['id', 'plan_no', 'plan_name']);
            $subjects = Subject::orderBy('subject_no')->get(['id', 'subject_no', 'subject_name']);
            $levels = range(1, 6);
            $semesters = [1 => 'First', 2 => 'Second', 3 => 'Summer'];
            return view('dashboard.data-entry.sections', compact('sections', 'academicYears', 'departments', 'plans', 'subjects', 'levels', 'semesters', 'request'));
        } catch (Exception $e) {
            Log::error('Error fetching sections: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load sections.');
        }
    }

    /**
     * Show the form for managing sections for a specific context.
     * (قد تقوم بإنشاء الشعب تلقائياً إذا لم تكن موجودة)
     */
    public function manage(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|integer|exists:plans,id', // ** تغيير: نأخذ plan_id مباشرة **
            'plan_level' => 'required|integer|min:1',
            'plan_semester' => 'required|integer|in:1,2,3', // فصل الخطة
            'academic_year' => 'required|integer|digits:4',
            'branch' => 'nullable|string|max:100',
        ]);

        $planId = $request->plan_id;
        $planLevel = $request->plan_level;
        $planSemester = $request->plan_semester; // فصل الخطة
        $academicYear = $request->academic_year;
        $branch = $request->filled('branch') ? $request->branch : null;

        try {
            $plan = Plan::with('department:id,department_name')->findOrFail($planId);

            // جلب المواد لهذا السياق من plan_subjects
            $planSubjectsForContext = PlanSubject::with('subject.subjectCategory')
                ->where('plan_id', $planId)
                ->where('plan_level', $planLevel)
                ->where('plan_semester', $planSemester) // فصل الخطة
                ->get();

            if ($planSubjectsForContext->isEmpty()) {
                return redirect()->route('data-entry.sections.index')
                    ->with('info', 'No subjects found in the selected plan/level/semester to manage sections for.');
            }

            // جلب الأعداد المتوقعة
            $expectedCount = PlanExpectedCount::where('plan_id', $planId)
                ->where('plan_level', $planLevel)
                ->where('plan_semester', $planSemester) // فصل الخطة
                ->where('academic_year', $academicYear)
                ->where(function ($q) use ($branch) {
                    is_null($branch) ? $q->whereNull('branch') : $q->where('branch', $branch);
                })
                ->first();

            if (!$expectedCount) {
                // إذا لم يتم إدخال العدد المتوقع، لا يمكن إنشاء شعب
                // لكن يمكن عرض الشعب الحالية إذا وجدت
                $currentSectionsBySubject = $this->getCurrentSectionsGroupedBySubject($planSubjectsForContext, $academicYear, $planSemester, $branch);

                return view('dashboard.data-entry.manage-sections', compact(
                    'plan',
                    'planSubjectsForContext',
                    'academicYear',
                    'planLevel',
                    'planSemester', // تم تغيير semester إلى planSemester
                    'branch',
                    'expectedCount',
                    'currentSectionsBySubject'
                ))->with('warning', 'Expected student count not found for this context. Please add it first to generate sections.');
            }

            // جلب/تحديث الشعب
            // سنمرر expectedCount لدالة التقسيم
            $this->generateOrUpdateSectionsForContext($plan, $planLevel, $planSemester, $academicYear, $branch, $expectedCount);

            // جلب الشعب الحالية بعد الإنشاء/التحديث
            $currentSectionsBySubject = $this->getCurrentSectionsGroupedBySubject($planSubjectsForContext, $academicYear, $request->input('semester_of_sections', $planSemester), $branch); // استخدام فصل الشعبة

            return view('dashboard.data-entry.manage-sections', compact(
                'plan',
                'planSubjectsForContext',
                'academicYear',
                'planLevel',
                'planSemester', // فصل الخطة
                'branch',
                'expectedCount',
                'currentSectionsBySubject'
            ));
        } catch (Exception $e) {
            Log::error('Error loading manage sections view: ' . $e->getMessage());
            return redirect()->route('data-entry.sections.index')
                ->with('error', 'Could not load section management page.');
        }
    }

    /**
     * Helper to get current sections grouped by subject.
     */
    // private function getCurrentSectionsGroupedBySubject($planSubjectsForContext, $academicYear, $semesterOfSections, $branch)
    // {
    //     $currentSectionsBySubject = collect();
    //     foreach ($planSubjectsForContext as $ps) {
    //         $query = Section::where('plan_subject_id', $ps->id)
    //             ->where('academic_year', $academicYear)
    //             ->where('semester', $semesterOfSections); // فصل الشعبة
    //         if (is_null($branch)) {
    //             $query->whereNull('branch');
    //         } else {
    //             $query->where('branch', $branch);
    //         }
    //         $currentSectionsBySubject[$ps->subject_id] = $query->orderBy('section_number')->get();
    //     }
    //     return $currentSectionsBySubject;
    // }


    /**
     * Trigger section generation from a button.
     */
    public function generateSectionsFromButton(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
            'plan_level' => 'required|integer|min:1',
            'plan_semester' => 'required|integer|in:1,2,3',
            'academic_year' => 'required|integer|digits:4',
            'branch' => 'nullable|string|max:100',
        ]);

        $plan = Plan::find($request->plan_id);
        $expectedCount = PlanExpectedCount::where('plan_id', $request->plan_id)
            ->where('plan_level', $request->plan_level)
            ->where('plan_semester', $request->plan_semester)
            ->where('academic_year', $request->academic_year)
            ->where(function ($q) use ($request) {
                is_null($request->branch) ? $q->whereNull('branch') : $q->where('branch', $request->branch);
            })
            ->first();

        if (!$expectedCount) {
            return redirect()->back()->with('error', 'Expected count not found. Cannot generate sections.');
        }

        try {
            $this->generateOrUpdateSectionsForContext(
                $plan,
                $request->plan_level,
                $request->plan_semester, // فصل الخطة
                $request->academic_year,
                $request->branch,
                $expectedCount
            );
            return redirect()->route('data-entry.sections.manage', $request->only(['plan_id', 'plan_level', 'plan_semester', 'academic_year', 'branch']))
                ->with('success', 'Sections generated/updated successfully.');
        } catch (Exception $e) {
            Log::error('Manual Section Generation Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate sections.');
        }
    }

    /**
     * Core logic to generate or update sections for a given context.
     * (This can be moved to a Service class later)
     */
    private function generateOrUpdateSectionsForContext(Plan $plan, $planLevel, $planSemester, $academicYear, $branch, PlanExpectedCount $expectedCount)
    {
        // 1. جلب المواد لهذا السياق
        $planSubjects = PlanSubject::with('subject.subjectCategory')
            ->where('plan_id', $plan->id)
            ->where('plan_level', $planLevel)
            ->where('plan_semester', $planSemester) // فصل الخطة
            ->get();

        if ($planSubjects->isEmpty()) {
            Log::info("No subjects to process for Plan ID {$plan->id}, Level {$planLevel}, Semester {$planSemester}.");
            return; // لا يوجد مواد لإنشاء شعب لها
        }

        $totalExpected = $expectedCount->male_count + $expectedCount->female_count;
        if ($totalExpected <= 0) {
            Log::info("Total expected students is zero for Plan ID {$plan->id}, Level {$planLevel}, Semester {$planSemester}, Year {$academicYear}. Deleting existing sections if any.");
            // حذف الشعب الحالية إذا كان العدد المتوقع صفر
            Section::whereIn('plan_subject_id', $planSubjects->pluck('id'))
                ->where('academic_year', $academicYear)
                ->where('semester', $planSemester) // * فصل الشعبة *
                ->where(function ($q) use ($branch) {
                    is_null($branch) ? $q->whereNull('branch') : $q->where('branch', $branch);
                })
                ->delete();
            return;
        }


        // 2. حذف الشعب القديمة لهذا السياق (لتجنب التكرار عند التحديث)
        // **تحذير:** هذا سيحذف أي تعديلات يدوية قام بها رئيس القسم.
        // قد تحتاج لمنطق أكثر تعقيداً للتحديث بدلاً من الحذف والإنشاء.
        // للتبسيط الآن، سنقوم بالحذف ثم الإنشاء.
        Section::whereIn('plan_subject_id', $planSubjects->pluck('id'))
            ->where('academic_year', $academicYear)
            ->where('semester', $planSemester) // * فصل الشعبة *
            ->where(function ($q) use ($branch) {
                is_null($branch) ? $q->whereNull('branch') : $q->where('branch', $branch);
            })
            ->delete();
        Log::info("Deleted old sections for Plan ID {$plan->id}, L{$planLevel}S{$planSemester}, Year {$academicYear}, Branch '{$branch}'.");


        // 3. إنشاء شعب جديدة لكل مادة
        foreach ($planSubjects as $ps) {
            $subjectCategoryName = strtolower(optional(optional($ps->subject)->subjectCategory)->subject_category_name ?? '');

            // --- أ. الشعب النظرية (أو الجزء النظري من مادة مشتركة) ---
            if (Str::contains($subjectCategoryName, 'theory') || Str::contains($subjectCategoryName, 'نظري') || Str::contains($subjectCategoryName, 'combined') || Str::contains($subjectCategoryName, 'مشترك')) {
                Section::create([
                    'plan_subject_id' => $ps->id,
                    'academic_year' => $academicYear,
                    'semester' => $planSemester, // * فصل الشعبة هو نفسه فصل الخطة هنا *
                    'section_number' => 1, // شعبة نظرية واحدة كبيرة
                    'student_count' => $totalExpected,
                    'section_gender' => 'Mixed', // افتراضي
                    'branch' => $branch,
                ]);
                Log::info("Created T-Section for Subject ID {$ps->subject_id}, PlanSub ID {$ps->id}");
            }

            // --- ب. الشعب العملية (أو الجزء العملي من مادة مشتركة) ---
            if (Str::contains($subjectCategoryName, 'practical') || Str::contains($subjectCategoryName, 'عملي') || Str::contains($subjectCategoryName, 'combined') || Str::contains($subjectCategoryName, 'مشترك')) {
                if ($subjectCategoryName == 'theory' || Str::contains($subjectCategoryName, 'نظري')) continue; // إذا كانت نظرية بحتة، لا تنشئ عملي

                $labCapacity = self::DEFAULT_LAB_CAPACITY; // استخدام قيمة ثابتة مؤقتاً
                // يمكنك لاحقاً جلب سعة المختبر المناسب لهذه المادة من rooms/room_types
                // $suitableLab = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%') /* or specific lab type */)
                //                    ->orderBy('room_size', 'asc')->where('room_size', '>=', 15)->first(); // مثال بسيط
                // if ($suitableLab) $labCapacity = $suitableLab->room_size;


                if ($totalExpected > 0 && $labCapacity > 0) {
                    $numLabSections = ceil($totalExpected / $labCapacity);
                    $baseStudentsPerSection = floor($totalExpected / $numLabSections);
                    $remainderStudents = $totalExpected % $numLabSections;

                    for ($i = 1; $i <= $numLabSections; $i++) {
                        $studentsInThisSection = $baseStudentsPerSection + ($remainderStudents > 0 ? 1 : 0);
                        if ($remainderStudents > 0) $remainderStudents--;

                        if ($studentsInThisSection > 0) { // لا تنشئ شعبة بصفر طلاب
                            Section::create([
                                'plan_subject_id' => $ps->id,
                                'academic_year' => $academicYear,
                                'semester' => $planSemester, // * فصل الشعبة هو نفسه فصل الخطة هنا *
                                'section_number' => $i, // رقم شعبة العملي
                                'student_count' => $studentsInThisSection,
                                'section_gender' => 'Mixed', // افتراضي
                                'branch' => $branch,
                            ]);
                            Log::info("Created P-Section {$i} for Subject ID {$ps->subject_id}, PlanSub ID {$ps->id}");
                        }
                    }
                }
            }
        }
        Log::info("Finished generating/updating sections for Plan ID {$plan->id}, L{$planLevel}S{$planSemester}, Year {$academicYear}, Branch '{$branch}'.");
    }


    // --- دوال Store, Update, Destroy للشعب (تُستدعى من صفحة manage) ---
    public function store(Request $request)
    {
        // دالة store هنا يجب أن تستقبل plan_subject_id, academic_year, semester, branch
        // من حقول مخفية في الفورم الذي يفتحه مودال الإضافة في صفحة manage
        // ... (نفس كود store من الرد السابق مع تعديل بسيط للـ redirect) ...

        $validator = Validator::make($request->all(), [
            'plan_subject_id' => 'required|integer|exists:plan_subjects,id',
            'academic_year' => 'required|integer|digits:4',
            'semester' => 'required|integer|in:1,2,3',
            'branch' => 'nullable|string|max:100',
            'section_number' => 'required|integer|min:1',
            'student_count' => 'required|integer|min:0',
            'section_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
        ]);

        $validator->after(function ($validator) use ($request) { /* ... التحقق من التفرد ... */
        });

        if ($validator->fails()) {
            return redirect()->route('data-entry.sections.manage', $request->only(['plan_subject_id', 'academic_year', 'semester', 'branch']))
                ->withErrors($validator, 'storeSection')
                ->withInput();
        }
        $data = $validator->validated();
        $data['branch'] = empty($data['branch']) ? null : $data['branch'];
        try {
            Section::create($data);
            return redirect()->route('data-entry.sections.manage', $request->only(['plan_subject_id', 'academic_year', 'semester', 'branch']))
                ->with('success', 'Section created successfully.');
        } catch (Exception $e) { /* ... */
        }
    }

    public function update(Request $request, Section $section)
    {
        // ... (نفس كود update من الرد السابق مع تعديل بسيط للـ redirect) ...
        // ... والتأكد من تمرير بيانات السياق للـ redirect ...
        $errorBagName = 'updateSection_' . $section->id;
        $validator = Validator::make($request->all(), [ /* ... rules ... */]);
        $validator->after(function ($validator) use ($request, $section) { /* ... التحقق من التفرد ... */
        });

        if ($validator->fails()) {
            return redirect()->route('data-entry.sections.manage', [
                'plan_subject_id' => $section->plan_subject_id,
                'academic_year' => $section->academic_year,
                'semester' => $section->semester,
                'branch' => $section->branch,
            ])->withErrors($validator, $errorBagName)->withInput();
        }
        $data = $validator->safe()->only(['section_number', 'student_count', 'section_gender', 'branch']);
        $data['branch'] = empty($data['branch']) ? null : $data['branch'];
        try {
            $section->update($data);
            return redirect()->route('data-entry.sections.manage', [
                'plan_subject_id' => $section->plan_subject_id,
                'academic_year' => $section->academic_year,
                'semester' => $section->semester,
                'branch' => $section->branch,
            ])->with('success', 'Section updated successfully.');
        } catch (Exception $e) { /* ... */
        }
    }

    public function destroy(Section $section)
    {
        // ... (نفس كود destroy من الرد السابق مع تعديل بسيط للـ redirect) ...
        $redirectParams = [ /* ... بيانات السياق ... */];
        try {
            $section->delete();
            return redirect()->route('data-entry.sections.manage', $redirectParams)
                ->with('success', 'Section deleted successfully.');
        } catch (Exception $e) { /* ... */
        }
    }

// *********************************************************************************************************
// *********************************************************************************************************
    // --- الدوال الجديدة للتحكم من سياق PlanExpectedCount ---

    /**
     * Show the form for managing sections for a specific ExpectedCount context.
     */
    public function manageSectionsForContext(PlanExpectedCount $expectedCount) // Route Model Binding
    {
        try {
            // تحميل العلاقات اللازمة لـ expectedCount
            $expectedCount->load('plan.department');

            // جلب المواد لهذا السياق (خطة، مستوى، فصل)
            $planSubjectsForContext = PlanSubject::with('subject.subjectCategory')
                ->where('plan_id', $expectedCount->plan_id)
                ->where('plan_level', $expectedCount->plan_level)
                ->where('plan_semester', $expectedCount->plan_semester)
                ->get();

            // جلب الشعب الحالية المسجلة لهذا السياق
            $currentSectionsBySubject = $this->getCurrentSectionsGroupedBySubject(
                $planSubjectsForContext,
                $expectedCount->academic_year,
                $expectedCount->plan_semester, // **مهم: فصل الشعبة هو فصل الخطة/العدد المتوقع**
                $expectedCount->branch
            );

            return view('dashboard.data-entry.manage-sections-for-context', compact(
                'expectedCount', // كائن العدد المتوقع كاملاً
                'planSubjectsForContext',
                'currentSectionsBySubject'
            ));
        } catch (Exception $e) {
            Log::error('Error loading manage sections for context: ' . $e->getMessage());
            return redirect()->route('data-entry.plan-expected-counts.index')
                ->with('error', 'Could not load section management page for the selected context.');
        }
    }

    /**
     * Trigger section generation for a specific context from a button.
     * لتوليد الشعب تلقائيًا بناءً على أعداد الطلاب المتوقعين.
     */
    public function generateSectionsForContextButton(Request $request, PlanExpectedCount $expectedCount)
    {
        try {
            $this->generateOrUpdateSectionsLogic(
                $expectedCount->plan, // تمرير كائن الخطة
                $expectedCount->plan_level,
                $expectedCount->plan_semester, // فصل الخطة
                $expectedCount->academic_year,
                $expectedCount->branch,
                $expectedCount // تمرير كائن العدد المتوقع
            );
            return redirect()->route('data-entry.sections.manageContext', $expectedCount->id)
                ->with('success', 'Sections generated/updated successfully.');
        } catch (Exception $e) {
            Log::error('Manual Section Generation Failed for ExpectedCount ID ' . $expectedCount->id . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate sections.');
        }
    }

    /**
     * Core logic to generate or update sections for a given context.
     * هي الدالة الأساسية التي تحتوي على منطق إنشاء الشعب (نظرية و/أو عملية).
     */
    private function generateOrUpdateSectionsLogic(Plan $plan, $planLevel, $planSemester, $academicYear, $branch, PlanExpectedCount $expectedCount)
    {
        Log::info("Attempting to generate sections for Plan:{$plan->id}, L:{$planLevel}, S:{$planSemester}, Year:{$academicYear}, Branch:'{$branch}'");

        $planSubjects = PlanSubject::with('subject.subjectCategory')
            ->where('plan_id', $plan->id)
            ->where('plan_level', $planLevel)
            ->where('plan_semester', $planSemester)
            ->get();

        if ($planSubjects->isEmpty()) {
            Log::info("No subjects found for this context. No sections generated.");
            return;
        }

        $totalExpected = $expectedCount->male_count + $expectedCount->female_count;

        // --- حذف الشعب القديمة أولاً لهذا السياق المحدد ---
        Section::whereIn('plan_subject_id', $planSubjects->pluck('id'))
            ->where('academic_year', $academicYear)
            ->where('semester', $planSemester) // **فصل الشعبة هو نفسه فصل الخطة**
            ->where(function ($q) use ($branch) {
                is_null($branch) ? $q->whereNull('branch') : $q->where('branch', $branch);
            })
            ->delete();
        Log::info("Deleted old sections for the context.");

        if ($totalExpected <= 0) {
            Log::info("Total expected students is zero. No new sections will be created.");
            return;
        }

        // --- إنشاء شعب جديدة ---
        foreach ($planSubjects as $ps) {
            $subjectCategoryName = strtolower(optional(optional($ps->subject)->subjectCategory)->subject_category_name ?? '');
            $subjectId = $ps->subject_id;

            // 1. الشعب النظرية
            if (Str::contains($subjectCategoryName, 'theory') || Str::contains($subjectCategoryName, 'نظري') || Str::contains($subjectCategoryName, 'combined') || Str::contains($subjectCategoryName, 'مشترك')) {
                Section::create([
                    'plan_subject_id' => $ps->id,
                    'academic_year' => $academicYear,
                    'semester' => $planSemester, // **فصل الشعبة**
                    'section_number' => 1,
                    'student_count' => $totalExpected,
                    'section_gender' => 'Mixed',
                    'branch' => $branch,
                    // 'activity_type' => 'Theory', // لا نحتاجه الآن
                ]);
                Log::info("Created Theory Section for Subject ID {$subjectId}, PlanSub ID {$ps->id}");
            }

            // 2. الشعب العملية (إذا كانت المادة عملية أو مشتركة)
            if (Str::contains($subjectCategoryName, 'practical') || Str::contains($subjectCategoryName, 'عملي') || (Str::contains($subjectCategoryName, 'combined') || Str::contains($subjectCategoryName, 'مشترك'))) {
                // تجنب إنشاء شعب عملية لمادة نظرية بحتة إذا كانت الفئة "نظري" فقط
                if ($subjectCategoryName == 'theory' || Str::contains($subjectCategoryName, 'نظري')) {
                    if (!(Str::contains($subjectCategoryName, 'combined') || Str::contains($subjectCategoryName, 'مشترك'))) {
                        continue; // تخطى إنشاء عملي إذا كانت نظرية فقط وليست مشتركة
                    }
                }

                $labCapacity = self::DEFAULT_LAB_CAPACITY;
                if ($totalExpected > 0 && $labCapacity > 0) {
                    $numLabSections = ceil($totalExpected / $labCapacity);
                    $baseStudentsPerSection = floor($totalExpected / $numLabSections);
                    $remainderStudents = $totalExpected % $numLabSections;

                    for ($i = 1; $i <= $numLabSections; $i++) {
                        $studentsInThisSection = $baseStudentsPerSection + ($remainderStudents > 0 ? 1 : 0);
                        if ($remainderStudents > 0) $remainderStudents--;

                        if ($studentsInThisSection > 0) {
                            Section::create([
                                'plan_subject_id' => $ps->id,
                                'academic_year' => $academicYear,
                                'semester' => $planSemester, // **فصل الشعبة**
                                'section_number' => $i, // رقم شعبة العملي
                                'student_count' => $studentsInThisSection,
                                'section_gender' => 'Mixed',
                                'branch' => $branch,
                                // 'activity_type' => 'Practical', // لا نحتاجه
                            ]);
                            Log::info("Created Practical Section {$i} for Subject ID {$subjectId}, PlanSub ID {$ps->id}");
                        }
                    }
                }
            }
        }
        Log::info("Finished generating sections for the context.");
    }


    /**
     * Helper to get current sections grouped by subject for the manage view.
     * لجلب الشعب الحالية مجمعة حسب المادة.
     */
    private function getCurrentSectionsGroupedBySubject($planSubjectsForContext, $academicYear, $sectionsSemester, $branch)
    {
        $currentSectionsBySubject = collect();
        foreach ($planSubjectsForContext as $ps) {
            $query = Section::where('plan_subject_id', $ps->id)
                ->where('academic_year', $academicYear)
                ->where('semester', $sectionsSemester); // استخدام فصل الشعبة
            if (is_null($branch)) {
                $query->whereNull('branch');
            } else {
                $query->where('branch', $branch);
            }
            $currentSectionsBySubject[$ps->subject_id] = $query->orderBy('section_number')->get();
        }
        return $currentSectionsBySubject;
    }

    /**
     *
     * لتخزين شعبة جديدة يدويًا بعد التحقق من صحتها وتفردها.
     */
    public function storeSectionInContext(Request $request, PlanExpectedCount $expectedCount)
    {
        $errorBagName = 'storeSectionModal'; // اسم موحد للـ error bag لمودال الإضافة

        // 1. Validation
        $validator = Validator::make($request->all(), [
            'plan_subject_id_from_modal' => 'required|integer|exists:plan_subjects,id',
            'section_number' => 'required|integer|min:1',
            'student_count' => 'required|integer|min:0',
            'section_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
            // الفرع سيؤخذ من $expectedCount->branch، لكن يمكن إرساله كـ hidden للـ validation
            // 'branch_from_modal' => 'nullable|string|max:100', // إذا أردت التحقق منه
        ]);

        // 2. التحقق من التفرد يدوياً
        $validator->after(function ($validator) use ($request, $expectedCount) {
            if (!$validator->errors()->hasAny()) { // فقط إذا لم تكن هناك أخطاء أخرى
                // الفرع المستخدم للتحقق هو فرع السياق (expectedCount)
                $branchValue = $expectedCount->branch;

                $exists = Section::where('academic_year', $expectedCount->academic_year)
                                  ->where('semester', $expectedCount->plan_semester) // فصل الشعبة هو فصل الخطة/العدد المتوقع
                                  ->where('plan_subject_id', $request->input('plan_subject_id_from_modal'))
                                  ->where('section_number', $request->input('section_number'))
                                  ->where(function ($query) use ($branchValue) {
                                      if (is_null($branchValue)) { $query->whereNull('branch'); }
                                      else { $query->where('branch', $branchValue); }
                                  })->exists();
                if ($exists) {
                    $validator->errors()->add('section_unique', 'This section number already exists for this subject in this context (Year, Semester, Branch).');
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->route('data-entry.sections.manageContext', $expectedCount->id)
                        ->withErrors($validator, $errorBagName)
                        ->withInput();
        }

        // 3. Prepare Data
        $dataToCreate = [
            'plan_subject_id' => $request->input('plan_subject_id_from_modal'),
            'academic_year' => $expectedCount->academic_year,
            'semester' => $expectedCount->plan_semester, // **مهم: فصل الشعبة هو نفسه فصل الخطة/العدد المتوقع**
            'branch' => $expectedCount->branch, // استخدام الفرع من السياق
            'section_number' => $request->input('section_number'),
            'student_count' => $request->input('student_count'),
            'section_gender' => $request->input('section_gender'),
        ];

        // 4. Add to Database
        try {
            Section::create($dataToCreate);
            return redirect()->route('data-entry.sections.manageContext', $expectedCount->id)
                             ->with('success', 'Section added successfully to the context.');
        } catch (Exception $e) {
            Log::error('Section (in context) Creation Failed: ' . $e->getMessage());
            return redirect()->route('data-entry.sections.manageContext', $expectedCount->id)
                             ->with('error', 'Failed to add section. An unexpected error occurred.')
                             ->withInput(); // إرجاع كل المدخلات
        }
    }

    /**
     * Update the specified section in storage within its context.
     */
    public function updateSectionInContext(Request $request, Section $section) // Route Model Binding للشعبة
    {
        // تحديد السياق للعودة إليه (من الشعبة نفسها)
        $expectedCountContext = PlanExpectedCount::where('plan_id', $section->planSubject->plan_id)
                                        ->where('academic_year', $section->academic_year)
                                        ->where('plan_level', $section->planSubject->plan_level)
                                        ->where('plan_semester', $section->planSubject->plan_semester) // فصل الخطة
                                        ->where('branch', $section->branch) // فرع الشعبة هو فرع السياق
                                        ->first();
        if(!$expectedCountContext){
             Log::error("Context (ExpectedCount) not found for updating section ID: {$section->id}");
             return redirect()->route('data-entry.sections.index')->with('error', 'Could not determine the context to return to after update.');
        }

        $errorBagName = 'updateSectionModal_' . $section->id;

        // 1. Validation (فقط للحقول القابلة للتعديل)
        $validator = Validator::make($request->all(), [
             'section_number' => 'required|integer|min:1',
             'student_count' => 'required|integer|min:0',
             'section_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
             // الفرع لا يتم تعديله من هنا لأنه جزء من السياق الأصلي
        ]);

        // التحقق من التفرد لرقم الشعبة (إذا تغير)
         $validator->after(function ($validator) use ($request, $section) {
              if (!$validator->errors()->has('section_number') && $request->input('section_number') != $section->section_number) {
                 // الفرع المستخدم للتحقق هو فرع الشعبة الحالية (الذي لا يتغير)
                 $branchValue = $section->branch;
                 $exists = Section::where('academic_year', $section->academic_year)
                                   ->where('semester', $section->semester)
                                   ->where('plan_subject_id', $section->plan_subject_id)
                                   ->where('section_number', $request->input('section_number'))
                                   ->where(function ($query) use ($branchValue) {
                                       if (is_null($branchValue)) { $query->whereNull('branch'); }
                                       else { $query->where('branch', $branchValue); }
                                   })
                                   ->where('id', '!=', $section->id) // استثناء السجل الحالي
                                   ->exists();
                 if ($exists) {
                     $validator->errors()->add('section_unique', 'This section number already exists for this subject in this context.');
                 }
             }
         });

         if ($validator->fails()) {
             return redirect()->route('data-entry.sections.manageContext', $expectedCountContext->id)
                         ->withErrors($validator, $errorBagName)
                         ->withInput();
         }

        // 2. Prepare Data (فقط الحقول المسموح بتعديلها)
        $dataToUpdate = $validator->safe()->only(['section_number', 'student_count', 'section_gender']);
        // لا نعدل الفرع من هنا، هو جزء من السياق

        // 3. Update Database
        try {
            $section->update($dataToUpdate);
            return redirect()->route('data-entry.sections.manageContext', $expectedCountContext->id)
                             ->with('success', 'Section updated successfully.');
        } catch (Exception $e) {
            Log::error('Section Update (in context) Failed for ID ' . $section->id . ': ' . $e->getMessage());
            return redirect()->route('data-entry.sections.manageContext', $expectedCountContext->id)
                             ->with('error', 'Failed to update section.')
                             ->withInput();
        }
    }

    /**
     * Remove the specified section from storage within its context.
     */
    public function destroySectionInContext(Section $section)
    {
        // تحديد السياق للعودة إليه
         $expectedCountContext = PlanExpectedCount::where('plan_id', $section->planSubject->plan_id)
                                        ->where('academic_year', $section->academic_year)
                                        ->where('plan_level', $section->planSubject->plan_level)
                                        ->where('plan_semester', $section->planSubject->plan_semester)
                                        ->where('branch', $section->branch)
                                        ->first();
        try {
            // (اختياري) التحقق من وجود ارتباطات في generated_schedules قبل الحذف
            // if ($section->scheduleEntries()->exists()) {
            //     return redirect()->route('data-entry.sections.manageContext', $expectedCountContext->id)
            //                      ->with('error', 'Cannot delete section. It is currently scheduled.');
            // }

            $section->delete();

            if ($expectedCountContext) {
                return redirect()->route('data-entry.sections.manageContext', $expectedCountContext->id)
                                 ->with('success', 'Section deleted successfully.');
            }
            // إذا لم يتم العثور على سياق العدد المتوقع، ارجع لصفحة الشعب العامة
            return redirect()->route('data-entry.sections.index')
                             ->with('success', 'Section deleted, but could not redirect to specific context.');

        } catch (Exception $e) {
            Log::error('Section Deletion (in context) Failed for ID ' . $section->id . ': ' . $e->getMessage());
            if ($expectedCountContext) {
                return redirect()->route('data-entry.sections.manageContext', $expectedCountContext->id)
                                 ->with('error', 'Failed to delete section.');
            }
            return redirect()->route('data-entry.sections.index')
                             ->with('error', 'Failed to delete section.');
        }
    }


    // public function index(Request $request) // إضافة Request للفلترة
    // {
    //     try {
    //         // جلب الشعب مع العلاقات المتداخلة للفرز والعرض
    //         // استخدام when للفلترة الاختيارية
    //         $query = Section::with([
    //             'planSubject.plan:id,plan_no', // جلب الخطة
    //             'planSubject.subject:id,subject_no,subject_name' // جلب المادة
    //         ])
    //             ->when($request->filled('plan_id'), function ($q) use ($request) {
    //                 $q->whereHas('planSubject.plan', fn($subQ) => $subQ->where('id', $request->plan_id));
    //             })
    //             ->when($request->filled('level'), function ($q) use ($request) {
    //                 $q->whereHas('planSubject', fn($subQ) => $subQ->where('plan_level', $request->level));
    //             })
    //             ->when($request->filled('semester'), function ($q) use ($request) {
    //                 // يجب التحقق من فصل الشعبة نفسها
    //                 $q->where('semester', $request->semester);
    //             })
    //             ->when($request->filled('academic_year'), function ($q) use ($request) {
    //                 $q->where('academic_year', $request->academic_year);
    //             });


    //         $sections = $query->orderBy('academic_year', 'desc')
    //             ->orderBy('semester')
    //             ->orderBy(function ($q) { // ترتيب معقد حسب الخطة والمستوى والمادة
    //                 $q->from('plan_subjects')->whereColumn('plan_subjects.id', 'sections.plan_subject_id')->selectRaw('plan_id');
    //             })
    //             ->orderBy(function ($q) {
    //                 $q->from('plan_subjects')->whereColumn('plan_subjects.id', 'sections.plan_subject_id')->selectRaw('plan_level');
    //             })
    //             ->orderBy(function ($q) {
    //                 $q->from('plan_subjects')->whereColumn('plan_subjects.id', 'sections.plan_subject_id')->selectRaw('subject_id');
    //             })
    //             ->orderBy('section_number')
    //             ->paginate(20);

    //         // جلب بيانات plan_subjects للـ dropdown في المودال
    //         // (قد يكون كبيراً جداً، الأفضل جلبها حسب الخطة/المستوى/الفصل المختارة)
    //         // سنقوم بجلبها كلها الآن للتبسيط، مع تحميل العلاقات اللازمة
    //         $planSubjects = PlanSubject::with(['plan:id,plan_name', 'subject:id,subject_no,subject_name'])
    //             ->whereHas('plan', fn($q) => $q->where('is_active', true)) // فقط للخطط الفعالة
    //             ->get();

    //         return view('dashboard.data-entry.sections', compact('sections', 'planSubjects'));
    //     } catch (Exception $e) {
    //         Log::error('Error fetching sections: ' . $e->getMessage());
    //         return redirect()->back()->with('error', 'Could not load sections.');
    //     }
    // }


    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     // 1. Validation (مع التحقق من تفرد رقم الشعبة لنفس المادة/السنة/الفصل/الفرع)
    //     $validator = Validator::make($request->all(), [
    //         'academic_year' => 'required|integer|digits:4|min:2020',
    //         'semester' => 'required|integer|min:1|max:3',
    //         'plan_subject_id' => 'required|integer|exists:plan_subjects,id',
    //         'section_number' => 'required|integer|min:1',
    //         'student_count' => 'required|integer|min:0',
    //         'section_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
    //         'branch' => 'nullable|string|max:100',
    //     ]);

    //     // التحقق من التفرد يدوياً
    //     $validator->after(function ($validator) use ($request) {
    //         if (!$validator->errors()->hasAny(['academic_year', 'semester', 'plan_subject_id', 'section_number'])) {
    //             $exists = Section::where('academic_year', $request->input('academic_year'))
    //                 ->where('semester', $request->input('semester'))
    //                 ->where('plan_subject_id', $request->input('plan_subject_id'))
    //                 ->where('section_number', $request->input('section_number'))
    //                 ->where(function ($query) use ($request) {
    //                     if (empty($request->input('branch'))) {
    //                         $query->whereNull('branch');
    //                     } else {
    //                         $query->where('branch', $request->input('branch'));
    //                     }
    //                 })->exists();
    //             if ($exists) {
    //                 $validator->errors()->add('section_unique', 'This section number already exists for this subject in this year/semester/branch.');
    //             }
    //         }
    //     });

    //     if ($validator->fails()) {
    //         return redirect()->back()
    //             ->withErrors($validator, 'store')
    //             ->withInput();
    //     }

    //     // 2. Prepare Data
    //     $data = $validator->validated();
    //     $data['branch'] = empty($data['branch']) ? null : $data['branch'];

    //     // 3. Add to Database
    //     try {
    //         Section::create($data);
    //         return redirect()->route('data-entry.sections.index')
    //             ->with('success', 'Section created successfully.');
    //     } catch (Exception $e) {
    //         Log::error('Section Creation Failed: ' . $e->getMessage());
    //         return redirect()->back()
    //             ->with('error', 'Failed to create section.')
    //             ->withInput();
    //     }
    // }

    // /**
    //  * Update the specified resource in storage.
    //  */
    // public function update(Request $request, Section $section)
    // {
    //     // 1. Validation (لا نسمح بتغيير المادة/السنة/الفصل عادةً، فقط رقم الشعبة والعدد والجنس والفرع)
    //     $validator = Validator::make($request->all(), [
    //         'section_number' => 'required|integer|min:1',
    //         'student_count' => 'required|integer|min:0',
    //         'section_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
    //         'branch' => 'nullable|string|max:100',
    //         // لا ننسى تمرير الحقول الأصلية للتحقق من التفرد
    //         'academic_year' => 'required|integer', // من الحقل المخفي
    //         'semester' => 'required|integer',
    //         'plan_subject_id' => 'required|integer|exists:plan_subjects,id', // من الحقل المخفي
    //     ]);

    //     // التحقق من التفرد يدوياً (مع تجاهل الصف الحالي)
    //     $validator->after(function ($validator) use ($request, $section) {
    //         if (!$validator->errors()->hasAny(['section_number'])) {
    //             $exists = Section::where('academic_year', $request->input('academic_year')) // استخدام القيمة الأصلية
    //                 ->where('semester', $request->input('semester')) // استخدام القيمة الأصلية
    //                 ->where('plan_subject_id', $request->input('plan_subject_id')) // استخدام القيمة الأصلية
    //                 ->where('section_number', $request->input('section_number')) // القيمة الجديدة
    //                 ->where(function ($query) use ($request) {
    //                     if (empty($request->input('branch'))) {
    //                         $query->whereNull('branch');
    //                     } else {
    //                         $query->where('branch', $request->input('branch'));
    //                     }
    //                 })
    //                 ->where('id', '!=', $section->id) // استثناء السجل الحالي
    //                 ->exists();
    //             if ($exists) {
    //                 $validator->errors()->add('section_unique', 'This section number already exists for this subject in this year/semester/branch.');
    //             }
    //         }
    //     });

    //     if ($validator->fails()) {
    //         return redirect()->back()
    //             ->withErrors($validator, 'update_' . $section->id)
    //             ->withInput();
    //     }

    //     // 2. Prepare Data (فقط الحقول المسموح بتعديلها)
    //     $data = $validator->safe()->only(['section_number', 'student_count', 'section_gender', 'branch']); // استخدام safe() للحصول على البيانات التي تم التحقق منها فقط
    //     $data['branch'] = empty($data['branch']) ? null : $data['branch'];

    //     // 3. Update Database
    //     try {
    //         $section->update($data);
    //         return redirect()->route('data-entry.sections.index')
    //             ->with('success', 'Section updated successfully.');
    //     } catch (Exception $e) {
    //         Log::error('Section Update Failed: ' . $e->getMessage());
    //         return redirect()->back()
    //             ->with('error', 'Failed to update section.')
    //             ->withInput();
    //     }
    // }

    // /**
    //  * Remove the specified resource from storage.
    //  */
    // public function destroy(Section $section)
    // {
    //     // التحقق من وجود ارتباطات في الجدول النهائي
    //     // if ($section->scheduleEntries()->exists()) {
    //     //     return redirect()->route('data-entry.sections.index')
    //     //                      ->with('error', 'Cannot delete section. It is used in schedules.');
    //     // }

    //     try {
    //         $section->delete();
    //         return redirect()->route('data-entry.sections.index')
    //             ->with('success', 'Section deleted successfully.');
    //     } catch (Exception $e) {
    //         Log::error('Section Deletion Failed: ' . $e->getMessage());
    //         return redirect()->route('data-entry.sections.index')
    //             ->with('error', 'Failed to delete section.');
    //     }
    // }

    // --- API Methods (يمكن إضافتها لاحقاً بنفس النمط) ---

} // نهاية الكلاس
