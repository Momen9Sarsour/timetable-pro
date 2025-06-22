<?php

namespace App\Http\Controllers\DataEntry;

use Exception;
use App\Models\Role;
use App\Models\User;
use App\Models\Department;
use App\Models\Instructor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; // اختياري

class InstructorController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================

    /**
     * Display a listing of the instructors (Web View) with Pagination.
     * عرض قائمة المدرسين لصفحة الويب مع تقسيم الصفحات (الأحدث أولاً)
     */
    // public function index()
    // {
    //     try {
    //         // جلب المدرسين مرتبين بالأحدث مع علاقات user و department وتقسيم الصفحات
    //         $instructors = Instructor::with(['user', 'department']) // Eager load relations
    //             ->latest('id')                // Order by newest first
    //             ->paginate(10);               // Paginate results

    //         // جلب المستخدمين المتاحين لربطهم
    //         $availableUsers = User::whereDoesntHave('instructor')
    //             ->whereHas('role', fn($q) => $q->whereIn('name', ['instructor', 'hod', 'admin']))
    //             ->orderBy('name')->get();

    //         // جلب الأقسام للقائمة المنسدلة
    //         $departments = Department::orderBy('department_name')->get();

    //         return view('dashboard.data-entry.instructors', compact('instructors', 'availableUsers', 'departments'));
    //     } catch (Exception $e) {
    //         Log::error('Error fetching instructors for web view: ' . $e->getMessage());
    //         return redirect()->back()->with('error', 'Could not load instructors.');
    //     }
    // }

    public function index(Request $request)
    {
        try {
            $instructors = Instructor::with(['user:id,name,email', 'department:id,department_name'])
                ->latest('id')
                ->paginate(15);
            // لجلب الأقسام لنموذج الإضافة/التعديل
            $departments = Department::orderBy('department_name')->get(['id', 'department_name']);
            // لجلب الأدوار المحتملة للمدرس (إذا أردت تحديدها في الفورم)
            $instructorRoles = Role::whereIn('name', ['instructor', 'hod'])->orderBy('display_name')->get(['id', 'display_name']);


            return view('dashboard.data-entry.instructors', compact('instructors', 'departments', 'instructorRoles'));
        } catch (Exception $e) {
            Log::error('Error fetching instructors: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load instructors list.');
        }
    }

    /**
     * Store a newly created instructor from web request.
     * تخزين مدرس جديد قادم من طلب ويب
     */
    // public function store(Request $request)
    // {
    //     // 1. Validation
    //     $validatedData = $request->validate([
    //         'user_id' => 'required|integer|exists:users,id|unique:instructors,user_id',
    //         'instructor_no' => 'required|string|max:20|unique:instructors,instructor_no',
    //         'instructor_name' => 'required|string|max:255',
    //         'academic_degree' => 'nullable|string|max:100',
    //         'department_id' => 'required|integer|exists:departments,id',
    //         'max_weekly_hours' => 'nullable|integer|min:0|max:100',
    //         // 'office_location' => 'nullable|string|max:255', // حقول المكتب
    //         // 'office_hours' => 'nullable|string|max:255',
    //         'availability_preferences' => 'nullable|string',
    //     ]);

    //     // التحقق الإضافي من دور المستخدم
    //     $user = User::find($request->user_id);
    //     if (!$user || !$user->hasRole(['instructor', 'hod', 'admin'])) {
    //         return redirect()->back()
    //             ->with('error', 'The selected user does not have a valid role to be an instructor.')
    //             ->withInput();
    //     }

    //     // 2. Prepare Data (validatedData جاهزة)
    //     $data = $validatedData;

    //     // 3. Add to Database
    //     try {
    //         Instructor::create($data);
    //         // 4. Redirect
    //         return redirect()->route('data-entry.instructors.index') // تأكد من اسم الروت
    //             ->with('success', 'Instructor created successfully.');
    //     } catch (Exception $e) {
    //         Log::error('Instructor Creation Failed (Web): ' . $e->getMessage());
    //         return redirect()->back()
    //             ->with('error', 'Failed to create instructor.')
    //             ->withInput();
    //     }
    // }

    public function store(Request $request)
    {
        $errorBagName = 'addInstructorModal';
        $validatedData = $request->validateWithBag($errorBagName, [
            // User fields
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed', // استخدام قاعدة أبسط
            // 'password' => ['required', 'confirmed', Password::min(8)], // استخدام قاعدة أبسط
            'role_id_for_instructor' => 'required|integer|exists:roles,id', // دور المستخدم الجديد (مدرس/رئيس قسم)

            // Instructor fields
            'instructor_no' => 'required|string|max:20|unique:instructors,instructor_no',
            'academic_degree' => 'nullable|string|max:100',
            'department_id' => 'required|integer|exists:departments,id',
            'max_weekly_hours' => 'nullable|integer|min:0|max:100',
            // 'office_location' => 'nullable|string|max:255',
            // 'office_hours' => 'nullable|string|max:255',
            'availability_preferences' => 'nullable|string',
        ]);

        DB::beginTransaction(); // بدء Transaction
        try {
            // 1. إنشاء المستخدم
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'role_id' => $validatedData['role_id_for_instructor'], // استخدام الدور المحدد
                'email_verified_at' => now(), // تفعيل الإيميل مباشرة (اختياري)
            ]);

            // 2. إنشاء المدرس وربطه باليوزر
            Instructor::create([
                'user_id' => $user->id,
                'instructor_no' => $validatedData['instructor_no'],
                'instructor_name' => $user->name, // استخدام اسم اليوزر كاسم افتراضي للمدرس
                'academic_degree' => $validatedData['academic_degree'],
                'department_id' => $validatedData['department_id'],
                'max_weekly_hours' => $validatedData['max_weekly_hours'],
                // 'office_location' => $validatedData['office_location'],
                // 'office_hours' => $validatedData['office_hours'],
                'availability_preferences' => $validatedData['availability_preferences'],
            ]);

            DB::commit(); // تأكيد العمليات

            return redirect()->route('data-entry.instructors.index')
                ->with('success', 'Instructor and user account created successfully.');
        } catch (Exception $e) {
            DB::rollBack(); // التراجع عن العمليات في حالة الخطأ
            Log::error('Instructor & User Creation Failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create instructor: ' . $e->getMessage())
                ->withInput()
                ->withErrors($validatedData, $errorBagName); // إعادة أخطاء التحقق إذا فشل بعد التحقق (نادر)
        }
    }

    /**
     * Update the specified instructor from web request.
     * تحديث مدرس محدد قادم من طلب ويب
     */
    // public function update(Request $request, Instructor $instructor)
    // {
    //     // 1. Validation
    //     $validatedData = $request->validate([
    //         'instructor_no' => 'required|string|max:20|unique:instructors,instructor_no,' . $instructor->id,
    //         'instructor_name' => 'required|string|max:255',
    //         'academic_degree' => 'nullable|string|max:100',
    //         'department_id' => 'required|integer|exists:departments,id',
    //         'max_weekly_hours' => 'nullable|integer|min:0|max:100',
    //         // 'office_location' => 'nullable|string|max:255',
    //         // 'office_hours' => 'nullable|string|max:255',
    //         'availability_preferences' => 'nullable|string',
    //     ]);

    //     // 2. Prepare Data (validatedData جاهزة)
    //     $data = $validatedData;

    //     // 3. Update Database
    //     try {
    //         $instructor->update($data);
    //         // 4. Redirect
    //         return redirect()->route('data-entry.instructors.index') // تأكد من اسم الروت
    //             ->with('success', 'Instructor updated successfully.');
    //     } catch (Exception $e) {
    //         Log::error('Instructor Update Failed (Web): ' . $e->getMessage());
    //         return redirect()->back()
    //             ->with('error', 'Failed to update instructor.')
    //             ->withInput();
    //     }
    // }

    public function update(Request $request, Instructor $instructor)
    {
        $user = $instructor->user; // جلب المستخدم المرتبط
        if (!$user) {
            // معالجة حالة عدم وجود مستخدم مرتبط (نادر الحدوث إذا كان الإنشاء صحيحاً)
            return redirect()->route('data-entry.instructors.index')->with('error', 'User account for this instructor not found.');
        }

        $errorBagName = 'editInstructorModal_' . $instructor->id;
        $validatedData = $request->validate([
            // User fields
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed', // اختياري
            // 'password' => ['nullable', 'confirmed', Password::min(8)], // اختياري
            'role_id_for_instructor' => 'required|integer|exists:roles,id',

            // Instructor fields
            'instructor_no' => ['required', 'string', 'max:20', Rule::unique('instructors')->ignore($instructor->id)],
            'academic_degree' => 'nullable|string|max:100',
            'department_id' => 'required|integer|exists:departments,id',
            'max_weekly_hours' => 'nullable|integer|min:0|max:100',
            // 'office_location' => 'nullable|string|max:255',
            // 'office_hours' => 'nullable|string|max:255',
            'availability_preferences' => 'nullable|string',
        ]);

        // dd('dddd');
        DB::beginTransaction();
        try {
            // 1. تحديث بيانات المستخدم
            $userData = [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'role_id' => $validatedData['role_id_for_instructor'],
            ];
            if (!empty($validatedData['password'])) {
                $userData['password'] = Hash::make($validatedData['password']);
            }
            $user->update($userData);

            // 2. تحديث بيانات المدرس
            $instructor->update([
                'instructor_no' => $validatedData['instructor_no'],
                'instructor_name' => $validatedData['name'], // تحديث اسم المدرس ليتطابق
                'academic_degree' => $validatedData['academic_degree'],
                'department_id' => $validatedData['department_id'],
                'max_weekly_hours' => $validatedData['max_weekly_hours'],
                // 'office_location' => $validatedData['office_location'],
                // 'office_hours' => $validatedData['office_hours'],
                'availability_preferences' => $validatedData['availability_preferences'],
            ]);

            DB::commit();
            return redirect()->route('data-entry.instructors.index')
                ->with('success', 'Instructor and user account updated successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Instructor & User Update Failed for Instructor ID ' . $instructor->id . ': ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update instructor: ' . $e->getMessage())
                ->withInput()
                ->withErrors($validatedData, $errorBagName);
        }
    }

    /**
     * Remove the specified instructor from web request.
     * حذف مدرس محدد قادم من طلب ويب
     */
    // public function destroy(Instructor $instructor)
    // {
    //     // (اختياري) التحقق من السجلات المرتبطة
    //     // if ($instructor->scheduleEntries()->exists()) { ... }

    //     // 1. Delete from Database
    //     try {
    //         $instructor->delete();
    //         // 2. Redirect
    //         return redirect()->route('data-entry.instructors.index') // تأكد من اسم الروت
    //             ->with('success', 'Instructor record deleted successfully.');
    //     } catch (Exception $e) {
    //         Log::error('Instructor Deletion Failed (Web): ' . $e->getMessage());
    //         return redirect()->route('data-entry.instructors.index') // تأكد من اسم الروت
    //             ->with('error', 'Failed to delete instructor record.');
    //     }
    // }

    public function destroy(Instructor $instructor)
    {
        // (اختياري) التحقق من وجود ارتباطات أخرى للمدرس قبل الحذف (مثل شعب معينة له)
        // if ($instructor->sections()->exists() || $instructor->scheduleEntries()->exists()) {
        //     return redirect()->route('data-entry.instructors.index')
        //                      ->with('error', 'Cannot delete instructor. They are assigned to sections or schedules.');
        // }

        DB::beginTransaction();
        try {
            $user = $instructor->user; // جلب المستخدم المرتبط

            // حذف سجل المدرس أولاً (سيؤدي لتشغيل onDelete('set null') إذا كان user_id في instructors يقبله)
            // لكن بما أننا سنحذف اليوزر، ترتيب الحذف هنا ليس حرجاً جداً
            $instructor->delete();

            // ثم حذف المستخدم المرتبط (إذا وجد)
            if ($user) {
                // (اختياري) تحقق إذا كان هذا المستخدم له أدوار أخرى غير "instructor" أو "hod"
                // إذا كان له أدوار إدارية أخرى هامة، قد لا ترغب بحذفه تلقائياً
                // if ($user->role->name === 'admin' && User::where('role_id', $user->role_id)->count() <= 1) {
                //     DB::rollBack();
                //     return redirect()->route('data-entry.instructors.index')
                //                      ->with('error', 'Cannot delete the last admin user via instructor deletion.');
                // }
                $user->delete();
            }

            DB::commit();
            return redirect()->route('data-entry.instructors.index')
                ->with('success', 'Instructor and associated user account deleted successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Instructor & User Deletion Failed for Instructor ID ' . $instructor->id . ': ' . $e->getMessage());
            return redirect()->route('data-entry.instructors.index')
                ->with('error', 'Failed to delete instructor: ' . $e->getMessage());
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
            $query = Instructor::with([
                'user:id,name,email,role_id', // جلب دور المستخدم أيضاً
                'user.role:id,name,display_name', // تفاصيل الدور
                'department:id,department_name'
            ]);

            if ($request->has('department_id')) {
                $query->where('department_id', $request->department_id);
            }
            if ($request->has('q')) {
                $searchTerm = $request->q;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('instructor_no', 'like', "%{$searchTerm}%")
                      ->orWhere('instructor_name', 'like', "%{$searchTerm}%")
                      ->orWhereHas('user', fn($userQuery) => $userQuery->where('name', 'like', "%{$searchTerm}%")->orWhere('email', 'like', "%{$searchTerm}%"));
                });
            }

            // --- الـ Pagination للـ API (معطل حالياً، جلب الكل) ---
            $instructors = $query->latest('id')->get();
            /*
            $perPage = $request->query('per_page', 15);
            $instructorsPaginated = $query->latest('id')->paginate($perPage);
            return response()->json([
                'success' => true,
                'data' => $instructorsPaginated->items(),
                'pagination' => [ 'total' => $instructorsPaginated->total(), ... ]
            ], 200);
            */

            return response()->json(['success' => true, 'data' => $instructors], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching instructors: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * Store a newly created instructor and associated user from API request.
     */
    public function apiStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // User fields
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8', // لا حاجة لـ confirmed في API عادةً
            'role_id_for_instructor' => 'required|integer|exists:roles,id',

            // Instructor fields
            'instructor_no' => 'required|string|max:20|unique:instructors,instructor_no',
            'academic_degree' => 'nullable|string|max:100',
            'department_id' => 'required|integer|exists:departments,id',
            'max_weekly_hours' => 'nullable|integer|min:0|max:100',
            // 'office_location' => 'nullable|string|max:255',
            // 'office_hours' => 'nullable|string|max:255',
            'availability_preferences' => 'nullable|string', // أو 'nullable|json'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated(); // الحصول على البيانات المتحقق منها

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'role_id' => $validatedData['role_id_for_instructor'],
                'email_verified_at' => now(), // افترض التفعيل المباشر للـ API
            ]);

            $instructor = Instructor::create([
                'user_id' => $user->id,
                'instructor_no' => $validatedData['instructor_no'],
                'instructor_name' => $user->name,
                'academic_degree' => $validatedData['academic_degree'] ?? null,
                'department_id' => $validatedData['department_id'],
                'max_weekly_hours' => $validatedData['max_weekly_hours'] ?? null,
                // 'office_location' => $validatedData['office_location'] ?? null,
                // 'office_hours' => $validatedData['office_hours'] ?? null,
                'availability_preferences' => $validatedData['availability_preferences'] ?? null,
            ]);

            DB::commit();
            // تحميل العلاقات لعرضها في الاستجابة
            $instructor->load(['user.role', 'department']);
            return response()->json([
                'success' => true,
                'data' => $instructor,
                'message' => 'Instructor and user account created successfully.'
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('API Instructor & User Creation Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create instructor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified instructor (API).
     */
    public function apiShow(Instructor $instructor) // Route Model Binding
    {
        try {
            $instructor->load(['user.role', 'department', 'subjects:subjects.id,subject_no,subject_name']); // تحميل المواد المعينة أيضاً
            return response()->json(['success' => true, 'data' => $instructor], 200);
        } catch (Exception $e) {
            Log::error("API Error fetching instructor ID {$instructor->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Instructor not found or server error.'], 404); // أو 500
        }
    }

    /**
     * Update the specified instructor and associated user from API request.
     */
    public function apiUpdate(Request $request, Instructor $instructor)
    {
        $user = $instructor->user;
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User account for this instructor not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            // User fields (sometimes لأن الـ API قد يرسل جزءاً من البيانات)
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'required|string|min:8', // كلمة المرور اختيارية
            'role_id_for_instructor' => 'sometimes|required|integer|exists:roles,id',

            // Instructor fields
            'instructor_no' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('instructors')->ignore($instructor->id)],
            'academic_degree' => 'sometimes|nullable|string|max:100',
            'department_id' => 'sometimes|required|integer|exists:departments,id',
            'max_weekly_hours' => 'sometimes|nullable|integer|min:0|max:100',
            // 'office_location' => 'sometimes|nullable|string|max:255',
            // 'office_hours' => 'sometimes|nullable|string|max:255',
            'availability_preferences' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated(); // الحصول فقط على البيانات التي تم التحقق منها وتم إرسالها

        DB::beginTransaction();
        try {
            // 1. تحديث بيانات المستخدم (فقط إذا تم إرسالها)
            $userDataToUpdate = [];
            if (isset($validatedData['name'])) $userDataToUpdate['name'] = $validatedData['name'];
            if (isset($validatedData['email'])) $userDataToUpdate['email'] = $validatedData['email'];
            if (isset($validatedData['role_id_for_instructor'])) $userDataToUpdate['role_id'] = $validatedData['role_id_for_instructor'];
            if (!empty($validatedData['password'])) {
                $userDataToUpdate['password'] = Hash::make($validatedData['password']);
            }
            if (!empty($userDataToUpdate)) {
                $user->update($userDataToUpdate);
            }

            // 2. تحديث بيانات المدرس (فقط إذا تم إرسالها)
            $instructorDataToUpdate = [];
            if (isset($validatedData['instructor_no'])) $instructorDataToUpdate['instructor_no'] = $validatedData['instructor_no'];
            if (isset($validatedData['name'])) $instructorDataToUpdate['instructor_name'] = $validatedData['name']; // تحديث اسم المدرس
            if (array_key_exists('academic_degree', $validatedData)) $instructorDataToUpdate['academic_degree'] = $validatedData['academic_degree'];
            if (isset($validatedData['department_id'])) $instructorDataToUpdate['department_id'] = $validatedData['department_id'];
            if (array_key_exists('max_weekly_hours', $validatedData)) $instructorDataToUpdate['max_weekly_hours'] = $validatedData['max_weekly_hours'];
            // if (array_key_exists('office_location', $validatedData)) $instructorDataToUpdate['office_location'] = $validatedData['office_location'];
            // if (array_key_exists('office_hours', $validatedData)) $instructorDataToUpdate['office_hours'] = $validatedData['office_hours'];
            if (array_key_exists('availability_preferences', $validatedData)) $instructorDataToUpdate['availability_preferences'] = $validatedData['availability_preferences'];

            if (!empty($instructorDataToUpdate)) {
                $instructor->update($instructorDataToUpdate);
            }

            DB::commit();
            $instructor->load(['user.role', 'department']); // إعادة تحميل العلاقات
            return response()->json([
                'success' => true,
                'data' => $instructor,
                'message' => 'Instructor and user account updated successfully.'
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('API Instructor & User Update Failed for Instructor ID ' . $instructor->id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update instructor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified instructor and their associated user account (API).
     */
    public function apiDestroy(Instructor $instructor)
    {
        // (اختياري) التحقق من الارتباطات قبل الحذف
        // if ($instructor->sections()->exists() || $instructor->scheduleEntries()->exists()) {
        //     return response()->json(['success' => false, 'message' => 'Cannot delete: Instructor has assignments.'], 409);
        // }

        DB::beginTransaction();
        try {
            $user = $instructor->user;
            $instructor->delete(); // حذف المدرس
            if ($user) {
                // (اختياري) التحقق من الأدوار الأخرى للمستخدم قبل حذفه
                $user->delete(); // حذف المستخدم
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Instructor and associated user deleted successfully.'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('API Instructor & User Deletion Failed for Instructor ID ' . $instructor->id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete instructor: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Display a listing of the instructors (API).
     */
    // public function apiIndex(Request $request)
    // {
    //     try {
    //         $query = Instructor::with(['user:id,name,email', 'department:id,department_name']);

    //         // (اختياري) فلترة
    //         if ($request->has('department_id')) {
    //             $query->where('department_id', $request->department_id);
    //         }

    //         // --- الخيار 1: جلب كل المدرسين (الحالة الحالية) ---
    //         $instructors = $query->latest('id')->get();

    //         /*
    //         $perPage = $request->query('per_page', 15);
    //         $instructorsPaginated = $query->latest('id')
    //                                       ->paginate($perPage);

    //         return response()->json([
    //             'success' => true,
    //             'data' => $instructorsPaginated->items(),
    //             'pagination' => [
    //                 'total' => $instructorsPaginated->total(),
    //                 'per_page' => $instructorsPaginated->perPage(),
    //                 'current_page' => $instructorsPaginated->currentPage(),
    //                 'last_page' => $instructorsPaginated->lastPage(),
    //             ]
    //         ], 200);
    //         */

    //         return response()->json(['success' => true, 'data' => $instructors], 200);
    //     } catch (Exception $e) {
    //         Log::error('API Error fetching instructors: ' . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Server Error'], 500);
    //     }
    // }

    // /**
    //  * Store a newly created instructor from API request.
    //  */
    // public function apiStore(Request $request)
    // {
    //     // 1. Validation
    //     $validatedData = $request->validate([
    //         'user_id' => 'required|integer|exists:users,id|unique:instructors,user_id',
    //         'instructor_no' => 'required|string|max:20|unique:instructors,instructor_no',
    //         'instructor_name' => 'required|string|max:255',
    //         'academic_degree' => 'nullable|string|max:100',
    //         'department_id' => 'required|integer|exists:departments,id',
    //         'max_weekly_hours' => 'nullable|integer|min:0|max:100',
    //         // 'office_location' => 'nullable|string|max:255',
    //         // 'office_hours' => 'nullable|string|max:255',
    //         'availability_preferences' => 'nullable|string',
    //     ]);

    //     // التحقق من دور المستخدم
    //     $user = User::find($request->user_id);
    //     if (!$user || !$user->hasRole(['instructor', 'hod', 'admin'])) {
    //         return response()->json(['success' => false, 'message' => 'The selected user does not have a valid role.'], 422);
    //     }

    //     // 2. Add to Database
    //     try {
    //         $instructor = Instructor::create($validatedData);
    //         $instructor->load(['user:id,name,email', 'department:id,department_name']); // تحميل العلاقات
    //         // 3. Return Success JSON Response
    //         return response()->json([
    //             'success' => true,
    //             'data' => $instructor,
    //             'message' => 'Instructor created successfully.'
    //         ], 201);
    //     } catch (Exception $e) {
    //         Log::error('API Instructor Creation Failed: ' . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Failed to create instructor.'], 500);
    //     }
    // }

    // /**
    //  * Display the specified instructor (API).
    //  */
    // public function apiShow(Instructor $instructor)
    // {
    //     $instructor->load(['user:id,name,email', 'department:id,department_name']);
    //     return response()->json([
    //         'success' => true,
    //         'data' => $instructor,
    //     ], 200);
    // }

    // /**
    //  * Update the specified instructor from API request.
    //  */
    // public function apiUpdate(Request $request, Instructor $instructor)
    // {
    //     // 1. Validation
    //     $validatedData = $request->validate([
    //         'instructor_no' => [
    //             'sometimes',
    //             'required',
    //             'string',
    //             'max:20',
    //             'unique:instructors,instructor_no,' . $instructor->id,
    //         ],
    //         'instructor_name' => 'sometimes|required|string|max:255',
    //         'academic_degree' => 'sometimes|nullable|string|max:100',
    //         'department_id' => 'sometimes|required|integer|exists:departments,id',
    //         'max_weekly_hours' => 'sometimes|nullable|integer|min:0|max:100',
    //         // 'office_location' => 'sometimes|nullable|string|max:255',
    //         // 'office_hours' => 'sometimes|nullable|string|max:255',
    //         'availability_preferences' => 'sometimes|nullable|string',
    //     ]);

    //     // 2. Update Database
    //     try {
    //         $instructor->update($validatedData);
    //         $instructor->load(['user:id,name,email', 'department:id,department_name']); // تحميل العلاقات بعد التحديث
    //         // 3. Return Success JSON Response
    //         return response()->json([
    //             'success' => true,
    //             'data' => $instructor,
    //             'message' => 'Instructor updated successfully.'
    //         ], 200);
    //     } catch (Exception $e) {
    //         Log::error('API Instructor Update Failed: ' . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Failed to update instructor.'], 500);
    //     }
    // }

    // /**
    //  * Remove the specified instructor from API request.
    //  * حذف مدرس محدد قادم من طلب API
    //  */
    // public function apiDestroy(Instructor $instructor)
    // {
    //     // (اختياري) التحقق من السجلات المرتبطة
    //     // if ($instructor->scheduleEntries()->exists()) { ... }

    //     // 1. Delete from Database
    //     try {
    //         $instructor->delete(); // حذف سجل المدرس فقط، المستخدم المرتبط يبقى
    //         // 2. Return Success JSON Response
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Instructor record deleted successfully.'
    //         ], 200);
    //     } catch (Exception $e) {
    //         Log::error('API Instructor Deletion Failed: ' . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Failed to delete instructor record.'], 500);
    //     }
    // }
} // نهاية الكلاس
