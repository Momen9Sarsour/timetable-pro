<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Room;
use Exception;

class FitnessCalculatorServiceNew
{
    private $roomCapacitiesMap = [];
    private $sectionLoadsMap = [];
    private $planGroupsSections = [];

    public function calculateFitness($chromosome)
    {
        try {
            $this->loadDataMaps();

            $instructorConflicts = $this->checkInstructorConflicts($chromosome);
            $roomConflicts = $this->checkRoomConflicts($chromosome);
            $roomCapacityViolations = $this->checkRoomCapacityViolations($chromosome);
            $planGroupConflicts = $this->checkPlanGroupConflicts($chromosome);

            $conflicts = $instructorConflicts + $roomConflicts + $roomCapacityViolations + $planGroupConflicts;
            $fitness = 1 / (1 + $conflicts);

            return $fitness;

        } catch (Exception $e) {
            Log::error("Error calculating fitness: " . $e->getMessage());
            return 0;
        }
    }

    private function loadDataMaps()
    {
        $this->roomCapacitiesMap = Room::pluck('room_size', 'id')->toArray();

        $this->sectionLoadsMap = DB::table('sections as s')
            ->join('plan_subjects as ps', 's.plan_subject_id', '=', 'ps.id')
            ->pluck('s.student_count', 's.id')
            ->toArray();

        $this->planGroupsSections = $this->getPlanGroupsSectionsList();
    }

    private function getPlanGroupsSectionsList()
    {
        return DB::table('plan_groups as pg')
            ->select(
                'pg.plan_id',
                'pg.plan_level',
                'pg.semester as plan_semester',
                'pg.group_no',
                DB::raw('GROUP_CONCAT(pg.section_id) as plan_sections'),
                DB::raw('COUNT(pg.section_id) as plan_sections_count')
            )
            ->groupBy('pg.plan_id', 'pg.plan_level', 'pg.semester', 'pg.group_no')
            ->get()
            ->toArray();
    }

    private function checkInstructorConflicts($chromosome)
    {
        $instructorSchedule = [];

        foreach ($chromosome['genes'] as $gene) {
            $instructorId = $gene['instructor_id'];
            if ($instructorId === 0) continue;

            if (!isset($instructorSchedule[$instructorId])) {
                $instructorSchedule[$instructorId] = [];
            }

            foreach ($gene['timeslots'] as $timeslot) {
                $day = $timeslot['timeslot_day'];

                if (!isset($instructorSchedule[$instructorId][$day])) {
                    $instructorSchedule[$instructorId][$day] = [];
                }

                $instructorSchedule[$instructorId][$day][] = [
                    'startTime' => $timeslot['start_time'],
                    'endTime' => $timeslot['end_time'],
                    'geneId' => $gene['gene_id']
                ];
            }
        }

        $conflictCount = 0;

        foreach ($instructorSchedule as $daySchedules) {
            foreach ($daySchedules as $sessions) {
                for ($i = 0; $i < count($sessions); $i++) {
                    for ($j = $i + 1; $j < count($sessions); $j++) {
                        if ($this->isOverlap($sessions[$i], $sessions[$j])) {
                            $conflictCount++;
                        }
                    }
                }
            }
        }

        return $conflictCount;
    }

    private function checkRoomConflicts($chromosome)
    {
        $roomSchedule = [];

        foreach ($chromosome['genes'] as $gene) {
            $roomId = $gene['room_id'];

            if (!isset($roomSchedule[$roomId])) {
                $roomSchedule[$roomId] = [];
            }

            foreach ($gene['timeslots'] as $timeslot) {
                $day = $timeslot['timeslot_day'];

                if (!isset($roomSchedule[$roomId][$day])) {
                    $roomSchedule[$roomId][$day] = [];
                }

                $roomSchedule[$roomId][$day][] = [
                    'startTime' => $timeslot['start_time'],
                    'endTime' => $timeslot['end_time'],
                    'geneId' => $gene['gene_id']
                ];
            }
        }

        $conflictCount = 0;

        foreach ($roomSchedule as $daySchedules) {
            foreach ($daySchedules as $sessions) {
                for ($i = 0; $i < count($sessions); $i++) {
                    for ($j = $i + 1; $j < count($sessions); $j++) {
                        if ($this->isOverlap($sessions[$i], $sessions[$j])) {
                            $conflictCount++;
                        }
                    }
                }
            }
        }

        return $conflictCount;
    }

    private function isOverlap($a, $b)
    {
        $startA = $this->toMinutes($a['startTime']);
        $endA = $this->toMinutes($a['endTime']);
        $startB = $this->toMinutes($b['startTime']);
        $endB = $this->toMinutes($b['endTime']);

        return ($startA < $endB) && ($startB < $endA);
    }

    private function toMinutes($timeStr)
    {
        list($h, $m) = explode(':', $timeStr);
        return (int)$h * 60 + (int)$m;
    }

    private function checkRoomCapacityViolations($chromosome)
    {
        $violationCount = 0;

        foreach ($chromosome['genes'] as $gene) {
            $roomId = $gene['room_id'];
            $roomSize = $this->roomCapacitiesMap[$roomId] ?? null;
            $sectionLoad = $this->sectionLoadsMap[$gene['section_id']] ?? null;

            if (!$roomSize || !$sectionLoad) {
                continue;
            }

            if ($sectionLoad > $roomSize) {
                $violationCount++;
            }
        }

        return $violationCount;
    }

    private function checkPlanGroupConflicts($chromosome)
    {
        $conflictCount = 0;

        foreach ($this->planGroupsSections as $groupRow) {
            $sectionIds = array_map('intval', explode(',', $groupRow->plan_sections));

            $genesInGroup = array_filter($chromosome['genes'], function($gene) use ($sectionIds) {
                return in_array($gene['section_id'], $sectionIds);
            });

            $sessions = [];

            foreach ($genesInGroup as $gene) {
                foreach ($gene['timeslots'] as $timeslot) {
                    $sessions[] = [
                        'geneId' => $gene['gene_id'],
                        'day' => $timeslot['timeslot_day'],
                        'startTime' => $timeslot['start_time'],
                        'endTime' => $timeslot['end_time']
                    ];
                }
            }

            for ($i = 0; $i < count($sessions); $i++) {
                for ($j = $i + 1; $j < count($sessions); $j++) {
                    $a = $sessions[$i];
                    $b = $sessions[$j];

                    if ($a['day'] === $b['day'] && $this->isOverlap($a, $b)) {
                        $conflictCount++;
                    }
                }
            }
        }

        return $conflictCount;
    }

    public function calculateDetailedFitness($chromosome)
    {
        $this->loadDataMaps();

        $breakdown = [
            'instructor_conflicts' => $this->checkInstructorConflicts($chromosome),
            'room_conflicts' => $this->checkRoomConflicts($chromosome),
            'capacity_violations' => $this->checkRoomCapacityViolations($chromosome),
            'group_conflicts' => $this->checkPlanGroupConflicts($chromosome)
        ];

        $breakdown['total_conflicts'] = array_sum($breakdown);
        $breakdown['fitness'] = 1 / (1 + $breakdown['total_conflicts']);

        return $breakdown;
    }

    public function getPopulationFitnessStats($population)
    {
        $fitnessValues = [];

        foreach ($population as $chromosome) {
            $fitnessValues[] = $this->calculateFitness($chromosome);
        }

        return [
            'best_fitness' => max($fitnessValues),
            'worst_fitness' => min($fitnessValues),
            'average_fitness' => array_sum($fitnessValues) / count($fitnessValues),
            'fitness_values' => $fitnessValues
        ];
    }
}
