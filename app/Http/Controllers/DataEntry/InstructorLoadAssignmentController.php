<?php

namespace App\Http\Controllers\DataEntry;

use Exception;
use App\Models\Section;
use App\Models\Instructor;
use App\Models\PlanSubject;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\PlanExpectedCount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class InstructorLoadAssignmentController extends Controller
{
    //
    // --- ثوابت للتقسيم (يمكن نقلها لإعدادات لاحقاً) ---
    // const DEFAULT_THEORY_CAPACITY_FALLBACK = 50;
    // const DEFAULT_PRACTICAL_CAPACITY_FALLBACK = 25;
    // const DEFAULT_THEORY_SECTION_CAPACITY_FALLBACK = 50;
    // const DEFAULT_PRACTICAL_SECTION_CAPACITY_FALLBACK = 25;
    // const MIN_STUDENTS_FOR_NEW_SECTION = 10;

    // --- عدادات لعملية الـ Import ---
    private $sectionsCreatedByGenerate = 0;
    private $assignmentsCreated = 0;
    private $assignmentsUpdated = 0; // إذا سمحنا بتحديث تعيين موجود
    private $assignmentsSkipped = 0;
    private $skippedDetails = [];
    private $processedInstructorsInFile = []; // لتتبع معالجة المدرسين داخل الملف

    private function resetCounters()
    {
        $this->sectionsCreatedByGenerate = 0;
        $this->assignmentsCreated = 0;
        $this->assignmentsUpdated = 0;
        $this->assignmentsSkipped = 0;
        $this->skippedDetails = [];
        $this->processedInstructorsInFile = [];
    }
    // --- ثوابت للتقسيم ---
    const DEFAULT_THEORY_SECTION_CAPACITY_FALLBACK = 50;
    const DEFAULT_PRACTICAL_SECTION_CAPACITY_FALLBACK = 25;
    const MIN_STUDENTS_FOR_NEW_SECTION = 10;

    // --- عدادات لعملية توليد الشعب الناقصة ---
    // private $sectionsCreatedByGenerate = 0;
    private $subjectsProcessedForGeneration = 0;
    private $contextsProcessedForGeneration = 0;

    private function resetCountersForGenerate()
    {
        $this->sectionsCreatedByGenerate = 0;
        $this->subjectsProcessedForGeneration = 0;
        $this->contextsProcessedForGeneration = 0;
    }

    /**
     * Generate missing sections for all plan expected counts
     * where subjects are defined in the plan but no sections exist yet for that specific context.
     */
    public function generateMissingSections(Request $request) // أضفنا Request إذا أردت فلاتر لاحقاً
    {
        Log::info("User triggered 'Generate Missing Sections' process.");
        $this->resetCountersForGenerate();

        // يمكنك إضافة فلاتر هنا للسنة الأكاديمية أو الفصل إذا أردت
        // مثال: $academicYearFilter = $request->input('filter_year');
        // $semesterFilter = $request->input('filter_semester');

        $expectedCountsQuery = PlanExpectedCount::with('plan');

        // if ($academicYearFilter) {
        //     $expectedCountsQuery->where('academic_year', $academicYearFilter);
        // }
        // if ($semesterFilter) {
        //     // نفترض أن semester في expectedCount هو فصل الخطة
        //     $expectedCountsQuery->where('plan_semester', $semesterFilter);
        // }

        $expectedCounts = $expectedCountsQuery->get();

        if ($expectedCounts->isEmpty()) {
            return redirect()->route('data-entry.instructor-section.index') // افترض أن هذا هو اسم روت صفحة الأزرار
                ->with('info', 'No expected student counts found to process.');
        }

        DB::beginTransaction();
        try {
            foreach ($expectedCounts as $ec) {
                $this->contextsProcessedForGeneration++;
                Log::info("Processing ExpectedCount ID: {$ec->id} for Plan: {$ec->plan->plan_no}, Level: {$ec->plan_level}, PlanSem: {$ec->plan_semester}, Year: {$ec->academic_year}, Branch: {$ec->branch}");

                $planSubjects = PlanSubject::with('subject.subjectCategory')
                    ->where('plan_id', $ec->plan_id)
                    ->where('plan_level', $ec->plan_level)
                    ->where('plan_semester', $ec->plan_semester) // مواد هذا الفصل من الخطة
                    ->get();

                if ($planSubjects->isEmpty()) {
                    Log::info("No subjects in plan for EC ID {$ec->id}.");
                    continue;
                }

                foreach ($planSubjects as $ps) {
                    // التحقق إذا كانت هناك شعب منشأة بالفعل لهذه المادة (ps) في هذا السياق (ec)
                    $existingSectionsCount = Section::where('plan_subject_id', $ps->id)
                        ->where('academic_year', $ec->academic_year)
                        ->where('semester', $ec->plan_semester) // **فصل الشعبة يجب أن يطابق فصل الخطة هنا**
                        ->where(fn($q) => is_null($ec->branch) ? $q->whereNull('branch') : $q->where('branch', $ec->branch))
                        ->count();

                    if ($existingSectionsCount > 0) {
                        Log::info("Sections already exist for PS ID {$ps->id} (Subject: {$ps->subject->subject_no}) in context of EC ID {$ec->id}. Skipping automatic generation for this subject.");
                        continue; // تخطى هذه المادة إذا كانت شعبها موجودة بالفعل
                    }

                    // إذا لم تكن هناك شعب، قم بتوليدها
                    $this->generateSectionsLogicForSingleSubject($ps, $ec, true); // true لتحديث العداد
                    $this->subjectsProcessedForGeneration++;
                }
            }
            DB::commit();

            $message = "Missing sections generation process completed. ";
            if ($this->sectionsCreatedByGenerate > 0) {
                $message .= "{$this->sectionsCreatedByGenerate} new sections were created across {$this->subjectsProcessedForGeneration} subjects in {$this->contextsProcessedForGeneration} contexts. ";
            } elseif ($this->contextsProcessedForGeneration > 0) {
                $message .= "No new sections needed to be created (all relevant sections might already exist or no subjects to process in {$this->contextsProcessedForGeneration} contexts). ";
            } else {
                $message .= "No relevant contexts found to process for section generation.";
            }
            return redirect()->route('data-entry.instructor-section.index')->with('success', $message);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error during generateMissingSections: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->route('data-entry.instructor-section.index')->with('error', 'Failed to generate missing sections: ' . $e->getMessage());
        }
    }

    /**
     * Core logic to generate sections for a SINGLE PlanSubject within an ExpectedCount context.
     * (This function will now only create sections if they don't exist for a specific subject-context)
     */
    private function generateSectionsLogicForSingleSubject(PlanSubject $planSubject, PlanExpectedCount $expectedCount, bool $updateGlobalCounter = false)
    {
        Log::info("Logic: Generating sections for PS ID: {$planSubject->id} (Subject: {$planSubject->subject->subject_no}) within EC ID: {$expectedCount->id}");

        $subject = $planSubject->subject()->with('subjectCategory')->first();
        if (!$subject || !$subject->subjectCategory) {
            Log::error("Logic: Subject or its category is missing for PS ID: {$planSubject->id}. Cannot create sections.");
            // لا ترمي Exception هنا، فقط سجل الخطأ وتجاوز هذه المادة
            return;
        }

        $totalExpected = $expectedCount->male_count + $expectedCount->female_count;
        if ($totalExpected <= 0) {
            Log::info("Logic: Total expected is zero for PS ID {$planSubject->id}. No sections created.");
            return;
        }

        // لا نحتاج لحذف الشعب القديمة هنا لأن generateMissingSections تتحقق من عدم وجودها
        // وإذا كانت تُستدعى من زر "Regenerate" خاص بمادة، فذلك الزر سيقوم بالحذف أولاً

        $subjectCategoryName = strtolower($subject->subjectCategory->subject_category_name);
        $nextSectionNumberForThisSubject = 1; // عداد الشعب لهذه المادة (نظري ثم عملي)

        // --- 1. الجزء النظري ---
        if (($subject->theoretical_hours ?? 0) > 0) {
            // الشرط الآن أبسط: إذا كانت المادة نظرية أو مشتركة
            if (Str::contains($subjectCategoryName, ['theory', 'نظري']) || Str::contains($subjectCategoryName, ['combined', 'مشترك', 'نظري وعملي'])) {
                $capacity = ($subject->capacity_theoretical_section ?? 0) > 0 ? $subject->capacity_theoretical_section : self::DEFAULT_THEORY_SECTION_CAPACITY_FALLBACK;
                if ($capacity <= 0) $capacity = self::DEFAULT_THEORY_SECTION_CAPACITY_FALLBACK;

                $numSections = ceil($totalExpected / $capacity);
                if ($numSections > 1) { /* ... (نفس منطق MIN_STUDENTS_FOR_NEW_SECTION) ... */
                }
                if ($numSections == 0 && $totalExpected > 0) $numSections = 1;

                if ($numSections > 0) {
                    $baseStudents = floor($totalExpected / $numSections);
                    $remainder = $totalExpected % $numSections;
                    for ($i = 0; $i < $numSections; $i++) {
                        $count = $baseStudents + ($remainder > 0 ? 1 : 0);
                        if ($remainder > 0) $remainder--;
                        if ($count > 0) {
                            Section::create([
                                'plan_subject_id' => $planSubject->id,
                                'academic_year' => $expectedCount->academic_year,
                                'semester' => $expectedCount->plan_semester, // فصل الشعبة = فصل الخطة
                                'activity_type' => 'Theory',
                                'section_number' => $nextSectionNumberForThisSubject++,
                                'student_count' => $count,
                                'section_gender' => 'Mixed',
                                'branch' => $expectedCount->branch,
                            ]);
                            if ($updateGlobalCounter) $this->sectionsCreatedByGenerate++;
                        }
                    }
                    Log::info("Logic: Created {$numSections} Theory Sections for PS ID {$planSubject->id}");
                }
            }
        }

        // --- 2. الجزء العملي ---
        if (($subject->practical_hours ?? 0) > 0) {
            // الشرط الآن أبسط: إذا كانت المادة عملية أو مشتركة
            if (Str::contains($subjectCategoryName, ['practical', 'عملي']) || Str::contains($subjectCategoryName, ['combined', 'مشترك', 'نظري وعملي'])) {
                $capacity = ($subject->capacity_practical_section ?? 0) > 0 ? $subject->capacity_practical_section : self::DEFAULT_PRACTICAL_SECTION_CAPACITY_FALLBACK;
                if ($capacity <= 0) $capacity = self::DEFAULT_PRACTICAL_SECTION_CAPACITY_FALLBACK;

                if ($totalExpected > 0 && $capacity > 0) {
                    $numSections = ceil($totalExpected / $capacity);
                    if ($numSections > 1) { /* ... (نفس منطق MIN_STUDENTS_FOR_NEW_SECTION) ... */
                    }
                    if ($numSections == 0 && $totalExpected > 0) $numSections = 1;

                    if ($numSections > 0) {
                        $baseStudents = floor($totalExpected / $numSections);
                        $remainder = $totalExpected % $numSections;
                        for ($i = 0; $i < $numSections; $i++) {
                            $count = $baseStudents + ($remainder > 0 ? 1 : 0);
                            if ($remainder > 0) $remainder--;
                            if ($count > 0) {
                                Section::create([
                                    'plan_subject_id' => $planSubject->id,
                                    'academic_year' => $expectedCount->academic_year,
                                    'semester' => $expectedCount->plan_semester, // فصل الشعبة
                                    'activity_type' => 'Practical',
                                    'section_number' => $nextSectionNumberForThisSubject++,
                                    'student_count' => $count,
                                    'section_gender' => 'Mixed',
                                    'branch' => $expectedCount->branch,
                                ]);
                                if ($updateGlobalCounter) $this->sectionsCreatedByGenerate++;
                            }
                        }
                        Log::info("Logic: Created {$numSections} Practical Sections for PS ID {$planSubject->id}");
                    }
                }
            }
        }
        if ($nextSectionNumberForThisSubject == 1) {
            Log::warning("Logic: No sections (Theory or Practical) actually created for Subject ID {$subject->id} (PS ID {$planSubject->id}). Check hours, capacities, or category '{$subjectCategoryName}'.");
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Handle the import of instructor loads from an Excel file.
     */
    // public function importInstructorLoadsExcel(Request $request)
    // {
    //     $request->validate([
    //         'instructor_loads_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
    //         'target_semester' => ['required', 'integer', Rule::in([1, 2, 3])], // الفصل الدراسي المستهدف
    //         // (اختياري) يمكن إضافة target_academic_year إذا أردت أن يكون الملف مرناً لعدة سنوات
    //     ]);

    //     $this->resetCounters();
    //     $targetSemester = $request->input('target_semester');
    //     // $targetAcademicYear = $request->input('target_academic_year', date('Y')); // افترض السنة الحالية إذا لم تحدد

    //     Log::info("Starting Excel import for instructor loads for Semester: {$targetSemester}");

    //     // الخطوة الأولى: حذف التعيينات القديمة لهذا الفصل
    //     try {
    //         // نفترض أن جدول instructor_section يربط instructor_id بـ section_id
    //         // ونفترض أن جدول sections يحتوي على semester و academic_year
    //         DB::table('instructor_section')
    //             ->whereIn('section_id', function ($query) use ($targetSemester) {
    //                 $query->select('id')->from('sections')
    //                     ->where('semester', $targetSemester);
    //                 //   ->where('academic_year', $targetAcademicYear); // إذا أضفت فلتر السنة
    //             })
    //             ->delete();
    //         Log::info("Cleared old instructor assignments for Semester: {$targetSemester}");
    //     } catch (Exception $e) {
    //         Log::error("Failed to clear old assignments: " . $e->getMessage());
    //         return redirect()->route('data-entry.instructor-subject.index')
    //             ->with('error', 'Failed to clear old assignments before import: ' . $e->getMessage());
    //     }


    //     try {
    //         $rows = Excel::toArray(new \stdClass(), $request->file('instructor_loads_excel_file'))[0];
    //         if (count($rows) <= 1) { /* ... رسالة خطأ ... */
    //         }
    //         $header = array_map('strtolower', array_map('trim', array_shift($rows)));

    //         // البحث عن مواقع الأعمدة
    //         $instructorNameCol = $this->getColumnIndex($header, ['اسم المدرس', 'instructor_name', 'instructor']);
    //         $subjectNameCol = $this->getColumnIndex($header, ['اسم المساق', 'subject_name', 'subject']);
    //         $specializationLevelCol = $this->getColumnIndex($header, ['التخصص والمستوى', 'specialization_level', 'level_spec']);
    //         $theorySectionsCountCol = $this->getColumnIndex($header, ['عدد الشعب نظري', 'theory_sections']);
    //         $practicalSectionsCountCol = $this->getColumnIndex($header, ['عدد الشعب عملي', 'practical_sections']);
    //         $branchCol = $this->getColumnIndex($header, ['الفرع', 'branch']);

    //         if (is_null($instructorNameCol) || is_null($subjectNameCol) || is_null($specializationLevelCol)) {
    //             $missing = []; /* ... تحديد الأعمدة المفقودة ... */
    //             return redirect()->route('data-entry.instructor-subject.index')->with('error', 'Excel missing columns: ' . implode(', ', $missing));
    //         }

    //         $currentRowNumber = 1;
    //         $currentInstructorFromFile = null;
    //         $currentInstructorModel = null;

    //         foreach ($rows as $rowIndex => $row) {
    //             $currentRowNumber++;
    //             $rowData = [];
    //             foreach ($header as $index => $colName) {
    //                 $rowData[$colName] = $row[$index] ?? null;
    //             }

    //             if (count(array_filter($row)) == 0) {
    //                 $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (empty).";
    //                 $this->assignmentsSkipped++;
    //                 continue;
    //             }

    //             // معالجة اسم المدرس (قد يكون مدمجاً)
    //             $instructorNameInput = trim($rowData[$header[$instructorNameCol]] ?? null);
    //             if (!empty($instructorNameInput)) {
    //                 $currentInstructorFromFile = $instructorNameInput;
    //                 $currentInstructorModel = Instructor::where('instructor_name', 'LIKE', "%{$currentInstructorFromFile}%")
    //                     ->orWhere('instructor_no', $currentInstructorFromFile)->first(); // أو البحث بالرقم
    //                 if (!$currentInstructorModel) {
    //                     $this->skippedDetails[] = "Row {$currentRowNumber}: Instructor '{$currentInstructorFromFile}' not found. Skipping subsequent rows for this instructor until a new name is found.";
    //                     $currentInstructorFromFile = null; // لإيقاف معالجة أسطر هذا المدرس
    //                 }
    //             }
    //             if (!$currentInstructorModel) {
    //                 if (!empty($instructorNameInput)) $this->assignmentsSkipped++;
    //                 continue;
    //             } // إذا لم يتم العثور على مدرس وبدأنا بسطر جديد له

    //             // تجاهل أسطر "المجموع" أو الفواصل
    //             if (strtolower(trim($rowData[$header[$subjectNameCol]] ?? '')) === 'المجموع') {
    //                 $currentInstructorFromFile = null;
    //                 $currentInstructorModel = null;
    //                 continue;
    //             }

    //             $subjectIdentifier = trim($rowData[$header[$subjectNameCol]] ?? null);
    //             $specLevelInput = trim($rowData[$header[$specializationLevelCol]] ?? null);
    //             $numTheoryToAssign = isset($theorySectionsCountCol) ? (int)($rowData[$header[$theorySectionsCountCol]] ?? 0) : 0;
    //             $numPracticalToAssign = isset($practicalSectionsCountCol) ? (int)($rowData[$header[$practicalSectionsCountCol]] ?? 0) : 0;
    //             $branchFromFile = isset($branchCol) ? trim($rowData[$header[$branchCol]] ?? 'Default') : 'Default'; // Default to 'Default' or handle null
    //             if (strtolower($branchFromFile) === 'دمج' || strtolower($branchFromFile) === 'default' || empty($branchFromFile)) {
    //                 $branchFromFile = null; // التعامل مع "دمج" كفرع افتراضي (لا فرع)
    //             }


    //             if (empty($subjectIdentifier) || empty($specLevelInput)) {
    //                 $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (missing subject or spec/level for instructor {$currentInstructorModel->instructor_name}).";
    //                 $this->assignmentsSkipped++;
    //                 continue;
    //             }

    //             // استخراج رمز التخصص والمستوى
    //             preg_match('/([a-zA-Z]+)(\d+)/', strtoupper(str_replace(' ', '', $specLevelInput)), $matches);
    //             $planCodeFromFile = $matches[1] ?? null;
    //             $levelFromFile = $matches[2] ?? null;

    //             if (!$planCodeFromFile || !$levelFromFile) {
    //                 $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (Could not parse specialization/level '{$specLevelInput}').";
    //                 $this->assignmentsSkipped++;
    //                 continue;
    //             }

    //             $subjectModel = $this->findSubject($subjectIdentifier);
    //             if (!$subjectModel) {
    //                 $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (Subject '{$subjectIdentifier}' not found).";
    //                 $this->assignmentsSkipped++;
    //                 continue;
    //             }

    //             // البحث عن PlanSubject
    //             $planSubject = PlanSubject::where('subject_id', $subjectModel->id)
    //                 ->where('plan_level', $levelFromFile)
    //                 ->whereHas('plan', fn($q) => $q->where('plan_no', 'LIKE', "%{$planCodeFromFile}%")
    //                     ->orWhere('plan_name', 'LIKE', "%{$planCodeFromFile}%")) // بحث مرن عن الخطة
    //                 ->where('plan_semester', $targetSemester) // ** استخدام الفصل المستهدف من المودال **
    //                 ->first();

    //             if (!$planSubject) {
    //                 $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (Subject '{$subjectModel->subject_no}' not found in Plan like '{$planCodeFromFile}' Level {$levelFromFile} Semester {$targetSemester}).";
    //                 $this->assignmentsSkipped++;
    //                 continue;
    //             }

    //             // السنة الأكاديمية للشعب (نفترض أنها السنة الحالية أو سنة محددة للعملية)
    //             // يجب أن تكون الشعب منشأة لهذه السنة والفصل
    //             $targetAcademicYear = PlanExpectedCount::where('plan_id', $planSubject->plan_id)
    //                 ->where('plan_level', $planSubject->plan_level)
    //                 ->where('plan_semester', $planSubject->plan_semester)
    //                 ->max('academic_year'); // أو طريقة أخرى لتحديد السنة الأكاديمية للشعب
    //             if (!$targetAcademicYear) {
    //                 $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (Could not determine academic year for sections of PS ID {$planSubject->id}).";
    //                 $this->assignmentsSkipped++;
    //                 continue;
    //             }


    //             // تعيين الشعب النظرية
    //             if ($numTheoryToAssign > 0) {
    //                 $availableTheorySections = Section::where('plan_subject_id', $planSubject->id)
    //                     ->where('academic_year', $targetAcademicYear)->where('semester', $targetSemester)
    //                     ->where('activity_type', 'Theory')->where('branch', $branchFromFile)
    //                     ->whereDoesntHave('instructors') // التي لم تُعطَ لمدرس بعد
    //                     ->orderBy('section_number')->take($numTheoryToAssign)->pluck('id');

    //                 if ($availableTheorySections->count() < $numTheoryToAssign) {
    //                     $this->skippedDetails[] = "Row {$currentRowNumber}: Not enough available Theory sections for '{$subjectModel->subject_no}' for instructor '{$currentInstructorModel->instructor_name}'. Needed: {$numTheoryToAssign}, Found: {$availableTheorySections->count()}";
    //                 }
    //                 foreach ($availableTheorySections as $sectionId) {
    //                     // التحقق من النصاب قبل الإضافة
    //                     // ... (منطق التحقق من النصاب) ...
    //                     $currentInstructorModel->sections()->attach($sectionId); // استخدام جدول instructor_section
    //                     $this->assignmentsCreated++;
    //                 }
    //             }
    //             // تعيين الشعب العملية (بنفس الطريقة)
    //             if ($numPracticalToAssign > 0) { /* ... */
    //             }
    //         } // نهاية حلقة الصفوف

    //         DB::commit();
    //         // بناء رسالة النجاح
    //         $messages = []; /* ... */
    //         return redirect()->route('data-entry.instructor-subject.index')->with('success', implode(' ', $messages))->with('skipped_details', $this->skippedDetails);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('Instructor Loads Excel Import Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
    //         return redirect()->route('data-entry.instructor-subject.index')->with('error', 'Error during import: ' . $e->getMessage())->with('skipped_details', $this->skippedDetails);
    //     }
    // }
    // ... (generateSectionsLogicForSingleSubject من الرد السابق) ...

    // --- عدادات لعملية الـ Import لتوزيع الأحمال ---
    private $assignmentsCreatedCount = 0;
    private $assignmentsSkippedCount = 0;
    private $assignmentSkippedDetails = []; // لتخزين تفاصيل الأسطر المتجاهلة في توزيع الأحمال
    private $processedInstructorsForLoad = []; // لتتبع معالجة المدرسين داخل ملف توزيع الأحمال

    private function resetCountersForLoadAssignment()
    {
        $this->assignmentsCreatedCount = 0;
        $this->assignmentsSkippedCount = 0;
        $this->assignmentSkippedDetails = [];
        $this->processedInstructorsForLoad = [];
    }

    /**
     * Handle the import of instructor loads from an Excel file.
     */
    // public function importInstructorLoadsExcel(Request $request)
    // {
    //     $request->validate([
    //         'instructor_loads_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
    //         'target_semester' => ['required', 'integer', Rule::in([1, 2, 3])],
    //         // يمكنك إضافة target_academic_year هنا إذا أردت أن يكون الملف مرناً لسنوات مختلفة
    //         // 'target_academic_year' => 'required|integer|digits:4',
    //     ]);

    //     $this->resetCountersForLoadAssignment(); // *** إعادة تعيين العدادات الخاصة بهذه العملية ***
    //     $targetSemester = (int) $request->input('target_semester');
    //     // افترض أننا نعمل على السنة الأكاديمية "الحالية" أو الأكثر شيوعاً إذا لم يتم تحديدها
    //     // هذا الجزء يحتاج لتحديد أدق: هل ملف الإكسل دائماً للسنة الحالية؟
    //     // $targetAcademicYear = $request->input('target_academic_year', AcademicYear::getCurrent()->year); // مثال
    //     // للتبسيط الآن، سنبحث عن شعب تطابق الفصل بغض النظر عن السنة، ولكن هذا قد يسبب مشاكل
    //     // الأفضل هو تحديد السنة الأكاديمية أيضاً

    //     Log::info("Starting Excel import for instructor loads for Target Semester: {$targetSemester}");

    //     DB::beginTransaction();
    //     try {
    //         // الخطوة الأولى: حذف التعيينات القديمة لهذا الفصل (ولسنة محددة إذا تم تحديدها)
    //         // هذا يفترض أن جدول instructor_section يربط instructor_id بـ section_id
    //         $affectedRows = DB::table('instructor_section')
    //             ->whereIn('section_id', function ($query) use ($targetSemester) {
    //                 $query->select('id')->from('sections')
    //                     ->where('semester', $targetSemester);
    //                 //   ->where('academic_year', $targetAcademicYear); // إذا أضفت فلتر السنة
    //             })
    //             ->delete();
    //         Log::info("Cleared {$affectedRows} old instructor assignments for Target Semester: {$targetSemester}.");

    //         $rows = Excel::toArray(new \stdClass(), $request->file('instructor_loads_excel_file'))[0];
    //         if (count($rows) <= 1) {
    //             throw new Exception('Uploaded Excel file is empty or contains only a header row.');
    //         }

    //         $header = array_map('strtolower', array_map('trim', array_shift($rows)));
    //         // تحديد مواقع الأعمدة بناءً على الأسماء المحتملة
    //         $colMap = [
    //             'instructor' => $this->getColumnIndex($header, ['اسم المدرس', 'instructor_name', 'instructor', 'المدرس']),
    //             'subject' => $this->getColumnIndex($header, ['اسم المساق', 'subject_name', 'subject', 'المساق']),
    //             'spec_level' => $this->getColumnIndex($header, ['التخصص والمستوى', 'specialization_level', 'level_spec', 'التخصص']),
    //             'theory_count' => $this->getColumnIndex($header, ['عدد الشعب نظري', 'theory_sections', 'نظري']),
    //             'practical_count' => $this->getColumnIndex($header, ['عدد الشعب عملي', 'practical_sections', 'عملي']),
    //             'branch' => $this->getColumnIndex($header, ['الفرع', 'branch']),
    //         ];


    //         if (is_null($colMap['instructor']) || is_null($colMap['subject']) || is_null($colMap['spec_level'])) {
    //             throw new Exception("Excel file is missing critical columns (Instructor, Subject, or Specialization/Level). Please check header row.");
    //         }

    //         $currentRowNumber = 1; // للعد من الصف الثاني بعد العناوين

    //         $currentInstructorModel = null;
    //         $currentInstructorTotalHours = 0; // لتتبع نصاب المدرس الحالي في الملف
    //         // dd([
    //         //     'instructor' => $this->getColumnIndex($header, ['اسم المدرس', 'instructor_name', 'instructor', 'المدرس']),
    //         //     'subject' => $this->getColumnIndex($header, ['اسم المساق', 'subject_name', 'subject', 'المساق']),
    //         //     'spec_level' => $this->getColumnIndex($header, ['التخصص والمستوى', 'specialization_level', 'level_spec', 'التخصص']),
    //         //     'theory_count' => $this->getColumnIndex($header, ['عدد الشعب نظري', 'theory_sections', 'نظري']),
    //         //     'practical_count' => $this->getColumnIndex($header, ['عدد الشعب عملي', 'practical_sections', 'عملي']),
    //         //     'branch' => $this->getColumnIndex($header, ['الفرع', 'branch']),
    //         // ]);

    //         foreach ($rows as $rowIndex => $row) {
    //             $currentRowNumber++;
    //             $rowData = [];
    //             foreach ($header as $index => $colName) {
    //                 $rowData[$colName] = $row[$index] ?? null;
    //             }

    //             if (count(array_filter($row, fn($cell) => !is_null($cell) && $cell !== '')) == 0) {
    //                 $this->assignmentSkippedDetails[] = "Row {$currentRowNumber}: Skipped (empty row).";
    //                 $this->assignmentsSkippedCount++;
    //                 continue;
    //             }

    //             // معالجة اسم المدرس (قد يكون مدمجاً)
    //             $instructorNameInput = trim($rowData[$header[$colMap['instructor']]] ?? null);
    //             if (!empty($instructorNameInput)) {
    //                 $currentInstructorModel = $this->findInstructor($instructorNameInput);
    //                 $currentInstructorTotalHours = 0; // إعادة تعيين الساعات للمدرس الجديد
    //                 $this->processedInstructorsForLoad[$currentInstructorModel->id ?? 'unknown'] = true; // لتتبع المدرسين المعالجين
    //                 if (!$currentInstructorModel) {
    //                     $this->assignmentSkippedDetails[] = "Row {$currentRowNumber}: Instructor '{$instructorNameInput}' not found. Skipping subsequent rows for this instructor.";
    //                     $currentInstructorFromFile = null; // إيقاف معالجة هذا المدرس
    //                     $this->assignmentsSkippedCount++;
    //                     continue; // انتقل للصف التالي إذا كان اسم المدرس جديداً وغير موجود
    //                 }
    //             }
    //             if (!$currentInstructorModel) { // إذا كان اسم المدرس فارغاً وما زلنا لا نملك مدرس حالي
    //                 if (!empty(trim(implode("", $row)))) { // إذا لم يكن الصف فارغاً تماماً
    //                     $this->assignmentSkippedDetails[] = "Row {$currentRowNumber}: Skipped (instructor name missing or not found previously).";
    //                     $this->assignmentsSkippedCount++;
    //                 }
    //                 continue;
    //             }

    //             if (strtolower(trim($rowData[$header[$colMap['subject']]] ?? '')) === 'المجموع') {
    //                 $currentInstructorModel = null;
    //                 continue;
    //             } // تجاهل أسطر المجموع

    //             $subjectIdentifier = trim($rowData[$header[$colMap['subject']]] ?? null);
    //             $specLevelInput = trim($rowData[$header[$colMap['spec_level']]] ?? null);
    //             $numTheoryToAssign = isset($colMap['theory_count']) ? (int)($rowData[$header[$colMap['theory_count']]] ?? 0) : 0;
    //             $numPracticalToAssign = isset($colMap['practical_count']) ? (int)($rowData[$header[$colMap['practical_count']]] ?? 0) : 0;
    //             $branchFromFile = isset($colMap['branch']) ? trim($rowData[$header[$colMap['branch']]] ?? 'Default') : 'Default';
    //             if (strtolower($branchFromFile) === 'دمج' || strtolower($branchFromFile) === 'default' || empty($branchFromFile)) $branchFromFile = null;

    //             if (empty($subjectIdentifier) || empty($specLevelInput) || ($numTheoryToAssign == 0 && $numPracticalToAssign == 0)) {
    //                 $this->assignmentSkippedDetails[] = "Row {$currentRowNumber} (Inst:{$currentInstructorModel->id}): Skipped (missing subject, spec/level, or section counts).";
    //                 $this->assignmentsSkippedCount++;
    //                 continue;
    //             }

    //             preg_match('/([a-zA-Z\s]+)(\d*)/u', preg_replace('/\s+/', '', $specLevelInput), $matches);
    //             $planCodeFromFile = trim($matches[1] ?? null);
    //             $levelFromFile = trim($matches[2] ?? null);


    //             if (!$planCodeFromFile || !$levelFromFile) {
    //                 $this->assignmentSkippedDetails[] = "Row {$currentRowNumber}: Skipped (Could not parse '{$specLevelInput}' to SpecCode and Level).";
    //                 $this->assignmentsSkippedCount++;
    //                 continue;
    //             }

    //             $subjectModel = $this->findSubject($subjectIdentifier);
    //             if (!$subjectModel) {
    //                 $this->assignmentSkippedDetails[] = "Row {$currentRowNumber}: Skipped (Subject '{$subjectIdentifier}' not found).";
    //                 $this->assignmentsSkippedCount++;
    //                 continue;
    //             }

    //             // البحث عن PlanSubject
    //             $planSubject = PlanSubject::where('subject_id', $subjectModel->id)
    //                 ->where('plan_level', $levelFromFile)
    //                 ->whereHas('plan', fn($q) => $q->where(DB::raw('REPLACE(LOWER(plan_no), " ", "")'), 'LIKE', "%" . strtolower(str_replace(' ', '', $planCodeFromFile)) . "%")
    //                     ->orWhere(DB::raw('REPLACE(LOWER(plan_name), " ", "")'), 'LIKE', "%" . strtolower(str_replace(' ', '', $planCodeFromFile)) . "%"))
    //                 ->where('plan_semester', $targetSemester) // الفصل المستهدف من المودال
    //                 ->first();

    //             if (!$planSubject) {
    //                 $this->assignmentSkippedDetails[] = "Row {$currentRowNumber}: Skipped (Subject '{$subjectModel->subject_no}' not found in Plan like '{$planCodeFromFile}' Level {$levelFromFile} for Target Semester {$targetSemester}).";
    //                 $this->assignmentsSkippedCount++;
    //                 continue;
    //             }

    //             // تحديد السنة الأكاديمية للشعب التي سنبحث عنها (الأحدث أو المحددة)
    //             // هذا الجزء قد يحتاج لتحديد السنة الأكاديمية بشكل أدق
    //             $academicYearForSections = PlanExpectedCount::where('plan_id', $planSubject->plan_id)
    //                 ->where('plan_level', $planSubject->plan_level)
    //                 ->where('plan_semester', $planSubject->plan_semester) // فصل الخطة
    //                 ->max('academic_year'); // افترض أحدث سنة

    //             if (!$academicYearForSections) {
    //                 $this->assignmentSkippedDetails[] = "Row {$currentRowNumber}: Skipped (No academic year context found for sections of PS ID {$planSubject->id}).";
    //                 $this->assignmentsSkippedCount++;
    //                 continue;
    //             }

    //             // تعيين الشعب النظرية
    //             if ($numTheoryToAssign > 0) {
    //                 $assignedCount = $this->assignSectionsToInstructor($currentInstructorModel, $planSubject, $academicYearForSections, $targetSemester, 'Theory', $numTheoryToAssign, $branchFromFile, $currentInstructorTotalHours, $currentRowNumber);
    //                 $currentInstructorTotalHours += ($assignedCount * ($subjectModel->theoretical_hours ?? 0)); // تقدير للساعات
    //             }
    //             // تعيين الشعب العملية
    //             if ($numPracticalToAssign > 0) {
    //                 $assignedCount = $this->assignSectionsToInstructor($currentInstructorModel, $planSubject, $academicYearForSections, $targetSemester, 'Practical', $numPracticalToAssign, $branchFromFile, $currentInstructorTotalHours, $currentRowNumber);
    //                 $currentInstructorTotalHours += ($assignedCount * ($subjectModel->practical_hours ?? 0)); // تقدير للساعات
    //             }
    //         } // نهاية حلقة الصفوف

    //         DB::commit();
    //         $messages = [];
    //         if ($this->assignmentsCreated > 0) $messages[] = "{$this->assignmentsCreated} section assignments created/updated.";
    //         if ($this->assignmentsSkippedCount > 0) $messages[] = "{$this->assignmentsSkippedCount} assignment rows were skipped.";
    //         if (empty($messages)) {
    //             return redirect()->route('data-entry.instructor-subject.index')->with('info', 'Excel processed. No new assignments or all rows skipped.');
    //         }
    //         return redirect()->route('data-entry.instructor-subject.index')->with('success', implode(' ', $messages))->with('skipped_details', $this->assignmentSkippedDetails);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('Instructor Loads Excel Import Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
    //         return redirect()->route('data-entry.instructor-subject.index')->with('error', 'Error during import: ' . $e->getMessage())->with('skipped_details', $this->assignmentSkippedDetails);
    //     }
    // }

    /**
     * Handle the import of instructor loads from an Excel file.
     * (هذه الدالة هي التي يتم استدعاؤها من الروت)
     */
    public function importInstructorLoadsExcel(Request $request)
    {
        $request->validate([
            'instructor_loads_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'target_semester' => ['required', 'integer', Rule::in([1, 2, 3])], // الفصل الدراسي المستهدف
            // 'target_academic_year' => 'required|integer|digits:4', // إذا أردت تحديد السنة
        ]);

        $this->resetCountersForLoadAssignment();
        $targetSemester = (int) $request->input('target_semester');
        // افترض أننا نعمل على السنة الأكاديمية "الحالية" أو الأكثر شيوعاً
        // $targetAcademicYear = $request->input('target_academic_year', date('Y')); // مثال

        Log::info("Starting Excel import for instructor loads for Target Semester: {$targetSemester}.");

        DB::beginTransaction();
        try {
            // 1. حذف التعيينات القديمة لهذا الفصل (ولسنة محددة إذا تم تحديدها)
            $sectionsToClearQuery = Section::where('semester', $targetSemester);
            // if (isset($targetAcademicYear)) {
            //     $sectionsToClearQuery->where('academic_year', $targetAcademicYear);
            // }
            $sectionIdsToClear = $sectionsToClearQuery->pluck('id');

            if ($sectionIdsToClear->isNotEmpty()) {
                DB::table('instructor_section')->whereIn('section_id', $sectionIdsToClear)->delete();
                Log::info("Cleared old instructor assignments for Target Semester: {$targetSemester}.");
            } else {
                Log::info("No sections found for Target Semester: {$targetSemester} to clear assignments from.");
            }


            // 2. قراءة ملف الإكسل
            $rows = Excel::toArray(new \stdClass(), $request->file('instructor_loads_excel_file'))[0]; // أول شيت
            if (count($rows) <= 1) { // تحقق من وجود بيانات بعد العناوين
                throw new Exception('Uploaded Excel file is empty or contains only a header row.');
            }

            $header = array_map('strtolower', array_map('trim', array_shift($rows))); // العناوين

            $sectionIdsToClear = $sectionsToClearQuery->pluck('id');

            // تحديد مواقع الأعمدة المتوقعة
            $colMap = [
                'instructor' => $this->getColumnIndex($header, ['اسم المدرس', 'instructor_name', 'instructor']),
                'subject' => $this->getColumnIndex($header, ['اسم المساق', 'subject_name', 'subject']),
                'spec_level' => $this->getColumnIndex($header, ['التخصص والمستوى', 'specialization_level']),
                'theory_sections_count' => $this->getColumnIndex($header, ['عدد الشعب نظري', 'theory_sections']),
                'practical_sections_count' => $this->getColumnIndex($header, ['عدد الشعب عملي', 'practical_sections']),
                'branch' => $this->getColumnIndex($header, ['الفرع', 'branch']),
            ];

            if (is_null($colMap['instructor']) || is_null($colMap['subject']) || is_null($colMap['spec_level'])) {
                $missing = [];
                if (is_null($colMap['instructor'])) $missing[] = "'اسم المدرس'";
                if (is_null($colMap['subject'])) $missing[] = "'اسم المساق'";
                if (is_null($colMap['spec_level'])) $missing[] = "'التخصص والمستوى'";
                throw new Exception("Excel file is missing critical columns: " . implode(', ', $missing) . ". Please check header row.");
            }

            $currentRowNumber = 1; // يبدأ من 2 فعلياً لأننا أزلنا العناوين
            $currentInstructorModel = null;
            $currentInstructorTotalHours = 0;

            foreach ($rows as $rowIndex => $rowArray) {
                $currentRowNumber++; // رقم الصف الفعلي في الإكسل

                // تحويل الصف لمصفوفة بأسماء العناوين الأصلية
                $rowData = [];
                foreach ($header as $index => $colName) {
                    $rowData[$colName] = $rowArray[$index] ?? null;
                }
                // تجاهل الصفوف الفارغة تماماً
                if (count(array_filter($rowArray, fn($cell) => !is_null($cell) && $cell !== '')) == 0) {
                    $this->assignmentSkippedDetails[] = "Row {$currentRowNumber}: Skipped (empty row).";
                    $this->assignmentsSkippedCount++;
                    continue;
                }

                // 1. معالجة اسم المدرس (الخلايا المدمجة)
                $instructorNameInput = trim($rowData[$header[$colMap['instructor']]] ?? null);
                if (!empty($instructorNameInput)) {
                    $currentInstructorModel = $this->findInstructor($instructorNameInput);
                    $currentInstructorTotalHours = 0; // إعادة تعيين عند تغير المدرس
                    if (!$currentInstructorModel) {
                        $this->assignmentSkippedDetails[] = "Row {$currentRowNumber}: Instructor '{$instructorNameInput}' not found. Skipping this and subsequent related rows until a new instructor name is found.";
                        $currentInstructorModel = null; // لمنع معالجة أسطر هذا المدرس
                        $this->assignmentsSkippedCount++;
                        continue;
                    }
                }
                if (!$currentInstructorModel) { // إذا كان اسم المدرس فارغاً وما زلنا لا نملك مدرس حالي
                    $this->assignmentSkippedDetails[] = "Row {$currentRowNumber}: Skipped (instructor name missing for this subject assignment).";
                    $this->assignmentsSkippedCount++;
                    continue;
                }

                // تجاهل أسطر "المجموع" أو الفواصل إذا كانت تعتمد على عمود فارغ للمادة
                $subjectIdentifier = trim($rowData[$header[$colMap['subject']]] ?? null);
                if (empty($subjectIdentifier) || strtolower($subjectIdentifier) === 'المجموع') {
                    $this->assignmentSkippedDetails[] = "Row {$currentRowNumber}: Skipped (assumed to be a separator or total row).";
                    $this->assignmentsSkippedCount++;
                    // لا نغير $currentInstructorModel هنا، قد يكون هناك المزيد من المواد لنفس المدرس
                    continue;
                }


                $specLevelInput = trim($rowData[$header[$colMap['spec_level']]] ?? null);
                $numTheoryToAssign = isset($colMap['theory_sections_count']) ? (int)($rowData[$header[$colMap['theory_sections_count']]] ?? 0) : 0;
                $numPracticalToAssign = isset($colMap['practical_sections_count']) ? (int)($rowData[$header[$colMap['practical_sections_count']]] ?? 0) : 0;
                $branchFromFile = isset($colMap['branch']) ? trim($rowData[$header[$colMap['branch']]] ?? null) : null;
                if (is_null($branchFromFile) || strtolower($branchFromFile) === 'دمج' || strtolower($branchFromFile) === 'default' || $branchFromFile === '') {
                    $branchFromFile = null;
                }

                if (empty($specLevelInput) || ($numTheoryToAssign == 0 && $numPracticalToAssign == 0)) {
                    $this->assignmentSkippedDetails[] = "Row {$currentRowNumber} (Inst:{$currentInstructorModel->instructor_no}, Subj:{$subjectIdentifier}): Skipped (missing specialization/level or no sections to assign).";
                    $this->assignmentsSkippedCount++;
                    continue;
                }

                // استخراج رمز الخطة والمستوى
                preg_match('/([a-zA-Z]+)(\d*)/u', preg_replace('/\s+/', '', $specLevelInput), $matches);
                $planCodeFromFile = !empty($matches[1]) ? trim($matches[1]) : null;
                $levelFromFile = !empty($matches[2]) ? trim($matches[2]) : null;

                if (!$planCodeFromFile || !$levelFromFile) {
                    $this->assignmentSkippedDetails[] = "Row {$currentRowNumber}: Skipped (Could not parse '{$specLevelInput}' to Plan Code and Level).";
                    $this->assignmentsSkippedCount++;
                    continue;
                }

                $subjectModel = $this->findSubject($subjectIdentifier);
                if (!$subjectModel) {
                    $this->assignmentSkippedDetails[] = "Row {$currentRowNumber}: Skipped (Subject '{$subjectIdentifier}' not found).";
                    $this->assignmentsSkippedCount++;
                    continue;
                }

                // البحث عن PlanSubject (المادة في الخطة والمستوى والفصل المستهدف)
                $planSubject = PlanSubject::where('subject_id', $subjectModel->id)
                    ->where('plan_level', $levelFromFile)
                    ->whereHas('plan', fn($q) => $q->where(DB::raw('REPLACE(LOWER(plan_no), " ", "")'), 'LIKE', "%" . strtolower(str_replace(' ', '', $planCodeFromFile)) . "%"))
                    ->where('plan_semester', $targetSemester) // الفصل المستهدف من المودال
                    ->first();

                if (!$planSubject) {
                    $this->assignmentSkippedDetails[] = "Row {$currentRowNumber}: Skipped (Subject '{$subjectModel->subject_no}' not found in Plan like '{$planCodeFromFile}', Level {$levelFromFile} for Target Semester {$targetSemester}).";
                    $this->assignmentsSkippedCount++;
                    continue;
                }

                // dd(['PlanExpectedCount' =>PlanExpectedCount::where('plan_id', $planSubject->plan_id)
                //     ->where('plan_level', $planSubject->plan_level)
                //     ->where('plan_semester', $planSubject->plan_semester) // فصل الخطة
                //     ->where(fn($q) => is_null($branchFromFile) ? $q->whereNull('branch') : $q->where('branch', $branchFromFile))
                //     ->max('academic_year'),
                //     'plan_id' => $planSubject->plan_id,
                //     'plan_level' => $planSubject->plan_level,
                //     'plan_semester' => $planSubject->plan_semester,
                // ]);
                // تحديد السنة الأكاديمية للشعب التي نبحث عنها (الأحدث لهذا السياق)
                $academicYearForSections = PlanExpectedCount::where('plan_id', $planSubject->plan_id)
                    ->where('plan_level', $planSubject->plan_level)
                    ->where('plan_semester', $planSubject->plan_semester) // فصل الخطة
                    ->where(fn($q) => is_null($branchFromFile) ? $q->whereNull('branch') : $q->where('branch', $branchFromFile))
                    ->max('academic_year');

                if (!$academicYearForSections) {
                    $this->assignmentSkippedDetails[] = "Row {$currentRowNumber}: Skipped (Could not determine active Academic Year for sections of PS ID {$planSubject->id} and branch '{$branchFromFile}').";
                    $this->assignmentsSkippedCount++;
                    continue;
                }

                // dd([
                //     '$targetSemester' => $targetSemester,
                //     // '$sectionsToClearQuery' => $sectionsToClearQuery,
                //     // '$sectionIdsToClear' => $sectionIdsToClear,
                //     '$rows' => $rows,
                //     '$colMap' => $colMap,
                //     '$colMapinstructor' => $colMap['instructor'],
                //     '$rowData' => $rowData,
                //     '$instructorNameInput' => $instructorNameInput,
                //     '$currentInstructorModel' => $currentInstructorModel,
                //     '$subjectIdentifier' => $subjectIdentifier,
                //     '$specLevelInput' => $specLevelInput,
                //     '$numTheoryToAssign' => $numTheoryToAssign,
                //     '$numPracticalToAssign' => $numPracticalToAssign,
                //     '$branchFromFile' => $branchFromFile,
                //     '$planCodeFromFile' => $planCodeFromFile,
                //     '$levelFromFile' => $levelFromFile,
                //     '$colMapsubject' => $colMap['subject'],
                //     '$subjectModel' => $subjectModel,
                //     '$planSubject' => $planSubject,
                //     '$academicYearForSections' => $academicYearForSections,
                // ]);

                // تعيين الشعب النظرية
                if ($numTheoryToAssign > 0) {
                    $assignedTheory = $this->assignSectionsToInstructor($currentInstructorModel, $planSubject, $academicYearForSections, $targetSemester, 'Theory', $numTheoryToAssign, $branchFromFile, $currentInstructorTotalHours, $currentRowNumber);
                    $currentInstructorTotalHours += ($assignedTheory * ($subjectModel->theoretical_hours ?? 0));
                }
                // تعيين الشعب العملية
                if ($numPracticalToAssign > 0) {
                    $assignedPractical = $this->assignSectionsToInstructor($currentInstructorModel, $planSubject, $academicYearForSections, $targetSemester, 'Practical', $numPracticalToAssign, $branchFromFile, $currentInstructorTotalHours, $currentRowNumber);
                    $currentInstructorTotalHours += ($assignedPractical * ($subjectModel->practical_hours ?? 0));
                }
            } // نهاية حلقة الصفوف

            DB::commit();
            // بناء رسالة النجاح
            $messages = [];
            if ($this->assignmentsCreatedCount > 0) $messages[] = "{$this->assignmentsCreatedCount} section assignments created/updated.";
            if ($this->assignmentsSkippedCount > 0) $messages[] = "{$this->assignmentsSkippedCount} assignment rows were skipped (see details below if any).";
            if (empty($messages)) {
                return redirect()->route('data-entry.instructor-section.index')->with('info', 'Excel processed. No new assignments made or all rows skipped.');
            } // افترض أن هذا هو اسم الروت لصفحة الأزرار
            return redirect()->route('data-entry.instructor-section.index')->with('success', implode(' ', $messages))->with('skipped_details', $this->assignmentSkippedDetails);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Instructor Loads Excel Import Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->route('data-entry.instructor-section.index')->with('error', 'Error during import: ' . $e->getMessage())->with('skipped_details', $this->assignmentSkippedDetails);
        }
    }

    // --- الدوال المساعدة (getColumnIndex, findInstructor, findSubject, normalizeArabicStringForSearch, assignSectionsToInstructor) ---
    // --- يجب نسخها بالكامل من الردود السابقة ووضعها هنا ---


    private function getColumnIndex(array $headerArray, array $possibleNames): ?int
    {
        // تطبيع الأسماء المحتملة التي نبحث عنها
        $normalizedPossibleNames = array_map(function ($name) {
            return strtolower(str_replace([' ', '_', '-'], '', trim($name)));
        }, $possibleNames);

        // المرور على العناوين الفعلية من الملف
        foreach ($headerArray as $index => $headerName) {
            // تطبيع العنوان الحالي من الملف
            $normalizedHeaderName = strtolower(str_replace([' ', '_', '-'], '', trim($headerName)));
            // التحقق إذا كان العنوان الحالي موجوداً في قائمة الأسماء المحتملة
            if (in_array($normalizedHeaderName, $normalizedPossibleNames)) {
                return $index; // إرجاع الموقع (index) للعمود
            }
        }
        return null; // لم يتم العثور على العمود
    }

    private function findInstructor($identifier)
    {
        if (empty(trim($identifier))) {
            return null;
        }

        // 1. محاولة البحث بالرقم الوظيفي (instructor_no) أولاً
        // نفترض أن الرقم الوظيفي قد يحتوي على حروف وأرقام
        $instructor = Instructor::where('instructor_no', trim($identifier))->first();
        if ($instructor) {
            return $instructor;
        }

        // 2. إذا لم يتم العثور عليه بالرقم، حاول البحث بالاسم
        // (مع تطبيع الاسم وتجاهل الدرجة العلمية المحتملة بين قوسين)
        $nameToSearch = trim($identifier);
        if (preg_match('/\(.+\)/u', $nameToSearch, $matches)) {
            $nameToSearch = trim(str_replace($matches[0], '', $nameToSearch));
        }
        $normalizedName = $this->normalizeArabicStringForSearch($nameToSearch);


        // البحث في instructor_name
        $instructor = Instructor::whereRaw('REPLACE(LOWER(instructor_name), "", "") LIKE ?', ["%{$normalizedName}%"])->first();
        // dd([
        //     'identifier' => $identifier,
        //     'instructor' => $instructor,
        //     'nameToSearch' => $nameToSearch,
        //     'normalizedName' => $normalizedName,
        // ]);
        if ($instructor) {
            return $instructor;
        }
        // dd('KKKK');

        // 3. (اختياري) البحث في اسم المستخدم المرتبط (user.name) إذا لم يتم العثور عليه في instructor_name
        // هذا يتطلب أن تكون علاقة user محملة أو تقوم بـ join
        // للتبسيط الآن، سنكتفي بالبحث في instructor_no و instructor_name
        /*
        $instructor = Instructor::whereHas('user', function ($query) use ($normalizedName) {
            $query->whereRaw('REPLACE(LOWER(name), " ", "") LIKE ?', ["%{$normalizedName}%"]);
        })->first();
        if ($instructor) {
            return $instructor;
        }
        */

        return null; // لم يتم العثور على المدرس
    }

    /**
     * Normalize Arabic string for searching (remove Hamza, Al, spaces, tolower).
     */
    private function normalizeArabicStringForSearch($string): string
    {
        if (is_null($string)) return '';
        // $string = str_replace(['أ', 'إ', 'آ'], 'ا', $string); // توحيد الهمزات إلى ألف
        // $string = str_replace('ى', 'ي', $string); // ألف مقصورة إلى ياء
        // $string = str_replace('ة', 'ه', $string); // تاء مربوطة إلى هاء
        // $string = preg_replace('/^ال/u', '', $string); // إزالة "ال" التعريف
        // $string = strtolower(preg_replace('/\s+/u', '', trim($string))); // إزالة كل الفراغات وتحويل لحروف صغيرة
        $string = strtolower($string); // إزالة كل الفراغات وتحويل لحروف صغيرة
        return $string;
    }

    /**
     * Find department by ID, Name, or Code.
     */
    private function findDepartment($identifier)
    {
        if (empty(trim($identifier))) return null;
        if (is_numeric($identifier)) return Department::find($identifier);

        $normalizedIdentifier = $this->normalizeArabicStringForSearch($identifier);
        // البحث بالاسم المطبع أو بالكود المطبع
        return Department::where(DB::raw('REPLACE(LOWER(department_name), " ", "")'), 'LIKE', "%{$normalizedIdentifier}%")
            ->orWhere(DB::raw('REPLACE(LOWER(department_no), " ", "")'), 'LIKE', "%{$normalizedIdentifier}%")
            ->first();
    }

    /**
     * Find subject by ID, No, or Name.
     */
    private function findSubject($identifier)
    {
        if (empty(trim($identifier))) return null;
        if (is_numeric($identifier)) return Subject::find($identifier);

        $normalizedIdentifier = $this->normalizeArabicStringForSearch($identifier);
        // البحث برقم المادة (الكود)
        $subject = Subject::where(DB::raw('REPLACE(LOWER(subject_no), "", "")'), $normalizedIdentifier)->first();
        if ($subject) return $subject;

        // البحث باسم المادة
        return Subject::where(DB::raw('REPLACE(LOWER(subject_name), "", "")'), 'LIKE', "%{$normalizedIdentifier}%")->first();
    }

    /**
     * Helper function to generate a unique email.
     */
    private function generateUniqueEmail($baseName, $domain = '@ptc.edu', $instructorNo = null, $counter = 0): string
    {
        $prefix = Str::slug($baseName, '');
        if (empty($prefix) && $instructorNo) {
            $prefix = 'inst' . preg_replace('/[^A-Za-z0-9]/', '', $instructorNo);
        } elseif (empty($prefix) && empty($instructorNo)) {
            $prefix = 'instructor' . Str::random(4);
        }

        $uniquePart = '';
        if ($counter > 0) {
            $uniquePart = str_pad($counter, 2, '0', STR_PAD_LEFT);
        } elseif ($instructorNo && $counter == 0) { // استخدام الرقم الوظيفي في المحاولة الأولى إذا كان العداد صفر
            $uniquePart = preg_replace('/[^A-Za-z0-9]/', '', $instructorNo);
        }

        $emailTry = $prefix . $uniquePart . $domain;
        if (strlen($emailTry) > 255 - strlen($domain) - 1) { // ضمان ألا يتجاوز الطول
            $prefix = substr($prefix, 0, 60); // تقصير
            $emailTry = $prefix . $uniquePart . $domain;
        }

        if (User::where('email', $emailTry)->exists()) {
            return $this->generateUniqueEmail($baseName, $domain, $instructorNo, $counter + 1); // زيادة العداد في المحاولة التالية
        }
        return $emailTry;
    }

    /**
     * Helper function to create a user for an instructor.
     */
    private function createUserForInstructor($name, $email, $roleName = 'instructor', $password = 'DefaultPass123!'): User // كلمة مرور افتراضية أقوى
    {
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => Str::studly($roleName)]);
        }
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Helper to assign a number of available sections of a specific type to an instructor.
     */
    private function assignSectionsToInstructor(Instructor $instructor, PlanSubject $planSubject, $academicYear, $semester, $activityType, $numToAssign, $branch, &$currentTotalHours, $excelRowNumber)
    {
        $assignedCount = 0;
        if ($numToAssign <= 0) return $assignedCount;

        // جلب الشعب المتاحة (غير المعينة)
        $availableSections = Section::where('plan_subject_id', $planSubject->id)
            ->where('academic_year', $academicYear)->where('semester', $semester)
            ->where('activity_type', $activityType)
            ->where(fn($q) => is_null($branch) ? $q->whereNull('branch') : $q->where('branch', $branch))
            ->whereDoesntHave('instructors') // التي ليس لها مدرس معين في instructor_section
            ->orderBy('section_number')->take($numToAssign)->get();

        if ($availableSections->count() < $numToAssign) {
            $this->assignmentSkippedDetails[] = "Row {$excelRowNumber}: Not enough available {$activityType} sections for '{$planSubject->subject->subject_no}'. Needed: {$numToAssign}, Found: {$availableSections->count()} for Instructor '{$instructor->instructor_name}'.";
            // لا نوقف العملية، نحاول تعيين المتاح
        }

        foreach ($availableSections as $section) {
            // تقدير ساعات الشعبة (نحتاج عدد الساعات الأسبوعية للمادة)
            $sectionHours = ($activityType == 'Theory') ? ($planSubject->subject->theoretical_hours ?? 0) : ($planSubject->subject->practical_hours ?? 0);

            if (($currentTotalHours + $sectionHours) > ($instructor->max_weekly_hours ?? 18)) { // 18 كساعات افتراضية قصوى
                $this->assignmentSkippedDetails[] = "Row {$excelRowNumber}: Assignment of {$activityType} Section #{$section->section_number} for '{$planSubject->subject->subject_no}' to '{$instructor->instructor_name}' skipped (Exceeds max weekly hours: {$currentTotalHours} + {$sectionHours} > {$instructor->max_weekly_hours}).";
                continue; // تخطى هذه الشعبة
            }

            try {
                // استخدام attach إذا كانت العلاقة many-to-many معرفة في Instructor model
                // $instructor->sections()->attach($section->id);

                // أو إذا كان هناك عمود instructor_id في جدول sections
                // $section->update(['instructor_id' => $instructor->id]);

                // بما أننا استخدمنا جدول وسيط instructor_section، فالأفضل استخدام attach
                // تأكد أن علاقة sections() معرفة في موديل Instructor
                if (method_exists($instructor, 'sections')) {
                    $instructor->sections()->attach($section->id);
                    $currentTotalHours += $sectionHours;
                    $this->assignmentsCreatedCount++;
                    $assignedCount++;
                } else {
                    Log::error("Instructor model is missing 'sections' relationship for attach/sync.");
                    $this->assignmentSkippedDetails[] = "Row {$excelRowNumber}: System error - Instructor model missing 'sections' relationship.";
                }
            } catch (Exception $e) {
                Log::error("Failed to assign Section ID {$section->id} to Instructor ID {$instructor->id}: " . $e->getMessage());
                $this->assignmentSkippedDetails[] = "Row {$excelRowNumber}: Error assigning {$activityType} Section #{$section->section_number} for '{$planSubject->subject->subject_no}' to '{$instructor->instructor_name}'.";
            }
        }
        return $assignedCount;
    }
}
