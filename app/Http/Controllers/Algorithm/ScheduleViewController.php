<?php

namespace App\Http\Controllers\Algorithm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Population;
use App\Models\Chromosome;
use App\Models\Plan;
use App\Models\Instructor;
use App\Models\Room;
use Exception;

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
            $groupedSchedule = $expandedSchedule->groupBy(function($item) {
                return "{$item->plan_name}|{$item->plan_level}|{$item->plan_semester}|{$item->group_no}";
            });

            // Apply filters if provided
            if ($request->filled('plan_id') || $request->filled('level') ||
                $request->filled('semester') || $request->filled('group_no')) {

                $groupedSchedule = $groupedSchedule->filter(function($sessions, $key) use ($request) {
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
                $instructorSchedules = $instructorSchedules->filter(function($sessions, $instructorId) use ($request) {
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
                $roomSchedules = $roomSchedules->filter(function($sessions, $roomId) use ($request) {
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
}
