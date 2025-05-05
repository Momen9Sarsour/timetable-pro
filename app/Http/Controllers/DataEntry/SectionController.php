<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\Section; // تم استيراده
use App\Models\PlanSubject; // نحتاج لمدخلات الخطة/المادة
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Exception;

class SectionController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) // إضافة Request للفلترة
    {
        try {
            // جلب الشعب مع العلاقات المتداخلة للفرز والعرض
            // استخدام when للفلترة الاختيارية
            $query = Section::with([
                'planSubject.plan:id,plan_no', // جلب الخطة
                'planSubject.subject:id,subject_no,subject_name' // جلب المادة
            ])
                ->when($request->filled('plan_id'), function ($q) use ($request) {
                    $q->whereHas('planSubject.plan', fn($subQ) => $subQ->where('id', $request->plan_id));
                })
                ->when($request->filled('level'), function ($q) use ($request) {
                    $q->whereHas('planSubject', fn($subQ) => $subQ->where('plan_level', $request->level));
                })
                ->when($request->filled('semester'), function ($q) use ($request) {
                    // يجب التحقق من فصل الشعبة نفسها
                    $q->where('semester', $request->semester);
                })
                ->when($request->filled('academic_year'), function ($q) use ($request) {
                    $q->where('academic_year', $request->academic_year);
                });


            $sections = $query->orderBy('academic_year', 'desc')
                ->orderBy('semester')
                ->orderBy(function ($q) { // ترتيب معقد حسب الخطة والمستوى والمادة
                    $q->from('plan_subjects')->whereColumn('plan_subjects.id', 'sections.plan_subject_id')->selectRaw('plan_id');
                })
                ->orderBy(function ($q) {
                    $q->from('plan_subjects')->whereColumn('plan_subjects.id', 'sections.plan_subject_id')->selectRaw('plan_level');
                })
                ->orderBy(function ($q) {
                    $q->from('plan_subjects')->whereColumn('plan_subjects.id', 'sections.plan_subject_id')->selectRaw('subject_id');
                })
                ->orderBy('section_number')
                ->paginate(20);

            // جلب بيانات plan_subjects للـ dropdown في المودال
            // (قد يكون كبيراً جداً، الأفضل جلبها حسب الخطة/المستوى/الفصل المختارة)
            // سنقوم بجلبها كلها الآن للتبسيط، مع تحميل العلاقات اللازمة
            $planSubjects = PlanSubject::with(['plan:id,plan_name', 'subject:id,subject_no,subject_name'])
                ->whereHas('plan', fn($q) => $q->where('is_active', true)) // فقط للخطط الفعالة
                ->get();

            return view('dashboard.data-entry.sections', compact('sections', 'planSubjects'));
        } catch (Exception $e) {
            Log::error('Error fetching sections: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load sections.');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validation (مع التحقق من تفرد رقم الشعبة لنفس المادة/السنة/الفصل/الفرع)
        $validator = Validator::make($request->all(), [
            'academic_year' => 'required|integer|digits:4|min:2020',
            'semester' => 'required|integer|min:1|max:3',
            'plan_subject_id' => 'required|integer|exists:plan_subjects,id',
            'section_number' => 'required|integer|min:1',
            'student_count' => 'required|integer|min:0',
            'section_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
            'branch' => 'nullable|string|max:100',
        ]);

        // التحقق من التفرد يدوياً
        $validator->after(function ($validator) use ($request) {
            if (!$validator->errors()->hasAny(['academic_year', 'semester', 'plan_subject_id', 'section_number'])) {
                $exists = Section::where('academic_year', $request->input('academic_year'))
                    ->where('semester', $request->input('semester'))
                    ->where('plan_subject_id', $request->input('plan_subject_id'))
                    ->where('section_number', $request->input('section_number'))
                    ->where(function ($query) use ($request) {
                        if (empty($request->input('branch'))) {
                            $query->whereNull('branch');
                        } else {
                            $query->where('branch', $request->input('branch'));
                        }
                    })->exists();
                if ($exists) {
                    $validator->errors()->add('section_unique', 'This section number already exists for this subject in this year/semester/branch.');
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'store')
                ->withInput();
        }

        // 2. Prepare Data
        $data = $validator->validated();
        $data['branch'] = empty($data['branch']) ? null : $data['branch'];

        // 3. Add to Database
        try {
            Section::create($data);
            return redirect()->route('data-entry.sections.index')
                ->with('success', 'Section created successfully.');
        } catch (Exception $e) {
            Log::error('Section Creation Failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create section.')
                ->withInput();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Section $section)
    {
        // 1. Validation (لا نسمح بتغيير المادة/السنة/الفصل عادةً، فقط رقم الشعبة والعدد والجنس والفرع)
        $validator = Validator::make($request->all(), [
            'section_number' => 'required|integer|min:1',
            'student_count' => 'required|integer|min:0',
            'section_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
            'branch' => 'nullable|string|max:100',
            // لا ننسى تمرير الحقول الأصلية للتحقق من التفرد
            'academic_year' => 'required|integer', // من الحقل المخفي
            'semester' => 'required|integer',
            'plan_subject_id' => 'required|integer|exists:plan_subjects,id', // من الحقل المخفي
        ]);

        // التحقق من التفرد يدوياً (مع تجاهل الصف الحالي)
        $validator->after(function ($validator) use ($request, $section) {
            if (!$validator->errors()->hasAny(['section_number'])) {
                $exists = Section::where('academic_year', $request->input('academic_year')) // استخدام القيمة الأصلية
                    ->where('semester', $request->input('semester')) // استخدام القيمة الأصلية
                    ->where('plan_subject_id', $request->input('plan_subject_id')) // استخدام القيمة الأصلية
                    ->where('section_number', $request->input('section_number')) // القيمة الجديدة
                    ->where(function ($query) use ($request) {
                        if (empty($request->input('branch'))) {
                            $query->whereNull('branch');
                        } else {
                            $query->where('branch', $request->input('branch'));
                        }
                    })
                    ->where('id', '!=', $section->id) // استثناء السجل الحالي
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('section_unique', 'This section number already exists for this subject in this year/semester/branch.');
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'update_' . $section->id)
                ->withInput();
        }

        // 2. Prepare Data (فقط الحقول المسموح بتعديلها)
        $data = $validator->safe()->only(['section_number', 'student_count', 'section_gender', 'branch']); // استخدام safe() للحصول على البيانات التي تم التحقق منها فقط
        $data['branch'] = empty($data['branch']) ? null : $data['branch'];

        // 3. Update Database
        try {
            $section->update($data);
            return redirect()->route('data-entry.sections.index')
                ->with('success', 'Section updated successfully.');
        } catch (Exception $e) {
            Log::error('Section Update Failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update section.')
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Section $section)
    {
        // التحقق من وجود ارتباطات في الجدول النهائي
        // if ($section->scheduleEntries()->exists()) {
        //     return redirect()->route('data-entry.sections.index')
        //                      ->with('error', 'Cannot delete section. It is used in schedules.');
        // }

        try {
            $section->delete();
            return redirect()->route('data-entry.sections.index')
                ->with('success', 'Section deleted successfully.');
        } catch (Exception $e) {
            Log::error('Section Deletion Failed: ' . $e->getMessage());
            return redirect()->route('data-entry.sections.index')
                ->with('error', 'Failed to delete section.');
        }
    }

    // --- API Methods (يمكن إضافتها لاحقاً بنفس النمط) ---

} // نهاية الكلاس
