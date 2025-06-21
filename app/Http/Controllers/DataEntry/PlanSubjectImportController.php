<?php

namespace App\Http\Controllers\DataEntry;

use Exception;
use App\Models\Plan;
use App\Models\Subject;
use App\Models\PlanSubject;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel; // لاستخدام Excel مباشرة
use Illuminate\Validation\Rule; // قد لا نحتاجه هنا إذا التحقق داخل اللوب

class PlanSubjectImportController extends Controller
{
    private $createdCount = 0;
    private $updatedCount = 0; // إذا احتجنا للتحديث لاحقاً
    private $skippedCount = 0;
    private $alreadyExistedCount = 0;
    private $invalidPlanCount = 0;
    private $processedPlanSubjectKeys = [];
    private $skippedDetails = []; // لتخزين تفاصيل الأسطر المتجاهلة

    /**
     * Handle the import of plan subjects from an Excel file for a specific plan.
     */
    public function handleImport(Request $request, Plan $plan) // Route Model Binding للخطة
    {
        $request->validate([
            'plan_subjects_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        // إعادة تعيين العدادات لكل عملية رفع
        $this->resetCounters();

        try {
            // قراءة الملف مباشرة (بدون كلاس Import منفصل)
            $rows = Excel::toArray(new \stdClass(), $request->file('plan_subjects_excel_file'))[0]; // الحصول على أول شيت

            if (empty($rows)) {
                return redirect()->route('data-entry.plans.manageSubjects', $plan->id)
                    ->with('error', 'The uploaded Excel file is empty or could not be read.');
            }

            // افترض أن الصف الأول هو العناوين
            $header = array_map('strtolower', array_map('trim', array_shift($rows))); // قراءة العناوين وتحويلها لحروف صغيرة وإزالة الفراغات

            // تحديد أسماء الأعمدة المتوقعة (بحروف صغيرة وبدون فراغات لتسهيل المطابقة)
            $expectedPlanKey = 'plan_id'; // أو 'planname' أو 'planid' (سنتعامل مع هذا)
            $expectedLevelKey = 'plan_level';
            $expectedSemesterKey = 'plan_semester';
            $expectedSubjectKey = 'subject_id'; // أو 'subjectno', 'subjectname'

            // البحث عن مواقع الأعمدة بناءً على العناوين
            $planCol = $this->getColumnIndex($header, ['plan_id', 'planid', 'plan name', 'planname', 'plan_name', 'plan_no', 'planno']);
            $levelCol = $this->getColumnIndex($header, ['plan_level', 'planlevel', 'level']);
            $semesterCol = $this->getColumnIndex($header, ['plan_semester', 'plansemester', 'semester']);
            $subjectCol = $this->getColumnIndex($header, ['subject_id', 'subjectid', 'subject_no', 'subjectno', 'subject_name', 'subjectname']);

            // التحقق من وجود الأعمدة الأساسية
            if (is_null($planCol) || is_null($levelCol) || is_null($semesterCol) || is_null($subjectCol)) {
                $missing = [];
                if (is_null($planCol)) $missing[] = "'plan_id' or 'plan_name' or 'plan_no'";
                if (is_null($levelCol)) $missing[] = "'plan_level' or 'planlevel' or 'level'";
                if (is_null($semesterCol)) $missing[] = "'plan_semester' or 'plansemester' or 'semester'";
                if (is_null($subjectCol)) $missing[] = "'subject_id' or 'subject_no' or 'subject_name'";
                return redirect()->route('data-entry.plans.manageSubjects', $plan->id)
                    ->with('error', 'Excel file is missing required columns: ' . implode(', ', $missing) . '. Please check the header row.');
            }

            $currentRowNumber = 1; // لبدء العد من الصف الثاني (بعد العناوين)

            foreach ($rows as $row) {
                $currentRowNumber++; // رقم الصف الفعلي في الإكسل

                // تحويل الصف لمصفوفة بأسماء العناوين الأصلية (للتوافق مع findSubject)
                $rowData = [];
                foreach ($header as $index => $colName) {
                    $rowData[$colName] = $row[$index] ?? null;
                }
                // dd($rowData);

                // 1. تجاهل الصفوف الفارغة تماماً
                if (count(array_filter($row)) == 0) {
                    $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (completely empty row).";
                    $this->skippedCount++;
                    continue;
                }

                $planIdentifier = trim($rowData[$header[$planCol]] ?? null);
                $levelInput = trim($rowData[$header[$levelCol]] ?? null);
                $semesterInput = trim($rowData[$header[$semesterCol]] ?? null);
                $subjectIdentifier = trim($rowData[$header[$subjectCol]] ?? null);


                // تجاهل إذا كانت البيانات الأساسية فارغة
                if (empty($planIdentifier) || empty($subjectIdentifier) || empty($levelInput) || empty($semesterInput)) {
                    $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (missing plan, subject, level, or semester identifier).";
                    $this->skippedCount++;
                    continue;
                }

                // 2. التحقق من تطابق الخطة
                $planIdInFile = null;
                if (is_numeric($planIdentifier)) {
                    $planIdInFile = (int) $planIdentifier;
                } else { /* ... (نفس منطق البحث عن الخطة بالاسم من كلاس Import السابق) ... */
                    // محاولة البحث بالاسم (مع تجاهل حالة الأحرف والسنة إذا كانت مدمجة)
                    // نفترض أن اسم الخطة في الملف قد يكون "Plan Name Only" أو "Plan Name - YYYY"
                    $planNameFromFile = preg_replace('/-\s*\d{4}$/', '', $planIdentifier); // إزالة "- YYYY"
                    $foundPlan = Plan::whereRaw('LOWER(REPLACE(plan_name, " ", "")) LIKE ?', ['%' . strtolower(str_replace(' ', '', $planNameFromFile)) . '%'])
                        ->orWhereRaw('LOWER(REPLACE(plan_no, " ", "")) LIKE ?', ['%' . strtolower(str_replace(' ', '', $planIdentifier)) . '%'])
                        ->first();
                    if ($foundPlan) {
                        $planIdInFile = $foundPlan->id;
                    }
                }

                if (is_null($planIdInFile) || $planIdInFile !== $plan->id) {
                    $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (Plan '{$planIdentifier}' does not match current plan '{$plan->plan_no}').";
                    $this->invalidPlanCount++;
                    continue;
                }

                // 3. معالجة المستوى والفصل
                $level = $this->normalizeLevelOrSemester($levelInput);
                $semester = $this->normalizeLevelOrSemester($semesterInput);

                if (is_null($level) || is_null($semester) || $level < 1 || $semester < 1) {
                    $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (Invalid level '{$levelInput}' or semester '{$semesterInput}').";
                    $this->skippedCount++;
                    continue;
                }

                // 4. معالجة معرّف المادة
                $subject = $this->findSubject($subjectIdentifier);
                if (!$subject) {
                    $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (Subject '{$subjectIdentifier}' not found).";
                    $this->skippedCount++;
                    continue;
                }

                // 5. التحقق من التكرار داخل الملف
                $uniqueKey = $plan->id . '-' . $subject->id . '-' . $level . '-' . $semester;
                if (isset($this->processedPlanSubjectKeys[$uniqueKey])) {
                    $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (Duplicate entry for subject '{$subject->subject_no}' in this level/semester within the file).";
                    $this->skippedCount++;
                    continue;
                }
                $this->processedPlanSubjectKeys[$uniqueKey] = true;

                // 6. التحقق من وجود الربط مسبقاً في قاعدة البيانات
                $existingPlanSubject = PlanSubject::where('plan_id', $plan->id)
                    ->where('subject_id', $subject->id)
                    // ->where('plan_level', $level)
                    // ->where('plan_semester', $semester)
                    ->first();


                if ($existingPlanSubject) {

                    // *************************************************************************
                    if ($existingPlanSubject->plan_level != $level || $existingPlanSubject->plan_semester != $semester) {
                        // dd([
                        //     'planIdentifier' => $planIdentifier,
                        //     'levelInput' => $levelInput,
                        //     'semesterInput' => $semesterInput,
                        //     'subjectIdentifier' => $subjectIdentifier,
                        //     'existingPlanSubject' => $existingPlanSubject,
                        // ]);
                        // تحديث المستوى والفصل
                        $existingPlanSubject->update([
                            'plan_level' => $level,
                            'plan_semester' => $semester,
                        ]);
                        $this->updatedCount++;
                        $skippedDetails[] = "Row {$currentRowNumber} (SubjID:{$subject->id}): Updated level/semester in plan '{$plan->plan_no}'.";
                    } else {
                        // dd($existingPlanSubject);
                        // نفس المادة ونفس المستوى والفصل، لا تغيير
                        $this->skippedCount++;
                        $skippedDetails[] = "Row {$currentRowNumber} (SubjID:{$subject->id}): Already exists in plan '{$plan->plan_no}' at this level/semester.";
                    }
                    // *************************************************************************

                    $this->alreadyExistedCount++;
                    // لا نسجلها في skippedDetails إلا إذا أردت
                } else {
                    PlanSubject::create([
                        'plan_id'       => $plan->id,
                        'subject_id'    => $subject->id,
                        'plan_level'    => $level,
                        'plan_semester' => $semester,
                    ]);
                    $this->createdCount++;
                }
            } // نهاية حلقة الصفوف

            // بناء رسالة النجاح
            $messages = [];
            if ($this->createdCount > 0) $messages[] = "{$this->createdCount} new subject(s) added to plan '{$plan->plan_no}'.";
            if ($this->alreadyExistedCount > 0) $messages[] = "{$this->alreadyExistedCount} subject(s) were already assigned and unchanged.";
            if ($this->invalidPlanCount > 0) $messages[] = "{$this->invalidPlanCount} row(s) were skipped (did not match current plan).";
            if ($this->skippedCount > 0) $messages[] = "{$this->skippedCount} other row(s) were skipped (empty or invalid data).";

            if (empty($messages)) {
                return redirect()->route('data-entry.plans.manageSubjects', $plan->id)->with('info', 'Excel file processed. No changes made or no valid data found.');
            } else {
                return redirect()->route('data-entry.plans.manageSubjects', $plan->id)->with('success', implode(' ', $messages))->with('skipped_details', $this->skippedDetails);
            }
        } catch (Exception $e) {
            Log::error('Plan Subjects Excel Import Failed for Plan ID ' . $plan->id . ': ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->route('data-entry.plans.manageSubjects', $plan->id)
                ->with('error', 'An error occurred during Excel import: ' . $e->getMessage());
        }
    }

    /**
     * Helper function to get column index by trying multiple possible header names.
     */
    private function getColumnIndex(array $header, array $possibleNames)
    {
        foreach ($possibleNames as $name) {
            $name = strtolower(str_replace(' ', '', $name)); // تطبيع الاسم للبحث
            $index = array_search($name, array_map(fn($h) => strtolower(str_replace(' ', '', $h)), $header));
            if ($index !== false) {
                return $index;
            }
        }
        return null;
    }

    /**
     * Helper function to normalize level/semester input.
     */
    private function normalizeLevelOrSemester($input)
    {
        if (is_numeric($input)) {
            return (int) $input;
        }
        $inputLower = strtolower(trim($input));
        $map = [
            'أولى' => 1,
            'سنة أولى' => 1,
            'سنه أولى' => 1,
            'سنة اولى' => 1,
            'سنه اولى' => 1,
            'اولى' => 1,
            'first' => 1,
            'First' => 1,
            '1st' => 1,
            'one' => 1,
            '1' => 1,
            'ثانية' => 2,
            'ثانيه' => 2,
            'second' => 2,
            '2nd' => 2,
            'two' => 2,
            '2' => 2,
            'ثانية' => 2,
            'سنة ثانية' => 2,
            'سنه ثانية' => 2,
            'سنة ثانيه' => 2,
            'سنه ثانيه' => 2,
            'ثانيه' => 2,
            'second' => 2,
            'Second' => 2,
            '2nd' => 2,
            'two' => 2,
            '2' => 2,
            'ثالثة' => 3,
            'ثالثه' => 3,
            'third' => 3,
            '3rd' => 3,
            'three' => 3,
            '3' => 3,
            'ثالثة' => 3,
            'سنة ثانية' => 3,
            'سنه ثالثة' => 3,
            'سنة ثالثه' => 3,
            'سنه ثالثه' => 3,
            'ثانيه' => 3,
            'third' => 3,
            'Third' => 3,
            '3rd' => 3,
            'three' => 3,
            '3' => 3,
            'رابعة' => 4,
            'رابعه' => 4,
            'fourth' => 4,
            '4th' => 4,
            'four' => 4,
            '4' => 4,
            'رابعة' => 4,
            'سنة رابعة' => 4,
            'سنه رابعة' => 4,
            'سنة رابعه' => 4,
            'سنه رابعه' => 4,
            'رابعه' => 4,
            'fourth' => 4,
            'Fourth' => 4,
            '4th' => 4,
            'four' => 4,
            '4' => 4,
            // أضف المزيد حسب الحاجة للفصول والمستويات
            'فصل أول' => 1,
            'فصل اول' => 1,
            'أول' => 1,
            'اول' => 1,
            'first' => 1,
            'First' => 1,
            '1' => 1,
            'فصل ثاني' => 2,
            'فصل ثانى' => 2,
            'ثاني' => 2,
            'ثانى' => 2,
            'second' => 2,
            'Second' => 2,
            '2' => 2,
            'فصل ثالث' => 3,
            'ثالث' => 3,
            'صيفي' => 3,
            'summer' => 3,
            'third' => 3,
            'Third' => 3,
            '3' => 3,
        ];
        return $map[$inputLower] ?? (is_numeric($inputLower) ? (int)$inputLower : null);
    }

    /**
     * Helper function to find subject by ID, No, or Name.
     */
    private function findSubject($identifier)
    {
        if (is_numeric($identifier)) {
            return Subject::find($identifier);
        }
        // البحث بالكود (رقم المادة) - تجاهل حالة الأحرف
        $subject = Subject::whereRaw('LOWER(subject_no) = ?', [strtolower($identifier)])->first();
        if ($subject) {
            return $subject;
        }
        // البحث بالاسم - تجاهل حالة الأحرف والهمزات والفراغات الزائدة
        $normalizedIdentifier = $this->normalizeArabicString($identifier);
        return Subject::whereRaw('REPLACE(LOWER(subject_name), " ", "") LIKE ?', ['%' . str_replace(' ', '', $normalizedIdentifier) . '%'])
            ->orWhereRaw('LOWER(subject_name) LIKE ?', ['%' . $normalizedIdentifier . '%']) // بحث أوسع قليلاً
            ->first(); // قد تحتاج لمنطق أدق إذا كان هناك أسماء متشابهة جداً
    }

    /**
     * Helper function to normalize Arabic string (remove Alif variants, normalize spaces).
     */
    private function normalizeArabicString($string)
    {
        $string = preg_replace('/[أإآ]/u', 'ا', $string); // توحيد الألفات
        $string = preg_replace('/\s+/u', ' ', trim($string)); // إزالة الفراغات الزائدة
        return strtolower($string);
    }

    private function resetCounters()
    {
        $this->createdCount = 0;
        $this->updatedCount = 0;
        $this->skippedCount = 0;
        $this->alreadyExistedCount = 0;
        $this->invalidPlanCount = 0;
        $this->processedPlanSubjectKeys = [];
        $this->skippedDetails = [];
    }

        // =============================================
    //             API Controller Methods
    // =============================================

    /**
     * API: Handle the import of plan subjects from an Excel file for a specific plan.
     */
    public function handleApiImport(Request $request, Plan $plan) // Route Model Binding للخطة
    {
        // 1. التحقق من وجود الملف وصيغته (باستخدام Validator لـ API)
        $validator = Validator::make($request->all(), [
            'plan_subjects_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422); // Unprocessable Entity
        }

        $this->resetCounters(); // إعادة تعيين العدادات

        try {
            $rows = Excel::toArray(new \stdClass(), $request->file('plan_subjects_excel_file'))[0];

            if (count($rows) <= 1) { // إذا كان الملف فارغاً أو يحتوي على العناوين فقط
                return response()->json(['success' => false, 'message' => 'Uploaded Excel file is empty or contains only a header row.'], 400);
            }

            $header = array_map('strtolower', array_map('trim', array_shift($rows)));
            $planCol = $this->getColumnIndex($header, ['plan_id', 'planid', 'plan name', 'planname', 'plan_name', 'plan_no', 'planno']);
            $levelCol = $this->getColumnIndex($header, ['plan_level', 'planlevel', 'level']);
            $semesterCol = $this->getColumnIndex($header, ['plan_semester', 'plansemester', 'semester']);
            $subjectCol = $this->getColumnIndex($header, ['subject_id', 'subjectid', 'subject_no', 'subjectno', 'subject_name', 'subjectname']);

            if (is_null($planCol) || is_null($levelCol) || is_null($semesterCol) || is_null($subjectCol)) {
                $missing = [];
                if (is_null($planCol)) $missing[] = "'plan_id' or 'plan_name' or 'plan_no'";
                if (is_null($levelCol)) $missing[] = "'plan_level' or 'planlevel' or 'level'";
                if (is_null($semesterCol)) $missing[] = "'plan_semester' or 'plansemester' or 'semester'";
                if (is_null($subjectCol)) $missing[] = "'subject_id' or 'subject_no' or 'subject_name'";
                return response()->json(['success' => false, 'message' => 'Excel file is missing required columns: ' . implode(', ', $missing)], 400);
            }

            $currentRowNumber = 1;
            foreach ($rows as $row) {
                $currentRowNumber++;
                $rowData = [];
                foreach ($header as $index => $colName) {
                    $rowData[$colName] = $row[$index] ?? null;
                }

                if (count(array_filter($row)) == 0) {
                    $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (empty).";
                    $this->skippedCount++;
                    continue;
                }

                $planIdentifier = trim($rowData[$header[$planCol]] ?? null);
                $levelInput = trim($rowData[$header[$levelCol]] ?? null);
                $semesterInput = trim($rowData[$header[$semesterCol]] ?? null);
                $subjectIdentifier = trim($rowData[$header[$subjectCol]] ?? null);

                if (empty($planIdentifier) || empty($subjectIdentifier) || empty($levelInput) || empty($semesterInput)) {
                    $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (missing core identifiers).";
                    $this->skippedCount++;
                    continue;
                }

                $planIdInFile = null;
                if (is_numeric($planIdentifier)) {
                    $planIdInFile = (int) $planIdentifier;
                } else {
                    $planNameFromFile = preg_replace('/-\s*\d{4}$/', '', $planIdentifier);
                    $foundPlan = Plan::whereRaw('LOWER(REPLACE(plan_name, " ", "")) LIKE ?', ['%' . strtolower(str_replace(' ', '', $planNameFromFile)) . '%'])
                        ->orWhereRaw('LOWER(REPLACE(plan_no, " ", "")) LIKE ?', ['%' . strtolower(str_replace(' ', '', $planIdentifier)) . '%'])
                        ->first();
                    if ($foundPlan) {
                        $planIdInFile = $foundPlan->id;
                    }
                }
                if (is_null($planIdInFile) || $planIdInFile !== $plan->id) {
                    $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (Plan '{$planIdentifier}' in file does not match current plan '{$plan->plan_no}').";
                    $this->invalidPlanCount++;
                    continue;
                }

                $level = $this->normalizeLevelOrSemester($levelInput);
                $semester = $this->normalizeLevelOrSemester($semesterInput);
                if (is_null($level) || is_null($semester) || $level < 1 || $semester < 1) {
                    $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (Invalid level '{$levelInput}' or semester '{$semesterInput}').";
                    $this->skippedCount++;
                    continue;
                }

                $subject = $this->findSubject($subjectIdentifier);
                if (!$subject) {
                    $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (Subject '{$subjectIdentifier}' not found).";
                    $this->skippedCount++;
                    continue;
                }

                $uniqueKey = $plan->id . '-' . $subject->id . '-' . $level . '-' . $semester;
                if (isset($this->processedPlanSubjectKeys[$uniqueKey])) {
                    $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (Duplicate entry for subject '{$subject->subject_no}' in this L/S within the file).";
                    $this->skippedCount++;
                    continue;
                }
                $this->processedPlanSubjectKeys[$uniqueKey] = true;

                $existingPlanSubject = PlanSubject::where('plan_id', $plan->id)
                    ->where('subject_id', $subject->id)
                    // ->where('plan_level', $level) // ** التحقق من المستوى والفصل للربط الموجود **
                    // ->where('plan_semester', $semester)
                    ->first();
                if ($existingPlanSubject) {

                    // *************************************************************************
                    if ($existingPlanSubject->plan_level != $level || $existingPlanSubject->plan_semester != $semester) {
                        // dd([
                        //     'planIdentifier' => $planIdentifier,
                        //     'levelInput' => $levelInput,
                        //     'semesterInput' => $semesterInput,
                        //     'subjectIdentifier' => $subjectIdentifier,
                        //     'existingPlanSubject' => $existingPlanSubject,
                        // ]);
                        // تحديث المستوى والفصل
                        $existingPlanSubject->update([
                            'plan_level' => $level,
                            'plan_semester' => $semester,
                        ]);
                        $this->updatedCount++;
                        $skippedDetails[] = "Row {$currentRowNumber} (SubjID:{$subject->id}): Updated level/semester in plan '{$plan->plan_no}'.";
                    } else {
                        // dd($existingPlanSubject);
                        // نفس المادة ونفس المستوى والفصل، لا تغيير
                        $this->skippedCount++;
                        $skippedDetails[] = "Row {$currentRowNumber} (SubjID:{$subject->id}): Already exists in plan '{$plan->plan_no}' at this level/semester.";
                    }
                    // *************************************************************************

                    $this->alreadyExistedCount++;
                    // لا نسجلها في skippedDetails إلا إذا أردت، لأنها ليست خطأ بالضرورة
                    // $this->skippedDetails[] = "Row {$currentRowNumber}: Subject ID {$subject->id} already exists in Plan ID {$plan->id} at L{$level}S{$semester}.";
                } else {
                    PlanSubject::create([
                        'plan_id'       => $plan->id,
                        'subject_id'    => $subject->id,
                        'plan_level'    => $level,
                        'plan_semester' => $semester,
                    ]);
                    $this->createdCount++;
                }
            }

            $summary = [];
            if ($this->createdCount > 0) $summary['new_subjects_added_to_plan'] = $this->createdCount;
            if ($this->alreadyExistedCount > 0) $summary['subjects_already_assigned_and_unchanged'] = $this->alreadyExistedCount;
            if ($this->invalidPlanCount > 0) $summary['rows_skipped_due_to_plan_mismatch'] = $this->invalidPlanCount;
            if ($this->skippedCount > 0) $summary['other_rows_skipped_or_duplicate_in_file'] = $this->skippedCount;

            if (empty($summary) && empty(array_filter($this->skippedDetails, fn($detail) => !Str::contains($detail, 'already exists')))) {
                return response()->json([
                    'success' => true,
                    'message' => 'Excel file processed. No new subjects added or all data already matched/skipped.',
                    'summary' => $summary,
                    'skipped_details' => $this->skippedDetails
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => "Plan subjects import processed for plan '{$plan->plan_no}'.",
                'summary' => $summary,
                'skipped_details' => $this->skippedDetails
            ], 200);
        } catch (Exception $e) {
            Log::error('API Plan Subjects Excel Import Failed for Plan ID ' . $plan->id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during Excel import: ' . $e->getMessage()
            ], 500);
        }
    }
}
