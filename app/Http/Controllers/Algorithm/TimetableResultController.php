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

    /**
     * عرض تفاصيل الكروموسوم مع تحسين استهلاك الذاكرة
     */
    public function show(Chromosome $chromosome)
    {
        try {
            // تحسين 1: تحميل البيانات الأساسية فقط
            $chromosome->load('population');

            // تحسين 2: استخدام chunk loading للجينات الكبيرة
            $geneCount = $chromosome->genes()->count();
            
            if ($geneCount > 1000) {
                return $this->showLargeChromosome($chromosome);
            }

            // تحسين 3: تحديد الحقول المطلوبة فقط
            $genes = $chromosome->genes()
                ->select([
                    'gene_id',
                    'chromosome_id',
                    'section_id',
                    'instructor_id',
                    'room_id',
                    'timeslot_ids',
                    'lecture_unique_id',
                    'student_group_id'
                ])
                ->with([
                    'section:id,student_count,activity_type,academic_year,branch,plan_subject_id,section_number,instructor_id',
                    'section.planSubject:id,subject_id,plan_id,plan_level,plan_semester',
                    'section.planSubject.subject:id,subject_name',
                    'section.planSubject.plan:id,plan_no',
                    'instructor:id,user_id',
                    'instructor.user:id,name',
                    'room:id,room_name,room_size,room_type_id',
                    'room.roomType:id,room_type_name',
                ])
                ->get();

            if ($genes->isEmpty()) {
                throw new Exception("No schedule data found for this chromosome.");
            }

            // تحسين 4: تجنب تحميل كل البيانات مرة واحدة
            $daysOfWeek = ['1', '2', '3', '4', '5', '6', '7'];
            $allTimeslots = Timeslot::select('timeslot_id', 'day', 'start_time', 'end_time')
                ->orderByRaw("FIELD(day, '" . implode("','", $daysOfWeek) . "')")
                ->orderBy('start_time')
                ->get();

            if ($allTimeslots->isEmpty()) {
                throw new Exception("No timeslots defined in the system.");
            }

            $timeslotsByDay = $allTimeslots->groupBy('day');
            $slotPositions = $this->buildSlotPositions($timeslotsByDay);

            // تحسين 5: تحميل الأقسام المطلوبة فقط
            $sectionIds = $genes->pluck('section_id')->unique();
            $sections = Section::whereIn('id', $sectionIds)
                ->select('id', 'activity_type', 'section_number', 'plan_subject_id', 'academic_year', 'branch')
                ->with([
                    'planSubject:id,plan_id,plan_level,plan_semester',
                    'planSubject.plan:id,plan_no'
                ])
                ->get();

            $studentGroupMap = $this->buildStudentGroupMap($sections);
            $scheduleByGroup = $this->buildScheduleByGroup($studentGroupMap, $genes);

            // تحسين 6: فحص التعارضات بكفاءة أكبر
            $conflictChecker = new OptimizedConflictCheckerService($genes);
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

            // تحسين 7: تحميل البيانات المرجعية عند الحاجة فقط
            $allRooms = $this->getOptimizedRoomData($genes);
            $allInstructors = $this->getOptimizedInstructorData($genes);
            $timeSlotUsage = $this->buildTimeSlotUsage($genes);
            $totalColumnsOverall = $allTimeslots->count();

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
     * معالجة الكروموسومات الكبيرة جداً
     */
    private function showLargeChromosome(Chromosome $chromosome)
    {
        // للكروموسومات الضخمة، استخدم pagination أو lazy loading
        return view('dashboard.algorithm.timetable-result.show-large', [
            'chromosome' => $chromosome,
            'message' => 'This schedule is too large. Please use the export function or view specific sections.'
        ]);
    }

    /**
     * بناء خريطة مواضع الفترات الزمنية
     */
    private function buildSlotPositions($timeslotsByDay): array
    {
        $slotPositions = [];
        $dayOffset = 0;
        
        foreach ($timeslotsByDay as $day => $daySlots) {
            foreach ($daySlots->values() as $index => $slot) {
                $slotPositions[$slot->id] = $dayOffset + $index;
            }
            $dayOffset += $daySlots->count();
        }
        
        return $slotPositions;
    }

    /**
     * بناء الجدول حسب المجموعات
     */
    private function buildScheduleByGroup($studentGroupMap, $genes): array
    {
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
        
        return $scheduleByGroup;
    }

    /**
     * الحصول على بيانات القاعات المحسنة
     */
    private function getOptimizedRoomData($genes): array
    {
        $roomIds = $genes->pluck('room_id')->unique();
        
        return Room::whereIn('id', $roomIds)
            ->select('id', 'room_name', 'room_size', 'room_type_id')
            ->with('roomType:id,room_type_name')
            ->get()
            ->map(function ($room) {
                return [
                    'id' => $room->id,
                    'name' => $room->room_name,
                    'type' => optional($room->roomType)->room_type_name ?? '',
                    'size' => $room->room_size,
                ];
            })
            ->toArray();
    }

    /**
     * الحصول على بيانات المدرسين المحسنة
     */
    private function getOptimizedInstructorData($genes): array
    {
        $instructorIds = $genes->pluck('instructor_id')->unique();
        
        return Instructor::whereIn('id', $instructorIds)
            ->select('id', 'user_id')
            ->with([
                'user:id,name',
                'subjects:id'
            ])
            ->get()
            ->map(function ($instructor) {
                return [
                    'id' => $instructor->id,
                    'name' => $instructor->user->name ?? 'Unknown',
                    'subject_ids' => $instructor->subjects->pluck('id')->toArray(),
                ];
            })
            ->toArray();
    }

    /**
     * بناء استخدام الفترات الزمنية
     */
    private function buildTimeSlotUsage($genes): array
    {
        $timeSlotUsage = [];
        
        foreach ($genes as $gene) {
            $timeslotIds = is_string($gene->timeslot_ids) 
                ? json_decode($gene->timeslot_ids, true) 
                : ($gene->timeslot_ids ?? []);
                
            foreach ($timeslotIds as $tsId) {
                if (!isset($timeSlotUsage[$tsId])) {
                    $timeSlotUsage[$tsId] = [
                        'rooms' => [],
                        'instructors' => []
                    ];
                }
                $timeSlotUsage[$tsId]['rooms'][] = $gene->room_id;
                $timeSlotUsage[$tsId]['instructors'][] = $gene->instructor_id;
            }
        }
        
        return $timeSlotUsage;
    }

    /**
     * حفظ التعديلات مع تحسين الأداء
     */
    public function saveEdits(Request $request)
    {
        $request->validate([
            'chromosome_id' => 'required|exists:chromosomes,chromosome_id',
            'edits' => 'array|max:100', // حد أقصى للتعديلات
            'edits.*.gene_id' => 'required|exists:genes,gene_id',
            'edits.*.field' => 'required|in:instructor,room',
            'edits.*.new_value_id' => 'required|integer',
            'moves' => 'array|max:50', // حد أقصى للحركات
            'moves.*.gene_id' => 'required|exists:genes,gene_id',
            'moves.*.new_timeslot_ids' => 'required|array',
            'moves.*.new_timeslot_ids.*' => 'integer|exists:timeslots,id',
        ]);

        try {
            $chromosome = Chromosome::findOrFail($request->chromosome_id);
            $updatedGenes = [];
            $movesSaved = 0;
            $editsSaved = 0;
            
            $oldPenalties = $this->getCurrentPenalties($chromosome);
            
            DB::transaction(function () use ($request, &$updatedGenes, &$movesSaved, &$editsSaved, $chromosome) {
                
                // معالجة التعديلات على دفعات
                if (!empty($request->edits)) {
                    $this->processBatchEdits($request->edits, $updatedGenes, $editsSaved);
                }

                // معالجة الحركات على دفعات
                if (!empty($request->moves)) {
                    $this->processBatchMoves($request->moves, $updatedGenes, $movesSaved);
                }

                // إعادة حساب التعارضات
                if (!empty($updatedGenes)) {
                    $this->recalculateAllConflicts($chromosome);
                }
            });

            $chromosome->refresh();
            $newPenalties = $this->getCurrentPenalties($chromosome);
            
            $penaltyChange = $newPenalties['total_penalty'] - $oldPenalties['total_penalty'];
            $improvementText = $this->getImprovementText($penaltyChange);

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
     * معالجة التعديلات على دفعات
     */
    private function processBatchEdits($edits, &$updatedGenes, &$editsSaved): void
    {
        $geneIds = collect($edits)->pluck('gene_id')->unique();
        $genes = Gene::whereIn('gene_id', $geneIds)->get()->keyBy('gene_id');
        
        foreach ($edits as $edit) {
            if (!isset($genes[$edit['gene_id']])) continue;
            
            $gene = $genes[$edit['gene_id']];
            $field = $edit['field'] . '_id';
            
            $gene->{$field} = $edit['new_value_id'];
            $gene->save();
            
            $updatedGenes[] = $gene->fresh()->load([
                'instructor.user:id,name',
                'room.roomType:id,room_type_name',
                'section.planSubject.subject:id,subject_name'
            ]);
            $editsSaved++;
        }
    }

    /**
     * معالجة الحركات على دفعات
     */
    private function processBatchMoves($moves, &$updatedGenes, &$movesSaved): void
    {
        $geneIds = collect($moves)->pluck('gene_id')->unique();
        $genes = Gene::whereIn('gene_id', $geneIds)->get()->keyBy('gene_id');
        
        foreach ($moves as $move) {
            if (!isset($genes[$move['gene_id']])) continue;
            
            $gene = $genes[$move['gene_id']];
            $gene->timeslot_ids = $move['new_timeslot_ids'];
            $gene->save();
            
            $updatedGenes[] = $gene->fresh()->load([
                'instructor.user:id,name',
                'room.roomType:id,room_type_name',
                'section.planSubject.subject:id,subject_name'
            ]);
            $movesSaved++;
        }
    }

    /**
     * الحصول على العقوبات الحالية
     */
    private function getCurrentPenalties(Chromosome $chromosome): array
    {
        return [
            'total_penalty' => $chromosome->penalty_value ?? 0,
            'student_conflicts' => $chromosome->student_conflict_penalty ?? 0,
            'teacher_conflicts' => $chromosome->teacher_conflict_penalty ?? 0,
            'room_conflicts' => $chromosome->room_conflict_penalty ?? 0,
            'capacity_conflicts' => $chromosome->capacity_conflict_penalty ?? 0,
            'room_type_conflicts' => $chromosome->room_type_conflict_penalty ?? 0,
            'teacher_eligibility_conflicts' => $chromosome->teacher_eligibility_conflict_penalty ?? 0,
        ];
    }

    /**
     * الحصول على نص التحسن
     */
    private function getImprovementText($penaltyChange): string
    {
        if ($penaltyChange < 0) {
            return "تحسن بـ " . abs($penaltyChange) . " عقوبة";
        } elseif ($penaltyChange > 0) {
            return "زاد بـ " . $penaltyChange . " عقوبة";
        } else {
            return "لا تغيير في العقوبات";
        }
    }

    /**
     * إعادة حساب شاملة محسنة للتعارضات
     */
    private function recalculateAllConflicts(Chromosome $chromosome): void
    {
        // استخدام chunk loading للجينات الكبيرة
        $geneCount = $chromosome->genes()->count();
        
        if ($geneCount > 500) {
            $this->recalculateConflictsInChunks($chromosome);
            return;
        }

        // المعالجة العادية للكروموسومات الصغيرة
        $genes = $chromosome->genes()
            ->select(['gene_id', 'section_id', 'instructor_id', 'room_id', 'timeslot_ids', 'lecture_unique_id', 'student_group_id'])
            ->with([
                'section:id,student_count,activity_type,instructor_id',
                'section.planSubject:id,subject_id',
                'section.planSubject.subject:id,subject_name',
                'room:id,room_size,room_type_id',
                'room.roomType:id,room_type_name',
                'instructor:id',
                'instructor.subjects:id',
            ])
            ->get();

        if ($genes->isEmpty()) {
            Log::warning("No genes found for chromosome {$chromosome->chromosome_id}");
            return;
        }

        $penalties = $this->calculateAllPenalties($genes);
        $totalPenalty = array_sum($penalties);
        $fitnessValue = 1 / (1 + $totalPenalty);

        $chromosome->update(array_merge($penalties, [
            'penalty_value' => $totalPenalty,
            'fitness_value' => $fitnessValue,
        ]));

        Log::info("Chromosome {$chromosome->chromosome_id} recalculated: Total Penalty: {$totalPenalty}");
    }

    /**
     * إعادة حساب التعارضات على دفعات للكروموسومات الكبيرة
     */
    private function recalculateConflictsInChunks(Chromosome $chromosome): void
    {
        $penalties = [
            'student_conflict_penalty' => 0,
            'teacher_conflict_penalty' => 0,
            'room_conflict_penalty' => 0,
            'capacity_conflict_penalty' => 0,
            'room_type_conflict_penalty' => 0,
            'teacher_eligibility_conflict_penalty' => 0,
        ];

        $usageMap = [];
        
        // معالجة الجينات على دفعات
        $chromosome->genes()
            ->select(['gene_id', 'section_id', 'instructor_id', 'room_id', 'timeslot_ids', 'lecture_unique_id', 'student_group_id'])
            ->with([
                'section:id,student_count,activity_type,instructor_id',
                'section.planSubject:id,subject_id',
                'room:id,room_size,room_type_id',
                'instructor:id',
            ])
            ->chunk(100, function ($genes) use (&$penalties, &$usageMap) {
                // حساب العقوبات لكل دفعة
                $this->addChunkPenalties($genes, $penalties, $usageMap);
            });

        $totalPenalty = array_sum($penalties);
        $fitnessValue = 1 / (1 + $totalPenalty);

        $chromosome->update(array_merge($penalties, [
            'penalty_value' => $totalPenalty,
            'fitness_value' => $fitnessValue,
        ]));
    }

    /**
     * إضافة العقوبات للدفعة الحالية
     */
    private function addChunkPenalties($genes, &$penalties, &$usageMap): void
    {
        foreach ($genes as $gene) {
            $timeslotIds = is_string($gene->timeslot_ids) 
                ? json_decode($gene->timeslot_ids, true) 
                : ($gene->timeslot_ids ?? []);

            // فحص تعارضات المدرسين والقاعات
            foreach ($timeslotIds as $timeslotId) {
                if (isset($usageMap['instructors'][$gene->instructor_id][$timeslotId])) {
                    $penalties['teacher_conflict_penalty']++;
                }
                $usageMap['instructors'][$gene->instructor_id][$timeslotId] = true;

                if (isset($usageMap['rooms'][$gene->room_id][$timeslotId])) {
                    $penalties['room_conflict_penalty']++;
                }
                $usageMap['rooms'][$gene->room_id][$timeslotId] = true;
            }

            // فحص سعة القاعة
            if ($gene->section && $gene->room && $gene->section->student_count > $gene->room->room_size) {
                $penalties['capacity_conflict_penalty']++;
            }
        }
    }

    // باقي الدوال المساعدة تبقى كما هي مع تحسينات طفيفة...
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

    // باقي دوال حساب العقوبات تبقى كما هي...
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
                        $penalty++;
                    }
                    $sharedTheoryUsage[$contextKey][$timeslotId] = true;

                } else {
                    foreach ($studentGroupIds as $groupId) {
                        if (isset($studentGroupUsage[$groupId][$timeslotId])) {
                            $penalty++;
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
                    $penalty++;
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
                    $penalty++;
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
            if ($gene->section && $gene->room && $gene->section->student_count > $gene->room->room_size) {
                $penalty++;
            }
        }
        return $penalty;
    }

    private function calculateRoomTypeConflicts(Collection $genes): int
    {
        $penalty = 0;
        foreach ($genes->unique('lecture_unique_id') as $gene) {
            $isPracticalBlock = Str::contains($gene->lecture_unique_id, 'practical');
            $roomTypeName = strtolower(optional($gene->room->roomType)->room_type_name ?? '');
            $isPracticalRoom = Str::contains($roomTypeName, ['lab', 'مختبر', 'workshop', 'ورشة']);

            if ($isPracticalBlock && !$isPracticalRoom) {
                $penalty++;
            }
            if (!$isPracticalBlock && $isPracticalRoom) {
                $penalty++;
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
                $penalty++;
                continue;
            }

            if ($section->instructor && $section->instructor->id == $instructor->id) {
                continue;
            }

            if (!$instructor->subjects || !$instructor->subjects->contains($subject->id)) {
                $penalty++;
            }
        }
        return $penalty;
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
}

/**
 * خدمة فحص التعارضات المحسنة مع إدارة أفضل للذاكرة
 */
class OptimizedConflictCheckerService
{
    private Collection $genes;
    private array $conflicts = [];
    private array $conflictingGeneIds = [];

    public function __construct(Collection $genes)
    {
        $this->genes = $genes;
        $this->findConflicts();
    }

    private function findConflicts(): void
    {
        // استخدام الذاكرة بكفاءة - تحميل الفترات الزمنية مرة واحدة
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
                $instructorKey = "{$gene->instructor_id}_{$timeslotId}";
                if (isset($usageMap['instructors'][$instructorKey])) {
                    $conflictingGeneId = $usageMap['instructors'][$instructorKey];
                    $this->addConflict(
                        'Instructor Conflict',
                        "المدرس له محاضرتان متداخلتان",
                        [$gene->gene_id, $conflictingGeneId],
                        '#dc3545'
                    );
                }
                $usageMap['instructors'][$instructorKey] = $gene->gene_id;

                // فحص تعارضات القاعات
                $roomKey = "{$gene->room_id}_{$timeslotId}";
                if (isset($usageMap['rooms'][$roomKey])) {
                    $conflictingGeneId = $usageMap['rooms'][$roomKey];
                    $this->addConflict(
                        'Room Conflict',
                        "القاعة محجوزة لمحاضرتين متداخلتين",
                        [$gene->gene_id, $conflictingGeneId],
                        '#fd7e14'
                    );
                }
                $usageMap['rooms'][$roomKey] = $gene->gene_id;

                // فحص تعارضات الطلاب
                if ($isTheoreticalBlock) {
                    $contextKey = $this->getStudentContextKey($gene);
                    $theoryKey = "{$contextKey}_{$timeslotId}";
                    
                    if (isset($usageMap['theoretical_shared'][$theoryKey])) {
                        $conflictingGeneId = $usageMap['theoretical_shared'][$theoryKey];
                        $this->addConflict(
                            'Student Conflict',
                            "تعارض طلاب في المواد النظرية",
                            [$gene->gene_id, $conflictingGeneId],
                            '#e83e8c'
                        );
                    }
                    $usageMap['theoretical_shared'][$theoryKey] = $gene->gene_id;

                } else {
                    foreach ($studentGroupIds as $groupId) {
                        $groupKey = "{$groupId}_{$timeslotId}";
                        if (isset($usageMap['student_groups'][$groupKey])) {
                            $conflictingGeneId = $usageMap['student_groups'][$groupKey];
                            $this->addConflict(
                                'Student Conflict',
                                "تعارض مجموعة طلاب في المواد العملية",
                                [$gene->gene_id, $conflictingGeneId],
                                '#e83e8c'
                            );
                        }
                        $usageMap['student_groups'][$groupKey] = $gene->gene_id;
                    }
                }
            }
        }
    }

    private function getStudentContextKey($gene): string
    {
        $sectionInfo = $gene->section ?? null;
        if (!$sectionInfo) return 'unknown';
        
        $planSubject = optional($sectionInfo)->planSubject;
        return implode('-', [
            optional($planSubject)->plan_level ?? 0,
            optional($planSubject)->plan_semester ?? 0,
            $sectionInfo->academic_year ?? 0,
            $sectionInfo->branch ?? 'default'
        ]);
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
            if ($gene->section && $gene->room && $gene->room->roomType) {
                $activityType = $gene->section->activity_type;
                $roomTypeName = strtolower($gene->room->roomType->room_type_name);
                
                if ($activityType == 'Practical' && 
                    !Str::contains($roomTypeName, ['lab', 'مختبر', 'workshop', 'ورشة'])) {
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

                if (!($section->instructor && $section->instructor->id == $instructor->id) && 
                    !($instructor->subjects && $instructor->subjects->contains($subject->id))) {
                    $instructorName = optional($instructor->user)->name ?? 'Unknown';
                    $this->addConflict(
                        'Instructor Qualification',
                        "المدرس {$instructorName} غير مؤهل لتدريس {$subject->subject_name}",
                        [$gene->gene_id],
                        '#6f42c1'
                    );
                }
            }
        }
    }

    private function addConflict(string $type, string $description, array $geneIds, string $color): void
    {
        // تجنب التكرار
        $conflictKey = implode('-', array_merge([$type], sort($geneIds)));
        if (!isset($this->conflicts[$conflictKey])) {
            $this->conflicts[$conflictKey] = compact('type', 'description', 'geneIds', 'color');
            foreach ($geneIds as $id) {
                $this->conflictingGeneIds[$id] = $type;
            }
        }
    }

    public function getConflicts(): array
    {
        return array_values($this->conflicts);
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
            'Room Capacity' => '#fd7e14',
            'Room Type' => '#ffc107',
            'Instructor Qualification' => '#6f42c1',
            default => '#6c7d6dff'
        };
    }
}
