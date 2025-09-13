<?php

///////////////////////////////////// نسخة لإكمال الخوارزمية من population موجود //////////////////////////////////////////////

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

class ContinueEvolutionService
{
    // --- الإعدادات والبيانات المحملة ---
    private array $settings;
    private Population $populationRun;
    private Population $parentPopulation;
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
    public function __construct(array $settings, Population $populationRun, Population $parentPopulation)
    {
        $this->settings = $settings;
        $this->populationRun = $populationRun;
        $this->parentPopulation = $parentPopulation;
        Log::info("Continue Evolution Service initialized for Run ID: {$this->populationRun->population_id}, Parent ID: {$this->parentPopulation->population_id}");
    }

    /**
     * الدالة الرئيسية - تكمل من population موجود
     */
    public function continueFromParent()
    {
        try {
            $this->populationRun->update(['status' => 'running', 'start_time' => now()]);
            $this->loadAndPrepareData();

            // **نسخ الكروموسومات من الأب كنقطة بداية**
            $currentGenerationNumber = 1;
            $currentPopulation = $this->copyParentChromosomes($currentGenerationNumber);

            // حفظ صفوة الجيل الأول (المنسوخ من الأب)
            $this->updateEliteChromosomes($currentPopulation, $currentGenerationNumber);

            Log::info("Starting evolution from Parent Population. Generation #{$currentGenerationNumber} copied and evaluated.");

            // $maxGenerations = $this->settings['max_generations'];
            $maxGenerations = 200;
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
                // تحديث صفوة الجيل الجديد
                $this->updateEliteChromosomes($currentPopulation, $currentGenerationNumber);

                Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
            }

            $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
            $this->populationRun->update([
                'status' => 'completed',
                'end_time' => now(),
                'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
            ]);
            Log::info("Continue Evolution Run ID: {$this->populationRun->population_id} completed successfully.");
        } catch (Exception $e) {
            Log::error("Continue Evolution Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString());
            $this->populationRun->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * نسخ الكروموسومات من الـ Population الأب
     */
    private function copyParentChromosomes(int $generationNumber): Collection
    {
        Log::info("Copying chromosomes from parent population #{$this->parentPopulation->population_id}");

        // جلب كروموسومات الأب
        $parentChromosomes = $this->parentPopulation->chromosomes()->with('genes')->get();

        if ($parentChromosomes->isEmpty()) {
            throw new Exception("Parent population has no chromosomes to continue from.");
        }

        $copiedChromosomes = collect();

        foreach ($parentChromosomes as $parentChromosome) {
            // إنشاء نسخة من الكروموسوم
            $newChromosome = Chromosome::create([
                'population_id' => $this->populationRun->population_id,
                'penalty_value' => $parentChromosome->penalty_value,
                'generation_number' => $generationNumber,
                'fitness_value' => $parentChromosome->fitness_value,
                // نسخ كل قيم penalties الفردية
                'student_conflict_penalty' => $parentChromosome->student_conflict_penalty ?? 0,
                'teacher_conflict_penalty' => $parentChromosome->teacher_conflict_penalty ?? 0,
                'room_conflict_penalty' => $parentChromosome->room_conflict_penalty ?? 0,
                'capacity_conflict_penalty' => $parentChromosome->capacity_conflict_penalty ?? 0,
                'room_type_conflict_penalty' => $parentChromosome->room_type_conflict_penalty ?? 0,
                'teacher_eligibility_conflict_penalty' => $parentChromosome->teacher_eligibility_conflict_penalty ?? 0,
            ]);

            // نسخ جميع الجينات
            $genesToInsert = [];
            foreach ($parentChromosome->genes as $gene) {
                $genesToInsert[] = [
                    'chromosome_id' => $newChromosome->chromosome_id,
                    'lecture_unique_id' => $gene->lecture_unique_id,
                    'section_id' => $gene->section_id,
                    'instructor_id' => $gene->instructor_id,
                    'room_id' => $gene->room_id,
                    'timeslot_ids' => is_string($gene->timeslot_ids) ? $gene->timeslot_ids : json_encode($gene->timeslot_ids),
                    'student_group_id' => is_string($gene->student_group_id) ? $gene->student_group_id : json_encode($gene->student_group_id),
                    'block_type' => $gene->block_type,
                    'block_duration' => $gene->block_duration,
                ];
            }

            if (!empty($genesToInsert)) {
                Gene::insert($genesToInsert);
            }

            $copiedChromosomes->push($newChromosome);
        }

        Log::info("Copied " . $copiedChromosomes->count() . " chromosomes from parent population");
        return $copiedChromosomes;
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

        Log::info("Elite chromosomes updated for Generation #{$generationNumber}: " . implode(', ', $currentEliteIds));
        Log::info("Elite history now contains " . count($currentEliteHistory) . " generations");
    }

    //======================================================================
    // المرحلة الأولى: تحميل وتحضير البيانات (نفس الكود الأصلي)
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

        if ($this->lectureBlocksToSchedule->isEmpty()) {
            throw new Exception("No lecture blocks to schedule were found after processing credit hours.");
        }

        Log::info("Data loaded and precomputed: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
    }

    /**
     * [الدالة المحورية الجديدة] - نفس الكود الأصلي
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
     * [مصححة] - نفس الكود الأصلي
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
     * [مصححة] - نفس الكود الأصلي
     */
    private function splitPracticalBlocks(Section $section, Instructor $instructor, array &$instructorLoad)
    {
        $totalSlotsNeeded = $section->planSubject->subject->practical_hours * ($this->settings['practical_credit_to_slots'] ?? 1);
        if ($totalSlotsNeeded <= 0) return;

        $remainingSlots = $totalSlotsNeeded;
        $blockCounter = 1;

        // استراتيجية التقسيم للبلوكات العملية
        while ($remainingSlots > 0) {
            // تحديد حجم البلوك حسب العدد المتبقي
            if ($remainingSlots >= 4) {
                $slotsForThisBlock = 4;
            } elseif ($remainingSlots >= 3) {
                $slotsForThisBlock = 3;
            } elseif ($remainingSlots >= 2) {
                $slotsForThisBlock = 2;
            } else {
                $slotsForThisBlock = 1;
            }

            $uniqueId = "{$section->id}-practical-block{$blockCounter}";
            $this->lectureBlocksToSchedule->push((object)[
                'unique_id' => $uniqueId,
                'section' => $section,
                'instructor_id' => $instructor->id,
                'student_group_id' => $this->studentGroupMap[$section->id] ?? [],
                'block_type' => 'practical',
                'slots_needed' => $slotsForThisBlock,
                'block_duration' => $slotsForThisBlock * 50,
            ]);

            $instructorLoad[$instructor->id] += $slotsForThisBlock;
            $remainingSlots -= $slotsForThisBlock;
            $blockCounter++;
        }
    }

    // باقي الدوال المساعدة (نفس الكود الأصلي)
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
    // باقي دوال الخوارزمية (نفس الكود الأصلي تماماً)
    //======================================================================

    private function selectParents(Collection $population): array
    {
        $selectionSlug = $this->loadedSelectionTypes->find($this->settings['selection_type_id'])->slug ?? 'tournament_selection';

        switch ($selectionSlug) {
            case 'tournament_selection':
                return $this->tournamentSelection($population);
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
        Log::info("Creating new generation #{$nextGenerationNumber} using Elitism + Hybrid approach");
        $newPopulation = [];
        $parentPool = array_filter($parents);

        if (empty($parentPool)) {
            Log::warning("Parent pool is empty for generation {$nextGenerationNumber}. Cannot create new generation.");
            return collect();
        }

        $currentPopulation = collect($parentPool);
        $elitismCount = $this->populationRun->elitism_count ?? 5;

        // **الخطوة الأولى: نسخ الصفوة للجيل الجديد**
        $eliteChromosomes = $this->copyEliteChromosomes($currentPopulation, $elitismCount, $nextGenerationNumber, $populationRun);

        // **الخطوة الثانية: إنتاج باقي الكروموسومات الجديدة**
        $remainingSlots = $this->settings['population_size'] - count($eliteChromosomes);

        if ($remainingSlots <= 0) {
            Log::info("Elite chromosomes filled the entire population");
            return collect($eliteChromosomes);
        }

        // **(التحسين الرئيسي)**: جلب جينات كل الآباء المحتملين مرة واحدة فقط
        $parentIds = $currentPopulation->pluck('chromosome_id')->unique();
        $allParentGenes = Gene::whereIn('chromosome_id', $parentIds)->get()->groupBy('chromosome_id');

        // **نحتفظ بنفس المنطق الهجين:** نمر على الآباء بالترتيب
        $addedChildren = 0;
        foreach ($currentPopulation as $parent1) {
            if ($addedChildren >= $remainingSlots) break;

            // --- اختيار الأب الثاني (سريع جداً الآن) ---
            $tournamentSize = $this->settings['selection_size'] ?? 5;
            $participants = $currentPopulation->random(min($tournamentSize, $currentPopulation->count()));
            $parent2 = $participants->sortByDesc('fitness_value')->first();

            // نحصل على جيناتهم من المجموعة التي جلبناها مسبقاً
            $p1Genes = $allParentGenes->get($parent1->chromosome_id, collect());
            $p2Genes = $allParentGenes->get($parent2->chromosome_id, collect());

            if ($p1Genes->isEmpty() || $p2Genes->isEmpty()) continue;

            $childGenesCollection = collect();
            // التزاوج
            if (lcg_value() < ($this->settings['crossover_rate'] ?? 0.95)) {
                $childGenesCollection = $this->performCrossover($p1Genes, $p2Genes);
            } else {
                // الابن نسخة من الأب الأفضل
                $childGenesCollection = ($parent1->fitness_value >= $parent2->fitness_value) ? $p1Genes : $p2Genes;
            }

            // **تحويل الـ Collection إلى array قبل تمريرها**
            $mutatedChildGenes = $this->performMutation($childGenesCollection->all());

            // حفظ الابن الجديد
            $newPopulation[] = $this->saveChildChromosome($mutatedChildGenes, $nextGenerationNumber, $populationRun);
            $addedChildren++;
        }

        // دمج الصفوة مع الكروموسومات الجديدة
        $finalPopulation = array_merge($eliteChromosomes, $newPopulation);

        Log::info("Generation {$nextGenerationNumber} created with " . count($finalPopulation) . " chromosomes (including " . count($eliteChromosomes) . " elites)");

        return collect($finalPopulation);
    }

    // باقي دوال الخوارزمية (نفس الكود الأصلي) - يمكن نسخها من GeneticAlgorithmService
    // للاختصار، سأضع فقط الدوال الأساسية...

    private function copyEliteChromosomes(Collection $population, int $elitismCount, int $newGenerationNumber, Population $populationRun): array
    {
        $copiedElites = [];
        $eliteChromosomes = $population->sortByDesc('fitness_value')->take($elitismCount);

        foreach ($eliteChromosomes as $eliteChromosome) {
            $newEliteChromosome = Chromosome::create([
                'population_id' => $populationRun->population_id,
                'penalty_value' => $eliteChromosome->penalty_value,
                'generation_number' => $newGenerationNumber,
                'fitness_value' => $eliteChromosome->fitness_value,
                'student_conflict_penalty' => $eliteChromosome->student_conflict_penalty ?? 0,
                'teacher_conflict_penalty' => $eliteChromosome->teacher_conflict_penalty ?? 0,
                'room_conflict_penalty' => $eliteChromosome->room_conflict_penalty ?? 0,
                'capacity_conflict_penalty' => $eliteChromosome->capacity_conflict_penalty ?? 0,
                'room_type_conflict_penalty' => $eliteChromosome->room_type_conflict_penalty ?? 0,
                'teacher_eligibility_conflict_penalty' => $eliteChromosome->teacher_eligibility_conflict_penalty ?? 0,
            ]);

            $originalGenes = Gene::where('chromosome_id', $eliteChromosome->chromosome_id)->get();
            $genesToInsert = [];

            foreach ($originalGenes as $gene) {
                $genesToInsert[] = [
                    'chromosome_id' => $newEliteChromosome->chromosome_id,
                    'lecture_unique_id' => $gene->lecture_unique_id,
                    'section_id' => $gene->section_id,
                    'instructor_id' => $gene->instructor_id,
                    'room_id' => $gene->room_id,
                    'timeslot_ids' => is_string($gene['timeslot_ids']) ? $gene['timeslot_ids'] : json_encode($gene['timeslot_ids']),
                    'student_group_id' => is_string($gene['student_group_id']) ? $gene['student_group_id'] : json_encode($gene['student_group_id']),
                    'block_type' => $gene->block_type,
                    'block_duration' => $gene->block_duration,
                ];
            }

            if (!empty($genesToInsert)) {
                Gene::insert($genesToInsert);
            }
            $copiedElites[] = $newEliteChromosome;
        }

        Log::info("Copied " . count($copiedElites) . " elite chromosomes to generation {$newGenerationNumber}");
        return $copiedElites;
    }

    private function performCrossover(Collection $p1Genes, Collection $p2Genes): Collection
    {
        $crossoverSlug = $this->loadedCrossoverTypes->find($this->settings['crossover_type_id'])->slug ?? 'single_point';

        switch ($crossoverSlug) {
            case 'single_point':
                return $this->singlePointCrossover($p1Genes, $p2Genes);
            default:
                return $this->singlePointCrossover($p1Genes, $p2Genes);
        }
    }

    private function singlePointCrossover(Collection $p1Genes, Collection $p2Genes): Collection
    {
        $p1GenesByKey = $p1Genes->keyBy('lecture_unique_id');
        $p2GenesByKey = $p2Genes->keyBy('lecture_unique_id');
        $childGenes = collect();

        $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
        $currentIndex = 0;

        foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
            $source = ($currentIndex < $crossoverPoint) ? $p1GenesByKey : $p2GenesByKey;
            $fallbackSource = ($currentIndex < $crossoverPoint) ? $p2GenesByKey : $p1GenesByKey;

            $gene = $source->get($lectureBlock->unique_id) ?? $fallbackSource->get($lectureBlock->unique_id);
            if ($gene) {
                $modifiedGene = $this->applyRandomCrossoverChange(clone $gene, $lectureBlock);
                $childGenes->push($modifiedGene);
            }
            $currentIndex++;
        }
        return $childGenes;
    }

    private function applyRandomCrossoverChange($gene, $lectureBlock)
    {
        // 30% احتمال عدم التغيير (الحفاظ على الجين كما هو)
        if (lcg_value() < 0.3) {
            return $gene;
        }

        // اختيار نوع التغيير عشوائياً
        $changeType = rand(1, 3);

        switch ($changeType) {
            case 1: // تغيير القاعة فقط
                $newRoom = $this->getRandomRoomForBlock($lectureBlock);
                $gene->room_id = $newRoom->id;
                break;

            case 2: // تغيير الوقت فقط
                $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
                $gene->timeslot_ids = $newTimeslots;
                break;

            case 3: // تغيير القاعة والوقت معاً
                $newRoom = $this->getRandomRoomForBlock($lectureBlock);
                $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
                $gene->room_id = $newRoom->id;
                $gene->timeslot_ids = $newTimeslots;
                break;
        }

        return $gene;
    }

    private function performMutation(array $genes): array
    {
        if (lcg_value() < $this->settings['mutation_rate'] && !empty($genes)) {
            $mutationSlug = $this->loadedMutationTypes->find($this->settings['mutation_type_id'])->slug ?? 'smart_swap';

            switch ($mutationSlug) {
                case 'smart_swap':
                    return $this->smartSwapMutation($genes);
                default:
                    return $this->smartSwapMutation($genes);
            }
        }
        return $genes;
    }

    private function smartSwapMutation(array $genes): array
    {
        if (empty($genes)) return [];

        // **الخطوة الأولى: حساب conflicts لكل جين**
        $geneConflictScores = [];

        foreach ($genes as $index => $gene) {
            if (!$gene) {
                $geneConflictScores[$index] = 0;
                continue;
            }

            $conflicts = 0;
            $otherGenes = array_filter($genes, fn($g, $i) => $g && $i !== $index, ARRAY_FILTER_USE_BOTH);
            $conflicts += $this->calculateGeneConflicts($gene, $otherGenes);
            $conflicts += $this->calculateStructuralPenalties($gene);

            $geneConflictScores[$index] = $conflicts;
        }

        // **الخطوة الثانية: اختيار الجين الأكثر تعارضاً للطفرة**
        $targetGeneIndex = null;
        if (!empty($geneConflictScores)) {
            if (lcg_value() < 0.7) {
                $targetGeneIndex = array_keys($geneConflictScores, max($geneConflictScores))[0];
            } else {
                arsort($geneConflictScores);
                $topWorst = array_slice($geneConflictScores, 0, 3, true);
                $targetGeneIndex = array_rand($topWorst);
            }
        }

        if ($targetGeneIndex === null) {
            $targetGeneIndex = array_rand($genes);
        }

        $geneToMutate = $genes[$targetGeneIndex];
        $lectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $geneToMutate->lecture_unique_id);
        if (!$lectureBlock) {
            return $genes;
        }

        $currentConflicts = $this->calculateGeneConflicts($geneToMutate, array_filter($genes, fn($g, $i) => $g && $i !== $targetGeneIndex, ARRAY_FILTER_USE_BOTH));

        // 1. محاولة تحسين القاعة فقط
        if ($this->tryImproveRoom($geneToMutate, $lectureBlock, $genes, $targetGeneIndex, $currentConflicts)) {
            $genes[$targetGeneIndex] = $geneToMutate;
            return $genes;
        }

        // 2. محاولة تحسين الوقت فقط
        if ($this->tryImproveTimeslot($geneToMutate, $lectureBlock, $genes, $targetGeneIndex, $currentConflicts)) {
            $genes[$targetGeneIndex] = $geneToMutate;
            return $genes;
        }

        // 3. محاولة تحسين كليهما
        if ($this->tryImproveBoth($geneToMutate, $lectureBlock, $genes, $targetGeneIndex, $currentConflicts)) {
            $genes[$targetGeneIndex] = $geneToMutate;
            return $genes;
        }

        // 4. الحل الأخير: إذا فشلت المحاولات، نغير الاثنين معاً
        $changeType = rand(1, 3);
        switch ($changeType) {
            case 1:
                $newRoom = $this->getRandomRoomForBlock($lectureBlock);
                $geneToMutate->room_id = $newRoom->id;
                break;
            case 2:
                $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
                $geneToMutate->timeslot_ids = $newTimeslots;
                break;
            case 3:
                $newRoom = $this->getRandomRoomForBlock($lectureBlock);
                $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
                $geneToMutate->room_id = $newRoom->id;
                $geneToMutate->timeslot_ids = $newTimeslots;
                break;
        }

        $genes[$targetGeneIndex] = $geneToMutate;
        return $genes;
    }

    private function tryImproveRoom($geneToMutate, $lectureBlock, array $genes, int $geneIndex, int $currentConflicts): bool
    {
        $roomCandidates = ($lectureBlock->block_type === 'practical') ? $this->practicalRooms : $this->theoryRooms;
        $attempts = min(5, $roomCandidates->count());

        for ($i = 0; $i < $attempts; $i++) {
            $newRoom = $roomCandidates->random();
            $tempGene = clone $geneToMutate;
            $tempGene->room_id = $newRoom->id;

            $newConflicts = $this->calculateGeneConflicts($tempGene, array_filter($genes, fn($g, $i) => $g && $i !== $geneIndex, ARRAY_FILTER_USE_BOTH));

            if ($newConflicts < $currentConflicts) {
                $geneToMutate->room_id = $newRoom->id;
                return true;
            }
        }
        return false;
    }

    private function tryImproveTimeslot($geneToMutate, $lectureBlock, array $genes, int $geneIndex, int $currentConflicts): bool
    {
        $attempts = 5;

        for ($i = 0; $i < $attempts; $i++) {
            $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
            $tempGene = clone $geneToMutate;
            $tempGene->timeslot_ids = $newTimeslots;

            $newConflicts = $this->calculateGeneConflicts($tempGene, array_filter($genes, fn($g, $i) => $g && $i !== $geneIndex, ARRAY_FILTER_USE_BOTH));

            if ($newConflicts < $currentConflicts) {
                $geneToMutate->timeslot_ids = $newTimeslots;
                return true;
            }
        }
        return false;
    }

    private function tryImproveBoth($geneToMutate, $lectureBlock, array $genes, int $geneIndex, int $currentConflicts): bool
    {
        $roomCandidates = ($lectureBlock->block_type === 'practical') ? $this->practicalRooms : $this->theoryRooms;
        $attempts = 3;

        for ($i = 0; $i < $attempts; $i++) {
            $newRoom = $roomCandidates->random();
            $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);

            $tempGene = clone $geneToMutate;
            $tempGene->room_id = $newRoom->id;
            $tempGene->timeslot_ids = $newTimeslots;

            $newConflicts = $this->calculateGeneConflicts($tempGene, array_filter($genes, fn($g, $i) => $g && $i !== $geneIndex, ARRAY_FILTER_USE_BOTH));

            if ($newConflicts < $currentConflicts) {
                $geneToMutate->room_id = $newRoom->id;
                $geneToMutate->timeslot_ids = $newTimeslots;
                return true;
            }
        }
        return false;
    }

    private function calculateStructuralPenalties($gene): int
    {
        $penalties = 0;

        if ($gene->section && $gene->room && $gene->section->student_count > $gene->room->room_size) {
            $penalties += 1;
        }

        $isPracticalBlock = Str::contains($gene->lecture_unique_id, 'practical');
        $isPracticalRoom = $gene->room && Str::contains(strtolower(optional($gene->room->roomType)->room_type_name), ['lab', 'مختبر']);

        if ($isPracticalBlock && !$isPracticalRoom) {
            $penalties += 1;
        }
        if (!$isPracticalBlock && $isPracticalRoom) {
            $penalties += 1;
        }

        return $penalties;
    }

    private function calculateGeneConflicts($targetGene, array $otherGenes): int
    {
        $conflicts = 0;
        $studentGroupIds = $targetGene->student_group_id ?? [];

        foreach ($targetGene->timeslot_ids as $timeslotId) {
            foreach ($otherGenes as $otherGene) {
                if (!$otherGene) continue;

                if (in_array($timeslotId, $otherGene->timeslot_ids)) {
                    if ($otherGene->instructor_id == $targetGene->instructor_id) {
                        $conflicts += 100;
                    }
                    if ($otherGene->room_id == $targetGene->room_id) {
                        $conflicts += 80;
                    }
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

        $possibleStartSlots = $this->timeslots->filter(function ($slot) use ($slotsNeeded) {
            return isset($this->consecutiveTimeslotsMap[$slot->id]) &&
                (count($this->consecutiveTimeslotsMap[$slot->id]) + 1) >= $slotsNeeded;
        });

        if ($possibleStartSlots->isEmpty()) {
            return $this->timeslots->random($slotsNeeded)->pluck('id')->toArray();
        }

        $startSlot = $possibleStartSlots->random();
        $selectedSlots = [$startSlot->id];

        for ($i = 0; $i < ($slotsNeeded - 1); $i++) {
            if (isset($this->consecutiveTimeslotsMap[$startSlot->id][$i])) {
                $selectedSlots[] = $this->consecutiveTimeslotsMap[$startSlot->id][$i];
            }
        }

        if (count($selectedSlots) < $slotsNeeded) {
            $additionalSlots = $this->timeslots->whereNotIn('id', $selectedSlots)
                ->random($slotsNeeded - count($selectedSlots))
                ->pluck('id')
                ->toArray();
            $selectedSlots = array_merge($selectedSlots, $additionalSlots);
        }

        return array_slice($selectedSlots, 0, $slotsNeeded);
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

    // دوال التقييم (نفس الكود من InitialPopulationService)
    private function evaluateFitness(Collection $chromosomes)
    {
        $chromosomeIds = $chromosomes->pluck('chromosome_id')->filter();
        if ($chromosomeIds->isEmpty()) {
            return;
        }

        $allGenesOfGeneration = Gene::whereIn('chromosome_id', $chromosomeIds)
            ->with(['section.planSubject.subject', 'room.roomType', 'instructor'])
            ->get()
            ->groupBy('chromosome_id');

        DB::transaction(function () use ($chromosomes, $allGenesOfGeneration) {
            foreach ($chromosomes as $chromosome) {
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
