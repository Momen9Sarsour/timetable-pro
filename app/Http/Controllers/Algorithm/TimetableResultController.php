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
    public function index()
    {
        try {
            $latestSuccessfulRun = Population::where('status', 'completed')
                ->orderBy('end_time', 'desc')
                ->first();

            $topChromosomes = collect();

            if ($latestSuccessfulRun) {
                $topChromosomes = $latestSuccessfulRun->chromosomes()
                    ->orderBy('penalty_value', 'asc')
                    ->orderBy('chromosome_id', 'desc')
                    ->orderBy('penalty_value', 'asc')
                    ->orderBy('chromosome_id', 'desc')
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
     * عرض تفاصيل الكروموسوم مع تجميع المجموعات المبسط
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

            // **تبسيط تجميع المجموعات بناءً على البيانات الموجودة في الجينات**
            $scheduleByGroup = $this->buildSimplifiedStudentGroups($genes);
            $totalColumnsOverall = $allTimeslots->count();

            // **فحص التعارضات المحسن**
            $conflictChecker = new ConflictCheckerService($genes);
            $conflicts = $conflictChecker->getConflicts();
            $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

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
                'allInstructors'
            ));

        } catch (Exception $e) {
            Log::error("Error displaying timetable: " . $e->getMessage());
            return redirect()
                ->route('algorithm-control.timetable.results.index')
                ->with('error', 'Could not load the timetable: ' . $e->getMessage());
        }
    }

    /**
     * تجميع المجموعات المبسط بناءً على البيانات الموجودة في الجينات
     */
    private function buildSimplifiedStudentGroups(Collection $genes): array
    {
        $scheduleByGroup = [];

        // تجميع الجينات حسب السياق (نفس الخطة والمستوى والسنة)
        $genesByContext = $genes->groupBy(function ($gene) {
            $section = $gene->section;
            $planSubject = $section->planSubject ?? null;

            if (!$planSubject) return 'unknown';

            return implode('-', [
                $planSubject->plan_id,
                $planSubject->plan_level,
                $section->academic_year,
                $section->branch ?? 'default'
            ]);
        });

        foreach ($genesByContext as $contextKey => $contextGenes) {
            if ($contextKey === 'unknown') continue;

            $firstGene = $contextGenes->first();
            $section = $firstGene->section;
            $planSubject = $section->planSubject;

            if (!$planSubject || !$planSubject->plan) continue;

            // جمع كل المجموعات الموجودة في هذا السياق
            $allGroupsInContext = collect();
            foreach ($contextGenes as $gene) {
                $studentGroupIds = is_string($gene->student_group_id)
                    ? json_decode($gene->student_group_id, true)
                    : ($gene->student_group_id ?? []);

                foreach ($studentGroupIds as $groupId) {
                    $allGroupsInContext->push($groupId);
                }
            }

            $uniqueGroups = $allGroupsInContext->unique()->sort();

            // إنشاء مجموعة لكل رقم مجموعة
            foreach ($uniqueGroups as $groupNumber) {
                $groupId = $contextKey . '-group-' . $groupNumber;
                $groupName = $planSubject->plan->plan_no . " " . $planSubject->plan_level . " | Group " . $groupNumber;

                $scheduleByGroup[$groupId] = [
                    'name' => $groupName,
                    'blocks' => []
                ];

                // إضافة الجينات التي تحتوي على هذه المجموعة
                foreach ($contextGenes as $gene) {
                    $studentGroupIds = is_string($gene->student_group_id)
                        ? json_decode($gene->student_group_id, true)
                        : ($gene->student_group_id ?? []);

                    if (in_array($groupNumber, $studentGroupIds)) {
                        $scheduleByGroup[$groupId]['blocks'][] = $gene;
                    }
                }
            }
        }

        return $scheduleByGroup;
    }

    /**
     * حفظ التعديلات مع إعادة حساب التعارضات
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
            $updatedGenes = [];
            $movesSaved = 0;
            $editsSaved = 0;

            DB::transaction(function () use ($request, &$updatedGenes, &$movesSaved, &$editsSaved) {

                // حفظ تعديلات الحقول
                if (!empty($request->edits)) {
                    foreach ($request->edits as $edit) {
                        $gene = Gene::findOrFail($edit['gene_id']);
                        $field = $edit['field'] . '_id';

                        $gene->{$field} = $edit['new_value_id'];
                        $gene->save();

                        $updatedGenes[] = $gene->fresh()->load(['instructor.user', 'room.roomType']);
                        $editsSaved++;
                    }
                }

                // حفظ تحركات البلوكات
                if (!empty($request->moves)) {
                    foreach ($request->moves as $move) {
                        $gene = Gene::findOrFail($move['gene_id']);
                        $gene->timeslot_ids = $move['new_timeslot_ids'];
                        $gene->save();

                        $updatedGenes[] = $gene->fresh()->load(['instructor.user', 'room.roomType']);
                        $movesSaved++;
                    }
                }

                // إعادة حساب العقوبات والتعارضات
                if (!empty($updatedGenes)) {
                    $this->recalculateChromosomeFitness($request->chromosome_id);
                }
            });

            // جلب التعارضات الجديدة بعد التحديث
            $chromosome = Chromosome::findOrFail($request->chromosome_id);
            $allGenes = $chromosome->genes()->with([
                'section.planSubject.subject',
                'room.roomType',
                'instructor.subjects'
            ])->get();

            $conflictChecker = new ConflictCheckerService($allGenes);
            $newConflicts = $conflictChecker->getConflicts();
            $conflictingGeneIds = $conflictChecker->getConflictingGeneIds();

            return response()->json([
                'success' => true,
                'message' => "تم حفظ {$editsSaved} تعديل و {$movesSaved} حركة بنجاح",
                'updated_genes' => $updatedGenes,
                'edits_saved' => $editsSaved,
                'moves_saved' => $movesSaved,
                'new_conflicts' => $newConflicts,
                'conflicting_gene_ids' => $conflictingGeneIds,
                'updated_chromosome' => [
                    'penalty_value' => $chromosome->penalty_value,
                    'fitness_value' => $chromosome->fitness_value,
                    'student_conflict_penalty' => $chromosome->student_conflict_penalty,
                    'teacher_conflict_penalty' => $chromosome->teacher_conflict_penalty,
                    'room_conflict_penalty' => $chromosome->room_conflict_penalty,
                    'capacity_conflict_penalty' => $chromosome->capacity_conflict_penalty,
                    'room_type_conflict_penalty' => $chromosome->room_type_conflict_penalty,
                    'teacher_eligibility_conflict_penalty' => $chromosome->teacher_eligibility_conflict_penalty,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('فشل حفظ التعديلات: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل الحفظ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إعادة حساب العقوبات للكروموسوم
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

        Log::info("Chromosome {$chromosomeId} recalculated - Total penalty: {$totalPenalty}");
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
                continue;
                continue;
            }

            if (!$instructor->subjects || !$instructor->subjects->contains($subject->id)) {
                $penalty += 1;
            }
        }
        return $penalty;
    }
}

/**
 * خدمة فحص التعارضات المحسنة
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
        $this->checkResourceConflicts();
        $this->checkQualityConflicts();
    }

    private function checkResourceConflicts(): void
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
            if ($gene->section && $gene->room && $gene->section->student_count > $gene->room->room_size) {
                $this->addConflict(
                    'Room Capacity',
                    "عدد الطلاب يتجاوز سعة القاعة",
                    [$gene->gene_id],
                    '#ffc107'
                );
            }

            if ($gene->section && $gene->room && $gene->room->roomType && $gene->section->planSubject && $gene->section->planSubject->subject) {
                $activityType = $gene->section->activity_type;
                $roomTypeName = strtolower($gene->room->roomType->room_type_name);
                if ($activityType == 'Practical' && !Str::contains($roomTypeName, ['lab', 'مختبر', 'workshop', 'ورشة'])) {
                    $this->addConflict(
                        'Room Type',
                        "مادة عملية في قاعة غير مخبرية",
                        [$gene->gene_id],
                        '#ffc107'
                    );
                }
            }

            if ($gene->instructor && $gene->section && $gene->section->planSubject && $gene->section->planSubject->subject) {
                $instructor = $gene->instructor;
                $section = $gene->section;
                $subject = $gene->section->planSubject->subject;

                if (!($section->instructor && $section->instructor->id == $instructor->id) &&
                    !($instructor->subjects && $instructor->subjects->contains($subject->id))) {
                    $this->addConflict(
                        'Instructor Qualification',
                        "المدرس غير مؤهل لتدريس المادة",
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
            'Instructor Conflict' => '#dc3545',
            'Room Conflict' => '#fd7e14',
            'Student Conflict' => '#e83e8c',
            'Room Capacity', 'Room Type' => '#ffc107',
            'Instructor Qualification' => '#6f42c1',
            default => '#0d6efd' // اللون الأزرق للبلوكات بدون تعارض
        };
    }
}

