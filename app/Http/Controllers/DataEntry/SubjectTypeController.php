<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\SubjectType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Exception;

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
}
