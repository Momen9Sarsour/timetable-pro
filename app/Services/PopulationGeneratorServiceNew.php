<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Population;
use App\Models\Room;
use Exception;

class PopulationGeneratorServiceNew
{
    private $dayMapping = [
        'Saturday' => 0,
        'Sunday' => 1,
        'Monday' => 2,
        'Tuesday' => 3,
        'Wednesday' => 4,
        'Thursday' => 5,
        'Friday' => 6
    ];

    private $dayOptions = [
        '3h_1h' => ['Sunday', 'Tuesday', 'Thursday'],
        '3h_1_5h' => ['Monday', 'Wednesday'],
        '2h_1h_smw' => ['Sunday', 'Tuesday'],
        '2h_1h_sww' => ['Sunday', 'Thursday'],
        '2h_1h_mw' => ['Monday', 'Wednesday'],
        '2h_1h_st' => ['Tuesday', 'Thursday'],
        '1h' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']
    ];

    private $availableStartTimes = [
        "08:00",
        "08:30",
        "09:00",
        "09:30",
        "10:00",
        "10:30",
        "11:00",
        "11:30",
        "12:00",
        "12:30",
        "13:00",
        "13:30",
        "14:00",
        "14:30",
        "15:00",
        "15:30"
    ];

    public function generatePopulation($populationId = 0, $populationSize = 0)
    {
        try {
            Log::info("Starting population generation - Population ID: {$populationId}, Size: {$populationSize}");

            $planSections = $this->getPlanSections();
            $roomsList = $this->getRoomsList();
            $chromosomes = $this->generateChromosomes($populationId, $populationSize, $planSections, $roomsList);

            Log::info("Population generation completed - Generated: " . count($chromosomes) . " chromosomes");
            return $chromosomes;
        } catch (Exception $e) {
            Log::error("Error generating population: " . $e->getMessage());
            throw new Exception('Error generating population: ' . $e->getMessage());
        }
    }

    private function getPlanSections()
    {
        return DB::table('sections as s')
            ->join('plan_subjects as ps', 's.plan_subject_id', '=', 'ps.id')
            ->join('subjects as sub', 'ps.subject_id', '=', 'sub.id')
            ->select(
                's.id as section_id',
                's.instructor_id',
                'sub.subject_hours',
                'sub.subject_category_id',
                'ps.plan_id',
                'ps.plan_level',
                'ps.plan_semester',
                's.student_count',
                's.activity_type'
            )
            ->get();
    }

    private function getRoomsList()
    {
        return Room::with('roomType')->get();
    }

    private function generateChromosomes($populationId, $populationSize, $planSections, $roomsList)
    {
        $chromosomes = [];
        $geneIdCounter = 0;

        for ($i = 0; $i < $populationSize; $i++) {
            $chromosome_id = $i + 1;
            $genes = [];

            foreach ($planSections as $sectionRow) {
                $geneId = ++$geneIdCounter;
                $genes[] = [
                    'gene_id' => $geneId,
                    'chromosome_id' => $chromosome_id,
                    'section_id' => $sectionRow->section_id,
                    'instructor_id' => (int)$sectionRow->instructor_id,
                    'room_id' => $this->getRandomRoom($sectionRow->subject_category_id, $roomsList),
                    'subject_hours' => $sectionRow->subject_hours,
                    'subject_category_id' => $sectionRow->subject_category_id,
                    'timeslots' => $this->getRandomTimeSlots(
                        $sectionRow->subject_hours,
                        $sectionRow->subject_category_id,
                        $geneId
                    )
                ];
            }

            $chromosomes[] = [
                'population_id' => $populationId,
                'chromosome_id' => $chromosome_id,
                'genes' => $genes,
                'fitness' => 0,
                'is_fittest' => -1
            ];
        }

        return $chromosomes;
    }

    // ✅ التعديل الأساسي: استخدام room_category_id فقط
    private function getRandomRoom($subject_category_id, $roomsList)
    {
        $filteredRooms = $roomsList->filter(function($room) use ($subject_category_id) {
            return $room->room_category_id == $subject_category_id;
        });

        if ($filteredRooms->isEmpty()) {
            $filteredRooms = $roomsList;
        }

        $randomRoom = $filteredRooms->random();
        return $randomRoom->id;
    }

    public function getRandomTimeSlots($subject_hours = 0, $subject_category_id = 0, $gene_id = 0)
    {
        $schedule = [];
        $sessionNo = 1;
        $chosenDays = [];
        $durationPerSession = $subject_hours;

        if ($subject_hours == 1) {
            $chosenDays = [$this->dayOptions['1h'][array_rand($this->dayOptions['1h'])]];
        } elseif ($subject_hours == 2) {
            if ($subject_category_id == 1) {
                $options = ['2h_1h_smw', '2h_1h_sww', '2h_1h_mw', '2h_1h_st'];
                $chosen = $options[array_rand($options)];
                $chosenDays = $this->dayOptions[$chosen];
                $durationPerSession = 1;
            } else {
                $chosenDays = [$this->dayOptions['1h'][array_rand($this->dayOptions['1h'])]];
            }
        } elseif ($subject_hours == 3) {
            if ($subject_category_id == 1) {
                $options = ['3h_1h', '3h_1_5h'];
                $chosen = $options[array_rand($options)];
                $chosenDays = $this->dayOptions[$chosen];
                $durationPerSession = ($chosen === '3h_1_5h') ? 1.5 : 1;
            } else {
                $chosenDays = [$this->dayOptions['1h'][array_rand($this->dayOptions['1h'])]];
            }
        }

        $timeSlot = $this->pickRandomStartTime($durationPerSession);

        foreach ($chosenDays as $day) {
            $dayNum = $this->dayMapping[$day];
            $schedule[] = [
                'timeslot_day' => $dayNum,
                'start_time' => $timeSlot['startTime'],
                'end_time' => $timeSlot['endTime'],
                'duration_hours' => $durationPerSession,
                'session_no' => $sessionNo++,
                'gene_id' => $gene_id
            ];
        }

        return $schedule;
    }

    private function pickRandomStartTime($duration)
    {
        $maxDurationSlots = $duration * 2;
        $maxStartIndex = count($this->availableStartTimes) - $maxDurationSlots;

        if ($maxStartIndex < 0) {
            $maxStartIndex = 0;
        }

        $randomIndex = rand(0, max(0, $maxStartIndex));
        $startTime = $this->availableStartTimes[$randomIndex];

        list($startHour, $startMin) = explode(':', $startTime);
        $startHour = (int)$startHour;
        $startMin = (int)$startMin;

        $endHour = $startHour;
        $endMin = $startMin + ($duration * 60);

        if ($endMin >= 60) {
            $endHour += floor($endMin / 60);
            $endMin = $endMin % 60;
        }

        $endTime = sprintf("%02d:%02d", $endHour, $endMin);

        return [
            'startTime' => $startTime,
            'endTime' => $endTime
        ];
    }

    // ✅ التعديل: إزالة تحديث best_chromosome_id من هنا
    public function generateAndSavePopulation($populationConfig)
    {
        try {
            $population = Population::create($populationConfig);

            $populationData = $this->generatePopulation(
                $population->population_id,
                $populationConfig['population_size']
            );

            $saveService = new PopulationSaveServiceNew();
            $saveService->savePopulation($population->population_id, $populationData);

            // ✅ تحديث الحالة فقط بدون best_chromosome_id
            DB::table('populations')
                ->where('population_id', $population->population_id)
                ->update([
                    'status' => 'running',
                    'updated_at' => now()
                ]);

            Log::info("Population {$population->population_id} generated and saved successfully");
            return $population;
        } catch (Exception $e) {
            Log::error("Error generating and saving population: " . $e->getMessage());
            throw new Exception('Error generating and saving population: ' . $e->getMessage());
        }
    }

    public function validateGenerationParams($populationSize, $maxGenerations = null)
    {
        $errors = [];

        if ($populationSize <= 0) {
            $errors[] = "Population size must be greater than 0";
        }

        if ($populationSize > 1000) {
            $errors[] = "Population size too large (max: 1000)";
            }

        if ($maxGenerations !== null && $maxGenerations <= 0) {
            $errors[] = "Max generations must be greater than 0";
        }

        $sectionsCount = DB::table('sections')->count();
        if ($sectionsCount == 0) {
            $errors[] = "No sections found. Please generate sections first.";
        }

        $roomsCount = DB::table('rooms')->count();
        if ($roomsCount == 0) {
            $errors[] = "No rooms found. Please add rooms to the system.";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sections_count' => $sectionsCount,
            'rooms_count' => $roomsCount
        ];
    }

    public function getGenerationStats($populationSize, $sectionsCount)
    {
        $genesPerChromosome = $sectionsCount;
        $totalGenes = $populationSize * $genesPerChromosome;
        $estimatedTimeslots = $totalGenes * 2;

        return [
            'population_size' => $populationSize,
            'sections_count' => $sectionsCount,
            'genes_per_chromosome' => $genesPerChromosome,
            'total_genes' => $totalGenes,
            'estimated_timeslots' => $estimatedTimeslots,
            'available_days' => count($this->dayMapping),
            'available_time_slots' => count($this->availableStartTimes)
        ];
    }
}
