<?php

namespace App\Http\Controllers\Algorithm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\SectionGeneratorServiceNew;
use App\Services\PlanGroupServiceNew;
use Exception;
use Illuminate\Support\Facades\DB;

class NewGeneticController extends Controller
{
    public function sectionsIndex()
    {
        try {
            $sectionService = new SectionGeneratorServiceNew();
            $stats = $sectionService->getSectionsStats();

            return view('algorithm.new-system.sections.index', compact('stats'));
        } catch (Exception $e) {
            Log::error("Error loading sections page: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading sections page: ' . $e->getMessage());
        }
    }

    public function generateSections(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'clear_existing' => 'boolean',
                'run_in_background' => 'boolean'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->with('error', 'Validation failed');
            }

            $clearExisting = $request->input('clear_existing', false);
            $sectionService = new SectionGeneratorServiceNew();

            if ($clearExisting) {
                $deletedCount = $sectionService->clearSections();
                Log::info("Cleared {$deletedCount} existing sections");
            }

            set_time_limit(300);
            $insertedCount = $sectionService->generateSections();

            return redirect()->back()->with('success', "Successfully generated {$insertedCount} sections");
        } catch (Exception $e) {
            Log::error("Error generating sections: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error generating sections: ' . $e->getMessage());
        }
    }

    public function planGroupsIndex()
    {
        try {
            $planGroupService = new PlanGroupServiceNew();
            $stats = $planGroupService->getPlanGroupsStats();
            // Get groups with subject names
            $groupsWithSubjects = DB::table('plan_groups as pg')
                ->join('sections as s', 'pg.section_id', '=', 's.id')
                ->join('plan_subjects as ps', 's.plan_subject_id', '=', 'ps.id')
                ->join('subjects as sub', 'ps.subject_id', '=', 'sub.id')
                ->select(
                    'pg.plan_id',
                    'pg.plan_level',
                    'pg.semester as plan_semester',
                    DB::raw('COUNT(DISTINCT pg.group_id) as groups_count'),
                    DB::raw('GROUP_CONCAT(DISTINCT sub.subject_name SEPARATOR ", ") as subjects')
                )
                ->groupBy('pg.plan_id', 'pg.plan_level', 'pg.semester')
                ->get();

            $stats['groups_by_plan_with_subjects'] = $groupsWithSubjects;

            return view('algorithm.new-system.plan-groups.index', compact('stats'));
        } catch (Exception $e) {
            Log::error("Error loading plan groups page: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading plan groups page: ' . $e->getMessage());
        }
    }

    public function generatePlanGroups(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'clear_existing' => 'boolean',
                'run_in_background' => 'boolean'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->with('error', 'Validation failed');
            }

            $clearExisting = $request->input('clear_existing', false);
            $planGroupService = new PlanGroupServiceNew();

            if ($clearExisting) {
                $deletedCount = $planGroupService->clearPlanGroups();
                Log::info("Cleared {$deletedCount} existing plan groups");
            }

            set_time_limit(300);
            $insertedCount = $planGroupService->generatePlanGroups();

            return redirect()->back()->with('success', "Successfully generated {$insertedCount} plan groups");
        } catch (Exception $e) {
            Log::error("Error generating plan groups: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error generating plan groups: ' . $e->getMessage());
        }
    }

    public function getSectionsStats()
    {
        try {
            $sectionService = new SectionGeneratorServiceNew();
            $stats = $sectionService->getSectionsStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getPlanGroupsStats()
    {
        try {
            $planGroupService = new PlanGroupServiceNew();
            $stats = $planGroupService->getPlanGroupsStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getPlanGroupsByContext(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'plan_id' => 'required|integer',
                'plan_level' => 'required|integer',
                'plan_semester' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $planGroupService = new PlanGroupServiceNew();
            $groups = $planGroupService->getPlanGroupsByContext(
                $request->plan_id,
                $request->plan_level,
                $request->plan_semester
            );

            return response()->json([
                'success' => true,
                'data' => $groups
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function clearSections()
    {
        try {
            $sectionService = new SectionGeneratorServiceNew();
            $deletedCount = $sectionService->clearSections();

            return response()->json([
                'success' => true,
                'message' => "Successfully cleared {$deletedCount} sections"
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function clearPlanGroups()
    {
        try {
            $planGroupService = new PlanGroupServiceNew();
            $deletedCount = $planGroupService->clearPlanGroups();

            return response()->json([
                'success' => true,
                'message' => "Successfully cleared {$deletedCount} plan groups"
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function validateRequirements()
    {
        try {
            $requirements = [
                'plan_subjects_count' => DB::table('plan_subjects')->count(),
                'subjects_count' => DB::table('subjects')->count(),
                'instructors_count' => DB::table('instructors')->count(),
                'instructor_subjects_count' => DB::table('instructor_subject')->count(),
                'expected_counts_count' => DB::table('plan_expected_counts')->count(),
                'rooms_count' => DB::table('rooms')->count()
            ];

            $errors = [];

            if ($requirements['plan_subjects_count'] == 0) {
                $errors[] = 'No plan subjects found. Please add plan subjects first.';
            }

            if ($requirements['subjects_count'] == 0) {
                $errors[] = 'No subjects found. Please add subjects first.';
            }

            if ($requirements['instructors_count'] == 0) {
                $errors[] = 'No instructors found. Please add instructors first.';
            }

            if ($requirements['instructor_subjects_count'] == 0) {
                $errors[] = 'No instructor-subject assignments found. Please assign subjects to instructors.';
            }

            if ($requirements['expected_counts_count'] == 0) {
                $errors[] = 'No expected student counts found. Please add expected counts for plans.';
            }

            if ($requirements['rooms_count'] == 0) {
                $errors[] = 'No rooms found. Please add rooms first.';
            }

            return response()->json([
                'success' => empty($errors),
                'requirements' => $requirements,
                'errors' => $errors
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
