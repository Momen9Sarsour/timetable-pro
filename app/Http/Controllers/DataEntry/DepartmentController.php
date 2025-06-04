<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request; // استيراد Request
use Illuminate\Support\Facades\Log; // لاستخدام اللوغ (اختياري لكن مفيد)
use Exception; // لاستخدام Exception في الـ catch
use Maatwebsite\Excel\Facades\Excel; // *** استيراد Excel Facade ***
use Illuminate\Support\Collection;   // *** استيراد Collection ***
use Illuminate\Support\Facades\Validator; // لاستخدام Validator يدوياً

class DepartmentController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================

    /**
     * Display a listing of the departments (Web View).
     */
    public function index()
    {
        // جلب كل الأقسام مرتبة بالاسم
        // $departments = Department::orderBy('department_name')->get();
        $departments = Department::latest()->paginate(10);
        // إرسال البيانات إلى الـ view
        return view('dashboard.data-entry.departments', compact('departments'));
    }

    /**
     * Store a newly created department from web request.
     */
    public function store(Request $request)
    {
        // 1. التحقق من البيانات (Validation) - بسيط ومباشر
        $request->validate([
            'department_no' => 'required|string|max:20|unique:departments,department_no',
            'department_name' => 'required|string|max:255',
        ]);

        // 2. تجهيز البيانات (لا حاجة لمعالجة خاصة هنا)
        $data = $request->only(['department_no', 'department_name']);

        // 3. إضافة للـ Database باستخدام ::create
        try {
            Department::create($data);
            // 4. إعادة التوجيه لصفحة العرض مع رسالة نجاح
            return redirect()->route('data-entry.departments.index') // تأكد أن اسم الروت صحيح
                ->with('success', 'Department created successfully.');
        } catch (Exception $e) {
            // تسجيل الخطأ للمطور (اختياري)
            Log::error('Department Creation Failed (Web): ' . $e->getMessage());
            // 4. إعادة التوجيه للصفحة السابقة مع رسالة خطأ وإرجاع المدخلات
            return redirect()->back()
                ->with('error', 'Failed to create department.')
                ->withInput();
        }
    }

    /**
     * Update the specified department from web request.
     * (نستخدم Route Model Binding هنا لجلب $department تلقائياً)
     */
    public function update(Request $request, Department $department)
    {
        // 1. التحقق من البيانات (Validation) - مع تجاهل الصف الحالي لـ unique
        $request->validate([
            'department_no' => 'required|string|max:20|unique:departments,department_no,' . $department->id,
            'department_name' => 'required|string|max:255',
        ]);

        // 2. تجهيز البيانات
        $data = $request->only(['department_no', 'department_name']);

        // 3. تحديث في الـ Database باستخدام ->update()
        try {
            $department->update($data);
            // 4. إعادة التوجيه لصفحة العرض مع رسالة نجاح
            return redirect()->route('data-entry.departments.index') // تأكد أن اسم الروت صحيح
                ->with('success', 'Department updated successfully.');
        } catch (Exception $e) {
            // تسجيل الخطأ (اختياري)
            Log::error('Department Update Failed (Web): ' . $e->getMessage());
            // 4. إعادة التوجيه للصفحة السابقة مع رسالة خطأ وإرجاع المدخلات
            return redirect()->back()
                ->with('error', 'Failed to update department.')
                ->withInput();
        }
    }

    /**
     * Remove the specified department from web request.
     */
    public function destroy(Department $department)
    {
        // (اختياري) التحقق من السجلات المرتبطة قبل الحذف
        if ($department->instructors()->exists() || $department->subjects()->exists() || $department->plans()->exists()) {
            return redirect()->route('data-entry.departments.index') // تأكد أن اسم الروت صحيح
                ->with('error', 'Cannot delete department. It has associated records.');
        }

        // 1. حذف من الـ Database باستخدام ->delete()
        try {
            $department->delete();
            // 2. إعادة التوجيه لصفحة العرض مع رسالة نجاح
            return redirect()->route('data-entry.departments.index') // تأكد أن اسم الروت صحيح
                ->with('success', 'Department deleted successfully.');
        } catch (Exception $e) {
            // تسجيل الخطأ (اختياري)
            Log::error('Department Deletion Failed (Web): ' . $e->getMessage());
            // 2. إعادة التوجيه لصفحة العرض مع رسالة خطأ
            return redirect()->route('data-entry.departments.index') // تأكد أن اسم الروت صحيح
                ->with('error', 'Failed to delete department.');
        }
    }


    /**
     * Handle bulk upload of departments from Excel file.
     */
    public function bulkUpload(Request $request)
    {
        // 1. التحقق من الملف المرفوع
        $request->validate([
            'department_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:2048', // 2MB max
        ], [], ['department_excel_file' => 'Excel file']); // اسم مخصص للحقل في رسائل الخطأ

        try {
            // 2. قراءة البيانات من ملف الإكسل
            // withHeadingRow: يفترض أن الصف الأول هو العناوين
            // mapInto: يحاول تحويل كل صف لمصفوفة (يمكن استخدام ToCollection أيضاً)
            $rows = Excel::toCollection(collect(), $request->file('department_excel_file'))->first();

            if ($rows->isEmpty()) {
                return redirect()->route('data-entry.departments.index')
                    ->with('error', 'The uploaded Excel file is empty or has no data rows.');
            }

            $createdCount = 0;
            $updatedCount = 0;
            $skippedRows = 0;
            $processedRows = collect(); // لتتبع الأرقام والأسماء التي تمت معالجتها من الملف لتجنب التكرار داخل الملف
            $skippedDetails = [];

            // الحصول على الصف الأول كعناوين (بتحويلها لـ snake_case ومتوافقة مع DB)
            $header = $rows->first()->map(function ($item) {
                return strtolower(str_replace(' ', '_', $item));
            });

            // إزالة صف العناوين من البيانات
            $dataRows = $rows->slice(1);

            foreach ($dataRows as $rowKey => $rowArray) {
                // تحويل الصف الحالي لمصفوفة باستخدام العناوين كمفاتيح
                $row = $header->combine($rowArray);

                $departmentNo = trim($row->get('department_no', '')); // القيمة من عمود B
                $departmentName = trim($row->get('department_name', '')); // القيمة من عمود C

                // 1. تجاهل الأسطر الفارغة تماماً (إذا كان الرقم والاسم فارغين)
                if (empty($departmentNo) && empty($departmentName)) {
                    $skippedRows++;
                    $skippedDetails[] = "Row " . ($rowKey + 2) . ": Skipped because both department_no and department_name are empty.";
                    continue;
                }

                // 2. تجاهل الأسطر التي قد تكون مدمجة أو لا تحتوي على البيانات المطلوبة
                // (نفترض أن department_no أو department_name يجب أن يكون موجوداً)
                if (empty($departmentNo) || empty($departmentName)) {
                    Log::warning("Skipping row " . ($rowKey + 1) . " due to missing department_no or department_name.", $row->toArray());
                    $skippedRows++;
                    $skippedDetails[] = "Row " . ($rowKey + 2) . ": Missing department_no or department_name.";
                    continue;
                }


                // 4. فحص التكرار داخل الملف نفسه (بناءً على department_no)
                if ($processedRows->contains('department_no', $departmentNo)) {
                    Log::info("Skipping duplicate department_no '{$departmentNo}' from Excel file (already processed).");
                    $skippedRows++;
                    $skippedDetails[] = "Row " . ($rowKey + 2) . ": Duplicate department_no '{$departmentNo}' in file.";
                    continue;
                }
                // أو بناءً على department_name إذا كان يجب أن يكون فريداً أيضاً
                if ($processedRows->contains('department_name', $departmentName)) {
                    Log::info("Skipping duplicate department_name '{$departmentName}' from Excel file (already processed).");
                    $skippedRows++;
                    $skippedDetails[] = "Row " . ($rowKey + 2) . ": Duplicate department_name '{$departmentName}' in file.";
                    continue;
                }


                // 3. البحث عن القسم في قاعدة البيانات وتحديثه أو إنشاؤه
                // البحث برقم القسم أولاً (لأنه يفترض أن يكون فريداً وأساسياً)
                $department = Department::where('department_no', $departmentNo)->first();

                if ($department) {
                    // القسم موجود، قم بالتحديث (فقط إذا كان الاسم مختلفاً)
                    if ($department->department_name !== $departmentName) {
                        $department->department_name = $departmentName;
                        $department->save();
                        $updatedCount++;
                    }
                } else {
                    // القسم غير موجود برقم القسم، ابحث بالاسم (احتياطي)
                    $departmentByName = Department::where('department_name', $departmentName)->first();
                    if ($departmentByName) {
                        // وجد بالاسم، قم بتحديث رقمه إذا كان مختلفاً
                        if ($departmentByName->department_no !== $departmentNo) {
                            $departmentByName->department_no = $departmentNo; // تحديث الرقم
                            $departmentByName->save();
                        }
                        $updatedCount++;
                        $department = $departmentByName; // استخدم هذا القسم لتتبع المعالج
                    } else {
                        // القسم غير موجود لا بالرقم ولا بالاسم، قم بإنشاء جديد
                        Department::create([
                            'department_no' => $departmentNo,
                            'department_name' => $departmentName,
                        ]);
                        $createdCount++;
                    }
                }
                // إضافة للبيانات التي تمت معالجتها من الملف
                $processedRows->push(['department_no' => $departmentNo, 'department_name' => $departmentName]);
            }

            $message = "Bulk upload completed. ";
            if ($createdCount > 0) $message .= "{$createdCount} new departments created. ";
            if ($updatedCount > 0) $message .= "{$updatedCount} departments updated. ";
            if ($skippedRows > 0) $message .= "{$skippedRows} rows skipped (empty or duplicate within file).";

            return redirect()->route('data-entry.departments.index')
                ->with('success', $message)
                ->with('skipped_details', $skippedDetails);
        } catch (Exception $e) {
            Log::error('Department Bulk Upload Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->route('data-entry.departments.index')
                ->with('error', 'An error occurred during bulk upload: ' . $e->getMessage());
        }
    }


    // =============================================
    //             API Controller Methods
    // =============================================

    /**
     * Display a listing of the departments (API).
     */
    public function apiIndex()
    {
        try {
            $departments = Department::orderBy('department_name')->get(['id', 'department_no', 'department_name']); // جلب حقول محددة
            // إرجاع استجابة JSON ناجحة
            return response()->json([
                'success' => true,
                'data' => $departments,
            ], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching departments: ' . $e->getMessage());
            // إرجاع استجابة JSON للخطأ
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * Store a newly created department from API request.
     */
    public function apiStore(Request $request)
    {
        // 1. Validation (Laravel يعيد 422 JSON تلقائياً عند الفشل)
        $validatedData = $request->validate([
            'department_no' => 'required|string|max:20|unique:departments,department_no',
            'department_name' => 'required|string|max:255',
        ]);

        // 2. Add to Database
        try {
            // استخدام create وإرجاع الموديل المنشأ مباشرة
            $department = Department::create($validatedData);
            // 3. Return Success JSON Response (201 Created)
            return response()->json([
                'success' => true,
                'data' => $department, // إرجاع القسم المنشأ
                'message' => 'Department created successfully.'
            ], 201);
        } catch (Exception $e) {
            Log::error('API Department Creation Failed: ' . $e->getMessage());
            // 3. Return Error JSON Response
            return response()->json(['success' => false, 'message' => 'Failed to create department.'], 500);
        }
    }

    /**
     * Display the specified department (API).
     * (نستخدم Route Model Binding هنا أيضاً)
     */
    public function apiShow(Department $department)
    {
        // إرجاع القسم مباشرة
        return response()->json([
            'success' => true,
            'data' => $department,
        ], 200);
    }

    /**
     * Update the specified department from API request.
     * تحديث قسم محدد قادم من طلب API
     */
    public function apiUpdate(Request $request, Department $department)
    {
        // 1. Validation (استخدام sometimes لأن API قد يرسل جزءاً من البيانات)
        $validatedData = $request->validate([
            'department_no' => [
                'sometimes', // فقط تحقق إذا كان الحقل موجوداً في الطلب
                'required',
                'string',
                'max:20',
                'unique:departments,department_no,' . $department->id, // تجاهل الصف الحالي
            ],
            'department_name' => 'sometimes|required|string|max:255',
        ]);

        // 2. Update Database
        try {
            // تحديث القسم بالبيانات التي تم التحقق منها فقط
            $department->update($validatedData);
            // 3. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $department, // إرجاع القسم المحدث
                'message' => 'Department updated successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API Department Update Failed: ' . $e->getMessage());
            // 3. Return Error JSON Response
            return response()->json(['success' => false, 'message' => 'Failed to update department.'], 500);
        }
    }

    /**
     * Remove the specified department from API request.
     */
    public function apiDestroy(Department $department)
    {
        // (اختياري) التحقق من السجلات المرتبطة
        if ($department->instructors()->exists() || $department->subjects()->exists() || $department->plans()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete department due to associated records.'
            ], 409); // 409 Conflict
        }

        // 1. Delete from Database
        try {
            $department->delete();
            // 2. Return Success JSON Response (200 OK or 204 No Content)
            return response()->json([
                'success' => true,
                'message' => 'Department deleted successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API Department Deletion Failed: ' . $e->getMessage());
            // 2. Return Error JSON Response
            return response()->json(['success' => false, 'message' => 'Failed to delete department.'], 500);
        }
    }

    /**
     * Handle bulk upload of departments from Excel file via API.
     */
    public function apiBulkUpload(Request $request)
    {
        // 1. التحقق من الملف المرفوع
        $validator = Validator::make($request->all(), [
            'department_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ], [], ['department_excel_file' => 'Excel file']);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422); // Unprocessable Entity
        }

        try {
            $rows = Excel::toCollection(collect(), $request->file('department_excel_file'))->first();

            if ($rows->isEmpty() || $rows->count() <= 1) { // أقل من أو يساوي 1 للتحقق من وجود صف بيانات واحد على الأقل بعد الهيدر
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded Excel file is empty or contains no data rows after the header.'
                ], 400); // Bad Request
            }

            $createdCount = 0;
            $updatedCount = 0;
            $skippedCount = 0; // لتعداد الصفوف المتجاهلة
            $skippedDetails = []; // لتخزين تفاصيل الصفوف المتجاهلة
            $processedRows = collect();

            $header = $rows->first()->map(fn($item) => strtolower(str_replace(' ', '_', $item ?? '')));
            $dataRows = $rows->slice(1);

            foreach ($dataRows as $rowKey => $rowArray) {
                $row = $header->combine($rowArray->map(fn($val) => trim($val ?? ''))); // Trim values

                $departmentNo = $row->get('department_no', '');
                $departmentName = $row->get('department_name', '');
                $currentRowNumber = $rowKey + 2; // رقم الصف الفعلي في الإكسل (مع الهيدر)

                if (empty($departmentNo) && empty($departmentName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped because both department_no and department_name are empty.";
                    continue;
                }
                if (empty($departmentNo) || empty($departmentName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped due to missing department_no or department_name.";
                    continue;
                }
                if ($processedRows->contains('department_no', $departmentNo)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped duplicate department_no '{$departmentNo}' from within this file.";
                    continue;
                }
                if ($processedRows->contains('department_name', $departmentName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped duplicate department_name '{$departmentName}' from within this file.";
                    continue;
                }

                $department = Department::where('department_no', $departmentNo)->first();
                $updatedThisRow = false;

                if ($department) {
                    if ($department->department_name !== $departmentName) {
                        $department->department_name = $departmentName;
                        $department->save();
                        $updatedCount++;
                        $updatedThisRow = true;
                    }
                } else {
                    $departmentByName = Department::where('department_name', $departmentName)->first();
                    if ($departmentByName) {
                        if ($departmentByName->department_no !== $departmentNo) {
                            $departmentByName->department_no = $departmentNo;
                            $departmentByName->save();
                        }
                        $updatedCount++;
                        $updatedThisRow = true;
                        $department = $departmentByName;
                    } else {
                        Department::create([
                            'department_no' => $departmentNo,
                            'department_name' => $departmentName,
                        ]);
                        $createdCount++;
                    }
                }
                if (!$updatedThisRow && $department) { // لم يتم التحديث ولكنه موجود، يعتبر كأنه تمت معالجته
                    // No action needed, just mark as processed if it existed and wasn't updated
                }
                $processedRows->push(['department_no' => $departmentNo, 'department_name' => $departmentName]);
            }

            $summaryMessage = "Bulk upload processed.";
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
            Log::error('API Department Bulk Upload Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during bulk upload.',
                'error_details' => $e->getMessage() // إرسال تفاصيل الخطأ (للمطور)
            ], 500); // Internal Server Error
        }
    }
}
