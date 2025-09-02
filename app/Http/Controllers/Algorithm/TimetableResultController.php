<?php

namespace App\Http\Controllers\Algorithm;

use Exception;
use App\Models\Gene;
use App\Models\Section;
use App\Models\Timeslot;
use App\Models\Chromosome;
use App\Models\Population;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\ConflictCheckerService;

class TimetableResultController extends Controller
{
    /**
     * Display a list of generation runs and top chromosomes for a selected run.
     */
    //**************************  شغال مية المية يعمري ************************** //
    // public function index() // لم نعد بحاجة لـ $request هنا مبدئياً
    // {
    //     try {
    //         // 1. جلب آخر عملية تشغيل مكتملة (latest successful run)
    //         $latestSuccessfulRun = Population::where('status', 'completed')
    //             ->orderBy('end_time', 'desc') // الأحدث حسب وقت الانتهاء
    //             ->first();

    //         $topChromosomes = collect(); // مجموعة فارغة افتراضياً

    //         // 2. إذا وجدنا عملية تشغيل ناجحة، جلب أفضل 5 كروموسومات منها
    //         if ($latestSuccessfulRun) {
    //             $topChromosomes = $latestSuccessfulRun->chromosomes()
    //                 ->orderBy('penalty_value', 'asc') // الأقل عقوبة (الأفضل) في الأعلى
    //                 ->take(5) // جلب أفضل 5 فقط
    //                 ->get();
    //         }

    //         // 3. تمرير البيانات للـ View
    //         return view('dashboard.algorithm.timetable-result.index', compact(
    //             'latestSuccessfulRun', // تمرير معلومات عملية التشغيل نفسها
    //             'topChromosomes'
    //         ));
    //     } catch (Exception $e) {
    //         Log::error('Error loading timetable results index: ' . $e->getMessage());
    //         return redirect()->route('dashboard.index')->with('error', 'Could not load the results page.');
    //     }
    // }

    public function index()
    {
        try {
            $latestSuccessfulRun = Population::where('status', 'completed')
                ->orderBy('end_time', 'desc')
                ->first();

            $topChromosomes = collect();

            if ($latestSuccessfulRun) {
                $topChromosomes = $latestSuccessfulRun->chromosomes()
                    ->orderBy('penalty_value', 'asc')           // الأفضل أولاً
                    ->orderBy('chromosome_id', 'desc')          // إذا تساوت العقوبة، الأحدث أولاً
                    ->take(5)
                    ->get();
            }

            return view('dashboard.algorithm.timetable-result.index', compact(
                'latestSuccessfulRun',
                'topChromosomes'
            ));
        } catch (Exception $e) {
            Log::error('Error loading timetable results index: ' . $e->getMessage());
            return redirect()->route('dashboard.index')->with('error', 'Could not load the results page.');
        }
    }

    public function setBestChromosome(Population $population, Request $request)
    {
        $request->validate([
            'chromosome_id' => ['required', 'exists:chromosomes,chromosome_id', 'integer']
        ]);

        $chromosome = Chromosome::find($request->chromosome_id);

        if (!$chromosome || $chromosome->population_id != $population->population_id) {
            return back()->with('error', 'Invalid chromosome selected.');
        }

        $population->update(['best_chromosome_id' => $chromosome->chromosome_id]);

        return back()->with('success', "Chromosome #{$chromosome->chromosome_id} is now set as the best solution.");
    }

    /**
     * Display the best timetable for a given CHROMSOME.
     */
    // public function show(Chromosome $chromosome) // استخدام Route Model Binding لـ Chromosome
    // {
    //     try {
    //         // تحميل المعلومات المرتبطة بالكروموسوم (عملية التشغيل)
    //         $chromosome->load('population');

    //         // جلب كل الجينات (المحاضرات) مع كل تفاصيلها اللازمة للعرض
    //         $genes = $chromosome->genes()->with([
    //             'section.planSubject.subject',
    //             'instructor.user',
    //             'room.roomType',
    //             'timeslot',
    //         ])->get();

    //         // استخدام Service لفحص التعارضات وتحديدها
    //         $conflictChecker = new ConflictCheckerService($genes);
    //         $conflicts = $conflictChecker->getConflicts();
    //         $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

    //         // جلب كل الفترات الزمنية المتاحة لعرض هيكل الجدول
    //         $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');

    //         // تحضير بيانات الجدول للعرض
    //         $scheduleData = [];
    //         foreach ($genes as $gene) {
    //             if ($gene->timeslot) {
    //                 $scheduleData[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene;
    //             }
    //         }

    //         return view('dashboard.algorithm.timetable-result.show', compact(
    //             'chromosome', // تم تغيير اسم المتغير
    //             'scheduleData',
    //             'timeslots',
    //             'conflicts',
    //             'conflictingGeneIds'
    //         ));
    //     } catch (Exception $e) {
    //         Log::error("Error showing timetable result for Chromosome ID {$chromosome->chromosome_id}: " . $e->getMessage());
    //         return redirect()->route('dashboard.timetable.results.index')->with('error', 'Could not display the schedule result.');
    //     }
    // }

    // داخل TimetableResultController.php

    // public function show(Chromosome $chromosome)
    // {
    //     try {
    //         $chromosome->load('population');

    //         // 1. جلب كل الجينات اللازمة
    //         $genes = $chromosome->genes()->with([
    //             'section.planSubject.subject',
    //             'section.planSubject.plan',
    //             'instructor.user',
    //             'room',
    //             'timeslot'
    //         ])->get();

    //         // 2. جلب كل الفترات الزمنية وتنظيمها
    //         $allTimeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
    //             ->orderBy('start_time')->get();
    //         if ($allTimeslots->isEmpty()) {
    //             throw new Exception("No timeslots found in the system.");
    //         }
    //         $timeslotsByDay = $allTimeslots->groupBy('day');

    //         // 3. بناء "خريطة مواقع" لكل فترة زمنية عبر كل الأيام
    //         $slotPositions = [];
    //         $dayOffset = 0;
    //         foreach ($timeslotsByDay as $day => $daySlots) {
    //             foreach ($daySlots->values() as $index => $slot) {
    //                 $slotPositions[$slot->id] = $dayOffset + $index;
    //             }
    //             $dayOffset += $daySlots->count();
    //         }

    //         // 4. تجميع الجينات إلى بلوكات محاضرات
    //         $lectureBlocks = collect($genes)->groupBy('lecture_unique_id')->map(function ($lectureGenes) {
    //             if ($lectureGenes->isEmpty()) return null;
    //             $firstGene = $lectureGenes->first();
    //             $sortedTimeslots = $lectureGenes->pluck('timeslot')->sortBy('start_time');
    //             return [
    //                 'gene' => $firstGene,
    //                 'start_slot_id' => optional($sortedTimeslots->first())->id,
    //                 'slots_count' => $lectureGenes->count(),
    //                 'gene_ids' => $lectureGenes->pluck('gene_id')->all(),
    //             ];
    //         })->filter()->values();

    //         // 5. بناء خريطة مجموعات الطلاب (نستدعي نفس المنطق من الـ Service كمساعد)
    //         $sections = Section::whereIn('id', $genes->pluck('section_id')->unique())->get();
    //         $studentGroupMap = $this->buildStudentGroupMap($sections);

    //         // 6. تجميع البلوكات حسب مجموعة الطلاب
    //         $scheduleByGroup = [];
    //         foreach ($lectureBlocks as $block) {
    //             $sectionId = $block['gene']->section_id;
    //             if (isset($studentGroupMap[$sectionId])) {
    //                 $groupId = $studentGroupMap[$sectionId]['id'];
    //                 $groupName = $studentGroupMap[$sectionId]['name'];

    //                 if (!isset($scheduleByGroup[$groupId])) {
    //                     $scheduleByGroup[$groupId] = ['name' => $groupName, 'blocks' => []];
    //                 }
    //                 $scheduleByGroup[$groupId]['blocks'][] = $block;
    //             }
    //         }

    //         // 7. فحص التعارضات
    //         $conflictChecker = new ConflictCheckerService($genes);
    //         $conflicts = $conflictChecker->getConflicts();
    //         $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

    //         return view('dashboard.algorithm.timetable-result.show', compact(
    //             'chromosome',
    //             'scheduleByGroup', // **(الأهم)** البيانات مجمعة حسب مجموعة الطلاب
    //             'timeslotsByDay',
    //             'slotPositions', // خريطة مواقع الأعمدة
    //             'conflicts',
    //             'conflictingGeneIds'
    //         ));
    //     } catch (Exception $e) {
    //         Log::error("Error showing timetable result: " . $e->getMessage());
    //         return redirect()->route('algorithm-control.timetable.results.index')->with('error', 'Could not display the schedule result: ' . $e->getMessage());
    //     }
    // }

    /**
     * دالة مساعدة (يمكن وضعها داخل الكنترولر) لبناء خريطة مجموعات الطلاب
     */
    // private function buildStudentGroupMap(Collection $sections): array
    // {
    //     $studentGroupMap = [];
    //     $sectionsByContext = $sections->groupBy(function ($section) {
    //         $ps = $section->planSubject;
    //         return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
    //     });

    //     foreach ($sectionsByContext as $contextKey => $sectionsInContext) {
    //         $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id')->map->count()->max() ?? 0;
    //         $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

    //         for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
    //             $firstSection = $sectionsInContext->first()->planSubject;
    //             $groupName = optional($firstSection->plan)->plan_no . " | Lvl " . $firstSection->plan_level . " | Grp " . $groupIndex;
    //             $groupId = $contextKey . '-group-' . $groupIndex;

    //             $theorySections = $sectionsInContext->where('activity_type', 'Theory');
    //             foreach ($theorySections as $theorySection) {
    //                 $studentGroupMap[$theorySection->id] = ['id' => $groupId, 'name' => $groupName];
    //             }

    //             $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
    //             foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
    //                 $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
    //                 if ($sortedSections->has($groupIndex - 1)) {
    //                     $practicalSection = $sortedSections->get($groupIndex - 1);
    //                     $studentGroupMap[$practicalSection->id] = ['id' => $groupId, 'name' => $groupName];
    //                 }
    //             }
    //         }
    //     }
    //     return $studentGroupMap;
    // }

    // public function show(Chromosome $chromosome)
    // {
    //     try {
    //         $chromosome->load('population');

    //         // 1. جلب كل الجينات اللازمة مع كل العلاقات المطلوبة
    //         $genes = $chromosome->genes()->with([
    //             'section.planSubject.subject',
    //             'section.planSubject.plan',
    //             'instructor.user',
    //             'room',
    //             'timeslot'
    //         ])->get();

    //         // 2. جلب كل الفترات الزمنية وتنظيمها
    //         $allTimeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
    //             ->orderBy('start_time')
    //             ->get();

    //         if ($allTimeslots->isEmpty()) {
    //             throw new Exception("No timeslots found in the system. Please generate them first.");
    //         }

    //         $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    //         // فرز الأيام حسب الترتيب الصحيح
    //         $daysToDisplay = $allTimeslots->pluck('day')->unique()->sort(function ($a, $b) use ($daysOfWeek) {
    //             return array_search($a, $daysOfWeek) <=> array_search($b, $daysOfWeek);
    //         })->values();

    //         $timeslotsByDay = $allTimeslots->groupBy('day');

    //         // 3. بناء "خريطة مواقع" لكل فترة زمنية عبر كل الأيام
    //         // هذه الخريطة تخبرنا عن "رقم العمود" لكل فترة زمنية
    //         $slotPositions = [];
    //         $dayOffset = 0;
    //         foreach ($timeslotsByDay as $day => $daySlots) {
    //             foreach ($daySlots->values() as $index => $slot) {
    //                 // رقم العمود هو إزاحة اليوم + فهرس الفترة داخل اليوم
    //                 $slotPositions[$slot->id] = $dayOffset + $index;
    //             }
    //             $dayOffset += $daySlots->count();
    //         }

    //         // 4. تجميع الجينات إلى "بلوكات محاضرات"
    //         $lectureBlocks = collect($genes)->groupBy('lecture_unique_id')->map(function ($lectureGenes) {
    //             if ($lectureGenes->isEmpty()) return null;

    //             $firstGene = $lectureGenes->first();
    //             $sortedTimeslots = $lectureGenes->pluck('timeslot')->sortBy('start_time');

    //             return [
    //                 'gene' => $firstGene,
    //                 'start_slot_id' => optional($sortedTimeslots->first())->id,
    //                 'slots_count' => $lectureGenes->count(), // **(الأهم)** عدد الفترات التي يشغلها
    //                 'gene_ids' => $lectureGenes->pluck('gene_id')->all(),
    //             ];
    //         })->filter()->values();

    //         // 5. بناء خريطة مجموعات الطلاب (نستدعي نفس المنطق من الـ Service كمساعد)
    //         $sections = Section::whereIn('id', $genes->pluck('section_id')->unique())->with('planSubject.plan')->get();
    //         $studentGroupMap = $this->buildStudentGroupMap($sections);

    //         // 6. تجميع البلوكات حسب مجموعة الطلاب
    //         $scheduleByGroup = [];
    //         foreach ($lectureBlocks as $block) {
    //             $sectionId = $block['gene']->section_id;
    //             if (isset($studentGroupMap[$sectionId])) {
    //                 $groupId = $studentGroupMap[$sectionId]['id'];
    //                 $groupName = $studentGroupMap[$sectionId]['name'];

    //                 if (!isset($scheduleByGroup[$groupId])) {
    //                     $scheduleByGroup[$groupId] = ['name' => $groupName, 'blocks' => []];
    //                 }
    //                 $scheduleByGroup[$groupId]['blocks'][] = $block;
    //             }
    //         }

    //         // 7. فحص التعارضات
    //         $conflictChecker = new ConflictCheckerService($genes);
    //         $conflicts = $conflictChecker->getConflicts();
    //         $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

    //         return view('dashboard.algorithm.timetable-result.show', compact(
    //             'chromosome',
    //             'scheduleByGroup', // البيانات مجمعة حسب مجموعة الطلاب
    //             'timeslotsByDay',
    //             'slotPositions', // خريطة مواقع الأعمدة
    //             'conflicts',
    //             'conflictingGeneIds'
    //         ));
    //     } catch (Exception $e) {
    //         Log::error("Error showing timetable result: " . $e->getMessage());
    //         return redirect()->route('algorithm-control.timetable.results.index')->with('error', 'Could not display the schedule result: ' . $e->getMessage());
    //     }
    // }

    // public function show(Chromosome $chromosome)
    // {
    //     try {
    //         $chromosome->load('population');

    //         $genes = $chromosome->genes()->with([
    //             'section.planSubject.subject',
    //             'section.planSubject.plan',
    //             'instructor.user',
    //             'room',
    //             'timeslot'
    //         ])->get();

    //         $allTimeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
    //             ->orderBy('start_time')->get();
    //         if ($allTimeslots->isEmpty()) {
    //             throw new Exception("No timeslots found in the system. Please generate them first.");
    //         }

    //         $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    //         $daysToDisplay = $allTimeslots->pluck('day')->unique()->sort(function ($a, $b) use ($daysOfWeek) {
    //             return array_search($a, $daysOfWeek) <=> array_search($b, $daysOfWeek);
    //         })->values();

    //         $timeslotsByDay = $allTimeslots->groupBy('day');

    //         $slotPositions = [];
    //         $dayOffset = 0;
    //         foreach ($timeslotsByDay as $day => $daySlots) {
    //             foreach ($daySlots->values() as $index => $slot) {
    //                 $slotPositions[$slot->id] = $dayOffset + $index;
    //             }
    //             $dayOffset += $daySlots->count();
    //         }

    //         $lectureBlocks = collect($genes)->groupBy('lecture_unique_id')->map(function ($lectureGenes) {
    //             if ($lectureGenes->isEmpty()) return null;
    //             $firstGene = $lectureGenes->first();
    //             $sortedTimeslots = $lectureGenes->pluck('timeslot')->sortBy('start_time');
    //             return [
    //                 'gene' => $firstGene,
    //                 'start_slot_id' => optional($sortedTimeslots->first())->id,
    //                 'slots_count' => $lectureGenes->count(),
    //                 'gene_ids' => $lectureGenes->pluck('gene_id')->all(),
    //             ];
    //         })->filter()->values();

    //         $sections = Section::whereIn('id', $genes->pluck('section_id')->unique())->with('planSubject.plan')->get();
    //         $studentGroupMap = $this->buildStudentGroupMap($sections);

    //         $scheduleByGroup = [];
    //         foreach ($lectureBlocks as $block) {
    //             $sectionId = $block['gene']->section_id;
    //             if (isset($studentGroupMap[$sectionId])) {
    //                 $groupId = $studentGroupMap[$sectionId]['id'];
    //                 $groupName = $studentGroupMap[$sectionId]['name'];

    //                 if (!isset($scheduleByGroup[$groupId])) {
    //                     $scheduleByGroup[$groupId] = ['name' => $groupName, 'blocks' => []];
    //                 }
    //                 $scheduleByGroup[$groupId]['blocks'][] = $block;
    //             }
    //         }

    //         // **(إضافة جديدة)**: حساب stack_level بعد تجميع البلوكات حسب المجموعة
    //         foreach ($scheduleByGroup as $groupId => &$groupData) {
    //             $slotsForGroup = [];
    //             $sortedBlocks = collect($groupData['blocks'])->sortBy(function ($block) {
    //                 return optional(optional($block['gene'])->timeslot)->start_time;
    //             });

    //             foreach ($sortedBlocks as &$block) {
    //                 if (empty($block['start_slot_id']) || !isset($slotPositions[$block['start_slot_id']])) continue;
    //                 $startColumn = $slotPositions[$block['start_slot_id']];
    //                 $span = $block['slots_count'];
    //                 $stackLevel = 0;

    //                 while (true) {
    //                     $isOccupied = false;
    //                     if (isset($slotsForGroup[$stackLevel])) {
    //                         foreach ($slotsForGroup[$stackLevel] as $occupiedSlot) {
    //                             if (
    //                                 $startColumn < ($occupiedSlot['start'] + $occupiedSlot['span']) &&
    //                                 ($startColumn + $span) > $occupiedSlot['start']
    //                             ) {
    //                                 $isOccupied = true;
    //                                 break;
    //                             }
    //                         }
    //                     }
    //                     if (!$isOccupied) {
    //                         $slotsForGroup[$stackLevel][] = ['start' => $startColumn, 'span' => $span];
    //                         $block['stack_level'] = $stackLevel; // **(الأهم)** إضافة المفتاح هنا
    //                         break;
    //                     }
    //                     $stackLevel++;
    //                 }
    //             }
    //             $groupData['blocks'] = $sortedBlocks->all();
    //         }
    //         unset($groupData); // لكسر المرجع

    //         $conflictChecker = new ConflictCheckerService($genes);
    //         $conflicts = $conflictChecker->getConflicts();
    //         $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

    //         return view('dashboard.algorithm.timetable-result.show', compact(
    //             'chromosome',
    //             'scheduleByGroup',
    //             'timeslotsByDay',
    //             'slotPositions',
    //             'conflicts',
    //             'conflictingGeneIds',
    //         ));
    //     } catch (Exception $e) {
    //         Log::error("Error showing timetable result: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    //         return redirect()->route('algorithm-control.timetable.results.index')->with('error', 'Could not display the schedule result: ' . $e->getMessage());
    //     }
    // }

    // public function show(Chromosome $chromosome)
    // {
    //     try {
    //         $chromosome->load('population');

    //         $genes = $chromosome->genes()->with([
    //             'section.planSubject.subject',
    //             'section.planSubject.plan',
    //             'instructor.user',
    //             'room',
    //             'timeslot'
    //         ])->get();

    //         $allTimeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
    //             ->orderBy('start_time')->get();
    //         if ($allTimeslots->isEmpty()) {
    //             throw new Exception("No timeslots found in the system. Please generate them first.");
    //         }

    //         $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    //         $daysToDisplay = $allTimeslots->pluck('day')->unique()->sort(function ($a, $b) use ($daysOfWeek) {
    //             return array_search($a, $daysOfWeek) <=> array_search($b, $daysOfWeek);
    //         })->values();

    //         $timeslotsByDay = $allTimeslots->groupBy('day');

    //         $slotPositions = [];
    //         $dayOffset = 0;
    //         foreach ($timeslotsByDay as $day => $daySlots) {
    //             foreach ($daySlots->values() as $index => $slot) {
    //                 $slotPositions[$slot->id] = $dayOffset + $index;
    //             }
    //             $dayOffset += $daySlots->count();
    //         }

    //         $lectureBlocks = collect($genes)->groupBy('lecture_unique_id')->map(function ($lectureGenes) {
    //             if ($lectureGenes->isEmpty()) return null;
    //             $firstGene = $lectureGenes->first();
    //             $sortedTimeslots = $lectureGenes->pluck('timeslot')->sortBy('start_time');
    //             return [
    //                 'gene' => $firstGene,
    //                 'start_slot_id' => optional($sortedTimeslots->first())->id,
    //                 'slots_count' => $lectureGenes->count(), // **هذه هي القيمة الصحيحة لحجم البلوك**
    //                 'gene_ids' => $lectureGenes->pluck('gene_id')->all(),
    //             ];
    //         })->filter()->values();

    //         $sections = Section::whereIn('id', $genes->pluck('section_id')->unique())->with('planSubject.plan')->get();
    //         $studentGroupMap = $this->buildStudentGroupMap($sections);

    //         $scheduleByGroup = [];
    //         foreach ($lectureBlocks as $block) {
    //             $sectionId = $block['gene']->section_id;
    //             if (isset($studentGroupMap[$sectionId])) {
    //                 $groupId = $studentGroupMap[$sectionId]['id'];
    //                 $groupName = $studentGroupMap[$sectionId]['name'];

    //                 if (!isset($scheduleByGroup[$groupId])) {
    //                     $scheduleByGroup[$groupId] = ['name' => $groupName, 'blocks' => []];
    //                 }
    //                 $scheduleByGroup[$groupId]['blocks'][] = $block;
    //             }
    //         }

    //         // **(إضافة مهمة)**: حساب stack_level بعد تجميع البلوكات حسب المجموعة
    //         foreach ($scheduleByGroup as $groupId => &$groupData) { // استخدام & للمرور بالمرجع
    //             $slotsForGroup = [];
    //             $sortedBlocks = collect($groupData['blocks'])->sortBy(function ($block) {
    //                 return optional(optional($block['gene'])->timeslot)->start_time;
    //             });

    //             foreach ($sortedBlocks as &$block) { // استخدام & للمرور بالمرجع
    //                 if (empty($block['start_slot_id']) || !isset($slotPositions[$block['start_slot_id']])) {
    //                     $block['stack_level'] = 0; // قيمة افتراضية
    //                     continue;
    //                 };

    //                 $startColumn = $slotPositions[$block['start_slot_id']];
    //                 $span = $block['slots_count'];
    //                 $stackLevel = 0;

    //                 while (true) {
    //                     $isOccupied = false;
    //                     if (isset($slotsForGroup[$stackLevel])) {
    //                         foreach ($slotsForGroup[$stackLevel] as $occupiedSlot) {
    //                             if (
    //                                 $startColumn < ($occupiedSlot['start'] + $occupiedSlot['span']) &&
    //                                 ($startColumn + $span) > $occupiedSlot['start']
    //                             ) {
    //                                 $isOccupied = true;
    //                                 break;
    //                             }
    //                         }
    //                     }
    //                     if (!$isOccupied) {
    //                         $slotsForGroup[$stackLevel][] = ['start' => $startColumn, 'span' => $span];
    //                         $block['stack_level'] = $stackLevel; // **(الأهم)** إضافة المفتاح هنا بشكل صحيح
    //                         break;
    //                     }
    //                     $stackLevel++;
    //                 }
    //             }
    //             $groupData['blocks'] = $sortedBlocks->all();
    //         }
    //         unset($groupData); // لكسر المرجع

    //         $conflictChecker = new ConflictCheckerService($genes);
    //         $conflicts = $conflictChecker->getConflicts();
    //         $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

    //         return view('dashboard.algorithm.timetable-result.show', compact(
    //             'chromosome',
    //             'scheduleByGroup',
    //             'timeslotsByDay',
    //             'slotPositions',
    //             'conflicts',
    //             'conflictingGeneIds'
    //         ));
    //     } catch (Exception $e) {
    //         Log::error("Error showing timetable result: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    //         return redirect()->route('algorithm-control.timetable.results.index')->with('error', 'Could not display the schedule result: ' . $e->getMessage());
    //     }
    // }
    // *****************************************************************
    //     public function show(Chromosome $chromosome)
    // {
    //     try {
    //         $chromosome->load('population');

    //         // 1. جلب كل الجينات (البلوكات) اللازمة
    //         $genes = $chromosome->genes()->with([
    //             'section.planSubject.subject',
    //             'section.planSubject.plan',
    //             'instructor.user', 'room'
    //         ])->get();

    //         // 2. جلب كل الفترات الزمنية وتنظيمها
    //         $allTimeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
    //             ->orderBy('start_time')->get();
    //         if ($allTimeslots->isEmpty()) {
    //             throw new Exception("No timeslots found in the system. Please generate them first.");
    //         }

    //         $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    //         $daysToDisplay = $allTimeslots->pluck('day')->unique()->sort(function($a, $b) use ($daysOfWeek) {
    //             return array_search($a, $daysOfWeek) <=> array_search($b, $daysOfWeek);
    //         })->values();

    //         $timeslotsByDay = $allTimeslots->groupBy('day');
    //         $allTimeslotsById = $allTimeslots->keyBy('id');

    //         // 3. بناء "خريطة مواقع" لكل فترة زمنية عبر كل الأيام
    //         $slotPositions = [];
    //         $dayOffset = 0;
    //         foreach ($timeslotsByDay as $day => $daySlots) {
    //             foreach ($daySlots->values() as $index => $slot) {
    //                 $slotPositions[$slot->id] = $dayOffset + $index;
    //             }
    //             $dayOffset += $daySlots->count();
    //         }

    //         // 4. بناء خريطة مجموعات الطلاب
    //         $sections = Section::whereIn('id', $genes->pluck('section_id')->unique())->with('planSubject.plan')->get();
    //         $studentGroupMap = $this->buildStudentGroupMap($sections);

    //         // 5. تجميع الجينات (البلوكات) حسب مجموعة الطلاب
    //         $scheduleByGroup = [];
    //         foreach ($genes as $gene) {
    //             $sectionId = $gene->section_id;
    //             if (isset($studentGroupMap[$sectionId])) {
    //                 $groupId = $studentGroupMap[$sectionId]['id'];
    //                 $groupName = $studentGroupMap[$sectionId]['name'];

    //                 if (!isset($scheduleByGroup[$groupId])) {
    //                     $scheduleByGroup[$groupId] = ['name' => $groupName, 'blocks' => []];
    //                 }
    //                 // نضيف الجين (البلوك) كاملاً
    //                 $scheduleByGroup[$groupId]['blocks'][] = $gene;
    //             }
    //         }

    //         // 6. فحص التعارضات (سنحتاج لتحديث الـ Service لاحقاً، لكن مبدئياً سيعمل)
    //         // لتحويل الجينات الجديدة لشكل يفهمه الـ Service القديم
    //         $flatGenesForConflictCheck = collect();
    //         foreach($genes as $geneBlock) {
    //             foreach($geneBlock->timeslot_ids as $tsId){
    //                 $newGene = $geneBlock->replicate();
    //                 $newGene->timeslot_id = $tsId;
    //                 $newGene->setRelation('timeslot', $allTimeslotsById->get($tsId));
    //                 $flatGenesForConflictCheck->push($newGene);
    //             }
    //         }
    //         $conflictChecker = new ConflictCheckerService($flatGenesForConflictCheck);
    //         $conflicts = $conflictChecker->getConflicts();
    //         $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

    //         return view('dashboard.algorithm.timetable-result.show', compact(
    //             'chromosome',
    //             'scheduleByGroup',
    //             'timeslotsByDay',
    //             'slotPositions',
    //             'conflicts',
    //             'conflictingGeneIds' // سنحتاجه لتلوين البلوكات
    //         ));

    //     } catch (Exception $e) {
    //         Log::error("Error showing timetable result: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    //         return redirect()->route('algorithm-control.timetable.results.index')->with('error', 'Could not display the schedule result: ' . $e->getMessage());
    //     }
    // }
    // *************************************************

    // public function show(Chromosome $chromosome)
    // {
    //     try {
    //         $chromosome->load('population');

    //         $genes = $chromosome->genes()->with([
    //             'section.planSubject.subject',
    //             'section.planSubject.plan',
    //             'instructor.user', 'room', 'timeslot'
    //         ])->get();

    //         $allTimeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
    //             ->orderBy('start_time')->get();
    //         if ($allTimeslots->isEmpty()) {
    //             throw new Exception("No timeslots found in the system. Please generate them first.");
    //         }

    //         $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    //         $daysToDisplay = $allTimeslots->pluck('day')->unique()->sort(function($a, $b) use ($daysOfWeek) {
    //             return array_search($a, $daysOfWeek) <=> array_search($b, $daysOfWeek);
    //         })->values();

    //         $timeslotsByDay = $allTimeslots->groupBy('day');

    //         $slotPositions = [];
    //         $dayOffset = 0;
    //         foreach ($timeslotsByDay as $day => $daySlots) {
    //             foreach ($daySlots->values() as $index => $slot) {
    //                 $slotPositions[$slot->id] = $dayOffset + $index;
    //             }
    //             $dayOffset += $daySlots->count();
    //         }

    //         $lectureBlocks = collect($genes)->groupBy('lecture_unique_id')->map(function ($lectureGenes) {
    //             if ($lectureGenes->isEmpty()) return null;
    //             $firstGene = $lectureGenes->first();
    //             $sortedTimeslots = $lectureGenes->pluck('timeslot')->sortBy('start_time');
    //             return [
    //                 'gene' => $firstGene,
    //                 'start_slot_id' => optional($sortedTimeslots->first())->id,
    //                 'slots_count' => $lectureGenes->count(), // **هذه هي القيمة الصحيحة لحجم البلوك**
    //                 'gene_ids' => $lectureGenes->pluck('gene_id')->all(),
    //             ];
    //         })->filter()->values();

    //         $sections = Section::whereIn('id', $genes->pluck('section_id')->unique())->with('planSubject.plan')->get();
    //         $studentGroupMap = $this->buildStudentGroupMap($sections);

    //         $scheduleByGroup = [];
    //         foreach ($lectureBlocks as $block) {
    //             $sectionId = $block['gene']->section_id;
    //             if (isset($studentGroupMap[$sectionId])) {
    //                 $groupId = $studentGroupMap[$sectionId]['id'];
    //                 $groupName = $studentGroupMap[$sectionId]['name'];

    //                 if (!isset($scheduleByGroup[$groupId])) {
    //                     $scheduleByGroup[$groupId] = ['name' => $groupName, 'blocks' => []];
    //                 }
    //                 $scheduleByGroup[$groupId]['blocks'][] = $block;
    //             }
    //         }

    //         // **(إضافة مهمة)**: حساب stack_level بعد تجميع البلوكات حسب المجموعة
    //         foreach ($scheduleByGroup as $groupId => &$groupData) { // استخدام & للمرور بالمرجع
    //             $slotsForGroup = [];
    //             $sortedBlocks = collect($groupData['blocks'])->sortBy(function($block){
    //                 return optional(optional($block['gene'])->timeslot)->start_time;
    //             });

    //             foreach ($sortedBlocks as &$block) { // استخدام & للمرور بالمرجع
    //                  if (empty($block['start_slot_id']) || !isset($slotPositions[$block['start_slot_id']])) {
    //                     $block['stack_level'] = 0; // قيمة افتراضية
    //                     continue;
    //                  };

    //                  $startColumn = $slotPositions[$block['start_slot_id']];
    //                  $span = $block['slots_count'];
    //                  $stackLevel = 0;

    //                  while (true) {
    //                     $isOccupied = false;
    //                     if (isset($slotsForGroup[$stackLevel])) {
    //                         foreach ($slotsForGroup[$stackLevel] as $occupiedSlot) {
    //                             if ($startColumn < ($occupiedSlot['start'] + $occupiedSlot['span']) &&
    //                                 ($startColumn + $span) > $occupiedSlot['start']) {
    //                                 $isOccupied = true;
    //                                 break;
    //                             }
    //                         }
    //                     }
    //                     if (!$isOccupied) {
    //                         $slotsForGroup[$stackLevel][] = ['start' => $startColumn, 'span' => $span];
    //                         $block['stack_level'] = $stackLevel; // **(الأهم)** إضافة المفتاح هنا بشكل صحيح
    //                         break;
    //                     }
    //                     $stackLevel++;
    //                  }
    //             }
    //             $groupData['blocks'] = $sortedBlocks->all();
    //         }
    //         unset($groupData); // لكسر المرجع

    //         $conflictChecker = new ConflictCheckerService($genes);
    //         $conflicts = $conflictChecker->getConflicts();
    //         $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

    //         return view('dashboard.algorithm.timetable-result.show', compact(
    //             'chromosome', 'scheduleByGroup', 'timeslotsByDay', 'slotPositions', 'conflicts', 'conflictingGeneIds'
    //         ));

    //     } catch (Exception $e) {
    //         Log::error("Error showing timetable result: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    //         return redirect()->route('algorithm-control.timetable.results.index')->with('error', 'Could not display the schedule result: ' . $e->getMessage());
    //     }
    // }

    // ***************************************************************************************************
    // public function show(Chromosome $chromosome)
    // {
    //     try {
    //         $chromosome->load('population');

    //         $genes = $chromosome->genes()->with([
    //             'section.planSubject.subject',
    //             'section.planSubject.plan',
    //             'instructor.user',
    //             'room',
    //         ])->get();

    //         $allTimeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
    //             ->orderBy('start_time')->get();
    //         if ($allTimeslots->isEmpty()) throw new Exception("No timeslots found in the system.");

    //         $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    //         $daysToDisplay = $allTimeslots->pluck('day')->unique()->sort(function ($a, $b) use ($daysOfWeek) {
    //             return array_search($a, $daysOfWeek) <=> array_search($b, $daysOfWeek);
    //         })->values();

    //         $timeslotsByDay = $allTimeslots->groupBy('day');
    //         $allTimeslotsById = $allTimeslots->keyBy('id');

    //         $slotPositions = [];
    //         $dayOffset = 0;
    //         foreach ($timeslotsByDay as $day => $daySlots) {
    //             foreach ($daySlots->values() as $index => $slot) {
    //                 $slotPositions[$slot->id] = $dayOffset + $index;
    //             }
    //             $dayOffset += $daySlots->count();
    //         }

    //         // بناء خريطة مجموعات الطلاب
    //         $sections = Section::whereIn('id', $genes->pluck('section_id')->unique())->with('planSubject.plan')->get();
    //         $studentGroupMap = $this->buildStudentGroupMap($sections);

    //         // تجميع الجينات (البلوكات) حسب مجموعة الطلاب
    //         $scheduleByGroup = [];
    //         // أولاً نهيئ كل المجموعات
    //         foreach ($studentGroupMap as $mapInfo) {
    //             if (!isset($scheduleByGroup[$mapInfo['id']])) {
    //                 $scheduleByGroup[$mapInfo['id']] = ['name' => $mapInfo['name'], 'blocks' => []];
    //             }
    //         }
    //         // ثانياً نملأ البلوكات
    //         foreach ($genes as $gene) {
    //             $sectionId = $gene->section_id;
    //             if (isset($studentGroupMap[$sectionId])) {
    //                 $groupId = $studentGroupMap[$sectionId]['id'];
    //                 $scheduleByGroup[$groupId]['blocks'][] = $gene;
    //             }
    //         }
    //         // ترتيب المجموعات بناء على الاسم
    //         ksort($scheduleByGroup);

    //         // فحص التعارضات
    //         $flatGenesForConflictCheck = collect();
    //         foreach ($genes as $geneBlock) {
    //             // إضافة علاقات الـ timeslot يدوياً للـ flat genes
    //             $geneBlock->setRelation('timeslot', collect($geneBlock->timeslot_ids)->map(fn($id) => $allTimeslotsById->get($id)));
    //             foreach ($geneBlock->timeslot_ids as $tsId) {
    //                 $newGene = clone $geneBlock;
    //                 $newGene->timeslot_id_single = $tsId;
    //                 $flatGenesForConflictCheck->push($newGene);
    //             }
    //         }
    //         $conflictChecker = new ConflictCheckerService($flatGenesForConflictCheck);
    //         $conflicts = $conflictChecker->getConflicts(); // قائمة التعارضات النصية
    //         $conflictingGeneIds = $conflictChecker->getConflictingGeneIds(); // IDs الجينات المتعارضة

    //         return view('dashboard.algorithm.timetable-result.show', compact(
    //             'chromosome',
    //             'scheduleByGroup',
    //             'timeslotsByDay',
    //             'slotPositions',
    //             'conflicts',
    //             'conflictingGeneIds' // سنحتاجه لتلوين البلوكات
    //         ));
    //     } catch (Exception $e) {
    //         Log::error("Error showing timetable result: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    //         return redirect()->route('algorithm-control.timetable.results.index')->with('error', 'Could not display the schedule result: ' . $e->getMessage());
    //     }
    // }
    // /**
    //  * دالة مساعدة (يمكن وضعها داخل الكنترولر) لبناء خريطة مجموعات الطلاب
    //  */
    // private function buildStudentGroupMap(Collection $sections): array
    // {
    //     $studentGroupMap = [];
    //     $sectionsByContext = $sections->groupBy(function ($section) {
    //         $ps = $section->planSubject;
    //         if (!$ps) return 'unknown';
    //         return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
    //     });

    //     foreach ($sectionsByContext as $contextKey => $sectionsInContext) {
    //         if ($contextKey === 'unknown') continue;

    //         $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id')->map->count()->max() ?? 0;
    //         $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

    //         for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
    //             $firstSectionPlanSubject = $sectionsInContext->first()->planSubject;
    //             $groupName = optional($firstSectionPlanSubject->plan)->plan_no . "" . $firstSectionPlanSubject->plan_level . " | Group " . $groupIndex;
    //             $groupId = $contextKey . '-group-' . $groupIndex;

    //             $theorySections = $sectionsInContext->where('activity_type', 'Theory');
    //             foreach ($theorySections as $theorySection) {
    //                 $studentGroupMap[$theorySection->id] = ['id' => $groupId, 'name' => $groupName];
    //             }

    //             $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
    //             foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
    //                 $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
    //                 if ($sortedSections->has($groupIndex - 1)) {
    //                     $practicalSection = $sortedSections->get($groupIndex - 1);
    //                     $studentGroupMap[$practicalSection->id] = ['id' => $groupId, 'name' => $groupName];
    //                 }
    //             }
    //         }
    //     }
    //     return $studentGroupMap;
    // }


    // public function show(Chromosome $chromosome)
    // {
    //     try {
    //         $chromosome->load('population');

    //         $genes = $chromosome->genes()->with([
    //             'section.planSubject.subject',
    //             'section.planSubject.plan',
    //             'instructor.user',
    //             'room',
    //         ])->get();

    //         $allTimeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
    //             ->orderBy('start_time')->get();
    //         if ($allTimeslots->isEmpty()) throw new Exception("No timeslots found in the system.");

    //         $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    //         $daysToDisplay = $allTimeslots->pluck('day')->unique()->sort(function ($a, $b) use ($daysOfWeek) {
    //             return array_search($a, $daysOfWeek) <=> array_search($b, $daysOfWeek);
    //         })->values();

    //         $timeslotsByDay = $allTimeslots->groupBy('day');
    //         $allTimeslotsById = $allTimeslots->keyBy('id');

    //         $slotPositions = [];
    //         $dayOffset = 0;
    //         foreach ($timeslotsByDay as $day => $daySlots) {
    //             foreach ($daySlots->values() as $index => $slot) {
    //                 $slotPositions[$slot->id] = $dayOffset + $index;
    //             }
    //             $dayOffset += $daySlots->count();
    //         }

    //         $sections = Section::whereIn('id', $genes->pluck('section_id')->unique())->with('planSubject.plan')->get();
    //         $studentGroupMap = $this->buildStudentGroupMap($sections);

    //         $scheduleByGroup = [];

    //         // --- (المنطق المصحح) لتهيئة المجموعات ---
    //         $uniqueGroups = collect($studentGroupMap)->flatten(1)->unique('id')->keyBy('id');
    //         foreach ($uniqueGroups as $groupId => $groupInfo) {
    //             $scheduleByGroup[$groupId] = ['name' => $groupInfo['name'], 'blocks' => []];
    //         }

    //         foreach ($genes as $gene) {
    //             $sectionId = $gene->section_id;
    //             if (isset($studentGroupMap[$sectionId])) {
    //                 foreach ($studentGroupMap[$sectionId] as $groupInfo) {
    //                     $groupId = $groupInfo['id'];
    //                     $scheduleByGroup[$groupId]['blocks'][] = $gene;
    //                 }
    //             }
    //         }
    //         ksort($scheduleByGroup);

    //         $flatGenesForConflictCheck = collect();
    //         foreach ($genes as $geneBlock) {
    //             $geneBlock->setRelation('timeslot', collect($geneBlock->timeslot_ids)->map(fn($id) => $allTimeslotsById->get($id)));
    //             foreach ($geneBlock->timeslot_ids as $tsId) {
    //                 $newGene = clone $geneBlock;
    //                 $newGene->timeslot_id_single = $tsId;
    //                 $flatGenesForConflictCheck->push($newGene);
    //             }
    //         }
    //         $conflictChecker = new ConflictCheckerService($flatGenesForConflictCheck);
    //         $conflicts = $conflictChecker->getConflictColorts();
    //         $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

    //         return view('dashboard.algorithm.timetable-result.show', compact(
    //             'chromosome',
    //             'scheduleByGroup',
    //             'timeslotsByDay',
    //             'slotPositions',
    //             'conflicts',
    //             'conflictingGeneIds'
    //         ));
    //     } catch (Exception $e) {
    //         Log::error("Error showing timetable result: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    //         return redirect()->route('algorithm-control.timetable.results.index')->with('error', 'Could not display the schedule result: ' . $e->getMessage());
    //     }
    // }

    // /**
    //  * دالة مساعدة (داخل الكنترولر) لبناء خريطة مجموعات الطلاب (منطق مصحح للنظري)
    //  */
    // private function buildStudentGroupMap(Collection $sections): array
    // {
    //     $studentGroupMap = [];
    //     $sectionsByContext = $sections->groupBy(function ($section) {
    //         $ps = $section->planSubject;
    //         if (!$ps) return 'unknown';
    //         return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
    //     });

    //     foreach ($sectionsByContext as $contextKey => $sectionsInContext) {
    //         if ($contextKey === 'unknown') continue;

    //         $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id')->map->count()->max() ?? 0;
    //         $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

    //         $groupsInContext = [];
    //         for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
    //             $firstSectionPlanSubject = $sectionsInContext->first()->planSubject;
    //             $groupName = optional($firstSectionPlanSubject->plan)->plan_no . " | Lvl " . $firstSectionPlanSubject->plan_level . " | Grp " . $groupIndex;
    //             $groupId = $contextKey . '-group-' . $groupIndex;
    //             $groupsInContext[] = ['id' => $groupId, 'name' => $groupName];
    //         }

    //         foreach ($sectionsInContext as $section) {
    //             if ($section->activity_type == 'Theory') {
    //                 $studentGroupMap[$section->id] = $groupsInContext;
    //             } elseif ($section->activity_type == 'Practical') {
    //                 $practicalSectionsForThisSubject = $sectionsInContext
    //                     ->where('plan_subject_id', $section->plan_subject_id)
    //                     ->where('activity_type', 'Practical')
    //                     ->sortBy('section_number')->values();

    //                 $sectionIndex = $practicalSectionsForThisSubject->search(fn($s) => $s->id == $section->id);

    //                 if ($sectionIndex !== false && isset($groupsInContext[$sectionIndex])) {
    //                     $studentGroupMap[$section->id] = [$groupsInContext[$sectionIndex]];
    //                 }
    //             }
    //         }
    //     }
    //     return $studentGroupMap;
    // }
    // ******************************* END OF STUDENT GROUP MAP *******************************

    // public function show(Chromosome $chromosome)
    // {
    //     // try {
    //     $chromosome->load('population');

    //     $genes = $chromosome->genes()->with([
    //         'section.planSubject.subject',
    //         'section.planSubject.plan',
    //         'instructor.user',
    //         'room.roomType',
    //     ])->get();

    //     if ($genes->isEmpty()) {
    //         throw new Exception("No schedule data found for this chromosome.");
    //     }

    //     $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    //     $allTimeslots = Timeslot::orderByRaw("FIELD(day, '" . implode("','", $daysOfWeek) . "')")
    //         ->orderBy('start_time')
    //         ->get();

    //     if ($allTimeslots->isEmpty()) {
    //         throw new Exception("No timeslots defined in the system.");
    //     }

    //     $timeslotsByDay = $allTimeslots->groupBy('day');
    //     $allTimeslotsById = $allTimeslots->keyBy('id');

    //     $slotPositions = [];
    //     $dayOffset = 0;
    //     foreach ($timeslotsByDay as $day => $daySlots) {
    //         foreach ($daySlots->values() as $index => $slot) {
    //             $slotPositions[$slot->id] = $dayOffset + $index;
    //         }
    //         $dayOffset += $daySlots->count();
    //     }

    //     $sections = Section::whereIn('id', $genes->pluck('section_id')->unique())
    //         ->with('planSubject.plan')
    //         ->get();

    //     $studentGroupMap = $this->buildStudentGroupMap($sections);
    //     $totalColumnsOverall = $allTimeslots->count();

    //     $scheduleByGroup = [];

    //     $uniqueGroups = collect($studentGroupMap)->flatten(1)->pluck('name', 'id')->unique();
    //     foreach ($uniqueGroups as $groupId => $groupName) {
    //         $scheduleByGroup[$groupId] = [
    //             'name' => $groupName,
    //             'blocks' => []
    //         ];
    //     }

    //     foreach ($genes as $gene) {
    //         $sectionId = $gene->section_id;
    //         if (isset($studentGroupMap[$sectionId])) {
    //             foreach ($studentGroupMap[$sectionId] as $groupInfo) {
    //                 $scheduleByGroup[$groupInfo['id']]['blocks'][] = $gene;
    //             }
    //         }
    //     }

    //     // --- فحص التعارضات ---
    //     $flatGenesForConflictCheck = collect();
    //     foreach ($genes as $geneBlock) {
    //         $geneBlock->setRelation('timeslot', collect($geneBlock->timeslot_ids)->map(fn($id) => $allTimeslotsById->get($id)));
    //         foreach ($geneBlock->timeslot_ids as $tsId) {
    //             $newGene = clone $geneBlock;
    //             $newGene->timeslot_id_single = $tsId;
    //             $flatGenesForConflictCheck->push($newGene);
    //         }
    //     }

    //     $conflictChecker = new ConflictCheckerService($flatGenesForConflictCheck);
    //     $conflicts = $conflictChecker->getConflicts();
    //     $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

    //     return view('dashboard.algorithm.timetable-result.show', compact(
    //         'chromosome',
    //         'scheduleByGroup',
    //         'timeslotsByDay',
    //         'slotPositions',
    //         'totalColumnsOverall',
    //         'conflicts',
    //         'conflictingGeneIds',
    //         'conflictChecker'
    //     ));

    //     // } catch (Exception $e) {
    //     //     Log::error("Error displaying timetable: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    //     //     return redirect()
    //     //         ->route('algorithm-control.timetable.results.index')
    //     //         ->with('error', 'Could not load the timetable: ' . $e->getMessage());
    //     // }
    // }
////////////////////////////////////////////////// كان شغال /////////////////////////////////////////////////////
    /**
     * عرض تفاصيل الكروموسوم (الجدول)
     */
    public function show(Chromosome $chromosome)
    {
        try {
            $chromosome->load('population');

            $genes = $chromosome->genes()->with([
                'section.planSubject.subject',
                'section.planSubject.plan',
                'instructor.user',
                'room.roomType',
            ])->get();

            if ($genes->isEmpty()) {
                throw new Exception("No schedule data found for this chromosome.");
            }

            $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $allTimeslots = Timeslot::orderByRaw("FIELD(day, '" . implode("','", $daysOfWeek) . "')")
                ->orderBy('start_time')
                ->get();

            if ($allTimeslots->isEmpty()) {
                throw new Exception("No timeslots defined in the system.");
            }

            $timeslotsByDay = $allTimeslots->groupBy('day');
            $allTimeslotsById = $allTimeslots->keyBy('id');

            $slotPositions = [];
            $dayOffset = 0;
            foreach ($timeslotsByDay as $day => $daySlots) {
                foreach ($daySlots->values() as $index => $slot) {
                    $slotPositions[$slot->id] = $dayOffset + $index;
                }
                $dayOffset += $daySlots->count();
            }

            $sections = Section::whereIn('id', $genes->pluck('section_id')->unique())
                ->with('planSubject.plan')
                ->get();

            $studentGroupMap = $this->buildStudentGroupMap($sections);
            $totalColumnsOverall = $allTimeslots->count();

            $scheduleByGroup = [];

            $uniqueGroups = collect($studentGroupMap)->flatten(1)->pluck('name', 'id')->unique();
            foreach ($uniqueGroups as $groupId => $groupName) {
                $scheduleByGroup[$groupId] = [
                    'name' => $groupName,
                    'blocks' => []
                ];
            }

            foreach ($genes as $gene) {
                $sectionId = $gene->section_id;
                if (isset($studentGroupMap[$sectionId])) {
                    foreach ($studentGroupMap[$sectionId] as $groupInfo) {
                        $scheduleByGroup[$groupInfo['id']]['blocks'][] = $gene;
                    }
                }
            }

            // --- فحص التعارضات ---
            $flatGenesForConflictCheck = collect();
            foreach ($genes as $geneBlock) {
                $geneBlock->setRelation('timeslot', collect($geneBlock->timeslot_ids)->map(fn($id) => $allTimeslotsById->get($id)));
                foreach ($geneBlock->timeslot_ids as $tsId) {
                    $newGene = clone $geneBlock;
                    $newGene->timeslot_id_single = $tsId;
                    $flatGenesForConflictCheck->push($newGene);
                }
            }

            $conflictChecker = new ConflictCheckerService($flatGenesForConflictCheck);
            $conflicts = $conflictChecker->getConflicts();
            $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

            // --- إعداد البيانات للعرض ---
            $allRooms = \App\Models\Room::with('roomType')->get()->map(function ($room) {
                return [
                    'id' => $room->id,
                    'name' => $room->room_name,
                    'type' => optional($room->roomType)->room_type_name ?? '',
                    'size' => $room->room_size,
                ];
            })->toArray();

            $allInstructors = \App\Models\Instructor::with('user')->get()->map(function ($instructor) {
                return [
                    'id' => $instructor->id,
                    'name' => $instructor->user->name,
                    'subject_ids' => $instructor->subjects->pluck('id')->toArray(),
                ];
            })->toArray();

            $timeSlotUsage = [];
            foreach ($genes as $gene) {
                foreach ($gene->timeslot_ids as $tsId) {
                    $timeSlotUsage[$tsId]['rooms'][] = $gene->room_id;
                    $timeSlotUsage[$tsId]['instructors'][] = $gene->instructor_id;
                }
            }

            return view('dashboard.algorithm.timetable-result.show', compact(
                'chromosome',
                'scheduleByGroup',
                'timeslotsByDay',
                'slotPositions',
                'totalColumnsOverall',
                'conflicts',
                'conflictingGeneIds',
                'conflictChecker',
                'allRooms',
                'allInstructors',
                'timeSlotUsage'
            ));

        } catch (Exception $e) {
            Log::error("Error displaying timetable: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()
                ->route('algorithm-control.timetable.results.index')
                ->with('error', 'Could not load the timetable: ' . $e->getMessage());
        }
    }

    /**
     * بناء خريطة توزيع المجموعات
     */
    private function buildStudentGroupMap(Collection $sections): array
    {
        $studentGroupMap = [];

        $sectionsByContext = $sections->groupBy(function ($section) {
            $ps = $section->planSubject;
            if (!$ps) return 'unknown';
            return implode('-', [
                $ps->plan_id,
                $ps->plan_level,
                $ps->plan_semester,
                $section->academic_year,
                $section->branch ?? 'default'
            ]);
        });

        foreach ($sectionsByContext as $contextKey => $sectionsInContext) {
            if ($contextKey === 'unknown') continue;

            $maxPracticalSections = $sectionsInContext
                ->where('activity_type', 'Practical')
                ->groupBy('plan_subject_id')
                ->map->count()
                ->max() ?? 0;

            $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

            $groupsInContext = [];
            for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
                $firstSectionPlanSubject = $sectionsInContext->first()->planSubject;
                $groupName = optional($firstSectionPlanSubject->plan)->plan_no
                    . " " . $firstSectionPlanSubject->plan_level
                    . " | Grp " . $groupIndex;

                $groupId = $contextKey . '-group-' . $groupIndex;
                $groupsInContext[] = [
                    'id' => $groupId,
                    'name' => $groupName
                ];
            }

            foreach ($sectionsInContext as $section) {
                if ($section->activity_type === 'Theory') {
                    $studentGroupMap[$section->id] = $groupsInContext;
                } elseif ($section->activity_type === 'Practical') {
                    $practicalSectionsForSubject = $sectionsInContext
                        ->where('plan_subject_id', $section->plan_subject_id)
                        ->where('activity_type', 'Practical')
                        ->sortBy('section_number')
                        ->values();

                    $sectionIndex = $practicalSectionsForSubject->search(fn($s) => $s->id == $section->id);
                    if ($sectionIndex !== false && isset($groupsInContext[$sectionIndex])) {
                        $studentGroupMap[$section->id] = [$groupsInContext[$sectionIndex]];
                    }
                }
            }
        }

        return $studentGroupMap;
    }

    public function saveEdits(Request $request)
    {
        $request->validate([
            'edits' => 'required|array',
            'edits.*.gene_id' => 'required|exists:genes,gene_id',
            'edits.*.field' => 'required|in:instructor,room',
            'edits.*.new_value_id' => 'required|integer',
        ]);

        try {
            DB::transaction(function () use ($request) {
                foreach ($request->edits as $edit) {
                    $gene = Gene::findOrFail($edit['gene_id']);
                    $field = $edit['field'] . '_id'; // instructor_id, room_id

                    // حفظ التعديل في جدول التغييرات
                    DB::table('gene_edits')->insert([
                        'gene_id' => $edit['gene_id'],
                        'field' => $edit['field'],
                        'old_value_id' => $gene->{$field},
                        'new_value_id' => $edit['new_value_id'],
                        'changed_by' => auth()->id(),
                        'changed_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // تحديث الجين
                    $gene->{$field} = $edit['new_value_id'];
                    $gene->save();
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ التعديلات بنجاح.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل الحفظ: ' . $e->getMessage()
            ], 500);
        }
    }
/////////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * تعيين كروموسوم كـ "الأفضل" في عملية التشغيل
     */
    // public function setBestChromosome(Request $request, Population $population)
    // {
    //     $request->validate([
    //         'chromosome_id' => [
    //             'required',
    //             'integer',
    //             'exists:chromosomes,chromosome_id',
    //             'in:' . $population->chromosomes()->pluck('chromosome_id')->join(',')
    //         ]
    //     ]);

    //     try {
    //         $chromosome = $population->chromosomes()->findOrFail($request->chromosome_id);

    //         $population->update(['best_chromosome_id' => $chromosome->chromosome_id]);

    //         return back()->with('success', "✅ Chromosome #{$chromosome->chromosome_id} is now set as the best solution.");
    //     } catch (Exception $e) {
    //         Log::error("Failed to set best chromosome: " . $e->getMessage());
    //         return back()->with('error', '❌ Could not set the chromosome as best: ' . $e->getMessage());
    //     }
    // }

    /**
     * دالة مساعدة: بناء خريطة المجموعات (نظرية مشتركة، عملية مقسومة)
     */
    // private function buildStudentGroupMap(Collection $sections): array
    // {
    //     $studentGroupMap = [];
    //     $sectionsByContext = $sections->groupBy(function ($section) {
    //         $ps = $section->planSubject;
    //         if (!$ps) return 'unknown';
    //         return implode('-', [
    //             $ps->plan_id,
    //             $ps->plan_level,
    //             $ps->plan_semester,
    //             $section->academic_year,
    //             $section->branch ?? 'default'
    //         ]);
    //     });

    //     foreach ($sectionsByContext as $contextKey => $sectionsInContext) {
    //         if ($contextKey === 'unknown') continue;

    //         $maxPracticalSections = $sectionsInContext
    //             ->where('activity_type', 'Practical')
    //             ->groupBy('plan_subject_id')
    //             ->map->count()
    //             ->max() ?? 0;

    //         $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;
    //         $groupsInContext = [];

    //         for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
    //             $firstSectionPlanSubject = $sectionsInContext->first()->planSubject;
    //             $groupName = optional($firstSectionPlanSubject->plan)->plan_no . " | Lvl " . $firstSectionPlanSubject->plan_level . " | Grp " . $groupIndex;
    //             $groupId = $contextKey . '-group-' . $groupIndex;
    //             $groupsInContext[] = ['id' => $groupId, 'name' => $groupName];
    //         }

    //         foreach ($sectionsInContext as $section) {
    //             if ($section->activity_type == 'Theory') {
    //                 $studentGroupMap[$section->id] = $groupsInContext;
    //             } elseif ($section->activity_type == 'Practical') {
    //                 $practicalSectionsForThisSubject = $sectionsInContext
    //                     ->where('plan_subject_id', $section->plan_subject_id)
    //                     ->where('activity_type', 'Practical')
    //                     ->sortBy('section_number')->values();

    //                 $sectionIndex = $practicalSectionsForThisSubject->search(fn($s) => $s->id == $section->id);
    //                 if ($sectionIndex !== false && isset($groupsInContext[$sectionIndex])) {
    //                     $studentGroupMap[$section->id] = [$groupsInContext[$sectionIndex]];
    //                 }
    //             }
    //         }
    //     }

    //     return $studentGroupMap;
    // }














    // public function show(Chromosome $chromosome)
    // {
    //     try {
    //         // 1. تحميل البيانات الأساسية بكفاءة
    //         $chromosome->load('population');
    //         $genes = $chromosome->genes()->with([
    //             'section.planSubject.subject',
    //             'section.planSubject.plan',
    //             'instructor',
    //             'room.roomType',
    //         ])->get();

    //         $allTimeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
    //             ->orderBy('start_time')->get();

    //         if ($allTimeslots->isEmpty()) {
    //             throw new Exception("No timeslots found in the system.");
    //         }

    //         // 2. بناء خريطة مجموعات الطلاب
    //         $sections = Section::whereIn('id', $genes->pluck('section_id')->unique())->with('planSubject.plan')->get();
    //         $studentGroupMap = $this->buildStudentGroupMap($sections);

    //         // 3. تنظيم الجدول للعرض (حسب كل مجموعة)
    //         $scheduleByGroup = [];
    //         $uniqueGroups = collect($studentGroupMap)->flatten(1)->unique('id')->sortBy('name');
    //         foreach ($uniqueGroups as $groupInfo) {
    //             $scheduleByGroup[$groupInfo['id']] = ['name' => $groupInfo['name'], 'blocks' => []];
    //         }

    //         foreach ($genes as $gene) {
    //             $gene->timeslot_ids =is_string($gene->timeslot_ids) ? json_decode($gene->timeslot_ids, true) : $gene->timeslot_ids; // التأكد من أنها مصفوفة
    //             // $gene->timeslot_ids = json_decode($gene->timeslot_ids, true) ?? []; // التأكد من أنها مصفوفة
    //             $gene->student_group_ids = json_decode($gene->student_group_ids, true) ?? [];

    //             if (isset($studentGroupMap[$gene->section_id])) {
    //                 foreach ($studentGroupMap[$gene->section_id] as $groupInfo) {
    //                     $scheduleByGroup[$groupInfo['id']]['blocks'][] = $gene;
    //                 }
    //             }
    //         }

    //         // 4. [الخطوة الجديدة] استخدام خدمة كشف التعارضات
    //         $conflictChecker = new ConflictCheckerService($genes);
    //         $conflicts = $conflictChecker->getConflicts();
    //         $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

    //         // 5. تحضير البيانات اللازمة للـ View
    //         $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    //         $timeslotsByDay = $allTimeslots->groupBy(function ($slot) use ($daysOfWeek) {
    //             return $daysOfWeek[array_search($slot->day, $daysOfWeek)];
    //         });

    //         return view('dashboard.algorithm.timetable-result.show', compact(
    //             'chromosome',
    //             'scheduleByGroup',
    //             'timeslotsByDay',
    //             'conflicts',
    //             'conflictingGeneIds'
    //         ));

    //     } catch (Exception $e) {
    //         Log::error("Error showing timetable result: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    //         return back()->with('error', 'Could not display the schedule result: ' . $e->getMessage());
    //     }
    // }

    // /**
    //  * دالة مساعدة لبناء خريطة مجموعات الطلاب
    //  */
    // private function buildStudentGroupMap(Collection $sections): array
    // {
    //     // ... (هذه الدالة تبقى كما هي بدون تغيير) ...
    //     $studentGroupMap = [];
    //     $sectionsByContext = $sections->groupBy(function ($section) {
    //         $ps = $section->planSubject;
    //         if (!$ps) return 'unknown';
    //         return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
    //     });

    //     foreach ($sectionsByContext as $contextKey => $sectionsInContext) {
    //         if ($contextKey === 'unknown') continue;

    //         $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id')->map->count()->max() ?? 0;
    //         $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

    //         $groupsInContext = [];
    //         for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
    //             $firstSectionPlanSubject = $sectionsInContext->first()->planSubject;
    //             $groupName = optional($firstSectionPlanSubject->plan)->plan_no . " | Lvl " . $firstSectionPlanSubject->plan_level . " | Grp " . $groupIndex;
    //             $groupId = $contextKey . '-group-' . $groupIndex;
    //             $groupsInContext[] = ['id' => $groupId, 'name' => $groupName];
    //         }

    //         foreach ($sectionsInContext as $section) {
    //             if ($section->activity_type == 'Theory') {
    //                 $studentGroupMap[$section->id] = $groupsInContext;
    //             } elseif ($section->activity_type == 'Practical') {
    //                 $practicalSectionsForThisSubject = $sectionsInContext
    //                     ->where('plan_subject_id', $section->plan_subject_id)
    //                     ->where('activity_type', 'Practical')
    //                     ->sortBy('section_number')->values();

    //                 $sectionIndex = $practicalSectionsForThisSubject->search(fn($s) => $s->id == $section->id);

    //                 if ($sectionIndex !== false && isset($groupsInContext[$sectionIndex])) {
    //                     $studentGroupMap[$section->id] = [$groupsInContext[$sectionIndex]];
    //                 }
    //             }
    //         }
    //     }
    //     return $studentGroupMap;
    // }
}
