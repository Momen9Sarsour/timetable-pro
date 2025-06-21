<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\SubjectType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Exception;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class SubjectTypeController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================

    /**
     * Display a listing of the subject types (Web View) with Pagination.
     */
    public function index()
    {
        try {
            // استخدام latest('id') و paginate
            $subjectTypes = SubjectType::latest('id')->paginate(15);
            // توجيه للـ view الجديد
            return view('dashboard.data-entry.subject-types', compact('subjectTypes'));
        } catch (Exception $e) {
            Log::error('Error fetching subject types: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load subject types.');
        }
    }

    /**
     * Store a newly created subject type in storage.
     */
    public function store(Request $request)
    {
        // 1. Validation
        $validatedData = $request->validate([ // استخدام validatedData
            'subject_type_name' => 'required|string|max:100|unique:subjects_types,subject_type_name',
        ]);

        // 2. Prepare Data (validatedData جاهزة)
        $data = $validatedData;

        // 3. Add to Database
        try {
            SubjectType::create($data);
            // 4. Redirect to the new index route
            return redirect()->route('data-entry.subject-types.index') // توجيه للـ index
                ->with('success', 'Subject Type created successfully.');
        } catch (Exception $e) {
            Log::error('Subject Type Creation Failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create subject type.')
                ->withInput();
        }
    }

    /**
     * Update the specified subject type in storage.
     */
    public function update(Request $request, SubjectType $subjectType)
    {
        // 1. Validation
        $validatedData = $request->validate([ // استخدام validatedData
            'subject_type_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('subjects_types')->ignore($subjectType->id),
            ],
        ]);

        // 2. Prepare Data (validatedData جاهزة)
        $data = $validatedData;

        // 3. Update Database
        try {
            $subjectType->update($data);
            // 4. Redirect
            return redirect()->route('data-entry.subject-types.index') // توجيه للـ index
                ->with('success', 'Subject Type updated successfully.');
        } catch (Exception $e) {
            Log::error('Subject Type Update Failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update subject type.')
                ->withInput();
        }
    }

    /**
     * Remove the specified subject type from storage.
     */
    public function destroy(SubjectType $subjectType)
    {
        // التحقق من وجود مواد مرتبطة
        if ($subjectType->subjects()->exists()) {
            return redirect()->route('data-entry.subject-types.index') // توجيه للـ index
                ->with('error', 'Cannot delete: assigned to subjects.');
        }

        // 1. Delete from Database
        try {
            $subjectType->delete();
            // 2. Redirect
            return redirect()->route('data-entry.subject-types.index') // توجيه للـ index
                ->with('success', 'Subject Type deleted successfully.');
        } catch (Exception $e) {
            Log::error('Subject Type Deletion Failed: ' . $e->getMessage());
            return redirect()->route('data-entry.subject-types.index') // توجيه للـ index
                ->with('error', 'Failed to delete subject type.');
        }
    }


    /**
     * Handle bulk upload of subject types from Excel file.
     */
    public function bulkUpload(Request $request)
    {
        $request->validate([
            'subject_type_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ], [], ['subject_type_excel_file' => 'Excel file']);

        try {
            $rows = Excel::toCollection(collect(), $request->file('subject_type_excel_file'))->first();

            if ($rows->isEmpty() || $rows->count() <= 1) {
                return redirect()->route('data-entry.subject-types.index')
                                 ->with('error', 'Uploaded Excel file is empty or has no data rows.');
            }

            $createdCount = 0;
            $skippedCount = 0;
            $skippedDetails = [];
            $processedNames = collect();

            $header = $rows->first()->map(fn ($item) => strtolower(str_replace(' ', '_', $item ?? '')));
            $dataRows = $rows->slice(1);

            foreach ($dataRows as $rowKey => $rowArray) {
                $row = $header->combine($rowArray->map(fn($val) => trim($val ?? '')));
                $currentRowNumber = $rowKey + 2;

                $subjectTypeName = $row->get('subject_type_name', '');

                // 1. تجاهل الأسطر الفارغة
                if (empty($subjectTypeName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped because subject_type_name is empty.";
                    continue;
                }

                // 2. فحص التكرار داخل الملف نفسه
                if ($processedNames->contains($subjectTypeName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped duplicate name '{$subjectTypeName}' from within this file.";
                    continue;
                }

                // 3. التحقق من وجود الاسم في قاعدة البيانات
                // بما أن أسماء أنواع المواد يجب أن تكون فريدة، إذا وجد، نتجاهله
                $existingType = SubjectType::where('subject_type_name', $subjectTypeName)->first();

                if ($existingType) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Subject type '{$subjectTypeName}' already exists in the system.";
                    $processedNames->push($subjectTypeName);
                    continue;
                }

                // 4. إنشاء نوع مادة جديد
                SubjectType::create([
                    'subject_type_name' => $subjectTypeName,
                ]);
                $createdCount++;
                $processedNames->push($subjectTypeName);
            }

            $message = "Subject Types bulk upload processed. ";
            if ($createdCount > 0) $message .= "{$createdCount} new types created. ";
            if ($skippedCount > 0) $message .= "{$skippedCount} rows skipped. ";

            if (!empty($skippedDetails)) {
                session()->flash('skipped_details', $skippedDetails);
            }

            return redirect()->route('data-entry.subject-types.index')->with('success', trim($message));

        } catch (Exception $e) {
            Log::error('Subject Type Bulk Upload Failed: ' . $e->getMessage());
            return redirect()->route('data-entry.subject-types.index')
                             ->with('error', 'An error occurred during bulk upload: ' . $e->getMessage());
        }
    }

    // =============================================
    //             API Controller Methods
    // =============================================
    public function apiIndex()
    {
        try {
            $types = SubjectType::latest('id')->get(['id', 'subject_type_name']);
            return response()->json(['success' => true, 'data' => $types], 200);
        } catch (Exception $e) { /* ... error handling ... */
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    public function apiStore(Request $request)
    {
        $validatedData = $request->validate(['subject_type_name' => 'required|string|max:100|unique:subjects_types,subject_type_name']);
        try {
            $type = SubjectType::create($validatedData);
            return response()->json(['success' => true, 'data' => $type, 'message' => 'Subject Type created.'], 201);
        } catch (Exception $e) { /* ... error handling ... */
            return response()->json(['success' => false, 'message' => 'Failed to create.'], 500);
        }
    }

    public function apiShow(SubjectType $subjectType)
    { // Route Model Binding
        return response()->json(['success' => true, 'data' => $subjectType], 200);
    }

    public function apiUpdate(Request $request, SubjectType $subjectType)
    {
        $validatedData = $request->validate(['subject_type_name' => ['required', 'string', 'max:100', Rule::unique('subjects_types')->ignore($subjectType->id)]]);
        try {
            $subjectType->update($validatedData);
            return response()->json(['success' => true, 'data' => $subjectType, 'message' => 'Subject Type updated.'], 200);
        } catch (Exception $e) { /* ... error handling ... */
            return response()->json(['success' => false, 'message' => 'Failed to update.'], 500);
        }
    }

    public function apiDestroy(SubjectType $subjectType)
    {
        if ($subjectType->subjects()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete: assigned to subjects.'], 409);
        }
        try {
            $subjectType->delete();
            return response()->json(['success' => true, 'message' => 'Subject Type deleted.'], 200);
        } catch (Exception $e) { /* ... error handling ... */
            return response()->json(['success' => false, 'message' => 'Failed to delete.'], 500);
        }
    }

    /**
     * Handle bulk upload of subject types from Excel file via API.
     */
    public function apiBulkUpload(Request $request)
    {
        // 1. التحقق من الملف المرفوع
        $validator = Validator::make($request->all(), [
            'subject_type_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ], [], ['subject_type_excel_file' => 'Excel file']); // اسم مخصص للحقل في رسائل الخطأ

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for uploaded file.',
                'errors' => $validator->errors()
            ], 422); // Unprocessable Entity
        }

        try {
            // 2. قراءة البيانات من ملف الإكسل
            $rows = Excel::toCollection(collect(), $request->file('subject_type_excel_file'))->first();

            if ($rows->isEmpty() || $rows->count() <= 1) { // <= 1 للتحقق من وجود صف بيانات واحد على الأقل بعد الهيدر
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded Excel file is empty or contains no data rows after the header.'
                ], 400); // Bad Request
            }

            $createdCount = 0;
            $skippedCount = 0;
            $skippedDetails = [];
            $processedNames = collect(); // لتتبع الأسماء التي تمت معالجتها من الملف

            // الحصول على الصف الأول كعناوين (بتحويلها لـ snake_case)
            $header = $rows->first()->map(fn ($item) => strtolower(str_replace(' ', '_', $item ?? '')));
            // إزالة صف العناوين من البيانات
            $dataRows = $rows->slice(1);

            foreach ($dataRows as $rowKey => $rowArray) {
                // تحويل الصف الحالي لمصفوفة باستخدام العناوين كمفاتيح
                $row = $header->combine($rowArray->map(fn($val) => trim($val ?? '')));
                $currentRowNumber = $rowKey + 2; // رقم الصف الفعلي في الإكسل (مع الهيدر)

                // العمود B هو 'subject_type_name' (بافتراض تطابق اسم الهيدر بعد تحويله)
                $subjectTypeName = $row->get('subject_type_name', '');

                // 1. تجاهل الأسطر الفارغة (إذا كان subject_type_name فارغاً)
                if (empty($subjectTypeName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped because subject_type_name is empty.";
                    continue;
                }

                // 2. فحص التكرار داخل الملف نفسه
                if ($processedNames->contains($subjectTypeName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped duplicate name '{$subjectTypeName}' from within this file.";
                    continue;
                }

                // 3. التحقق من وجود الاسم في قاعدة البيانات
                // بما أن أسماء أنواع المواد يجب أن تكون فريدة، إذا وجد، نتجاهله
                $existingType = SubjectType::where('subject_type_name', $subjectTypeName)->first();

                if ($existingType) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Subject type '{$subjectTypeName}' already exists in the system.";
                    $processedNames->push($subjectTypeName); // اعتبره معالجاً
                    continue;
                }

                // 4. إنشاء نوع مادة جديد (التحقق من الصحة تم ضمنياً)
                SubjectType::create([
                    'subject_type_name' => $subjectTypeName,
                ]);
                $createdCount++;
                $processedNames->push($subjectTypeName);
            }

            // 5. بناء الاستجابة
            $summaryMessage = "Subject Types bulk upload processed via API.";
            $responseData = [
                'created_count' => $createdCount,
                'skipped_count' => $skippedCount,
                'skipped_details' => $skippedDetails,
            ];

            return response()->json([
                'success' => true,
                'message' => $summaryMessage,
                'data' => $responseData
            ], 200); // OK

        } catch (Exception $e) {
            Log::error('API Subject Type Bulk Upload Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during API bulk upload.',
                'error_details' => $e->getMessage() // للمطور
            ], 500); // Internal Server Error
        }
    }
}
