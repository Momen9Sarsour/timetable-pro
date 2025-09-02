<?php

///////////////////////////////////// الكود الجديد بالتوزيع الجديد يعمري //////////////////////////////////////////////


namespace App\Services;

use App\Models\Population;
use Illuminate\Support\Str;
use App\Models\Chromosome;
use App\Models\Gene;
use App\Models\Section;
use App\Models\Instructor;
use App\Models\Room;
use App\Models\Timeslot;
use App\Models\CrossoverType;
use App\Models\SelectionType;
use App\Models\MutationType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class GeneticAlgorithmService
{
    // --- الإعدادات والبيانات المحملة ---
    private array $settings;
    private Population $populationRun;
    private Collection $instructors;
    private Collection $theoryRooms;
    private Collection $practicalRooms;
    private Collection $timeslots;
    private Collection $lectureBlocksToSchedule;
    private array $consecutiveTimeslotsMap = [];
    private array $studentGroupMap = [];
    private array $instructorAssignmentMap = [];
    private array $resourceUsageCache = [];

    // (جديد) لتخزين أنواع العمليات لتجنب الاستعلام المتكرر
    private Collection $loadedCrossoverTypes;
    private Collection $loadedSelectionTypes;
    private Collection $loadedMutationTypes;

    /**
     * المُنشئ (Constructor)
     */
    public function __construct(array $settings, Population $populationRun)
    {
        $this->settings = $settings;
        $this->populationRun = $populationRun;
        Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
    }

    /**
     * الدالة الرئيسية (run)
     */
    public function run()
    {
        try {
            $this->populationRun->update(['status' => 'running', 'start_time' => now()]);
            $this->loadAndPrepareData();

            $currentGenerationNumber = 1;
            $currentPopulation = $this->createInitialPopulation($currentGenerationNumber, $this->populationRun);
            $this->evaluateFitness($currentPopulation);
            Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

            $maxGenerations = $this->settings['max_generations'];
            while ($currentGenerationNumber < $maxGenerations) {
                $bestInGen = $currentPopulation->sortByDesc('fitness_value')->first();
                // نستخدم fitness_value هنا بدلاً من penalty_value
                if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
                    Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
                    break;
                }

                $parents = $this->selectParents($currentPopulation);
                $currentGenerationNumber++;
                $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber, $this->populationRun);
                $this->evaluateFitness($currentPopulation);
                Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
            }

            $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
            $this->populationRun->update([
                'status' => 'completed',
                'end_time' => now(),
                'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
            ]);
            Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
        } catch (Exception $e) {
            Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString());
            $this->populationRun->update(['status' => 'failed']);
            throw $e;
        }
    }

    //======================================================================
    // المرحلة الأولى: تحميل وتحضير البيانات
    //======================================================================

    private function loadAndPrepareData()
    {
        Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

        // جلب كل البيانات اللازمة من قاعدة البيانات مرة واحدة
        $sections = Section::with(['planSubject.subject', 'instructors'])
            ->where('academic_year', $this->settings['academic_year'])
            ->where('semester', $this->settings['semester'])
            ->get();

        if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

        $this->instructors = Instructor::with('subjects')->get();
        $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
        $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
        $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();

        // جلب أنواع العمليات وتخزينها
        $this->loadedCrossoverTypes = CrossoverType::where('is_active', true)->get();
        $this->loadedSelectionTypes = SelectionType::where('is_active', true)->get();
        $this->loadedMutationTypes = MutationType::where('is_active', true)->get();

        // بناء الخرائط المساعدة
        $this->buildConsecutiveTimeslotsMap();
        $this->buildStudentGroupMap($sections);

        // **(الخطوة الأهم)**: التحضير المسبق الكامل للبلوكات مع تعيين المدرسين
        $this->precomputeLectureBlocks($sections);
        // dd([
        //     'lectureBlocksToSchedule' => $this->lectureBlocksToSchedule,
        // ]);

        if ($this->lectureBlocksToSchedule->isEmpty()) {
            throw new Exception("No lecture blocks to schedule were found after processing credit hours.");
        }

        Log::info("Data loaded and precomputed: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
    }
    /**
     * [الدالة المحورية الجديدة]
     * تقوم بالتحضير المسبق لكل بلوكات المحاضرات، وتعيين المدرسين، وتجهيزها للجدولة.
     */
    private function precomputeLectureBlocks(Collection $sections)
    {
        $this->lectureBlocksToSchedule = collect();
        $instructorLoad = $this->instructors->mapWithKeys(fn($inst) => [$inst->id => 0])->toArray();
        $sectionsBySubject = $sections->groupBy('planSubject.subject.id');

        foreach ($sectionsBySubject as $subjectSections) {
            $firstSection = $subjectSections->first();
            $subject = optional(optional($firstSection)->planSubject)->subject;
            if (!$subject) continue;

            // اختيار المدرس الأقل عبئاً مرة واحدة لكل مادة
            $assignedInstructor = $this->getLeastLoadedInstructorForSubject($subject, $instructorLoad);

            // التعامل مع الجزء النظري
            if ($subject->theoretical_hours > 0) {
                $theorySection = $subjectSections->firstWhere('activity_type', 'Theory');
                if ($theorySection) {
                    // استدعاء دالة تقسيم البلوكات النظرية
                    $this->splitTheoryBlocks($theorySection, $assignedInstructor, $instructorLoad);
                }
            }

            // التعامل مع الجزء العملي
            if ($subject->practical_hours > 0) {
                $practicalSections = $subjectSections->where('activity_type', 'Practical');
                foreach ($practicalSections as $practicalSection) {
                    // استدعاء دالة تقسيم البلوكات العملية
                    $this->splitPracticalBlocks($practicalSection, $assignedInstructor, $instructorLoad);
                }
            }
        }
    }
    /**
     * [مصححة]
     * تقسم الساعات النظرية إلى بلوكات (2+1) حسب المنطق المطلوب.
     */
    private function splitTheoryBlocks(Section $section, Instructor $instructor, array &$instructorLoad)
    {
        $totalSlotsNeeded = $section->planSubject->subject->theoretical_hours * ($this->settings['theory_credit_to_slots'] ?? 1);
        $remainingSlots = $totalSlotsNeeded;
        $blockCounter = 1;

        while ($remainingSlots > 0) {
            $slotsForThisBlock = ($remainingSlots >= 2) ? 2 : 1;

            $uniqueId = "{$section->id}-theory-block{$blockCounter}";
            $this->lectureBlocksToSchedule->push((object)[
                'unique_id' => $uniqueId,
                'section' => $section,
                'instructor_id' => $instructor->id,
                'student_group_id' => $this->studentGroupMap[$section->id] ?? [],
                'block_type' => 'theory',
                'slots_needed' => $slotsForThisBlock,
                'block_duration' => $totalSlotsNeeded * 50,
            ]);
            $instructorLoad[$instructor->id] += $slotsForThisBlock;
            $remainingSlots -= $slotsForThisBlock;
            $blockCounter++;
        }
    }

    /**
     * [مصححة]
     * تنشئ بلوكاً واحداً متصلاً للجزء العملي بالحجم الصحيح.
     */
    private function splitPracticalBlocks(Section $section, Instructor $instructor, array &$instructorLoad)
{
    $totalSlotsNeeded = $section->planSubject->subject->practical_hours * ($this->settings['practical_credit_to_slots'] ?? 1);
    if ($totalSlotsNeeded <= 0) return;

    $remainingSlots = $totalSlotsNeeded;
    $blockCounter = 1;

    // استراتيجية التقسيم للبلوكات العملية (مثل النظري ولكن بأحجام مختلفة)
    while ($remainingSlots > 0) {
        // تحديد حجم البلوك حسب العدد المتبقي
        if ($remainingSlots >= 4) {
            $slotsForThisBlock = 4; // بلوك كبير من 4 فترات
        } elseif ($remainingSlots >= 3) {
            $slotsForThisBlock = 3; // بلوك متوسط من 3 فترات
        } elseif ($remainingSlots >= 2) {
            $slotsForThisBlock = 2; // بلوك صغير من فترتين
        } else {
            $slotsForThisBlock = 1; // بلوك صغير جداً من فترة واحدة
        }

        $uniqueId = "{$section->id}-practical-block{$blockCounter}";
        $this->lectureBlocksToSchedule->push((object)[
            'unique_id' => $uniqueId,
            'section' => $section,
            'instructor_id' => $instructor->id,
            'student_group_id' => $this->studentGroupMap[$section->id] ?? [],
            'block_type' => 'practical',
            'slots_needed' => $slotsForThisBlock,
            'block_duration' => $slotsForThisBlock * 50, // مدة البلوك بالدقائق
        ]);

        // dd([
        //     'unique_id' => $uniqueId,
        //     'section' => $section,
        //     'instructor_id' => $instructor->id,
        //     'student_group_id' => $this->studentGroupMap[$section->id] ?? [],
        //     'block_type' => 'practical',
        //     'slots_needed' => $slotsForThisBlock,
        //     'block_duration' => $slotsForThisBlock * 50,
        // ]);

        $instructorLoad[$instructor->id] += $slotsForThisBlock;
        $remainingSlots -= $slotsForThisBlock;
        $blockCounter++;
    }
}

    private function getLeastLoadedInstructorForSubject(\App\Models\Subject $subject, array $instructorLoad)
    {
        $suitableInstructors = $this->instructors->filter(fn($inst) => $inst->subjects->contains($subject->id));
        if ($suitableInstructors->isEmpty()) return $this->instructors->random();
        return $suitableInstructors->sortBy(fn($inst) => $instructorLoad[$inst->id] ?? 0)->first();
    }

    private function buildStudentGroupMap(Collection $sections)
    {
        $this->studentGroupMap = [];
        $sectionsByContext = $sections->groupBy(function ($section) {
            $ps = $section->planSubject;
            return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
        });

        foreach ($sectionsByContext as $sectionsInContext) {
            $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id')->map->count()->max() ?? 0;
            $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

            for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
                $theorySections = $sectionsInContext->where('activity_type', 'Theory');
                foreach ($theorySections as $theorySection) {
                    $this->studentGroupMap[$theorySection->id][] = $groupIndex;
                }

                $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
                foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
                    $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
                    if ($sortedSections->has($groupIndex - 1)) {
                        $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
                        $this->studentGroupMap[$practicalSectionForThisGroup->id][] = $groupIndex;
                    }
                }
            }
        }
    }
    private function buildConsecutiveTimeslotsMap()
    {
        $timeslotsByDay = $this->timeslots->groupBy('day');
        $this->consecutiveTimeslotsMap = [];
        foreach ($timeslotsByDay as $dayTimeslots) {
            $dayTimeslotsValues = $dayTimeslots->values();
            for ($i = 0; $i < $dayTimeslotsValues->count(); $i++) {
                $currentSlot = $dayTimeslotsValues[$i];
                $this->consecutiveTimeslotsMap[$currentSlot->id] = [];
                for ($j = $i + 1; $j < $dayTimeslotsValues->count(); $j++) {
                    $nextSlot = $dayTimeslotsValues[$j];
                    if ($nextSlot->start_time == $currentSlot->end_time) {
                        $this->consecutiveTimeslotsMap[$currentSlot->id][] = $nextSlot->id;
                        $currentSlot = $nextSlot;
                    } else {
                        break;
                    }
                }
            }
        }
    }

    //======================================================================
    // المرحلة الثانية: إنشاء الجيل الأول
    //======================================================================


    private function createInitialPopulation(int $generationNumber, Population $populationRun): Collection
    {
        Log::info("Creating intelligent initial population (Generation #{$generationNumber})");
        $createdChromosomes = collect();
        for ($i = 0; $i < $this->settings['population_size']; $i++) {
            $chromosome = Chromosome::create([
                'population_id' => $populationRun->population_id,
                'penalty_value' => -1,
                'generation_number' => $generationNumber,
                'fitness_value' => 0
            ]);
            $this->generateGenesForChromosome($chromosome);
            $createdChromosomes->push($chromosome);
        }
        return $createdChromosomes;
    }

    private function generateGenesForChromosome(Chromosome $chromosome)
    {
        // نهيئ خريطة استخدام الموارد فارغة لهذا الكروموسوم
        $resourceUsageMap = [];
        $genesToInsert = [];

        // نرتب البلوكات من الأصعب (الأكبر حجماً) إلى الأسهل
        $sortedBlocks = $this->lectureBlocksToSchedule->sortByDesc('slots_needed');

        foreach ($sortedBlocks as $lectureBlock) {
            // نختار قاعة مناسبة
            $room = $this->getRandomRoomForBlock($lectureBlock);

            // نبحث عن وقت مثالي
            $foundSlots = $this->findOptimalSlotForBlock($lectureBlock, $room->id, $resourceUsageMap);

            // نحدّث خريطة الموارد المستخدمة فوراً
            $this->updateResourceMap($foundSlots, $lectureBlock->instructor_id, $room->id, $lectureBlock->student_group_id, $resourceUsageMap);

            $genesToInsert[] = [
                'chromosome_id' => $chromosome->chromosome_id,
                'lecture_unique_id' => $lectureBlock->unique_id,
                'section_id' => $lectureBlock->section->id,
                'instructor_id' => $lectureBlock->instructor_id,
                'room_id' => $room->id,
                'timeslot_ids' => json_encode($foundSlots),
                'student_group_id' => json_encode($lectureBlock->student_group_id),
                'block_type' => $lectureBlock->block_type,
                'block_duration' => $lectureBlock->block_duration,
            ];
        }

        if (!empty($genesToInsert)) {
            Gene::insert($genesToInsert);
        }
    }

    private function findOptimalSlotForBlock(\stdClass $lectureBlock, int $roomId, array &$usageMap): array
    {
        $maxAttempts = 100; // عدد المحاولات لإيجاد مكان
        $lastResortSlots = []; // لتخزين حل بديل

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            // نختار فترة بداية عشوائية
            $startSlotId = $this->timeslots->random()->id;

            // نتحقق إذا كان يمكن تكوين بلوك كامل من هذه النقطة
            if (isset($this->consecutiveTimeslotsMap[$startSlotId]) && (count($this->consecutiveTimeslotsMap[$startSlotId]) + 1) >= $lectureBlock->slots_needed) {
                $trialSlots = array_merge([$startSlotId], array_slice($this->consecutiveTimeslotsMap[$startSlotId], 0, $lectureBlock->slots_needed - 1));

                // نخزن أول محاولة كحل أخير في حالة الفشل
                if ($attempt == 0) $lastResortSlots = $trialSlots;

                // نفحص التعارضات
                $isConflict = $this->checkConflictsForSlots($trialSlots, $lectureBlock->instructor_id, $roomId, $lectureBlock->student_group_id, $usageMap);

                if (!$isConflict) {
                    return $trialSlots; // وجدنا مكاناً مثالياً!
                }
            }
        }

        // إذا فشلت كل المحاولات، نرجع أول مكان جربناه (السماح بالخطأ)
        return !empty($lastResortSlots) ? $lastResortSlots : $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
    }

    private function checkConflictsForSlots(array $slotIds, int $instructorId, int $roomId, array $studentGroupIds, array $usageMap): bool
    {
        foreach ($slotIds as $slotId) {
            if (isset($usageMap[$slotId])) {
                if (isset($usageMap[$slotId]['instructors'][$instructorId])) return true;
                if (isset($usageMap[$slotId]['rooms'][$roomId])) return true;
                if (!empty($studentGroupIds)) {
                    foreach ($studentGroupIds as $groupId) {
                        if (isset($usageMap[$slotId]['student_groups'][$groupId])) return true;
                    }
                }
            }
        }
        return false;
    }

    private function updateResourceMap(array $slotIds, int $instructorId, int $roomId, array $studentGroupIds, array &$usageMap): void
    {
        foreach ($slotIds as $slotId) {
            $usageMap[$slotId]['instructors'][$instructorId] = true;
            $usageMap[$slotId]['rooms'][$roomId] = true;
            if (!empty($studentGroupIds)) {
                foreach ($studentGroupIds as $groupId) {
                    $usageMap[$slotId]['student_groups'][$groupId] = true;
                }
            }
        }
    }


    private function getRandomRoomForBlock(\stdClass $lectureBlock)
    {
        $section = $lectureBlock->section;
        $roomsSource = ($lectureBlock->block_type === 'practical') ? $this->practicalRooms : $this->theoryRooms;
        if ($roomsSource->isEmpty()) {
            $roomsSource = ($lectureBlock->block_type === 'practical') ? $this->theoryRooms : $this->practicalRooms;
            if ($roomsSource->isEmpty()) return Room::all()->random();
        }

        $suitableRooms = $roomsSource->where('room_size', '>=', $section->student_count);
        return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $roomsSource->random();
    }


    private function findRandomConsecutiveTimeslots(int $slotsNeeded): array
    {
        if ($slotsNeeded <= 0) return [];
        if ($slotsNeeded == 1) return [$this->timeslots->random()->id];

        $possibleSlots = $this->getPossibleStartSlots($slotsNeeded);
        return $possibleSlots->isNotEmpty() ? $possibleSlots->random() : [$this->timeslots->random()->id];
    }
    private function countConflictsForSlots(array $slotIds, ?int $instructorId, ?int $roomId, ?array $studentGroupIds): int
    {
        $conflicts = 0;

        foreach ($slotIds as $slotId) {
            if (!isset($this->resourceUsageCache[$slotId])) {
                continue; // لا توجد موارد مستخدمة في هذا الوقت
            }

            // فحص تعارض الطلاب
            if (!empty($studentGroupIds)) {
                foreach ($studentGroupIds as $groupId) {
                    if (isset($this->resourceUsageCache[$slotId]['student_groups'][$groupId])) {
                        $conflicts += 10; // وزن أعلى لتعارض الطلاب
                    }
                }
            }

            // فحص تعارض المدرس
            if ($instructorId && isset($this->resourceUsageCache[$slotId]['instructors'][$instructorId])) {
                $conflicts += 5; // وزن متوسط لتعارض المدرس
            }

            // فحص تعارض القاعة
            if ($roomId && isset($this->resourceUsageCache[$slotId]['rooms'][$roomId])) {
                $conflicts += 3; // وزن أقل لتعارض القاعة
            }
        }

        return $conflicts;
    }
    private function updateResourceCache(array $slotIds, int $instructorId, int $roomId, ?array $studentGroupIds): void
    {
        foreach ($slotIds as $slotId) {
            $this->resourceUsageCache[$slotId]['instructors'][$instructorId] = true;
            $this->resourceUsageCache[$slotId]['rooms'][$roomId] = true;
            if ($studentGroupIds) {
                foreach ($studentGroupIds as $groupId) {
                    $this->resourceUsageCache[$slotId]['student_groups'][$groupId] = true;
                }
            }
        }
    }

    private function getPossibleStartSlots(int $slotsNeeded): Collection
    {
        if ($slotsNeeded <= 0) return collect();
        return $this->timeslots->filter(function ($slot) use ($slotsNeeded) {
            return isset($this->consecutiveTimeslotsMap[$slot->id]) && (count($this->consecutiveTimeslotsMap[$slot->id]) + 1) >= $slotsNeeded;
        })->mapWithKeys(function ($slot) use ($slotsNeeded) {
            return [$slot->id => array_merge([$slot->id], array_slice($this->consecutiveTimeslotsMap[$slot->id], 0, $slotsNeeded - 1))];
        });
    }

    //======================================================================
    // المرحلة الثالثة: التقييم والتحسين
    //======================================================================
    private function evaluateFitness(Collection $chromosomes)
    {
        // نحصل على IDs لكل الكروموسومات في الجيل الحالي
        $chromosomeIds = $chromosomes->pluck('chromosome_id')->filter();
        if ($chromosomeIds->isEmpty()) {
            return; // لا يوجد شيء لتقييمه
        }

        // **(التحسين الرئيسي)**: نجلب كل الجينات لكل الكروموسومات في هذا الجيل
        // باستعلام واحد فقط من قاعدة البيانات.
        $allGenesOfGeneration = Gene::whereIn('chromosome_id', $chromosomeIds)
            ->with(['section.planSubject.subject', 'room.roomType', 'instructor'])
            ->get()
            ->groupBy('chromosome_id'); // نجمع الجينات حسب الكروموسوم التابعة له

        DB::transaction(function () use ($chromosomes, $allGenesOfGeneration) {
            foreach ($chromosomes as $chromosome) {
                // نحصل على جينات هذا الكروموسوم من المجموعة التي جلبناها مسبقاً (عملية سريعة جداً في الذاكرة)
                $genes = $allGenesOfGeneration->get($chromosome->chromosome_id, collect());

                if ($genes->isEmpty()) {
                    $this->updateChromosomeFitness($chromosome, ['empty_chromosome' => 99999]);
                    continue;
                }

                $resourceUsageMap = [];
                $penalties = [];

                $penalties['student_conflict_penalty'] = $this->calculateStudentConflicts($genes, $resourceUsageMap);
                $penalties['teacher_conflict_penalty'] = $this->calculateTeacherConflicts($genes, $resourceUsageMap);
                $penalties['room_conflict_penalty'] = $this->calculateRoomConflicts($genes, $resourceUsageMap);
                $penalties['capacity_conflict_penalty'] = $this->calculateCapacityConflicts($genes);
                $penalties['room_type_conflict_penalty'] = $this->calculateRoomTypeConflicts($genes);
                $penalties['teacher_eligibility_conflict_penalty'] = $this->calculateTeacherEligibilityConflicts($genes);

                $this->updateChromosomeFitness($chromosome, $penalties);
            }
        });
    }
    private function calculateStudentConflicts(Collection $genes, array &$usageMap): int
    {
        $penalty = 0;
        foreach ($genes as $gene) {
            $studentGroupIds = $gene->student_group_id ?? [];
            foreach ($gene->timeslot_ids as $timeslotId) {
                foreach ($studentGroupIds as $groupId) {
                    if (isset($usageMap['student_groups'][$groupId][$timeslotId])) {
                        $penalty += 1;
                        // $penalty += 2000;
                    }
                    $usageMap['student_groups'][$groupId][$timeslotId] = true;
                }
            }
        }
        return $penalty;
    }
    private function calculateTeacherConflicts(Collection $genes, array &$usageMap): int
    {
        $penalty = 0;
        foreach ($genes as $gene) {
            foreach ($gene->timeslot_ids as $timeslotId) {
                if (isset($usageMap['instructors'][$gene->instructor_id][$timeslotId])) {
                    $penalty += 1;
                    // $penalty += 1000;
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
            foreach ($gene->timeslot_ids as $timeslotId) {
                if (isset($usageMap['rooms'][$gene->room_id][$timeslotId])) {
                    $penalty += 1;
                    // $penalty += 800;
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
                // $penalty += 500;
            }
        }
        return $penalty;
    }
    private function calculateRoomTypeConflicts(Collection $genes): int
    {
        $penalty = 0;
        foreach ($genes->unique('lecture_unique_id') as $gene) {
            $isPracticalBlock = Str::contains($gene->lecture_unique_id, 'practical');
            $isPracticalRoom = Str::contains(strtolower(optional($gene->room->roomType)->room_type_name), ['lab', 'مختبر']);

            if ($isPracticalBlock && !$isPracticalRoom) {
                $penalty += 1;
                // $penalty += 600;
            }
            if (!$isPracticalBlock && $isPracticalRoom) {
                $penalty += 1;
                // $penalty += 300;
            }
        }
        return $penalty;
    }
    private function calculateTeacherEligibilityConflicts(Collection $genes): int
    {
        $penalty = 0;
        foreach ($genes->unique('lecture_unique_id') as $gene) {
            if (!optional($gene->instructor)->canTeach(optional(optional($gene->section)->planSubject)->subject)) {
                $penalty += 1;
                // $penalty += 2000;
            }
        }
        return $penalty;
    }
    private function updateChromosomeFitness(Chromosome $chromosome, array $penalties)
    {
        $totalPenalty = array_sum($penalties);
        $fitnessValue = 1 / (1 + $totalPenalty);

        $updateData = array_merge($penalties, [
            'penalty_value' => $totalPenalty,
            'fitness_value' => $fitnessValue,
        ]);

        Chromosome::where('chromosome_id', $chromosome->chromosome_id)->update($updateData);
        $chromosome->fill($updateData);
    }

    private function selectParents(Collection $population): array
    {
        $selectionSlug = $this->loadedSelectionTypes->find($this->settings['selection_type_id'])->slug ?? 'tournament_selection';

        // **(جديد)**: استخدام switch لتحديد طريقة الاختيار
        switch ($selectionSlug) {
            case 'tournament_selection':
                return $this->tournamentSelection($population);
                // أضف الحالات الأخرى هنا في المستقبل
            default:
                return $this->tournamentSelection($population);
        }
    }

    private function tournamentSelection(Collection $population): array
    {
        $parents = [];
        $tournamentSize = $this->settings['selection_size'] ?? 5;
        $populationCount = $population->count();
        if ($populationCount == 0) return [];

        for ($i = 0; $i < $this->settings['population_size']; $i++) {
            $participants = $population->random(min($tournamentSize, $populationCount));
            $parents[] = $participants->sortByDesc('fitness_value')->first();
        }
        return $parents;
    }
    private function createNewGeneration(array $parents, int $nextGenerationNumber, Population $populationRun): Collection
    {
        Log::info("Creating new generation #{$nextGenerationNumber} using Hybrid approach (Optimized)");
        $newPopulation = [];
        $parentPool = array_filter($parents);

        if (empty($parentPool)) {
            Log::warning("Parent pool is empty for generation {$nextGenerationNumber}. Cannot create new generation.");
            return collect();
        }

        // **(التحسين الرئيسي الأول)**: جلب جينات كل الآباء المحتملين مرة واحدة فقط
        $parentIds = collect($parentPool)->pluck('chromosome_id')->unique();
        $allParentGenes = Gene::whereIn('chromosome_id', $parentIds)->get()->groupBy('chromosome_id');

        // **(التحسين الرئيسي الثاني)**: تحويل الآباء إلى Collection لتسهيل العمليات
        $currentPopulation = collect($parentPool);

        // **نحتفظ بنفس المنطق الهجين:** نمر على الآباء بالترتيب
        foreach ($currentPopulation as $parent1) {
            if (count($newPopulation) >= $this->settings['population_size']) break;

            // --- اختيار الأب الثاني (سريع جداً الآن) ---
            // نختار من السكان الحاليين (موجودين في الذاكرة)
            $tournamentSize = $this->settings['selection_size'] ?? 5;
            $participants = $currentPopulation->random(min($tournamentSize, $currentPopulation->count()));
            $parent2 = $participants->sortByDesc('fitness_value')->first();
            // --- انتهى اختيار الأب الثاني ---

            // نحصل على جيناتهم من المجموعة التي جلبناها مسبقاً (سريع جداً)
            $p1Genes = $allParentGenes->get($parent1->chromosome_id, collect());
            $p2Genes = $allParentGenes->get($parent2->chromosome_id, collect());

            if ($p1Genes->isEmpty() || $p2Genes->isEmpty()) continue; // نتجاهل الآباء الذين ليس لديهم جينات

            $childGenes = [];
            // التزاوج
            if (lcg_value() < ($this->settings['crossover_rate'] ?? 0.95)) {
                $childGenes = $this->performCrossover($p1Genes, $p2Genes);
            } else {
                // الابن نسخة من الأب الأفضل
                $childGenes = ($parent1->fitness_value >= $parent2->fitness_value) ? $p1Genes->all() : $p2Genes->all();
            }

            // الطفرة
            $mutatedChildGenes = $this->performMutation($childGenes);

            // حفظ الابن الجديد
            $newPopulation[] = $this->saveChildChromosome($mutatedChildGenes, $nextGenerationNumber, $populationRun);
        }

        return collect($newPopulation);
    }

    private function performCrossover(Chromosome $parent1, Chromosome $parent2): Collection
    {
        $crossoverSlug = $this->loadedCrossoverTypes->find($this->settings['crossover_type_id'])->slug ?? 'single_point';

        // **(جديد)**: استخدام switch لتحديد طريقة التزاوج
        switch ($crossoverSlug) {
            case 'single_point':
                return $this->singlePointCrossover($parent1, $parent2);
                // أضف الحالات الأخرى هنا في المستقبل
            default:
                return $this->singlePointCrossover($parent1, $parent2);
        }
    }

    private function singlePointCrossover(Chromosome $parent1, Chromosome $parent2): Collection
    {
        $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
        $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');
        $childGenes = collect();

        $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
        $currentIndex = 0;

        foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
            $source = ($currentIndex < $crossoverPoint) ? $p1Genes : $p2Genes;
            $gene = $source->get($lectureBlock->unique_id) ?? $p2Genes->get($lectureBlock->unique_id) ?? $p1Genes->get($lectureBlock->unique_id);
            if ($gene) $childGenes->push($gene);
            $currentIndex++;
        }
        return $childGenes;
    }

    private function performMutation(array $genes): array
    {
        if (lcg_value() < $this->settings['mutation_rate']) {
            $mutationSlug = $this->loadedMutationTypes->find($this->settings['mutation_type_id'])->slug ?? 'smart_swap';

            // **(جديد)**: استخدام switch لتحديد طريقة الطفرة
            switch ($mutationSlug) {
                case 'smart_swap':
                    return $this->smartSwapMutation($genes);
                    // أضف الحالات الأخرى هنا في المستقبل
                default:
                    return $this->smartSwapMutation($genes);
            }
        }
        return $genes;
    }

    private function swapMutation(array $genes): array
    {
        if (count($genes) < 2) return $genes;

        // اختر جينين عشوائيين
        $index1 = array_rand($genes);
        $index2 = array_rand($genes);

        while ($index2 == $index1 && count($genes) > 1) {
            $index2 = array_rand($genes);
        }

        // التحقق من نوع البيانات وإنشاء نسخ
        if (is_object($genes[$index1]) && is_object($genes[$index2])) {
            // إذا كانت كائنات، استخدم clone
            $gene1 = clone $genes[$index1];
            $gene2 = clone $genes[$index2];
        } else {
            // إذا كانت مصفوفات أو أنواع أخرى، أنشئ كائنات جديدة
            $gene1 = (object) $genes[$index1];
            $gene2 = (object) $genes[$index2];
        }

        // تبديل الأوقات والقاعات بين الجينين
        $tempRoom = $gene1->room_id;
        $tempTimeslots = $gene1->timeslot_ids;

        $gene1->room_id = $gene2->room_id;
        $gene1->timeslot_ids = $gene2->timeslot_ids;

        $gene2->room_id = $tempRoom;
        $gene2->timeslot_ids = $tempTimeslots;

        // تحقق من التعارضات قبل القبول
        if (
            !$this->isGeneConflictingWithRest($gene1, $genes) &&
            !$this->isGeneConflictingWithRest($gene2, $genes)
        ) {
            $genes[$index1] = $gene1;
            $genes[$index2] = $gene2;
        }

        return $genes;
    }
    private function smartSwapMutation(array $genes): array
    {
        if (count($genes) < 2) return $genes;

        $swapCandidates = [];

        foreach ($genes as $idx1 => $gene1) {
            foreach ($genes as $idx2 => $gene2) {
                if ($idx1 >= $idx2) continue;

                // التحقق من التشابه
                if ($gene1->block_type == $gene2->block_type) {
                    $conflicts1Before = $this->calculateGeneConflicts($gene1, array_diff_key($genes, [$idx1 => 1]));
                    $conflicts2Before = $this->calculateGeneConflicts($gene2, array_diff_key($genes, [$idx2 => 1]));

                    // محاكاة التبديل
                    $temp1 = clone $gene1;
                    $temp2 = clone $gene2;

                    $tempTimeslots = $temp1->timeslot_ids;
                    $temp1->timeslot_ids = $temp2->timeslot_ids;
                    $temp2->timeslot_ids = $tempTimeslots;

                    $conflicts1After = $this->calculateGeneConflicts($temp1, array_diff_key($genes, [$idx1 => 1]));
                    $conflicts2After = $this->calculateGeneConflicts($temp2, array_diff_key($genes, [$idx2 => 1]));

                    $improvementScore = ($conflicts1Before + $conflicts2Before) - ($conflicts1After + $conflicts2After);

                    if ($improvementScore > 0) {
                        $swapCandidates[] = [
                            'idx1' => $idx1,
                            'idx2' => $idx2,
                            'improvement' => $improvementScore,
                            'gene1' => $temp1,
                            'gene2' => $temp2
                        ];
                    }
                }
            }
        }

        if (!empty($swapCandidates)) {
            usort($swapCandidates, fn($a, $b) => $b['improvement'] <=> $a['improvement']);
            $bestSwap = $swapCandidates[0];

            $genes[$bestSwap['idx1']] = $bestSwap['gene1'];
            $genes[$bestSwap['idx2']] = $bestSwap['gene2'];
        } else {
            // إذا لم نجد تحسين، نعمل swap عشوائي
            return $this->swapMutation($genes);
        }

        return $genes;
    }
    private function calculateGeneConflicts($targetGene, array $otherGenes): int
    {
        $conflicts = 0;
        $studentGroupIds = $targetGene->student_group_id ?? [];

        foreach ($targetGene->timeslot_ids as $timeslotId) {
            foreach ($otherGenes as $otherGene) {
                if (!$otherGene) continue;

                if (in_array($timeslotId, $otherGene->timeslot_ids)) {
                    // تعارض المدرس
                    if ($otherGene->instructor_id == $targetGene->instructor_id) {
                        $conflicts += 100;
                    }
                    // تعارض القاعة
                    if ($otherGene->room_id == $targetGene->room_id) {
                        $conflicts += 80;
                    }
                    // تعارض الطلاب
                    $otherStudentGroupIds = $otherGene->student_group_id ?? [];
                    if (!empty($studentGroupIds) && !empty($otherStudentGroupIds)) {
                        if (count(array_intersect($studentGroupIds, $otherStudentGroupIds)) > 0) {
                            $conflicts += 200;
                        }
                    }
                }
            }
        }

        return $conflicts;
    }
    private function isGeneConflictingWithRest($targetGene, array $allOtherGenes): bool
    {
        // تحويل targetGene لكائن إذا كان مصفوفة
        if (is_array($targetGene)) {
            $targetGene = (object) $targetGene;
        }

        // فلترة الجينات الأخرى - استبعاد الجين المستهدف
        $otherGenes = array_filter($allOtherGenes, function ($g) use ($targetGene) {
            if (!$g) return false;

            // تحويل لكائن إذا كان مصفوفة
            if (is_array($g)) {
                $g = (object) $g;
            }

            return $g->lecture_unique_id != $targetGene->lecture_unique_id;
        });

        // استخراج student group IDs مع التعامل مع JSON
        $studentGroupIds = $targetGene->student_group_id ?? [];
        if (is_string($studentGroupIds)) {
            $studentGroupIds = json_decode($studentGroupIds, true) ?? [];
        }

        // استخراج timeslot IDs مع التعامل مع JSON
        $targetTimeslots = $targetGene->timeslot_ids ?? [];
        if (is_string($targetTimeslots)) {
            $targetTimeslots = json_decode($targetTimeslots, true) ?? [];
        }

        foreach ($targetTimeslots as $timeslotId) {
            foreach ($otherGenes as $otherGene) {
                // تحويل لكائن إذا كان مصفوفة
                if (is_array($otherGene)) {
                    $otherGene = (object) $otherGene;
                }

                // استخراج timeslots للجين الآخر
                $otherTimeslots = $otherGene->timeslot_ids ?? [];
                if (is_string($otherTimeslots)) {
                    $otherTimeslots = json_decode($otherTimeslots, true) ?? [];
                }

                if (in_array($timeslotId, $otherTimeslots)) {
                    // تعارض المدرس
                    if ($otherGene->instructor_id == $targetGene->instructor_id) {
                        return true;
                    }

                    // تعارض القاعة
                    if ($otherGene->room_id == $targetGene->room_id) {
                        return true;
                    }

                    // تعارض الطلاب
                    $otherStudentGroupIds = $otherGene->student_group_id ?? [];
                    if (is_string($otherStudentGroupIds)) {
                        $otherStudentGroupIds = json_decode($otherStudentGroupIds, true) ?? [];
                    }

                    if (!empty($studentGroupIds) && !empty($otherStudentGroupIds)) {
                        if (count(array_intersect($studentGroupIds, $otherStudentGroupIds)) > 0) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    private function saveChildChromosome(array $genes, int $generationNumber, Population $populationRun): Chromosome
    {
        $chromosome = Chromosome::create([
            'population_id' => $populationRun->population_id,
            'penalty_value' => -1,
            'generation_number' => $generationNumber,
            'fitness_value' => 0
        ]);

        $genesToInsert = [];
        foreach ($genes as $gene) {
            if (is_null($gene)) continue;
            // التحويل من كائن إلى مصفوفة إذا لزم الأمر
            $geneArray = is_array($gene) ? $gene : $gene->toArray();

            $genesToInsert[] = [
                'chromosome_id' => $chromosome->chromosome_id,
                'lecture_unique_id' => $geneArray['lecture_unique_id'],
                'section_id' => $geneArray['section_id'],
                'instructor_id' => $geneArray['instructor_id'],
                'room_id' => $geneArray['room_id'],
                'timeslot_ids' => is_string($geneArray['timeslot_ids']) ? $geneArray['timeslot_ids'] : json_encode($geneArray['timeslot_ids']),
                'student_group_id' => is_string($geneArray['student_group_id']) ? $geneArray['student_group_id'] : json_encode($geneArray['student_group_id']),
                'block_type' => $geneArray['block_type'],
                'block_duration' => $geneArray['block_duration'],
            ];
        }
        if (!empty($genesToInsert)) Gene::insert($genesToInsert);
        return $chromosome;
    }
}
