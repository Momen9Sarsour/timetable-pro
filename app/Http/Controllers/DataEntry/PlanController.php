<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Imports\PlanSubjectsImport;
use App\Models\Plan;
use App\Models\Department;
use App\Models\PlanSubject;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Exception;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;

class PlanController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================
    // الدوال (index, store, update, destroy, manageSubjects, addSubject, removeSubject)
    // تبقى كما هي في الكود السابق الذي أرسلته - تأكد أنها موجودة وصحيحة.

    public function index()
    { /* ... كود index للويب مع pagination ... */
        try {
            $plans = Plan::with('department:id,department_name') // تحديد حقول القسم
                ->latest('id')
                ->paginate(15);
            $departments = Department::orderBy('department_name')->get(['id', 'department_name']); // تحديد الحقول
            return view('dashboard.data-entry.plans', compact('plans', 'departments'));
        } catch (Exception $e) {
            Log::error('Error fetching academic plans: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load academic plans.');
        }
    }
    public function store(Request $request)
    { /* ... كود store للويب ... */
        $validatedData = $request->validate(['plan_no' => 'required|string|max:50|unique:plans,plan_no', 'plan_name' => 'required|string|max:255', 'year' => 'required|integer|digits:4|min:2000', 'plan_hours' => 'required|integer|min:1', 'department_id' => 'required|integer|exists:departments,id', 'is_active' => 'sometimes|boolean',]);
        $data = $validatedData;
        $data['is_active'] = $request->has('is_active');
        try {
            Plan::create($data);
            return redirect()->route('data-entry.plans.index')->with('success', 'Academic Plan created successfully.');
        } catch (Exception $e) {
            Log::error('Plan Creation Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create academic plan.')->withInput();
        }
    }
    public function update(Request $request, Plan $plan)
    { /* ... كود update للويب ... */
        $validatedData = $request->validate(['plan_no' => 'required|string|max:50|unique:plans,plan_no,' . $plan->id, 'plan_name' => 'required|string|max:255', 'year' => 'required|integer|digits:4|min:2000', 'plan_hours' => 'required|integer|min:1', 'department_id' => 'required|integer|exists:departments,id', 'is_active' => 'sometimes|boolean',]);
        $data = $validatedData;
        $data['is_active'] = $request->has('is_active');
        try {
            $plan->update($data);
            return redirect()->route('data-entry.plans.index')->with('success', 'Academic Plan updated successfully.');
        } catch (Exception $e) {
            Log::error('Plan Update Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update academic plan.')->withInput();
        }
    }
    public function destroy(Plan $plan)
    { /* ... كود destroy للويب ... */
        // if ($plan->planSubjectEntries()->exists()) {
        //     return redirect()->route('data-entry.plans.index')->with('error', 'Cannot delete plan. It has subjects assigned.');
        // }
        try {
            Log::warning("Force deleting plan ID: {$plan->id} and its subjects.");
            // استخدام delete() على العلاقة لحذف كل سجلات plan_subjects المرتبطة
            $plan->planSubjectEntries()->delete();
            Log::info("Associated subjects for plan ID: {$plan->id} deleted.");
            $plan->delete();
            return redirect()->route('data-entry.plans.index')->with('success', 'Academic Plan deleted successfully.');
        } catch (Exception $e) {
            Log::error('Plan Deletion Failed: ' . $e->getMessage());
            return redirect()->route('data-entry.plans.index')->with('error', 'Failed to delete academic plan.');
        }
    }

    // --- الدوال المساعدة للرفع بالجملة ---
    private function normalizeArabicString($string)
    {
        if (is_null($string)) return null;
        $search = array('أ', 'إ', 'آ', 'ى', 'ة');
        $replace = array('ا', 'ا', 'ا', 'ي', 'ه');
        return str_replace($search, $replace, $string);
    }

    private function findRelatedId($modelClass, $nameColumn, $valueFromExcel, &$skippedDetails, $rowNum, $attributeFriendlyName, $searchByIdColumn = 'id', $searchByNoColumn = null, $noValueFromExcel = null)
    {
        if (is_numeric($valueFromExcel)) {
            $recordById = $modelClass::where($searchByIdColumn, $valueFromExcel)->first();
            if ($recordById) {
                return $recordById->id;
            }
        }
        if (!empty($valueFromExcel)) {
            $normalizedValue = $this->normalizeArabicString(strtolower(trim($valueFromExcel)));
            $recordByName = $modelClass::all()->first(function ($item) use ($nameColumn, $normalizedValue) {
                return $this->normalizeArabicString(strtolower(trim($item->$nameColumn))) === $normalizedValue;
            });
            if ($recordByName) {
                return $recordByName->id;
            }
        }
        if ($searchByNoColumn && !empty($noValueFromExcel)) {
            $recordByNo = $modelClass::where($searchByNoColumn, $noValueFromExcel)->first();
            if ($recordByNo) return $recordByNo->id;
        }
        $skippedDetails[] = "Row {$rowNum}: {$attributeFriendlyName} '{$valueFromExcel}' (or No: '{$noValueFromExcel}') not found or invalid. This row will be skipped.";
        return null;
    }

    /**
     * Handle bulk upload of academic plans from Excel file for Web.
     */
    public function bulkUpload(Request $request)
    {
        $request->validate([
            'plan_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [], ['plan_excel_file' => 'Excel file']);

        try {
            $rows = Excel::toCollection(collect(), $request->file('plan_excel_file'))->first();
            if ($rows->isEmpty() || $rows->count() <= 1) {
                return redirect()->route('data-entry.plans.index')->with('error', 'Uploaded Excel file is empty or has no data rows.');
            }

            $createdCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $skippedDetails = [];
            $processedPlanNos = collect();
            $header = $rows->first()->map(fn($item) => strtolower(str_replace([' ', '-'], '_', $item ?? '')));
            $dataRows = $rows->slice(1);

            foreach ($dataRows as $rowKey => $rowArray) {
                $row = $header->combine($rowArray->map(fn($val) => trim($val ?? '')));
                $currentRowNumber = $rowKey + 2;

                $planNo = $row->get('plan_no');
                $planName = $row->get('plan_name');

                if (empty($planNo) && empty($planName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped (empty plan_no & name).";
                    continue;
                }
                if (empty($planNo)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber} (Name: {$planName}): Skipped (missing plan_no).";
                    continue;
                }
                if (empty($planName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber} (No: {$planNo}): Skipped (missing plan_name).";
                    continue;
                }

                if ($processedPlanNos->contains($planNo)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped (duplicate plan_no '{$planNo}' in this file).";
                    continue;
                }

                $departmentValueFromExcel = $row->get('department_id'); // Could be ID, Name, or department_no
                $departmentNoFromExcelIfText = (!is_numeric($departmentValueFromExcel) && !empty($departmentValueFromExcel)) ? $departmentValueFromExcel : null;

                $departmentId = $this->findRelatedId(
                    Department::class,
                    'department_name',
                    $departmentValueFromExcel,
                    $skippedDetails,
                    $currentRowNumber,
                    'Department',
                    'id',
                    'department_no',
                    $departmentNoFromExcelIfText
                );

                if (is_null($departmentId) && !empty($departmentValueFromExcel)) {
                    $skippedCount++;
                    continue;
                }
                if (is_null($departmentId) && empty($departmentValueFromExcel)) { // Department is required
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber} (PlanNo: {$planNo}): Skipped - Department is required.";
                    continue;
                }

                $isActiveValue = $row->get('is_active', '1');
                $isActive = filter_var($isActiveValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if (is_null($isActive)) {
                    $isActive = (strtolower(trim($isActiveValue)) === 'active' || $isActiveValue === '1' || strtolower(trim($isActiveValue)) === 'yes');
                }

                $dataToValidate = [
                    'plan_no' => $planNo,
                    'plan_name' => $planName,
                    'year' => $row->get('year'),
                    'plan_hours' => $row->get('plan_hours'),
                    'department_id' => $departmentId,
                    'is_active' => $isActive,
                ];

                $validator = Validator::make($dataToValidate, [
                    'plan_no' => 'required|string|max:50',
                    'plan_name' => 'required|string|max:255',
                    'year' => 'required|integer|digits:4|min:2000|max:' . (date('Y') + 10),
                    'plan_hours' => 'required|integer|min:1|max:300',
                    'department_id' => 'required|integer|exists:departments,id',
                    'is_active' => 'required|boolean',
                ]);

                if ($validator->fails()) {
                    $skippedCount++;
                    $errors = implode('; ', $validator->errors()->all());
                    $skippedDetails[] = "Row {$currentRowNumber} (PlanNo: {$planNo}): Skipped - Validation: {$errors}";
                    continue;
                }
                $validatedData = $validator->validated();

                $plan = Plan::updateOrCreate(['plan_no' => $validatedData['plan_no']], $validatedData);
                if ($plan->wasRecentlyCreated) {
                    $createdCount++;
                } elseif ($plan->wasChanged()) {
                    $updatedCount++;
                } else {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber} (PlanNo: {$planNo}): Data already up-to-date.";
                }
                $processedPlanNos->push($planNo);
            }

            $message = "Academic Plans bulk upload processed. ";
            if ($createdCount > 0) $message .= "{$createdCount} new created. ";
            if ($updatedCount > 0) $message .= "{$updatedCount} updated. ";
            if ($skippedCount > 0) $message .= "{$skippedCount} skipped. ";
            if (!empty($skippedDetails)) {
                session()->flash('skipped_details', $skippedDetails);
            }
            return redirect()->route('data-entry.plans.index')->with('success', trim($message));
        } catch (Exception $e) {
            Log::error('Plan Bulk Upload Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->route('data-entry.plans.index')->with('error', 'Upload error: ' . $e->getMessage());
        }
    }

    public function manageSubjects(Plan $plan)
    { /* ... كود manageSubjects للويب ... */
        try {
            $allSubjects = Subject::orderBy('subject_name')->get(['id', 'subject_no', 'subject_name']);
            $addedSubjectIds = $plan->planSubjectEntries()->pluck('subject_id')->toArray();
            return view('dashboard.data-entry.plan-subjects-manage', compact('plan', 'allSubjects', 'addedSubjectIds'));
        } catch (Exception $e) {
            Log::error("Error loading manage subjects view for Plan ID {$plan->id}: " . $e->getMessage());
            return redirect()->route('data-entry.plans.index')->with('error', 'Could not load plan subject management page.');
        }
    }

    public function addSubject(Request $request, Plan $plan, $level, $semester)
    { /* ... كود addSubject للويب ... */
        $validatedData = $request->validate(['subject_id' => ['required', 'integer', 'exists:subjects,id', Rule::unique('plan_subjects')->where(fn($q) => $q->where('plan_id', $plan->id)->where('plan_level', $level)->where('plan_semester', $semester))],], ['subject_id.unique' => 'Subject already added.', 'subject_id.*' => 'Invalid subject selected.']);
        try {
            PlanSubject::create(['plan_id' => $plan->id, 'subject_id' => $validatedData['subject_id'], 'plan_level' => $level, 'plan_semester' => $semester]);
            return redirect()->route('data-entry.plans.manageSubjects', $plan->id)->with('success', 'Subject added successfully.');
        } catch (Exception $e) {
            Log::error("Error adding subject {$request->subject_id} to plan {$plan->id} (L{$level}S{$semester}): " . $e->getMessage());
            return redirect()->route('data-entry.plans.manageSubjects', $plan->id)->with('error', 'Failed to add subject.')->withInput(['subject_id' => $request->subject_id]);
        }
    }

    public function removeSubject(Plan $plan, PlanSubject $planSubject)
    { /* ... كود removeSubject للويب ... */
        if ($planSubject->plan_id !== $plan->id) {
            abort(404);
        }
        try {
            $subjectName = optional($planSubject->subject)->subject_name ?? 'N/A';
            $planSubject->delete();
            return redirect()->route('data-entry.plans.manageSubjects', $plan->id)->with('success', "Subject '{$subjectName}' removed.");
        } catch (Exception $e) {
            Log::error("Error removing plan subject ID {$planSubject->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to remove subject.');
        }
    }

    // **************************************** الرفع بالاكسل ****************************************************

    // /**
    //  * Helper function to find Plan ID by various identifiers.
    //  */
    // private function findPlanIdSmart($identifier, &$skippedDetails, $rowNum)
    // {
    //     if (empty($identifier)) {
    //         // إذا كان عمود الخطة فارغاً، سنفترض أنه يقصد الخطة الحالية التي يعمل عليها المستخدم (plan_id_context)
    //         // ولكن هذا المنطق يجب أن يكون خارج هذه الدالة المساعدة. هنا، إذا كان فارغاً، لا يوجد تطابق.
    //         // $skippedDetails[] = "Row {$rowNum}: Plan identifier is empty."; // لا نسجل خطأ هنا إذا كان اختيارياً
    //         return null; // أو نرجع قيمة خاصة للإشارة أنه فارغ
    //     }
    //     // 1. Try as ID
    //     if (is_numeric($identifier)) {
    //         $plan = Plan::find($identifier);
    //         if ($plan) return $plan->id;
    //     }
    //     // 2. Try as plan_no
    //     $plan = Plan::where('plan_no', $identifier)->first();
    //     if ($plan) return $plan->id;

    //     // 3. Try as plan_name (case-insensitive, hamza-insensitive)
    //     $normalizedIdentifier = $this->normalizeArabicString(strtolower(trim($identifier)));
    //     $plan = Plan::all()->first(function ($item) use ($normalizedIdentifier) {
    //         return $this->normalizeArabicString(strtolower(trim($item->plan_name))) === $normalizedIdentifier;
    //     });
    //     if ($plan) return $plan->id;

    //     $skippedDetails[] = "Row {$rowNum}: Plan '{$identifier}' not found by ID, No, or Name. This subject entry will be skipped.";
    //     return null;
    // }

    // /**
    //  * Helper function to find Subject ID by various identifiers.
    //  */

    // private function findSubjectIdSmart($identifier, &$skippedDetails, $rowNum)
    // {
    //     if (empty($identifier)) {
    //         $skippedDetails[] = "Row {$rowNum}: Subject identifier is empty. Record skipped.";
    //         return null;
    //     }
    //     // 1. Try as ID
    //     if (is_numeric($identifier)) {
    //         $subject = Subject::find($identifier);
    //         if ($subject) return $subject->id;
    //     }
    //     // 2. Try as subject_no
    //     $subject = Subject::where('subject_no', $identifier)->first();
    //     if ($subject) return $subject->id;

    //     // 3. Try as subject_name (case-insensitive, hamza-insensitive)
    //     $normalizedIdentifier = $this->normalizeArabicString(strtolower(trim($identifier)));
    //     $subject = Subject::all()->first(function ($item) use ($normalizedIdentifier) {
    //         return $this->normalizeArabicString(strtolower(trim($item->subject_name))) === $normalizedIdentifier;
    //     });
    //     if ($subject) return $subject->id;

    //     $skippedDetails[] = "Row {$rowNum}: Subject '{$identifier}' not found by ID, No, or Name. Record skipped.";
    //     return null;
    // }

    // /**
    //  * Helper function to parse plan_level.
    //  */
    // private function parsePlanLevel($value, &$skippedDetails, $rowNum)
    // {
    //     if (is_numeric($value) && $value >= 1 && $value <= 6) return (int)$value; // Max 6 levels
    //     $normalized = $this->normalizeArabicString(strtolower(trim($value)));
    //     $mapping = ['اولى' => 1, 'ثانية' => 2, 'ثالثة' => 3, 'رابعة' => 4, 'خامسة' => 5, 'سادسة' => 6, 'first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4, 'fifth' => 5, 'sixth' => 6, 'سنة اولى' => 1, 'سنه اولى' => 1];
    //     if (isset($mapping[$normalized])) return $mapping[$normalized];
    //     $skippedDetails[] = "Row {$rowNum}: Invalid Plan Level '{$value}'. Skipped."; return null;
    // }

    // /**
    //  * Helper function to parse plan_semester.
    //  */
    // private function parsePlanSemester($value, &$skippedDetails, $rowNum)
    // {
    //     if (is_numeric($value) && in_array((int)$value, [1, 2, 3])) return (int)$value;
    //     $normalized = $this->normalizeArabicString(strtolower(trim($value)));
    //     $mapping = ['اول' => 1, 'ثاني' => 2, 'ثالث' => 3, 'صيفي' => 3, 'first' => 1, 'second' => 2, 'summer' => 3, 'فصل اول' => 1, 'فصل ثاني' => 2];
    //     if (isset($mapping[$normalized])) return $mapping[$normalized];
    //     $skippedDetails[] = "Row {$rowNum}: Invalid Plan Semester '{$value}'. Skipped."; return null;
    // }

    // /**
    //  * Handle bulk upload of subjects to a specific plan from Excel file for Web.
    //  */
    // public function bulkUploadPlanSubjects(Request $request, Plan $plan_context) // Route Model Binding للخطة الحالية
    // {
    //     $request->validate([
    //         'plan_subjects_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
    //     ]);

    //     try {
    //         $rows = Excel::toCollection(collect(), $request->file('plan_subjects_excel_file'))->first();
    //         if ($rows->isEmpty() || $rows->count() <= 1) { /* ... error handling ... */ }

    //         $createdCount = 0; $updatedCount = 0; $skippedCount = 0;
    //         $skippedDetails = []; $processedEntriesInFile = collect();

    //         $header = $rows->first()->map(fn ($item) => strtolower(str_replace([' ', '-'], '_', $item ?? '')));
    //         $dataRows = $rows->slice(1);

    //         foreach ($dataRows as $rowKey => $rowArray) {
    //             $row = $header->combine($rowArray->map(fn($val) => trim($val ?? '')));
    //             $currentRowNumber = $rowKey + 2;

    //             // 1. تجاهل الأسطر الفارغة (نحتاج على الأقل معرّف مادة ومستوى وفصل)
    //             $subjectIdentifierExcel = $row->get('subject_id'); // اسم العمود من الإكسل
    //             $planLevelExcel = $row->get('plan_level');
    //             $planSemesterExcel = $row->get('plan_semester');

    //             if (empty($subjectIdentifierExcel) || empty($planLevelExcel) || empty($planSemesterExcel)) {
    //                 $skippedCount++; $skippedDetails[] = "Row {$currentRowNumber}: Skipped (missing subject, level, or semester)."; continue;
    //             }

    //             // 2. (اختياري) التحقق من الخطة إذا كان عمود plan_id موجوداً في الإكسل
    //             $planIdentifierExcel = $row->get('plan_id'); // اسم العمود من الإكسل
    //             if (!empty($planIdentifierExcel)) {
    //                 $planIdFromFile = $this->findPlanIdSmart($planIdentifierExcel, $skippedDetails, $currentRowNumber);
    //                 if (is_null($planIdFromFile)) { $skippedCount++; continue; } // تم تسجيل الخطأ
    //                 if ($planIdFromFile !== $plan_context->id) {
    //                     $skippedCount++; $skippedDetails[] = "Row {$currentRowNumber}: Subject '{$subjectIdentifierExcel}' belongs to plan '{$planIdentifierExcel}', but you are managing plan '{$plan_context->plan_no}'. Skipped."; continue;
    //                 }
    //             }
    //             // إذا لم يكن عمود plan_id موجوداً أو كان فارغاً، نفترض أن الإدخال للخطة الحالية $plan_context

    //             // 3. تحويل معرّف المادة إلى ID
    //             $subjectId = $this->findSubjectIdSmart($subjectIdentifierExcel, $skippedDetails, $currentRowNumber);
    //             if (is_null($subjectId)) { $skippedCount++; continue; }

    //             // 4. تحويل المستوى والفصل
    //             $validPlanLevel = $this->parsePlanLevel($planLevelExcel, $skippedDetails, $currentRowNumber);
    //             if (is_null($validPlanLevel)) { $skippedCount++; continue; }
    //             $validPlanSemester = $this->parsePlanSemester($planSemesterExcel, $skippedDetails, $currentRowNumber);
    //             if (is_null($validPlanSemester)) { $skippedCount++; continue; }

    //             // 5. التحقق من التكرار داخل الملف
    //             $fileEntryKey = "{$plan_context->id}-{$subjectId}-{$validPlanLevel}-{$validPlanSemester}";
    //             if ($processedEntriesInFile->contains($fileEntryKey)) {
    //                 $skippedCount++; $skippedDetails[] = "Row {$currentRowNumber} (SubjID:{$subjectId}): Skipped (duplicate entry in this file for this plan/level/semester)."; continue;
    //             }

    //             // 6. البحث عن المادة في الخطة الحالية
    //             $existingPlanSubject = PlanSubject::where('plan_id', $plan_context->id)
    //                                              ->where('subject_id', $subjectId)
    //                                              ->first();
    //             if ($existingPlanSubject) {
    //                 // المادة موجودة بالفعل في الخطة، تحقق إذا كان المستوى/الفصل مختلفاً
    //                 if ($existingPlanSubject->plan_level != $validPlanLevel || $existingPlanSubject->plan_semester != $validPlanSemester) {
    //                     // تحديث المستوى والفصل
    //                     $existingPlanSubject->update([
    //                         'plan_level' => $validPlanLevel,
    //                         'plan_semester' => $validPlanSemester,
    //                     ]);
    //                     $updatedCount++;
    //                     $skippedDetails[] = "Row {$currentRowNumber} (SubjID:{$subjectId}): Updated level/semester in plan '{$plan_context->plan_no}'.";
    //                 } else {
    //                     // نفس المادة ونفس المستوى والفصل، لا تغيير
    //                     $skippedCount++; $skippedDetails[] = "Row {$currentRowNumber} (SubjID:{$subjectId}): Already exists in plan '{$plan_context->plan_no}' at this level/semester.";
    //                 }
    //             } else {
    //                 // المادة غير موجودة في الخطة، قم بإضافتها
    //                 PlanSubject::create([
    //                     'plan_id' => $plan_context->id,
    //                     'subject_id' => $subjectId,
    //                     'plan_level' => $validPlanLevel,
    //                     'plan_semester' => $validPlanSemester,
    //                 ]);
    //                 $createdCount++;
    //             }
    //             $processedEntriesInFile->push($fileEntryKey);
    //         }

    //         $message = "Plan Subjects bulk upload for plan '{$plan_context->plan_no}' processed. ";
    //         if ($createdCount > 0) $message .= "{$createdCount} new subjects added. ";
    //         if ($updatedCount > 0) $message .= "{$updatedCount} subjects' level/semester updated. ";
    //         if ($skippedCount > 0) $message .= "{$skippedCount} rows skipped/no change. ";
    //         if (!empty($skippedDetails)) { session()->flash('skipped_details', $skippedDetails); }
    //         return redirect()->route('data-entry.plans.manageSubjects', $plan_context->id)->with('success', trim($message));

    //     } catch (Exception $e) {
    //         Log::error('Plan Subjects Bulk Upload Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
    //         return redirect()->route('data-entry.plans.manageSubjects', ['plan' => $plan_context])->with('error', 'Upload error: ' . $e->getMessage());
    //         // return redirect()->route('data-entry.plans.manageSubjects', $plan_context->id)->with('error', 'Upload error: ' . $e->getMessage());
    //         // return redirect()->route('data-entry.plans.manageSubjects', $plan->id)->with('error', 'Upload error: ' . $e->getMessage());
    //     }
    // }


    /**
     * Handle the import of plan subjects from an Excel file for a specific plan.
     */
    public function importSubjectsExcel(Request $request, Plan $plan) // Route Model Binding للخطة
    {
        $request->validate([
            'plan_subjects_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120', // 5MB max
        ]);

        try {
            // تمرير كائن الخطة الحالية للـ Importer
            $import = new PlanSubjectsImport($plan);

            Excel::import($import, $request->file('plan_subjects_excel_file'));

            $createdCount = $import->getCreatedCount();
            $updatedCount = $import->getUpdatedCount(); // سنستخدمه إذا كان هناك تحديث لحقول أخرى في plan_subjects
            $skippedCount = $import->getSkippedCount();
            $alreadyExistedCount = $import->getAlreadyExistedCount(); // عدد المواد التي كانت موجودة ولم تتغير
            $invalidPlanCount = $import->getInvalidPlanCount(); // عدد الصفوف التي لم تتطابق مع الخطة الحالية

            $messages = [];
            if ($createdCount > 0) $messages[] = "{$createdCount} new subject(s) added to the plan.";
            if ($updatedCount > 0) $messages[] = "{$updatedCount} existing subject assignment(s) updated."; // (إذا كان هناك تحديث)
            if ($alreadyExistedCount > 0) $messages[] = "{$alreadyExistedCount} subject(s) were already assigned and unchanged.";
            if ($invalidPlanCount > 0) $messages[] = "{$invalidPlanCount} row(s) were skipped as they did not match the current plan or had other critical errors.";
            if ($skippedCount > 0) $messages[] = "{$skippedCount} additional row(s) were skipped (e.g., empty or header).";


            if (empty($messages)) {
                $flashMessage = 'Excel file processed, but no changes were made or no valid data found.';
                return redirect()->route('data-entry.plans.manageSubjects', $plan->id)
                                 ->with('info', $flashMessage);
            } else {
                $flashMessage = "Plan subjects import processed: " . implode(' ', $messages);
                 return redirect()->route('data-entry.plans.manageSubjects', $plan->id)
                                  ->with('success', $flashMessage);
            }


        } catch (ExcelValidationException $e) {
             $failures = $e->failures();
             $errorMessages = [];
             foreach ($failures as $failure) {
                 $errorMessages[] = "Row " . $failure->row() . ": " . implode(', ', $failure->errors()) . " (Value: '" . ($failure->values()[$failure->attribute()] ?? 'N/A') . "')";
             }
             Log::warning('Plan Subjects Excel Import Validation Failures for Plan ID ' . $plan->id .': ', $failures);
             return redirect()->route('data-entry.plans.manageSubjects', $plan->id)
                              ->with('error', 'Import failed due to validation errors in the file. Please check details below.')
                              ->with('import_excel_errors', $errorMessages);

        } catch (Exception $e) {
            Log::error('Plan Subjects Excel Import Failed for Plan ID ' . $plan->id . ': ' . $e->getMessage());
            return redirect()->route('data-entry.plans.manageSubjects', $plan->id)
                             ->with('error', 'An error occurred during the Excel import: ' . $e->getMessage());
        }
    }

    /**
     * Handle bulk upload of subjects to a specific plan from Excel file for Web.
     */
    // public function bulkUploadPlanSubjects(Request $request, Plan $plan) // Route Model Binding للـ Plan
    // {
    //     $request->validate([
    //         'plan_subjects_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
    //     ], [], ['plan_subjects_excel_file' => 'Excel file for plan subjects']);

    //     try {
    //         $rows = Excel::toCollection(collect(), $request->file('plan_subjects_excel_file'))->first();
    //         if ($rows->isEmpty() || $rows->count() <= 1) {
    //             return redirect()->route('data-entry.plans.manageSubjects', $plan->id)->with('error', 'Uploaded file is empty or has no data rows.');
    //         }

    //         $createdCount = 0; $skippedCount = 0;
    //         $skippedDetails = []; $processedEntries = collect(); // لتتبع الإدخالات (plan_id, subject_id, level, semester)

    //         $header = $rows->first()->map(fn ($item) => strtolower(str_replace([' ', '-'], '_', $item ?? '')));
    //         $dataRows = $rows->slice(1);

    //         foreach ($dataRows as $rowKey => $rowArray) {
    //             $row = $header->combine($rowArray->map(fn($val) => trim($val ?? '')));
    //             $currentRowNumber = $rowKey + 2;

    //             $subjectIdentifier = $row->get('subject_id'); // اسم العمود المتوقع للمادة
    //             $planLevel = $row->get('plan_level');
    //             $planSemester = $row->get('plan_semester');

    //             // dd($subjectIdentifier);
    //             if (empty($subjectIdentifier) || empty($planLevel) || empty($planSemester)) {
    //                 $skippedCount++; $skippedDetails[] = "Row {$currentRowNumber}: Skipped (missing subject, level, or semester)."; continue;
    //             }

    //             // تحويل معرّف المادة إلى ID
    //             $subjectId = $this->findSubjectIdSmart($subjectIdentifier, $skippedDetails, $currentRowNumber);
    //             if (is_null($subjectId)) { $skippedCount++; continue; } // تم تسجيل الخطأ في findSubjectIdSmart

    //             // التحقق من صحة المستوى والفصل
    //             $levelSemesterValidator = Validator::make(
    //                 ['plan_level' => $planLevel, 'plan_semester' => $planSemester],
    //                 ['plan_level' => 'required|integer|min:1|max:6', 'plan_semester' => 'required|integer|min:1|max:3']
    //             );
    //             if ($levelSemesterValidator->fails()) {
    //                 $skippedCount++; $errors = implode('; ', $levelSemesterValidator->errors()->all());
    //                 $skippedDetails[] = "Row {$currentRowNumber} (Subject ID: {$subjectId}): Skipped - Invalid level/semester: {$errors}"; continue;
    //             }
    //             $validPlanLevel = $levelSemesterValidator->validated()['plan_level'];
    //             $validPlanSemester = $levelSemesterValidator->validated()['plan_semester'];


    //             // بناء مفتاح فريد للتحقق من التكرار داخل الملف أو في قاعدة البيانات
    //             $uniqueKeyInFile = "{$plan->id}-{$subjectId}-{$validPlanLevel}-{$validPlanSemester}";
    //             $uniqueKeyInDB = [
    //                 'plan_id' => $plan->id,
    //                 'subject_id' => $subjectId,
    //                 'plan_level' => $validPlanLevel,
    //                 'plan_semester' => $validPlanSemester,
    //             ];

    //             if ($processedEntries->contains($uniqueKeyInFile)) {
    //                 $skippedCount++; $skippedDetails[] = "Row {$currentRowNumber}: Skipped (duplicate entry in this file)."; continue;
    //             }

    //             // بما أننا لا نحدث، فقط نضيف إذا لم يكن موجوداً
    //             $existingEntry = PlanSubject::where($uniqueKeyInDB)->first();
    //             if ($existingEntry) {
    //                 $skippedCount++; $skippedDetails[] = "Row {$currentRowNumber} (Subject ID: {$subjectId}): Entry already exists in this plan/level/semester.";
    //                 $processedEntries->push($uniqueKeyInFile); // اعتبره معالجاً
    //                 continue;
    //             }

    //             PlanSubject::create($uniqueKeyInDB);
    //             $createdCount++;
    //             $processedEntries->push($uniqueKeyInFile);
    //         }

    //         $message = "Plan Subjects bulk upload processed. ";
    //         if ($createdCount > 0) $message .= "{$createdCount} new subject assignments created for this plan. ";
    //         if ($skippedCount > 0) $message .= "{$skippedCount} rows skipped. ";
    //         if (!empty($skippedDetails)) { session()->flash('skipped_details', $skippedDetails); }
    //         return redirect()->route('data-entry.plans.manageSubjects', $plan->id)->with('success', trim($message));

    //     } catch (Exception $e) {
    //         Log::error('Plan Subjects Bulk Upload Failed for Plan ID '.$plan->id.': ' . $e->getMessage());
    //         return redirect()->route('data-entry.plans.manageSubjects', $plan->id)->with('error', 'Upload error: ' . $e->getMessage());
    //     }
    // }




    // =============================================
    //             API Controller Methods
    // =============================================

    /**
     * Display a listing of the academic plans (API).
     * عرض قائمة الخطط للـ API (بدون Pagination حالياً)
     */
    public function apiIndex(Request $request)
    {
        try {
            $query = Plan::with('department:id,department_name'); // تحميل القسم مع حقول محددة

            // (اختياري) فلترة بسيطة
            if ($request->has('department_id')) {
                $query->where('department_id', $request->department_id);
            }
            if ($request->boolean('active')) { // للبحث عن الخطط الفعالة فقط ?active=true
                $query->where('is_active', true);
            }
            if ($request->boolean('inactive')) { // للبحث عن غير الفعالة ?inactive=true
                $query->where('is_active', false);
            }
            if ($request->has('year')) {
                $query->where('year', $request->year);
            }
            if ($request->has('q')) { // بحث بالرقم أو الاسم
                $searchTerm = $request->q;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('plan_no', 'like', "%{$searchTerm}%")
                        ->orWhere('plan_name', 'like', "%{$searchTerm}%");
                });
            }

            // --- جلب كل الخطط (الحالة الحالية) ---
            $plans = $query->latest('id') // الترتيب بالأحدث
                ->get();

            // --- كود الـ Pagination (معطل) ---
            /*
            $perPage = $request->query('per_page', 15);
            $plansPaginated = $query->latest('id')->paginate($perPage);
            return response()->json([
                'success' => true,
                'data' => $plansPaginated->items(),
                'pagination' => [ 'total' => $plansPaginated->total(), ... ]
            ], 200);
            */

            return response()->json(['success' => true, 'data' => $plans], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching plans: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * Store a newly created academic plan from API request.
     * تخزين خطة جديدة قادمة من طلب API
     */
    public function apiStore(Request $request)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'plan_no' => 'required|string|max:50|unique:plans,plan_no',
            'plan_name' => 'required|string|max:255',
            'year' => 'required|integer|digits:4|min:2000',
            'plan_hours' => 'required|integer|min:1',
            'department_id' => 'required|integer|exists:departments,id',
            'is_active' => 'sometimes|boolean', // API يمكنه إرسال true/false مباشرة
        ]);

        // 2. Prepare Data (Handle is_active default if not sent)
        $data = $validatedData;
        $data['is_active'] = $request->boolean('is_active'); // استخدام boolean() للتعامل مع true/false/'1'/'0'

        // 3. Add to Database
        try {
            $plan = Plan::create($data);
            $plan->load('department:id,department_name'); // تحميل القسم لعرضه
            // 4. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $plan,
                'message' => 'Academic Plan created successfully.'
            ], 201);
        } catch (Exception $e) {
            Log::error('API Plan Creation Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create plan.'], 500);
        }
    }

    /**
     * Display the specified academic plan (API).
     * عرض خطة محددة للـ API
     */
    public function apiShow(Plan $plan)
    {
        try {
            // تحميل القسم والمواد المرتبطة (مع تحديد الحقول)
            $plan->load([
                'department:id,department_name',
                // جلب مواد الخطة مرتبة حسب المستوى ثم الفصل
                'planSubjectEntries' => function ($query) {
                    $query->orderBy('plan_level')->orderBy('plan_semester');
                },
                'planSubjectEntries.subject:id,subject_no,subject_name' // تحميل تفاصيل المادة
            ]);
            return response()->json(['success' => true, 'data' => $plan], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching plan details: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not load plan details.'], 500);
        }
    }

    /**
     * Update the specified academic plan from API request.
     * تحديث خطة محددة قادمة من طلب API
     */
    public function apiUpdate(Request $request, Plan $plan)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'plan_no' => ['sometimes', 'required', 'string', 'max:50', 'unique:plans,plan_no,' . $plan->id],
            'plan_name' => 'sometimes|required|string|max:255',
            'year' => 'sometimes|required|integer|digits:4|min:2000',
            'plan_hours' => 'sometimes|required|integer|min:1',
            'department_id' => 'sometimes|required|integer|exists:departments,id',
            'is_active' => 'sometimes|boolean',
        ]);

        // 2. Prepare Data for Update
        $data = $validatedData;
        // تحديث is_active فقط إذا تم إرسالها
        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        // 3. Update Database
        try {
            $plan->update($data);
            $plan->load('department:id,department_name'); // تحميل القسم بعد التحديث
            // 4. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $plan,
                'message' => 'Academic Plan updated successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API Plan Update Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update plan.'], 500);
        }
    }

    /**
     * Remove the specified academic plan from API request.
     * حذف خطة محددة قادمة من طلب API
     */
    public function apiDestroy(Plan $plan)
    {
        // التحقق من وجود مواد مرتبطة
        if ($plan->planSubjectEntries()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete plan. It has subjects assigned.'], 409);
        }

        // 1. Delete
        try {
            $plan->delete();
            // 2. Return Success JSON Response
            return response()->json(['success' => true, 'message' => 'Academic Plan deleted successfully.'], 200);
        } catch (Exception $e) {
            Log::error('API Plan Deletion Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete plan.'], 500);
        }
    }

    // --- API Methods for Plan Subjects (Add/Remove) ---

    /**
     * Add a subject to a plan via API.
     * (Note: Level and semester come from the request body here)
     */
    public function apiAddSubject(Request $request, Plan $plan)
    {
        $validatedData = $request->validate([
            'subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
                Rule::unique('plan_subjects')->where(function ($query) use ($plan, $request) {
                    return $query->where('plan_id', $plan->id)
                        ->where('plan_level', $request->input('plan_level'))
                        ->where('plan_semester', $request->input('plan_semester'));
                }),
            ],
            'plan_level' => 'required|integer|min:1',
            'plan_semester' => 'required|integer|min:1',
        ], ['subject_id.unique' => 'Subject already added to this level/semester.']);

        try {
            $planSubject = PlanSubject::create([
                'plan_id' => $plan->id,
                'subject_id' => $validatedData['subject_id'],
                'plan_level' => $validatedData['plan_level'],
                'plan_semester' => $validatedData['plan_semester'],
            ]);
            $planSubject->load('subject:id,subject_no,subject_name'); // Load subject details
            return response()->json(['success' => true, 'data' => $planSubject, 'message' => 'Subject added to plan.'], 201);
        } catch (Exception $e) {
            Log::error("API Error adding subject {$request->subject_id} to plan {$plan->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to add subject.'], 500);
        }
    }

    /**
     * Remove a subject from a plan via API.
     * (Uses Route Model Binding for PlanSubject)
     */
    public function apiRemoveSubject(Plan $plan, PlanSubject $planSubject)
    {
        if ($planSubject->plan_id !== $plan->id) {
            return response()->json(['success' => false, 'message' => 'Subject association not found in this plan.'], 404);
        }

        try {
            $planSubject->delete();
            return response()->json(['success' => true, 'message' => 'Subject removed from plan.'], 200);
        } catch (Exception $e) {
            Log::error("API Error removing plan subject ID {$planSubject->id} from plan {$plan->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to remove subject.'], 500);
        }
    }


    /**
     * Handle bulk upload of academic plans from Excel file via API.
     */
    public function apiBulkUpload(Request $request)
    {
        // 1. التحقق من الملف المرفوع
        $validator = Validator::make($request->all(), [
            'plan_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120', // 5MB max
        ], [], ['plan_excel_file' => 'Excel file']);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'File validation failed.',
                'errors' => $validator->errors()
            ], 422); // Unprocessable Entity
        }

        try {
            // 2. قراءة البيانات من ملف الإكسل
            $rows = Excel::toCollection(collect(), $request->file('plan_excel_file'))->first();

            if ($rows->isEmpty() || $rows->count() <= 1) { // يجب أن يحتوي على صف عناوين وصف بيانات واحد على الأقل
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded Excel file is empty or contains no data rows after the header.'
                ], 400); // Bad Request
            }

            $createdCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $skippedDetails = [];
            $processedPlanNos = collect(); // لتتبع أرقام الخطط التي تمت معالجتها من الملف الحالي

            // الحصول على الصف الأول كعناوين (بتحويلها لـ snake_case)
            $header = $rows->first()->map(fn($item) => strtolower(str_replace([' ', '-'], '_', $item ?? '')));
            // إزالة صف العناوين من البيانات
            $dataRows = $rows->slice(1);

            foreach ($dataRows as $rowKey => $rowArray) {
                // تحويل الصف الحالي لمصفوفة باستخدام العناوين كمفاتيح مع trim للقيم
                $row = $header->combine($rowArray->map(fn($val) => trim($val ?? '')));
                $currentRowNumber = $rowKey + 2; // رقم الصف الفعلي في الإكسل (يبدأ من 1، والهيدر هو الصف 1)

                $planNo = $row->get('plan_no');
                $planName = $row->get('plan_name');

                // 1. تجاهل الأسطر الفارغة تماماً (إذا كان رقم الخطة واسمها فارغين)
                if (empty($planNo) && empty($planName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped because both plan_no and plan_name are empty.";
                    continue;
                }
                // تجاهل إذا كان رقم الخطة أو اسم الخطة مفقوداً (اعتبارهم حقولاً أساسية)
                if (empty($planNo)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber} (Plan Name: {$planName}): Skipped because plan_no is missing.";
                    continue;
                }
                if (empty($planName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber} (Plan No: {$planNo}): Skipped because plan_name is missing.";
                    continue;
                }

                // 4. فحص التكرار داخل الملف نفسه بناءً على plan_no
                if ($processedPlanNos->contains($planNo)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped duplicate plan_no '{$planNo}' from within this file.";
                    continue;
                }

                // جلب department_id (قد يكون ID أو اسم أو رقم قسم من الإكسل)
                $departmentValueFromExcel = $row->get('department_id'); // اسم العمود كما هو في الإكسل (بعد التحويل لـ snake_case)
                $departmentNoFromExcelIfText = (!is_numeric($departmentValueFromExcel) && !empty($departmentValueFromExcel)) ? $departmentValueFromExcel : null;

                $departmentId = $this->findRelatedId(
                    Department::class,
                    'department_name', // العمود للبحث بالاسم في جدول departments
                    $departmentValueFromExcel,
                    $skippedDetails, // مصفوفة لتمرير تفاصيل التجاهل
                    $currentRowNumber,
                    'Department', // اسم ودي للخاصية
                    'id', // عمود الـ ID في جدول departments
                    'department_no', // عمود الرقم في جدول departments للبحث به كخيار ثانٍ
                    $departmentNoFromExcelIfText // قيمة رقم القسم إذا كانت القيمة من الإكسل نصية
                );

                // إذا كان القسم مطلوباً ولم يتم العثور عليه، تجاهل الصف
                if (is_null($departmentId) && !empty($departmentValueFromExcel)) { // كان هناك قيمة ولكن لم يتم العثور على تطابق
                    $skippedCount++;
                    // findRelatedId ستكون قد أضافت التفاصيل لـ skippedDetails
                    continue;
                }
                if (is_null($departmentId) && empty($departmentValueFromExcel)) { // حقل القسم فارغ في الإكسل (وهو مطلوب)
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber} (PlanNo: {$planNo}): Skipped - Department information is required and was not found or is missing in the Excel file.";
                    continue;
                }

                // معالجة is_active
                $isActiveValue = $row->get('is_active', '1'); // قيمة افتراضية '1' إذا لم يكن العمود موجوداً أو فارغاً
                $isActive = filter_var($isActiveValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if (is_null($isActive)) { // إذا لم تكن القيمة true/false/1/0/on/off
                    $isActive = (strtolower(trim($isActiveValue)) === 'active' || $isActiveValue === '1' || strtolower(trim($isActiveValue)) === 'yes');
                }


                // تجهيز البيانات للتحقق والحفظ
                $dataToValidateAndSave = [
                    'plan_no' => $planNo,
                    'plan_name' => $planName,
                    'year' => $row->get('year'),
                    'plan_hours' => $row->get('plan_hours'),
                    'department_id' => $departmentId, // الـ ID الرقمي للقسم
                    'is_active' => $isActive,
                ];

                // التحقق من صحة البيانات الرقمية الأخرى
                $rowValidator = Validator::make($dataToValidateAndSave, [
                    'plan_no' => 'required|string|max:50', // لا نتحقق من unique هنا لأننا سنستخدم updateOrCreate
                    'plan_name' => 'required|string|max:255',
                    'year' => 'required|integer|digits:4|min:2000|max:' . (date('Y') + 10), // سنة منطقية
                    'plan_hours' => 'required|integer|min:1|max:300', // ساعات منطقية
                    'department_id' => 'required|integer|exists:departments,id',
                    'is_active' => 'required|boolean',
                ]);

                if ($rowValidator->fails()) {
                    $skippedCount++;
                    $errors = implode('; ', $rowValidator->errors()->all());
                    $skippedDetails[] = "Row {$currentRowNumber} (PlanNo: {$planNo}): Skipped - Validation errors: {$errors}";
                    continue;
                }
                $validatedData = $rowValidator->validated(); // البيانات النظيفة

                // 3. البحث عن الخطة في قاعدة البيانات وتحديثها أو إنشاؤها
                $plan = Plan::updateOrCreate(
                    ['plan_no' => $validatedData['plan_no']], // الشرط للبحث (أو الإنشاء إذا لم يوجد)
                    $validatedData // البيانات للتحديث أو الإنشاء
                );

                if ($plan->wasRecentlyCreated) {
                    $createdCount++;
                } elseif ($plan->wasChanged()) { // للتحقق إذا تم تحديث أي حقل فعلاً
                    $updatedCount++;
                } else {
                    // لم يتم إنشاؤه ولم يتغير (موجود بنفس البيانات)
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber} (PlanNo: {$planNo}): Data already up-to-date in the system.";
                }
                $processedPlanNos->push($planNo);
            }

            // 4. بناء الاستجابة
            $summaryMessage = "Academic Plans bulk upload processed via API.";
            $responseData = [
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'skipped_details' => $skippedDetails,
            ];

            return response()->json([
                'success' => true,
                'message' => $summaryMessage,
                'data' => $responseData
            ], 200); // OK

        } catch (Exception $e) {
            Log::error('API Plan Bulk Upload Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during API bulk upload.',
                'error_details' => $e->getMessage() // للمطور
            ], 500); // Internal Server Error
        }
    }
}
