<?php

namespace App\Http\Controllers\DataEntry;

use Exception;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Instructor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class InstructorSubjectsController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================

    /**
     * Display a listing of instructors and their assigned subjects.
     */
    public function index()
    {
        try {
            // جلب المدرسين مع تحميل العلاقات اللازمة بكفاءة
            $instructors = Instructor::with([
                'user:id,name',
                'department:id,department_name',
                'subjects:subjects.id,subject_no,subject_name' // جلب المواد المعينة مع الحقول الأساسية
            ])
                ->withCount('subjects') // إضافة عمود subjects_count
                ->latest('id')
                ->paginate(15); // استخدام Pagination

            return view('dashboard.data-entry.instructor-subjects', compact('instructors'));
        } catch (Exception $e) {
            Log::error('Error fetching instructor-subject index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load instructor-subject assignments page.');
        }
    }

    /**
     * Show the form for editing the assigned subjects for a specific instructor.
     */
    public function edit(Instructor $instructor)
    {
        try {
            // تحميل معلومات المدرس الأساسية
            $instructor->load('user:id,name', 'department:id,department_name');

            // جلب كل المواد المتاحة في النظام، مع تحميل قسم كل مادة
            $allSubjects = Subject::with('department:id,department_name')
                ->orderBy('subject_no')
                ->get(['id', 'subject_no', 'subject_name', 'department_id']);

            // جلب IDs المواد المعينة حالياً لهذا المدرس (الطريقة الأكثر أماناً)
            $assignedSubjectIds = $instructor->subjects()->pluck('subjects.id')->toArray();

            return view('dashboard.data-entry.instructor-subject-edit', compact(
                'instructor',
                'allSubjects',
                'assignedSubjectIds'
            ));
        } catch (Exception $e) {
            Log::error('Error loading edit assignments view for instructor ID ' . $instructor->id . ': ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->route('data-entry.instructor-subjects.index')->with('error', 'Could not load the assignment editing page. A server error occurred.');
        }
    }

    /**
     * Update the subjects assigned to the specified instructor.
     */
    public function sync(Request $request, Instructor $instructor)
    {
        $validatedData = $request->validate([
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'integer|exists:subjects,id',
        ], [
            'subject_ids.array' => 'The selected subjects must be in a valid format.',
            'subject_ids.*.exists' => 'One or more of the selected subjects do not exist.',
        ]);

        try {
            // استخدام sync() لتحديث الارتباطات في الجدول الوسيط
            $instructor->subjects()->sync($validatedData['subject_ids'] ?? []);

            $instructorName = $instructor->instructor_name ?? optional($instructor->user)->name ?? "ID: {$instructor->id}";
            return redirect()->route('data-entry.instructor-subjects.index')
                ->with('success', 'Subject assignments updated successfully for ' . $instructorName);
        } catch (Exception $e) {
            Log::error('Error syncing subjects for instructor ID ' . $instructor->id . ': ' . $e->getMessage());
            return redirect()->route('data-entry.instructor-subjects.edit', $instructor->id)
                ->with('error', 'Failed to update subject assignments due to a server error.');
        }
    }



    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     try {
    //         // جلب المدرسين مع القسم وعدد المواد المعينة (لتجنب تحميل كل المواد)
    //         $instructors = Instructor::with(['user:id,name', 'department:id,department_name'])
    //             ->withCount('sections') // إضافة عمود subjects_count
    //             ->latest()
    //             ->paginate(10); // Pagination للصفحة الرئيسية

    //         return view('dashboard.data-entry.instructor-subjects', compact('instructors')); // View جديد للعرض
    //     } catch (Exception $e) {
    //         Log::error('Error fetching instructor-subject index: ' . $e->getMessage());
    //         return redirect()->back()->with('error', 'Could not load instructor assignments.');
    //     }
    // }

    // /**
    //  * Show the form for editing the assigned subjects for a specific instructor.
    //  */

    // public function editAssignments(Instructor $instructor) // Route Model Binding للمدرس
    // {
    //     try {
    //         // جلب كل المواد مع شعبها (مع تحميل العلاقات اللازمة للشعبة)
    //         $allSubjectsWithSections = Subject::with([
    //             'subjectCategory:id,subject_category_name', // فئة المادة
    //             // جلب شعب المادة مع تحميل مدرسينها
    //             'planSubjectEntries.sections.instructors:id,instructor_name' // لمعرفة إذا كانت الشعبة مأخوذة
    //         ])
    //             ->orderBy('subject_no')
    //             ->get();

    //         // جلب IDs الشعب المعينة لهذا المدرس المحدد
    //         $assignedSectionIds = $instructor->sections()->pluck('sections.id')->toArray();

    //         return view('dashboard.data-entry.instructor-subject-edit', compact(
    //             'instructor',
    //             'allSubjectsWithSections',
    //             'assignedSectionIds'
    //         ));
    //     } catch (Exception $e) {
    //         Log::error('Error loading edit assignments view for instructor ID ' . $instructor->id . ': ' . $e->getMessage());
    //         return redirect()->route('data-entry.instructor-subject.index')->with('error', 'Could not load assignment editing page.');
    //     }
    // }


    // /**
    //  * Update the sections assigned to the specified instructor.
    //  */
    // public function syncAssignments(Request $request, Instructor $instructor)
    // {
    //     // 1. Validation (فقط section_ids)
    //     $validatedData = $request->validate([
    //         'section_ids' => 'nullable|array', // قد تكون فارغة إذا أردنا إزالة كل التعيينات
    //         'section_ids.*' => 'integer|exists:sections,id',
    //     ]);

    //     try {
    //         // 2. استخدام sync() لتحديث الارتباطات في جدول instructor_section
    //         $instructor->sections()->sync($validatedData['section_ids'] ?? []);

    //         // 3. Redirect إلى صفحة العرض الرئيسية مع رسالة نجاح
    //         return redirect()->route('data-entry.instructor-subject.index')
    //             ->with('success', 'Section assignments updated successfully for ' . ($instructor->instructor_name ?? optional($instructor->user)->name));
    //     } catch (Exception $e) {
    //         Log::error('Error syncing sections for instructor ID ' . $instructor->id . ': ' . $e->getMessage());
    //         // العودة لصفحة التعديل نفسها مع رسالة خطأ
    //         return redirect()->route('data-entry.instructor-subject.edit', $instructor->id)
    //             ->with('error', 'Failed to update section assignments.');
    //     }
    // }

    // public function editAssignments(Instructor $instructor) // استخدام Route Model Binding
    // {
    //     try {
    //         // جلب كل المواد المتاحة
    //         $allSubjects = Subject::with('department:id,department_name')
    //             ->orderBy('subject_no')
    //             ->get(['id', 'subject_no', 'subject_name', 'department_id']);

    //         // جلب IDs المواد المعينة لهذا المدرس
    //         $assignedSubjectIds = $instructor->subjects()->pluck('subjects.id')->toArray();

    //         // توجيه لـ view التعديل
    //         return view('dashboard.data-entry.instructor-subject-edit', compact(
    //             'instructor',
    //             'allSubjects',
    //             'assignedSubjectIds'
    //         ));
    //     } catch (Exception $e) {
    //         Log::error('Error loading edit assignments view for instructor ID ' . $instructor->id . ': ' . $e->getMessage());
    //         return redirect()->route('data-entry.instructor-subject.index')->with('error', 'Could not load assignment editing page.');
    //     }
    // }

    // /**
    //  * Update the subjects assigned to the specified instructor.
    //  */
    // public function syncAssignments(Request $request, Instructor $instructor) // استخدام Route Model Binding
    // {
    //     // 1. Validation (فقط للمواد المختارة)
    //     $validatedData = $request->validate([
    //         // instructor_id يأتي من الروت
    //         'subject_ids' => 'nullable|array',
    //         'subject_ids.*' => 'integer|exists:subjects,id',
    //     ]);

    //     try {
    //         // 2. استخدام sync() لتحديث الارتباطات
    //         $instructor->subjects()->sync($validatedData['subject_ids'] ?? []);

    //         // 3. Redirect إلى صفحة العرض الرئيسية مع رسالة نجاح
    //         return redirect()->route('data-entry.instructor-subject.index')
    //             ->with('success', 'Subject assignments updated successfully for ' . ($instructor->instructor_name ?? optional($instructor->user)->name));
    //     } catch (Exception $e) {
    //         Log::error('Error syncing subjects for instructor ID ' . $instructor->id . ': ' . $e->getMessage());
    //         // العودة لصفحة التعديل نفسها مع رسالة خطأ
    //         return redirect()->route('data-entry.instructor-subject.edit', $instructor->id)
    //             ->with('error', 'Failed to update subject assignments.');
    //     }
    // }

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
    // public function apiShowAssignments(Instructor $instructor) // استخدام Route Model Binding
    // {
    //     try {
    //         // جلب IDs المواد المعينة لهذا المدرس
    //         $assignedSubjectIds = $instructor->subjects()->pluck('subjects.id')->toArray();

    //         // جلب كل المواد المتاحة
    //         $allSubjects = Subject::orderBy('subject_no')
    //             ->get(['id', 'subject_no', 'subject_name']) // جلب الحقول الأساسية
    //             ->map(function ($subject) use ($assignedSubjectIds) {
    //                 // إضافة حقل is_assigned لكل مادة
    //                 $subject->is_assigned = in_array($subject->id, $assignedSubjectIds);
    //                 return $subject;
    //             });

    //         return response()->json([
    //             'success' => true,
    //             'data' => [
    //                 'instructor' => $instructor->load('user:id,name'), // تحميل بيانات المدرس الأساسية
    //                 'subjects' => $allSubjects // قائمة كل المواد مع حقل is_assigned
    //             ]
    //         ], 200);
    //     } catch (Exception $e) {
    //         Log::error("API Error fetching assignment details for instructor ID {$instructor->id}: " . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Server Error'], 500);
    //     }
    // }


    // /**
    //  * Sync subjects for a specific instructor (API).
    //  */
    // public function apiSyncAssignments(Request $request, Instructor $instructor)
    // {
    //     $validatedData = $request->validate([
    //         'subject_ids' => 'present|array',
    //         'subject_ids.*' => 'integer|exists:subjects,id',
    //     ]);

    //     try {
    //         $instructor->subjects()->sync($validatedData['subject_ids'] ?? []);

    //         // إرجاع قائمة المواد المعينة المحدثة
    //         $updatedAssignedSubjects = $instructor->subjects()
    //             ->orderBy('subject_no')
    //             ->get(['subjects.id', 'subject_no', 'subject_name']);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Subject assignments updated successfully.',
    //             'data' => $updatedAssignedSubjects
    //         ], 200);
    //     } catch (Exception $e) {
    //         Log::error('API Error syncing subjects for instructor ID ' . $instructor->id . ': ' . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Failed to update assignments.'], 500);
    //     }
    // }
    public function apiShowAssignments(Instructor $instructor) // Route Model Binding للمدرس
    {
        try {
            // جلب IDs الشعب المعينة لهذا المدرس المحدد
            $assignedSectionIdsForCurrentInstructor = $instructor->sections()->pluck('sections.id')->toArray();

            // جلب كل المواد مع شعبها، مع تحديد حالة كل شعبة
            $allSubjectsWithSections = Subject::with([
                'subjectCategory:id,subject_category_name',
                // جلب كل شعب المادة، مع تحميل المدرسين المعينين لكل شعبة
                'planSubjectEntries.sections' => function ($query) {
                    $query->with('instructors:instructors.id,instructors.instructor_name,instructors.user_id') // جلب معلومات المدرس المعين
                        ->select('sections.*'); // تأكد من جلب كل أعمدة sections
                },
                // تحميل علاقة user للمدرسين المعينين للشعبة (إذا أردت اسم اليوزر)
                'planSubjectEntries.sections.instructors.user:id,name'
            ])
                ->orderBy('subject_no')
                ->get();

            // معالجة البيانات لإضافة حالة التعيين لكل شعبة
            $subjectsFormatted = $allSubjectsWithSections->map(function ($subject) use ($assignedSectionIdsForCurrentInstructor, $instructor) {
                $processedSections = collect();
                $allSectionsTakenByOthersForThisSubject = true;

                if ($subject->planSubjectEntries->isNotEmpty()) {
                    foreach ($subject->planSubjectEntries as $planSubEntry) {
                        foreach ($planSubEntry->sections as $section) {
                            $isAssignedToCurrent = in_array($section->id, $assignedSectionIdsForCurrentInstructor);
                            $assignedToOtherInstructor = false;
                            $otherInstructorName = null;

                            if (!$section->instructors->isEmpty()) { // إذا كانت الشعبة معينة
                                if (!$isAssignedToCurrent) { // ولم تكن معينة للمدرس الحالي
                                    $assignedToOtherInstructor = true;
                                    $firstOtherInstructor = $section->instructors->first(fn($instr) => $instr->id !== $instructor->id);
                                    if ($firstOtherInstructor) {
                                        $otherInstructorName = $firstOtherInstructor->instructor_name ?? optional($firstOtherInstructor->user)->name;
                                    }
                                }
                            }

                            // اعرض الشعبة فقط إذا كانت متاحة أو معينة للمدرس الحالي
                            if (!$assignedToOtherInstructor || $isAssignedToCurrent) {
                                $allSectionsTakenByOthersForThisSubject = false; // على الأقل شعبة واحدة يمكن التفاعل معها
                            }

                            // إضافة معلومات مفيدة للـ API
                            $processedSections->push([
                                'id' => $section->id,
                                'section_number' => $section->section_number,
                                'activity_type' => $section->activity_type,
                                'student_count' => $section->student_count,
                                'academic_year' => $section->academic_year,
                                'semester' => $section->semester,
                                'branch' => $section->branch,
                                'is_assigned_to_current_instructor' => $isAssignedToCurrent,
                                'is_assigned_to_other_instructor' => $assignedToOtherInstructor,
                                'other_instructor_name' => $otherInstructorName,
                            ]);
                        }
                    }
                }

                // لا ترجع المادة إذا كانت كل شعبها مأخوذة من قبل مدرسين آخرين
                if ($allSectionsTakenByOthersForThisSubject && !$processedSections->contains('is_assigned_to_current_instructor', true) && $processedSections->isNotEmpty()) {
                    return null; // أو يمكنك إرجاع المادة مع مصفوفة شعب فارغة إذا أردت إظهارها
                }


                return [
                    'id' => $subject->id,
                    'subject_no' => $subject->subject_no,
                    'subject_name' => $subject->subject_name,
                    'subject_category' => optional($subject->subjectCategory)->subject_category_name,
                    'sections' => $processedSections->sortBy(['activity_type', 'section_number'])->values(), // values() لإعادة الفهرسة
                ];
            })->filter()->values(); // filter() لإزالة المواد التي أرجعت null, ثم values() لإعادة الفهرسة

            return response()->json([
                'success' => true,
                'data' => [
                    'instructor' => [ // بيانات المدرس الحالي
                        'id' => $instructor->id,
                        'name' => $instructor->instructor_name ?? optional($instructor->user)->name,
                        'instructor_no' => $instructor->instructor_no
                    ],
                    'assignable_subjects' => $subjectsFormatted // قائمة المواد مع شعبها وحالة التعيين
                ]
            ], 200);
        } catch (Exception $e) {
            Log::error("API Error fetching assignments for instructor ID {$instructor->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Sync sections for a specific instructor.
     * (نفس دالة apiSyncAssignments السابقة، تعمل على علاقة sections)
     */
    public function apiSyncAssignments(Request $request, Instructor $instructor)
    {
        $validatedData = $request->validate([
            'section_ids' => 'present|array',
            'section_ids.*' => 'integer|exists:sections,id',
        ]);

        try {
            $instructor->sections()->sync($validatedData['section_ids'] ?? []);

            // إرجاع قائمة المواد المعينة المحدثة للمدرس
            $updatedAssignedSections = $instructor->sections()
                ->with(['planSubject.subject:id,subject_no,subject_name']) // جلب معلومات المادة
                ->orderBy('activity_type')->orderBy('section_number')
                ->get(['sections.id', 'plan_subject_id', 'activity_type', 'section_number', 'student_count']); // تحديد حقول الشعبة

            return response()->json([
                'success' => true,
                'message' => 'Section assignments updated successfully.',
                'data' => $updatedAssignedSections
            ], 200);
        } catch (Exception $e) {
            Log::error('API Error syncing sections for instructor ID ' . $instructor->id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update assignments.'], 500);
        }
    }

    /**
     * API: Get sections currently assigned to a specific instructor.
     * جلب الشعب المعينة حالياً لمدرس محدد عبر API
     */
    public function apiGetAssignedSections(Instructor $instructor)
    {
        try {
            $assignedSections = $instructor->sections() // استدعاء علاقة sections الجديدة
                ->with([ // تحميل معلومات إضافية لكل شعبة
                    'planSubject.subject:id,subject_no,subject_name',
                    'planSubject.plan:id,plan_no'
                ])
                ->orderBy('academic_year')->orderBy('semester')
                ->orderBy('activity_type')->orderBy('section_number')
                ->get([ // تحديد الحقول المطلوبة من جدول sections وجداول الربط
                    'sections.id',
                    'sections.plan_subject_id',
                    'sections.academic_year',
                    'sections.semester',
                    'sections.activity_type',
                    'sections.section_number',
                    'sections.student_count',
                    'sections.section_gender',
                    'sections.branch'
                ]);

            return response()->json([
                'success' => true,
                'data' => $assignedSections
            ], 200);
        } catch (Exception $e) {
            Log::error("API Error fetching assigned sections for instructor ID {$instructor->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Get available sections that can be assigned.
     * جلب الشعب المتاحة (التي لم يتم تعيينها لأي مدرس بعد) عبر API.
     * يمكن فلترتها اختيارياً حسب plan_subject_id, academic_year, semester, branch.
     */
    public function apiGetAvailableSections(Request $request)
    {
        try {
            // جلب كل IDs الشعب التي تم تعيينها بالفعل لأي مدرس
            $assignedSectionIdsGlobally = DB::table('instructor_section')->pluck('section_id')->toArray();

            $query = Section::whereNotIn('id', $assignedSectionIdsGlobally) // استبعاد الشعب المعينة
                ->with([
                    'planSubject.subject:id,subject_no,subject_name',
                    'planSubject.plan:id,plan_no'
                ]);

            // (اختياري) فلاتر إضافية
            if ($request->filled('plan_subject_id')) {
                $query->where('plan_subject_id', $request->plan_subject_id);
            }
            if ($request->filled('academic_year')) {
                $query->where('academic_year', $request->academic_year);
            }
            if ($request->filled('semester')) {
                $query->where('semester', $request->semester);
            }
            if ($request->filled('branch')) {
                $query->where(function ($q) use ($request) {
                    is_null($request->branch) || $request->branch === '' || strtolower($request->branch) === 'none' ?
                        $q->whereNull('branch') :
                        $q->where('branch', $request->branch);
                });
            }
            if ($request->has('activity_type')) {
                $query->where('activity_type', $request->activity_type);
            }


            $availableSections = $query->orderBy('academic_year')->orderBy('semester')
                ->orderBy('activity_type')->orderBy('section_number')
                ->get([
                    'sections.id',
                    'sections.plan_subject_id',
                    'sections.academic_year',
                    'sections.semester',
                    'sections.activity_type',
                    'sections.section_number',
                    'sections.student_count',
                    'sections.section_gender',
                    'sections.branch'
                ]);

            return response()->json([
                'success' => true,
                'data' => $availableSections
            ], 200);
        } catch (Exception $e) {
            Log::error("API Error fetching available sections: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Get subjects assigned to a specific instructor (API).
     */
    // public function apiGetAssignedSubjects(Instructor $instructor) // استخدام Route Model Binding
    // {
    //     try {
    //         // جلب المواد المعينة لهذا المدرس فقط مع تحديد الحقول المطلوبة
    //         $assignedSubjects = $instructor->subjects() // استدعاء علاقة subjects
    //             ->orderBy('subject_no')
    //             ->get(['subjects.id', 'subject_no', 'subject_name']); // تحديد الحقول من جدول subjects

    //         return response()->json([
    //             'success' => true,
    //             'data' => $assignedSubjects
    //         ], 200);
    //     } catch (Exception $e) {
    //         Log::error("API Error fetching assigned subjects for instructor ID {$instructor->id}: " . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Server Error'], 500);
    //     }
    // }

    // /**
    //  * Get available subjects for a specific instructor (API).
    //  */
    // public function apiGetAvailableSubjects(Instructor $instructor)
    // {
    //     try {
    //         // جلب IDs المواد المعينة بالفعل لهذا المدرس
    //         $assignedSubjectIds = $instructor->subjects()->pluck('subjects.id')->toArray();

    //         // جلب كل المواد التي *ليست* ضمن قائمة المواد المعينة
    //         $availableSubjects = Subject::whereNotIn('id', $assignedSubjectIds)
    //             ->orderBy('subject_no')
    //             ->get(['id', 'subject_no', 'subject_name']);

    //         return response()->json([
    //             'success' => true,
    //             'data' => $availableSubjects
    //         ], 200);
    //     } catch (Exception $e) {
    //         Log::error("API Error fetching available subjects for instructor ID {$instructor->id}: " . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Server Error'], 500);
    //     }
    // }
}
