<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Department;
use App\Models\PlanSubject;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Exception;

class PlanController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================
    // الدوال (index, store, update, destroy, manageSubjects, addSubject, removeSubject)
    // تبقى كما هي في الكود السابق الذي أرسلته - تأكد أنها موجودة وصحيحة.

    public function index()
    { /* ... كود index للويب مع pagination ... */
        try {
            $plans = Plan::with('department:id,department_name') // تحديد حقول القسم
                ->latest('id')
                ->paginate(15);
            $departments = Department::orderBy('department_name')->get(['id', 'department_name']); // تحديد الحقول
            return view('dashboard.data-entry.plans', compact('plans', 'departments'));
        } catch (Exception $e) {
            Log::error('Error fetching academic plans: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load academic plans.');
        }
    }
    public function store(Request $request)
    { /* ... كود store للويب ... */
        $validatedData = $request->validate(['plan_no' => 'required|string|max:50|unique:plans,plan_no', 'plan_name' => 'required|string|max:255', 'year' => 'required|integer|digits:4|min:2000', 'plan_hours' => 'required|integer|min:1', 'department_id' => 'required|integer|exists:departments,id', 'is_active' => 'sometimes|boolean',]);
        $data = $validatedData;
        $data['is_active'] = $request->has('is_active');
        try {
            Plan::create($data);
            return redirect()->route('data-entry.plans.index')->with('success', 'Academic Plan created successfully.');
        } catch (Exception $e) {
            Log::error('Plan Creation Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create academic plan.')->withInput();
        }
    }
    public function update(Request $request, Plan $plan)
    { /* ... كود update للويب ... */
        $validatedData = $request->validate(['plan_no' => 'required|string|max:50|unique:plans,plan_no,' . $plan->id, 'plan_name' => 'required|string|max:255', 'year' => 'required|integer|digits:4|min:2000', 'plan_hours' => 'required|integer|min:1', 'department_id' => 'required|integer|exists:departments,id', 'is_active' => 'sometimes|boolean',]);
        $data = $validatedData;
        $data['is_active'] = $request->has('is_active');
        try {
            $plan->update($data);
            return redirect()->route('data-entry.plans.index')->with('success', 'Academic Plan updated successfully.');
        } catch (Exception $e) {
            Log::error('Plan Update Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update academic plan.')->withInput();
        }
    }
    public function destroy(Plan $plan)
    { /* ... كود destroy للويب ... */
        // if ($plan->planSubjectEntries()->exists()) {
        //     return redirect()->route('data-entry.plans.index')->with('error', 'Cannot delete plan. It has subjects assigned.');
        // }
        try {
            Log::warning("Force deleting plan ID: {$plan->id} and its subjects.");
            // استخدام delete() على العلاقة لحذف كل سجلات plan_subjects المرتبطة
            $plan->planSubjectEntries()->delete();
            Log::info("Associated subjects for plan ID: {$plan->id} deleted.");
            $plan->delete();
            return redirect()->route('data-entry.plans.index')->with('success', 'Academic Plan deleted successfully.');
        } catch (Exception $e) {
            Log::error('Plan Deletion Failed: ' . $e->getMessage());
            return redirect()->route('data-entry.plans.index')->with('error', 'Failed to delete academic plan.');
        }
    }
    public function manageSubjects(Plan $plan)
    { /* ... كود manageSubjects للويب ... */
        try {
            $allSubjects = Subject::orderBy('subject_name')->get(['id', 'subject_no', 'subject_name']);
            $addedSubjectIds = $plan->planSubjectEntries()->pluck('subject_id')->toArray();
            return view('dashboard.data-entry.plan-subjects-manage', compact('plan', 'allSubjects', 'addedSubjectIds'));
        } catch (Exception $e) {
            Log::error("Error loading manage subjects view for Plan ID {$plan->id}: " . $e->getMessage());
            return redirect()->route('data-entry.plans.index')->with('error', 'Could not load plan subject management page.');
        }
    }
    public function addSubject(Request $request, Plan $plan, $level, $semester)
    { /* ... كود addSubject للويب ... */
        $validatedData = $request->validate(['subject_id' => ['required', 'integer', 'exists:subjects,id', Rule::unique('plan_subjects')->where(fn($q) => $q->where('plan_id', $plan->id)->where('plan_level', $level)->where('plan_semester', $semester))],], ['subject_id.unique' => 'Subject already added.', 'subject_id.*' => 'Invalid subject selected.']);
        try {
            PlanSubject::create(['plan_id' => $plan->id, 'subject_id' => $validatedData['subject_id'], 'plan_level' => $level, 'plan_semester' => $semester]);
            return redirect()->route('data-entry.plans.manageSubjects', $plan->id)->with('success', 'Subject added successfully.');
        } catch (Exception $e) {
            Log::error("Error adding subject {$request->subject_id} to plan {$plan->id} (L{$level}S{$semester}): " . $e->getMessage());
            return redirect()->route('data-entry.plans.manageSubjects', $plan->id)->with('error', 'Failed to add subject.')->withInput(['subject_id' => $request->subject_id]);
        }
    }
    public function removeSubject(Plan $plan, PlanSubject $planSubject)
    { /* ... كود removeSubject للويب ... */
        if ($planSubject->plan_id !== $plan->id) {
            abort(404);
        }
        try {
            $subjectName = optional($planSubject->subject)->subject_name ?? 'N/A';
            $planSubject->delete();
            return redirect()->route('data-entry.plans.manageSubjects', $plan->id)->with('success', "Subject '{$subjectName}' removed.");
        } catch (Exception $e) {
            Log::error("Error removing plan subject ID {$planSubject->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to remove subject.');
        }
    }


    // =============================================
    //             API Controller Methods
    // =============================================

    /**
     * Display a listing of the academic plans (API).
     * عرض قائمة الخطط للـ API (بدون Pagination حالياً)
     */
    public function apiIndex(Request $request)
    {
        try {
            $query = Plan::with('department:id,department_name'); // تحميل القسم مع حقول محددة

            // (اختياري) فلترة بسيطة
            if ($request->has('department_id')) {
                $query->where('department_id', $request->department_id);
            }
            if ($request->boolean('active')) { // للبحث عن الخطط الفعالة فقط ?active=true
                $query->where('is_active', true);
            }
            if ($request->boolean('inactive')) { // للبحث عن غير الفعالة ?inactive=true
                $query->where('is_active', false);
            }
            if ($request->has('year')) {
                $query->where('year', $request->year);
            }
            if ($request->has('q')) { // بحث بالرقم أو الاسم
                $searchTerm = $request->q;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('plan_no', 'like', "%{$searchTerm}%")
                        ->orWhere('plan_name', 'like', "%{$searchTerm}%");
                });
            }

            // --- جلب كل الخطط (الحالة الحالية) ---
            $plans = $query->latest('id') // الترتيب بالأحدث
                ->get();

            // --- كود الـ Pagination (معطل) ---
            /*
            $perPage = $request->query('per_page', 15);
            $plansPaginated = $query->latest('id')->paginate($perPage);
            return response()->json([
                'success' => true,
                'data' => $plansPaginated->items(),
                'pagination' => [ 'total' => $plansPaginated->total(), ... ]
            ], 200);
            */

            return response()->json(['success' => true, 'data' => $plans], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching plans: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * Store a newly created academic plan from API request.
     * تخزين خطة جديدة قادمة من طلب API
     */
    public function apiStore(Request $request)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'plan_no' => 'required|string|max:50|unique:plans,plan_no',
            'plan_name' => 'required|string|max:255',
            'year' => 'required|integer|digits:4|min:2000',
            'plan_hours' => 'required|integer|min:1',
            'department_id' => 'required|integer|exists:departments,id',
            'is_active' => 'sometimes|boolean', // API يمكنه إرسال true/false مباشرة
        ]);

        // 2. Prepare Data (Handle is_active default if not sent)
        $data = $validatedData;
        $data['is_active'] = $request->boolean('is_active'); // استخدام boolean() للتعامل مع true/false/'1'/'0'

        // 3. Add to Database
        try {
            $plan = Plan::create($data);
            $plan->load('department:id,department_name'); // تحميل القسم لعرضه
            // 4. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $plan,
                'message' => 'Academic Plan created successfully.'
            ], 201);
        } catch (Exception $e) {
            Log::error('API Plan Creation Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create plan.'], 500);
        }
    }

    /**
     * Display the specified academic plan (API).
     * عرض خطة محددة للـ API
     */
    public function apiShow(Plan $plan)
    {
        try {
            // تحميل القسم والمواد المرتبطة (مع تحديد الحقول)
            $plan->load([
                'department:id,department_name',
                // جلب مواد الخطة مرتبة حسب المستوى ثم الفصل
                'planSubjectEntries' => function ($query) {
                    $query->orderBy('plan_level')->orderBy('plan_semester');
                },
                'planSubjectEntries.subject:id,subject_no,subject_name' // تحميل تفاصيل المادة
            ]);
            return response()->json(['success' => true, 'data' => $plan], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching plan details: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not load plan details.'], 500);
        }
    }

    /**
     * Update the specified academic plan from API request.
     * تحديث خطة محددة قادمة من طلب API
     */
    public function apiUpdate(Request $request, Plan $plan)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'plan_no' => ['sometimes', 'required', 'string', 'max:50', 'unique:plans,plan_no,' . $plan->id],
            'plan_name' => 'sometimes|required|string|max:255',
            'year' => 'sometimes|required|integer|digits:4|min:2000',
            'plan_hours' => 'sometimes|required|integer|min:1',
            'department_id' => 'sometimes|required|integer|exists:departments,id',
            'is_active' => 'sometimes|boolean',
        ]);

        // 2. Prepare Data for Update
        $data = $validatedData;
        // تحديث is_active فقط إذا تم إرسالها
        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        // 3. Update Database
        try {
            $plan->update($data);
            $plan->load('department:id,department_name'); // تحميل القسم بعد التحديث
            // 4. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $plan,
                'message' => 'Academic Plan updated successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API Plan Update Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update plan.'], 500);
        }
    }

    /**
     * Remove the specified academic plan from API request.
     * حذف خطة محددة قادمة من طلب API
     */
    public function apiDestroy(Plan $plan)
    {
        // التحقق من وجود مواد مرتبطة
        if ($plan->planSubjectEntries()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete plan. It has subjects assigned.'], 409);
        }

        // 1. Delete
        try {
            $plan->delete();
            // 2. Return Success JSON Response
            return response()->json(['success' => true, 'message' => 'Academic Plan deleted successfully.'], 200);
        } catch (Exception $e) {
            Log::error('API Plan Deletion Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete plan.'], 500);
        }
    }

    // --- API Methods for Plan Subjects (Add/Remove) ---

    /**
     * Add a subject to a plan via API.
     * (Note: Level and semester come from the request body here)
     */
    public function apiAddSubject(Request $request, Plan $plan)
    {
        $validatedData = $request->validate([
            'subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
                Rule::unique('plan_subjects')->where(function ($query) use ($plan, $request) {
                    return $query->where('plan_id', $plan->id)
                        ->where('plan_level', $request->input('plan_level'))
                        ->where('plan_semester', $request->input('plan_semester'));
                }),
            ],
            'plan_level' => 'required|integer|min:1',
            'plan_semester' => 'required|integer|min:1',
        ], ['subject_id.unique' => 'Subject already added to this level/semester.']);

        try {
            $planSubject = PlanSubject::create([
                'plan_id' => $plan->id,
                'subject_id' => $validatedData['subject_id'],
                'plan_level' => $validatedData['plan_level'],
                'plan_semester' => $validatedData['plan_semester'],
            ]);
            $planSubject->load('subject:id,subject_no,subject_name'); // Load subject details
            return response()->json(['success' => true, 'data' => $planSubject, 'message' => 'Subject added to plan.'], 201);
        } catch (Exception $e) {
            Log::error("API Error adding subject {$request->subject_id} to plan {$plan->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to add subject.'], 500);
        }
    }

    /**
     * Remove a subject from a plan via API.
     * (Uses Route Model Binding for PlanSubject)
     */
    public function apiRemoveSubject(Plan $plan, PlanSubject $planSubject)
    {
        if ($planSubject->plan_id !== $plan->id) {
            return response()->json(['success' => false, 'message' => 'Subject association not found in this plan.'], 404);
        }

        try {
            $planSubject->delete();
            return response()->json(['success' => true, 'message' => 'Subject removed from plan.'], 200);
        } catch (Exception $e) {
            Log::error("API Error removing plan subject ID {$planSubject->id} from plan {$plan->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to remove subject.'], 500);
        }
    }
}
