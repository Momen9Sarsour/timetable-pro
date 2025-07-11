<?php

namespace App\Services;

use App\Models\Population;
use App\Models\Chromosome;
use App\Models\Gene;
use App\Models\Section;
use App\Models\Instructor;
use App\Models\Room;
use App\Models\Timeslot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class GeneticAlgorithmService
{
    // الخصائص لتخزين البيانات والإعدادات
    private array $settings;
    private Population $populationRun;
    private Collection $sectionsToSchedule;
    private Collection $instructors;
    private Collection $rooms;
    private Collection $timeslots;

    /**
     * تهيئة الـ Service
     */
    public function __construct(array $settings, Population $populationRun)
    {
        $this->settings = $settings;
        $this->populationRun = $populationRun;
        Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
    }

    /**
     * الدالة الرئيسية لتشغيل الخوارزمية
     */
    public function run()
    {
        try {
            $this->populationRun->update(['status' => 'running', 'start_time' => now()]);
            // 1. تحميل كل البيانات اللازمة
            $this->loadDataForContext();

            // 2. إنشاء الجيل الأول وتقييمه
            $currentGenerationNumber = 1;
            $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
            $this->evaluateFitness($currentPopulation);
            Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

            // 3. حلقة تطور الأجيال
            $maxGenerations = $this->settings['max_generations'];
            while ($currentGenerationNumber < $maxGenerations) {
                // التحقق من شرط التوقف
                $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
                if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
                    Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
                    break;
                }

                // اختيار الآباء
                $parents = $this->selectParents($currentPopulation);

                // إنشاء الجيل الجديد
                $currentGenerationNumber++;
                $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);

                // تقييم الجيل الجديد
                $this->evaluateFitness($currentPopulation);
                Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
            }

            // 4. تحديث النتيجة النهائية
            $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
            $this->populationRun->update([
                'status' => 'completed',
                'end_time' => now(),
                'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
            ]);
            // dd([
            //     'currentPopulation' => $currentPopulation,
            //     'bestInGen' => $bestInGen,
            //     'finalBest' => $finalBest,
            //     '$finalBest->chromosome_id' => $finalBest->chromosome_id,
            // ]);
            Log::info("GA Run ID: {$this->populationRun->population_id} completed.");
        } catch (Exception $e) {
            Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            $this->populationRun->update(['status' => 'failed']);
            throw $e; // Throw exception to fail the job
        }
    }

    /**
     * تحميل البيانات المفلترة بناءً على السياق (سنة وفصل)
     */
    private function loadDataForContext()
    {
        Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

        // جلب الشعب المحددة للسياق
        $this->sectionsToSchedule = Section::where('academic_year', $this->settings['academic_year'])
            ->where('semester', $this->settings['semester'])->get();
        if ($this->sectionsToSchedule->isEmpty()) {
            throw new Exception("No sections found for the selected context (Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}).");
        }

        // جلب كل الموارد الأخرى المتاحة
        $this->instructors = Instructor::all();
        $this->rooms = Room::all();
        $this->timeslots = Timeslot::all(); // *** نعبئ this->timeslots هنا ***

        if ($this->instructors->isEmpty() || $this->rooms->isEmpty() || $this->timeslots->isEmpty()) {
            throw new Exception("Missing essential data (instructors, rooms, or timeslots).");
        }
        Log::info("Data loaded: " . $this->sectionsToSchedule->count() . " sections to be scheduled.");
    }

    /**
     * إنشاء الجيل الأول من الجداول العشوائية
     */
    private function createInitialPopulation(int $generationNumber): Collection
    {
        Log::info("Creating initial population (Generation #{$generationNumber})");
        $newChromosomesData = [];
        for ($i = 0; $i < $this->settings['population_size']; $i++) {
            $newChromosomesData[] = [
                'population_id' => $this->populationRun->population_id,
                'penalty_value' => -1, // قيمة مبدئية
                'generation_number' => $generationNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        Chromosome::insert($newChromosomesData); // إدخال كل الكروموسومات دفعة واحدة

        // جلب الكروموسومات التي تم إنشاؤها للتو للحصول على IDs
        $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)
            ->where('generation_number', $generationNumber)->get();

        $allGenesToInsert = [];
        foreach ($createdChromosomes as $chromosome) {
            foreach ($this->sectionsToSchedule as $section) {
                $allGenesToInsert[] = [
                    'chromosome_id' => $chromosome->chromosome_id,
                    'section_id' => $section->id,
                    'instructor_id' => $this->instructors->random()->id,
                    'room_id' => $this->rooms->random()->id,
                    'timeslot_id' => $this->timeslots->random()->id, // *** استخدام الطريقة الصحيحة ***
                ];
            }
        }

        // إدخال كل الجينات لكل الكروموسومات دفعة واحدة (أكثر كفاءة)
        foreach (array_chunk($allGenesToInsert, 500) as $chunk) {
            Gene::insert($chunk);
        }


        // dd([
        //     'createdChromosomes' => $createdChromosomes,
        //     '$this->timeslots->random()->id' => $this->timeslots->random()->id,
        //     'allGenesToInsert' => $allGenesToInsert,
        //     'array_chunk($allGenesToInsert,500)' => array_chunk($allGenesToInsert,500),
        // ]);
        return $createdChromosomes;
    }

    /**
     * تقييم جودة كل جدول في المجموعة
     */
    private function evaluateFitness(Collection $chromosomes)
    {
        Log::info("Evaluating fitness for " . $chromosomes->count() . " chromosomes...");
        foreach ($chromosomes as $chromosome) {
            $genes = $chromosome->genes()->with(['instructor', 'room', 'timeslot', 'section.planSubject.subject'])->get();
            // $genes = $chromosome->genes()->with(['instructor', 'room', 'timeslot', 'section.planSubject.subject', 'section.activity_type'])->get();
            $totalPenalty = 0;
            $genesByTimeslot = $genes->groupBy('timeslot_id');
            foreach ($genesByTimeslot as $genesInSlot) {
                if ($genesInSlot->count() > 1) {
                    $totalPenalty += ($genesInSlot->count() - $genesInSlot->pluck('instructor_id')->unique()->count()) * 1000;
                    $totalPenalty += ($genesInSlot->count() - $genesInSlot->pluck('room_id')->unique()->count()) * 1000;
                    $totalPenalty += ($genesInSlot->count() - $genesInSlot->pluck('section_id')->unique()->count()) * 1000;
                }
            }
            // ... (باقي حسابات الـ penalty للسعة والنوع) ...
            $chromosome->update(['penalty_value' => $totalPenalty]);
        }
        // dd([
        //     'chromosomes' => $chromosomes,
        //     'genes' => $chromosome->genes->all(),
        //     'genesByTimeslot' => $genesByTimeslot,
        // ]);

        Log::info("Fitness evaluation completed.");
    }

    /**
     * اختيار الآباء (Tournament Selection)
     */
    private function selectParents(Collection $population): array
    {
        $parents = [];
        $populationSize = $this->settings['population_size'];
        $tournamentSize = 3; // يمكن جعله إعداداً
        for ($i = 0; $i < $populationSize; $i++) {
            $parents[] = $population->random($tournamentSize)->sortBy('penalty_value')->first();
        }
        // dd([
        //     'population' => $population,
        //     'parents' => $parents,
        // ]);
        return $parents;
    }

    /**
     * إنشاء جيل جديد بالتزاوج والطفرة
     */
    private function createNewGeneration(array $parents, int $nextGenerationNumber): Collection
    {
        Log::info("Creating new generation #{$nextGenerationNumber}");
        $childrenChromosomesData = [];
        $populationSize = $this->settings['population_size'];
        $parentPool = $parents;

        for ($i = 0; $i < $populationSize; $i += 2) {
            if (count($parentPool) < 2) {
                $parentPool = $parents;
            }
            $p1_key = array_rand($parentPool);
            $parent1 = $parentPool[$p1_key];
            unset($parentPool[$p1_key]);
            $p2_key = array_rand($parentPool);
            $parent2 = $parentPool[$p2_key];
            unset($parentPool[$p2_key]);

            [$child1GenesData, $child2GenesData] = $this->performCrossover($parent1, $parent2);

            $childrenChromosomesData[] = $this->performMutation($child1GenesData);
            if (count($childrenChromosomesData) < $populationSize) {
                $childrenChromosomesData[] = $this->performMutation($child2GenesData);
            }
        }


        $newlyCreatedChromosomes = [];
        foreach ($childrenChromosomesData as $genesToInsert) {
            $newlyCreatedChromosomes[] = $this->saveChildChromosome($genesToInsert, $nextGenerationNumber);
        }
        // dd([
        //     'nextGenerationNumber' => $nextGenerationNumber,
        //     'parents' => $parents,
        //     'parentPool' => $parentPool,
        //     'child1GenesData' => $child1GenesData,
        //     'child2GenesData' => $child2GenesData,
        //     'childrenChromosomesData' => $childrenChromosomesData,
        //     'newlyCreatedChromosomes' => $newlyCreatedChromosomes,
        // ]);
        return collect($newlyCreatedChromosomes);
    }

    /**
     * تنفيذ التزاوج (Single-Point Crossover) - آمن
     */
    private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
    {
        $p1Genes = $parent1->genes()->get()->keyBy('section_id');
        $p2Genes = $parent2->genes()->get()->keyBy('section_id');
        $child1GenesData = [];
        $child2GenesData = [];
        $crossoverPoint = rand(1, $this->sectionsToSchedule->count() - 1);
        $currentIndex = 0;

        foreach ($this->sectionsToSchedule as $section) {
            $sectionId = $section->id;
            $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Genes : $p2Genes;
            $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Genes : $p1Genes;
            $gene1 = $sourceForChild1->get($sectionId) ?? $p2Genes->get($sectionId);
            $gene2 = $sourceForChild2->get($sectionId) ?? $p1Genes->get($sectionId);

            $child1GenesData[] = $gene1 ? $this->extractGeneData($gene1) : $this->createRandomGeneData($sectionId);
            $child2GenesData[] = $gene2 ? $this->extractGeneData($gene2) : $this->createRandomGeneData($sectionId);
            $currentIndex++;
        }
        return [$child1GenesData, $child2GenesData];
    }

    /**
     * تنفيذ الطفرة
     */
    private function performMutation(array $genes): array
    {
        foreach ($genes as &$geneData) {
            if (lcg_value() < $this->settings['mutation_rate']) {
                $geneData['timeslot_id'] = $this->timeslots->random()->id; // ** استخدام الطريقة الصحيحة **
            }
        }
        return $genes;
    }


    /**
     * دوال مساعدة
     */
    private function saveChildChromosome(array $genes, int $generationNumber): Chromosome
    {
        $chromosome = Chromosome::create(['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber]);
        foreach ($genes as &$geneData) {
            $geneData['chromosome_id'] = $chromosome->chromosome_id;
        }
        Gene::insert($genes);
        return $chromosome;
    }
    private function extractGeneData($gene): array
    {
        return ['section_id' => $gene->section_id, 'instructor_id' => $gene->instructor_id, 'room_id' => $gene->room_id, 'timeslot_id' => $gene->timeslot_id];
    }

    private function createRandomGeneData(int $sectionId): array
    {
        return [
            'section_id' => $sectionId,
            'instructor_id' => $this->instructors->random()->id,
            'room_id' => $this->rooms->random()->id,
            'timeslot_id' => $this->timeslots->random()->id, // ** استخدام الطريقة الصحيحة **
        ];
    }
}
