<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // اختياري لكن مفيد للـ debugging
use Illuminate\Validation\Rule; // لاستخدام Rule::unique
use Exception;

class RoleController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================

    /**
     * Display a listing of the roles (Web View) with Pagination.
     */
    public function index()
    {
        // $roles = Role::orderBy('display_name')->paginate(5); // استخدام paginate هنا
        $roles = Role::latest()->paginate(6); // استخدام paginate هنا
        return view('dashboard.data-entry.roles', compact('roles'));
    }

    /**
     * Store a newly created role from web request.
     * تخزين دور جديد قادم من طلب ويب
     */
    public function store(Request $request)
    {
        // 1. Validation
        $request->validate([
            'name' => 'required|string|max:50|unique:roles,name|alpha_dash',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        // 2. Prepare Data
        $data = $request->only(['name', 'display_name', 'description']);
        $data['name'] = strtolower($data['name']);

        // 3. Add to Database
        try {
            Role::create($data);
            // 4. Redirect with Success Message
            return redirect()->route('data-entry.roles.index') // تأكد من اسم الروت
                ->with('success', 'Role created successfully.');
        } catch (Exception $e) {
            Log::error('Role Creation Failed (Web): ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create role.')
                ->withInput();
        }
    }

    /**
     * Update the specified role from web request.
     * تحديث دور محدد قادم من طلب ويب
     */
    public function update(Request $request, Role $role)
    {
        // 1. Validation
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('roles')->ignore($role->id), // تجاهل الدور الحالي
            ],
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        // 2. Prepare Data
        $data = $request->only(['name', 'display_name', 'description']);
        $data['name'] = strtolower($data['name']);

        // (اختياري) منع تعديل اسم النظام للأدوار الأساسية
        // $coreRoles = ['admin', 'hod', 'instructor', 'student'];
        // if (in_array($role->name, $coreRoles) && $role->name !== $data['name']) {
        //     return redirect()->back()
        //                      ->with('error', 'Cannot change the system name of core roles.')
        //                      ->withInput();
        // }


        // 3. Update Database
        try {
            $role->update($data);
            // 4. Redirect
            return redirect()->route('data-entry.roles.index') // تأكد من اسم الروت
                ->with('success', 'Role updated successfully.');
        } catch (Exception $e) {
            Log::error('Role Update Failed (Web): ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update role.')
                ->withInput();
        }
    }

    /**
     * Remove the specified role from web request.
     * حذف دور محدد قادم من طلب ويب
     */
    public function destroy(Role $role)
    {
        // التحقق إذا كان هناك مستخدمون مرتبطون بهذا الدور
        if ($role->users()->exists()) {
            return redirect()->route('data-entry.roles.index') // تأكد من اسم الروت
                ->with('error', 'Cannot delete role. Users are assigned to this role.');
        }

        // (اختياري) منع حذف الأدوار الأساسية
        // $coreRoles = ['admin', 'hod', 'instructor', 'student'];
        // if (in_array($role->name, $coreRoles)) {
        //     return redirect()->route('data-entry.roles.index')
        //                      ->with('error', 'Cannot delete core system roles.');
        // }

        // 1. Delete from Database
        try {
            $role->delete();
            // 2. Redirect
            return redirect()->route('data-entry.roles.index') // تأكد من اسم الروت
                ->with('success', 'Role deleted successfully.');
        } catch (Exception $e) {
            Log::error('Role Deletion Failed (Web): ' . $e->getMessage());
            return redirect()->route('data-entry.roles.index') // تأكد من اسم الروت
                ->with('error', 'Failed to delete role.');
        }
    }

    // =============================================
    //             API Controller Methods
    // =============================================

    /**
     * Display a listing of the roles (API).
     * عرض قائمة الأدوار للـ API
     */
    public function apiIndex(Request $request) // إضافة Request للتحكم بالـ Pagination
    {
        try {
            $roles = Role::latest('id')->get();

            return response()->json([
                'success' => true,
                'data' => $roles
            ], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching roles: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * Store a newly created role from API request.
     */
    public function apiStore(Request $request)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name|alpha_dash',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);
        // $validatedData['name'] = strtolower($validatedData['name']);

        // 2. Add to Database
        try {
            $role = Role::create($validatedData);
            // 3. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $role,
                'message' => 'Role created successfully.'
            ], 201); // 201 Created
        } catch (Exception $e) {
            Log::error('API Role Creation Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create role.'], 500);
        }
    }

    /**
     * Display the specified role (API).
     */
    public function apiShow(Role $role)
    {
        // dd($role->name);
        return response()->json([
            'success' => true,
            'data' => $role,
        ], 200);
    }
    // Route::get('/role/{id}', [RoomController::class, 'apiShow']);
    // public function apiShow($id) // <-- تستقبل الـ ID فقط (كـ string أو int)
    // {
    //     // تحتاج للبحث عن الموديل يدوياً
    //     $role = Role::find($id); // أو findOrFail($id)

    //     if (!$role) {
    //         return response()->json(['success' => false, 'message' => 'Role not found.'], 404);
    //     }

    //     return response()->json(['success' => true, 'data' => $role], 200);
    // }


    /**
     * Update the specified role from API request.
     */
    public function apiUpdate(Request $request, Role $role)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'name' => [
                'sometimes', // فقط تحقق إذا تم إرسال الحقل
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('roles')->ignore($role->id),
            ],
            'display_name' => 'sometimes|required|string|max:100',
            'description' => 'sometimes|nullable|string|max:255',
        ]);

        // if (isset($validatedData['name'])) {
        //     $validatedData['name'] = strtolower($validatedData['name']);
        // }

        // 2. Update Database
        try {
            $role->update($validatedData);
            // 3. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $role, // إرجاع الدور المحدث
                'message' => 'Role updated successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API Role Update Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update role.'], 500);
        }
    }

    /**
     * Remove the specified role from API request.
     */
    public function apiDestroy(Role $role)
    {
        // التحقق من المستخدمين المرتبطين
        if ($role->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role. Users are assigned.'
            ], 409); // 409 Conflict
        }

        // 1. Delete from Database
        try {
            $role->delete();
            // 2. Return Success JSON Response
            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API Role Deletion Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete role.'], 500);
        }
    }
}
