<?php

namespace App\Http\Controllers\Algorithm;

use Exception;
use App\Models\Gene;
use App\Models\Section;
use App\Models\Timeslot;
use App\Models\Chromosome;
use App\Models\Population;
use App\Models\Instructor;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class TimetableResultController extends Controller
{
    /**
     * Display a list of generation runs and top chromosomes for a selected run.
     */
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
     * عرض تفاصيل الكروموسوم (الجدول) - محسن لحل مشكلة عدد التعارضات
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

            // **الحل الصحيح**: فحص التعارضات بدون تكرار الجينات
            $conflictChecker = new ConflictCheckerService($genes);
            $conflicts = $conflictChecker->getConflicts();
            $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

            // **إحصائيات التعارضات الصحيحة**
            $conflictStats = [
                'total_conflicts' => count($conflicts),
                'total_penalty' => $chromosome->penalty_value ?? 0,
                'student_conflicts' => $chromosome->student_conflict_penalty ?? 0,
                'teacher_conflicts' => $chromosome->teacher_conflict_penalty ?? 0,
                'room_conflicts' => $chromosome->room_conflict_penalty ?? 0,
                'capacity_conflicts' => $chromosome->capacity_conflict_penalty ?? 0,
                'room_type_conflicts' => $chromosome->room_type_conflict_penalty ?? 0,
                'teacher_eligibility_conflicts' => $chromosome->teacher_eligibility_conflict_penalty ?? 0,
            ];

            // --- إعداد البيانات للعرض ---
            $allRooms = Room::with('roomType')->get()->map(function ($room) {
                return [
                    'id' => $room->id,
                    'name' => $room->room_name,
                    'type' => optional($room->roomType)->room_type_name ?? '',
                    'size' => $room->room_size,
                ];
            })->toArray();

            $allInstructors = Instructor::with(['user', 'subjects'])->get()->map(function ($instructor) {
                return [
                    'id' => $instructor->id,
                    'name' => $instructor->user->name ?? 'Unknown',
                    'subject_ids' => $instructor->subjects->pluck('id')->toArray(),
                ];
            })->toArray();

            $timeSlotUsage = [];
            foreach ($genes as $gene) {
                $timeslotIds = is_string($gene->timeslot_ids) 
                    ? json_decode($gene->timeslot_ids, true) 
                    : ($gene->timeslot_ids ?? []);
                    
                foreach ($timeslotIds as $tsId) {
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
                'conflictStats',
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

    /**
     * حفظ التعديلات - محسن مع حفظ السحب والإفلات
     */
    // public function saveEdits(Request $request)
    // {
    //     $request->validate([
    //         'chromosome_id' => 'required|exists:chromosomes,chromosome_id',
    //         'edits' => 'array',
    //         'edits.*.gene_id' => 'required|exists:genes,gene_id',
    //         'edits.*.field' => 'required|in:instructor,room',
    //         'edits.*.new_value_id' => 'required|integer',
    //         'moves' => 'array',
    //         'moves.*.gene_id' => 'required|exists:genes,gene_id',
    //         'moves.*.new_timeslot_ids' => 'required|array',
    //         'moves.*.new_timeslot_ids.*' => 'integer|exists:timeslots,id',
    //     ]);

    //     try {
    //         $updatedGenes = [];
    //         $movesSaved = 0;
    //         $editsSaved = 0;
            
    //         DB::transaction(function () use ($request, &$updatedGenes, &$movesSaved, &$editsSaved) {
    //             // حفظ تعديلات الحقول (المدرس والقاعة)
    //             if (!empty($request->edits)) {
    //                 foreach ($request->edits as $edit) {
    //                     $gene = Gene::findOrFail($edit['gene_id']);
    //                     $field = $edit['field'] . '_id';
    //                     $oldValue = $gene->{$field};

    //                     // حفظ في جدول التغييرات
    //                     DB::table('gene_edits')->insert([
    //                         'gene_id' => $edit['gene_id'],
    //                         'field' => $edit['field'],
    //                         'old_value_id' => $oldValue,
    //                         'new_value_id' => $edit['new_value_id'],
    //                         'changed_by' => auth()->id(),
    //                         'changed_at' => now(),
    //                         'created_at' => now(),
    //                         'updated_at' => now(),
    //                     ]);

    //                     // تحديث الجين
    //                     $gene->{$field} = $edit['new_value_id'];
    //                     $gene->save();
                        
    //                     $updatedGenes[] = $gene->fresh()->load(['instructor.user', 'room.roomType']);
    //                     $editsSaved++;
    //                 }
    //             }

    //             // **حفظ تحركات البلوكات (السحب والإفلات)**
    //             if (!empty($request->moves)) {
    //                 foreach ($request->moves as $move) {
    //                     $gene = Gene::findOrFail($move['gene_id']);
    //                     $oldTimeslotIds = is_string($gene->timeslot_ids) 
    //                         ? json_decode($gene->timeslot_ids, true) 
    //                         : ($gene->timeslot_ids ?? []);
    //                     $newTimeslotIds = $move['new_timeslot_ids'];

    //                     // حفظ في جدول التغييرات
    //                     DB::table('gene_edits')->insert([
    //                         'gene_id' => $move['gene_id'],
    //                         'field' => 'timeslot_ids',
    //                         'old_value_id' => json_encode($oldTimeslotIds),
    //                         'new_value_id' => json_encode($newTimeslotIds),
    //                         'changed_by' => auth()->id(),
    //                         'changed_at' => now(),
    //                         'created_at' => now(),
    //                         'updated_at' => now(),
    //                     ]);

    //                     // تحديث الجين
    //                     $gene->timeslot_ids = $newTimeslotIds;
    //                     $gene->save();

    //                     $updatedGenes[] = $gene->fresh()->load(['instructor.user', 'room.roomType']);
    //                     $movesSaved++;
    //                 }
    //             }

    //             // **إعادة حساب العقوبات للكروموسوم**
    //             if (!empty($updatedGenes)) {
    //                 $this->recalculateChromosomeFitness($request->chromosome_id);
    //             }
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'message' => "تم حفظ {$editsSaved} تعديل و {$movesSaved} حركة بنجاح",
    //             'updated_genes' => $updatedGenes,
    //             'edits_saved' => $editsSaved,
    //             'moves_saved' => $movesSaved
    //         ]);

    //     } catch (\Exception $e) {
    //         Log::error('فشل حفظ التعديلات: ' . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'فشل الحفظ: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
    /**
 * حفظ التعديلات - محسن مع الحفظ على الكروموسوم الأصلي أولاً
 */
// public function saveEdits(Request $request)
// {
//     $request->validate([
//         'chromosome_id' => 'required|exists:chromosomes,chromosome_id',
//         'edits' => 'array',
//         'edits.*.gene_id' => 'required|exists:genes,gene_id',
//         'edits.*.field' => 'required|in:instructor,room',
//         'edits.*.new_value_id' => 'required|integer',
//         'moves' => 'array',
//         'moves.*.gene_id' => 'required|exists:genes,gene_id',
//         'moves.*.new_timeslot_ids' => 'required|array',
//         'moves.*.new_timeslot_ids.*' => 'integer|exists:timeslots,id',
//     ]);

//     try {
//         $updatedGenes = [];
//         $movesSaved = 0;
//         $editsSaved = 0;
        
//         DB::transaction(function () use ($request, &$updatedGenes, &$movesSaved, &$editsSaved) {
            
//             // **المرحلة الأولى**: حفظ تعديلات الحقول (المدرس والقاعة) على الكروموسوم الأصلي
//             if (!empty($request->edits)) {
//                 foreach ($request->edits as $edit) {
//                     $gene = Gene::findOrFail($edit['gene_id']);
//                     $field = $edit['field'] . '_id';
//                     $oldValue = $gene->{$field};

//                     // **حفظ التعديل على الكروموسوم الأصلي مباشرة**
//                     $gene->{$field} = $edit['new_value_id'];
//                     $gene->save();

//                     // **اختياري**: حفظ في جدول التاريخ للتتبع (تعليق مؤقتاً)
//                     /*
//                     DB::table('gene_edits')->insert([
//                         'gene_id' => $edit['gene_id'],
//                         'field' => $edit['field'],
//                         'old_value_id' => $oldValue,
//                         'new_value_id' => $edit['new_value_id'],
//                         'changed_by' => auth()->id(),
//                         'changed_at' => now(),
//                         'created_at' => now(),
//                         'updated_at' => now(),
//                     ]);
//                     */

//                     $updatedGenes[] = $gene->fresh()->load(['instructor.user', 'room.roomType']);
//                     $editsSaved++;
//                 }
//             }

//             // **المرحلة الثانية**: حفظ تحركات البلوكات (السحب والإفلات) على الكروموسوم الأصلي
//             if (!empty($request->moves)) {
//                 foreach ($request->moves as $move) {
//                     $gene = Gene::findOrFail($move['gene_id']);
//                     $oldTimeslotIds = is_string($gene->timeslot_ids) 
//                         ? json_decode($gene->timeslot_ids, true) 
//                         : ($gene->timeslot_ids ?? []);
//                     $newTimeslotIds = $move['new_timeslot_ids'];

//                     // **حفظ التحريك على الكروموسوم الأصلي مباشرة**
//                     $gene->timeslot_ids = $newTimeslotIds;
//                     $gene->save();

//                     // **اختياري**: حفظ في جدول التاريخ للتتبع (تعليق مؤقتاً)
//                     /*
//                     DB::table('gene_edits')->insert([
//                         'gene_id' => $move['gene_id'],
//                         'field' => 'timeslot_ids',
//                         'old_value_id' => json_encode($oldTimeslotIds),
//                         'new_value_id' => json_encode($newTimeslotIds),
//                         'changed_by' => auth()->id(),
//                         'changed_at' => now(),
//                         'created_at' => now(),
//                         'updated_at' => now(),
//                     ]);
//                     */

//                     $updatedGenes[] = $gene->fresh()->load(['instructor.user', 'room.roomType']);
//                     $movesSaved++;
//                 }
//             }

//             // **المرحلة الثالثة**: إعادة حساب العقوبات للكروموسوم بعد التعديلات
//             if (!empty($updatedGenes)) {
//                 $this->recalculateChromosomeFitness($request->chromosome_id);
//                 Log::info("Chromosome {$request->chromosome_id} fitness recalculated after {$editsSaved} edits and {$movesSaved} moves");
//             }
//         });

//         return response()->json([
//             'success' => true,
//             'message' => "تم حفظ {$editsSaved} تعديل و {$movesSaved} حركة بنجاح على الكروموسوم الأصلي",
//             'updated_genes' => $updatedGenes,
//             'edits_saved' => $editsSaved,
//             'moves_saved' => $movesSaved,
//             'chromosome_updated' => true
//         ]);

//     } catch (\Exception $e) {
//         Log::error('فشل حفظ التعديلات على الكروموسوم الأصلي: ' . $e->getMessage());
//         return response()->json([
//             'success' => false,
//             'message' => 'فشل الحفظ: ' . $e->getMessage()
//         ], 500);
//     }
// }

/**
 * حفظ التعديلات - مع إعادة حساب التعارضات الشاملة
 */
public function saveEdits(Request $request)
{
    $request->validate([
        'chromosome_id' => 'required|exists:chromosomes,chromosome_id',
        'edits' => 'array',
        'edits.*.gene_id' => 'required|exists:genes,gene_id',
        'edits.*.field' => 'required|in:instructor,room',
        'edits.*.new_value_id' => 'required|integer',
        'moves' => 'array',
        'moves.*.gene_id' => 'required|exists:genes,gene_id',
        'moves.*.new_timeslot_ids' => 'required|array',
        'moves.*.new_timeslot_ids.*' => 'integer|exists:timeslots,id',
    ]);

    try {
        $chromosome = Chromosome::findOrFail($request->chromosome_id);
        $updatedGenes = [];
        $movesSaved = 0;
        $editsSaved = 0;
        
        // حفظ القيم القديمة للمقارنة
        $oldPenalties = [
            'total_penalty' => $chromosome->penalty_value ?? 0,
            'student_conflicts' => $chromosome->student_conflict_penalty ?? 0,
            'teacher_conflicts' => $chromosome->teacher_conflict_penalty ?? 0,
            'room_conflicts' => $chromosome->room_conflict_penalty ?? 0,
            'capacity_conflicts' => $chromosome->capacity_conflict_penalty ?? 0,
            'room_type_conflicts' => $chromosome->room_type_conflict_penalty ?? 0,
            'teacher_eligibility_conflicts' => $chromosome->teacher_eligibility_conflict_penalty ?? 0,
        ];
        
        DB::transaction(function () use ($request, &$updatedGenes, &$movesSaved, &$editsSaved, $chromosome) {
            
            // **المرحلة الأولى**: حفظ تعديلات الحقول (المدرس والقاعة)
            if (!empty($request->edits)) {
                foreach ($request->edits as $edit) {
                    $gene = Gene::findOrFail($edit['gene_id']);
                    $field = $edit['field'] . '_id';
                    $oldValue = $gene->{$field};

                    // حفظ التعديل على الكروموسوم الأصلي
                    $gene->{$field} = $edit['new_value_id'];
                    $gene->save();

                    $updatedGenes[] = $gene->fresh()->load(['instructor.user', 'room.roomType', 'section.planSubject.subject']);
                    $editsSaved++;
                    
                    Log::info("Gene {$gene->gene_id}: {$field} changed from {$oldValue} to {$edit['new_value_id']}");
                }
            }

            // **المرحلة الثانية**: حفظ تحركات البلوكات (السحب والإفلات)
            if (!empty($request->moves)) {
                foreach ($request->moves as $move) {
                    $gene = Gene::findOrFail($move['gene_id']);
                    $oldTimeslotIds = is_string($gene->timeslot_ids) 
                        ? json_decode($gene->timeslot_ids, true) 
                        : ($gene->timeslot_ids ?? []);
                    $newTimeslotIds = $move['new_timeslot_ids'];

                    // حفظ التحريك على الكروموسوم الأصلي
                    $gene->timeslot_ids = $newTimeslotIds;
                    $gene->save();

                    $updatedGenes[] = $gene->fresh()->load(['instructor.user', 'room.roomType', 'section.planSubject.subject']);
                    $movesSaved++;
                    
                    Log::info("Gene {$gene->gene_id}: timeslots changed from [" . implode(',', $oldTimeslotIds) . "] to [" . implode(',', $newTimeslotIds) . "]");
                }
            }

            // **المرحلة الثالثة**: إعادة حساب شاملة لكل التعارضات
            if (!empty($updatedGenes)) {
                $this->recalculateAllConflicts($chromosome);
            }
        });

        // جلب القيم الجديدة بعد إعادة الحساب
        $chromosome->refresh();
        $newPenalties = [
            'total_penalty' => $chromosome->penalty_value ?? 0,
            'student_conflicts' => $chromosome->student_conflict_penalty ?? 0,
            'teacher_conflicts' => $chromosome->teacher_conflict_penalty ?? 0,
            'room_conflicts' => $chromosome->room_conflict_penalty ?? 0,
            'capacity_conflicts' => $chromosome->capacity_conflict_penalty ?? 0,
            'room_type_conflicts' => $chromosome->room_type_conflict_penalty ?? 0,
            'teacher_eligibility_conflicts' => $chromosome->teacher_eligibility_conflict_penalty ?? 0,
        ];

        // حساب التحسن أو التدهور
        $penaltyChange = $newPenalties['total_penalty'] - $oldPenalties['total_penalty'];
        $improvementText = $penaltyChange < 0 ? "تحسن بـ " . abs($penaltyChange) . " عقوبة" : 
                          ($penaltyChange > 0 ? "زاد بـ " . $penaltyChange . " عقوبة" : "لا تغيير في العقوبات");

        return response()->json([
            'success' => true,
            'message' => "تم حفظ {$editsSaved} تعديل و {$movesSaved} حركة بنجاح - {$improvementText}",
            'updated_genes' => $updatedGenes,
            'edits_saved' => $editsSaved,
            'moves_saved' => $movesSaved,
            'old_penalties' => $oldPenalties,
            'new_penalties' => $newPenalties,
            'penalty_change' => $penaltyChange,
            'improvement_text' => $improvementText,
            'chromosome_updated' => true,
            'fitness_value' => $chromosome->fitness_value
        ]);

    } catch (\Exception $e) {
        Log::error('فشل حفظ التعديلات: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        return response()->json([
            'success' => false,
            'message' => 'فشل الحفظ: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * إعادة حساب شاملة لكل أنواع التعارضات للكروموسوم
 */
private function recalculateAllConflicts(Chromosome $chromosome): void
{
    // جلب كل الجينات للكروموسوم مع العلاقات المطلوبة
    $genes = $chromosome->genes()->with([
        'section.planSubject.subject', 
        'room.roomType', 
        'instructor.subjects',
        'section.instructor' // المدرس المخصص للشعبة
    ])->get();

    if ($genes->isEmpty()) {
        Log::warning("No genes found for chromosome {$chromosome->chromosome_id}");
        return;
    }

    // حساب كل أنواع العقوبات
    $penalties = $this->calculateAllPenalties($genes);
    $totalPenalty = array_sum($penalties);
    $fitnessValue = 1 / (1 + $totalPenalty);

    // تحديث الكروموسوم
    $chromosome->update(array_merge($penalties, [
        'penalty_value' => $totalPenalty,
        'fitness_value' => $fitnessValue,
    ]));

    Log::info("Chromosome {$chromosome->chromosome_id} recalculated:");
    Log::info("- Total Penalty: {$totalPenalty}");
    Log::info("- Student Conflicts: {$penalties['student_conflict_penalty']}");
    Log::info("- Teacher Conflicts: {$penalties['teacher_conflict_penalty']}");
    Log::info("- Room Conflicts: {$penalties['room_conflict_penalty']}");
    Log::info("- Capacity Conflicts: {$penalties['capacity_conflict_penalty']}");
    Log::info("- Room Type Conflicts: {$penalties['room_type_conflict_penalty']}");
    Log::info("- Teacher Eligibility Conflicts: {$penalties['teacher_eligibility_conflict_penalty']}");
    Log::info("- Fitness Value: {$fitnessValue}");
}

    /**
     * إعادة حساب العقوبات للكروموسوم بعد التعديل
     */
    private function recalculateChromosomeFitness(int $chromosomeId): void
    {
        $chromosome = Chromosome::findOrFail($chromosomeId);
        $genes = $chromosome->genes()->with([
            'section.planSubject.subject', 
            'room.roomType', 
            'instructor.subjects'
        ])->get();

        if ($genes->isEmpty()) {
            return;
        }

        $penalties = $this->calculateAllPenalties($genes);
        $totalPenalty = array_sum($penalties);
        $fitnessValue = 1 / (1 + $totalPenalty);

        $chromosome->update(array_merge($penalties, [
            'penalty_value' => $totalPenalty,
            'fitness_value' => $fitnessValue,
        ]));

        Log::info("Chromosome {$chromosomeId} fitness recalculated. New penalty: {$totalPenalty}");
    }

    /**
     * حساب جميع أنواع العقوبات
     */
    private function calculateAllPenalties(Collection $genes): array
    {
        $resourceUsageMap = [];
        
        return [
            'student_conflict_penalty' => $this->calculateStudentConflicts($genes, $resourceUsageMap),
            'teacher_conflict_penalty' => $this->calculateTeacherConflicts($genes, $resourceUsageMap),
            'room_conflict_penalty' => $this->calculateRoomConflicts($genes, $resourceUsageMap),
            'capacity_conflict_penalty' => $this->calculateCapacityConflicts($genes),
            'room_type_conflict_penalty' => $this->calculateRoomTypeConflicts($genes),
            'teacher_eligibility_conflict_penalty' => $this->calculateTeacherEligibilityConflicts($genes),
        ];
    }

    private function calculateStudentConflicts(Collection $genes, array &$usageMap): int
    {
        $penalty = 0;
        $studentGroupUsage = [];
        $sharedTheoryUsage = [];

        foreach ($genes as $gene) {
            $studentGroupIds = is_string($gene->student_group_id) 
                ? json_decode($gene->student_group_id, true) 
                : ($gene->student_group_id ?? []);
            
            $timeslotIds = is_string($gene->timeslot_ids) 
                ? json_decode($gene->timeslot_ids, true) 
                : ($gene->timeslot_ids ?? []);

            $isTheoreticalBlock = Str::contains($gene->lecture_unique_id, 'theory');

            foreach ($timeslotIds as $timeslotId) {
                if ($isTheoreticalBlock) {
                    $sectionInfo = $gene->section ?? null;
                    $contextKey = '';
                    
                    if ($sectionInfo) {
                        $planSubject = optional($sectionInfo)->planSubject;
                        $contextKey = implode('-', [
                            optional($planSubject)->plan_level ?? 0,
                            optional($planSubject)->plan_semester ?? 0,
                            $sectionInfo->academic_year ?? 0,
                            $sectionInfo->branch ?? 'default'
                        ]);
                    }

                    if (isset($sharedTheoryUsage[$contextKey][$timeslotId])) {
                        $penalty += 1;
                    }
                    $sharedTheoryUsage[$contextKey][$timeslotId] = true;

                } else {
                    foreach ($studentGroupIds as $groupId) {
                        if (isset($studentGroupUsage[$groupId][$timeslotId])) {
                            $penalty += 1;
                        }
                        $studentGroupUsage[$groupId][$timeslotId] = true;
                    }
                }
            }
        }

        $usageMap['student_groups'] = $studentGroupUsage;
        $usageMap['theoretical_shared'] = $sharedTheoryUsage;
        return $penalty;
    }

    private function calculateTeacherConflicts(Collection $genes, array &$usageMap): int
    {
        $penalty = 0;
        foreach ($genes as $gene) {
            $timeslotIds = is_string($gene->timeslot_ids) 
                ? json_decode($gene->timeslot_ids, true) 
                : ($gene->timeslot_ids ?? []);

            foreach ($timeslotIds as $timeslotId) {
                if (isset($usageMap['instructors'][$gene->instructor_id][$timeslotId])) {
                    $penalty += 1;
                }
                $usageMap['instructors'][$gene->instructor_id][$timeslotId] = true;
            }
        }
        return $penalty;
    }

    private function calculateRoomConflicts(Collection $genes, array &$usageMap): int
    {
        $penalty = 0;
        foreach ($genes as $gene) {
            $timeslotIds = is_string($gene->timeslot_ids) 
                ? json_decode($gene->timeslot_ids, true) 
                : ($gene->timeslot_ids ?? []);

            foreach ($timeslotIds as $timeslotId) {
                if (isset($usageMap['rooms'][$gene->room_id][$timeslotId])) {
                    $penalty += 1;
                }
                $usageMap['rooms'][$gene->room_id][$timeslotId] = true;
            }
        }
        return $penalty;
    }

    private function calculateCapacityConflicts(Collection $genes): int
    {
        $penalty = 0;
        foreach ($genes->unique('lecture_unique_id') as $gene) {
            if ($gene->section->student_count > $gene->room->room_size) {
                $penalty += 1;
            }
        }
        return $penalty;
    }

    private function calculateRoomTypeConflicts(Collection $genes): int
    {
        $penalty = 0;
        foreach ($genes->unique('lecture_unique_id') as $gene) {
            $isPracticalBlock = Str::contains($gene->lecture_unique_id, 'practical');
            $isPracticalRoom = Str::contains(strtolower(optional($gene->room->roomType)->room_type_name ?? ''), ['lab', 'مختبر']);

            if ($isPracticalBlock && !$isPracticalRoom) {
                $penalty += 1;
            }
            if (!$isPracticalBlock && $isPracticalRoom) {
                $penalty += 1;
            }
        }
        return $penalty;
    }

    private function calculateTeacherEligibilityConflicts(Collection $genes): int
    {
        $penalty = 0;
        foreach ($genes->unique('lecture_unique_id') as $gene) {
            $instructor = $gene->instructor;
            $section = $gene->section;
            $subject = optional(optional($section)->planSubject)->subject;

            if (!$instructor || !$subject) {
                $penalty += 1;
                continue;
            }

            if ($section->instructor && $section->instructor->id == $instructor->id) {
                continue; // لا عقوبة - هو المدرس المخصص
            }

            if (!$instructor->subjects || !$instructor->subjects->contains($subject->id)) {
                $penalty += 1;
            }
        }
        return $penalty;
    }
}

/**
 * خدمة فحص التعارضات المحسنة - مدمجة في نفس الملف
 */
class ConflictCheckerService
{
    private Collection $genes;
    public array $conflicts = [];
    private array $conflictingGeneIds = [];

    public function __construct(Collection $genes)
    {
        $this->genes = $genes;
        $this->findConflicts();
    }

    private function findConflicts(): void
    {
        $allTimeslots = Timeslot::all()->keyBy('id');

        // 1. تعارضات الموارد (مدرس، قاعة، طلاب)
        $this->checkResourceConflicts($allTimeslots);

        // 2. تعارضات السعة ونوع القاعة وأهلية المدرس
        $this->checkQualityConflicts();
    }

    private function checkResourceConflicts($allTimeslots): void
    {
        $usageMap = [
            'instructors' => [],
            'rooms' => [],
            'student_groups' => [],
            'theoretical_shared' => []
        ];

        foreach ($this->genes as $gene) {
            $timeslotIds = is_string($gene->timeslot_ids) 
                ? json_decode($gene->timeslot_ids, true) 
                : ($gene->timeslot_ids ?? []);
            
            $studentGroupIds = is_string($gene->student_group_id) 
                ? json_decode($gene->student_group_id, true) 
                : ($gene->student_group_id ?? []);

            $isTheoreticalBlock = Str::contains($gene->lecture_unique_id, 'theory');

            foreach ($timeslotIds as $timeslotId) {
                // فحص تعارضات المدرسين
                if (isset($usageMap['instructors'][$gene->instructor_id][$timeslotId])) {
                    $conflictingGeneId = $usageMap['instructors'][$gene->instructor_id][$timeslotId];
                    $this->addConflict(
                        'Instructor Conflict',
                        "المدرس له محاضرتان متداخلتان",
                        [$gene->gene_id, $conflictingGeneId],
                        '#dc3545'
                    );
                }
                $usageMap['instructors'][$gene->instructor_id][$timeslotId] = $gene->gene_id;

                // فحص تعارضات القاعات
                if (isset($usageMap['rooms'][$gene->room_id][$timeslotId])) {
                    $conflictingGeneId = $usageMap['rooms'][$gene->room_id][$timeslotId];
                    $this->addConflict(
                        'Room Conflict',
                        "القاعة محجوزة لمحاضرتين متداخلتين",
                        [$gene->gene_id, $conflictingGeneId],
                        '#fd7e14'
                    );
                }
                $usageMap['rooms'][$gene->room_id][$timeslotId] = $gene->gene_id;

                // فحص تعارضات الطلاب
                if ($isTheoreticalBlock) {
                    $sectionInfo = $gene->section ?? null;
                    $contextKey = '';
                    
                    if ($sectionInfo) {
                        $planSubject = optional($sectionInfo)->planSubject;
                        $contextKey = implode('-', [
                            optional($planSubject)->plan_level ?? 0,
                            optional($planSubject)->plan_semester ?? 0,
                            $sectionInfo->academic_year ?? 0,
                            $sectionInfo->branch ?? 'default'
                        ]);
                    }

                    if (isset($usageMap['theoretical_shared'][$contextKey][$timeslotId])) {
                        $conflictingGeneId = $usageMap['theoretical_shared'][$contextKey][$timeslotId];
                        $this->addConflict(
                            'Student Conflict',
                            "تعارض طلاب في المواد النظرية",
                            [$gene->gene_id, $conflictingGeneId],
                            '#e83e8c'
                        );
                    }
                    $usageMap['theoretical_shared'][$contextKey][$timeslotId] = $gene->gene_id;

                } else {
                    foreach ($studentGroupIds as $groupId) {
                        if (isset($usageMap['student_groups'][$groupId][$timeslotId])) {
                            $conflictingGeneId = $usageMap['student_groups'][$groupId][$timeslotId];
                            $this->addConflict(
                                'Student Conflict',
                                "تعارض مجموعة طلاب في المواد العملية",
                                [$gene->gene_id, $conflictingGeneId],
                                '#e83e8c'
                            );
                        }
                        $usageMap['student_groups'][$groupId][$timeslotId] = $gene->gene_id;
                    }
                }
            }
        }
    }

    private function checkQualityConflicts(): void
    {
        foreach ($this->genes as $gene) {
            // فحص سعة القاعة
            if ($gene->section && $gene->room && $gene->section->student_count > $gene->room->room_size) {
                $this->addConflict(
                    'Room Capacity',
                    "عدد الطلاب ({$gene->section->student_count}) يتجاوز سعة القاعة ({$gene->room->room_size})",
                    [$gene->gene_id],
                    '#fd7e14'
                );
            }

            // فحص نوع القاعة
            if ($gene->section && $gene->room && $gene->room->roomType && $gene->section->planSubject && $gene->section->planSubject->subject) {
                $activityType = $gene->section->activity_type;
                $roomTypeName = strtolower($gene->room->roomType->room_type_name);
                if ($activityType == 'Practical' && !Str::contains($roomTypeName, ['lab', 'مختبر', 'workshop', 'ورشة'])) {
                    $this->addConflict(
                        'Room Type',
                        "مادة عملية في قاعة غير مخبرية ({$gene->room->room_name})",
                        [$gene->gene_id],
                        '#ffc107'
                    );
                }
            }

            // فحص أهلية المدرس
            if ($gene->instructor && $gene->section && $gene->section->planSubject && $gene->section->planSubject->subject) {
                $instructor = $gene->instructor;
                $section = $gene->section;
                $subject = $gene->section->planSubject->subject;

                // إذا لم يكن المدرس المخصص وغير مؤهل
                if (!($section->instructor && $section->instructor->id == $instructor->id) && 
                    !($instructor->subjects && $instructor->subjects->contains($subject->id))) {
                    $this->addConflict(
                        'Instructor Qualification',
                        "المدرس {$gene->instructor->user->name} غير مؤهل لتدريس {$subject->subject_name}",
                        [$gene->gene_id],
                        '#6f42c1'
                    );
                }
            }
        }
    }

    private function addConflict(string $type, string $description, array $geneIds, string $color): void
    {
        $this->conflicts[] = compact('type', 'description', 'geneIds', 'color');
        foreach ($geneIds as $id) {
            $this->conflictingGeneIds[$id] = $type;
        }
    }

    public function getConflicts(): array
    {
        return $this->conflicts;
    }

    public function getConflictingGeneIds(): array
    {
        return array_keys($this->conflictingGeneIds);
    }

    public function getGeneConflictType(int $geneId): ?string
    {
        return $this->conflictingGeneIds[$geneId] ?? null;
    }

    public function getGeneConflictColor(int $geneId): string
    {
        return $this->getConflictColor($this->getGeneConflictType($geneId));
    }

    private function getConflictColor(?string $type): string
    {
        return match ($type) {
            'Instructor Conflict' => '#dc3545',        // أحمر للمدرسين
            'Room Conflict' => '#fd7e14',              // برتقالي للقاعات
            'Student Conflict' => '#e83e8c',           // وردي للطلاب
            'Room Capacity' => '#fd7e14',              // برتقالي لسعة القاعة
            'Room Type' => '#ffc107',                  // أصفر لنوع القاعة
            'Instructor Qualification' => '#6f42c1',   // بنفسجي لأهلية المدرس
            default => '#6c7d6dff'                       // رمادي للباقي
        };
    }
}