<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Plan;
use App\Models\Department;
use App\Models\PlanSubject;
use App\Models\PlanExpectedCount;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Str;

class SectionController22 extends Controller
{
    // القيم الافتراضية إذا لم يحددها المستخدم للمادة
    const DEFAULT_THEORY_SECTION_CAPACITY = 50;
    const DEFAULT_PRACTICAL_SECTION_CAPACITY = 25;
    const MIN_PRACTICAL_SECTION_STUDENTS = 15; // الحد الأدنى لمحاولة تجنب شعب عملية صغيرة جداً

    /**
     * Display a listing of all sections with filtering capabilities.
     */
    public function index(Request $request)
    {
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
                ->orderBy('activity_type')->orderBy('section_number')
                ->paginate(20);

            $academicYears = Section::query()->orWhereNotNull('academic_year')->distinct()->orderBy('academic_year', 'desc')->pluck('academic_year');
            $departments = Department::orderBy('department_name')->get(['id', 'department_name']);
            $plans = Plan::where('is_active', true)->orderBy('plan_name')->get(['id', 'plan_no', 'plan_name']);
            $subjectsForFilter = Subject::orderBy('subject_no')->get(['id', 'subject_no', 'subject_name']);
            $levels = PlanSubject::query()->orWhereNotNull('plan_level')->distinct()->orderBy('plan_level')->pluck('plan_level');
            $semesters = [1 => 'First', 2 => 'Second', 3 => 'Summer'];

            return view('dashboard.data-entry.sections', compact(
                'sections',
                'academicYears',
                'departments',
                'plans',
                'subjectsForFilter',
                'levels',
                'semesters',
                'request'
            ));
        } catch (Exception $e) {
            Log::error('Error fetching sections: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load sections list.');
        }
    }

    /**
     * Show the page for managing ALL sections of a specific SUBJECT within a specific CONTEXT.
     */
    // public function manageSubjectContext(Request $request)
    // {
    //     $request->validate([
    //         'plan_subject_id' => 'required|integer|exists:plan_subjects,id',
    //         'academic_year' => 'required|integer|digits:4',
    //         'semester' => 'required|integer|in:1,2,3', // هذا هو فصل الشعب
    //         'branch' => 'nullable|string|max:100',
    //     ]);

    //     $planSubjectId = $request->plan_subject_id;
    //     $academicYear = $request->academic_year;
    //     $semesterOfSections = $request->semester;
    //     $branch = $request->filled('branch') ? $request->branch : null;

    //     try {
    //         $planSubject = PlanSubject::with(['plan.department', 'subject.subjectCategory'])->findOrFail($planSubjectId);

    //         $expectedCount = PlanExpectedCount::where('plan_id', $planSubject->plan_id)
    //             ->where('plan_level', $planSubject->plan_level)
    //             ->where('plan_semester', $planSubject->plan_semester)
    //             ->where('academic_year', $academicYear)
    //             ->where(fn ($q) => is_null($branch) ? $q->whereNull('branch') : $q->where('branch', $branch))
    //             ->first();

    //         $currentSections = Section::where('plan_subject_id', $planSubjectId)
    //                                   ->where('academic_year', $academicYear)
    //                                   ->where('semester', $semesterOfSections)
    //                                   ->where(fn ($q) => is_null($branch) ? $q->whereNull('branch') : $q->where('branch', $branch))
    //                                   ->orderBy('activity_type')->orderBy('section_number')->get();

    //         return view('dashboard.data-entry.sections.manage', compact(
    //             'planSubject', 'academicYear', 'semesterOfSections', 'branch',
    //             'expectedCount', 'currentSections'
    //         ));

    //     } catch (Exception $e) {
    //         Log::error('Error loading manage sections for subject context: ' . $e->getMessage());
    //         return redirect()->route('data-entry.sections.index')->with('error', 'Could not load section management page for the selected subject.');
    //     }
    // }
    // في SectionController.php
    // public function manageSubjectContext(Request $request)
    // {
    //     // 1. Validation للبارامترات القادمة من الـ Query String
    //     // (التي تم تمريرها من زر "Manage" في صفحة index)
    //     $validatedData = $request->validate([
    //         'plan_subject_id' => 'required|integer|exists:plan_subjects,id',
    //         'academic_year' => 'required|integer|digits:4',
    //         'semester' => 'required|integer|in:1,2,3', // *** اسم البارامتر هنا هو 'semester' ***
    //         'branch' => 'nullable|string|max:100',
    //     ]);

    //     $planSubjectId = $validatedData['plan_subject_id'];
    //     $academicYear = $validatedData['academic_year'];
    //     $semesterOfSections = $validatedData['semester']; // *** القيمة ستأتي من 'semester' ***
    //     $branch = $validatedData['branch'] ?? null;


    //     try {
    //         $planSubject = PlanSubject::with(['plan.department', 'subject.subjectCategory'])->findOrFail($planSubjectId);

    //         // جلب العدد المتوقع لهذا السياق العام
    //         // (فصل الخطة هنا هو $planSubject->plan_semester)
    //         $expectedCount = PlanExpectedCount::where('plan_id', $planSubject->plan_id)
    //             ->where('plan_level', $planSubject->plan_level)
    //             ->where('plan_semester', $planSubject->plan_semester) // ** فصل الخطة **
    //             ->where('academic_year', $academicYear)
    //             ->where(fn($q) => is_null($branch) ? $q->whereNull('branch') : $q->where('branch', $branch))
    //             ->first();

    //         // جلب الشعب الحالية لهذه المادة المحددة في هذا السياق
    //         // (فصل الشعبة هو $semesterOfSections)
    //         $currentSections = Section::where('plan_subject_id', $planSubjectId)
    //             ->where('academic_year', $academicYear)
    //             ->where('semester', $semesterOfSections) // ** فصل الشعبة **
    //             ->where(fn($q) => is_null($branch) ? $q->whereNull('branch') : $q->where('branch', $branch))
    //             ->orderBy('activity_type')->orderBy('section_number')->get();

    //         return view('dashboard.data-entry.sections.manage', compact( // ** اسم الـ View الجديد **
    //             'planSubject',
    //             'academicYear',
    //             'semesterOfSections', // هذا هو فصل الشعب
    //             'branch',
    //             'expectedCount',
    //             'currentSections'
    //         ));
    //     } catch (Exception $e) {
    //         Log::error('Error loading manage sections for subject context: ' . $e->getMessage());
    //         return redirect()->route('data-entry.sections.index')->with('error', 'Could not load section management page for the selected subject.');
    //     }
    // }

    public function manageSubjectContext(Request $request) // الدالة تستقبل الـ Request
    {
        // 1. Validation للبارامترات القادمة من الـ Query String
        $validatedData = $request->validate([
            'plan_subject_id' => 'required|integer|exists:plan_subjects,id',
            'academic_year' => 'required|integer|digits:4',
            'semester' => 'required|integer|in:1,2,3', // *** نتوقع بارامتر 'semester' ***
            'branch' => 'nullable|string|max:100',
        ]);

        // استخدام القيم المتحقق منها
        $planSubjectId = $validatedData['plan_subject_id'];
        $academicYear = $validatedData['academic_year'];
        $semesterOfSections = $validatedData['semester']; // *** هنا نستخدم القيمة من 'semester' ***
        $branch = $validatedData['branch'] ?? null;

        try {
            $planSubject = PlanSubject::with(['plan.department', 'subject.subjectCategory'])->findOrFail($planSubjectId);

            $expectedCount = PlanExpectedCount::where('plan_id', $planSubject->plan_id)
                ->where('plan_level', $planSubject->plan_level)
                ->where('plan_semester', $planSubject->plan_semester) // فصل الخطة
                ->where('academic_year', $academicYear)
                ->where(fn($q) => is_null($branch) ? $q->whereNull('branch') : $q->where('branch', $branch))
                ->first();

            $currentSections = Section::where('plan_subject_id', $planSubjectId)
                ->where('academic_year', $academicYear)
                ->where('semester', $semesterOfSections) // فصل الشعبة
                ->where(fn($q) => is_null($branch) ? $q->whereNull('branch') : $q->where('branch', $branch))
                ->orderBy('activity_type')->orderBy('section_number')->get();
            $subjectCategoryName = $planSubject;
            return view('dashboard.data-entry.sections-manage', compact( // ** اسم الـ View الجديد **
                'planSubject',
                'academicYear',
                'semesterOfSections', // هذا هو فصل الشعب الذي سيُستخدم في الـ view
                'branch',
                'expectedCount',
                'currentSections',
                'subjectCategoryName'
            ));
        } catch (Exception $e) {
            Log::error('Error loading manage sections for subject context: ' . $e->getMessage());
            // إضافة تفاصيل أكثر للخطأ إذا أمكن
            return redirect()->route('data-entry.sections.index')
                ->with('error', 'Could not load section management page for the selected subject. Details: ' . $e->getMessage());
        }
    }

    /**
     * Trigger section generation for a specific SUBJECT within a context from a button.
     */
    public function generateForSubject(Request $request)
    {
        $request->validate([
            'plan_subject_id' => 'required|integer|exists:plan_subjects,id',
            'expected_count_id' => 'required|integer|exists:plan_expected_counts,id',
        ]);

        $planSubject = PlanSubject::find($request->plan_subject_id);
        $expectedCount = PlanExpectedCount::find($request->expected_count_id);

        if (!$planSubject || !$expectedCount) {
            return redirect()->back()->with('error', 'Invalid context for generating sections.');
        }

        try {
            $this->generateSectionsLogic($planSubject, $expectedCount);
            return redirect()->route('data-entry.sections.manageSubjectContext', [
                'plan_subject_id' => $planSubject->id,
                'academic_year' => $expectedCount->academic_year,
                'semester' => $expectedCount->plan_semester,
                'branch' => $expectedCount->branch,
            ])->with('success', 'Sections for subject generated/updated successfully.');
        } catch (Exception $e) {
            Log::error('Manual Section Generation for Subject Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->back()->with('error', 'Failed to generate sections: ' . $e->getMessage());
        }
    }

    /**
     * Core logic to generate/update sections for a SINGLE PlanSubject.
     */
    private function generateSectionsLogic(PlanSubject $planSubject, PlanExpectedCount $expectedCount)
    {
        Log::info("Generating sections for PS ID: {$planSubject->id} within ExpectedCount ID: {$expectedCount->id}");

        $subject = $planSubject->subject()->with('subjectCategory')->first();
        if (!$subject || !$subject->subjectCategory) {
            Log::warning("Subject or Category not found for PS ID {$planSubject->id}. Skipping.");
            throw new Exception("Subject or its category is missing for PlanSubject ID: {$planSubject->id}.");
        }

        $totalExpected = $expectedCount->male_count + $expectedCount->female_count;

        Section::where('plan_subject_id', $planSubject->id)
            ->where('academic_year', $expectedCount->academic_year)
            ->where('semester', $expectedCount->plan_semester)
            ->where(function ($q) use ($expectedCount) {
                is_null($expectedCount->branch) ? $q->whereNull('branch') : $q->where('branch', $expectedCount->branch);
            })
            ->delete();
        Log::info("Deleted old sections for PS ID {$planSubject->id} in context.");

        if ($totalExpected <= 0) {
            Log::info("Total expected is zero for context.");
            return;
        }

        $nextSectionNumber = 1;

        // الجزء النظري
        if (($subject->theoretical_hours ?? 0) > 0) {
            $theoryCapacity = $subject->capacity_theoretical_section ?? self::DEFAULT_THEORY_SECTION_CAPACITY;
            if ($theoryCapacity <= 0) $theoryCapacity = self::DEFAULT_THEORY_SECTION_CAPACITY; // Fallback if zero

            $numTheorySections = ceil($totalExpected / $theoryCapacity);
            $baseStudentsPerTheory = floor($totalExpected / $numTheorySections);
            $remainderTheory = $totalExpected % $numTheorySections;

            for ($i = 0; $i < $numTheorySections; $i++) {
                $studentsInThisTheorySec = $baseStudentsPerTheory + ($remainderTheory > 0 ? 1 : 0);
                if ($remainderTheory > 0) $remainderTheory--;
                if ($studentsInThisTheorySec > 0) {
                    Section::create([
                        'plan_subject_id' => $planSubject->id,
                        'academic_year' => $expectedCount->academic_year,
                        'semester' => $expectedCount->plan_semester,
                        'activity_type' => 'Theory',
                        'section_number' => $nextSectionNumber++,
                        'student_count' => $studentsInThisTheorySec,
                        'section_gender' => 'Mixed',
                        'branch' => $expectedCount->branch,
                    ]);
                }
            }
            Log::info("Created {$numTheorySections} Theory Sections for PS ID {$planSubject->id}");
        }

        // الجزء العملي
        if (($subject->practical_hours ?? 0) > 0) {
            $practicalCapacity = $subject->capacity_practical_section ?? self::DEFAULT_PRACTICAL_SECTION_CAPACITY;
            if ($practicalCapacity <= 0) $practicalCapacity = self::DEFAULT_PRACTICAL_SECTION_CAPACITY;

            $numPracticalSections = ceil($totalExpected / $practicalCapacity);
            // المنطق الجديد لتجنب شعب صغيرة جداً للعملي
            if ($numPracticalSections > 1) {
                $lastSectionStudents = $totalExpected - (floor($totalExpected / $practicalCapacity) * $practicalCapacity);
                if ($lastSectionStudents > 0 && $lastSectionStudents < self::MIN_PRACTICAL_SECTION_STUDENTS && $numPracticalSections > 1) {
                    $numPracticalSections = floor($totalExpected / $practicalCapacity); // تقليل عدد الشعب
                    if ($numPracticalSections == 0 && $totalExpected > 0) $numPracticalSections = 1; // Ensure at least one section if students exist
                }
            }
            if ($numPracticalSections == 0 && $totalExpected > 0) $numPracticalSections = 1;


            if ($numPracticalSections > 0) {
                $baseStudentsPerPractical = floor($totalExpected / $numPracticalSections);
                $remainderPractical = $totalExpected % $numPracticalSections;

                for ($i = 0; $i < $numPracticalSections; $i++) {
                    $studentsInThisPracticalSec = $baseStudentsPerPractical + ($remainderPractical > 0 ? 1 : 0);
                    if ($remainderPractical > 0) $remainderPractical--;
                    if ($studentsInThisPracticalSec > 0) {
                        Section::create([
                            'plan_subject_id' => $planSubject->id,
                            'academic_year' => $expectedCount->academic_year,
                            'semester' => $expectedCount->plan_semester,
                            'activity_type' => 'Practical',
                            'section_number' => $nextSectionNumber++,
                            'student_count' => $studentsInThisPracticalSec,
                            'section_gender' => 'Mixed',
                            'branch' => $expectedCount->branch,
                        ]);
                    }
                }
                Log::info("Created {$numPracticalSections} Practical Sections for PS ID {$planSubject->id}");
            }
        }
        Log::info("Finished generating sections for PS ID {$planSubject->id}.");
    }


    /**
     * Store a newly created section.
     */
    public function store(Request $request)
    {
        $errorBagName = 'addSectionModal';
        $validator = Validator::make($request->all(), [
            'plan_subject_id' => 'required|integer|exists:plan_subjects,id',
            'activity_type' => ['required', Rule::in(['Theory', 'Practical'])],
            'academic_year' => 'required|integer|digits:4',
            'semester' => 'required|integer|in:1,2,3',
            'branch' => 'nullable|string|max:100',
            'section_number' => 'required|integer|min:1',
            'student_count' => 'required|integer|min:0',
            'section_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
        ]);

        $planSubjectId = $request->input('plan_subject_id');
        $academicYear = $request->input('academic_year');
        $semester = $request->input('semester');
        $branch = $request->input('branch');
        $activityType = $request->input('activity_type');
        $validator->after(function ($validator) use ($request, $planSubjectId, $academicYear, $semester, $branch, $activityType) {
            if (!$validator->errors()->hasAny()) {
                $exists = Section::where('plan_subject_id', $planSubjectId)
                ->where('academic_year', $academicYear)
                ->where('semester', $semester)
                ->where('activity_type', $activityType)
                ->where('section_number', $request->input('section_number'))
                ->where(fn($q) => is_null($branch) ? $q->whereNull('branch') : $q->where('branch', $branch))
                ->exists();
                // dd([$request->all()
                //     ,$exists,
                //     planExpectedCount::where('plan_subject_id', $planSubjectId)->where('plan_semester',$semester)
                // ]);
                if ($exists) {
                    $validator->errors()->add('section_unique', 'This section (number & activity type) already exists.');
                    // dd($request->all());
                }
            }
        });

        $redirectParams = ['plan_subject_id' => $planSubjectId, 'academic_year' => $academicYear, 'semester_of_sections' => $semester, 'branch' => $branch];
        if ($validator->fails()) {
            return redirect()->route('data-entry.sections.manageSubjectContext', $redirectParams)
                ->withErrors($validator, $errorBagName)->withInput();
        }

        try {
            $data = $validator->validated();
            $data['branch'] = empty($data['branch']) ? null : $data['branch'];
            Section::create($data);
            return redirect()->route('data-entry.sections.manageSubjectContext', $redirectParams)->with('success', 'Section added.');
        } catch (Exception $e) {
            Log::error('Section Store Failed: ' . $e->getMessage());
            return redirect()->route('data-entry.sections.manageSubjectContext', $redirectParams)->with('error', 'Failed to add section.')->withInput();
        }
    }

    /**
     * Update the specified section.
     */
    public function update(Request $request, Section $section)
    {
        $errorBagName = 'editSectionModal_' . $section->id;
        $redirectParams = ['plan_subject_id' => $section->plan_subject_id, 'academic_year' => $section->academic_year, 'semester_of_sections' => $section->semester, 'branch' => $section->branch];

        $validator = Validator::make($request->all(), [
            'section_number' => 'required|integer|min:1',
            'student_count' => 'required|integer|min:0',
            'section_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
        ]);

        // التحقق من تفرد رقم الشعبة إذا تغير
        $validator->after(function ($validator) use ($request, $section) {
            if (!$validator->errors()->has('section_number') && $request->input('section_number') != $section->section_number) {
                $exists = Section::where('plan_subject_id', $section->plan_subject_id)
                    ->where('academic_year', $section->academic_year)
                    ->where('semester', $section->semester)
                    ->where('activity_type', $section->activity_type)
                    ->where('section_number', $request->input('section_number'))
                    ->where('branch', $section->branch)
                    ->where('id', '!=', $section->id)
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('section_unique', 'This section number already exists.');
                }
            }
        });

        // التحقق من عدم تجاوز العدد الكلي للطلاب
        $validator->after(function ($validator) use ($request, $section) {
            if (!$validator->errors()->has('student_count')) {
                $expectedCount = PlanExpectedCount::where('plan_id', $section->planSubject->plan_id)
                    ->where('academic_year', $section->academic_year)
                    ->where('plan_level', $section->planSubject->plan_level)
                    ->where('plan_semester', $section->planSubject->plan_semester)
                    ->where('branch', $section->branch)->first();

                if ($expectedCount) {
                    $newStudentCount = (int) $request->input('student_count');
                    $totalExpected = $expectedCount->male_count + $expectedCount->female_count;
                    $otherSectionsSum = Section::where('plan_subject_id', $section->plan_subject_id)
                        ->where('academic_year', $section->academic_year)
                        ->where('semester', $section->semester)
                        ->where('activity_type', $section->activity_type) // ** مهم: لنفس نوع النشاط **
                        ->where('branch', $section->branch)
                        ->where('id', '!=', $section->id)
                        ->sum('student_count');
                    if (($otherSectionsSum + $newStudentCount) > $totalExpected) {
                        $validator->errors()->add('student_count', "Total students (" . ($otherSectionsSum + $newStudentCount) . ") exceeds expected ({$totalExpected}).");
                    }
                }
            }
        });


        if ($validator->fails()) {
            return redirect()->route('data-entry.sections.manageSubjectContext', $redirectParams)
                ->withErrors($validator, $errorBagName)->withInput();
        }

        try {
            $dataToUpdate = $validator->safe()->only(['section_number', 'student_count', 'section_gender']);
            $section->update($dataToUpdate);
            return redirect()->route('data-entry.sections.manageSubjectContext', $redirectParams)->with('success', 'Section updated.');
        } catch (Exception $e) {
            Log::error('Section Update Failed: ' . $e->getMessage());
            return redirect()->route('data-entry.sections.manageSubjectContext', $redirectParams)->with('error', 'Failed to update.')->withInput();
        }
    }

    /**
     * Remove the specified section.
     */
    public function destroy(Section $section)
    {
        $redirectParams = ['plan_subject_id' => $section->plan_subject_id, 'academic_year' => $section->academic_year, 'semester_of_sections' => $section->semester, 'branch' => $section->branch];
        try {
            // لا يوجد قيود إضافية حالياً على الحذف هنا
            $section->delete();
            return redirect()->route('data-entry.sections.manageSubjectContext', $redirectParams)->with('success', 'Section deleted.');
        } catch (Exception $e) {
            Log::error('Section Destroy Failed: ' . $e->getMessage());
            return redirect()->route('data-entry.sections.manageSubjectContext', $redirectParams)->with('error', 'Failed to delete.');
        }
    }










    // ************************************************************************************************

    const DEFAULT_THEORY_CAPACITY = 50; // افترض سعة كبيرة جداً للنظري المشترك
    const DEFAULT_LAB_CAPACITY = 25;    // سعة افتراضية للمختبر

    public function manageSectionsForContext(PlanExpectedCount $expectedCount) // Route Model Binding
    {
        try {
            $expectedCount->load('plan.department');

            $planSubjectsForContext = PlanSubject::with('subject.subjectCategory')
                ->where('plan_id', $expectedCount->plan_id)
                ->where('plan_level', $expectedCount->plan_level)
                ->where('plan_semester', $expectedCount->plan_semester)
                ->get();

            // جلب الشعب الحالية مجمعة حسب المادة ونوع النشاط
            $currentSectionsBySubjectAndActivity = $this->getCurrentSectionsGrouped($planSubjectsForContext, $expectedCount);
            // dd('ddd');

            return view('dashboard.data-entry.manage-sections-for-context', compact(
                'expectedCount',
                'planSubjectsForContext',
                'currentSectionsBySubjectAndActivity' // تم تغيير الاسم
            ));
        } catch (Exception $e) {
            Log::error('Error loading manage sections for context: ' . $e->getMessage());
            return redirect()->route('data-entry.plan-expected-counts.index')
                ->with('error', 'Could not load section management page for the selected context.');
        }
    }

    private function getCurrentSectionsGrouped($planSubjectsForContext, PlanExpectedCount $expectedCount)
    {
        $grouped = collect();
        foreach ($planSubjectsForContext as $ps) {
            $sections = Section::where('plan_subject_id', $ps->id)
                ->where('academic_year', $expectedCount->academic_year)
                ->where('semester', $expectedCount->plan_semester) // فصل الشعبة
                ->where(function ($q) use ($expectedCount) {
                    is_null($expectedCount->branch) ? $q->whereNull('branch') : $q->where('branch', $expectedCount->branch);
                })
                ->orderBy('activity_type')->orderBy('section_number') // ترتيب إضافي
                ->get();
            // تجميع إضافي حسب activity_type
            $grouped[$ps->subject_id] = $sections->groupBy('activity_type');
        }
        return $grouped;
    }

    public function generateSectionsForContextButton(Request $request, PlanExpectedCount $expectedCount)
    {
        try {
            $this->generateSectionsLogic22($expectedCount); // تمرير كائن العدد المتوقع مباشرة
            return redirect()->route('data-entry.sections.manageContext', $expectedCount->id)
                ->with('success', 'Sections generated/updated successfully.');
        } catch (Exception $e) {
            Log::error('Manual Section Generation Failed for ExpectedCount ID ' . $expectedCount->id . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate sections: ' . $e->getMessage());
        }
    }

    private function generateSectionsLogic22(PlanExpectedCount $expectedCount)
    {
        Log::info("Generating sections for ExpectedCount ID: {$expectedCount->id}");

        $planSubjects = PlanSubject::with('subject.subjectCategory')
            ->where('plan_id', $expectedCount->plan_id)
            ->where('plan_level', $expectedCount->plan_level)
            ->where('plan_semester', $expectedCount->plan_semester)
            ->get();

        if ($planSubjects->isEmpty()) {
            Log::info("No subjects for context.");
            return;
        }

        $totalExpected = $expectedCount->male_count + $expectedCount->female_count;

        Section::whereIn('plan_subject_id', $planSubjects->pluck('id'))
            ->where('academic_year', $expectedCount->academic_year)
            ->where('semester', $expectedCount->plan_semester)
            ->where(function ($q) use ($expectedCount) {
                is_null($expectedCount->branch) ? $q->whereNull('branch') : $q->where('branch', $expectedCount->branch);
            })
            ->delete();
        Log::info("Deleted old sections for context of ExpectedCount ID: {$expectedCount->id}");

        if ($totalExpected <= 0) {
            Log::info("Total expected is zero.");
            return;
        }

        foreach ($planSubjects as $ps) {
            $subject = $ps->subject;
            if (!$subject || !$subject->subjectCategory) {
                Log::warning("Skipping PS ID {$ps->id}, missing subject/category info.");
                continue;
            }

            $subjectCategoryName = strtolower($subject->subjectCategory->subject_category_name);
            $planSubjectId = $ps->id;
            $subjectIdForLog = $subject->id;
            $nextSectionNumberForThisPS = 1;

            Log::info("Processing Subject ID {$subjectIdForLog} (Category: {$subjectCategoryName}), PlanSubject ID: {$planSubjectId}");

            // 1. إنشاء الجزء النظري (إذا كانت المادة نظرية أو مشتركة وتحتوي على ساعات نظرية)
            if (($subject->theoretical_hours ?? 0) > 0 &&
                (Str::contains($subjectCategoryName, ['theory', 'نظري']) || Str::contains($subjectCategoryName, ['combined', 'مشترك', 'نظري وعملي']))
            ) {
                Section::create([
                    'plan_subject_id' => $planSubjectId,
                    'academic_year' => $expectedCount->academic_year,
                    'semester' => $expectedCount->plan_semester,
                    'activity_type' => 'Theory',
                    'section_number' => $nextSectionNumberForThisPS++,
                    'student_count' => $totalExpected,
                    'section_gender' => 'Mixed',
                    'branch' => $expectedCount->branch,
                ]);
                Log::info("Created Theory Section #" . ($nextSectionNumberForThisPS - 1) . " for PS ID {$planSubjectId}");
            }

            // 2. إنشاء الجزء العملي (إذا كانت المادة عملية أو مشتركة وتحتوي على ساعات عملية)
            if (($subject->practical_hours ?? 0) > 0 &&
                (Str::contains($subjectCategoryName, ['practical', 'عملي']) || Str::contains($subjectCategoryName, ['combined', 'مشترك', 'نظري وعملي']))
            ) {

                // **لا نضع شرط continue هنا للمواد المشتركة**
                // الشرط الأصلي كان: if (($subjectCategoryName == 'theory' || Str::contains($subjectCategoryName, 'نظري')) && ! (Str::contains($subjectCategoryName, 'combined') || Str::contains($subjectCategoryName, 'مشترك'))) continue;
                // هذا الشرط كان يمنع إنشاء عملي إذا كانت الفئة "نظري" حتى لو كانت مشتركة (وهذا خطأ)

                $labCapacity = self::DEFAULT_LAB_CAPACITY;
                if ($totalExpected > 0 && $labCapacity > 0) {
                    $numLabSections = ceil($totalExpected / $labCapacity);
                    $baseStudentsPerSection = floor($totalExpected / $numLabSections);
                    $remainderStudents = $totalExpected % $numLabSections;

                    for ($i = 0; $i < $numLabSections; $i++) {
                        $studentsInThisSection = $baseStudentsPerSection + ($remainderStudents > 0 ? 1 : 0);
                        if ($remainderStudents > 0) $remainderStudents--;

                        if ($studentsInThisSection > 0) {
                            Section::create([
                                'plan_subject_id' => $planSubjectId,
                                'academic_year' => $expectedCount->academic_year,
                                'semester' => $expectedCount->plan_semester,
                                'activity_type' => 'Practical',
                                'section_number' => $nextSectionNumberForThisPS++, // سيأخذ الرقم التالي بعد النظري
                                'student_count' => $studentsInThisSection,
                                'section_gender' => 'Mixed',
                                'branch' => $expectedCount->branch,
                            ]);
                            Log::info("Created Practical Section #" . ($nextSectionNumberForThisPS - 1) . " for PS ID {$planSubjectId}");
                        }
                    }
                }
            }
            if ($nextSectionNumberForThisPS == 1) { // لم يتم إنشاء أي شعبة لهذه المادة
                Log::warning("No sections (Theory or Practical) were created for Subject ID {$subjectIdForLog} (PS ID {$planSubjectId}). Check hours and category.");
            }
        }
        Log::info("Finished generating sections for ExpectedCount ID: {$expectedCount->id}");
    }

    public function storeSectionInContext(Request $request, PlanExpectedCount $expectedCount)
    {
        $errorBagName = 'storeSectionModal';
        $validator = Validator::make($request->all(), [
            'plan_subject_id_from_modal' => 'required|integer|exists:plan_subjects,id',
            'activity_type_from_modal' => ['required', Rule::in(['Theory', 'Practical'])], // ** حقل جديد **
            'section_number' => 'required|integer|min:1',
            'student_count' => 'required|integer|min:0',
            'section_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
        ]);

        $validator->after(function ($validator) use ($request, $expectedCount) {
            if (!$validator->errors()->hasAny()) {
                $exists = Section::where('plan_subject_id', $request->input('plan_subject_id_from_modal'))
                    ->where('academic_year', $expectedCount->academic_year)
                    ->where('semester', $expectedCount->plan_semester)
                    ->where('activity_type', $request->input('activity_type_from_modal')) // ** إضافة للتحقق **
                    ->where('section_number', $request->input('section_number'))
                    ->where('branch', $expectedCount->branch)
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('section_unique', 'This section (number & activity type) already exists for this subject/context.');
                }
            }
        });
        // *** 3. التحقق من عدم تجاوز العدد الإجمالي المتوقع للطلاب ***
        $validator->after(function ($validator) use ($request, $expectedCount) {
            if (!$validator->errors()->has('student_count') && $request->filled('plan_subject_id_from_modal') && $request->filled('activity_type_from_modal')) {
                $newStudentCountForThisSection = (int) $request->input('student_count');
                $totalExpectedStudents = $expectedCount->male_count + $expectedCount->female_count;

                // جلب مجموع طلاب الشعب الموجودة حالياً لنفس المادة ونفس نوع النشاط ونفس السياق
                $currentSectionsStudentSum = Section::where('plan_subject_id', $request->input('plan_subject_id_from_modal'))
                    ->where('academic_year', $expectedCount->academic_year)
                    ->where('semester', $expectedCount->plan_semester)
                    ->where('activity_type', $request->input('activity_type_from_modal'))
                    ->where('branch', $expectedCount->branch)
                    // لا نحتاج لاستثناء ID هنا لأننا نضيف شعبة جديدة
                    ->sum('student_count');

                $newTotalAllocated = $currentSectionsStudentSum + $newStudentCountForThisSection;

                if ($newTotalAllocated > $totalExpectedStudents) {
                    $validator->errors()->add(
                        'student_count', // ربط الخطأ بحقل عدد الطلاب
                        "Adding this section will make the total allocated students ({$newTotalAllocated}) exceed the expected total ({$totalExpectedStudents}) for this subject/activity."
                    );
                }
            }
        });
        // *** نهاية التحقق من المجموع ***



        if ($validator->fails()) {
            return redirect()->route('data-entry.sections.manageContext', $expectedCount->id)
                ->withErrors($validator) // إرسال الأخطاء للـ error bag الافتراضي
                ->withInput();
            // $validator->errors()->add('section_unique', 'This section number already exists for this subject in this context (Year, Semester, Branch).');
        }

        try {
            Section::create([
                'plan_subject_id' => $request->plan_subject_id_from_modal,
                'academic_year' => $expectedCount->academic_year,
                'semester' => $expectedCount->plan_semester,
                'activity_type' => $request->activity_type_from_modal, // ** حفظ النوع **
                'section_number' => $request->section_number,
                'student_count' => $request->student_count,
                'section_gender' => $request->section_gender,
                'branch' => $expectedCount->branch,
            ]);
            return redirect()->route('data-entry.sections.manageContext', $expectedCount->id)
                ->with('success', 'Section added successfully.');
        } catch (Exception $e) {
            Log::error('Section (in context) Creation Failed: ' . $e->getMessage());
            // *** إرسال رسالة خطأ عامة للصفحة الرئيسية ***
            return redirect()->route('data-entry.sections.manageContext', $expectedCount->id)
                ->with('error', 'Failed to add section. An unexpected error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }


    /**
     * Update the specified section in storage within its context.
     */
    public function updateSectionInContext(Request $request, Section $section) // Route Model Binding للشعبة
    {
        // 1. جلب سياق العدد المتوقع لهذه الشعبة
        $expectedCountContext = PlanExpectedCount::where('plan_id', $section->planSubject->plan_id)
            ->where('academic_year', $section->academic_year)
            ->where('plan_level', $section->planSubject->plan_level)
            ->where('plan_semester', $section->planSubject->plan_semester) // فصل الخطة
            ->where('branch', $section->branch) // فرع الشعبة هو فرع السياق
            ->first();

        if (!$expectedCountContext) {
            Log::error("Context (ExpectedCount) not found for updating section ID: {$section->id}");
            // يمكنك إعادة توجيه مع خطأ عام، أو السماح بالتحديث بدون هذا التحقق إذا لم يكن العدد المتوقع إلزامياً
            return redirect()->back()->with('error', 'Could not determine the context for validation. Update aborted.');
        }

        $errorBagName = 'updateSectionModal_' . $section->id;

        // 2. Validation الأساسي للحقول
        $validator = Validator::make($request->all(), [
            'section_number' => 'required|integer|min:1',
            'student_count' => 'required|integer|min:0', // ** سنضيف التحقق من المجموع لاحقاً **
            'section_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
            // الفرع لا يتغير عادة من هنا
        ]);

        // 3. التحقق من تفرد رقم الشعبة (إذا تغير)
        $validator->after(function ($validator) use ($request, $section) {
            if (!$validator->errors()->has('section_number') && $request->input('section_number') != $section->section_number) {
                $exists = Section::where('plan_subject_id', $section->plan_subject_id)
                    ->where('academic_year', $section->academic_year)
                    ->where('semester', $section->semester)
                    ->where('activity_type', $section->activity_type)
                    ->where('section_number', $request->input('section_number'))
                    ->where('branch', $section->branch)
                    ->where('id', '!=', $section->id)
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('section_unique', 'This section number already exists for this subject in this context.');
                }
            }
        });

        // *** 4. التحقق من عدم تجاوز العدد الإجمالي المتوقع للطلاب ***
        $validator->after(function ($validator) use ($request, $section, $expectedCountContext) {
            if (!$validator->errors()->has('student_count')) { // فقط إذا كان عدد الطلاب المدخل صحيحاً
                $newStudentCountForThisSection = (int) $request->input('student_count');
                $totalExpectedStudents = $expectedCountContext->male_count + $expectedCountContext->female_count;

                // جلب مجموع طلاب الشعب الأخرى لنفس المادة ونفس نوع النشاط ونفس السياق
                $otherSectionsStudentSum = Section::where('plan_subject_id', $section->plan_subject_id)
                    ->where('academic_year', $section->academic_year)
                    ->where('semester', $section->semester)
                    ->where('activity_type', $section->activity_type)
                    ->where('branch', $section->branch)
                    ->where('id', '!=', $section->id) // استثناء الشعبة الحالية
                    ->sum('student_count');

                $newTotalAllocated = $otherSectionsStudentSum + $newStudentCountForThisSection;

                if ($newTotalAllocated > $totalExpectedStudents) {
                    $validator->errors()->add(
                        'student_count', // ربط الخطأ بحقل عدد الطلاب
                        "The total number of students allocated to sections ({$newTotalAllocated}) cannot exceed the expected total ({$totalExpectedStudents}) for this subject/activity."
                    );
                }
            }
        });
        // *** نهاية التحقق من المجموع ***

        if ($validator->fails()) {
            return redirect()->route('data-entry.sections.manageContext', $expectedCountContext->id)
                ->withErrors($validator) // إرسال الأخطاء للـ error bag الافتراضي
                ->withInput();
        }

        // 5. Prepare Data (فقط الحقول المسموح بتعديلها)
        $dataToUpdate = $validator->safe()->only(['section_number', 'student_count', 'section_gender']);
        // الفرع لا يتم تعديله من هنا عادةً لأنه جزء من السياق

        // 6. Update Database
        try {
            $section->update($dataToUpdate);
            return redirect()->route('data-entry.sections.manageContext', $expectedCountContext->id)
                ->with('success', 'Section updated successfully.');
        } catch (Exception $e) {
            Log::error('Section Update (in context) Failed: ' . $e->getMessage());
            return redirect()->route('data-entry.sections.manageContext', $expectedCountContext->id)
                ->with('error', 'Failed to update section: ' . $e->getMessage())
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
            $section->delete();

            if ($expectedCountContext) {
                return redirect()->route('data-entry.sections.manageContext', $expectedCountContext->id)
                    ->with('success', 'Section deleted successfully.');
            }
            // إذا لم يتم العثور على سياق العدد المتوقع، ارجع لصفحة الشعب العامة
            return redirect()->route('data-entry.sections.index')
                ->with('success', 'Section deleted, but could not redirect to specific context.');
        } catch (Exception $e) {
            Log::error('Section Deletion (in context) Failed: ' . $e->getMessage());
            $redirectRoute = $expectedCountContext ? route('data-entry.sections.manageContext', $expectedCountContext->id) : route('data-entry.sections.index');
            return redirect($redirectRoute)->with('error', 'Failed to delete section: ' . $e->getMessage());
        }
    }
}
