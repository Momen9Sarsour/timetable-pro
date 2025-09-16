<?php

///////////////////////////////////// نسخة In-Memory Evolution - بدون عمليات DB متكررة //////////////////////////////////////////////

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

    // (جديد) متغيرات للعمل في الذاكرة
    private array $memoryCurrentGeneration = [];
    private array $eliteHistoryInMemory = [];
    private array $bestChromosomeHistory = [];

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
     * الدالة الرئيسية المحسنة - تعمل كلها في الذاكرة
     */
    public function continueFromParent()
    {
        try {
            $this->populationRun->update(['status' => 'running', 'start_time' => now()]);
            Log::info("213223 Starting in-memory evolution for Population ID: {$this->populationRun->population_id}");

            // **الخطوة 1: تحميل وتحضير البيانات الأساسية**
            Log::info("Step 1: Loading and preparing basic data");
            $this->loadAndPrepareData();

            // **الخطوة 2: تحميل population الأب إلى الذاكرة**
            Log::info("Step 2: Loading parent population to memory");
            $this->loadParentPopulationToMemory();

            // **الخطوة 3: تشغيل الخوارزمية في الذاكرة**
            Log::info("Step 3: Running evolution algorithm in memory");
            $this->runInMemoryEvolution();

            // **الخطوة 4: حفظ النتيجة النهائية في قاعدة البيانات**
            Log::info("Step 4: Saving final results to database");
            $this->saveFinalResultToDatabase();

            $this->populationRun->update([
                'status' => 'completed',
                'end_time' => now()
            ]);

            Log::info("In-Memory Evolution completed successfully for Run ID: {$this->populationRun->population_id}");

        } catch (Exception $e) {
            Log::error("In-Memory Evolution failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString());
            $this->populationRun->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * [دالة جديدة] تحميل population الأب إلى الذاكرة
     */
    private function loadParentPopulationToMemory()
    {
        Log::info("Loading parent population #{$this->parentPopulation->population_id} chromosomes to memory");

        // جلب كروموسومات الأب مع جيناتها
        $parentChromosomes = $this->parentPopulation->chromosomes()->with('genes')->get();

        if ($parentChromosomes->isEmpty()) {
            throw new Exception("Parent population has no chromosomes to continue from.");
        }

        // تحويل البيانات إلى صيغة الذاكرة
        $this->memoryCurrentGeneration = [];
        
        foreach ($parentChromosomes as $parentChromosome) {
            $chromosomeData = [
                'chromosome_data' => [
                    'penalty_value' => $parentChromosome->penalty_value,
                    'fitness_value' => $parentChromosome->fitness_value,
                    'student_conflict_penalty' => $parentChromosome->student_conflict_penalty ?? 0,
                    'teacher_conflict_penalty' => $parentChromosome->teacher_conflict_penalty ?? 0,
                    'room_conflict_penalty' => $parentChromosome->room_conflict_penalty ?? 0,
                    'capacity_conflict_penalty' => $parentChromosome->capacity_conflict_penalty ?? 0,
                    'room_type_conflict_penalty' => $parentChromosome->room_type_conflict_penalty ?? 0,
                    'teacher_eligibility_conflict_penalty' => $parentChromosome->teacher_eligibility_conflict_penalty ?? 0,
                ],
                'genes_data' => []
            ];

            // تحويل الجينات
            foreach ($parentChromosome->genes as $gene) {
                $chromosomeData['genes_data'][] = [
                    'lecture_unique_id' => $gene->lecture_unique_id,
                    'section_id' => $gene->section_id,
                    'instructor_id' => $gene->instructor_id,
                    'room_id' => $gene->room_id,
                    'timeslot_ids' => is_string($gene->timeslot_ids) ? json_decode($gene->timeslot_ids, true) : $gene->timeslot_ids,
                    'student_group_id' => is_string($gene->student_group_id) ? json_decode($gene->student_group_id, true) : $gene->student_group_id,
                    'block_type' => $gene->block_type,
                    'block_duration' => $gene->block_duration,
                ];
            }

            $this->memoryCurrentGeneration[] = $chromosomeData;
        }

        Log::info("Loaded " . count($this->memoryCurrentGeneration) . " parent chromosomes to memory");

        // حفظ Elite الجيل الأول
        $this->updateEliteHistoryInMemory(1);
    }

    /**
     * [دالة جديدة] تشغيل الخوارزمية كاملة في الذاكرة
     */
    private function runInMemoryEvolution()
    {
        $maxGenerations = $this->settings['max_generations'] ?? 10;
        $currentGenerationNumber = 1; // بدأنا من الجيل الأول (الأب)

        Log::info("Starting in-memory evolution loop. Max generations: {$maxGenerations}");

        while ($currentGenerationNumber < $maxGenerations) {
            // فحص إيجاد الحل الأمثل
            $bestInGeneration = $this->findBestChromosomeInMemory();
            if ($bestInGeneration && $bestInGeneration['chromosome_data']['penalty_value'] == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
                Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping evolution.");
                break;
            }

            // اختيار الآباء
            Log::info("Generation #{$currentGenerationNumber}: Selecting parents for next generation");
            $selectedParents = $this->selectParentsFromMemory();

            // إنشاء الجيل الجديد
            $nextGenerationNumber = $currentGenerationNumber + 1;
            Log::info("Generation #{$nextGenerationNumber}: Creating new generation in memory");
            
            $this->createNextGenerationInMemory($selectedParents, $nextGenerationNumber);
            
            // تقييم الجيل الجديد
            Log::info("Generation #{$nextGenerationNumber}: Evaluating fitness in memory");
            $this->evaluateFitnessInMemory();

            // حفظ Elite الجيل الجديد
            $this->updateEliteHistoryInMemory($nextGenerationNumber);

            // حفظ أفضل كروموسوم
            $this->updateBestChromosomeHistoryInMemory($nextGenerationNumber);

            $currentGenerationNumber = $nextGenerationNumber;
            Log::info("Generation #{$currentGenerationNumber} completed successfully in memory");
        }

        Log::info("In-memory evolution completed. Final generation: #{$currentGenerationNumber}");
    }

    /**
     * [دالة جديدة] اختيار الآباء من الذاكرة
     */
    private function selectParentsFromMemory(): array
    {
        // تحويل بيانات الذاكرة إلى Collection للتوافق مع الدوال الموجودة
        $memoryCollection = collect();
        
        foreach ($this->memoryCurrentGeneration as $chromosomeData) {
            $chromosomeObj = (object) $chromosomeData['chromosome_data'];
            $memoryCollection->push($chromosomeObj);
        }

        // استخدام نفس منطق الاختيار الموجود
        return $this->selectParents($memoryCollection);
    }

    /**
     * [دالة جديدة] إنشاء الجيل الجديد في الذاكرة
     */
    private function createNextGenerationInMemory(array $selectedParents, int $nextGenerationNumber)
    {
        $elitismCount = $this->populationRun->elitism_count ?? 5;
        $newGenerationInMemory = [];

        // **الخطوة 1: حفظ Elite (أفضل الكروموسومات)**
        Log::info("Step 1: Preserving Elite ({$elitismCount}) chromosomes in memory");
        
        // ترتيب الجيل الحالي حسب fitness وأخذ الأفضل
        usort($this->memoryCurrentGeneration, function ($a, $b) {
            return $b['chromosome_data']['fitness_value'] <=> $a['chromosome_data']['fitness_value'];
        });

        for ($i = 0; $i < $elitismCount && $i < count($this->memoryCurrentGeneration); $i++) {
            $newGenerationInMemory[] = $this->memoryCurrentGeneration[$i]; // نسخ Elite كما هم
        }

        // **الخطوة 2: إنشاء أطفال جدد من Crossover/Mutation**
        $remainingSlots = $this->settings['population_size'] - count($newGenerationInMemory);
        Log::info("Step 2: Creating {$remainingSlots} new children through crossover/mutation");

        $addedChildren = 0;
        while ($addedChildren < $remainingSlots && !empty($selectedParents)) {
            // اختيار أبوين عشوائيين
            $parent1 = $selectedParents[array_rand($selectedParents)];
            $parent2 = $selectedParents[array_rand($selectedParents)];

            // العثور على جينات الآباء في الذاكرة
            $parent1Genes = $this->findParentGenesInMemory($parent1);
            $parent2Genes = $this->findParentGenesInMemory($parent2);

            if (empty($parent1Genes) || empty($parent2Genes)) continue;

            // إجراء التزاوج
            $childGenes = [];
            if (lcg_value() < ($this->settings['crossover_rate'] ?? 0.95)) {
                $childGenes = $this->performCrossoverInMemory($parent1Genes, $parent2Genes);
            } else {
                $childGenes = (rand(0, 1) === 0) ? $parent1Genes : $parent2Genes;
            }

            // إجراء الطفرة
            $mutatedChildGenes = $this->performMutationInMemory($childGenes);

            // إنشاء كروموسوم جديد في الذاكرة
            $newChildChromosome = [
                'chromosome_data' => [
                    'penalty_value' => -1, // سيتم حسابه في التقييم
                    'fitness_value' => 0,
                    'student_conflict_penalty' => 0,
                    'teacher_conflict_penalty' => 0,
                    'room_conflict_penalty' => 0,
                    'capacity_conflict_penalty' => 0,
                    'room_type_conflict_penalty' => 0,
                    'teacher_eligibility_conflict_penalty' => 0,
                ],
                'genes_data' => $mutatedChildGenes
            ];

            $newGenerationInMemory[] = $newChildChromosome;
            $addedChildren++;
        }

        // استبدال الجيل الحالي بالجديد في الذاكرة
        $this->memoryCurrentGeneration = $newGenerationInMemory;
        Log::info("New generation #{$nextGenerationNumber} created in memory. Total chromosomes: " . count($this->memoryCurrentGeneration));
    }

    /**
     * [دالة جديدة] البحث عن جينات الأب في الذاكرة
     */
    private function findParentGenesInMemory($parentChromosome): array
    {
        foreach ($this->memoryCurrentGeneration as $chromosomeInMemory) {
            // نقارن fitness_value للعثور على نفس الكروموسوم
            if (abs($chromosomeInMemory['chromosome_data']['fitness_value'] - $parentChromosome->fitness_value) < 0.0001) {
                return $chromosomeInMemory['genes_data'];
            }
        }
        
        // إذا لم نجده، نعيد أول واحد متوفر
        return !empty($this->memoryCurrentGeneration) ? $this->memoryCurrentGeneration[0]['genes_data'] : [];
    }

    /**
     * [دالة جديدة] تزاوج في الذاكرة
     */
    private function performCrossoverInMemory(array $parent1Genes, array $parent2Genes): array
    {
        $crossoverSlug = $this->loadedCrossoverTypes->find($this->settings['crossover_type_id'])->slug ?? 'single_point';

        switch ($crossoverSlug) {
            case 'single_point':
                return $this->singlePointCrossoverInMemory($parent1Genes, $parent2Genes);
            default:
                return $this->singlePointCrossoverInMemory($parent1Genes, $parent2Genes);
        }
    }

    /**
     * [دالة جديدة] تزاوج نقطة واحدة في الذاكرة
     */
    private function singlePointCrossoverInMemory(array $parent1Genes, array $parent2Genes): array
    {
        // تنظيم الجينات حسب lecture_unique_id
        $p1GenesByKey = [];
        $p2GenesByKey = [];
        
        foreach ($parent1Genes as $gene) {
            $p1GenesByKey[$gene['lecture_unique_id']] = $gene;
        }
        
        foreach ($parent2Genes as $gene) {
            $p2GenesByKey[$gene['lecture_unique_id']] = $gene;
        }

        $childGenes = [];
        $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
        $currentIndex = 0;

        foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
            $source = ($currentIndex < $crossoverPoint) ? $p1GenesByKey : $p2GenesByKey;
            $fallbackSource = ($currentIndex < $crossoverPoint) ? $p2GenesByKey : $p1GenesByKey;

            $gene = $source[$lectureBlock->unique_id] ?? $fallbackSource[$lectureBlock->unique_id] ?? null;
            
            if ($gene) {
                // تطبيق تغيير عشوائي
                $modifiedGene = $this->applyRandomCrossoverChangeInMemory($gene, $lectureBlock);
                $childGenes[] = $modifiedGene;
            }
            
            $currentIndex++;
        }

        return $childGenes;
    }

    /**
     * [دالة جديدة] تطبيق تغيير عشوائي في التزاوج - في الذاكرة
     */
    private function applyRandomCrossoverChangeInMemory(array $gene, $lectureBlock): array
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
                $gene['room_id'] = $newRoom->id;
                break;

            case 2: // تغيير الوقت فقط
                $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
                $gene['timeslot_ids'] = $newTimeslots;
                break;

            case 3: // تغيير القاعة والوقت معاً
                $newRoom = $this->getRandomRoomForBlock($lectureBlock);
                $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
                $gene['room_id'] = $newRoom->id;
                $gene['timeslot_ids'] = $newTimeslots;
                break;
        }

        return $gene;
    }

    /**
     * [دالة جديدة] إجراء الطفرة في الذاكرة
     */
    private function performMutationInMemory(array $genes): array
    {
        if (lcg_value() < $this->settings['mutation_rate'] && !empty($genes)) {
            $mutationSlug = $this->loadedMutationTypes->find($this->settings['mutation_type_id'])->slug ?? 'smart_swap';

            switch ($mutationSlug) {
                case 'smart_swap':
                    return $this->smartSwapMutationInMemory($genes);
                default:
                    return $this->smartSwapMutationInMemory($genes);
            }
        }
        return $genes;
    }

    /**
     * [دالة جديدة] طفرة ذكية في الذاكرة
     */
    private function smartSwapMutationInMemory(array $genes): array
    {
        if (empty($genes)) return $genes;

        // حساب conflicts لكل جين
        $geneConflictScores = [];
        
        foreach ($genes as $index => $gene) {
            $conflicts = 0;
            $otherGenes = array_filter($genes, fn($g, $i) => $i !== $index, ARRAY_FILTER_USE_BOTH);
            $conflicts += $this->calculateGeneConflictsInMemory($gene, $otherGenes);
            $geneConflictScores[$index] = $conflicts;
        }

        // اختيار الجين الأكثر تعارضاً للطفرة
        $targetGeneIndex = null;
        if (!empty($geneConflictScores)) {
            if (lcg_value() < 0.7) {
                $maxConflicts = max($geneConflictScores);
                $targetGeneIndex = array_search($maxConflicts, $geneConflictScores);
            } else {
                arsort($geneConflictScores);
                $topWorst = array_slice($geneConflictScores, 0, 3, true);
                $targetGeneIndex = array_rand($topWorst);
            }
        }

        if ($targetGeneIndex === null || !isset($genes[$targetGeneIndex])) {
            $targetGeneIndex = array_rand($genes);
        }

        $geneToMutate = $genes[$targetGeneIndex];
        $lectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $geneToMutate['lecture_unique_id']);
        
        if (!$lectureBlock) {
            return $genes;
        }

        $currentConflicts = $this->calculateGeneConflictsInMemory($geneToMutate, array_filter($genes, fn($g, $i) => $i !== $targetGeneIndex, ARRAY_FILTER_USE_BOTH));

        // محاولة تحسين القاعة
        if ($this->tryImproveRoomInMemory($geneToMutate, $lectureBlock, $genes, $targetGeneIndex, $currentConflicts)) {
            $genes[$targetGeneIndex] = $geneToMutate;
            return $genes;
        }

        // محاولة تحسين الوقت
        if ($this->tryImproveTimeslotInMemory($geneToMutate, $lectureBlock, $genes, $targetGeneIndex, $currentConflicts)) {
            $genes[$targetGeneIndex] = $geneToMutate;
            return $genes;
        }

        // محاولة تحسين كليهما
        if ($this->tryImproveBothInMemory($geneToMutate, $lectureBlock, $genes, $targetGeneIndex, $currentConflicts)) {
            $genes[$targetGeneIndex] = $geneToMutate;
            return $genes;
        }

        // الحل الأخير: تغيير عشوائي
        $changeType = rand(1, 3);
        switch ($changeType) {
            case 1:
                $newRoom = $this->getRandomRoomForBlock($lectureBlock);
                $geneToMutate['room_id'] = $newRoom->id;
                break;
            case 2:
                $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
                $geneToMutate['timeslot_ids'] = $newTimeslots;
                break;
            case 3:
                $newRoom = $this->getRandomRoomForBlock($lectureBlock);
                $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
                $geneToMutate['room_id'] = $newRoom->id;
                $geneToMutate['timeslot_ids'] = $newTimeslots;
                break;
        }

        $genes[$targetGeneIndex] = $geneToMutate;
        return $genes;
    }

    /**
     * [دالة جديدة] حساب التعارضات في الذاكرة
     */
    private function calculateGeneConflictsInMemory(array $targetGene, array $otherGenes): int
    {
        $conflicts = 0;

        if (!isset($targetGene['timeslot_ids']) || !isset($targetGene['instructor_id']) || !isset($targetGene['room_id'])) {
            return 0;
        }

        $studentGroupIds = $targetGene['student_group_id'] ?? [];
        $targetTimeslots = is_array($targetGene['timeslot_ids']) ? $targetGene['timeslot_ids'] : json_decode($targetGene['timeslot_ids'], true) ?? [];

        foreach ($targetTimeslots as $timeslotId) {
            foreach ($otherGenes as $otherGene) {
                if (!$otherGene || !isset($otherGene['timeslot_ids']) || !isset($otherGene['instructor_id']) || !isset($otherGene['room_id'])) {
                    continue;
                }

                $otherTimeslots = is_array($otherGene['timeslot_ids']) ? $otherGene['timeslot_ids'] : json_decode($otherGene['timeslot_ids'], true) ?? [];

                if (in_array($timeslotId, $otherTimeslots)) {
                    if ($otherGene['instructor_id'] == $targetGene['instructor_id']) {
                        $conflicts += 100;
                    }
                    if ($otherGene['room_id'] == $targetGene['room_id']) {
                        $conflicts += 80;
                    }
                    $otherStudentGroupIds = $otherGene['student_group_id'] ?? [];
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

    /**
     * [دالة جديدة] محاولة تحسين القاعة في الذاكرة
     */
    private function tryImproveRoomInMemory(array &$geneToMutate, $lectureBlock, array $genes, int $geneIndex, int $currentConflicts): bool
    {
        $roomCandidates = ($lectureBlock->block_type === 'practical') ? $this->practicalRooms : $this->theoryRooms;
        $attempts = min(5, $roomCandidates->count());

        for ($i = 0; $i < $attempts; $i++) {
            $newRoom = $roomCandidates->random();
            $tempGene = $geneToMutate;
            $tempGene['room_id'] = $newRoom->id;

            $newConflicts = $this->calculateGeneConflictsInMemory($tempGene, array_filter($genes, fn($g, $i) => $i !== $geneIndex, ARRAY_FILTER_USE_BOTH));

            if ($newConflicts < $currentConflicts) {
                $geneToMutate['room_id'] = $newRoom->id;
                return true;
            }
        }
        return false;
    }

    /**
     * [دالة جديدة] محاولة تحسين الوقت في الذاكرة
     */
    private function tryImproveTimeslotInMemory(array &$geneToMutate, $lectureBlock, array $genes, int $geneIndex, int $currentConflicts): bool
    {
        $attempts = 5;

        for ($i = 0; $i < $attempts; $i++) {
            $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
            $tempGene = $geneToMutate;
            $tempGene['timeslot_ids'] = $newTimeslots;

            $newConflicts = $this->calculateGeneConflictsInMemory($tempGene, array_filter($genes, fn($g, $i) => $i !== $geneIndex, ARRAY_FILTER_USE_BOTH));

            if ($newConflicts < $currentConflicts) {
                $geneToMutate['timeslot_ids'] = $newTimeslots;
                return true;
            }
        }
        return false;
    }

    /**
     * [دالة جديدة] محاولة تحسين كليهما في الذاكرة
     */
    private function tryImproveBothInMemory(array &$geneToMutate, $lectureBlock, array $genes, int $geneIndex, int $currentConflicts): bool
    {
        $roomCandidates = ($lectureBlock->block_type === 'practical') ? $this->practicalRooms : $this->theoryRooms;
        $attempts = 3;

        for ($i = 0; $i < $attempts; $i++) {
            $newRoom = $roomCandidates->random();
            $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);

            $tempGene = $geneToMutate;
            $tempGene['room_id'] = $newRoom->id;
            $tempGene['timeslot_ids'] = $newTimeslots;

            $newConflicts = $this->calculateGeneConflictsInMemory($tempGene, array_filter($genes, fn($g, $i) => $i !== $geneIndex, ARRAY_FILTER_USE_BOTH));

            if ($newConflicts < $currentConflicts) {
                $geneToMutate['room_id'] = $newRoom->id;
                $geneToMutate['timeslot_ids'] = $newTimeslots;
                return true;
            }
        }
        return false;
    }

    /**
     * [دالة جديدة] تقييم اللياقة في الذاكرة
     */
    private function evaluateFitnessInMemory()
    {
        foreach ($this->memoryCurrentGeneration as &$chromosomeData) {
            $genes = $chromosomeData['genes_data'];
            
            if (empty($genes)) {
                $chromosomeData['chromosome_data'] = [
                    'student_conflict_penalty' => 99999,
                    'teacher_conflict_penalty' => 0,
                    'room_conflict_penalty' => 0,
                    'capacity_conflict_penalty' => 0,
                    'room_type_conflict_penalty' => 0,
                    'teacher_eligibility_conflict_penalty' => 0,
                    'penalty_value' => 99999,
                    'fitness_value' => 0.00001,
                ];
                continue;
            }

            $resourceUsageMap = [];
            $penalties = [];

            $penalties['student_conflict_penalty'] = $this->calculateStudentConflictsInMemory($genes, $resourceUsageMap);
            $penalties['teacher_conflict_penalty'] = $this->calculateTeacherConflictsInMemory($genes, $resourceUsageMap);
            $penalties['room_conflict_penalty'] = $this->calculateRoomConflictsInMemory($genes, $resourceUsageMap);
            $penalties['capacity_conflict_penalty'] = $this->calculateCapacityConflictsInMemory($genes);
            $penalties['room_type_conflict_penalty'] = $this->calculateRoomTypeConflictsInMemory($genes);
            $penalties['teacher_eligibility_conflict_penalty'] = $this->calculateTeacherEligibilityConflictsInMemory($genes);

            $totalPenalty = array_sum($penalties);
            $fitnessValue = 1 / (1 + $totalPenalty);

            $chromosomeData['chromosome_data'] = array_merge($penalties, [
                'penalty_value' => $totalPenalty,
                'fitness_value' => $fitnessValue,
            ]);
        }
    }

    /**
     * [دالة جديدة] حساب تعارضات الطلاب في الذاكرة
     */
    private function calculateStudentConflictsInMemory(array $genes, array &$usageMap): int
    {
        $penalty = 0;
        foreach ($genes as $gene) {
            $studentGroupIds = $gene['student_group_id'] ?? [];
            $timeslotIds = is_array($gene['timeslot_ids']) ? $gene['timeslot_ids'] : json_decode($gene['timeslot_ids'], true) ?? [];
            
            foreach ($timeslotIds as $timeslotId) {
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

    /**
     * [دالة جديدة] حساب تعارضات المدرسين في الذاكرة
     */
    private function calculateTeacherConflictsInMemory(array $genes, array &$usageMap): int
    {
        $penalty = 0;
        foreach ($genes as $gene) {
            $instructorId = $gene['instructor_id'] ?? null;
            $timeslotIds = is_array($gene['timeslot_ids']) ? $gene['timeslot_ids'] : json_decode($gene['timeslot_ids'], true) ?? [];
            
            if (!$instructorId) continue;
            
            foreach ($timeslotIds as $timeslotId) {
                if (isset($usageMap['instructors'][$instructorId][$timeslotId])) {
                    $penalty += 1;
                }
                $usageMap['instructors'][$instructorId][$timeslotId] = true;
            }
        }
        return $penalty;
    }

    /**
     * [دالة جديدة] حساب تعارضات القاعات في الذاكرة
     */
    private function calculateRoomConflictsInMemory(array $genes, array &$usageMap): int
    {
        $penalty = 0;
        foreach ($genes as $gene) {
            $roomId = $gene['room_id'] ?? null;
            $timeslotIds = is_array($gene['timeslot_ids']) ? $gene['timeslot_ids'] : json_decode($gene['timeslot_ids'], true) ?? [];
            
            if (!$roomId) continue;
            
            foreach ($timeslotIds as $timeslotId) {
                if (isset($usageMap['rooms'][$roomId][$timeslotId])) {
                    $penalty += 1;
                }
                $usageMap['rooms'][$roomId][$timeslotId] = true;
            }
        }
        return $penalty;
    }

    /**
     * [دالة جديدة] حساب تعارضات السعة في الذاكرة
     */
    private function calculateCapacityConflictsInMemory(array $genes): int
    {
        $penalty = 0;
        $processedLectures = [];
        
        foreach ($genes as $gene) {
            $lectureUniqueId = $gene['lecture_unique_id'] ?? null;
            if (!$lectureUniqueId || in_array($lectureUniqueId, $processedLectures)) continue;
            
            $processedLectures[] = $lectureUniqueId;
            
            $sectionId = $gene['section_id'] ?? null;
            $roomId = $gene['room_id'] ?? null;
            
            if (!$sectionId || !$roomId) continue;
            
            $section = Section::find($sectionId);
            $room = Room::find($roomId);
            
            if ($section && $room && $section->student_count > $room->room_size) {
                $penalty += 1;
            }
        }
        return $penalty;
    }

    /**
     * [دالة جديدة] حساب تعارضات نوع القاعة في الذاكرة
     */
    private function calculateRoomTypeConflictsInMemory(array $genes): int
    {
        $penalty = 0;
        $processedLectures = [];
        
        foreach ($genes as $gene) {
            $lectureUniqueId = $gene['lecture_unique_id'] ?? null;
            if (!$lectureUniqueId || in_array($lectureUniqueId, $processedLectures)) continue;
            
            $processedLectures[] = $lectureUniqueId;
            
            $roomId = $gene['room_id'] ?? null;
            if (!$roomId) continue;
            
            $room = Room::with('roomType')->find($roomId);
            if (!$room || !$room->roomType) continue;
            
            $isPracticalBlock = Str::contains($lectureUniqueId, 'practical');
            $isPracticalRoom = Str::contains(strtolower($room->roomType->room_type_name), ['lab', 'مختبر']);

            if ($isPracticalBlock && !$isPracticalRoom) {
                $penalty += 1;
            }
            if (!$isPracticalBlock && $isPracticalRoom) {
                $penalty += 1;
            }
        }
        return $penalty;
    }

    /**
     * [دالة جديدة] حساب تعارضات أهلية المدرس في الذاكرة
     */
    private function calculateTeacherEligibilityConflictsInMemory(array $genes): int
    {
        $penalty = 0;
        $processedLectures = [];
        
        foreach ($genes as $gene) {
            $lectureUniqueId = $gene['lecture_unique_id'] ?? null;
            if (!$lectureUniqueId || in_array($lectureUniqueId, $processedLectures)) continue;
            
            $processedLectures[] = $lectureUniqueId;
            
            $instructorId = $gene['instructor_id'] ?? null;
            $sectionId = $gene['section_id'] ?? null;
            
            if (!$instructorId || !$sectionId) continue;
            
            $instructor = Instructor::with('subjects')->find($instructorId);
            $section = Section::with('planSubject.subject')->find($sectionId);
            
            if (!$instructor || !$section || !$section->planSubject || !$section->planSubject->subject) continue;
            
            if (!$instructor->subjects->contains($section->planSubject->subject->id)) {
                $penalty += 1;
            }
        }
        return $penalty;
    }

    /**
     * [دالة جديدة] العثور على أفضل كروموسوم في الذاكرة
     */
    private function findBestChromosomeInMemory(): ?array
    {
        if (empty($this->memoryCurrentGeneration)) return null;
        
        $bestChromosome = null;
        $bestPenalty = PHP_INT_MAX;
        
        foreach ($this->memoryCurrentGeneration as $chromosomeData) {
            $penalty = $chromosomeData['chromosome_data']['penalty_value'] ?? PHP_INT_MAX;
            if ($penalty < $bestPenalty) {
                $bestPenalty = $penalty;
                $bestChromosome = $chromosomeData;
            }
        }
        
        return $bestChromosome;
    }

    /**
     * [دالة جديدة] تحديث تاريخ Elite في الذاكرة
     */
    private function updateEliteHistoryInMemory(int $generationNumber)
    {
        $elitismCount = $this->populationRun->elitism_count ?? 5;
        
        // ترتيب الكروموسومات حسب fitness
        $sortedChromosomes = $this->memoryCurrentGeneration;
        usort($sortedChromosomes, function ($a, $b) {
            return $b['chromosome_data']['fitness_value'] <=> $a['chromosome_data']['fitness_value'];
        });
        
        // أخذ أفضل الكروموسومات
        $currentEliteIds = [];
        for ($i = 0; $i < min($elitismCount, count($sortedChromosomes)); $i++) {
            $currentEliteIds[] = $i; // استخدام index كمعرف مؤقت
        }
        
        // حفظ في تاريخ Elite
        $this->eliteHistoryInMemory[] = $currentEliteIds;
        
        Log::info("Elite updated for Generation #{$generationNumber}: " . count($currentEliteIds) . " chromosomes");
    }

    /**
     * [دالة جديدة] تحديث تاريخ أفضل كروموسوم في الذاكرة
     */
    private function updateBestChromosomeHistoryInMemory(int $generationNumber)
    {
        $bestChromosome = $this->findBestChromosomeInMemory();
        
        if ($bestChromosome) {
            $this->bestChromosomeHistory[$generationNumber] = [
                'penalty_value' => $bestChromosome['chromosome_data']['penalty_value'],
                'fitness_value' => $bestChromosome['chromosome_data']['fitness_value'],
                'chromosome_data' => $bestChromosome
            ];
            
            Log::info("Best chromosome updated for Generation #{$generationNumber}. Penalty: {$bestChromosome['chromosome_data']['penalty_value']}");
        }
    }

    /**
     * [دالة جديدة] حفظ النتائج النهائية في قاعدة البيانات
     */
    private function saveFinalResultToDatabase()
    {
        Log::info("Saving final evolution results to database");
        
        if (empty($this->memoryCurrentGeneration)) {
            Log::warning("No final generation to save");
            return;
        }

        DB::transaction(function () {
            $savedChromosomes = collect();
            $finalGenerationNumber = count($this->eliteHistoryInMemory);
            
            // حفظ كل كروموسومات الجيل الأخير
            foreach ($this->memoryCurrentGeneration as $chromosomeData) {
                $newChromosome = Chromosome::create([
                    'population_id' => $this->populationRun->population_id,
                    'generation_number' => $finalGenerationNumber,
                    'penalty_value' => $chromosomeData['chromosome_data']['penalty_value'],
                    'fitness_value' => $chromosomeData['chromosome_data']['fitness_value'],
                    'student_conflict_penalty' => $chromosomeData['chromosome_data']['student_conflict_penalty'],
                    'teacher_conflict_penalty' => $chromosomeData['chromosome_data']['teacher_conflict_penalty'],
                    'room_conflict_penalty' => $chromosomeData['chromosome_data']['room_conflict_penalty'],
                    'capacity_conflict_penalty' => $chromosomeData['chromosome_data']['capacity_conflict_penalty'],
                    'room_type_conflict_penalty' => $chromosomeData['chromosome_data']['room_type_conflict_penalty'],
                    'teacher_eligibility_conflict_penalty' => $chromosomeData['chromosome_data']['teacher_eligibility_conflict_penalty'],
                ]);

                // حفظ جينات الكروموسوم
                $genesToInsert = [];
                foreach ($chromosomeData['genes_data'] as $geneData) {
                    $genesToInsert[] = [
                        'chromosome_id' => $newChromosome->chromosome_id,
                        'lecture_unique_id' => $geneData['lecture_unique_id'],
                        'section_id' => $geneData['section_id'],
                        'instructor_id' => $geneData['instructor_id'],
                        'room_id' => $geneData['room_id'],
                        'timeslot_ids' => is_string($geneData['timeslot_ids']) ? $geneData['timeslot_ids'] : json_encode($geneData['timeslot_ids']),
                        'student_group_id' => is_string($geneData['student_group_id']) ? $geneData['student_group_id'] : json_encode($geneData['student_group_id']),
                        'block_type' => $geneData['block_type'],
                        'block_duration' => $geneData['block_duration'],
                    ];
                }

                if (!empty($genesToInsert)) {
                    Gene::insert($genesToInsert);
                }

                $savedChromosomes->push($newChromosome);
            }

            // تحديث best_chromosome_id
            $bestChromosome = $savedChromosomes->sortBy('penalty_value')->first();
            if ($bestChromosome) {
                $this->populationRun->update([
                    'best_chromosome_id' => $bestChromosome->chromosome_id
                ]);
                Log::info("Best chromosome saved with ID: {$bestChromosome->chromosome_id}, Penalty: {$bestChromosome->penalty_value}");
            }

            // تحديث elite_chromosome_ids
            $elitismCount = $this->populationRun->elitism_count ?? 5;
            $eliteChromosomes = $savedChromosomes->sortByDesc('fitness_value')->take($elitismCount);
            $eliteIds = $eliteChromosomes->pluck('chromosome_id')->toArray();
            
            $this->populationRun->update([
                'elite_chromosome_ids' => $this->eliteHistoryInMemory
            ]);

            Log::info("Final results saved successfully. Total chromosomes: " . $savedChromosomes->count());
        });
    }

    //======================================================================
    // الدوال الأصلية التي تبقى كما هي (بدون تعديل)
    //======================================================================

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

        // **الخطوة الأولى: حساب conflicts لكل جين - بدون structural penalties**
        $genes = array_map(function ($gene) {
            return is_array($gene) ? (object) $gene : $gene;
        }, $genes);
        $geneConflictScores = [];
        
        foreach ($genes as $index => $gene) {
            if (!$gene) {
                $geneConflictScores[$index] = 0;
                continue;
            }

            $conflicts = 0;
            $otherGenes = array_filter($genes, fn($g, $i) => $g && $i !== $index, ARRAY_FILTER_USE_BOTH);
            $conflicts += $this->calculateGeneConflicts($gene, $otherGenes);

            // **إزالة الـ structural penalties من الـ mutation لتجنب مشاكل البيانات المفقودة**
            // $conflicts += $this->calculateStructuralPenalties($gene); // محذوفة

            $geneConflictScores[$index] = $conflicts;
        }

        // **الخطوة الثانية: اختيار الجين الأكثر تعارضاً للطفرة**
        $targetGeneIndex = null;
        if (!empty($geneConflictScores)) {
            if (lcg_value() < 0.7) {
                $maxConflicts = max($geneConflictScores);
                $targetGeneIndex = array_search($maxConflicts, $geneConflictScores);
            } else {
                arsort($geneConflictScores);
                $topWorst = array_slice($geneConflictScores, 0, 3, true);
                $targetGeneIndex = array_rand($topWorst);
            }
        }

        if ($targetGeneIndex === null || !isset($genes[$targetGeneIndex])) {
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

    private function calculateGeneConflicts($targetGene, array $otherGenes): int
    {
        $conflicts = 0;

        // **إصلاح: تحويل array إلى object إذا لزم الأمر**
        if (is_array($targetGene)) {
            $targetGene = (object) $targetGene;
        }

        // **إصلاح: فحص أمان للبيانات**
        if (!isset($targetGene->timeslot_ids) || !isset($targetGene->instructor_id) || !isset($targetGene->room_id)) {
            Log::warning("Target gene missing required data during conflict calculation");
            return 0;
        }

        $studentGroupIds = $targetGene->student_group_id ?? [];

        // تأكد من أن timeslot_ids مصفوفة
        $targetTimeslots = is_array($targetGene->timeslot_ids) ? $targetGene->timeslot_ids : json_decode($targetGene->timeslot_ids, true) ?? [];

        foreach ($targetTimeslots as $timeslotId) {
            foreach ($otherGenes as $otherGene) {
                if (!$otherGene) continue;

                // **إصلاح: تحويل array إلى object إذا لزم الأمر**
                if (is_array($otherGene)) {
                    $otherGene = (object) $otherGene;
                }

                // **إصلاح: فحص أمان لجين المقارنة**
                if (!isset($otherGene->timeslot_ids) || !isset($otherGene->instructor_id) || !isset($otherGene->room_id)) {
                    continue;
                }

                // تأكد من أن timeslot_ids مصفوفة للجين الآخر
                $otherTimeslots = is_array($otherGene->timeslot_ids) ? $otherGene->timeslot_ids : json_decode($otherGene->timeslot_ids, true) ?? [];

                if (in_array($timeslotId, $otherTimeslots)) {
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

    // دوال التقييم (نفس الكود من InitialPopulationService)
    private function evaluateFitness(Collection $chromosomes)
    {
        $chromosomeIds = $chromosomes->pluck('chromosome_id')->filter();
        if ($chromosomeIds->isEmpty()) {
            Log::warning("No chromosomes to evaluate fitness for");
            return;
        }

        Log::info("Evaluating fitness for " . $chromosomeIds->count() . " chromosomes");

        $allGenesOfGeneration = Gene::whereIn('chromosome_id', $chromosomeIds)
            ->with(['section.planSubject.subject', 'room.roomType', 'instructor'])
            ->get()
            ->groupBy('chromosome_id');

        DB::transaction(function () use ($chromosomes, $allGenesOfGeneration) {
            foreach ($chromosomes as $chromosome) {
                $genes = $allGenesOfGeneration->get($chromosome->chromosome_id, collect());

                if ($genes->isEmpty()) {
                    // **مصححة: استخدام penalty صحيح بدلاً من empty_chromosome**
                    $this->updateChromosomeFitness($chromosome, [
                        'student_conflict_penalty' => 99999,
                        'teacher_conflict_penalty' => 0,
                        'room_conflict_penalty' => 0,
                        'capacity_conflict_penalty' => 0,
                        'room_type_conflict_penalty' => 0,
                        'teacher_eligibility_conflict_penalty' => 0,
                    ]);
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

        Log::info("Fitness evaluation completed successfully");
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