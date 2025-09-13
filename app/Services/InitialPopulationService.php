<?php

///////////////////////////////////// نسخة مبسطة للجيل الأول فقط //////////////////////////////////////////////

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

class InitialPopulationService
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
        // Log::info("Initial Population Service initialized for Run ID: {$this->populationRun->population_id}");
    }

    /**
     * الدالة الرئيسية - تنشئ الجيل الأول فقط
     */
    public function generateInitialGeneration()
    {
        try {
            $this->populationRun->update(['status' => 'running', 'start_time' => now()]);
            $this->loadAndPrepareData();

            $currentGenerationNumber = 1;
            $currentPopulation = $this->createInitialPopulation($currentGenerationNumber, $this->populationRun);
            $this->evaluateFitness($currentPopulation);

            // حفظ صفوة الجيل الأول
            $this->updateEliteChromosomes($currentPopulation, $currentGenerationNumber);

            // Log::info("Initial Generation #{$currentGenerationNumber} fitness evaluated.");

            // تحديد أفضل كروموسوم
            $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();

            $this->populationRun->update([
                'status' => 'completed',
                'end_time' => now(),
                'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
            ]);

            // Log::info("Initial Population Generation completed successfully for Run ID: {$this->populationRun->population_id}");
        } catch (Exception $e) {
            // Log::error("Initial Population Generation failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString());
            $this->populationRun->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * دالة جديدة لتحديث الصفوة مع التاريخ
     */
    private function updateEliteChromosomes(Collection $population, int $generationNumber)
    {
        // $elitismCount = $this->settings['elitism_count_chromosomes'] ?? 5;
        $elitismCount = $this->populationRun->elitism_count ?? 5;

        // اختيار أفضل الكروموسومات
        $currentEliteIds = $population->sortByDesc('fitness_value')
            ->take($elitismCount)
            ->pluck('chromosome_id')
            ->toArray();

        // جلب التاريخ الحالي للصفوة
        $currentEliteHistory = $this->populationRun->elite_chromosome_ids ?? [];

        // إضافة الجيل الجديد للتاريخ
        $currentEliteHistory[] = $currentEliteIds;

        // تحديث البيانات في قاعدة البيانات
        $this->populationRun->update([
            'elite_chromosome_ids' => $currentEliteHistory
        ]);

        // Log::info("Elite chromosomes updated for Generation #{$generationNumber}: " . implode(', ', $currentEliteIds));
        // Log::info("Elite history now contains " . count($currentEliteHistory) . " generations");
    }

    //======================================================================
    // المرحلة الأولى: تحميل وتحضير البيانات
    //======================================================================

    private function loadAndPrepareData()
    {
        // Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

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

        if ($this->lectureBlocksToSchedule->isEmpty()) {
            throw new Exception("No lecture blocks to schedule were found after processing credit hours.");
        }

        // Log::info("Data loaded and precomputed: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
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
        // Log::info("Creating intelligent initial population (Generation #{$generationNumber})");
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
            $foundSlots = $this->findRandomSlotForBlock($lectureBlock);

            // **فحص للتأكد من صحة العدد - مرجع للجيل الأول**
            if (count($foundSlots) != $lectureBlock->slots_needed) {
                // Log::warning("Block {$lectureBlock->unique_id} needs {$lectureBlock->slots_needed} slots but got " . count($foundSlots) . " slots: " . json_encode($foundSlots));
            } else {
                // Log::info("Block {$lectureBlock->unique_id} successfully got {$lectureBlock->slots_needed} slots: " . json_encode($foundSlots));
            }

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

    // الدالة الجديدة للاختيار العشوائي الكامل في الجيل الأول
    private function findRandomSlotForBlock(\stdClass $lectureBlock): array
    {
        $slotsNeeded = $lectureBlock->slots_needed;

        // إذا كان محتاج فترة واحدة فقط، اختر عشوائي
        if ($slotsNeeded <= 1) {
            return [$this->timeslots->random()->id];
        }

        // للفترات المتعددة، ابحث عن فترات متتالية عشوائياً
        $possibleStartSlots = $this->timeslots->filter(function ($slot) use ($slotsNeeded) {
            // تأكد أن هذا الـ slot يمكن أن يكون بداية لعدد الفترات المطلوبة
            return isset($this->consecutiveTimeslotsMap[$slot->id]) &&
                (count($this->consecutiveTimeslotsMap[$slot->id]) + 1) >= $slotsNeeded;
        });

        if ($possibleStartSlots->isEmpty()) {
            // إذا لم نجد فترات متتالية كافية، اختر فترات منفصلة عشوائياً
            return $this->timeslots->random($slotsNeeded)->pluck('id')->toArray();
        }

        // اختر نقطة بداية عشوائية من الفترات الممكنة
        $startSlot = $possibleStartSlots->random();

        // بناء مصفوفة الفترات المتتالية
        $selectedSlots = [$startSlot->id];

        // إضافة الفترات التالية حسب العدد المطلوب
        for ($i = 0; $i < ($slotsNeeded - 1); $i++) {
            if (isset($this->consecutiveTimeslotsMap[$startSlot->id][$i])) {
                $selectedSlots[] = $this->consecutiveTimeslotsMap[$startSlot->id][$i];
            }
        }

        // تأكد أن العدد صحيح
        if (count($selectedSlots) < $slotsNeeded) {
            // إذا لم نحصل على العدد الكافي، أضف فترات عشوائية إضافية
            $additionalSlots = $this->timeslots->whereNotIn('id', $selectedSlots)
                ->random($slotsNeeded - count($selectedSlots))
                ->pluck('id')
                ->toArray();
            $selectedSlots = array_merge($selectedSlots, $additionalSlots);
        }

        return array_slice($selectedSlots, 0, $slotsNeeded);
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
            $isPracticalRoom = Str::contains(strtolower(optional($gene->room->roomType)->room_type_name), ['lab', 'مختبر']);

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
            if (!optional($gene->instructor)->canTeach(optional(optional($gene->section)->planSubject)->subject)) {
                $penalty += 1;
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
}
