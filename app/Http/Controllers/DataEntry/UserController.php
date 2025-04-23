<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log; // اختياري
use Illuminate\Validation\Rules\Password; // للتحقق المعقد (اختياري)
use Illuminate\Validation\Rule;
use Exception;

class UserController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================

    /**
     * Display a listing of the users (Web View) with Pagination.
     */
    public function index()
    {
        try {
            // جلب المستخدمين مرتبين بالأحدث مع الدور وتقسيم الصفحات
            $users = User::with('role')->latest()->paginate(15);

            $roles = Role::orderBy('display_name')->get();

            return view('dashboard.data-entry.users', compact('users', 'roles'));
        } catch (Exception $e) {
            Log::error('Error fetching users for web view: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load users.');
        }
    }

    /**
     * Store a newly created user from web request.
     */
    public function store(Request $request)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed', // أبسط قاعدة
            // 'password' => ['required', 'confirmed', Password::min(8)], // قاعدة أبسط باستخدام Rule
            'role_id' => 'required|integer|exists:roles,id',
            'verify_email' => 'sometimes|boolean', // استخدام sometimes
        ]);

        // 2. Prepare Data
        $data = [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role_id' => $validatedData['role_id'],
            'email_verified_at' => isset($validatedData['verify_email']) && $validatedData['verify_email'] ? now() : null,
        ];

        // 3. Add to Database
        try {
            User::create($data);
            // 4. Redirect
            return redirect()->route('data-entry.users.index')
                ->with('success', 'User created successfully.');
        } catch (Exception $e) {
            Log::error('User Creation Failed (Web): ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create user.')
                ->withInput();
        }
    }

    /**
     * Update the specified user from web request.
     */
    public function update(Request $request, User $user)
    {
        // منع تعديل الأدمن الأول
        if ($user->id === 1 && auth()->id() !== 1) {
            return redirect()->route('data-entry.users.index')
                ->with('error', 'Unauthorized action.');
        }

        // 1. Validation
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed', // كلمة المرور اختيارية عند التحديث
            // 'password' => ['nullable', 'confirmed', Password::min(8)],
            'role_id' => [
                'required',
                'integer',
                'exists:roles,id',
                // دالة التحقق لمنع تغيير دور الأدمن الأول
                function ($attribute, $value, $fail) use ($user) {
                    if ($user->id === 1 && Role::find($value)?->name !== 'admin') {
                        $fail('The primary admin role cannot be changed.');
                    }
                },
            ],
            'verify_email' => 'sometimes|boolean',
            'unverify_email' => 'sometimes|boolean',
        ]);

        // 2. Prepare Data for Update
        $data = [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
        ];

        // إضافة الدور فقط إذا لم يكن الأدمن الأول أو لم يتغير دوره
        if ($user->id !== 1 || $validatedData['role_id'] == $user->role_id) {
            $data['role_id'] = $validatedData['role_id'];
        } else {
            // تجاهل تغيير الدور للأدمن الأول (يمكن إضافة رسالة تحذير)
            session()->flash('warning', 'Primary admin role cannot be changed.');
        }

        // إضافة كلمة المرور فقط إذا تم إدخالها
        if (!empty($validatedData['password'])) {
            $data['password'] = Hash::make($validatedData['password']);
        }

        // تحديث حالة تأكيد الإيميل
        if (isset($validatedData['unverify_email']) && $validatedData['unverify_email']) {
            $data['email_verified_at'] = null;
        } elseif (isset($validatedData['verify_email']) && $validatedData['verify_email']) {
            $data['email_verified_at'] = now();
        }

        // 3. Update Database
        try {
            $user->update($data);
            // 4. Redirect
            return redirect()->route('data-entry.users.index') // تأكد من اسم الروت
                ->with('success', 'User updated successfully.');
        } catch (Exception $e) {
            Log::error('User Update Failed (Web): ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update user.')
                ->withInput();
        }
    }

    /**
     * Remove the specified user from web request.
     */
    public function destroy(User $user)
    {
        // منع حذف الأدمن الأول أو المستخدم الحالي
        if ($user->id === 1) {
            return redirect()->route('data-entry.users.index')
                ->with('error', 'The primary admin user cannot be deleted.');
        }
        if ($user->id === auth()->id()) {
            return redirect()->route('data-entry.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        // 1. Delete from Database
        try {
            $user->delete(); // هذا سيضبط user_id في instructors إلى null بسبب onDelete('set null')
            // 2. Redirect
            return redirect()->route('data-entry.users.index')
                ->with('success', 'User deleted successfully.');
        } catch (Exception $e) {
            Log::error('User Deletion Failed (Web): ' . $e->getMessage());
            return redirect()->route('data-entry.users.index')
                ->with('error', 'Failed to delete user.');
        }
    }


    // =============================================
    //             API Controller Methods
    // =============================================

    /**
     * Display a listing of the users (API).
     * عرض قائمة المستخدمين للـ API (بدون Pagination حالياً)
     */
    public function apiIndex(Request $request)
    {
        try {
            $query = User::with('role:id,name,display_name'); // تحميل الدور مع حقول محددة

            // (اختياري) فلترة
            if ($request->has('role_id')) {
                $query->where('role_id', $request->role_id);
            }
            // if ($request->has('q')) { // بحث بالاسم أو الإيميل
            //     $searchTerm = $request->q;
            //     $query->where(function ($q) use ($searchTerm) {
            //         $q->where('name', 'like', "%{$searchTerm}%")
            //           ->orWhere('email', 'like', "%{$searchTerm}%");
            //     });
            // }

            // --- الخيار 1: جلب كل المستخدمين (الحالة الحالية) ---
            $users = $query->latest('id') // الترتيب بالأحدث
                ->get(['id', 'name', 'email', 'role_id', 'email_verified_at', 'created_at']); // تحديد الحقول

            // --- الخيار 2: كود الـ Pagination للـ API (معطل حالياً) ---
            /*
            $perPage = $request->query('per_page', 15);
            $usersPaginated = $query->latest('id')
                                    ->paginate($perPage, ['id', 'name', 'email', 'role_id', 'email_verified_at', 'created_at']);

            // نحتاج لتحميل الدور يدوياً بعد الـ pagination
            // $usersPaginated->load('role:id,name,display_name');

            return response()->json([
                'success' => true,
                'data' => $usersPaginated->items(),
                'pagination' => [
                    'total' => $usersPaginated->total(),
                    'per_page' => $usersPaginated->perPage(),
                    'current_page' => $usersPaginated->currentPage(),
                    'last_page' => $usersPaginated->lastPage(),
                ]
            ], 200);
            */
            // --- نهاية كود الـ Pagination المعطل ---


            return response()->json(['success' => true, 'data' => $users], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching users: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * Store a newly created user from API request.
     * تخزين مستخدم جديد قادم من طلب API
     */
    public function apiStore(Request $request)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8', // لا نحتاج confirmed في الـ API عادةً
            'role_id' => 'required|integer|exists:roles,id',
            'verify_email' => 'sometimes|boolean',
        ]);

        // 2. Prepare Data
        $data = [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role_id' => $validatedData['role_id'],
            'email_verified_at' => isset($validatedData['verify_email']) && $validatedData['verify_email'] ? now() : null,
        ];

        // 3. Add to Database
        try {
            $user = User::create($data);
            $user->load('role:id,name,display_name'); // تحميل الدور لعرضه
            // 4. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User created successfully.'
            ], 201);
        } catch (Exception $e) {
            Log::error('API User Creation Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create user.'], 500);
        }
    }

    /**
     * Display the specified user (API).
     */
    public function apiShow(User $user)
    {
        $user->load('role:id,name,display_name'); // تحميل الدور
        // $user->load('instructor:id,instructor_no');
        return response()->json([
            'success' => true,
            'data' => $user,
        ], 200);
    }

    /**
     * Update the specified user from API request.
     */
    public function apiUpdate(Request $request, User $user)
    {
        // منع تعديل الأدمن الأول (حماية بسيطة)
        if ($user->id === 1 && auth()->id() !== 1) { // افترض أن المصادقة تعمل
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403); // 403 Forbidden
        }

        // 1. Validation
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],
            'password' => 'sometimes|nullable|string|min:8', // كلمة المرور اختيارية
            'role_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:roles,id',
                function ($attribute, $value, $fail) use ($user) {
                    if ($user->id === 1 && Role::find($value)?->name !== 'admin') {
                        $fail('The primary admin role cannot be changed.');
                    }
                },
            ],
            'verify_email' => 'sometimes|boolean',
            'unverify_email' => 'sometimes|boolean',
        ]);

        // 2. Prepare Data for Update
        $data = [];
        if (isset($validatedData['name'])) $data['name'] = $validatedData['name'];
        if (isset($validatedData['email'])) $data['email'] = $validatedData['email'];
        if (isset($validatedData['role_id']) && ($user->id !== 1 || $validatedData['role_id'] == $user->role_id)) {
            $data['role_id'] = $validatedData['role_id'];
        }
        if (!empty($validatedData['password'])) {
            $data['password'] = Hash::make($validatedData['password']);
        }
        if (isset($validatedData['unverify_email']) && $validatedData['unverify_email']) {
            $data['email_verified_at'] = null;
        } elseif (isset($validatedData['verify_email']) && $validatedData['verify_email']) {
            $data['email_verified_at'] = now();
        }


        // 3. Update Database
        try {
            // فقط قم بالتحديث إذا كان هناك بيانات للتحديث
            if (!empty($data)) {
                $user->update($data);
            }
            $user->load('role:id,name,display_name'); // تحميل الدور بعد التحديث
            // 4. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User updated successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API User Update Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update user.'], 500);
        }
    }

    /**
     * Remove the specified user from API request.
     */
    public function apiDestroy(User $user)
    {
        // منع حذف الأدمن الأول أو المستخدم الحالي (إذا كانت المصادقة مفعلة)
        if ($user->id === 1) {
            return response()->json(['success' => false, 'message' => 'Primary admin cannot be deleted.'], 403);
        }
        if ($user->id === auth()->id()) { // افترض أن المصادقة تعمل
            return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 403);
        }

        // 1. Delete from Database
        try {
            $user->delete();
            // 2. Return Success JSON Response
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API User Deletion Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete user.'], 500);
        }
    }
}
