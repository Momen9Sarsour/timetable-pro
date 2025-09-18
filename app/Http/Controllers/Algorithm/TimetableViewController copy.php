<?php

namespace App\Http\Controllers\Algorithm;

use App\Models\Plan;
use App\Models\Timeslot;
use App\Models\Chromosome;
use App\Models\Population;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PlanSubject;
use App\Models\Section;

class TimetableViewController extends Controller
{
    private function getBestTimetableGenes()
    {
        $lastSuccessfulRun = Population::where('status', 'completed')->orderBy('end_time', 'desc')->first();
        if (!$lastSuccessfulRun || !$lastSuccessfulRun->best_chromosome_id) {
            return null;
        }
        $bestChromosome = \App\Models\Chromosome::find($lastSuccessfulRun->best_chromosome_id);
        if (!$bestChromosome) {
            return null;
        }
        return $bestChromosome->genes()->with([
            'section.planSubject.subject.subjectCategory',
            'section.planSubject.plan.department',
            'instructor.user',
            'room',
            'timeslot'
        ])->get();
    }

    public function viewSectionTimetables(Request $request)
    {
        // =========================================================================
        // الخطوة 1: جلب أفضل جدول تم إنشاؤه من قاعدة البيانات
        // =========================================================================
        // نبحث عن آخر عملية تشغيل للخوارزمية كانت حالتها "مكتملة"
        $lastSuccessfulRun = Population::where('status', 'completed')->orderBy('end_time', 'desc')->first();

        // إذا لم نجد أي عملية ناجحة، أو لم يكن لها أفضل حل مسجل، نعرض صفحة "لا توجد نتائج"
        if (!$lastSuccessfulRun || !$lastSuccessfulRun->best_chromosome_id) {
            return view('dashboard.timetables.no-result');
        }

        // نجد الكروموسوم (الجدول) الأفضل بناءً على الـ ID المسجل
        $bestChromosome = \App\Models\Chromosome::find($lastSuccessfulRun->best_chromosome_id);
        if (!$bestChromosome) {
            return view('dashboard.timetables.no-result');
        }

        // نجلب كل الجينات (المحاضرات) الخاصة بهذا الجدول مع تحميل كل العلاقات اللازمة (المادة، المدرس، القاعة، الوقت) بكفاءة
        $allGenes = $bestChromosome->genes()->with([
            'section.planSubject.subject.subjectCategory',
            'section.planSubject.plan.department',
            'instructor.user',
            'room',
            'timeslot'
        ])->get();

        // إذا كان الجدول فارغاً لسبب ما، نعرض صفحة "لا توجد نتائج"
        if ($allGenes->isEmpty()) {
            return view('dashboard.timetables.no-result');
        }

        // =========================================================================
        // الخطوة 2: فلترة المحاضرات بناءً على طلب المستخدم من الفلاتر في الواجهة
        // =========================================================================
        $genesForContext = $allGenes;
        if ($request->filled('plan_id')) {
            $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_id == $request->plan_id);
        }
        if ($request->filled('plan_level')) {
            $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_level == $request->plan_level);
        }
        if ($request->filled('plan_semester')) {
            $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_semester == $request->plan_semester);
        }
        if ($request->filled('academic_year')) {
            $genesForContext = $genesForContext->filter(fn($gene) => optional($gene->section)->academic_year == $request->academic_year);
        }

        // =========================================================================
        // الخطوة 3: تجميع المحاضرات المفلترة حسب السياق (خطة-مستوى-فصل...)
        // =========================================================================
        $genesGroupedByContext = $genesForContext->groupBy(function ($gene) {
            $section = $gene->section;
            if (!$section || !$section->planSubject) return 'unknown';
            // إنشاء مفتاح فريد لكل سياق، مثال: "1-1-1-2025-default"
            return implode('-', [
                $section->planSubject->plan_id,
                $section->planSubject->plan_level,
                $section->planSubject->plan_semester,
                $section->academic_year,
                $section->branch ?? 'default'
            ]);
        });

        // =========================================================================
        // الخطوة 4: بناء الجداول لكل سياق (هنا المنطق الرئيسي والمعدل)
        // =========================================================================
        $timetablesByContext = [];
        foreach ($genesGroupedByContext as $contextKey => $genesInContext) {
            if ($contextKey === 'unknown' || $genesInContext->isEmpty()) continue;

            // استخراج معلومات السياق لعرضها في العنوان (اسم الخطة، المستوى، إلخ)
            $firstGene = $genesInContext->first();
            $contextInfo = [
                'plan' => optional(optional($firstGene->section)->planSubject)->plan,
                'level' => optional($firstGene->section->planSubject)->plan_level,
                'semester' => optional($firstGene->section->planSubject)->plan_semester,
                'year' => $firstGene->section->academic_year,
                'branch' => $firstGene->section->branch,
            ];

            // --- (أ) تحديد عدد مجموعات الطلاب الرئيسية (عدد الجداول التي سنعرضها) ---
            // نحسب أكبر عدد من الشعب العملية لأي مادة في هذا السياق. هذا هو الذي يحدد كم مجموعة طلاب لدينا.
            $maxPracticalSectionsForAnySubject = $genesInContext
                ->filter(fn($g) => $g->section->activity_type == 'Practical') // فلترة الشعب العملية فقط
                ->groupBy('section.plan_subject_id')                        // تجميع حسب المادة
                ->map(fn($genesForSubject) => $genesForSubject->count())     // حساب عدد الشعب العملية لكل مادة
                ->max() ?? 0;                                                // أخذ القيمة القصوى

            // إذا لم يكن هناك أي شعب عملية (كل المواد نظرية)، نفترض وجود مجموعة طلاب واحدة فقط
            $numberOfMainGroups = $maxPracticalSectionsForAnySubject > 0 ? $maxPracticalSectionsForAnySubject : 1;

            // --- (ب) استخراج كل المحاضرات النظرية المشتركة لهذا السياق ---
            $theoryGenesInContext = $genesInContext->filter(fn($gene) => $gene->section->activity_type == 'Theory');

            // --- (ج) بناء جدول لكل مجموعة طلاب ---
            $timetablesForThisContext = [];
            for ($groupIndex = 1; $groupIndex <= $numberOfMainGroups; $groupIndex++) {

                // سنجمع كل محاضرات هذه المجموعة هنا
                $allGenesForThisStudentGroup = collect();

                // 1. إضافة كل المحاضرات النظرية المشتركة. كل المجموعات تحضرها.
                $allGenesForThisStudentGroup = $allGenesForThisStudentGroup->merge($theoryGenesInContext);

                // 2. البحث عن المحاضرات العملية "الخاصة" بهذه المجموعة وإضافتها
                $subjectsInContext = $genesInContext->pluck('section.planSubject')->unique('id');

                foreach ($subjectsInContext as $planSubject) {
                    // نجلب كل الشعب العملية لهذه المادة، ونرتبها حسب رقمها
                    $practicalGenesForThisSubject = $genesInContext->filter(function ($gene) use ($planSubject) {
                        return $gene->section->plan_subject_id == $planSubject->id && $gene->section->activity_type == 'Practical';
                    })->sortBy('section.section_number')->values(); // values() لإعادة ترتيب الفهرس ليبدأ من 0

                    // إذا كان للمادة شعب عملية، نختار الشعبة التي ترتيبها يطابق ترتيب مجموعة الطلاب الحالية
                    if ($practicalGenesForThisSubject->isNotEmpty()) {
                        // المجموعة الأولى (index=1) تأخذ الشعبة العملية الأولى (at index 0)
                        $geneForThisGroup = $practicalGenesForThisSubject->get($groupIndex - 1);

                        if ($geneForThisGroup) {
                            $allGenesForThisStudentGroup->push($geneForThisGroup);
                        }
                    }
                }

                // 3. بناء شبكة الجدول لهذه المجموعة
                if ($allGenesForThisStudentGroup->isNotEmpty()) {
                    $scheduleGrid = [];
                    foreach ($allGenesForThisStudentGroup->unique('gene_id') as $gene) {
                        if ($gene->timeslot) {
                            $scheduleGrid[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene;
                        }
                    }

                    $timetablesForThisContext[] = [
                        'title' => "Student Group {$groupIndex}", // عنوان الجدول (يمكن تغييره لاحقاً إلى "شعبة 2" مثلاً)
                        'schedule' => $scheduleGrid
                    ];
                }
            }

            if (!empty($timetablesForThisContext)) {
                $timetablesByContext[$contextKey] = ['info' => $contextInfo, 'timetables' => $timetablesForThisContext];
            }
        }

        // =========================================================================
        // الخطوة 5: جلب البيانات اللازمة للفلاتر في الواجهة
        // =========================================================================
        $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();
        $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');
        $levels = PlanSubject::query()->whereNotNull('plan_level')->distinct()->orderBy('plan_level')->pluck('plan_level');
        $semesters = [1 => 'First', 2 => 'Second', 3 => 'Summer'];
        $academicYears = Section::query()->whereNotNull('academic_year')->distinct()->orderBy('academic_year', 'desc')->pluck('academic_year');

        // =========================================================================
        // الخطوة 6: إرسال كل البيانات المجهزة إلى الـ View
        // =========================================================================
        return view('dashboard.timetables.sections', compact(
            'timetablesByContext',
            'timeslots',
            'plans',
            'request',
            'levels',
            'semesters',
            'academicYears'
        ));
    }

    /**
     * 2. عرض جداول المدرسين
     */
    public function viewInstructorTimetables(Request $request)
    {
        $genes = $this->getBestTimetableGenes();
        if (is_null($genes)) {
            return view('dashboard.timetables.no-result');
        }

        // تحضير بيانات الجداول (مجمعة حسب المدرس)
        $timetablesByInstructor = [];
        $allInstructorsInTimetable = $genes->pluck('instructor')->unique('id')->sortBy(fn($inst) => optional($inst->user)->name);

        // تطبيق الفلاتر
        $filteredInstructors = $allInstructorsInTimetable;
        if ($request->filled('instructor_id')) {
            $filteredInstructors = $filteredInstructors->where('id', $request->instructor_id);
        }
        // ... (يمكن إضافة فلتر حسب القسم) ...


        foreach ($filteredInstructors as $instructor) {
            $instructorGenes = $genes->where('instructor_id', $instructor->id);
            $scheduleGrid = [];
            foreach ($instructorGenes as $gene) {
                if ($gene->timeslot) {
                    $scheduleGrid[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene;
                }
            }
            $timetablesByInstructor[$instructor->id] = [
                'instructor' => $instructor,
                'schedule' => $scheduleGrid
            ];
        }

        // جلب بيانات الفلاتر (كل المدرسين)
        $instructorsForFilter = $allInstructorsInTimetable;
        $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');

        return view('dashboard.timetables.instructors', compact(
            'timetablesByInstructor',
            'timeslots',
            'instructorsForFilter',
            'request'
        ));
    }

    /**
     * 3. عرض جداول القاعات
     */
    public function viewRoomTimetables(Request $request)
    {
        $genes = $this->getBestTimetableGenes();
        if (is_null($genes)) {
            return view('dashboard.timetables.no-result');
        }

        // تحضير بيانات الجداول (مجمعة حسب القاعة)
        $timetablesByRoom = [];
        $allRoomsInTimetable = $genes->pluck('room')->unique('id')->sortBy('room_no');

        // تطبيق الفلاتر
        $filteredRooms = $allRoomsInTimetable;
        if ($request->filled('room_id')) {
            $filteredRooms = $filteredRooms->where('id', $request->room_id);
        }
        // ... (يمكن إضافة فلتر حسب نوع القاعة) ...


        foreach ($filteredRooms as $room) {
            $roomGenes = $genes->where('room_id', $room->id);
            $scheduleGrid = [];
            foreach ($roomGenes as $gene) {
                if ($gene->timeslot) {
                    $scheduleGrid[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene;
                }
            }
            $timetablesByRoom[$room->id] = [
                'room' => $room,
                'schedule' => $scheduleGrid
            ];
        }

        // جلب بيانات الفلاتر (كل القاعات)
        $roomsForFilter = $allRoomsInTimetable;
        $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');

        return view('dashboard.timetables.rooms', compact(
            'timetablesByRoom',
            'timeslots',
            'roomsForFilter',
            'request'
        ));
    }
}
