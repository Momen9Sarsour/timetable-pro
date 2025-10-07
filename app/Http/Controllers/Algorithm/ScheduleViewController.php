<?php

namespace App\Http\Controllers\Algorithm;

use Exception;
use App\Models\Plan;
use App\Models\Room;
use App\Models\Chromosome;
use App\Models\Instructor;
use App\Models\Population;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\GeneticAlgorithmServiceNew;
use App\Services\FitnessCalculatorServiceNew;

class ScheduleViewController extends Controller
{
    /**
     * Get best chromosome from latest population
     */
    private function getBestChromosome($populationId = null)
    {
        try {
            // إذا تم تحديد population_id
            if ($populationId) {
                $population = Population::find($populationId);

                if (!$population) {
                    throw new Exception("Population not found");
                }

                // الشرط الأول: أفضل كروموسوم محدد في الـ population
                if ($population->best_chromosome_id) {
                    return Chromosome::find($population->best_chromosome_id);
                }

                // الشرط الثاني: أقل عقوبة من هذا الـ population
                return Chromosome::where('population_id', $populationId)
                    ->orderBy('penalty_value', 'asc')
                    ->orderByDesc('fitness_value')
                    ->first();
            }

            // إذا لم يتم التحديد، نأخذ آخر population
            $latestPopulation = Population::latest('created_at')->first();

            if (!$latestPopulation) {
                return null;
            }

            // الشرط الأول: best_chromosome_id
            if ($latestPopulation->best_chromosome_id) {
                return Chromosome::find($latestPopulation->best_chromosome_id);
            }

            // الشرط الثاني: أقل عقوبة
            return Chromosome::where('population_id', $latestPopulation->population_id)
                ->orderBy('penalty_value', 'asc')
                ->orderByDesc('fitness_value')
                ->first();
        } catch (Exception $e) {
            Log::error("Error getting best chromosome: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get schedule data for a chromosome
     */
    private function getScheduleData($chromosomeId)
    {
        // ✅ الخطوة الأولى: جلب كل الـ genes مع timeslots
        $genesData = DB::table('genes as g')
            ->join('chromosomes as c', 'g.chromosome_id', '=', 'c.chromosome_id')
            ->join('timeslots as t', 'g.gene_id', '=', 't.gene_id')
            ->join('sections as s', 'g.section_id', '=', 's.id')
            ->join('plan_subjects as ps', 's.plan_subject_id', '=', 'ps.id')
            ->join('subjects as sub', 'ps.subject_id', '=', 'sub.id')
            ->join('instructors as i', 'g.instructor_id', '=', 'i.id')
            ->join('rooms as r', 'g.room_id', '=', 'r.id')
            ->join('plans as p', 'ps.plan_id', '=', 'p.id')
            ->where('c.chromosome_id', $chromosomeId)
            ->select(
                'g.gene_id',
                's.id as section_id',
                'p.plan_name',
                'ps.plan_id',
                'ps.plan_level',
                'ps.plan_semester',
                'sub.subject_name',
                'sub.subject_no',
                'i.instructor_name',
                'i.id as instructor_id',
                'r.room_name',
                'r.room_no',
                'r.id as room_id',
                't.timeslot_day',
                't.start_time',
                't.end_time',
                't.duration_hours',
                's.activity_type'
            )
            ->orderBy('t.timeslot_day')
            ->orderBy('t.start_time')
            ->get();

        // ✅ الخطوة الثانية: لكل section، نجيب الـ group_numbers من plan_groups
        $sectionIds = $genesData->pluck('section_id')->unique();

        $groupsMap = DB::table('plan_groups')
            ->whereIn('section_id', $sectionIds)
            ->select('section_id', DB::raw('GROUP_CONCAT(DISTINCT group_no ORDER BY group_no SEPARATOR ",") as group_numbers'))
            ->groupBy('section_id')
            ->pluck('group_numbers', 'section_id')
            ->toArray();

        // ✅ الخطوة الثالثة: نضيف group_numbers لكل session
        foreach ($genesData as $session) {
            $session->group_numbers = $groupsMap[$session->section_id] ?? null;
        }

        return $genesData;
    }

    /**
     * Show groups schedules
     */
    public function showGroupsSchedule(Request $request)
    {
        try {
            $populationId = $request->get('population_id');
            $bestChromosome = $this->getBestChromosome($populationId);

            if (!$bestChromosome) {
                return view('algorithm.new-system.schedules.groups')
                    ->with('error', 'No chromosome data found. Please generate a population first.');
            }

            $scheduleData = $this->getScheduleData($bestChromosome->chromosome_id);

            // Get filter options
            $plans = Plan::orderBy('plan_name')->get();
            $populations = Population::orderBy('created_at', 'desc')->take(10)->get();

            // ✅ الآن نفصل المواد حسب كل group_no
            $expandedSchedule = [];

            foreach ($scheduleData as $session) {
                // ✅ التعامل مع المواد التي ليس لها plan_groups
                if (empty($session->group_numbers)) {
                    // ⚠️ هذه المادة ليس لها مجموعات محددة في plan_groups
                    // نحتاج نعرف المجموعات الموجودة لهذه الخطة/المستوى/الفصل

                    // نجيب كل المجموعات الموجودة لنفس الـ context
                    $contextGroups = DB::table('plan_groups')
                        ->where('plan_id', $session->plan_id)
                        ->where('plan_level', $session->plan_level)
                        ->where('semester', $session->plan_semester)
                        ->distinct()
                        ->pluck('group_no')
                        ->sort()
                        ->values();

                    // إذا ما في مجموعات أصلاً، نتخطى
                    if ($contextGroups->isEmpty()) {
                        continue;
                    }

                    // نضيف المادة لكل المجموعات (لأنها مشتركة)
                    foreach ($contextGroups as $groupNo) {
                        $newSession = clone $session;
                        $newSession->group_no = $groupNo;
                        $expandedSchedule[] = $newSession;
                    }
                } else {
                    // فصل الـ group_numbers (مثال: "1,2,3")
                    $groupNumbers = explode(',', $session->group_numbers);

                    // إنشاء نسخة من الـ session لكل group
                    foreach ($groupNumbers as $groupNo) {
                        $newSession = clone $session;
                        $newSession->group_no = trim($groupNo);
                        $expandedSchedule[] = $newSession;
                    }
                }
            }

            // تحويل إلى Collection
            $expandedSchedule = collect($expandedSchedule);

            // Group schedule by plan/level/semester/group
            $groupedSchedule = $expandedSchedule->groupBy(function ($item) {
                return "{$item->plan_name}|{$item->plan_level}|{$item->plan_semester}|{$item->group_no}";
            });

            // Apply filters if provided
            if (
                $request->filled('plan_id') || $request->filled('level') ||
                $request->filled('semester') || $request->filled('group_no')
            ) {

                $groupedSchedule = $groupedSchedule->filter(function ($sessions, $key) use ($request) {
                    $first = $sessions->first();

                    if ($request->filled('plan_id')) {
                        $plan = Plan::find($request->plan_id);
                        if ($plan && $first->plan_name != $plan->plan_name) {
                            return false;
                        }
                    }

                    if ($request->filled('level') && $first->plan_level != $request->level) {
                        return false;
                    }

                    if ($request->filled('semester') && $first->plan_semester != $request->semester) {
                        return false;
                    }

                    if ($request->filled('group_no') && $first->group_no != $request->group_no) {
                        return false;
                    }

                    return true;
                });
            }

            return view('algorithm.new-system.schedules.groups', compact(
                'groupedSchedule',
                'bestChromosome',
                'plans',
                'populations'
            ));
        } catch (Exception $e) {
            Log::error("Error loading groups schedule: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading groups schedule: ' . $e->getMessage());
        }
    }

    /**
     * Show instructors schedules
     */
    public function showInstructorsSchedule(Request $request)
    {
        try {
            $populationId = $request->get('population_id');
            $bestChromosome = $this->getBestChromosome($populationId);

            if (!$bestChromosome) {
                return view('algorithm.new-system.schedules.instructors')
                    ->with('error', 'No chromosome data found. Please generate a population first.');
            }

            $scheduleData = $this->getScheduleData($bestChromosome->chromosome_id);

            // Get filter options
            $instructors = Instructor::orderBy('instructor_name')->get();
            $populations = Population::orderBy('created_at', 'desc')->take(10)->get();

            // Group by instructor
            $instructorSchedules = $scheduleData->groupBy('instructor_id');

            // Apply filter if instructor selected
            if ($request->filled('instructor_id')) {
                $instructorSchedules = $instructorSchedules->filter(function ($sessions, $instructorId) use ($request) {
                    return $instructorId == $request->instructor_id;
                });
            }

            return view('algorithm.new-system.schedules.instructors', compact(
                'instructorSchedules',
                'bestChromosome',
                'instructors',
                'populations'
            ));
        } catch (Exception $e) {
            Log::error("Error loading instructors schedule: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading instructors schedule: ' . $e->getMessage());
        }
    }

    /**
     * Show rooms schedules
     */
    public function showRoomsSchedule(Request $request)
    {
        try {
            $populationId = $request->get('population_id');
            $bestChromosome = $this->getBestChromosome($populationId);

            if (!$bestChromosome) {
                return view('algorithm.new-system.schedules.rooms')
                    ->with('error', 'No chromosome data found. Please generate a population first.');
            }

            $scheduleData = $this->getScheduleData($bestChromosome->chromosome_id);

            // Get filter options
            $rooms = Room::orderBy('room_name')->get();
            $populations = Population::orderBy('created_at', 'desc')->take(10)->get();

            // Group by room
            $roomSchedules = $scheduleData->groupBy('room_id');

            // Apply filter if room selected
            if ($request->filled('room_id')) {
                $roomSchedules = $roomSchedules->filter(function ($sessions, $roomId) use ($request) {
                    return $roomId == $request->room_id;
                });
            }

            return view('algorithm.new-system.schedules.rooms', compact(
                'roomSchedules',
                'bestChromosome',
                'rooms',
                'populations'
            ));
        } catch (Exception $e) {
            Log::error("Error loading rooms schedule: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading rooms schedule: ' . $e->getMessage());
        }
    }

    /**
     * Save schedule edits and recalculate conflicts
     */
    public function saveEdits(Request $request)
    {
        try {
            DB::beginTransaction();

            $chromosomeId = $request->chromosome_id;
            $changes = $request->changes ?? [];

            if (empty($changes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No changes to save'
                ], 400);
            }

            $editCount = 0;
            $moveCount = 0;

            foreach ($changes as $change) {
                if ($change['type'] === 'edit') {
                    // ✅ إضافة logging
                    Log::info("Processing edit change:", $change);

                    // تحديث القاعة أو المدرس
                    $field = $change['field'] === 'instructor' ? 'instructor_id' : 'room_id';

                    // ✅ التأكد من وجود القيمة
                    if (!isset($change['new_value_id'])) {
                        Log::warning("Missing new_value_id in change:", $change);
                        continue;
                    }

                    $updated = DB::table('genes')
                        ->where('gene_id', $change['gene_id'])
                        ->update([
                            $field => $change['new_value_id'],
                            'updated_at' => now()
                        ]);

                    Log::info("Updated {$field} for gene {$change['gene_id']}: {$updated} rows affected");

                    $editCount++;
                } else if ($change['type'] === 'move') {
                    // جلب الـ gene الحالي لمعرفة اليوم
                    $currentGene = DB::table('genes')
                        ->join('timeslots', 'genes.gene_id', '=', 'timeslots.gene_id')
                        ->where('genes.gene_id', $change['gene_id'])
                        ->select('timeslots.timeslot_day')
                        ->first();

                    if ($currentGene) {
                        $day = $currentGene->timeslot_day;

                        // حذف الـ timeslots القديمة
                        DB::table('timeslots')
                            ->where('gene_id', $change['gene_id'])
                            ->delete();

                        // إنشاء timeslots جديدة
                        $this->createNewTimeslots(
                            $change['gene_id'],
                            $change['new_start'],
                            $change['new_end'],
                            $day
                        );

                        // جلب الـ timeslot IDs الجديدة
                        $newTimeslotIds = DB::table('timeslots')
                            ->where('gene_id', $change['gene_id'])
                            ->orderBy('session_no')
                            ->pluck('timeslot_id')
                            ->toArray();

                        // تحديث timeslot_ids في الـ gene
                        DB::table('genes')
                            ->where('gene_id', $change['gene_id'])
                            ->update([
                                'timeslot_ids' => json_encode($newTimeslotIds),
                                'updated_at' => now()
                            ]);

                        $moveCount++;
                    }
                }
            }

            // ✅ إعادة حساب التعارضات
            $conflicts = $this->recalculateConflicts($chromosomeId);

            // ✅ تحديث حقول الـ chromosome
            $this->updateChromosomePenalties($chromosomeId, $conflicts);

            // ✅ جلب الـ chromosome المحدث
            $updatedChromosome = DB::table('chromosomes')
                ->where('chromosome_id', $chromosomeId)
                ->first();

            DB::commit();

            Log::info("Schedule edits saved for chromosome {$chromosomeId}: {$editCount} edits, {$moveCount} moves");

            return response()->json([
                'success' => true,
                'message' => 'Changes saved successfully',
                'edits_saved' => $editCount,
                'moves_saved' => $moveCount,
                'updated_chromosome' => $updatedChromosome,
                'conflicts' => $conflicts
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error saving schedule edits: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error saving changes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new timeslot records
     */
    private function createNewTimeslots($geneId, $startMinutes, $endMinutes, $day)
    {
        // حساب عدد الـ slots المطلوبة (كل slot = 30 دقيقة)
        $duration = $endMinutes - $startMinutes;
        $slotsCount = ceil($duration / 30);

        $currentMinutes = $startMinutes;

        for ($i = 0; $i < $slotsCount; $i++) {
            $startHour = floor($currentMinutes / 60) + 8;
            $startMinute = $currentMinutes % 60;

            $nextMinutes = $currentMinutes + 30;
            $endHour = floor($nextMinutes / 60) + 8;
            $endMinute = $nextMinutes % 60;

            DB::table('timeslots')->insert([
                'gene_id' => $geneId,
                'timeslot_day' => $day,
                'start_time' => sprintf('%02d:%02d:00', $startHour, $startMinute),
                'end_time' => sprintf('%02d:%02d:00', $endHour, $endMinute),
                'duration_hours' => 0.5, // 30 دقيقة
                'session_no' => $i + 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $currentMinutes += 30;
        }
    }

    /**
     * Recalculate all conflicts for a chromosome
     */
    private function recalculateConflicts($chromosomeId)
    {
        $fitnessCalculator = new FitnessCalculatorServiceNew();
        $gaService = new GeneticAlgorithmServiceNew();

        // تحميل الكروموسوم
        $population = $gaService->loadPopulation(
            DB::table('chromosomes')->where('chromosome_id', $chromosomeId)->value('population_id')
        );

        $chromosome = collect($population)->firstWhere('chromosome_id', $chromosomeId);

        if (!$chromosome) {
            return [
                'student_conflicts' => 0,
                'teacher_conflicts' => 0,
                'room_conflicts' => 0,
                'capacity_conflicts' => 0,
                'room_type_conflicts' => 0,
                'teacher_eligibility_conflicts' => 0,
                'total_penalty' => 0
            ];
        }

        // حساب الـ fitness والتعارضات
        $result = $fitnessCalculator->calculateFitness($chromosome);

        return [
            'student_conflicts' => $result['student_conflict_penalty'] ?? 0,
            'teacher_conflicts' => $result['teacher_conflict_penalty'] ?? 0,
            'room_conflicts' => $result['room_conflict_penalty'] ?? 0,
            'capacity_conflicts' => $result['capacity_conflict_penalty'] ?? 0,
            'room_type_conflicts' => 0, // إذا كان موجود في الـ result
            'teacher_eligibility_conflicts' => 0, // إذا كان موجود في الـ result
            'total_penalty' => $result['penalty_value'] ?? 0
        ];
    }

    /**
     * Update chromosome penalty fields
     */
    private function updateChromosomePenalties($chromosomeId, $conflicts)
    {
        DB::table('chromosomes')
            ->where('chromosome_id', $chromosomeId)
            ->update([
                'student_conflict_penalty' => $conflicts['student_conflicts'],
                'teacher_conflict_penalty' => $conflicts['teacher_conflicts'],
                'room_conflict_penalty' => $conflicts['room_conflicts'],
                'capacity_conflict_penalty' => $conflicts['capacity_conflicts'],
                'room_type_conflict_penalty' => $conflicts['room_type_conflicts'],
                'teacher_eligibility_conflict_penalty' => $conflicts['teacher_eligibility_conflicts'],
                'penalty_value' => $conflicts['total_penalty'],
                'fitness_value' => 1 / (1 + $conflicts['total_penalty']),
                'updated_at' => now()
            ]);
    }
}
