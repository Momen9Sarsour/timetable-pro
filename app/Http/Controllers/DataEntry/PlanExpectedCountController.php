<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\PlanExpectedCount; // تم استيراده
use App\Models\Plan; // نحتاج الخطط للـ dropdown
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Exception;

class PlanExpectedCountController extends Controller
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
            // جلب الأعداد مع الخطط، مرتبة بالسنة ثم الخطة ثم المستوى ثم الفصل
            $expectedCounts = PlanExpectedCount::with('plan:id,plan_no,plan_name')
                ->orderBy('academic_year', 'desc')
                ->orderBy('plan_id') // أو حسب اسم الخطة إذا أردت
                ->orderBy('plan_level')
                ->orderBy('plan_semester')
                ->paginate(20); // عدد أكبر في الصفحة

            // جلب الخطط للـ dropdown في المودال
            $plans = Plan::where('is_active', true)->orderBy('plan_name')->get(['id', 'plan_no', 'plan_name']);

            return view('dashboard.data-entry.plan-expected-counts', compact('expectedCounts', 'plans'));
        } catch (Exception $e) {
            Log::error('Error fetching expected counts: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load expected counts.');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validation مع التحقق من التفرد
        $validator = Validator::make($request->all(), [
            'academic_year' => 'required|integer|digits:4|min:2020',
            'plan_id' => 'required|integer|exists:plans,id',
            'plan_level' => 'required|integer|min:1|max:6',
            'plan_semester' => 'required|integer|min:1|max:3',
            'male_count' => 'required|integer|min:0',
            'female_count' => 'required|integer|min:0',
            'branch' => 'nullable|string|max:100',
        ]);

        // التحقق من التفرد يدوياً (لنفس الخطة، المستوى، الفصل، السنة، والفرع)
        $validator->after(function ($validator) use ($request) {
            if (!$validator->errors()->hasAny(['academic_year', 'plan_id', 'plan_level', 'plan_semester'])) {
                $exists = PlanExpectedCount::where('academic_year', $request->input('academic_year'))
                    ->where('plan_id', $request->input('plan_id'))
                    ->where('plan_level', $request->input('plan_level'))
                    ->where('plan_semester', $request->input('plan_semester'))
                    // التعامل مع الفرع - إذا كان فارغاً، نعتبره NULL
                    ->where(function ($query) use ($request) {
                        if (empty($request->input('branch'))) {
                            $query->whereNull('branch');
                        } else {
                            $query->where('branch', $request->input('branch'));
                        }
                    })
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('count_unique', 'An expected count entry already exists for this specific plan, year, level, semester, and branch.');
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'store') // استخدام error bag
                ->withInput();
        }

        // 2. Prepare Data
        $data = $validator->validated();
        // تعيين branch إلى null إذا كان فارغاً
        $data['branch'] = empty($data['branch']) ? null : $data['branch'];

        // 3. Add to Database
        try {
            PlanExpectedCount::create($data);
            return redirect()->route('data-entry.plan-expected-counts.index')
                ->with('success', 'Expected count created successfully.');
        } catch (Exception $e) {
            Log::error('Expected Count Creation Failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create expected count.')
                ->withInput();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PlanExpectedCount $planExpectedCount) // استخدام Route Model Binding
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'academic_year' => 'required|integer|digits:4|min:2020',
            'plan_id' => 'required|integer|exists:plans,id',
            'plan_level' => 'required|integer|min:1|max:6',
            'plan_semester' => 'required|integer|min:1|max:3',
            'male_count' => 'required|integer|min:0',
            'female_count' => 'required|integer|min:0',
            'branch' => 'nullable|string|max:100',
        ]);

        // 2. التحقق من التفرد يدوياً (مع تجاهل الصف الحالي)
        $validator->after(function ($validator) use ($request, $planExpectedCount) {
            if (!$validator->errors()->hasAny(['academic_year', 'plan_id', 'plan_level', 'plan_semester'])) {
                $exists = PlanExpectedCount::where('academic_year', $request->input('academic_year'))
                    ->where('plan_id', $request->input('plan_id'))
                    ->where('plan_level', $request->input('plan_level'))
                    ->where('plan_semester', $request->input('plan_semester'))
                    ->where(function ($query) use ($request) {
                        if (empty($request->input('branch'))) {
                            $query->whereNull('branch');
                        } else {
                            $query->where('branch', $request->input('branch'));
                        }
                    })
                    ->where('id', '!=', $planExpectedCount->id) // استثناء السجل الحالي
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('count_unique', 'Another expected count entry already exists for this specific combination.');
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'update_' . $planExpectedCount->id) // error bag للتحديث
                ->withInput();
        }

        // 3. Prepare Data
        $data = $validator->validated();
        $data['branch'] = empty($data['branch']) ? null : $data['branch'];

        // 4. Update Database
        try {
            $planExpectedCount->update($data);
            return redirect()->route('data-entry.plan-expected-counts.index')
                ->with('success', 'Expected count updated successfully.');
        } catch (Exception $e) {
            Log::error('Expected Count Update Failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update expected count.')
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PlanExpectedCount $planExpectedCount)
    {
        // لا يوجد عادةً ارتباطات تمنع حذف هذا السجل
        try {
            $planExpectedCount->delete();
            return redirect()->route('data-entry.plan-expected-counts.index')
                ->with('success', 'Expected count deleted successfully.');
        } catch (Exception $e) {
            Log::error('Expected Count Deletion Failed: ' . $e->getMessage());
            return redirect()->route('data-entry.plan-expected-counts.index')
                ->with('error', 'Failed to delete expected count.');
        }
    }

    // =============================================
    //             API Controller Methods
    // =============================================

    /**
     * Display a listing of the expected counts (API).
     * عرض قائمة الأعداد المتوقعة للـ API (بدون Pagination حالياً)
     */
    public function apiIndex(Request $request)
    {
        try {
            $query = PlanExpectedCount::with('plan:id,plan_no,plan_name'); // تحميل الخطة

            // (اختياري) فلترة
            if ($request->has('academic_year')) {
                $query->where('academic_year', $request->academic_year);
            }
            if ($request->has('plan_id')) {
                $query->where('plan_id', $request->plan_id);
            }
            if ($request->has('level')) {
                $query->where('plan_level', $request->level);
            }
            if ($request->has('semester')) {
                $query->where('plan_semester', $request->semester);
            }
            if ($request->has('branch')) {
                if (strtolower($request->branch) == 'null' || $request->branch == '') {
                    $query->whereNull('branch');
                } else {
                    $query->where('branch', $request->branch);
                }
            }

            // --- جلب كل النتائج (الحالة الحالية) ---
            $expectedCounts = $query->orderBy('academic_year', 'desc')
                ->orderBy('plan_id')
                ->orderBy('plan_level')
                ->orderBy('plan_semester')
                ->get();

            // --- كود الـ Pagination للـ API (معطل) ---
            /*
            $perPage = $request->query('per_page', 25); // عدد أكبر في الصفحة
            $expectedCountsPaginated = $query->orderBy('academic_year', 'desc')
                                             ->orderBy('plan_id')
                                             ->orderBy('plan_level')
                                             ->orderBy('plan_semester')
                                             ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $expectedCountsPaginated->items(),
                'pagination' => [ 'total' => $expectedCountsPaginated->total(), ... ]
            ], 200);
            */

            return response()->json(['success' => true, 'data' => $expectedCounts], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching expected counts: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * Store a newly created expected count from API request.
     * تخزين عدد متوقع جديد قادم من طلب API
     */
    public function apiStore(Request $request)
    {
        // 1. Validation (نفس قواعد الويب ولكن بدون after للتبسيط الأولي للـ API)
        // التحقق من التفرد يمكن إضافته كقاعدة Rule::unique مركبة إذا لزم الأمر
        $validator = Validator::make($request->all(), [
            'academic_year' => 'required|integer|digits:4|min:2020',
            'plan_id' => 'required|integer|exists:plans,id',
            'plan_level' => 'required|integer|min:1|max:6',
            'plan_semester' => 'required|integer|min:1|max:3',
            'male_count' => 'required|integer|min:0',
            'female_count' => 'required|integer|min:0',
            'branch' => 'nullable|string|max:100',
            // يمكنك إضافة قاعدة unique مركبة هنا إذا أردت
        ]);

        // التحقق من التفرد يدوياً (لضمان الدقة كما في الويب)
        $validator->after(function ($validator) use ($request) {
            if (!$validator->errors()->hasAny()) { // تحقق فقط إذا لم يكن هناك أخطاء أخرى
                $exists = PlanExpectedCount::where('academic_year', $request->input('academic_year'))
                    ->where('plan_id', $request->input('plan_id'))
                    ->where('plan_level', $request->input('plan_level'))
                    ->where('plan_semester', $request->input('plan_semester'))
                    ->where(function ($query) use ($request) {
                        if (empty($request->input('branch'))) {
                            $query->whereNull('branch');
                        } else {
                            $query->where('branch', $request->input('branch'));
                        }
                    })->exists();
                if ($exists) {
                    $validator->errors()->add('count_unique', 'Entry already exists for this combination.');
                }
            }
        });


        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // 2. Prepare Data
        $data = $validator->validated();
        $data['branch'] = empty($data['branch']) ? null : $data['branch'];

        // 3. Add to Database
        try {
            $count = PlanExpectedCount::create($data);
            $count->load('plan:id,plan_no,plan_name'); // تحميل الخطة للاستجابة
            // 4. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $count,
                'message' => 'Expected count created successfully.'
            ], 201);
        } catch (Exception $e) {
            Log::error('API Expected Count Creation Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create expected count.'], 500);
        }
    }

    /**
     * Display the specified expected count (API).
     * عرض عدد متوقع محدد للـ API
     */
    public function apiShow(PlanExpectedCount $planExpectedCount) // Route Model Binding
    {
        try {
            $planExpectedCount->load('plan:id,plan_no,plan_name'); // تحميل الخطة المرتبطة
            return response()->json(['success' => true, 'data' => $planExpectedCount], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching expected count details: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not load details.'], 500);
        }
    }

    /**
     * Update the specified expected count from API request.
     * تحديث عدد متوقع محدد قادم من طلب API
     */
    public function apiUpdate(Request $request, PlanExpectedCount $planExpectedCount)
    {
        // 1. Validation (استخدام sometimes)
        $validator = Validator::make($request->all(), [
            'academic_year' => 'sometimes|required|integer|digits:4|min:2020',
            'plan_id' => 'sometimes|required|integer|exists:plans,id',
            'plan_level' => 'sometimes|required|integer|min:1|max:6',
            'plan_semester' => 'sometimes|required|integer|min:1|max:3',
            'male_count' => 'sometimes|required|integer|min:0',
            'female_count' => 'sometimes|required|integer|min:0',
            'branch' => 'sometimes|nullable|string|max:100',
        ]);

        // التحقق من التفرد يدوياً (مع تجاهل الصف الحالي)
        $validator->after(function ($validator) use ($request, $planExpectedCount) {
            if (!$validator->errors()->hasAny()) {
                $exists = PlanExpectedCount::where('academic_year', $request->input('academic_year', $planExpectedCount->academic_year)) // استخدام القيمة الحالية كافتراضي
                    ->where('plan_id', $request->input('plan_id', $planExpectedCount->plan_id))
                    ->where('plan_level', $request->input('plan_level', $planExpectedCount->plan_level))
                    ->where('plan_semester', $request->input('plan_semester', $planExpectedCount->plan_semester))
                    ->where(function ($query) use ($request, $planExpectedCount) {
                        $branch = $request->input('branch', $planExpectedCount->branch); // التعامل مع القيمة الافتراضية للفرع
                        if (is_null($branch) || $branch === '') {
                            $query->whereNull('branch');
                        } else {
                            $query->where('branch', $branch);
                        }
                    })
                    ->where('id', '!=', $planExpectedCount->id)
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('count_unique', 'Another entry already exists for this combination.');
                }
            }
        });


        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // 2. Prepare Data for Update
        $data = $validator->validated();
        // التعامل مع branch إذا تم إرساله
        if ($request->has('branch')) {
            $data['branch'] = empty($data['branch']) ? null : $data['branch'];
        }

        // 3. Update Database
        try {
            $planExpectedCount->update($data);
            $planExpectedCount->load('plan:id,plan_no,plan_name'); // تحميل الخطة بعد التحديث
            // 4. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $planExpectedCount,
                'message' => 'Expected count updated successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API Expected Count Update Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update expected count.'], 500);
        }
    }

    /**
     * Remove the specified expected count from API request.
     * حذف عدد متوقع محدد قادم من طلب API
     */
    public function apiDestroy(PlanExpectedCount $planExpectedCount)
    {
        // لا يوجد قيود عادةً
        try {
            $planExpectedCount->delete();
            return response()->json([
                'success' => true,
                'message' => 'Expected count deleted successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API Expected Count Deletion Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete expected count.'], 500);
        }
    }
}
