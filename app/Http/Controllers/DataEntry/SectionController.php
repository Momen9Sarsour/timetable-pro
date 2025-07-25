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

class SectionController extends Controller
{
    const DEFAULT_THEORY_CAPACITY_FALLBACK = 50;
    const DEFAULT_PRACTICAL_CAPACITY_FALLBACK = 25;
    const MIN_STUDENTS_FOR_NEW_SECTION = 10;

    /**
     * Display a listing of all sections with filtering.
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

            return view('dashboard.data-entry.sections.index', compact(
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
    public function manageSubjectContext(Request $request)
    {
        $request->validate([
            'plan_subject_id' => 'required|integer|exists:plan_subjects,id',
            'academic_year' => 'required|integer|digits:4',
            'semester_of_sections' => 'required|integer|in:1,2,3',
            'branch' => 'nullable|string|max:100',
        ]);

        $planSubjectId = $request->plan_subject_id;
        $academicYear = $request->academic_year;
        $semesterOfSections = $request->semester_of_sections;
        $branch = $request->filled('branch') ? $request->branch : null;

        try {
            $planSubject = PlanSubject::with(['plan.department', 'subject.subjectCategory'])->findOrFail($planSubjectId);

            $expectedCount = PlanExpectedCount::where('plan_id', $planSubject->plan_id)
                ->where('plan_level', $planSubject->plan_level)
                ->where('plan_semester', $planSubject->plan_semester)
                ->where('academic_year', $academicYear)
                ->where(fn($q) => is_null($branch) ? $q->whereNull('branch') : $q->where('branch', $branch))
                ->first();

            $currentSections = Section::where('plan_subject_id', $planSubjectId)
                ->where('academic_year', $academicYear)
                ->where('semester', $semesterOfSections)
                ->where(fn($q) => is_null($branch) ? $q->whereNull('branch') : $q->where('branch', $branch))
                ->orderBy('activity_type')->orderBy('section_number')->get();

            return view('dashboard.data-entry.sections.manage', compact(
                'planSubject',
                'academicYear',
                'semesterOfSections',
                'branch',
                'expectedCount',
                'currentSections',
                'request'
            ));
        } catch (Exception $e) {
            Log::error('Error loading manage sections for subject context: ' . $e->getMessage());
            return redirect()->route('data-entry.sections.index')->with('error', 'Could not load section management page.');
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
                'semester_of_sections' => $expectedCount->plan_semester,
                'branch' => $expectedCount->branch,
            ])->with('success', 'Sections for subject generated/updated successfully.');
        } catch (Exception $e) {
            Log::error('Manual Section Generation for Subject Failed: ' . $e->getMessage());
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
            $errorMessage = "Subject or its category is missing for PlanSubject ID: {$planSubject->id}. Cannot generate sections.";
            Log::error($errorMessage);
            throw new Exception($errorMessage);
        }

        $totalExpected = $expectedCount->male_count + $expectedCount->female_count;

        Section::where('plan_subject_id', $planSubject->id)
            ->where('academic_year', $expectedCount->academic_year)
            ->where('semester', $expectedCount->plan_semester)
            ->where(function ($q) use ($expectedCount) {
                is_null($expectedCount->branch) ? $q->whereNull('branch') : $q->where('branch', $expectedCount->branch);
            })
            ->delete();
        Log::info("Deleted old sections for PS ID {$planSubject->id} in context of ExpectedCount ID: {$expectedCount->id}.");

        if ($totalExpected <= 0) {
            Log::info("Total expected students is zero for PS ID {$planSubject->id}. No new sections will be created.");
            return;
        }

        $subjectCategoryName = strtolower($subject->subjectCategory->subject_category_name);
        $nextSectionNumber = 1;

        // --- تحديد السعات المستخدمة ---
        $capacityTheoreticalToUse = self::DEFAULT_THEORY_CAPACITY_FALLBACK;
        if (isset($subject->load_theoretical_section) && $subject->load_theoretical_section > 0) {
            $capacityTheoreticalToUse = $subject->load_theoretical_section;
        }
        // dd(isset($subject->load_theoretical_section));
        Log::info("Subject ID {$subject->id}: Theoretical Capacity to Use = {$capacityTheoreticalToUse} (Subject defined: {$subject->capacity_theoretical_section})");

        $capacityPracticalToUse = self::DEFAULT_PRACTICAL_CAPACITY_FALLBACK;
        if (isset($subject->load_practical_section) && $subject->load_practical_section > 0) {
            $capacityPracticalToUse = $subject->load_practical_section;
        }
        Log::info("Subject ID {$subject->id}: Practical Capacity to Use = {$capacityPracticalToUse} (Subject defined: {$subject->capacity_practical_section})");
        // --- نهاية تحديد السعات ---

        // --- 1. الجزء النظري ---
        if (($subject->theoretical_hours ?? 0) > 0) {
            // لا ننشئ جزء نظري إذا كانت المادة "عملية فقط"
            if (
                !($subjectCategoryName == 'practical' || Str::contains($subjectCategoryName, 'عملي')) ||
                (Str::contains($subjectCategoryName, ['combined', 'مشترك', 'نظري وعملي']))
            ) {

                if ($totalExpected > 0 && $capacityTheoreticalToUse > 0) {
                    $numSections = ceil($totalExpected / $capacityTheoreticalToUse);
                    if ($numSections > 1) {
                        $studentsInLastSection = $totalExpected % $capacityTheoreticalToUse;
                        if ($studentsInLastSection == 0) $studentsInLastSection = $capacityTheoreticalToUse;
                        if ($studentsInLastSection < self::MIN_STUDENTS_FOR_NEW_SECTION && $numSections > 1) {
                            $numSections = floor($totalExpected / $capacityTheoreticalToUse);
                            if ($numSections == 0 && $totalExpected > 0) $numSections = 1;
                        }
                    }
                    if ($numSections == 0 && $totalExpected > 0) $numSections = 1;

                    if ($numSections > 0) {
                        $baseStudents = floor($totalExpected / $numSections);
                        $remainder = $totalExpected % $numSections;
                        for ($i = 0; $i < $numSections; $i++) {
                            $count = $baseStudents + ($remainder > 0 ? 1 : 0);
                            if ($remainder > 0) $remainder--;
                            if ($count > 0) {
                                Section::create(['plan_subject_id' => $planSubject->id, 'academic_year' => $expectedCount->academic_year, 'semester' => $expectedCount->plan_semester, 'activity_type' => 'Theory', 'section_number' => $nextSectionNumber++, 'student_count' => $count, 'section_gender' => 'Mixed', 'branch' => $expectedCount->branch]);
                            }
                        }
                        Log::info("Created {$numSections} Theory Sections for PS ID {$planSubject->id}");
                    }
                }
            }
        }

        // --- 2. الجزء العملي ---
        if (($subject->practical_hours ?? 0) > 0) {
            // لا ننشئ جزء عملي إذا كانت المادة "نظرية فقط" وليست "مشتركة"
            if (
                !($subjectCategoryName == 'theory' || Str::contains($subjectCategoryName, 'نظري')) ||
                (Str::contains($subjectCategoryName, ['combined', 'مشترك', 'نظري وعملي']))
            ) {

                if ($totalExpected > 0 && $capacityPracticalToUse > 0) {
                    $numSections = ceil($totalExpected / $capacityPracticalToUse);
                    if ($numSections > 1) {
                        $studentsInLastSection = $totalExpected % $capacityPracticalToUse;
                        if ($studentsInLastSection == 0) $studentsInLastSection = $capacityPracticalToUse;
                        if ($studentsInLastSection < self::MIN_STUDENTS_FOR_NEW_SECTION && $numSections > 1) {
                            $numSections = floor($totalExpected / $capacityPracticalToUse);
                            if ($numSections == 0 && $totalExpected > 0) $numSections = 1;
                        }
                    }
                    if ($numSections == 0 && $totalExpected > 0) $numSections = 1;

                    if ($numSections > 0) {
                        $baseStudents = floor($totalExpected / $numSections);
                        $remainder = $totalExpected % $numSections;
                        for ($i = 0; $i < $numSections; $i++) {
                            $count = $baseStudents + ($remainder > 0 ? 1 : 0);
                            if ($remainder > 0) $remainder--;
                            if ($count > 0) {
                                Section::create(['plan_subject_id' => $planSubject->id, 'academic_year' => $expectedCount->academic_year, 'semester' => $expectedCount->plan_semester, 'activity_type' => 'Practical', 'section_number' => $nextSectionNumber++, 'student_count' => $count, 'section_gender' => 'Mixed', 'branch' => $expectedCount->branch]);
                            }
                        }
                        Log::info("Created {$numSections} Practical Sections for PS ID {$planSubject->id}");
                    }
                }
            }
        }
        if ($nextSectionNumber == 1) {
            Log::warning("No sections created for PS ID {$planSubject->id}.");
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
                    ->where('academic_year', $academicYear)->where('semester', $semester)
                    ->where('activity_type', $activityType)
                    ->where('section_number', $request->input('section_number'))
                    ->where(fn($q) => is_null($branch) ? $q->whereNull('branch') : $q->where('branch', $branch))
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('section_unique', 'This section (number & activity type) already exists.');
                }
            }
        });

        // التحقق من تجاوز العدد الإجمالي للطلاب
        $planSubjectForContext = PlanSubject::find($planSubjectId);
        if ($planSubjectForContext) {
            $expectedCount = PlanExpectedCount::where('plan_id', $planSubjectForContext->plan_id)
                ->where('academic_year', $academicYear)
                ->where('plan_level', $planSubjectForContext->plan_level)
                ->where('plan_semester', $planSubjectForContext->plan_semester)
                ->where(fn($q) => is_null($branch) ? $q->whereNull('branch') : $q->where('branch', $branch))
                ->first();

            if ($expectedCount) {
                $validator->after(function ($validator) use ($request, $expectedCount, $planSubjectId, $academicYear, $semester, $branch, $activityType) {
                    if (!$validator->errors()->has('student_count')) {
                        $newStudentCount = (int) $request->input('student_count');
                        $totalExpected = $expectedCount->male_count + $expectedCount->female_count;
                        $otherSectionsSum = Section::where('plan_subject_id', $planSubjectId)
                            ->where('academic_year', $academicYear)->where('semester', $semester)
                            ->where('activity_type', $activityType)->where('branch', $branch)
                            ->sum('student_count');
                        if (($otherSectionsSum + $newStudentCount) > $totalExpected) {
                            $validator->errors()->add('student_count_total', "Total students (" . ($otherSectionsSum + $newStudentCount) . ") exceeds expected ({$totalExpected}). Max remaining: " . max(0, $totalExpected - $otherSectionsSum));
                        }
                    }
                });
            }
        }


        $redirectParams = ['plan_subject_id' => $planSubjectId, 'academic_year' => $academicYear, 'semester_of_sections' => $semester, 'branch' => $branch];
        if ($validator->fails()) {
            return redirect()->route('data-entry.sections.manageSubjectContext', $redirectParams)->with('error', 'Failed to create section.')
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
        $redirectParams = ['plan_subject_id' => $section->plan_subject_id, 'academic_year' => $section->academic_year, 'semester_of_sections' => $section->semester, 'branch' => $section->branch];
        $errorBagName = 'editSectionModal_' . $section->id; // اسم مميز للـ error bag
        $validator = Validator::make($request->all(), [
            'section_number' => 'required|integer|min:1',
            'student_count' => 'required|integer|min:0',
            'section_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
        ]);

        // التحقق من التفرد لرقم الشعبة إذا تغير
        $validator->after(function ($validator) use ($request, $section) {
            if (!$validator->errors()->has('section_number') && $request->input('section_number') != $section->section_number) {
                $exists = Section::where('plan_subject_id', $section->plan_subject_id)
                    ->where('academic_year', $section->academic_year)->where('semester', $section->semester)
                    ->where('activity_type', $section->activity_type)->where('section_number', $request->input('section_number'))
                    ->where('branch', $section->branch)->where('id', '!=', $section->id)->exists();
                if ($exists) {
                    $validator->errors()->add('section_unique', 'This section number already exists for this subject/context.');
                }
            }
        });

        // التحقق من عدم تجاوز العدد الكلي للطلاب
        $validator->after(function ($validator) use ($request, $section) {
            if (!$validator->errors()->has('student_count')) {
                $expectedCount = PlanExpectedCount::where('plan_id', $section->planSubject->plan_id)
                    ->where('academic_year', $section->academic_year)->where('plan_level', $section->planSubject->plan_level)
                    ->where('plan_semester', $section->planSubject->plan_semester)->where('branch', $section->branch)->first();
                if ($expectedCount) {
                    $newStudentCount = (int) $request->input('student_count');
                    $totalExpected = $expectedCount->male_count + $expectedCount->female_count;
                    $otherSectionsSum = Section::where('plan_subject_id', $section->plan_subject_id)
                        ->where('academic_year', $section->academic_year)->where('semester', $section->semester)
                        ->where('activity_type', $section->activity_type)->where('branch', $section->branch)
                        ->where('id', '!=', $section->id)->sum('student_count');
                    if (($otherSectionsSum + $newStudentCount) > $totalExpected) {
                        $validator->errors()->add('student_count_total', "Total students (" . ($otherSectionsSum + $newStudentCount) . ") exceeds expected ({$totalExpected}).");
                    }
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->route('data-entry.sections.manageSubjectContext', $redirectParams)
                ->with('error', 'Failed to update section.')
                ->with('editSectionId', $section->id) // لإظهار المودال الصحيح
                ->with('sectionForModal', $section) // لتعيين القيم الافتراضية
                ->withErrors($validator, $errorBagName)
                ->withInput();
        }

        try {
            $dataToUpdate = $validator->safe()->only(['section_number', 'student_count', 'section_gender']);
            // الفرع ونوع النشاط لا يتم تعديلهما عادةً من هنا
            $section->update($dataToUpdate);
            return redirect()->route('data-entry.sections.manageSubjectContext', $redirectParams)->with('success', 'Section updated.');
        } catch (Exception $e) {
            Log::error('Section Update Failed for ID ' . $section->id . ': ' . $e->getMessage());
            return redirect()->route('data-entry.sections.manageSubjectContext', $redirectParams)->with('error', 'Failed to update section: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified section.
     */
    public function destroy(Section $section)
    {
        $redirectParams = ['plan_subject_id' => $section->plan_subject_id, 'academic_year' => $section->academic_year, 'semester_of_sections' => $section->semester, 'branch' => $section->branch];
        try {
            $section->delete();
            return redirect()->route('data-entry.sections.manageSubjectContext', $redirectParams)->with('success', 'Section deleted.');
        } catch (Exception $e) {
            Log::error('Section Destroy Failed for ID ' . $section->id . ': ' . $e->getMessage());
            return redirect()->route('data-entry.sections.manageSubjectContext', $redirectParams)->with('error', 'Failed to delete section: ' . $e->getMessage());
        }
    }

    // =============================================
    //             API Controller Methods
    // =============================================

    /**
     * API: Display a listing of all sections with filtering.
     */
    public function apiIndex(Request $request)
    {
        try {
            $query = Section::with([
                'planSubject.plan:id,plan_no,plan_name',
                'planSubject.subject:id,subject_no,subject_name',
                'planSubject.plan.department:id,department_name'
            ]);

            if ($request->filled('academic_year')) {
                $query->where('academic_year', $request->academic_year);
            }
            if ($request->filled('semester')) {
                $query->where('semester', $request->semester);
            }
            if ($request->filled('plan_subject_id')) {
                $query->where('plan_subject_id', $request->plan_subject_id);
            }
            if ($request->filled('activity_type')) {
                $query->where('activity_type', $request->activity_type);
            }
            if ($request->filled('branch')) {
                if (strtolower($request->branch) === 'none' || $request->branch === '') {
                    $query->whereNull('branch');
                } else {
                    $query->where('branch', $request->branch);
                }
            }

            $sections = $query->orderBy('academic_year', 'desc')->orderBy('semester')
                ->orderBy('plan_subject_id')->orderBy('activity_type')->orderBy('section_number')
                ->get(); // *** جلب كل النتائج للـ API (بدون pagination حالياً) ***

            return response()->json(['success' => true, 'data' => $sections], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching sections: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error: Could not retrieve sections.'], 500);
        }
    }


    /**
     * API: Get sections for a specific subject context.
     */
    public function apiGetSectionsForSubjectContext(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_subject_id' => 'required|integer|exists:plan_subjects,id',
            'academic_year' => 'required|integer|digits:4',
            'semester' => 'required|integer|in:1,2,3', // فصل الشعبة
            'branch' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $planSubject = PlanSubject::with(['subject:id,subject_no,subject_name', 'plan:id,plan_no'])->find($request->plan_subject_id);
            if (!$planSubject) {
                return response()->json(['success' => false, 'message' => 'PlanSubject context not found.'], 404);
            }

            $sections = Section::where('plan_subject_id', $request->plan_subject_id)
                ->where('academic_year', $request->academic_year)
                ->where('semester', $request->semester)
                ->where(fn($q) => is_null($request->branch) ? $q->whereNull('branch') : $q->where('branch', $request->branch))
                ->orderBy('activity_type')->orderBy('section_number')->get();

            return response()->json([
                'success' => true,
                'context' => ['plan_subject' => $planSubject, /* ... */],
                'data' => $sections
            ], 200);
        } catch (Exception $e) { /* ... */
        }
    }


    /**
     * API: Store a newly created section.
     */
    public function apiStore(Request $request)
    {
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

        // التحقق من التفرد
        $validator->after(function ($validator) use ($request) {
            if (!$validator->errors()->hasAny()) {
                $exists = Section::where('plan_subject_id', $request->input('plan_subject_id'))
                    ->where('academic_year', $request->input('academic_year'))
                    ->where('semester', $request->input('semester'))
                    ->where('activity_type', $request->input('activity_type'))
                    ->where('section_number', $request->input('section_number'))
                    ->where(function ($query) use ($request) {
                        $branch = $request->input('branch');
                        is_null($branch) || $branch === '' ? $query->whereNull('branch') : $query->where('branch', $branch);
                    })->exists();
                if ($exists) {
                    $validator->errors()->add('section_unique', 'This section (number & activity type) already exists for this context.');
                }
            }
        });

        // التحقق من تجاوز العدد الكلي
        $planSubjectForContext = PlanSubject::find($request->input('plan_subject_id'));
        if ($planSubjectForContext) {
            $expectedCount = PlanExpectedCount::where('plan_id', $planSubjectForContext->plan_id)
                ->where('academic_year', $request->input('academic_year'))
                ->where('plan_level', $planSubjectForContext->plan_level)
                ->where('plan_semester', $planSubjectForContext->plan_semester)
                ->where(fn($q) => is_null($request->input('branch')) ? $q->whereNull('branch') : $q->where('branch', $request->input('branch')))
                ->first();

            if ($expectedCount) {
                $validator->after(function ($validator) use ($request, $expectedCount) {
                    if (!$validator->errors()->has('student_count')) {
                        $newStudentCount = (int) $request->input('student_count');
                        $totalExpected = $expectedCount->male_count + $expectedCount->female_count;
                        $otherSectionsSum = Section::where('plan_subject_id', $request->input('plan_subject_id'))
                            ->where('academic_year', $request->input('academic_year'))
                            ->where('semester', $request->input('semester'))
                            ->where('activity_type', $request->input('activity_type'))
                            ->where(fn($q) => is_null($request->input('branch')) ? $q->whereNull('branch') : $q->where('branch', $request->input('branch')))
                            ->sum('student_count');
                        if (($otherSectionsSum + $newStudentCount) > $totalExpected) {
                            $validator->errors()->add('student_count_total', "Total allocated students (" . ($otherSectionsSum + $newStudentCount) . ") would exceed expected ({$totalExpected}). Max remaining for new section: " . max(0, $totalExpected - $otherSectionsSum));
                        }
                    }
                });
            }
        }

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();
            $data['branch'] = empty($data['branch']) ? null : $data['branch'];
            $section = Section::create($data);
            $section->load(['planSubject.subject:id,subject_no,subject_name', 'planSubject.plan:id,plan_no']);
            return response()->json(['success' => true, 'data' => $section, 'message' => 'Section created successfully.'], 201);
        } catch (Exception $e) {
            Log::error('API Section Store Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create section.'], 500);
        }
    }

    /**
     * API: Display the specified section.
     */
    public function apiShow(Section $section)
    {
        try {
            $section->load(['planSubject.plan:id,plan_no,plan_name', 'planSubject.subject:id,subject_no,subject_name']);
            return response()->json(['success' => true, 'data' => $section], 200);
        } catch (Exception $e) {
            Log::error("API Error fetching section ID {$section->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Section not found or server error.'], 404);
        }
    }

    /**
     * API: Update the specified section.
     */
    public function apiUpdate(Request $request, Section $section)
    {
        $validator = Validator::make($request->all(), [
            'section_number' => 'sometimes|required|integer|min:1',
            'student_count' => 'sometimes|required|integer|min:0',
            'section_gender' => ['sometimes', 'required', Rule::in(['Male', 'Female', 'Mixed'])],
            // السياق (plan_subject_id, activity_type, academic_year, semester, branch) لا يتغير عادةً
        ]);

        // التحقق من التفرد إذا تم تعديل section_number
        $validator->after(function ($validator) use ($request, $section) {
            if ($request->has('section_number') && $request->input('section_number') != $section->section_number) {
                $exists = Section::where('plan_subject_id', $section->plan_subject_id)
                    ->where('academic_year', $section->academic_year)->where('semester', $section->semester)
                    ->where('activity_type', $section->activity_type)->where('section_number', $request->input('section_number'))
                    ->where('branch', $section->branch)->where('id', '!=', $section->id)->exists();
                if ($exists) {
                    $validator->errors()->add('section_unique', 'This section number already exists for this context.');
                }
            }
        });

        // التحقق من تجاوز العدد الكلي للطلاب إذا تم تعديل student_count
        $validator->after(function ($validator) use ($request, $section) {
            if ($request->has('student_count') && $request->input('student_count') != $section->student_count) {
                $expectedCount = PlanExpectedCount::where('plan_id', $section->planSubject->plan_id)
                    ->where('academic_year', $section->academic_year)->where('plan_level', $section->planSubject->plan_level)
                    ->where('plan_semester', $section->planSubject->plan_semester)->where('branch', $section->branch)->first();
                if ($expectedCount) {
                    $newStudentCount = (int) $request->input('student_count');
                    $totalExpected = $expectedCount->male_count + $expectedCount->female_count;
                    $otherSectionsSum = Section::where('plan_subject_id', $section->plan_subject_id)
                        ->where('academic_year', $section->academic_year)->where('semester', $section->semester)
                        ->where('activity_type', $section->activity_type)->where('branch', $section->branch)
                        ->where('id', '!=', $section->id)->sum('student_count');
                    if (($otherSectionsSum + $newStudentCount) > $totalExpected) {
                        $validator->errors()->add('student_count_total', "Total allocated students (" . ($otherSectionsSum + $newStudentCount) . ") would exceed expected ({$totalExpected}).");
                    }
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        try {
            $dataToUpdate = $validator->safe()->only(['section_number', 'student_count', 'section_gender']);
            $section->update($dataToUpdate);
            $section->load(['planSubject.subject', 'planSubject.plan']);
            return response()->json(['success' => true, 'data' => $section, 'message' => 'Section updated successfully.'], 200);
        } catch (Exception $e) {
            Log::error('API Section Update Failed for ID ' . $section->id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update section.'], 500);
        }
    }

    /**
     * API: Remove the specified section.
     */
    public function apiDestroy(Section $section)
    {
        try {
            $section->delete();
            return response()->json(['success' => true, 'message' => 'Section deleted successfully.'], 200);
        } catch (Exception $e) {
            Log::error('API Section Destroy Failed for ID ' . $section->id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete section.'], 500);
        }
    }

    /**
     * API: Trigger section generation for a specific SUBJECT and context.
     */
    public function apiGenerateForSubject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_subject_id' => 'required|integer|exists:plan_subjects,id',
            'expected_count_id' => 'required|integer|exists:plan_expected_counts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $planSubject = PlanSubject::find($request->plan_subject_id);
        $expectedCount = PlanExpectedCount::find($request->expected_count_id);

        if (!$planSubject || !$expectedCount) {
            return response()->json(['success' => false, 'message' => 'Invalid context for generating sections.'], 404);
        }

        try {
            $this->generateSectionsLogic($planSubject, $expectedCount);
            $newSections = Section::where('plan_subject_id', $planSubject->id)
                ->where('academic_year', $expectedCount->academic_year)
                ->where('semester', $expectedCount->plan_semester)
                ->where(fn($q) => is_null($expectedCount->branch) ? $q->whereNull('branch') : $q->where('branch', $expectedCount->branch))
                ->orderBy('activity_type')->orderBy('section_number')
                ->with(['planSubject.subject:id,subject_no,subject_name']) // تحميل تفاصيل المادة
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Sections generated/updated successfully for the subject.',
                'data' => $newSections
            ], 200);
        } catch (Exception $e) {
            Log::error('API Manual Section Generation for Subject Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to generate sections: ' . $e->getMessage()], 500);
        }
    }
}
