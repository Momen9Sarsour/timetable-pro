<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // اختياري
use Illuminate\Validation\Rule;
use Exception;

class InstructorController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================

    /**
     * Display a listing of the instructors (Web View) with Pagination.
     * عرض قائمة المدرسين لصفحة الويب مع تقسيم الصفحات (الأحدث أولاً)
     */
    public function index()
    {
        try {
            // جلب المدرسين مرتبين بالأحدث مع علاقات user و department وتقسيم الصفحات
            $instructors = Instructor::with(['user', 'department']) // Eager load relations
                ->latest('id')                // Order by newest first
                ->paginate(10);               // Paginate results

            // جلب المستخدمين المتاحين لربطهم
            $availableUsers = User::whereDoesntHave('instructor')
                ->whereHas('role', fn($q) => $q->whereIn('name', ['instructor', 'hod', 'admin']))
                ->orderBy('name')->get();

            // جلب الأقسام للقائمة المنسدلة
            $departments = Department::orderBy('department_name')->get();

            return view('dashboard.data-entry.instructors', compact('instructors', 'availableUsers', 'departments'));
        } catch (Exception $e) {
            Log::error('Error fetching instructors for web view: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load instructors.');
        }
    }

    /**
     * Store a newly created instructor from web request.
     * تخزين مدرس جديد قادم من طلب ويب
     */
    public function store(Request $request)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'user_id' => 'required|integer|exists:users,id|unique:instructors,user_id',
            'instructor_no' => 'required|string|max:20|unique:instructors,instructor_no',
            'instructor_name' => 'required|string|max:255',
            'academic_degree' => 'nullable|string|max:100',
            'department_id' => 'required|integer|exists:departments,id',
            'max_weekly_hours' => 'nullable|integer|min:0|max:100',
            // 'office_location' => 'nullable|string|max:255', // حقول المكتب
            // 'office_hours' => 'nullable|string|max:255',
            'availability_preferences' => 'nullable|string',
        ]);

        // التحقق الإضافي من دور المستخدم
        $user = User::find($request->user_id);
        if (!$user || !$user->hasRole(['instructor', 'hod', 'admin'])) {
            return redirect()->back()
                ->with('error', 'The selected user does not have a valid role to be an instructor.')
                ->withInput();
        }

        // 2. Prepare Data (validatedData جاهزة)
        $data = $validatedData;

        // 3. Add to Database
        try {
            Instructor::create($data);
            // 4. Redirect
            return redirect()->route('data-entry.instructors.index') // تأكد من اسم الروت
                ->with('success', 'Instructor created successfully.');
        } catch (Exception $e) {
            Log::error('Instructor Creation Failed (Web): ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create instructor.')
                ->withInput();
        }
    }

    /**
     * Update the specified instructor from web request.
     * تحديث مدرس محدد قادم من طلب ويب
     */
    public function update(Request $request, Instructor $instructor)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'instructor_no' => 'required|string|max:20|unique:instructors,instructor_no,' . $instructor->id,
            'instructor_name' => 'required|string|max:255',
            'academic_degree' => 'nullable|string|max:100',
            'department_id' => 'required|integer|exists:departments,id',
            'max_weekly_hours' => 'nullable|integer|min:0|max:100',
            // 'office_location' => 'nullable|string|max:255',
            // 'office_hours' => 'nullable|string|max:255',
            'availability_preferences' => 'nullable|string',
        ]);

        // 2. Prepare Data (validatedData جاهزة)
        $data = $validatedData;

        // 3. Update Database
        try {
            $instructor->update($data);
            // 4. Redirect
            return redirect()->route('data-entry.instructors.index') // تأكد من اسم الروت
                ->with('success', 'Instructor updated successfully.');
        } catch (Exception $e) {
            Log::error('Instructor Update Failed (Web): ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update instructor.')
                ->withInput();
        }
    }

    /**
     * Remove the specified instructor from web request.
     * حذف مدرس محدد قادم من طلب ويب
     */
    public function destroy(Instructor $instructor)
    {
        // (اختياري) التحقق من السجلات المرتبطة
        // if ($instructor->scheduleEntries()->exists()) { ... }

        // 1. Delete from Database
        try {
            $instructor->delete();
            // 2. Redirect
            return redirect()->route('data-entry.instructors.index') // تأكد من اسم الروت
                ->with('success', 'Instructor record deleted successfully.');
        } catch (Exception $e) {
            Log::error('Instructor Deletion Failed (Web): ' . $e->getMessage());
            return redirect()->route('data-entry.instructors.index') // تأكد من اسم الروت
                ->with('error', 'Failed to delete instructor record.');
        }
    }


    // =============================================
    //             API Controller Methods
    // =============================================

    /**
     * Display a listing of the instructors (API).
     */
    public function apiIndex(Request $request)
    {
        try {
            $query = Instructor::with(['user:id,name,email', 'department:id,department_name']);

            // (اختياري) فلترة
            if ($request->has('department_id')) {
                $query->where('department_id', $request->department_id);
            }

            // --- الخيار 1: جلب كل المدرسين (الحالة الحالية) ---
            $instructors = $query->latest('id')->get();

            /*
            $perPage = $request->query('per_page', 15);
            $instructorsPaginated = $query->latest('id')
                                          ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $instructorsPaginated->items(),
                'pagination' => [
                    'total' => $instructorsPaginated->total(),
                    'per_page' => $instructorsPaginated->perPage(),
                    'current_page' => $instructorsPaginated->currentPage(),
                    'last_page' => $instructorsPaginated->lastPage(),
                ]
            ], 200);
            */

            return response()->json(['success' => true, 'data' => $instructors], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching instructors: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * Store a newly created instructor from API request.
     */
    public function apiStore(Request $request)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'user_id' => 'required|integer|exists:users,id|unique:instructors,user_id',
            'instructor_no' => 'required|string|max:20|unique:instructors,instructor_no',
            'instructor_name' => 'required|string|max:255',
            'academic_degree' => 'nullable|string|max:100',
            'department_id' => 'required|integer|exists:departments,id',
            'max_weekly_hours' => 'nullable|integer|min:0|max:100',
            // 'office_location' => 'nullable|string|max:255',
            // 'office_hours' => 'nullable|string|max:255',
            'availability_preferences' => 'nullable|string',
        ]);

        // التحقق من دور المستخدم
        $user = User::find($request->user_id);
        if (!$user || !$user->hasRole(['instructor', 'hod', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'The selected user does not have a valid role.'], 422);
        }

        // 2. Add to Database
        try {
            $instructor = Instructor::create($validatedData);
            $instructor->load(['user:id,name,email', 'department:id,department_name']); // تحميل العلاقات
            // 3. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $instructor,
                'message' => 'Instructor created successfully.'
            ], 201);
        } catch (Exception $e) {
            Log::error('API Instructor Creation Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create instructor.'], 500);
        }
    }

    /**
     * Display the specified instructor (API).
     */
    public function apiShow(Instructor $instructor)
    {
        $instructor->load(['user:id,name,email', 'department:id,department_name']);
        return response()->json([
            'success' => true,
            'data' => $instructor,
        ], 200);
    }

    /**
     * Update the specified instructor from API request.
     */
    public function apiUpdate(Request $request, Instructor $instructor)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'instructor_no' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                'unique:instructors,instructor_no,' . $instructor->id,
            ],
            'instructor_name' => 'sometimes|required|string|max:255',
            'academic_degree' => 'sometimes|nullable|string|max:100',
            'department_id' => 'sometimes|required|integer|exists:departments,id',
            'max_weekly_hours' => 'sometimes|nullable|integer|min:0|max:100',
            // 'office_location' => 'sometimes|nullable|string|max:255',
            // 'office_hours' => 'sometimes|nullable|string|max:255',
            'availability_preferences' => 'sometimes|nullable|string',
        ]);

        // 2. Update Database
        try {
            $instructor->update($validatedData);
            $instructor->load(['user:id,name,email', 'department:id,department_name']); // تحميل العلاقات بعد التحديث
            // 3. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $instructor,
                'message' => 'Instructor updated successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API Instructor Update Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update instructor.'], 500);
        }
    }

    /**
     * Remove the specified instructor from API request.
     * حذف مدرس محدد قادم من طلب API
     */
    public function apiDestroy(Instructor $instructor)
    {
        // (اختياري) التحقق من السجلات المرتبطة
        // if ($instructor->scheduleEntries()->exists()) { ... }

        // 1. Delete from Database
        try {
            $instructor->delete(); // حذف سجل المدرس فقط، المستخدم المرتبط يبقى
            // 2. Return Success JSON Response
            return response()->json([
                'success' => true,
                'message' => 'Instructor record deleted successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API Instructor Deletion Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete instructor record.'], 500);
        }
    }
} // نهاية الكلاس
