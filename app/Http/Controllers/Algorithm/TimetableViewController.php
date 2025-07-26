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
    /**
     * جلب الجينات الخاصة بأفضل جدول دراسي
     */
    // private function getBestTimetableGenes()
    // {
    //     // جلب آخر عملية تشغيل مكتملة
    //     $lastSuccessfulRun = Population::where('status', 'completed')
    //         ->orderBy('end_time', 'desc')
    //         ->first();

    //     if (!$lastSuccessfulRun || !$lastSuccessfulRun->best_chromosome_id) {
    //         return null; // لا يوجد جدول لعرضه
    //     }

    //     // جلب أفضل كروموسوم
    //     $bestChromosome = Chromosome::find($lastSuccessfulRun->best_chromosome_id);
    //     if (!$bestChromosome) {
    //         return null;
    //     }

    //     // جلب كل الجينات (المحاضرات) مع كل تفاصيلها اللازمة للعرض
    //     return $bestChromosome->genes()->with([
    //         'section.planSubject.subject',
    //         'section.planSubject.plan',
    //         'instructor.user',
    //         'room',
    //         'timeslot'
    //     ])->get();
    // }

    // /**
    //  * 1. عرض جداول الشعب
    //  */
    // public function viewSectionTimetables(Request $request)
    // {
    //     $genes = $this->getBestTimetableGenes();
    //     if (is_null($genes)) {
    //         return view('dashboard.timetables.no-result'); // صفحة بسيطة تخبر المستخدم بعدم وجود جدول
    //     }

    //     // تحضير بيانات الجداول (مجمعة حسب الشعبة)
    //     $timetablesBySection = [];
    //     $allSectionsInTimetable = $genes->pluck('section')->unique('id'); // قائمة بالشعب الموجودة في الجدول فقط

    //     // تطبيق الفلاتر
    //     $filteredSections = $allSectionsInTimetable;
    //     if ($request->filled('plan_id')) {
    //         $filteredSections = $filteredSections->filter(fn($section) => optional(optional($section->planSubject)->plan)->id == $request->plan_id);
    //     }
    //     // ... (يمكن إضافة فلاتر أخرى بنفس الطريقة) ...


    //     foreach ($filteredSections as $section) {
    //         // جلب جينات هذه الشعبة المحددة
    //         $sectionGenes = $genes->where('section_id', $section->id);

    //         // تحضير شبكة الجدول لهذه الشعبة
    //         $scheduleGrid = [];
    //         foreach ($sectionGenes as $gene) {
    //             if ($gene->timeslot) {
    //                 $scheduleGrid[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene;
    //             }
    //         }
    //         $timetablesBySection[$section->id] = [
    //             'section' => $section,
    //             'schedule' => $scheduleGrid
    //         ];
    //     }

    //     // جلب بيانات الفلاتر
    //     $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();
    //     // ... (جلب باقي بيانات الفلاتر: الأقسام، المستويات...)

    //     // جلب كل الفترات الزمنية لعرض هيكل الجدول
    //     $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');


    //     return view('dashboard.timetables.sections', compact(
    //         'timetablesBySection',
    //         'timeslots',
    //         'plans',
    //         'request'
    //         // ... (باقي بيانات الفلاتر)
    //     ));
    // }

    // private function getBestTimetableGenes()
    // {
    //     $lastSuccessfulRun = Population::where('status', 'completed')->orderBy('end_time', 'desc')->first();
    //     if (!$lastSuccessfulRun || !$lastSuccessfulRun->best_chromosome_id) {
    //         return null;
    //     }
    //     $bestChromosome = Chromosome::find($lastSuccessfulRun->best_chromosome_id);
    //     if (!$bestChromosome) {
    //         return null;
    //     }
    //     return $bestChromosome->genes()->with([
    //         'section.planSubject.subject.subjectCategory',
    //         'section.planSubject.plan',
    //         'instructor.user',
    //         'room',
    //         'timeslot'
    //     ])->get();
    // }

    // public function viewSectionTimetables(Request $request)
    // {
    //     // التحقق من وجود بيانات أساسية
    //     $allGenes = $this->getBestTimetableGenes();
    //     if (is_null($allGenes) || $allGenes->isEmpty()) {
    //         return view('dashboard.timetables.no-result');
    //     }

    //     // 1. فلترة الجينات بناءً على السياق المحدد من المستخدم (خطة، مستوى، سنة، فصل...)
    //     // هذا الجزء يحتاج لفلاتر في الـ view وتطبيقها هنا
    //     // للتبسيط الآن، سنفترض أننا نريد عرض شعب خطة ومستوى وفصل معين
    //     $planId = $request->input('plan_id', 1); // مثال: فلتر حسب الخطة ID=1
    //     $level = $request->input('plan_level', 1); // مثال: المستوى الأول
    //     $semester = $request->input('plan_semester', 1); // مثال: الفصل الأول
    //     $academicYear = $request->input('academic_year', 2025); // مثال: السنة
    //     // ... (يمكن إضافة فرع)

    //     $genesForContext = $allGenes->filter(function ($gene) use ($planId, $level, $semester, $academicYear) {
    //         return optional(optional($gene->section)->planSubject)->plan_id == $planId &&
    //             optional($gene->section->planSubject)->plan_level == $level &&
    //             optional($gene->section->planSubject)->plan_semester == $semester &&
    //             $gene->section->academic_year == $academicYear;
    //     });

    //     if ($genesForContext->isEmpty()) {
    //         // لا يوجد محاضرات مجدولة لهذا السياق
    //         return view('dashboard.timetables.sections', [
    //             'timetablesByMainSection' => [],
    //             'timeslots' => Timeslot::orderBy('start_time')->get()->groupBy('day'),
    //             'plans' => Plan::where('is_active', true)->get(),
    //             'request' => $request,
    //             'contextInfo' => "No scheduled classes found for the selected context."
    //         ]);
    //     }


    //     // 2. تحديد عدد الجداول الرئيسية (الشعب الرئيسية)
    //     // هو أكبر رقم شعبة عملية (section_number) في هذا السياق
    //     $maxPracticalSectionNumber = $genesForContext->filter(fn($gene) => $gene->section->activity_type == 'Practical')
    //         ->max('section.section_number') ?? 0;
    //     // إذا لم يكن هناك شعب عملية، قد نعتمد على عدد الشعب النظرية
    //     if ($maxPracticalSectionNumber == 0) {
    //         $maxPracticalSectionNumber = $genesForContext->filter(fn($gene) => $gene->section->activity_type == 'Theory')
    //             ->max('section.section_number') ?? 1;
    //     }

    //     // 3. جلب كل الشعب النظرية في هذا السياق
    //     $theorySections = $genesForContext->filter(fn($gene) => $gene->section->activity_type == 'Theory')
    //         ->pluck('section')->unique('id');
    //     $numberOfTheorySections = $theorySections->count();


    //     // 4. بناء كل جدول رئيسي
    //     $timetablesByMainSection = [];
    //     for ($mainSectionNum = 1; $mainSectionNum <= $maxPracticalSectionNumber; $mainSectionNum++) {
    //         $allGenesForThisMainSection = collect();

    //         // أ. إضافة المحاضرات العملية الخاصة بهذه الشعبة الرئيسية
    //         $practicalGenes = $genesForContext->filter(function ($gene) use ($mainSectionNum) {
    //             return $gene->section->activity_type == 'Practical' &&
    //                 $gene->section->section_number == $mainSectionNum;
    //         });
    //         $allGenesForThisMainSection = $allGenesForThisMainSection->merge($practicalGenes);

    //         // ب. إضافة المحاضرات النظرية المشتركة
    //         if ($numberOfTheorySections == 1) {
    //             // إذا كانت هناك شعبة نظرية واحدة فقط، فهي مشتركة للجميع
    //             $allGenesForThisMainSection = $allGenesForThisMainSection->merge($theorySections->pluck('genes')->flatten());
    //         } elseif ($numberOfTheorySections > 1) {
    //             // منطق توزيع الشعب العملية على الشعب النظرية
    //             // مثال بسيط: الشعب العملية الفردية (1,3,5) مع النظرية 1، والزوجية (2,4,6) مع النظرية 2
    //             $theorySectionIndex = ($mainSectionNum - 1) % $numberOfTheorySections; // 0, 1, 0, 1...
    //             $assignedTheorySection = $theorySections->values()->get($theorySectionIndex);
    //             if ($assignedTheorySection) {
    //                 $allGenesForThisMainSection = $allGenesForThisMainSection->merge($assignedTheorySection->genes);
    //             }
    //         }

    //         // بناء شبكة الجدول لهذه الشعبة الرئيسية
    //         $scheduleGrid = [];
    //         foreach ($allGenesForThisMainSection->unique('id') as $gene) {
    //             if ($gene->timeslot) {
    //                 $scheduleGrid[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene;
    //             }
    //         }
    //         // اسم الشعبة الرئيسية
    //         $sectionTitle = "Section {$mainSectionNum}";
    //         $timetablesByMainSection[$mainSectionNum] = [
    //             'title' => $sectionTitle,
    //             'schedule' => $scheduleGrid
    //         ];
    //     }

    //     // جلب بيانات الفلاتر
    //     $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();
    //     $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');
    //     $contextInfo = "Displaying {$maxPracticalSectionNumber} main section(s) for Plan: {$genesForContext->first()->section->planSubject->plan->plan_no}, Level: {$level}, Term: {$semester}";

    //     return view('dashboard.timetables.sections', compact(
    //         'timetablesByMainSection',
    //         'timeslots',
    //         'plans',
    //         'request',
    //         'contextInfo'
    //     ));
    // }

    // private function getBestTimetableGenes()
    // {
    //     $lastSuccessfulRun = Population::where('status', 'completed')->orderBy('end_time', 'desc')->first();
    //     if (!$lastSuccessfulRun || !$lastSuccessfulRun->best_chromosome_id) { return null; }
    //     $bestChromosome = \App\Models\Chromosome::find($lastSuccessfulRun->best_chromosome_id);
    //     if (!$bestChromosome) { return null; }
    //     return $bestChromosome->genes()->with([
    //         'section.planSubject.subject.subjectCategory',
    //         'section.planSubject.plan.department', // تحميل كل العلاقات اللازمة
    //         'instructor.user',

    //         'room',
    //         'timeslot'
    //     ])->get();
    // }

    /**
     * عرض جداول الشعب (بالمنطق الجديد والمحسن)
     */
    // public function viewSectionTimetables(Request $request)
    // {
    //     $allGenes = $this->getBestTimetableGenes();
    //     if (is_null($allGenes) || $allGenes->isEmpty()) {
    //         return view('dashboard.timetables.no-result');
    //     }

    //     // --- 1. فلترة الجينات بناءً على السياق المحدد من المستخدم ---
    //     $genesForContext = $allGenes;
    //     if ($request->filled('plan_id')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_id == $request->plan_id);
    //     }
    //     if ($request->filled('plan_level')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_level == $request->plan_level);
    //     }
    //     if ($request->filled('plan_semester')) { // هذا هو فصل الخطة
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_semester == $request->plan_semester);
    //     }
    //     if ($request->filled('academic_year')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional($gene->section)->academic_year == $request->academic_year);
    //     }
    //     // ... (يمكن إضافة فلاتر أخرى بنفس الطريقة) ...

    //     // --- 2. تجميع الجينات حسب "المجموعة" (السياق) ---
    //     // المفتاح هو: plan_id-level-semester-year-branch
    //     $genesGroupedByContext = $genesForContext->groupBy(function ($gene) {
    //         $section = $gene->section;
    //         if (!$section || !$section->planSubject) return 'unknown';
    //         return implode('-', [
    //             $section->planSubject->plan_id,
    //             $section->planSubject->plan_level,
    //             $section->planSubject->plan_semester,
    //             $section->academic_year,
    //             $section->branch ?? 'default'
    //         ]);
    //     });


    //     // --- 3. بناء الجداول لكل مجموعة ---
    //     $timetablesByContext = [];
    //     foreach ($genesGroupedByContext as $contextKey => $genesInContext) {
    //         if ($contextKey === 'unknown') continue;

    //         $firstGene = $genesInContext->first();
    //         $contextInfo = [
    //             'plan' => optional(optional($firstGene->section)->planSubject)->plan,
    //             'level' => optional($firstGene->section->planSubject)->plan_level,
    //             'semester' => optional($firstGene->section->planSubject)->plan_semester,
    //             'year' => $firstGene->section->academic_year,
    //             'branch' => $firstGene->section->branch,
    //         ];

    //         // تحديد عدد الجداول الرئيسية (الشعب) لهذه المجموعة
    //         $maxPracticalSectionNumber = $genesInContext->filter(fn($g) => $g->section->activity_type == 'Practical')->max('section.section_number') ?? 0;
    //         if ($maxPracticalSectionNumber == 0) {
    //              $maxPracticalSectionNumber = $genesInContext->filter(fn($g) => $g->section->activity_type == 'Theory')->max('section.section_number') ?? 1;
    //         }

    //         $timetablesForThisContext = [];
    //         for ($mainSectionNum = 1; $mainSectionNum <= $maxPracticalSectionNumber; $mainSectionNum++) {
    //             // أ. جلب المحاضرات العملية لهذه الشعبة الرئيسية (لكل المواد)
    //             $practicalGenes = $genesInContext->filter(function ($gene) use ($mainSectionNum) {
    //                 return $gene->section->activity_type == 'Practical' && $gene->section->section_number == $mainSectionNum;
    //             });

    //             // ب. جلب المحاضرات النظرية المشتركة
    //             $theoryGenes = $genesInContext->filter(fn($gene) => $gene->section->activity_type == 'Theory');

    //             // ج. دمج المحاضرات
    //             $allGenesForThisMainSection = $practicalGenes->merge($theoryGenes);

    //             // د. بناء شبكة الجدول
    //             $scheduleGrid = [];
    //             foreach ($allGenesForThisMainSection->unique('id') as $gene) {
    //                 if ($gene->timeslot) { $scheduleGrid[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene; }
    //             }

    //             $timetablesForThisContext[] = [
    //                 'title' => "Section {$mainSectionNum}",
    //                 'schedule' => $scheduleGrid
    //             ];
    //         }
    //         $timetablesByContext[$contextKey] = ['info' => $contextInfo, 'timetables' => $timetablesForThisContext];
    //     }


    //     // جلب بيانات الفلاتر
    //     $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();
    //     $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');
    //     // ... (جلب باقي بيانات الفلاتر إذا احتجت)

    //     return view('dashboard.timetables.sections', compact(
    //         'timetablesByContext',
    //         'timeslots',
    //         'plans',
    //         'request',
    //         'timetablesForThisContext'
    //     ));
    // }

    // public function viewSectionTimetables(Request $request)
    // {
    //     $allGenes = $this->getBestTimetableGenes();
    //     if (is_null($allGenes) || $allGenes->isEmpty()) {
    //         return view('dashboard.timetables.no-result');
    //     }

    //     // --- 1. فلترة الجينات بناءً على السياق المحدد من المستخدم ---
    //     $genesForContext = $allGenes;
    //     if ($request->filled('plan_id')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_id == $request->plan_id);
    //     }
    //     if ($request->filled('plan_level')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_level == $request->plan_level);
    //     }
    //     if ($request->filled('academic_year')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional($gene->section)->academic_year == $request->academic_year);
    //     }
    //     // ... (يمكن إضافة فلاتر أخرى: فصل، فرع) ...

    //     // --- 2. تجميع الجينات حسب "المجموعة" (السياق) ---
    //     $genesGroupedByContext = $genesForContext->groupBy(function ($gene) {
    //         $section = $gene->section;
    //         if (!$section || !$section->planSubject) return 'unknown';
    //         return implode('-', [
    //             $section->planSubject->plan_id,
    //             $section->planSubject->plan_level,
    //             $section->planSubject->plan_semester, // فصل الخطة
    //             $section->academic_year,
    //             $section->branch ?? 'default'
    //         ]);
    //     });


    //     // --- 3. بناء الجداول لكل مجموعة ---
    //     $timetablesByContext = [];
    //     foreach ($genesGroupedByContext as $contextKey => $genesInContext) {
    //         if ($contextKey === 'unknown' || $genesInContext->isEmpty()) continue;

    //         $firstGene = $genesInContext->first();
    //         $contextInfo = [
    //             'plan' => optional(optional($firstGene->section)->planSubject)->plan,
    //             'level' => optional($firstGene->section->planSubject)->plan_level,
    //             'semester' => optional($firstGene->section->planSubject)->plan_semester, // فصل الخطة
    //             'year' => $firstGene->section->academic_year,
    //             'branch' => $firstGene->section->branch,
    //         ];

    //         // 4. تحديد عدد الجداول الرئيسية (الشعب الرئيسية)
    //         $maxSectionNumber = $genesInContext->pluck('section.section_number')->max() ?? 1;

    //         // 5. جلب كل المحاضرات النظرية المشتركة لهذا السياق
    //         $theoryGenes = $genesInContext->filter(fn($gene) => $gene->section->activity_type == 'Theory');

    //         $timetablesForThisContext = [];
    //         for ($mainSectionNum = 1; $mainSectionNum <= $maxSectionNumber; $mainSectionNum++) {
    //             // أ. جلب المحاضرات العملية الخاصة بهذه الشعبة الرئيسية (لكل المواد)
    //             $practicalGenes = $genesInContext->filter(function ($gene) use ($mainSectionNum) {
    //                 return $gene->section->activity_type == 'Practical' &&
    //                        $gene->section->section_number == $mainSectionNum;
    //             });

    //             // ب. دمج المحاضرات العملية مع المحاضرات النظرية المشتركة
    //             $allGenesForThisMainSection = $practicalGenes->merge($theoryGenes);

    //             // د. بناء شبكة الجدول
    //             $scheduleGrid = [];
    //             foreach ($allGenesForThisMainSection->unique('id') as $gene) {
    //                 if ($gene->timeslot) { $scheduleGrid[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene; }
    //             }

    //             $timetablesForThisContext[] = [
    //                 'title' => "Section {$mainSectionNum}",
    //                 'schedule' => $scheduleGrid
    //             ];
    //         }
    //         $timetablesByContext[$contextKey] = ['info' => $contextInfo, 'timetables' => $timetablesForThisContext];
    //     }


    //     // جلب بيانات الفلاتر
    //     $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();
    //     $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');
    //     // ... (جلب باقي بيانات الفلاتر إذا احتجت)

    //     return view('dashboard.timetables.sections', compact(
    //         'timetablesByContext',
    //         'timeslots',
    //         'plans',
    //         'request'
    //     ));
    // }
    // **********************************************************************************
    // private function getBestTimetableGenes()
    // {
    //     $lastSuccessfulRun = Population::where('status', 'completed')->orderBy('end_time', 'desc')->first();
    //     if (!$lastSuccessfulRun || !$lastSuccessfulRun->best_chromosome_id) { return null; }
    //     $bestChromosome = \App\Models\Chromosome::find($lastSuccessfulRun->best_chromosome_id);
    //     if (!$bestChromosome) { return null; }
    //     return $bestChromosome->genes()->with([
    //         'section.planSubject.subject.subjectCategory',
    //         'section.planSubject.plan.department',
    //         'instructor.user',
    //         'room',
    //         'timeslot'
    //     ])->get();
    // }

    // /**
    //  * عرض جداول الشعب (بالمنطق الصحيح والمضمون)
    //  */
    // public function viewSectionTimetables(Request $request)
    // {
    //     $allGenes = $this->getBestTimetableGenes();
    //     if (is_null($allGenes) || $allGenes->isEmpty()) {
    //         return view('dashboard.timetables.no-result');
    //     }

    //     // --- 1. فلترة الجينات بناءً على السياق المحدد من المستخدم (إذا وجد) ---
    //     $genesForContext = $allGenes;
    //     if ($request->filled('plan_id')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_id == $request->plan_id);
    //     }
    //     if ($request->filled('plan_level')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_level == $request->plan_level);
    //     }
    //     if ($request->filled('academic_year')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional($gene->section)->academic_year == $request->academic_year);
    //     }
    //     // ... (فلاتر أخرى) ...

    //     // --- 2. تجميع الجينات حسب "المجموعة" (السياق) ---
    //     $genesGroupedByContext = $genesForContext->groupBy(function ($gene) {
    //         $section = $gene->section;
    //         if (!$section || !$section->planSubject) return 'unknown';
    //         return implode('-', [
    //             $section->planSubject->plan_id,
    //             $section->planSubject->plan_level,
    //             $section->planSubject->plan_semester,
    //             $section->academic_year,
    //             $section->branch ?? 'default'
    //         ]);
    //     });


    //     // --- 3. بناء الجداول لكل مجموعة ---
    //     $timetablesByContext = [];
    //     foreach ($genesGroupedByContext as $contextKey => $genesInContext) {
    //         if ($contextKey === 'unknown' || $genesInContext->isEmpty()) continue;

    //         $firstGene = $genesInContext->first();
    //         $contextInfo = [
    //             'plan' => optional(optional($firstGene->section)->planSubject)->plan,
    //             'level' => optional($firstGene->section->planSubject)->plan_level,
    //             'semester' => optional($firstGene->section->planSubject)->plan_semester,
    //             'year' => $firstGene->section->academic_year,
    //             'branch' => $firstGene->section->branch,
    //         ];

    //         // 4. تحديد عدد الجداول الرئيسية (أكبر رقم شعبة)
    //         $maxSectionNumber = $genesInContext->pluck('section.section_number')->max() ?? 1;

    //         // 5. جلب كل المحاضرات النظرية المشتركة لهذا السياق
    //         $theoryGenesInContext = $genesInContext->filter(fn($gene) => $gene->section->activity_type == 'Theory');

    //         $timetablesForThisContext = [];
    //         for ($mainSectionNum = 1; $mainSectionNum <= $maxSectionNumber; $mainSectionNum++) {
    //             // أ. جلب المحاضرات العملية الخاصة بهذه الشعبة الرئيسية (لكل المواد)
    //             $practicalGenesForThisSection = $genesInContext->filter(function ($gene) use ($mainSectionNum) {
    //                 return $gene->section->activity_type == 'Practical' &&
    //                        $gene->section->section_number == $mainSectionNum;
    //             });

    //             // ب. *** دمج المحاضرات العملية مع كل المحاضرات النظرية المشتركة ***
    //             $allGenesForThisMainSection = $practicalGenesForThisSection->merge($theoryGenesInContext);

    //             // د. بناء شبكة الجدول
    //             if ($allGenesForThisMainSection->isNotEmpty()) { // فقط أنشئ جدولاً إذا كان هناك محاضرات
    //                 $scheduleGrid = [];
    //                 foreach ($allGenesForThisMainSection->unique('id') as $gene) {
    //                     if ($gene->timeslot) { $scheduleGrid[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene; }
    //                 }
    //                 $timetablesForThisContext[] = [
    //                     'title' => "Section {$mainSectionNum}",
    //                     'schedule' => $scheduleGrid
    //                 ];
    //             }
    //         }
    //         if (!empty($timetablesForThisContext)) {
    //              $timetablesByContext[$contextKey] = ['info' => $contextInfo, 'timetables' => $timetablesForThisContext];
    //         }
    //     }


    //     // جلب بيانات الفلاتر
    //     $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();
    //     $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');
    //     // ... (جلب باقي بيانات الفلاتر إذا احتجت)

    //     return view('dashboard.timetables.sections', compact(
    //         'timetablesByContext',
    //         'timeslots',
    //         'plans',
    //         'request'
    //     ));
    // }

    // دالة getBestTimetableGenes تبقى كما هي
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

    /**
     * عرض جداول الشعب (بالمنطق الصحيح والمضمون)
     */
    // public function viewSectionTimetables(Request $request)
    // {
    //     $allGenes = $this->getBestTimetableGenes();
    //     if (is_null($allGenes) || $allGenes->isEmpty()) {
    //         return view('dashboard.timetables.no-result');
    //     }

    //     // 1. فلترة الجينات بناءً على السياق المحدد من المستخدم (إذا وجد)
    //     $genesForContext = $allGenes;

    //     if ($request->filled('plan_id')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_id == $request->plan_id);
    //     }
    //     if ($request->filled('plan_level')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_level == $request->plan_level);
    //     }
    //     if ($request->filled('academic_year')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional($gene->section)->academic_year == $request->academic_year);
    //     }
    //     // ... (فلاتر أخرى) ...

    //     // 2. تجميع الجينات حسب "المجموعة" (السياق)
    //     $genesGroupedByContext = $genesForContext->groupBy(function ($gene) {
    //         $section = $gene->section;
    //         if (!$section || !$section->planSubject) return 'unknown';
    //         return implode('-', [
    //             $section->planSubject->plan_id,
    //             $section->planSubject->plan_level,
    //             $section->planSubject->plan_semester,
    //             $section->academic_year,
    //             $section->branch ?? 'default'
    //         ]);
    //     });

    //     // 3. بناء الجداول لكل مجموعة
    //     $timetablesByContext = [];
    //     foreach ($genesGroupedByContext as $contextKey => $genesInContext) {
    //         if ($contextKey === 'unknown' || $genesInContext->isEmpty()) continue;

    //         $firstGene = $genesInContext->first();
    //         $contextInfo = [
    //             'plan' => optional(optional($firstGene->section)->planSubject)->plan,
    //             'level' => optional($firstGene->section->planSubject)->plan_level,
    //             'semester' => optional($firstGene->section->planSubject)->plan_semester,
    //             'year' => $firstGene->section->academic_year,
    //             'branch' => $firstGene->section->branch,
    //         ];

    //         // 4. تحديد عدد الجداول الرئيسية (أكبر رقم شعبة عملية)
    //         $maxPracticalSectionNumber = $genesInContext->filter(fn($g) => $g->section->activity_type == 'Practical')->max('section.section_number');
    //         // إذا لم يكن هناك شعب عملية، افترض وجود شعبة رئيسية واحدة على الأقل
    //         if (empty($maxPracticalSectionNumber) || $maxPracticalSectionNumber < 1) {
    //             $maxPracticalSectionNumber = 1;
    //         }

    //         // 5. *** استخراج كل المحاضرات النظرية لهذا السياق ***
    //         $theoryGenesInContext = $genesInContext->filter(fn($gene) => $gene->section->activity_type == 'Theory' || $gene->section->activity_type == 'نظري' || $gene->section->activity_type == 'theory');

    //         $timetablesForThisContext = [];
    //         for ($mainSectionNum = 0; $mainSectionNum <= $maxPracticalSectionNumber; $mainSectionNum++) {
    //             // أ. جلب المحاضرات العملية الخاصة بهذه الشعبة الرئيسية (لكل المواد)
    //             $practicalGenesForThisSection = $genesInContext->filter(function ($gene) use ($mainSectionNum) {
    //                 return $gene->section->activity_type == 'Practical' &&
    //                     $gene->section->section_number == $mainSectionNum;
    //             });

    //             // ب. *** دمج المحاضرات العملية مع كل المحاضرات النظرية المشتركة ***
    //             // هذا هو السطر الصحيح للدمج
    //             $allGenesForThisMainSection = $practicalGenesForThisSection->merge($theoryGenesInContext);

    //             // د. بناء شبكة الجدول
    //             if ($allGenesForThisMainSection->isNotEmpty()) {
    //                 $scheduleGrid = [];
    //                 foreach ($allGenesForThisMainSection->unique('id') as $gene) {
    //                     // if ($gene->timeslot) {
    //                         $scheduleGrid[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene;
    //                     // }
    //                 }
    //                 $timetablesForThisContext[] = [
    //                     'title' => "Section {$mainSectionNum}",
    //                     'schedule' => $scheduleGrid
    //                 ];
    //             }
    //         }
    //         if (!empty($timetablesForThisContext)) {
    //             $timetablesByContext[$contextKey] = ['info' => $contextInfo, 'timetables' => $timetablesForThisContext];
    //         }
    //     }

    //     dd([
    //         // '$allGenes' => $allGenes,
    //         // '$genesForContext' => $genesForContext,
    //         // '$genesGroupedByContext' => $genesGroupedByContext,
    //         // '$section' => $gene->section,
    //         // '$timetablesByContext' => $timetablesByContext,
    //         // '$firstGene' => $firstGene,
    //         // '$contextInfo' => $contextInfo,
    //         // '$maxPracticalSectionNumber' => $maxPracticalSectionNumber,
    //         // '$timetablesForThisContext' => $timetablesForThisContext,
    //         // '$theoryGenesInContext' => $theoryGenesInContext,
    //         '$practicalGenesForThisSection' => $practicalGenesForThisSection,
    //         '$allGenesForThisMainSection' => $allGenesForThisMainSection,
    //         '$scheduleGrid' => $scheduleGrid,
    //     ]);


    //     // جلب بيانات الفلاتر
    //     $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();
    //     $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');
    //     // ... (جلب باقي بيانات الفلاتر إذا احتجت)

    //     return view('dashboard.timetables.sections', compact(
    //         'timetablesByContext',
    //         'timeslots',
    //         'plans',
    //         'request'
    //     ));
    // }

    // public function viewSectionTimetables(Request $request)
    // {
    //     $allGenes = $this->getBestTimetableGenes();
    //     if (is_null($allGenes) || $allGenes->isEmpty()) {
    //         return view('dashboard.timetables.no-result');
    //     }

    //     // --- 1. فلترة الجينات بناءً على السياق المحدد من المستخدم ---
    //     $genesForContext = $allGenes;
    //     if ($request->filled('plan_id')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_id == $request->plan_id);
    //     }
    //     if ($request->filled('plan_level')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_level == $request->plan_level);
    //     }
    //     if ($request->filled('plan_semester')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_semester == $request->plan_semester);
    //     }
    //     if ($request->filled('academic_year')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional($gene->section)->academic_year == $request->academic_year);
    //     }

    //     // --- 2. تجميع الجينات حسب "المجموعة" (السياق) ---
    //     $genesGroupedByContext = $genesForContext->groupBy(function ($gene) {
    //         $section = $gene->section;
    //         if (!$section || !$section->planSubject) return 'unknown';
    //         return implode('-', [
    //             $section->planSubject->plan_id,
    //             $section->planSubject->plan_level,
    //             $section->planSubject->plan_semester,
    //             $section->academic_year,
    //             $section->branch ?? 'default'
    //         ]);
    //     });

    //     $levels = PlanSubject::query()->orWhereNotNull('plan_level')->distinct()->orderBy('plan_level')->pluck('plan_level');
    //     $semesters = [1 => 'First', 2 => 'Second', 3 => 'Summer'];
    //     $academicYears = Section::query()->orWhereNotNull('academic_year')->distinct()->orderBy('academic_year', 'desc')->pluck('academic_year');

    //     // --- 3. بناء الجداول لكل مجموعة ---
    //     $timetablesByContext = [];
    //     foreach ($genesGroupedByContext as $contextKey => $genesInContext) {
    //         if ($contextKey === 'unknown' || $genesInContext->isEmpty()) continue;

    //         $firstGene = $genesInContext->first();
    //         $contextInfo = [
    //             'plan' => optional(optional($firstGene->section)->planSubject)->plan,
    //             'level' => optional($firstGene->section->planSubject)->plan_level,
    //             'semester' => optional($firstGene->section->planSubject)->plan_semester,
    //             'year' => $firstGene->section->academic_year,
    //             'branch' => $firstGene->section->branch,
    //         ];

    //         // تحديد عدد الجداول الرئيسية (أكبر رقم شعبة عملية)
    //         $maxSectionNumber = $genesInContext->filter(fn($g) => $g->section->activity_type == 'Practical')->max('section.section_number') ?? 0;
    //         if ($maxSectionNumber == 0) {
    //             $maxSectionNumber = $genesInContext->filter(fn($g) => $g->section->activity_type == 'Theory')->max('section.section_number') ?? 1;
    //         }

    //         // جلب كل الشعب النظرية في هذا السياق
    //         $theoryGenes = $genesInContext->filter(fn($g) => $g->section->activity_type == 'Theory');
    //         $theorySections = $theoryGenes->pluck('section')->unique('id')->sortBy('section_number'); // الشعب النظرية مرتبة
    //         $numberOfTheorySections = $theorySections->count();

    //         $timetablesForThisContext = [];
    //         for ($mainSectionNum = 1; $mainSectionNum <= $maxSectionNumber; $mainSectionNum++) {
    //             $allGenesForThisMainSection = collect();

    //             // أ. جلب المحاضرات العملية لهذه الشعبة الرئيسية (لكل المواد)
    //             $practicalGenes = $genesInContext->filter(function ($gene) use ($mainSectionNum) {
    //                 return $gene->section->activity_type == 'Practical' &&
    //                     $gene->section->section_number == $mainSectionNum;
    //             });
    //             $allGenesForThisMainSection = $allGenesForThisMainSection->merge($practicalGenes);

    //             // ب. جلب المحاضرات النظرية المشتركة
    //             if ($numberOfTheorySections == 1) {
    //                 // إذا كانت هناك شعبة نظرية واحدة فقط، فهي مشتركة للجميع
    //                 $allGenesForThisMainSection = $allGenesForThisMainSection->merge($theoryGenes);
    //             } elseif ($numberOfTheorySections > 1) {
    //                 // منطق توزيع الشعب العملية على الشعب النظرية
    //                 $theorySectionIndex = ($mainSectionNum - 1) % $numberOfTheorySections;
    //                 $assignedTheorySection = $theorySections->values()->get($theorySectionIndex);
    //                 if ($assignedTheorySection) {
    //                     // جلب الجينات الخاصة بهذه الشعبة النظرية فقط
    //                     $assignedTheoryGenes = $theoryGenes->where('section_id', $assignedTheorySection->id);
    //                     $allGenesForThisMainSection = $allGenesForThisMainSection->merge($assignedTheoryGenes);
    //                 }
    //             }

    //             // بناء شبكة الجدول
    //             $scheduleGrid = [];
    //             foreach ($allGenesForThisMainSection->unique('gene_id') as $gene) {
    //                 if ($gene->timeslot) {
    //                     $scheduleGrid[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene;
    //                 }
    //             }

    //             $timetablesForThisContext[] = [
    //                 'title' => "Section {$mainSectionNum}",
    //                 'schedule' => $scheduleGrid
    //             ];
    //         }
    //         $timetablesByContext[$contextKey] = ['info' => $contextInfo, 'timetables' => $timetablesForThisContext];
    //     }


    //     // dd([
    //     //     // '$genesGroupedByContext' => $genesGroupedByContext,
    //     //     '$timetablesByContext' => $timetablesByContext,
    //     // ]);


    //     // جلب بيانات الفلاتر (نفس الكود السابق)
    //     $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();
    //     $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');
    //     // ...

    //     return view('dashboard.timetables.sections', compact(
    //         'timetablesByContext',
    //         'timeslots',
    //         'plans', // لتعبئة الفلتر
    //         'request',
    //         'levels',
    //         'semesters',
    //         'academicYears'
    //     ));
    // }

    // public function viewSectionTimetables(Request $request)
    // {
    //     $allGenes = $this->getBestTimetableGenes();
    //     if (is_null($allGenes) || $allGenes->isEmpty()) {
    //         return view('dashboard.timetables.no-result');
    //     }

    //     // --- 1. فلترة الجينات بناءً على السياق المحدد من المستخدم (الكود الحالي صحيح) ---
    //     $genesForContext = $allGenes;
    //     if ($request->filled('plan_id')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_id == $request->plan_id);
    //     }
    //     if ($request->filled('plan_level')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_level == $request->plan_level);
    //     }
    //     if ($request->filled('plan_semester')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_semester == $request->plan_semester);
    //     }
    //     if ($request->filled('academic_year')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional($gene->section)->academic_year == $request->academic_year);
    //     }
    //     // ... (يمكن إضافة فلاتر أخرى هنا)

    //     // --- 2. تجميع الجينات حسب "المجموعة" (السياق) ---
    //     $genesGroupedByContext = $genesForContext->groupBy(function ($gene) {
    //         $section = $gene->section;
    //         if (!$section || !$section->planSubject) return 'unknown';
    //         return implode('-', [
    //             $section->planSubject->plan_id,
    //             $section->planSubject->plan_level,
    //             $section->planSubject->plan_semester,
    //             $section->academic_year,
    //             $section->branch ?? 'default'
    //         ]);
    //     });

    //     // --- 3. بناء الجداول لكل مجموعة (هنا التعديل الرئيسي) ---
    //     $timetablesByContext = [];
    //     foreach ($genesGroupedByContext as $contextKey => $genesInContext) {
    //         if ($contextKey === 'unknown' || $genesInContext->isEmpty()) continue;

    //         $firstGene = $genesInContext->first();
    //         $contextInfo = [
    //             'plan' => optional(optional($firstGene->section)->planSubject)->plan,
    //             'level' => optional($firstGene->section->planSubject)->plan_level,
    //             'semester' => optional($firstGene->section->planSubject)->plan_semester,
    //             'year' => $firstGene->section->academic_year,
    //             'branch' => $firstGene->section->branch,
    //         ];

    //         // *** الخطوة 4 (المعدلة): تحديد عدد الجداول الرئيسية بشكل صحيح ***
    //         // عدد الجداول يساوي أكبر "رقم شعبة" (section_number) موجود في هذا السياق، بغض النظر عن نوعها (نظري أو عملي).
    //         // هذا يفترض أن أرقام الشعب العملية هي التي تحدد عدد المجموعات.
    //         $maxSectionNumber = $genesInContext->pluck('section.section_number')->max() ?? 1;

    //         // *** الخطوة 5 (المعدلة): استخراج كل المحاضرات النظرية لهذا السياق مرة واحدة ***
    //         // هذه هي المحاضرات المشتركة بين كل جداول الطلاب.
    //         $theoryGenesInContext = $genesInContext->filter(function ($gene) {
    //             return $gene->section->activity_type == 'Theory';
    //         });

    //         $timetablesForThisContext = [];
    //         // *** نبدأ الحلقة من 1 إلى أكبر رقم شعبة وجدناه ***
    //         for ($mainSectionNum = 1; $mainSectionNum <= $maxSectionNumber; $mainSectionNum++) {

    //             // أ. جلب المحاضرات "الخاصة" بهذه الشعبة الرئيسية (غالباً العملية)
    //             // نجمع كل الجينات التي رقم شعبتها يطابق رقم الجدول الحالي.
    //             $specificGenesForThisSection = $genesInContext->filter(function ($gene) use ($mainSectionNum) {
    //                 return $gene->section->section_number == $mainSectionNum && $gene->section->activity_type != 'Theory';
    //             });

    //             // ب. دمج المحاضرات "الخاصة" مع "كل" المحاضرات النظرية "المشتركة"
    //             $allGenesForThisMainSection = $specificGenesForThisSection->merge($theoryGenesInContext);

    //             // ج. بناء شبكة الجدول فقط إذا كان هناك محاضرات لهذه الشعبة
    //             if ($allGenesForThisMainSection->isNotEmpty()) {
    //                 $scheduleGrid = [];
    //                 foreach ($allGenesForThisMainSection->unique('gene_id') as $gene) {
    //                     if ($gene->timeslot) {
    //                         $scheduleGrid[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene;
    //                     }
    //                 }

    //                 $timetablesForThisContext[] = [
    //                     'title' => "Section {$mainSectionNum}",
    //                     'schedule' => $scheduleGrid
    //                 ];
    //             }
    //         }

    //         if (!empty($timetablesForThisContext)) {
    //             $timetablesByContext[$contextKey] = ['info' => $contextInfo, 'timetables' => $timetablesForThisContext];
    //         }
    //     }

    //     // جلب بيانات الفلاتر (نفس الكود الحالي)
    //     $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();
    //     $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');
    //     $levels = PlanSubject::query()->orWhereNotNull('plan_level')->distinct()->orderBy('plan_level')->pluck('plan_level');
    //     $semesters = [1 => 'First', 2 => 'Second', 3 => 'Summer'];
    //     $academicYears = Section::query()->orWhereNotNull('academic_year')->distinct()->orderBy('academic_year', 'desc')->pluck('academic_year');

    //     return view('dashboard.timetables.sections', compact(
    //         'timetablesByContext',
    //         'timeslots',
    //         'plans',
    //         'request',
    //         'levels',
    //         'semesters',
    //         'academicYears'
    //     ));
    // }

    // public function viewSectionTimetables(Request $request)
    // {
    //     $allGenes = $this->getBestTimetableGenes();
    //     if (is_null($allGenes) || $allGenes->isEmpty()) {
    //         return view('dashboard.timetables.no-result');
    //     }

    //     // --- 1. فلترة الجينات بناءً على السياق (لا تغيير) ---
    //     $genesForContext = $allGenes;
    //     if ($request->filled('plan_id')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_id == $request->plan_id);
    //     }
    //     if ($request->filled('plan_level')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_level == $request->plan_level);
    //     }
    //     if ($request->filled('plan_semester')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_semester == $request->plan_semester);
    //     }
    //     if ($request->filled('academic_year')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional($gene->section)->academic_year == $request->academic_year);
    //     }
    //     // ...

    //     // --- 2. تجميع الجينات حسب السياق (لا تغيير) ---
    //     $genesGroupedByContext = $genesForContext->groupBy(function ($gene) {
    //         $section = $gene->section;
    //         if (!$section || !$section->planSubject) return 'unknown';
    //         return implode('-', [
    //             $section->planSubject->plan_id,
    //             $section->planSubject->plan_level,
    //             $section->planSubject->plan_semester,
    //             $section->academic_year,
    //             $section->branch ?? 'default'
    //         ]);
    //     });

    //     // --- 3. بناء الجداول لكل مجموعة (هنا التعديل الرئيسي) ---
    //     $timetablesByContext = [];
    //     foreach ($genesGroupedByContext as $contextKey => $genesInContext) {
    //         if ($contextKey === 'unknown' || $genesInContext->isEmpty()) continue;

    //         $firstGene = $genesInContext->first();
    //         $contextInfo = [
    //             'plan' => optional(optional($firstGene->section)->planSubject)->plan,
    //             'level' => optional($firstGene->section->planSubject)->plan_level,
    //             'semester' => optional($firstGene->section->planSubject)->plan_semester,
    //             'year' => $firstGene->section->academic_year,
    //             'branch' => $firstGene->section->branch,
    //         ];

    //         // *** الخطوة 4 (المعدلة): تحديد "أرقام الشعب الرئيسية" ***
    //         // هذه هي أرقام الشعب العملية فقط. هي التي تحدد عدد الجداول.
    //         $mainSectionNumbers = $genesInContext
    //             ->filter(fn($g) => $g->section->activity_type == 'Practical')
    //             ->pluck('section.section_number')
    //             ->unique()
    //             ->sort()
    //             ->values();

    //         // **حالة خاصة:** إذا لم يكن هناك أي شعب عملية على الإطلاق (كل المواد نظرية)،
    //         // نعتبر أن هناك شعبة رئيسية واحدة فقط (رقم 1).
    //         if ($mainSectionNumbers->isEmpty()) {
    //             $mainSectionNumbers = collect([1]);
    //         }

    //         // *** الخطوة 5 (لا تغيير): استخراج كل المحاضرات النظرية المشتركة ***
    //         $theoryGenesInContext = $genesInContext->filter(fn($gene) => $gene->section->activity_type == 'Theory');

    //         $timetablesForThisContext = [];
    //         // *** نبدأ الحلقة بناءً على أرقام الشعب الرئيسية التي حددناها ***
    //         foreach ($mainSectionNumbers as $mainSectionNum) {

    //             // أ. جلب المحاضرات "الخاصة" بهذه الشعبة (العملية والنظرية غير المشتركة)
    //             $specificGenesForThisSection = $genesInContext->filter(function ($gene) use ($mainSectionNum) {
    //                 // نأخذ أي شعبة رقمها يطابق رقم الشعبة الرئيسية الحالية
    //                 return $gene->section->section_number == $mainSectionNum;
    //             });

    //             // ب. دمج المحاضرات "الخاصة" مع المحاضرات النظرية "المشتركة"
    //             // نستخدم unique() لضمان عدم تكرار محاضرة نظرية إذا كان رقمها يطابق رقم شعبة عملية
    //             $allGenesForThisMainSection = $specificGenesForThisSection
    //                 ->merge($theoryGenesInContext)
    //                 ->unique('gene_id');

    //             // ج. بناء شبكة الجدول
    //             if ($allGenesForThisMainSection->isNotEmpty()) {
    //                 $scheduleGrid = [];
    //                 foreach ($allGenesForThisMainSection as $gene) {
    //                     if ($gene->timeslot) {
    //                         $scheduleGrid[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene;
    //                     }
    //                 }

    //                 $timetablesForThisContext[] = [
    //                     'title' => "Section {$mainSectionNum}", // العنوان الآن صحيح (مثلاً جدول شعبة 2)
    //                     'schedule' => $scheduleGrid
    //                 ];
    //             }
    //         }

    //         if (!empty($timetablesForThisContext)) {
    //             $timetablesByContext[$contextKey] = ['info' => $contextInfo, 'timetables' => $timetablesForThisContext];
    //         }
    //     }

    //     // جلب بيانات الفلاتر (لا تغيير)
    //     $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();
    //     $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');
    //     $levels = PlanSubject::query()->orWhereNotNull('plan_level')->distinct()->orderBy('plan_level')->pluck('plan_level');
    //     $semesters = [1 => 'First', 2 => 'Second', 3 => 'Summer'];
    //     $academicYears = Section::query()->orWhereNotNull('academic_year')->distinct()->orderBy('academic_year', 'desc')->pluck('academic_year');

    //     return view('dashboard.timetables.sections', compact(
    //         'timetablesByContext',
    //         'timeslots',
    //         'plans',
    //         'request',
    //         'levels',
    //         'semesters',
    //         'academicYears'
    //     ));
    // }

    //     public function viewSectionTimetables(Request $request)
    // {
    //     $allGenes = $this->getBestTimetableGenes();
    //     if (is_null($allGenes) || $allGenes->isEmpty()) {
    //         return view('dashboard.timetables.no-result');
    //     }

    //     // --- 1. فلترة الجينات بناءً على السياق (لا تغيير) ---
    //     $genesForContext = $allGenes;
    //     if ($request->filled('plan_id')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_id == $request->plan_id);
    //     }
    //     // ... (باقي الفلاتر)
    //     if ($request->filled('plan_level')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_level == $request->plan_level);
    //     }
    //     if ($request->filled('plan_semester')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_semester == $request->plan_semester);
    //     }
    //     if ($request->filled('academic_year')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional($gene->section)->academic_year == $request->academic_year);
    //     }

    //     // --- 2. تجميع الجينات حسب السياق (لا تغيير) ---
    //     $genesGroupedByContext = $genesForContext->groupBy(function ($gene) {
    //         $section = $gene->section;
    //         if (!$section || !$section->planSubject) return 'unknown';
    //         return implode('-', [
    //             $section->planSubject->plan_id, $section->planSubject->plan_level,
    //             $section->planSubject->plan_semester, $section->academic_year, $section->branch ?? 'default'
    //         ]);
    //     });

    //     // --- 3. بناء الجداول لكل مجموعة (هنا التعديل الرئيسي) ---
    //     $timetablesByContext = [];
    //     foreach ($genesGroupedByContext as $contextKey => $genesInContext) {
    //         if ($contextKey === 'unknown' || $genesInContext->isEmpty()) continue;

    //         $firstGene = $genesInContext->first();
    //         $contextInfo = [
    //             'plan' => optional(optional($firstGene->section)->planSubject)->plan,
    //             'level' => optional($firstGene->section->planSubject)->plan_level,
    //             'semester' => optional($firstGene->section->planSubject)->plan_semester,
    //             'year' => $firstGene->section->academic_year,
    //             'branch' => $firstGene->section->branch,
    //         ];

    //         // *** الخطوة 4 (معدلة بالكامل): تحديد أرقام الشعب الرئيسية بدقة ***

    //         // أ. جلب كل الشعب النظرية والعملية لهذا السياق
    //         $theorySectionsInContext = $genesInContext->filter(fn($g) => $g->section->activity_type == 'Theory');
    //         $practicalSectionsInContext = $genesInContext->filter(fn($g) => $g->section->activity_type == 'Practical');

    //         // ب. تحديد أكبر رقم شعبة للمواد "النظرية فقط" أو "النظرية والعملية"
    //         $lastTheorySectionNumber = $theorySectionsInContext->pluck('section.section_number')->max() ?? 0;

    //         // ج. تحديد أرقام الشعب الرئيسية. هي كل أرقام الشعب العملية الموجودة.
    //         $mainSectionNumbers = $practicalSectionsInContext
    //             ->pluck('section.section_number')
    //             ->unique()
    //             ->sort()
    //             ->values();

    //         // **حالة خاصة:** إذا لم يكن هناك أي شعب عملية (كل المواد نظرية)،
    //         // نعتبر أن هناك شعبة رئيسية واحدة فقط تساوي أكبر رقم شعبة نظرية (أو 1 إذا لم يوجد نظري).
    //         if ($mainSectionNumbers->isEmpty()) {
    //             $mainSectionNumbers = collect([max(1, $lastTheorySectionNumber)]);
    //         }

    //         // *** الخطوة 5: استخراج المحاضرات المشتركة ***
    //         // المحاضرات المشتركة هي كل المحاضرات النظرية.
    //         $commonTheoryGenes = $theorySectionsInContext;

    //         $timetablesForThisContext = [];
    //         // *** نبدأ الحلقة بناءً على أرقام الشعب الرئيسية التي حددناها ***
    //         foreach ($mainSectionNumbers as $mainSectionNum) {

    //             // أ. جلب المحاضرات العملية الخاصة بهذه الشعبة الرئيسية
    //             $specificPracticalGenes = $practicalSectionsInContext->filter(function ($gene) use ($mainSectionNum) {
    //                 return $gene->section->section_number == $mainSectionNum;
    //             });

    //             // ب. دمج المحاضرات العملية الخاصة مع "كل" المحاضرات النظرية المشتركة
    //             $allGenesForThisMainSection = $specificPracticalGenes
    //                 ->merge($commonTheoryGenes)
    //                 ->unique('gene_id');

    //             // ج. بناء شبكة الجدول
    //             if ($allGenesForThisMainSection->isNotEmpty()) {
    //                 $scheduleGrid = [];
    //                 foreach ($allGenesForThisMainSection as $gene) {
    //                     if ($gene->timeslot) {
    //                         $scheduleGrid[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene;
    //                     }
    //                 }

    //                 $timetablesForThisContext[] = [
    //                     'title' => "Section {$mainSectionNum}",
    //                     'schedule' => $scheduleGrid
    //                 ];
    //             }
    //         }

    //         if (!empty($timetablesForThisContext)) {
    //              $timetablesByContext[$contextKey] = ['info' => $contextInfo, 'timetables' => $timetablesForThisContext];
    //         }
    //     }

    //     // جلب بيانات الفلاتر (لا تغيير)
    //     $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();
    //     $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');
    //     $levels = PlanSubject::query()->orWhereNotNull('plan_level')->distinct()->orderBy('plan_level')->pluck('plan_level');
    //     $semesters = [1 => 'First', 2 => 'Second', 3 => 'Summer'];
    //     $academicYears = Section::query()->orWhereNotNull('academic_year')->distinct()->orderBy('academic_year', 'desc')->pluck('academic_year');

    //     return view('dashboard.timetables.sections', compact(
    //         'timetablesByContext',
    //         'timeslots',
    //         'plans',
    //         'request',
    //         'levels',
    //         'semesters',
    //         'academicYears'
    //     ));
    // }

    // public function viewSectionTimetables(Request $request)
    // {
    //     $allGenes = $this->getBestTimetableGenes(); // Renamed for clarity
    //     if (is_null($allGenes) || $allGenes->isEmpty()) {
    //         return view('dashboard.timetables.no-result');
    //     }

    //     // --- 1. Filter genes based on user's context request (No change) ---
    //     $genesForContext = $this->filterGenesByRequest($allGenes, $request);

    //     // --- 2. Group genes by their context (plan-level-semester-etc.) (No change) ---
    //     $genesGroupedByContext = $this->groupGenesByContext($genesForContext);

    //     // --- 3. Build timetables for each group (MAJOR LOGIC CHANGE HERE) ---
    //     $timetablesByContext = [];
    //     foreach ($genesGroupedByContext as $contextKey => $genesInContext) {
    //         if ($contextKey === 'unknown' || $genesInContext->isEmpty()) continue;

    //         $firstGene = $genesInContext->first();
    //         $contextInfo = $this->getContextInfo($firstGene);

    //         // --- Step 4 (NEW LOGIC): Determine the main student groups and their section numbers ---
    //         // Let's find out how many student groups we have.
    //         // This is determined by the maximum number of PRACTICAL sections for any single subject.
    //         $maxPracticalSectionsForAnySubject = $genesInContext
    //             ->filter(fn($g) => $g->section->activity_type == 'Practical')
    //             ->groupBy('section.plan_subject_id') // Group by subject
    //             ->map(fn($genesForSubject) => $genesForSubject->count()) // Count sections for each subject
    //             ->max() ?? 0;

    //         // If there are no practical sections at all, we assume there is only one student group.
    //         $numberOfMainGroups = $maxPracticalSectionsForAnySubject > 0 ? $maxPracticalSectionsForAnySubject : 1;

    //         // --- Step 5 (NEW LOGIC): Get all shared (Theory) lectures for this context ---
    //         $theoryGenesInContext = $genesInContext->filter(fn($gene) => $gene->section->activity_type == 'Theory');

    //         // --- Step 6 (NEW LOGIC): Build a timetable for each main student group ---
    //         $timetablesForThisContext = [];
    //         for ($groupIndex = 1; $groupIndex <= $numberOfMainGroups; $groupIndex++) {

    //             // This collection will hold all lectures for this specific student group's timetable.
    //             $allGenesForThisStudentGroup = collect();

    //             // a. Add all the shared THEORY lectures. Every group attends these.
    //             $allGenesForThisStudentGroup = $allGenesForThisStudentGroup->merge($theoryGenesInContext);

    //             // b. Find and add the SPECIFIC practical lectures for this group.
    //             // We need to iterate over every subject in the context.
    //             $subjectsInContext = $genesInContext->pluck('section.planSubject')->unique('id');

    //             foreach($subjectsInContext as $planSubject) {
    //                 // Get all practical sections for THIS subject
    //                 $practicalGenesForThisSubject = $genesInContext->filter(function($gene) use ($planSubject) {
    //                     return $gene->section->plan_subject_id == $planSubject->id && $gene->section->activity_type == 'Practical';
    //                 })->sortBy('section.section_number')->values(); // Sort by section number and re-index

    //                 // If this subject has practical sections, find the one for the current student group.
    //                 if ($practicalGenesForThisSubject->isNotEmpty()) {
    //                     // The first student group (index 1) gets the first practical section (at index 0), and so on.
    //                     $geneForThisGroup = $practicalGenesForThisSubject->get($groupIndex - 1);

    //                     if ($geneForThisGroup) {
    //                         $allGenesForThisStudentGroup->push($geneForThisGroup);
    //                     }
    //                 }
    //             }

    //             // c. Build the schedule grid for this student group's timetable.
    //             if ($allGenesForThisStudentGroup->isNotEmpty()) {
    //                 $scheduleGrid = [];
    //                 foreach ($allGenesForThisStudentGroup->unique('gene_id') as $gene) {
    //                     if ($gene->timeslot) {
    //                         $scheduleGrid[$gene->timeslot->day][$gene->timeslot->start_time][] = $gene;
    //                     }
    //                 }

    //                 $timetablesForThisContext[] = [
    //                     'title' => "Student Group {$groupIndex}",
    //                     'schedule' => $scheduleGrid
    //                 ];
    //             }
    //         }

    //         if (!empty($timetablesForThisContext)) {
    //              $timetablesByContext[$contextKey] = ['info' => $contextInfo, 'timetables' => $timetablesForThisContext];
    //         }
    //     }

    //     // --- Load data for filters (No change) ---
    //     $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();
    //     $timeslots = Timeslot::orderBy('start_time')->get()->groupBy('day');
    //     $levels = PlanSubject::query()->orWhereNotNull('plan_level')->distinct()->orderBy('plan_level')->pluck('plan_level');
    //     $semesters = [1 => 'First', 2 => 'Second', 3 => 'Summer'];
    //     $academicYears = Section::query()->orWhereNotNull('academic_year')->distinct()->orderBy('academic_year', 'desc')->pluck('academic_year');

    //     return view('dashboard.timetables.sections', compact(
    //         'timetablesByContext', 'timeslots', 'plans', 'request',
    //         'levels', 'semesters', 'academicYears'
    //     ));
    // }

    // // --- Helper functions to keep the main method clean ---

    // private function getBestTimetableGenes()
    // {
    //     $lastSuccessfulRun = Population::where('status', 'completed')->orderBy('end_time', 'desc')->first();
    //     if (!$lastSuccessfulRun || !$lastSuccessfulRun->best_chromosome_id) return null;

    //     $bestChromosome = \App\Models\Chromosome::find($lastSuccessfulRun->best_chromosome_id);
    //     if (!$bestChromosome) return null;

    //     return $bestChromosome->genes()->with([
    //         'section.planSubject.subject.subjectCategory',
    //         'section.planSubject.plan.department',
    //         'instructor.user',
    //         'room',
    //         'timeslot'
    //     ])->get();
    // }

    // private function filterGenesByRequest($allGenes, Request $request)
    // {
    //     $genesForContext = $allGenes;
    //     if ($request->filled('plan_id')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_id == $request->plan_id);
    //     }
    //     if ($request->filled('plan_level')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_level == $request->plan_level);
    //     }
    //     if ($request->filled('plan_semester')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional(optional($gene->section)->planSubject)->plan_semester == $request->plan_semester);
    //     }
    //     if ($request->filled('academic_year')) {
    //         $genesForContext = $genesForContext->filter(fn($gene) => optional($gene->section)->academic_year == $request->academic_year);
    //     }
    //     return $genesForContext;
    // }

    // private function groupGenesByContext($genes)
    // {
    //     return $genes->groupBy(function ($gene) {
    //         $section = $gene->section;
    //         if (!$section || !$section->planSubject) return 'unknown';
    //         return implode('-', [
    //             $section->planSubject->plan_id, $section->planSubject->plan_level,
    //             $section->planSubject->plan_semester, $section->academic_year, $section->branch ?? 'default'
    //         ]);
    //     });
    // }

    // private function getContextInfo($gene)
    // {
    //     return [
    //         'plan' => optional(optional($gene->section)->planSubject)->plan,
    //         'level' => optional($gene->section->planSubject)->plan_level,
    //         'semester' => optional($gene->section->planSubject)->plan_semester,
    //         'year' => $gene->section->academic_year,
    //         'branch' => $gene->section->branch,
    //     ];
    // }

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
