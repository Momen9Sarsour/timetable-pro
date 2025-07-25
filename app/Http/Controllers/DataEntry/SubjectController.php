<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Imports\SubjectsImport; // *** تفعيل هذا ***
use Maatwebsite\Excel\Facades\Excel; // *** تفعيل هذا ***
use Maatwebsite\Excel\Validators\ValidationException; // *** لاستقبال أخطاء التحقق من Excel ***
use App\Models\Department;
use App\Models\Subject;
use App\Models\SubjectCategory;
use App\Models\SubjectType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // اختياري
use Illuminate\Validation\Rule;
use Exception;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================

    /**
     * Display a listing of the subjects (Web View) with Pagination.
     * عرض قائمة المواد لصفحة الويب مع تقسيم الصفحات (الأحدث أولاً)
     */
    public function index()
    {
        try {
            // جلب المواد مرتبة بالأحدث مع العلاقات وتقسيم الصفحات
            $subjects = Subject::with(['subjectType', 'subjectCategory', 'department'])
                ->latest('id') // Order by newest first based on ID
                ->paginate(15); // Paginate results

            // جلب البيانات للقوائم المنسدلة
            $subjectTypes = SubjectType::orderBy('subject_type_name')->get();
            $subjectCategories = SubjectCategory::orderBy('subject_category_name')->get();
            $departments = Department::orderBy('department_name')->get();

            return view('dashboard.data-entry.subjects', compact('subjects', 'subjectTypes', 'subjectCategories', 'departments'));
        } catch (Exception $e) {
            Log::error('Error fetching subjects for web view: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load subjects.');
        }
    }

    /**
     * Store a newly created subject from web request.
     * تخزين مادة جديدة قادمة من طلب ويب
     */
    public function store(Request $request)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'subject_no' => 'required|string|max:20|unique:subjects,subject_no',
            'subject_name' => 'required|string|max:255',
            'subject_load' => 'required|integer|min:0',
            'theoretical_hours' => 'required|integer|min:0',
            'practical_hours' => 'required|integer|min:0',
            'load_theoretical_section' => 'nullable|integer|min:1', // nullable للسماح بالقيمة الافتراضية
            'load_practical_section' => 'nullable|integer|min:1',
            'subject_type_id' => 'required|integer|exists:subjects_types,id',
            'subject_category_id' => 'required|integer|exists:subjects_categories,id',
            'department_id' => 'required|integer|exists:departments,id',
        ]);

        // 2. Prepare Data (validatedData جاهزة)
        $data = $validatedData;
        if (!isset($validatedData['load_theoretical_section']) || is_null($validatedData['load_theoretical_section'])) {
            // القيمة الافتراضية من قاعدة البيانات ستُستخدم إذا كان الحقل nullable
            // أو يمكنك تعيينها هنا صراحة إذا أردت:
            $dataToCreate['load_theoretical_section'] = 50;
        }
        if (!isset($validatedData['load_practical_section']) || is_null($validatedData['load_practical_section'])) {
            $dataToCreate['load_practical_section'] = 25;
        }
        // 3. Add to Database
        try {
            Subject::create($data);
            // 4. Redirect
            return redirect()->route('data-entry.subjects.index') // تأكد من اسم الروت
                ->with('success', 'Subject created successfully.');
        } catch (Exception $e) {
            Log::error('Subject Creation Failed (Web): ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create subject.')
                ->withInput();
        }
    }

    /**
     * Update the specified subject from web request.
     * تحديث مادة محددة قادمة من طلب ويب
     */
    public function update(Request $request, Subject $subject)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'subject_no' => 'required|string|max:20|unique:subjects,subject_no,' . $subject->id,
            'subject_name' => 'required|string|max:255',
            'subject_load' => 'required|integer|min:0',
            'theoretical_hours' => 'required|integer|min:0',
            'practical_hours' => 'required|integer|min:0',
            'load_theoretical_section' => 'nullable|integer|min:1',
            'load_practical_section' => 'nullable|integer|min:1',
            'subject_type_id' => 'required|integer|exists:subjects_types,id',
            'subject_category_id' => 'required|integer|exists:subjects_categories,id',
            'department_id' => 'required|integer|exists:departments,id',
        ]);

        // 2. Prepare Data (validatedData جاهزة)
        $data = $validatedData;
        // إذا أرسل المستخدم قيمة فارغة، يجب أن نخزن NULL وليس string فارغ (إذا كان الحقل يقبل NULL)
        $dataToUpdate['load_theoretical_section'] = $request->filled('load_theoretical_section') ? $request->input('load_theoretical_section') : null;
        $dataToUpdate['load_practical_section'] = $request->filled('load_practical_section') ? $request->input('load_practical_section') : null;

        // 3. Update Database
        try {
            $subject->update($data);
            // 4. Redirect
            return redirect()->route('data-entry.subjects.index') // تأكد من اسم الروت
                ->with('success', 'Subject updated successfully.');
        } catch (Exception $e) {
            Log::error('Subject Update Failed (Web): ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update subject.')
                ->withInput();
        }
    }

    /**
     * Remove the specified subject from web request.
     * حذف مادة محددة قادمة من طلب ويب
     */
    public function destroy(Subject $subject)
    {
        // (اختياري) التحقق من الارتباطات
        if ($subject->planSubjectEntries()->exists()) {
            return redirect()->route('data-entry.subjects.index') // تأكد من اسم الروت
                ->with('error', 'Cannot delete subject. It is included in academic plans.');
        }

        // 1. Delete from Database
        try {
            $subject->delete();
            // 2. Redirect
            return redirect()->route('data-entry.subjects.index') // تأكد من اسم الروت
                ->with('success', 'Subject deleted successfully.');
        } catch (Exception $e) {
            Log::error('Subject Deletion Failed (Web): ' . $e->getMessage());
            return redirect()->route('data-entry.subjects.index') // تأكد من اسم الروت
                ->with('error', 'Failed to delete subject.');
        }
    }

    /**
     * Handle bulk upload (Web).
     * معالجة الرفع بالجملة (معطل مؤقتاً)
     */
    // public function bulkUpload(Request $request)
    // {
    //     //  Log::info('Bulk upload endpoint hit (Web) - Feature disabled.'); // تسجيل معلومة
    //     //  return redirect()->route('data-entry.subjects.index')
    //     //                   ->with('info', 'Bulk upload feature is currently disabled.');

    //      // 1. التحقق من وجود الملف وصيغته
    //      $request->validate([
    //         'subject_file' => 'required|file|mimes:xlsx,xls,csv|max:5120', // زيادة الحد الأقصى للحجم إلى 5MB مثلاً
    //     ]);

    //     // 2. محاولة استيراد الملف باستخدام كلاس Import
    //     try {
    //         Excel::import(new SubjectsImport, $request->file('subject_file'));

    //         // 3. إعادة التوجيه مع رسالة نجاح
    //         return redirect()->route('data-entry.subjects.index')
    //                          ->with('success', 'Subjects imported successfully!');

    //     } catch (ValidationException $e) {
    //         // 3. التعامل مع أخطاء التحقق داخل الملف (التي تم تعريفها في SubjectsImport)
    //          $failures = $e->failures(); // الحصول على مصفوفة الأخطاء
    //          $errorMessages = [];
    //          foreach ($failures as $failure) {
    //              // بناء رسالة خطأ لكل صف خاطئ
    //              $errorMessages[] = "Row " . $failure->row() . ": " . implode(', ', $failure->errors()) . " (Value: '" . $failure->values()[$failure->attribute()] . "')";
    //          }
    //          Log::warning('Subject Bulk Upload Validation Failures: ', $failures);
    //          // إعادة التوجيه مع رسالة خطأ عامة وعرض الأخطاء التفصيلية
    //          return redirect()->back()
    //                           ->with('error', 'Import failed due to validation errors in the file. Please check the details below.')
    //                           ->with('import_errors', $errorMessages); // إرسال مصفوفة الأخطاء للـ view

    //     } catch (Exception $e) {
    //         // 3. التعامل مع أخطاء أخرى (مثل مشكلة في قراءة الملف)
    //         Log::error('Subject Bulk Upload Failed (General): ' . $e->getMessage());
    //         return redirect()->back()
    //                          ->with('error', 'An error occurred during the upload process. Please ensure the file format is correct and try again.');
    //     }
    // }
    // public function bulkUpload(Request $request)
    // {
    //     $request->validate([
    //         'subject_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
    //     ]);

    //     $importer = new SubjectsImport(); // إنشاء instance من الـ Importer

    //     try {
    //         Excel::import($importer, $request->file('subject_file'));

    //         // الحصول على الأخطاء (إن وجدت) من الـ Importer
    //         $errors = $importer->getErrors();
    //         $importedCount = $importer->getImportedRowCount();

    //         if (!empty($errors)) {
    //             // إذا كان هناك أخطاء validation
    //             $errorMessages = [];
    //             foreach ($errors as $rowIndex => $rowErrors) {
    //                 $errorMessages[] = "Row " . $rowIndex . ": " . implode(', ', $rowErrors);
    //             }
    //             return redirect()->back()
    //                 ->with('error', "Import completed with errors. {$importedCount} rows imported successfully. Please check the details below.")
    //                 ->with('import_errors', $errorMessages);
    //         }

    //         // إذا لم يكن هناك أخطاء
    //         return redirect()->route('data-entry.subjects.index')
    //             ->with('success', "Subjects imported successfully! ({$importedCount} rows added).");
    //     } catch (Exception $e) {
    //         // التعامل مع أخطاء قراءة الملف أو أخطاء فادحة أخرى
    //         Log::error('Subject Bulk Upload Failed (General): ' . $e->getMessage());
    //         return redirect()->back()
    //             ->with('error', 'An critical error occurred during the upload process. Please check the file or contact support. Error: ' . $e->getMessage());
    //     }
    // }

    /**
     * Helper function to normalize Arabic text.
     */
    private function normalizeArabicString($string)
    {
        if (is_null($string)) return null;
        $search = array('أ', 'إ', 'آ', 'ى', 'ة');
        $replace = array('ا', 'ا', 'ا', 'ي', 'ه'); // تطبيع بسيط
        return str_replace($search, $replace, $string);
    }

    /**
     * Helper function to find related ID by name or use ID if numeric.
     */
    private function findRelatedId($modelClass, $nameColumn, $valueFromExcel, &$skippedDetails, $rowNum, $attributeFriendlyName, $searchByIdColumn = 'id')
    {
        if (is_numeric($valueFromExcel)) {
            if ($modelClass::where($searchByIdColumn, $valueFromExcel)->exists()) {
                return (int)$valueFromExcel;
            } else {
                $skippedDetails[] = "Row {$rowNum}: Invalid {$attributeFriendlyName} ID '{$valueFromExcel}'. Record skipped.";
                return null;
            }
        } elseif (!empty($valueFromExcel)) {
            $normalizedValue = $this->normalizeArabicString(strtolower(trim($valueFromExcel)));
            $record = $modelClass::all()->first(function ($item) use ($nameColumn, $normalizedValue) {
                return $this->normalizeArabicString(strtolower(trim($item->$nameColumn))) === $normalizedValue;
            });
            if ($record) {
                return $record->id;
            } else {
                $skippedDetails[] = "Row {$rowNum}: {$attributeFriendlyName} '{$valueFromExcel}' not found. Record skipped.";
                return null;
            }
        }
        // إذا كانت القيمة فارغة ولم تكن مطلوبة، قد نرجع null بدون تسجيل خطأ، أو نسجل خطأ إذا كانت مطلوبة
        // $skippedDetails[] = "Row {$rowNum}: {$attributeFriendlyName} is empty. Record skipped."; // إذا كان الحقل مطلوباً
        return null; // إذا كان الحقل اختيارياً والقيمة فارغة
    }


    /**
     * Handle bulk upload of subjects from Excel file for Web.
     */
    public function bulkUpload(Request $request)
    {
        $request->validate([
            'subject_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [], ['subject_excel_file' => 'Excel file']);

        try {
            $rows = Excel::toCollection(collect(), $request->file('subject_excel_file'))->first();

            if ($rows->isEmpty() || $rows->count() <= 1) {
                return redirect()->route('data-entry.subjects.index')->with('error', 'Uploaded Excel file is empty or has no data rows.');
            }

            $createdCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $skippedDetails = [];
            $processedSubjectNos = collect();

            $header = $rows->first()->map(fn($item) => strtolower(str_replace([' ', '-'], '_', $item ?? '')));
            $dataRows = $rows->slice(1);

            foreach ($dataRows as $rowKey => $rowArray) {
                $row = $header->combine($rowArray->map(fn($val) => trim($val ?? '')));
                $currentRowNumber = $rowKey + 2;

                $subjectNo = $row->get('subject_no');
                $subjectName = $row->get('subject_name');

                if (empty($subjectNo) && empty($subjectName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped (empty subject_no & name).";
                    continue;
                }
                if (empty($subjectNo) || empty($subjectName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped (missing subject_no or name).";
                    continue;
                }
                if ($processedSubjectNos->contains($subjectNo)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped (duplicate subject_no '{$subjectNo}' in file).";
                    continue;
                }

                $subjectTypeId = $this->findRelatedId(SubjectType::class, 'subject_type_name', $row->get('subject_type_id'), $skippedDetails, $currentRowNumber, 'Subject Type');
                if (is_null($subjectTypeId) && !empty($row->get('subject_type_id'))) {
                    $skippedCount++;
                    continue;
                } // إذا كان هناك قيمة ولم يتم العثور عليها

                $subjectCategoryId = $this->findRelatedId(SubjectCategory::class, 'subject_category_name', $row->get('subject_category_id'), $skippedDetails, $currentRowNumber, 'Subject Category');
                if (is_null($subjectCategoryId) && !empty($row->get('subject_category_id'))) {
                    $skippedCount++;
                    continue;
                }

                $departmentId = $this->findRelatedId(Department::class, 'department_name', $row->get('department_id'), $skippedDetails, $currentRowNumber, 'Department');
                if (is_null($departmentId) && !empty($row->get('department_id'))) {
                    $skippedCount++;
                    continue;
                }


                $dataToValidate = [
                    'subject_no' => $subjectNo,
                    'subject_name' => $subjectName,
                    'subject_load' => $row->get('subject_load'),
                    'theoretical_hours' => $row->get('theoretical_hours'),
                    'practical_hours' => $row->get('practical_hours'),
                    'capacity_theoretical_section' => $row->get('capacity_theoretical_section'),
                    'capacity_practical_section' => $row->get('capacity_practical_section'),
                    'subject_type_id' => $subjectTypeId,
                    'subject_category_id' => $subjectCategoryId,
                    'department_id' => $departmentId,
                ];

                $validator = Validator::make($dataToValidate, [
                    'subject_no' => 'required|string|max:20',
                    'subject_name' => 'required|string|max:255',
                    'subject_load' => 'required|integer|min:0',
                    'theoretical_hours' => 'required|integer|min:0',
                    'practical_hours' => 'required|integer|min:0',
                    'capacity_theoretical_section' => 'nullable|integer|min:1',
                    'capacity_practical_section' => 'nullable|integer|min:1',
                    'subject_type_id' => 'required|integer|exists:subjects_types,id',
                    'subject_category_id' => 'required|integer|exists:subjects_categories,id',
                    'department_id' => 'required|integer|exists:departments,id',
                ]);

                if ($validator->fails()) {
                    $skippedCount++;
                    $errors = implode(', ', $validator->errors()->all());
                    $skippedDetails[] = "Row {$currentRowNumber} (SubjectNo: {$subjectNo}): Skipped - Validation: {$errors}";
                    continue;
                }
                $validatedData = $validator->validated();
                // استخدام القيم الافتراضية إذا كانت السعات null بعد التحقق
                $validatedData['capacity_theoretical_section'] = $validatedData['capacity_theoretical_section'] ?? 50;
                $validatedData['capacity_practical_section'] = $validatedData['capacity_practical_section'] ?? 25;


                $subject = Subject::where('subject_no', $validatedData['subject_no'])->first();
                if ($subject) {
                    $subject->update($validatedData);
                    $updatedCount++;
                } else {
                    Subject::create($validatedData);
                    $createdCount++;
                }
                $processedSubjectNos->push($subjectNo);
            }

            $message = "Subjects bulk upload processed. ";
            if ($createdCount > 0) $message .= "{$createdCount} new created. ";
            if ($updatedCount > 0) $message .= "{$updatedCount} updated. ";
            if ($skippedCount > 0) $message .= "{$skippedCount} skipped. ";
            if (!empty($skippedDetails)) {
                session()->flash('skipped_details', $skippedDetails);
            }
            return redirect()->route('data-entry.subjects.index')->with('success', trim($message));
        } catch (Exception $e) {
            Log::error('Subject Bulk Upload Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->route('data-entry.subjects.index')->with('error', 'Upload error: ' . $e->getMessage());
        }
    }


    // =============================================
    //             API Controller Methods
    // =============================================

    /**
     * Display a listing of the subjects (API).
     * عرض قائمة المواد للـ API (بدون Pagination أو فلترة حالياً)
     */
    public function apiIndex(Request $request) // أبقينا على Request إذا احتجناها لاحقاً
    {
        try {
            $query = Subject::with([
                'subjectType:id,subject_type_name',
                'subjectCategory:id,subject_category_name',
                'department:id,department_name'
            ]);

            // --- إزالة كود الفلترة ---

            // --- الخيار 1: جلب كل المواد (الحالة الحالية) ---
            $subjects = $query->latest('id') // الترتيب بالأحدث
                ->get();

            // --- الخيار 2: كود الـ Pagination للـ API (معطل حالياً) ---
            /*
            $perPage = $request->query('per_page', 15);
            $subjectsPaginated = $query->latest('id')
                                       ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $subjectsPaginated->items(),
                'pagination' => [
                    'total' => $subjectsPaginated->total(),
                    'per_page' => $subjectsPaginated->perPage(),
                    'current_page' => $subjectsPaginated->currentPage(),
                    'last_page' => $subjectsPaginated->lastPage(),
                ]
            ], 200);
            */
            // --- نهاية كود الـ Pagination المعطل ---


            return response()->json(['success' => true, 'data' => $subjects], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching subjects: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * Store a newly created subject from API request.
     * تخزين مادة جديدة قادمة من طلب API
     */
    public function apiStore(Request $request)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'subject_no' => 'required|string|max:20|unique:subjects,subject_no',
            'subject_name' => 'required|string|max:255',
            'subject_load' => 'required|integer|min:0',
            'theoretical_hours' => 'required|integer|min:0',
            'practical_hours' => 'required|integer|min:0',
            'subject_type_id' => 'required|integer|exists:subjects_types,id',
            'subject_category_id' => 'required|integer|exists:subjects_categories,id',
            'department_id' => 'required|integer|exists:departments,id',
        ]);

        // 2. Add to Database
        try {
            $subject = Subject::create($validatedData);
            $subject->load(['subjectType:id,subject_type_name', 'subjectCategory:id,subject_category_name', 'department:id,department_name']);
            // 3. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $subject,
                'message' => 'Subject created successfully.'
            ], 201);
        } catch (Exception $e) {
            Log::error('API Subject Creation Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create subject.'], 500);
        }
    }

    /**
     * Display the specified subject (API).
     * عرض مادة محددة للـ API
     */
    public function apiShow(Subject $subject)
    {
        $subject->load(['subjectType:id,subject_type_name', 'subjectCategory:id,subject_category_name', 'department:id,department_name']);
        return response()->json(['success' => true, 'data' => $subject], 200);
    }

    /**
     * Update the specified subject from API request.
     * تحديث مادة محددة قادمة من طلب API
     */
    public function apiUpdate(Request $request, Subject $subject)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'subject_no' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                'unique:subjects,subject_no,' . $subject->id,
            ],
            'subject_name' => 'sometimes|required|string|max:255',
            'subject_load' => 'sometimes|required|integer|min:0',
            'theoretical_hours' => 'sometimes|required|integer|min:0',
            'practical_hours' => 'sometimes|required|integer|min:0',
            'subject_type_id' => 'sometimes|required|integer|exists:subjects_types,id',
            'subject_category_id' => 'sometimes|required|integer|exists:subjects_categories,id',
            'department_id' => 'sometimes|required|integer|exists:departments,id',
        ]);

        // 2. Update Database
        try {
            $subject->update($validatedData);
            $subject->load(['subjectType:id,subject_type_name', 'subjectCategory:id,subject_category_name', 'department:id,department_name']);
            // 3. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $subject,
                'message' => 'Subject updated successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API Subject Update Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update subject.'], 500);
        }
    }

    /**
     * Remove the specified subject from API request.
     * حذف مادة محددة قادمة من طلب API
     */
    public function apiDestroy(Subject $subject)
    {
        // (اختياري) التحقق من الارتباطات
        if ($subject->planSubjectEntries()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete subject. It is included in academic plans.'
            ], 409);
        }

        // 1. Delete from Database
        try {
            $subject->delete();
            // 2. Return Success JSON Response
            return response()->json([
                'success' => true,
                'message' => 'Subject deleted successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API Subject Deletion Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete subject.'], 500);
        }
    }

    /**
     * Handle bulk upload (API).
     */
    // public function apiBulkUpload(Request $request)
    // {
    //     Log::info('Bulk upload endpoint hit (API) - Feature disabled.');
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Bulk upload feature is not yet available for the API.'
    //     ], 501); // 501 Not Implemented

    //     /* // كود تفعيلها لاحقاً
    //     $validator = Validator::make($request->all(), [
    //         'subject_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    //     }

    //     try {
    //         Excel::import(new SubjectsImport, $request->file('subject_file'));
    //         return response()->json(['success' => true, 'message' => 'Subjects imported successfully!'], 200);
    //     } catch (ValidationException $e) {
    //         $failures = $e->failures();
    //         $errorDetails = [];
    //          foreach ($failures as $failure) {
    //              $errorDetails[] = [
    //                  'row' => $failure->row(),
    //                  'attribute' => $failure->attribute(),
    //                  'errors' => $failure->errors(),
    //                  'value' => $failure->values()[$failure->attribute()] ?? null
    //              ];
    //          }
    //          Log::warning('API Subject Bulk Upload Validation Failures: ', $failures);
    //          return response()->json([
    //             'success' => false,
    //             'message' => 'Import failed due to validation errors.',
    //             'errors' => $errorDetails
    //         ], 422);
    //     } catch (Exception $e) {
    //          Log::error('API Subject Bulk Upload Failed (General): ' . $e->getMessage());
    //          return response()->json(['success' => false, 'message' => 'An error occurred during the upload process.'], 500);
    //     }*/
    // }


    /**
     * Handle bulk upload of subjects from Excel file via API.
     */
    public function apiBulkUpload(Request $request)
    {
        $validator = Validator::make($request->all(), ['subject_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120']);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'File validation failed.', 'errors' => $validator->errors()], 422);
        }

        try {
            $rows = Excel::toCollection(collect(), $request->file('subject_excel_file'))->first();
            if ($rows->isEmpty() || $rows->count() <= 1) {
                return response()->json(['success' => false, 'message' => 'File empty or no data rows.'], 400);
            }

            $createdCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $skippedDetails = [];
            $processedSubjectNos = collect();
            $header = $rows->first()->map(fn($item) => strtolower(str_replace([' ', '-'], '_', $item ?? '')));
            $dataRows = $rows->slice(1);

            foreach ($dataRows as $rowKey => $rowArray) {
                $row = $header->combine($rowArray->map(fn($val) => trim($val ?? '')));
                $currentRowNumber = $rowKey + 2;
                $subjectNo = $row->get('subject_no');
                $subjectName = $row->get('subject_name');

                if (empty($subjectNo) && empty($subjectName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped (empty).";
                    continue;
                }
                if (empty($subjectNo) || empty($subjectName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped (missing no or name).";
                    continue;
                }
                if ($processedSubjectNos->contains($subjectNo)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped (duplicate no '{$subjectNo}' in file).";
                    continue;
                }

                $subjectTypeId = $this->findRelatedId(SubjectType::class, 'subject_type_name', $row->get('subject_type_id'), $skippedDetails, $currentRowNumber, 'Type');
                if (is_null($subjectTypeId) && !empty($row->get('subject_type_id'))) {
                    $skippedCount++;
                    continue;
                }
                $subjectCategoryId = $this->findRelatedId(SubjectCategory::class, 'subject_category_name', $row->get('subject_category_id'), $skippedDetails, $currentRowNumber, 'Category');
                if (is_null($subjectCategoryId) && !empty($row->get('subject_category_id'))) {
                    $skippedCount++;
                    continue;
                }
                $departmentId = $this->findRelatedId(Department::class, 'department_name', $row->get('department_id'), $skippedDetails, $currentRowNumber, 'Department');
                if (is_null($departmentId) && !empty($row->get('department_id'))) {
                    $skippedCount++;
                    continue;
                }

                // $dataToValidate = [ /* ... نفس مصفوفة dataToValidate من دالة الويب ... */ ];
                $dataToValidate = [
                    'subject_no' => $subjectNo,
                    'subject_name' => $subjectName,
                    'subject_load' => $row->get('subject_load'),
                    'theoretical_hours' => $row->get('theoretical_hours'),
                    'practical_hours' => $row->get('practical_hours'),
                    'capacity_theoretical_section' => $row->get('capacity_theoretical_section'),
                    'capacity_practical_section' => $row->get('capacity_practical_section'),
                    'subject_type_id' => $subjectTypeId,
                    'subject_category_id' => $subjectCategoryId,
                    'department_id' => $departmentId,
                ];

                $validator = Validator::make($dataToValidate, [
                    'subject_no' => 'required|string|max:20',
                    'subject_name' => 'required|string|max:255',
                    'subject_load' => 'required|integer|min:0',
                    'theoretical_hours' => 'required|integer|min:0',
                    'practical_hours' => 'required|integer|min:0',
                    'capacity_theoretical_section' => 'nullable|integer|min:1',
                    'capacity_practical_section' => 'nullable|integer|min:1',
                    'subject_type_id' => 'required|integer|exists:subjects_types,id',
                    'subject_category_id' => 'required|integer|exists:subjects_categories,id',
                    'department_id' => 'required|integer|exists:departments,id',
                ]);
                // $validator = Validator::make($dataToValidate, [ /* ... نفس قواعد الـ validation ... */ ]);
                // if ($validator->fails()) { /* ... تجاهل وتسجيل ... */ continue; }
                if ($validator->fails()) {
                    $skippedCount++;
                    $errors = implode(', ', $validator->errors()->all());
                    $skippedDetails[] = "Row {$currentRowNumber} (SubjectNo: {$subjectNo}): Skipped - Validation: {$errors}";
                    continue;
                }
                $validatedData = $validator->validated();
                $validatedData['capacity_theoretical_section'] = $validatedData['capacity_theoretical_section'] ?? 50;
                $validatedData['capacity_practical_section'] = $validatedData['capacity_practical_section'] ?? 25;

                $subject = Subject::updateOrCreate(['subject_no' => $validatedData['subject_no']], $validatedData);
                if ($subject->wasRecentlyCreated) {
                    $createdCount++;
                } elseif ($subject->wasChanged()) {
                    $updatedCount++;
                } else {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber} (No: {$subjectNo}): Data already up-to-date.";
                }
                $processedSubjectNos->push($subjectNo);
            }

            return response()->json(['success' => true, 'message' => "Subjects uploaded.", 'data' => compact('createdCount', 'updatedCount', 'skippedCount', 'skippedDetails')], 200);
        } catch (Exception $e) {
            Log::error('API Subject Bulk Upload Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Upload error.', 'error' => $e->getMessage()], 500);
        }
    }
}
