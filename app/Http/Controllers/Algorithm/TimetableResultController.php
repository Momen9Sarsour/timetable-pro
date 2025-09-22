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

    // public function saveEdits(Request $request)
    // {
    //     $request->validate([
    //         'edits' => 'required|array',
    //         'edits.*.gene_id' => 'required|exists:genes,gene_id',
    //         'edits.*.field' => 'required|in:instructor,room',
    //         'edits.*.new_value_id' => 'required|integer',
    //     ]);

    //     try {
    //         DB::transaction(function () use ($request) {
    //             foreach ($request->edits as $edit) {
    //                 $gene = Gene::findOrFail($edit['gene_id']);
    //                 $field = $edit['field'] . '_id'; // instructor_id, room_id

    //                 // حفظ التعديل في جدول التغييرات
    //                 DB::table('gene_edits')->insert([
    //                     'gene_id' => $edit['gene_id'],
    //                     'field' => $edit['field'],
    //                     'old_value_id' => $gene->{$field},
    //                     'new_value_id' => $edit['new_value_id'],
    //                     'changed_by' => auth()->id(),
    //                     'changed_at' => now(),
    //                     'created_at' => now(),
    //                     'updated_at' => now(),
    //                 ]);

    //                 // تحديث الجين
    //                 $gene->{$field} = $edit['new_value_id'];
    //                 $gene->save();
    //             }
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'تم حفظ التعديلات بنجاح.'
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'فشل الحفظ: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
    /**
     * حفظ التعديلات والحركات في قاعدة البيانات
     */
    public function saveEdits(Chromosome $chromosome, Request $request)
    {
        $request->validate([
            'edits' => 'array',
            'edits.*.gene_id' => 'required|exists:genes,gene_id',
            'edits.*.field' => 'required|in:instructor,room',
            'edits.*.new_value_id' => 'required|integer',
            'edits.*.old_value_id' => 'nullable',
            'moves' => 'array',
            'moves.*.gene_id' => 'required|exists:genes,gene_id',
            'moves.*.new_timeslots' => 'required|array',
            'moves.*.new_position' => 'required|array',
        ]);

        try {
            DB::transaction(function () use ($request, $chromosome) {
                $currentUserId = 1; // مؤقت - سيتم استبداله بـ auth()->id()

                // معالجة تعديلات الحقول (مدرس/قاعة)
                if (!empty($request->edits)) {
                    foreach ($request->edits as $edit) {
                        $gene = Gene::where('gene_id', $edit['gene_id'])
                            ->where('chromosome_id', $chromosome->chromosome_id)
                            ->firstOrFail();

                        $field = $edit['field'] . '_id'; // instructor_id, room_id
                        $oldValue = $gene->{$field};

                        // حفظ التعديل في جدول التغييرات
                        DB::table('gene_edits')->insert([
                            'gene_id' => $edit['gene_id'],
                            'field' => $edit['field'],
                            'old_value_id' => $oldValue,
                            'new_value_id' => $edit['new_value_id'],
                            'changed_by' => $currentUserId,
                            'changed_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // تحديث الجين
                        $gene->{$field} = $edit['new_value_id'];
                        $gene->save();
                    }
                }

                // معالجة تحركات البلوكات
                if (!empty($request->moves)) {
                    foreach ($request->moves as $move) {
                        $gene = Gene::where('gene_id', $move['gene_id'])
                            ->where('chromosome_id', $chromosome->chromosome_id)
                            ->firstOrFail();

                        $oldTimeslots = $gene->timeslot_ids;

                        // حفظ تحديث الوقت في جدول التغييرات
                        DB::table('gene_edits')->insert([
                            'gene_id' => $move['gene_id'],
                            'field' => 'timeslots',
                            'old_value_id' => json_encode($oldTimeslots),
                            'new_value_id' => json_encode($move['new_timeslots']),
                            'changed_by' => $currentUserId,
                            'changed_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // تحديث التايم سلوتس
                        $gene->timeslot_ids = $move['new_timeslots'];
                        $gene->save();
                    }
                }

                // إعادة حساب عقوبات التعارض للكروموسوم
                $this->recalculateChromosomePenalties($chromosome);
            });

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ التعديلات بنجاح في قاعدة البيانات.',
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving edits: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل في حفظ التغييرات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إعادة حساب عقوبات التعارض بعد التعديلات
     */
    private function recalculateChromosomePenalties(Chromosome $chromosome)
    {
        try {
            // جلب جميع الجينات المحدثة
            $genes = $chromosome->genes()->with([
                'section.planSubject.subject',
                'instructor.user',
                'room.roomType',
            ])->get();

            // تحويل الجينات لفحص التعارضات
            $flatGenesForConflictCheck = collect();
            $allTimeslots = Timeslot::all()->keyBy('id');

            foreach ($genes as $geneBlock) {
                $geneBlock->setRelation('timeslot', collect($geneBlock->timeslot_ids)->map(fn($id) => $allTimeslots->get($id)));
                foreach ($geneBlock->timeslot_ids as $tsId) {
                    $newGene = clone $geneBlock;
                    $newGene->timeslot_id_single = $tsId;
                    $flatGenesForConflictCheck->push($newGene);
                }
            }

            // حساب التعارضات الجديدة
            $conflictChecker = new ConflictCheckerService($flatGenesForConflictCheck);
            $penalties = $conflictChecker->getConflicts();
            // $penalties = $conflictChecker->calculatePenalties();
            // $conflictChecker = new ConflictCheckerService($flatGenesForConflictCheck);
            // $conflicts = $conflictChecker->getConflicts();
            // $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

            // تحديث عقوبات الكروموسوم
            $chromosome->update([
                'student_conflict_penalty' => $penalties['student_conflict_penalty'] ?? 0,
                'teacher_conflict_penalty' => $penalties['teacher_conflict_penalty'] ?? 0,
                'room_conflict_penalty' => $penalties['room_conflict_penalty'] ?? 0,
                'capacity_conflict_penalty' => $penalties['capacity_conflict_penalty'] ?? 0,
                'room_type_conflict_penalty' => $penalties['room_type_conflict_penalty'] ?? 0,
                'teacher_eligibility_conflict_penalty' => $penalties['teacher_eligibility_conflict_penalty'] ?? 0,
                'penalty_value' => array_sum($penalties),
                'fitness_value' => $this->calculateFitnessFromPenalty(array_sum($penalties))
            ]);
        } catch (\Exception $e) {
            Log::error('Error recalculating penalties: ' . $e->getMessage());
        }
    }

    /**
     * حساب قيمة اللياقة من العقوبة
     */
    private function calculateFitnessFromPenalty($penaltyValue)
    {
        if ($penaltyValue <= 0) {
            return 1.0; // أفضل لياقة
        }

        return 1.0 / (1.0 + $penaltyValue);
    }

    /**
     * API للحصول على التعارضات الحالية لبلوك معين
     */
    public function checkBlockConflicts(Request $request)
    {
        $request->validate([
            'gene_id' => 'required|exists:genes,gene_id',
            'instructor_id' => 'nullable|exists:instructors,id',
            'room_id' => 'nullable|exists:rooms,id',
            'timeslot_ids' => 'required|array',
            'timeslot_ids.*' => 'exists:timeslots,id'
        ]);

        try {
            $geneId = $request->gene_id;
            $instructorId = $request->instructor_id;
            $roomId = $request->room_id;
            $timeslotIds = $request->timeslot_ids;

            $conflicts = [];

            // فحص تعارض المدرس
            if ($instructorId) {
                $instructorConflicts = Gene::where('instructor_id', $instructorId)
                    ->where('gene_id', '!=', $geneId)
                    ->get()
                    ->filter(function ($gene) use ($timeslotIds) {
                        return !empty(array_intersect($gene->timeslot_ids, $timeslotIds));
                    });

                foreach ($instructorConflicts as $conflict) {
                    $conflicts[] = [
                        'type' => 'instructor',
                        'description' => 'المدرس مشغول في نفس الوقت',
                        'conflicting_gene_id' => $conflict->gene_id
                    ];
                }
            }

            // فحص تعارض القاعة
            if ($roomId) {
                $roomConflicts = Gene::where('room_id', $roomId)
                    ->where('gene_id', '!=', $geneId)
                    ->get()
                    ->filter(function ($gene) use ($timeslotIds) {
                        return !empty(array_intersect($gene->timeslot_ids, $timeslotIds));
                    });

                foreach ($roomConflicts as $conflict) {
                    $conflicts[] = [
                        'type' => 'room',
                        'description' => 'القاعة محجوزة لمحاضرة أخرى',
                        'conflicting_gene_id' => $conflict->gene_id
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'conflicts' => $conflicts,
                'has_conflict' => count($conflicts) > 0
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في فحص التعارضات: ' . $e->getMessage()
            ], 500);
        }
    }
}
