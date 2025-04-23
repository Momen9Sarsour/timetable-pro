<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request; // استيراد Request
use Illuminate\Support\Facades\Log; // لاستخدام اللوغ (اختياري لكن مفيد)
use Exception; // لاستخدام Exception في الـ catch

class DepartmentController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================

    /**
     * Display a listing of the departments (Web View).
     * عرض قائمة الأقسام لصفحة الويب
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
     * تخزين قسم جديد قادم من طلب ويب (نموذج الإضافة)
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
     * تحديث قسم محدد قادم من طلب ويب (نموذج التعديل)
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
     * حذف قسم محدد قادم من طلب ويب (زر الحذف)
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


    // =============================================
    //             API Controller Methods
    // =============================================

     /**
     * Display a listing of the departments (API).
     * عرض قائمة الأقسام للـ API
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
     * تخزين قسم جديد قادم من طلب API
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
     * عرض قسم محدد للـ API
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
                'required', 'string', 'max:20',
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
     * حذف قسم محدد قادم من طلب API
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

} // نهاية الكلاس
