<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\SubjectCategory; // تم استيراده
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Exception;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class SubjectCategoryController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================

    /**
     * Display a listing of the subject categories.
     */
    public function index()
    {
        try {
            // تغيير اسم المتغير والموديل والـ view
            $subjectCategories = SubjectCategory::latest('id')->paginate(15);
            return view('dashboard.data-entry.subject-categories', compact('subjectCategories'));
        } catch (Exception $e) {
            Log::error('Error fetching subject categories: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load subject categories.');
        }
    }

    /**
     * Store a newly created subject category in storage.
     */
    public function store(Request $request)
    {
        // 1. Validation (تغيير اسم الحقل والجدول)
        $validatedData = $request->validate([
            'subject_category_name' => 'required|string|max:100|unique:subjects_categories,subject_category_name',
        ]);

        // 2. Prepare Data
        $data = $validatedData;

        // 3. Add to Database
        try {
            SubjectCategory::create($data);
            // 4. Redirect (تغيير الـ route والرسالة)
            return redirect()->route('data-entry.subject-categories.index')
                ->with('success', 'Subject Category created successfully.');
        } catch (Exception $e) {
            Log::error('Subject Category Creation Failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create subject category.')
                ->withInput();
        }
    }

    /**
     * Update the specified subject category in storage.
     */
    public function update(Request $request, SubjectCategory $subjectCategory) // تغيير المتغير
    {
        // 1. Validation (تغيير اسم الحقل والجدول والمتغير)
        $validatedData = $request->validate([
            'subject_category_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('subjects_categories')->ignore($subjectCategory->id),
            ],
        ]);

        // 2. Prepare Data
        $data = $validatedData;

        // 3. Update Database
        try {
            $subjectCategory->update($data);
            // 4. Redirect (تغيير الـ route والرسالة)
            return redirect()->route('data-entry.subject-categories.index')
                ->with('success', 'Subject Category updated successfully.');
        } catch (Exception $e) {
            Log::error('Subject Category Update Failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update subject category.')
                ->withInput();
        }
    }

    /**
     * Remove the specified subject category from storage.
     */
    public function destroy(SubjectCategory $subjectCategory) // تغيير المتغير
    {
        // التحقق من المواد المرتبطة
        if ($subjectCategory->subjects()->exists()) {
            return redirect()->route('data-entry.subject-categories.index')
                ->with('error', 'Cannot delete category. It is assigned to subjects.');
        }

        // 1. Delete
        try {
            $subjectCategory->delete();
            // 2. Redirect (تغيير الـ route والرسالة)
            return redirect()->route('data-entry.subject-categories.index')
                ->with('success', 'Subject Category deleted successfully.');
        } catch (Exception $e) {
            Log::error('Subject Category Deletion Failed: ' . $e->getMessage());
            return redirect()->route('data-entry.subject-categories.index')
                ->with('error', 'Failed to delete subject category.');
        }
    }


    /**
     * Handle bulk upload of subject categories from Excel file for Web.
     */
    public function bulkUpload(Request $request)
    {
        $request->validate([
            'subject_category_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ], [], ['subject_category_excel_file' => 'Excel file']);

        try {
            $rows = Excel::toCollection(collect(), $request->file('subject_category_excel_file'))->first();

            if ($rows->isEmpty() || $rows->count() <= 1) {
                return redirect()->route('data-entry.subject-categories.index')
                                 ->with('error', 'Uploaded Excel file is empty or has no data rows after the header.');
            }

            $createdCount = 0;
            $skippedCount = 0;
            $skippedDetails = [];
            $processedNames = collect(); // لتتبع الأسماء التي تمت معالجتها من الملف الحالي

            // الحصول على الصف الأول كعناوين (بتحويلها لـ snake_case)
            // هذا يفترض أن اسم العمود في الإكسل هو "subject_category_name" أو ما يشبهه
            $header = $rows->first()->map(fn ($item) => strtolower(str_replace([' ', '-'], '_', $item ?? '')));
            $dataRows = $rows->slice(1); // إزالة صف العناوين

            foreach ($dataRows as $rowKey => $rowArray) {
                // تحويل الصف الحالي لمصفوفة باستخدام العناوين كمفاتيح
                $row = $header->combine($rowArray->map(fn($val) => trim($val ?? '')));
                $currentRowNumber = $rowKey + 2; // رقم الصف الفعلي في الإكسل

                $categoryName = $row->get('subject_category_name', ''); // جلب القيمة

                // 1. تجاهل الأسطر الفارغة
                if (empty($categoryName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped because subject_category_name is empty.";
                    continue;
                }

                // 2. فحص التكرار داخل الملف نفسه
                if ($processedNames->contains($categoryName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped duplicate category name '{$categoryName}' from within this file.";
                    continue;
                }

                // 3. التحقق من وجود الاسم في قاعدة البيانات (أسماء الفئات يجب أن تكون فريدة)
                $existingCategory = SubjectCategory::where('subject_category_name', $categoryName)->first();

                if ($existingCategory) {
                    // الفئة موجودة بالفعل، تجاهلها (لا نقوم بالتحديث هنا عادةً)
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Subject category '{$categoryName}' already exists in the system.";
                    $processedNames->push($categoryName); // اعتبره معالجاً
                    continue;
                }

                // 4. إنشاء فئة مادة جديدة
                SubjectCategory::create([
                    'subject_category_name' => $categoryName,
                ]);
                $createdCount++;
                $processedNames->push($categoryName);
            }

            $message = "Subject Categories bulk upload processed. ";
            if ($createdCount > 0) $message .= "{$createdCount} new categories created. ";
            if ($skippedCount > 0) $message .= "{$skippedCount} rows skipped. ";

            if (!empty($skippedDetails)) {
                // استخدام session flash لتمرير تفاصيل الصفوف المتجاهلة
                session()->flash('skipped_details', $skippedDetails);
            }

            return redirect()->route('data-entry.subject-categories.index')->with('success', trim($message));

        } catch (Exception $e) {
            Log::error('Subject Category Bulk Upload Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->route('data-entry.subject-categories.index')
                             ->with('error', 'An error occurred during bulk upload: ' . $e->getMessage());
        }
    }

    // =============================================
    //             API Controller Methods
    // =============================================
    public function apiIndex()
    {
        try {
            $categories = SubjectCategory::latest('id')->get(['id', 'subject_category_name']);
            return response()->json(['success' => true, 'data' => $categories], 200);
        } catch (Exception $e) { /* ... */
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }
    public function apiStore(Request $request)
    {
        $validatedData = $request->validate(['subject_category_name' => 'required|string|max:100|unique:subjects_categories,subject_category_name']);
        try {
            $category = SubjectCategory::create($validatedData);
            return response()->json(['success' => true, 'data' => $category, 'message' => 'Category created.'], 201);
        } catch (Exception $e) { /* ... */
            return response()->json(['success' => false, 'message' => 'Failed to create.'], 500);
        }
    }
    public function apiShow(SubjectCategory $subjectCategory)
    {
        return response()->json(['success' => true, 'data' => $subjectCategory], 200);
    }
    public function apiUpdate(Request $request, SubjectCategory $subjectCategory)
    {
        $validatedData = $request->validate(['subject_category_name' => ['required', 'string', 'max:100', Rule::unique('subjects_categories')->ignore($subjectCategory->id)]]);
        try {
            $subjectCategory->update($validatedData);
            return response()->json(['success' => true, 'data' => $subjectCategory, 'message' => 'Category updated.'], 200);
        } catch (Exception $e) { /* ... */
            return response()->json(['success' => false, 'message' => 'Failed to update.'], 500);
        }
    }
    public function apiDestroy(SubjectCategory $subjectCategory)
    {
        if ($subjectCategory->subjects()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete: assigned.'], 409);
        }
        try {
            $subjectCategory->delete();
            return response()->json(['success' => true, 'message' => 'Category deleted.'], 200);
        } catch (Exception $e) { /* ... */
            return response()->json(['success' => false, 'message' => 'Failed to delete.'], 500);
        }
    }

    /**
     * Handle bulk upload of subject categories from Excel file via API.
     */
    public function apiBulkUpload(Request $request)
    {
        // 1. التحقق من الملف المرفوع
        $validator = Validator::make($request->all(), [
            'subject_category_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ], [], ['subject_category_excel_file' => 'Excel file']);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for uploaded file.',
                'errors' => $validator->errors()
            ], 422); // Unprocessable Entity
        }

        try {
            // 2. قراءة البيانات من ملف الإكسل
            $rows = Excel::toCollection(collect(), $request->file('subject_category_excel_file'))->first();

            if ($rows->isEmpty() || $rows->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded Excel file is empty or contains no data rows after the header.'
                ], 400); // Bad Request
            }

            $createdCount = 0;
            $skippedCount = 0;
            $skippedDetails = [];
            $processedNames = collect();

            $header = $rows->first()->map(fn ($item) => strtolower(str_replace([' ', '-'], '_', $item ?? '')));
            $dataRows = $rows->slice(1);

            foreach ($dataRows as $rowKey => $rowArray) {
                $row = $header->combine($rowArray->map(fn($val) => trim($val ?? '')));
                $currentRowNumber = $rowKey + 2;
                $categoryName = $row->get('subject_category_name', '');

                if (empty($categoryName)) {
                    $skippedCount++; $skippedDetails[] = "Row {$currentRowNumber}: Skipped (empty name)."; continue;
                }
                if ($processedNames->contains($categoryName)) {
                    $skippedCount++; $skippedDetails[] = "Row {$currentRowNumber}: Skipped (duplicate '{$categoryName}' in file)."; continue;
                }
                if (SubjectCategory::where('subject_category_name', $categoryName)->exists()) {
                    $skippedCount++; $skippedDetails[] = "Row {$currentRowNumber}: Category '{$categoryName}' already exists.";
                    $processedNames->push($categoryName); continue;
                }

                SubjectCategory::create(['subject_category_name' => $categoryName]);
                $createdCount++; $processedNames->push($categoryName);
            }

            // 3. بناء الاستجابة
            $summaryMessage = "Subject Categories bulk upload processed via API.";
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
            Log::error('API Subject Category Bulk Upload Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during API bulk upload.',
                'error_details' => $e->getMessage() // للمطور
            ], 500); // Internal Server Error
        }
    }
}
