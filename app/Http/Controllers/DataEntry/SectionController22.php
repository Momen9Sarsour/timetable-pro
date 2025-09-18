<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Plan;
use App\Models\Department;
use App\Models\PlanSubject;
use App\Models\PlanExpectedCount;
use App\Models\Subject;
use App\Models\PlanGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Str;

class SectionController22 extends Controller
{
    // القيم الافتراضية إذا لم يحددها المستخدم للمادة
    const DEFAULT_THEORY_CAPACITY_FALLBACK = 50;
    const DEFAULT_PRACTICAL_CAPACITY_FALLBACK = 25;
    const MIN_STUDENTS_FOR_NEW_SECTION = 10;

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

        // حذف الشعب القديمة
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

            // تحديد سعة الشعب النظرية
            $capacityTheoreticalToUse = self::DEFAULT_THEORY_CAPACITY_FALLBACK;
            if (isset($subject->load_theoretical_section) && $subject->load_theoretical_section > 0) {
                $capacityTheoreticalToUse = $subject->load_theoretical_section;
            }
            Log::info("Subject ID {$subject->id}: Theoretical Capacity to Use = {$capacityTheoreticalToUse}");

            // تحديد سعة الشعب العملية
            $capacityPracticalToUse = self::DEFAULT_PRACTICAL_CAPACITY_FALLBACK;
            if (isset($subject->load_practical_section) && $subject->load_practical_section > 0) {
                $capacityPracticalToUse = $subject->load_practical_section;
            }
            Log::info("Subject ID {$subject->id}: Practical Capacity to Use = {$capacityPracticalToUse}");

            // 1. إنشاء الجزء النظري (إذا كانت المادة نظرية أو مشتركة وتحتوي على ساعات نظرية)
            if (($subject->theoretical_hours ?? 0) > 0 &&
                (Str::contains($subjectCategoryName, ['theory', 'نظري']) || Str::contains($subjectCategoryName, ['combined', 'مشترك', 'نظري وعملي']))
            ) {
                $numTheorySections = ceil($totalExpected / $capacityTheoreticalToUse);
                $baseStudentsPerSection = floor($totalExpected / $numTheorySections);
                $remainderStudents = $totalExpected % $numTheorySections;

                for ($i = 0; $i < $numTheorySections; $i++) {
                    $studentsInThisSection = $baseStudentsPerSection + ($remainderStudents > 0 ? 1 : 0);
                    if ($remainderStudents > 0) $remainderStudents--;

                    Section::create([
                        'plan_subject_id' => $planSubjectId,
                        'academic_year' => $expectedCount->academic_year,
                        'semester' => $expectedCount->plan_semester,
                        'activity_type' => 'Theory',
                        'section_number' => $nextSectionNumberForThisPS++,
                        'student_count' => $studentsInThisSection,
                        'section_gender' => 'Mixed',
                        'branch' => $expectedCount->branch,
                    ]);
                    Log::info("Created Theory Section #" . ($nextSectionNumberForThisPS - 1) .
                        " with {$studentsInThisSection} students for PS ID {$planSubjectId}");
                }
            }

            // 2. إنشاء الجزء العملي (إذا كانت المادة عملية أو مشتركة وتحتوي على ساعات عملية)
            if (($subject->practical_hours ?? 0) > 0 &&
                (Str::contains($subjectCategoryName, ['practical', 'عملي']) || Str::contains($subjectCategoryName, ['combined', 'مشترك', 'نظري وعملي']))
            ) {
                if ($totalExpected > 0 && $capacityPracticalToUse > 0) {
                    $numLabSections = ceil($totalExpected / $capacityPracticalToUse);
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
                                'section_number' => $nextSectionNumberForThisPS++,
                                'student_count' => $studentsInThisSection,
                                'section_gender' => 'Mixed',
                                'branch' => $expectedCount->branch,
                            ]);
                            Log::info("Created Practical Section #" . ($nextSectionNumberForThisPS - 1) .
                                " with {$studentsInThisSection} students for PS ID {$planSubjectId}");
                        }
                    }
                }
            }

            if ($nextSectionNumberForThisPS == 1) {
                Log::warning("No sections (Theory or Practical) were created for Subject ID {$subjectIdForLog} (PS ID {$planSubjectId}). Check hours and category.");
            }
        }

        // إنشاء المجموعات بعد إنشاء الشعب
        $this->generatePlanGroups($expectedCount);

        Log::info("Finished generating sections for ExpectedCount ID: {$expectedCount->id}");
    }

    /**
     * دالة جديدة لإنشاء المجموعات بناءً على الشعب المُنشأة
     */
    private function generatePlanGroups(PlanExpectedCount $expectedCount)
    {
        try {
            Log::info("Generating plan groups for ExpectedCount ID: {$expectedCount->id}");

            // حذف المجموعات القديمة لهذا السياق
            PlanGroup::clearContextGroups(
                $expectedCount->plan_id,
                $expectedCount->plan_level,
                $expectedCount->academic_year,
                $expectedCount->plan_semester,
                $expectedCount->branch
            );

            // جلب كل الشعب في هذا السياق
            $sectionsInContext = Section::where('academic_year', $expectedCount->academic_year)
                ->where('semester', $expectedCount->plan_semester)
                ->where(function ($q) use ($expectedCount) {
                    is_null($expectedCount->branch) ? $q->whereNull('branch') : $q->where('branch', $expectedCount->branch);
                })
                ->whereHas('planSubject', function ($q) use ($expectedCount) {
                    $q->where('plan_id', $expectedCount->plan_id)
                      ->where('plan_level', $expectedCount->plan_level)
                      ->where('plan_semester', $expectedCount->plan_semester);
                })
                ->with(['planSubject.subject'])
                ->get();

            if ($sectionsInContext->isEmpty()) {
                Log::info("No sections found for context. No groups to create.");
                return;
            }

            // حساب عدد المجموعات (نفس المنطق من buildStudentGroupMap)
            $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')
                ->groupBy('plan_subject_id')->map->count()->max() ?? 0;
            $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

            Log::info("Calculated number of student groups: {$numberOfStudentGroups}");

            // إنشاء المجموعات
            $groupsToInsert = [];

            for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
                // ربط الشعب النظرية بكل المجموعات
                $theorySections = $sectionsInContext->where('activity_type', 'Theory');
                foreach ($theorySections as $theorySection) {
                    $groupsToInsert[] = [
                        'plan_id' => $expectedCount->plan_id,
                        'plan_level' => $expectedCount->plan_level,
                        'academic_year' => $expectedCount->academic_year,
                        'semester' => $expectedCount->plan_semester,
                        'branch' => $expectedCount->branch,
                        'section_id' => $theorySection->id,
                        'group_no' => $groupIndex,
                        'group_size' => $theorySection->student_count,
                        'gender' => $theorySection->section_gender ?? 'Mixed',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // ربط الشعب العملية - كل شعبة عملية بمجموعة واحدة
                $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')
                    ->groupBy('plan_subject_id');

                foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
                    $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
                    if ($sortedSections->has($groupIndex - 1)) {
                        $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
                        $groupsToInsert[] = [
                            'plan_id' => $expectedCount->plan_id,
                            'plan_level' => $expectedCount->plan_level,
                            'academic_year' => $expectedCount->academic_year,
                            'semester' => $expectedCount->plan_semester,
                            'branch' => $expectedCount->branch,
                            'section_id' => $practicalSectionForThisGroup->id,
                            'group_no' => $groupIndex,
                            'group_size' => $practicalSectionForThisGroup->student_count,
                            'gender' => $practicalSectionForThisGroup->section_gender ?? 'Mixed',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            // إدراج كل المجموعات دفعة واحدة
            if (!empty($groupsToInsert)) {
                PlanGroup::insert($groupsToInsert);
                Log::info("Created " . count($groupsToInsert) . " group assignments for ExpectedCount ID: {$expectedCount->id}");
            }

        } catch (Exception $e) {
            Log::error("Failed to generate plan groups for ExpectedCount ID: {$expectedCount->id}. Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Store the specified section in storage within its context.
     */
    public function storeSectionInContext(Request $request, PlanExpectedCount $expectedCount)
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

        // dd($request->input('plan_subject_id_from_modal'));

        $planSubjectId = $request->input('plan_subject_id_from_modal');
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
                    // dd($request->all());
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
            $newSection = Section::create($data);

            // تحديث المجموعات بعد إضافة شعبة جديدة
            $this->updatePlanGroupsForSection($newSection, $expectedCount);

            return redirect()->route('data-entry.sections.manageSubjectContext', $redirectParams)->with('success', 'Section added.');
        } catch (Exception $e) {
            Log::error('Section Store Failed: ' . $e->getMessage());
            return redirect()->route('data-entry.sections.manageSubjectContext', $redirectParams)->with('error', 'Failed to add section.')->withInput();
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

        $errorBagName = 'updateSectionModal_' . $section->id; // اسم مميز للـ error bag

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

            // تحديث المجموعات بعد تحديث الشعبة
            $this->updatePlanGroupsForSection($section, $expectedCountContext);

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
            // حذف المجموعات المرتبطة بالشعبة أولاً
            PlanGroup::clearSectionGroups($section->id);

            $section->delete();

            // إعادة توليد المجموعات لكامل السياق بعد حذف الشعبة
            if ($expectedCountContext) {
                $this->generatePlanGroups($expectedCountContext);
                return redirect()->route('data-entry.sections.manageContext', $expectedCountContext->id)
                    ->with('success', 'Section deleted successfully.');
            }
            // إذا لم يتم العثور على سياق العدد المتوقع ارجع لصفحة الشعب العامة
            return redirect()->route('data-entry.sections.index')
                ->with('success', 'Section deleted, but could not redirect to specific context.');
        } catch (Exception $e) {
            Log::error('Section Deletion (in context) Failed: ' . $e->getMessage());
            $redirectRoute = $expectedCountContext ? route('data-entry.sections.manageContext', $expectedCountContext->id) : route('data-entry.sections.index');
            return redirect($redirectRoute)->with('error', 'Failed to delete section: ' . $e->getMessage());
        }
    }

    /**
     * دالة مساعدة لتحديث المجموعات عند إضافة/تحديث شعبة واحدة
     */
    private function updatePlanGroupsForSection(Section $section, PlanExpectedCount $expectedCount)
    {
        try {
            Log::info("Updating plan groups after section change for Section ID: {$section->id}");

            // إعادة توليد كل المجموعات لهذا السياق
            $this->generatePlanGroups($expectedCount);

            Log::info("Plan groups updated successfully after section change");
        } catch (Exception $e) {
            Log::error("Failed to update plan groups for section {$section->id}: " . $e->getMessage());
            // لا نوقف العملية، لكن نسجل الخطأ
        }
    }


    // =============================================
    //             API Controller Methods
    // =============================================

    /**
     * عرض جميع الشعب لسياق محدد
     */
    public function APIGetSectionsForContext(PlanExpectedCount $expectedCount)
    {
        try {
            $sections = Section::where('academic_year', $expectedCount->academic_year)
                ->where('semester', $expectedCount->plan_semester)
                ->where('branch', $expectedCount->branch)
                ->with('planSubject.subject')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sections,
                'message' => 'Sections retrieved successfully'
            ]);
        } catch (Exception $e) {
            Log::error('API Get Sections Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve sections'
            ], 500);
        }
    }

    /**
     * عرض شعب لمادة محددة في سياق معين
     */
    public function APIGetSectionsForSubject(PlanExpectedCount $expectedCount, PlanSubject $planSubject)
    {
        try {
            $sections = Section::where('plan_subject_id', $planSubject->id)
                ->where('academic_year', $expectedCount->academic_year)
                ->where('semester', $expectedCount->plan_semester)
                ->where('branch', $expectedCount->branch)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'theory_sections' => $sections->where('activity_type', 'Theory'),
                    'practical_sections' => $sections->where('activity_type', 'Practical')
                ],
                'message' => 'Subject sections retrieved successfully'
            ]);
        } catch (Exception $e) {
            Log::error('API Get Subject Sections Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve subject sections'
            ], 500);
        }
    }

    /**
     * إنشاء شعبة جديدة
     */
    public function APIStoreSectionInContext(Request $request, PlanExpectedCount $expectedCount)
    {
        $validator = Validator::make($request->all(), [
            'plan_subject_id' => 'required|integer|exists:plan_subjects,id',
            'activity_type' => ['required', Rule::in(['Theory', 'Practical'])],
            'section_number' => 'required|integer|min:1',
            'student_count' => 'required|integer|min:0',
            'section_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
        ]);

        // التحقق من تفرد الشعبة
        $validator->after(function ($validator) use ($request, $expectedCount) {
            $exists = Section::where('plan_subject_id', $request->plan_subject_id)
                ->where('academic_year', $expectedCount->academic_year)
                ->where('semester', $expectedCount->plan_semester)
                ->where('activity_type', $request->activity_type)
                ->where('section_number', $request->section_number)
                ->where('branch', $expectedCount->branch)
                ->exists();

            if ($exists) {
                $validator->errors()->add('section_unique', 'This section already exists in this context.');
            }
        });

        // التحقق من عدم تجاوز العدد المتوقع
        $validator->after(function ($validator) use ($request, $expectedCount) {
            $planSubject = PlanSubject::find($request->plan_subject_id);
            if ($planSubject) {
                $planSubjectId = $request->input('plan_subject_id_from_modal');
                $academicYear = $request->input('academic_year');
                $semester = $request->input('semester');
                $branch = $request->input('branch');
                $activityType = $request->input('activity_type');

                $totalExpected = $expectedCount->male_count + $expectedCount->female_count;
                $currentTotal = Section::where('plan_subject_id', $request->plan_subject_id)
                    ->where('academic_year', $expectedCount->academic_year)
                    ->where('semester', $expectedCount->plan_semester)
                    ->where('activity_type', $request->activity_type)
                    ->where('branch', $expectedCount->branch)
                    ->sum('student_count');

                $newStudentCount = (int) $request->input('student_count');
                $totalExpected = $expectedCount->male_count + $expectedCount->female_count;
                $otherSectionsSum = Section::where('plan_subject_id', $planSubjectId)
                    ->where('academic_year', $academicYear)->where('semester', $semester)
                    ->where('activity_type', $activityType)->where('branch', $branch)
                    ->sum('student_count');

                if (($currentTotal + $request->student_count) > $totalExpected) {
                    $validator->errors()->add('student_count_total', "Total students (" . ($otherSectionsSum + $newStudentCount) . ") exceeds expected ({$totalExpected}). Max remaining: " . max(0, $totalExpected - $otherSectionsSum));
                    // $validator->errors()->add('student_count', 'Total students exceed expected count.');

                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $section = Section::create([
                'plan_subject_id' => $request->plan_subject_id,
                'academic_year' => $expectedCount->academic_year,
                'semester' => $expectedCount->plan_semester,
                'activity_type' => $request->activity_type,
                'section_number' => $request->section_number,
                'student_count' => $request->student_count,
                'section_gender' => $request->section_gender,
                'branch' => $expectedCount->branch,
            ]);

            // تحديث المجموعات
            $this->updatePlanGroupsForSection($section, $expectedCount);

            return response()->json([
                'success' => true,
                'data' => $section,
                'message' => 'Section created successfully'
            ], 201);
        } catch (Exception $e) {
            Log::error('API Section Creation Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create section'
            ], 500);
        }
    }

    /**
     * تحديث شعبة موجودة
     */
    public function APIUpdateSectionInContext(Request $request, Section $section)
    {
        $validator = Validator::make($request->all(), [
            'section_number' => 'required|integer|min:1',
            'student_count' => 'required|integer|min:0',
            'section_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
        ]);

        // التحقق من تفرد رقم الشعبة إذا تغير
        $validator->after(function ($validator) use ($request, $section) {
            if ($request->section_number != $section->section_number) {
                $exists = Section::where('plan_subject_id', $section->plan_subject_id)
                    ->where('academic_year', $section->academic_year)
                    ->where('semester', $section->semester)
                    ->where('activity_type', $section->activity_type)
                    ->where('section_number', $request->section_number)
                    ->where('branch', $section->branch)
                    ->where('id', '!=', $section->id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('section_unique', 'This section number already exists.');
                }
            }
        });

        // التحقق من عدم تجاوز العدد المتوقع
        $validator->after(function ($validator) use ($request, $section) {
            $expectedCount = PlanExpectedCount::where('plan_id', $section->planSubject->plan_id)
                ->where('academic_year', $section->academic_year)
                ->where('plan_level', $section->planSubject->plan_level)
                ->where('plan_semester', $section->planSubject->plan_semester)
                ->where('branch', $section->branch)
                ->first();

            if ($expectedCount) {
                $totalExpected = $expectedCount->male_count + $expectedCount->female_count;
                $currentTotal = Section::where('plan_subject_id', $section->plan_subject_id)
                    ->where('academic_year', $section->academic_year)
                    ->where('semester', $section->semester)
                    ->where('activity_type', $section->activity_type)
                    ->where('branch', $section->branch)
                    ->where('id', '!=', $section->id)
                    ->sum('student_count');

                if (($currentTotal + $request->student_count) > $totalExpected) {
                    $validator->errors()->add('student_count', 'Total students exceed expected count.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $section->update([
                'section_number' => $request->section_number,
                'student_count' => $request->student_count,
                'section_gender' => $request->section_gender,
            ]);

            // تحديث المجموعات
            $expectedCount = PlanExpectedCount::where('plan_id', $section->planSubject->plan_id)
                ->where('academic_year', $section->academic_year)
                ->where('plan_level', $section->planSubject->plan_level)
                ->where('plan_semester', $section->planSubject->plan_semester)
                ->where('branch', $section->branch)
                ->first();

            if ($expectedCount) {
                $this->updatePlanGroupsForSection($section, $expectedCount);
            }

            return response()->json([
                'success' => true,
                'data' => $section,
                'message' => 'Section updated successfully'
            ]);
        } catch (Exception $e) {
            Log::error('API Section Update Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update section'
            ], 500);
        }
    }

    /**
     * حذف شعبة
     */
    public function APIDestroySectionInContext(Section $section)
    {
        try {
            // حذف المجموعات المرتبطة أولاً
            PlanGroup::clearSectionGroups($section->id);

            // العثور على السياق لإعادة توليد المجموعات
            $expectedCount = PlanExpectedCount::where('plan_id', $section->planSubject->plan_id)
                ->where('academic_year', $section->academic_year)
                ->where('plan_level', $section->planSubject->plan_level)
                ->where('plan_semester', $section->planSubject->plan_semester)
                ->where('branch', $section->branch)
                ->first();

            $section->delete();

            // إعادة توليد المجموعات للسياق
            if ($expectedCount) {
                $this->generatePlanGroups($expectedCount);
            }

            return response()->json([
                'success' => true,
                'message' => 'Section deleted successfully'
            ]);
        } catch (Exception $e) {
            Log::error('API Section Deletion Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete section'
            ], 500);
        }
    }

    /**
     * إنشاء شعب تلقائياً
     */
    public function APIGenerateSectionsForContext(Request $request, PlanExpectedCount $expectedCount)
    {
        try {
            $this->generateSectionsLogic22($expectedCount);

            return response()->json([
                'success' => true,
                'message' => 'Sections generated successfully'
            ]);
        } catch (Exception $e) {
            Log::error('API Generate Sections Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate sections'
            ], 500);
        }
    }



    // =============================================
    //             API Controller Methods
    // =============================================

    /**
     * API: Display a listing of all sections with optional filtering.
     * (Used if you need a general endpoint to list all sections, can be paginated)
     */
    // public function apiIndex(Request $request)
    // {
    //     try {
    //         $query = Section::with([
    //             'planSubject.plan:id,plan_no,plan_name',
    //             'planSubject.subject:id,subject_no,subject_name',
    //             'planSubject.plan.department:id,department_name'
    //         ]);

    //         if ($request->filled('academic_year')) {
    //             $query->where('academic_year', $request->academic_year);
    //         }
    //         if ($request->filled('semester')) {
    //             $query->where('semester', $request->semester);
    //         }
    //         if ($request->filled('plan_subject_id')) {
    //             $query->where('plan_subject_id', $request->plan_subject_id);
    //         }
    //         if ($request->filled('activity_type')) {
    //             $query->where('activity_type', $request->activity_type);
    //         }
    //         if ($request->filled('branch')) {
    //             $query->where('branch', $request->branch == 'none' ? null : $request->branch);
    //         }

    //         // --- Pagination for API (example, you can enable it) ---
    //         $perPage = $request->query('per_page', 15); // Default 15 items per page
    //         $sections = $query->orderBy('academic_year', 'desc')
    //             ->orderBy('semester')
    //             ->orderBy('plan_subject_id')
    //             ->orderBy('activity_type')
    //             ->orderBy('section_number')
    //             ->paginate($perPage);

    //         return response()->json([
    //             'success' => true,
    //             'data' => $sections->items(),
    //             'pagination' => [
    //                 'total' => $sections->total(),
    //                 'per_page' => $sections->perPage(),
    //                 'current_page' => $sections->currentPage(),
    //                 'last_page' => $sections->lastPage(),
    //                 'from' => $sections->firstItem(),
    //                 'to' => $sections->lastItem(),
    //             ]
    //         ], 200);
    //     } catch (Exception $e) {
    //         Log::error('API Error fetching all sections: ' . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Server Error: Could not retrieve sections.'], 500);
    //     }
    // }

    // /**
    //  * API: Get sections for a specific subject context defined by PlanExpectedCount.
    //  */
    // public function apiManageSectionsForContext(PlanExpectedCount $expectedCount)
    // {
    //     try {
    //         // $expectedCount->load('plan:id,plan_no,plan_name', 'plan.department:id,department_name');
    //         $expectedCount->load('plan.department');
    //         $planSubjectsForContext = PlanSubject::with('subject:id,subject_no,subject_name,theoretical_hours,practical_hours,capacity_theoretical_section,capacity_practical_section', 'subject.subjectCategory:id,subject_category_name')
    //             ->where('plan_id', $expectedCount->plan_id)
    //             ->where('plan_level', $expectedCount->plan_level)
    //             ->where('plan_semester', $expectedCount->plan_semester)
    //             ->get();

    //         $currentSectionsBySubjectAndActivity = $this->getCurrentSectionsGrouped($planSubjectsForContext, $expectedCount);

    //         return response()->json([
    //             'success' => true,
    //             'data' => [
    //                 'expected_count_context' => $expectedCount,
    //                 'plan_subjects_in_context' => $planSubjectsForContext,
    //                 'current_sections_grouped' => $currentSectionsBySubjectAndActivity,
    //             ]
    //         ], 200);
    //     } catch (Exception $e) {
    //         Log::error("API Error loading manage sections for ExpectedCount ID {$expectedCount->id}: " . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Server Error: Could not load section management data.'], 500);
    //     }
    // }

    // /**
    //  * API: Trigger section generation for ALL subjects within a specific ExpectedCount context.
    //  */
    // public function apiGenerateSectionsForContextButton(PlanExpectedCount $expectedCount)
    // {
    //     try {
    //         $this->generateSectionsLogicForAllSubjectsInContext($expectedCount); // نفس دالة الويب

    //         $planSubjectsForContext = PlanSubject::where('plan_id', $expectedCount->plan_id)
    //             ->where('plan_level', $expectedCount->plan_level)
    //             ->where('plan_semester', $expectedCount->plan_semester)
    //             ->get();
    //         $newSectionsGrouped = $this->getCurrentSectionsGrouped($planSubjectsForContext, $expectedCount);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'All sections for context generated/updated successfully.',
    //             'data' => $newSectionsGrouped
    //         ], 200);
    //     } catch (Exception $e) {
    //         Log::error('API Manual Section Generation Failed for ExpectedCount ID ' . $expectedCount->id . ': ' . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Failed to generate sections: ' . $e->getMessage()], 500);
    //     }
    // }

    // /**
    //  * API: Store a newly created section within a specific ExpectedCount context.
    //  */
    // public function apiStoreSectionInContext(Request $request, PlanExpectedCount $expectedCount)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'plan_subject_id' => 'required|integer|exists:plan_subjects,id',
    //         'activity_type' => ['required', Rule::in(['Theory', 'Practical'])],
    //         'section_number' => 'required|integer|min:1',
    //         'student_count' => 'required|integer|min:0',
    //         'section_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
    //     ]);

    //     // التحقق من التفرد
    //     $validator->after(function ($validator) use ($request, $expectedCount) {
    //         if (!$validator->errors()->hasAny()) {
    //             $exists = Section::where('plan_subject_id', $request->input('plan_subject_id'))
    //                 ->where('academic_year', $expectedCount->academic_year)
    //                 ->where('semester', $expectedCount->plan_semester)
    //                 ->where('activity_type', $request->input('activity_type'))
    //                 ->where('section_number', $request->input('section_number'))
    //                 ->where('branch', $expectedCount->branch)
    //                 ->exists();
    //             if ($exists) {
    //                 $validator->errors()->add('section_unique', 'This section (number & activity type) already exists for this subject/context.');
    //             }
    //         }
    //     });

    //     // التحقق من تجاوز العدد الكلي
    //     if ($expectedCount) {
    //         $validator->after(function ($validator) use ($request, $expectedCount) {
    //             if (!$validator->errors()->has('student_count') && $request->filled('plan_subject_id')) {
    //                 $newStudentCount = (int) $request->input('student_count');
    //                 $totalExpected = $expectedCount->male_count + $expectedCount->female_count;
    //                 $otherSectionsSum = Section::where('plan_subject_id', $request->input('plan_subject_id'))
    //                     ->where('academic_year', $expectedCount->academic_year)
    //                     ->where('semester', $expectedCount->plan_semester)
    //                     ->where('activity_type', $request->input('activity_type'))
    //                     ->where('branch', $expectedCount->branch)
    //                     ->sum('student_count');
    //                 if (($otherSectionsSum + $newStudentCount) > $totalExpected) {
    //                     $validator->errors()->add('student_count_total', "Total allocated students (" . ($otherSectionsSum + $newStudentCount) . ") would exceed expected ({$totalExpected}). Max remaining for new section: " . max(0, $totalExpected - $otherSectionsSum));
    //                 }
    //             }
    //         });
    //     }

    //     if ($validator->fails()) {
    //         return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
    //     }

    //     try {
    //         $section = Section::create([
    //             'plan_subject_id' => $request->input('plan_subject_id'),
    //             'academic_year' => $expectedCount->academic_year,
    //             'semester' => $expectedCount->plan_semester,
    //             'activity_type' => $request->input('activity_type'),
    //             'section_number' => $request->input('section_number'),
    //             'student_count' => $request->input('student_count'),
    //             'section_gender' => $request->input('section_gender'),
    //             'branch' => $expectedCount->branch,
    //         ]);
    //         $section->load('planSubject.subject:id,subject_no,subject_name');
    //         return response()->json(['success' => true, 'data' => $section, 'message' => 'Section added to context successfully.'], 201);
    //     } catch (Exception $e) {
    //         Log::error('API Section (in context) Store Failed: ' . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Failed to add section.'], 500);
    //     }
    // }

    // /**
    //  * API: Display the specified section details.
    //  */
    // public function apiShowSectionDetails(Section $section) // Route Model Binding
    // {
    //     try {
    //         $section->load(['planSubject.plan:id,plan_no,plan_name', 'planSubject.subject:id,subject_no,subject_name']);
    //         return response()->json(['success' => true, 'data' => $section], 200);
    //     } catch (Exception $e) {
    //         Log::error("API Error fetching section ID {$section->id}: " . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Section not found or server error.'], 404);
    //     }
    // }

    // /**
    //  * API: Update the specified section details.
    //  */
    // public function apiUpdateSectionDetails(Request $request, Section $section) // Route Model Binding
    // {
    //     $validator = Validator::make($request->all(), [
    //         'section_number' => 'sometimes|required|integer|min:1',
    //         'student_count' => 'sometimes|required|integer|min:0',
    //         'section_gender' => ['sometimes', 'required', Rule::in(['Male', 'Female', 'Mixed'])],
    //         // السياق لا يتم تعديله
    //     ]);

    //     // التحقق من التفرد إذا تم تعديل section_number
    //     $validator->after(function ($validator) use ($request, $section) {
    //         if ($request->has('section_number') && $request->input('section_number') != $section->section_number) {
    //             $exists = Section::where('plan_subject_id', $section->plan_subject_id)->where('academic_year', $section->academic_year)
    //                 ->where('semester', $section->semester)->where('activity_type', $section->activity_type)
    //                 ->where('section_number', $request->input('section_number'))->where('branch', $section->branch)
    //                 ->where('id', '!=', $section->id)->exists();
    //             if ($exists) {
    //                 $validator->errors()->add('section_unique', 'This section number already exists for this context.');
    //             }
    //         }
    //     });

    //     // التحقق من تجاوز العدد الكلي للطلاب إذا تم تعديل student_count
    //     $validator->after(function ($validator) use ($request, $section) {
    //         if ($request->has('student_count')) { // لا نتحقق من التغيير، دائماً نتحقق إذا أرسل
    //             $expectedCount = PlanExpectedCount::where('plan_id', $section->planSubject->plan_id)
    //                 ->where('academic_year', $section->academic_year)->where('plan_level', $section->planSubject->plan_level)
    //                 ->where('plan_semester', $section->planSubject->plan_semester)->where('branch', $section->branch)->first();
    //             if ($expectedCount) {
    //                 $newStudentCount = (int) $request->input('student_count');
    //                 $totalExpected = $expectedCount->male_count + $expectedCount->female_count;
    //                 $otherSectionsSum = Section::where('plan_subject_id', $section->plan_subject_id)
    //                     ->where('academic_year', $section->academic_year)->where('semester', $section->semester)
    //                     ->where('activity_type', $section->activity_type)->where('branch', $section->branch)
    //                     ->where('id', '!=', $section->id)->sum('student_count');
    //                 if (($otherSectionsSum + $newStudentCount) > $totalExpected) {
    //                     $validator->errors()->add('student_count_total', "Total allocated students (" . ($otherSectionsSum + $newStudentCount) . ") would exceed expected ({$totalExpected}). Max allowed for this section: " . max(0, $totalExpected - $otherSectionsSum + $section->student_count));
    //                 }
    //             }
    //         }
    //     });


    //     if ($validator->fails()) {
    //         return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
    //     }

    //     try {
    //         $dataToUpdate = $validator->safe()->only(['section_number', 'student_count', 'section_gender']);
    //         $section->update($dataToUpdate);
    //         $section->load(['planSubject.subject', 'planSubject.plan']);
    //         return response()->json(['success' => true, 'data' => $section, 'message' => 'Section updated successfully.'], 200);
    //     } catch (Exception $e) {
    //         Log::error('API Section Update Failed for ID ' . $section->id . ': ' . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Failed to update section.'], 500);
    //     }
    // }

    // /**
    //  * API: Remove the specified section.
    //  */
    // public function apiDestroySectionDetails(Section $section) // Route Model Binding
    // {
    //     // يمكنك إضافة تحقق هنا إذا كانت الشعبة مستخدمة في generated_schedules
    //     if ($section->scheduleEntries()->exists()) {
    //         return response()->json(['success' => false, 'message' => 'Cannot delete section. It is used in schedules.'], 409);
    //     }
    //     try {
    //         $section->delete();
    //         return response()->json(['success' => true, 'message' => 'Section deleted successfully.'], 200);
    //     } catch (Exception $e) {
    //         Log::error('API Section Destroy Failed for ID ' . $section->id . ': ' . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Failed to delete section.'], 500);
    //     }
    // }
}
