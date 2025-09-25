<?php

// namespace App\Services;

// use App\Models\Population;
// use App\Models\Chromosome;
// use App\Models\Gene;
// use App\Models\Section;
// use App\Models\Instructor;
// use App\Models\Room;
// use App\Models\Timeslot;
// use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Str;
// use Exception;

// class GeneticAlgorithmService
// {
//     // --- خصائص لتخزين الإعدادات والبيانات ---
//     private array $settings;
//     private Population $populationRun;
//     private Collection $sectionsToSchedule; // **(العودة للبساطة)** قائمة بالشعب المطلوب جدولتها (جين واحد لكل شعبة)
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;
//     private Collection $timeslots;

//     // *** (جديد ومهم) ***
//     // خريطة لربط كل شعبة بمجموعة الطلاب التي تنتمي إليها
//     private array $studentGroupMap = [];

//     /**
//      * دالة البناء (Constructor)
//      * تستقبل إعدادات الخوارزمية وسجل عملية التشغيل
//      */
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     /**
//      * الدالة الرئيسية لتشغيل الخوارزمية بالكامل
//      */
//     public function run()
//     {
//         try {
//             $this->populationRun->update(['status' => 'running', 'start_time' => now()]);

//             // 1. تحميل وتجهيز البيانات
//             $this->loadDataForContext();

//             // 2. إنشاء الجيل الأول وتقييمه
//             $currentGenerationNumber = 1;
//             $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
//             $this->evaluateFitness($currentPopulation);
//             Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//             // 3. حلقة تطور الأجيال
//             $maxGenerations = $this->settings['max_generations'];
//             while ($currentGenerationNumber < $maxGenerations) {
//                 $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
//                 if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//                     Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//                     break;
//                 }

//                 $parents = $this->selectParents($currentPopulation);
//                 $currentGenerationNumber++;
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//                 $this->evaluateFitness($currentPopulation);
//                 Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//             }

//             // 4. تحديث النتيجة النهائية في قاعدة البيانات
//             $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
//             $this->populationRun->update([
//                 'status' => 'completed', 'end_time' => now(),
//                 'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
//             ]);
//             Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
//         } catch (Exception $e) {
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     /**
//      * دالة لتحميل كل البيانات وتجهيزها للخوارزمية
//      */
//     private function loadDataForContext()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         // **(العودة للبساطة)** جلب الشعب المطلوبة فقط
//         $this->sectionsToSchedule = Section::with('planSubject.subject.instructors', 'planSubject.plan')
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])->get();

//         if ($this->sectionsToSchedule->isEmpty()) throw new Exception("No sections found for the selected context.");

//         // --- (المنطق الجديد) بناء خريطة مجموعات الطلاب الذكية ---
//         $this->buildStudentGroupMap($this->sectionsToSchedule);

//         // --- تحميل باقي البيانات (لا تغيير) ---
//         $this->instructors = Instructor::with('subjects')->get();
//         $this->timeslots = Timeslot::all();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();

//         if ($this->instructors->isEmpty() || ($this->theoryRooms->isEmpty() && $this->practicalRooms->isEmpty()) || $this->timeslots->isEmpty()) {
//             throw new Exception("Missing essential data (instructors, rooms, or timeslots).");
//         }
//         Log::info("Data loaded: " . $this->sectionsToSchedule->count() . " sections will be scheduled.");
//     }

//     /**
//      * دالة ذكية لبناء خريطة تربط كل شعبة بمجموعة الطلاب الخاصة بها
//      */
//     private function buildStudentGroupMap(Collection $sections)
//     {
//         $this->studentGroupMap = [];
//         // نجمع الشعب حسب السياق العام (بدون رقم الشعبة)
//         $sectionsByContext = $sections->groupBy(function($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $sectionsInContext) {
//             // نحدد عدد مجموعات الطلاب بناءً على أكبر عدد شعب عملية لأي مادة
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             // نمر على كل مجموعة طلاب لنحدد شعبها
//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 // الشعب النظرية مشتركة لكل المجموعات في هذا السياق
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id] = $groupIndex;
//                 }

//                 // الشعب العملية يتم توزيعها
//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
//                 foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
//                     }
//                 }
//             }
//         }
//     }

//     /**
//      * دالة لإنشاء الجيل الأول من الحلول العشوائية (لكن بذكاء)
//      */
//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//         }
//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $genesToInsert = [];
//             foreach ($this->sectionsToSchedule as $section) {
//                 $instructor = $this->getRandomInstructorForSection($section);
//                 $room = $this->getRandomRoomForSection($section);
//                 $genesToInsert[] = [
//                     'chromosome_id' => $chromosome->chromosome_id,
//                     'section_id' => $section->id,
//                     'instructor_id' => $instructor->id,
//                     'room_id' => $room->id,
//                     'timeslot_id' => $this->timeslots->random()->id,
//                 ];
//             }
//             // إدخال الجينات على دفعات لكل كروموسوم لتجنب استهلاك الذاكرة
//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     /**
//      * دالة مساعدة لاختيار مدرس مناسب للمادة
//      */
//     private function getRandomInstructorForSection(Section $section)
//     {
//         $subject = optional($section->planSubject)->subject;
//         if (!$subject) return $this->instructors->random();

//         $suitableInstructors = $this->instructors->filter(fn ($instructor) => $instructor->subjects->contains($subject->id));
//         if ($suitableInstructors->isNotEmpty()) return $suitableInstructors->random();

//         Log::warning("No specific instructors assigned to subject '{$subject->subject_name}'. Choosing a random instructor.");
//         return $this->instructors->random();
//     }

//     /**
//      * دالة مساعدة لاختيار قاعة مناسبة للشعبة
//      */
//     private function getRandomRoomForSection(Section $section)
//     {
//         $roomsSource = ($section->activity_type === 'Practical') ? $this->practicalRooms : $this->theoryRooms;
//         if ($roomsSource->isEmpty()) {
//             $roomsSource = ($section->activity_type === 'Practical') ? $this->theoryRooms : $this->practicalRooms;
//             if($roomsSource->isEmpty()) return Room::all()->random();
//         }

//         $suitableRooms = $roomsSource->where('room_size', '>=', $section->student_count);
//         return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $roomsSource->random();
//     }

//     /**
//      * دالة تقييم الجودة (Fitness Function)، تقوم بحساب العقوبات
//      */
//     private function evaluateFitness(Collection $chromosomes)
//     {
//         foreach ($chromosomes as $chromosome) {
//             $genes = $chromosome->genes()->with(['instructor', 'room', 'timeslot', 'section.planSubject.subject'])->get();
//             $totalPenalty = 0;

//             // --- فحص التعارضات العامة (مدرس، قاعة) ---
//             $genesByTimeslot = $genes->groupBy('timeslot_id');
//             foreach ($genesByTimeslot as $genesInSlot) {
//                 if ($genesInSlot->count() > 1) {
//                     $totalPenalty += ($genesInSlot->count() - $genesInSlot->pluck('instructor_id')->unique()->count()) * 1000;
//                     $totalPenalty += ($genesInSlot->count() - $genesInSlot->pluck('room_id')->unique()->count()) * 1000;
//                 }
//             }

//             // --- فحص تعارضات "مجموعات الطلاب" باستخدام الخريطة ---
//             $studentGroupTimeslotUsage = [];
//             foreach ($genes as $gene) {
//                 $sectionId = $gene->section_id;
//                 $timeslotId = $gene->timeslot_id;

//                 if (isset($this->studentGroupMap[$sectionId])) {
//                     $studentGroupId = $this->studentGroupMap[$sectionId];
//                     $key = $studentGroupId . '-' . $timeslotId;
//                     if (isset($studentGroupTimeslotUsage[$key])) {
//                         $totalPenalty += 2000; // عقوبة تعارض طالب
//                     }
//                     $studentGroupTimeslotUsage[$key] = true;
//                 }
//             }

//             // --- فحص القيود الخاصة بكل جين (سعة، نوع، أهلية) ---
//             foreach ($genes as $gene) {
//                 if (!$gene->section || !$gene->room || !$gene->instructor) continue;

//                 // قيد سعة القاعة
//                 if ($gene->section->student_count > $gene->room->room_size) $totalPenalty += 800;

//                 // قيد نوع القاعة
//                 $roomType = optional($gene->room->roomType)->room_type_name ?? '';
//                 $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);
//                 if ($gene->section->activity_type == 'Practical' && !$isPracticalRoom) $totalPenalty += 600;
//                 if ($gene->section->activity_type == 'Theory' && $isPracticalRoom) $totalPenalty += 600;

//                 // قيد أهلية المدرس
//                 if (!optional($gene->instructor)->canTeach(optional(optional($gene->section)->planSubject)->subject)) $totalPenalty += 2000;
//             }

//             DB::table('chromosomes')->where('chromosome_id', $chromosome->chromosome_id)->update(['penalty_value' => $totalPenalty]);
//             $chromosome->penalty_value = $totalPenalty;
//         }
//     }

//     /**
//      * دالة اختيار الآباء للجيل القادم
//      */
//     private function selectParents(Collection $population): array
//     {
//         $parents = [];
//         $tournamentSize = 3;
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $parents[] = $population->random($tournamentSize)->sortBy('penalty_value')->first();
//         }
//         return $parents;
//     }

//     /**
//      * دالة إنشاء جيل جديد من الآباء
//      */
//     private function createNewGeneration(array $parents, int $nextGenerationNumber): Collection
//     {
//         Log::info("Creating new generation #{$nextGenerationNumber}");
//         $childrenData = [];
//         $parentPool = $parents;

//         for ($i = 0; $i < $this->settings['population_size']; $i += 2) {
//             if (count($parentPool) < 2) $parentPool = $parents;

//             $p1_key = array_rand($parentPool); $parent1 = $parentPool[$p1_key]; unset($parentPool[$p1_key]);
//             $p2_key = array_rand($parentPool); $parent2 = $parentPool[$p2_key]; unset($parentPool[$p2_key]);

//             [$child1GenesData, $child2GenesData] = $this->performCrossover($parent1, $parent2);

//             $childrenData[] = $this->performMutation($child1GenesData);
//             if (count($childrenData) < $this->settings['population_size']) {
//                 $childrenData[] = $this->performMutation($child2GenesData);
//             }
//         }

//         $newlyCreatedChromosomes = [];
//         foreach ($childrenData as $genesToInsert) {
//             $newlyCreatedChromosomes[] = $this->saveChildChromosome($genesToInsert, $nextGenerationNumber);
//         }
//         return collect($newlyCreatedChromosomes);
//     }

//     /**
//      * دالة التزاوج (Crossover)
//      */
//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Genes = $parent1->genes()->get()->keyBy('section_id');
//         $p2Genes = $parent2->genes()->get()->keyBy('section_id');
//         $child1Genes = []; $child2Genes = [];

//         $crossoverPoint = rand(1, $this->sectionsToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->sectionsToSchedule as $section) {
//             $sectionId = $section->id;
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Genes : $p2Genes;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Genes : $p1Genes;

//             $gene1 = $sourceForChild1->get($sectionId) ?? $p2Genes->get($sectionId);
//             $gene2 = $sourceForChild2->get($sectionId) ?? $p1Genes->get($sectionId);

//             $child1Genes[] = $gene1 ? $this->extractGeneData($gene1) : $this->createRandomGeneData($sectionId);
//             $child2Genes[] = $gene2 ? $this->extractGeneData($gene2) : $this->createRandomGeneData($sectionId);
//             $currentIndex++;
//         }
//         return [$child1Genes, $child2Genes];
//     }

//     /**
//      * دالة الطفرة (Mutation)
//      */
//     private function performMutation(array $genes): array
//     {
//         foreach ($genes as &$geneData) {
//             if (lcg_value() < $this->settings['mutation_rate']) {
//                 $mutationType = rand(1, 3);
//                 $section = $this->sectionsToSchedule->find($geneData['section_id']);
//                 if ($section) {
//                     switch ($mutationType) {
//                         case 1: $geneData['timeslot_id'] = $this->timeslots->random()->id; break;
//                         case 2: $geneData['room_id'] = $this->getRandomRoomForSection($section)->id; break;
//                         case 3: $geneData['instructor_id'] = $this->getRandomInstructorForSection($section)->id; break;
//                     }
//                 }
//             }
//         }
//         return $genes;
//     }

//     /**
//      * دالة مساعدة لحفظ الكروموسوم الابن وجيناته في قاعدة البيانات
//      */
//     private function saveChildChromosome(array $genes, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create(['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber]);

//         $genesWithChromosomeId = array_map(fn($g) => array_merge($g, ['chromosome_id' => $chromosome->chromosome_id]), $genes);

//         foreach (array_chunk($genesWithChromosomeId, 500) as $chunk) {
//             Gene::insert($chunk);
//         }
//         return $chromosome;
//     }

//     /**
//      * دالة مساعدة لاستخراج بيانات الجين كمصفوفة
//      */
//     private function extractGeneData($gene): array
//     {
//         return ['section_id' => $gene->section_id, 'instructor_id' => $gene->instructor_id, 'room_id' => $gene->room_id, 'timeslot_id' => $gene->timeslot_id];
//     }

//     /**
//      * دالة مساعدة لإنشاء جين عشوائي في حالة عدم وجوده في أحد الآباء
//      */
//     private function createRandomGeneData(int $sectionId): array
//     {
//         $section = $this->sectionsToSchedule->find($sectionId);
//         if (!$section) return [];
//         $instructor = $this->getRandomInstructorForSection($section);
//         $room = $this->getRandomRoomForSection($section);
//         return [
//             'section_id' => $sectionId,
//             'instructor_id' => $instructor->id,
//             'room_id' => $room->id,
//             'timeslot_id' => $this->timeslots->random()->id,
//         ];
//     }
// }


// namespace App\Services;

// use App\Models\Population;
// use App\Models\Chromosome;
// use App\Models\Gene;
// use App\Models\Section;
// use App\Models\Instructor;
// use App\Models\Room;
// use App\Models\Timeslot;
// use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Str;
// use Exception;

// class GeneticAlgorithmService
// {
//     // --- خصائص لتخزين الإعدادات والبيانات ---
//     private array $settings;
//     private Population $populationRun;
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;
//     private Collection $timeslots;

//     // (جديد ومحسن) قائمة بالمحاضرات المطلوب جدولتها (شعبة + عدد الفترات كوحدة واحدة)
//     private Collection $lecturesToSchedule;

//     // (جديد) خريطة الأوقات المتتالية لتسريع البحث
//     private array $consecutiveTimeslotsMap = [];

//     // (جديد ومهم جداً) خريطة لربط كل "شعبة" بمجموعة الطلاب التي تنتمي إليها
//     private array $studentGroupMap = [];

//     /**
//      * دالة البناء (Constructor)
//      * تستقبل إعدادات الخوارزمية وسجل عملية التشغيل
//      */
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     /**
//      * الدالة الرئيسية لتشغيل الخوارزمية بالكامل
//      */
//     public function run()
//     {
//         try {
//             $this->populationRun->update(['status' => 'running', 'start_time' => now()]);

//             // 1. تحميل وتجهيز كل البيانات اللازمة
//             $this->loadAndPrepareData();

//             // 2. إنشاء الجيل الأول وتقييمه
//             $currentGenerationNumber = 1;
//             $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
//             $this->evaluateFitness($currentPopulation);
//             Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//             // 3. حلقة تطور الأجيال
//             $maxGenerations = $this->settings['max_generations'];
//             while ($currentGenerationNumber < $maxGenerations) {
//                 $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
//                 if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//                     Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//                     break;
//                 }

//                 $parents = $this->selectParents($currentPopulation);
//                 $currentGenerationNumber++;
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//                 $this->evaluateFitness($currentPopulation);
//                 Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//             }

//             // 4. تحديث النتيجة النهائية في قاعدة البيانات
//             $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
//             $this->populationRun->update([
//                 'status' => 'completed', 'end_time' => now(),
//                 'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
//             ]);
//             Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
//         } catch (Exception $e) {
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     /**
//      * دالة لتحميل كل البيانات وتجهيزها للخوارزمية
//      * هذه الدالة تقوم بكل العمل التحضيري الذكي
//      */
//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with('planSubject.subject', 'planSubject.plan')
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])->get();
//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         // --- الخطوة 1: (المنطق الجديد) تحويل الشعب إلى "بلوكات محاضرات" ---
//         $this->lecturesToSchedule = collect();
//         foreach ($sections as $section) {
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             // نحسب إجمالي عدد الفترات الزمنية المطلوبة لهذا البلوك (المحاضرة)
//             $totalSlotsNeeded = 0;
//             if ($section->activity_type == 'Theory' || $section->activity_type == 'Theory & Practical' || $section->activity_type == 'theory' || $section->activity_type == 'نظري') {
//                 $totalSlotsNeeded = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//             }if ($section->activity_type == 'Practical' || $section->activity_type == 'Theory & Practical' || $section->activity_type == 'practical' || $section->activity_type == 'عملي') {
//                 $totalSlotsNeeded = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//             }

//             // نضيف المحاضرة كبلوك واحد فقط إذا كانت تحتاج فترات زمنية
//             if ($totalSlotsNeeded > 0) {
//                 $this->lecturesToSchedule->push((object)[
//                     'section' => $section,
//                     'slots_needed' => $totalSlotsNeeded, // إجمالي عدد الفترات المتتالية المطلوبة
//                     'unique_id' => $section->id // المعرف الفريد الآن هو فقط ID الشعبة
//                 ]);
//             }
//         }
//         if ($this->lecturesToSchedule->isEmpty()) throw new Exception("No lectures to schedule were found after processing credit hours.");

//         // --- الخطوة 2: بناء "خريطة مجموعات الطلاب" الذكية ---
//         $this->buildStudentGroupMap($sections);

//         // --- الخطوة 3: تحميل باقي الموارد وبناء خريطة الأوقات المتتالية ---
//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();
//         $this->buildConsecutiveTimeslotsMap();

//         Log::info("Data loaded: " . $this->lecturesToSchedule->count() . " lectures will be scheduled.");
//     }

//     /**
//      * دالة ذكية لبناء خريطة تربط كل شعبة بمجموعة الطلاب الخاصة بها
//      */
//     private function buildStudentGroupMap(Collection $sections)
//     {
//         $this->studentGroupMap = [];
//         $sectionsByContext = $sections->groupBy(function($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $sectionsInContext) {
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id] = $groupIndex;
//                 }

//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
//                 foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
//                     }
//                 }
//             }
//         }
//     }

//     /**
//      * دالة مساعدة تبني مصفوفة لتسريع إيجاد الفترات الزمنية المتتالية
//      */
//     private function buildConsecutiveTimeslotsMap()
//     {
//         $timeslotsByDay = $this->timeslots->groupBy('day');
//         $this->consecutiveTimeslotsMap = [];
//         foreach ($timeslotsByDay as $dayTimeslots) {
//             $dayTimeslotsValues = $dayTimeslots->values();
//             for ($i = 0; $i < $dayTimeslotsValues->count(); $i++) {
//                 $currentSlot = $dayTimeslotsValues[$i];
//                 $this->consecutiveTimeslotsMap[$currentSlot->id] = [];
//                 for ($j = $i + 1; $j < $dayTimeslotsValues->count(); $j++) {
//                     $nextSlot = $dayTimeslotsValues[$j];
//                     if ($nextSlot->start_time == $currentSlot->end_time) {
//                         $this->consecutiveTimeslotsMap[$currentSlot->id][] = $nextSlot->id;
//                         $currentSlot = $nextSlot;
//                     } else {
//                         break;
//                     }
//                 }
//             }
//         }
//     }

//     /**
//      * دالة لإنشاء الجيل الأول من الحلول العشوائية (لكن بذكاء)
//      */
//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//         }
//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $genesToInsert = [];
//             foreach ($this->lecturesToSchedule as $lecture) {
//                 $instructor = $this->getRandomInstructorForSection($lecture->section);
//                 $room = $this->getRandomRoomForSection($lecture->section);
//                 $consecutiveSlots = $this->findRandomConsecutiveTimeslots($lecture->slots_needed);
//                 foreach ($consecutiveSlots as $timeslotId) {
//                     $genesToInsert[] = ['chromosome_id' => $chromosome->chromosome_id, 'lecture_unique_id' => $lecture->unique_id, 'section_id' => $lecture->section->id, 'instructor_id' => $instructor->id, 'room_id' => $room->id, 'timeslot_id' => $timeslotId];
//                 }
//             }
//             // إدخال الجينات على دفعات لكل كروموسوم لتجنب استهلاك الذاكرة
//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     /**
//      * دالة مساعدة لإيجاد فترات زمنية متتالية عشوائية
//      */
//     private function findRandomConsecutiveTimeslots(int $slotsNeeded): array
//     {
//         if ($slotsNeeded == 1) {
//             return [$this->timeslots->random()->id];
//         }
//         $attempts = 0;
//         while ($attempts < 200) {
//             $startSlot = $this->timeslots->random();
//             if (isset($this->consecutiveTimeslotsMap[$startSlot->id]) && count($this->consecutiveTimeslotsMap[$startSlot->id]) >= ($slotsNeeded - 1)) {
//                 return array_merge([$startSlot->id], array_slice($this->consecutiveTimeslotsMap[$startSlot->id], 0, $slotsNeeded - 1));
//             }
//             $attempts++;
//         }
//         Log::warning("Could not find {$slotsNeeded} consecutive slots, returning a single random slot as fallback.");
//         return [$this->timeslots->random()->id];
//     }

//     /**
//      * دالة مساعدة لاختيار مدرس مناسب للمادة
//      */
//     private function getRandomInstructorForSection(Section $section)
//     {
//         $subject = optional($section->planSubject)->subject;
//         if (!$subject) return $this->instructors->random();

//         $suitableInstructors = $this->instructors->filter(fn ($instructor) => $instructor->subjects->contains($subject->id));
//         if ($suitableInstructors->isNotEmpty()) return $suitableInstructors->random();

//         Log::warning("No specific instructors assigned to subject '{$subject->subject_name}'. Choosing a random instructor.");
//         return $this->instructors->random();
//     }

//     /**
//      * دالة مساعدة لاختيار قاعة مناسبة للشعبة
//      */
//     private function getRandomRoomForSection(Section $section)
//     {
//         $roomsSource = ($section->activity_type === 'Practical') ? $this->practicalRooms : $this->theoryRooms;
//         if ($roomsSource->isEmpty()) {
//             $roomsSource = ($section->activity_type === 'Practical') ? $this->theoryRooms : $this->practicalRooms;
//             if($roomsSource->isEmpty()) return Room::all()->random();
//         }

//         $suitableRooms = $roomsSource->where('room_size', '>=', $section->student_count);
//         return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $roomsSource->random();
//     }

//     /**
//      * دالة تقييم الجودة (Fitness Function)، تقوم بحساب العقوبات
//      */
//     private function evaluateFitness(Collection $chromosomes)
//     {
//         foreach ($chromosomes as $chromosome) {
//             $genes = $chromosome->genes()->with(['instructor', 'room', 'timeslot', 'section.planSubject.subject'])->get();
//             $totalPenalty = 0;

//             // تجميع الجينات حسب الوقت لفحص التعارضات العامة (مدرس، قاعة)
//             $genesByTimeslot = $genes->groupBy('timeslot_id');
//             foreach ($genesByTimeslot as $genesInSlot) {
//                 if ($genesInSlot->count() > 1) {
//                     $totalPenalty += ($genesInSlot->count() - $genesInSlot->pluck('instructor_id')->unique()->count()) * 1000;
//                     $totalPenalty += ($genesInSlot->count() - $genesInSlot->pluck('room_id')->unique()->count()) * 1000;
//                 }
//             }

//             // --- فحص تعارضات "مجموعات الطلاب" باستخدام الخريطة ---
//             $studentGroupTimeslotUsage = [];
//             foreach ($genes as $gene) {
//                 $sectionId = $gene->section_id;
//                 $timeslotId = $gene->timeslot_id;

//                 if (isset($this->studentGroupMap[$sectionId])) {
//                     $studentGroupId = $this->studentGroupMap[$sectionId];
//                     $key = $studentGroupId . '-' . $timeslotId;
//                     if (isset($studentGroupTimeslotUsage[$key])) {
//                         $totalPenalty += 2000; // عقوبة تعارض طالب
//                     }
//                     $studentGroupTimeslotUsage[$key] = true;
//                 }
//             }

//             // فحص القيود الخاصة بكل محاضرة (نمر على المحاضرات كوحدات)
//             foreach ($genes->unique('lecture_unique_id') as $representativeGene) {
//                 if (!$representativeGene->section || !$representativeGene->room || !$representativeGene->instructor) continue;

//                 // قيد سعة القاعة
//                 if ($representativeGene->section->student_count > $representativeGene->room->room_size) $totalPenalty += 800;

//                 // قيد نوع القاعة
//                 $roomType = optional($representativeGene->room->roomType)->room_type_name ?? '';
//                 $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);
//                 if ($representativeGene->section->activity_type == 'Practical' && !$isPracticalRoom) $totalPenalty += 600;
//                 if ($representativeGene->section->activity_type == 'Theory' && $isPracticalRoom) $totalPenalty += 600;

//                 // قيد أهلية المدرس
//                 if (!optional($representativeGene->instructor)->canTeach(optional(optional($representativeGene->section)->planSubject)->subject)) $totalPenalty += 2000;
//             }

//             // نستخدم تحديث مباشر لقاعدة البيانات لتجنب مشاكل الذاكرة مع Eloquent
//             DB::table('chromosomes')->where('chromosome_id', $chromosome->chromosome_id)->update(['penalty_value' => $totalPenalty]);
//             $chromosome->penalty_value = $totalPenalty; // تحديث الكائن في الذاكرة أيضاً للاستخدام في نفس الجيل
//         }
//     }

//     /**
//      * دالة اختيار الآباء للجيل القادم
//      */
//     private function selectParents(Collection $population): array
//     {
//         $parents = [];
//         $tournamentSize = 3;
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $parents[] = $population->random($tournamentSize)->sortBy('penalty_value')->first();
//         }
//         return $parents;
//     }

//     /**
//      * دالة إنشاء جيل جديد من الآباء
//      */
//     private function createNewGeneration(array $parents, int $nextGenerationNumber): Collection
//     {
//         Log::info("Creating new generation #{$nextGenerationNumber}");
//         $childrenData = [];
//         $parentPool = $parents;

//         for ($i = 0; $i < $this->settings['population_size']; $i += 2) {
//             if (count($parentPool) < 2) $parentPool = $parents;

//             $p1_key = array_rand($parentPool); $parent1 = $parentPool[$p1_key]; unset($parentPool[$p1_key]);
//             $p2_key = array_rand($parentPool); $parent2 = $parentPool[$p2_key]; unset($parentPool[$p2_key]);

//             [$child1Lectures, $child2Lectures] = $this->performCrossover($parent1, $parent2);

//             $childrenData[] = $this->performMutation($child1Lectures);
//             if (count($childrenData) < $this->settings['population_size']) {
//                 $childrenData[] = $this->performMutation($child2Lectures);
//             }
//         }

//         $newlyCreatedChromosomes = [];
//         foreach ($childrenData as $lecturesToInsert) {
//             $newlyCreatedChromosomes[] = $this->saveChildChromosome($lecturesToInsert, $nextGenerationNumber);
//         }
//         return collect($newlyCreatedChromosomes);
//     }

//     /**
//      * دالة التزاوج (Crossover)
//      */
//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Lectures = $parent1->genes()->get()->groupBy('lecture_unique_id');
//         $p2Lectures = $parent2->genes()->get()->groupBy('lecture_unique_id');
//         $child1Lectures = []; $child2Lectures = [];

//         $crossoverPoint = rand(1, $this->lecturesToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lecturesToSchedule as $lecture) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$lecture->unique_id] = $sourceForChild1->get($lecture->unique_id) ?? $p2Lectures->get($lecture->unique_id);
//             $child2Lectures[$lecture->unique_id] = $sourceForChild2->get($lecture->unique_id) ?? $p1Lectures->get($lecture->unique_id);

//             $currentIndex++;
//         }
//         return [$child1Lectures, $child2Lectures];
//     }

//     /**
//      * دالة الطفرة (Mutation)
//      */
//     private function performMutation(array $lectures): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($lectures)) return [];
//             $lectureKeyToMutate = array_rand($lectures);
//             $originalLectureInfo = $this->lecturesToSchedule->firstWhere('unique_id', $lectureKeyToMutate);
//             if(!$originalLectureInfo) return $lectures;

//             $newInstructor = $this->getRandomInstructorForSection($originalLectureInfo->section);
//             $newRoom = $this->getRandomRoomForSection($originalLectureInfo->section);
//             $newTimeslots = $this->findRandomConsecutiveTimeslots($originalLectureInfo->slots_needed);

//             $mutatedGenes = [];
//             foreach ($newTimeslots as $timeslotId) {
//                  $gene = new Gene(['lecture_unique_id' => $lectureKeyToMutate, 'section_id' => $originalLectureInfo->section->id, 'instructor_id' => $newInstructor->id, 'room_id' => $newRoom->id, 'timeslot_id' => $timeslotId]);
//                  $mutatedGenes[] = $gene;
//             }
//             $lectures[$lectureKeyToMutate] = collect($mutatedGenes);
//         }
//         return $lectures;
//     }

//     /**
//      * دالة مساعدة لحفظ الكروموسوم الابن وجيناته في قاعدة البيانات
//      */
//     private function saveChildChromosome(array $lectures, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create(['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber]);

//         $genesToInsert = [];
//         foreach($lectures as $lectureGenes) {
//             if (is_null($lectureGenes)) continue;
//             foreach($lectureGenes as $gene) {
//                 $genesToInsert[] = [
//                     'chromosome_id' => $chromosome->chromosome_id,
//                     'lecture_unique_id' => $gene->lecture_unique_id,
//                     'section_id' => $gene->section_id,
//                     'instructor_id' => $gene->instructor_id,
//                     'room_id' => $gene->room_id,
//                     'timeslot_id' => $gene->timeslot_id,
//                 ];
//             }
//         }

//         foreach (array_chunk($genesToInsert, 500) as $chunk) {
//             Gene::insert($chunk);
//         }
//         return $chromosome;
//     }
// }



/// مش مية المية
// namespace App\Services;

// use App\Models\Population;
// use App\Models\Chromosome;
// use App\Models\Gene;
// use App\Models\Section;
// use App\Models\Instructor;
// use App\Models\Room;
// use App\Models\Timeslot;
// use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Str;
// use Exception;

// class GeneticAlgorithmService
// {
//     // --- خصائص لتخزين الإعدادات والبيانات ---
//     private array $settings;
//     private Population $populationRun;
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;
//     private Collection $timeslots;
//     private Collection $lecturesToSchedule; // قائمة بالمحاضرات المطلوب جدولتها
//     private array $consecutiveTimeslotsMap = []; // خريطة الأوقات المتتالية
//     private array $studentGroupMap = []; // خريطة مجموعات الطلاب

//     /**
//      * دالة البناء (Constructor)
//      */
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     /**
//      * الدالة الرئيسية لتشغيل الخوارزمية بالكامل
//      */
//     public function run()
//     {
//         try {
//             $this->populationRun->update(['status' => 'running', 'start_time' => now()]);
//             $this->loadAndPrepareData();

//             $currentGenerationNumber = 1;
//             $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
//             $this->evaluateFitness($currentPopulation);
//             Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//             $maxGenerations = $this->settings['max_generations'];
//             while ($currentGenerationNumber < $maxGenerations) {
//                 $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
//                 if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//                     Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//                     break;
//                 }

//                 $parents = $this->selectParents($currentPopulation);
//                 $currentGenerationNumber++;
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//                 $this->evaluateFitness($currentPopulation);
//                 Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//             }

//             $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
//             $this->populationRun->update([
//                 'status' => 'completed',
//                 'end_time' => now(),
//                 'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
//             ]);
//             Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
//         } catch (Exception $e) {
//             Log::error("GA Run failed: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     /**
//      * دالة لتحميل وتجهيز البيانات
//      */
//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with('planSubject.subject', 'planSubject.plan')
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])->get();
//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         $this->lecturesToSchedule = collect();
//         foreach ($sections as $section) {
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             $totalSlotsNeeded = 0;
//             if ($section->activity_type == 'Theory' || $section->activity_type == 'Theory & Practical' || $section->activity_type == 'theory' || $section->activity_type == 'نظري') {
//                 $totalSlotsNeeded = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//             }
//             if ($section->activity_type == 'Practical' || $section->activity_type == 'Theory & Practical' || $section->activity_type == 'practical' || $section->activity_type == 'عملي') {
//                 $totalSlotsNeeded = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//             }

//             if ($totalSlotsNeeded > 0) {
//                 $this->lecturesToSchedule->push((object)[
//                     'section' => $section,
//                     'slots_needed' => $totalSlotsNeeded,
//                     'unique_id' => $section->id // المعرف الفريد هو ID الشعبة
//                 ]);
//             }
//         }
//         if ($this->lecturesToSchedule->isEmpty()) throw new Exception("No lectures to schedule were found after processing credit hours.");

//         $this->buildStudentGroupMap($sections);
//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();
//         $this->buildConsecutiveTimeslotsMap();

//         Log::info("Data loaded: " . $this->lecturesToSchedule->count() . " lectures will be scheduled.");
//     }

//     /**
//      * دالة بناء خريطة مجموعات الطلاب
//      */
//     private function buildStudentGroupMap(Collection $sections)
//     {
//         $this->studentGroupMap = [];
//         $sectionsByContext = $sections->groupBy(function ($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $sectionsInContext) {
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id] = $groupIndex;
//                 }

//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
//                 foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
//                     }
//                 }
//             }
//         }
//     }
//     /**
//      * دالة بناء خريطة الأوقات المتتالية
//      */
//     private function buildConsecutiveTimeslotsMap()
//     {
//         $timeslotsByDay = $this->timeslots->groupBy('day');
//         $this->consecutiveTimeslotsMap = [];
//         foreach ($timeslotsByDay as $dayTimeslots) {
//             $dayTimeslotsValues = $dayTimeslots->values();
//             for ($i = 0; $i < $dayTimeslotsValues->count(); $i++) {
//                 $currentSlot = $dayTimeslotsValues[$i];
//                 $this->consecutiveTimeslotsMap[$currentSlot->id] = [];
//                 for ($j = $i + 1; $j < $dayTimeslotsValues->count(); $j++) {
//                     $nextSlot = $dayTimeslotsValues[$j];
//                     if ($nextSlot->start_time == $currentSlot->end_time) {
//                         $this->consecutiveTimeslotsMap[$currentSlot->id][] = $nextSlot->id;
//                         $currentSlot = $nextSlot;
//                     } else {
//                         break;
//                     }
//                 }
//             }
//         }
//     }

//     /**
//      * دالة لإنشاء الجيل الأول (معدلة لضمان إنشاء الجينات بشكل صحيح)
//      */
//     private function createInitialPopulation(int $generationNumber)
//     {
//         Log::info("Creating initial population (Generation #{$generationNumber})");

//         $populationSize = $this->settings['population_size'];
//         $createdChromosomes = collect();

//         for ($i = 0; $i < $populationSize; $i++) {
//             $chromosome = Chromosome::create([
//                 'population_id' => $this->populationRun->population_id,
//                 'penalty_value' => -1,
//                 'generation_number' => $generationNumber,
//             ]);

//             $genesToInsert = [];
//             foreach ($this->lecturesToSchedule as $lecture) {
//                 $instructor = $this->getRandomInstructorForSection($lecture->section);
//                 $room = $this->getRandomRoomForSection($lecture->section);
//                 $consecutiveSlots = $this->findRandomConsecutiveTimeslots($lecture->slots_needed);

//                 // **(تصحيح مهم)**: نضمن أن كل فترة زمنية في البلوك يتم إضافتها كجين منفصل
//                 foreach ($consecutiveSlots as $timeslotId) {
//                     $genesToInsert[] = [
//                         'chromosome_id' => $chromosome->chromosome_id,
//                         'lecture_unique_id' => $lecture->unique_id,
//                         'section_id' => $lecture->section->id,
//                         'instructor_id' => $instructor->id,
//                         'room_id' => $room->id,
//                         'timeslot_id' => $timeslotId,
//                     ];
//                 }
//             }

//             // إدخال جينات هذا الكروموسوم على دفعات
//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//             $createdChromosomes->push($chromosome);
//         }

//         return $createdChromosomes;
//     }

//     /**
//      * دالة مساعدة لإيجاد فترات زمنية متتالية عشوائية
//      */
//     private function findRandomConsecutiveTimeslots(int $slotsNeeded)
//     {
//         // $lectures = [];
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($lectures)) return [];
//             $lectureKeyToMutate = array_rand($lectures);
//             $originalLectureInfo = $this->lecturesToSchedule->firstWhere('unique_id', $lectureKeyToMutate);
//             if (!$originalLectureInfo) return $lectures;

//             $newInstructor = $this->getRandomInstructorForSection($originalLectureInfo->section);
//             $newRoom = $this->getRandomRoomForSection($originalLectureInfo->section);
//             $newTimeslots = $this->findRandomConsecutiveTimeslots($originalLectureInfo->slots_needed);

//             $mutatedGenes = [];
//             foreach ($newTimeslots as $timeslotId) {
//                 $gene = new Gene([
//                     'lecture_unique_id' => $lectureKeyToMutate,
//                     'section_id' => $originalLectureInfo->section->id,
//                     'instructor_id' => $newInstructor->id,
//                     'room_id' => $newRoom->id,
//                     'timeslot_id' => $timeslotId
//                 ]);
//                 $mutatedGenes[] = $gene;
//             }
//             $lectures[$lectureKeyToMutate] = collect($mutatedGenes);
//             return $lectures;
//         }else{
//             return $this->timeslots->random();
//         }
//     }
//     /**
//      * دالة مساعدة لاختيار مدرس مناسب
//      */
//     private function getRandomInstructorForSection(Section $section)
//     {
//         // $lectures = [];
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($lectures)) return [];
//             $lectureKeyToMutate = array_rand($lectures);
//             $originalLectureInfo = $this->lecturesToSchedule->firstWhere('unique_id', $lectureKeyToMutate);
//             if (!$originalLectureInfo) return $lectures;

//             $newInstructor = $this->getRandomInstructorForSection($originalLectureInfo->section);
//             $newRoom = $this->getRandomRoomForSection($originalLectureInfo->section);
//             $newTimeslots = $this->findRandomConsecutiveTimeslots($originalLectureInfo->slots_needed);

//             $mutatedGenes = [];
//             foreach ($newTimeslots as $timeslotId) {
//                 $gene = new Gene(['lecture_unique_id' => $lectureKeyToMutate, 'section_id' => $originalLectureInfo->section->id, 'instructor_id' => $newInstructor->id, 'room_id' => $newRoom->id, 'timeslot_id' => $timeslotId]);
//                 $mutatedGenes[] = $gene;
//             }
//             $lectures[$lectureKeyToMutate] = collect($mutatedGenes);
//             return $lectures;
//         }else{
//             return $this->instructors->random();
//         }
//     }
//     /**
//      * دالة مساعدة لاختيار قاعة مناسبة
//      */
//     private function getRandomRoomForSection(Section $section)
//     {
//         // $lectures = [];
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($lectures)) return [];
//             $lectureKeyToMutate = array_rand($lectures);
//             $originalLectureInfo = $this->lecturesToSchedule->firstWhere('unique_id', $lectureKeyToMutate);
//             if (!$originalLectureInfo) return $lectures;

//             $newInstructor = $this->getRandomInstructorForSection($originalLectureInfo->section);
//             $newRoom = $this->getRandomRoomForSection($originalLectureInfo->section);
//             $newTimeslots = $this->findRandomConsecutiveTimeslots($originalLectureInfo->slots_needed);

//             $mutatedGenes = [];
//             foreach ($newTimeslots as $timeslotId) {
//                 $gene = new Gene([
//                     'lecture_unique_id' => $lectureKeyToMutate,
//                     'section_id' => $originalLectureInfo->section->id,
//                     'instructor_id' => $newInstructor->id,
//                     'room_id' => $newRoom->id,
//                     'timeslot_id' => $timeslotId
//                 ]);
//                 $mutatedGenes[] = $gene;
//             }
//             $lectures[$lectureKeyToMutate] = collect($mutatedGenes);
//             return $lectures;
//         } else {
//             return $this->theoryRooms->random();
//         }
//     }

//     /**
//      * دالة تقييم الجودة (Fitness Function)
//      */
//     private function evaluateFitness(Collection $chromosomes)
//     {
//         foreach ($chromosomes as $chromosome) {
//             $genes = $chromosome->genes()->with(['instructor', 'room', 'timeslot', 'section.planSubject.subject'])->get();
//             $totalPenalty = 0;

//             // تجميع الجينات حسب الوقت لفحص التعارضات العامة (مدرس، قاعة)
//             $genesByTimeslot = $genes->groupBy('timeslot_id');
//             foreach ($genesByTimeslot as $genesInSlot) {
//                 if ($genesInSlot->count() > 1) {
//                     $totalPenalty += ($genesInSlot->count() - $genesInSlot->pluck('instructor_id')->unique()->count()) * 1000;
//                     $totalPenalty += ($genesInSlot->count() - $genesInSlot->pluck('room_id')->unique()->count()) * 1000;
//                 }
//             }

//             // --- فحص تعارضات "مجموعات الطلاب" باستخدام الخريطة ---
//             $studentGroupTimeslotUsage = [];
//             foreach ($genes as $gene) {
//                 $sectionId = $gene->section_id;
//                 $timeslotId = $gene->timeslot_id;

//                 if (isset($this->studentGroupMap[$sectionId])) {
//                     $studentGroupId = $this->studentGroupMap[$sectionId];
//                     $key = $studentGroupId . '-' . $timeslotId;
//                     if (isset($studentGroupTimeslotUsage[$key])) {
//                         $totalPenalty += 2000; // عقوبة تعارض طالب
//                     }
//                     $studentGroupTimeslotUsage[$key] = true;
//                 }
//             }

//             // فحص القيود الخاصة بكل محاضرة (نمر على المحاضرات كوحدات)
//             foreach ($genes->unique('lecture_unique_id') as $representativeGene) {
//                 if (!$representativeGene->section || !$representativeGene->room || !$representativeGene->instructor) continue;

//                 // قيد سعة القاعة
//                 if ($representativeGene->section->student_count > $representativeGene->room->room_size) $totalPenalty += 800;

//                 // قيد نوع القاعة
//                 $roomType = optional($representativeGene->room->roomType)->room_type_name ?? '';
//                 $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);
//                 if ($representativeGene->section->activity_type == 'Practical' && !$isPracticalRoom) $totalPenalty += 600;
//                 if ($representativeGene->section->activity_type == 'Theory' && $isPracticalRoom) $totalPenalty += 600;

//                 // قيد أهلية المدرس
//                 if (!optional($representativeGene->instructor)->canTeach(optional(optional($representativeGene->section)->planSubject)->subject)) $totalPenalty += 2000;
//             }

//             // نستخدم تحديث مباشر لقاعدة البيانات لتجنب مشاكل الذاكرة مع Eloquent
//             DB::table('chromosomes')->where('chromosome_id', $chromosome->chromosome_id)->update(['penalty_value' => $totalPenalty]);
//             $chromosome->penalty_value = $totalPenalty; // تحديث الكائن في الذاكرة أيضاً للاستخدام في نفس الجيل
//         }
//     }

//     /**
//      * دالة اختيار الآباء
//      */
//     private function selectParents(Collection $population)
//     {
//         $parents = [];
//         $tournamentSize = 3;
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $parents[] = $population->random($tournamentSize)->sortBy('penalty_value')->first();
//         }
//         return $parents;
//     }

//     /**
//      * دالة إنشاء جيل جديد
//      */
//     private function createNewGeneration(array $parents, int $nextGenerationNumber): Collection
//     {
//         Log::info("Creating new generation #{$nextGenerationNumber}");
//         $children = collect();
//         $parentPool = $parents;

//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             if (count($parentPool) < 2) $parentPool = $parents;

//             $p1_key = array_rand($parentPool);
//             $parent1 = $parentPool[$p1_key];
//             unset($parentPool[$p1_key]);
//             $p2_key = array_rand($parentPool);
//             $parent2 = $parentPool[$p2_key];
//             unset($parentPool[$p2_key]);

//             $childLectures = $this->performCrossover($parent1, $parent2);
//             $childLectures = $this->performMutation($childLectures);

//             $children->push($this->saveChildChromosome($childLectures, $nextGenerationNumber));
//         }

//         return $children;
//     }

//     /**
//      * دالة التزاوج (Crossover) - معدلة لتكون أبسط وتنتج طفلاً واحداً
//      */
//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): Collection
//     {
//         $p1Lectures = $parent1->genes()->get()->groupBy('lecture_unique_id');
//         $p2Lectures = $parent2->genes()->get()->groupBy('lecture_unique_id');
//         $childLectures = collect();

//         $crossoverPoint = rand(1, $this->lecturesToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lecturesToSchedule as $lecture) {
//             $source = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $genesForLecture = $source->get($lecture->unique_id) ?? ($p1Lectures->get($lecture->unique_id) ?? $p2Lectures->get($lecture->unique_id));
//             if ($genesForLecture) {
//                 $childLectures[$lecture->unique_id] = $genesForLecture;
//             }
//             $currentIndex++;
//         }
//         return $childLectures;
//     }

//     /**
//      * دالة الطفرة (Mutation) - معدلة لتكون أوضح
//      */
//     private function performMutation(Collection $lectures)
//     {
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if ($lectures->isEmpty()) return $lectures;

//             $lectureKeyToMutate = $lectures->keys()->random();
//             $originalLectureInfo = $this->lecturesToSchedule->firstWhere('unique_id', $lectureKeyToMutate);
//             if (!$originalLectureInfo) return $lectures;

//             $newInstructor = $this->getRandomInstructorForSection($originalLectureInfo->section);
//             $newRoom = $this->getRandomRoomForSection($originalLectureInfo->section);
//             $newTimeslots = $this->findRandomConsecutiveTimeslots($originalLectureInfo->slots_needed);

//             $mutatedGenes = collect();
//             foreach ($newTimeslots as $timeslotId) {
//                 $gene = new Gene([
//                     'lecture_unique_id' => $lectureKeyToMutate,
//                     'section_id' => $originalLectureInfo->section->id,
//                     'instructor_id' => $newInstructor->id,
//                     'room_id' => $newRoom->id,
//                     'timeslot_id' => $timeslotId
//                 ]);
//                 $mutatedGenes->push($gene);
//             }
//             $lectures[$lectureKeyToMutate] = $mutatedGenes;
//         }
//         return $lectures;
//     }

//     /**
//      * دالة مساعدة لحفظ الكروموسوم الابن وجيناته
//      */
//     private function saveChildChromosome(Collection $lectures, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create(['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber]);

//         $genesToInsert = [];
//         foreach ($lectures as $lectureGenes) {
//             if (is_null($lectureGenes)) continue;
//             foreach ($lectureGenes as $gene) {
//                 $genesToInsert[] = [
//                     'chromosome_id' => $chromosome->chromosome_id,
//                     'lecture_unique_id' => $gene->lecture_unique_id,
//                     'section_id' => $gene->section_id,
//                     'instructor_id' => $gene->instructor_id,
//                     'room_id' => $gene->room_id,
//                     'timeslot_id' => $gene->timeslot_id,
//                 ];
//             }
//         }

//         foreach (array_chunk($genesToInsert, 500) as $chunk) {
//             Gene::insert($chunk);
//         }
//         return $chromosome;
//     }
// }




// namespace App\Services;

// use App\Models\Population;
// use App\Models\Chromosome;
// use App\Models\Gene;
// use App\Models\Section;
// use App\Models\Instructor;
// use App\Models\Room;
// use App\Models\Timeslot;
// use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Str;
// use Exception;

// class GeneticAlgorithmService
// {
//     // --- خصائص لتخزين الإعدادات والبيانات ---
//     private array $settings;
//     private Population $populationRun;
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;
//     private Collection $timeslots;

//     // (جديد ومحسن) قائمة بالمحاضرات المطلوب جدولتها (شعبة + عدد الفترات كوحدة واحدة)
//     private Collection $lecturesToSchedule;

//     // (جديد) خريطة الأوقات المتتالية لتسريع البحث
//     private array $consecutiveTimeslotsMap = [];

//     // (جديد ومهم جداً) خريطة لربط كل "شعبة" بمجموعة الطلاب التي تنتمي إليها
//     private array $studentGroupMap = [];

//     /**
//      * دالة البناء (Constructor)
//      * تستقبل إعدادات الخوارزمية وسجل عملية التشغيل
//      */
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     /**
//      * الدالة الرئيسية لتشغيل الخوارزمية بالكامل
//      */
//     public function run()
//     {
//         try {
//             $this->populationRun->update(['status' => 'running', 'start_time' => now()]);

//             // 1. تحميل وتجهيز كل البيانات اللازمة
//             $this->loadAndPrepareData();

//             // 2. إنشاء الجيل الأول وتقييمه
//             $currentGenerationNumber = 1;
//             $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
//             $this->evaluateFitness($currentPopulation);
//             Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//             // 3. حلقة تطور الأجيال
//             $maxGenerations = $this->settings['max_generations'];
//             while ($currentGenerationNumber < $maxGenerations) {
//                 $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
//                 if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//                     Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//                     break;
//                 }

//                 $parents = $this->selectParents($currentPopulation);
//                 $currentGenerationNumber++;
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//                 $this->evaluateFitness($currentPopulation);
//                 Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//             }

//             // 4. تحديث النتيجة النهائية في قاعدة البيانات
//             $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
//             $this->populationRun->update([
//                 'status' => 'completed',
//                 'end_time' => now(),
//                 'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
//             ]);
//             Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
//         } catch (Exception $e) {
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     /**
//      * دالة لتحميل كل البيانات وتجهيزها للخوارزمية
//      * هذه الدالة تقوم بكل العمل التحضيري الذكي
//      */
//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with('planSubject.subject', 'planSubject.plan')
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])->get();
//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         // --- الخطوة 1: (المنطق الجديد) تحويل الشعب إلى "بلوكات محاضرات" ---
//         $this->lecturesToSchedule = collect();
//         foreach ($sections as $section) {
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             // نحسب إجمالي عدد الفترات الزمنية المطلوبة لهذا البلوك (المحاضرة)
//             $totalSlotsNeeded = 0;
//             if ($section->activity_type == 'Theory') {
//                 $totalSlotsNeeded = ($subject->theoretical_hours ?? 0) * ($this->settings['theory_credit_to_slots'] ?? 1);
//             } elseif ($section->activity_type == 'Practical') {
//                 $totalSlotsNeeded = ($subject->practical_hours ?? 0) * ($this->settings['practical_credit_to_slots'] ?? 1);
//             }

//             // نضيف المحاضرة كبلوك واحد فقط إذا كانت تحتاج فترات زمنية
//             if ($totalSlotsNeeded > 0) {
//                 $this->lecturesToSchedule->push((object)[
//                     'section' => $section,
//                     'slots_needed' => $totalSlotsNeeded,
//                     'unique_id' => $section->id // المعرف الفريد الآن هو فقط ID الشعبة
//                 ]);
//             }
//         }
//         if ($this->lecturesToSchedule->isEmpty()) throw new Exception("No lectures to schedule were found after processing credit hours.");

//         // --- الخطوة 2: بناء "خريطة مجموعات الطلاب" الذكية ---
//         $this->buildStudentGroupMap($sections);

//         // --- الخطوة 3: تحميل باقي الموارد وبناء خريطة الأوقات المتتالية ---
//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();
//         $this->buildConsecutiveTimeslotsMap();

//         Log::info("Data loaded: " . $this->lecturesToSchedule->count() . " lectures will be scheduled.");
//     }

//     /**
//      * دالة ذكية لبناء خريطة تربط كل شعبة بمجموعة الطلاب الخاصة بها
//      */
//     private function buildStudentGroupMap(Collection $sections)
//     {
//         $this->studentGroupMap = [];
//         $sectionsByContext = $sections->groupBy(function ($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $sectionsInContext) {
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id] = $groupIndex;
//                 }

//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
//                 foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
//                     }
//                 }
//             }
//         }
//     }

//     /**
//      * دالة مساعدة تبني مصفوفة لتسريع إيجاد الفترات الزمنية المتتالية
//      */
//     private function buildConsecutiveTimeslotsMap()
//     {
//         $timeslotsByDay = $this->timeslots->groupBy('day');
//         $this->consecutiveTimeslotsMap = [];
//         foreach ($timeslotsByDay as $dayTimeslots) {
//             $dayTimeslotsValues = $dayTimeslots->values();
//             for ($i = 0; $i < $dayTimeslotsValues->count(); $i++) {
//                 $currentSlot = $dayTimeslotsValues[$i];
//                 $this->consecutiveTimeslotsMap[$currentSlot->id] = [];
//                 for ($j = $i + 1; $j < $dayTimeslotsValues->count(); $j++) {
//                     $nextSlot = $dayTimeslotsValues[$j];
//                     if ($nextSlot->start_time == $currentSlot->end_time) {
//                         $this->consecutiveTimeslotsMap[$currentSlot->id][] = $nextSlot->id;
//                         $currentSlot = $nextSlot;
//                     } else {
//                         break;
//                     }
//                 }
//             }
//         }
//     }

//     /**
//      * دالة لإنشاء الجيل الأول من الحلول العشوائية (لكن بذكاء)
//      */
//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//         }
//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $genesToInsert = [];
//             foreach ($this->lecturesToSchedule as $lecture) {
//                 $instructor = $this->getRandomInstructorForSection($lecture->section);
//                 $room = $this->getRandomRoomForSection($lecture->section);
//                 $consecutiveSlots = $this->findRandomConsecutiveTimeslots($lecture->slots_needed);
//                 foreach ($consecutiveSlots as $timeslotId) {
//                     $genesToInsert[] = ['chromosome_id' => $chromosome->chromosome_id, 'lecture_unique_id' => $lecture->unique_id, 'section_id' => $lecture->section->id, 'instructor_id' => $instructor->id, 'room_id' => $room->id, 'timeslot_id' => $timeslotId];
//                 }
//             }
//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     /**
//      * دالة مساعدة لإيجاد فترات زمنية متتالية عشوائية
//      */
//     private function findRandomConsecutiveTimeslots(int $slotsNeeded): array
//     {
//         if ($slotsNeeded <= 0) return []; // لا نرجع أي شيء إذا لم تكن هناك حاجة
//         if ($slotsNeeded == 1) {
//             return [$this->timeslots->random()->id];
//         }
//         $attempts = 0;
//         while ($attempts < 200) {
//             $startSlot = $this->timeslots->random();
//             if (isset($this->consecutiveTimeslotsMap[$startSlot->id]) && count($this->consecutiveTimeslotsMap[$startSlot->id]) >= ($slotsNeeded - 1)) {
//                 return array_merge([$startSlot->id], array_slice($this->consecutiveTimeslotsMap[$startSlot->id], 0, $slotsNeeded - 1));
//             }
//             $attempts++;
//         }
//         Log::warning("Could not find {$slotsNeeded} consecutive slots, returning a single random slot as fallback.");
//         return [$this->timeslots->random()->id];
//     }

//     /**
//      * دالة مساعدة لاختيار مدرس مناسب للمادة
//      */
//     private function getRandomInstructorForSection(Section $section)
//     {
//         $subject = optional($section->planSubject)->subject;
//         if (!$subject) return $this->instructors->random();

//         $suitableInstructors = $this->instructors->filter(fn($instructor) => $instructor->subjects->contains($subject->id));
//         if ($suitableInstructors->isNotEmpty()) return $suitableInstructors->random();

//         Log::warning("No specific instructors assigned to subject '{$subject->subject_name}'. Choosing a random instructor.");
//         return $this->instructors->random();
//     }

//     /**
//      * دالة مساعدة لاختيار قاعة مناسبة للشعبة
//      */
//     private function getRandomRoomForSection(Section $section)
//     {
//         $roomsSource = ($section->activity_type === 'Practical') ? $this->practicalRooms : $this->theoryRooms;
//         if ($roomsSource->isEmpty()) {
//             $roomsSource = ($section->activity_type === 'Practical') ? $this->theoryRooms : $this->practicalRooms;
//             if ($roomsSource->isEmpty()) return Room::all()->random();
//         }

//         $suitableRooms = $roomsSource->where('room_size', '>=', $section->student_count);
//         return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $roomsSource->random();
//     }

//     /**
//      * دالة تقييم الجودة (Fitness Function)، تقوم بحساب العقوبات
//      */
//     private function evaluateFitness(Collection $chromosomes)
//     {
//         foreach ($chromosomes as $chromosome) {
//             $genes = $chromosome->genes()->with(['instructor', 'room', 'timeslot', 'section.planSubject.subject'])->get();
//             $totalPenalty = 0;

//             $genesByTimeslot = $genes->groupBy('timeslot_id');
//             foreach ($genesByTimeslot as $genesInSlot) {
//                 if ($genesInSlot->count() > 1) {
//                     $totalPenalty += ($genesInSlot->count() - $genesInSlot->pluck('instructor_id')->unique()->count()) * 1000;
//                     $totalPenalty += ($genesInSlot->count() - $genesInSlot->pluck('room_id')->unique()->count()) * 1000;
//                 }
//             }

//             $studentGroupTimeslotUsage = [];
//             foreach ($genes as $gene) {
//                 $sectionId = $gene->section_id;
//                 $timeslotId = $gene->timeslot_id;

//                 if (isset($this->studentGroupMap[$sectionId])) {
//                     $studentGroupId = $this->studentGroupMap[$sectionId];
//                     $key = $studentGroupId . '-' . $timeslotId;
//                     if (isset($studentGroupTimeslotUsage[$key])) {
//                         $totalPenalty += 2000;
//                     }
//                     $studentGroupTimeslotUsage[$key] = true;
//                 }
//             }

//             foreach ($genes->unique('lecture_unique_id') as $representativeGene) {
//                 if (!$representativeGene->section || !$representativeGene->room || !$representativeGene->instructor) continue;
//                 if ($representativeGene->section->student_count > $representativeGene->room->room_size) $totalPenalty += 800;

//                 $roomType = optional($representativeGene->room->roomType)->room_type_name ?? '';
//                 $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);
//                 if ($representativeGene->section->activity_type == 'Practical' && !$isPracticalRoom) $totalPenalty += 600;
//                 if ($representativeGene->section->activity_type == 'Theory' && $isPracticalRoom) $totalPenalty += 600;

//                 if (!optional($representativeGene->instructor)->canTeach(optional(optional($representativeGene->section)->planSubject)->subject)) $totalPenalty += 2000;
//             }

//             DB::table('chromosomes')->where('chromosome_id', $chromosome->chromosome_id)->update(['penalty_value' => $totalPenalty]);
//             $chromosome->penalty_value = $totalPenalty;
//         }
//     }

//     /**
//      * دالة اختيار الآباء للجيل القادم
//      */
//     private function selectParents(Collection $population): array
//     {
//         $parents = [];
//         $tournamentSize = 3;
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $parents[] = $population->random($tournamentSize)->sortBy('penalty_value')->first();
//         }
//         return $parents;
//     }

//     /**
//      * دالة إنشاء جيل جديد من الآباء
//      */
//     private function createNewGeneration(array $parents, int $nextGenerationNumber): Collection
//     {
//         Log::info("Creating new generation #{$nextGenerationNumber}");
//         $childrenData = [];
//         $parentPool = $parents;

//         for ($i = 0; $i < $this->settings['population_size']; $i += 2) {
//             if (count($parentPool) < 2) $parentPool = $parents;

//             $p1_key = array_rand($parentPool);
//             $parent1 = $parentPool[$p1_key];
//             unset($parentPool[$p1_key]);
//             $p2_key = array_rand($parentPool);
//             $parent2 = $parentPool[$p2_key];
//             unset($parentPool[$p2_key]);

//             [$child1Lectures, $child2Lectures] = $this->performCrossover($parent1, $parent2);

//             $childrenData[] = $this->performMutation($child1Lectures);
//             if (count($childrenData) < $this->settings['population_size']) {
//                 $childrenData[] = $this->performMutation($child2Lectures);
//             }
//         }

//         $newlyCreatedChromosomes = [];
//         foreach ($childrenData as $lecturesToInsert) {
//             $newlyCreatedChromosomes[] = $this->saveChildChromosome($lecturesToInsert, $nextGenerationNumber);
//         }
//         return collect($newlyCreatedChromosomes);
//     }

//     /**
//      * دالة التزاوج (Crossover)
//      */
//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Lectures = $parent1->genes()->get()->groupBy('lecture_unique_id');
//         $p2Lectures = $parent2->genes()->get()->groupBy('lecture_unique_id');
//         $child1Lectures = [];
//         $child2Lectures = [];

//         $crossoverPoint = rand(1, $this->lecturesToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lecturesToSchedule as $lecture) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$lecture->unique_id] = $sourceForChild1->get($lecture->unique_id) ?? $p2Lectures->get($lecture->unique_id);
//             $child2Lectures[$lecture->unique_id] = $sourceForChild2->get($lecture->unique_id) ?? $p1Lectures->get($lecture->unique_id);

//             $currentIndex++;
//         }
//         return [$child1Lectures, $child2Lectures];
//     }

//     /**
//      * دالة الطفرة (Mutation)
//      */
//     private function performMutation(array $lectures): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($lectures)) return [];
//             $lectureKeyToMutate = array_rand($lectures);
//             $originalLectureInfo = $this->lecturesToSchedule->firstWhere('unique_id', $lectureKeyToMutate);
//             if (!$originalLectureInfo) return $lectures;

//             $newInstructor = $this->getRandomInstructorForSection($originalLectureInfo->section);
//             $newRoom = $this->getRandomRoomForSection($originalLectureInfo->section);
//             $newTimeslots = $this->findRandomConsecutiveTimeslots($originalLectureInfo->slots_needed);

//             $mutatedGenes = [];
//             foreach ($newTimeslots as $timeslotId) {
//                 $gene = new Gene(['lecture_unique_id' => $lectureKeyToMutate, 'section_id' => $originalLectureInfo->section->id, 'instructor_id' => $newInstructor->id, 'room_id' => $newRoom->id, 'timeslot_id' => $timeslotId]);
//                 $mutatedGenes[] = $gene;
//             }
//             $lectures[$lectureKeyToMutate] = collect($mutatedGenes);
//         }
//         return $lectures;
//     }

//     /**
//      * دالة مساعدة لحفظ الكروموسوم الابن وجيناته في قاعدة البيانات
//      */
//     private function saveChildChromosome(array $lectures, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create(['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber]);

//         $genesToInsert = [];
//         foreach ($lectures as $lectureGenes) {
//             if (is_null($lectureGenes)) continue;
//             foreach ($lectureGenes as $gene) {
//                 $genesToInsert[] = [
//                     'chromosome_id' => $chromosome->chromosome_id,
//                     'lecture_unique_id' => $gene->lecture_unique_id,
//                     'section_id' => $gene->section_id,
//                     'instructor_id' => $gene->instructor_id,
//                     'room_id' => $gene->room_id,
//                     'timeslot_id' => $gene->timeslot_id,
//                 ];
//             }
//         }

//         foreach (array_chunk($genesToInsert, 500) as $chunk) {
//             Gene::insert($chunk);
//         }
//         return $chromosome;
//     }
// }


// namespace App\Services;

// use App\Models\Population;
// use App\Models\Chromosome;
// use App\Models\Gene;
// use App\Models\Section;
// use App\Models\Instructor;
// use App\Models\Room;
// use App\Models\Timeslot;
// use Carbon\Carbon;
// use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Str;
// use Exception;

// class GeneticAlgorithmService
// {
//     // --- خصائص لتخزين الإعدادات والبيانات ---
//     private array $settings;
//     private Population $populationRun;
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;

//     // (جديد) قائمة "بمهام الجدولة" (بلوكات المحاضرات)
//     private Collection $schedulingTasks;

//     // (جديد ومهم جداً) خريطة لربط كل "شعبة" بمجموعة الطلاب التي تنتمي إليها
//     private array $studentGroupMap = [];

//     // (جديد) حدود اليوم الدراسي
//     private array $dayBoundaries = [];

//     private Collection $lecturesToSchedule;

//     /**
//      * دالة البناء (Constructor)
//      */
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     /**
//      * الدالة الرئيسية لتشغيل الخوارزمية بالكامل
//      */
//     public function run()
//     {
//         try {
//             $this->populationRun->update(['status' => 'running', 'start_time' => now()]);
//             $this->loadAndPrepareData();

//             $currentGenerationNumber = 1;
//             $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
//             $this->evaluateFitness($currentPopulation);
//             Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//             $maxGenerations = $this->settings['max_generations'];
//             while ($currentGenerationNumber < $maxGenerations) {
//                 $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
//                 if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//                     Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//                     break;
//                 }

//                 $parents = $this->selectParents($currentPopulation);
//                 $currentGenerationNumber++;
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//                 $this->evaluateFitness($currentPopulation);
//                 Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//             }

//             $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
//             $this->populationRun->update([
//                 'status' => 'completed', 'end_time' => now(),
//                 'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
//             ]);
//             Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
//         } catch (Exception $e) {
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     /**
//      * دالة لتحميل كل البيانات وتجهيزها للخوارزمية (القلب النابض للتحضير)
//      */
//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with('planSubject.subject', 'planSubject.plan')
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])->get();
//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         // --- الخطوة 1: (المنطق الجديد) "تقطيع" الشعب إلى بلوكات محاضرات (مهام جدولة) ---
//         $this->schedulingTasks = collect();
//         foreach ($sections as $section) {
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             // أ. معالجة الجزء العملي (دائماً بلوك واحد)
//             if ($section->activity_type == 'Practical' && $subject->practical_hours > 0) {
//                 $duration = $subject->practical_hours * $this->settings['practical_minutes_per_credit'];
//                 if ($duration > 0) {
//                     $this->schedulingTasks->push((object)[
//                         'section' => $section,
//                         'duration_minutes' => $duration,
//                         'unique_id' => $section->id . '-prac'
//                     ]);
//                 }
//             }

//             // ب. معالجة الجزء النظري (قابل للتقسيم)
//             if ($section->activity_type == 'Theory' && $subject->theoretical_hours > 0) {
//                 $totalHours = $subject->theoretical_hours;
//                 $minutesPerCredit = $this->settings['theory_minutes_per_credit'];

//                 // استراتيجية التقسيم: بلوكات من ساعتين قدر الإمكان
//                 $twoHourBlocks = floor($totalHours / 2);
//                 $oneHourBlocks = $totalHours % 2;

//                 for ($i = 0; $i < $twoHourBlocks; $i++) {
//                     $this->schedulingTasks->push((object)[
//                         'section' => $section, 'duration_minutes' => 2 * $minutesPerCredit,
//                         'unique_id' => $section->id . '-theory-' . $i
//                     ]);
//                 }
//                 if ($oneHourBlocks > 0) {
//                     $this->schedulingTasks->push((object)[
//                         'section' => $section, 'duration_minutes' => 1 * $minutesPerCredit,
//                         'unique_id' => $section->id . '-theory-' . $twoHourBlocks
//                     ]);
//                 }
//             }
//         }
//         if ($this->schedulingTasks->isEmpty()) throw new Exception("No scheduling tasks were created from the sections.");

//         // --- الخطوة 2: بناء خريطة مجموعات الطلاب الذكية (لا تغيير) ---
//         $this->buildStudentGroupMap($sections);

//         // --- الخطوة 3: تحميل باقي الموارد وتحديد حدود الدوام ---
//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();

//         $timeslots = Timeslot::all();
//         if ($timeslots->isEmpty()) throw new Exception("Timeslots are not defined. Please generate them first.");

//         // تحديد حدود اليوم الدراسي من جدول timeslots
//         $this->dayBoundaries['start'] = Carbon::parse($timeslots->min('start_time'));
//         $this->dayBoundaries['end'] = Carbon::parse($timeslots->max('end_time'));
//         $this->dayBoundaries['days'] = $timeslots->pluck('day')->unique()->values()->all();

//         Log::info("Data loaded: " . $this->schedulingTasks->count() . " scheduling tasks created.");
//     }

//     /**
//      * دالة ذكية لبناء خريطة تربط كل شعبة بمجموعة الطلاب الخاصة بها
//      */
//     private function buildStudentGroupMap(Collection $sections) {
//         $this->studentGroupMap = [];
//         $sectionsByContext = $sections->groupBy(function ($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $sectionsInContext) {
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id] = $groupIndex;
//                 }

//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
//                 foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
//                     }
//                 }
//             }
//         }
//      }

//     /**
//      * دالة لإنشاء الجيل الأول من الحلول العشوائية (لكن بذكاء)
//      */
//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//         }
//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $genesToInsert = [];
//             foreach ($this->schedulingTasks as $task) {
//                 $instructor = $this->getRandomInstructorForSection($task->section);
//                 $room = $this->getRandomRoomForSection($task->section);

//                 // اختيار يوم ووقت بدء عشوائيين
//                 $day = $this->dayBoundaries['days'][array_rand($this->dayBoundaries['days'])];
//                 $startTime = $this->getRandomStartTime($task->duration_minutes);
//                 $endTime = $startTime->copy()->addMinutes($task->duration_minutes);

//                 $genesToInsert[] = [
//                     'chromosome_id' => $chromosome->chromosome_id,
//                     'lecture_unique_id' => $task->unique_id,
//                     'section_id' => $task->section->id,
//                     'instructor_id' => $instructor->id,
//                     'room_id' => $room->id,
//                     'day' => $day, // **جديد**
//                     'start_time' => $startTime->format('H:i:s'), // **جديد**
//                     'end_time' => $endTime->format('H:i:s'), // **جديد**
//                 ];
//             }
//             // إدخال الجينات على دفعات
//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     /**
//      * دالة مساعدة لاختيار وقت بدء عشوائي ضمن حدود الدوام
//      */
//     private function getRandomStartTime(int $durationMinutes)
//     {
//         $startOfDay = $this->dayBoundaries['start'];
//         $endOfDay = $this->dayBoundaries['end'];
//         // آخر وقت ممكن لبدء المحاضرة هو نهاية الدوام - مدة المحاضرة
//         $latestPossibleStart = $endOfDay->copy()->subMinutes($durationMinutes);

//         if ($latestPossibleStart->lt($startOfDay)) {
//             // إذا كانت المحاضرة أطول من يوم الدوام، نبدأ من بداية اليوم (حالة نادرة)
//             return $startOfDay;
//         }

//         $totalMinutesRange = $startOfDay->diffInMinutes($latestPossibleStart);
//         $randomMinutes = rand(0, $totalMinutesRange);

//         return $startOfDay->copy()->addMinutes($randomMinutes);
//     }

//     /**
//      * دالة مساعدة لاختيار مدرس مناسب للمادة
//      */
//     private function getRandomInstructorForSection(Section $section) {
//          $subject = optional($section->planSubject)->subject;
//         if (!$subject) return $this->instructors->random();

//         $suitableInstructors = $this->instructors->filter(fn($instructor) => $instructor->subjects->contains($subject->id));
//         if ($suitableInstructors->isNotEmpty()) return $suitableInstructors->random();

//         Log::warning("No specific instructors assigned to subject '{$subject->subject_name}'. Choosing a random instructor.");
//         return $this->instructors->random();
//      }

//     /**
//      * دالة مساعدة لاختيار قاعة مناسبة للشعبة
//      */
//     private function getRandomRoomForSection(Section $section) {
//         $roomsSource = ($section->activity_type === 'Practical') ? $this->practicalRooms : $this->theoryRooms;
//         if ($roomsSource->isEmpty()) {
//             $roomsSource = ($section->activity_type === 'Practical') ? $this->theoryRooms : $this->practicalRooms;
//             if ($roomsSource->isEmpty()) return Room::all()->random();
//         }

//         $suitableRooms = $roomsSource->where('room_size', '>=', $section->student_count);
//         return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $roomsSource->random();
//      }

//     /**
//      * دالة تقييم الجودة (Fitness Function)، تقوم بحساب العقوبات
//      */
//     private function evaluateFitness(Collection $chromosomes)
//     {
//         foreach ($chromosomes as $chromosome) {
//             $genes = $chromosome->genes()->with(['instructor', 'room', 'section.planSubject.subject'])->get();
//             $totalPenalty = 0;

//             $genesByDay = $genes->groupBy('day');

//             foreach($genesByDay as $dayGenes) {
//                 // نمر على كل زوج ممكن من المحاضرات في نفس اليوم لفحص التعارض
//                 foreach ($dayGenes as $i => $geneA) {
//                     for ($j = $i + 1; $j < $dayGenes->count(); $j++) {
//                         $geneB = $dayGenes[$j];

//                         // تحويل الأوقات إلى كائنات Carbon للمقارنة
//                         $startA = Carbon::parse($geneA->start_time);
//                         $endA = Carbon::parse($geneA->end_time);
//                         $startB = Carbon::parse($geneB->start_time);
//                         $endB = Carbon::parse($geneB->end_time);

//                         // شرط التداخل الزمني
//                         if ($startA < $endB && $endA > $startB) {
//                             // إذا تداخلت زمنياً، نفحص تعارض الموارد
//                             if ($geneA->instructor_id == $geneB->instructor_id) $totalPenalty += 1000;
//                             if ($geneA->room_id == $geneB->room_id) $totalPenalty += 1000;

//                             // فحص تعارض الطلاب باستخدام الخريطة
//                             $groupA = $this->studentGroupMap[$geneA->section_id] ?? null;
//                             $groupB = $this->studentGroupMap[$geneB->section_id] ?? null;
//                             if ($groupA && $groupB && $groupA == $groupB) $totalPenalty += 2000;
//                         }
//                     }
//                 }
//             }

//             // فحص القيود الخاصة بكل محاضرة (نمر على المحاضرات كوحدات)
//             foreach ($genes as $gene) {
//                 if (!$gene->section || !$gene->room || !$gene->instructor) continue;
//                 if ($gene->section->student_count > $gene->room->room_size) $totalPenalty += 800;

//                 $roomType = optional($gene->room->roomType)->room_type_name ?? '';
//                 $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);
//                 if ($gene->section->activity_type == 'Practical' && !$isPracticalRoom) $totalPenalty += 600;
//                 if ($gene->section->activity_type == 'Theory' && $isPracticalRoom) $totalPenalty += 600;

//                 if (!optional($gene->instructor)->canTeach(optional(optional($gene->section)->planSubject)->subject)) $totalPenalty += 2000;
//             }

//             DB::table('chromosomes')->where('chromosome_id', $chromosome->chromosome_id)->update(['penalty_value' => $totalPenalty]);
//             $chromosome->penalty_value = $totalPenalty;
//         }
//     }

//     /**
//      * دالة اختيار الآباء للجيل القادم
//      */
//     private function selectParents(Collection $population){
//         $parents = [];
//         $tournamentSize = 3;
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $parents[] = $population->random($tournamentSize)->sortBy('penalty_value')->first();
//         }
//         return $parents;
//      }

//     /**
//      * دالة إنشاء جيل جديد من الآباء
//      */
//     private function createNewGeneration(array $parents, int $nextGenerationNumber) {
//         Log::info("Creating new generation #{$nextGenerationNumber}");
//         $childrenData = [];
//         $parentPool = $parents;

//         for ($i = 0; $i < $this->settings['population_size']; $i += 2) {
//             if (count($parentPool) < 2) $parentPool = $parents;

//             $p1_key = array_rand($parentPool);
//             $parent1 = $parentPool[$p1_key];
//             unset($parentPool[$p1_key]);
//             $p2_key = array_rand($parentPool);
//             $parent2 = $parentPool[$p2_key];
//             unset($parentPool[$p2_key]);

//             [$child1Lectures, $child2Lectures] = $this->performCrossover($parent1, $parent2);

//             $childrenData[] = $this->performMutation($child1Lectures);
//             if (count($childrenData) < $this->settings['population_size']) {
//                 $childrenData[] = $this->performMutation($child2Lectures);
//             }
//         }

//         $newlyCreatedChromosomes = [];
//         foreach ($childrenData as $lecturesToInsert) {
//             $newlyCreatedChromosomes[] = $this->saveChildChromosome($lecturesToInsert, $nextGenerationNumber);
//         }
//         return collect($newlyCreatedChromosomes);
//      }

//     /**
//      * دالة التزاوج (Crossover)
//      */
//     private function performCrossover(Chromosome $parent1, Chromosome $parent2){
//         $p1Lectures = $parent1->genes()->get()->groupBy('lecture_unique_id');
//         $p2Lectures = $parent2->genes()->get()->groupBy('lecture_unique_id');
//         $child1Lectures = [];
//         $child2Lectures = [];

//         $crossoverPoint = rand(1, $this->lecturesToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lecturesToSchedule as $lecture) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$lecture->unique_id] = $sourceForChild1->get($lecture->unique_id) ?? $p2Lectures->get($lecture->unique_id);
//             $child2Lectures[$lecture->unique_id] = $sourceForChild2->get($lecture->unique_id) ?? $p1Lectures->get($lecture->unique_id);

//             $currentIndex++;
//         }
//         return [$child1Lectures, $child2Lectures];
//      }

//     /**
//      * دالة الطفرة (Mutation)
//      */
//     private function performMutation(array $lectures): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($lectures)) return [];
//             $lectureKeyToMutate = array_rand($lectures);
//             $originalLectureInfo = $this->schedulingTasks->firstWhere('unique_id', $lectureKeyToMutate);
//             if(!$originalLectureInfo) return $lectures;

//             // نولد معلومات جديدة للمحاضرة
//             $newDay = $this->dayBoundaries['days'][array_rand($this->dayBoundaries['days'])];
//             $newStartTime = $this->getRandomStartTime($originalLectureInfo->duration_minutes);
//             $newEndTime = $newStartTime->copy()->addMinutes($originalLectureInfo->duration_minutes);

//             $mutatedGene = $lectures[$lectureKeyToMutate];
//             $mutatedGene->day = $newDay;
//             $mutatedGene->start_time = $newStartTime->format('H:i:s');
//             $mutatedGene->end_time = $newEndTime->format('H:i:s');

//             $lectures[$lectureKeyToMutate] = $mutatedGene;
//         }
//         return $lectures;
//     }

//     /**
//      * دالة مساعدة لحفظ الكروموسوم الابن وجيناته في قاعدة البيانات
//      */
//     private function saveChildChromosome(array $lectures, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create(['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber]);

//         $genesToInsert = [];
//         foreach($lectures as $gene) {
//             if (is_null($gene)) continue;
//             // نستخرج البيانات كمصفوفة لإدخالها
//             $genesToInsert[] = [
//                 'chromosome_id' => $chromosome->chromosome_id,
//                 'lecture_unique_id' => $gene->lecture_unique_id,
//                 'section_id' => $gene->section_id,
//                 'instructor_id' => $gene->instructor_id,
//                 'room_id' => $gene->room_id,
//                 'day' => $gene->day,
//                 'start_time' => $gene->start_time,
//                 'end_time' => $gene->end_time,
//             ];
//         }

//         foreach (array_chunk($genesToInsert, 500) as $chunk) {
//             Gene::insert($chunk);
//         }
//         return $chromosome;
//     }
// }



// namespace App\Services;

// use App\Models\Population;
// use App\Models\Chromosome;
// use App\Models\Gene;
// use App\Models\Section;
// use App\Models\Instructor;
// use App\Models\Room;
// use App\Models\Timeslot;
// use Carbon\Carbon;
// use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Str;
// use Exception;

// class GeneticAlgorithmService
// {
//     // --- خصائص لتخزين الإعدادات والبيانات ---
//     private array $settings;
//     private Population $populationRun;
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;

//     // (جديد) قائمة "بمهام الجدولة" (بلوكات المحاضرات)
//     private Collection $schedulingTasks;

//     // (جديد ومهم جداً) خريطة لربط كل "شعبة" بمجموعة الطلاب التي تنتمي إليها
//     private array $studentGroupMap = [];

//     // (جديد) حدود اليوم الدراسي
//     private array $dayBoundaries = [];

//     /**
//      * دالة البناء (Constructor)
//      */
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     /**
//      * الدالة الرئيسية لتشغيل الخوارزمية بالكامل
//      */
//     public function run()
//     {
//         try {
//             $this->populationRun->update(['status' => 'running', 'start_time' => now()]);
//             $this->loadAndPrepareData();

//             $currentGenerationNumber = 1;
//             $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
//             $this->evaluateFitness($currentPopulation);
//             Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//             $maxGenerations = $this->settings['max_generations'];
//             while ($currentGenerationNumber < $maxGenerations) {
//                 $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
//                 if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//                     Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//                     break;
//                 }

//                 $parents = $this->selectParents($currentPopulation);
//                 $currentGenerationNumber++;
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//                 $this->evaluateFitness($currentPopulation);
//                 Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//             }

//             $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
//             $this->populationRun->update([
//                 'status' => 'completed', 'end_time' => now(),
//                 'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
//             ]);
//             Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
//         } catch (Exception $e) {
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     /**
//      * دالة لتحميل كل البيانات وتجهيزها للخوارزمية (القلب النابض للتحضير)
//      */
//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with('planSubject.subject', 'planSubject.plan')
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])->get();
//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         // --- الخطوة 1: (المنطق الجديد) "تقطيع" الشعب إلى بلوكات محاضرات (مهام جدولة) ---
//         $this->schedulingTasks = collect();
//         foreach ($sections as $section) {
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             // أ. معالجة الجزء العملي (دائماً بلوك واحد)
//             if ($section->activity_type == 'Practical' && $subject->practical_hours > 0) {
//                 $duration = $subject->practical_hours * $this->settings['practical_minutes_per_credit'];
//                 if ($duration > 0) {
//                     $this->schedulingTasks->push((object)[
//                         'section' => $section,
//                         'duration_minutes' => $duration,
//                         'unique_id' => $section->id . '-prac'
//                     ]);
//                 }
//             }

//             // ب. معالجة الجزء النظري (قابل للتقسيم)
//             if ($section->activity_type == 'Theory' && $subject->theoretical_hours > 0) {
//                 $totalHours = $subject->theoretical_hours;
//                 $minutesPerCredit = $this->settings['theory_minutes_per_credit'];

//                 // استراتيجية التقسيم: بلوكات من ساعتين قدر الإمكان
//                 $twoHourBlocks = floor($totalHours / 2);
//                 $oneHourBlocks = $totalHours % 2;

//                 for ($i = 0; $i < $twoHourBlocks; $i++) {
//                     $this->schedulingTasks->push((object)[
//                         'section' => $section, 'duration_minutes' => 2 * $minutesPerCredit,
//                         'unique_id' => $section->id . '-theory-' . $i
//                     ]);
//                 }
//                 if ($oneHourBlocks > 0) {
//                     $this->schedulingTasks->push((object)[
//                         'section' => $section, 'duration_minutes' => 1 * $minutesPerCredit,
//                         'unique_id' => $section->id . '-theory-' . $twoHourBlocks
//                     ]);
//                 }
//             }
//         }
//         if ($this->schedulingTasks->isEmpty()) throw new Exception("No scheduling tasks were created from the sections.");

//         // --- الخطوة 2: بناء خريطة مجموعات الطلاب الذكية (لا تغيير) ---
//         $this->buildStudentGroupMap($sections);

//         // --- الخطوة 3: تحميل باقي الموارد وتحديد حدود الدوام ---
//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();

//         $timeslots = Timeslot::all();
//         if ($timeslots->isEmpty()) throw new Exception("Timeslots are not defined. Please generate them first.");

//         // تحديد حدود اليوم الدراسي من جدول timeslots
//         $this->dayBoundaries['start'] = Carbon::parse($timeslots->min('start_time'));
//         $this->dayBoundaries['end'] = Carbon::parse($timeslots->max('end_time'));
//         $this->dayBoundaries['days'] = $timeslots->pluck('day')->unique()->values()->all();

//         Log::info("Data loaded: " . $this->schedulingTasks->count() . " scheduling tasks created.");
//     }

//     /**
//      * دالة ذكية لبناء خريطة تربط كل شعبة بمجموعة الطلاب الخاصة بها
//      */
//     private function buildStudentGroupMap(Collection $sections) {
//         $this->studentGroupMap = [];
//         $sectionsByContext = $sections->groupBy(function ($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $sectionsInContext) {
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id] = $groupIndex;
//                 }

//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
//                 foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
//                     }
//                 }
//             }
//         }
//      }

//     /**
//      * دالة لإنشاء الجيل الأول من الحلول العشوائية (لكن بذكاء)
//      */
//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//         }
//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $genesToInsert = [];
//             foreach ($this->schedulingTasks as $task) {
//                 $instructor = $this->getRandomInstructorForSection($task->section);
//                 $room = $this->getRandomRoomForSection($task->section);

//                 // اختيار يوم ووقت بدء عشوائيين
//                 $day = $this->dayBoundaries['days'][array_rand($this->dayBoundaries['days'])];
//                 $startTime = $this->getRandomStartTime($task->duration_minutes);
//                 $endTime = $startTime->copy()->addMinutes($task->duration_minutes);

//                 $genesToInsert[] = [
//                     'chromosome_id' => $chromosome->chromosome_id,
//                     'lecture_unique_id' => $task->unique_id,
//                     'section_id' => $task->section->id,
//                     'instructor_id' => $instructor->id,
//                     'room_id' => $room->id,
//                     'day' => $day,
//                     'start_time' => $startTime->format('H:i:s'),
//                     'end_time' => $endTime->format('H:i:s'),
//                 ];
//             }
//             // إدخال الجينات على دفعات
//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     /**
//      * دالة مساعدة لاختيار وقت بدء عشوائي ضمن حدود الدوام
//      */
//     private function getRandomStartTime(int $durationMinutes)
//     {
//         $startOfDay = $this->dayBoundaries['start'];
//         $endOfDay = $this->dayBoundaries['end'];
//         $latestPossibleStart = $endOfDay->copy()->subMinutes($durationMinutes);

//         if ($latestPossibleStart->lt($startOfDay)) {
//             return $startOfDay;
//         }

//         $totalMinutesRange = $startOfDay->diffInMinutes($latestPossibleStart);
//         $randomMinutes = rand(0, $totalMinutesRange);

//         return $startOfDay->copy()->addMinutes($randomMinutes);
//     }

//     /**
//      * دالة مساعدة لاختيار مدرس مناسب للمادة
//      */
//     private function getRandomInstructorForSection(Section $section) {
//          $subject = optional($section->planSubject)->subject;
//         if (!$subject) return $this->instructors->random();

//         $suitableInstructors = $this->instructors->filter(fn($instructor) => $instructor->subjects->contains($subject->id));
//         if ($suitableInstructors->isNotEmpty()) return $suitableInstructors->random();

//         Log::warning("No specific instructors assigned to subject '{$subject->subject_name}'. Choosing a random instructor.");
//         return $this->instructors->random();
//      }

//     /**
//      * دالة مساعدة لاختيار قاعة مناسبة للشعبة
//      */
//     private function getRandomRoomForSection(Section $section) {
//         $roomsSource = ($section->activity_type === 'Practical') ? $this->practicalRooms : $this->theoryRooms;
//         if ($roomsSource->isEmpty()) {
//             $roomsSource = ($section->activity_type === 'Practical') ? $this->theoryRooms : $this->practicalRooms;
//             if ($roomsSource->isEmpty()) return Room::all()->random();
//         }

//         $suitableRooms = $roomsSource->where('room_size', '>=', $section->student_count);
//         return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $roomsSource->random();
//      }

//     /**
//      * دالة تقييم الجودة (Fitness Function)، تقوم بحساب العقوبات
//      */
//     private function evaluateFitness(Collection $chromosomes)
//     {
//         foreach ($chromosomes as $chromosome) {
//             $genes = $chromosome->genes()->with(['instructor', 'room', 'section.planSubject.subject'])->get();
//             $totalPenalty = 0;

//             $genesByDay = $genes->groupBy('day');

//             foreach($genesByDay as $dayGenes) {
//                 // نمر على كل زوج ممكن من المحاضرات في نفس اليوم لفحص التعارض
//                 foreach ($dayGenes as $i => $geneA) {
//                     for ($j = $i + 1; $j < $dayGenes->count(); $j++) {
//                         $geneB = $dayGenes[$j];

//                         // تحويل الأوقات إلى كائنات Carbon للمقارنة
//                         $startA = Carbon::parse($geneA->start_time);
//                         $endA = Carbon::parse($geneA->end_time);
//                         $startB = Carbon::parse($geneB->start_time);
//                         $endB = Carbon::parse($geneB->end_time);

//                         // شرط التداخل الزمني
//                         if ($startA < $endB && $endA > $startB) {
//                             // إذا تداخلت زمنياً، نفحص تعارض الموارد
//                             if ($geneA->instructor_id == $geneB->instructor_id) $totalPenalty += 1000;
//                             if ($geneA->room_id == $geneB->room_id) $totalPenalty += 1000;

//                             // فحص تعارض الطلاب باستخدام الخريطة
//                             $groupA = $this->studentGroupMap[$geneA->section_id] ?? null;
//                             $groupB = $this->studentGroupMap[$geneB->section_id] ?? null;
//                             if ($groupA && $groupB && $groupA == $groupB) $totalPenalty += 2000;
//                         }
//                     }
//                 }
//             }

//             // فحص القيود الخاصة بكل محاضرة
//             foreach ($genes as $gene) {
//                 if (!$gene->section || !$gene->room || !$gene->instructor) continue;
//                 if ($gene->section->student_count > $gene->room->room_size) $totalPenalty += 800;

//                 $roomType = optional($gene->room->roomType)->room_type_name ?? '';
//                 $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);
//                 if ($gene->section->activity_type == 'Practical' && !$isPracticalRoom) $totalPenalty += 600;
//                 if ($gene->section->activity_type == 'Theory' && $isPracticalRoom) $totalPenalty += 600;

//                 if (!optional($gene->instructor)->canTeach(optional(optional($gene->section)->planSubject)->subject)) $totalPenalty += 2000;
//             }

//             DB::table('chromosomes')->where('chromosome_id', $chromosome->chromosome_id)->update(['penalty_value' => $totalPenalty]);
//             $chromosome->penalty_value = $totalPenalty;
//         }
//     }

//     /**
//      * دالة اختيار الآباء للجيل القادم
//      */
//     private function selectParents(Collection $population){
//         $parents = [];
//         $tournamentSize = 3;
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $parents[] = $population->random($tournamentSize)->sortBy('penalty_value')->first();
//         }
//         return $parents;
//      }

//     /**
//      * دالة إنشاء جيل جديد من الآباء
//      */
//     private function createNewGeneration(array $parents, int $nextGenerationNumber) {
//         Log::info("Creating new generation #{$nextGenerationNumber}");
//         $childrenData = [];
//         $parentPool = $parents;

//         for ($i = 0; $i < $this->settings['population_size']; $i += 2) {
//             if (count($parentPool) < 2) $parentPool = $parents;

//             $p1_key = array_rand($parentPool);
//             $parent1 = $parentPool[$p1_key];
//             unset($parentPool[$p1_key]);
//             $p2_key = array_rand($parentPool);
//             $parent2 = $parentPool[$p2_key];
//             unset($parentPool[$p2_key]);

//             [$child1Genes, $child2Genes] = $this->performCrossover($parent1, $parent2);

//             $childrenData[] = $this->performMutation($child1Genes);
//             if (count($childrenData) < $this->settings['population_size']) {
//                 $childrenData[] = $this->performMutation($child2Genes);
//             }
//         }

//         $newlyCreatedChromosomes = [];
//         foreach ($childrenData as $genesToInsert) {
//             $newlyCreatedChromosomes[] = $this->saveChildChromosome($genesToInsert, $nextGenerationNumber);
//         }
//         return collect($newlyCreatedChromosomes);
//      }

//     /**
//      * دالة التزاوج (Crossover)
//      */
//     private function performCrossover(Chromosome $parent1, Chromosome $parent2){
//         $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');
//         $child1Genes = [];
//         $child2Genes = [];

//         $crossoverPoint = rand(1, $this->schedulingTasks->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->schedulingTasks as $task) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Genes : $p2Genes;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Genes : $p1Genes;

//             $child1Genes[$task->unique_id] = $sourceForChild1->get($task->unique_id) ?? $p2Genes->get($task->unique_id);
//             $child2Genes[$task->unique_id] = $sourceForChild2->get($task->unique_id) ?? $p1Genes->get($task->unique_id);

//             $currentIndex++;
//         }
//         return [$child1Genes, $child2Genes];
//      }

//     /**
//      * دالة الطفرة (Mutation)
//      */
//     private function performMutation(array $genes): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($genes)) return [];
//             $taskKeyToMutate = array_rand($genes);
//             $originalTaskInfo = $this->schedulingTasks->firstWhere('unique_id', $taskKeyToMutate);
//             if(!$originalTaskInfo) return $genes;

//             // نولد معلومات جديدة للمحاضرة
//             $newDay = $this->dayBoundaries['days'][array_rand($this->dayBoundaries['days'])];
//             $newStartTime = $this->getRandomStartTime($originalTaskInfo->duration_minutes);
//             $newEndTime = $newStartTime->copy()->addMinutes($originalTaskInfo->duration_minutes);

//             $mutatedGene = $genes[$taskKeyToMutate];
//             $mutatedGene->day = $newDay;
//             $mutatedGene->start_time = $newStartTime->format('H:i:s');
//             $mutatedGene->end_time = $newEndTime->format('H:i:s');

//             $genes[$taskKeyToMutate] = $mutatedGene;
//         }
//         return $genes;
//     }

//     /**
//      * دالة مساعدة لحفظ الكروموسوم الابن وجيناته في قاعدة البيانات
//      */
//     private function saveChildChromosome(array $genesData, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create(['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber]);

//         $genesToInsert = [];
//         foreach($genesData as $gene) {
//             if (is_null($gene)) continue;
//             // نستخرج البيانات كمصفوفة لإدخالها
//             $genesToInsert[] = [
//                 'chromosome_id' => $chromosome->chromosome_id,
//                 'lecture_unique_id' => $gene->lecture_unique_id,
//                 'section_id' => $gene->section_id,
//                 'instructor_id' => $gene->instructor_id,
//                 'room_id' => $gene->room_id,
//                 'day' => $gene->day,
//                 'start_time' => $gene->start_time,
//                 'end_time' => $gene->end_time,
//             ];
//         }

//         foreach (array_chunk($genesToInsert, 500) as $chunk) {
//             Gene::insert($chunk);
//         }
//         return $chromosome;
//     }
// }




// namespace App\Services;

// use App\Models\Population;
// use App\Models\Chromosome;
// use App\Models\Gene;
// use App\Models\Section;
// use App\Models\Instructor;
// use App\Models\Room;
// use App\Models\Timeslot;
// use Carbon\Carbon;
// use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Str;
// use Exception;

// class GeneticAlgorithmService
// {
//     // --- خصائص لتخزين الإعدادات والبيانات ---
//     private array $settings;
//     private Population $populationRun;
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;

//     // (جديد) قائمة "بمهام الجدولة" (بلوكات المحاضرات)
//     private Collection $schedulingTasks;

//     // (جديد ومهم جداً) خريطة لربط كل "شعبة" بمجموعة الطلاب التي تنتمي إليها
//     private array $studentGroupMap = [];

//     // (جديد) حدود اليوم الدراسي
//     private array $dayBoundaries = [];

//     /**
//      * دالة البناء (Constructor)
//      */
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     /**
//      * الدالة الرئيسية لتشغيل الخوارزمية بالكامل
//      */
//     public function run()
//     {
//         try {
//             $this->populationRun->update(['status' => 'running', 'start_time' => now()]);
//             $this->loadAndPrepareData();

//             $currentGenerationNumber = 1;
//             $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
//             $this->evaluateFitness($currentPopulation);
//             Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//             $maxGenerations = $this->settings['max_generations'];
//             while ($currentGenerationNumber < $maxGenerations) {
//                 $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
//                 if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//                     Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//                     break;
//                 }

//                 $parents = $this->selectParents($currentPopulation);
//                 $currentGenerationNumber++;
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//                 $this->evaluateFitness($currentPopulation);
//                 Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//             }

//             $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
//             $this->populationRun->update([
//                 'status' => 'completed', 'end_time' => now(),
//                 'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
//             ]);
//             // dd([
//             //     '$this->settings' => $this->settings,
//             //     'currentPopulation' => $currentPopulation,
//             //     'maxGenerations' => $maxGenerations,
//             //     'bestInGen' => $bestInGen,
//             //     'parents' => $parents,
//             //     'finalBest' => $finalBest,

//             // ]);
//             Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
//         } catch (Exception $e) {
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     /**
//      * دالة لتحميل كل البيانات وتجهيزها للخوارزمية (القلب النابض للتحضير)
//      */
//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with('planSubject.subject', 'planSubject.plan')
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])->get();
//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         // --- الخطوة 1: (المنطق الجديد) "تقطيع" الشعب إلى بلوكات محاضرات (مهام جدولة) ---
//         $this->schedulingTasks = collect();
//         foreach ($sections as $section) {
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             if ($section->activity_type == 'Practical' && $subject->practical_hours > 0) {
//                 $duration = $subject->practical_hours * $this->settings['practical_minutes_per_credit'];
//                 if ($duration > 0) {
//                     $this->schedulingTasks->push((object)[
//                         'section' => $section,
//                         'duration_minutes' => $duration,
//                         'unique_id' => $section->id . '-prac'
//                     ]);
//                 }
//             }

//             if ($section->activity_type == 'Theory' && $subject->theoretical_hours > 0) {
//                 $totalHours = $subject->theoretical_hours;
//                 $minutesPerCredit = $this->settings['theory_minutes_per_credit'];

//                 $twoHourBlocks = floor($totalHours / 2);
//                 $oneHourBlocks = $totalHours % 2;

//                 for ($i = 0; $i < $twoHourBlocks; $i++) {
//                     $this->schedulingTasks->push((object)[
//                         'section' => $section, 'duration_minutes' => 2 * $minutesPerCredit,
//                         'unique_id' => $section->id . '-theory-' . $i
//                     ]);
//                 }
//                 if ($oneHourBlocks > 0) {
//                     $this->schedulingTasks->push((object)[
//                         'section' => $section, 'duration_minutes' => 1 * $minutesPerCredit,
//                         'unique_id' => $section->id . '-theory-' . $twoHourBlocks
//                     ]);
//                 }
//             }
//         }
//         if ($this->schedulingTasks->isEmpty()) throw new Exception("No scheduling tasks were created from the sections.");

//         $this->buildStudentGroupMap($sections);

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();

//         $timeslots = Timeslot::all();
//         if ($timeslots->isEmpty()) throw new Exception("Timeslots are not defined. Please generate them first.");

//         $this->dayBoundaries['start'] = Carbon::parse($timeslots->min('start_time'));
//         $this->dayBoundaries['end'] = Carbon::parse($timeslots->max('end_time'));
//         $this->dayBoundaries['days'] = $timeslots->pluck('day')->unique()->values()->all();

//         dd([
//             'sections' => $sections,
//             // 'this->schedulingTasks' => $this->schedulingTasks,
//             // 'this->instructors' => $this->instructors,
//             // 'this->theoryRooms' => $this->theoryRooms,
//             // 'this->practicalRooms' => $this->practicalRooms,
//             // 'this->dayBoundaries' => $this->dayBoundaries,
//             // 'timeslots' => $timeslots,

//         ]);

//         Log::info("Data loaded: " . $this->schedulingTasks->count() . " scheduling tasks created.");
//     }

//     private function buildStudentGroupMap(Collection $sections) {
//         $this->studentGroupMap = [];
//         $sectionsByContext = $sections->groupBy(function ($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $sectionsInContext) {
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id] = $groupIndex;
//                 }

//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
//                 foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
//                     }
//                 }
//             }
//         }
//      }

//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//         }
//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $genesToInsert = [];
//             foreach ($this->schedulingTasks as $task) {
//                 $instructor = $this->getRandomInstructorForSection($task->section);
//                 $room = $this->getRandomRoomForSection($task->section);

//                 $day = $this->dayBoundaries['days'][array_rand($this->dayBoundaries['days'])];
//                 $startTime = $this->getRandomStartTime($task->duration_minutes);
//                 $endTime = $startTime->copy()->addMinutes($task->duration_minutes);

//                 $genesToInsert[] = [
//                     'chromosome_id' => $chromosome->chromosome_id,
//                     'lecture_unique_id' => $task->unique_id,
//                     'section_id' => $task->section->id,
//                     'instructor_id' => $instructor->id,
//                     'room_id' => $room->id,
//                     'day' => $day,
//                     'start_time' => $startTime->format('H:i:s'),
//                     'end_time' => $endTime->format('H:i:s'),
//                 ];
//             }
//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     private function getRandomStartTime(int $durationMinutes)
//     {
//         $startOfDay = $this->dayBoundaries['start'];
//         $endOfDay = $this->dayBoundaries['end'];
//         $latestPossibleStart = $endOfDay->copy()->subMinutes($durationMinutes);

//         if ($latestPossibleStart->lt($startOfDay)) return $startOfDay;

//         $totalMinutesRange = $startOfDay->diffInMinutes($latestPossibleStart);
//         $randomMinutes = rand(0, $totalMinutesRange);

//         return $startOfDay->copy()->addMinutes($randomMinutes);
//     }

//     private function getRandomInstructorForSection(Section $section) {
//          $subject = optional($section->planSubject)->subject;
//         if (!$subject) return $this->instructors->random();

//         $suitableInstructors = $this->instructors->filter(fn($instructor) => $instructor->subjects->contains($subject->id));
//         if ($suitableInstructors->isNotEmpty()) return $suitableInstructors->random();

//         Log::warning("No specific instructors assigned to subject '{$subject->subject_name}'. Choosing a random instructor.");
//         return $this->instructors->random();
//      }

//     private function getRandomRoomForSection(Section $section) {
//         $roomsSource = ($section->activity_type === 'Practical') ? $this->practicalRooms : $this->theoryRooms;
//         if ($roomsSource->isEmpty()) {
//             $roomsSource = ($section->activity_type === 'Practical') ? $this->theoryRooms : $this->practicalRooms;
//             if ($roomsSource->isEmpty()) return Room::all()->random();
//         }

//         $suitableRooms = $roomsSource->where('room_size', '>=', $section->student_count);
//         return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $roomsSource->random();
//      }

//     private function evaluateFitness(Collection $chromosomes)
//     {
//         foreach ($chromosomes as $chromosome) {
//             $genes = $chromosome->genes()->with(['instructor', 'room', 'section.planSubject.subject'])->get();
//             $totalPenalty = 0;

//             $genesByDay = $genes->groupBy('day');

//             foreach($genesByDay as $dayGenes) {
//                 foreach ($dayGenes as $i => $geneA) {
//                     for ($j = $i + 1; $j < $dayGenes->count(); $j++) {
//                         $geneB = $dayGenes[$j];
//                         $startA = Carbon::parse($geneA->start_time);
//                         $endA = Carbon::parse($geneA->end_time);
//                         $startB = Carbon::parse($geneB->start_time);
//                         $endB = Carbon::parse($geneB->end_time);

//                         if ($startA < $endB && $endA > $startB) {
//                             if ($geneA->instructor_id == $geneB->instructor_id) $totalPenalty += 1000;
//                             if ($geneA->room_id == $geneB->room_id) $totalPenalty += 1000;

//                             $groupA = $this->studentGroupMap[$geneA->section_id] ?? null;
//                             $groupB = $this->studentGroupMap[$geneB->section_id] ?? null;
//                             if ($groupA && $groupB && $groupA == $groupB) $totalPenalty += 2000;
//                         }
//                     }
//                 }
//             }

//             foreach ($genes as $gene) {
//                 if (!$gene->section || !$gene->room || !$gene->instructor) continue;
//                 if ($gene->section->student_count > $gene->room->room_size) $totalPenalty += 800;

//                 $roomType = optional($gene->room->roomType)->room_type_name ?? '';
//                 $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);
//                 if ($gene->section->activity_type == 'Practical' && !$isPracticalRoom) $totalPenalty += 600;
//                 if ($gene->section->activity_type == 'Theory' && $isPracticalRoom) $totalPenalty += 600;

//                 if (!optional($gene->instructor)->canTeach(optional(optional($gene->section)->planSubject)->subject)) $totalPenalty += 2000;
//             }

//             DB::table('chromosomes')->where('chromosome_id', $chromosome->chromosome_id)->update(['penalty_value' => $totalPenalty]);
//             $chromosome->penalty_value = $totalPenalty;
//         }
//     }

//     private function selectParents(Collection $population){
//         $parents = [];
//         $tournamentSize = 3;
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $parents[] = $population->random($tournamentSize)->sortBy('penalty_value')->first();
//         }
//         return $parents;
//      }

//     private function createNewGeneration(array $parents, int $nextGenerationNumber) {
//         Log::info("Creating new generation #{$nextGenerationNumber}");
//         $childrenData = [];
//         $parentPool = $parents;

//         for ($i = 0; $i < $this->settings['population_size']; $i += 2) {
//             if (count($parentPool) < 2) $parentPool = $parents;

//             $p1_key = array_rand($parentPool); $parent1 = $parentPool[$p1_key]; unset($parentPool[$p1_key]);
//             $p2_key = array_rand($parentPool); $parent2 = $parentPool[$p2_key]; unset($parentPool[$p2_key]);

//             [$child1Genes, $child2Genes] = $this->performCrossover($parent1, $parent2);

//             $childrenData[] = $this->performMutation($child1Genes);
//             if (count($childrenData) < $this->settings['population_size']) {
//                 $childrenData[] = $this->performMutation($child2Genes);
//             }
//         }

//         $newlyCreatedChromosomes = [];
//         foreach ($childrenData as $genesToInsert) {
//             $newlyCreatedChromosomes[] = $this->saveChildChromosome($genesToInsert, $nextGenerationNumber);
//         }
//         return collect($newlyCreatedChromosomes);
//      }

//     private function performCrossover(Chromosome $parent1, Chromosome $parent2){
//         $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');
//         $child1Genes = []; $child2Genes = [];

//         $crossoverPoint = rand(1, $this->schedulingTasks->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->schedulingTasks as $task) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Genes : $p2Genes;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Genes : $p1Genes;

//             $child1Genes[$task->unique_id] = $sourceForChild1->get($task->unique_id) ?? $p2Genes->get($task->unique_id);
//             $child2Genes[$task->unique_id] = $sourceForChild2->get($task->unique_id) ?? $p1Genes->get($task->unique_id);

//             $currentIndex++;
//         }
//         return [$child1Genes, $child2Genes];
//      }

//     private function performMutation(array $genes): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($genes)) return [];
//             $taskKeyToMutate = array_rand($genes);
//             $originalTaskInfo = $this->schedulingTasks->firstWhere('unique_id', $taskKeyToMutate);
//             if(!$originalTaskInfo) return $genes;

//             $mutatedGene = $genes[$taskKeyToMutate];

//             // نولد معلومات جديدة للمحاضرة
//             $mutatedGene->day = $this->dayBoundaries['days'][array_rand($this->dayBoundaries['days'])];
//             $startTime = $this->getRandomStartTime($originalTaskInfo->duration_minutes);
//             $mutatedGene->start_time = $startTime->format('H:i:s');
//             $mutatedGene->end_time = $startTime->copy()->addMinutes($originalTaskInfo->duration_minutes)->format('H:i:s');

//             $genes[$taskKeyToMutate] = $mutatedGene;
//         }
//         return $genes;
//     }

//     private function saveChildChromosome(array $genesData, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create(['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber]);

//         $genesToInsert = [];
//         foreach($genesData as $gene) {
//             if (is_null($gene)) continue;
//             $genesToInsert[] = [
//                 'chromosome_id' => $chromosome->chromosome_id,
//                 'lecture_unique_id' => $gene->lecture_unique_id,
//                 'section_id' => $gene->section_id,
//                 'instructor_id' => $gene->instructor_id,
//                 'room_id' => $gene->room_id,
//                 'day' => $gene->day,
//                 'start_time' => $gene->start_time,
//                 'end_time' => $gene->end_time,
//             ];
//         }

//         foreach (array_chunk($genesToInsert, 500) as $chunk) {
//             Gene::insert($chunk);
//         }
//         return $chromosome;
//     }
// }



// namespace App\Services;

// use App\Models\Population;
// use App\Models\Chromosome;
// use App\Models\Gene;
// use App\Models\Section;
// use App\Models\Instructor;
// use App\Models\Room;
// use App\Models\Timeslot;
// use Carbon\Carbon;
// use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Str;
// use Exception;

// class GeneticAlgorithmService
// {
//     // --- خصائص لتخزين الإعدادات والبيانات ---
//     private array $settings;
//     private Population $populationRun;
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;

//     // (جديد) قائمة "بمهام الجدولة" (بلوكات المحاضرات)
//     private Collection $schedulingTasks;

//     // (جديد ومهم جداً) خريطة لربط كل "شعبة" بمجموعة الطلاب التي تنتمي إليها
//     private array $studentGroupMap = [];

//     // (جديد) حدود اليوم الدراسي
//     private array $dayBoundaries = [];

//     /**
//      * دالة البناء (Constructor)
//      */
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     /**
//      * الدالة الرئيسية لتشغيل الخوارزمية بالكامل
//      */
//     public function run()
//     {
//         try {
//             $this->populationRun->update(['status' => 'running', 'start_time' => now()]);
//             $this->loadAndPrepareData();

//             $currentGenerationNumber = 1;
//             $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
//             $this->evaluateFitness($currentPopulation);
//             Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//             $maxGenerations = $this->settings['max_generations'];
//             while ($currentGenerationNumber < $maxGenerations) {
//                 $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
//                 if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//                     Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//                     break;
//                 }

//                 $parents = $this->selectParents($currentPopulation);
//                 $currentGenerationNumber++;
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//                 $this->evaluateFitness($currentPopulation);
//                 Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//             }

//             $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
//             $this->populationRun->update([
//                 'status' => 'completed', 'end_time' => now(),
//                 'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
//             ]);
//             Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
//         } catch (Exception $e) {
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     /**
//      * دالة لتحميل كل البيانات وتجهيزها للخوارزمية (القلب النابض للتحضير)
//      */
//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with('planSubject.subject', 'planSubject.plan')
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])->get();
//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         // --- الخطوة 1: (المنطق الجديد) "تقطيع" الشعب إلى بلوكات محاضرات (مهام جدولة) ---
//         $this->schedulingTasks = collect();
//         foreach ($sections as $section) {
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             if ($section->activity_type == 'Practical' && $subject->practical_hours > 0) {
//                 $duration = $subject->practical_hours * $this->settings['practical_minutes_per_credit'];
//                 if ($duration > 0) {
//                     $this->schedulingTasks->push((object)[
//                         'section' => $section,
//                         'duration_minutes' => $duration,
//                         'unique_id' => $section->id . '-prac'
//                     ]);
//                 }
//             }

//             if ($section->activity_type == 'Theory' && $subject->theoretical_hours > 0) {
//                 $totalHours = $subject->theoretical_hours;
//                 $minutesPerCredit = $this->settings['theory_minutes_per_credit'];

//                 $twoHourBlocks = floor($totalHours / 2);
//                 $oneHourBlocks = $totalHours % 2;

//                 for ($i = 0; $i < $twoHourBlocks; $i++) {
//                     $this->schedulingTasks->push((object)[
//                         'section' => $section, 'duration_minutes' => 2 * $minutesPerCredit,
//                         'unique_id' => $section->id . '-theory-' . $i
//                     ]);
//                 }
//                 if ($oneHourBlocks > 0) {
//                     $this->schedulingTasks->push((object)[
//                         'section' => $section, 'duration_minutes' => 1 * $minutesPerCredit,
//                         'unique_id' => $section->id . '-theory-' . $twoHourBlocks
//                     ]);
//                 }
//             }
//         }
//         if ($this->schedulingTasks->isEmpty()) throw new Exception("No scheduling tasks were created from the sections.");

//         $this->buildStudentGroupMap($sections);

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();

//         $timeslots = Timeslot::all();
//         if ($timeslots->isEmpty()) throw new Exception("Timeslots are not defined. Please generate them first.");

//         $this->dayBoundaries['start'] = Carbon::parse($timeslots->min('start_time'));
//         $this->dayBoundaries['end'] = Carbon::parse($timeslots->max('end_time'));
//         $this->dayBoundaries['days'] = $timeslots->pluck('day')->unique()->values()->all();

//         Log::info("Data loaded: " . $this->schedulingTasks->count() . " scheduling tasks created.");
//     }

//     private function buildStudentGroupMap(Collection $sections) {
//         $this->studentGroupMap = [];
//         $sectionsByContext = $sections->groupBy(function ($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $sectionsInContext) {
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id] = $groupIndex;
//                 }

//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
//                 foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
//                     }
//                 }
//             }
//         }
//      }

//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//         }
//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $genesToInsert = [];
//             foreach ($this->schedulingTasks as $task) {
//                 $instructor = $this->getRandomInstructorForSection($task->section);
//                 $room = $this->getRandomRoomForSection($task->section);

//                 $day = $this->dayBoundaries['days'][array_rand($this->dayBoundaries['days'])];
//                 $startTime = $this->getRandomStartTime($task->duration_minutes);
//                 $endTime = $startTime->copy()->addMinutes($task->duration_minutes);

//                 // **(تصحيح مهم)**: التأكد من أننا نمرر الأعمدة الصحيحة
//                 $genesToInsert[] = [
//                     'chromosome_id' => $chromosome->chromosome_id,
//                     'lecture_unique_id' => $task->unique_id,
//                     'section_id' => $task->section->id,
//                     'instructor_id' => $instructor->id,
//                     'room_id' => $room->id,
//                     'day' => $day,
//                     'start_time' => $startTime->format('H:i:s'),
//                     'end_time' => $endTime->format('H:i:s'),
//                 ];
//             }
//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     private function getRandomStartTime(int $durationMinutes)
//     {
//         $startOfDay = $this->dayBoundaries['start'];
//         $endOfDay = $this->dayBoundaries['end'];
//         $latestPossibleStart = $endOfDay->copy()->subMinutes($durationMinutes);

//         if ($latestPossibleStart->lt($startOfDay)) return $startOfDay;

//         $totalMinutesRange = $startOfDay->diffInMinutes($latestPossibleStart);
//         $randomMinutes = rand(0, $totalMinutesRange);

//         return $startOfDay->copy()->addMinutes($randomMinutes);
//     }

//     private function getRandomInstructorForSection(Section $section) {
//          $subject = optional($section->planSubject)->subject;
//         if (!$subject) return $this->instructors->random();

//         $suitableInstructors = $this->instructors->filter(fn($instructor) => $instructor->subjects->contains($subject->id));
//         if ($suitableInstructors->isNotEmpty()) return $suitableInstructors->random();

//         Log::warning("No specific instructors assigned to subject '{$subject->subject_name}'. Choosing a random instructor.");
//         return $this->instructors->random();
//      }

//     private function getRandomRoomForSection(Section $section) {
//         $roomsSource = ($section->activity_type === 'Practical') ? $this->practicalRooms : $this->theoryRooms;
//         if ($roomsSource->isEmpty()) {
//             $roomsSource = ($section->activity_type === 'Practical') ? $this->theoryRooms : $this->practicalRooms;
//             if ($roomsSource->isEmpty()) return Room::all()->random();
//         }

//         $suitableRooms = $roomsSource->where('room_size', '>=', $section->student_count);
//         return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $roomsSource->random();
//      }

//     private function evaluateFitness(Collection $chromosomes)
//     {
//         foreach ($chromosomes as $chromosome) {
//             $genes = $chromosome->genes()->with(['instructor', 'room', 'section.planSubject.subject'])->get();
//             $totalPenalty = 0;

//             $genesByDay = $genes->groupBy('day');

//             foreach($genesByDay as $dayGenes) {
//                 foreach ($dayGenes as $i => $geneA) {
//                     for ($j = $i + 1; $j < $dayGenes->count(); $j++) {
//                         $geneB = $dayGenes[$j];
//                         $startA = Carbon::parse($geneA->start_time);
//                         $endA = Carbon::parse($geneA->end_time);
//                         $startB = Carbon::parse($geneB->start_time);
//                         $endB = Carbon::parse($geneB->end_time);

//                         if ($startA < $endB && $endA > $startB) {
//                             if ($geneA->instructor_id == $geneB->instructor_id) $totalPenalty += 1000;
//                             if ($geneA->room_id == $geneB->room_id) $totalPenalty += 1000;

//                             $groupA = $this->studentGroupMap[$geneA->section_id] ?? null;
//                             $groupB = $this->studentGroupMap[$geneB->section_id] ?? null;
//                             if ($groupA && $groupB && $groupA == $groupB) $totalPenalty += 2000;
//                         }
//                     }
//                 }
//             }

//             foreach ($genes as $gene) {
//                 if (!$gene->section || !$gene->room || !$gene->instructor) continue;
//                 if ($gene->section->student_count > $gene->room->room_size) $totalPenalty += 800;

//                 $roomType = optional($gene->room->roomType)->room_type_name ?? '';
//                 $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);
//                 if ($gene->section->activity_type == 'Practical' && !$isPracticalRoom) $totalPenalty += 600;
//                 if ($gene->section->activity_type == 'Theory' && $isPracticalRoom) $totalPenalty += 600;

//                 if (!optional($gene->instructor)->canTeach(optional(optional($gene->section)->planSubject)->subject)) $totalPenalty += 2000;
//             }

//             DB::table('chromosomes')->where('chromosome_id', $chromosome->chromosome_id)->update(['penalty_value' => $totalPenalty]);
//             $chromosome->penalty_value = $totalPenalty;
//         }
//     }

//     private function selectParents(Collection $population){
//         $parents = [];
//         $tournamentSize = 3;
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $parents[] = $population->random($tournamentSize)->sortBy('penalty_value')->first();
//         }
//         return $parents;
//      }

//     private function createNewGeneration(array $parents, int $nextGenerationNumber) {
//         Log::info("Creating new generation #{$nextGenerationNumber}");
//         $childrenData = [];
//         $parentPool = $parents;

//         for ($i = 0; $i < $this->settings['population_size']; $i += 2) {
//             if (count($parentPool) < 2) $parentPool = $parents;

//             $p1_key = array_rand($parentPool); $parent1 = $parentPool[$p1_key]; unset($parentPool[$p1_key]);
//             $p2_key = array_rand($parentPool); $parent2 = $parentPool[$p2_key]; unset($parentPool[$p2_key]);

//             [$child1Genes, $child2Genes] = $this->performCrossover($parent1, $parent2);

//             $childrenData[] = $this->performMutation($child1Genes);
//             if (count($childrenData) < $this->settings['population_size']) {
//                 $childrenData[] = $this->performMutation($child2Genes);
//             }
//         }

//         $newlyCreatedChromosomes = [];
//         foreach ($childrenData as $genesToInsert) {
//             $newlyCreatedChromosomes[] = $this->saveChildChromosome($genesToInsert, $nextGenerationNumber);
//         }
//         return collect($newlyCreatedChromosomes);
//      }

//     private function performCrossover(Chromosome $parent1, Chromosome $parent2){
//         $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');
//         $child1Genes = []; $child2Genes = [];

//         $crossoverPoint = rand(1, $this->schedulingTasks->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->schedulingTasks as $task) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Genes : $p2Genes;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Genes : $p1Genes;

//             $child1Genes[$task->unique_id] = $sourceForChild1->get($task->unique_id) ?? $p2Genes->get($task->unique_id);
//             $child2Genes[$task->unique_id] = $sourceForChild2->get($task->unique_id) ?? $p1Genes->get($task->unique_id);

//             $currentIndex++;
//         }
//         return [$child1Genes, $child2Genes];
//      }

//     private function performMutation(array $genes): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($genes)) return [];
//             $taskKeyToMutate = array_rand($genes);
//             $originalTaskInfo = $this->schedulingTasks->firstWhere('unique_id', $taskKeyToMutate);
//             if(!$originalTaskInfo) return $genes;

//             $mutatedGene = $genes[$taskKeyToMutate];

//             $mutatedGene->day = $this->dayBoundaries['days'][array_rand($this->dayBoundaries['days'])];
//             $startTime = $this->getRandomStartTime($originalTaskInfo->duration_minutes);
//             $mutatedGene->start_time = $startTime->format('H:i:s');
//             $mutatedGene->end_time = $startTime->copy()->addMinutes($originalTaskInfo->duration_minutes)->format('H:i:s');

//             $genes[$taskKeyToMutate] = $mutatedGene;
//         }
//         return $genes;
//     }

//     private function saveChildChromosome(array $genesData, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create(['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber]);

//         $genesToInsert = [];
//         foreach($genesData as $gene) {
//             if (is_null($gene)) continue;
//             // **(تصحيح مهم)**: التأكد من أننا نمرر الأعمدة الصحيحة لعملية الإدخال
//             $genesToInsert[] = [
//                 'chromosome_id' => $chromosome->chromosome_id,
//                 'lecture_unique_id' => $gene->lecture_unique_id,
//                 'section_id' => $gene->section_id,
//                 'instructor_id' => $gene->instructor_id,
//                 'room_id' => $gene->room_id,
//                 'day' => $gene->day,
//                 'start_time' => $gene->start_time,
//                 'end_time' => $gene->end_time,
//             ];
//         }

//         foreach (array_chunk($genesToInsert, 500) as $chunk) {
//             Gene::insert($chunk);
//         }
//         return $chromosome;
//     }
// }



// namespace App\Services;

// use App\Models\Population;
// use App\Models\Chromosome;
// use App\Models\Gene;
// use App\Models\Section;
// use App\Models\Instructor;
// use App\Models\Room;
// use App\Models\Timeslot;
// use Carbon\Carbon;
// use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Str;
// use Exception;

// class GeneticAlgorithmService
// {
//     // --- خصائص لتخزين الإعدادات والبيانات ---
//     private array $settings;
//     private Population $populationRun;
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;
//     private Collection $timeslots;

//     // قائمة "بمهام الجدولة" (بلوكات المحاضرات)
//     private Collection $schedulingTasks;

//     // خريطة الأوقات المتتالية لتسريع البحث
//     private array $consecutiveTimeslotsMap = [];

//     // خريطة لربط كل "شعبة" بمجموعة الطلاب التي تنتمي إليها
//     private array $studentGroupMap = [];

//     /**
//      * دالة البناء (Constructor)
//      * تستقبل إعدادات الخوارزمية وسجل عملية التشغيل
//      */
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     /**
//      * الدالة الرئيسية لتشغيل الخوارزمية بالكامل
//      */
//     public function run()
//     {
//         try {
//             $this->populationRun->update(['status' => 'running', 'start_time' => now()]);
//             $this->loadAndPrepareData();

//             $currentGenerationNumber = 1;
//             $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
//             $this->evaluateFitness($currentPopulation);
//             Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//             $maxGenerations = $this->settings['max_generations'];
//             while ($currentGenerationNumber < $maxGenerations) {
//                 $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
//                 if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//                     Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//                     break;
//                 }

//                 $parents = $this->selectParents($currentPopulation);
//                 $currentGenerationNumber++;
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//                 $this->evaluateFitness($currentPopulation);
//                 Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//             }

//             $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
//             $this->populationRun->update([
//                 'status' => 'completed', 'end_time' => now(),
//                 'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
//             ]);
//             Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
//         } catch (Exception $e) {
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     /**
//      * دالة لتحميل كل البيانات وتجهيزها للخوارزمية
//      * هذه الدالة تقوم بكل العمل التحضيري الذكي
//      */
//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with('planSubject.subject', 'planSubject.plan')
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])->get();
//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         // --- الخطوة 1: "تقطيع" الشعب إلى "مهام جدولة" (بلوكات) ---
//         $this->schedulingTasks = collect();
//         foreach ($sections as $section) {
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             // معالجة الجزء العملي (دائماً بلوك واحد)
//             if ($section->activity_type == 'Practical' && $subject->practical_hours > 0) {
//                 $slotsNeeded = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//                 if ($slotsNeeded > 0) {
//                     $this->schedulingTasks->push((object)[ 'section' => $section, 'slots_needed' => $slotsNeeded, 'unique_id' => $section->id . '-prac' ]);
//                 }
//             }

//             // معالجة الجزء النظري (قابل للتقسيم إلى بلوكات)
//             if ($section->activity_type == 'Theory' && $subject->theoretical_hours > 0) {
//                 $totalHours = $subject->theoretical_hours;
//                 $slotsPerCredit = $this->settings['theory_credit_to_slots'];

//                 $twoHourBlocks = floor($totalHours / 2);
//                 $oneHourBlocks = $totalHours % 2;

//                 for ($i = 0; $i < $twoHourBlocks; $i++) {
//                     $this->schedulingTasks->push((object)[ 'section' => $section, 'slots_needed' => 2 * $slotsPerCredit, 'unique_id' => $section->id . '-theory-' . $i ]);
//                 }
//                 if ($oneHourBlocks > 0) {
//                     $this->schedulingTasks->push((object)[ 'section' => $section, 'slots_needed' => 1 * $slotsPerCredit, 'unique_id' => $section->id . '-theory-' . $twoHourBlocks ]);
//                 }
//             }
//         }
//         if ($this->schedulingTasks->isEmpty()) throw new Exception("No scheduling tasks were created from the sections.");

//         // --- الخطوة 2: بناء "خريطة مجموعات الطلاب" الذكية ---
//         $this->buildStudentGroupMap($sections);

//         // --- الخطوة 3: تحميل باقي الموارد وبناء خريطة الأوقات المتتالية ---
//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();
//         $this->buildConsecutiveTimeslotsMap();

//         Log::info("Data loaded: " . $this->schedulingTasks->count() . " scheduling tasks created.");
//     }

//     /**
//      * دالة ذكية لبناء خريطة تربط كل شعبة بمجموعة الطلاب الخاصة بها
//      */
//     private function buildStudentGroupMap(Collection $sections)
//     {
//         $this->studentGroupMap = [];
//         $sectionsByContext = $sections->groupBy(function ($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $sectionsInContext) {
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id] = $groupIndex;
//                 }

//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
//                 foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
//                     }
//                 }
//             }
//         }
//     }

//     /**
//      * دالة مساعدة تبني مصفوفة لتسريع إيجاد الفترات الزمنية المتتالية
//      */
//     private function buildConsecutiveTimeslotsMap()
//     {
//         $timeslotsByDay = $this->timeslots->groupBy('day');
//         $this->consecutiveTimeslotsMap = [];
//         foreach ($timeslotsByDay as $dayTimeslots) {
//             $dayTimeslotsValues = $dayTimeslots->values();
//             for ($i = 0; $i < $dayTimeslotsValues->count(); $i++) {
//                 $currentSlot = $dayTimeslotsValues[$i];
//                 $this->consecutiveTimeslotsMap[$currentSlot->id] = [];
//                 for ($j = $i + 1; $j < $dayTimeslotsValues->count(); $j++) {
//                     $nextSlot = $dayTimeslotsValues[$j];
//                     if ($nextSlot->start_time == $currentSlot->end_time) {
//                         $this->consecutiveTimeslotsMap[$currentSlot->id][] = $nextSlot->id;
//                         $currentSlot = $nextSlot;
//                     } else {
//                         break;
//                     }
//                 }
//             }
//         }
//     }

//     /**
//      * دالة لإنشاء الجيل الأول من الحلول العشوائية (لكن بذكاء)
//      */
//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//         }
//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $genesToInsert = [];
//             foreach ($this->schedulingTasks as $task) {
//                 $instructor = $this->getRandomInstructorForSection($task->section);
//                 $room = $this->getRandomRoomForSection($task->section);
//                 $consecutiveSlots = $this->findRandomConsecutiveTimeslots($task->slots_needed);

//                 foreach ($consecutiveSlots as $timeslotId) {
//                     $genesToInsert[] = [
//                         'chromosome_id' => $chromosome->chromosome_id,
//                         'lecture_unique_id' => $task->unique_id,
//                         'section_id' => $task->section->id,
//                         'instructor_id' => $instructor->id,
//                         'room_id' => $room->id,
//                         'timeslot_id' => $timeslotId,
//                     ];
//                 }
//             }
//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     /**
//      * دالة مساعدة لإيجاد فترات زمنية متتالية عشوائية
//      */
//     private function findRandomConsecutiveTimeslots(int $slotsNeeded): array
//     {
//         if ($slotsNeeded <= 0) return [];
//         if ($slotsNeeded == 1) return [$this->timeslots->random()->id];
//         $attempts = 0;
//         while ($attempts < 200) {
//             $startSlot = $this->timeslots->random();
//             if (isset($this->consecutiveTimeslotsMap[$startSlot->id]) && count($this->consecutiveTimeslotsMap[$startSlot->id]) >= ($slotsNeeded - 1)) {
//                 return array_merge([$startSlot->id], array_slice($this->consecutiveTimeslotsMap[$startSlot->id], 0, $slotsNeeded - 1));
//             }
//             $attempts++;
//         }
//         Log::warning("Could not find {$slotsNeeded} consecutive slots, returning a single random slot as fallback.");
//         return [$this->timeslots->random()->id];
//     }

//     /**
//      * دالة مساعدة لاختيار مدرس مناسب للمادة
//      */
//     private function getRandomInstructorForSection(Section $section)
//     {
//         $subject = optional($section->planSubject)->subject;
//         if (!$subject) return $this->instructors->random();

//         $suitableInstructors = $this->instructors->filter(fn ($instructor) => $instructor->subjects->contains($subject->id));
//         if ($suitableInstructors->isNotEmpty()) return $suitableInstructors->random();

//         Log::warning("No specific instructors assigned to subject '{$subject->subject_name}'. Choosing a random instructor.");
//         return $this->instructors->random();
//     }

//     /**
//      * دالة مساعدة لاختيار قاعة مناسبة للشعبة
//      */
//     private function getRandomRoomForSection(Section $section)
//     {
//         $roomsSource = ($section->activity_type === 'Practical') ? $this->practicalRooms : $this->theoryRooms;
//         if ($roomsSource->isEmpty()) {
//             $roomsSource = ($section->activity_type === 'Practical') ? $this->theoryRooms : $this->practicalRooms;
//             if($roomsSource->isEmpty()) return Room::all()->random();
//         }

//         $suitableRooms = $roomsSource->where('room_size', '>=', $section->student_count);
//         return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $roomsSource->random();
//     }

//     /**
//      * دالة تقييم الجودة (Fitness Function)، تقوم بحساب العقوبات
//      */
//     private function evaluateFitness(Collection $chromosomes)
//     {
//         foreach ($chromosomes as $chromosome) {
//             $genes = $chromosome->genes()->with(['instructor', 'room', 'timeslot', 'section.planSubject.subject'])->get();
//             $totalPenalty = 0;

//             $genesByTimeslot = $genes->groupBy('timeslot_id');
//             foreach ($genesByTimeslot as $genesInSlot) {
//                 if ($genesInSlot->count() > 1) {
//                     $totalPenalty += ($genesInSlot->count() - $genesInSlot->pluck('instructor_id')->unique()->count()) * 1000;
//                     $totalPenalty += ($genesInSlot->count() - $genesInSlot->pluck('room_id')->unique()->count()) * 1000;
//                 }
//             }

//             $studentGroupTimeslotUsage = [];
//             foreach ($genes as $gene) {
//                 $sectionId = $gene->section_id;
//                 $timeslotId = $gene->timeslot_id;

//                 if (isset($this->studentGroupMap[$sectionId])) {
//                     $studentGroupId = $this->studentGroupMap[$sectionId];
//                     $key = $studentGroupId . '-' . $timeslotId;
//                     if (isset($studentGroupTimeslotUsage[$key])) {
//                         $totalPenalty += 2000;
//                     }
//                     $studentGroupTimeslotUsage[$key] = true;
//                 }
//             }

//             foreach ($genes->unique('lecture_unique_id') as $representativeGene) {
//                 if (!$representativeGene->section || !$representativeGene->room || !$representativeGene->instructor) continue;
//                 if ($representativeGene->section->student_count > $representativeGene->room->room_size) $totalPenalty += 800;

//                 $roomType = optional($representativeGene->room->roomType)->room_type_name ?? '';
//                 $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);
//                 if ($representativeGene->section->activity_type == 'Practical' && !$isPracticalRoom) $totalPenalty += 600;
//                 if ($representativeGene->section->activity_type == 'Theory' && $isPracticalRoom) $totalPenalty += 600;

//                 if (!optional($representativeGene->instructor)->canTeach(optional(optional($representativeGene->section)->planSubject)->subject)) $totalPenalty += 2000;
//             }

//             DB::table('chromosomes')->where('chromosome_id', $chromosome->chromosome_id)->update(['penalty_value' => $totalPenalty]);
//             $chromosome->penalty_value = $totalPenalty;
//         }
//     }

//     /**
//      * دالة اختيار الآباء للجيل القادم
//      */
//     private function selectParents(Collection $population): array
//     {
//         $parents = [];
//         $tournamentSize = 3;
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $parents[] = $population->random($tournamentSize)->sortBy('penalty_value')->first();
//         }
//         return $parents;
//     }

//     /**
//      * دالة إنشاء جيل جديد من الآباء
//      */
//     private function createNewGeneration(array $parents, int $nextGenerationNumber): Collection
//     {
//         Log::info("Creating new generation #{$nextGenerationNumber}");
//         $childrenData = [];
//         $parentPool = $parents;

//         for ($i = 0; $i < $this->settings['population_size']; $i += 2) {
//             if (count($parentPool) < 2) $parentPool = $parents;

//             $p1_key = array_rand($parentPool); $parent1 = $parentPool[$p1_key]; unset($parentPool[$p1_key]);
//             $p2_key = array_rand($parentPool); $parent2 = $parentPool[$p2_key]; unset($parentPool[$p2_key]);

//             [$child1Lectures, $child2Lectures] = $this->performCrossover($parent1, $parent2);

//             $childrenData[] = $this->performMutation($child1Lectures);
//             if (count($childrenData) < $this->settings['population_size']) {
//                 $childrenData[] = $this->performMutation($child2Lectures);
//             }
//         }

//         $newlyCreatedChromosomes = [];
//         foreach ($childrenData as $lecturesToInsert) {
//             $newlyCreatedChromosomes[] = $this->saveChildChromosome($lecturesToInsert, $nextGenerationNumber);
//         }
//         return collect($newlyCreatedChromosomes);
//     }

//     /**
//      * دالة التزاوج (Crossover)
//      */
//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Lectures = $parent1->genes()->get()->groupBy('lecture_unique_id');
//         $p2Lectures = $parent2->genes()->get()->groupBy('lecture_unique_id');
//         $child1Lectures = []; $child2Lectures = [];

//         $crossoverPoint = rand(1, $this->schedulingTasks->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->schedulingTasks as $task) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$task->unique_id] = $sourceForChild1->get($task->unique_id) ?? $p2Lectures->get($task->unique_id);
//             $child2Lectures[$task->unique_id] = $sourceForChild2->get($task->unique_id) ?? $p1Lectures->get($task->unique_id);

//             $currentIndex++;
//         }
//         return [$child1Lectures, $child2Lectures];
//     }

//     /**
//      * دالة الطفرة (Mutation)
//      */
//     private function performMutation(array $lectures): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($lectures)) return [];
//             $taskKeyToMutate = array_rand($lectures);
//             $originalTaskInfo = $this->schedulingTasks->firstWhere('unique_id', $taskKeyToMutate);
//             if(!$originalTaskInfo) return $lectures;

//             $newInstructor = $this->getRandomInstructorForSection($originalTaskInfo->section);
//             $newRoom = $this->getRandomRoomForSection($originalTaskInfo->section);
//             $newTimeslots = $this->findRandomConsecutiveTimeslots($originalTaskInfo->slots_needed);

//             $mutatedGenes = [];
//             foreach ($newTimeslots as $timeslotId) {
//                  $gene = new Gene([
//                      'lecture_unique_id' => $taskKeyToMutate,
//                      'section_id' => $originalTaskInfo->section->id,
//                      'instructor_id' => $newInstructor->id,
//                      'room_id' => $newRoom->id,
//                      'timeslot_id' => $timeslotId
//                  ]);
//                  $mutatedGenes[] = $gene;
//             }
//             $lectures[$taskKeyToMutate] = collect($mutatedGenes);
//         }
//         return $lectures;
//     }

//     /**
//      * دالة مساعدة لحفظ الكروموسوم الابن وجيناته في قاعدة البيانات
//      */
//     private function saveChildChromosome(array $lectures, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create(['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber]);

//         $genesToInsert = [];
//         foreach($lectures as $lectureGenes) {
//             if (is_null($lectureGenes)) continue;
//             foreach($lectureGenes as $gene) {
//                 $genesToInsert[] = [
//                     'chromosome_id' => $chromosome->chromosome_id,
//                     'lecture_unique_id' => $gene->lecture_unique_id,
//                     'section_id' => $gene->section_id,
//                     'instructor_id' => $gene->instructor_id,
//                     'room_id' => $gene->room_id,
//                     'timeslot_id' => $gene->timeslot_id,
//                 ];
//             }
//         }

//         foreach (array_chunk($genesToInsert, 500) as $chunk) {
//             Gene::insert($chunk);
//         }
//         return $chromosome;
//     }
// }


namespace App\Services;

use App\Models\Population;
use App\Models\Chromosome;
use App\Models\Gene;
use App\Models\Section;
use App\Models\Instructor;
use App\Models\Room;
use App\Models\Timeslot;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class GeneticAlgorithmService
{
    // --- خصائص لتخزين الإعدادات والبيانات ---
    private array $settings;
    private Population $populationRun;
    private Collection $instructors;
    private Collection $theoryRooms;
    private Collection $practicalRooms;

    // (جديد) قائمة "بمهام الجدولة" (بلوكات المحاضرات)
    private Collection $schedulingTasks;

    // (جديد ومهم جداً) خريطة لربط كل "شعبة" بمجموعة الطلاب التي تنتمي إليها
    private array $studentGroupMap = [];

    // (جديد) حدود اليوم الدراسي
    private array $dayBoundaries = [];

    /**
     * دالة البناء (Constructor)
     */
    public function __construct(array $settings, Population $populationRun)
    {
        $this->settings = $settings;
        $this->populationRun = $populationRun;
        Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
    }

    /**
     * الدالة الرئيسية لتشغيل الخوارزمية بالكامل
     */
    public function run()
    {
        try {
            $this->populationRun->update(['status' => 'running', 'start_time' => now()]);
            $this->loadAndPrepareData();

            $currentGenerationNumber = 1;
            $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
            $this->evaluateFitness($currentPopulation);
            Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

            $maxGenerations = $this->settings['max_generations'];
            while ($currentGenerationNumber < $maxGenerations) {
                $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
                if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
                    Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
                    break;
                }

                $parents = $this->selectParents($currentPopulation);
                $currentGenerationNumber++;
                $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
                $this->evaluateFitness($currentPopulation);
                Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
            }

            $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
            $this->populationRun->update([
                'status' => 'completed', 'end_time' => now(),
                'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
            ]);
            Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
        } catch (Exception $e) {
            Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            $this->populationRun->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * دالة لتحميل كل البيانات وتجهيزها للخوارزمية (القلب النابض للتحضير)
     */
    private function loadAndPrepareData()
    {
        Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

        $sections = Section::with('planSubject.subject', 'planSubject.plan')
            ->where('academic_year', $this->settings['academic_year'])
            ->where('semester', $this->settings['semester'])->get();
        if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

        // --- الخطوة 1: (المنطق الجديد) "تقطيع" الشعب إلى بلوكات محاضرات (مهام جدولة) ---
        $this->schedulingTasks = collect();
        foreach ($sections as $section) {
            $subject = optional($section->planSubject)->subject;
            if (!$subject) continue;

            if ($section->activity_type == 'Practical' && $subject->practical_hours > 0) {
                $duration = $subject->practical_hours * $this->settings['practical_minutes_per_credit'];
                if ($duration > 0) {
                    $this->schedulingTasks->push((object)[
                        'section' => $section,
                        'duration_minutes' => $duration,
                        'unique_id' => $section->id . '-prac'
                    ]);
                }
            }

            if ($section->activity_type == 'Theory' && $subject->theoretical_hours > 0) {
                $totalHours = $subject->theoretical_hours;
                $minutesPerCredit = $this->settings['theory_minutes_per_credit'];

                $twoHourBlocks = floor($totalHours / 2);
                $oneHourBlocks = $totalHours % 2;

                for ($i = 0; $i < $twoHourBlocks; $i++) {
                    $this->schedulingTasks->push((object)[
                        'section' => $section, 'duration_minutes' => 2 * $minutesPerCredit,
                        'unique_id' => $section->id . '-theory-' . $i
                    ]);
                }
                if ($oneHourBlocks > 0) {
                    $this->schedulingTasks->push((object)[
                        'section' => $section, 'duration_minutes' => 1 * $minutesPerCredit,
                        'unique_id' => $section->id . '-theory-' . $twoHourBlocks
                    ]);
                }
            }
        }
        if ($this->schedulingTasks->isEmpty()) throw new Exception("No scheduling tasks were created from the sections.");

        $this->buildStudentGroupMap($sections);

        $this->instructors = Instructor::with('subjects')->get();
        $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
        $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();

        $timeslots = Timeslot::all();
        if ($timeslots->isEmpty()) throw new Exception("Timeslots are not defined. Please generate them first.");

        $this->dayBoundaries['start'] = Carbon::parse($timeslots->min('start_time'));
        $this->dayBoundaries['end'] = Carbon::parse($timeslots->max('end_time'));
        $this->dayBoundaries['days'] = $timeslots->pluck('day')->unique()->values()->all();

        Log::info("Data loaded: " . $this->schedulingTasks->count() . " scheduling tasks created.");
    }

    private function buildStudentGroupMap(Collection $sections) {
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
                    $this->studentGroupMap[$theorySection->id] = $groupIndex;
                }

                $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
                foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
                    $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
                    if ($sortedSections->has($groupIndex - 1)) {
                        $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
                        $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
                    }
                }
            }
        }
     }

    private function createInitialPopulation(int $generationNumber): Collection
    {
        Log::info("Creating initial population (Generation #{$generationNumber})");

        $chromosomesToInsert = [];
        for ($i = 0; $i < $this->settings['population_size']; $i++) {
            $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
        }
        Chromosome::insert($chromosomesToInsert);
        $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

        foreach ($createdChromosomes as $chromosome) {
            $genesToInsert = [];
            foreach ($this->schedulingTasks as $task) {
                $instructor = $this->getRandomInstructorForSection($task->section);
                $room = $this->getRandomRoomForSection($task->section);

                $day = $this->dayBoundaries['days'][array_rand($this->dayBoundaries['days'])];
                $startTime = $this->getRandomStartTime($task->duration_minutes);
                $endTime = $startTime->copy()->addMinutes($task->duration_minutes);

                // **(تصحيح مهم)**: التأكد من أننا نمرر الأعمدة الصحيحة
                $genesToInsert[] = [
                    'chromosome_id' => $chromosome->chromosome_id,
                    'lecture_unique_id' => $task->unique_id,
                    'section_id' => $task->section->id,
                    'instructor_id' => $instructor->id,
                    'room_id' => $room->id,
                    'day' => $day,
                    'start_time' => $startTime->format('H:i:s'),
                    'end_time' => $endTime->format('H:i:s'),
                ];
            }
            foreach (array_chunk($genesToInsert, 500) as $chunk) {
                Gene::insert($chunk);
            }
        }

        return $createdChromosomes;
    }

    private function getRandomStartTime(int $durationMinutes)
    {
        $startOfDay = $this->dayBoundaries['start'];
        $endOfDay = $this->dayBoundaries['end'];
        $latestPossibleStart = $endOfDay->copy()->subMinutes($durationMinutes);

        if ($latestPossibleStart->lt($startOfDay)) return $startOfDay;

        $totalMinutesRange = $startOfDay->diffInMinutes($latestPossibleStart);
        $randomMinutes = rand(0, $totalMinutesRange);

        return $startOfDay->copy()->addMinutes($randomMinutes);
    }

    private function getRandomInstructorForSection(Section $section) {
         $subject = optional($section->planSubject)->subject;
        if (!$subject) return $this->instructors->random();

        $suitableInstructors = $this->instructors->filter(fn($instructor) => $instructor->subjects->contains($subject->id));
        if ($suitableInstructors->isNotEmpty()) return $suitableInstructors->random();

        Log::warning("No specific instructors assigned to subject '{$subject->subject_name}'. Choosing a random instructor.");
        return $this->instructors->random();
     }

    private function getRandomRoomForSection(Section $section) {
        $roomsSource = ($section->activity_type === 'Practical') ? $this->practicalRooms : $this->theoryRooms;
        if ($roomsSource->isEmpty()) {
            $roomsSource = ($section->activity_type === 'Practical') ? $this->theoryRooms : $this->practicalRooms;
            if ($roomsSource->isEmpty()) return Room::all()->random();
        }

        $suitableRooms = $roomsSource->where('room_size', '>=', $section->student_count);
        return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $roomsSource->random();
     }

    private function evaluateFitness(Collection $chromosomes)
    {
        foreach ($chromosomes as $chromosome) {
            $genes = $chromosome->genes()->with(['instructor', 'room', 'section.planSubject.subject'])->get();
            $totalPenalty = 0;

            $genesByDay = $genes->groupBy('day');

            foreach($genesByDay as $dayGenes) {
                foreach ($dayGenes as $i => $geneA) {
                    for ($j = $i + 1; $j < $dayGenes->count(); $j++) {
                        $geneB = $dayGenes[$j];
                        $startA = Carbon::parse($geneA->start_time);
                        $endA = Carbon::parse($geneA->end_time);
                        $startB = Carbon::parse($geneB->start_time);
                        $endB = Carbon::parse($geneB->end_time);

                        if ($startA < $endB && $endA > $startB) {
                            if ($geneA->instructor_id == $geneB->instructor_id) $totalPenalty += 1000;
                            if ($geneA->room_id == $geneB->room_id) $totalPenalty += 1000;

                            $groupA = $this->studentGroupMap[$geneA->section_id] ?? null;
                            $groupB = $this->studentGroupMap[$geneB->section_id] ?? null;
                            if ($groupA && $groupB && $groupA == $groupB) $totalPenalty += 2000;
                        }
                    }
                }
            }

            foreach ($genes as $gene) {
                if (!$gene->section || !$gene->room || !$gene->instructor) continue;
                if ($gene->section->student_count > $gene->room->room_size) $totalPenalty += 800;

                $roomType = optional($gene->room->roomType)->room_type_name ?? '';
                $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);
                if ($gene->section->activity_type == 'Practical' && !$isPracticalRoom) $totalPenalty += 600;
                if ($gene->section->activity_type == 'Theory' && $isPracticalRoom) $totalPenalty += 600;

                if (!optional($gene->instructor)->canTeach(optional(optional($gene->section)->planSubject)->subject)) $totalPenalty += 2000;
            }

            DB::table('chromosomes')->where('chromosome_id', $chromosome->chromosome_id)->update(['penalty_value' => $totalPenalty]);
            $chromosome->penalty_value = $totalPenalty;
        }
    }

    private function selectParents(Collection $population){
        $parents = [];
        $tournamentSize = 3;
        for ($i = 0; $i < $this->settings['population_size']; $i++) {
            $parents[] = $population->random($tournamentSize)->sortBy('penalty_value')->first();
        }
        return $parents;
     }

    private function createNewGeneration(array $parents, int $nextGenerationNumber) {
        Log::info("Creating new generation #{$nextGenerationNumber}");
        $childrenData = [];
        $parentPool = $parents;

        for ($i = 0; $i < $this->settings['population_size']; $i += 2) {
            if (count($parentPool) < 2) $parentPool = $parents;

            $p1_key = array_rand($parentPool); $parent1 = $parentPool[$p1_key]; unset($parentPool[$p1_key]);
            $p2_key = array_rand($parentPool); $parent2 = $parentPool[$p2_key]; unset($parentPool[$p2_key]);

            [$child1Genes, $child2Genes] = $this->performCrossover($parent1, $parent2);

            $childrenData[] = $this->performMutation($child1Genes);
            if (count($childrenData) < $this->settings['population_size']) {
                $childrenData[] = $this->performMutation($child2Genes);
            }
        }

        $newlyCreatedChromosomes = [];
        foreach ($childrenData as $genesToInsert) {
            $newlyCreatedChromosomes[] = $this->saveChildChromosome($genesToInsert, $nextGenerationNumber);
        }
        return collect($newlyCreatedChromosomes);
     }

    private function performCrossover(Chromosome $parent1, Chromosome $parent2){
        $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
        $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');
        $child1Genes = []; $child2Genes = [];

        $crossoverPoint = rand(1, $this->schedulingTasks->count() - 1);
        $currentIndex = 0;

        foreach ($this->schedulingTasks as $task) {
            $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Genes : $p2Genes;
            $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Genes : $p1Genes;

            $child1Genes[$task->unique_id] = $sourceForChild1->get($task->unique_id) ?? $p2Genes->get($task->unique_id);
            $child2Genes[$task->unique_id] = $sourceForChild2->get($task->unique_id) ?? $p1Genes->get($task->unique_id);

            $currentIndex++;
        }
        return [$child1Genes, $child2Genes];
     }

    private function performMutation(array $genes): array
    {
        if (lcg_value() < $this->settings['mutation_rate']) {
            if (empty($genes)) return [];
            $taskKeyToMutate = array_rand($genes);
            $originalTaskInfo = $this->schedulingTasks->firstWhere('unique_id', $taskKeyToMutate);
            if(!$originalTaskInfo) return $genes;

            $mutatedGene = $genes[$taskKeyToMutate];

            $mutatedGene->day = $this->dayBoundaries['days'][array_rand($this->dayBoundaries['days'])];
            $startTime = $this->getRandomStartTime($originalTaskInfo->duration_minutes);
            $mutatedGene->start_time = $startTime->format('H:i:s');
            $mutatedGene->end_time = $startTime->copy()->addMinutes($originalTaskInfo->duration_minutes)->format('H:i:s');

            $genes[$taskKeyToMutate] = $mutatedGene;
        }
        return $genes;
    }

    private function saveChildChromosome(array $genesData, int $generationNumber): Chromosome
    {
        $chromosome = Chromosome::create(['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber]);

        $genesToInsert = [];
        foreach($genesData as $gene) {
            if (is_null($gene)) continue;
            // **(تصحيح مهم)**: التأكد من أننا نمرر الأعمدة الصحيحة لعملية الإدخال
            $genesToInsert[] = [
                'chromosome_id' => $chromosome->chromosome_id,
                'lecture_unique_id' => $gene->lecture_unique_id,
                'section_id' => $gene->section_id,
                'instructor_id' => $gene->instructor_id,
                'room_id' => $gene->room_id,
                'day' => $gene->day,
                'start_time' => $gene->start_time,
                'end_time' => $gene->end_time,
            ];
        }

        foreach (array_chunk($genesToInsert, 500) as $chunk) {
            Gene::insert($chunk);
        }
        return $chromosome;
    }
}
