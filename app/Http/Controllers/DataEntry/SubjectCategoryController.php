<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\SubjectCategory; // تم استيراده
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Exception;

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
}
