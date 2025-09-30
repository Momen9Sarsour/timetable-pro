<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PlanGroup;
use App\Models\Section;
use Exception;

class PlanGroupServiceNew
{
    /**
     * Generate plan groups based on sections and student counts
     *
     * @return int Number of plan groups created
     * @throws Exception
     */
    public function generatePlanGroups()
    {
        try {
            Log::info("Starting plan groups generation process...");

            // 1. Fetch sections data: section_id, plan_id, plan_level, plan_semester, subject_id, student_count
            $sectionsData = $this->getSectionsData();

            // 2. Group sections by plan context
            $sectionsGrouped = $this->groupSectionsByPlan($sectionsData);

            // 3. Generate plan groups for each plan context
            $insertBatch = $this->generateAllPlanGroups($sectionsGrouped);

            // 4. Insert generated groups into the plan_groups table
            $insertedCount = $this->insertPlanGroups($insertBatch);

            Log::info("Plan groups generation completed successfully. Inserted: {$insertedCount} plan groups");

            return $insertedCount;

        } catch (Exception $e) {
            Log::error("Error generating plan groups: " . $e->getMessage());
            throw new Exception('Error generating plan groups: ' . $e->getMessage());
        }
    }

    /**
     * Get sections data with all required information
     */
    private function getSectionsData()
    {
        // Using the view from original code or building equivalent query
        return DB::table('sections as s')
            ->join('plan_subjects as ps', 's.plan_subject_id', '=', 'ps.id')
            ->join('subjects as sub', 'ps.subject_id', '=', 'sub.id')
            ->select(
                'ps.plan_id',
                'ps.plan_level',
                'ps.plan_semester',
                'ps.subject_id',
                's.student_count',
                's.id as section_id',
                'sub.subject_load',
                's.section_number',
                'sub.subject_load as subject_load'
            )
            ->get();
    }

    /**
     * Group sections by plan context (plan_id-plan_level-plan_semester)
     */
    private function groupSectionsByPlan($sectionsData)
    {
        $sections = [];

        foreach ($sectionsData as $row) {
            $key = "{$row->plan_id}-{$row->plan_level}-{$row->plan_semester}";

            if (!isset($sections[$key])) {
                $sections[$key] = [];
            }

            $sections[$key][] = [
                'plan_id' => $row->plan_id,
                'plan_level' => $row->plan_level,
                'plan_semester' => $row->plan_semester,
                'subject_id' => $row->subject_id,
                'student_count' => $row->student_count,
                'section_id' => $row->section_id,
                'section_load' => $row->subject_load, // Using subject_load as section capacity
                'section_number' => $row->section_number,
                'subject_load' => $row->subject_load,
                'totalLoad' => $row->subject_load,
                'remainingSeats' => $row->subject_load
            ];
        }

        return $sections;
    }

    /**
     * Generate plan groups for all plan contexts
     */
    private function generateAllPlanGroups($sectionsGrouped)
    {
        $insertBatch = [];

        foreach ($sectionsGrouped as $key => $planSections) {
            list($planId, $planLevel, $planSemester) = explode('-', $key);
            $planStudentsCount = $planSections[0]['student_count'];

            $result = $this->generatePlanGroupsFromSections(
                $planSections,
                $planStudentsCount,
                $planId,
                $planLevel,
                $planSemester
            );

            $insertBatch = array_merge($insertBatch, $result);
        }

        return $insertBatch;
    }

    /**
     * Generate plan groups from sections for a specific plan context
     * This is the core algorithm from the original code
     */
    private function generatePlanGroupsFromSections($planSections, $planStudentsCount, $planId, $planLevel, $planSemester)
    {
        $insertBatch = [];
        $subjects = [];

        // Group sections by SUBJECT_ID
        foreach ($planSections as $row) {
            if (!isset($subjects[$row['subject_id']])) {
                $subjects[$row['subject_id']] = [];
            }
            $subjects[$row['subject_id']][] = [
                'section_id' => $row['section_id'],
                'section_load' => $row['section_load'],
                'remainingSeats' => $row['section_load']
            ];
        }

        $totalStudents = $planStudentsCount;
        $minSectionLoad = min(array_map(fn($c) => $c['subject_load'], $planSections));

        $numCopies = ceil($totalStudents / $minSectionLoad);

        $groupNo = 1;
        $remainingStudents = $totalStudents;

        while ($remainingStudents > 0) {
            $groupSize = min($minSectionLoad, $remainingStudents);

            foreach ($subjects as $subjectId => $subjectSections) {
                $availableSection = null;

                // Find a section with enough remaining seats
                foreach ($subjectSections as $section) {
                    if ($section['remainingSeats'] >= $groupSize) {
                        $availableSection = $section;
                        break;
                    }
                }

                if ($availableSection) {
                    $insertBatch[] = [
                        'plan_id' => $planId,
                        'plan_level' => $planLevel,
                        'academic_year' => date('Y'), // Current year
                        'semester' => $planSemester,
                        'branch' => null, // Default value
                        'gender' => 'Mixed', // Default value
                        'subject_id' => (int)$subjectId,
                        'section_id' => $availableSection['section_id'],
                        'group_no' => $groupNo,
                        'group_size' => $groupSize,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                    // Update remaining seats
                    foreach ($subjects[$subjectId] as &$section) {
                        if ($section['section_id'] === $availableSection['section_id']) {
                            $section['remainingSeats'] -= $groupSize;
                            break;
                        }
                    }
                } else {
                    Log::warning("Not enough seats for SUBJECT_ID={$subjectId}, GROUP_NO={$groupNo} - Skipping assignment");
                }
            }

            $remainingStudents -= $groupSize;
            $groupNo++;
        }

        Log::info("Generated " . count($insertBatch) . " plan group assignments for plan {$planId}-{$planLevel}-{$planSemester}");
        return $insertBatch;
    }

    /**
     * Insert plan groups into database
     */
    private function insertPlanGroups($insertBatch)
    {
        if (empty($insertBatch)) {
            Log::warning("No plan groups to insert");
            return 0;
        }

        // Chunk insert to avoid hitting max packet size
        $insertedCount = 0;
        foreach (array_chunk($insertBatch, 100) as $chunk) {
            DB::table('plan_groups')->insert($chunk);
            $insertedCount += count($chunk);
        }

        Log::info("Inserted {$insertedCount} plan groups successfully");
        return $insertedCount;
    }

    /**
     * Clear all existing plan groups (use with caution)
     */
    public function clearPlanGroups()
    {
        try {
            $deletedCount = PlanGroup::count();
            PlanGroup::truncate();
            Log::info("Cleared {$deletedCount} plan groups from database");
            return $deletedCount;
        } catch (Exception $e) {
            Log::error("Error clearing plan groups: " . $e->getMessage());
            throw new Exception('Error clearing plan groups: ' . $e->getMessage());
        }
    }

    /**
     * Get plan groups statistics
     */
    public function getPlanGroupsStats()
    {
        return [
            'total_groups' => PlanGroup::count(),
            'groups_by_plan' => PlanGroup::selectRaw('plan_id, plan_level, semester, COUNT(*) as groups_count')
                ->groupBy('plan_id', 'plan_level', 'semester')
                ->get(),
            'groups_by_subject' => PlanGroup::selectRaw('subject_id, COUNT(*) as groups_count')
                ->groupBy('subject_id')
                ->get(),
            'average_group_size' => PlanGroup::avg('group_size')
        ];
    }

    /**
     * Get plan groups for a specific plan context
     */
    public function getPlanGroupsByContext($planId, $planLevel, $planSemester)
    {
        return PlanGroup::where('plan_id', $planId)
            ->where('plan_level', $planLevel)
            ->where('semester', $planSemester)
            ->with(['section', 'section.planSubject.subject'])
            ->orderBy('group_no')
            ->get();
    }
}

// TODO: Implement Job for background processing
/*
// Job implementation (commented out for now)
namespace App\Jobs\Algorithm;

use App\Jobs\Job;
use App\Services\PlanGroupServiceNew;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class GeneratePlanGroupsJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $planId;
    protected $planLevel;
    protected $planSemester;

    public function __construct($planId = null, $planLevel = null, $planSemester = null)
    {
        $this->planId = $planId;
        $this->planLevel = $planLevel;
        $this->planSemester = $planSemester;
    }

    public function handle()
    {
        $service = new PlanGroupServiceNew();
        return $service->generatePlanGroups();
    }
}
*/
