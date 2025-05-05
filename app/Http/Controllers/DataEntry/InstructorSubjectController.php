<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\Subject;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstructorSubjectController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // جلب المدرسين مع القسم وعدد المواد المعينة (لتجنب تحميل كل المواد)
            $instructors = Instructor::with(['user:id,name', 'department:id,department_name'])
                ->withCount('subjects') // إضافة عمود subjects_count
                ->latest()
                ->paginate(10); // Pagination للصفحة الرئيسية

            return view('dashboard.data-entry.instructor-subject', compact('instructors')); // View جديد للعرض
        } catch (Exception $e) {
            Log::error('Error fetching instructor-subject index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load instructor assignments.');
        }
    }

    /**
     * Show the form for editing the assigned subjects for a specific instructor.
     */
    public function editAssignments(Instructor $instructor) // استخدام Route Model Binding
    {
        try {
            // جلب كل المواد المتاحة
            $allSubjects = Subject::with('department:id,department_name')
                ->orderBy('subject_no')
                ->get(['id', 'subject_no', 'subject_name', 'department_id']);

            // جلب IDs المواد المعينة لهذا المدرس
            $assignedSubjectIds = $instructor->subjects()->pluck('subjects.id')->toArray();

            // توجيه لـ view التعديل
            return view('dashboard.data-entry.instructor-subject-edit', compact(
                'instructor',
                'allSubjects',
                'assignedSubjectIds'
            ));
        } catch (Exception $e) {
            Log::error('Error loading edit assignments view for instructor ID ' . $instructor->id . ': ' . $e->getMessage());
            return redirect()->route('data-entry.instructor-subject.index')->with('error', 'Could not load assignment editing page.');
        }
    }

    /**
     * Update the subjects assigned to the specified instructor.
     */
    public function syncAssignments(Request $request, Instructor $instructor) // استخدام Route Model Binding
    {
        // 1. Validation (فقط للمواد المختارة)
        $validatedData = $request->validate([
            // instructor_id يأتي من الروت
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'integer|exists:subjects,id',
        ]);

        try {
            // 2. استخدام sync() لتحديث الارتباطات
            $instructor->subjects()->sync($validatedData['subject_ids'] ?? []);

            // 3. Redirect إلى صفحة العرض الرئيسية مع رسالة نجاح
            return redirect()->route('data-entry.instructor-subject.index')
                ->with('success', 'Subject assignments updated successfully for ' . ($instructor->instructor_name ?? optional($instructor->user)->name));
        } catch (Exception $e) {
            Log::error('Error syncing subjects for instructor ID ' . $instructor->id . ': ' . $e->getMessage());
            // العودة لصفحة التعديل نفسها مع رسالة خطأ
            return redirect()->route('data-entry.instructor-subject.edit', $instructor->id)
                ->with('error', 'Failed to update subject assignments.');
        }
    }

    // =============================================
    //             API Controller Methods
    // =============================================

    /**
     * Display a listing of instructors with their assigned subject count (API).
     */
    public function apiIndex(Request $request) // إضافة Request للـ pagination المستقبلي
    {
        try {
            $query = Instructor::with(['user:id,name', 'department:id,department_name'])
                ->withCount('subjects'); // حساب عدد المواد

            // (اختياري) فلترة بسيطة
            if ($request->has('department_id')) {
                $query->where('department_id', $request->department_id);
            }

            // --- جلب كل النتائج (بدون pagination حالياً) ---
            $instructors = $query->latest('id')->get();

            // --- كود الـ Pagination (معطل) ---
            /*
            $perPage = $request->query('per_page', 20);
            $instructorsPaginated = $query->latest('id')->paginate($perPage);
            return response()->json([
                'success' => true,
                'data' => $instructorsPaginated->items(),
                'pagination' => [ 'total' => $instructorsPaginated->total(), ... ]
            ], 200);
            */

            return response()->json(['success' => true, 'data' => $instructors], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching instructor assignments index: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * Get all subjects, indicating which are assigned to a specific instructor (API).
     */
    public function apiShowAssignments(Instructor $instructor) // استخدام Route Model Binding
    {
        try {
            // جلب IDs المواد المعينة لهذا المدرس
            $assignedSubjectIds = $instructor->subjects()->pluck('subjects.id')->toArray();

            // جلب كل المواد المتاحة
            $allSubjects = Subject::orderBy('subject_no')
                ->get(['id', 'subject_no', 'subject_name']) // جلب الحقول الأساسية
                ->map(function ($subject) use ($assignedSubjectIds) {
                    // إضافة حقل is_assigned لكل مادة
                    $subject->is_assigned = in_array($subject->id, $assignedSubjectIds);
                    return $subject;
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'instructor' => $instructor->load('user:id,name'), // تحميل بيانات المدرس الأساسية
                    'subjects' => $allSubjects // قائمة كل المواد مع حقل is_assigned
                ]
            ], 200);
        } catch (Exception $e) {
            Log::error("API Error fetching assignment details for instructor ID {$instructor->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }


    /**
     * Sync subjects for a specific instructor (API).
     */
    public function apiSyncAssignments(Request $request, Instructor $instructor)
    {
        $validatedData = $request->validate([
            'subject_ids' => 'present|array',
            'subject_ids.*' => 'integer|exists:subjects,id',
        ]);

        try {
            $instructor->subjects()->sync($validatedData['subject_ids'] ?? []);

            // إرجاع قائمة المواد المعينة المحدثة
            $updatedAssignedSubjects = $instructor->subjects()
                ->orderBy('subject_no')
                ->get(['subjects.id', 'subject_no', 'subject_name']);

            return response()->json([
                'success' => true,
                'message' => 'Subject assignments updated successfully.',
                'data' => $updatedAssignedSubjects
            ], 200);
        } catch (Exception $e) {
            Log::error('API Error syncing subjects for instructor ID ' . $instructor->id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update assignments.'], 500);
        }
    }

    /**
     * Get subjects assigned to a specific instructor (API).
     */
    public function apiGetAssignedSubjects(Instructor $instructor) // استخدام Route Model Binding
    {
        try {
            // جلب المواد المعينة لهذا المدرس فقط مع تحديد الحقول المطلوبة
            $assignedSubjects = $instructor->subjects() // استدعاء علاقة subjects
                ->orderBy('subject_no')
                ->get(['subjects.id', 'subject_no', 'subject_name']); // تحديد الحقول من جدول subjects

            return response()->json([
                'success' => true,
                'data' => $assignedSubjects
            ], 200);
        } catch (Exception $e) {
            Log::error("API Error fetching assigned subjects for instructor ID {$instructor->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * Get available subjects for a specific instructor (API).
     */
    public function apiGetAvailableSubjects(Instructor $instructor)
    {
        try {
            // جلب IDs المواد المعينة بالفعل لهذا المدرس
            $assignedSubjectIds = $instructor->subjects()->pluck('subjects.id')->toArray();

            // جلب كل المواد التي *ليست* ضمن قائمة المواد المعينة
            $availableSubjects = Subject::whereNotIn('id', $assignedSubjectIds)
                ->orderBy('subject_no')
                ->get(['id', 'subject_no', 'subject_name']);

            return response()->json([
                'success' => true,
                'data' => $availableSubjects
            ], 200);
        } catch (Exception $e) {
            Log::error("API Error fetching available subjects for instructor ID {$instructor->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }
}
