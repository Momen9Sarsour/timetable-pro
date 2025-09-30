<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PlanSubject;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Instructor;
use App\Models\PlanExpectedCount;
use Exception;

class SectionGeneratorServiceNew
{
    public function generateSections()
    {
        try {
            Log::info("Starting section generation process...");

            $planSubjects = $this->getPlanSubjectsWithDetails();
            $expectedCountMap = $this->getExpectedCountsMap();
            $subjectInstructorsMap = $this->getSubjectInstructorsMap();
            $sections = $this->buildSections($planSubjects, $expectedCountMap, $subjectInstructorsMap);
            $insertedCount = $this->insertSections($sections);

            Log::info("Section generation completed successfully. Inserted: {$insertedCount} sections");
            return $insertedCount;
        } catch (Exception $e) {
            Log::error("Error generating sections: " . $e->getMessage());
            throw new Exception('Error generating sections: ' . $e->getMessage());
        }
    }

    private function getPlanSubjectsWithDetails()
    {
        return DB::table('plan_subjects as ps')
            ->join('subjects as s', 'ps.subject_id', '=', 's.id')
            ->select(
                'ps.id as plan_subject_id',
                'ps.plan_id',
                'ps.plan_level',
                'ps.plan_semester',
                'ps.subject_id',
                's.subject_no',
                's.subject_load'
            )
            ->orderBy('ps.id')
            ->get();
    }

    private function getExpectedCountsMap()
    {
        $expectedCounts = DB::table('plan_expected_counts')
            ->selectRaw('plan_id, plan_level, plan_semester, (male_count + female_count) as students_count')
            ->get();

        $expectedCountMap = [];
        foreach ($expectedCounts as $row) {
            $key = "{$row->plan_id}|{$row->plan_level}|{$row->plan_semester}";
            $expectedCountMap[$key] = $row->students_count;
        }

        return $expectedCountMap;
    }

    private function getSubjectInstructorsMap()
    {
        $instructorSubjects = DB::table('instructor_subject')
            ->select('instructor_id', 'subject_id')
            ->get();

        $subjectInstructorsMap = [];
        foreach ($instructorSubjects as $row) {
            if (!isset($subjectInstructorsMap[$row->subject_id])) {
                $subjectInstructorsMap[$row->subject_id] = [];
            }
            $subjectInstructorsMap[$row->subject_id][] = $row->instructor_id;
        }

        return $subjectInstructorsMap;
    }

    private function buildSections($planSubjects, $expectedCountMap, $subjectInstructorsMap)
    {
        $sections = [];

        foreach ($planSubjects as $ps) {
            if (in_array($ps->subject_id, [243, 244])) {
                continue;
            }

            $key = "{$ps->plan_id}|{$ps->plan_level}|{$ps->plan_semester}";
            $studentsCount = $expectedCountMap[$key] ?? 0;
            $subjectLoad = $ps->subject_load ?: 25;

            if ($studentsCount === 0) {
                Log::warning("No students found for plan_id: {$ps->plan_id}, level: {$ps->plan_level}, semester: {$ps->plan_semester}");
                continue;
            }

            $numberOfSections = ceil($studentsCount / $subjectLoad);
            $instructors = $subjectInstructorsMap[$ps->subject_id] ?? [];

            if (empty($instructors)) {
                Log::warning("No instructor found for subject_id {$ps->subject_id}");
                continue;
            }

            $activity_type = str_ends_with($ps->subject_no, '-P') ? 'Practical' : 'Theory';

            for ($i = 0; $i < $numberOfSections; $i++) {
                $instructor_id = $instructors[array_rand($instructors)];
                $sectionStudents = min($subjectLoad, $studentsCount - ($i * $subjectLoad));

                $sections[] = [
                    'plan_subject_id' => $ps->plan_subject_id,
                    'instructor_id' => $instructor_id,
                    'activity_type' => $activity_type,
                    'section_number' => $i + 1,
                    'student_count' => $sectionStudents,
                    'section_gender' => 'Mixed',
                    'branch' => null,
                    'academic_year' => date('Y'),
                    'semester' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }

        return $sections;
    }

    private function insertSections($sections)
    {
        if (empty($sections)) {
            Log::warning("No sections to insert");
            return 0;
        }

        $year = date('Y');
        $semester = 1; // أو خليها dynamic حسب ما تحتاج
        Section::where('academic_year', $year)
            ->where('semester', $semester)
            ->delete();

        $insertedCount = 0;
        foreach (array_chunk($sections, 100) as $chunk) {
            DB::table('sections')->insert($chunk);
            $insertedCount += count($chunk);
        }

        Log::info("Inserted {$insertedCount} sections successfully");
        return $insertedCount;
    }

    public function clearSections()
    {
        try {
            $deletedCount = Section::count();
            // Section::truncate();
            $year = date('Y');
            $semester = 1; // أو خليها dynamic حسب ما تحتاج
            Section::where('academic_year', $year)
                ->where('semester', $semester)
                ->delete();
            Log::info("Cleared {$deletedCount} sections from database");
            return $deletedCount;
        } catch (Exception $e) {
            Log::error("Error clearing sections: " . $e->getMessage());
            throw new Exception('Error clearing sections: ' . $e->getMessage());
        }
    }

    public function getSectionsStats()
    {
        return [
            'total_sections' => Section::count(),
            'theory_sections' => Section::where('activity_type', 'Theory')->count(),
            'practical_sections' => Section::where('activity_type', 'Practical')->count(),
            'sections_by_plan' => Section::join('plan_subjects', 'sections.plan_subject_id', '=', 'plan_subjects.id')
                ->selectRaw('plan_subjects.plan_id, COUNT(*) as sections_count')
                ->groupBy('plan_subjects.plan_id')
                ->get()
        ];
    }
}
