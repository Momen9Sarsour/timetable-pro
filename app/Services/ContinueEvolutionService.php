<?php

///////////////////////////////////// نسخة محسنة للأداء والجودة - تقليل الوقت وتحسين النتائج //////////////////////////////////////////////

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
use App\Models\PlanGroup;
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

    // (جديد) لتخزين أنواع العمليات لتجنب الاستعلام المتكرر
    private Collection $loadedCrossoverTypes;
    private Collection $loadedSelectionTypes;
    private Collection $loadedMutationTypes;

    // === المرحلة الأولى: تحسين الأداء ===
    // (جديد) Cache موحد لكل البيانات في الذاكرة
    private array $globalDataCache = [
        'all_genes' => [],
        'chromosome_fitness' => [],
        'elite_pool' => [],
        'generation_stats' => []
    ];

    // (جديد) نظام Elite مستمر
    private array $persistentElitePool = [];
    private int $generationsWithoutImprovement = 0;
    private float $bestOverallFitness = 0;

    /**
     * المُنشئ (Constructor)
     */
    public function __construct(array $settings, Population $populationRun, Population $parentPopulation)
    {
        $this->settings = $settings;
        $this->populationRun = $populationRun;
        $this->parentPopulation = $parentPopulation;
        Log::info("952222----Optimized Continue Evolution Service initialized for Run ID: {$this->populationRun->population_id}");
    }

    /**
     * الدالة الرئيسية - محسنة للأداء والجودة
     */
    public function continueFromParent()
    {
        try {
            $this->populationRun->update(['status' => 'running', 'start_time' => now()]);
            Log::info("Starting optimized evolution for Population ID: {$this->populationRun->population_id}");

            $this->loadAndPrepareData();

            // **تحميل البيانات إلى Cache موحد**
            $this->initializeGlobalCache();

            // **نسخ الكروموسومات من الأب كنقطة بداية**
            $currentGenerationNumber = 1;
            $this->copyParentChromosomesOptimized($currentGenerationNumber);

            // **تقييم الجيل المنسوخ**
            $this->evaluateFitnessOptimized($currentGenerationNumber);

            Log::info("Starting evolution from Parent Population. Generation #{$currentGenerationNumber} copied and evaluated.");

            $maxGenerations = $this->settings['max_generations'];
            $maxGenerationsWithoutImprovement = 200; // توقف بعد 5 أجيال بدون تحسن

            while ($currentGenerationNumber < $maxGenerations && $this->generationsWithoutImprovement < $maxGenerationsWithoutImprovement) {

                // **فحص أفضل كروموسوم في الجيل الحالي**
                $currentBestFitness = $this->getCurrentBestFitness($currentGenerationNumber);

                if ($currentBestFitness > $this->bestOverallFitness) {
                    $this->bestOverallFitness = $currentBestFitness;
                    $this->generationsWithoutImprovement = 0;
                    Log::info("Improvement found in Generation #{$currentGenerationNumber}, fitness: {$currentBestFitness}");
                } else {
                    $this->generationsWithoutImprovement++;
                    Log::info("No improvement in Generation #{$currentGenerationNumber}, count: {$this->generationsWithoutImprovement}");
                }

                // **فحص الحل الأمثل**
                $bestPenalty = $this->getCurrentBestPenalty($currentGenerationNumber);
                if ($bestPenalty == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
                    Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
                    break;
                }

                $currentGenerationNumber++;

                // **إنشاء الجيل الجديد بالطريقة المحسنة**
                $this->createNewGenerationOptimized($currentGenerationNumber);

                Log::info("Generation #{$currentGenerationNumber} created and evaluated successfully.");
            }

            if ($this->generationsWithoutImprovement >= $maxGenerationsWithoutImprovement) {
                Log::info("Stopped after {$maxGenerationsWithoutImprovement} generations without improvement.");
            }

            $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
            $this->populationRun->update([
                'status' => 'completed',
                'end_time' => now(),
                'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
            ]);
            Log::info("Optimized Evolution Run ID: {$this->populationRun->population_id} completed successfully.");
        } catch (Exception $e) {
            Log::error("Optimized Evolution Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString());
            $this->populationRun->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * === المرحلة الأولى: تحسين الأداء ===
     * تهيئة Cache موحد لكل البيانات
     */
    private function initializeGlobalCache()
    {
        Log::info("Initializing global cache for optimized performance");

        // تفريغ Cache
        $this->globalDataCache = [
            'all_genes' => [],
            'chromosome_fitness' => [],
            'elite_pool' => [],
            'generation_stats' => []
        ];

        Log::info("Global cache initialized successfully");
    }

    /**
     * نسخ الكروموسومات بطريقة محسنة - استعلام واحد كبير
     */
    private function copyParentChromosomesOptimized(int $generationNumber): void
    {
        Log::info("Copying chromosomes from parent population #{$this->parentPopulation->population_id} with optimization");

        // **تحسين رئيسي**: جلب كل البيانات بـ 2 استعلام فقط بدلاً من عشرات الاستعلامات
        $parentChromosomes = $this->parentPopulation->chromosomes()->get();
        if ($parentChromosomes->isEmpty()) {
            throw new Exception("Parent population has no chromosomes to continue from.");
        }

        $parentChromosomeIds = $parentChromosomes->pluck('chromosome_id');

        // **استعلام واحد لكل الجينات**
        $allParentGenes = Gene::whereIn('chromosome_id', $parentChromosomeIds)->get()->groupBy('chromosome_id');

        DB::transaction(function () use ($parentChromosomes, $allParentGenes, $generationNumber) {
            $chromosomesToInsert = [];
            $genesToInsert = [];

            foreach ($parentChromosomes as $parentChromosome) {
                // إنشاء بيانات الكروموسوم الجديد
                $newChromosomeData = [
                    'population_id' => $this->populationRun->population_id,
                    'penalty_value' => $parentChromosome->penalty_value,
                    'generation_number' => $generationNumber,
                    'fitness_value' => $parentChromosome->fitness_value,
                    'student_conflict_penalty' => $parentChromosome->student_conflict_penalty ?? 0,
                    'teacher_conflict_penalty' => $parentChromosome->teacher_conflict_penalty ?? 0,
                    'room_conflict_penalty' => $parentChromosome->room_conflict_penalty ?? 0,
                    'capacity_conflict_penalty' => $parentChromosome->capacity_conflict_penalty ?? 0,
                    'room_type_conflict_penalty' => $parentChromosome->room_type_conflict_penalty ?? 0,
                    'teacher_eligibility_conflict_penalty' => $parentChromosome->teacher_eligibility_conflict_penalty ?? 0,
                ];

                $newChromosome = Chromosome::create($newChromosomeData);

                // حفظ في Cache
                $this->globalDataCache['chromosome_fitness'][$newChromosome->chromosome_id] = $newChromosomeData;

                // تحضير بيانات الجينات
                $parentGenes = $allParentGenes->get($parentChromosome->chromosome_id, collect());
                foreach ($parentGenes as $gene) {
                    $geneData = [
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
                    $genesToInsert[] = $geneData;

                    // حفظ في Cache
                    $this->globalDataCache['all_genes'][$newChromosome->chromosome_id][] = $geneData;
                }
            }

            // **إدراج الجينات على دفعات لتجنب مشكلة MySQL placeholders**
            if (!empty($genesToInsert)) {
                $batchSize = 1000; // دفعات من 1000 جين
                $chunks = array_chunk($genesToInsert, $batchSize);

                foreach ($chunks as $chunk) {
                    Gene::insert($chunk);
                }

                Log::info("Inserted " . count($genesToInsert) . " genes in " . count($chunks) . " batches");
            }
        });

        Log::info("Copied " . $parentChromosomes->count() . " chromosomes with optimized queries");
    }

    /**
     * إنشاء جيل جديد بطريقة محسنة مع Elite صحيح
     */
    private function createNewGenerationOptimized(int $generationNumber): void
    {
        Log::info("Creating generation #{$generationNumber} with proper Elite management");

        $populationSize = $this->settings['population_size'];
        $elitismCount = $this->populationRun->elitism_count ?? 5;

        // **الخطوة 1: الحصول على أفضل كروموسومات من الجيل السابق**
        $eliteChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)
            ->where('generation_number', $generationNumber - 1)
            ->orderBy('penalty_value', 'asc')
            ->orderByDesc('fitness_value')
            ->take($elitismCount)
            ->get();

        Log::info("Found " . $eliteChromosomes->count() . " elite chromosomes to preserve");

        // **الخطوة 2: اختيار الآباء للتزاوج**
        $parents = $this->selectParentsOptimized($generationNumber - 1);

        // **الخطوة 3: إنشاء الأطفال الجدد**
        $newChildrenData = $this->createNewChildrenOptimized($parents, $populationSize - $elitismCount);

        // **الخطوة 4: حفظ الأطفال الجدد فقط (Elite سيُحدث لاحقاً)**
        $this->saveChildrenOnly($generationNumber, $newChildrenData);

        // **الخطوة 5: تحديث generation_number للـ Elite (بدلاً من إعادة إنشائها)**
        $this->updateEliteGenerationNumber($eliteChromosomes, $generationNumber);

        // **الخطوة 6: حذف باقي الكروموسومات القديمة (ما عدا Elite)**
        $this->deleteOldGenerationExceptElite($generationNumber, $eliteChromosomes->pluck('chromosome_id')->toArray());

        // **الخطوة 7: تقييم الأطفال الجدد فقط (Elite لا يحتاج تقييم)**
        $this->evaluateChildrenOnly($generationNumber);

        Log::info("Generation {$generationNumber} created with proper Elite preservation. Population size: {$populationSize}");
    }

    /**
     * حفظ الأطفال الجدد فقط
     */
    private function saveChildrenOnly(int $generationNumber, array $newChildrenData): void
    {
        Log::info("Saving " . count($newChildrenData) . " new children to generation #{$generationNumber}");

        DB::transaction(function () use ($generationNumber, $newChildrenData) {
            $genesToInsert = [];

            foreach ($newChildrenData as $childData) {
                $newChildChromosome = Chromosome::create([
                    'population_id' => $this->populationRun->population_id,
                    'generation_number' => $generationNumber,
                    'penalty_value' => -1,
                    'fitness_value' => 0,
                    'student_conflict_penalty' => 0,
                    'teacher_conflict_penalty' => 0,
                    'room_conflict_penalty' => 0,
                    'capacity_conflict_penalty' => 0,
                    'room_type_conflict_penalty' => 0,
                    'teacher_eligibility_conflict_penalty' => 0,
                ]);

                foreach ($childData['genes_data'] as $geneData) {
                    $genesToInsert[] = [
                        'chromosome_id' => $newChildChromosome->chromosome_id,
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
            }

            // إدراج الجينات على دفعات
            if (!empty($genesToInsert)) {
                $batchSize = 1000;
                $chunks = array_chunk($genesToInsert, $batchSize);

                foreach ($chunks as $chunk) {
                    Gene::insert($chunk);
                }

                Log::info("Inserted " . count($genesToInsert) . " genes for new children in " . count($chunks) . " batches");
            }
        });
    }

    /**
     * تحديث رقم الجيل للـ Elite (بدلاً من إعادة إنشائها)
     */
    private function updateEliteGenerationNumber(Collection $eliteChromosomes, int $newGenerationNumber): void
    {
        Log::info("Updating generation number for " . $eliteChromosomes->count() . " elite chromosomes");

        $eliteIds = $eliteChromosomes->pluck('chromosome_id')->toArray();

        if (!empty($eliteIds)) {
            Chromosome::whereIn('chromosome_id', $eliteIds)
                ->update(['generation_number' => $newGenerationNumber]);

            Log::info("Updated generation_number to {$newGenerationNumber} for elite chromosomes: " . implode(', ', $eliteIds));
        }
    }

    /**
     * حذف الكروموسومات القديمة ما عدا Elite
     */
    private function deleteOldGenerationExceptElite(int $currentGenerationNumber, array $eliteIds): void
    {
        DB::transaction(function () use ($currentGenerationNumber, $eliteIds) {
            // جلب الكروموسومات القديمة (ما عدا الجيل الحالي والـ Elite)
            $query = Chromosome::where('population_id', $this->populationRun->population_id)
                ->where('generation_number', '!=', $currentGenerationNumber);

            if (!empty($eliteIds)) {
                $query->whereNotIn('chromosome_id', $eliteIds);
            }

            $oldChromosomeIds = $query->pluck('chromosome_id');

            if ($oldChromosomeIds->isNotEmpty()) {
                // حذف الجينات أولاً
                Gene::whereIn('chromosome_id', $oldChromosomeIds)->delete();
                // ثم حذف الكروموسومات
                Chromosome::whereIn('chromosome_id', $oldChromosomeIds)->delete();

                Log::info("Deleted " . $oldChromosomeIds->count() . " old chromosomes (preserved " . count($eliteIds) . " elite)");
            }
        });
    }

    /**
     * تقييم الأطفال الجدد فقط (Elite لا يحتاج إعادة تقييم)
     */
    private function evaluateChildrenOnly(int $generationNumber): void
    {
        // جلب الكروموسومات الجديدة فقط (اللي penalty_value = -1)
        $newChromosomeIds = Chromosome::where('population_id', $this->populationRun->population_id)
            ->where('generation_number', $generationNumber)
            ->where('penalty_value', -1)
            ->pluck('chromosome_id');

        if ($newChromosomeIds->isEmpty()) {
            Log::info("No new chromosomes to evaluate in generation {$generationNumber}");
            return;
        }

        Log::info("Evaluating fitness for " . $newChromosomeIds->count() . " new chromosomes in generation {$generationNumber}");

        // استعلام واحد لكل الجينات الجديدة فقط
        $allGenesOfNewChromosomes = Gene::whereIn('chromosome_id', $newChromosomeIds)
            ->with(['section.planSubject.subject', 'room.roomType', 'instructor'])
            ->get()
            ->groupBy('chromosome_id');

        DB::transaction(function () use ($newChromosomeIds, $allGenesOfNewChromosomes) {
            foreach ($newChromosomeIds as $chromosomeId) {
                $genes = $allGenesOfNewChromosomes->get($chromosomeId, collect());

                if ($genes->isEmpty()) {
                    $penalties = [
                        'student_conflict_penalty' => 99999,
                        'teacher_conflict_penalty' => 0,
                        'room_conflict_penalty' => 0,
                        'capacity_conflict_penalty' => 0,
                        'room_type_conflict_penalty' => 0,
                        'teacher_eligibility_conflict_penalty' => 0,
                    ];
                } else {
                    $resourceUsageMap = [];
                    $penalties = [];

                    // **استخدام دالة التقييم المحسنة للطلاب**
                    $penalties['student_conflict_penalty'] = $this->calculateStudentConflictsFixed($genes, $resourceUsageMap);
                    $penalties['teacher_conflict_penalty'] = $this->calculateTeacherConflicts($genes, $resourceUsageMap);
                    $penalties['room_conflict_penalty'] = $this->calculateRoomConflicts($genes, $resourceUsageMap);
                    $penalties['capacity_conflict_penalty'] = $this->calculateCapacityConflicts($genes);
                    $penalties['room_type_conflict_penalty'] = $this->calculateRoomTypeConflicts($genes);
                    $penalties['teacher_eligibility_conflict_penalty'] = $this->calculateTeacherEligibilityConflicts($genes);
                }

                $totalPenalty = array_sum($penalties);
                $fitnessValue = 1 / (1 + $totalPenalty);

                $updateData = array_merge($penalties, [
                    'penalty_value' => $totalPenalty,
                    'fitness_value' => $fitnessValue,
                ]);

                Chromosome::where('chromosome_id', $chromosomeId)->update($updateData);
            }
        });
    }

    /**
     * دالة تقييم محسنة لتعارضات الطلاب - تميز بين النظري والعملي
     */
    private function calculateStudentConflictsFixed(Collection $genes, array &$usageMap): int
    {
        $penalty = 0;

        foreach ($genes as $gene) {
            $studentGroupIds = $gene->student_group_id ?? [];
            $isTheoreticalBlock = Str::contains($gene->lecture_unique_id, 'theory');

            foreach ($gene->timeslot_ids as $timeslotId) {
                if ($isTheoreticalBlock) {
                    // للمواد النظرية: تحقق من التعارض مع مواد نظرية أخرى فقط
                    // (المواد النظرية مشتركة بين المجموعات)
                    if (isset($usageMap['theoretical_shared'][$timeslotId])) {
                        $penalty += 1;
                    }
                    $usageMap['theoretical_shared'][$timeslotId] = true;
                } else {
                    // للمواد العملية: تحقق من تعارض المجموعات المنفصلة
                    foreach ($studentGroupIds as $groupId) {
                        if (isset($usageMap['student_groups'][$groupId][$timeslotId])) {
                            $penalty += 1;
                        }
                        $usageMap['student_groups'][$groupId][$timeslotId] = true;
                    }
                }
            }
        }

        return $penalty;
    }

    /**
     * === المرحلة الثانية: تحسين جودة النتائج ===
     * تحديث Elite Pool المستمر
     */
    private function updatePersistentElitePool(int $generationNumber): void
    {
        $elitismCount = $this->populationRun->elitism_count ?? 5;

        // جلب أفضل الكروموسومات من الجيل الحالي
        $currentBest = Chromosome::where('population_id', $this->populationRun->population_id)
            ->where('generation_number', $generationNumber)
            ->orderBy('penalty_value', 'asc')
            ->orderByDesc('fitness_value')
            ->take($elitismCount * 2) // نأخذ ضعف العدد للمقارنة
            ->get();

        foreach ($currentBest as $chromosome) {
            // إضافة للـ Elite Pool المستمر
            $this->persistentElitePool[$chromosome->chromosome_id] = [
                'chromosome_id' => $chromosome->chromosome_id,
                'penalty_value' => $chromosome->penalty_value,
                'fitness_value' => $chromosome->fitness_value,
                'generation_found' => $generationNumber,
                'student_conflict_penalty' => $chromosome->student_conflict_penalty ?? 0,
                'teacher_conflict_penalty' => $chromosome->teacher_conflict_penalty ?? 0,
                'room_conflict_penalty' => $chromosome->room_conflict_penalty ?? 0,
                'capacity_conflict_penalty' => $chromosome->capacity_conflict_penalty ?? 0,
                'room_type_conflict_penalty' => $chromosome->room_type_conflict_penalty ?? 0,
                'teacher_eligibility_conflict_penalty' => $chromosome->teacher_eligibility_conflict_penalty ?? 0,
            ];
        }

        // ترتيب Elite Pool والاحتفاظ بأفضل العناصر فقط
        uasort($this->persistentElitePool, function ($a, $b) {
            if ($a['penalty_value'] == $b['penalty_value']) {
                return $b['fitness_value'] <=> $a['fitness_value'];
            }
            return $a['penalty_value'] <=> $b['penalty_value'];
        });

        // الاحتفاظ بأفضل عناصر Elite فقط
        $this->persistentElitePool = array_slice($this->persistentElitePool, 0, $elitismCount, true);

        Log::info("Updated persistent elite pool with " . count($this->persistentElitePool) . " chromosomes");
    }

    /**
     * اختيار الآباء بطريقة محسنة - Tournament Selection محسن
     */
    private function selectParentsOptimized(int $generationNumber): array
    {
        $populationSize = $this->settings['population_size'];
        $tournamentSize = $this->settings['selection_size'] ?? 5;

        // جلب كل كروموسومات الجيل الحالي
        $currentPopulation = Chromosome::where('population_id', $this->populationRun->population_id)
            ->where('generation_number', $generationNumber)
            ->orderByDesc('fitness_value')
            ->get();

        if ($currentPopulation->isEmpty()) {
            Log::warning("No chromosomes found for generation {$generationNumber}");
            return [];
        }

        $parents = [];

        // **تحسين**: 70% من الآباء من أفضل 50% من التجمع
        $topHalfCount = ceil($currentPopulation->count() / 2);
        $topHalf = $currentPopulation->take($topHalfCount);
        $bottomHalf = $currentPopulation->skip($topHalfCount);

        for ($i = 0; $i < $populationSize; $i++) {
            if (lcg_value() < 0.7 && $topHalf->isNotEmpty()) {
                // اختيار من أفضل 50%
                $participants = $topHalf->random(min($tournamentSize, $topHalf->count()));
            } else if ($bottomHalf->isNotEmpty()) {
                // اختيار من باقي التجمع
                $participants = $bottomHalf->random(min($tournamentSize, $bottomHalf->count()));
            } else {
                // fallback
                $participants = $currentPopulation->random(min($tournamentSize, $currentPopulation->count()));
            }

            $winner = $participants->sortByDesc('fitness_value')->first();
            if ($winner) {
                $parents[] = $winner;
            }
        }

        Log::info("Selected " . count($parents) . " parents using optimized tournament selection");
        return $parents;
    }

    /**
     * إنشاء أطفال جدد بطريقة محسنة
     */
    private function createNewChildrenOptimized(array $parents, int $childrenCount): array
    {
        $newChildrenData = [];
        $parentIds = array_filter(array_map(fn($p) => $p->chromosome_id ?? null, $parents));

        if (empty($parentIds)) {
            Log::warning("No valid parent IDs for creating children");
            return [];
        }

        // **تحسين**: جلب جينات كل الآباء بـ استعلام واحد**
        $allParentGenes = Gene::whereIn('chromosome_id', $parentIds)
            ->get()
            ->groupBy('chromosome_id')
            ->map(function ($genes) {
                return $genes->map(function ($gene) {
                    return [
                        'lecture_unique_id' => $gene->lecture_unique_id,
                        'section_id' => $gene->section_id,
                        'instructor_id' => $gene->instructor_id,
                        'room_id' => $gene->room_id,
                        'timeslot_ids' => is_string($gene->timeslot_ids) ? json_decode($gene->timeslot_ids, true) : $gene->timeslot_ids,
                        'student_group_id' => is_string($gene->student_group_id) ? json_decode($gene->student_group_id, true) : $gene->student_group_id,
                        'block_type' => $gene->block_type,
                        'block_duration' => $gene->block_duration,
                    ];
                })->toArray();
            });

        for ($i = 0; $i < $childrenCount; $i++) {
            // اختيار أبوين مختلفين
            $parent1Id = $parentIds[array_rand($parentIds)];
            do {
                $parent2Id = $parentIds[array_rand($parentIds)];
            } while ($parent1Id === $parent2Id && count($parentIds) > 1);

            $p1Genes = $allParentGenes->get($parent1Id, []);
            $p2Genes = $allParentGenes->get($parent2Id, []);

            if (empty($p1Genes) || empty($p2Genes)) continue;

            // التزاوج
            if (lcg_value() < $this->settings['crossover_rate']) {
                $childGenes = $this->performOptimizedCrossover($p1Genes, $p2Genes);
            } else {
                $childGenes = lcg_value() < 0.5 ? $p1Genes : $p2Genes;
            }

            // الطفرة المحسنة
            $mutatedChildGenes = $this->performOptimizedMutation($childGenes);

            $newChildrenData[] = ['genes_data' => $mutatedChildGenes];
        }

        Log::info("Created " . count($newChildrenData) . " new children with optimization");
        return $newChildrenData;
    }

    /**
     * === المرحلة الثالثة: معايرة العمليات ===
     * تزاوج محسن مع تحسين الأداء
     */
    private function performOptimizedCrossover(array $p1Genes, array $p2Genes): array
    {
        $crossoverSlug = $this->loadedCrossoverTypes->find($this->settings['crossover_type_id'])->slug ?? 'single_point';

        switch ($crossoverSlug) {
            case 'single_point':
                return $this->optimizedSinglePointCrossover($p1Genes, $p2Genes);
            default:
                return $this->optimizedSinglePointCrossover($p1Genes, $p2Genes);
        }
    }

    /**
     * تزاوج نقطة واحدة محسن
     */
    private function optimizedSinglePointCrossover(array $p1Genes, array $p2Genes): array
    {
        $p1GenesByKey = [];
        $p2GenesByKey = [];

        // تحسين: بناء الفهارس بطريقة أسرع
        foreach ($p1Genes as $gene) {
            $p1GenesByKey[$gene['lecture_unique_id']] = $gene;
        }
        foreach ($p2Genes as $gene) {
            $p2GenesByKey[$gene['lecture_unique_id']] = $gene;
        }

        $childGenes = [];
        $totalBlocks = $this->lectureBlocksToSchedule->count();
        $crossoverPoint = rand(1, $totalBlocks - 1);
        $currentIndex = 0;

        foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
            $source = ($currentIndex < $crossoverPoint) ? $p1GenesByKey : $p2GenesByKey;
            $fallbackSource = ($currentIndex < $crossoverPoint) ? $p2GenesByKey : $p1GenesByKey;

            $gene = $source[$lectureBlock->unique_id] ?? $fallbackSource[$lectureBlock->unique_id] ?? null;

            if ($gene) {
                // تطبيق تغيير عشوائي محسن (30% احتمال)
                if (lcg_value() < 0.3) {
                    $modifiedGene = $this->applyOptimizedCrossoverChange($gene, $lectureBlock);
                    $childGenes[] = $modifiedGene;
                } else {
                    $childGenes[] = $gene;
                }
            }
            $currentIndex++;
        }

        return $childGenes;
    }

    /**
     * تطبيق تغيير محسن أثناء التزاوج - أكثر عدوانية
     */
    private function applyOptimizedCrossoverChange(array $gene, \stdClass $lectureBlock): array
    {
        // **تحسين**: زيادة احتمالية التغيير من 30% إلى 70%
        if (lcg_value() > 0.7) {
            return $gene; // 30% احتمال عدم التغيير
        }

        $changeType = rand(1, 4); // زيادة الخيارات

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

            case 4: // **جديد**: تغيير متقدم - اختيار أفضل خيار من عدة محاولات
                $bestOption = $gene;
                $currentQuality = $this->estimateGeneQuality($gene, $lectureBlock);

                // جرب 3 خيارات مختلفة واختار الأفضل
                for ($i = 0; $i < 3; $i++) {
                    $testGene = $gene;

                    if ($i == 0) {
                        $testGene['room_id'] = $this->getRandomRoomForBlock($lectureBlock)->id;
                    } elseif ($i == 1) {
                        $testGene['timeslot_ids'] = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
                    } else {
                        $testGene['room_id'] = $this->getRandomRoomForBlock($lectureBlock)->id;
                        $testGene['timeslot_ids'] = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
                    }

                    $testQuality = $this->estimateGeneQuality($testGene, $lectureBlock);
                    if ($testQuality > $currentQuality) {
                        $bestOption = $testGene;
                        $currentQuality = $testQuality;
                    }
                }

                $gene = $bestOption;
                break;
        }

        return $gene;
    }

    /**
     * تقدير سريع لجودة الجين (بدون حساب تعارضات معقدة)
     */
    private function estimateGeneQuality(array $gene, \stdClass $lectureBlock): float
    {
        $quality = 0;

        // فحص مناسبة القاعة
        $room = $this->theoryRooms->merge($this->practicalRooms)->firstWhere('id', $gene['room_id']);
        if ($room) {
            $section = $lectureBlock->section;

            // جودة السعة
            if ($room->room_size >= $section->student_count) {
                $quality += 10;
                // مكافأة إضافية للقاعات المناسبة تماماً
                if ($room->room_size <= $section->student_count * 1.2) {
                    $quality += 5;
                }
            } else {
                $quality -= 20; // عقوبة كبيرة للسعة غير المناسبة
            }

            // جودة نوع القاعة
            $isPracticalBlock = $lectureBlock->block_type === 'practical';
            $isPracticalRoom = Str::contains(strtolower(optional($room->roomType)->room_type_name ?? ''), ['lab', 'مختبر']);

            if ($isPracticalBlock == $isPracticalRoom) {
                $quality += 15; // نوع القاعة مناسب
            } else {
                $quality -= 15; // نوع القاعة غير مناسب
            }
        }

        // جودة التوقيت (فحص بسيط)
        $timeslots = $gene['timeslot_ids'] ?? [];
        if (count($timeslots) == $lectureBlock->slots_needed) {
            $quality += 5; // العدد صحيح
        } else {
            $quality -= 10; // العدد خطأ
        }

        return $quality;
    }

    /**
     * طفرة محسنة مع التركيز على تعارضات الطلاب - أكثر عدوانية
     */
    private function performOptimizedMutation(array $genes): array
    {
        // **زيادة فعالية الطفرة**: حتى لو mutation_rate منخفض، نطبق طفرة أكثر عدوانية
        $effectiveMutationRate = max($this->settings['mutation_rate'], 0.05); // أقل شي 5%

        if (lcg_value() >= $effectiveMutationRate || empty($genes)) {
            return $genes;
        }

        // **تحسين 1**: طفرة متعددة الجينات بدلاً من جين واحد
        $numberOfGenesToMutate = max(1, intval(count($genes) * 0.1)); // طفرة 10% من الجينات

        // حساب تعارضات كل جين
        $geneConflictScores = [];
        foreach ($genes as $index => $gene) {
            $conflicts = $this->calculateStudentSpecificConflicts($gene, $genes, $index);
            $geneConflictScores[$index] = $conflicts;
        }

        // ترتيب الجينات حسب التعارضات (الأسوأ أولاً)
        arsort($geneConflictScores);

        // **تحسين 2**: اختيار أسوأ الجينات للطفرة
        $worstGenesIndices = array_slice(array_keys($geneConflictScores), 0, $numberOfGenesToMutate, true);

        $mutationSuccessCount = 0;

        foreach ($worstGenesIndices as $geneIndex) {
            $geneToMutate = $genes[$geneIndex];
            $lectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $geneToMutate['lecture_unique_id']);

            if (!$lectureBlock) continue;

            // **تحسين 3**: طفرة أكثر عدوانية - 3 محاولات تحسين مختلفة
            $originalGene = $geneToMutate;
            $bestImprovement = false;
            $currentConflicts = $geneConflictScores[$geneIndex];

            // محاولة 1: تغيير الوقت (3 محاولات)
            for ($attempt = 0; $attempt < 3; $attempt++) {
                $testGene = $originalGene;
                $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
                $testGene['timeslot_ids'] = $newTimeslots;

                $newConflicts = $this->calculateStudentSpecificConflicts($testGene, $genes, $geneIndex);
                if ($newConflicts < $currentConflicts) {
                    $geneToMutate['timeslot_ids'] = $newTimeslots;
                    $bestImprovement = true;
                    $currentConflicts = $newConflicts;
                    break;
                }
            }

            // محاولة 2: تغيير القاعة (3 محاولات)
            for ($attempt = 0; $attempt < 3; $attempt++) {
                $testGene = $geneToMutate; // استخدام أفضل نسخة حتى الآن
                $newRoom = $this->getRandomRoomForBlock($lectureBlock);
                $testGene['room_id'] = $newRoom->id;

                // فحص تعارضات القاعة سريعاً
                $hasRoomConflict = $this->quickRoomConflictCheck($testGene, $genes, $geneIndex);
                if (!$hasRoomConflict) {
                    $geneToMutate['room_id'] = $newRoom->id;
                    $bestImprovement = true;
                    break;
                }
            }

            // محاولة 3: إذا فشل التحسين، طفرة قوية (تغيير كلي)
            if (!$bestImprovement && lcg_value() < 0.3) {
                $geneToMutate['room_id'] = $this->getRandomRoomForBlock($lectureBlock)->id;
                $geneToMutate['timeslot_ids'] = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
                $bestImprovement = true;
            }

            if ($bestImprovement) {
                $genes[$geneIndex] = $geneToMutate;
                $mutationSuccessCount++;
            }
        }

        if ($mutationSuccessCount > 0) {
            Log::info("Successful mutations: {$mutationSuccessCount} out of {$numberOfGenesToMutate} attempted");
        }

        return $genes;
    }

    /**
     * فحص سريع لتعارضات القاعة
     */
    private function quickRoomConflictCheck(array $testGene, array $allGenes, int $excludeIndex): bool
    {
        $testTimeslots = $testGene['timeslot_ids'] ?? [];

        foreach ($allGenes as $index => $otherGene) {
            if ($index === $excludeIndex || !$otherGene) continue;

            $otherTimeslots = $otherGene['timeslot_ids'] ?? [];
            if (count(array_intersect($testTimeslots, $otherTimeslots)) > 0) {
                if ($otherGene['room_id'] == $testGene['room_id']) {
                    return true; // يوجد تعارض
                }
            }
        }

        return false; // لا يوجد تعارض
    }

    /**
     * حساب تعارضات محددة بالطلاب لجين معين
     */
    private function calculateStudentSpecificConflicts(array $targetGene, array $otherGenes, int $excludeIndex): int
    {
        $conflicts = 0;
        $studentGroupIds = $targetGene['student_group_id'] ?? [];
        $targetTimeslots = $targetGene['timeslot_ids'] ?? [];

        foreach ($targetTimeslots as $timeslotId) {
            foreach ($otherGenes as $index => $otherGene) {
                if ($index === $excludeIndex || !$otherGene) continue;

                $otherTimeslots = $otherGene['timeslot_ids'] ?? [];
                if (in_array($timeslotId, $otherTimeslots)) {
                    $otherStudentGroupIds = $otherGene['student_group_id'] ?? [];

                    // التركيز على تعارضات الطلاب (أهم نوع تعارض)
                    if (!empty($studentGroupIds) && !empty($otherStudentGroupIds)) {
                        if (count(array_intersect($studentGroupIds, $otherStudentGroupIds)) > 0) {
                            $conflicts += 10; // وزن عالي لتعارضات الطلاب
                        }
                    }
                }
            }
        }

        return $conflicts;
    }

    /**
     * محاولة تحسين ذكي للطفرة
     */
    private function trySmartMutationImprovement(array &$geneToMutate, \stdClass $lectureBlock, array $genes, int $geneIndex): bool
    {
        $currentConflicts = $this->calculateStudentSpecificConflicts($geneToMutate, $genes, $geneIndex);
        $bestImprovement = false;

        // محاولة 1: تحسين الوقت فقط
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
            $tempGene = $geneToMutate;
            $tempGene['timeslot_ids'] = $newTimeslots;

            $newConflicts = $this->calculateStudentSpecificConflicts($tempGene, $genes, $geneIndex);
            if ($newConflicts < $currentConflicts) {
                $geneToMutate['timeslot_ids'] = $newTimeslots;
                $bestImprovement = true;
                break;
            }
        }

        // محاولة 2: تحسين القاعة إذا لم ينجح تحسين الوقت
        if (!$bestImprovement) {
            for ($attempt = 0; $attempt < 3; $attempt++) {
                $newRoom = $this->getRandomRoomForBlock($lectureBlock);
                $tempGene = $geneToMutate;
                $tempGene['room_id'] = $newRoom->id;

                // تحقق من عدم وجود تعارض في القاعة
                $hasRoomConflict = false;
                foreach ($genes as $index => $otherGene) {
                    if ($index === $geneIndex || !$otherGene) continue;

                    $otherTimeslots = $otherGene['timeslot_ids'] ?? [];
                    $currentTimeslots = $geneToMutate['timeslot_ids'] ?? [];

                    if (count(array_intersect($currentTimeslots, $otherTimeslots)) > 0 && $otherGene['room_id'] == $newRoom->id) {
                        $hasRoomConflict = true;
                        break;
                    }
                }

                if (!$hasRoomConflict) {
                    $geneToMutate['room_id'] = $newRoom->id;
                    $bestImprovement = true;
                    break;
                }
            }
        }

        return $bestImprovement;
    }

    /**
     * حفظ الجيل الجديد بطريقة محسنة
     */
    private function saveNewGenerationOptimized(int $generationNumber, int $elitismCount, array $newChildrenData): void
    {
        Log::info("Saving generation #{$generationNumber} with optimization");

        DB::transaction(function () use ($generationNumber, $elitismCount, $newChildrenData) {
            $chromosomesToInsert = [];
            $genesToInsert = [];

            // **حفظ Elite من الـ Pool المستمر**
            $eliteCount = 0;
            foreach ($this->persistentElitePool as $eliteData) {
                if ($eliteCount >= $elitismCount) break;

                // جلب جينات Elite من الكروموسوم الأصلي
                $eliteGenes = Gene::where('chromosome_id', $eliteData['chromosome_id'])->get();

                // إنشاء كروموسوم Elite جديد
                $newEliteChromosome = Chromosome::create([
                    'population_id' => $this->populationRun->population_id,
                    'generation_number' => $generationNumber,
                    'penalty_value' => $eliteData['penalty_value'],
                    'fitness_value' => $eliteData['fitness_value'],
                    'student_conflict_penalty' => $eliteData['student_conflict_penalty'],
                    'teacher_conflict_penalty' => $eliteData['teacher_conflict_penalty'],
                    'room_conflict_penalty' => $eliteData['room_conflict_penalty'],
                    'capacity_conflict_penalty' => $eliteData['capacity_conflict_penalty'],
                    'room_type_conflict_penalty' => $eliteData['room_type_conflict_penalty'],
                    'teacher_eligibility_conflict_penalty' => $eliteData['teacher_eligibility_conflict_penalty'],
                ]);

                // نسخ جينات Elite
                foreach ($eliteGenes as $gene) {
                    $genesToInsert[] = [
                        'chromosome_id' => $newEliteChromosome->chromosome_id,
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

                $eliteCount++;
            }

            // **حفظ الأطفال الجدد**
            foreach ($newChildrenData as $childData) {
                $newChildChromosome = Chromosome::create([
                    'population_id' => $this->populationRun->population_id,
                    'generation_number' => $generationNumber,
                    'penalty_value' => -1,
                    'fitness_value' => 0,
                    'student_conflict_penalty' => 0,
                    'teacher_conflict_penalty' => 0,
                    'room_conflict_penalty' => 0,
                    'capacity_conflict_penalty' => 0,
                    'room_type_conflict_penalty' => 0,
                    'teacher_eligibility_conflict_penalty' => 0,
                ]);

                foreach ($childData['genes_data'] as $geneData) {
                    $genesToInsert[] = [
                        'chromosome_id' => $newChildChromosome->chromosome_id,
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
            }

            // **إدراج الجينات على دفعات لتجنب مشكلة MySQL placeholders**
            if (!empty($genesToInsert)) {
                $batchSize = 1000; // دفعات من 1000 جين
                $chunks = array_chunk($genesToInsert, $batchSize);

                foreach ($chunks as $chunk) {
                    Gene::insert($chunk);
                }

                Log::info("Inserted " . count($genesToInsert) . " genes in " . count($chunks) . " batches");
            }
        });

        Log::info("Generation #{$generationNumber} saved with {$elitismCount} elite and " . count($newChildrenData) . " children");
    }

    /**
     * تقييم محسن للـ fitness
     */
    private function evaluateFitnessOptimized(int $generationNumber): void
    {
        $chromosomeIds = Chromosome::where('population_id', $this->populationRun->population_id)
            ->where('generation_number', $generationNumber)
            ->pluck('chromosome_id');

        if ($chromosomeIds->isEmpty()) {
            Log::warning("No chromosomes to evaluate for generation {$generationNumber}");
            return;
        }

        Log::info("Evaluating fitness for " . $chromosomeIds->count() . " chromosomes in generation {$generationNumber}");

        // **تحسين رئيسي**: استعلام واحد لكل الجينات
        $allGenesOfGeneration = Gene::whereIn('chromosome_id', $chromosomeIds)
            ->with(['section.planSubject.subject', 'room.roomType', 'instructor'])
            ->get()
            ->groupBy('chromosome_id');

        DB::transaction(function () use ($chromosomeIds, $allGenesOfGeneration) {
            $updateData = [];

            foreach ($chromosomeIds as $chromosomeId) {
                $genes = $allGenesOfGeneration->get($chromosomeId, collect());

                if ($genes->isEmpty()) {
                    $penalties = [
                        'student_conflict_penalty' => 99999,
                        'teacher_conflict_penalty' => 0,
                        'room_conflict_penalty' => 0,
                        'capacity_conflict_penalty' => 0,
                        'room_type_conflict_penalty' => 0,
                        'teacher_eligibility_conflict_penalty' => 0,
                    ];
                } else {
                    $resourceUsageMap = [];
                    $penalties = [];

                    $penalties['student_conflict_penalty'] = $this->calculateStudentConflicts($genes, $resourceUsageMap);
                    $penalties['teacher_conflict_penalty'] = $this->calculateTeacherConflicts($genes, $resourceUsageMap);
                    $penalties['room_conflict_penalty'] = $this->calculateRoomConflicts($genes, $resourceUsageMap);
                    $penalties['capacity_conflict_penalty'] = $this->calculateCapacityConflicts($genes);
                    $penalties['room_type_conflict_penalty'] = $this->calculateRoomTypeConflicts($genes);
                    $penalties['teacher_eligibility_conflict_penalty'] = $this->calculateTeacherEligibilityConflicts($genes);
                }

                $totalPenalty = array_sum($penalties);
                $fitnessValue = 1 / (1 + $totalPenalty);

                $updateData[] = array_merge($penalties, [
                    'chromosome_id' => $chromosomeId,
                    'penalty_value' => $totalPenalty,
                    'fitness_value' => $fitnessValue,
                ]);
            }

            // **تحديث جماعي محسن**
            foreach ($updateData as $data) {
                $chromosomeId = $data['chromosome_id'];
                unset($data['chromosome_id']);
                Chromosome::where('chromosome_id', $chromosomeId)->update($data);
            }
        });

        Log::info("Fitness evaluation completed for generation {$generationNumber}");
    }

    /**
     * حذف الجيل القديم بطريقة محسنة
     */
    private function deleteOldGenerationOptimized(int $currentGenerationNumber): void
    {
        DB::transaction(function () use ($currentGenerationNumber) {
            $oldChromosomeIds = Chromosome::where('population_id', $this->populationRun->population_id)
                ->where('generation_number', '!=', $currentGenerationNumber)
                ->pluck('chromosome_id');

            if ($oldChromosomeIds->isNotEmpty()) {
                // حذف الجينات أولاً
                Gene::whereIn('chromosome_id', $oldChromosomeIds)->delete();
                // ثم حذف الكروموسومات
                Chromosome::whereIn('chromosome_id', $oldChromosomeIds)->delete();

                Log::info("Deleted " . $oldChromosomeIds->count() . " old chromosomes and their genes");
            }
        });
    }

    /**
     * الحصول على أفضل fitness في الجيل الحالي
     */
    private function getCurrentBestFitness(int $generationNumber): float
    {
        return Chromosome::where('population_id', $this->populationRun->population_id)
            ->where('generation_number', $generationNumber)
            ->max('fitness_value') ?? 0;
    }

    /**
     * الحصول على أقل penalty في الجيل الحالي
     */
    private function getCurrentBestPenalty(int $generationNumber): int
    {
        return Chromosome::where('population_id', $this->populationRun->population_id)
            ->where('generation_number', $generationNumber)
            ->min('penalty_value') ?? 99999;
    }

    //======================================================================
    // الدوال المنسوخة بدون تعديل من الكود الأصلي
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
        $this->studentGroupMap = $this->buildStudentGroupMapFromDatabase($this->settings['academic_year'], $this->settings['semester']);

        // **(الخطوة الأهم)**: التحضير المسبق الكامل للبلوكات مع تعيين المدرسين
        $this->precomputeLectureBlocks($sections);

        if ($this->lectureBlocksToSchedule->isEmpty()) {
            throw new Exception("No lecture blocks to schedule were found after processing credit hours.");
        }

        Log::info("Data loaded and precomputed: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
    }

    private function buildStudentGroupMapFromDatabase($academicYear, $semester)
    {
        $groupMap = [];

        $planGroups = PlanGroup::where('academic_year', $academicYear)
            ->where('semester', $semester)
            ->get()
            ->groupBy('section_id');

        foreach ($planGroups as $sectionId => $groups) {
            $groupMap[$sectionId] = $groups->pluck('group_no')->toArray();
        }

        return $groupMap;
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
    // private function calculateStudentConflicts(Collection $genes, array &$usageMap): int
    // {
    //     $penalty = 0;
    //     foreach ($genes as $gene) {
    //         $studentGroupIds = $gene->student_group_id ?? [];
    //         foreach ($gene->timeslot_ids as $timeslotId) {
    //             foreach ($studentGroupIds as $groupId) {
    //                 if (isset($usageMap['student_groups'][$groupId][$timeslotId])) {
    //                     $penalty += 1;
    //                 }
    //                 $usageMap['student_groups'][$groupId][$timeslotId] = true;
    //             }
    //         }
    //     }
    //     return $penalty;
    // }
    private function calculateStudentConflicts(Collection $genes, array &$usageMap): int
    {
        $penalty = 0;
        foreach ($genes as $gene) {
            $studentGroupIds = $gene->student_group_id ?? [];
            $isTheoreticalBlock = Str::contains($gene->lecture_unique_id, 'theory');

            foreach ($gene->timeslot_ids as $timeslotId) {
                if ($isTheoreticalBlock) {
                    // للمواد النظرية: تحقق من التعارض مع مواد أخرى فقط
                    if (isset($usageMap['theoretical_shared'][$timeslotId])) {
                        $penalty += 1;
                    }
                    $usageMap['theoretical_shared'][$timeslotId] = true;
                } else {
                    // للمواد العملية: تحقق من تعارض المجموعات
                    foreach ($studentGroupIds as $groupId) {
                        if (isset($usageMap['student_groups'][$groupId][$timeslotId])) {
                            $penalty += 1;
                        }
                        $usageMap['student_groups'][$groupId][$timeslotId] = true;
                    }
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
}
