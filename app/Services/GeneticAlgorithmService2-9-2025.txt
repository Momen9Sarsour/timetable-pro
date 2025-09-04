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
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Str;
// use Exception;

// class GeneticAlgorithmService
// {
//     // الخصائص لتخزين البيانات والإعدادات
//     private array $settings;
//     private Population $populationRun;
//     private Collection $sectionsToSchedule;
//     private Collection $instructors;
//     private Collection $rooms;
//     private Collection $timeslots;
//     private Collection $theoryRooms; // ** قاعات نظرية مفلترة**
//     private Collection $practicalRooms; // ** قاعات عملية مفلترة**

//     /**
//      * تهيئة الـ Service
//      */
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     /**
//      * الدالة الرئيسية لتشغيل الخوارزمية
//      */
//     public function run()
//     {
//         try {
//             $this->populationRun->update(['status' => 'running', 'start_time' => now()]);
//             // 1. تحميل كل البيانات اللازمة
//             $this->loadDataForContext();

//             // 2. إنشاء الجيل الأول وتقييمه
//             $currentGenerationNumber = 1;
//             $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
//             $this->evaluateFitness($currentPopulation);
//             Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//             // 3. حلقة تطور الأجيال
//             $maxGenerations = $this->settings['max_generations'];
//             while ($currentGenerationNumber < $maxGenerations) {
//                 // التحقق من شرط التوقف
//                 $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
//                 if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//                     Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//                     break;
//                 }

//                 // اختيار الآباء
//                 $parents = $this->selectParents($currentPopulation);

//                 // إنشاء الجيل الجديد
//                 $currentGenerationNumber++;
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);

//                 // تقييم الجيل الجديد
//                 $this->evaluateFitness($currentPopulation);
//                 Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//             }

//             // 4. تحديث النتيجة النهائية
//             $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
//             $this->populationRun->update([
//                 'status' => 'completed',
//                 'end_time' => now(),
//                 'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
//             ]);
//             // dd([
//             //     'currentPopulation' => $currentPopulation,
//             //     'bestInGen' => $bestInGen,
//             //     'finalBest' => $finalBest,
//             //     '$finalBest->chromosome_id' => $finalBest->chromosome_id,
//             // ]);
//             Log::info("GA Run ID: {$this->populationRun->population_id} completed.");
//         } catch (Exception $e) {
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e; // Throw exception to fail the job
//         }
//     }

//     /**
//      * تحميل البيانات المفلترة بناءً على السياق (سنة وفصل)
//      */
//     // private function loadDataForContext()
//     // {
//     //     Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//     //     // جلب الشعب المحددة للسياق
//     //     $this->sectionsToSchedule = Section::where('academic_year', $this->settings['academic_year'])
//     //         ->where('semester', $this->settings['semester'])->get();
//     //     if ($this->sectionsToSchedule->isEmpty()) {
//     //         throw new Exception("No sections found for the selected context (Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}).");
//     //     }

//     //     // جلب كل الموارد الأخرى المتاحة
//     //     $this->instructors = Instructor::all();
//     //     $this->rooms = Room::all();
//     //     $this->timeslots = Timeslot::all(); // *** نعبئ this->timeslots هنا ***

//     //     if ($this->instructors->isEmpty() || $this->rooms->isEmpty() || $this->timeslots->isEmpty()) {
//     //         throw new Exception("Missing essential data (instructors, rooms, or timeslots).");
//     //     }
//     //     Log::info("Data loaded: " . $this->sectionsToSchedule->count() . " sections to be scheduled.");
//     // }

//     private function loadDataForContext()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         // جلب الشعب وتحميل العلاقات اللازمة لتحديد المدرسين والقاعات
//         $this->sectionsToSchedule = Section::with('planSubject.subject.instructors') // *** تحميل المدرسين المرتبطين بالمادة ***
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])->get();

//         if ($this->sectionsToSchedule->isEmpty()) {
//             throw new Exception("No sections found for the selected context.");
//         }

//         // جلب كل المدرسين والقاعات والأوقات
//         $this->instructors = Instructor::all(); // سنستخدمها كـ fallback
//         $this->timeslots = Timeslot::all();
//         // **تقسيم القاعات حسب النوع**
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();

//         if ($this->instructors->isEmpty() || $this->theoryRooms->isEmpty() || $this->practicalRooms->isEmpty() || $this->timeslots->isEmpty()) {
//             throw new Exception("Missing essential data (instructors, theory/practical rooms, or timeslots).");
//         }
//         Log::info("Data loaded: " . $this->sectionsToSchedule->count() . " sections to be scheduled.");
//     }

//     /**
//      * إنشاء الجيل الأول من الجداول العشوائية
//      */
//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating initial population (Generation #{$generationNumber})");
//         $newChromosomesData = [];
//         // انشاء مجموعة جداول عشوائية Chromosomes
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $newChromosomesData[] = [
//                 'population_id' => $this->populationRun->population_id,
//                 'penalty_value' => -1, // قيمة مبدئية
//                 'generation_number' => $generationNumber,
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ];
//         }
//         Chromosome::insert($newChromosomesData); // إدخال كل الكروموسومات دفعة واحدة

//         // جلب الكروموسومات التي تم إنشاؤها للتو للحصول على IDs
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)
//         ->where('generation_number', $generationNumber)->get();

//         $allGenesToInsert = [];
//         foreach ($createdChromosomes as $chromosome) {
//             foreach ($this->sectionsToSchedule as $section) {
//                 $instructor = $this->getRandomInstructorForSection($section);
//                 $room = $this->getRandomRoomForSection($section);
//                 $allGenesToInsert[] = [
//                     'chromosome_id' => $chromosome->chromosome_id,
//                     'section_id' => $section->id,
//                     // 'instructor_id' => $this->instructors->random()->id,
//                     'instructor_id' => $instructor->id,
//                     // 'room_id' => $this->rooms->random()->id,
//                     'room_id' => $room->id,
//                     'timeslot_id' => $this->timeslots->random()->id, // *** استخدام الطريقة الصحيحة ***
//                 ];
//             }
//         }

//         // إدخال كل الجينات لكل الكروموسومات دفعة واحدة (أكثر كفاءة)
//         foreach (array_chunk($allGenesToInsert, 500) as $chunk) {
//             Gene::insert($chunk);
//         }


//         // dd([
//         //     'createdChromosomes' => $createdChromosomes,
//         //     '$this->timeslots->random()->id' => $this->timeslots->random()->id,
//         //     'allGenesToInsert' => $allGenesToInsert,
//         //     'array_chunk($allGenesToInsert,500)' => array_chunk($allGenesToInsert,500),
//         // ]);
//         return $createdChromosomes;
//     }

//     private function getRandomInstructorForSection(Section $section)
//     {
//         // 1. جلب المدرسين المعينين للمادة من خلال جدول instructor_subject
//         $suitableInstructors = optional(optional($section->planSubject)->subject)->instructors;

//         // 2. إذا وجد مدرسون مناسبون للمادة، اختر واحداً منهم عشوائياً
//         if ($suitableInstructors && $suitableInstructors->isNotEmpty()) {
//             return $suitableInstructors->random();
//         }

//         // جلب المدرسين المعينين لهذه الشعبة المحددة من خلال instructor_section
//         // if ($section->instructors->isNotEmpty()) {
//         //     return $section->instructors->random();
//         // }


//         // 3. كحل بديل (Fallback) إذا لم يتم تعيين مدرسين للمادة، اختر أي مدرس عشوائي
//         Log::warning("No specific instructors assigned to subject for section ID: {$section->id}. Choosing a random instructor.");
//         return $this->instructors->random();
//     }


//     /**
//      * دالة مساعدة لاختيار قاعة عشوائية ومناسبة للشعبة
//      */
//     private function getRandomRoomForSection(Section $section)
//     {
//         // 1. تحديد نوع النشاط للشعبة (نظري أو عملي)
//         $activityType = $section->activity_type;

//         // 2. إذا كان عملي، اختر من قاعات المختبرات
//         if ($activityType === 'Practical' && $this->practicalRooms->isNotEmpty()) {
//             // يمكنك إضافة منطق هنا لاختيار مختبر سعته مناسبة للشعبة
//             $suitableRooms = $this->practicalRooms->where('room_size', '>=', $section->student_count);
//             return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $this->practicalRooms->random(); // كحل بديل إذا لا توجد قاعة مناسبة
//         }

//         // 3. إذا كان نظري (أو كـ fallback)، اختر من القاعات النظرية
//         if ($this->theoryRooms->isNotEmpty()) {
//             $suitableRooms = $this->theoryRooms->where('room_size', '>=', $section->student_count);
//             return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $this->theoryRooms->random();
//         }

//         // 4. حل بديل أخير إذا لم يتم العثور على أي نوع
//         return Room::all()->random();
//     }

//     /**
//      * تقييم جودة كل جدول في المجموعة
//      */
//     private function evaluateFitness(Collection $chromosomes)
//     {
//         Log::info("Evaluating fitness for " . $chromosomes->count() . " chromosomes...");
//         // حساب العقوبات لكل جدول
//         foreach ($chromosomes as $chromosome) {
//             $genes = $chromosome->genes()->with(['instructor', 'room', 'timeslot', 'section.planSubject.subject'])->get();
//             // $genes = $chromosome->genes()->with(['instructor', 'room', 'timeslot', 'section.planSubject.subject', 'section.activity_type'])->get();
//             $totalPenalty = 0;
//             $genesByTimeslot = $genes->groupBy('timeslot_id');

//             // هنستخدم هذا المصفوفة لحساب الساعات اليومية
//             $dailyHours = [];

//             foreach ($genesByTimeslot as $genesInSlot) {
//                 if ($genesInSlot->count() > 1) {
//                     $totalPenalty += ($genesInSlot->count() - $genesInSlot->pluck('instructor_id')->unique()->count()) * 1000;
//                     $totalPenalty += ($genesInSlot->count() - $genesInSlot->pluck('room_id')->unique()->count()) * 1000;
//                     $totalPenalty += ($genesInSlot->count() - $genesInSlot->pluck('section_id')->unique()->count()) * 1000;
//                 }

//                 // القيود على كل محاضرة في هذا الوقت
//                 foreach ($genesInSlot as $gene) {
//                     $day = $gene->timeslot->day; // اليوم (مثلاً "Monday")

//                     // نحسب الساعات اليومية
//                     if (!isset($dailyHours[$day])) {
//                         $dailyHours[$day] = 0;
//                     }
//                     $dailyHours[$day] += $gene->timeslot->duration_hours;

//                     // سعة القاعة
//                     if ($gene->section->students_count > $gene->room->capacity) {
//                         $totalPenalty += 800 * ($gene->section->students_count - $gene->room->capacity);
//                     }

//                     // نوع القاعة
//                     if ($gene->section->activity_type != $gene->room->room_type) {
//                         $totalPenalty += 600;
//                     }

//                     // وقت المحاضرة (إجازة)
//                     if ($gene->timeslot->is_holiday) {
//                         $totalPenalty += 1500;
//                     }

//                     // أهلية المدرس
//                     // if (!$gene->instructor->canTeach($gene->section->subject)) {
//                     if (
//                         $gene->instructor &&
//                         $gene->section &&
//                         $gene->section->planSubject &&
//                         $gene->section->planSubject->subject &&
//                         !$gene->instructor->canTeach($gene->section->planSubject->subject)
//                     ) {
//                         $totalPenalty += 2000;
//                     }
//                 }
//             }

//             // القيود على الساعات اليومية
//             foreach ($dailyHours as $day => $hours) {
//                 if ($hours > 10) { // إذا تجاوزت 10 ساعات في اليوم
//                     $totalPenalty += 300;
//                 }
//             }
//             // ... (باقي حسابات الـ penalty للسعة والنوع) ...
//             $chromosome->update(['penalty_value' => $totalPenalty]);
//         }
//         // dd([
//         //     'chromosomes' => $chromosomes,
//         //     'genes' => $chromosome->genes->all(),
//         //     'genesByTimeslot' => $genesByTimeslot,
//         // ]);

//         Log::info("Fitness evaluation completed.");
//     }

//     /**
//      * اختيار الآباء (Tournament Selection)
//      */
//     private function selectParents(Collection $population): array
//     {
//         $parents = [];
//         $populationSize = $this->settings['population_size'];
//         $tournamentSize = 3; // يمكن جعله إعداداً
//         for ($i = 0; $i < $populationSize; $i++) {
//             $parents[] = $population->random($tournamentSize)->sortBy('penalty_value')->first();
//         }
//         // dd([
//         //     'population' => $population,
//         //     'parents' => $parents,
//         // ]);
//         return $parents;
//     }

//     /**
//      * إنشاء جيل جديد بالتزاوج والطفرة
//      */
//     private function createNewGeneration(array $parents, int $nextGenerationNumber): Collection
//     {
//         Log::info("Creating new generation #{$nextGenerationNumber}");
//         $childrenChromosomesData = [];
//         $populationSize = $this->settings['population_size'];
//         $parentPool = $parents;

//         for ($i = 0; $i < $populationSize; $i += 2) {
//             if (count($parentPool) < 2) {
//                 $parentPool = $parents;
//             }
//             $p1_key = array_rand($parentPool);
//             $parent1 = $parentPool[$p1_key];
//             unset($parentPool[$p1_key]);
//             $p2_key = array_rand($parentPool);
//             $parent2 = $parentPool[$p2_key];
//             unset($parentPool[$p2_key]);

//             [$child1GenesData, $child2GenesData] = $this->performCrossover($parent1, $parent2);

//             $childrenChromosomesData[] = $this->performMutation($child1GenesData);
//             if (count($childrenChromosomesData) < $populationSize) {
//                 $childrenChromosomesData[] = $this->performMutation($child2GenesData);
//             }
//         }


//         $newlyCreatedChromosomes = [];
//         foreach ($childrenChromosomesData as $genesToInsert) {
//             $newlyCreatedChromosomes[] = $this->saveChildChromosome($genesToInsert, $nextGenerationNumber);
//         }
//         // dd([
//         //     'nextGenerationNumber' => $nextGenerationNumber,
//         //     'parents' => $parents,
//         //     'parentPool' => $parentPool,
//         //     'child1GenesData' => $child1GenesData,
//         //     'child2GenesData' => $child2GenesData,
//         //     'childrenChromosomesData' => $childrenChromosomesData,
//         //     'newlyCreatedChromosomes' => $newlyCreatedChromosomes,
//         // ]);
//         return collect($newlyCreatedChromosomes);
//     }

//     /**
//      * تنفيذ التزاوج (Single-Point Crossover) - آمن
//      */
//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Genes = $parent1->genes()->get()->keyBy('section_id');
//         $p2Genes = $parent2->genes()->get()->keyBy('section_id');
//         $child1GenesData = [];
//         $child2GenesData = [];
//         $crossoverPoint = rand(1, $this->sectionsToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->sectionsToSchedule as $section) {
//             $sectionId = $section->id;
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Genes : $p2Genes;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Genes : $p1Genes;
//             $gene1 = $sourceForChild1->get($sectionId) ?? $p2Genes->get($sectionId);
//             $gene2 = $sourceForChild2->get($sectionId) ?? $p1Genes->get($sectionId);

//             $child1GenesData[] = $gene1 ? $this->extractGeneData($gene1) : $this->createRandomGeneData($sectionId);
//             $child2GenesData[] = $gene2 ? $this->extractGeneData($gene2) : $this->createRandomGeneData($sectionId);
//             $currentIndex++;
//         }
//         return [$child1GenesData, $child2GenesData];
//     }

//     /**
//      * تنفيذ الطفرة
//      */
//     // private function performMutation(array $genes): array
//     // {
//     //     foreach ($genes as &$geneData) {
//     //         if (lcg_value() < $this->settings['mutation_rate']) {
//     //             $geneData['timeslot_id'] = $this->timeslots->random()->id; // ** استخدام الطريقة الصحيحة **
//     //         }
//     //     }
//     //     return $genes;
//     // }
//     private function performMutation(array $genes): array
//     {
//         foreach ($genes as &$geneData) { // استخدام & لتمرير بالمرجع
//             if (lcg_value() < $this->settings['mutation_rate']) {
//                 Log::info("Mutation on gene for section ID: {$geneData['section_id']}");
//                 // اختيار عشوائي لما سيتم تغييره (الطفرة)
//                 $mutationType = rand(1, 3);
//                 switch ($mutationType) {
//                     case 1:
//                         // تغيير الفترة الزمنية
//                         $geneData['timeslot_id'] = $this->timeslots->random()->id;
//                         break;
//                     case 2:
//                         // تغيير القاعة (مع مراعاة النوع)
//                         $section = $this->sectionsToSchedule->find($geneData['section_id']);
//                         if ($section) {
//                             $geneData['room_id'] = $this->getRandomRoomForSection($section)->id;
//                         }
//                         break;
//                     case 3:
//                         // تغيير المدرس (مع مراعاة التخصص)
//                         $section = $this->sectionsToSchedule->find($geneData['section_id']);
//                         if ($section) {
//                             $geneData['instructor_id'] = $this->getRandomInstructorForSection($section)->id;
//                         }
//                         break;
//                 }
//             }
//         }
//         return $genes;
//     }


//     /**
//      * دوال مساعدة
//      */
//     private function saveChildChromosome(array $genes, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create(['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber]);
//         foreach ($genes as &$geneData) {
//             $geneData['chromosome_id'] = $chromosome->chromosome_id;
//         }
//         Gene::insert($genes);
//         return $chromosome;
//     }
//     private function extractGeneData($gene): array
//     {
//         return ['section_id' => $gene->section_id, 'instructor_id' => $gene->instructor_id, 'room_id' => $gene->room_id, 'timeslot_id' => $gene->timeslot_id];
//     }

//     private function createRandomGeneData(int $sectionId): array
//     {
//         $section = $this->sectionsToSchedule->find($sectionId);
//         if (!$section) return []; // حالة نادرة

//         $instructor = $this->getRandomInstructorForSection($section);
//         $room = $this->getRandomRoomForSection($section);

//         return [
//             'section_id' => $sectionId,
//             'instructor_id' => $instructor->id,
//             'room_id' => $room->id,
//             'timeslot_id' => $this->timeslots->random()->id,
//         ];

//         // return [
//         //     'section_id' => $sectionId,
//         //     'instructor_id' => $this->instructors->random()->id,
//         //     'room_id' => $this->rooms->random()->id,
//         //     'timeslot_id' => $this->timeslots->random()->id, // ** استخدام الطريقة الصحيحة **
//         // ];
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

//     // (جديد ومحسن) قائمة ببلوكات المحاضرات المطلوب جدولتها
//     private Collection $lectureBlocksToSchedule;

//     // (جديد) خريطة الأوقات المتتالية لتسريع البحث
//     private array $consecutiveTimeslotsMap = [];

//     // (جديد ومهم جداً) خريطة لربط كل "شعبة" بمجموعة الطلاب التي تنتمي إليها
//     private array $studentGroupMap = [];

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
//                 // $currentPopulation = $this->createNewGeneration($parents, $nextGenerationNumber);
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
//      * دالة لتحميل كل البيانات وتجهيزها للخوارزمية (العقل المدبر)
//      */
//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with('planSubject.subject', 'planSubject.plan')
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])->get();
//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         // --- الخطوة 1: (المنطق الجديد) إنشاء بلوكات المحاضرات ---
//         $this->lectureBlocksToSchedule = collect();
//         foreach ($sections as $section) {
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             if ($section->activity_type == 'Practical') {
//                 $totalSlotsNeeded = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//                 if ($totalSlotsNeeded > 0) {
//                     $this->lectureBlocksToSchedule->push((object)[
//                         'section' => $section,
//                         'slots_needed' => $totalSlotsNeeded,
//                         'unique_id' => $section->id . '-block1' // بلوك عملي واحد
//                     ]);
//                 }
//             } elseif ($section->activity_type == 'Theory') {
//                 $totalSlotsNeeded = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//                 $blockCounter = 1;
//                 // **التقسيم الذكي للبلوكات النظرية**
//                 while ($totalSlotsNeeded > 0) {
//                     $slotsForThisBlock = 0;
//                     if ($totalSlotsNeeded >= 2) {
//                         $slotsForThisBlock = 2; // نأخذ بلوك من ساعتين
//                     } else {
//                         $slotsForThisBlock = 1; // نأخذ بلوك من ساعة
//                     }
//                     $this->lectureBlocksToSchedule->push((object)[
//                         'section' => $section,
//                         'slots_needed' => $slotsForThisBlock,
//                         'unique_id' => $section->id . '-block' . $blockCounter++
//                     ]);
//                     $totalSlotsNeeded -= $slotsForThisBlock;
//                 }
//             }
//         }
//         if ($this->lectureBlocksToSchedule->isEmpty()) throw new Exception("No lecture blocks to schedule were found after processing credit hours.");

//         // --- الخطوة 2: بناء "خريطة مجموعات الطلاب" الذكية ---
//         $this->buildStudentGroupMap($sections);

//         // --- الخطوة 3: تحميل باقي الموارد وبناء خريطة الأوقات المتتالية ---
//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();
//         $this->buildConsecutiveTimeslotsMap();

//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
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
//             // الآن كل جين يمثل بلوك محاضرة كامل
//             foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//                 $instructor = $this->getRandomInstructorForSection($lectureBlock->section);
//                 $room = $this->getRandomRoomForSection($lectureBlock->section);
//                 $consecutiveSlots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);

//                 // **(المنطق الجديد)**: إنشاء سجل جين واحد فقط مع مصفوفة من الفترات الزمنية
//                 $genesToInsert[] = [
//                     'chromosome_id' => $chromosome->chromosome_id,
//                     'lecture_unique_id' => $lectureBlock->unique_id,
//                     'section_id' => $lectureBlock->section->id,
//                     'instructor_id' => $instructor->id,
//                     'room_id' => $room->id,
//                     'timeslot_ids' => json_encode($consecutiveSlots) // نحول المصفوفة إلى JSON
//                 ];
//             }
//             Gene::insert($genesToInsert);
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
//             $genes = $chromosome->genes()->with(['instructor', 'room', 'section.planSubject.subject'])->get();
//             $totalPenalty = 0;

//             // --- (المنطق الجديد) بناء خريطة استخدام الموارد ---
//             $resourceUsageMap = [];

//             foreach($genes as $gene) {
//                 $instructorId = $gene->instructor_id;
//                 $roomId = $gene->room_id;
//                 $studentGroupId = $this->studentGroupMap[$gene->section_id] ?? null;

//                 // نمر على كل فترة زمنية يشغلها هذا الجين (البلوك)
//                 foreach($gene->timeslot_ids as $timeslotId) {
//                     // فحص تعارض المدرس
//                     if (isset($resourceUsageMap[$timeslotId]['instructors'][$instructorId])) $totalPenalty += 1000;
//                     $resourceUsageMap[$timeslotId]['instructors'][$instructorId] = true;

//                     // فحص تعارض القاعة
//                     if (isset($resourceUsageMap[$timeslotId]['rooms'][$roomId])) $totalPenalty += 1000;
//                     $resourceUsageMap[$timeslotId]['rooms'][$roomId] = true;

//                     // فحص تعارض الطالب
//                     if ($studentGroupId && isset($resourceUsageMap[$timeslotId]['student_groups'][$studentGroupId])) $totalPenalty += 2000;
//                     if ($studentGroupId) $resourceUsageMap[$timeslotId]['student_groups'][$studentGroupId] = true;
//                 }

//                 // --- فحص القيود الخاصة بالجين نفسه (سعة، نوع، أهلية) ---
//                 if (!$gene->section || !$gene->room || !$gene->instructor) continue;

//                 if ($gene->section->student_count > $gene->room->room_size) $totalPenalty += 800;

//                 $roomType = optional($gene->room->roomType)->room_type_name ?? '';
//                 $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);
//                 if ($gene->section->activity_type == 'Practical' && !$isPracticalRoom) $totalPenalty += 600;
//                 if ($gene->section->activity_type == 'Theory' && $isPracticalRoom) $totalPenalty += 600;

//                 if (!optional($gene->instructor)->canTeach(optional($gene->section)->planSubject->subject)) $totalPenalty += 2000;
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
//     }

//     /**
//      * دالة التزاوج (Crossover)
//      */
//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');
//         $child1Genes = []; $child2Genes = [];

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Genes : $p2Genes;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Genes : $p1Genes;

//             $child1Genes[] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Genes->get($lectureBlock->unique_id);
//             $child2Genes[] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Genes->get($lectureBlock->unique_id);

//             $currentIndex++;
//         }
//         return [array_filter($child1Genes), array_filter($child2Genes)];
//     }

//     /**
//      * دالة الطفرة (Mutation)
//      */
//     private function performMutation(array $genes): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($genes)) return [];
//             $geneIndexToMutate = array_rand($genes);
//             $geneToMutate = $genes[$geneIndexToMutate];

//             $lectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $geneToMutate->lecture_unique_id);
//             if(!$lectureBlock) return $genes;

//             $geneToMutate->instructor_id = $this->getRandomInstructorForSection($lectureBlock->section)->id;
//             $geneToMutate->room_id = $this->getRandomRoomForSection($lectureBlock->section)->id;
//             $geneToMutate->timeslot_ids = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);

//             $genes[$geneIndexToMutate] = $geneToMutate;
//         }
//         return $genes;
//     }

//     /**
//      * دالة مساعدة لحفظ الكروموسوم الابن وجيناته في قاعدة البيانات
//      */
//     private function saveChildChromosome(array $genes, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create(['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber]);

//         $genesToInsert = [];
//         foreach($genes as $gene) {
//             $genesToInsert[] = [
//                 'chromosome_id' => $chromosome->chromosome_id,
//                 'lecture_unique_id' => $gene->lecture_unique_id,
//                 'section_id' => $gene->section_id,
//                 'instructor_id' => $gene->instructor_id,
//                 'room_id' => $gene->room_id,
//                 'timeslot_ids' => json_encode($gene->timeslot_ids),
//             ];
//         }

//         Gene::insert($genesToInsert);
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
//     private Collection $lectureBlocksToSchedule;
//     private array $consecutiveTimeslotsMap = [];
//     private array $studentGroupMap = [];

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
//      * دالة لتحميل كل البيانات وتجهيزها للخوارزمية (العقل المدبر)
//      */
//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with('planSubject.subject', 'planSubject.plan')
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])->get();
//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         $this->lectureBlocksToSchedule = collect();
//         foreach ($sections as $section) {
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             if ($section->activity_type == 'Practical') {
//                 $totalSlotsNeeded = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//                 if ($totalSlotsNeeded > 0) {
//                     $this->lectureBlocksToSchedule->push((object)[
//                         'section' => $section,
//                         'slots_needed' => $totalSlotsNeeded,
//                         'unique_id' => $section->id . '-block1'
//                     ]);
//                 }
//             } elseif ($section->activity_type == 'Theory') {
//                 $totalSlotsNeeded = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//                 $blockCounter = 1;
//                 while ($totalSlotsNeeded > 0) {
//                     $slotsForThisBlock = ($totalSlotsNeeded >= 2) ? 2 : 1;
//                     $this->lectureBlocksToSchedule->push((object)[
//                         'section' => $section,
//                         'slots_needed' => $slotsForThisBlock,
//                         'unique_id' => $section->id . '-block' . $blockCounter++
//                     ]);
//                     $totalSlotsNeeded -= $slotsForThisBlock;
//                 }
//             }
//         }
//         if ($this->lectureBlocksToSchedule->isEmpty()) throw new Exception("No lecture blocks to schedule were found after processing credit hours.");

//         $this->buildStudentGroupMap($sections);

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();
//         $this->buildConsecutiveTimeslotsMap();

//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
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
//      * دالة لإنشاء الجيل الأول من الحلول (بشكل ذكي وموجه)
//      */
//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating intelligent initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//         }
//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $resourceUsageMap = []; // خريطة استخدام موارد خاصة بهذا الكروموسوم
//             $genesToInsert = [];

//             foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//                 $instructor = $this->getRandomInstructorForSection($lectureBlock->section);
//                 $room = $this->getRandomRoomForSection($lectureBlock->section);
//                 $studentGroupId = $this->studentGroupMap[$lectureBlock->section->id] ?? null;

//                 $foundPlace = false;
//                 $bestFoundSlots = [];
//                 for ($attempt = 0; $attempt < 100; $attempt++) {
//                     $trialSlots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
//                     if (empty($trialSlots)) continue;

//                     $isConflict = $this->checkConflictsForSlots($trialSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap);

//                     if (!$isConflict) {
//                         $this->updateResourceMap($trialSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap);
//                         $bestFoundSlots = $trialSlots;
//                         $foundPlace = true;
//                         break;
//                     }

//                     if ($attempt == 0) {
//                         $bestFoundSlots = $trialSlots;
//                     }
//                 }

//                 if (!$foundPlace) {
//                      $this->updateResourceMap($bestFoundSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap);
//                 }

//                 foreach ($bestFoundSlots as $timeslotId) {
//                     $genesToInsert[] = ['chromosome_id' => $chromosome->chromosome_id, 'lecture_unique_id' => $lectureBlock->unique_id, 'section_id' => $lectureBlock->section->id, 'instructor_id' => $instructor->id, 'room_id' => $room->id, 'timeslot_id' => $timeslotId];
//                 }
//             }

//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     /**
//      * دالة مساعدة جديدة لفحص التعارضات في مجموعة من الفترات الزمنية
//      */
//     private function checkConflictsForSlots(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId, array $usageMap): bool
//     {
//         foreach ($slotIds as $slotId) {
//             if (isset($usageMap[$slotId])) {
//                 if (isset($usageMap[$slotId]['instructors'][$instructorId])) return true;
//                 if (isset($usageMap[$slotId]['rooms'][$roomId])) return true;
//                 if ($studentGroupId && isset($usageMap[$slotId]['student_groups'][$studentGroupId])) return true;
//             }
//         }
//         return false;
//     }

//     /**
//      * دالة مساعدة جديدة لتحديث خريطة استخدام الموارد
//      */
//     private function updateResourceMap(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId, array &$usageMap): void
//     {
//         foreach ($slotIds as $slotId) {
//             $usageMap[$slotId]['instructors'][$instructorId] = true;
//             $usageMap[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupId) {
//                 $usageMap[$slotId]['student_groups'][$studentGroupId] = true;
//             }
//         }
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

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$lectureBlock->unique_id] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Lectures->get($lectureBlock->unique_id);
//             $child2Lectures[$lectureBlock->unique_id] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Lectures->get($lectureBlock->unique_id);

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
//             $originalLectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $lectureKeyToMutate);
//             if(!$originalLectureBlock) return $lectures;

//             $newInstructor = $this->getRandomInstructorForSection($originalLectureBlock->section);
//             $newRoom = $this->getRandomRoomForSection($originalLectureBlock->section);
//             $newTimeslots = $this->findRandomConsecutiveTimeslots($originalLectureBlock->slots_needed);

//             $mutatedGenes = [];
//             foreach ($newTimeslots as $timeslotId) {
//                  $gene = new Gene([
//                      'lecture_unique_id' => $lectureKeyToMutate,
//                      'section_id' => $originalLectureBlock->section->id,
//                      'instructor_id' => $newInstructor->id,
//                      'room_id' => $newRoom->id,
//                      'timeslot_id' => $timeslotId
//                  ]);
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
// ***************************************************************************************

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
//     private Collection $lectureBlocksToSchedule;
//     private array $consecutiveTimeslotsMap = [];
//     private array $studentGroupMap = [];

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
//      * دالة لتحميل كل البيانات وتجهيزها للخوارزمية (العقل المدبر)
//      */
//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with('planSubject.subject', 'planSubject.plan')
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])->get();
//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         $this->lectureBlocksToSchedule = collect();
//         foreach ($sections as $section) {
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             if ($section->activity_type == 'Practical') {
//                 $totalSlotsNeeded = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//                 if ($totalSlotsNeeded > 0) {
//                     $this->lectureBlocksToSchedule->push((object)[
//                         'section' => $section,
//                         'slots_needed' => $totalSlotsNeeded,
//                         'unique_id' => $section->id . '-block1'
//                     ]);
//                 }
//             } elseif ($section->activity_type == 'Theory') {
//                 $totalSlotsNeeded = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//                 $blockCounter = 1;
//                 while ($totalSlotsNeeded > 0) {
//                     $slotsForThisBlock = ($totalSlotsNeeded >= 2) ? 2 : 1;
//                     $this->lectureBlocksToSchedule->push((object)[
//                         'section' => $section,
//                         'slots_needed' => $slotsForThisBlock,
//                         'unique_id' => $section->id . '-block' . $blockCounter++
//                     ]);
//                     $totalSlotsNeeded -= $slotsForThisBlock;
//                 }
//             }
//         }
//         if ($this->lectureBlocksToSchedule->isEmpty()) throw new Exception("No lecture blocks to schedule were found after processing credit hours.");

//         $this->buildStudentGroupMap($sections);

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();
//         $this->buildConsecutiveTimeslotsMap();

//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
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

//     // =========================================================================
//     // **(المنطق الجديد والذكي هنا)**
//     // =========================================================================
//     /**
//      * دالة لإنشاء الجيل الأول من الحلول (بشكل ذكي وموجه)
//      */
//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating intelligent initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//         }
//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $resourceUsageMap = []; // خريطة استخدام موارد خاصة بهذا الكروموسوم
//             $genesToInsert = [];

//             // **البحث المنظم** بدلاً من العشوائي
//             // نقوم بعمل نسخة عشوائية من الفترات الزمنية لنبدأ البحث من أماكن مختلفة لكل كروموسوم
//             $shuffledTimeslots = $this->timeslots->shuffle();

//             foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//                 $instructor = $this->getRandomInstructorForSection($lectureBlock->section);
//                 $room = $this->getRandomRoomForSection($lectureBlock->section);
//                 $studentGroupId = $this->studentGroupMap[$lectureBlock->section->id] ?? null;

//                 $foundSlots = [];

//                 // **حلقة البحث المنظم**
//                 foreach ($shuffledTimeslots as $startSlot) {
//                     // هل يمكن تشكيل بلوك كامل من هذه النقطة؟
//                     if (isset($this->consecutiveTimeslotsMap[$startSlot->id]) && count($this->consecutiveTimeslotsMap[$startSlot->id]) >= ($lectureBlock->slots_needed - 1)) {
//                         $trialSlots = array_merge([$startSlot->id], array_slice($this->consecutiveTimeslotsMap[$startSlot->id], 0, $lectureBlock->slots_needed - 1));

//                         // نفحص التعارضات
//                         if (!$this->checkConflictsForSlots($trialSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap)) {
//                             // وجدنا مكاناً ممتازاً!
//                             $foundSlots = $trialSlots;
//                             break; // نخرج من حلقة البحث عن وقت
//                         }
//                     }
//                 }

//                 // إذا فشل البحث المنظم (وهو أمر نادر)، نضعها في أول مكان عشوائي كحل أخير
//                 if (empty($foundSlots)) {
//                     $foundSlots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
//                 }

//                 // نحدّث خريطة الموارد ونجهز الجينات للحفظ
//                 $this->updateResourceMap($foundSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap);
//                 foreach ($foundSlots as $timeslotId) {
//                     $genesToInsert[] = ['chromosome_id' => $chromosome->chromosome_id, 'lecture_unique_id' => $lectureBlock->unique_id, 'section_id' => $lectureBlock->section->id, 'instructor_id' => $instructor->id, 'room_id' => $room->id, 'timeslot_id' => $timeslotId];
//                 }
//             }

//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     /**
//      * دالة مساعدة جديدة لفحص التعارضات في مجموعة من الفترات الزمنية
//      */
//     private function checkConflictsForSlots(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId, array $usageMap): bool
//     {
//         foreach ($slotIds as $slotId) {
//             if (isset($usageMap[$slotId])) {
//                 if (isset($usageMap[$slotId]['instructors'][$instructorId])) return true;
//                 if (isset($usageMap[$slotId]['rooms'][$roomId])) return true;
//                 if ($studentGroupId && isset($usageMap[$slotId]['student_groups'][$studentGroupId])) return true;
//             }
//         }
//         return false;
//     }

//     /**
//      * دالة مساعدة جديدة لتحديث خريطة استخدام الموارد
//      */
//     private function updateResourceMap(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId, array &$usageMap): void
//     {
//         foreach ($slotIds as $slotId) {
//             $usageMap[$slotId]['instructors'][$instructorId] = true;
//             $usageMap[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupId) {
//                 $usageMap[$slotId]['student_groups'][$studentGroupId] = true;
//             }
//         }
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

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$lectureBlock->unique_id] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Lectures->get($lectureBlock->unique_id);
//             $child2Lectures[$lectureBlock->unique_id] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Lectures->get($lectureBlock->unique_id);

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
//             $originalLectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $lectureKeyToMutate);
//             if(!$originalLectureBlock) return $lectures;

//             $newInstructor = $this->getRandomInstructorForSection($originalLectureBlock->section);
//             $newRoom = $this->getRandomRoomForSection($originalLectureBlock->section);
//             $newTimeslots = $this->findRandomConsecutiveTimeslots($originalLectureBlock->slots_needed);

//             $mutatedGenes = [];
//             foreach ($newTimeslots as $timeslotId) {
//                  $gene = new Gene([
//                      'lecture_unique_id' => $lectureKeyToMutate,
//                      'section_id' => $originalLectureBlock->section->id,
//                      'instructor_id' => $newInstructor->id,
//                      'room_id' => $newRoom->id,
//                      'timeslot_id' => $timeslotId
//                  ]);
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

// ************************************************************************
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
//     private Collection $lectureBlocksToSchedule;
//     private array $consecutiveTimeslotsMap = [];
//     private array $studentGroupMap = [];


//     /**
//      * دالة البناء (Constructor)
//      */
//     // public function __construct(array $settings, Population $populationRun)
//     // {
//     //     $this->settings = $settings;
//     //     $this->populationRun = $populationRun;
//     //     Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     // }
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     /**
//      * الدالة الرئيسية لتشغيل الخوارزمية بالكامل
//      */
//     // public function run()
//     // {
//     //     try {
//     //         $this->populationRun->update(['status' => 'running', 'start_time' => now()]);

//     //         $this->loadAndPrepareData();

//     //         $currentGenerationNumber = 1;
//     //         $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
//     //         $this->evaluateFitness($currentPopulation);
//     //         Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//     //         $maxGenerations = $this->settings['max_generations'];
//     //         while ($currentGenerationNumber < $maxGenerations) {
//     //             $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
//     //             if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//     //                 Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//     //                 break;
//     //             }

//     //             $parents = $this->selectParents($currentPopulation);
//     //             $currentGenerationNumber++;
//     //             $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//     //             $this->evaluateFitness($currentPopulation);
//     //             Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//     //         }

//     //         $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
//     //         $this->populationRun->update([
//     //             'status' => 'completed',
//     //             'end_time' => now(),
//     //             'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
//     //         ]);
//     //         Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
//     //     } catch (Exception $e) {
//     //         Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//     //         $this->populationRun->update(['status' => 'failed']);
//     //         throw $e;
//     //     }
//     // }
//         public function run()
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
//      * دالة لتحميل كل البيانات وتجهيزها للخوارزمية (العقل المدبر)
//      */
//     // private function loadAndPrepareData()
//     // {
//     //     Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//     //     $sections = Section::with('planSubject.subject', 'planSubject.plan')
//     //         ->where('academic_year', $this->settings['academic_year'])
//     //         ->where('semester', $this->settings['semester'])->get();
//     //     if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//     //     $this->lectureBlocksToSchedule = collect();
//     //     foreach ($sections as $section) {
//     //         $subject = optional($section->planSubject)->subject;
//     //         if (!$subject) continue;

//     //         if ($section->activity_type == 'Practical') {
//     //             $totalSlotsNeeded = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//     //             if ($totalSlotsNeeded > 0) {
//     //                 $this->lectureBlocksToSchedule->push((object)[
//     //                     'section' => $section,
//     //                     'slots_needed' => $totalSlotsNeeded,
//     //                     'unique_id' => $section->id . '-block1'
//     //                 ]);
//     //             }
//     //         } elseif ($section->activity_type == 'Theory') {
//     //             $totalSlotsNeeded = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//     //             $blockCounter = 1;
//     //             while ($totalSlotsNeeded > 0) {
//     //                 $slotsForThisBlock = ($totalSlotsNeeded >= 2) ? 2 : 1;
//     //                 $this->lectureBlocksToSchedule->push((object)[
//     //                     'section' => $section,
//     //                     'slots_needed' => $slotsForThisBlock,
//     //                     'unique_id' => $section->id . '-block' . $blockCounter++
//     //                 ]);
//     //                 $totalSlotsNeeded -= $slotsForThisBlock;
//     //             }
//     //         }
//     //     }
//     //     if ($this->lectureBlocksToSchedule->isEmpty()) throw new Exception("No lecture blocks to schedule were found after processing credit hours.");

//     //     $this->buildStudentGroupMap($sections);

//     //     $this->instructors = Instructor::with('subjects')->get();
//     //     $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//     //     $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//     //     $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();
//     //     $this->buildConsecutiveTimeslotsMap();

//     //     Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//     // }

//         private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with('planSubject.subject', 'planSubject.plan')
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])->get();
//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         $this->lectureBlocksToSchedule = collect();
//         foreach ($sections as $section) {
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             if ($section->activity_type == 'Practical') {
//                 $totalSlotsNeeded = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//                 if ($totalSlotsNeeded > 0) {
//                     $this->lectureBlocksToSchedule->push((object)[
//                         'section' => $section,
//                         'slots_needed' => $totalSlotsNeeded,
//                         'unique_id' => $section->id . '-block1'
//                     ]);
//                 }
//             } elseif ($section->activity_type == 'Theory') {
//                 $totalSlotsNeeded = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//                 $blockCounter = 1;
//                 while ($totalSlotsNeeded > 0) {
//                     $slotsForThisBlock = ($totalSlotsNeeded >= 2) ? 2 : 1;
//                     $this->lectureBlocksToSchedule->push((object)[
//                         'section' => $section,
//                         'slots_needed' => $slotsForThisBlock,
//                         'unique_id' => $section->id . '-block' . $blockCounter++
//                     ]);
//                     $totalSlotsNeeded -= $slotsForThisBlock;
//                 }
//             }
//         }
//         if ($this->lectureBlocksToSchedule->isEmpty()) throw new Exception("No lecture blocks to schedule were found after processing credit hours.");

//         $this->buildStudentGroupMap($sections);

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();
//         $this->buildConsecutiveTimeslotsMap();

//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//     }

//     /**
//      * دالة ذكية لبناء خريطة تربط كل شعبة بمجموعة الطلاب الخاصة بها
//      */
//     // private function buildStudentGroupMap(Collection $sections)
//     // {
//     //     $this->studentGroupMap = [];
//     //     $sectionsByContext = $sections->groupBy(function ($section) {
//     //         $ps = $section->planSubject;
//     //         return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//     //     });

//     //     foreach ($sectionsByContext as $sectionsInContext) {
//     //         $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//     //         $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//     //         for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//     //             $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//     //             foreach ($theorySections as $theorySection) {
//     //                 $this->studentGroupMap[$theorySection->id] = $groupIndex;
//     //             }

//     //             $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
//     //             foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
//     //                 $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//     //                 if ($sortedSections->has($groupIndex - 1)) {
//     //                     $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//     //                     $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
//     //                 }
//     //             }
//     //         }
//     //     }
//     // }
//         private function buildStudentGroupMap(Collection $sections)
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
//     // private function buildConsecutiveTimeslotsMap()
//     // {
//     //     $timeslotsByDay = $this->timeslots->groupBy('day');
//     //     $this->consecutiveTimeslotsMap = [];
//     //     foreach ($timeslotsByDay as $dayTimeslots) {
//     //         $dayTimeslotsValues = $dayTimeslots->values();
//     //         for ($i = 0; $i < $dayTimeslotsValues->count(); $i++) {
//     //             $currentSlot = $dayTimeslotsValues[$i];
//     //             $this->consecutiveTimeslotsMap[$currentSlot->id] = [];
//     //             for ($j = $i + 1; $j < $dayTimeslotsValues->count(); $j++) {
//     //                 $nextSlot = $dayTimeslotsValues[$j];
//     //                 if ($nextSlot->start_time == $currentSlot->end_time) {
//     //                     $this->consecutiveTimeslotsMap[$currentSlot->id][] = $nextSlot->id;
//     //                     $currentSlot = $nextSlot;
//     //                 } else {
//     //                     break;
//     //                 }
//     //             }
//     //         }
//     //     }
//     // }
//         private function buildConsecutiveTimeslotsMap()
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

//     // =========================================================================
//     // **(المنطق الجديد والذكي هنا)**
//     // =========================================================================
//     /**
//      * دالة لإنشاء الجيل الأول من الحلول (بشكل ذكي وموجه)
//      */
//     // private function createInitialPopulation(int $generationNumber): Collection
//     // {
//     //     Log::info("Creating intelligent initial population (Generation #{$generationNumber})");

//     //     $chromosomesToInsert = [];
//     //     for ($i = 0; $i < $this->settings['population_size']; $i++) {
//     //         $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//     //     }
//     //     Chromosome::insert($chromosomesToInsert);
//     //     $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

//     //     foreach ($createdChromosomes as $chromosome) {
//     //         $resourceUsageMap = []; // خريطة استخدام موارد خاصة بهذا الكروموسوم
//     //         $genesToInsert = [];

//     //         // **البحث المنظم** بدلاً من العشوائي
//     //         // نقوم بعمل نسخة عشوائية من الفترات الزمنية لنبدأ البحث من أماكن مختلفة لكل كروموسوم
//     //         $shuffledTimeslots = $this->timeslots->shuffle();

//     //         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//     //             $instructor = $this->getRandomInstructorForSection($lectureBlock->section);
//     //             $room = $this->getRandomRoomForSection($lectureBlock->section);
//     //             $studentGroupId = $this->studentGroupMap[$lectureBlock->section->id] ?? null;

//     //             $foundSlots = [];

//     //             // **حلقة البحث المنظم**
//     //             foreach ($shuffledTimeslots as $startSlot) {
//     //                 // هل يمكن تشكيل بلوك كامل من هذه النقطة؟
//     //                 if (isset($this->consecutiveTimeslotsMap[$startSlot->id]) && count($this->consecutiveTimeslotsMap[$startSlot->id]) >= ($lectureBlock->slots_needed - 1)) {
//     //                     $trialSlots = array_merge([$startSlot->id], array_slice($this->consecutiveTimeslotsMap[$startSlot->id], 0, $lectureBlock->slots_needed - 1));

//     //                     // نفحص التعارضات
//     //                     if (!$this->checkConflictsForSlots($trialSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap)) {
//     //                         // وجدنا مكاناً ممتازاً!
//     //                         $foundSlots = $trialSlots;
//     //                         break; // نخرج من حلقة البحث عن وقت
//     //                     }
//     //                 }
//     //             }

//     //             // إذا فشل البحث المنظم (وهو أمر نادر)، نضعها في أول مكان عشوائي كحل أخير
//     //             if (empty($foundSlots)) {
//     //                 $foundSlots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
//     //             }

//     //             // نحدّث خريطة الموارد ونجهز الجينات للحفظ
//     //             $this->updateResourceMap($foundSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap);
//     //             foreach ($foundSlots as $timeslotId) {
//     //                 $genesToInsert[] = ['chromosome_id' => $chromosome->chromosome_id, 'lecture_unique_id' => $lectureBlock->unique_id, 'section_id' => $lectureBlock->section->id, 'instructor_id' => $instructor->id, 'room_id' => $room->id, 'timeslot_id' => $timeslotId];
//     //             }
//     //         }

//     //         foreach (array_chunk($genesToInsert, 500) as $chunk) {
//     //             Gene::insert($chunk);
//     //         }
//     //     }

//     //     return $createdChromosomes;
//     // }

//         private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating highly intelligent initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//         }
//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

//         // **(تحسين)** ترتيب المحاضرات من الأصعب (الأطول) إلى الأسهل (الأقصر)
//         $sortedLectureBlocks = $this->lectureBlocksToSchedule->sortByDesc('slots_needed');

//         foreach ($createdChromosomes as $chromosome) {
//             $resourceUsageMap = []; // خريطة استخدام موارد خاصة بهذا الكروموسوم
//             $genesToInsert = [];

//             foreach ($sortedLectureBlocks as $lectureBlock) {
//                 $instructor = $this->getRandomInstructorForSection($lectureBlock->section);
//                 $room = $this->getRandomRoomForSection($lectureBlock->section);
//                 $studentGroupId = $this->studentGroupMap[$lectureBlock->section->id] ?? null;

//                 $foundSlots = [];

//                 // **المرحلة الأولى: البحث عن مكان مثالي (لا تعارضات)**
//                 foreach ($this->timeslots as $startSlot) {
//                     $trialSlots = $this->getConsecutiveSlots($startSlot->id, $lectureBlock->slots_needed);
//                     if (empty($trialSlots)) continue;
//                     if (!$this->checkConflictsForSlots($trialSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap, false)) {
//                         $foundSlots = $trialSlots;
//                         break;
//                     }
//                 }

//                 // **المرحلة الثانية: إذا فشلنا، نبحث عن مكان بأقل تعارض (الأولوية للطالب)**
//                 if (empty($foundSlots)) {
//                     foreach ($this->timeslots as $startSlot) {
//                         $trialSlots = $this->getConsecutiveSlots($startSlot->id, $lectureBlock->slots_needed);
//                         if (empty($trialSlots)) continue;
//                         // نفحص تعارض الطالب فقط
//                         if (!$this->checkConflictsForSlots($trialSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap, true)) {
//                              $foundSlots = $trialSlots;
//                              break;
//                         }
//                     }
//                 }

//                 // **المرحلة الثالثة: إذا فشلنا مرة أخرى، نضعها في أول مكان متاح**
//                 if (empty($foundSlots)) {
//                     $foundSlots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
//                 }

//                 // نحدّث خريطة الموارد ونجهز الجينات للحفظ
//                 $this->updateResourceMap($foundSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap);
//                 foreach ($foundSlots as $timeslotId) {
//                     $genesToInsert[] = ['chromosome_id' => $chromosome->chromosome_id, 'lecture_unique_id' => $lectureBlock->unique_id, 'section_id' => $lectureBlock->section->id, 'instructor_id' => $instructor->id, 'room_id' => $room->id, 'timeslot_id' => $timeslotId];
//                 }
//             }

//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     /**
//      * دالة مساعدة جديدة لفحص التعارضات في مجموعة من الفترات الزمنية
//      */
//     // private function checkConflictsForSlots(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId, array $usageMap): bool
//     // {
//     //     foreach ($slotIds as $slotId) {
//     //         if (isset($usageMap[$slotId])) {
//     //             if (isset($usageMap[$slotId]['instructors'][$instructorId])) return true;
//     //             if (isset($usageMap[$slotId]['rooms'][$roomId])) return true;
//     //             if ($studentGroupId && isset($usageMap[$slotId]['student_groups'][$studentGroupId])) return true;
//     //         }
//     //     }
//     //     return false;
//     // }

//     private function checkConflictsForSlots(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId, array $usageMap, bool $studentOnly = false): bool
//     {
//         foreach ($slotIds as $slotId) {
//             if (isset($usageMap[$slotId])) {
//                 if ($studentOnly) {
//                     if ($studentGroupId && isset($usageMap[$slotId]['student_groups'][$studentGroupId])) return true;
//                 } else {
//                     if (isset($usageMap[$slotId]['instructors'][$instructorId])) return true;
//                     if (isset($usageMap[$slotId]['rooms'][$roomId])) return true;
//                     if ($studentGroupId && isset($usageMap[$slotId]['student_groups'][$studentGroupId])) return true;
//                 }
//             }
//         }
//         return false;
//     }

//     /**
//      * دالة مساعدة جديدة لتحديث خريطة استخدام الموارد
//      */
//     // private function updateResourceMap(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId, array &$usageMap): void
//     // {
//     //     foreach ($slotIds as $slotId) {
//     //         $usageMap[$slotId]['instructors'][$instructorId] = true;
//     //         $usageMap[$slotId]['rooms'][$roomId] = true;
//     //         if ($studentGroupId) {
//     //             $usageMap[$slotId]['student_groups'][$studentGroupId] = true;
//     //         }
//     //     }
//     // }
//         private function updateResourceMap(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId, array &$usageMap): void
//     {
//         foreach ($slotIds as $slotId) {
//             $usageMap[$slotId]['instructors'][$instructorId] = true;
//             $usageMap[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupId) {
//                 $usageMap[$slotId]['student_groups'][$studentGroupId] = true;
//             }
//         }
//     }

//     /**
//      * دالة مساعدة جديدة ومحسنة لإيجاد الفترات المتتالية (بدون عشوائية)
//      */
//     private function getConsecutiveSlots(int $startSlotId, int $slotsNeeded): array
//     {
//         if ($slotsNeeded <= 0) return [];
//         if ($slotsNeeded == 1) return [$startSlotId];

//         if (isset($this->consecutiveTimeslotsMap[$startSlotId]) && count($this->consecutiveTimeslotsMap[$startSlotId]) >= ($slotsNeeded - 1)) {
//             return array_merge([$startSlotId], array_slice($this->consecutiveTimeslotsMap[$startSlotId], 0, $slotsNeeded - 1));
//         }
//         return []; // لا يوجد عدد كافٍ من الفترات المتتالية
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

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$lectureBlock->unique_id] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Lectures->get($lectureBlock->unique_id);
//             $child2Lectures[$lectureBlock->unique_id] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Lectures->get($lectureBlock->unique_id);

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
//             $originalLectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $lectureKeyToMutate);
//             if (!$originalLectureBlock) return $lectures;

//             $newInstructor = $this->getRandomInstructorForSection($originalLectureBlock->section);
//             $newRoom = $this->getRandomRoomForSection($originalLectureBlock->section);
//             $newTimeslots = $this->findRandomConsecutiveTimeslots($originalLectureBlock->slots_needed);

//             $mutatedGenes = [];
//             foreach ($newTimeslots as $timeslotId) {
//                 $gene = new Gene([
//                     'lecture_unique_id' => $lectureKeyToMutate,
//                     'section_id' => $originalLectureBlock->section->id,
//                     'instructor_id' => $newInstructor->id,
//                     'room_id' => $newRoom->id,
//                     'timeslot_id' => $timeslotId
//                 ]);
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
//     private Collection $lectureBlocksToSchedule;
//     private array $consecutiveTimeslotsMap = [];
//     private array $studentGroupMap = [];

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
//     // public function run()
//     // {
//     //     try {
//     //         $this->populationRun->update(['status' => 'running', 'start_time' => now()]);

//     //         $this->loadAndPrepareData();

//     //         $currentGenerationNumber = 1;
//     //         $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
//     //         $this->evaluateFitness($currentPopulation);
//     //         Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//     //         $maxGenerations = $this->settings['max_generations'];
//     //         while ($currentGenerationNumber < $maxGenerations) {
//     //             $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
//     //             if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//     //                 Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//     //                 break;
//     //             }

//     //             $parents = $this->selectParents($currentPopulation);
//     //             $currentGenerationNumber++;
//     //             $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//     //             $this->evaluateFitness($currentPopulation);
//     //             Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//     //         }

//     //         $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
//     //         $this->populationRun->update([
//     //             'status' => 'completed', 'end_time' => now(),
//     //             'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
//     //         ]);
//     //         Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
//     //     } catch (Exception $e) {
//     //         Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//     //         $this->populationRun->update(['status' => 'failed']);
//     //         throw $e;
//     //     }
//     // }
//     // public function run()
//     // {
//     //     try {
//     //         $this->populationRun->update(['status' => 'running', 'start_time' => now()]);

//     //         $this->loadAndPrepareData();

//     //         $currentGenerationNumber = 1;
//     //         $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
//     //         $this->evaluateFitness($currentPopulation);
//     //         Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//     //         $maxGenerations = $this->settings['max_generations'];
//     //         while ($currentGenerationNumber < $maxGenerations) {
//     //             $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
//     //             if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//     //                 Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//     //                 break;
//     //             }

//     //             $parents = $this->selectParents($currentPopulation);
//     //             $currentGenerationNumber++;
//     //             $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//     //             $this->evaluateFitness($currentPopulation);
//     //             Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//     //         }

//     //         $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
//     //         $this->populationRun->update([
//     //             'status' => 'completed',
//     //             'end_time' => now(),
//     //             'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
//     //         ]);
//     //         Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
//     //     } catch (Exception $e) {
//     //         Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//     //         $this->populationRun->update(['status' => 'failed']);
//     //         throw $e;
//     //     }
//     // }
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
//             $this->populationRun->update(['status' => 'completed', 'end_time' => now(), 'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null]);
//             Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
//         } catch (Exception $e) {
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }


//     /**
//      * دالة لتحميل كل البيانات وتجهيزها للخوارزمية (العقل المدبر)
//      */
//     // private function loadAndPrepareData()
//     // {
//     //     Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//     //     $sections = Section::with('planSubject.subject', 'planSubject.plan')
//     //         ->where('academic_year', $this->settings['academic_year'])
//     //         ->where('semester', $this->settings['semester'])->get();
//     //     if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//     //     // نحمل المدرسين مرة واحدة في البداية لتحسين الأداء
//     //     $this->instructors = Instructor::with('subjects')->get();

//     //     $this->lectureBlocksToSchedule = collect();
//     //     foreach ($sections as $section) {
//     //         $subject = optional($section->planSubject)->subject;
//     //         if (!$subject) continue;

//     //         // **(جديد)** نختار مدرساً واحداً وثابتاً لكل شعبة من البداية
//     //         $assignedInstructor = $this->getRandomInstructorForSection($section);

//     //         if ($section->activity_type == 'Practical') {
//     //             $totalSlotsNeeded = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//     //             if ($totalSlotsNeeded > 0) {
//     //                 // **(تصحيح)** العملي هو بلوك واحد فقط
//     //                 $this->lectureBlocksToSchedule->push((object)[
//     //                     'section' => $section,
//     //                     'slots_needed' => $totalSlotsNeeded,
//     //                     'unique_id' => $section->id . '-block1',
//     //                     'instructor' => $assignedInstructor // نثبت المدرس
//     //                 ]);
//     //             }
//     //         } elseif ($section->activity_type == 'Theory') {
//     //             $totalSlotsNeeded = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//     //             $blockCounter = 1;
//     //             // **(تصحيح)** التقسيم الذكي للبلوكات النظرية
//     //             while ($totalSlotsNeeded > 0) {
//     //                 $slotsForThisBlock = ($totalSlotsNeeded >= 2) ? 2 : 1;
//     //                 $this->lectureBlocksToSchedule->push((object)[
//     //                     'section' => $section,
//     //                     'slots_needed' => $slotsForThisBlock,
//     //                     'unique_id' => $section->id . '-block' . $blockCounter++,
//     //                     'instructor' => $assignedInstructor // نثبت نفس المدرس لكل أجزاء المحاضرة
//     //                 ]);
//     //                 $totalSlotsNeeded -= $slotsForThisBlock;
//     //             }
//     //         }
//     //     }
//     //     if ($this->lectureBlocksToSchedule->isEmpty()) throw new Exception("No lecture blocks to schedule were found after processing credit hours.");

//     //     $this->buildStudentGroupMap($sections);

//     //     $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//     //     $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//     //     $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();
//     //     $this->buildConsecutiveTimeslotsMap();

//     //     Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//     // }
//     // private function loadAndPrepareData()
//     // {
//     //     Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//     //     $sections = Section::with('planSubject.subject', 'planSubject.plan')
//     //         ->where('academic_year', $this->settings['academic_year'])
//     //         ->where('semester', $this->settings['semester'])->get();
//     //     if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//     //     $this->lectureBlocksToSchedule = collect();
//     //     foreach ($sections as $section) {
//     //         $subject = optional($section->planSubject)->subject;
//     //         if (!$subject) continue;

//     //         // **(المنطق المصحح)**: التفريق بين العملي والنظري
//     //         if ($section->activity_type == 'Practical') {
//     //             $totalSlotsNeeded = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//     //             if ($totalSlotsNeeded > 0) {
//     //                 // العملي هو دائماً بلوك واحد متصل
//     //                 $this->lectureBlocksToSchedule->push((object)[
//     //                     'section' => $section,
//     //                     'slots_needed' => $totalSlotsNeeded,
//     //                     'unique_id' => $section->id . '-block-1'
//     //                 ]);
//     //             }
//     //         } elseif ($section->activity_type == 'Theory') {
//     //             $totalSlotsNeeded = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//     //             $blockCounter = 1;
//     //             // التقسيم الذكي للبلوكات النظرية
//     //             while ($totalSlotsNeeded > 0) {
//     //                 $slotsForThisBlock = ($totalSlotsNeeded >= 2) ? 2 : 1;
//     //                 $this->lectureBlocksToSchedule->push((object)[
//     //                     'section' => $section,
//     //                     'slots_needed' => $slotsForThisBlock,
//     //                     'unique_id' => $section->id . '-block-theory-' . $blockCounter++
//     //                 ]);
//     //                 $totalSlotsNeeded -= $slotsForThisBlock;
//     //             }
//     //         }
//     //     }
//     //     if ($this->lectureBlocksToSchedule->isEmpty()) throw new Exception("No lecture blocks to schedule were found after processing credit hours.");

//     //     $this->buildStudentGroupMap($sections);

//     //     $this->instructors = Instructor::with('subjects')->get();
//     //     $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//     //     $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//     //     $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();
//     //     $this->buildConsecutiveTimeslotsMap();

//     //     Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//     // }
//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with('planSubject.subject', 'planSubject.plan')
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])->get();
//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         $this->lectureBlocksToSchedule = collect();
//         foreach ($sections as $section) {
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             // **(المنطق المصحح)**: فصل تام بين العملي والنظري
//             if ($section->activity_type == 'Practical') {
//                 $totalSlotsNeeded = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//                 if ($totalSlotsNeeded > 0) {
//                     $this->lectureBlocksToSchedule->push((object)[
//                         'section' => $section,
//                         'slots_needed' => $totalSlotsNeeded,
//                         'unique_id' => $section->id . '-block-practical'
//                     ]);
//                 }
//             }
//             if ($section->activity_type == 'Theory') { // استخدام if وليس elseif
//                 $totalSlotsNeeded = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//                 $blockCounter = 1;
//                 while ($totalSlotsNeeded > 0) {
//                     $slotsForThisBlock = ($totalSlotsNeeded >= 2) ? 2 : 1;
//                     $this->lectureBlocksToSchedule->push((object)[
//                         'section' => $section,
//                         'slots_needed' => $slotsForThisBlock,
//                         'unique_id' => $section->id . '-block-theory-' . $blockCounter++
//                     ]);
//                     $totalSlotsNeeded -= $slotsForThisBlock;
//                 }
//             }
//         }
//         if ($this->lectureBlocksToSchedule->isEmpty()) throw new Exception("No lecture blocks to schedule were found after processing credit hours.");

//         $this->buildStudentGroupMap($sections);

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();
//         $this->buildConsecutiveTimeslotsMap();

//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//     }


//     /**
//      * دالة ذكية لبناء خريطة تربط كل شعبة بمجموعة الطلاب الخاصة بها
//      */
//     // private function buildStudentGroupMap(Collection $sections)
//     // {
//     //     $this->studentGroupMap = [];
//     //     $sectionsByContext = $sections->groupBy(function ($section) {
//     //         $ps = $section->planSubject;
//     //         return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//     //     });

//     //     foreach ($sectionsByContext as $sectionsInContext) {
//     //         $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//     //         $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//     //         for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//     //             $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//     //             foreach ($theorySections as $theorySection) {
//     //                 $this->studentGroupMap[$theorySection->id] = $groupIndex;
//     //             }

//     //             $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
//     //             foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
//     //                 $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//     //                 if ($sortedSections->has($groupIndex - 1)) {
//     //                     $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//     //                     $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
//     //                 }
//     //             }
//     //         }
//     //     }
//     // }
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
//     // private function buildConsecutiveTimeslotsMap()
//     // {
//     //     $timeslotsByDay = $this->timeslots->groupBy('day');
//     //     $this->consecutiveTimeslotsMap = [];
//     //     foreach ($timeslotsByDay as $dayTimeslots) {
//     //         $dayTimeslotsValues = $dayTimeslots->values();
//     //         for ($i = 0; $i < $dayTimeslotsValues->count(); $i++) {
//     //             $currentSlot = $dayTimeslotsValues[$i];
//     //             $this->consecutiveTimeslotsMap[$currentSlot->id] = [];
//     //             for ($j = $i + 1; $j < $dayTimeslotsValues->count(); $j++) {
//     //                 $nextSlot = $dayTimeslotsValues[$j];
//     //                 if ($nextSlot->start_time == $currentSlot->end_time) {
//     //                     $this->consecutiveTimeslotsMap[$currentSlot->id][] = $nextSlot->id;
//     //                     $currentSlot = $nextSlot;
//     //                 } else {
//     //                     break;
//     //                 }
//     //             }
//     //         }
//     //     }
//     // }
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
//      * دالة لإنشاء الجيل الأول من الحلول (بشكل ذكي وموجه جداً)
//      */
//     // private function createInitialPopulation(int $generationNumber): Collection
//     // {
//     //     Log::info("Creating highly intelligent initial population (Generation #{$generationNumber})");

//     //     $chromosomesToInsert = [];
//     //     for ($i = 0; $i < $this->settings['population_size']; $i++) {
//     //         $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//     //     }
//     //     Chromosome::insert($chromosomesToInsert);
//     //     $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

//     //     $sortedLectureBlocks = $this->lectureBlocksToSchedule->sortByDesc('slots_needed');

//     //     foreach ($createdChromosomes as $chromosome) {
//     //         $resourceUsageMap = [];
//     //         $genesToInsert = [];

//     //         foreach ($sortedLectureBlocks as $lectureBlock) {
//     //             $instructor = $lectureBlock->instructor;
//     //             $room = $this->getRandomRoomForSection($lectureBlock->section);
//     //             $studentGroupId = $this->studentGroupMap[$lectureBlock->section->id] ?? null;

//     //             $foundSlots = $this->findBestAvailableSlots($lectureBlock, $instructor, $room, $studentGroupId, $resourceUsageMap);

//     //             $this->updateResourceMap($foundSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap);
//     //             foreach ($foundSlots as $timeslotId) {
//     //                 $genesToInsert[] = ['chromosome_id' => $chromosome->chromosome_id, 'lecture_unique_id' => $lectureBlock->unique_id, 'section_id' => $lectureBlock->section->id, 'instructor_id' => $instructor->id, 'room_id' => $room->id, 'timeslot_id' => $timeslotId];
//     //             }
//     //         }

//     //         foreach (array_chunk($genesToInsert, 500) as $chunk) {
//     //             Gene::insert($chunk);
//     //         }
//     //     }

//     //     return $createdChromosomes;
//     // }
//     // private function createInitialPopulation(int $generationNumber): Collection
//     // {
//     //     Log::info("Creating highly intelligent initial population (Generation #{$generationNumber})");

//     //     $chromosomesToInsert = [];
//     //     for ($i = 0; $i < $this->settings['population_size']; $i++) {
//     //         $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//     //     }
//     //     Chromosome::insert($chromosomesToInsert);
//     //     $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

//     //     $sortedLectureBlocks = $this->lectureBlocksToSchedule->sortByDesc('slots_needed');

//     //     foreach ($createdChromosomes as $chromosome) {
//     //         $resourceUsageMap = [];
//     //         $genesToInsert = [];

//     //         foreach ($sortedLectureBlocks as $lectureBlock) {
//     //             $instructor = $this->getRandomInstructorForSection($lectureBlock->section);
//     //             $room = $this->getRandomRoomForSection($lectureBlock->section);
//     //             $studentGroupId = $this->studentGroupMap[$lectureBlock->section->id] ?? null;

//     //             $foundSlots = $this->findBestAvailableSlots($lectureBlock, $instructor, $room, $studentGroupId, $resourceUsageMap);

//     //             $this->updateResourceMap($foundSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap);
//     //             foreach ($foundSlots as $timeslotId) {
//     //                 $genesToInsert[] = ['chromosome_id' => $chromosome->chromosome_id, 'lecture_unique_id' => $lectureBlock->unique_id, 'section_id' => $lectureBlock->section->id, 'instructor_id' => $instructor->id, 'room_id' => $room->id, 'timeslot_id' => $timeslotId];
//     //             }
//     //         }

//     //         foreach (array_chunk($genesToInsert, 500) as $chunk) {
//     //             Gene::insert($chunk);
//     //         }
//     //     }

//     //     return $createdChromosomes;
//     // }
//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating highly intelligent initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//         }
//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

//         $sortedLectureBlocks = $this->lectureBlocksToSchedule->sortByDesc('slots_needed');

//         foreach ($createdChromosomes as $chromosome) {
//             $resourceUsageMap = [];
//             $genesToInsert = [];

//             foreach ($sortedLectureBlocks as $lectureBlock) {
//                 $instructor = $this->getRandomInstructorForSection($lectureBlock->section);
//                 $room = $this->getRandomRoomForSection($lectureBlock->section);
//                 $studentGroupId = $this->studentGroupMap[$lectureBlock->section->id] ?? null;

//                 $foundSlots = $this->findBestAvailableSlots($lectureBlock, $instructor, $room, $studentGroupId, $resourceUsageMap);

//                 $this->updateResourceMap($foundSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap);
//                 foreach ($foundSlots as $timeslotId) {
//                     $genesToInsert[] = ['chromosome_id' => $chromosome->chromosome_id, 'lecture_unique_id' => $lectureBlock->unique_id, 'section_id' => $lectureBlock->section->id, 'instructor_id' => $instructor->id, 'room_id' => $room->id, 'timeslot_id' => $timeslotId];
//                 }
//             }

//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     /**
//      * دالة البحث الذكي عن أفضل مكان متاح
//      */
//     // private function findBestAvailableSlots($lectureBlock, $instructor, $room, $studentGroupId, array &$resourceUsageMap): array
//     // {
//     //     // المرحلة الأولى: البحث عن مكان مثالي (لا تعارضات)
//     //     foreach ($this->timeslots as $startSlot) {
//     //         $trialSlots = $this->getConsecutiveSlots($startSlot->id, $lectureBlock->slots_needed);
//     //         if (empty($trialSlots)) continue;
//     //         if (!$this->checkConflictsForSlots($trialSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap, false)) {
//     //             return $trialSlots;
//     //         }
//     //     }

//     //     // المرحلة الثانية: البحث عن مكان بأقل تعارض (الأولوية للطالب)
//     //     foreach ($this->timeslots as $startSlot) {
//     //         $trialSlots = $this->getConsecutiveSlots($startSlot->id, $lectureBlock->slots_needed);
//     //         if (empty($trialSlots)) continue;
//     //         if (!$this->checkConflictsForSlots($trialSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap, true)) {
//     //             return $trialSlots;
//     //         }
//     //     }

//     //     // المرحلة الثالثة: إذا فشلنا، نضعها في أول مكان عشوائي كحل أخير
//     //     return $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
//     // }
//     private function findBestAvailableSlots($lectureBlock, $instructor, $room, $studentGroupId, $resourceUsageMap): array
//     {
//         // المرحلة الأولى: البحث عن مكان مثالي (لا تعارضات)
//         foreach ($this->timeslots as $startSlot) {
//             $trialSlots = $this->getConsecutiveSlots($startSlot->id, $lectureBlock->slots_needed);
//             if (!empty($trialSlots) && !$this->checkConflictsForSlots($trialSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap, false)) {
//                 return $trialSlots;
//             }
//         }

//         // المرحلة الثانية: إذا فشلنا، نبحث عن مكان بأقل تعارض (الأولوية للطالب)
//         foreach ($this->timeslots as $startSlot) {
//             $trialSlots = $this->getConsecutiveSlots($startSlot->id, $lectureBlock->slots_needed);
//             if (!empty($trialSlots) && !$this->checkConflictsForSlots($trialSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap, true)) {
//                 return $trialSlots;
//             }
//         }

//         // المرحلة الثالثة: إذا فشلنا مرة أخرى، نضعها في أول مكان عشوائي متاح
//         return $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
//     }


//     /**
//      * دالة مساعدة لفحص التعارضات في مجموعة من الفترات الزمنية
//      */
//     // private function checkConflictsForSlots(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId, array $usageMap, bool $studentOnly = false): bool
//     // {
//     //     foreach ($slotIds as $slotId) {
//     //         if (isset($usageMap[$slotId])) {
//     //             if ($studentOnly) {
//     //                 if ($studentGroupId && isset($usageMap[$slotId]['student_groups'][$studentGroupId])) return true;
//     //             } else {
//     //                 if (isset($usageMap[$slotId]['instructors'][$instructorId])) return true;
//     //                 if (isset($usageMap[$slotId]['rooms'][$roomId])) return true;
//     //                 if ($studentGroupId && isset($usageMap[$slotId]['student_groups'][$studentGroupId])) return true;
//     //             }
//     //         }
//     //     }
//     //     return false;
//     // }
//     private function checkConflictsForSlots(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId, array $usageMap, bool $studentOnly = false): bool
//     {
//         foreach ($slotIds as $slotId) {
//             if (isset($usageMap[$slotId])) {
//                 if ($studentGroupId && isset($usageMap[$slotId]['student_groups'][$studentGroupId])) return true;
//                 if (!$studentOnly) {
//                     if (isset($usageMap[$slotId]['instructors'][$instructorId])) return true;
//                     if (isset($usageMap[$slotId]['rooms'][$roomId])) return true;
//                 }
//             }
//         }
//         return false;
//     }

//     /**
//      * دالة مساعدة لتحديث خريطة استخدام الموارد
//      */
//     // private function updateResourceMap(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId, array &$usageMap): void
//     // {
//     //     foreach ($slotIds as $slotId) {
//     //         $usageMap[$slotId]['instructors'][$instructorId] = true;
//     //         $usageMap[$slotId]['rooms'][$roomId] = true;
//     //         if ($studentGroupId) {
//     //             $usageMap[$slotId]['student_groups'][$studentGroupId] = true;
//     //         }
//     //     }
//     // }
//     private function updateResourceMap(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId, array &$usageMap): void
//     {
//         foreach ($slotIds as $slotId) {
//             $usageMap[$slotId]['instructors'][$instructorId] = true;
//             $usageMap[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupId) {
//                 $usageMap[$slotId]['student_groups'][$studentGroupId] = true;
//             }
//         }
//     }

//     /**
//      * دالة مساعدة لإيجاد الفترات المتتالية (بدون عشوائية)
//      */
//     // private function getConsecutiveSlots(int $startSlotId, int $slotsNeeded): array
//     // {
//     //     if ($slotsNeeded <= 0) return [];
//     //     if ($slotsNeeded == 1) return [$startSlotId];

//     //     if (isset($this->consecutiveTimeslotsMap[$startSlotId]) && count($this->consecutiveTimeslotsMap[$startSlotId]) >= ($slotsNeeded - 1)) {
//     //         return array_merge([$startSlotId], array_slice($this->consecutiveTimeslotsMap[$startSlotId], 0, $slotsNeeded - 1));
//     //     }
//     //     return [];
//     // }
//     private function getConsecutiveSlots(int $startSlotId, int $slotsNeeded): array
//     {
//         if ($slotsNeeded <= 0) return [];
//         if ($slotsNeeded == 1) return [$startSlotId];

//         if (isset($this->consecutiveTimeslotsMap[$startSlotId]) && count($this->consecutiveTimeslotsMap[$startSlotId]) >= ($slotsNeeded - 1)) {
//             return array_merge([$startSlotId], array_slice($this->consecutiveTimeslotsMap[$startSlotId], 0, $slotsNeeded - 1));
//         }
//         return [];
//     }

//     /**
//      * دالة مساعدة لإيجاد فترات زمنية متتالية عشوائية (تستخدم كحل أخير)
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
//     private function getRandomInstructorForSection(Section $section): Instructor
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
//     private function getRandomRoomForSection(Section $section): Room
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

//                 // تم تعيين المدرس مسبقاً، لذا لا حاجة لفحص الأهلية هنا لأنها مضمونة
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
//     // private function createNewGeneration(array $parents, int $nextGenerationNumber): Collection
//     // {
//     //     Log::info("Creating new generation #{$nextGenerationNumber}");
//     //     $childrenData = [];
//     //     $parentPool = $parents;

//     //     for ($i = 0; $i < $this->settings['population_size']; $i += 2) {
//     //         if (count($parentPool) < 2) $parentPool = $parents;

//     //         $p1_key = array_rand($parentPool);
//     //         $parent1 = $parentPool[$p1_key];
//     //         unset($parentPool[$p1_key]);
//     //         $p2_key = array_rand($parentPool);
//     //         $parent2 = $parentPool[$p2_key];
//     //         unset($parentPool[$p2_key]);

//     //         [$child1Lectures, $child2Lectures] = $this->performCrossover($parent1, $parent2);

//     //         $childrenData[] = $this->performMutation($child1Lectures);
//     //         if (count($childrenData) < $this->settings['population_size']) {
//     //             $childrenData[] = $this->performMutation($child2Lectures);
//     //         }
//     //     }

//     //     $newlyCreatedChromosomes = [];
//     //     foreach ($childrenData as $lecturesToInsert) {
//     //         $newlyCreatedChromosomes[] = $this->saveChildChromosome($lecturesToInsert, $nextGenerationNumber);
//     //     }
//     //     return collect($newlyCreatedChromosomes);
//     // }
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

//             // **(المنطق الجديد)**: الطفرة الذكية
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

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$lectureBlock->unique_id] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Lectures->get($lectureBlock->unique_id);
//             $child2Lectures[$lectureBlock->unique_id] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Lectures->get($lectureBlock->unique_id);

//             $currentIndex++;
//         }
//         return [$child1Lectures, $child2Lectures];
//     }

//     /**
//      * دالة الطفرة (Mutation) - **(المنطق الجديد والذكي)**
//      */
//     // private function performMutation(array $lectures): array
//     // {
//     //     if (lcg_value() < $this->settings['mutation_rate']) {
//     //         if (empty($lectures)) return [];

//     //         $usageMap = [];
//     //         $lectureKeyToMutate = array_rand($lectures);

//     //         // 1. بناء خريطة استخدام الموارد لهذا الحل، مع تجاهل البلوك الذي سنغيره
//     //         foreach ($lectures as $key => $lectureGenes) {
//     //             if ($key === $lectureKeyToMutate || is_null($lectureGenes)) continue;
//     //             $firstGene = $lectureGenes->first();
//     //             $this->updateResourceMap(
//     //                 $lectureGenes->pluck('timeslot_id')->all(),
//     //                 $firstGene->instructor_id,
//     //                 $firstGene->room_id,
//     //                 $this->studentGroupMap[$firstGene->section_id] ?? null,
//     //                 $usageMap
//     //             );
//     //         }

//     //         $originalLectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $lectureKeyToMutate);
//     //         if (!$originalLectureBlock) return $lectures;

//     //         // 2. البحث بذكاء عن مكان جديد
//     //         $newRoom = $this->getRandomRoomForSection($originalLectureBlock->section);
//     //         $newSlots = $this->findBestAvailableSlots($originalLectureBlock, $originalLectureBlock->instructor, $newRoom, $this->studentGroupMap[$originalLectureBlock->section->id] ?? null, $usageMap);

//     //         // 3. تحديث الجينات بالوقت والقاعة الجديدين
//     //         $mutatedGenes = [];
//     //         foreach ($newSlots as $timeslotId) {
//     //             $gene = new Gene([
//     //                 'lecture_unique_id' => $lectureKeyToMutate,
//     //                 'section_id' => $originalLectureBlock->section->id,
//     //                 'instructor_id' => $originalLectureBlock->instructor->id,
//     //                 'room_id' => $newRoom->id,
//     //                 'timeslot_id' => $timeslotId
//     //             ]);
//     //             $mutatedGenes[] = $gene;
//     //         }
//     //         $lectures[$lectureKeyToMutate] = collect($mutatedGenes);
//     //     }
//     //     return $lectures;
//     // }
//     // private function performMutation(array $lectures): array
//     // {
//     //     if (lcg_value() < $this->settings['mutation_rate'] && !empty($lectures)) {
//     //         // 1. نختار بلوك محاضرة عشوائي من الحل الحالي لتغييره
//     //         $lectureKeyToMutate = array_rand($lectures);
//     //         $lectureToMutate = $this->lectureBlocksToSchedule->firstWhere('unique_id', $lectureKeyToMutate);
//     //         if (!$lectureToMutate) return $lectures;

//     //         // 2. نزيل البلوك القديم من الحل ونبني خريطة استخدام الموارد للباقي
//     //         $currentSolution = $lectures;
//     //         unset($currentSolution[$lectureKeyToMutate]);
//     //         $resourceUsageMap = $this->buildResourceMapFromLectures($currentSolution);

//     //         // 3. نختار مدرساً وقاعة جديدين (يمكن أن يكونا نفس القديمين)
//     //         $newInstructor = $this->getRandomInstructorForSection($lectureToMutate->section);
//     //         $newRoom = $this->getRandomRoomForSection($lectureToMutate->section);
//     //         $studentGroupId = $this->studentGroupMap[$lectureToMutate->section->id] ?? null;

//     //         // 4. نستخدم "البحث المنظم" لإيجاد أفضل مكان جديد للبلوك
//     //         $newSlots = $this->findBestAvailableSlots($lectureToMutate, $newInstructor, $newRoom, $studentGroupId, $resourceUsageMap);

//     //         // 5. ننشئ جينات جديدة للبلوك في مكانه الجديد
//     //         $mutatedGenes = [];
//     //         foreach ($newSlots as $timeslotId) {
//     //             $gene = new Gene([
//     //                 'lecture_unique_id' => $lectureKeyToMutate,
//     //                 'section_id' => $lectureToMutate->section->id,
//     //                 'instructor_id' => $newInstructor->id,
//     //                 'room_id' => $newRoom->id,
//     //                 'timeslot_id' => $timeslotId
//     //             ]);
//     //             $mutatedGenes[] = $gene;
//     //         }
//     //         // 6. نحدث الحل بالبلوك الجديد
//     //         $lectures[$lectureKeyToMutate] = collect($mutatedGenes);
//     //     }
//     //     return $lectures;
//     // }
//     private function performMutation(array $lectures): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate'] && !empty($lectures)) {
//             // 1. نختار بلوك محاضرة عشوائي من الحل الحالي لتغييره
//             $lectureKeyToMutate = array_rand($lectures);
//             $lectureGenesToMutate = $lectures[$lectureKeyToMutate];
//             if (is_null($lectureGenesToMutate) || $lectureGenesToMutate->isEmpty()) return $lectures;

//             $originalLectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $lectureKeyToMutate);
//             if(!$originalLectureBlock) return $lectures;

//             // 2. نزيل البلوك القديم من الحل ونبني خريطة استخدام الموارد للباقي
//             $currentSolution = $lectures;
//             unset($currentSolution[$lectureKeyToMutate]);
//             $resourceUsageMap = $this->buildResourceMapFromLectures($currentSolution);

//             // 3. نستخدم نفس المدرس والقاعة (يمكن تطويرها لاحقاً لتغييرهما أيضاً)
//             $instructor = $lectureGenesToMutate->first()->instructor;
//             $room = $lectureGenesToMutate->first()->room;
//             $studentGroupId = $this->studentGroupMap[$originalLectureBlock->section->id] ?? null;

//             // 4. نستخدم "البحث المنظم" لإيجاد أفضل مكان جديد للبلوك
//             $newSlots = $this->findBestAvailableSlots($originalLectureBlock, $instructor, $room, $studentGroupId, $resourceUsageMap);

//             // 5. ننشئ جينات جديدة للبلوك في مكانه الجديد
//             $mutatedGenes = [];
//             foreach ($newSlots as $timeslotId) {
//                  $gene = new Gene([
//                      'lecture_unique_id' => $lectureKeyToMutate,
//                      'section_id' => $originalLectureBlock->section->id,
//                      'instructor_id' => $instructor->id,
//                      'room_id' => $room->id,
//                      'timeslot_id' => $timeslotId
//                  ]);
//                  $mutatedGenes[] = $gene;
//             }
//             // 6. نحدث الحل بالبلوك الجديد
//             $lectures[$lectureKeyToMutate] = collect($mutatedGenes);
//         }
//         return $lectures;
//     }

//     // private function buildResourceMapFromLectures(array $lectures): array
//     // {
//     //     $usageMap = [];
//     //     foreach ($lectures as $lectureGenes) {
//     //         if (is_null($lectureGenes)) continue;

//     //         $firstGene = $lectureGenes->first();
//     //         $instructorId = $firstGene->instructor_id;
//     //         $roomId = $firstGene->room_id;
//     //         $studentGroupId = $this->studentGroupMap[$firstGene->section_id] ?? null;

//     //         foreach ($lectureGenes as $gene) {
//     //             $this->updateResourceMap([$gene->timeslot_id], $instructorId, $roomId, $studentGroupId, $usageMap);
//     //         }
//     //     }
//     //     return $usageMap;
//     // }
//         private function buildResourceMapFromLectures(array $lectures): array
//     {
//         $usageMap = [];
//         foreach($lectures as $lectureKey => $lectureGenes) {
//             if (is_null($lectureGenes) || $lectureGenes->isEmpty()) continue;

//             $firstGene = $lectureGenes->first();
//             $instructorId = $firstGene->instructor_id;
//             $roomId = $firstGene->room_id;
//             $studentGroupId = $this->studentGroupMap[$firstGene->section_id] ?? null;

//             foreach($lectureGenes as $gene) {
//                 // قد يكون الجين عبارة عن كائن Eloquent أو مصفوفة، نوحد التعامل معه
//                 $timeslotId = is_array($gene) ? $gene['timeslot_id'] : $gene->timeslot_id;
//                 $this->updateResourceMap([$timeslotId], $instructorId, $roomId, $studentGroupId, $usageMap);
//             }
//         }
//         return $usageMap;
//     }

//     /**
//      * دالة مساعدة لحفظ الكروموسوم الابن وجيناته في قاعدة البيانات
//      */
//     private function saveChildChromosome(array $lectures, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create(['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber]);

//         $genesToInsert = [];
//         foreach ($lectures as $lectureGenes) {
//             if (is_null($lectureGenes) || $lectureGenes->isEmpty()) continue;
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

//         if (!empty($genesToInsert)) {
//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
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
//     private array $settings;
//     private Population $populationRun;
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;
//     private Collection $timeslots;
//     private Collection $lectureBlocksToSchedule;
//     private array $consecutiveTimeslotsMap = [];
//     private array $studentGroupMap = [];

//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

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
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $this->instructors = Instructor::with('subjects')->get();
//         $sections = Section::with('planSubject.subject', 'planSubject.plan')
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])->get();
//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         $this->lectureBlocksToSchedule = collect();
//         foreach ($sections as $section) {
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             // **(منطق جديد)** تعيين مدرس ثابت لكل شعبة
//             $assignedInstructor = $this->getRandomInstructorForSection($section);

//             if ($section->activity_type == 'Practical') {
//                 $totalSlotsNeeded = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//                 if ($totalSlotsNeeded > 0) {
//                     $this->lectureBlocksToSchedule->push((object)[
//                         'section' => $section,
//                         'slots_needed' => $totalSlotsNeeded,
//                         'unique_id' => $section->id . '-practical',
//                         'assigned_instructor' => $assignedInstructor
//                     ]);
//                 }
//             } elseif ($section->activity_type == 'Theory') {
//                 $totalSlotsNeeded = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//                 $blockCounter = 1;
//                 while ($totalSlotsNeeded > 0) {
//                     $slotsForThisBlock = ($totalSlotsNeeded >= 2 && $totalSlotsNeeded != 3) ? 2 : 1; // تعديل بسيط لتقسيم 3 ساعات إلى 2+1
//                      if ($totalSlotsNeeded == 3) $slotsForThisBlock = 2;

//                     $this->lectureBlocksToSchedule->push((object)[
//                         'section' => $section,
//                         'slots_needed' => $slotsForThisBlock,
//                         'unique_id' => $section->id . '-theory-' . $blockCounter++,
//                         'assigned_instructor' => $assignedInstructor
//                     ]);
//                     $totalSlotsNeeded -= $slotsForThisBlock;
//                 }
//             }
//         }
//         if ($this->lectureBlocksToSchedule->isEmpty()) throw new Exception("No lecture blocks to schedule were found after processing credit hours.");

//         $this->buildStudentGroupMap($sections);

//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();
//         $this->buildConsecutiveTimeslotsMap();

//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//     }

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

//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating highly intelligent initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//         }
//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();

//         $sortedLectureBlocks = $this->lectureBlocksToSchedule->sortByDesc('slots_needed');

//         foreach ($createdChromosomes as $chromosome) {
//             $resourceUsageMap = [];
//             $genesToInsert = [];

//             foreach ($sortedLectureBlocks as $lectureBlock) {
//                 // **(منطق جديد)**: نستخدم المدرس المعين مسبقاً
//                 $instructor = $lectureBlock->assigned_instructor;
//                 $room = $this->getRandomRoomForSection($lectureBlock->section);
//                 $studentGroupId = $this->studentGroupMap[$lectureBlock->section->id] ?? null;

//                 $foundSlots = $this->findBestAvailableSlots($lectureBlock, $instructor, $room, $studentGroupId, $resourceUsageMap);

//                 $this->updateResourceMap($foundSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap);
//                 foreach ($foundSlots as $timeslotId) {
//                     $genesToInsert[] = ['chromosome_id' => $chromosome->chromosome_id, 'lecture_unique_id' => $lectureBlock->unique_id, 'section_id' => $lectureBlock->section->id, 'instructor_id' => $instructor->id, 'room_id' => $room->id, 'timeslot_id' => $timeslotId];
//                 }
//             }

//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     private function findBestAvailableSlots($lectureBlock, $instructor, $room, $studentGroupId, &$resourceUsageMap): array
//     {
//         // المرحلة الأولى: البحث المنظم عن مكان مثالي
//         foreach ($this->timeslots as $startSlot) {
//             $trialSlots = $this->getConsecutiveSlots($startSlot->id, $lectureBlock->slots_needed);
//             if (!empty($trialSlots) && !$this->checkConflictsForSlots($trialSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap)) {
//                 return $trialSlots;
//             }
//         }

//         // المرحلة الثانية: إذا فشلنا، نبحث عن مكان بأقل تعارض (الأولوية للطالب)
//         foreach ($this->timeslots as $startSlot) {
//             $trialSlots = $this->getConsecutiveSlots($startSlot->id, $lectureBlock->slots_needed);
//             if (!empty($trialSlots) && !$this->checkConflictsForSlots($trialSlots, $instructor->id, $room->id, $studentGroupId, $resourceUsageMap, true)) {
//                 return $trialSlots;
//             }
//         }

//         // المرحلة الثالثة: كحل أخير، نختار مكاناً عشوائياً
//         return $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
//     }

//     private function checkConflictsForSlots(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId, array $usageMap, bool $studentOnly = false): bool
//     {
//         foreach ($slotIds as $slotId) {
//             if (isset($usageMap[$slotId])) {
//                 if ($studentGroupId && isset($usageMap[$slotId]['student_groups'][$studentGroupId])) return true;
//                 if (!$studentOnly) {
//                     if (isset($usageMap[$slotId]['instructors'][$instructorId])) return true;
//                     if (isset($usageMap[$slotId]['rooms'][$roomId])) return true;
//                 }
//             }
//         }
//         return false;
//     }

//     private function updateResourceMap(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId, array &$usageMap): void
//     {
//         foreach ($slotIds as $slotId) {
//             $usageMap[$slotId]['instructors'][$instructorId] = true;
//             $usageMap[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupId) {
//                 $usageMap[$slotId]['student_groups'][$studentGroupId] = true;
//             }
//         }
//     }

//     private function getConsecutiveSlots(int $startSlotId, int $slotsNeeded): array
//     {
//         if ($slotsNeeded <= 0) return [];
//         if ($slotsNeeded == 1) return [$startSlotId];

//         if (isset($this->consecutiveTimeslotsMap[$startSlotId]) && count($this->consecutiveTimeslotsMap[$startSlotId]) >= ($slotsNeeded - 1)) {
//             return array_merge([$startSlotId], array_slice($this->consecutiveTimeslotsMap[$startSlotId], 0, $slotsNeeded - 1));
//         }
//         return [];
//     }

//     private function findRandomConsecutiveTimeslots(int $slotsNeeded): array
//     {
//         if ($slotsNeeded <= 0) return [];
//         if ($slotsNeeded == 1) return [$this->timeslots->random()->id];

//         $shuffledTimeslots = $this->timeslots->shuffle();
//         foreach ($shuffledTimeslots as $startSlot) {
//             if (isset($this->consecutiveTimeslotsMap[$startSlot->id]) && count($this->consecutiveTimeslotsMap[$startSlot->id]) >= ($slotsNeeded - 1)) {
//                 return array_merge([$startSlot->id], array_slice($this->consecutiveTimeslotsMap[$startSlot->id], 0, $slotsNeeded - 1));
//             }
//         }
//         Log::warning("Could not find {$slotsNeeded} consecutive slots, returning a single random slot as fallback.");
//         return [$this->timeslots->random()->id]; // حل بديل أخير
//     }

//     private function getRandomInstructorForSection(Section $section)
//     {
//         $subject = optional($section->planSubject)->subject;
//         if (!$subject) return $this->instructors->random();

//         $suitableInstructors = $this->instructors->filter(fn ($instructor) => $instructor->subjects->contains($subject->id));
//         if ($suitableInstructors->isNotEmpty()) return $suitableInstructors->random();

//         Log::warning("No specific instructors assigned to subject '{$subject->subject_name}'. Choosing a random instructor.");
//         return $this->instructors->random();
//     }

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

//     private function selectParents(Collection $population): array
//     {
//         $parents = [];
//         $tournamentSize = 3;
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $parents[] = $population->random($tournamentSize)->sortBy('penalty_value')->first();
//         }
//         return $parents;
//     }

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

//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Lectures = $parent1->genes()->get()->groupBy('lecture_unique_id');
//         $p2Lectures = $parent2->genes()->get()->groupBy('lecture_unique_id');
//         $child1Lectures = []; $child2Lectures = [];

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$lectureBlock->unique_id] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Lectures->get($lectureBlock->unique_id);
//             $child2Lectures[$lectureBlock->unique_id] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Lectures->get($lectureBlock->unique_id);

//             $currentIndex++;
//         }
//         return [$child1Lectures, $child2Lectures];
//     }

//     private function performMutation(array $lectures): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate'] && !empty($lectures)) {
//             $lectureKeyToMutate = array_rand($lectures);
//             $lectureToMutate = $this->lectureBlocksToSchedule->firstWhere('unique_id', $lectureKeyToMutate);
//             if(!$lectureToMutate) return $lectures;

//             $currentSolution = $lectures;
//             unset($currentSolution[$lectureKeyToMutate]);
//             $resourceUsageMap = $this->buildResourceMapFromLectures($currentSolution);

//             $instructor = $lectureToMutate->assigned_instructor; // **(منطق جديد)** نستخدم المدرس الثابت
//             $room = $this->getRandomRoomForSection($lectureToMutate->section);
//             $studentGroupId = $this->studentGroupMap[$lectureToMutate->section->id] ?? null;

//             $newSlots = $this->findBestAvailableSlots($lectureToMutate, $instructor, $room, $studentGroupId, $resourceUsageMap);

//             $mutatedGenes = [];
//             foreach ($newSlots as $timeslotId) {
//                  $gene = new Gene([
//                      'lecture_unique_id' => $lectureKeyToMutate,
//                      'section_id' => $lectureToMutate->section->id,
//                      'instructor_id' => $instructor->id,
//                      'room_id' => $room->id,
//                      'timeslot_id' => $timeslotId
//                  ]);
//                  $mutatedGenes[] = $gene;
//             }
//             $lectures[$lectureKeyToMutate] = collect($mutatedGenes);
//         }
//         return $lectures;
//     }

//     private function buildResourceMapFromLectures(array $lectures): array
//     {
//         $usageMap = [];
//         foreach($lectures as $lectureGenes) {
//             if (is_null($lectureGenes)) continue;

//             $firstGene = $lectureGenes->first();
//             $instructorId = $firstGene->instructor_id;
//             $roomId = $firstGene->room_id;
//             $studentGroupId = $this->studentGroupMap[$firstGene->section_id] ?? null;

//             foreach($lectureGenes as $gene) {
//                 $this->updateResourceMap([$gene->timeslot_id], $instructorId, $roomId, $studentGroupId, $usageMap);
//             }
//         }
//         return $usageMap;
//     }

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

// *********************************************** z.ai ***********************************

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
//     private array $settings;
//     private Population $populationRun;
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;
//     private Collection $timeslots;
//     private Collection $lectureBlocksToSchedule;
//     private array $consecutiveTimeslotsMap = [];
//     private array $studentGroupMap = [];
//     private array $instructorAssignmentMap = []; // جديد: لتتبع مدرس كل مادة

//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

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
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     // private function loadAndPrepareData()
//     // {
//     //     Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//     //     $sections = Section::with('planSubject.subject', 'planSubject.plan')
//     //         ->where('academic_year', $this->settings['academic_year'])
//     //         ->where('semester', $this->settings['semester'])->get();

//     //     if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//     //     $this->lectureBlocksToSchedule = collect();
//     //     $this->instructorAssignmentMap = [];

//     //     foreach ($sections as $section) {
//     //         $subject = optional($section->planSubject)->subject;
//     //         if (!$subject) continue;

//     //         // تحديد عدد الساعات الفعلية المطلوبة
//     //         if ($section->activity_type == 'Practical') {
//     //             $totalHours = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//     //             $blockType = 'practical';
//     //         } else {
//     //             $totalHours = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//     //             $blockType = 'theory';
//     //         }

//     //         if ($totalHours <= 0) continue;

//     //         // تقسيم البلوكات حسب النوع
//     //         if ($blockType == 'practical') {
//     //             // العملي دائماً بلوك واحد متصل
//     //             $this->lectureBlocksToSchedule->push((object)[
//     //                 'section' => $section,
//     //                 'block_type' => $blockType,
//     //                 'total_hours' => $totalHours,
//     //                 'slots_needed' => $totalHours,
//     //                 'is_continuous' => true,
//     //                 'unique_id' => $section->id . '-practical-block'
//     //             ]);
//     //         } else {
//     //             // النظري يمكن تقسيمه
//     //             $this->splitTheoryBlocks($section, $totalHours);
//     //         }
//     //     }
//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sectionsQuery = Section::with('planSubject.subject', 'planSubject.plan')
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester']);

//         $sections = $sectionsQuery->get(); // تنفيذ الاستعلام أولاً
//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         $this->lectureBlocksToSchedule = collect();
//         $this->instructorAssignmentMap = [];

//         foreach ($sections as $section) { // الآن $section هو Section model
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             // تحديد عدد الساعات الفعلية المطلوبة
//             if ($section->activity_type == 'Practical') {
//                 $totalHours = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//                 $blockType = 'practical';
//             } else {
//                 $totalHours = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//                 $blockType = 'theory';
//             }

//             if ($totalHours <= 0) continue;

//             // تقسيم البلوكات حسب النوع
//             if ($blockType == 'practical') {
//                 // العملي دائماً بلوك واحد متصل
//                 $this->lectureBlocksToSchedule->push((object)[
//                     'section' => $section,
//                     'block_type' => $blockType,
//                     'total_hours' => $totalHours,
//                     'slots_needed' => $totalHours,
//                     'is_continuous' => true,
//                     'unique_id' => $section->id . '-practical-block'
//                 ]);
//             } else {
//                 // النظري يمكن تقسيمه
//                 $this->splitTheoryBlocks($section, $totalHours);
//             }
//         }


//         if ($this->lectureBlocksToSchedule->isEmpty()) {
//             throw new Exception("No lecture blocks to schedule were found after processing credit hours.");
//         }

//         $this->buildStudentGroupMap($sections);
//         $this->buildInstructorAssignmentMap();

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) =>
//         $q->where('room_type_name', 'not like', '%Lab%')
//             ->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) =>
//         $q->where('room_type_name', 'like', '%Lab%')
//             ->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
//             ->orderBy('start_time')->get();
//         $this->buildConsecutiveTimeslotsMap();

//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//     }

//     private function splitTheoryBlocks(Section $section, int $totalHours)
//     {
//         $blockCounter = 1;
//         $remainingHours = $totalHours;

//         while ($remainingHours > 0) {
//             // استراتيجية التقسيم: حاول إنشاء بلوكات بحجم 2 ساعات أولاً
//             if ($remainingHours >= 2 && rand(0, 1) == 1) {
//                 $blockHours = 2;
//             } else {
//                 $blockHours = 1;
//             }

//             $this->lectureBlocksToSchedule->push((object)[
//                 'section' => $section,
//                 'block_type' => 'theory',
//                 'total_hours' => $blockHours,
//                 'slots_needed' => $blockHours,
//                 'is_continuous' => true, // سيتم التحقق لاحقاً من إمكانية الاتصال
//                 'unique_id' => $section->id . '-theory-block' . $blockCounter++
//             ]);

//             $remainingHours -= $blockHours;
//         }
//     }

//     private function buildInstructorAssignmentMap()
//     {
//         foreach ($this->lectureBlocksToSchedule as $block) {
//             $subjectId = $block->section->planSubject->subject_id;

//             if (!isset($this->instructorAssignmentMap[$subjectId])) {
//                 $this->instructorAssignmentMap[$subjectId] = $this->getRandomInstructorForSection($block->section)->id;
//             }
//         }
//     }

//     private function buildStudentGroupMap(Collection $sections)
//     {
//         $this->studentGroupMap = [];
//         $sectionsByContext = $sections->groupBy(function ($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $sectionsInContext) {
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')
//                 ->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 // الشعب النظرية مشتركة بين جميع المجموعات
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id] = $groupIndex;
//                 }

//                 // الشعب العملية موزعة على المجموعات
//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')
//                     ->groupBy('plan_subject_id');

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

//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating intelligent initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = [
//                 'population_id' => $this->populationRun->population_id,
//                 'penalty_value' => -1,
//                 'generation_number' => $generationNumber,
//                 'created_at' => now(),
//                 'updated_at' => now()
//             ];
//         }

//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)
//             ->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $resourceUsageMap = [];
//             $genesToInsert = [];

//             // ترتيب البلوكات حسب الأولوية (العملية أولاً، ثم النظرية الكبيرة)
//             $sortedBlocks = $this->lectureBlocksToSchedule
//                 ->sortByDesc('slots_needed')
//                 ->sortByDesc('block_type'); // practical أولاً

//             foreach ($sortedBlocks as $lectureBlock) {
//                 $instructorId = $this->instructorAssignmentMap[$lectureBlock->section->planSubject->subject_id];
//                 $room = $this->getRandomRoomForSection($lectureBlock->section);
//                 $studentGroupId = $this->studentGroupMap[$lectureBlock->section->id] ?? null;

//                 $foundSlots = $this->findOptimalSlotForBlock($lectureBlock, $instructorId, $room->id, $studentGroupId, $resourceUsageMap);

//                 // تحديث خريطة الموارد
//                 $this->updateResourceMap($foundSlots, $instructorId, $room->id, $studentGroupId, $resourceUsageMap);

//                 // إضافة الجينات
//                 foreach ($foundSlots as $timeslotId) {
//                     $genesToInsert[] = [
//                         'chromosome_id' => $chromosome->chromosome_id,
//                         'lecture_unique_id' => $lectureBlock->unique_id,
//                         'section_id' => $lectureBlock->section->id,
//                         'instructor_id' => $instructorId,
//                         'room_id' => $room->id,
//                         'timeslot_id' => $timeslotId,
//                         'block_type' => $lectureBlock->block_type,
//                         'block_duration' => $lectureBlock->slots_needed,
//                         'is_continuous' => $this->areSlotsConsecutive($foundSlots),
//                         'student_group_id' => $studentGroupId,
//                     ];
//                 }
//             }

//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     private function findOptimalSlotForBlock($lectureBlock, int $instructorId, int $roomId, ?int $studentGroupId, array &$resourceUsageMap): array
//     {
//         $bestSlots = [];
//         $minConflicts = PHP_INT_MAX;

//         // البحث عن أفضل مكان مع أولويات مختلفة
//         $priorities = [
//             // الأولوية 1: لا تعارضات مع الطالب
//             ['student_group', 'room', 'instructor'],
//             // الأولوية 2: لا تعارضات مع الطالب والقاعة
//             ['student_group', 'room'],
//             // الأولوية 3: لا تعارضات مع الطالب فقط
//             ['student_group'],
//         ];

//         foreach ($priorities as $priority) {
//             foreach ($this->timeslots as $startSlot) {
//                 if (
//                     isset($this->consecutiveTimeslotsMap[$startSlot->id]) &&
//                     count($this->consecutiveTimeslotsMap[$startSlot->id]) >= ($lectureBlock->slots_needed - 1)
//                 ) {

//                     $trialSlots = array_merge(
//                         [$startSlot->id],
//                         array_slice($this->consecutiveTimeslotsMap[$startSlot->id], 0, $lectureBlock->slots_needed - 1)
//                     );

//                     $conflicts = $this->countConflictsByPriority($trialSlots, $instructorId, $roomId, $studentGroupId, $resourceUsageMap, $priority);

//                     if ($conflicts < $minConflicts) {
//                         $minConflicts = $conflicts;
//                         $bestSlots = $trialSlots;

//                         if ($conflicts == 0) {
//                             return $bestSlots; // وجدنا المكان المثالي
//                         }
//                     }
//                 }
//             }

//             if ($minConflicts == 0) break;
//         }

//         // إذا لم نجد مكانًا مثاليًا، ابحث عن أي مكان متاح
//         if (empty($bestSlots)) {
//             $bestSlots = $this->findAnyAvailableSlot($lectureBlock, $instructorId, $roomId, $studentGroupId, $resourceUsageMap);
//         }

//         return $bestSlots;
//     }

//     private function countConflictsByPriority(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId, array $usageMap, array $priority): int
//     {
//         $conflicts = 0;

//         foreach ($slotIds as $slotId) {
//             if (isset($usageMap[$slotId])) {
//                 // التحقق من التعارضات حسب الأولوية
//                 if (in_array('student_group', $priority) && $studentGroupId && isset($usageMap[$slotId]['student_groups'][$studentGroupId])) {
//                     $conflicts += 2000;
//                 }
//                 if (in_array('room', $priority) && isset($usageMap[$slotId]['rooms'][$roomId])) {
//                     $conflicts += 800;
//                 }
//                 if (in_array('instructor', $priority) && isset($usageMap[$slotId]['instructors'][$instructorId])) {
//                     $conflicts += 1000;
//                 }
//             }
//         }

//         return $conflicts;
//     }

//     private function findAnyAvailableSlot($lectureBlock, int $instructorId, int $roomId, ?int $studentGroupId, array &$resourceUsageMap): array
//     {
//         // محاولة إيجاد أي مكان متاح حتى لو فيه تعارضات
//         foreach ($this->timeslots as $startSlot) {
//             if (
//                 isset($this->consecutiveTimeslotsMap[$startSlot->id]) &&
//                 count($this->consecutiveTimeslotsMap[$startSlot->id]) >= ($lectureBlock->slots_needed - 1)
//             ) {

//                 $trialSlots = array_merge(
//                     [$startSlot->id],
//                     array_slice($this->consecutiveTimeslotsMap[$startSlot->id], 0, $lectureBlock->slots_needed - 1)
//                 );

//                 return $trialSlots;
//             }
//         }

//         // الحل الأخير: أي فترة متاحة حتى لو غير متصلة
//         $availableSlots = $this->timeslots->random($lectureBlock->slots_needed)->pluck('id')->toArray();
//         return $availableSlots;
//     }

//     private function areSlotsConsecutive(array $slotIds): bool
//     {
//         if (count($slotIds) <= 1) return true;

//         $slots = $this->timeslots->whereIn('id', $slotIds)->sortBy('start_time');

//         for ($i = 1; $i < $slots->count(); $i++) {
//             $current = $slots[$i];
//             $previous = $slots[$i - 1];

//             if ($current->day != $previous->day || $current->start_time != $previous->end_time) {
//                 return false;
//             }
//         }

//         return true;
//     }

//     private function updateResourceMap(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId, array &$usageMap): void
//     {
//         foreach ($slotIds as $slotId) {
//             $usageMap[$slotId]['instructors'][$instructorId] = true;
//             $usageMap[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupId) {
//                 $usageMap[$slotId]['student_groups'][$studentGroupId] = true;
//             }
//         }
//     }

//     private function getRandomInstructorForSection(Section $section)
//     {
//         $subject = optional($section->planSubject)->subject;
//         if (!$subject) return $this->instructors->random();

//         $suitableInstructors = $this->instructors->filter(fn($instructor) =>
//         $instructor->subjects->contains($subject->id));

//         return $suitableInstructors->isNotEmpty() ? $suitableInstructors->random() : $this->instructors->random();
//     }

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

//     private function evaluateFitness(Collection $chromosomes)
//     {
//         // تحميل جميع الجينات مرة واحدة للأداء
//         $allGenes = Gene::whereIn('chromosome_id', $chromosomes->pluck('chromosome_id'))
//             ->with(['instructor', 'room', 'timeslot', 'section.planSubject.subject'])
//             ->get()
//             ->groupBy('chromosome_id');

//         foreach ($chromosomes as $chromosome) {
//             $genes = $allGenes[$chromosome->chromosome_id];
//             $totalPenalty = 0;

//             // بناء خريطة استخدام موارد شاملة
//             $resourceUsageMap = [];
//             $studentGroupUsage = [];
//             $instructorUsage = [];

//             // التحقق من تعارضات البلوكات
//             foreach ($genes->groupBy('lecture_unique_id') as $block) {
//                 $timeslotIds = $block->pluck('timeslot_id')->unique();
//                 $instructorId = $block->first()->instructor_id;
//                 $roomId = $block->first()->room_id;
//                 $sectionId = $block->first()->section_id;
//                 $studentGroupId = $block->first()->student_group_id;
//                 $blockType = $block->first()->block_type;
//                 $isContinuous = $block->first()->is_continuous;

//                 // عقوبة على عدم استمرارية البلوكات (للمواد النظرية)
//                 if ($blockType == 'theory' && !$isContinuous && count($timeslotIds) > 1) {
//                     $totalPenalty += 300;
//                 }

//                 // التحقق من تعارضات المدرس في جميع فترات البلوك
//                 foreach ($timeslotIds as $timeslotId) {
//                     if (isset($instructorUsage[$instructorId][$timeslotId])) {
//                         $totalPenalty += 1000;
//                     }
//                     $instructorUsage[$instructorId][$timeslotId] = true;
//                 }

//                 // التحقق من تعارضات القاعة في جميع فترات البلوك
//                 foreach ($timeslotIds as $timeslotId) {
//                     if (isset($resourceUsageMap[$timeslotId]['rooms'][$roomId])) {
//                         $totalPenalty += 800;
//                     }
//                 }

//                 // التحقق من تعارضات مجموعات الطلاب (أهم عقوبة)
//                 if ($studentGroupId) {
//                     foreach ($timeslotIds as $timeslotId) {
//                         if (isset($studentGroupUsage[$studentGroupId][$timeslotId])) {
//                             $totalPenalty += 2000;
//                         }
//                         $studentGroupUsage[$studentGroupId][$timeslotId] = true;
//                     }
//                 }

//                 // تحديث خريطة الموارد
//                 foreach ($timeslotIds as $timeslotId) {
//                     $resourceUsageMap[$timeslotId]['rooms'][$roomId] = true;
//                 }
//             }

//             // التحقق من القيود الإضافية
//             $totalPenalty += $this->checkAdditionalConstraints($genes);

//             DB::table('chromosomes')->where('chromosome_id', $chromosome->chromosome_id)
//                 ->update(['penalty_value' => $totalPenalty]);
//             $chromosome->penalty_value = $totalPenalty;
//         }
//     }

//     private function checkAdditionalConstraints(Collection $genes): int
//     {
//         $penalty = 0;

//         foreach ($genes->unique('lecture_unique_id') as $representativeGene) {
//             if (!$representativeGene->section || !$representativeGene->room || !$representativeGene->instructor) {
//                 continue;
//             }

//             // التحقق من سعة القاعة
//             if ($representativeGene->section->student_count > $representativeGene->room->room_size) {
//                 $penalty += 800;
//             }

//             // التحقق من نوع القاعة
//             $roomType = optional($representativeGene->room->roomType)->room_type_name ?? '';
//             $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);

//             if ($representativeGene->block_type == 'Practical' && !$isPracticalRoom) {
//                 $penalty += 600;
//             }
//             if ($representativeGene->block_type == 'Theory' && $isPracticalRoom) {
//                 $penalty += 600;
//             }

//             // التحقق من أهلية المدرس
//             if (!optional($representativeGene->instructor)->canTeach(
//                 optional($representativeGene->section->planSubject)->subject
//             )) {
//                 $penalty += 2000;
//             }
//         }

//         return $penalty;
//     }

//     private function selectParents(Collection $population): array
//     {
//         $parents = [];
//         $tournamentSize = 3;

//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $parents[] = $population->random($tournamentSize)->sortBy('penalty_value')->first();
//         }

//         return $parents;
//     }

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

//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Lectures = $parent1->genes()->get()->groupBy('lecture_unique_id');
//         $p2Lectures = $parent2->genes()->get()->groupBy('lecture_unique_id');
//         $child1Lectures = [];
//         $child2Lectures = [];

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$lectureBlock->unique_id] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Lectures->get($lectureBlock->unique_id);
//             $child2Lectures[$lectureBlock->unique_id] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Lectures->get($lectureBlock->unique_id);

//             $currentIndex++;
//         }

//         return [$child1Lectures, $child2Lectures];
//     }

//     private function performMutation(array $lectures): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($lectures)) return [];

//             $lectureKeyToMutate = array_rand($lectures);
//             $originalLectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $lectureKeyToMutate);

//             if (!$originalLectureBlock) return $lectures;

//             // الحفاظ على نفس المدرس للمادة النظرية
//             $instructorId = $this->instructorAssignmentMap[$originalLectureBlock->section->planSubject->subject_id];
//             $newRoom = $this->getRandomRoomForSection($originalLectureBlock->section);
//             $studentGroupId = $this->studentGroupMap[$originalLectureBlock->section->id] ?? null;

//             // إيجاد فترات زمنية جديدة
//             $resourceUsageMap = []; // خريطة مؤقتة للتحقق من التعارضات
//             $newTimeslots = $this->findOptimalSlotForBlock($originalLectureBlock, $instructorId, $newRoom->id, $studentGroupId, $resourceUsageMap);

//             $mutatedGenes = [];
//             foreach ($newTimeslots as $timeslotId) {
//                 $mutatedGenes[] = new Gene([
//                     'lecture_unique_id' => $lectureKeyToMutate,
//                     'section_id' => $originalLectureBlock->section->id,
//                     'instructor_id' => $instructorId,
//                     'room_id' => $newRoom->id,
//                     'timeslot_id' => $timeslotId,
//                     'block_type' => $originalLectureBlock->block_type,
//                     'block_duration' => $originalLectureBlock->slots_needed,
//                     'is_continuous' => $this->areSlotsConsecutive($newTimeslots),
//                     'student_group_id' => $studentGroupId,
//                 ]);
//             }

//             $lectures[$lectureKeyToMutate] = collect($mutatedGenes);
//         }

//         return $lectures;
//     }

//     private function saveChildChromosome(array $lectures, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create([
//             'population_id' => $this->populationRun->population_id,
//             'penalty_value' => -1,
//             'generation_number' => $generationNumber
//         ]);

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
//                     'block_type' => $gene->block_type,
//                     'block_duration' => $gene->block_duration,
//                     'is_continuous' => $gene->is_continuous,
//                     'student_group_id' => $gene->student_group_id,
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
//     private array $settings;
//     private Population $populationRun;
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;
//     private Collection $timeslots;
//     private Collection $lectureBlocksToSchedule;
//     private array $consecutiveTimeslotsMap = [];
//     private array $studentGroupMap = [];
//     private array $instructorAssignmentMap = [];
//     private array $resourceUsageCache = []; // كاش جديد للموارد

//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

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
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with(['planSubject.subject', 'planSubject.plan'])
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])
//             ->get();

//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         $this->lectureBlocksToSchedule = collect();
//         $this->instructorAssignmentMap = [];

//         foreach ($sections as $section) {
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             // تحديد عدد الساعات الفعلية المطلوبة
//             if ($section->activity_type == 'Practical') {
//                 $totalHours = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//                 $blockType = 'practical';
//             } else {
//                 $totalHours = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//                 $blockType = 'theory';
//             }

//             if ($totalHours <= 0) continue;

//             // تقسيم البلوكات حسب النوع
//             if ($blockType == 'practical') {
//                 // العملي دائماً بلوك واحد متصل
//                 $this->lectureBlocksToSchedule->push((object)[
//                     'section' => $section,
//                     'block_type' => $blockType,
//                     'total_hours' => $totalHours,
//                     'slots_needed' => $totalHours,
//                     'is_continuous' => true,
//                     'unique_id' => $section->id . '-practical-block'
//                 ]);
//             } else {
//                 // النظري يمكن تقسيمه
//                 $this->splitTheoryBlocks($section, $totalHours);
//             }
//         }

//         if ($this->lectureBlocksToSchedule->isEmpty()) {
//             throw new Exception("No lecture blocks to schedule were found after processing credit hours.");
//         }

//         $this->buildStudentGroupMap($sections);
//         $this->buildInstructorAssignmentMap();

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) =>
//             $q->where('room_type_name', 'not like', '%Lab%')
//              ->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) =>
//             $q->where('room_type_name', 'like', '%Lab%')
//              ->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
//             ->orderBy('start_time')->get();
//         $this->buildConsecutiveTimeslotsMap();

//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//     }

//     private function splitTheoryBlocks(Section $section, int $totalHours)
//     {
//         $blockCounter = 1;
//         $remainingHours = $totalHours;

//         while ($remainingHours > 0) {
//             // استراتيجية التقسيم: حاول إنشاء بلوكات بحجم 2 ساعات أولاً
//             if ($remainingHours >= 2) {
//                 $blockHours = 2;
//             } else {
//                 $blockHours = 1;
//             }

//             $this->lectureBlocksToSchedule->push((object)[
//                 'section' => $section,
//                 'block_type' => 'theory',
//                 'total_hours' => $blockHours,
//                 'slots_needed' => $blockHours,
//                 'is_continuous' => true, // سيتم التحقق لاحقاً من إمكانية الاتصال
//                 'unique_id' => $section->id . '-theory-block' . $blockCounter++
//             ]);

//             $remainingHours -= $blockHours;
//         }
//     }

//     private function buildInstructorAssignmentMap()
//     {
//         foreach ($this->lectureBlocksToSchedule as $block) {
//             $subjectId = $block->section->planSubject->subject_id;

//             if (!isset($this->instructorAssignmentMap[$subjectId])) {
//                 $this->instructorAssignmentMap[$subjectId] = $this->getRandomInstructorForSection($block->section)->id;
//             }
//         }
//     }

//     private function buildStudentGroupMap(Collection $sections)
//     {
//         $this->studentGroupMap = [];
//         $sectionsByContext = $sections->groupBy(function($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $contextKey => $sectionsInContext) {
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')
//                 ->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 // الشعب النظرية مشتركة بين جميع المجموعات
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id] = $groupIndex;
//                 }

//                 // الشعب العملية موزعة على المجموعات
//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')
//                     ->groupBy('plan_subject_id');

//                 foreach ($practicalSectionsBySubject as $subjectId => $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
//                     }
//                 }
//             }
//         }
//     }

//     private function buildConsecutiveTimeslotsMap()
//     {
//         $timeslotsByDay = $this->timeslots->groupBy('day');
//         $this->consecutiveTimeslotsMap = [];

//         foreach ($timeslotsByDay as $dayTimeslots) {
//             $dayTimeslotsValues = $dayTimeslots->sortBy('start_time')->values();
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

//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating intelligent initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = [
//                 'population_id' => $this->populationRun->population_id,
//                 'penalty_value' => -1,
//                 'generation_number' => $generationNumber,
//                 'created_at' => now(),
//                 'updated_at' => now()
//             ];
//         }

//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)
//             ->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $this->resourceUsageCache = []; // إعادة تعيين الكاش لكل كروموسوم
//             $genesToInsert = [];

//             // ترتيب البلوكات حسب الأولوية (العملية أولاً، ثم النظرية الكبيرة)
//             $sortedBlocks = $this->lectureBlocksToSchedule
//                 ->sortByDesc('slots_needed')
//                 ->sortByDesc('block_type'); // practical أولاً

//             foreach ($sortedBlocks as $lectureBlock) {
//                 $instructorId = $this->instructorAssignmentMap[$lectureBlock->section->planSubject->subject_id];
//                 $room = $this->getRandomRoomForSection($lectureBlock->section);
//                 $studentGroupId = $this->studentGroupMap[$lectureBlock->section->id] ?? null;

//                 $foundSlots = $this->findOptimalSlotForBlock($lectureBlock, $instructorId, $room->id, $studentGroupId);

//                 // تحديث خريطة الموارد
//                 $this->updateResourceCache($foundSlots, $instructorId, $room->id, $studentGroupId);

//                 // إضافة الجينات
//                 foreach ($foundSlots as $timeslotId) {
//                     $genesToInsert[] = [
//                         'chromosome_id' => $chromosome->chromosome_id,
//                         'lecture_unique_id' => $lectureBlock->unique_id,
//                         'section_id' => $lectureBlock->section->id,
//                         'instructor_id' => $instructorId,
//                         'room_id' => $room->id,
//                         'timeslot_id' => $timeslotId,
//                         'block_type' => $lectureBlock->block_type,
//                         'block_duration' => $lectureBlock->slots_needed,
//                         'is_continuous' => $this->areSlotsConsecutive($foundSlots),
//                         'student_group_id' => $studentGroupId,
//                     ];
//                 }
//             }

//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     private function findOptimalSlotForBlock($lectureBlock, int $instructorId, int $roomId, ?int $studentGroupId): array
//     {
//         $bestSlots = [];
//         $minConflicts = PHP_INT_MAX;

//         // البحث عن أفضل مكان مع أولويات مختلفة
//         foreach ($this->timeslots as $startSlot) {
//             if (isset($this->consecutiveTimeslotsMap[$startSlot->id]) &&
//                 count($this->consecutiveTimeslotsMap[$startSlot->id]) >= ($lectureBlock->slots_needed - 1)) {

//                 $trialSlots = array_merge([$startSlot->id],
//                     array_slice($this->consecutiveTimeslotsMap[$startSlot->id], 0, $lectureBlock->slots_needed - 1));

//                 $conflicts = $this->countConflictsForSlots($trialSlots, $instructorId, $roomId, $studentGroupId);

//                 if ($conflicts < $minConflicts) {
//                     $minConflicts = $conflicts;
//                     $bestSlots = $trialSlots;

//                     if ($conflicts == 0) {
//                         return $bestSlots; // وجدنا المكان المثالي
//                     }
//                 }
//             }
//         }

//         // إذا لم نجد مكانًا مثاليًا، ابحث عن أي مكان متاح
//         if (empty($bestSlots)) {
//             $bestSlots = $this->findAnyAvailableSlot($lectureBlock, $instructorId, $roomId, $studentGroupId);
//         }

//         return $bestSlots;
//     }

//     private function countConflictsForSlots(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId): int
//     {
//         $conflicts = 0;

//         foreach ($slotIds as $slotId) {
//             if (isset($this->resourceUsageCache[$slotId])) {
//                 // التحقق من تعارضات مجموعات الطلاب (أهم عقوبة)
//                 if ($studentGroupId && isset($this->resourceUsageCache[$slotId]['student_groups'][$studentGroupId])) {
//                     $conflicts += 2000;
//                 }

//                 // التحقق من تعارضات المدرس
//                 if (isset($this->resourceUsageCache[$slotId]['instructors'][$instructorId])) {
//                     $conflicts += 1000;
//                 }

//                 // التحقق من تعارضات القاعة
//                 if (isset($this->resourceUsageCache[$slotId]['rooms'][$roomId])) {
//                     $conflicts += 800;
//                 }
//             }
//         }

//         return $conflicts;
//     }

//     private function findAnyAvailableSlot($lectureBlock, int $instructorId, int $roomId, ?int $studentGroupId): array
//     {
//         // محاولة إيجاد أي مكان متاح حتى لو فيه تعارضات
//         foreach ($this->timeslots as $startSlot) {
//             if (isset($this->consecutiveTimeslotsMap[$startSlot->id]) &&
//                 count($this->consecutiveTimeslotsMap[$startSlot->id]) >= ($lectureBlock->slots_needed - 1)) {

//                 $trialSlots = array_merge([$startSlot->id],
//                     array_slice($this->consecutiveTimeslotsMap[$startSlot->id], 0, $lectureBlock->slots_needed - 1));

//                 return $trialSlots;
//             }
//         }

//         // الحل الأخير: أي فترة متاحة حتى لو غير متصلة
//         $availableSlots = [];
//         for ($i = 0; $i < $lectureBlock->slots_needed; $i++) {
//             $availableSlots[] = $this->timeslots->random()->id;
//         }

//         return $availableSlots;
//     }

//     private function areSlotsConsecutive(array $slotIds): bool
//     {
//         if (count($slotIds) <= 1) return true;

//         $slots = $this->timeslots->whereIn('id', $slotIds)->sortBy('start_time');

//         for ($i = 1; $i < $slots->count(); $i++) {
//             $current = $slots[$i];
//             $previous = $slots[$i - 1];

//             if ($current->day != $previous->day || $current->start_time != $previous->end_time) {
//                 return false;
//             }
//         }

//         return true;
//     }

//     private function updateResourceCache(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId): void
//     {
//         foreach ($slotIds as $slotId) {
//             $this->resourceUsageCache[$slotId]['instructors'][$instructorId] = true;
//             $this->resourceUsageCache[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupId) {
//                 $this->resourceUsageCache[$slotId]['student_groups'][$studentGroupId] = true;
//             }
//         }
//     }

//     private function getRandomInstructorForSection(Section $section)
//     {
//         $subject = optional($section->planSubject)->subject;
//         if (!$subject) return $this->instructors->random();

//         $suitableInstructors = $this->instructors->filter(fn ($instructor) =>
//             $instructor->subjects->contains($subject->id));

//         return $suitableInstructors->isNotEmpty() ? $suitableInstructors->random() : $this->instructors->random();
//     }

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

//     private function evaluateFitness(Collection $chromosomes)
//     {
//         // تحميل جميع الجينات مرة واحدة للأداء
//         $allGenes = Gene::whereIn('chromosome_id', $chromosomes->pluck('chromosome_id'))
//             ->with(['instructor', 'room', 'timeslot', 'section.planSubject.subject'])
//             ->get()
//             ->groupBy('chromosome_id');

//         foreach ($chromosomes as $chromosome) {
//             $genes = $allGenes[$chromosome->chromosome_id];
//             $totalPenalty = 0;

//             // بناء خريطة استخدام موارد شاملة
//             $resourceUsageMap = [];
//             $studentGroupUsage = [];
//             $instructorUsage = [];

//             // التحقق من تعارضات البلوكات
//             foreach ($genes->groupBy('lecture_unique_id') as $block) {
//                 $timeslotIds = $block->pluck('timeslot_id')->unique();
//                 $instructorId = $block->first()->instructor_id;
//                 $roomId = $block->first()->room_id;
//                 $sectionId = $block->first()->section_id;
//                 $studentGroupId = $block->first()->student_group_id;
//                 $blockType = $block->first()->block_type;
//                 $isContinuous = $block->first()->is_continuous;

//                 // عقوبة على عدم استمرارية البلوكات (للمواد النظرية)
//                 if ($blockType == 'theory' && !$isContinuous && count($timeslotIds) > 1) {
//                     $totalPenalty += 300;
//                 }

//                 // التحقق من تعارضات المدرس في جميع فترات البلوك
//                 foreach ($timeslotIds as $timeslotId) {
//                     if (isset($instructorUsage[$instructorId][$timeslotId])) {
//                         $totalPenalty += 1000;
//                     }
//                     $instructorUsage[$instructorId][$timeslotId] = true;
//                 }

//                 // التحقق من تعارضات القاعة في جميع فترات البلوك
//                 foreach ($timeslotIds as $timeslotId) {
//                     if (isset($resourceUsageMap[$timeslotId]['rooms'][$roomId])) {
//                         $totalPenalty += 800;
//                     }
//                     $resourceUsageMap[$timeslotId]['rooms'][$roomId] = true;
//                 }

//                 // التحقق من تعارضات مجموعات الطلاب (أهم عقوبة)
//                 if ($studentGroupId) {
//                     foreach ($timeslotIds as $timeslotId) {
//                         if (isset($studentGroupUsage[$studentGroupId][$timeslotId])) {
//                             $totalPenalty += 2000;
//                         }
//                         $studentGroupUsage[$studentGroupId][$timeslotId] = true;
//                     }
//                 }
//             }

//             // التحقق من القيود الإضافية
//             $totalPenalty += $this->checkAdditionalConstraints($genes);

//             DB::table('chromosomes')->where('chromosome_id', $chromosome->chromosome_id)
//                 ->update(['penalty_value' => $totalPenalty]);
//             $chromosome->penalty_value = $totalPenalty;
//         }
//     }

//     private function checkAdditionalConstraints(Collection $genes): int
//     {
//         $penalty = 0;

//         foreach ($genes->unique('lecture_unique_id') as $representativeGene) {
//             if (!$representativeGene->section || !$representativeGene->room || !$representativeGene->instructor) {
//                 continue;
//             }

//             // التحقق من سعة القاعة
//             if ($representativeGene->section->student_count > $representativeGene->room->room_size) {
//                 $penalty += 800;
//             }

//             // التحقق من نوع القاعة
//             $roomType = optional($representativeGene->room->roomType)->room_type_name ?? '';
//             $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);

//             if ($representativeGene->block_type == 'Practical' && !$isPracticalRoom) {
//                 $penalty += 600;
//             }
//             if ($representativeGene->block_type == 'Theory' && $isPracticalRoom) {
//                 $penalty += 600;
//             }

//             // التحقق من أهلية المدرس
//             if (!optional($representativeGene->instructor)->canTeach(
//                 optional($representativeGene->section->planSubject)->subject
//             )) {
//                 $penalty += 2000;
//             }
//         }

//         return $penalty;
//     }

//     private function selectParents(Collection $population): array
//     {
//         $parents = [];
//         $tournamentSize = 3;

//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $parents[] = $population->random($tournamentSize)->sortBy('penalty_value')->first();
//         }

//         return $parents;
//     }

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

//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Lectures = $parent1->genes()->get()->groupBy('lecture_unique_id');
//         $p2Lectures = $parent2->genes()->get()->groupBy('lecture_unique_id');
//         $child1Lectures = [];
//         $child2Lectures = [];

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$lectureBlock->unique_id] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Lectures->get($lectureBlock->unique_id);
//             $child2Lectures[$lectureBlock->unique_id] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Lectures->get($lectureBlock->unique_id);

//             $currentIndex++;
//         }

//         return [$child1Lectures, $child2Lectures];
//     }

//     private function performMutation(array $lectures): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($lectures)) return [];

//             $lectureKeyToMutate = array_rand($lectures);
//             $originalLectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $lectureKeyToMutate);

//             if (!$originalLectureBlock) return $lectures;

//             // الحفاظ على نفس المدرس للمادة النظرية
//             $instructorId = $this->instructorAssignmentMap[$originalLectureBlock->section->planSubject->subject_id];
//             $newRoom = $this->getRandomRoomForSection($originalLectureBlock->section);
//             $studentGroupId = $this->studentGroupMap[$originalLectureBlock->section->id] ?? null;

//             // إيجاد فترات زمنية جديدة
//             $this->resourceUsageCache = []; // إعادة تعيين الكاش للطفرة
//             $newTimeslots = $this->findOptimalSlotForBlock($originalLectureBlock, $instructorId, $newRoom->id, $studentGroupId);

//             $mutatedGenes = [];
//             foreach ($newTimeslots as $timeslotId) {
//                 $mutatedGenes[] = new Gene([
//                     'lecture_unique_id' => $lectureKeyToMutate,
//                     'section_id' => $originalLectureBlock->section->id,
//                     'instructor_id' => $instructorId,
//                     'room_id' => $newRoom->id,
//                     'timeslot_id' => $timeslotId,
//                     'block_type' => $originalLectureBlock->block_type,
//                     'block_duration' => $originalLectureBlock->slots_needed,
//                     'is_continuous' => $this->areSlotsConsecutive($newTimeslots),
//                     'student_group_id' => $studentGroupId,
//                 ]);
//             }

//             $lectures[$lectureKeyToMutate] = collect($mutatedGenes);
//         }

//         return $lectures;
//     }

//     private function saveChildChromosome(array $lectures, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create([
//             'population_id' => $this->populationRun->population_id,
//             'penalty_value' => -1,
//             'generation_number' => $generationNumber
//         ]);

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
//                     'block_type' => $gene->block_type,
//                     'block_duration' => $gene->block_duration,
//                     'is_continuous' => $gene->is_continuous,
//                     'student_group_id' => $gene->student_group_id,
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
//     private array $settings;
//     private Population $populationRun;
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;
//     private Collection $timeslots;
//     private Collection $lectureBlocksToSchedule;
//     private array $consecutiveTimeslotsMap = [];
//     private array $studentGroupMap = [];
//     private array $instructorAssignmentMap = [];
//     private array $resourceUsageCache = [];

//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

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
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sectionsQuery = Section::with(['planSubject.subject', 'planSubject.plan'])
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester']);

//         $sections = $sectionsQuery->get();
//         $sections = $sections->fresh();

//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         $this->lectureBlocksToSchedule = collect();
//         $this->instructorAssignmentMap = [];

//         // فصل البلوكات النظرية والعملية
//         $theoryBlocks = collect();
//         $practicalBlocks = collect();

//         foreach ($sections as $section) {
//             if (!$section instanceof \App\Models\Section) {
//                 continue;
//             }

//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             // تحديد عدد الساعات الفعلية المطلوبة
//             if ($section->activity_type == 'Practical') {
//                 $totalHours = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//                 $blockType = 'practical';
//             } else {
//                 $totalHours = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//                 $blockType = 'theory';
//             }

//             if ($totalHours <= 0) continue;

//             $blockData = (object)[
//                 'section' => $section,
//                 'block_type' => $blockType,
//                 'total_hours' => $totalHours,
//                 'slots_needed' => $totalHours,
//                 'is_continuous' => true,
//                 'unique_id' => $section->id . '-' . $blockType . '-block'
//             ];

//             // فصل البلوكات حسب النوع
//             if ($blockType == 'practical') {
//                 $practicalBlocks->push($blockData);
//             } else {
//                 $theoryBlocks->push($blockData);
//             }
//         }

//         // دمج البلوكات: النظرية أولاً، ثم العملية (فكرتك الممتازة!)
//         $this->lectureBlocksToSchedule = $theoryBlocks->merge($practicalBlocks);

//         if ($this->lectureBlocksToSchedule->isEmpty()) {
//             throw new Exception("No lecture blocks to schedule were found after processing credit hours.");
//         }

//         $this->buildStudentGroupMap($sections);
//         $this->buildInstructorAssignmentMap();

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) =>
//         $q->where('room_type_name', 'not like', '%Lab%')
//             ->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) =>
//         $q->where('room_type_name', 'like', '%Lab%')
//             ->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
//             ->orderBy('start_time')->get();
//         $this->buildConsecutiveTimeslotsMap();

//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//         Log::info("Theory blocks: " . $theoryBlocks->count() . ", Practical blocks: " . $practicalBlocks->count());
//     }

//     private function buildInstructorAssignmentMap()
//     {
//         foreach ($this->lectureBlocksToSchedule as $block) {
//             $subjectId = $block->section->planSubject->subject_id;

//             if (!isset($this->instructorAssignmentMap[$subjectId])) {
//                 $this->instructorAssignmentMap[$subjectId] = $this->getRandomInstructorForSection($block->section)->id;
//             }
//         }
//     }

//     private function buildStudentGroupMap(Collection $sections)
//     {
//         $this->studentGroupMap = [];
//         $sectionsByContext = $sections->groupBy(function ($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $contextKey => $sectionsInContext) {
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')
//                 ->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 // الشعب النظرية مشتركة بين جميع المجموعات
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id] = $groupIndex;
//                 }

//                 // الشعب العملية موزعة على المجموعات
//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')
//                     ->groupBy('plan_subject_id');

//                 foreach ($practicalSectionsBySubject as $subjectId => $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
//                     }
//                 }
//             }
//         }
//     }

//     private function buildConsecutiveTimeslotsMap()
//     {
//         $timeslotsByDay = $this->timeslots->groupBy('day');
//         $this->consecutiveTimeslotsMap = [];

//         foreach ($timeslotsByDay as $dayTimeslots) {
//             $dayTimeslotsValues = $dayTimeslots->sortBy('start_time')->values();
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

//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating intelligent initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = [
//                 'population_id' => $this->populationRun->population_id,
//                 'penalty_value' => -1,
//                 'generation_number' => $generationNumber,
//                 'created_at' => now(),
//                 'updated_at' => now()
//             ];
//         }

//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)
//             ->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $this->resourceUsageCache = [];
//             $genesToInsert = [];

//             foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//                 $instructorId = $this->instructorAssignmentMap[$lectureBlock->section->planSubject->subject_id];
//                 $room = $this->getRandomRoomForSection($lectureBlock->section);
//                 $studentGroupId = $this->studentGroupMap[$lectureBlock->section->id] ?? null;

//                 $foundSlots = $this->findOptimalSlotForBlock($lectureBlock, $instructorId, $room->id, $studentGroupId);

//                 $this->updateResourceCache($foundSlots, $instructorId, $room->id, $studentGroupId);

//                 foreach ($foundSlots as $timeslotId) {
//                     $genesToInsert[] = [
//                         'chromosome_id' => $chromosome->chromosome_id,
//                         'lecture_unique_id' => $lectureBlock->unique_id,
//                         'section_id' => $lectureBlock->section->id,
//                         'instructor_id' => $instructorId,
//                         'room_id' => $room->id,
//                         'timeslot_id' => $timeslotId,
//                         'block_type' => $lectureBlock->block_type,
//                         'block_duration' => $lectureBlock->slots_needed,
//                         'is_continuous' => $this->areSlotsConsecutive($foundSlots),
//                         'student_group_id' => $studentGroupId,
//                     ];
//                 }
//             }

//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     private function findOptimalSlotForBlock($lectureBlock, int $instructorId, int $roomId, ?int $studentGroupId): array
//     {
//         $bestSlots = [];
//         $minConflicts = PHP_INT_MAX;

//         // البحث عن أفضل مكان مع أولويات مختلفة
//         foreach ($this->timeslots as $startSlot) {
//             if (
//                 isset($this->consecutiveTimeslotsMap[$startSlot->id]) &&
//                 count($this->consecutiveTimeslotsMap[$startSlot->id]) >= ($lectureBlock->slots_needed - 1)
//             ) {

//                 $trialSlots = array_merge(
//                     [$startSlot->id],
//                     array_slice($this->consecutiveTimeslotsMap[$startSlot->id], 0, $lectureBlock->slots_needed - 1)
//                 );

//                 $conflicts = $this->countConflictsForSlots($trialSlots, $instructorId, $roomId, $studentGroupId);

//                 if ($conflicts < $minConflicts) {
//                     $minConflicts = $conflicts;
//                     $bestSlots = $trialSlots;

//                     if ($conflicts == 0) {
//                         return $bestSlots; // وجدنا المكان المثالي
//                     }
//                 }
//             }
//         }

//         // إذا لم نجد مكانًا مثاليًا، ابحث عن أي مكان متاح
//         if (empty($bestSlots)) {
//             $bestSlots = $this->findAnyAvailableSlot($lectureBlock, $instructorId, $roomId, $studentGroupId);
//         }

//         return $bestSlots;
//     }

//     private function countConflictsForSlots(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId): int
//     {
//         $conflicts = 0;

//         foreach ($slotIds as $slotId) {
//             if (isset($this->resourceUsageCache[$slotId])) {
//                 // التحقق من تعارضات مجموعات الطلاب (أهم عقوبة)
//                 if ($studentGroupId && isset($this->resourceUsageCache[$slotId]['student_groups'][$studentGroupId])) {
//                     $conflicts += 2000;
//                 }

//                 // التحقق من تعارضات المدرس
//                 if (isset($this->resourceUsageCache[$slotId]['instructors'][$instructorId])) {
//                     $conflicts += 1000;
//                 }

//                 // التحقق من تعارضات القاعة
//                 if (isset($this->resourceUsageCache[$slotId]['rooms'][$roomId])) {
//                     $conflicts += 800;
//                 }
//             }
//         }

//         return $conflicts;
//     }

//     private function findAnyAvailableSlot($lectureBlock, int $instructorId, int $roomId, ?int $studentGroupId): array
//     {
//         // محاولة إيجاد أي مكان متاح حتى لو فيه تعارضات
//         foreach ($this->timeslots as $startSlot) {
//             if (
//                 isset($this->consecutiveTimeslotsMap[$startSlot->id]) &&
//                 count($this->consecutiveTimeslotsMap[$startSlot->id]) >= ($lectureBlock->slots_needed - 1)
//             ) {

//                 $trialSlots = array_merge(
//                     [$startSlot->id],
//                     array_slice($this->consecutiveTimeslotsMap[$startSlot->id], 0, $lectureBlock->slots_needed - 1)
//                 );

//                 return $trialSlots;
//             }
//         }

//         // الحل الأخير: أي فترة متاحة حتى لو غير متصلة
//         $availableSlots = $this->timeslots->random($lectureBlock->slots_needed)->pluck('id')->toArray();
//         return $availableSlots;
//     }

//     private function areSlotsConsecutive(array $slotIds): bool
//     {
//         if (count($slotIds) <= 1) return true;

//         $slots = $this->timeslots->whereIn('id', $slotIds)->sortBy('start_time');

//         for ($i = 1; $i < $slots->count(); $i++) {
//             $current = $slots[$i];
//             $previous = $slots[$i - 1];

//             if ($current->day != $previous->day || $current->start_time != $previous->end_time) {
//                 return false;
//             }
//         }

//         return true;
//     }

//     private function updateResourceCache(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId): void
//     {
//         foreach ($slotIds as $slotId) {
//             $this->resourceUsageCache[$slotId]['instructors'][$instructorId] = true;
//             $this->resourceUsageCache[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupId) {
//                 $this->resourceUsageCache[$slotId]['student_groups'][$studentGroupId] = true;
//             }
//         }
//     }

//     private function getRandomInstructorForSection(Section $section)
//     {
//         $subject = optional($section->planSubject)->subject;
//         if (!$subject) return $this->instructors->random();

//         $suitableInstructors = $this->instructors->filter(fn($instructor) =>
//         $instructor->subjects->contains($subject->id));

//         return $suitableInstructors->isNotEmpty() ? $suitableInstructors->random() : $this->instructors->random();
//     }

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

//     private function evaluateFitness(Collection $chromosomes)
//     {
//         // تحسين الأداء: تحميل جميع الجينات مرة واحدة
//         $chromosomeIds = $chromosomes->pluck('chromosome_id');
//         $allGenes = Gene::whereIn('chromosome_id', $chromosomeIds)
//             ->with(['instructor', 'room', 'timeslot', 'section.planSubject.subject'])
//             ->get()
//             ->groupBy('chromosome_id');

//         foreach ($chromosomes as $chromosome) {
//             if (!isset($allGenes[$chromosome->chromosome_id])) {
//                 continue;
//             }

//             $genes = $allGenes[$chromosome->chromosome_id];
//             $totalPenalty = 0;

//             // إعادة تعيين الخريطة لكل كروموسوم
//             $resourceUsageMap = [];
//             $studentGroupUsage = [];
//             $instructorUsage = [];

//             // التحقق من تعارضات البلوكات
//             foreach ($genes->groupBy('lecture_unique_id') as $block) {
//                 $timeslotIds = $block->pluck('timeslot_id')->unique();
//                 $instructorId = $block->first()->instructor_id;
//                 $roomId = $block->first()->room_id;
//                 $sectionId = $block->first()->section_id;
//                 $studentGroupId = $block->first()->student_group_id;
//                 $blockType = $block->first()->block_type;
//                 $isContinuous = $block->first()->is_continuous;

//                 // عقوبة على عدم استمرارية البلوكات (للمواد النظرية فقط)
//                 if ($blockType == 'theory' && !$isContinuous && count($timeslotIds) > 1) {
//                     $totalPenalty += 300;
//                 }

//                 // التحقق من تعارضات المدرس في جميع فترات البلوك
//                 foreach ($timeslotIds as $timeslotId) {
//                     if (isset($instructorUsage[$instructorId][$timeslotId])) {
//                         $totalPenalty += 1000;
//                     }
//                     $instructorUsage[$instructorId][$timeslotId] = true;
//                 }

//                 // التحقق من تعارضات القاعة في جميع فترات البلوك
//                 foreach ($timeslotIds as $timeslotId) {
//                     if (isset($resourceUsageMap[$timeslotId]['rooms'][$roomId])) {
//                         $totalPenalty += 800;
//                     }
//                 }

//                 // التحقق من تعارضات مجموعات الطلاب (أهم عقوبة)
//                 if ($studentGroupId) {
//                     foreach ($timeslotIds as $timeslotId) {
//                         if (isset($studentGroupUsage[$studentGroupId][$timeslotId])) {
//                             $totalPenalty += 2000;
//                         }
//                         $studentGroupUsage[$studentGroupId][$timeslotId] = true;
//                     }
//                 }

//                 // تحديث خريطة الموارد
//                 foreach ($timeslotIds as $timeslotId) {
//                     $resourceUsageMap[$timeslotId]['rooms'][$roomId] = true;
//                 }
//             }

//             // التحقق من القيود الإضافية
//             $totalPenalty += $this->checkAdditionalConstraints($genes);

//             // تحديث العقوبة في قاعدة البيانات
//             DB::table('chromosomes')
//                 ->where('chromosome_id', $chromosome->chromosome_id)
//                 ->update(['penalty_value' => $totalPenalty]);

//             $chromosome->penalty_value = $totalPenalty;
//         }
//     }

//     private function checkAdditionalConstraints(Collection $genes): int
//     {
//         $penalty = 0;

//         foreach ($genes->unique('lecture_unique_id') as $representativeGene) {
//             if (!$representativeGene->section || !$representativeGene->room || !$representativeGene->instructor) {
//                 continue;
//             }

//             // التحقق من سعة القاعة
//             if ($representativeGene->section->student_count > $representativeGene->room->room_size) {
//                 $penalty += 800;
//             }

//             // التحقق من نوع القاعة
//             $roomType = optional($representativeGene->room->roomType)->room_type_name ?? '';
//             $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);

//             if ($representativeGene->block_type == 'Practical' && !$isPracticalRoom) {
//                 $penalty += 600;
//             }
//             if ($representativeGene->block_type == 'Theory' && $isPracticalRoom) {
//                 $penalty += 600;
//             }

//             // التحقق من أهلية المدرس
//             if (!optional($representativeGene->instructor)->canTeach(
//                 optional($representativeGene->section->planSubject)->subject
//             )) {
//                 $penalty += 2000;
//             }

//             // عقوبة على البلوكات العملية غير المتصلة (يجب أن تكون متصلة دائماً)
//             if ($representativeGene->block_type == 'Practical' && !$representativeGene->is_continuous) {
//                 $penalty += 1000;
//             }
//         }

//         return $penalty;
//     }

//     private function selectParents(Collection $population): array
//     {
//         $parents = [];
//         $tournamentSize = 3;

//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $participants = $population->random($tournamentSize);
//             $parents[] = $participants->sortBy('penalty_value')->first();
//         }

//         return $parents;
//     }

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

//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Lectures = $parent1->genes()->get()->groupBy('lecture_unique_id');
//         $p2Lectures = $parent2->genes()->get()->groupBy('lecture_unique_id');
//         $child1Lectures = [];
//         $child2Lectures = [];

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$lectureBlock->unique_id] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Lectures->get($lectureBlock->unique_id);
//             $child2Lectures[$lectureBlock->unique_id] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Lectures->get($lectureBlock->unique_id);

//             $currentIndex++;
//         }

//         return [$child1Lectures, $child2Lectures];
//     }

//     private function performMutation(array $lectures): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($lectures)) return [];

//             $lectureKeyToMutate = array_rand($lectures);
//             $originalLectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $lectureKeyToMutate);

//             if (!$originalLectureBlock) return $lectures;

//             // الحفاظ على نفس المدرس للمادة النظرية
//             $instructorId = $this->instructorAssignmentMap[$originalLectureBlock->section->planSubject->subject_id];
//             $newRoom = $this->getRandomRoomForSection($originalLectureBlock->section);
//             $studentGroupId = $this->studentGroupMap[$originalLectureBlock->section->id] ?? null;

//             // إعادة تعيين خريطة الموارد المؤقتة
//             $tempResourceMap = [];

//             // إيجاد فترات زمنية جديدة
//             $newTimeslots = $this->findOptimalSlotForBlock($originalLectureBlock, $instructorId, $newRoom->id, $studentGroupId);

//             $mutatedGenes = [];
//             foreach ($newTimeslots as $timeslotId) {
//                 $mutatedGenes[] = new Gene([
//                     'lecture_unique_id' => $lectureKeyToMutate,
//                     'section_id' => $originalLectureBlock->section->id,
//                     'instructor_id' => $instructorId,
//                     'room_id' => $newRoom->id,
//                     'timeslot_id' => $timeslotId,
//                     'block_type' => $originalLectureBlock->block_type,
//                     'block_duration' => $originalLectureBlock->slots_needed,
//                     'is_continuous' => $this->areSlotsConsecutive($newTimeslots),
//                     'student_group_id' => $studentGroupId,
//                 ]);
//             }

//             $lectures[$lectureKeyToMutate] = collect($mutatedGenes);
//         }

//         return $lectures;
//     }

//     private function saveChildChromosome(array $lectures, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create([
//             'population_id' => $this->populationRun->population_id,
//             'penalty_value' => -1,
//             'generation_number' => $generationNumber
//         ]);

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
//                     'block_type' => $gene->block_type,
//                     'block_duration' => $gene->block_duration,
//                     'is_continuous' => $gene->is_continuous,
//                     'student_group_id' => $gene->student_group_id,
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
//     private array $settings;
//     private Population $populationRun;
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;
//     private Collection $timeslots;
//     private Collection $lectureBlocksToSchedule;
//     private array $consecutiveTimeslotsMap = [];
//     private array $studentGroupMap = [];
//     private array $instructorAssignmentMap = [];
//     private array $resourceUsageCache = [];

//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

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
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sectionsQuery = Section::with(['planSubject.subject', 'planSubject.plan'])
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester']);

//         $sections = $sectionsQuery->get();
//         $sections = $sections->fresh();

//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         $this->lectureBlocksToSchedule = collect();
//         $this->instructorAssignmentMap = [];

//         // فصل البلوكات النظرية والعملية
//         $theoryBlocks = collect();
//         $practicalBlocks = collect();

//         foreach ($sections as $section) {
//             if (!$section instanceof \App\Models\Section) {
//                 continue;
//             }

//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             // تحديد عدد الساعات الفعلية المطلوبة
//             if ($section->activity_type == 'Practical') {
//                 $totalHours = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//                 $blockType = 'practical';
//             } else {
//                 $totalHours = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//                 $blockType = 'theory';
//             }

//             if ($totalHours <= 0) continue;

//             // تقسيم البلوكات حسب النوع
//             if ($blockType == 'practical') {
//                 $this->splitPracticalBlocks($section, $totalHours, $practicalBlocks);
//             } else {
//                 $this->splitTheoryBlocks($section, $totalHours, $theoryBlocks);
//             }
//         }

//         // دمج البلوكات: النظرية أولاً، ثم العملية
//         $this->lectureBlocksToSchedule = $theoryBlocks->merge($practicalBlocks);

//         if ($this->lectureBlocksToSchedule->isEmpty()) {
//             throw new Exception("No lecture blocks to schedule were found after processing credit hours.");
//         }

//         $this->buildStudentGroupMap($sections);
//         $this->buildInstructorAssignmentMap();

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) =>
//             $q->where('room_type_name', 'not like', '%Lab%')
//              ->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) =>
//             $q->where('room_type_name', 'like', '%Lab%')
//              ->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
//             ->orderBy('start_time')->get();
//         $this->buildConsecutiveTimeslotsMap();

//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//         Log::info("Theory blocks: " . $theoryBlocks->count() . ", Practical blocks: " . $practicalBlocks->count());
//     }

//     private function splitTheoryBlocks(Section $section, int $totalHours, Collection &$theoryBlocks)
//     {
//         $blockCounter = 1;
//         $remainingHours = $totalHours;

//         while ($remainingHours > 0) {
//             // استراتيجية التقسيم: حاول إنشاء بلوكات بحجم 2 ساعات أولاً
//             if ($remainingHours >= 2) {
//                 $blockHours = 2;
//             } else {
//                 $blockHours = 1;
//             }

//             $theoryBlocks->push((object)[
//                 'section' => $section,
//                 'block_type' => 'theory',
//                 'total_hours' => $blockHours,
//                 'slots_needed' => $blockHours,
//                 'is_continuous' => true,
//                 'unique_id' => $section->id . '-theory-block' . $blockCounter++
//             ]);

//             $remainingHours -= $blockHours;
//         }
//     }

//     private function splitPracticalBlocks(Section $section, int $totalHours, Collection &$practicalBlocks)
//     {
//         $blockCounter = 1;
//         $remainingHours = $totalHours;

//         while ($remainingHours > 0) {
//             // استراتيجية التقسيم: حاول إنشاء بلوكات بحجم 4 فترات كحد أقصى
//             if ($remainingHours >= 4) {
//                 $blockHours = 4;
//             } elseif ($remainingHours >= 3) {
//                 $blockHours = 3;
//             } elseif ($remainingHours >= 2) {
//                 $blockHours = 2;
//             } else {
//                 $blockHours = 1;
//             }

//             $practicalBlocks->push((object)[
//                 'section' => $section,
//                 'block_type' => 'practical',
//                 'total_hours' => $blockHours,
//                 'slots_needed' => $blockHours,
//                 'is_continuous' => true, // العملي يجب أن يكون متصلاً دائماً
//                 'unique_id' => $section->id . '-practical-block' . $blockCounter++
//             ]);

//             $remainingHours -= $blockHours;
//         }
//     }

//     private function buildInstructorAssignmentMap()
//     {
//         foreach ($this->lectureBlocksToSchedule as $block) {
//             $subjectId = $block->section->planSubject->subject_id;

//             if (!isset($this->instructorAssignmentMap[$subjectId])) {
//                 $this->instructorAssignmentMap[$subjectId] = $this->getRandomInstructorForSection($block->section)->id;
//             }
//         }
//     }

//     private function buildStudentGroupMap(Collection $sections)
//     {
//         $this->studentGroupMap = [];
//         $sectionsByContext = $sections->groupBy(function($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $contextKey => $sectionsInContext) {
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')
//                 ->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 // الشعب النظرية مشتركة بين جميع المجموعات
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id] = $groupIndex;
//                 }

//                 // الشعب العملية موزعة على المجموعات
//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')
//                     ->groupBy('plan_subject_id');

//                 foreach ($practicalSectionsBySubject as $subjectId => $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
//                     }
//                 }
//             }
//         }
//     }

//     private function buildConsecutiveTimeslotsMap()
//     {
//         $timeslotsByDay = $this->timeslots->groupBy('day');
//         $this->consecutiveTimeslotsMap = [];

//         foreach ($timeslotsByDay as $dayTimeslots) {
//             $dayTimeslotsValues = $dayTimeslots->sortBy('start_time')->values();
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

//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating intelligent initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = [
//                 'population_id' => $this->populationRun->population_id,
//                 'penalty_value' => -1,
//                 'generation_number' => $generationNumber,
//                 'created_at' => now(),
//                 'updated_at' => now()
//             ];
//         }

//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)
//             ->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $this->resourceUsageCache = [];
//             $genesToInsert = [];

//             foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//                 $instructorId = $this->instructorAssignmentMap[$lectureBlock->section->planSubject->subject_id];
//                 $room = $this->getRandomRoomForSection($lectureBlock->section);
//                 $studentGroupId = $this->studentGroupMap[$lectureBlock->section->id] ?? null;

//                 $foundSlots = $this->findOptimalSlotForBlock($lectureBlock, $instructorId, $room->id, $studentGroupId);

//                 $this->updateResourceCache($foundSlots, $instructorId, $room->id, $studentGroupId);

//                 // إضافة جين واحد فقط مع جميع الفترات الزمنية
//                 $genesToInsert[] = [
//                     'chromosome_id' => $chromosome->chromosome_id,
//                     'lecture_unique_id' => $lectureBlock->unique_id,
//                     'section_id' => $lectureBlock->section->id,
//                     'instructor_id' => $instructorId,
//                     'room_id' => $room->id,
//                     'timeslot_ids' => json_encode($foundSlots), // تخزين جميع الفترات كـ JSON
//                     'block_type' => $lectureBlock->block_type,
//                     'block_duration' => $lectureBlock->slots_needed,
//                     'is_continuous' => $this->areSlotsConsecutive($foundSlots),
//                     'student_group_id' => $studentGroupId,
//                 ];
//             }

//             foreach (array_chunk($genesToInsert, 500) as $chunk) {
//                 Gene::insert($chunk);
//             }
//         }

//         return $createdChromosomes;
//     }

//     private function findOptimalSlotForBlock($lectureBlock, int $instructorId, int $roomId, ?int $studentGroupId): array
//     {
//         $bestSlots = [];
//         $minConflicts = PHP_INT_MAX;

//         // البحث عن أفضل مكان مع أولويات مختلفة
//         foreach ($this->timeslots as $startSlot) {
//             if (isset($this->consecutiveTimeslotsMap[$startSlot->id]) &&
//                 count($this->consecutiveTimeslotsMap[$startSlot->id]) >= ($lectureBlock->slots_needed - 1)) {

//                 $trialSlots = array_merge([$startSlot->id],
//                     array_slice($this->consecutiveTimeslotsMap[$startSlot->id], 0, $lectureBlock->slots_needed - 1));

//                 $conflicts = $this->countConflictsForSlots($trialSlots, $instructorId, $roomId, $studentGroupId);

//                 if ($conflicts < $minConflicts) {
//                     $minConflicts = $conflicts;
//                     $bestSlots = $trialSlots;

//                     if ($conflicts == 0) {
//                         return $bestSlots; // وجدنا المكان المثالي
//                     }
//                 }
//             }
//         }

//         // إذا لم نجد مكانًا مثاليًا، ابحث عن أي مكان متاح
//         if (empty($bestSlots)) {
//             $bestSlots = $this->findAnyAvailableSlot($lectureBlock, $instructorId, $roomId, $studentGroupId);
//         }

//         return $bestSlots;
//     }

//     private function countConflictsForSlots(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId): int
//     {
//         $conflicts = 0;

//         foreach ($slotIds as $slotId) {
//             if (isset($this->resourceUsageCache[$slotId])) {
//                 // التحقق من تعارضات مجموعات الطلاب (أهم عقوبة)
//                 if ($studentGroupId && isset($this->resourceUsageCache[$slotId]['student_groups'][$studentGroupId])) {
//                     $conflicts += 2000;
//                 }

//                 // التحقق من تعارضات المدرس
//                 if (isset($this->resourceUsageCache[$slotId]['instructors'][$instructorId])) {
//                     $conflicts += 1000;
//                 }

//                 // التحقق من تعارضات القاعة
//                 if (isset($this->resourceUsageCache[$slotId]['rooms'][$roomId])) {
//                     $conflicts += 800;
//                 }
//             }
//         }

//         return $conflicts;
//     }

//     private function findAnyAvailableSlot($lectureBlock, int $instructorId, int $roomId, ?int $studentGroupId): array
//     {
//         // محاولة إيجاد أي مكان متاح حتى لو فيه تعارضات
//         foreach ($this->timeslots as $startSlot) {
//             if (isset($this->consecutiveTimeslotsMap[$startSlot->id]) &&
//                 count($this->consecutiveTimeslotsMap[$startSlot->id]) >= ($lectureBlock->slots_needed - 1)) {

//                 $trialSlots = array_merge([$startSlot->id],
//                     array_slice($this->consecutiveTimeslotsMap[$startSlot->id], 0, $lectureBlock->slots_needed - 1));

//                 return $trialSlots;
//             }
//         }

//         // الحل الأخير: أي فترة متاحة حتى لو غير متصلة
//         $availableSlots = $this->timeslots->random($lectureBlock->slots_needed)->pluck('id')->toArray();
//         return $availableSlots;
//     }

//     private function areSlotsConsecutive(array $slotIds): bool
//     {
//         if (count($slotIds) <= 1) return true;

//         $slots = $this->timeslots->whereIn('id', $slotIds)->sortBy('start_time');

//         for ($i = 1; $i < $slots->count(); $i++) {
//             $current = $slots[$i];
//             $previous = $slots[$i - 1];

//             if ($current->day != $previous->day || $current->start_time != $previous->end_time) {
//                 return false;
//             }
//         }

//         return true;
//     }

//     private function updateResourceCache(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId): void
//     {
//         foreach ($slotIds as $slotId) {
//             $this->resourceUsageCache[$slotId]['instructors'][$instructorId] = true;
//             $this->resourceUsageCache[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupId) {
//                 $this->resourceUsageCache[$slotId]['student_groups'][$studentGroupId] = true;
//             }
//         }
//     }

//     private function getRandomInstructorForSection(Section $section)
//     {
//         $subject = optional($section->planSubject)->subject;
//         if (!$subject) return $this->instructors->random();

//         $suitableInstructors = $this->instructors->filter(fn ($instructor) =>
//             $instructor->subjects->contains($subject->id));

//         return $suitableInstructors->isNotEmpty() ? $suitableInstructors->random() : $this->instructors->random();
//     }

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

//     private function evaluateFitness(Collection $chromosomes)
//     {
//         // تحسين الأداء: تحميل جميع الجينات مرة واحدة
//         $chromosomeIds = $chromosomes->pluck('chromosome_id');
//         $allGenes = Gene::whereIn('chromosome_id', $chromosomeIds)
//             ->with(['instructor', 'room', 'section.planSubject.subject'])
//             ->get()
//             ->groupBy('chromosome_id');

//         foreach ($chromosomes as $chromosome) {
//             if (!isset($allGenes[$chromosome->chromosome_id])) {
//                 continue;
//             }

//             $genes = $allGenes[$chromosome->chromosome_id];
//             $totalPenalty = 0;

//             // إعادة تعيين الخريطة لكل كروموسوم
//             $resourceUsageMap = [];
//             $studentGroupUsage = [];
//             $instructorUsage = [];

//             // التحقق من تعارضات البلوكات
//             foreach ($genes as $gene) {
//                 $timeslotIds = is_array($gene->timeslot_ids) ? $gene->timeslot_ids : json_decode($gene->timeslot_ids, true);
//                 if (!$timeslotIds) continue;

//                 $instructorId = $gene->instructor_id;
//                 $roomId = $gene->room_id;
//                 $sectionId = $gene->section_id;
//                 $studentGroupId = $gene->student_group_id;
//                 $blockType = $gene->block_type;
//                 $isContinuous = $gene->is_continuous;

//                 // عقوبة على عدم استمرارية البلوكات
//                 if (!$isContinuous && count($timeslotIds) > 1) {
//                     $totalPenalty += $blockType == 'Practical' ? 1000 : 300;
//                 }

//                 // التحقق من تعارضات المدرس في جميع فترات البلوك
//                 foreach ($timeslotIds as $timeslotId) {
//                     if (isset($instructorUsage[$instructorId][$timeslotId])) {
//                         $totalPenalty += 1000;
//                     }
//                     $instructorUsage[$instructorId][$timeslotId] = true;
//                 }

//                 // التحقق من تعارضات القاعة في جميع فترات البلوك
//                 foreach ($timeslotIds as $timeslotId) {
//                     if (isset($resourceUsageMap[$timeslotId]['rooms'][$roomId])) {
//                         $totalPenalty += 800;
//                     }
//                 }

//                 // التحقق من تعارضات مجموعات الطلاب (أهم عقوبة)
//                 if ($studentGroupId) {
//                     foreach ($timeslotIds as $timeslotId) {
//                         if (isset($studentGroupUsage[$studentGroupId][$timeslotId])) {
//                             $totalPenalty += 2000;
//                         }
//                         $studentGroupUsage[$studentGroupId][$timeslotId] = true;
//                     }
//                 }

//                 // تحديث خريطة الموارد
//                 foreach ($timeslotIds as $timeslotId) {
//                     $resourceUsageMap[$timeslotId]['rooms'][$roomId] = true;
//                 }
//             }

//             // التحقق من القيود الإضافية
//             $totalPenalty += $this->checkAdditionalConstraints($genes);

//             // تحديث العقوبة في قاعدة البيانات
//             DB::table('chromosomes')
//                 ->where('chromosome_id', $chromosome->chromosome_id)
//                 ->update(['penalty_value' => $totalPenalty]);

//             $chromosome->penalty_value = $totalPenalty;
//         }
//     }

//     private function checkAdditionalConstraints(Collection $genes): int
//     {
//         $penalty = 0;

//         foreach ($genes as $gene) {
//             if (!$gene->section || !$gene->room || !$gene->instructor) {
//                 continue;
//             }

//             // التحقق من سعة القاعة
//             if ($gene->section->student_count > $gene->room->room_size) {
//                 $penalty += 800;
//             }

//             // التحقق من نوع القاعة
//             $roomType = optional($gene->room->roomType)->room_type_name ?? '';
//             $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);

//             if ($gene->block_type == 'Practical' && !$isPracticalRoom) {
//                 $penalty += 600;
//             }
//             if ($gene->block_type == 'Theory' && $isPracticalRoom) {
//                 $penalty += 600;
//             }

//             // التحقق من أهلية المدرس
//             if (!optional($gene->instructor)->canTeach(
//                 optional($gene->section->planSubject)->subject
//             )) {
//                 $penalty += 2000;
//             }
//         }

//         return $penalty;
//     }

//     private function selectParents(Collection $population): array
//     {
//         $parents = [];
//         $tournamentSize = 3;

//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $participants = $population->random($tournamentSize);
//             $parents[] = $participants->sortBy('penalty_value')->first();
//         }

//         return $parents;
//     }

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

//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Lectures = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Lectures = $parent2->genes()->get()->keyBy('lecture_unique_id');
//         $child1Lectures = [];
//         $child2Lectures = [];

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$lectureBlock->unique_id] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Lectures->get($lectureBlock->unique_id);
//             $child2Lectures[$lectureBlock->unique_id] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Lectures->get($lectureBlock->unique_id);

//             $currentIndex++;
//         }

//         return [$child1Lectures, $child2Lectures];
//     }

//     private function performMutation(array $lectures): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($lectures)) return [];

//             $lectureKeyToMutate = array_rand($lectures);
//             $originalLectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $lectureKeyToMutate);

//             if (!$originalLectureBlock) return $lectures;

//             // الحفاظ على نفس المدرس للمادة
//             $instructorId = $this->instructorAssignmentMap[$originalLectureBlock->section->planSubject->subject_id];
//             $newRoom = $this->getRandomRoomForSection($originalLectureBlock->section);
//             $studentGroupId = $this->studentGroupMap[$originalLectureBlock->section->id] ?? null;

//             // إعادة تعيين خريطة الموارد المؤقتة
//             $tempResourceMap = [];

//             // إيجاد فترات زمنية جديدة
//             $newTimeslots = $this->findOptimalSlotForBlock($originalLectureBlock, $instructorId, $newRoom->id, $studentGroupId);

//             $mutatedGene = new Gene([
//                 'lecture_unique_id' => $lectureKeyToMutate,
//                 'section_id' => $originalLectureBlock->section->id,
//                 'instructor_id' => $instructorId,
//                 'room_id' => $newRoom->id,
//                 'timeslot_ids' => json_encode($newTimeslots),
//                 'block_type' => $originalLectureBlock->block_type,
//                 'block_duration' => $originalLectureBlock->slots_needed,
//                 'is_continuous' => $this->areSlotsConsecutive($newTimeslots),
//                 'student_group_id' => $studentGroupId,
//             ]);

//             $lectures[$lectureKeyToMutate] = $mutatedGene;
//         }

//         return $lectures;
//     }

//     private function saveChildChromosome(array $lectures, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create([
//             'population_id' => $this->populationRun->population_id,
//             'penalty_value' => -1,
//             'generation_number' => $generationNumber
//         ]);

//         $genesToInsert = [];
//         foreach ($lectures as $lectureGenes) {
//             if (is_null($lectureGenes)) continue;

//             $timeslotIds = is_array($lectureGenes->timeslot_ids) ?
//                 $lectureGenes->timeslot_ids :
//                 json_decode($lectureGenes->timeslot_ids, true);

//             $genesToInsert[] = [
//                 'chromosome_id' => $chromosome->chromosome_id,
//                 'lecture_unique_id' => $lectureGenes->lecture_unique_id,
//                 'section_id' => $lectureGenes->section_id,
//                 'instructor_id' => $lectureGenes->instructor_id,
//                 'room_id' => $lectureGenes->room_id,
//                 'timeslot_ids' => json_encode($timeslotIds),
//                 'block_type' => $lectureGenes->block_type,
//                 'block_duration' => $lectureGenes->block_duration,
//                 'is_continuous' => $lectureGenes->is_continuous,
//                 'student_group_id' => $lectureGenes->student_group_id,
//             ];
//         }

//         foreach (array_chunk($genesToInsert, 500) as $chunk) {
//             Gene::insert($chunk);
//         }

//         return $chromosome;
//     }
// }

// ****************************************** deepseek ai *********************************

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
//     // الخصائص الأساسية للخوارزمية
//     private array $settings;
//     private Population $populationRun;
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;
//     private Collection $timeslots;
//     private Collection $lectureBlocksToSchedule;
//     private array $consecutiveTimeslotsMap = [];
//     private array $studentGroupMap = [];
//     private array $instructorAssignmentMap = [];
//     private array $resourceUsageCache = [];

//     /**
//      * Constructor - تهيئة الخدمة
//      */
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     /**
//      * Main execution flow - التسلسل الرئيسي للتنفيذ
//      * 1. __construct()
//      * 2. -> run()
//      *    -> loadAndPrepareData()
//      *      -> splitTheoryBlocks()
//      *      -> splitPracticalBlocks()
//      *      -> buildStudentGroupMap()
//      *      -> buildInstructorAssignmentMap()
//      *      -> buildConsecutiveTimeslotsMap()
//      *    -> createInitialPopulation()
//      *      -> findOptimalSlotForBlock()
//      *        -> countConflictsForSlots()
//      *        -> findFallbackSlot()
//      *      -> areSlotsConsecutive()
//      *      -> updateResourceCache()
//      *    -> evaluateFitness()
//      *      -> checkAdditionalConstraints()
//      *    -> [Generations loop]
//      *      -> selectParents()
//      *      -> createNewGeneration()
//      *        -> performCrossover()
//      *        -> performMutation()
//      *          -> findOptimalSlotForBlock()
//      *        -> saveChildChromosome()
//      *      -> evaluateFitness()
//      */
//     public function run()
//     {
//         try {
//             $this->populationRun->update([
//                 'status' => 'running',
//                 'start_time' => now(),
//                 'best_penalty' => PHP_INT_MAX,
//                 'generations_without_improvement' => 0
//             ]);

//             // Step 1: تحميل وتحضير البيانات
//             $this->loadAndPrepareData();

//             // Step 2: إنشاء الجيل الأول
//             $currentGenerationNumber = 1;
//             $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
//             $this->evaluateFitness($currentPopulation);
//             Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//             // Step 3: حلقة التطور عبر الأجيال
//             $maxGenerations = $this->settings['max_generations'];
//             while ($currentGenerationNumber < $maxGenerations) {
//                 $bestInGen = $currentPopulation->sortBy('penalty_value')->first();

//                 // التحقق من وجود حل مثالي
//                 if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//                     Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//                     break;
//                 }

//                 // تحديث أفضل عقوبة
//                 if ($bestInGen->penalty_value < $this->populationRun->best_penalty) {
//                     $this->populationRun->update([
//                         'best_penalty' => $bestInGen->penalty_value,
//                         'generations_without_improvement' => 0
//                     ]);
//                 } else {
//                     $this->populationRun->increment('generations_without_improvement');
//                 }

//                 // Step 4: اختيار الآباء وإنشاء جيل جديد
//                 $parents = $this->selectParents($currentPopulation);
//                 $currentGenerationNumber++;
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//                 $this->evaluateFitness($currentPopulation);
//                 Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//             }

//             // Step 5: حفظ أفضل كروموسوم بعد الانتهاء
//             $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)
//                 ->orderBy('penalty_value', 'asc')
//                 ->first();

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
//      * تحميل جميع البيانات المطلوبة للجدولة
//      */
//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         // 1. تحميل الشعب والمواد
//         $sections = Section::with(['planSubject.subject', 'planSubject.plan'])
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])
//             ->get();

//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         // 2. تقسيم البلوكات النظرية والعملية
//         $this->lectureBlocksToSchedule = collect();
//         $theoryBlocks = collect();
//         $practicalBlocks = collect();

//         foreach ($sections as $section) {
//             /** @var Section $section */
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             if ($section->activity_type == 'Practical') {
//                 $totalHours = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//                 $this->splitPracticalBlocks($section, $totalHours, $practicalBlocks);
//             } else {
//                 $totalHours = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//                 $this->splitTheoryBlocks($section, $totalHours, $theoryBlocks);
//             }
//         }

//         // 3. دمج البلوكات (نظرية أولاً ثم عملية)
//         $this->lectureBlocksToSchedule = $theoryBlocks->merge($practicalBlocks);
//         if ($this->lectureBlocksToSchedule->isEmpty()) {
//             throw new Exception("No lecture blocks to schedule were found after processing credit hours.");
//         }

//         // 4. بناء خرائط التجميع
//         $this->buildStudentGroupMap($sections);
//         $this->buildInstructorAssignmentMap();

//         // 5. تحميل الموارد
//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) =>
//             $q->where('room_type_name', 'not like', '%Lab%')
//              ->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) =>
//             $q->where('room_type_name', 'like', '%Lab%')
//              ->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
//             ->orderBy('start_time')->get();

//         // 6. بناء خريطة الفترات المتتالية
//         $this->buildConsecutiveTimeslotsMap();

//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//     }

//     /**
//      * تقسيم البلوكات النظرية حسب الإعدادات
//      */
//     private function splitTheoryBlocks(Section $section, int $totalHours, Collection &$theoryBlocks)
//     {
//         $subject = $section->planSubject->subject;
//         $theoryHoursInPlan = $subject->theoretical_hours;
//         $multiplier = $this->settings['theory_credit_to_slots'];

//         $totalTheoryHours = $theoryHoursInPlan * $multiplier;
//         $remainingHours = $totalTheoryHours;
//         $blockCounter = 1;

//         // استراتيجية التقسيم الذكية للنظرية (تفضيل بلوكات 2 ساعات)
//         while ($remainingHours > 0) {
//             $blockHours = $this->determineOptimalBlockSize($remainingHours);

//             $theoryBlocks->push((object)[
//                 'section' => $section,
//                 'block_type' => 'theory',
//                 'total_hours' => $blockHours,
//                 'slots_needed' => $blockHours,
//                 'is_continuous' => true,
//                 'unique_id' => $section->id . '-theory-block' . $blockCounter++,
//                 'original_hours' => $theoryHoursInPlan
//             ]);

//             $remainingHours -= $blockHours;
//         }
//     }

//     /**
//      * تحديد حجم البلوك الأمثل للنظرية
//      */
//     private function determineOptimalBlockSize(int $remainingHours): int
//     {
//         // تفضيل بلوكات 2 ساعات إن أمكن
//         if ($remainingHours >= 2) {
//             return 2;
//         }
//         return 1;
//     }

//     /**
//      * تقسيم البلوكات العملية حسب الإعدادات
//      */
//     private function splitPracticalBlocks(Section $section, int $totalHours, Collection &$practicalBlocks)
//     {
//         $subject = $section->planSubject->subject;
//         $practicalHoursInPlan = $subject->practical_hours;
//         $multiplier = $this->settings['practical_credit_to_slots'];

//         // البلوكات العملية يجب أن تكون متصلة دائماً وبدون تقسيم
//         $totalPracticalHours = $practicalHoursInPlan * $multiplier;

//         $practicalBlocks->push((object)[
//             'section' => $section,
//             'block_type' => 'practical',
//             'total_hours' => $totalPracticalHours,
//             'slots_needed' => $totalPracticalHours,
//             'is_continuous' => true,
//             'unique_id' => $section->id . '-practical-block1',
//             'original_hours' => $practicalHoursInPlan
//         ]);
//     }

//     /**
//      * بناء خريطة مجموعات الطلاب
//      */
//     private function buildStudentGroupMap(Collection $sections)
//     {
//         $this->studentGroupMap = [];
//         $sectionsByContext = $sections->groupBy(function($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $contextKey => $sectionsInContext) {
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')
//                 ->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 // الشعب النظرية مشتركة بين جميع المجموعات
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id] = $groupIndex;
//                 }

//                 // الشعب العملية موزعة على المجموعات
//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')
//                     ->groupBy('plan_subject_id');

//                 foreach ($practicalSectionsBySubject as $subjectId => $sectionsForOneSubject) {
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
//      * بناء خريطة تعيين المدرسين
//      */
//     private function buildInstructorAssignmentMap()
//     {
//         foreach ($this->lectureBlocksToSchedule as $block) {
//             $subjectId = $block->section->planSubject->subject_id;

//             if (!isset($this->instructorAssignmentMap[$subjectId])) {
//                 $instructor = $this->getRandomInstructorForSection($block->section);
//                 $this->instructorAssignmentMap[$subjectId] = $instructor->id;

//                 // تعيين نفس المدرس لجميع بلوكات هذه المادة
//                 $relatedBlocks = $this->lectureBlocksToSchedule->filter(function($b) use ($subjectId) {
//                     return $b->section->planSubject->subject_id == $subjectId;
//                 });

//                 foreach ($relatedBlocks as $relatedBlock) {
//                     $this->instructorAssignmentMap[$subjectId] = $instructor->id;
//                 }
//             }
//         }
//     }

//     /**
//      * بناء خريطة الفترات المتتالية
//      */
//     private function buildConsecutiveTimeslotsMap()
//     {
//         $timeslotsByDay = $this->timeslots->groupBy('day');
//         $this->consecutiveTimeslotsMap = [];

//         foreach ($timeslotsByDay as $dayTimeslots) {
//             $dayTimeslotsValues = $dayTimeslots->sortBy('start_time')->values();
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
//      * إنشاء الجيل الأول من الكروموسومات
//      */
//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating intelligent initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = [
//                 'population_id' => $this->populationRun->population_id,
//                 'penalty_value' => -1,
//                 'generation_number' => $generationNumber,
//                 'created_at' => now(),
//                 'updated_at' => now()
//             ];
//         }

//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)
//             ->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $this->resourceUsageCache = [];
//             $genesToInsert = [];

//             foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//                 $instructorId = $this->instructorAssignmentMap[$lectureBlock->section->planSubject->subject_id];
//                 $room = $this->getRandomRoomForSection($lectureBlock->section);
//                 $studentGroupId = $this->studentGroupMap[$lectureBlock->section->id] ?? null;

//                 $foundSlots = $this->findOptimalSlotForBlock($lectureBlock, $instructorId, $room->id, $studentGroupId);

//                 $this->updateResourceCache($foundSlots, $instructorId, $room->id, $studentGroupId);

//                 $genesToInsert[] = [
//                     'chromosome_id' => $chromosome->chromosome_id,
//                     'lecture_unique_id' => $lectureBlock->unique_id,
//                     'section_id' => $lectureBlock->section->id,
//                     'instructor_id' => $instructorId,
//                     'room_id' => $room->id,
//                     'timeslot_ids' => json_encode($foundSlots),
//                     'block_type' => $lectureBlock->block_type,
//                     'block_duration' => $lectureBlock->slots_needed,
//                     'is_continuous' => $this->areSlotsConsecutive($foundSlots),
//                     'student_group_id' => $studentGroupId,
//                 ];
//             }

//             Gene::insert($genesToInsert);
//         }

//         return $createdChromosomes;
//     }

//     /**
//      * البحث عن أفضل وقت للبلوك
//      */
//     private function findOptimalSlotForBlock($lectureBlock, int $instructorId, int $roomId, ?int $studentGroupId): array
//     {
//         $requiredSlots = $lectureBlock->slots_needed;
//         $bestSlots = [];
//         $minConflicts = PHP_INT_MAX;

//         // البحث عن أفضل وقت مع الأولويات
//         foreach ($this->timeslots as $startSlot) {
//             $consecutiveSlots = $this->getConsecutiveSlots($startSlot->id, $requiredSlots);

//             if (count($consecutiveSlots) >= $requiredSlots) {
//                 $conflicts = $this->countConflictsForSlots($consecutiveSlots, $instructorId, $roomId, $studentGroupId);

//                 if ($conflicts == 0) {
//                     return array_slice($consecutiveSlots, 0, $requiredSlots);
//                 }

//                 if ($conflicts < $minConflicts) {
//                     $minConflicts = $conflicts;
//                     $bestSlots = array_slice($consecutiveSlots, 0, $requiredSlots);
//                 }
//             }
//         }

//         // إذا لم نجد وقتاً مثالياً، نبحث بأولويات مخففة
//         if (empty($bestSlots)) {
//             $bestSlots = $this->findFallbackSlot($lectureBlock, $instructorId, $roomId, $studentGroupId);
//         }

//         return $bestSlots;
//     }

//     /**
//      * الحصول على الفترات المتتالية
//      */
//     private function getConsecutiveSlots(int $startSlotId, int $requiredSlots): array
//     {
//         $slots = [$startSlotId];
//         $currentSlotId = $startSlotId;

//         for ($i = 1; $i < $requiredSlots; $i++) {
//             if (empty($this->consecutiveTimeslotsMap[$currentSlotId])) {
//                 break;
//             }
//             $currentSlotId = $this->consecutiveTimeslotsMap[$currentSlotId][0];
//             $slots[] = $currentSlotId;
//         }

//         return $slots;
//     }

//     /**
//      * البحث عن وقت بديل عند عدم وجود وقت مثالي
//      */
//     private function findFallbackSlot($lectureBlock, int $instructorId, int $roomId, ?int $studentGroupId): array
//     {
//         $requiredSlots = $lectureBlock->slots_needed;

//         // المحاولة الأولى: تجاهل تعارضات المدرس فقط
//         foreach ($this->timeslots as $startSlot) {
//             $consecutiveSlots = $this->getConsecutiveSlots($startSlot->id, $requiredSlots);
//             $conflicts = $this->countConflictsForSlots($consecutiveSlots, 0, $roomId, $studentGroupId);

//             if ($conflicts == 0) {
//                 return array_slice($consecutiveSlots, 0, $requiredSlots);
//             }
//         }

//         // المحاولة الثانية: تجاهل تعارضات المدرس والقاعة
//         foreach ($this->timeslots as $startSlot) {
//             $consecutiveSlots = $this->getConsecutiveSlots($startSlot->id, $requiredSlots);
//             $conflicts = $this->countConflictsForSlots($consecutiveSlots, 0, 0, $studentGroupId);

//             if ($conflicts == 0) {
//                 return array_slice($consecutiveSlots, 0, $requiredSlots);
//             }
//         }

//         // الحل الأخير: أي فترات متاحة
//         return $this->timeslots->random($requiredSlots)->pluck('id')->toArray();
//     }

//     /**
//      * حساب التعارضات للفترات الزمنية
//      */
//     private function countConflictsForSlots(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId): int
//     {
//         $conflicts = 0;

//         foreach ($slotIds as $slotId) {
//             if (isset($this->resourceUsageCache[$slotId])) {
//                 // التحقق من تعارضات مجموعات الطلاب (أهم عقوبة)
//                 if ($studentGroupId && isset($this->resourceUsageCache[$slotId]['student_groups'][$studentGroupId])) {
//                     $conflicts += 2000;
//                 }

//                 // التحقق من تعارضات المدرس
//                 if (isset($this->resourceUsageCache[$slotId]['instructors'][$instructorId])) {
//                     $conflicts += 1000;
//                 }

//                 // التحقق من تعارضات القاعة
//                 if (isset($this->resourceUsageCache[$slotId]['rooms'][$roomId])) {
//                     $conflicts += 800;
//                 }
//             }
//         }

//         return $conflicts;
//     }

//     /**
//      * التحقق من اتصال الفترات الزمنية
//      */
//     private function areSlotsConsecutive(array $slotIds): bool
//     {
//         if (count($slotIds) <= 1) return true;

//         $slots = $this->timeslots->whereIn('id', $slotIds)->sortBy('start_time');

//         for ($i = 1; $i < $slots->count(); $i++) {
//             $current = $slots[$i];
//             $previous = $slots[$i - 1];

//             if ($current->day != $previous->day || $current->start_time != $previous->end_time) {
//                 return false;
//             }
//         }

//         return true;
//     }

//     /**
//      * تحديث خريطة استخدام الموارد
//      */
//     private function updateResourceCache(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId): void
//     {
//         foreach ($slotIds as $slotId) {
//             $this->resourceUsageCache[$slotId]['instructors'][$instructorId] = true;
//             $this->resourceUsageCache[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupId) {
//                 $this->resourceUsageCache[$slotId]['student_groups'][$studentGroupId] = true;
//             }
//         }
//     }

//     /**
//      * اختيار مدرس عشوائي للشعبة
//      */
//     private function getRandomInstructorForSection(Section $section)
//     {
//         $subject = optional($section->planSubject)->subject;
//         if (!$subject) return $this->instructors->random();

//         $suitableInstructors = $this->instructors->filter(fn ($instructor) =>
//             $instructor->subjects->contains($subject->id));

//         return $suitableInstructors->isNotEmpty() ? $suitableInstructors->random() : $this->instructors->random();
//     }

//     /**
//      * اختيار قاعة عشوائية للشعبة
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
//      * تقييم لياقة الكروموسومات
//      */
//     private function evaluateFitness(Collection $chromosomes)
//     {
//         $allGenes = Gene::whereIn('chromosome_id', $chromosomes->pluck('chromosome_id'))
//             ->with(['instructor', 'room', 'section.planSubject.subject'])
//             ->get()
//             ->groupBy('chromosome_id');

//         foreach ($chromosomes as $chromosome) {
//             if (!isset($allGenes[$chromosome->chromosome_id])) {
//                 continue;
//             }

//             $genes = $allGenes[$chromosome->chromosome_id];
//             $totalPenalty = 0;
//             $resourceUsageMap = [];
//             $studentGroupUsage = [];
//             $instructorUsage = [];

//             foreach ($genes as $gene) {
//                 $timeslotIds = json_decode($gene->timeslot_ids, true) ?? [];

//                 // التحقق من التعارضات
//                 foreach ($timeslotIds as $timeslotId) {
//                     // تعارضات المدرس
//                     if (isset($instructorUsage[$gene->instructor_id][$timeslotId])) {
//                         $totalPenalty += 1000;
//                     }
//                     $instructorUsage[$gene->instructor_id][$timeslotId] = true;

//                     // تعارضات القاعة
//                     if (isset($resourceUsageMap[$timeslotId]['rooms'][$gene->room_id])) {
//                         $totalPenalty += 800;
//                     }

//                     // تعارضات مجموعات الطلاب
//                     if ($gene->student_group_id) {
//                         if (isset($studentGroupUsage[$gene->student_group_id][$timeslotId])) {
//                             $totalPenalty += 2000;
//                         }
//                         $studentGroupUsage[$gene->student_group_id][$timeslotId] = true;
//                     }
//                 }

//                 // تحديث خريطة الموارد
//                 foreach ($timeslotIds as $timeslotId) {
//                     $resourceUsageMap[$timeslotId]['rooms'][$gene->room_id] = true;
//                 }

//                 // التحقق من القيود الإضافية
//                 $totalPenalty += $this->checkAdditionalConstraints($gene);
//             }

//             $chromosome->update(['penalty_value' => $totalPenalty]);
//         }
//     }

//     /**
//      * التحقق من القيود الإضافية
//      */
//     private function checkAdditionalConstraints(Gene $gene): int
//     {
//         $penalty = 0;

//         if (!$gene->section || !$gene->room || !$gene->instructor) {
//             return 0;
//         }

//         // التحقق من سعة القاعة
//         if ($gene->section->student_count > $gene->room->room_size) {
//             $penalty += 800;
//         }

//         // التحقق من نوع القاعة
//         $roomType = optional($gene->room->roomType)->room_type_name ?? '';
//         $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);

//         if ($gene->block_type == 'Practical' && !$isPracticalRoom) {
//             $penalty += 600;
//         }
//         if ($gene->block_type == 'Theory' && $isPracticalRoom) {
//             $penalty += 600;
//         }

//         return $penalty;
//     }

//     /**
//      * اختيار الآباء للتكاثر
//      */
//     private function selectParents(Collection $population): array
//     {
//         $parents = [];
//         $population = $population->sortBy('penalty_value');
//         $totalFitness = $population->sum(fn($c) => 1 / (1 + $c->penalty_value));

//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $random = mt_rand() / mt_getrandmax() * $totalFitness;
//             $sum = 0;

//             foreach ($population as $chromosome) {
//                 $sum += 1 / (1 + $chromosome->penalty_value);
//                 if ($sum >= $random) {
//                     $parents[] = $chromosome;
//                     break;
//                 }
//             }
//         }

//         return $parents;
//     }

//     /**
//      * إنشاء جيل جديد
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

//         $newChromosomes = collect();
//         foreach ($childrenData as $lecturesToInsert) {
//             $newChromosomes->push($this->saveChildChromosome($lecturesToInsert, $nextGenerationNumber));
//         }

//         return $newChromosomes;
//     }

//     /**
//      * تنفيذ التكاثر بين أبوين
//      */
//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Lectures = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Lectures = $parent2->genes()->get()->keyBy('lecture_unique_id');
//         $child1Lectures = [];
//         $child2Lectures = [];

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$lectureBlock->unique_id] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Lectures->get($lectureBlock->unique_id);
//             $child2Lectures[$lectureBlock->unique_id] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Lectures->get($lectureBlock->unique_id);

//             $currentIndex++;
//         }

//         return [$child1Lectures, $child2Lectures];
//     }

//     /**
//      * تطبيق الطفرات
//      */
//     private function performMutation(array $lectures): array
//     {
//         $mutationRate = $this->populationRun->generations_without_improvement > 3
//             ? $this->settings['mutation_rate'] * 1.5
//             : $this->settings['mutation_rate'];

//         if (lcg_value() < $mutationRate && !empty($lectures)) {
//             $lectureKeyToMutate = array_rand($lectures);
//             $originalLectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $lectureKeyToMutate);

//             if ($originalLectureBlock) {
//                 $instructorId = $this->instructorAssignmentMap[$originalLectureBlock->section->planSubject->subject_id];
//                 $newRoom = $this->getRandomRoomForSection($originalLectureBlock->section);
//                 $studentGroupId = $this->studentGroupMap[$originalLectureBlock->section->id] ?? null;

//                 $newTimeslots = $this->findOptimalSlotForBlock($originalLectureBlock, $instructorId, $newRoom->id, $studentGroupId);

//                 $lectures[$lectureKeyToMutate] = new Gene([
//                     'lecture_unique_id' => $lectureKeyToMutate,
//                     'section_id' => $originalLectureBlock->section->id,
//                     'instructor_id' => $instructorId,
//                     'room_id' => $newRoom->id,
//                     'timeslot_ids' => json_encode($newTimeslots),
//                     'block_type' => $originalLectureBlock->block_type,
//                     'block_duration' => $originalLectureBlock->slots_needed,
//                     'is_continuous' => $this->areSlotsConsecutive($newTimeslots),
//                     'student_group_id' => $studentGroupId,
//                 ]);
//             }
//         }

//         return $lectures;
//     }

//     /**
//      * حفظ الكروموسوم الجديد
//      */
//     private function saveChildChromosome(array $lectures, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create([
//             'population_id' => $this->populationRun->population_id,
//             'penalty_value' => -1,
//             'generation_number' => $generationNumber
//         ]);

//         $genesToInsert = [];
//         foreach ($lectures as $lectureGenes) {
//             if (!$lectureGenes) continue;

//             $timeslotIds = is_array($lectureGenes->timeslot_ids)
//                 ? $lectureGenes->timeslot_ids
//                 : json_decode($lectureGenes->timeslot_ids, true);

//             $genesToInsert[] = [
//                 'chromosome_id' => $chromosome->chromosome_id,
//                 'lecture_unique_id' => $lectureGenes->lecture_unique_id,
//                 'section_id' => $lectureGenes->section_id,
//                 'instructor_id' => $lectureGenes->instructor_id,
//                 'room_id' => $lectureGenes->room_id,
//                 'timeslot_ids' => json_encode($timeslotIds),
//                 'block_type' => $lectureGenes->block_type,
//                 'block_duration' => $lectureGenes->block_duration,
//                 'is_continuous' => $lectureGenes->is_continuous,
//                 'student_group_id' => $lectureGenes->student_group_id,
//             ];
//         }

//         Gene::insert($genesToInsert);
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
//     private array $settings;
//     private Population $populationRun;
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;
//     private Collection $timeslots;
//     private Collection $lectureBlocksToSchedule;
//     private array $consecutiveTimeslotsMap = [];
//     private array $studentGroupMap = [];
//     private array $instructorAssignmentMap = [];
//     private array $resourceUsageCache = [];

//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     public function run()
//     {
//         try {
//             $this->populationRun->update([
//                 'status' => 'running',
//                 'start_time' => now(),
//                 'best_penalty' => PHP_INT_MAX,
//                 'generations_without_improvement' => 0
//             ]);

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

//                 if ($bestInGen->penalty_value < $this->populationRun->best_penalty) {
//                     $this->populationRun->update([
//                         'best_penalty' => $bestInGen->penalty_value,
//                         'generations_without_improvement' => 0
//                     ]);
//                 } else {
//                     $this->populationRun->increment('generations_without_improvement');
//                 }

//                 $parents = $this->selectParents($currentPopulation);
//                 $currentGenerationNumber++;
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//                 $this->evaluateFitness($currentPopulation);
//                 Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//             }

//             $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)
//                 ->orderBy('penalty_value', 'asc')
//                 ->first();

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

//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with(['planSubject.subject', 'planSubject.plan'])
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])
//             ->get();

//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         $this->lectureBlocksToSchedule = collect();
//         $theoryBlocks = collect();
//         $practicalBlocks = collect();

//         foreach ($sections as $sectionItem) {
//             /** @var Section $section */
//             $section = $sectionItem;
//             $subject = optional($section->planSubject)->subject;
//             if (!$subject) continue;

//             if ($section->activity_type == 'Practical') {
//                 $totalHours = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//                 $this->splitPracticalBlocks($section, $totalHours, $practicalBlocks);
//             } else {
//                 $totalHours = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//                 $this->splitTheoryBlocks($section, $totalHours, $theoryBlocks);
//             }
//         }

//         $this->lectureBlocksToSchedule = $theoryBlocks->merge($practicalBlocks);
//         if ($this->lectureBlocksToSchedule->isEmpty()) {
//             throw new Exception("No lecture blocks to schedule were found after processing credit hours.");
//         }

//         $this->buildStudentGroupMap($sections);
//         $this->buildInstructorAssignmentMap();

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) =>
//             $q->where('room_type_name', 'not like', '%Lab%')
//              ->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) =>
//             $q->where('room_type_name', 'like', '%Lab%')
//              ->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
//             ->orderBy('start_time')->get();

//         $this->buildConsecutiveTimeslotsMap();

//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//     }

//     private function splitTheoryBlocks(Section $section, int $totalHours, Collection &$theoryBlocks)
//     {
//         $subject = $section->planSubject->subject;
//         $theoryHoursInPlan = $subject->theoretical_hours;
//         $multiplier = $this->settings['theory_credit_to_slots'];

//         $totalTheoryHours = $theoryHoursInPlan * $multiplier;
//         $remainingHours = $totalTheoryHours;
//         $blockCounter = 1;

//         while ($remainingHours > 0) {
//             $blockHours = $this->determineOptimalBlockSize($remainingHours);

//             $theoryBlocks->push((object)[
//                 'section' => $section,
//                 'block_type' => 'theory',
//                 'total_hours' => $blockHours,
//                 'slots_needed' => $blockHours,
//                 'is_continuous' => true,
//                 'unique_id' => $section->id . '-theory-block' . $blockCounter++,
//                 'original_hours' => $theoryHoursInPlan
//             ]);

//             $remainingHours -= $blockHours;
//         }
//     }

//     private function determineOptimalBlockSize(int $remainingHours): int
//     {
//         if ($remainingHours >= 2) {
//             return 2;
//         }
//         return 1;
//     }

//     private function splitPracticalBlocks(Section $section, int $totalHours, Collection &$practicalBlocks)
//     {
//         $subject = $section->planSubject->subject;
//         $practicalHoursInPlan = $subject->practical_hours;
//         $multiplier = $this->settings['practical_credit_to_slots'];

//         if ($practicalHoursInPlan <= 0) return;

//         $totalPracticalHours = $practicalHoursInPlan * $multiplier;

//         if ($totalPracticalHours > 4) {
//             $firstBlockHours = ceil($totalPracticalHours / 2);
//             $secondBlockHours = $totalPracticalHours - $firstBlockHours;

//             $practicalBlocks->push((object)[
//                 'section' => $section,
//                 'block_type' => 'practical',
//                 'total_hours' => $firstBlockHours,
//                 'slots_needed' => $firstBlockHours,
//                 'is_continuous' => true,
//                 'unique_id' => $section->id . '-practical-block1',
//                 'original_hours' => $practicalHoursInPlan
//             ]);

//             $practicalBlocks->push((object)[
//                 'section' => $section,
//                 'block_type' => 'practical',
//                 'total_hours' => $secondBlockHours,
//                 'slots_needed' => $secondBlockHours,
//                 'is_continuous' => true,
//                 'unique_id' => $section->id . '-practical-block2',
//                 'original_hours' => $practicalHoursInPlan
//             ]);
//         } else {
//             $practicalBlocks->push((object)[
//                 'section' => $section,
//                 'block_type' => 'practical',
//                 'total_hours' => $totalPracticalHours,
//                 'slots_needed' => $totalPracticalHours,
//                 'is_continuous' => true,
//                 'unique_id' => $section->id . '-practical-block1',
//                 'original_hours' => $practicalHoursInPlan
//             ]);
//         }
//     }

//     private function buildStudentGroupMap(Collection $sections)
//     {
//         $this->studentGroupMap = [];
//         $sectionsByContext = $sections->groupBy(function($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $contextKey => $sectionsInContext) {
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')
//                 ->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id] = $groupIndex;
//                 }

//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')
//                     ->groupBy('plan_subject_id');

//                 foreach ($practicalSectionsBySubject as $subjectId => $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
//                     }
//                 }
//             }
//         }
//     }

//     private function buildInstructorAssignmentMap()
//     {
//         foreach ($this->lectureBlocksToSchedule as $block) {
//             $subjectId = $block->section->planSubject->subject_id;

//             if (!isset($this->instructorAssignmentMap[$subjectId])) {
//                 $instructor = $this->getRandomInstructorForSection($block->section);
//                 $this->instructorAssignmentMap[$subjectId] = $instructor->id;

//                 $relatedBlocks = $this->lectureBlocksToSchedule->filter(function($b) use ($subjectId) {
//                     return $b->section->planSubject->subject_id == $subjectId;
//                 });

//                 foreach ($relatedBlocks as $relatedBlock) {
//                     $this->instructorAssignmentMap[$subjectId] = $instructor->id;
//                 }
//             }
//         }
//     }

//     private function buildConsecutiveTimeslotsMap()
//     {
//         $timeslotsByDay = $this->timeslots->groupBy('day');
//         $this->consecutiveTimeslotsMap = [];

//         foreach ($timeslotsByDay as $dayTimeslots) {
//             $dayTimeslotsValues = $dayTimeslots->sortBy('start_time')->values();
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

//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating intelligent initial population (Generation #{$generationNumber})");

//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = [
//                 'population_id' => $this->populationRun->population_id,
//                 'penalty_value' => -1,
//                 'generation_number' => $generationNumber,
//                 'created_at' => now(),
//                 'updated_at' => now()
//             ];
//         }

//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)
//             ->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $this->resourceUsageCache = [];
//             $genesToInsert = [];

//             foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//                 $instructorId = $this->instructorAssignmentMap[$lectureBlock->section->planSubject->subject_id];
//                 $room = $this->getRandomRoomForSection($lectureBlock->section);
//                 $studentGroupId = $this->studentGroupMap[$lectureBlock->section->id] ?? null;

//                 $foundSlots = $this->findOptimalSlotForBlock($lectureBlock, $instructorId, $room->id, $studentGroupId);

//                 $this->updateResourceCache($foundSlots, $instructorId, $room->id, $studentGroupId);

//                 $genesToInsert[] = [
//                     'chromosome_id' => $chromosome->chromosome_id,
//                     'lecture_unique_id' => $lectureBlock->unique_id,
//                     'section_id' => $lectureBlock->section->id,
//                     'instructor_id' => $instructorId,
//                     'room_id' => $room->id,
//                     'timeslot_ids' => json_encode($foundSlots),
//                     'block_type' => $lectureBlock->block_type,
//                     'block_duration' => $lectureBlock->slots_needed,
//                     'is_continuous' => $this->areSlotsConsecutive($foundSlots),
//                     'student_group_id' => $studentGroupId,
//                 ];
//             }

//             Gene::insert($genesToInsert);
//         }

//         return $createdChromosomes;
//     }

//     private function findOptimalSlotForBlock($lectureBlock, int $instructorId, int $roomId, ?int $studentGroupId): array
//     {
//         $requiredSlots = $lectureBlock->slots_needed;
//         $bestSlots = [];
//         $minConflicts = PHP_INT_MAX;

//         foreach ($this->timeslots as $startSlot) {
//             $consecutiveSlots = $this->getConsecutiveSlots($startSlot->id, $requiredSlots);

//             if (count($consecutiveSlots) >= $requiredSlots) {
//                 $conflicts = $this->countConflictsForSlots($consecutiveSlots, $instructorId, $roomId, $studentGroupId);

//                 if ($conflicts == 0) {
//                     return array_slice($consecutiveSlots, 0, $requiredSlots);
//                 }

//                 if ($conflicts < $minConflicts) {
//                     $minConflicts = $conflicts;
//                     $bestSlots = array_slice($consecutiveSlots, 0, $requiredSlots);
//                 }
//             }
//         }

//         if (empty($bestSlots)) {
//             $bestSlots = $this->findFallbackSlot($lectureBlock, $instructorId, $roomId, $studentGroupId);
//         }

//         return $bestSlots;
//     }

//     private function getConsecutiveSlots(int $startSlotId, int $requiredSlots): array
//     {
//         $slots = [$startSlotId];
//         $currentSlotId = $startSlotId;

//         for ($i = 1; $i < $requiredSlots; $i++) {
//             if (empty($this->consecutiveTimeslotsMap[$currentSlotId])) {
//                 break;
//             }
//             $currentSlotId = $this->consecutiveTimeslotsMap[$currentSlotId][0];
//             $slots[] = $currentSlotId;
//         }

//         return $slots;
//     }

//     private function findFallbackSlot($lectureBlock, int $instructorId, int $roomId, ?int $studentGroupId): array
//     {
//         $requiredSlots = $lectureBlock->slots_needed;

//         foreach ($this->timeslots as $startSlot) {
//             $consecutiveSlots = $this->getConsecutiveSlots($startSlot->id, $requiredSlots);
//             $conflicts = $this->countConflictsForSlots($consecutiveSlots, 0, $roomId, $studentGroupId);

//             if ($conflicts == 0) {
//                 return array_slice($consecutiveSlots, 0, $requiredSlots);
//             }
//         }

//         foreach ($this->timeslots as $startSlot) {
//             $consecutiveSlots = $this->getConsecutiveSlots($startSlot->id, $requiredSlots);
//             $conflicts = $this->countConflictsForSlots($consecutiveSlots, 0, 0, $studentGroupId);

//             if ($conflicts == 0) {
//                 return array_slice($consecutiveSlots, 0, $requiredSlots);
//             }
//         }

//         return $this->timeslots->random($requiredSlots)->pluck('id')->toArray();
//     }

//     private function countConflictsForSlots(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId): int
//     {
//         $conflicts = 0;

//         foreach ($slotIds as $slotId) {
//             if (isset($this->resourceUsageCache[$slotId])) {
//                 if ($studentGroupId && isset($this->resourceUsageCache[$slotId]['student_groups'][$studentGroupId])) {
//                     $conflicts += 2000;
//                 }

//                 if (isset($this->resourceUsageCache[$slotId]['instructors'][$instructorId])) {
//                     $conflicts += 1000;
//                 }

//                 if (isset($this->resourceUsageCache[$slotId]['rooms'][$roomId])) {
//                     $conflicts += 800;
//                 }
//             }
//         }

//         return $conflicts;
//     }

//     private function areSlotsConsecutive(array $slotIds): bool
//     {
//         if (count($slotIds) <= 1) return true;

//         $slots = $this->timeslots->whereIn('id', $slotIds)->sortBy('start_time');

//         for ($i = 1; $i < $slots->count(); $i++) {
//             $current = $slots[$i];
//             $previous = $slots[$i - 1];

//             if ($current->day != $previous->day || $current->start_time != $previous->end_time) {
//                 return false;
//             }
//         }

//         return true;
//     }

//     private function updateResourceCache(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId): void
//     {
//         foreach ($slotIds as $slotId) {
//             $this->resourceUsageCache[$slotId]['instructors'][$instructorId] = true;
//             $this->resourceUsageCache[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupId) {
//                 $this->resourceUsageCache[$slotId]['student_groups'][$studentGroupId] = true;
//             }
//         }
//     }

//     private function getRandomInstructorForSection(Section $section)
//     {
//         $subject = optional($section->planSubject)->subject;
//         if (!$subject) return $this->instructors->random();

//         $suitableInstructors = $this->instructors->filter(fn ($instructor) =>
//             $instructor->subjects->contains($subject->id));

//         return $suitableInstructors->isNotEmpty() ? $suitableInstructors->random() : $this->instructors->random();
//     }

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

//     private function evaluateFitness(Collection $chromosomes)
//     {
//         $allGenes = Gene::whereIn('chromosome_id', $chromosomes->pluck('chromosome_id'))
//             ->with(['instructor', 'room', 'section.planSubject.subject'])
//             ->get()
//             ->groupBy('chromosome_id');

//         foreach ($chromosomes as $chromosome) {
//             if (!isset($allGenes[$chromosome->chromosome_id])) {
//                 continue;
//             }

//             $genes = $allGenes[$chromosome->chromosome_id];
//             $totalPenalty = 0;
//             $resourceUsageMap = [];
//             $studentGroupUsage = [];
//             $instructorUsage = [];

//             foreach ($genes as $gene) {
//                 $timeslotIds = json_decode($gene->timeslot_ids, true) ?? [];

//                 foreach ($timeslotIds as $timeslotId) {
//                     if (isset($instructorUsage[$gene->instructor_id][$timeslotId])) {
//                         $totalPenalty += 1000;
//                     }

//                     if (isset($resourceUsageMap[$timeslotId]['rooms'][$gene->room_id])) {
//                         $totalPenalty += 800;
//                     }

//                     if ($gene->student_group_id) {
//                         if (isset($studentGroupUsage[$gene->student_group_id][$timeslotId])) {
//                             $totalPenalty += 2000;
//                         }
//                     }
//                 }

//                 foreach ($timeslotIds as $timeslotId) {
//                     $instructorUsage[$gene->instructor_id][$timeslotId] = true;
//                     $resourceUsageMap[$timeslotId]['rooms'][$gene->room_id] = true;

//                     if ($gene->student_group_id) {
//                         $studentGroupUsage[$gene->student_group_id][$timeslotId] = true;
//                     }
//                 }

//                 $totalPenalty += $this->checkAdditionalConstraints($gene);
//             }

//             $chromosome->update(['penalty_value' => $totalPenalty]);
//         }
//     }

//     private function checkAdditionalConstraints(Gene $gene): int
//     {
//         $penalty = 0;

//         if (!$gene->section || !$gene->room || !$gene->instructor) {
//             return 0;
//         }

//         $capacityDifference = $gene->section->student_count - $gene->room->room_size;
//         if ($capacityDifference > 0) {
//             $penalty += 800 + ($capacityDifference * 10);
//         }

//         $roomType = optional($gene->room->roomType)->room_type_name ?? '';
//         $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);

//         if ($gene->block_type == 'Practical' && !$isPracticalRoom) {
//             $penalty += 1000;
//         }

//         if ($gene->block_type == 'Theory' && $isPracticalRoom) {
//             $penalty += 600;
//         }

//         if ($gene->block_type == 'Practical' && !$gene->is_continuous) {
//             $penalty += 1500;
//         }

//         return $penalty;
//     }

//     private function selectParents(Collection $population): array
//     {
//         $parents = [];
//         $population = $population->sortBy('penalty_value');
//         $totalFitness = $population->sum(fn($c) => 1 / (1 + $c->penalty_value));

//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $random = mt_rand() / mt_getrandmax() * $totalFitness;
//             $sum = 0;

//             foreach ($population as $chromosome) {
//                 $sum += 1 / (1 + $chromosome->penalty_value);
//                 if ($sum >= $random) {
//                     $parents[] = $chromosome;
//                     break;
//                 }
//             }
//         }

//         return $parents;
//     }

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

//         $newChromosomes = collect();
//         foreach ($childrenData as $lecturesToInsert) {
//             $newChromosomes->push($this->saveChildChromosome($lecturesToInsert, $nextGenerationNumber));
//         }

//         return $newChromosomes;
//     }

//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Lectures = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Lectures = $parent2->genes()->get()->keyBy('lecture_unique_id');
//         $child1Lectures = [];
//         $child2Lectures = [];

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$lectureBlock->unique_id] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Lectures->get($lectureBlock->unique_id);
//             $child2Lectures[$lectureBlock->unique_id] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Lectures->get($lectureBlock->unique_id);

//             $currentIndex++;
//         }

//         return [$child1Lectures, $child2Lectures];
//     }

//     private function performMutation(array $lectures): array
//     {
//         $mutationRate = $this->populationRun->generations_without_improvement > 3
//             ? $this->settings['mutation_rate'] * 1.5
//             : $this->settings['mutation_rate'];

//         if (lcg_value() < $mutationRate && !empty($lectures)) {
//             $lectureKeyToMutate = array_rand($lectures);
//             $originalLectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $lectureKeyToMutate);

//             if ($originalLectureBlock) {
//                 $instructorId = $this->instructorAssignmentMap[$originalLectureBlock->section->planSubject->subject_id];
//                 $newRoom = $this->getRandomRoomForSection($originalLectureBlock->section);
//                 $studentGroupId = $this->studentGroupMap[$originalLectureBlock->section->id] ?? null;

//                 $newTimeslots = $this->findOptimalSlotForBlock($originalLectureBlock, $instructorId, $newRoom->id, $studentGroupId);

//                 $lectures[$lectureKeyToMutate] = new Gene([
//                     'lecture_unique_id' => $lectureKeyToMutate,
//                     'section_id' => $originalLectureBlock->section->id,
//                     'instructor_id' => $instructorId,
//                     'room_id' => $newRoom->id,
//                     'timeslot_ids' => json_encode($newTimeslots),
//                     'block_type' => $originalLectureBlock->block_type,
//                     'block_duration' => $originalLectureBlock->slots_needed,
//                     'is_continuous' => $this->areSlotsConsecutive($newTimeslots),
//                     'student_group_id' => $studentGroupId,
//                 ]);
//             }
//         }

//         return $lectures;
//     }

//     private function saveChildChromosome(array $lectures, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create([
//             'population_id' => $this->populationRun->population_id,
//             'penalty_value' => -1,
//             'generation_number' => $generationNumber
//         ]);

//         $genesToInsert = [];
//         foreach ($lectures as $lectureGenes) {
//             if (!$lectureGenes) continue;

//             $timeslotIds = is_array($lectureGenes->timeslot_ids)
//                 ? $lectureGenes->timeslot_ids
//                 : json_decode($lectureGenes->timeslot_ids, true);

//             $genesToInsert[] = [
//                 'chromosome_id' => $chromosome->chromosome_id,
//                 'lecture_unique_id' => $lectureGenes->lecture_unique_id,
//                 'section_id' => $lectureGenes->section_id,
//                 'instructor_id' => $lectureGenes->instructor_id,
//                 'room_id' => $lectureGenes->room_id,
//                 'timeslot_ids' => json_encode($timeslotIds),
//                 'block_type' => $lectureGenes->block_type,
//                 'block_duration' => $lectureGenes->block_duration,
//                 'is_continuous' => $lectureGenes->is_continuous,
//                 'student_group_id' => $lectureGenes->student_group_id,
//             ];
//         }

//         Gene::insert($genesToInsert);
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
//     private array $settings;
//     private Population $populationRun;
//     private Collection $instructors;
//     private Collection $theoryRooms;
//     private Collection $practicalRooms;
//     private Collection $timeslots;
//     private Collection $lectureBlocksToSchedule;
//     private array $consecutiveTimeslotsMap = [];
//     private array $studentGroupMap = [];
//     private array $instructorAssignmentMap = [];
//     private array $resourceUsageCache = [];

//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     public function run()
//     {
//         try {
//             $this->populationRun->update([
//                 'status' => 'running',
//                 'start_time' => now(),
//                 'best_penalty' => PHP_INT_MAX,
//                 'generations_without_improvement' => 0
//             ]);

//             $this->loadAndPrepareData();

//             $currentGenerationNumber = 1;
//             $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);
//             $this->evaluateFitness($currentPopulation);

//             $maxGenerations = $this->settings['max_generations'];
//             while ($currentGenerationNumber < $maxGenerations) {
//                 $bestInGen = $currentPopulation->sortBy('penalty_value')->first();

//                 if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//                     break;
//                 }

//                 if ($bestInGen->penalty_value < $this->populationRun->best_penalty) {
//                     $this->populationRun->update([
//                         'best_penalty' => $bestInGen->penalty_value,
//                         'generations_without_improvement' => 0
//                     ]);
//                 } else {
//                     $this->populationRun->increment('generations_without_improvement');
//                 }

//                 $parents = $this->selectParents($currentPopulation);
//                 $currentGenerationNumber++;
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//                 $this->evaluateFitness($currentPopulation);
//             }

//             $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)
//                 ->orderBy('penalty_value', 'asc')
//                 ->first();

//             $this->populationRun->update([
//                 'status' => 'completed',
//                 'end_time' => now(),
//                 'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
//             ]);
//         } catch (Exception $e) {
//             Log::error("GA Run failed: " . $e->getMessage());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     private function loadAndPrepareData()
//     {
//         $sections = Section::with(['planSubject.subject', 'planSubject.plan'])
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])
//             ->get();

//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         $this->lectureBlocksToSchedule = collect();
//         $theoryBlocks = collect();
//         $practicalBlocks = collect();

//         foreach ($sections as $section) {
//             /** @var Section $section */
//             $subject = $section->planSubject->subject ?? null;
//             if (!$subject) continue;

//             if ($section->activity_type == 'Practical') {
//                 $totalHours = $subject->practical_hours * $this->settings['practical_credit_to_slots'];
//                 $this->splitPracticalBlocks($section, $totalHours, $practicalBlocks);
//             } else {
//                 $totalHours = $subject->theoretical_hours * $this->settings['theory_credit_to_slots'];
//                 $this->splitTheoryBlocks($section, $totalHours, $theoryBlocks);
//             }
//         }

//         $this->lectureBlocksToSchedule = $theoryBlocks->merge($practicalBlocks);
//         if ($this->lectureBlocksToSchedule->isEmpty()) {
//             throw new Exception("No lecture blocks to schedule.");
//         }

//         $this->buildStudentGroupMap($sections);
//         $this->buildInstructorAssignmentMap();

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) =>
//             $q->where('room_type_name', 'not like', '%Lab%')
//              ->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) =>
//             $q->where('room_type_name', 'like', '%Lab%')
//              ->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
//             ->orderBy('start_time')->get();

//         $this->buildConsecutiveTimeslotsMap();
//     }

//     private function splitTheoryBlocks(Section $section, int $totalHours, Collection &$theoryBlocks)
//     {
//         $remainingHours = $totalHours;
//         $blockCounter = 1;

//         while ($remainingHours > 0) {
//             $blockHours = $remainingHours >= 2 ? 2 : 1;

//             $theoryBlocks->push((object)[
//                 'section' => $section,
//                 'block_type' => 'theory',
//                 'total_hours' => $blockHours,
//                 'slots_needed' => $blockHours,
//                 'is_continuous' => true,
//                 'unique_id' => $section->id . '-theory-block' . $blockCounter++,
//             ]);

//             $remainingHours -= $blockHours;
//         }
//     }

//     private function splitPracticalBlocks(Section $section, int $totalHours, Collection &$practicalBlocks)
//     {
//         // البلوكات العملية يجب أن تكون متصلة دائماً وبدون تقسيم
//         $practicalBlocks->push((object)[
//             'section' => $section,
//             'block_type' => 'practical',
//             'total_hours' => $totalHours,
//             'slots_needed' => $totalHours,
//             'is_continuous' => true,
//             'unique_id' => $section->id . '-practical-block',
//         ]);
//     }

//     private function buildStudentGroupMap(Collection $sections)
//     {
//         $this->studentGroupMap = [];
//         $sectionsByContext = $sections->groupBy(function($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $contextKey => $sectionsInContext) {
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')
//                 ->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id] = $groupIndex;
//                 }

//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')
//                     ->groupBy('plan_subject_id');

//                 foreach ($practicalSectionsBySubject as $subjectId => $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id] = $groupIndex;
//                     }
//                 }
//             }
//         }
//     }

//     private function buildInstructorAssignmentMap()
//     {
//         foreach ($this->lectureBlocksToSchedule as $block) {
//             $subjectId = $block->section->planSubject->subject_id;

//             if (!isset($this->instructorAssignmentMap[$subjectId])) {
//                 $instructor = $this->getRandomInstructorForSection($block->section);
//                 $this->instructorAssignmentMap[$subjectId] = $instructor->id;
//             }
//         }
//     }

//     private function buildConsecutiveTimeslotsMap()
//     {
//         $timeslotsByDay = $this->timeslots->groupBy('day');
//         $this->consecutiveTimeslotsMap = [];

//         foreach ($timeslotsByDay as $dayTimeslots) {
//             $dayTimeslotsValues = $dayTimeslots->sortBy('start_time')->values();
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

//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         $chromosomes = Chromosome::insertReturningIds(
//             $this->populationRun->population_id,
//             $generationNumber,
//             $this->settings['population_size']
//         );

//         foreach ($chromosomes as $chromosome) {
//             $this->resourceUsageCache = [];
//             $genesToInsert = [];

//             foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//                 $instructorId = $this->instructorAssignmentMap[$lectureBlock->section->planSubject->subject_id];
//                 $room = $this->getRandomRoomForSection($lectureBlock->section);
//                 $studentGroupId = $this->studentGroupMap[$lectureBlock->section->id] ?? null;

//                 $foundSlots = $this->findOptimalSlotForBlock($lectureBlock, $instructorId, $room->id, $studentGroupId);

//                 $this->updateResourceCache($foundSlots, $instructorId, $room->id, $studentGroupId);

//                 $genesToInsert[] = [
//                     'chromosome_id' => $chromosome->chromosome_id,
//                     'lecture_unique_id' => $lectureBlock->unique_id,
//                     'section_id' => $lectureBlock->section->id,
//                     'instructor_id' => $instructorId,
//                     'room_id' => $room->id,
//                     'timeslot_ids' => json_encode($foundSlots),
//                     'block_type' => $lectureBlock->block_type,
//                     'block_duration' => $lectureBlock->slots_needed,
//                     'is_continuous' => $this->areSlotsConsecutive($foundSlots),
//                     'student_group_id' => $studentGroupId,
//                 ];
//             }

//             Gene::insert($genesToInsert);
//         }

//         return $chromosomes;
//     }

//     private function findOptimalSlotForBlock($lectureBlock, int $instructorId, int $roomId, ?int $studentGroupId): array
//     {
//         $requiredSlots = $lectureBlock->slots_needed;
//         $bestSlots = [];
//         $minConflicts = PHP_INT_MAX;

//         foreach ($this->timeslots as $startSlot) {
//             $consecutiveSlots = $this->getConsecutiveSlots($startSlot->id, $requiredSlots);

//             if (count($consecutiveSlots) >= $requiredSlots) {
//                 $conflicts = $this->countConflictsForSlots($consecutiveSlots, $instructorId, $roomId, $studentGroupId);

//                 if ($conflicts == 0) {
//                     return array_slice($consecutiveSlots, 0, $requiredSlots);
//                 }

//                 if ($conflicts < $minConflicts) {
//                     $minConflicts = $conflicts;
//                     $bestSlots = array_slice($consecutiveSlots, 0, $requiredSlots);
//                 }
//             }
//         }

//         if (empty($bestSlots)) {
//             return $this->findAnyAvailableSlots($requiredSlots);
//         }

//         return $bestSlots;
//     }

//     private function getConsecutiveSlots(int $startSlotId, int $requiredSlots): array
//     {
//         $slots = [$startSlotId];
//         $currentSlotId = $startSlotId;

//         for ($i = 1; $i < $requiredSlots; $i++) {
//             if (empty($this->consecutiveTimeslotsMap[$currentSlotId])) {
//                 break;
//             }
//             $currentSlotId = $this->consecutiveTimeslotsMap[$currentSlotId][0];
//             $slots[] = $currentSlotId;
//         }

//         return $slots;
//     }

//     private function findAnyAvailableSlots(int $requiredSlots): array
//     {
//         $availableSlots = [];
//         foreach ($this->timeslots as $slot) {
//             if (empty($this->resourceUsageCache[$slot->id])) {
//                 $availableSlots[] = $slot->id;
//                 if (count($availableSlots) >= $requiredSlots) {
//                     return $availableSlots;
//                 }
//             }
//         }
//         return $this->timeslots->random(min($requiredSlots, $this->timeslots->count()))->pluck('id')->toArray();
//     }

//     private function countConflictsForSlots(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId): int
//     {
//         $conflicts = 0;

//         foreach ($slotIds as $slotId) {
//             if (isset($this->resourceUsageCache[$slotId])) {
//                 if ($studentGroupId && isset($this->resourceUsageCache[$slotId]['student_groups'][$studentGroupId])) {
//                     $conflicts += 2000;
//                 }

//                 if (isset($this->resourceUsageCache[$slotId]['instructors'][$instructorId])) {
//                     $conflicts += 1000;
//                 }

//                 if (isset($this->resourceUsageCache[$slotId]['rooms'][$roomId])) {
//                     $conflicts += 800;
//                 }
//             }
//         }

//         return $conflicts;
//     }

//     private function areSlotsConsecutive(array $slotIds): bool
//     {
//         if (count($slotIds) <= 1) return true;

//         $prevSlot = null;
//         foreach ($slotIds as $slotId) {
//             $slot = $this->timeslots->firstWhere('id', $slotId);
//             if (!$slot) continue;

//             if ($prevSlot) {
//                 if ($slot->day != $prevSlot->day || $slot->start_time != $prevSlot->end_time) {
//                     return false;
//                 }
//             }
//             $prevSlot = $slot;
//         }

//         return true;
//     }

//     private function updateResourceCache(array $slotIds, int $instructorId, int $roomId, ?int $studentGroupId): void
//     {
//         foreach ($slotIds as $slotId) {
//             $this->resourceUsageCache[$slotId]['instructors'][$instructorId] = true;
//             $this->resourceUsageCache[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupId) {
//                 $this->resourceUsageCache[$slotId]['student_groups'][$studentGroupId] = true;
//             }
//         }
//     }

//     private function getRandomInstructorForSection(Section $section)
//     {
//         $subject = $section->planSubject->subject ?? null;
//         if (!$subject) return $this->instructors->random();

//         $suitableInstructors = $this->instructors->filter(fn ($instructor) =>
//             $instructor->subjects->contains($subject->id));

//         return $suitableInstructors->isNotEmpty() ? $suitableInstructors->random() : $this->instructors->random();
//     }

//     private function getRandomRoomForSection(Section $section)
//     {
//         $roomsSource = ($section->activity_type === 'Practical') ? $this->practicalRooms : $this->theoryRooms;
//         if ($roomsSource->isEmpty()) {
//             $roomsSource = Room::all();
//         }

//         $suitableRooms = $roomsSource->where('room_size', '>=', $section->student_count);
//         return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $roomsSource->random();
//     }

//     private function evaluateFitness(Collection $chromosomes)
//     {
//         $allGenes = Gene::whereIn('chromosome_id', $chromosomes->pluck('chromosome_id'))
//             ->get()
//             ->groupBy('chromosome_id');

//         foreach ($chromosomes as $chromosome) {
//             if (!isset($allGenes[$chromosome->chromosome_id])) continue;

//             $genes = $allGenes[$chromosome->chromosome_id];
//             $totalPenalty = 0;
//             $resourceUsageMap = [];
//             $studentGroupUsage = [];
//             $instructorUsage = [];

//             foreach ($genes as $gene) {
//                 $timeslotIds = json_decode($gene->timeslot_ids, true) ?? [];

//                 foreach ($timeslotIds as $timeslotId) {
//                     if (isset($instructorUsage[$gene->instructor_id][$timeslotId])) {
//                         $totalPenalty += 1000;
//                     }

//                     if (isset($resourceUsageMap[$timeslotId]['rooms'][$gene->room_id])) {
//                         $totalPenalty += 800;
//                     }

//                     if ($gene->student_group_id) {
//                         if (isset($studentGroupUsage[$gene->student_group_id][$timeslotId])) {
//                             $totalPenalty += 2000;
//                         }
//                     }
//                 }

//                 foreach ($timeslotIds as $timeslotId) {
//                     $instructorUsage[$gene->instructor_id][$timeslotId] = true;
//                     $resourceUsageMap[$timeslotId]['rooms'][$gene->room_id] = true;

//                     if ($gene->student_group_id) {
//                         $studentGroupUsage[$gene->student_group_id][$timeslotId] = true;
//                     }
//                 }

//                 $totalPenalty += $this->checkAdditionalConstraints($gene);
//             }

//             $chromosome->update(['penalty_value' => $totalPenalty]);
//         }
//     }

//     private function checkAdditionalConstraints(Gene $gene): int
//     {
//         $penalty = 0;

//         $room = Room::find($gene->room_id);
//         $section = Section::find($gene->section_id);

//         if (!$section || !$room) return 0;

//         if ($section->student_count > $room->room_size) {
//             $penalty += 800;
//         }

//         $roomType = $room->roomType->room_type_name ?? '';
//         $isPracticalRoom = Str::contains(strtolower($roomType), ['lab', 'مختبر']);

//         if ($gene->block_type == 'Practical' && !$isPracticalRoom) {
//             $penalty += 1000;
//         }

//         if ($gene->block_type == 'Theory' && $isPracticalRoom) {
//             $penalty += 600;
//         }

//         if ($gene->block_type == 'Practical' && !$gene->is_continuous) {
//             $penalty += 1500;
//         }

//         return $penalty;
//     }

//     private function selectParents(Collection $population): array
//     {
//         return $population->sortBy('penalty_value')
//             ->take($this->settings['population_size'])
//             ->toArray();
//     }

//     private function createNewGeneration(array $parents, int $nextGenerationNumber): Collection
//     {
//         $childrenData = [];
//         $parentPool = $parents;

//         for ($i = 0; $i < $this->settings['population_size']; $i += 2) {
//             if (count($parentPool) < 2) $parentPool = $parents;

//             $parent1 = $parentPool[array_rand($parentPool)];
//             $parent2 = $parentPool[array_rand($parentPool)];

//             [$child1Lectures, $child2Lectures] = $this->performCrossover($parent1, $parent2);

//             $childrenData[] = $this->performMutation($child1Lectures);
//             if (count($childrenData) < $this->settings['population_size']) {
//                 $childrenData[] = $this->performMutation($child2Lectures);
//             }
//         }

//         $newChromosomes = collect();
//         foreach ($childrenData as $lectures) {
//             $newChromosomes->push($this->saveChildChromosome($lectures, $nextGenerationNumber));
//         }

//         return $newChromosomes;
//     }

//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Genes = $parent1->genes->keyBy('lecture_unique_id');
//         $p2Genes = $parent2->genes->keyBy('lecture_unique_id');
//         $child1Genes = [];
//         $child2Genes = [];

//         $crossoverPoint = rand(1, count($this->lectureBlocksToSchedule) - 1);
//         $index = 0;

//         foreach ($this->lectureBlocksToSchedule as $block) {
//             $child1Genes[$block->unique_id] = $index < $crossoverPoint
//                 ? ($p1Genes[$block->unique_id] ?? $p2Genes[$block->unique_id])
//                 : ($p2Genes[$block->unique_id] ?? $p1Genes[$block->unique_id]);

//             $child2Genes[$block->unique_id] = $index < $crossoverPoint
//                 ? ($p2Genes[$block->unique_id] ?? $p1Genes[$block->unique_id])
//                 : ($p1Genes[$block->unique_id] ?? $p2Genes[$block->unique_id]);

//             $index++;
//         }

//         return [$child1Genes, $child2Genes];
//     }

//     private function performMutation(array $genes): array
//     {
//         if (mt_rand() / mt_getrandmax() < $this->settings['mutation_rate']) {
//             $block = $this->lectureBlocksToSchedule->random();
//             $instructorId = $this->instructorAssignmentMap[$block->section->planSubject->subject_id];
//             $room = $this->getRandomRoomForSection($block->section);
//             $studentGroupId = $this->studentGroupMap[$block->section->id] ?? null;

//             $newTimeslots = $this->findOptimalSlotForBlock($block, $instructorId, $room->id, $studentGroupId);

//             $genes[$block->unique_id] = (object)[
//                 'lecture_unique_id' => $block->unique_id,
//                 'section_id' => $block->section->id,
//                 'instructor_id' => $instructorId,
//                 'room_id' => $room->id,
//                 'timeslot_ids' => $newTimeslots,
//                 'block_type' => $block->block_type,
//                 'block_duration' => $block->slots_needed,
//                 'is_continuous' => $this->areSlotsConsecutive($newTimeslots),
//                 'student_group_id' => $studentGroupId,
//             ];
//         }

//         return $genes;
//     }

//     private function saveChildChromosome(array $genes, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create([
//             'population_id' => $this->populationRun->population_id,
//             'penalty_value' => -1,
//             'generation_number' => $generationNumber
//         ]);

//         $genesToInsert = [];
//         foreach ($genes as $gene) {
//             $genesToInsert[] = [
//                 'chromosome_id' => $chromosome->chromosome_id,
//                 'lecture_unique_id' => $gene->lecture_unique_id,
//                 'section_id' => $gene->section_id,
//                 'instructor_id' => $gene->instructor_id,
//                 'room_id' => $gene->room_id,
//                 'timeslot_ids' => json_encode($gene->timeslot_ids),
//                 'block_type' => $gene->block_type,
//                 'block_duration' => $gene->block_duration,
//                 'is_continuous' => $gene->is_continuous,
//                 'student_group_id' => $gene->student_group_id,
//             ];
//         }

//         Gene::insert($genesToInsert);
//         return $chromosome;
//     }
// }

// ********************************* mauasu.mi 2022 *********************************



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
// use Exception;

// /**
//  * GeneticAlgorithmService
//  * هذا الكلاس هو "العقل المدبر" للخوارزمية الجينية.
//  * وظيفته أخذ البيانات الخام من قاعدة البيانات، تحضيرها، تشغيل الخوارزمية،
//  * ثم حفظ أفضل جدول ناتج.
//  */
// class GeneticAlgorithmService
// {
//     // --- الإعدادات والبيانات المحملة ---

//     /** @var array الإعدادات التي يتم تمريرها من المستخدم (حجم السكان، عدد الأجيال، إلخ). */
//     private array $settings;

//     /** @var Population سجل التشغيل الحالي في قاعدة البيانات. */
//     private Population $populationRun;

//     /** @var Collection قائمة بكل المدرسين المتاحين. */
//     private Collection $instructors;

//     /** @var Collection قائمة بالقاعات النظرية. */
//     private Collection $theoryRooms;

//     /** @var Collection قائمة بالقاعات العملية (المختبرات). */
//     private Collection $practicalRooms;

//     /** @var Collection قائمة بكل الفترات الزمنية المتاحة. */
//     private Collection $timeslots;

//     /** @var Collection القائمة النهائية للبلوكات (المحاضرات) التي يجب جدولتها. */
//     private Collection $lectureBlocksToSchedule;

//     /** @var array خريطة تساعد في إيجاد الفترات الزمنية المتصلة بسرعة. [timeslot_id => [next_slot_id, ...]] */
//     private array $consecutiveTimeslotsMap = [];

//     /** @var array خريطة تربط كل شعبة بمجموعة الطلاب الخاصة بها. [section_id => [student_group_id, ...]] */
//     private array $studentGroupMap = [];

//     /** @var array خريطة تربط كل بلوك بالمدرس المسؤول عنه. [lecture_unique_id => instructor_id] */
//     private array $instructorAssignmentMap = [];

//     /** @var array كاش مؤقت لتسريع عملية بناء الجيل الأول. */
//     private array $resourceUsageCache = [];


//     /**
//      * المُنشئ (Constructor)
//      * يقوم بتهيئة الخدمة بالإعدادات وسجل التشغيل.
//      */
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->id}");
//     }

//     /**
//      * الدالة الرئيسية (run)
//      * تدير عملية التوليد بأكملها من البداية إلى النهاية.
//      */
//     public function run()
//     {
//         try {
//             // تحديث حالة التشغيل إلى "قيد التنفيذ"
//             $this->populationRun->update(['status' => 'running', 'start_time' => now()]);

//             // 1. تحميل وتحضير كل البيانات اللازمة بكفاءة
//             $this->loadAndPrepareData();

//             // 2. إنشاء الجيل الأول بشكل ذكي
//             $currentGenerationNumber = 1;
//             $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);

//             // 3. تقييم الجيل الأول وحساب العقوبات
//             $this->evaluateFitness($currentPopulation);
//             Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//             // 4. بدء حلقة التطور للأجيال
//             $maxGenerations = $this->settings['generations_count'];
//             while ($currentGenerationNumber < $maxGenerations) {
//                 // التحقق من وجود حل مثالي لإيقاف العملية مبكراً
//                 $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
//                 if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//                     Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//                     break;
//                 }

//                 // اختيار الآباء وإنشاء جيل جديد
//                 $parents = $this->selectParents($currentPopulation);
//                 $currentGenerationNumber++;
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//                 $this->evaluateFitness($currentPopulation);
//                 Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//             }

//             // 5. الانتهاء وتحديث حالة التشغيل بالنتيجة النهائية
//             $finalBest = Chromosome::where('population_id', $this->populationRun->id)->orderBy('penalty_value', 'asc')->first();
//             $this->populationRun->update([
//                 'status' => 'completed',
//                 'end_time' => now(),
//                 'best_chromosome_id' => $finalBest ? $finalBest->id : null
//             ]);
//             Log::info("GA Run ID: {$this->populationRun->id} completed successfully.");
//         } catch (Exception $e) {
//             // في حالة حدوث أي خطأ، يتم تسجيله وتحديث الحالة إلى "فشل"
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     //======================================================================
//     // المرحلة الأولى: تحميل وتحضير البيانات
//     //======================================================================

//     /**
//      * يقوم بتحميل كل البيانات اللازمة من قاعدة البيانات وتحضيرها للاستخدام السريع في الذاكرة.
//      */
//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         // جلب كل البيانات اللازمة في أقل عدد ممكن من الاستعلامات
//         $sections = Section::with(['planSubject.subject', 'instructors'])
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])
//             ->get();

//         if ($sections->isEmpty()) {
//             throw new Exception("No sections found for the selected context.");
//         }

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();

//         // بناء الخرائط المساعدة لتسريع العمليات
//         $this->buildConsecutiveTimeslotsMap();
//         $this->buildStudentGroupMap($sections);

//         // توليد البلوكات النهائية التي سيتم جدولتها وتعيين المدرسين لها مسبقاً
//         $this->generateLectureBlocksAndAssignInstructors($sections);

//         if ($this->lectureBlocksToSchedule->isEmpty()) {
//             throw new Exception("No lecture blocks to schedule were found after processing credit hours.");
//         }

//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//     }

//     /**
//      * الدالة المحورية التي تقوم بتوليد البلوكات الصحيحة وتعيين المدرسين لها مسبقاً.
//      */
//     private function generateLectureBlocksAndAssignInstructors(Collection $sections)
//     {
//         $this->lectureBlocksToSchedule = collect();
//         $this->instructorAssignmentMap = [];
//         $subjectInstructorMap = []; // لتخزين المدرس الذي تم اختياره لكل مادة لضمان التناسق

//         // تجميع الشعب حسب المادة لتسهيل التعامل معها
//         $sectionsBySubject = $sections->groupBy('planSubject.subject.id');

//         foreach ($sectionsBySubject as $subjectId => $subjectSections) {
//             $firstSection = $subjectSections->first();
//             if (!$firstSection || !$firstSection->planSubject || !$firstSection->planSubject->subject) continue;
//             $subject = $firstSection->planSubject->subject;

//             // اختيار مدرس واحد فقط لهذه المادة وتخزينه
//             $assignedInstructor = $this->getRandomInstructorForSubject($subject);
//             $subjectInstructorMap[$subjectId] = $assignedInstructor;

//             // تقسيم البلوكات النظرية
//             if ($subject->theoretical_hours > 0) {
//                 $theorySection = $subjectSections->firstWhere('activity_type', 'Theory');
//                 if ($theorySection) {
//                     $totalTheorySlots = $subject->theoretical_hours * ($this->settings['theory_credit_to_slots'] ?? 1);
//                     $this->splitTheoryBlocks($theorySection, $totalTheorySlots, $assignedInstructor);
//                 }
//             }

//             // تقسيم البلوكات العملية
//             if ($subject->practical_hours > 0) {
//                 $practicalSections = $subjectSections->where('activity_type', 'Practical');
//                 foreach ($practicalSections as $practicalSection) {
//                     // حساب عدد الساعات الفعلية المطلوبة للعملي
//                     $totalPracticalSlots = $subject->practical_hours * ($this->settings['practical_credit_to_slots'] ?? 1);
//                     $this->splitPracticalBlocks($practicalSection, $totalPracticalSlots, $assignedInstructor);
//                 }
//             }
//         }
//     }

//     /**
//      * يقسم الساعات النظرية إلى بلوكات (2+1) حسب المنطق المطلوب.
//      */
//     private function splitTheoryBlocks(Section $section, int $totalSlots, Instructor $instructor)
//     {
//         $remainingSlots = $totalSlots;
//         $blockCounter = 1;

//         while ($remainingSlots > 0) {
//             // استراتيجية التقسيم: 2 ثم 1
//             $blockSlots = 1; // القيمة الافتراضية
//             if ($remainingSlots >= 2) {
//                 // إذا كان المتبقي 3، نبدأ ببلوك من ساعتين
//                 // إذا كان المتبقي 2 أو 4 أو أكثر، نبدأ ببلوك من ساعتين
//                 $blockSlots = 2;
//             }
//             // إذا كان المتبقي 1، سيبقى حجم البلوك 1

//             $uniqueId = "{$section->id}-theory-block{$blockCounter}";
//             $this->lectureBlocksToSchedule->push((object)[
//                 'section' => $section,
//                 'block_type' => 'theory',
//                 'slots_needed' => $blockSlots,
//                 'unique_id' => $uniqueId
//             ]);
//             // تعيين نفس المدرس للبلوك
//             $this->instructorAssignmentMap[$uniqueId] = $instructor->id;

//             $remainingSlots -= $blockSlots;
//             $blockCounter++;
//         }
//     }

//     /**
//      * ينشئ بلوك عملي واحد متصل مهما كان عدد ساعاته.
//      */
//     private function splitPracticalBlocks(Section $section, int $totalSlots, Instructor $instructor)
//     {
//         if ($totalSlots <= 0) return;

//         // العملي دائماً بلوك واحد متصل
//         $uniqueId = "{$section->id}-practical-block1";
//         $this->lectureBlocksToSchedule->push((object)[
//             'section' => $section,
//             'block_type' => 'practical',
//             'slots_needed' => $totalSlots,
//             'unique_id' => $uniqueId
//         ]);
//         // تعيين نفس المدرس للبلوك
//         $this->instructorAssignmentMap[$uniqueId] = $instructor->id;
//     }


//     /**
//      * يبني خريطة لمجموعات الطلاب لضمان عدم وجود تضارب للطالب.
//      */
//     private function buildStudentGroupMap(Collection $sections)
//     {
//         $this->studentGroupMap = [];
//         // تجميع الشعب حسب السياق (خطة، مستوى، فصل، فرع)
//         $sectionsByContext = $sections->groupBy(function ($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $contextKey => $sectionsInContext) {
//             // تحديد عدد المجموعات بناءً على المادة التي لديها أكبر عدد من الشعب العملية
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')
//                 ->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 // الشعب النظرية مشتركة بين جميع المجموعات
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id][] = $groupIndex;
//                 }

//                 // الشعب العملية موزعة على المجموعات
//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')
//                     ->groupBy('plan_subject_id');

//                 foreach ($practicalSectionsBySubject as $subjectId => $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id][] = $groupIndex;
//                     }
//                 }
//             }
//         }
//     }


//     /**
//      * يبني خريطة للأوقات المتصلة لتسريع البحث عن بلوكات زمنية.
//      */
//     private function buildConsecutiveTimeslotsMap()
//     {
//         $timeslotsByDay = $this->timeslots->groupBy('day');
//         $this->consecutiveTimeslotsMap = [];

//         foreach ($timeslotsByDay as $day => $dayTimeslots) {
//             $sortedSlots = $dayTimeslots->sortBy('start_time')->values();
//             for ($i = 0; $i < $sortedSlots->count(); $i++) {
//                 $currentSlotId = $sortedSlots[$i]->id;
//                 $this->consecutiveTimeslotsMap[$currentSlotId] = [];
//                 $lastSlot = $sortedSlots[$i];

//                 for ($j = $i + 1; $j < $sortedSlots->count(); $j++) {
//                     $nextSlot = $sortedSlots[$j];
//                     if ($nextSlot->start_time == $lastSlot->end_time) {
//                         $this->consecutiveTimeslotsMap[$currentSlotId][] = $nextSlot->id;
//                         $lastSlot = $nextSlot;
//                     } else {
//                         break;
//                     }
//                 }
//             }
//         }
//     }


//     //======================================================================
//     // المرحلة الثانية: إنشاء الجيل الأول
//     //======================================================================

//     /**
//      * يقوم بإنشاء الجيل الأول من الجداول بشكل ذكي.
//      */
//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating intelligent initial population (Generation #{$generationNumber})");

//         // إنشاء سجلات الكروموسومات دفعة واحدة لتحسين الأداء
//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosomesToInsert[] = ['population_id' => $this->populationRun->id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//         }
//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->id)->where('generation_number', $generationNumber)->get();

//         foreach ($createdChromosomes as $chromosome) {
//             $this->resourceUsageCache = []; // إعادة تعيين الكاش لكل كروموسوم جديد
//             $genesToInsert = [];

//             foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//                 $instructorId = $this->instructorAssignmentMap[$lectureBlock->unique_id];
//                 $room = $this->getRandomRoomForBlock($lectureBlock);
//                 $studentGroupIds = $this->studentGroupMap[$lectureBlock->section->id] ?? [];

//                 // البحث عن أفضل مكان بناءً على استراتيجية الأولويات
//                 $foundSlots = $this->findOptimalSlotForBlock($lectureBlock, $instructorId, $room->id, $studentGroupIds);

//                 // تحديث الكاش بالموارد المستخدمة
//                 $this->updateResourceCache($foundSlots, $instructorId, $room->id, $studentGroupIds);

//                 $genesToInsert[] = [
//                     'chromosome_id' => $chromosome->id,
//                     'lecture_unique_id' => $lectureBlock->unique_id,
//                     'section_id' => $lectureBlock->section->id,
//                     'instructor_id' => $instructorId,
//                     'room_id' => $room->id,
//                     'timeslot_ids' => json_encode($foundSlots),
//                     'student_group_ids' => json_encode($studentGroupIds), // تخزين مجموعات الطلاب
//                 ];
//             }
//             // إدخال الجينات دفعة واحدة لتحسين الأداء
//             Gene::insert($genesToInsert);
//         }
//         return $createdChromosomes;
//     }

//     /**
//      * يبحث عن أفضل مكان متاح للبلوك بناءً على استراتيجية الأولويات.
//      */
//     private function findOptimalSlotForBlock($lectureBlock, int $instructorId, int $roomId, array $studentGroupIds): array
//     {
//         $possibleStartSlots = $this->getPossibleStartSlots($lectureBlock->slots_needed);

//         // الأولوية 1: البحث عن مكان مثالي (صفر تعارضات)
//         foreach ($possibleStartSlots as $startSlotId => $trialSlots) {
//             if ($this->countConflictsForSlots($trialSlots, $instructorId, $roomId, $studentGroupIds) == 0) {
//                 return $trialSlots;
//             }
//         }

//         // الأولوية 2: البحث عن مكان بدون تعارض طالب أو قاعة
//         foreach ($possibleStartSlots as $startSlotId => $trialSlots) {
//             if ($this->countConflictsForSlots($trialSlots, null, $roomId, $studentGroupIds) == 0) {
//                 return $trialSlots;
//             }
//         }

//         // الأولوية 3: البحث عن مكان بدون تعارض طالب
//         foreach ($possibleStartSlots as $startSlotId => $trialSlots) {
//             if ($this->countConflictsForSlots($trialSlots, null, null, $studentGroupIds) == 0) {
//                 return $trialSlots;
//             }
//         }

//         // الحل الأخير: اختيار أي مكان عشوائي من الأماكن المتاحة
//         return $possibleStartSlots->isNotEmpty() ? $possibleStartSlots->random() : $this->timeslots->random($lectureBlock->slots_needed)->pluck('id')->toArray();
//     }

//     /**
//      * يحسب عدد التضاربات لمجموعة من الفترات الزمنية.
//      */
//     private function countConflictsForSlots(array $slotIds, ?int $instructorId, ?int $roomId, ?array $studentGroupIds): int
//     {
//         $conflicts = 0;
//         foreach ($slotIds as $slotId) {
//             if (isset($this->resourceUsageCache[$slotId])) {
//                 if ($studentGroupIds) {
//                     foreach ($studentGroupIds as $groupId) {
//                         if (isset($this->resourceUsageCache[$slotId]['student_groups'][$groupId])) $conflicts++;
//                     }
//                 }
//                 if ($instructorId && isset($this->resourceUsageCache[$slotId]['instructors'][$instructorId])) $conflicts++;
//                 if ($roomId && isset($this->resourceUsageCache[$slotId]['rooms'][$roomId])) $conflicts++;
//             }
//         }
//         return $conflicts;
//     }


//     //======================================================================
//     // المرحلة الثالثة: التقييم والتحسين (الحلقة الرئيسية)
//     //======================================================================

//     /**
//      * الدالة المحورية التي تقيم كل جدول وتحسب له درجة العقوبة.
//      */
//     private function evaluateFitness(Collection $chromosomes)
//     {
//         $chromosomeIds = $chromosomes->pluck('id');
//         // جلب كل الجينات مرة واحدة لتحسين الأداء
//         $allGenes = Gene::whereIn('chromosome_id', $chromosomeIds)->get()->groupBy('chromosome_id');

//         $updates = [];
//         foreach ($chromosomes as $chromosome) {
//             $genes = $allGenes->get($chromosome->id, collect());
//             $totalPenalty = 0;

//             // بناء خرائط الاستخدام لكل كروموسوم على حدة
//             $instructorUsage = []; // [instructor_id => [timeslot_id => true]]
//             $roomUsage = [];       // [room_id => [timeslot_id => true]]
//             $studentGroupUsage = []; // [student_group_id => [timeslot_id => true]]

//             foreach ($genes as $gene) {
//                 $timeslotIds = json_decode($gene->timeslot_ids, true) ?? [];
//                 $studentGroupIds = json_decode($gene->student_group_ids, true) ?? [];

//                 foreach ($timeslotIds as $timeslotId) {
//                     // حساب تضارب المجموعات (أهم عقوبة)
//                     if ($studentGroupIds) {
//                         foreach ($studentGroupIds as $groupId) {
//                             if (isset($studentGroupUsage[$groupId][$timeslotId])) $totalPenalty += 2000;
//                             $studentGroupUsage[$groupId][$timeslotId] = true;
//                         }
//                     }
//                     // حساب تضارب المدرس
//                     if (isset($instructorUsage[$gene->instructor_id][$timeslotId])) $totalPenalty += 1000;
//                     $instructorUsage[$gene->instructor_id][$timeslotId] = true;
//                     // حساب تضارب القاعة
//                     if (isset($roomUsage[$gene->room_id][$timeslotId])) $totalPenalty += 800;
//                     $roomUsage[$gene->room_id][$timeslotId] = true;
//                 }
//             }

//             // التحقق من القيود الإضافية (الحجم، النوع، إلخ)
//             $totalPenalty += $this->checkAdditionalConstraints($genes);

//             $chromosome->penalty_value = $totalPenalty;
//             $updates[] = ['id' => $chromosome->id, 'penalty_value' => $totalPenalty];
//         }

//         // تحديث العقوبات في قاعدة البيانات دفعة واحدة
//         if (!empty($updates)) {
//             Chromosome::upsert($updates, ['id'], ['penalty_value']);
//         }
//     }

//     /**
//      * يتحقق من القيود الإضافية مثل سعة القاعة ونوعها وأهلية المدرس.
//      */
//     private function checkAdditionalConstraints(Collection $genes): int
//     {
//         $penalty = 0;
//         // جلب البيانات اللازمة مسبقاً لتجنب الاستعلامات داخل الحلقة
//         $sections = Section::with('planSubject.subject')->whereIn('id', $genes->pluck('section_id'))->get()->keyBy('id');
//         $rooms = Room::with('roomType')->whereIn('id', $genes->pluck('room_id'))->get()->keyBy('id');
//         $instructors = Instructor::with('subjects')->whereIn('id', $genes->pluck('instructor_id'))->get()->keyBy('id');

//         foreach ($genes as $gene) {
//             $section = $sections->get($gene->section_id);
//             $room = $rooms->get($gene->room_id);
//             $instructor = $instructors->get($gene->instructor_id);

//             if (!$section || !$room || !$instructor) continue;

//             // التحقق من سعة القاعة
//             if ($section->student_count > $room->room_size) $penalty += 800;

//             // التحقق من نوع القاعة
//             $subject = optional($section->planSubject)->subject;
//             $requiredRoomTypeId = optional($subject)->required_room_type_id;
//             if ($requiredRoomTypeId && $room->room_type_id != $requiredRoomTypeId) $penalty += 600;

//             // التحقق من أهلية المدرس
//             if (!$instructor->subjects->contains($subject->id)) $penalty += 2000;
//         }
//         return $penalty;
//     }

//     /**
//      * يختار أفضل الآباء من الجيل الحالي باستخدام طريقة البطولة.
//      */
//     private function selectParents(Collection $population): array
//     {
//         $parents = [];
//         $tournamentSize = 3;
//         $populationCount = $population->count();

//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             if ($populationCount == 0) break;
//             $participants = $population->random(min($tournamentSize, $populationCount));
//             $parents[] = $participants->sortBy('penalty_value')->first();
//         }
//         return $parents;
//     }

//     /**
//      * ينشئ جيلاً جديداً من خلال عمليات التزاوج والطفرة.
//      */
//     private function createNewGeneration(array $parents, int $nextGenerationNumber): Collection
//     {
//         Log::info("Creating new generation #{$nextGenerationNumber}");
//         $childrenData = [];
//         $parentPool = array_filter($parents); // إزالة أي قيم null

//         if (empty($parentPool)) {
//             Log::warning("Parent pool is empty for generation {$nextGenerationNumber}. Cannot create new generation.");
//             return collect();
//         }

//         for ($i = 0; $i < $this->settings['population_size']; $i += 2) {
//             if (count($parentPool) < 2) {
//                 if (empty($parents)) break;
//                 $parentPool = array_filter($parents); // إعادة ملء حوض الآباء إذا لزم الأمر
//             }

//             $p1_key = array_rand($parentPool);
//             $parent1 = $parentPool[$p1_key];
//             unset($parentPool[$p1_key]);

//             $p2_key = array_rand($parentPool);
//             $parent2 = $parentPool[$p2_key];
//             unset($parentPool[$p2_key]);

//             if (!$parent1 || !$parent2) continue;

//             // عملية التزاوج
//             [$child1Lectures, $child2Lectures] = $this->performCrossover($parent1, $parent2);

//             // عملية الطفرة
//             $childrenData[] = $this->performMutation($child1Lectures);
//             if (count($childrenData) < $this->settings['population_size']) {
//                 $childrenData[] = $this->performMutation($child2Lectures);
//             }
//         }

//         // حفظ الأولاد الجدد في قاعدة البيانات
//         $newlyCreatedChromosomes = [];
//         foreach ($childrenData as $lecturesToInsert) {
//             if (!empty($lecturesToInsert)) {
//                 $newlyCreatedChromosomes[] = $this->saveChildChromosome($lecturesToInsert, $nextGenerationNumber);
//             }
//         }
//         return collect($newlyCreatedChromosomes);
//     }

//     /**
//      * يقوم بعملية التزاوج بين أبوين لإنتاج ولدين.
//      */
//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Lectures = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Lectures = $parent2->genes()->get()->keyBy('lecture_unique_id');
//         $child1Lectures = [];
//         $child2Lectures = [];

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$lectureBlock->unique_id] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Lectures->get($lectureBlock->unique_id);
//             $child2Lectures[$lectureBlock->unique_id] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Lectures->get($lectureBlock->unique_id);

//             $currentIndex++;
//         }
//         return [$child1Lectures, $child2Lectures];
//     }

//     /**
//      * يقوم بعملية الطفرة (تغيير عشوائي) على جينات الوليد.
//      */
//     private function performMutation(array $lectures): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($lectures)) return [];

//             $lectureKeyToMutate = array_rand($lectures);
//             $lectureToMutate = $lectures[$lectureKeyToMutate];
//             if (!$lectureToMutate) return $lectures;

//             $originalLectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $lectureKeyToMutate);
//             if (!$originalLectureBlock) return $lectures;

//             // الحفاظ على نفس المدرس للمادة
//             $instructorId = $this->instructorAssignmentMap[$originalLectureBlock->unique_id];
//             $newRoom = $this->getRandomRoomForBlock($originalLectureBlock);
//             $studentGroupIds = $this->studentGroupMap[$originalLectureBlock->section->id] ?? [];

//             // إيجاد فترات زمنية جديدة
//             $newTimeslots = $this->findOptimalSlotForBlock($originalLectureBlock, $instructorId, $newRoom->id, $studentGroupIds);

//             // تحديث الجين الذي حدثت له الطفرة
//             $lectureToMutate->room_id = $newRoom->id;
//             $lectureToMutate->timeslot_ids = json_encode($newTimeslots);
//             $lectures[$lectureKeyToMutate] = $lectureToMutate;
//         }
//         return $lectures;
//     }

//     /**
//      * يحفظ الكروموسوم الوليد وجيناته في قاعدة البيانات.
//      */
//     private function saveChildChromosome(array $lectures, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create(['population_id' => $this->populationRun->id, 'penalty_value' => -1, 'generation_number' => $generationNumber]);

//         $genesToInsert = [];
//         foreach ($lectures as $lectureGene) {
//             if (is_null($lectureGene)) continue;
//             $genesToInsert[] = [
//                 'chromosome_id' => $chromosome->id,
//                 'lecture_unique_id' => $lectureGene->lecture_unique_id,
//                 'section_id' => $lectureGene->section_id,
//                 'instructor_id' => $lectureGene->instructor_id,
//                 'room_id' => $lectureGene->room_id,
//                 'timeslot_ids' => is_string($lectureGene->timeslot_ids) ? $lectureGene->timeslot_ids : json_encode($lectureGene->timeslot_ids),
//                 'student_group_ids' => is_string($lectureGene->student_group_ids) ? $lectureGene->student_group_ids : json_encode($lectureGene->student_group_ids),
//             ];
//         }
//         Gene::insert($genesToInsert);
//         return $chromosome;
//     }

//     //======================================================================
//     // دوال مساعدة
//     //======================================================================

//     private function getRandomInstructorForSubject(\App\Models\Subject $subject)
//     {
//         $suitableInstructors = $this->instructors->filter(fn($instructor) => $instructor->subjects->contains($subject->id));
//         return $suitableInstructors->isNotEmpty() ? $suitableInstructors->random() : $this->instructors->random();
//     }

//     private function getRandomRoomForBlock(\stdClass $lectureBlock)
//     {
//         $section = $lectureBlock->section;
//         $roomsSource = ($lectureBlock->block_type === 'practical') ? $this->practicalRooms : $this->theoryRooms;
//         if ($roomsSource->isEmpty()) {
//             // كحل أخير، استخدم أي نوع من القاعات إذا كان النوع المطلوب غير متوفر
//             $roomsSource = ($lectureBlock->block_type === 'practical') ? $this->theoryRooms : $this->practicalRooms;
//             if ($roomsSource->isEmpty()) return Room::all()->random(); // كحل أخير جداً
//         }

//         $suitableRooms = $roomsSource->where('room_size', '>=', $section->student_count);
//         return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $roomsSource->random();
//     }

//     /**
//      * يرجع قائمة بكل الفترات الزمنية التي يمكن أن يبدأ منها بلوك بالحجم المطلوب.
//      */
//     private function getPossibleStartSlots(int $slotsNeeded): Collection
//     {
//         return $this->timeslots->filter(function ($slot) use ($slotsNeeded) {
//             // التأكد من أن الفترة الزمنية موجودة في خريطة الأوقات المتصلة وأنها كافية
//             return isset($this->consecutiveTimeslotsMap[$slot->id]) && (count($this->consecutiveTimeslotsMap[$slot->id]) + 1) >= $slotsNeeded;
//         })->mapWithKeys(function ($slot) use ($slotsNeeded) {
//             // إنشاء البلوك الزمني الكامل
//             return [$slot->id => array_merge([$slot->id], array_slice($this->consecutiveTimeslotsMap[$slot->id], 0, $slotsNeeded - 1))];
//         });
//     }

//     /**
//      * يقوم بتحديث كاش الموارد المستخدمة لتسريع عملية بناء الجيل الأول.
//      */
//     private function updateResourceCache(array $slotIds, int $instructorId, int $roomId, ?array $studentGroupIds): void
//     {
//         foreach ($slotIds as $slotId) {
//             $this->resourceUsageCache[$slotId]['instructors'][$instructorId] = true;
//             $this->resourceUsageCache[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupIds) {
//                 foreach ($studentGroupIds as $groupId) {
//                     $this->resourceUsageCache[$slotId]['student_groups'][$groupId] = true;
//                 }
//             }
//         }
//     }
// }




// namespace App\Services;

// use App\Models\Population;
// use Illuminate\Support\Str;

// use App\Models\Chromosome;
// use App\Models\Gene;
// use App\Models\Section;
// use App\Models\Instructor;
// use App\Models\Room;
// use App\Models\Timeslot;
// use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Exception;

// /**
//  * GeneticAlgorithmService
//  * هذا الكلاس هو "العقل المدبر" للخوارزمية الجينية.
//  * وظيفته أخذ البيانات الخام من قاعدة البيانات، تحضيرها، تشغيل الخوارزمية،
//  * ثم حفظ أفضل جدول ناتج.
//  */
// class GeneticAlgorithmService
// {
//     // --- الإعدادات والبيانات المحملة ---

//     /** @var array الإعدادات التي يتم تمريرها من المستخدم (حجم السكان، عدد الأجيال، إلخ). */
//     private array $settings;

//     /** @var Population سجل التشغيل الحالي في قاعدة البيانات. */
//     private Population $populationRun;

//     /** @var Collection قائمة بكل المدرسين المتاحين. */
//     private Collection $instructors;

//     /** @var Collection قائمة بالقاعات النظرية. */
//     private Collection $theoryRooms;

//     /** @var Collection قائمة بالقاعات العملية (المختبرات). */
//     private Collection $practicalRooms;

//     /** @var Collection قائمة بكل الفترات الزمنية المتاحة. */
//     private Collection $timeslots;

//     /** @var Collection القائمة النهائية للبلوكات (المحاضرات) التي يجب جدولتها. */
//     private Collection $lectureBlocksToSchedule;

//     /** @var array خريطة تساعد في إيجاد الفترات الزمنية المتصلة بسرعة. [timeslot_id => [next_slot_id, ...]] */
//     private array $consecutiveTimeslotsMap = [];

//     /** @var array خريطة تربط كل شعبة بمجموعة الطلاب الخاصة بها. [section_id => [student_group_id, ...]] */
//     private array $studentGroupMap = [];

//     /** @var array خريطة تربط كل بلوك بالمدرس المسؤول عنه. [lecture_unique_id => instructor_id] */
//     private array $instructorAssignmentMap = [];

//     /** @var array كاش مؤقت لتسريع عملية بناء الجيل الأول. */
//     private array $resourceUsageCache = [];


//     /**
//      * المُنشئ (Constructor)
//      * يقوم بتهيئة الخدمة بالإعدادات وسجل التشغيل.
//      */
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     /**
//      * الدالة الرئيسية (run)
//      * تدير عملية التوليد بأكملها من البداية إلى النهاية.
//      */
//     public function run()
//     {
//         // try {
//             // تحديث حالة التشغيل إلى "قيد التنفيذ"
//             $this->populationRun->update(['status' => 'running', 'start_time' => now()]);

//             // 1. تحميل وتحضير كل البيانات اللازمة بكفاءة
//             $this->loadAndPrepareData();

//             // 2. إنشاء الجيل الأول بشكل ذكي
//             $currentGenerationNumber = 1;
//             $currentPopulation = $this->createInitialPopulation($currentGenerationNumber);

//             // 3. تقييم الجيل الأول وحساب العقوبات
//             $this->evaluateFitness($currentPopulation);
//             Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//             // 4. بدء حلقة التطور للأجيال
//             $maxGenerations = $this->settings['max_generations'];
//             while ($currentGenerationNumber < $maxGenerations) {
//                 // التحقق من وجود حل مثالي لإيقاف العملية مبكراً
//                 $bestInGen = $currentPopulation->sortBy('penalty_value')->first();
//                 if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//                     Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//                     break;
//                 }

//                 // اختيار الآباء وإنشاء جيل جديد
//                 $parents = $this->selectParents($currentPopulation);
//                 $currentGenerationNumber++;
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber);
//                 $this->evaluateFitness($currentPopulation);
//                 Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");
//             }

//             // 5. الانتهاء وتحديث حالة التشغيل بالنتيجة النهائية
//             $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)->orderBy('penalty_value', 'asc')->first();
//             $this->populationRun->update([
//                 'status' => 'completed',
//                 'end_time' => now(),
//                 'best_chromosome_id' => $finalBest ? $finalBest->id : null
//             ]);
//             Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
//         // } catch (Exception $e) {
//         //     // في حالة حدوث أي خطأ، يتم تسجيله وتحديث الحالة إلى "فشل"
//         //     Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//         //     $this->populationRun->update(['status' => 'failed']);
//         //     throw $e;
//         // }
//     }

//     //======================================================================
//     // المرحلة الأولى: تحميل وتحضير البيانات
//     //======================================================================

//     /**
//      * يقوم بتحميل كل البيانات اللازمة من قاعدة البيانات وتحضيرها للاستخدام السريع في الذاكرة.
//      */
//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         // جلب كل البيانات اللازمة في أقل عدد ممكن من الاستعلامات
//         $sections = Section::with(['planSubject.subject', 'instructors'])
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])
//             ->get();

//         $sections = Section::with(['planSubject.subject.requiredRoomType', 'instructors'])
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])
//             ->get();


//         if ($sections->isEmpty()) {
//             throw new Exception("No sections found for the selected context.");
//         }

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();

//         // بناء الخرائط المساعدة لتسريع العمليات
//         $this->buildConsecutiveTimeslotsMap();
//         $this->buildStudentGroupMap($sections);

//         // توليد البلوكات النهائية التي سيتم جدولتها وتعيين المدرسين لها مسبقاً
//         $this->generateLectureBlocksAndAssignInstructors($sections);

//         if ($this->lectureBlocksToSchedule->isEmpty()) {
//             throw new Exception("No lecture blocks to schedule were found after processing credit hours.");
//         }

//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//     }

//     /**
//      * الدالة المحورية التي تقوم بتوليد البلوكات الصحيحة وتعيين المدرسين لها مسبقاً.
//      */
//     private function generateLectureBlocksAndAssignInstructors(Collection $sections)
//     {
//         $this->lectureBlocksToSchedule = collect();
//         $this->instructorAssignmentMap = [];
//         $subjectInstructorMap = []; // لتخزين المدرس الذي تم اختياره لكل مادة لضمان التناسق

//         // تجميع الشعب حسب المادة لتسهيل التعامل معها
//         $sectionsBySubject = $sections->groupBy('planSubject.subject.id');

//         foreach ($sectionsBySubject as $subjectId => $subjectSections) {
//             $firstSection = $subjectSections->first();
//             if (!$firstSection || !$firstSection->planSubject || !$firstSection->planSubject->subject) continue;
//             $subject = $firstSection->planSubject->subject;

//             // اختيار مدرس واحد فقط لهذه المادة وتخزينه
//             $assignedInstructor = $this->getRandomInstructorForSubject($subject);
//             $subjectInstructorMap[$subjectId] = $assignedInstructor;

//             // تقسيم البلوكات النظرية
//             if ($subject->theoretical_hours > 0) {
//                 $theorySection = $subjectSections->firstWhere('activity_type', 'Theory');
//                 if ($theorySection) {
//                     $totalTheorySlots = $subject->theoretical_hours * ($this->settings['theory_credit_to_slots'] ?? 1);
//                     $this->splitTheoryBlocks($theorySection, $totalTheorySlots, $assignedInstructor);
//                 }
//             }

//             // تقسيم البلوكات العملية
//             if ($subject->practical_hours > 0) {
//                 $practicalSections = $subjectSections->where('activity_type', 'Practical');
//                 foreach ($practicalSections as $practicalSection) {
//                     // حساب عدد الساعات الفعلية المطلوبة للعملي
//                     $totalPracticalSlots = $subject->practical_hours * ($this->settings['practical_credit_to_slots'] ?? 1);
//                     $this->splitPracticalBlocks($practicalSection, $totalPracticalSlots, $assignedInstructor);
//                 }
//             }
//         }
//     }

//     /**
//      * يقسم الساعات النظرية إلى بلوكات (2+1) حسب المنطق المطلوب.
//      */
//     private function splitTheoryBlocks(Section $section, int $totalSlots, Instructor $instructor)
//     {
//         $remainingSlots = $totalSlots;
//         $blockCounter = 1;

//         while ($remainingSlots > 0) {
//             // استراتيجية التقسيم: 2 ثم 1
//             $blockSlots = 1; // القيمة الافتراضية
//             if ($remainingSlots >= 2) {
//                 $blockSlots = 2;
//             }

//             $uniqueId = "{$section->id}-theory-block{$blockCounter}";
//             $this->lectureBlocksToSchedule->push((object)[
//                 'section' => $section,
//                 'block_type' => 'theory',
//                 'slots_needed' => $blockSlots,
//                 'unique_id' => $uniqueId
//             ]);
//             // تعيين نفس المدرس للبلوك
//             $this->instructorAssignmentMap[$uniqueId] = $instructor->id;

//             $remainingSlots -= $blockSlots;
//             $blockCounter++;
//         }
//     }

//     /**
//      * [تم التعديل الجذري هنا]
//      * يقسم الساعات العملية إلى بلوكات، مع إعطاء الأولوية للبلوكات الكبيرة المتصلة.
//      */
//     private function splitPracticalBlocks(Section $section, int $totalSlots, Instructor $instructor)
//     {
//         if ($totalSlots <= 0) return;

//         $remainingSlots = $totalSlots;
//         $blockCounter = 1;
//         // يمكن تعديل هذا الرقم للتحكم في حجم أكبر بلوك عملي
//         $maxBlockSize = 4;

//         while ($remainingSlots > 0) {
//             // تحديد حجم البلوك الحالي: هو إما الحجم الأقصى أو ما تبقى من ساعات، أيهما أقل
//             $blockSlots = min($remainingSlots, $maxBlockSize);

//             $uniqueId = "{$section->id}-practical-block{$blockCounter}";
//             $this->lectureBlocksToSchedule->push((object)[
//                 'section' => $section,
//                 'block_type' => 'practical',
//                 'slots_needed' => $blockSlots, // استخدام الحجم المحسوب
//                 'unique_id' => $uniqueId
//             ]);
//             $this->instructorAssignmentMap[$uniqueId] = $instructor->id;

//             $remainingSlots -= $blockSlots;
//             $blockCounter++;
//         }
//     }

//     /**
//      * يبني خريطة لمجموعات الطلاب لضمان عدم وجود تضارب للطالب.
//      */
//     private function buildStudentGroupMap(Collection $sections)
//     {
//         $this->studentGroupMap = [];
//         // تجميع الشعب حسب السياق (خطة، مستوى، فصل، فرع)
//         $sectionsByContext = $sections->groupBy(function ($section) {
//             $ps = $section->planSubject;
//             return implode('-', [$ps->plan_id, $ps->plan_level, $ps->plan_semester, $section->academic_year, $section->branch ?? 'default']);
//         });

//         foreach ($sectionsByContext as $contextKey => $sectionsInContext) {
//             // تحديد عدد المجموعات بناءً على المادة التي لديها أكبر عدد من الشعب العملية
//             $maxPracticalSections = $sectionsInContext->where('activity_type', 'Practical')
//                 ->groupBy('plan_subject_id')->map->count()->max() ?? 0;
//             $numberOfStudentGroups = $maxPracticalSections > 0 ? $maxPracticalSections : 1;

//             for ($groupIndex = 1; $groupIndex <= $numberOfStudentGroups; $groupIndex++) {
//                 // الشعب النظرية مشتركة بين جميع المجموعات
//                 $theorySections = $sectionsInContext->where('activity_type', 'Theory');
//                 foreach ($theorySections as $theorySection) {
//                     $this->studentGroupMap[$theorySection->id][] = $groupIndex;
//                 }

//                 // الشعب العملية موزعة على المجموعات
//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')
//                     ->groupBy('plan_subject_id');

//                 foreach ($practicalSectionsBySubject as $subjectId => $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id][] = $groupIndex;
//                     }
//                 }
//             }
//         }
//     }


//     /**
//      * يبني خريطة للأوقات المتصلة لتسريع البحث عن بلوكات زمنية.
//      */
//     private function buildConsecutiveTimeslotsMap()
//     {
//         $timeslotsByDay = $this->timeslots->groupBy('day');
//         $this->consecutiveTimeslotsMap = [];

//         foreach ($timeslotsByDay as $day => $dayTimeslots) {
//             $sortedSlots = $dayTimeslots->sortBy('start_time')->values();
//             for ($i = 0; $i < $sortedSlots->count(); $i++) {
//                 $currentSlotId = $sortedSlots[$i]->id;
//                 $this->consecutiveTimeslotsMap[$currentSlotId] = [];
//                 $lastSlot = $sortedSlots[$i];

//                 for ($j = $i + 1; $j < $sortedSlots->count(); $j++) {
//                     $nextSlot = $sortedSlots[$j];
//                     if ($nextSlot->start_time == $lastSlot->end_time) {
//                         $this->consecutiveTimeslotsMap[$currentSlotId][] = $nextSlot->id;
//                         $lastSlot = $nextSlot;
//                     } else {
//                         break;
//                     }
//                 }
//             }
//         }
//     }


//     //======================================================================
//     // المرحلة الثانية: إنشاء الجيل الأول
//     //======================================================================

//     /**
//      * يقوم بإنشاء الجيل الأول من الجداول بشكل ذكي.
//      */
//     private function createInitialPopulation(int $generationNumber): Collection
//     {
//         Log::info("Creating intelligent initial population (Generation #{$generationNumber})");

//         // إنشاء سجلات الكروموسومات دفعة واحدة لتحسين الأداء
//         $chromosomesToInsert = [];
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             // $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//             //  $chromosomesToInsert[] = ['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber, 'created_at' => now(), 'updated_at' => now()];
//             // $chromosomesToInsert[] = [
//             //     'population_id' => $this->populationRun->population_id,
//             //     'penalty_value' => -1,
//             //     'generation_number' => $generationNumber,
//             //     'created_at' => now(),
//             //     'updated_at' => now(),
//             // ];
//             $chromosomesToInsert[] = [
//                 'population_id' => $this->populationRun->population_id,
//                 'penalty_value' => -1, 'generation_number' => $generationNumber,
//                 'created_at' => now(),
//                 'updated_at' => now()
//             ];
//         }
//         // $this->chromosome->insert($chromosomesToInsert);
//         //  طريقة اخرى للاضافة يعمري غير المقديمة الي هي هذي $this->chromosome->insert($chromosomesToInsert);

//         Chromosome::insert($chromosomesToInsert);
//         $createdChromosomes = Chromosome::where('population_id', $this->populationRun->population_id)->where('generation_number', $generationNumber)->get();
//         // dd([
//         //     'chromosomesToInsert' => $chromosomesToInsert,
//         //     'population_size' => $this->settings['population_size'],
//         //     'populationRun' => $this->populationRun,
//         //     'populationRun_population_id' => $this->populationRun->population_id,
//         //     'createdChromosomes' => $createdChromosomes,
//         // ]);

//         foreach ($createdChromosomes as $chromosome) {
//             $this->resourceUsageCache = []; // إعادة تعيين الكاش لكل كروموسوم جديد
//             $genesToInsert = [];

//             foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//                 $instructorId = $this->instructorAssignmentMap[$lectureBlock->unique_id];
//                 $room = $this->getRandomRoomForBlock($lectureBlock);
//                 $studentGroupIds = $this->studentGroupMap[$lectureBlock->section->id] ?? [];

//                 // البحث عن أفضل مكان بناءً على استراتيجية الأولويات
//                 $foundSlots = $this->findOptimalSlotForBlock($lectureBlock, $instructorId, $room->id, $studentGroupIds);

//                 // تحديث الكاش بالموارد المستخدمة
//                 $this->updateResourceCache($foundSlots, $instructorId, $room->id, $studentGroupIds);

//                 $genesToInsert[] = [
//                     'chromosome_id' => $chromosome->chromosome_id,
//                     'lecture_unique_id' => $lectureBlock->unique_id,
//                     'section_id' => $lectureBlock->section->id,
//                     'instructor_id' => $instructorId,
//                     'room_id' => $room->id,
//                     'timeslot_ids' => json_encode($foundSlots),
//                     'student_group_id' => json_encode($studentGroupIds), // تخزين مجموعات الطلاب
//                 ];
//             }
//             // إدخال الجينات دفعة واحدة لتحسين الأداء
//             Gene::insert($genesToInsert);
//         }
//         return $createdChromosomes;
//     }

//     /**
//      * يبحث عن أفضل مكان متاح للبلوك بناءً على استراتيجية الأولويات.
//      */
//     private function findOptimalSlotForBlock($lectureBlock, int $instructorId, int $roomId, array $studentGroupIds): array
//     {
//         $possibleStartSlots = $this->getPossibleStartSlots($lectureBlock->slots_needed);

//         // الأولوية 1: البحث عن مكان مثالي (صفر تعارضات)
//         foreach ($possibleStartSlots as $startSlotId => $trialSlots) {
//             if ($this->countConflictsForSlots($trialSlots, $instructorId, $roomId, $studentGroupIds) == 0) {
//                 return $trialSlots;
//             }
//         }

//         // الأولوية 2: البحث عن مكان بدون تعارض طالب أو قاعة
//         foreach ($possibleStartSlots as $startSlotId => $trialSlots) {
//             if ($this->countConflictsForSlots($trialSlots, null, $roomId, $studentGroupIds) == 0) {
//                 return $trialSlots;
//             }
//         }

//         // الأولوية 3: البحث عن مكان بدون تعارض طالب
//         foreach ($possibleStartSlots as $startSlotId => $trialSlots) {
//             if ($this->countConflictsForSlots($trialSlots, null, null, $studentGroupIds) == 0) {
//                 return $trialSlots;
//             }
//         }

//         // الحل الأخير: اختيار أي مكان عشوائي من الأماكن المتاحة
//         return $possibleStartSlots->isNotEmpty() ? $possibleStartSlots->random() : $this->timeslots->random($lectureBlock->slots_needed)->pluck('id')->toArray();
//     }

//     /**
//      * يحسب عدد التضاربات لمجموعة من الفترات الزمنية.
//      */
//     private function countConflictsForSlots(array $slotIds, ?int $instructorId, ?int $roomId, ?array $studentGroupIds): int
//     {
//         $conflicts = 0;
//         foreach ($slotIds as $slotId) {
//             if (isset($this->resourceUsageCache[$slotId])) {
//                 if ($studentGroupIds) {
//                     foreach ($studentGroupIds as $groupId) {
//                         if (isset($this->resourceUsageCache[$slotId]['student_groups'][$groupId])) $conflicts++;
//                     }
//                 }
//                 if ($instructorId && isset($this->resourceUsageCache[$slotId]['instructors'][$instructorId])) $conflicts++;
//                 if ($roomId && isset($this->resourceUsageCache[$slotId]['rooms'][$roomId])) $conflicts++;
//             }
//         }
//         return $conflicts;
//     }


//     //======================================================================
//     // المرحلة الثالثة: التقييم والتحسين (الحلقة الرئيسية)
//     //======================================================================

//     /**
//      * [تم التصحيح النهائي]
//      * الدالة المحورية التي تقيم كل جدول (كروموسوم) وتحسب له درجة العقوبة الإجمالية.
//      */
//     private function evaluateFitness(Collection $chromosomes)
//     {
//         $chromosomeIds = $chromosomes->pluck('chromosome_id')->filter();

//         if ($chromosomeIds->isEmpty()) {
//             return;
//         }

//         $allGenes = Gene::whereIn('chromosome_id', $chromosomeIds)
//             ->with(['section.planSubject.subject', 'room.roomType', 'instructor'])
//             ->get()
//             ->groupBy('chromosome_id');

//         // [الحل النهائي] استخدام transaction لضمان سلامة البيانات
//         DB::transaction(function () use ($chromosomes, $allGenes) {
//             foreach ($chromosomes as $chromosome) {
//                 $genes = $allGenes->get($chromosome->chromosome_id, collect());

//                 if ($genes->isEmpty()) {
//                     $totalPenalty = 99999; // عقوبة قاسية للكروموسومات الفارغة
//                 } else {
//                     $totalPenalty = 0;

//                     $instructorUsage = [];
//                     $roomUsage = [];
//                     $studentGroupUsage = [];
//                     $instructorDailySlots = [];
//                     $studentGroupDailySlots = [];
//                     $subjectDailyUsage = [];

//                     foreach ($genes as $gene) {
//                         $timeslotIds = is_string($gene->timeslot_ids) ? json_decode($gene->timeslot_ids, true) : $gene->timeslot_ids;
//                         $timeslotIds = $timeslotIds ?? [];

//                         if (empty($timeslotIds) || !$this->timeslots->has($timeslotIds[0])) continue;

//                         $studentGroupIds = is_string($gene->student_group_ids) ? json_decode($gene->student_group_ids, true) : $gene->student_group_ids;
//                         $studentGroupIds = $studentGroupIds ?? [];

//                         $day = $this->timeslots->find($timeslotIds[0])->day;

//                         // --- التحقق من التعارضات الصارمة ---
//                         foreach ($timeslotIds as $timeslotId) {
//                             if ($studentGroupIds) {
//                                 foreach ($studentGroupIds as $groupId) {
//                                     if (isset($studentGroupUsage[$groupId][$timeslotId])) $totalPenalty += 2000;
//                                     $studentGroupUsage[$groupId][$timeslotId] = true;
//                                 }
//                             }
//                             if (isset($instructorUsage[$gene->instructor_id][$timeslotId])) $totalPenalty += 1000;
//                             $instructorUsage[$gene->instructor_id][$timeslotId] = true;
//                             if (isset($roomUsage[$gene->room_id][$timeslotId])) $totalPenalty += 800;
//                             $roomUsage[$gene->room_id][$timeslotId] = true;
//                         }

//                         // --- تجميع بيانات للتحقق من القيود المرنة ---
//                         $instructorDailySlots[$gene->instructor_id][$day] = ($instructorDailySlots[$gene->instructor_id][$day] ?? 0) + count($timeslotIds);
//                         if ($studentGroupIds) {
//                             foreach ($studentGroupIds as $groupId) {
//                                 $firstSlotTime = $this->timeslots->find($timeslotIds[0])->start_time;
//                                 $lastSlotTime = $this->timeslots->find(end($timeslotIds))->end_time;
//                                 if (!isset($studentGroupDailySlots[$groupId][$day])) {
//                                     $studentGroupDailySlots[$groupId][$day] = ['first' => $firstSlotTime, 'last' => $lastSlotTime];
//                                 } else {
//                                     if ($firstSlotTime < $studentGroupDailySlots[$groupId][$day]['first']) $studentGroupDailySlots[$groupId][$day]['first'] = $firstSlotTime;
//                                     if ($lastSlotTime > $studentGroupDailySlots[$groupId][$day]['last']) $studentGroupDailySlots[$groupId][$day]['last'] = $lastSlotTime;
//                                 }
//                             }
//                         }
//                         $subjectId = optional(optional($gene->section)->planSubject)->subject_id;
//                         if ($subjectId) $subjectDailyUsage[$subjectId][$day] = true;
//                     }

//                     // --- التحقق من القيود المرنة ---
//                     foreach ($genes as $gene) {
//                         if (!$gene->room || !$gene->section || !optional($gene->section)->planSubject) continue;
//                         $subject = $gene->section->planSubject->subject;
//                         $isPracticalBlock = $subject->practical_hours > 0;
//                         $isPracticalRoom = \Illuminate\Support\Str::contains(strtolower(optional($gene->room->roomType)->room_type_name), ['lab', 'مختبر']);
//                         if ($isPracticalBlock && !$isPracticalRoom) $totalPenalty += 600;
//                         if (!$isPracticalBlock && $isPracticalRoom) $totalPenalty += 300;
//                         if ($gene->section->student_count > $gene->room->room_size) $totalPenalty += 500;
//                     }

//                     foreach ($instructorDailySlots as $days) {
//                         foreach ($days as $count) {
//                             if ($count > 5) $totalPenalty += ($count - 5) * 100;
//                         }
//                     }
//                     foreach ($studentGroupDailySlots as $days) {
//                         foreach ($days as $times) {
//                             $start = new \DateTime($times['first']);
//                             $end = new \DateTime($times['last']);
//                             $hours = $end->diff($start)->h + ($end->diff($start)->i / 60);
//                             if ($hours > 7) $totalPenalty += ($hours - 7) * 50;
//                         }
//                     }
//                     $theoryBlocksBySubject = $genes->filter(fn($g) => optional(optional($g->section)->planSubject)->subject->practical_hours == 0)
//                         ->groupBy('section.planSubject.subject_id');
//                     foreach ($theoryBlocksBySubject as $blocks) {
//                         if ($blocks->count() > 1) {
//                             $daysUsed = $blocks->map(function ($g) {
//                                 $tsIds = is_string($g->timeslot_ids) ? json_decode($g->timeslot_ids, true) : $g->timeslot_ids;
//                                 return $this->timeslots->find($tsIds[0])->day;
//                             })->unique();
//                             if ($daysUsed->count() < $blocks->count()) $totalPenalty += 40;
//                         }
//                     }
//                 }

//                 // تحديث قيمة العقوبة في الكائن نفسه
//                 $chromosome->penalty_value = $totalPenalty;

//                 // [هنا التصحيح النهائي] استخدام update مباشر وآمن بدلاً من upsert
//                 Chromosome::where('chromosome_id', $chromosome->chromosome_id)
//                     ->update(['penalty_value' => $totalPenalty]);
//             }
//         });
//     }

//     /**
//      * يختار أفضل الآباء من الجيل الحالي باستخدام طريقة البطولة.
//      */
//     private function selectParents(Collection $population): array
//     {
//         $parents = [];
//         $tournamentSize = 3;
//         $populationCount = $population->count();

//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             if ($populationCount == 0) break;
//             $participants = $population->random(min($tournamentSize, $populationCount));
//             $parents[] = $participants->sortBy('penalty_value')->first();
//         }
//         return $parents;
//     }

//     /**
//      * ينشئ جيلاً جديداً من خلال عمليات التزاوج والطفرة.
//      */
//     private function createNewGeneration(array $parents, int $nextGenerationNumber): Collection
//     {
//         Log::info("Creating new generation #{$nextGenerationNumber}");
//         $childrenData = [];
//         $parentPool = array_filter($parents); // إزالة أي قيم null

//         if (empty($parentPool)) {
//             Log::warning("Parent pool is empty for generation {$nextGenerationNumber}. Cannot create new generation.");
//             return collect();
//         }

//         for ($i = 0; $i < $this->settings['population_size']; $i += 2) {
//             if (count($parentPool) < 2) {
//                 if (empty($parents)) break;
//                 $parentPool = array_filter($parents); // إعادة ملء حوض الآباء إذا لزم الأمر
//             }

//             $p1_key = array_rand($parentPool);
//             $parent1 = $parentPool[$p1_key];
//             unset($parentPool[$p1_key]);

//             $p2_key = array_rand($parentPool);
//             $parent2 = $parentPool[$p2_key];
//             unset($parentPool[$p2_key]);

//             if (!$parent1 || !$parent2) continue;

//             // عملية التزاوج
//             [$child1Lectures, $child2Lectures] = $this->performCrossover($parent1, $parent2);

//             // عملية الطفرة
//             $childrenData[] = $this->performMutation($child1Lectures);
//             if (count($childrenData) < $this->settings['population_size']) {
//                 $childrenData[] = $this->performMutation($child2Lectures);
//             }
//         }

//         // حفظ الأولاد الجدد في قاعدة البيانات
//         $newlyCreatedChromosomes = [];
//         foreach ($childrenData as $lecturesToInsert) {
//             if (!empty($lecturesToInsert)) {
//                 $newlyCreatedChromosomes[] = $this->saveChildChromosome($lecturesToInsert, $nextGenerationNumber);
//             }
//         }
//         return collect($newlyCreatedChromosomes);
//     }

//     /**
//      * يقوم بعملية التزاوج بين أبوين لإنتاج ولدين.
//      */
//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Lectures = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Lectures = $parent2->genes()->get()->keyBy('lecture_unique_id');
//         $child1Lectures = [];
//         $child2Lectures = [];

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Lectures : $p2Lectures;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Lectures : $p1Lectures;

//             $child1Lectures[$lectureBlock->unique_id] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Lectures->get($lectureBlock->unique_id);
//             $child2Lectures[$lectureBlock->unique_id] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Lectures->get($lectureBlock->unique_id);

//             $currentIndex++;
//         }
//         return [$child1Lectures, $child2Lectures];
//     }

//     /**
//      * يقوم بعملية الطفرة (تغيير عشوائي) على جينات الوليد.
//      */
//     private function performMutation(array $lectures): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate']) {
//             if (empty($lectures)) return [];

//             $lectureKeyToMutate = array_rand($lectures);
//             $lectureToMutate = $lectures[$lectureKeyToMutate];
//             if (!$lectureToMutate) return $lectures;

//             $originalLectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $lectureKeyToMutate);
//             if (!$originalLectureBlock) return $lectures;

//             // الحفاظ على نفس المدرس للمادة
//             $instructorId = $this->instructorAssignmentMap[$originalLectureBlock->unique_id];
//             $newRoom = $this->getRandomRoomForBlock($originalLectureBlock);
//             $studentGroupIds = $this->studentGroupMap[$originalLectureBlock->section->id] ?? [];

//             // إيجاد فترات زمنية جديدة
//             $newTimeslots = $this->findOptimalSlotForBlock($originalLectureBlock, $instructorId, $newRoom->id, $studentGroupIds);

//             // تحديث الجين الذي حدثت له الطفرة
//             $lectureToMutate->room_id = $newRoom->id;
//             $lectureToMutate->timeslot_ids = json_encode($newTimeslots);
//             $lectures[$lectureKeyToMutate] = $lectureToMutate;
//         }
//         return $lectures;
//     }

//     /**
//      * يحفظ الكروموسوم الوليد وجيناته في قاعدة البيانات.
//      */
//     private function saveChildChromosome(array $lectures, int $generationNumber): Chromosome
//     {
//         $chromosome = Chromosome::create(['population_id' => $this->populationRun->population_id, 'penalty_value' => -1, 'generation_number' => $generationNumber]);

//         $genesToInsert = [];
//         foreach ($lectures as $lectureGene) {
//             if (is_null($lectureGene)) continue;
//             $genesToInsert[] = [
//                 'chromosome_id' => $chromosome->chromosome_id,
//                 'lecture_unique_id' => $lectureGene->lecture_unique_id,
//                 'section_id' => $lectureGene->section_id,
//                 'instructor_id' => $lectureGene->instructor_id,
//                 'room_id' => $lectureGene->room_id,
//                 'timeslot_ids' => is_string($lectureGene->timeslot_ids) ? $lectureGene->timeslot_ids : json_encode($lectureGene->timeslot_ids),
//                 'student_group_id' => is_string($lectureGene->student_group_ids) ? $lectureGene->student_group_ids : json_encode($lectureGene->student_group_ids),
//             ];
//         }
//         Gene::insert($genesToInsert);
//         return $chromosome;
//     }

//     //======================================================================
//     // دوال مساعدة
//     //======================================================================

//     private function getRandomInstructorForSubject(\App\Models\Subject $subject)
//     {
//         $suitableInstructors = $this->instructors->filter(fn($instructor) => $instructor->subjects->contains($subject->id));
//         return $suitableInstructors->isNotEmpty() ? $suitableInstructors->random() : $this->instructors->random();
//     }

//     private function getRandomRoomForBlock(\stdClass $lectureBlock)
//     {
//         $section = $lectureBlock->section;
//         $roomsSource = ($lectureBlock->block_type === 'practical') ? $this->practicalRooms : $this->theoryRooms;
//         if ($roomsSource->isEmpty()) {
//             // كحل أخير، استخدم أي نوع من القاعات إذا كان النوع المطلوب غير متوفر
//             $roomsSource = ($lectureBlock->block_type === 'practical') ? $this->theoryRooms : $this->practicalRooms;
//             if ($roomsSource->isEmpty()) return Room::all()->random(); // كحل أخير جداً
//         }

//         $suitableRooms = $roomsSource->where('room_size', '>=', $section->student_count);
//         return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $roomsSource->random();
//     }

//     private function getPossibleStartSlots(int $slotsNeeded): Collection
//     {
//         return $this->timeslots->filter(function ($slot) use ($slotsNeeded) {
//             // التأكد من أن الفترة الزمنية موجودة في خريطة الأوقات المتصلة وأنها كافية
//             return isset($this->consecutiveTimeslotsMap[$slot->id]) && (count($this->consecutiveTimeslotsMap[$slot->id]) + 1) >= $slotsNeeded;
//         })->mapWithKeys(function ($slot) use ($slotsNeeded) {
//             // إنشاء البلوك الزمني الكامل
//             return [$slot->id => array_merge([$slot->id], array_slice($this->consecutiveTimeslotsMap[$slot->id], 0, $slotsNeeded - 1))];
//         });
//     }

//     private function updateResourceCache(array $slotIds, int $instructorId, int $roomId, ?array $studentGroupIds): void
//     {
//         foreach ($slotIds as $slotId) {
//             $this->resourceUsageCache[$slotId]['instructors'][$instructorId] = true;
//             $this->resourceUsageCache[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupIds) {
//                 foreach ($studentGroupIds as $groupId) {
//                     $this->resourceUsageCache[$slotId]['student_groups'][$groupId] = true;
//                 }
//             }
//         }
//     }
// }
//////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////


// المفروض هذا كودي هههههههههههههههه


// namespace App\Services;

// use App\Models\Population;
// use Illuminate\Support\Str;
// use App\Models\Chromosome;
// use App\Models\Gene;
// use App\Models\Section;
// use App\Models\Instructor;
// use App\Models\Room;
// use App\Models\Timeslot;
// use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
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
//     private Collection $lectureBlocksToSchedule;
//     private array $consecutiveTimeslotsMap = [];
//     private array $studentGroupMap = [];
//     private array $instructorAssignmentMap = [];
//     private array $resourceUsageCache = [];

//     /**
//      * المُنشئ (Constructor)
//      * يقوم بتهيئة الخدمة بالإعدادات وسجل التشغيل.
//      */
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     /**
//      * الدالة الرئيسية (run)
//      * تدير عملية التوليد بأكملها من البداية إلى النهاية.
//      */
//     public function run()
//     {
//         try {
//             $this->populationRun->update(['status' => 'running', 'start_time' => now()]);
//             $this->loadAndPrepareData();

//             $currentGenerationNumber = 1;
//             // **(تصحيح)**: تمرير populationRun للدالة
//             $currentPopulation = $this->createInitialPopulation($currentGenerationNumber, $this->populationRun);
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
//                 // **(تصحيح)**: تمرير populationRun للدالة
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber, $this->populationRun);
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
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     //======================================================================
//     // المرحلة الأولى: تحميل وتحضير البيانات
//     //======================================================================

//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         // **(حل مشكلة العلاقة)**: استدعاء العلاقات بشكل آمن وبسيط
//         $sections = Section::with(['planSubject.subject', 'instructors'])
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])
//             ->get();

//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();

//         $this->buildConsecutiveTimeslotsMap();
//         $this->buildStudentGroupMap($sections);
//         $this->generateLectureBlocksAndAssignInstructors($sections);

//         if ($this->lectureBlocksToSchedule->isEmpty()) throw new Exception("No lecture blocks to schedule were found after processing credit hours.");
//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//     }

//     private function generateLectureBlocksAndAssignInstructors(Collection $sections)
//     {
//         $this->lectureBlocksToSchedule = collect();
//         $this->instructorAssignmentMap = [];
//         $instructorLoad = $this->instructors->mapWithKeys(fn($inst) => [$inst->id => 0])->toArray();

//         $sectionsBySubject = $sections->groupBy('planSubject.subject.id');

//         foreach ($sectionsBySubject as $subjectId => $subjectSections) {
//             $firstSection = $subjectSections->first();
//             $subject = optional(optional($firstSection)->planSubject)->subject;
//             if (!$subject) continue;

//             $assignedInstructor = $this->getLeastLoadedInstructorForSubject($subject, $instructorLoad);

//             if ($subject->theoretical_hours > 0) {
//                 $theorySection = $subjectSections->firstWhere('activity_type', 'Theory');
//                 if ($theorySection) {
//                     $totalTheorySlots = $subject->theoretical_hours * ($this->settings['theory_credit_to_slots'] ?? 1);
//                     // $this->splitAndAssignBlocks($theorySection, 'theory', $totalTheorySlots, $assignedInstructor, $instructorLoad);
//                     $this->splitTheoryBlocks($theorySection, $totalTheorySlots, $assignedInstructor);
//                 }
//             }
//             if ($subject->practical_hours > 0) {
//                 $practicalSections = $subjectSections->where('activity_type', 'Practical');
//                 foreach ($practicalSections as $practicalSection) {
//                     $totalPracticalSlots = $subject->practical_hours * ($this->settings['practical_credit_to_slots'] ?? 1);
//                     // $this->splitAndAssignBlocks($practicalSection, 'practical', $totalPracticalSlots, $assignedInstructor, $instructorLoad);
//                     $this->splitPracticalBlocks($practicalSection, $totalPracticalSlots, $assignedInstructor);
//                 }
//             }
//         }
//     }


//     private function splitTheoryBlocks(Section $section, int $totalSlots, Instructor $instructor)
//     {
//         $remainingSlots = $totalSlots;
//         $blockCounter = 1;

//         while ($remainingSlots > 0) {
//             // استراتيجية التقسيم: 2 ثم 1
//             $blockSlots = 1; // القيمة الافتراضية
//             if ($remainingSlots >= 2) {
//                 $blockSlots = 2;
//             }

//             $uniqueId = "{$section->id}-theory-block{$blockCounter}";
//             $this->lectureBlocksToSchedule->push((object)[
//                 'section' => $section,
//                 'block_type' => 'theory',
//                 'slots_needed' => $blockSlots,
//                 'unique_id' => $uniqueId,
//                 'block_duration' => $blockSlots * 50,
//             ]);
//             // تعيين نفس المدرس للبلوك
//             $this->instructorAssignmentMap[$uniqueId] = $instructor->id;

//             $remainingSlots -= $blockSlots;
//             $blockCounter++;
//         }
//     }
//     private function splitPracticalBlocks(Section $section, int $totalSlots, Instructor $instructor)
//     {
//         if ($totalSlots <= 0) return;

//         $remainingSlots = $totalSlots;
//         $blockCounter = 1;
//         // يمكن تعديل هذا الرقم للتحكم في حجم أكبر بلوك عملي
//         $maxBlockSize = 4;

//         while ($remainingSlots > 0) {
//             // تحديد حجم البلوك الحالي: هو إما الحجم الأقصى أو ما تبقى من ساعات، أيهما أقل
//             $blockSlots = min($remainingSlots, $maxBlockSize);

//             $uniqueId = "{$section->id}-practical-block{$blockCounter}";
//             $this->lectureBlocksToSchedule->push((object)[
//                 'section' => $section,
//                 'block_type' => 'practical',
//                 'slots_needed' => $blockSlots, // استخدام الحجم المحسوب
//                 'unique_id' => $uniqueId,
//                 'block_duration' => $blockSlots * 50,
//             ]);
//             $this->instructorAssignmentMap[$uniqueId] = $instructor->id;

//             $remainingSlots -= $blockSlots;
//             $blockCounter++;
//         }
//     }

//     // private function splitAndAssignBlocks(Section $section, string $type, int $totalSlots, Instructor $instructor, array &$instructorLoad)
//     // {
//     //     $remainingSlots = $totalSlots;
//     //     $blockCounter = 1;
//     //     $strategy = ($type == 'theory') ? [2, 1] : [4];

//     //     while ($remainingSlots > 0) {
//     //         $blockSlots = 0;
//     //         foreach ($strategy as $size) {
//     //             if ($remainingSlots >= $size) {
//     //                 $blockSlots = $size;
//     //                 break;
//     //             }
//     //         }
//     //         if ($blockSlots == 0) $blockSlots = $remainingSlots;

//     //         $uniqueId = "{$section->id}-{$type}-block{$blockCounter}";
//     //         $this->lectureBlocksToSchedule->push((object)[
//     //             'section' => $section,
//     //             'block_type' => $type,
//     //             'slots_needed' => $blockSlots,
//     //             'block_duration' => $blockSlots * 50,
//     //             'unique_id' => $uniqueId
//     //         ]);
//     //         $this->instructorAssignmentMap[$uniqueId] = $instructor->id;
//     //         $instructorLoad[$instructor->id] += $blockSlots;

//     //         $remainingSlots -= $blockSlots;
//     //         $blockCounter++;
//     //     }
//     // }

//     private function getLeastLoadedInstructorForSubject(\App\Models\Subject $subject, array $instructorLoad)
//     {
//         $suitableInstructors = $this->instructors->filter(fn($inst) => $inst->subjects->contains($subject->id));
//         if ($suitableInstructors->isEmpty()) return $this->instructors->random();

//         return $suitableInstructors->sortBy(fn($inst) => $instructorLoad[$inst->id] ?? 0)->first();
//     }

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
//                     $this->studentGroupMap[$theorySection->id][] = $groupIndex;
//                 }

//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
//                 foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id][] = $groupIndex;
//                     }
//                 }
//             }
//         }
//     }

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

//     //======================================================================
//     // المرحلة الثانية: إنشاء الجيل الأول
//     //======================================================================

//     private function createInitialPopulation(int $generationNumber, Population $populationRun): Collection
//     {
//         Log::info("Creating intelligent initial population (Generation #{$generationNumber})");

//         $createdChromosomes = collect();
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosome = Chromosome::create([
//                 'population_id' => $populationRun->population_id,
//                 'penalty_value' => -1,
//                 'generation_number' => $generationNumber
//             ]);
//             $this->generateGenesForChromosome($chromosome);
//             $createdChromosomes->push($chromosome);
//         }

//         return $createdChromosomes;
//     }

//     private function generateGenesForChromosome(Chromosome $chromosome)
//     {
//         $this->resourceUsageCache = [];
//         $genesToInsert = [];

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $instructorId = $this->instructorAssignmentMap[$lectureBlock->unique_id];
//             $room = $this->getRandomRoomForBlock($lectureBlock);
//             $studentGroupIds = $this->studentGroupMap[$lectureBlock->section->id] ?? [];

//             $foundSlots = $this->findOptimalSlotForBlock($lectureBlock, $instructorId, $room->id, $studentGroupIds);
//             $this->updateResourceCache($foundSlots, $instructorId, $room->id, $studentGroupIds);

//             dd([
//                 'lectureBlock' => $lectureBlock->unique_id,
//             ]);
//             // بما أننا سنستخدم حقل JSON، ننشئ جيناً واحداً فقط
//             $genesToInsert[] = [
//                 'chromosome_id' => $chromosome->chromosome_id,
//                 'lecture_unique_id' => $lectureBlock->unique_id,
//                 'section_id' => $lectureBlock->section->id,
//                 'instructor_id' => $instructorId,
//                 'room_id' => $room->id,
//                 'timeslot_ids' => json_encode($foundSlots),
//                 'student_group_id' => json_encode($studentGroupIds),
//                 'block_type' => $lectureBlock->block_type,
//                 'block_duration' => $lectureBlock->block_duration,
//             ];
//         }

//         if (!empty($genesToInsert)) {
//             Gene::insert($genesToInsert);
//         }
//     }

//     private function findOptimalSlotForBlock($lectureBlock, int $instructorId, int $roomId, array $studentGroupIds): array
//     {
//         $possibleStartSlots = $this->getPossibleStartSlots($lectureBlock->slots_needed);

//         $shuffledSlots = $possibleStartSlots->shuffle();
//         foreach ($shuffledSlots as $trialSlots) {
//             if ($this->countConflictsForSlots($trialSlots, $instructorId, $roomId, $studentGroupIds) == 0) {
//                 return $trialSlots;
//             }
//         }

//         return $possibleStartSlots->isNotEmpty() ? $possibleStartSlots->random() : $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
//     }

//     private function countConflictsForSlots(array $slotIds, ?int $instructorId, ?int $roomId, ?array $studentGroupIds): int
//     {
//         $conflicts = 0;
//         foreach ($slotIds as $slotId) {
//             if (isset($this->resourceUsageCache[$slotId])) {
//                 if ($studentGroupIds) {
//                     foreach ($studentGroupIds as $groupId) {
//                         if (isset($this->resourceUsageCache[$slotId]['student_groups'][$groupId])) $conflicts++;
//                     }
//                 }
//                 if ($instructorId && isset($this->resourceUsageCache[$slotId]['instructors'][$instructorId])) $conflicts++;
//                 if ($roomId && isset($this->resourceUsageCache[$slotId]['rooms'][$roomId])) $conflicts++;
//             }
//         }
//         return $conflicts;
//     }

//     private function updateResourceCache(array $slotIds, int $instructorId, int $roomId, ?array $studentGroupIds): void
//     {
//         foreach ($slotIds as $slotId) {
//             $this->resourceUsageCache[$slotId]['instructors'][$instructorId] = true;
//             $this->resourceUsageCache[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupIds) {
//                 foreach ($studentGroupIds as $groupId) {
//                     $this->resourceUsageCache[$slotId]['student_groups'][$groupId] = true;
//                 }
//             }
//         }
//     }

//     //======================================================================
//     // المرحلة الثالثة: التقييم والتحسين
//     //======================================================================

//     // private function evaluateFitness(Collection $chromosomes)
//     // {
//     //     DB::transaction(function () use ($chromosomes) {
//     //         foreach ($chromosomes as $chromosome) {
//     //             $genes = $chromosome->genes()->with(['section.planSubject.subject', 'room.roomType', 'instructor'])->get();
//     //             $totalPenalty = 0;
//     //             $resourceUsageMap = [];

//     //             foreach ($genes as $gene) {
//     //                 $timeslotIds = $gene->timeslot_ids ?? [];
//     //                 $studentGroupIds = $gene->student_group_id ?? [];

//     //                 // --- التحقق من التعارضات الصارمة ---
//     //                 foreach ($timeslotIds as $timeslotId) {
//     //                     if ($studentGroupIds) {
//     //                         foreach ($studentGroupIds as $groupId) {
//     //                             if (isset($resourceUsageMap[$timeslotId]['student_groups'][$groupId])) $totalPenalty += 2000;
//     //                             $resourceUsageMap[$timeslotId]['student_groups'][$groupId] = true;
//     //                         }
//     //                     }
//     //                     if (isset($resourceUsageMap[$timeslotId]['instructors'][$gene->instructor_id])) $totalPenalty += 1000;
//     //                     $resourceUsageMap[$timeslotId]['instructors'][$gene->instructor_id] = true;

//     //                     if (isset($resourceUsageMap[$timeslotId]['rooms'][$gene->room_id])) $totalPenalty += 800;
//     //                     $resourceUsageMap[$timeslotId]['rooms'][$gene->room_id] = true;
//     //                 }

//     //                 // --- التحقق من القيود المرنة ---
//     //                 if (!$gene->room || !$gene->section || !optional($gene->section)->planSubject) continue;
//     //                 $subject = $gene->section->planSubject->subject;
//     //                 $isPracticalBlock = Str::contains($gene->lecture_unique_id, 'practical');
//     //                 $isPracticalRoom = Str::contains(strtolower(optional($gene->room->roomType)->room_type_name), ['lab', 'مختبر']);

//     //                 if ($isPracticalBlock && !$isPracticalRoom) $totalPenalty += 600;
//     //                 if (!$isPracticalBlock && $isPracticalRoom) $totalPenalty += 300;
//     //                 if ($gene->section->student_count > $gene->room->room_size) $totalPenalty += 500;
//     //             }

//     //             $chromosome->penalty_value = $totalPenalty;
//     //             Chromosome::where('chromosome_id', $chromosome->chromosome_id)->update(['penalty_value' => $totalPenalty]);
//     //         }
//     //     });
//     // }

//     /**
//      * [الدالة الرئيسية للتقييم]
//      * تمر على كل كروموسوم، تستدعي دوال حساب العقوبات، ثم تحدث النتيجة.
//      */
//     private function evaluateFitness(Collection $chromosomes)
//     {
//         // استخدام transaction لضمان أن كل التحديثات تتم معاً أو لا تتم على الإطلاق
//         DB::transaction(function () use ($chromosomes) {
//             foreach ($chromosomes as $chromosome) {
//                 // جلب الجينات مرة واحدة فقط لتحسين الأداء
//                 $genes = $chromosome->genes()->with(['section.planSubject.subject', 'room.roomType', 'instructor'])->get();

//                 if ($genes->isEmpty()) {
//                     // عقوبة قاسية للكروموسومات الفارغة
//                     $this->updateChromosomeFitness($chromosome, ['empty_chromosome' => 99999]);
//                     continue;
//                 }

//                 // مصفوفة لتخزين كل أنواع العقوبات بشكل منفصل
//                 $resourceUsageMap = [];
//                 $penalties = [];

//                 // حساب كل عقوبة صارمة (Hard Constraint) على حدة
//                 // $penalties['student_conflict_penalty'] = $this->calculateStudentConflicts($genes);
//                 // $penalties['teacher_conflict_penalty'] = $this->calculateTeacherConflicts($genes);
//                 // $penalties['room_conflict_penalty'] = $this->calculateRoomConflicts($genes);
//                 // **(الأهم)** نحسب كل العقوبات الصارمة التي تعتمد على الوقت أولاً
//                 // ونمرر الخريطة بالمرجع `&` ليتم بناؤها تدريجياً
//                 $penalties['student_conflict_penalty'] = $this->calculateStudentConflicts($genes, $resourceUsageMap);
//                 $penalties['teacher_conflict_penalty'] = $this->calculateTeacherConflicts($genes, $resourceUsageMap);
//                 $penalties['room_conflict_penalty'] = $this->calculateRoomConflicts($genes, $resourceUsageMap);

//                 $penalties['capacity_conflict_penalty'] = $this->calculateCapacityConflicts($genes);
//                 $penalties['room_type_conflict_penalty'] = $this->calculateRoomTypeConflicts($genes);
//                 $penalties['teacher_eligibility_conflict_penalty'] = $this->calculateTeacherEligibilityConflicts($genes);

//                 // تجميع النتائج وتحديث الكروموسوم في قاعدة البيانات
//                 $this->updateChromosomeFitness($chromosome, $penalties);
//             }
//         });
//     }

//     /**
//      * تحسب تعارضات الطلاب (نفس مجموعة الطلاب في نفس الوقت)، وتأخذ امتداد البلوك في الاعتبار.
//      */
//     private function calculateStudentConflicts(Collection $genes, array &$usageMap): int
//     {
//         $penalty = 0;
//         foreach ($genes as $gene) {
//             $studentGroupIds = $gene->student_group_ids ?? [];
//             // نمر على كل فترة زمنية يشغلها هذا البلوك
//             foreach ($gene->timeslot_ids as $timeslotId) {
//                 // نمر على كل مجموعة طلاب تنتمي لها هذه الشعبة
//                 foreach ($studentGroupIds as $groupId) {
//                     // نتحقق إذا كانت هذه المجموعة مشغولة مسبقاً في هذه الفترة الزمنية
//                     if (isset($usageMap['student_groups'][$groupId][$timeslotId])) {
//                         $penalty += 2000;
//                     }
//                     // نسجل أن هذه المجموعة أصبحت مشغولة في هذه الفترة
//                     $usageMap['student_groups'][$groupId][$timeslotId] = true;
//                 }
//             }
//         }
//         return $penalty;
//     }

//     /**
//      * تحسب تعارضات المدرسين (نفس المدرس في نفس الوقت)، وتأخذ امتداد البلوك في الاعتبار.
//      */
//     private function calculateTeacherConflicts(Collection $genes, array &$usageMap): int
//     {
//         $penalty = 0;
//         foreach ($genes as $gene) {
//             // نمر على كل فترة زمنية يشغلها هذا البلوك
//             foreach ($gene->timeslot_ids as $timeslotId) {
//                 // نتحقق إذا كان هذا المدرس مشغولاً مسبقاً في هذه الفترة الزمنية
//                 if (isset($usageMap['instructors'][$gene->instructor_id][$timeslotId])) {
//                     $penalty += 1000;
//                 }
//                 // نسجل أن هذا المدرس أصبح مشغولاً في هذه الفترة
//                 $usageMap['instructors'][$gene->instructor_id][$timeslotId] = true;
//             }
//         }
//         return $penalty;
//     }

//     /**
//      * تحسب تعارضات القاعات (نفس القاعة في نفس الوقت)، وتأخذ امتداد البلوك في الاعتبار.
//      */
//     private function calculateRoomConflicts(Collection $genes, array &$usageMap): int
//     {
//         $penalty = 0;
//         foreach ($genes as $gene) {
//             // نمر على كل فترة زمنية يشغلها هذا البلوك
//             foreach ($gene->timeslot_ids as $timeslotId) {
//                 // نتحقق إذا كانت هذه القاعة مشغولة مسبقاً في هذه الفترة الزمنية
//                 if (isset($usageMap['rooms'][$gene->room_id][$timeslotId])) {
//                     $penalty += 800;
//                 }
//                 // نسجل أن هذه القاعة أصبحت مشغولة في هذه الفترة
//                 $usageMap['rooms'][$gene->room_id][$timeslotId] = true;
//             }
//         }
//         return $penalty;
//     }

//     /**
//      * تحسب تعارضات الطلاب (نفس مجموعة الطلاب في نفس الوقت).
//      */
//     // private function calculateStudentConflicts(Collection $genes): int
//     // {
//     //     $penalty = 0;
//     //     $studentGroupUsage = [];
//     //     foreach ($genes as $gene) {
//     //         $studentGroupIds = $gene->student_group_ids ?? [];
//     //         foreach ($gene->timeslot_ids as $timeslotId) {
//     //             foreach ($studentGroupIds as $groupId) {
//     //                 if (isset($studentGroupUsage[$groupId][$timeslotId])) {
//     //                     $penalty += 2000; // عقوبة عالية جداً
//     //                 }
//     //                 $studentGroupUsage[$groupId][$timeslotId] = true;
//     //             }
//     //         }
//     //     }
//     //     return $penalty;
//     // }

//     // /**
//     //  * تحسب تعارضات المدرسين (نفس المدرس في نفس الوقت).
//     //  */
//     // private function calculateTeacherConflicts(Collection $genes): int
//     // {
//     //     $penalty = 0;
//     //     $instructorUsage = [];
//     //     foreach ($genes as $gene) {
//     //         foreach ($gene->timeslot_ids as $timeslotId) {
//     //             if (isset($instructorUsage[$gene->instructor_id][$timeslotId])) {
//     //                 $penalty += 1000;
//     //             }
//     //             $instructorUsage[$gene->instructor_id][$timeslotId] = true;
//     //         }
//     //     }
//     //     return $penalty;
//     // }

//     // /**
//     //  * تحسب تعارضات القاعات (نفس القاعة في نفس الوقت).
//     //  */
//     // private function calculateRoomConflicts(Collection $genes): int
//     // {
//     //     $penalty = 0;
//     //     $roomUsage = [];
//     //     foreach ($genes as $gene) {
//     //         foreach ($gene->timeslot_ids as $timeslotId) {
//     //             if (isset($roomUsage[$gene->room_id][$timeslotId])) {
//     //                 $penalty += 800;
//     //             }
//     //             $roomUsage[$gene->room_id][$timeslotId] = true;
//     //         }
//     //     }
//     //     return $penalty;
//     // }

//     /**
//      * تحسب عقوبات تجاوز سعة القاعة.
//      */
//     private function calculateCapacityConflicts(Collection $genes): int
//     {
//         $penalty = 0;
//         // نمر على البلوكات الفريدة فقط لأن السعة ثابتة لكل البلوك
//         foreach ($genes->unique('lecture_unique_id') as $gene) {
//             if ($gene->section->student_count > $gene->room->room_size) {
//                 $penalty += 500;
//             }
//         }
//         return $penalty;
//     }

//     /**
//      * تحسب عقوبات عدم تطابق نوع القاعة مع نوع المحاضرة.
//      */
//     private function calculateRoomTypeConflicts(Collection $genes): int
//     {
//         $penalty = 0;
//         foreach ($genes->unique('lecture_unique_id') as $gene) {
//             $isPracticalBlock = Str::contains($gene->lecture_unique_id, 'practical');
//             $isPracticalRoom = Str::contains(strtolower(optional($gene->room->roomType)->room_type_name), ['lab', 'مختبر']);

//             if ($isPracticalBlock && !$isPracticalRoom) {
//                 $penalty += 600; // محاضرة عملية في قاعة نظرية
//             }
//             if (!$isPracticalBlock && $isPracticalRoom) {
//                 $penalty += 300; // محاضرة نظرية في قاعة عملية (أقل أهمية)
//             }
//         }
//         return $penalty;
//     }

//     /**
//      * تحسب عقوبات عدم أهلية المدرس لتدريس المادة.
//      */
//     private function calculateTeacherEligibilityConflicts(Collection $genes): int
//     {
//         $penalty = 0;
//         foreach ($genes->unique('lecture_unique_id') as $gene) {
//             if (!optional($gene->instructor)->canTeach(optional(optional($gene->section)->planSubject)->subject)) {
//                 $penalty += 2000; // عقوبة عالية جداً
//             }
//         }
//         return $penalty;
//     }

//     /**
//      * دالة أخيرة تقوم بتجميع العقوبات وحساب الـ Fitness وتحديث قاعدة البيانات.
//      */
//     private function updateChromosomeFitness(Chromosome $chromosome, array $penalties)
//     {
//         // حساب مجموع العقوبات
//         $totalPenalty = array_sum($penalties);

//         // حساب قيمة الجودة (Fitness)
//         $fitnessValue = 1 / (1 + $totalPenalty);

//         // دمج مجموع العقوبات وقيمة الجودة مع مصفوفة العقوبات التفصيلية
//         $updateData = array_merge($penalties, [
//             'penalty_value' => $totalPenalty,
//             'fitness_value' => $fitnessValue,
//         ]);

//         // تحديث سجل الكروموسوم في قاعدة البيانات
//         Chromosome::where('chromosome_id', $chromosome->chromosome_id)->update($updateData);

//         // تحديث الكائن في الذاكرة أيضاً
//         $chromosome->fill($updateData);
//     }
//     //***************************************************************************************************** */

//     // إضافة هذه الدوال المحسّنة في GeneticAlgorithmService

//     /**
//      * اختيار الآباء - دعم طرق متعددة
//      */
//     private function selectParents(Collection $population): array
//     {
//         $selectionType = $this->settings['selection_type_id'] ?? 1;
//         $selectionSize = $this->settings['selection_size'] ?? 3;

//         switch ($selectionType) {
//             case 1: // Tournament Selection
//                 return $this->tournamentSelection($population, $selectionSize);
//             case 2: // Roulette Wheel Selection
//                 return $this->rouletteWheelSelection($population);
//             case 3: // Rank Selection
//                 return $this->rankSelection($population);
//             case 4: // Elitism + Tournament
//                 return $this->elitismWithTournament($population, $selectionSize);
//             default:
//                 return $this->tournamentSelection($population, $selectionSize);
//         }
//     }

//     /**
//      * Tournament Selection - الطريقة الحالية محسّنة
//      */
//     private function tournamentSelection(Collection $population, int $tournamentSize): array
//     {
//         $parents = [];
//         $populationCount = $population->count();

//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             if ($populationCount == 0) break;

//             // اختيار عشوائي للمشاركين في البطولة
//             $participants = $population->random(min($tournamentSize, $populationCount));

//             // الفائز هو صاحب أقل penalty
//             $winner = $participants->sortBy('penalty_value')->first();
//             $parents[] = $winner;
//         }

//         return $parents;
//     }

//     /**
//      * Roulette Wheel Selection
//      */
//     private function rouletteWheelSelection(Collection $population): array
//     {
//         $parents = [];

//         // حساب مجموع fitness للجميع
//         $totalFitness = $population->sum('fitness_value');

//         if ($totalFitness == 0) {
//             // في حالة أن جميع الكروموسومات لها fitness = 0
//             return $population->random($this->settings['population_size'])->toArray();
//         }

//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $randomValue = lcg_value() * $totalFitness;
//             $currentSum = 0;

//             foreach ($population as $chromosome) {
//                 $currentSum += $chromosome->fitness_value;
//                 if ($currentSum >= $randomValue) {
//                     $parents[] = $chromosome;
//                     break;
//                 }
//             }
//         }

//         return $parents;
//     }

//     /**
//      * Rank Selection
//      */
//     private function rankSelection(Collection $population): array
//     {
//         $parents = [];

//         // ترتيب الكروموسومات حسب fitness
//         $ranked = $population->sortByDesc('fitness_value')->values();
//         $populationSize = $ranked->count();

//         // إعطاء رتبة لكل كروموسوم
//         $totalRank = ($populationSize * ($populationSize + 1)) / 2;

//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $randomValue = lcg_value() * $totalRank;
//             $currentSum = 0;

//             foreach ($ranked as $index => $chromosome) {
//                 $rank = $populationSize - $index;
//                 $currentSum += $rank;
//                 if ($currentSum >= $randomValue) {
//                     $parents[] = $chromosome;
//                     break;
//                 }
//             }
//         }

//         return $parents;
//     }

//     /**
//      * Elitism with Tournament - يحتفظ بأفضل الحلول ويطبق Tournament على الباقي
//      */
//     private function elitismWithTournament(Collection $population, int $tournamentSize): array
//     {
//         $eliteSize = max(2, (int)($this->settings['population_size'] * 0.1)); // 10% نخبة

//         // احتفظ بأفضل الحلول
//         $elite = $population->sortBy('penalty_value')->take($eliteSize);

//         // طبق Tournament Selection على الباقي
//         $remainingNeeded = $this->settings['population_size'] - $eliteSize;
//         $remainingParents = [];

//         for ($i = 0; $i < $remainingNeeded; $i++) {
//             $participants = $population->random(min($tournamentSize, $population->count()));
//             $remainingParents[] = $participants->sortBy('penalty_value')->first();
//         }

//         return array_merge($elite->toArray(), $remainingParents);
//     }

//     /**
//      * Crossover محسّن - دعم أنواع متعددة
//      */
//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         // تحقق من معدل التقاطع
//         if (lcg_value() > $this->settings['crossover_rate']) {
//             // لا تقاطع، أعد الوالدين كما هما
//             return [
//                 $parent1->genes()->get()->toArray(),
//                 $parent2->genes()->get()->toArray()
//             ];
//         }

//         $crossoverType = $this->populationRun->crossover_id ?? 1;

//         switch ($crossoverType) {
//             case 1: // Single Point Crossover
//                 return $this->singlePointCrossover($parent1, $parent2);
//             case 2: // Two Point Crossover
//                 return $this->twoPointCrossover($parent1, $parent2);
//             case 3: // Uniform Crossover
//                 return $this->uniformCrossover($parent1, $parent2);
//                 // case 4: // PMX (Partially Mapped Crossover)
//                 // return $this->pmxCrossover($parent1, $parent2);
//             default:
//                 return $this->singlePointCrossover($parent1, $parent2);
//         }
//     }

//     /**
//      * Single Point Crossover - الطريقة الحالية
//      */
//     private function singlePointCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');

//         $child1Genes = [];
//         $child2Genes = [];

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Genes : $p2Genes;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Genes : $p1Genes;

//             $child1Genes[] = $sourceForChild1->get($lectureBlock->unique_id) ??
//                 $p2Genes->get($lectureBlock->unique_id);
//             $child2Genes[] = $sourceForChild2->get($lectureBlock->unique_id) ??
//                 $p1Genes->get($lectureBlock->unique_id);

//             $currentIndex++;
//         }

//         return [array_filter($child1Genes), array_filter($child2Genes)];
//     }

//     /**
//      * Two Point Crossover
//      */
//     private function twoPointCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');

//         $child1Genes = [];
//         $child2Genes = [];

//         $totalBlocks = $this->lectureBlocksToSchedule->count();
//         $point1 = rand(1, $totalBlocks - 2);
//         $point2 = rand($point1 + 1, $totalBlocks - 1);

//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             // المنطقة بين النقطتين تأتي من الوالد الآخر
//             $fromParent2 = ($currentIndex >= $point1 && $currentIndex < $point2);

//             $sourceForChild1 = $fromParent2 ? $p2Genes : $p1Genes;
//             $sourceForChild2 = $fromParent2 ? $p1Genes : $p2Genes;

//             $child1Genes[] = $sourceForChild1->get($lectureBlock->unique_id);
//             $child2Genes[] = $sourceForChild2->get($lectureBlock->unique_id);

//             $currentIndex++;
//         }

//         return [array_filter($child1Genes), array_filter($child2Genes)];
//     }

//     /**
//      * Uniform Crossover
//      */
//     private function uniformCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');

//         $child1Genes = [];
//         $child2Genes = [];

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             // احتمال 50% لكل جين من أي والد
//             if (lcg_value() < 0.5) {
//                 $child1Genes[] = $p1Genes->get($lectureBlock->unique_id);
//                 $child2Genes[] = $p2Genes->get($lectureBlock->unique_id);
//             } else {
//                 $child1Genes[] = $p2Genes->get($lectureBlock->unique_id);
//                 $child2Genes[] = $p1Genes->get($lectureBlock->unique_id);
//             }
//         }

//         return [array_filter($child1Genes), array_filter($child2Genes)];
//     }

//     /**
//      * Mutation محسّنة - دعم أنواع متعددة
//      */
//     private function performMutation(array $genes): array
//     {
//         if (lcg_value() >= $this->settings['mutation_rate'] || empty($genes)) {
//             return $genes;
//         }

//         $mutationType = $this->settings['mutation_type'] ?? 'smart';

//         switch ($mutationType) {
//             case 'random':
//                 return $this->randomMutation($genes);
//             case 'smart':
//                 return $this->smartMutation($genes);
//             case 'swap':
//                 return $this->swapMutation($genes);
//             case 'inversion':
//                 return $this->inversionMutation($genes);
//             case 'adaptive':
//                 return $this->adaptiveMutation($genes);
//             default:
//                 return $this->smartMutation($genes);
//         }
//     }

//     /**
//      * Random Mutation - تغيير عشوائي للجينات
//      */
//     private function randomMutation(array $genes): array
//     {
//         $geneIndexToMutate = array_rand($genes);
//         $geneToMutate = $genes[$geneIndexToMutate];

//         $lectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $geneToMutate->lecture_unique_id);
//         if (!$lectureBlock) return $genes;

//         // تغيير عشوائي للغرفة والوقت
//         $geneToMutate->room_id = $this->getRandomRoomForBlock($lectureBlock)->id;
//         $geneToMutate->timeslot_ids = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);

//         $genes[$geneIndexToMutate] = $geneToMutate;
//         return $genes;
//     }

//     /**
//      * Smart Mutation - الطريقة الحالية المحسّنة
//      */
//     private function smartMutation(array $genes): array
//     {
//         $geneIndexToMutate = array_rand($genes);
//         $geneToMutate = $genes[$geneIndexToMutate];

//         $lectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $geneToMutate->lecture_unique_id);
//         if (!$lectureBlock) return $genes;

//         // جرب تغيير الغرفة أولاً
//         $newRoom = $this->getRandomRoomForBlock($lectureBlock);
//         $tempGene = clone $geneToMutate;
//         $tempGene->room_id = $newRoom->id;

//         if (!$this->isGeneConflictingWithRest($tempGene, $genes)) {
//             $genes[$geneIndexToMutate] = $tempGene;
//             return $genes;
//         }

//         // جرب تغيير الوقت
//         $newTimeslots = $this->findOptimalSlotForBlock(
//             $lectureBlock,
//             $geneToMutate->instructor_id,
//             $geneToMutate->room_id,
//             $geneToMutate->student_group_id ?? []
//         );

//         $tempGene = clone $geneToMutate;
//         $tempGene->timeslot_ids = $newTimeslots;

//         if (!$this->isGeneConflictingWithRest($tempGene, $genes)) {
//             $genes[$geneIndexToMutate] = $tempGene;
//             return $genes;
//         }

//         // إذا فشل كلاهما، غيّر الاثنين
//         $geneToMutate->room_id = $newRoom->id;
//         $geneToMutate->timeslot_ids = $newTimeslots;
//         $genes[$geneIndexToMutate] = $geneToMutate;

//         return $genes;
//     }

//     /**
//      * Swap Mutation - تبديل جينين
//      */
//     private function swapMutation(array $genes): array
//     {
//         if (count($genes) < 2) return $genes;

//         // اختر جينين عشوائياً
//         $indices = array_rand($genes, 2);
//         $gene1 = $genes[$indices[0]];
//         $gene2 = $genes[$indices[1]];

//         // تبديل الأوقات والغرف بين الجينين
//         $tempRoom = $gene1->room_id;
//         $tempTime = $gene1->timeslot_ids;

//         $gene1->room_id = $gene2->room_id;
//         $gene1->timeslot_ids = $gene2->timeslot_ids;

//         $gene2->room_id = $tempRoom;
//         $gene2->timeslot_ids = $tempTime;

//         $genes[$indices[0]] = $gene1;
//         $genes[$indices[1]] = $gene2;

//         return $genes;
//     }

//     /**
//      * Inversion Mutation - عكس ترتيب مجموعة من الجينات
//      */
//     private function inversionMutation(array $genes): array
//     {
//         if (count($genes) < 3) return $genes;

//         // اختر نقطتين للعكس
//         $point1 = rand(0, count($genes) - 3);
//         $point2 = rand($point1 + 2, count($genes) - 1);

//         // اعكس الجينات بين النقطتين
//         $section = array_slice($genes, $point1, $point2 - $point1 + 1);
//         $reversed = array_reverse($section);

//         // أعد بناء المصفوفة
//         array_splice($genes, $point1, $point2 - $point1 + 1, $reversed);

//         return $genes;
//     }

//     /**
//      * Adaptive Mutation - معدل طفرة متكيف حسب الجيل
//      */
//     private function adaptiveMutation(array $genes): array
//     {
//         // حساب معدل الطفرة المتكيف
//         $currentGen = $this->populationRun->chromosomes()->max('generation_number') ?? 1;
//         $maxGen = $this->settings['max_generations'];

//         // يزداد معدل الطفرة مع تقدم الأجيال
//         $adaptiveRate = $this->settings['mutation_rate'] * (1 + ($currentGen / $maxGen));
//         $adaptiveRate = min($adaptiveRate, 0.5); // حد أقصى 50%

//         if (lcg_value() >= $adaptiveRate) {
//             return $genes;
//         }

//         // استخدم Smart Mutation مع المعدل المتكيف
//         return $this->smartMutation($genes);
//     }


//     // private function selectParents(Collection $population): array
//     // {
//     //     $parents = [];
//     //     $tournamentSize = 3;
//     //     $populationCount = $population->count();

//     //     for ($i = 0; $i < $this->settings['population_size']; $i++) {
//     //         if ($populationCount == 0) break;
//     //         $participants = $population->random(min($tournamentSize, $populationCount));
//     //         $parents[] = $participants->sortBy('penalty_value')->first();
//     //     }
//     //     return $parents;
//     // }

//     private function createNewGeneration(array $parents, int $nextGenerationNumber, Population $populationRun): Collection
//     {
//         Log::info("Creating new generation #{$nextGenerationNumber}");
//         $childrenData = [];
//         $parentPool = array_filter($parents);

//         if (empty($parentPool)) {
//             Log::warning("Parent pool is empty for generation {$nextGenerationNumber}. Cannot create new generation.");
//             return collect();
//         }

//         for ($i = 0; $i < $this->settings['population_size']; $i += 2) {
//             if (count($parentPool) < 2) {
//                 if (empty($parents)) break;
//                 $parentPool = array_filter($parents);
//             }

//             $p1_key = array_rand($parentPool);
//             $parent1 = $parentPool[$p1_key];
//             unset($parentPool[$p1_key]);
//             $p2_key = array_rand($parentPool);
//             $parent2 = $parentPool[$p2_key];
//             unset($parentPool[$p2_key]);

//             if (!$parent1 || !$parent2) continue;

//             [$child1Genes, $child2Genes] = $this->performCrossover($parent1, $parent2);
//             $childrenData[] = $this->performMutation($child1Genes);
//             if (count($childrenData) < $this->settings['population_size']) {
//                 $childrenData[] = $this->performMutation($child2Genes);
//             }
//         }

//         $newlyCreatedChromosomes = [];
//         foreach ($childrenData as $genesToInsert) {
//             if (!empty($genesToInsert)) {
//                 $newlyCreatedChromosomes[] = $this->saveChildChromosome($genesToInsert, $nextGenerationNumber, $populationRun);
//             }
//         }
//         return collect($newlyCreatedChromosomes);
//     }

//     // private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     // {
//     //     $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
//     //     $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');
//     //     $child1Genes = [];
//     //     $child2Genes = [];

//     //     $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//     //     $currentIndex = 0;

//     //     foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//     //         $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Genes : $p2Genes;
//     //         $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Genes : $p1Genes;

//     //         $child1Genes[] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Genes->get($lectureBlock->unique_id);
//     //         $child2Genes[] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Genes->get($lectureBlock->unique_id);

//     //         $currentIndex++;
//     //     }
//     //     return [array_filter($child1Genes), array_filter($child2Genes)];
//     // }

//     // private function performMutation(array $genes): array
//     // {
//     //     if (lcg_value() < $this->settings['mutation_rate'] && !empty($genes)) {
//     //         $geneIndexToMutate = array_rand($genes);
//     //         $geneToMutate = $genes[$geneIndexToMutate];

//     //         $lectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $geneToMutate->lecture_unique_id);
//     //         if (!$lectureBlock) return $genes;

//     //         $newRoom = $this->getRandomRoomForBlock($lectureBlock);
//     //         $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);

//     //         $tempGene = clone $geneToMutate;
//     //         $tempGene->room_id = $newRoom->id;
//     //         if (!$this->isGeneConflictingWithRest($tempGene, $genes)) {
//     //             $genes[$geneIndexToMutate] = $tempGene;
//     //             return $genes;
//     //         }

//     //         $tempGene = clone $geneToMutate;
//     //         $tempGene->timeslot_ids = $newTimeslots;
//     //         if (!$this->isGeneConflictingWithRest($tempGene, $genes)) {
//     //             $genes[$geneIndexToMutate] = $tempGene;
//     //             return $genes;
//     //         }

//     //         $geneToMutate->room_id = $newRoom->id;
//     //         $geneToMutate->timeslot_ids = $newTimeslots;
//     //         $genes[$geneIndexToMutate] = $geneToMutate;
//     //     }
//     //     return $genes;
//     // }

//     private function isGeneConflictingWithRest(Gene $targetGene, array $allOtherGenes): bool
//     {
//         $otherGenes = array_filter($allOtherGenes, fn($g) => $g && $g->lecture_unique_id != $targetGene->lecture_unique_id);
//         $studentGroupIds = $targetGene->student_group_id ?? [];

//         foreach ($targetGene->timeslot_ids as $timeslotId) {
//             foreach ($otherGenes as $otherGene) {
//                 if (in_array($timeslotId, $otherGene->timeslot_ids)) {
//                     if ($otherGene->instructor_id == $targetGene->instructor_id) return true;
//                     if ($otherGene->room_id == $targetGene->room_id) return true;

//                     $otherStudentGroupIds = $otherGene->student_group_id ?? [];
//                     if (!empty($studentGroupIds) && !empty($otherStudentGroupIds) && count(array_intersect($studentGroupIds, $otherStudentGroupIds)) > 0) return true;
//                 }
//             }
//         }
//         return false;
//     }

//     private function saveChildChromosome(array $genes, int $generationNumber, Population $populationRun): Chromosome
//     {
//         $chromosome = Chromosome::create([
//             'population_id' => $populationRun->population_id,
//             'penalty_value' => -1,
//             'generation_number' => $generationNumber
//         ]);

//         $genesToInsert = [];
//         foreach ($genes as $gene) {
//             if (is_null($gene)) continue;
//             $genesToInsert[] = [
//                 'chromosome_id' => $chromosome->chromosome_id,
//                 'lecture_unique_id' => $gene->lecture_unique_id,
//                 'section_id' => $gene->section_id,
//                 'instructor_id' => $gene->instructor_id,
//                 'room_id' => $gene->room_id,
//                 'timeslot_ids' => is_string($gene->timeslot_ids) ? $gene->timeslot_ids : json_encode($gene->timeslot_ids),
//                 'student_group_id' => is_string($gene->student_group_id) ? $gene->student_group_id : json_encode($gene->student_group_id),
//                 'block_type' => $gene->block_type,
//                 'block_duration' => $gene->block_duration,

//             ];
//         }
//         if (!empty($genesToInsert)) Gene::insert($genesToInsert);
//         return $chromosome;
//     }

//     private function getPossibleStartSlots(int $slotsNeeded): Collection
//     {
//         if ($slotsNeeded <= 0) return collect();
//         return $this->timeslots->filter(function ($slot) use ($slotsNeeded) {
//             return isset($this->consecutiveTimeslotsMap[$slot->id]) && (count($this->consecutiveTimeslotsMap[$slot->id]) + 1) >= $slotsNeeded;
//         })->mapWithKeys(function ($slot) use ($slotsNeeded) {
//             return [$slot->id => array_merge([$slot->id], array_slice($this->consecutiveTimeslotsMap[$slot->id], 0, $slotsNeeded - 1))];
//         });
//     }

//     private function findRandomConsecutiveTimeslots(int $slotsNeeded): array
//     {
//         if ($slotsNeeded <= 0) return [];
//         if ($slotsNeeded == 1) return [$this->timeslots->random()->id];

//         $possibleSlots = $this->getPossibleStartSlots($slotsNeeded);
//         return $possibleSlots->isNotEmpty() ? $possibleSlots->random() : [$this->timeslots->random()->id];
//     }

//     private function getRandomRoomForBlock(\stdClass $lectureBlock)
//     {
//         $section = $lectureBlock->section;
//         $roomsSource = ($lectureBlock->block_type === 'practical') ? $this->practicalRooms : $this->theoryRooms;
//         if ($roomsSource->isEmpty()) {
//             $roomsSource = ($lectureBlock->block_type === 'practical') ? $this->theoryRooms : $this->practicalRooms;
//             if ($roomsSource->isEmpty()) return Room::all()->random();
//         }

//         $suitableRooms = $roomsSource->where('room_size', '>=', $section->student_count);
//         return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $roomsSource->random();
//     }
// }


// ***********************************************************************


// namespace App\Services;

// use App\Models\Population;
// use Illuminate\Support\Str;
// use App\Models\Chromosome;
// use App\Models\Gene;
// use App\Models\Section;
// use App\Models\Instructor;
// use App\Models\Room;
// use App\Models\Timeslot;
// use App\Jobs\EvaluateChromosomeJob;
// use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
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
//     private Collection $lectureBlocksToSchedule;
//     private array $consecutiveTimeslotsMap = [];
//     private array $studentGroupMap = [];
//     private array $instructorAssignmentMap = [];
//     private array $resourceUsageCache = [];
//     private int $stagnationCounter = 0;
//     private float $lastBestFitness = 0;

//     /**
//      * Constructor
//      */
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     /**
//      * الدالة الرئيسية
//      */
//     public function run()
//     {
//         // try {
//         $this->populationRun->update(['status' => 'running', 'start_time' => now()]);
//         $this->loadAndPrepareData();

//         $currentGenerationNumber = 1;
//         $currentPopulation = $this->createInitialPopulation($currentGenerationNumber, $this->populationRun);

//         // استخدام المعالجة المتوازية للتقييم إذا كان العدد كبير
//         // if ($this->settings['population_size'] > 50) {
//         //     $this->evaluateFitnessParallel($currentPopulation);
//         // } else {
//         $this->evaluateFitness($currentPopulation);
//         // }

//         Log::info("Generation #{$currentGenerationNumber} fitness evaluated.");

//         $maxGenerations = $this->settings['max_generations'];

//         while ($currentGenerationNumber < $maxGenerations) {
//             $bestInGen = $currentPopulation->sortBy('penalty_value')->first();

//             // تتبع الركود
//             if ($bestInGen) {
//                 if ($bestInGen->penalty_value >= $this->lastBestFitness) {
//                     $this->stagnationCounter++;
//                 } else {
//                     $this->stagnationCounter = 0;
//                 }
//                 $this->lastBestFitness = $bestInGen->penalty_value;
//             }

//             // التوقف عند إيجاد حل مثالي
//             if ($bestInGen && $bestInGen->penalty_value == 0 && ($this->settings['stop_at_first_valid'] ?? false)) {
//                 Log::info("Optimal solution found in Generation #{$currentGenerationNumber}. Stopping.");
//                 break;
//             }

//             // الانتقاء الديناميكي للآباء
//             $parents = $this->selectParents($currentPopulation, $currentGenerationNumber);
//             $currentGenerationNumber++;

//             // إنشاء الجيل الجديد مع Elitism
//             $currentPopulation = $this->createNewGenerationWithElitism(
//                 $parents,
//                 $currentGenerationNumber,
//                 $this->populationRun,
//                 $currentPopulation
//             );

//             // التقييم
//             // if ($this->settings['population_size'] > 50) {
//             //     $this->evaluateFitnessParallel($currentPopulation);
//             // } else {
//             $this->evaluateFitness($currentPopulation);
//             // }

//             Log::info("Generation #{$currentGenerationNumber} fitness evaluated. Stagnation: {$this->stagnationCounter}");
//         }

//         $finalBest = Chromosome::where('population_id', $this->populationRun->population_id)
//             ->orderBy('penalty_value', 'asc')
//             ->first();

//         $this->populationRun->update([
//             'status' => 'completed',
//             'end_time' => now(),
//             'best_chromosome_id' => $finalBest ? $finalBest->chromosome_id : null
//         ]);

//         Log::info("GA Run ID: {$this->populationRun->population_id} completed successfully.");
//         // } catch (Exception $e) {
//         //     Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
//         //     $this->populationRun->update(['status' => 'failed']);
//         //     throw $e;
//         // }
//     }

//     private function createInitialPopulation(int $generationNumber, Population $populationRun): Collection
//     {
//         Log::info("Creating intelligent initial population (Generation #{$generationNumber})");

//         $createdChromosomes = collect();
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosome = Chromosome::create([
//                 'population_id' => $populationRun->population_id,
//                 'penalty_value' => -1,
//                 'generation_number' => $generationNumber
//             ]);
//             $this->generateGenesForChromosome($chromosome);
//             $createdChromosomes->push($chromosome);
//         }

//         return $createdChromosomes;
//     }

//     private function generateGenesForChromosome(Chromosome $chromosome)
//     {
//         $this->resourceUsageCache = [];
//         $genesToInsert = [];

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $instructorId = $this->instructorAssignmentMap[$lectureBlock->unique_id];
//             $room = $this->getRandomRoomForBlock($lectureBlock);
//             $studentGroupIds = $this->studentGroupMap[$lectureBlock->section->id] ?? [];

//             $foundSlots = $this->findOptimalSlotForBlock($lectureBlock, $instructorId, $room->id, $studentGroupIds);
//             $this->updateResourceCache($foundSlots, $instructorId, $room->id, $studentGroupIds);

//             // dd([
//             //     'gene' => $lectureBlock,
//             // ]);
//             // بما أننا سنستخدم حقل JSON، ننشئ جيناً واحداً فقط
//             $genesToInsert[] = [
//                 'chromosome_id' => $chromosome->chromosome_id,
//                 'lecture_unique_id' => $lectureBlock->unique_id,
//                 'section_id' => $lectureBlock->section->id,
//                 'instructor_id' => $instructorId,
//                 'room_id' => $room->id,
//                 'timeslot_ids' => json_encode($foundSlots),
//                 'student_group_id' => json_encode($studentGroupIds),
//                 'block_type' => $lectureBlock->block_type,
//                 'block_duration' => $lectureBlock->block_duration,
//             ];
//         }

//         if (!empty($genesToInsert)) {
//             Gene::insert($genesToInsert);
//         }
//     }

//     private function findOptimalSlotForBlock($lectureBlock, int $instructorId, int $roomId, array $studentGroupIds): array
//     {
//         $possibleStartSlots = $this->getPossibleStartSlots($lectureBlock->slots_needed);

//         $shuffledSlots = $possibleStartSlots->shuffle();
//         foreach ($shuffledSlots as $trialSlots) {
//             if ($this->countConflictsForSlots($trialSlots, $instructorId, $roomId, $studentGroupIds) == 0) {
//                 return $trialSlots;
//             }
//         }

//         return $possibleStartSlots->isNotEmpty() ? $possibleStartSlots->random() : $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
//     }

//     private function countConflictsForSlots(array $slotIds, ?int $instructorId, ?int $roomId, ?array $studentGroupIds): int
//     {
//         $conflicts = 0;
//         foreach ($slotIds as $slotId) {
//             if (isset($this->resourceUsageCache[$slotId])) {
//                 if ($studentGroupIds) {
//                     foreach ($studentGroupIds as $groupId) {
//                         if (isset($this->resourceUsageCache[$slotId]['student_groups'][$groupId])) $conflicts++;
//                     }
//                 }
//                 if ($instructorId && isset($this->resourceUsageCache[$slotId]['instructors'][$instructorId])) $conflicts++;
//                 if ($roomId && isset($this->resourceUsageCache[$slotId]['rooms'][$roomId])) $conflicts++;
//             }
//         }
//         return $conflicts;
//     }

//     private function updateResourceCache(array $slotIds, int $instructorId, int $roomId, ?array $studentGroupIds): void
//     {
//         foreach ($slotIds as $slotId) {
//             $this->resourceUsageCache[$slotId]['instructors'][$instructorId] = true;
//             $this->resourceUsageCache[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupIds) {
//                 foreach ($studentGroupIds as $groupId) {
//                     $this->resourceUsageCache[$slotId]['student_groups'][$groupId] = true;
//                 }
//             }
//         }
//     }

//     private function evaluateFitness(Collection $chromosomes)
//     {
//         // استخدام transaction لضمان أن كل التحديثات تتم معاً أو لا تتم على الإطلاق
//         DB::transaction(function () use ($chromosomes) {
//             foreach ($chromosomes as $chromosome) {
//                 // جلب الجينات مرة واحدة فقط لتحسين الأداء
//                 $genes = $chromosome->genes()->with(['section.planSubject.subject', 'room.roomType', 'instructor'])->get();

//                 if ($genes->isEmpty()) {
//                     // عقوبة قاسية للكروموسومات الفارغة
//                     $this->updateChromosomeFitness($chromosome, ['empty_chromosome' => 99999]);
//                     continue;
//                 }

//                 // مصفوفة لتخزين كل أنواع العقوبات بشكل منفصل
//                 $resourceUsageMap = [];
//                 $penalties = [];

//                 // حساب كل عقوبة صارمة (Hard Constraint) على حدة
//                 // $penalties['student_conflict_penalty'] = $this->calculateStudentConflicts($genes);
//                 // $penalties['teacher_conflict_penalty'] = $this->calculateTeacherConflicts($genes);
//                 // $penalties['room_conflict_penalty'] = $this->calculateRoomConflicts($genes);
//                 // **(الأهم)** نحسب كل العقوبات الصارمة التي تعتمد على الوقت أولاً
//                 // ونمرر الخريطة بالمرجع `&` ليتم بناؤها تدريجياً
//                 $penalties['student_conflict_penalty'] = $this->calculateStudentConflicts($genes, $resourceUsageMap);
//                 $penalties['teacher_conflict_penalty'] = $this->calculateTeacherConflicts($genes, $resourceUsageMap);
//                 $penalties['room_conflict_penalty'] = $this->calculateRoomConflicts($genes, $resourceUsageMap);

//                 $penalties['capacity_conflict_penalty'] = $this->calculateCapacityConflicts($genes);
//                 $penalties['room_type_conflict_penalty'] = $this->calculateRoomTypeConflicts($genes);
//                 $penalties['teacher_eligibility_conflict_penalty'] = $this->calculateTeacherEligibilityConflicts($genes);

//                 // تجميع النتائج وتحديث الكروموسوم في قاعدة البيانات
//                 $this->updateChromosomeFitness($chromosome, $penalties);
//             }
//         });
//     }

//     /**
//      * تحسب تعارضات الطلاب (نفس مجموعة الطلاب في نفس الوقت)، وتأخذ امتداد البلوك في الاعتبار.
//      */
//     private function calculateStudentConflicts(Collection $genes, array &$usageMap): int
//     {
//         $penalty = 0;
//         foreach ($genes as $gene) {
//             $studentGroupIds = $gene->student_group_ids ?? [];
//             // نمر على كل فترة زمنية يشغلها هذا البلوك
//             foreach ($gene->timeslot_ids as $timeslotId) {
//                 // نمر على كل مجموعة طلاب تنتمي لها هذه الشعبة
//                 foreach ($studentGroupIds as $groupId) {
//                     // نتحقق إذا كانت هذه المجموعة مشغولة مسبقاً في هذه الفترة الزمنية
//                     if (isset($usageMap['student_groups'][$groupId][$timeslotId])) {
//                         $penalty += 2000;
//                     }
//                     // نسجل أن هذه المجموعة أصبحت مشغولة في هذه الفترة
//                     $usageMap['student_groups'][$groupId][$timeslotId] = true;
//                 }
//             }
//         }
//         return $penalty;
//     }

//     /**
//      * تحسب تعارضات المدرسين (نفس المدرس في نفس الوقت)، وتأخذ امتداد البلوك في الاعتبار.
//      */
//     private function calculateTeacherConflicts(Collection $genes, array &$usageMap): int
//     {
//         $penalty = 0;
//         foreach ($genes as $gene) {
//             // نمر على كل فترة زمنية يشغلها هذا البلوك
//             foreach ($gene->timeslot_ids as $timeslotId) {
//                 // نتحقق إذا كان هذا المدرس مشغولاً مسبقاً في هذه الفترة الزمنية
//                 if (isset($usageMap['instructors'][$gene->instructor_id][$timeslotId])) {
//                     $penalty += 1000;
//                 }
//                 // نسجل أن هذا المدرس أصبح مشغولاً في هذه الفترة
//                 $usageMap['instructors'][$gene->instructor_id][$timeslotId] = true;
//             }
//         }
//         return $penalty;
//     }

//     /**
//      * تحسب تعارضات القاعات (نفس القاعة في نفس الوقت)، وتأخذ امتداد البلوك في الاعتبار.
//      */
//     private function calculateRoomConflicts(Collection $genes, array &$usageMap): int
//     {
//         $penalty = 0;
//         foreach ($genes as $gene) {
//             // نمر على كل فترة زمنية يشغلها هذا البلوك
//             foreach ($gene->timeslot_ids as $timeslotId) {
//                 // نتحقق إذا كانت هذه القاعة مشغولة مسبقاً في هذه الفترة الزمنية
//                 if (isset($usageMap['rooms'][$gene->room_id][$timeslotId])) {
//                     $penalty += 800;
//                 }
//                 // نسجل أن هذه القاعة أصبحت مشغولة في هذه الفترة
//                 $usageMap['rooms'][$gene->room_id][$timeslotId] = true;
//             }
//         }
//         return $penalty;
//     }

//     /**
//      * تحسب عقوبات تجاوز سعة القاعة.
//      */
//     private function calculateCapacityConflicts(Collection $genes): int
//     {
//         $penalty = 0;
//         // نمر على البلوكات الفريدة فقط لأن السعة ثابتة لكل البلوك
//         foreach ($genes->unique('lecture_unique_id') as $gene) {
//             if ($gene->section->student_count > $gene->room->room_size) {
//                 $penalty += 500;
//             }
//         }
//         return $penalty;
//     }

//     /**
//      * تحسب عقوبات عدم تطابق نوع القاعة مع نوع المحاضرة.
//      */
//     private function calculateRoomTypeConflicts(Collection $genes): int
//     {
//         $penalty = 0;
//         foreach ($genes->unique('lecture_unique_id') as $gene) {
//             $isPracticalBlock = Str::contains($gene->lecture_unique_id, 'practical');
//             $isPracticalRoom = Str::contains(strtolower(optional($gene->room->roomType)->room_type_name), ['lab', 'مختبر']);

//             if ($isPracticalBlock && !$isPracticalRoom) {
//                 $penalty += 600; // محاضرة عملية في قاعة نظرية
//             }
//             if (!$isPracticalBlock && $isPracticalRoom) {
//                 $penalty += 300; // محاضرة نظرية في قاعة عملية (أقل أهمية)
//             }
//         }
//         return $penalty;
//     }

//     /**
//      * تحسب عقوبات عدم أهلية المدرس لتدريس المادة.
//      */
//     private function calculateTeacherEligibilityConflicts(Collection $genes): int
//     {
//         $penalty = 0;
//         foreach ($genes->unique('lecture_unique_id') as $gene) {
//             if (!optional($gene->instructor)->canTeach(optional(optional($gene->section)->planSubject)->subject)) {
//                 $penalty += 2000; // عقوبة عالية جداً
//             }
//         }
//         return $penalty;
//     }

//     private function updateChromosomeFitness(Chromosome $chromosome, array $penalties)
//     {
//         // حساب مجموع العقوبات
//         $totalPenalty = array_sum($penalties);

//         // حساب قيمة الجودة (Fitness)
//         $fitnessValue = 1 / (1 + $totalPenalty);

//         // دمج مجموع العقوبات وقيمة الجودة مع مصفوفة العقوبات التفصيلية
//         $updateData = array_merge($penalties, [
//             'penalty_value' => $totalPenalty,
//             'fitness_value' => $fitnessValue,
//         ]);

//         // تحديث سجل الكروموسوم في قاعدة البيانات
//         Chromosome::where('chromosome_id', $chromosome->chromosome_id)->update($updateData);

//         // تحديث الكائن في الذاكرة أيضاً
//         $chromosome->fill($updateData);
//     }


//     //======================================================================
//     // تحميل وتحضير البيانات
//     //======================================================================

//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with(['planSubject.subject', 'instructors'])
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])
//             ->get();

//         if ($sections->isEmpty()) {
//             throw new Exception("No sections found for the selected context.");
//         }

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas(
//             'roomType',
//             fn($q) =>
//             $q->where('room_type_name', 'not like', '%Lab%')
//                 ->where('room_type_name', 'not like', '%مختبر%')
//         )->get();

//         $this->practicalRooms = Room::whereHas(
//             'roomType',
//             fn($q) =>
//             $q->where('room_type_name', 'like', '%Lab%')
//                 ->orWhere('room_type_name', 'like', '%مختبر%')
//         )->get();

//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
//             ->orderBy('start_time')
//             ->get();

//         $this->buildConsecutiveTimeslotsMap();
//         $this->buildStudentGroupMap($sections);
//         $this->generateLectureBlocksAndAssignInstructors($sections);

//         if ($this->lectureBlocksToSchedule->isEmpty()) {
//             throw new Exception("No lecture blocks to schedule were found after processing credit hours.");
//         }

//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//     }

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
//                     $this->studentGroupMap[$theorySection->id][] = $groupIndex;
//                 }

//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
//                 foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id][] = $groupIndex;
//                     }
//                 }
//             }
//         }
//     }

//     private function generateLectureBlocksAndAssignInstructors(Collection $sections)
//     {
//         $this->lectureBlocksToSchedule = collect();
//         $this->instructorAssignmentMap = [];
//         $instructorLoad = $this->instructors->mapWithKeys(fn($inst) => [$inst->id => 0])->toArray();

//         $sectionsBySubject = $sections->groupBy('planSubject.subject.id');

//         foreach ($sectionsBySubject as $subjectId => $subjectSections) {
//             $firstSection = $subjectSections->first();
//             $subject = optional(optional($firstSection)->planSubject)->subject;
//             if (!$subject) continue;

//             $assignedInstructor = $this->getLeastLoadedInstructorForSubject($subject, $instructorLoad);

//             // if ($subject->theoretical_hours > 0) {
//             //     $theorySection = $subjectSections->firstWhere('activity_type', 'Theory');
//             //     if ($theorySection) {
//             //         $totalTheorySlots = $subject->theoretical_hours * ($this->settings['theory_credit_to_slots'] ?? 1);
//             //         $this->splitAndAssignBlocks($theorySection, 'theory', $totalTheorySlots, $assignedInstructor, $instructorLoad);
//             //     }
//             // }
//             // if ($subject->practical_hours > 0) {
//             //     $practicalSections = $subjectSections->where('activity_type', 'Practical');
//             //     foreach ($practicalSections as $practicalSection) {
//             //         $totalPracticalSlots = $subject->practical_hours * ($this->settings['practical_credit_to_slots'] ?? 1);
//             //         $this->splitAndAssignBlocks($practicalSection, 'practical', $totalPracticalSlots, $assignedInstructor, $instructorLoad);
//             //     }
//             // }
//             // تقسيم البلوكات النظرية
//             if ($subject->theoretical_hours > 0) {
//                 $theorySection = $subjectSections->firstWhere('activity_type', 'Theory');
//                 if ($theorySection) {
//                     $totalTheorySlots = $subject->theoretical_hours * ($this->settings['theory_credit_to_slots'] ?? 1);
//                     $this->splitTheoryBlocks($theorySection, $totalTheorySlots, $assignedInstructor);
//                 }
//             }

//             // تقسيم البلوكات العملية
//             if ($subject->practical_hours > 0) {
//                 $practicalSections = $subjectSections->where('activity_type', 'Practical');
//                 foreach ($practicalSections as $practicalSection) {
//                     // حساب عدد الساعات الفعلية المطلوبة للعملي
//                     $totalPracticalSlots = $subject->practical_hours * ($this->settings['practical_credit_to_slots'] ?? 1);
//                     $this->splitPracticalBlocks($practicalSection, $totalPracticalSlots, $assignedInstructor);
//                 }
//             }
//         }
//     }

//     /**
//      * يقسم الساعات النظرية إلى بلوكات (2+1) حسب المنطق المطلوب.
//      */
//     private function splitTheoryBlocks(Section $section, int $totalSlots, Instructor $instructor)
//     {
//         $remainingSlots = $totalSlots;
//         $blockCounter = 1;

//         while ($remainingSlots > 0) {
//             // استراتيجية التقسيم: 2 ثم 1
//             $blockSlots = 1; // القيمة الافتراضية
//             if ($remainingSlots >= 2) {
//                 $blockSlots = 2;
//             }

//             $uniqueId = "{$section->id}-theory-block{$blockCounter}";
//             $this->lectureBlocksToSchedule->push((object)[
//                 'section' => $section,
//                 'block_type' => 'theory',
//                 'slots_needed' => $blockSlots,
//                 'unique_id' => $uniqueId,
//                 'block_duration' => $blockSlots * 50

//             ]);
//             // تعيين نفس المدرس للبلوك
//             $this->instructorAssignmentMap[$uniqueId] = $instructor->id;

//             $remainingSlots -= $blockSlots;
//             $blockCounter++;
//         }
//     }

//     /**
//      * [تم التعديل الجذري هنا]
//      * يقسم الساعات العملية إلى بلوكات، مع إعطاء الأولوية للبلوكات الكبيرة المتصلة.
//      */
//     private function splitPracticalBlocks(Section $section, int $totalSlots, Instructor $instructor)
//     {
//         if ($totalSlots <= 0) return;

//         $remainingSlots = $totalSlots;
//         $blockCounter = 1;
//         // يمكن تعديل هذا الرقم للتحكم في حجم أكبر بلوك عملي
//         $maxBlockSize = 4;

//         while ($remainingSlots > 0) {
//             // تحديد حجم البلوك الحالي: هو إما الحجم الأقصى أو ما تبقى من ساعات، أيهما أقل
//             $blockSlots = min($remainingSlots, $maxBlockSize);

//             $uniqueId = "{$section->id}-practical-block{$blockCounter}";
//             $this->lectureBlocksToSchedule->push((object)[
//                 'section' => $section,
//                 'block_type' => 'practical',
//                 'slots_needed' => $blockSlots, // استخدام الحجم المحسوب
//                 'unique_id' => $uniqueId,
//                 'block_duration' => $blockSlots * 50,
//             ]);
//             $this->instructorAssignmentMap[$uniqueId] = $instructor->id;

//             $remainingSlots -= $blockSlots;
//             $blockCounter++;
//         }
//     }

//     private function splitAndAssignBlocks(Section $section, string $type, int $totalSlots, Instructor $instructor, array &$instructorLoad)
//     {
//         $remainingSlots = $totalSlots;
//         $blockCounter = 1;
//         $strategy = ($type == 'theory') ? [2, 1] : [4, 3, 2, 1];

//         while ($remainingSlots > 0) {
//             $blockSlots = 0;
//             foreach ($strategy as $size) {
//                 if ($remainingSlots >= $size) {
//                     $blockSlots = $size;
//                     break;
//                 }
//             }
//             if ($blockSlots == 0) $blockSlots = $remainingSlots;

//             $uniqueId = "{$section->id}-{$type}-block{$blockCounter}";
//             $this->lectureBlocksToSchedule->push((object)[
//                 'section' => $section,
//                 'block_type' => $type,
//                 'slots_needed' => $blockSlots,
//                 'block_duration' => $blockSlots * 50,
//                 'unique_id' => $uniqueId
//             ]);
//             $this->instructorAssignmentMap[$uniqueId] = $instructor->id;
//             $instructorLoad[$instructor->id] += $blockSlots;

//             $remainingSlots -= $blockSlots;
//             $blockCounter++;
//         }
//     }

//     private function getLeastLoadedInstructorForSubject(\App\Models\Subject $subject, array $instructorLoad)
//     {
//         $suitableInstructors = $this->instructors->filter(fn($inst) => $inst->subjects->contains($subject->id));
//         if ($suitableInstructors->isEmpty()) return $this->instructors->random();

//         return $suitableInstructors->sortBy(fn($inst) => $instructorLoad[$inst->id] ?? 0)->first();
//     }

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

//     //======================================================================
//     // إنشاء الجيل مع Elitism
//     //======================================================================

//     private function createNewGenerationWithElitism(
//         array $parents,
//         int $nextGenerationNumber,
//         Population $populationRun,
//         Collection $previousGeneration
//     ): Collection {

//         Log::info("Creating new generation #{$nextGenerationNumber} with elitism");

//         // حفظ أفضل 10% من الجيل السابق (Elitism)
//         $elitismCount = max(2, floor($this->settings['population_size'] * 0.1));
//         $elites = $previousGeneration->sortBy('penalty_value')
//             ->take($elitismCount)
//             ->map(function ($elite) use ($nextGenerationNumber, $populationRun) {
//                 return $this->cloneChromosome($elite, $nextGenerationNumber, $populationRun);
//             });

//         $childrenNeeded = $this->settings['population_size'] - $elitismCount;
//         $childrenData = [];
//         $parentPool = array_filter($parents);

//         if (empty($parentPool)) {
//             Log::warning("Parent pool is empty for generation {$nextGenerationNumber}.");
//             return $elites;
//         }

//         // إنشاء الأطفال
//         for ($i = 0; $i < $childrenNeeded; $i += 2) {
//             if (count($parentPool) < 2) {
//                 $parentPool = array_filter($parents);
//             }

//             $p1_key = array_rand($parentPool);
//             $parent1 = $parentPool[$p1_key];
//             unset($parentPool[$p1_key]);

//             $p2_key = array_rand($parentPool);
//             $parent2 = $parentPool[$p2_key];
//             unset($parentPool[$p2_key]);

//             if (!$parent1 || !$parent2) continue;

//             // تطبيق Crossover Rate
//             if (lcg_value() < ($this->settings['crossover_rate'] ?? 0.8)) {
//                 [$child1Genes, $child2Genes] = $this->performCrossover($parent1, $parent2);
//             } else {
//                 // نسخ الآباء مباشرة
//                 $child1Genes = $parent1->genes()->get()->toArray();
//                 $child2Genes = $parent2->genes()->get()->toArray();
//             }

//             // تطبيق الطفرة
//             $childrenData[] = $this->performMutation($child1Genes);
//             if (count($childrenData) < $childrenNeeded) {
//                 $childrenData[] = $this->performMutation($child2Genes);
//             }
//         }

//         // حفظ الأطفال الجدد
//         $newlyCreatedChromosomes = collect();
//         foreach ($childrenData as $genesToInsert) {
//             if (!empty($genesToInsert)) {
//                 $newlyCreatedChromosomes->push(
//                     $this->saveChildChromosome($genesToInsert, $nextGenerationNumber, $populationRun)
//                 );
//             }
//         }

//         // دمج النخبة مع الأطفال الجدد
//         return $elites->merge($newlyCreatedChromosomes);
//     }

//     private function saveChildChromosome(array $genes, int $generationNumber, Population $populationRun): Chromosome
//     {
//         $chromosome = Chromosome::create([
//             'population_id' => $populationRun->population_id,
//             'penalty_value' => -1,
//             'generation_number' => $generationNumber
//         ]);

//         $genesToInsert = [];
//         foreach ($genes as $gene) {
//             if (is_null($gene)) continue;
//             // if (is_null($gene)) continue;

//             // إذا كان array رجّعه object
//             if (is_array($gene)) {
//                 $gene = (object) $gene;
//             }
//             // $instructorId = $this->instructorAssignmentMap[$gene->unique_id];
//             // dd([
//             //     'genes' => $gene
//             // ]);
//             $genesToInsert[] = [
//                 'chromosome_id' => $chromosome->chromosome_id,
//                 // 'lecture_unique_id' => $gene->lecture_unique_id,
//                 'lecture_unique_id' => $gene->lecture_unique_id,
//                 // 'lecture_unique_id' => $gene->lecture_unique_id,
//                 'section_id' => $gene->section_id,
//                 'instructor_id' => $gene->instructor_id,
//                 'room_id' => $gene->room_id,
//                 'timeslot_ids' => is_string($gene->timeslot_ids) ? $gene->timeslot_ids : json_encode($gene->timeslot_ids),
//                 'student_group_id' => is_string($gene->student_group_id) ? $gene->student_group_id : json_encode($gene->student_group_id),
//                 'block_type' => $gene->block_type,
//                 'block_duration' => $gene->block_duration,

//             ];
//         }
//         if (!empty($genesToInsert)) Gene::insert($genesToInsert);
//         return $chromosome;
//     }


//     /**
//      * نسخ كروموسوم للجيل التالي (للنخبة)
//      */
//     private function cloneChromosome(Chromosome $original, int $generationNumber, Population $populationRun): Chromosome
//     {
//         $newChromosome = Chromosome::create([
//             'population_id' => $populationRun->population_id,
//             'penalty_value' => $original->penalty_value,
//             'fitness_value' => $original->fitness_value,
//             'generation_number' => $generationNumber
//         ]);

//         $genesToClone = $original->genes()->get()->map(function ($gene) use ($newChromosome) {
//             return [
//                 'chromosome_id' => $newChromosome->chromosome_id,
//                 'lecture_unique_id' => $gene->lecture_unique_id,
//                 'section_id' => $gene->section_id,
//                 'instructor_id' => $gene->instructor_id,
//                 'room_id' => $gene->room_id,
//                 'timeslot_ids' => is_string($gene->timeslot_ids) ? $gene->timeslot_ids : json_encode($gene->timeslot_ids),
//                 'student_group_id' => is_string($gene->student_group_id) ? $gene->student_group_id : json_encode($gene->student_group_id),
//                 'block_type' => $gene->block_type,
//                 'block_duration' => $gene->block_duration,
//             ];
//         })->toArray();

//         if (!empty($genesToClone)) {
//             Gene::insert($genesToClone);
//         }

//         return $newChromosome;
//     }

//     //======================================================================
//     // Dynamic Selection
//     //======================================================================

//     private function selectParents(Collection $population, int $currentGeneration): array
//     {
//         $parents = [];
//         $populationCount = $population->count();

//         // حساب tournament size ديناميكي
//         $baseTournamentSize = $this->settings['selection_size'] ?? 3;
//         $maxGenerations = $this->settings['max_generations'];

//         // في البداية: حجم صغير للتنوع، في النهاية: حجم كبير للتقارب
//         $progressRatio = $currentGeneration / $maxGenerations;
//         $dynamicSize = $baseTournamentSize + floor($progressRatio * 5);
//         $dynamicSize = min($dynamicSize, floor($populationCount * 0.2));

//         Log::info("Generation {$currentGeneration}: Using tournament size {$dynamicSize}");

//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             if ($populationCount == 0) break;

//             $participants = $population->random(min($dynamicSize, $populationCount));
//             $parents[] = $participants->sortBy('penalty_value')->first();
//         }

//         return $parents;
//     }

//     //======================================================================
//     // Crossover Methods
//     //======================================================================

//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $crossoverType = $this->populationRun->crossover->crossover_name ?? 'single_point';

//         switch ($crossoverType) {
//             case 'uniform':
//                 return $this->uniformCrossover($parent1, $parent2);
//             case 'multi_point':
//                 return $this->multiPointCrossover($parent1, $parent2);
//             case 'smart':
//                 return $this->smartCrossover($parent1, $parent2);
//             default:
//                 return $this->singlePointCrossover($parent1, $parent2);
//         }
//     }

//     private function singlePointCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');
//         $child1Genes = [];
//         $child2Genes = [];

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Genes : $p2Genes;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Genes : $p1Genes;

//             $child1Genes[] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Genes->get($lectureBlock->unique_id);
//             $child2Genes[] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Genes->get($lectureBlock->unique_id);

//             $currentIndex++;
//         }

//         return [array_filter($child1Genes), array_filter($child2Genes)];
//     }

//     private function uniformCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');
//         $child1Genes = [];
//         $child2Genes = [];

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $uniqueId = $lectureBlock->unique_id;

//             if (rand(0, 1) == 0) {
//                 $child1Genes[] = $p1Genes->get($uniqueId) ?? $p2Genes->get($uniqueId);
//                 $child2Genes[] = $p2Genes->get($uniqueId) ?? $p1Genes->get($uniqueId);
//             } else {
//                 $child1Genes[] = $p2Genes->get($uniqueId) ?? $p1Genes->get($uniqueId);
//                 $child2Genes[] = $p1Genes->get($uniqueId) ?? $p2Genes->get($uniqueId);
//             }
//         }

//         return [array_filter($child1Genes), array_filter($child2Genes)];
//     }

//     private function multiPointCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');
//         $child1Genes = [];
//         $child2Genes = [];

//         $totalGenes = $this->lectureBlocksToSchedule->count();

//         // اختيار 3 نقاط قطع عشوائية
//         $crossoverPoints = [];
//         while (count($crossoverPoints) < 3) {
//             $point = rand(1, $totalGenes - 1);
//             if (!in_array($point, $crossoverPoints)) {
//                 $crossoverPoints[] = $point;
//             }
//         }
//         sort($crossoverPoints);

//         $currentIndex = 0;
//         $segmentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $useParent1 = ($segmentIndex % 2 == 0);

//             if ($useParent1) {
//                 $child1Genes[] = $p1Genes->get($lectureBlock->unique_id) ?? $p2Genes->get($lectureBlock->unique_id);
//                 $child2Genes[] = $p2Genes->get($lectureBlock->unique_id) ?? $p1Genes->get($lectureBlock->unique_id);
//             } else {
//                 $child1Genes[] = $p2Genes->get($lectureBlock->unique_id) ?? $p1Genes->get($lectureBlock->unique_id);
//                 $child2Genes[] = $p1Genes->get($lectureBlock->unique_id) ?? $p2Genes->get($lectureBlock->unique_id);
//             }

//             $currentIndex++;

//             if ($segmentIndex < count($crossoverPoints) && $currentIndex >= $crossoverPoints[$segmentIndex]) {
//                 $segmentIndex++;
//             }
//         }

//         return [array_filter($child1Genes), array_filter($child2Genes)];
//     }

//     private function smartCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');
//         $child1Genes = [];
//         $child2Genes = [];

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $uniqueId = $lectureBlock->unique_id;
//             $gene1 = $p1Genes->get($uniqueId);
//             $gene2 = $p2Genes->get($uniqueId);

//             if (!$gene1 || !$gene2) {
//                 $child1Genes[] = $gene1 ?? $gene2;
//                 $child2Genes[] = $gene2 ?? $gene1;
//                 continue;
//             }

//             $conflicts1InP1 = $this->calculateGeneConflicts($gene1, $p1Genes->except($uniqueId)->toArray());
//             $conflicts2InP2 = $this->calculateGeneConflicts($gene2, $p2Genes->except($uniqueId)->toArray());

//             if ($conflicts1InP1 <= $conflicts2InP2) {
//                 $child1Genes[] = $gene1;
//                 $child2Genes[] = $gene2;
//             } else {
//                 $child1Genes[] = $gene2;
//                 $child2Genes[] = $gene1;
//             }
//         }

//         return [array_filter($child1Genes), array_filter($child2Genes)];
//     }
//     /**
//      * دالة مساعدة لحساب conflicts لجين واحد
//      */
//     private function calculateGeneConflicts($targetGene, array $otherGenes): int
//     {
//         $conflicts = 0;
//         $studentGroupIds = $targetGene->student_group_id ?? [];

//         foreach ($targetGene->timeslot_ids as $timeslotId) {
//             foreach ($otherGenes as $otherGene) {
//                 if (!$otherGene) continue;

//                 if (in_array($timeslotId, $otherGene->timeslot_ids)) {
//                     // تعارض المدرس
//                     if ($otherGene->instructor_id == $targetGene->instructor_id) {
//                         $conflicts += 100;
//                     }
//                     // تعارض القاعة
//                     if ($otherGene->room_id == $targetGene->room_id) {
//                         $conflicts += 80;
//                     }
//                     // تعارض الطلاب
//                     $otherStudentGroupIds = $otherGene->student_group_id ?? [];
//                     if (!empty($studentGroupIds) && !empty($otherStudentGroupIds)) {
//                         if (count(array_intersect($studentGroupIds, $otherStudentGroupIds)) > 0) {
//                             $conflicts += 200;
//                         }
//                     }
//                 }
//             }
//         }

//         return $conflicts;
//     }

//     //======================================================================
//     // Mutation Methods المحسنة
//     //======================================================================

//     private function performMutation(array $genes): array
//     {
//         if (empty($genes)) return $genes;

//         $mutationType = $this->settings['mutation_type'] ?? 'random';

//         // معدل طفرة تكيفي إذا كان النوع adaptive
//         $mutationRate = $this->settings['mutation_rate'];
//         if ($mutationType == 'adaptive') {
//             // زيادة معدل الطفرة إذا كان هناك ركود
//             $mutationRate = min(0.5, $mutationRate * (1 + $this->stagnationCounter * 0.05));
//         }

//         if (lcg_value() < $mutationRate) {
//             switch ($mutationType) {
//                 case 'smart':
//                     return $this->smartMutation($genes);
//                 case 'swap':
//                     return $this->swapMutation($genes);
//                 case 'smart_swap':
//                     return $this->smartSwapMutation($genes);
//                 case 'adaptive':
//                     // استخدام smart mutation مع معدل متكيف
//                     return $this->smartMutation($genes);
//                 default:
//                     return $this->randomMutation($genes);
//             }
//         }

//         return $genes;
//     }

//     /**
//      * Random Mutation - الطفرة العشوائية الأصلية
//      */
//     private function randomMutation(array $genes): array
//     {
//         if (empty($genes)) return $genes;

//         $geneIndexToMutate = array_rand($genes);
//         $geneToMutate = $genes[$geneIndexToMutate];

//         $lectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $geneToMutate->lecture_unique_id);
//         if (!$lectureBlock) return $genes;

//         $newRoom = $this->getRandomRoomForBlock($lectureBlock);
//         $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);

//         $geneToMutate->room_id = $newRoom->id;
//         $geneToMutate->timeslot_ids = $newTimeslots;
//         $genes[$geneIndexToMutate] = $geneToMutate;

//         return $genes;
//     }

//     private function findRandomConsecutiveTimeslots(int $slotsNeeded): array
//     {
//         if ($slotsNeeded <= 0) return [];
//         if ($slotsNeeded == 1) return [$this->timeslots->random()->id];

//         $possibleSlots = $this->getPossibleStartSlots($slotsNeeded);
//         return $possibleSlots->isNotEmpty() ? $possibleSlots->random() : [$this->timeslots->random()->id];
//     }


//     private function getPossibleStartSlots(int $slotsNeeded): Collection
//     {
//         if ($slotsNeeded <= 0) return collect();
//         return $this->timeslots->filter(function ($slot) use ($slotsNeeded) {
//             return isset($this->consecutiveTimeslotsMap[$slot->id]) && (count($this->consecutiveTimeslotsMap[$slot->id]) + 1) >= $slotsNeeded;
//         })->mapWithKeys(function ($slot) use ($slotsNeeded) {
//             return [$slot->id => array_merge([$slot->id], array_slice($this->consecutiveTimeslotsMap[$slot->id], 0, $slotsNeeded - 1))];
//         });
//     }

//     private function getRandomRoomForBlock(\stdClass $lectureBlock)
//     {
//         $section = $lectureBlock->section;
//         $roomsSource = ($lectureBlock->block_type === 'practical') ? $this->practicalRooms : $this->theoryRooms;
//         if ($roomsSource->isEmpty()) {
//             $roomsSource = ($lectureBlock->block_type === 'practical') ? $this->theoryRooms : $this->practicalRooms;
//             if ($roomsSource->isEmpty()) return Room::all()->random();
//         }

//         $suitableRooms = $roomsSource->where('room_size', '>=', $section->student_count);
//         return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $roomsSource->random();
//     }


//     /**
//      * Smart Mutation - تركز على الجينات ذات التعارضات العالية
//      */
//     private function smartMutation(array $genes): array
//     {
//         // حساب التعارضات لكل جين
//         $genesWithConflicts = [];
//         foreach ($genes as $idx => $gene) {
//             $conflicts = $this->calculateGeneConflicts($gene, array_diff_key($genes, [$idx => 1]));
//             if ($conflicts > 0) {
//                 $genesWithConflicts[] = ['index' => $idx, 'conflicts' => $conflicts, 'gene' => $gene];
//             }
//         }

//         if (empty($genesWithConflicts)) {
//             // إذا لم توجد تعارضات، نعمل طفرة عشوائية
//             return $this->randomMutation($genes);
//         }

//         // اختيار جين من الأعلى تعارضاً (أعلى 20%)
//         usort($genesWithConflicts, fn($a, $b) => $b['conflicts'] <=> $a['conflicts']);
//         $topConflictingCount = max(1, ceil(count($genesWithConflicts) * 0.2));
//         $selectedGene = $genesWithConflicts[rand(0, $topConflictingCount - 1)];

//         $geneToMutate = $selectedGene['gene'];
//         $geneIndex = $selectedGene['index'];

//         $lectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $geneToMutate->lecture_unique_id);
//         if (!$lectureBlock) return $genes;

//         // محاولة إيجاد موقع أفضل
//         $bestRoom = null;
//         $bestTimeslots = null;
//         $minConflicts = $selectedGene['conflicts'];

//         // جرب 10 احتمالات واختر الأفضل
//         for ($i = 0; $i < 10; $i++) {
//             $testRoom = $this->getRandomRoomForBlock($lectureBlock);
//             $testTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);

//             $tempGene = clone $geneToMutate;
//             $tempGene->room_id = $testRoom->id;
//             $tempGene->timeslot_ids = $testTimeslots;

//             $conflicts = $this->calculateGeneConflicts($tempGene, array_diff_key($genes, [$geneIndex => 1]));

//             if ($conflicts < $minConflicts) {
//                 $bestRoom = $testRoom;
//                 $bestTimeslots = $testTimeslots;
//                 $minConflicts = $conflicts;
//             }
//         }

//         if ($bestRoom && $bestTimeslots) {
//             $geneToMutate->room_id = $bestRoom->id;
//             $geneToMutate->timeslot_ids = $bestTimeslots;
//             $genes[$geneIndex] = $geneToMutate;
//         }

//         return $genes;
//     }

//     /**
//      * Swap Mutation - تبديل بسيط بين جينين
//      */
//     // private function swapMutation(array $genes): array
//     // {
//     //     if (count($genes) < 2) return $genes;

//     //     $idx1 = array_rand($genes);
//     //     $idx2 = array_rand($genes);

//     //     while ($idx1 == $idx2) {
//     //         $idx2 = array_rand($genes);
//     //     }

//     //     // ensure timeslots always as array
//     //     $timeslots1 = is_string($genes[$idx1]->timeslot_ids)
//     //         ? json_decode($genes[$idx1]->timeslot_ids, true)
//     //         : $genes[$idx1]->timeslot_ids;

//     //     $timeslots2 = is_string($genes[$idx2]->timeslot_ids)
//     //         ? json_decode($genes[$idx2]->timeslot_ids, true)
//     //         : $genes[$idx2]->timeslot_ids;

//     //     $room1 = $genes[$idx1]->room_id;
//     //     $room2 = $genes[$idx2]->room_id;

//     //     // swap
//     //     $genes[$idx1]->timeslot_ids = $timeslots2;
//     //     $genes[$idx1]->room_id = $room2;

//     //     $genes[$idx2]->timeslot_ids = $timeslots1;
//     //     $genes[$idx2]->room_id = $room1;

//     //     return $genes;
//     // }
//     private function swapMutation(array $genes): array
//     {
//         if (count($genes) < 2) {
//             return $genes;
//         }

//         // نختار عنصرين عشوائيين مختلفين
//         $idx1 = array_rand($genes);
//         $idx2 = array_rand($genes);

//         while ($idx1 == $idx2) {
//             $idx2 = array_rand($genes);
//         }

//         // نتأكد إنو timeslot_ids يكون دايمًا array
//         $timeslots1 = is_string($genes[$idx1]['timeslot_ids'])
//             ? json_decode($genes[$idx1]['timeslot_ids'], true)
//             : $genes[$idx1]['timeslot_ids'];

//         $timeslots2 = is_string($genes[$idx2]['timeslot_ids'])
//             ? json_decode($genes[$idx2]['timeslot_ids'], true)
//             : $genes[$idx2]['timeslot_ids'];

//         // نخزن الغرف مؤقتًا
//         $room1 = $genes[$idx1]['room_id'];
//         $room2 = $genes[$idx2]['room_id'];

//         // نعمل التبديل
//         $genes[$idx1]['timeslot_ids'] = $timeslots2;
//         $genes[$idx1]['room_id']      = $room2;

//         $genes[$idx2]['timeslot_ids'] = $timeslots1;
//         $genes[$idx2]['room_id']      = $room1;

//         return $genes;
//     }


//     // private function swapMutation(array $genes): array
//     // {
//     //     if (count($genes) < 2) return $genes;

//     //     $idx1 = array_rand($genes);
//     //     $idx2 = array_rand($genes);

//     //     while ($idx1 == $idx2) {
//     //         $idx2 = array_rand($genes);
//     //     }

//     //     // dd([
//     //     //     'idx1' => $idx1,
//     //     //     'idx2' => $idx2,
//     //     //     'genes' => $genes[$idx1],
//     //     // ]);

//     //     // تبديل الـ timeslots والـ rooms
//     //     $temp = [
//     //         'timeslots' => json_decode($genes[$idx1]->timeslot_ids, true),
//     //         'room' => $genes[$idx1]->room_id
//     //     ];
//     //         //  json_encode($gene->timeslot_ids)
//     //     $genes[$idx1]->timeslot_ids = $genes[$idx2]->timeslot_ids;
//     //     $genes[$idx1]->room_id = $genes[$idx2]->room_id;

//     //     $genes[$idx2]->timeslot_ids = $temp['timeslots'];
//     //     $genes[$idx2]->room_id = $temp['room'];

//     //     return $genes;
//     // }

//     /**
//      * Smart Swap Mutation - تبديل ذكي بين جينين متشابهين
//      */
//     private function smartSwapMutation(array $genes): array
//     {
//         if (count($genes) < 2) return $genes;

//         $swapCandidates = [];

//         foreach ($genes as $idx1 => $gene1) {
//             foreach ($genes as $idx2 => $gene2) {
//                 if ($idx1 >= $idx2) continue;

//                 // التحقق من التشابه
//                 if ($gene1->block_type == $gene2->block_type) {
//                     $conflicts1Before = $this->calculateGeneConflicts($gene1, array_diff_key($genes, [$idx1 => 1]));
//                     $conflicts2Before = $this->calculateGeneConflicts($gene2, array_diff_key($genes, [$idx2 => 1]));

//                     // محاكاة التبديل
//                     $temp1 = clone $gene1;
//                     $temp2 = clone $gene2;

//                     $tempTimeslots = $temp1->timeslot_ids;
//                     $temp1->timeslot_ids = $temp2->timeslot_ids;
//                     $temp2->timeslot_ids = $tempTimeslots;

//                     $conflicts1After = $this->calculateGeneConflicts($temp1, array_diff_key($genes, [$idx1 => 1]));
//                     $conflicts2After = $this->calculateGeneConflicts($temp2, array_diff_key($genes, [$idx2 => 1]));

//                     $improvementScore = ($conflicts1Before + $conflicts2Before) - ($conflicts1After + $conflicts2After);

//                     if ($improvementScore > 0) {
//                         $swapCandidates[] = [
//                             'idx1' => $idx1,
//                             'idx2' => $idx2,
//                             'improvement' => $improvementScore,
//                             'gene1' => $temp1,
//                             'gene2' => $temp2
//                         ];
//                     }
//                 }
//             }
//         }

//         if (!empty($swapCandidates)) {
//             usort($swapCandidates, fn($a, $b) => $b['improvement'] <=> $a['improvement']);
//             $bestSwap = $swapCandidates[0];

//             $genes[$bestSwap['idx1']] = $bestSwap['gene1'];
//             $genes[$bestSwap['idx2']] = $bestSwap['gene2'];
//         } else {
//             // إذا لم نجد تحسين، نعمل swap عشوائي
//             return $this->swapMutation($genes);
//         }

//         return $genes;
//     }

//     //======================================================================
//     // Parallel Fitness Evaluation
//     //======================================================================

//     // private function evaluateFitnessParallel(Collection $chromosomes)
//     // {
//     //     $jobs = [];

//     //     // إطلاق Jobs للمعالجة المتوازية
//     //     foreach ($chromosomes as $chromosome) {
//     //         dispatch(new EvaluateChromosomeJob($chromosome->chromosome_id))
//     //             ->onQueue('ga-evaluation');
//     //     }

//     //     // انتظار انتهاء كل الـ jobs (مع timeout)
//     //     $maxWaitTime = 30;
//     //     $startTime = now();

//     //     while (true) {
//     //         $pendingChromosomes = Chromosome::whereIn('chromosome_id', $chromosomes->pluck('chromosome_id'))
//     //             ->where('penalty_value', -1)
//     //             ->count();

//     //         if ($pendingChromosomes == 0) {
//     //             break;
//     //         }

//     //         if (now()->diffInSeconds($startTime) > $maxWaitTime) {
//     //             Log::warning("Timeout waiting for fitness evaluation, falling back to sequential");
//     //             $this->evaluateFitness(
//     //                 Chromosome::whereIn('chromosome_id', $chromosomes->pluck('chromosome_id'))
//     //                     ->where('penalty_value', -1)
//     //                     ->get()
//     //             );
//     //             break;
//     //         }

//     //         sleep(1);
//     //     }

//     //     // تحديث الـ collection بالقيم الجديدة
//     //     $chromosomes->each(function ($chromosome) {
//     //         $chromosome->refresh();
//     //     });
//     // }

//     //======================================================================
//     // باقي الدوال الموجودة (نفس الكود السابق)
//     //======================================================================

//     // [نفس باقي الدوال من الكود الأصلي: evaluateFitness, calculateConflicts, الخ...]
//     // أتركها كما هي لأنها تعمل بشكل جيد

//     // ... باقي الكود كما هو ...
// }
/////////////////////////////////////////////  احمد  /////////////////////////////////////////////


// namespace App\Services;

// use App\Models\Population;
// use Illuminate\Support\Str;
// use App\Models\Chromosome;
// use App\Models\Gene;
// use App\Models\Section;
// use App\Models\Instructor;
// use App\Models\Room;
// use App\Models\Timeslot;
// use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
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
//     private Collection $lectureBlocksToSchedule;
//     private array $consecutiveTimeslotsMap = [];
//     private array $studentGroupMap = [];
//     private array $instructorAssignmentMap = [];
//     private array $resourceUsageCache = [];

//     /**
//      * المُنشئ (Constructor)
//      * يقوم بتهيئة الخدمة بالإعدادات وسجل التشغيل.
//      */
//     public function __construct(array $settings, Population $populationRun)
//     {
//         $this->settings = $settings;
//         $this->populationRun = $populationRun;
//         Log::info("GA Service initialized for Run ID: {$this->populationRun->population_id}");
//     }

//     /**
//      * الدالة الرئيسية (run)
//      * تدير عملية التوليد بأكملها من البداية إلى النهاية.
//      */
//     public function run()
//     {
//         try {
//             // تحديث الحقول الجديدة في جدول population
//             $this->populationRun->update([
//                 'status' => 'running',
//                 'start_time' => now(),
//             ]);

//             $this->loadAndPrepareData();

//             $currentGenerationNumber = 1;
//             $currentPopulation = $this->createInitialPopulation($currentGenerationNumber, $this->populationRun);
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
//                 $currentPopulation = $this->createNewGeneration($parents, $currentGenerationNumber, $this->populationRun);
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
//             Log::error("GA Run failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString());
//             $this->populationRun->update(['status' => 'failed']);
//             throw $e;
//         }
//     }

//     //======================================================================
//     // المرحلة الأولى: تحميل وتحضير البيانات
//     //======================================================================

//     private function loadAndPrepareData()
//     {
//         Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

//         $sections = Section::with(['planSubject.subject', 'instructors'])
//             ->where('academic_year', $this->settings['academic_year'])
//             ->where('semester', $this->settings['semester'])
//             ->get();

//         if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

//         $this->instructors = Instructor::with('subjects')->get();
//         $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
//         $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
//         $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();

//         $this->buildConsecutiveTimeslotsMap();
//         $this->buildStudentGroupMap($sections);
//         $this->generateLectureBlocksAndAssignInstructors($sections);

//         if ($this->lectureBlocksToSchedule->isEmpty()) throw new Exception("No lecture blocks to schedule were found after processing credit hours.");
//         Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
//     }

//     private function generateLectureBlocksAndAssignInstructors(Collection $sections)
//     {
//         $this->lectureBlocksToSchedule = collect();
//         $this->instructorAssignmentMap = [];
//         $instructorLoad = $this->instructors->mapWithKeys(fn($inst) => [$inst->id => 0])->toArray();

//         $sectionsBySubject = $sections->groupBy('planSubject.subject.id');

//         foreach ($sectionsBySubject as $subjectId => $subjectSections) {
//             $firstSection = $subjectSections->first();
//             $subject = optional(optional($firstSection)->planSubject)->subject;
//             if (!$subject) continue;

//             $assignedInstructor = $this->getLeastLoadedInstructorForSubject($subject, $instructorLoad);

//             if ($subject->theoretical_hours > 0) {
//                 $theorySection = $subjectSections->firstWhere('activity_type', 'Theory');
//                 if ($theorySection) {
//                     $totalTheorySlots = $subject->theoretical_hours * ($this->settings['theory_credit_to_slots'] ?? 1);
//                     $this->splitTheoryBlocks($theorySection, $totalTheorySlots, $assignedInstructor);
//                 }
//             }
//             if ($subject->practical_hours > 0) {
//                 $practicalSections = $subjectSections->where('activity_type', 'Practical');
//                 foreach ($practicalSections as $practicalSection) {
//                     $totalPracticalSlots = $subject->practical_hours * ($this->settings['practical_credit_to_slots'] ?? 1);
//                     $this->splitPracticalBlocks($practicalSection, $totalPracticalSlots, $assignedInstructor);
//                 }
//             }
//         }
//     }

//     private function splitTheoryBlocks(Section $section, int $totalSlots, Instructor $instructor)
//     {
//         $remainingSlots = $totalSlots;
//         $blockCounter = 1;

//         while ($remainingSlots > 0) {
//             $blockSlots = 1;
//             if ($remainingSlots >= 2) {
//                 $blockSlots = 2;
//             }

//             $uniqueId = "{$section->id}-theory-block{$blockCounter}";
//             $this->lectureBlocksToSchedule->push((object)[
//                 'section' => $section,
//                 'block_type' => 'theory',
//                 'slots_needed' => $blockSlots,
//                 'unique_id' => $uniqueId,
//                 'block_duration' => $blockSlots * 50,
//             ]);
//             $this->instructorAssignmentMap[$uniqueId] = $instructor->id;

//             $remainingSlots -= $blockSlots;
//             $blockCounter++;
//         }
//     }

//     private function splitPracticalBlocks(Section $section, int $totalSlots, Instructor $instructor)
//     {
//         if ($totalSlots <= 0) return;

//         $remainingSlots = $totalSlots;
//         $blockCounter = 1;
//         $maxBlockSize = 4;

//         while ($remainingSlots > 0) {
//             $blockSlots = min($remainingSlots, $maxBlockSize);

//             $uniqueId = "{$section->id}-practical-block{$blockCounter}";
//             $this->lectureBlocksToSchedule->push((object)[
//                 'section' => $section,
//                 'block_type' => 'practical',
//                 'slots_needed' => $blockSlots,
//                 'unique_id' => $uniqueId,
//                 'block_duration' => $blockSlots * 50,
//             ]);
//             $this->instructorAssignmentMap[$uniqueId] = $instructor->id;

//             $remainingSlots -= $blockSlots;
//             $blockCounter++;
//         }
//     }

//     private function getLeastLoadedInstructorForSubject(\App\Models\Subject $subject, array $instructorLoad)
//     {
//         $suitableInstructors = $this->instructors->filter(fn($inst) => $inst->subjects->contains($subject->id));
//         if ($suitableInstructors->isEmpty()) return $this->instructors->random();

//         return $suitableInstructors->sortBy(fn($inst) => $instructorLoad[$inst->id] ?? 0)->first();
//     }

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
//                     $this->studentGroupMap[$theorySection->id][] = $groupIndex;
//                 }

//                 $practicalSectionsBySubject = $sectionsInContext->where('activity_type', 'Practical')->groupBy('plan_subject_id');
//                 foreach ($practicalSectionsBySubject as $sectionsForOneSubject) {
//                     $sortedSections = $sectionsForOneSubject->sortBy('section_number')->values();
//                     if ($sortedSections->has($groupIndex - 1)) {
//                         $practicalSectionForThisGroup = $sortedSections->get($groupIndex - 1);
//                         $this->studentGroupMap[$practicalSectionForThisGroup->id][] = $groupIndex;
//                     }
//                 }
//             }
//         }
//     }

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

//     //======================================================================
//     // المرحلة الثانية: إنشاء الجيل الأول
//     //======================================================================

//     private function createInitialPopulation(int $generationNumber, Population $populationRun): Collection
//     {
//         Log::info("Creating intelligent initial population (Generation #{$generationNumber})");

//         $createdChromosomes = collect();
//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $chromosome = Chromosome::create([
//                 'population_id' => $populationRun->population_id,
//                 'penalty_value' => -1,
//                 'generation_number' => $generationNumber
//             ]);
//             $this->generateGenesForChromosome($chromosome);
//             $createdChromosomes->push($chromosome);
//         }

//         return $createdChromosomes;
//     }

//     // private function generateGenesForChromosome(Chromosome $chromosome)
//     // {
//     //     $this->resourceUsageCache = [];
//     //     $genesToInsert = [];

//     //     foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//     //         $instructorId = $this->instructorAssignmentMap[$lectureBlock->unique_id];
//     //         $room = $this->getRandomRoomForBlock($lectureBlock);
//     //         $studentGroupIds = $this->studentGroupMap[$lectureBlock->section->id] ?? [];

//     //         $foundSlots = $this->findOptimalSlotForBlock($lectureBlock, $instructorId, $room->id, $studentGroupIds);
//     //         $this->updateResourceCache($foundSlots, $instructorId, $room->id, $studentGroupIds);

//     //         $genesToInsert[] = [
//     //             'chromosome_id' => $chromosome->chromosome_id,
//     //             'lecture_unique_id' => $lectureBlock->unique_id,
//     //             'section_id' => $lectureBlock->section->id,
//     //             'instructor_id' => $instructorId,
//     //             'room_id' => $room->id,
//     //             'timeslot_ids' => json_encode($foundSlots),
//     //             'student_group_id' => json_encode($studentGroupIds),
//     //             'block_type' => $lectureBlock->block_type,
//     //             'block_duration' => $lectureBlock->block_duration,
//     //         ];
//     //     }

//     //     if (!empty($genesToInsert)) {
//     //         Gene::insert($genesToInsert);
//     //     }
//     // }
//     private function generateGenesForChromosome(Chromosome $chromosome)
//     {
//         // استخدم cache مشترك لكل الجينات في الكروموسوم
//         $this->resourceUsageCache = [];
//         $genesToInsert = [];

//         // ترتيب البلوكات حسب الأولوية (مثلاً: العملي أولاً لأنه أصعب في الجدولة)
//         $sortedBlocks = $this->lectureBlocksToSchedule->sortByDesc(function ($block) {
//             return $block->block_type === 'practical' ? 1 : 0;
//         });

//         foreach ($sortedBlocks as $lectureBlock) {
//             $instructorId = $this->instructorAssignmentMap[$lectureBlock->unique_id];
//             $room = $this->getRandomRoomForBlock($lectureBlock);
//             $studentGroupIds = $this->studentGroupMap[$lectureBlock->section->id] ?? [];

//             // البحث عن وقت مناسب مع مراعاة الموارد المستخدمة سابقاً
//             $foundSlots = $this->findOptimalSlotForBlock($lectureBlock, $instructorId, $room->id, $studentGroupIds);

//             // تحديث الـ cache فوراً قبل الانتقال للبلوك التالي
//             $this->updateResourceCache($foundSlots, $instructorId, $room->id, $studentGroupIds);

//             $genesToInsert[] = [
//                 'chromosome_id' => $chromosome->chromosome_id,
//                 'lecture_unique_id' => $lectureBlock->unique_id,
//                 'section_id' => $lectureBlock->section->id,
//                 'instructor_id' => $instructorId,
//                 'room_id' => $room->id,
//                 'timeslot_ids' => json_encode($foundSlots),
//                 'student_group_id' => json_encode($studentGroupIds),
//                 'block_type' => $lectureBlock->block_type,
//                 'block_duration' => $lectureBlock->block_duration,
//             ];
//         }

//         if (!empty($genesToInsert)) {
//             Gene::insert($genesToInsert);
//         }
//     }

//     private function findOptimalSlotForBlock($lectureBlock, int $instructorId, int $roomId, array $studentGroupIds): array
//     {
//         $possibleStartSlots = $this->getPossibleStartSlots($lectureBlock->slots_needed);

//         $shuffledSlots = $possibleStartSlots->shuffle();
//         foreach ($shuffledSlots as $trialSlots) {
//             if ($this->countConflictsForSlots($trialSlots, $instructorId, $roomId, $studentGroupIds) == 0) {
//                 return $trialSlots;
//             }
//         }

//         return $possibleStartSlots->isNotEmpty() ? $possibleStartSlots->random() : $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
//     }

//     // private function countConflictsForSlots(array $slotIds, ?int $instructorId, ?int $roomId, ?array $studentGroupIds): int
//     // {
//     //     $conflicts = 0;
//     //     foreach ($slotIds as $slotId) {
//     //         if (isset($this->resourceUsageCache[$slotId])) {
//     //             if ($studentGroupIds) {
//     //                 foreach ($studentGroupIds as $groupId) {
//     //                     if (isset($this->resourceUsageCache[$slotId]['student_groups'][$groupId])) $conflicts++;
//     //                 }
//     //             }
//     //             if ($instructorId && isset($this->resourceUsageCache[$slotId]['instructors'][$instructorId])) $conflicts++;
//     //             if ($roomId && isset($this->resourceUsageCache[$slotId]['rooms'][$roomId])) $conflicts++;
//     //         }
//     //     }
//     //     return $conflicts;
//     // }
//     private function countConflictsForSlots(array $slotIds, ?int $instructorId, ?int $roomId, ?array $studentGroupIds): int
//     {
//         $conflicts = 0;

//         foreach ($slotIds as $slotId) {
//             if (!isset($this->resourceUsageCache[$slotId])) {
//                 continue; // لا توجد موارد مستخدمة في هذا الوقت
//             }

//             // فحص تعارض الطلاب
//             if (!empty($studentGroupIds)) {
//                 foreach ($studentGroupIds as $groupId) {
//                     if (isset($this->resourceUsageCache[$slotId]['student_groups'][$groupId])) {
//                         $conflicts += 10; // وزن أعلى لتعارض الطلاب
//                     }
//                 }
//             }

//             // فحص تعارض المدرس
//             if ($instructorId && isset($this->resourceUsageCache[$slotId]['instructors'][$instructorId])) {
//                 $conflicts += 5; // وزن متوسط لتعارض المدرس
//             }

//             // فحص تعارض القاعة
//             if ($roomId && isset($this->resourceUsageCache[$slotId]['rooms'][$roomId])) {
//                 $conflicts += 3; // وزن أقل لتعارض القاعة
//             }
//         }

//         return $conflicts;
//     }

//     private function updateResourceCache(array $slotIds, int $instructorId, int $roomId, ?array $studentGroupIds): void
//     {
//         foreach ($slotIds as $slotId) {
//             $this->resourceUsageCache[$slotId]['instructors'][$instructorId] = true;
//             $this->resourceUsageCache[$slotId]['rooms'][$roomId] = true;
//             if ($studentGroupIds) {
//                 foreach ($studentGroupIds as $groupId) {
//                     $this->resourceUsageCache[$slotId]['student_groups'][$groupId] = true;
//                 }
//             }
//         }
//     }

//     //======================================================================
//     // المرحلة الثالثة: التقييم والتحسين
//     //======================================================================

//     private function evaluateFitness(Collection $chromosomes)
//     {
//         DB::transaction(function () use ($chromosomes) {
//             foreach ($chromosomes as $chromosome) {
//                 $genes = $chromosome->genes()->with(['section.planSubject.subject', 'room.roomType', 'instructor'])->get();

//                 if ($genes->isEmpty()) {
//                     $this->updateChromosomeFitness($chromosome, ['empty_chromosome' => 99999]);
//                     continue;
//                 }

//                 $resourceUsageMap = [];
//                 $penalties = [];

//                 $penalties['student_conflict_penalty'] = $this->calculateStudentConflicts($genes, $resourceUsageMap);
//                 $penalties['teacher_conflict_penalty'] = $this->calculateTeacherConflicts($genes, $resourceUsageMap);
//                 $penalties['room_conflict_penalty'] = $this->calculateRoomConflicts($genes, $resourceUsageMap);
//                 $penalties['capacity_conflict_penalty'] = $this->calculateCapacityConflicts($genes);
//                 $penalties['room_type_conflict_penalty'] = $this->calculateRoomTypeConflicts($genes);
//                 $penalties['teacher_eligibility_conflict_penalty'] = $this->calculateTeacherEligibilityConflicts($genes);

//                 $this->updateChromosomeFitness($chromosome, $penalties);
//             }
//         });
//     }

//     private function calculateStudentConflicts(Collection $genes, array &$usageMap): int
//     {
//         $penalty = 0;
//         foreach ($genes as $gene) {
//             $studentGroupIds = $gene->student_group_ids ?? [];
//             foreach ($gene->timeslot_ids as $timeslotId) {
//                 foreach ($studentGroupIds as $groupId) {
//                     if (isset($usageMap['student_groups'][$groupId][$timeslotId])) {
//                         $penalty += 2000;
//                     }
//                     $usageMap['student_groups'][$groupId][$timeslotId] = true;
//                 }
//             }
//         }
//         return $penalty;
//     }

//     private function calculateTeacherConflicts(Collection $genes, array &$usageMap): int
//     {
//         $penalty = 0;
//         foreach ($genes as $gene) {
//             foreach ($gene->timeslot_ids as $timeslotId) {
//                 if (isset($usageMap['instructors'][$gene->instructor_id][$timeslotId])) {
//                     $penalty += 1000;
//                 }
//                 $usageMap['instructors'][$gene->instructor_id][$timeslotId] = true;
//             }
//         }
//         return $penalty;
//     }

//     private function calculateRoomConflicts(Collection $genes, array &$usageMap): int
//     {
//         $penalty = 0;
//         foreach ($genes as $gene) {
//             foreach ($gene->timeslot_ids as $timeslotId) {
//                 if (isset($usageMap['rooms'][$gene->room_id][$timeslotId])) {
//                     $penalty += 800;
//                 }
//                 $usageMap['rooms'][$gene->room_id][$timeslotId] = true;
//             }
//         }
//         return $penalty;
//     }

//     private function calculateCapacityConflicts(Collection $genes): int
//     {
//         $penalty = 0;
//         foreach ($genes->unique('lecture_unique_id') as $gene) {
//             if ($gene->section->student_count > $gene->room->room_size) {
//                 $penalty += 500;
//             }
//         }
//         return $penalty;
//     }

//     private function calculateRoomTypeConflicts(Collection $genes): int
//     {
//         $penalty = 0;
//         foreach ($genes->unique('lecture_unique_id') as $gene) {
//             $isPracticalBlock = Str::contains($gene->lecture_unique_id, 'practical');
//             $isPracticalRoom = Str::contains(strtolower(optional($gene->room->roomType)->room_type_name), ['lab', 'مختبر']);

//             if ($isPracticalBlock && !$isPracticalRoom) {
//                 $penalty += 600;
//             }
//             if (!$isPracticalBlock && $isPracticalRoom) {
//                 $penalty += 300;
//             }
//         }
//         return $penalty;
//     }

//     private function calculateTeacherEligibilityConflicts(Collection $genes): int
//     {
//         $penalty = 0;
//         foreach ($genes->unique('lecture_unique_id') as $gene) {
//             if (!optional($gene->instructor)->canTeach(optional(optional($gene->section)->planSubject)->subject)) {
//                 $penalty += 2000;
//             }
//         }
//         return $penalty;
//     }

//     private function updateChromosomeFitness(Chromosome $chromosome, array $penalties)
//     {
//         $totalPenalty = array_sum($penalties);
//         $fitnessValue = 1 / (1 + $totalPenalty);

//         $updateData = array_merge($penalties, [
//             'penalty_value' => $totalPenalty,
//             'fitness_value' => $fitnessValue,
//         ]);

//         Chromosome::where('chromosome_id', $chromosome->chromosome_id)->update($updateData);
//         $chromosome->fill($updateData);
//     }

//     //======================================================================
//     // Selection Methods - محدثة لدعم أنواع مختلفة
//     //======================================================================

//     private function selectParents(Collection $population): array
//     {
//         $selectionType = $this->settings['selection_type_id'] ?? 1;

//         switch ($selectionType) {
//             case 1: // Tournament Selection
//                 return $this->tournamentSelection($population);
//             case 2: // Roulette Wheel Selection
//                 return $this->rouletteWheelSelection($population);
//             case 3: // Rank Selection
//                 return $this->rankSelection($population);
//             case 4: // Elitism + Tournament
//                 return $this->elitismWithTournament($population);
//             default:
//                 return $this->tournamentSelection($population);
//         }
//     }

//     private function tournamentSelection(Collection $population): array
//     {
//         $parents = [];
//         $tournamentSize = $this->settings['selection_size'] ?? 3;
//         $populationCount = $population->count();

//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             if ($populationCount == 0) break;
//             $participants = $population->random(min($tournamentSize, $populationCount));
//             $parents[] = $participants->sortBy('penalty_value')->first();
//         }
//         return $parents;
//     }

//     private function rouletteWheelSelection(Collection $population): array
//     {
//         $parents = [];
//         $totalFitness = $population->sum('fitness_value');

//         if ($totalFitness == 0) {
//             return $population->random($this->settings['population_size'])->toArray();
//         }

//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $randomValue = lcg_value() * $totalFitness;
//             $currentSum = 0;

//             foreach ($population as $chromosome) {
//                 $currentSum += $chromosome->fitness_value;
//                 if ($currentSum >= $randomValue) {
//                     $parents[] = $chromosome;
//                     break;
//                 }
//             }
//         }
//         return $parents;
//     }

//     private function rankSelection(Collection $population): array
//     {
//         $parents = [];
//         $sortedPopulation = $population->sortBy('penalty_value')->values();
//         $populationSize = $sortedPopulation->count();

//         // حساب مجموع الرتب
//         $rankSum = ($populationSize * ($populationSize + 1)) / 2;

//         for ($i = 0; $i < $this->settings['population_size']; $i++) {
//             $randomValue = lcg_value() * $rankSum;
//             $currentSum = 0;

//             for ($rank = 1; $rank <= $populationSize; $rank++) {
//                 $currentSum += ($populationSize - $rank + 1);
//                 if ($currentSum >= $randomValue) {
//                     $parents[] = $sortedPopulation[$rank - 1];
//                     break;
//                 }
//             }
//         }
//         return $parents;
//     }

//     private function elitismWithTournament(Collection $population): array
//     {
//         $parents = [];
//         $eliteSize = max(2, intval($this->settings['population_size'] * 0.1));

//         // إضافة النخبة
//         $elite = $population->sortBy('penalty_value')->take($eliteSize);
//         foreach ($elite as $chromosome) {
//             $parents[] = $chromosome;
//         }

//         // ملء الباقي باستخدام Tournament
//         $remainingSize = $this->settings['population_size'] - $eliteSize;
//         $tournamentSize = $this->settings['selection_size'] ?? 3;

//         for ($i = 0; $i < $remainingSize; $i++) {
//             $participants = $population->random(min($tournamentSize, $population->count()));
//             $parents[] = $participants->sortBy('penalty_value')->first();
//         }

//         return $parents;
//     }

//     //======================================================================
//     // Crossover Methods - محدثة لدعم أنواع مختلفة
//     //======================================================================

//     private function createNewGeneration(array $parents, int $nextGenerationNumber, Population $populationRun): Collection
//     {
//         Log::info("Creating new generation #{$nextGenerationNumber}");
//         $childrenData = [];
//         $parentPool = array_filter($parents);

//         if (empty($parentPool)) {
//             Log::warning("Parent pool is empty for generation {$nextGenerationNumber}. Cannot create new generation.");
//             return collect();
//         }

//         for ($i = 0; $i < $this->settings['population_size']; $i += 2) {
//             if (count($parentPool) < 2) {
//                 if (empty($parents)) break;
//                 $parentPool = array_filter($parents);
//             }

//             $p1_key = array_rand($parentPool);
//             $parent1 = $parentPool[$p1_key];
//             unset($parentPool[$p1_key]);

//             if (empty($parentPool)) break;

//             $p2_key = array_rand($parentPool);
//             $parent2 = $parentPool[$p2_key];
//             unset($parentPool[$p2_key]);

//             if (!$parent1 || !$parent2) continue;

//             // تطبيق معدل التزاوج
//             if (lcg_value() < $this->settings['crossover_rate']) {
//                 [$child1Genes, $child2Genes] = $this->performCrossover($parent1, $parent2);
//             } else {
//                 // نسخ الآباء مباشرة بدون تزاوج
//                 $child1Genes = $parent1->genes()->get()->toArray();
//                 $child2Genes = $parent2->genes()->get()->toArray();
//             }

//             $childrenData[] = $this->performMutation($child1Genes);
//             if (count($childrenData) < $this->settings['population_size']) {
//                 $childrenData[] = $this->performMutation($child2Genes);
//             }
//         }

//         $newlyCreatedChromosomes = [];
//         foreach ($childrenData as $genesToInsert) {
//             if (!empty($genesToInsert)) {
//                 $newlyCreatedChromosomes[] = $this->saveChildChromosome($genesToInsert, $nextGenerationNumber, $populationRun);
//             }
//         }
//         return collect($newlyCreatedChromosomes);
//     }

//     private function performCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $crossoverType = $this->settings['crossover_type_id'] ?? 1;

//         switch ($crossoverType) {
//             case 1: // Single Point Crossover
//                 return $this->singlePointCrossover($parent1, $parent2);
//             case 2: // Two Point Crossover
//                 return $this->twoPointCrossover($parent1, $parent2);
//             case 3: // Uniform Crossover
//                 return $this->uniformCrossover($parent1, $parent2);
//             case 4: // Cycle Crossover
//                 return $this->cycleCrossover($parent1, $parent2);
//             default:
//                 return $this->singlePointCrossover($parent1, $parent2);
//         }
//     }

//     private function singlePointCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');
//         $child1Genes = [];
//         $child2Genes = [];

//         $crossoverPoint = rand(1, $this->lectureBlocksToSchedule->count() - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $sourceForChild1 = ($currentIndex < $crossoverPoint) ? $p1Genes : $p2Genes;
//             $sourceForChild2 = ($currentIndex < $crossoverPoint) ? $p2Genes : $p1Genes;

//             $child1Genes[] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p2Genes->get($lectureBlock->unique_id);
//             $child2Genes[] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p1Genes->get($lectureBlock->unique_id);

//             $currentIndex++;
//         }
//         return [array_filter($child1Genes), array_filter($child2Genes)];
//     }

//     private function twoPointCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');
//         $child1Genes = [];
//         $child2Genes = [];

//         $totalBlocks = $this->lectureBlocksToSchedule->count();
//         $point1 = rand(1, $totalBlocks - 2);
//         $point2 = rand($point1 + 1, $totalBlocks - 1);
//         $currentIndex = 0;

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             $useP1ForChild1 = ($currentIndex < $point1 || $currentIndex >= $point2);

//             $sourceForChild1 = $useP1ForChild1 ? $p1Genes : $p2Genes;
//             $sourceForChild2 = $useP1ForChild1 ? $p2Genes : $p1Genes;

//             $child1Genes[] = $sourceForChild1->get($lectureBlock->unique_id) ?? $p1Genes->get($lectureBlock->unique_id);
//             $child2Genes[] = $sourceForChild2->get($lectureBlock->unique_id) ?? $p2Genes->get($lectureBlock->unique_id);

//             $currentIndex++;
//         }
//         return [array_filter($child1Genes), array_filter($child2Genes)];
//     }

//     private function uniformCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         $p1Genes = $parent1->genes()->get()->keyBy('lecture_unique_id');
//         $p2Genes = $parent2->genes()->get()->keyBy('lecture_unique_id');
//         $child1Genes = [];
//         $child2Genes = [];

//         foreach ($this->lectureBlocksToSchedule as $lectureBlock) {
//             if (lcg_value() < 0.5) {
//                 $child1Genes[] = $p1Genes->get($lectureBlock->unique_id) ?? $p2Genes->get($lectureBlock->unique_id);
//                 $child2Genes[] = $p2Genes->get($lectureBlock->unique_id) ?? $p1Genes->get($lectureBlock->unique_id);
//             } else {
//                 $child1Genes[] = $p2Genes->get($lectureBlock->unique_id) ?? $p1Genes->get($lectureBlock->unique_id);
//                 $child2Genes[] = $p1Genes->get($lectureBlock->unique_id) ?? $p2Genes->get($lectureBlock->unique_id);
//             }
//         }
//         return [array_filter($child1Genes), array_filter($child2Genes)];
//     }

//     private function cycleCrossover(Chromosome $parent1, Chromosome $parent2): array
//     {
//         // للبساطة، نستخدم Single Point Crossover كبديل
//         // يمكن تطوير Cycle Crossover المعقد لاحقاً إذا لزم الأمر
//         return $this->singlePointCrossover($parent1, $parent2);
//     }

//     //======================================================================
//     // Mutation Methods - محدثة لدعم أنواع مختلفة
//     //======================================================================

//     private function performMutation(array $genes): array
//     {
//         if (lcg_value() < $this->settings['mutation_rate'] && !empty($genes)) {
//             $mutationType = $this->settings['mutation_type'] ?? 'random';

//             switch ($mutationType) {
//                 case 'random':
//                     return $this->randomMutation($genes);
//                 case 'smart':
//                     return $this->smartMutation($genes);
//                 case 'swap':
//                     return $this->swapMutation($genes);
//                 case 'inversion':
//                     return $this->inversionMutation($genes);
//                 case 'adaptive':
//                     return $this->adaptiveMutation($genes);
//                 default:
//                     return $this->randomMutation($genes);
//             }
//         }
//         return $genes;
//     }

//     private function randomMutation(array $genes): array
//     {
//         $geneIndexToMutate = array_rand($genes);
//         $geneToMutate = $genes[$geneIndexToMutate];

//         // الوصول للقيمة من المصفوفة باستخدام ['key'] بدل ->property
//         $lectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $geneToMutate['lecture_unique_id']);
//         if (!$lectureBlock) return $genes;

//         // تغيير عشوائي للقاعة والوقت
//         $newRoom = $this->getRandomRoomForBlock($lectureBlock);
//         $newTimeslots = $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);

//         // تعديل البيانات داخل المصفوفة
//         $geneToMutate['room_id'] = $newRoom->id;
//         $geneToMutate['timeslot_ids'] = $newTimeslots;

//         // إرجاعه مكانه في المصفوفة
//         $genes[$geneIndexToMutate] = $geneToMutate;

//         return $genes;
//     }


//     private function smartMutation(array $genes): array
//     {
//         $geneIndexToMutate = array_rand($genes);
//         $geneToMutate = $genes[$geneIndexToMutate];

//         $lectureBlock = $this->lectureBlocksToSchedule->firstWhere('unique_id', $geneToMutate->lecture_unique_id);
//         if (!$lectureBlock) return $genes;

//         // محاولة تغيير القاعة أولاً
//         $newRoom = $this->getRandomRoomForBlock($lectureBlock);

//         // إنشاء نسخة من الجين بشكل آمن
//         if (is_object($geneToMutate)) {
//             $tempGene = clone $geneToMutate;
//         } else {
//             $tempGene = (object) $geneToMutate;
//         }

//         $tempGene->room_id = $newRoom->id;

//         if (!$this->isGeneConflictingWithRest($tempGene, $genes)) {
//             $genes[$geneIndexToMutate] = $tempGene;
//             return $genes;
//         }

//         // إذا فشلت، محاولة تغيير الوقت
//         $newTimeslots = $this->findOptimalSlotForBlock(
//             $lectureBlock,
//             $geneToMutate->instructor_id,
//             $geneToMutate->room_id,
//             $geneToMutate->student_group_id ?? []
//         );

//         // إنشاء نسخة جديدة
//         if (is_object($geneToMutate)) {
//             $tempGene = clone $geneToMutate;
//         } else {
//             $tempGene = (object) $geneToMutate;
//         }

//         $tempGene->timeslot_ids = $newTimeslots;

//         if (!$this->isGeneConflictingWithRest($tempGene, $genes)) {
//             $genes[$geneIndexToMutate] = $tempGene;
//             return $genes;
//         }

//         // إذا فشل كل شيء، قم بتغيير عشوائي
//         return $this->randomMutation($genes);
//     }

//     private function swapMutation(array $genes): array
//     {
//         if (count($genes) < 2) return $genes;

//         // اختر جينين عشوائيين
//         $index1 = array_rand($genes);
//         $index2 = array_rand($genes);

//         while ($index2 == $index1 && count($genes) > 1) {
//             $index2 = array_rand($genes);
//         }

//         // التحقق من نوع البيانات وإنشاء نسخ
//         if (is_object($genes[$index1]) && is_object($genes[$index2])) {
//             // إذا كانت كائنات، استخدم clone
//             $gene1 = clone $genes[$index1];
//             $gene2 = clone $genes[$index2];
//         } else {
//             // إذا كانت مصفوفات أو أنواع أخرى، أنشئ كائنات جديدة
//             $gene1 = (object) $genes[$index1];
//             $gene2 = (object) $genes[$index2];
//         }

//         // تبديل الأوقات والقاعات بين الجينين
//         $tempRoom = $gene1->room_id;
//         $tempTimeslots = $gene1->timeslot_ids;

//         $gene1->room_id = $gene2->room_id;
//         $gene1->timeslot_ids = $gene2->timeslot_ids;

//         $gene2->room_id = $tempRoom;
//         $gene2->timeslot_ids = $tempTimeslots;

//         // تحقق من التعارضات قبل القبول
//         if (
//             !$this->isGeneConflictingWithRest($gene1, $genes) &&
//             !$this->isGeneConflictingWithRest($gene2, $genes)
//         ) {
//             $genes[$index1] = $gene1;
//             $genes[$index2] = $gene2;
//         }

//         return $genes;
//     }


//     private function inversionMutation(array $genes): array
//     {
//         if (count($genes) < 3) return $genes;

//         // اختر نقطتين عشوائيتين
//         $indices = array_keys($genes);
//         $point1 = $indices[array_rand($indices)];
//         $point2 = $indices[array_rand($indices)];

//         if ($point1 > $point2) {
//             $temp = $point1;
//             $point1 = $point2;
//             $point2 = $temp;
//         }

//         // عكس ترتيب الجينات بين النقطتين
//         $segment = array_slice($genes, $point1, $point2 - $point1 + 1, true);
//         $reversedSegment = array_reverse($segment, true);

//         // دمج الأجزاء
//         $newGenes = array_slice($genes, 0, $point1, true) +
//             $reversedSegment +
//             array_slice($genes, $point2 + 1, null, true);

//         return $newGenes;
//     }

//     private function adaptiveMutation(array $genes): array
//     {
//         // حساب عدد التعارضات الحالية
//         $currentConflicts = $this->countTotalConflicts($genes);

//         if ($currentConflicts > 100) {
//             // إذا كان هناك الكثير من التعارضات، استخدم طفرة ذكية
//             return $this->smartMutation($genes);
//         } elseif ($currentConflicts > 50) {
//             // إذا كان هناك تعارضات متوسطة، استخدم طفرة التبديل
//             return $this->swapMutation($genes);
//         } else {
//             // إذا كان هناك تعارضات قليلة، استخدم طفرة عشوائية صغيرة
//             return $this->randomMutation($genes);
//         }
//     }

//     private function countTotalConflicts(array $genes): int
//     {
//         $conflicts = 0;
//         $usageMap = [];

//         foreach ($genes as $gene) {
//             if (!$gene) continue;

//             $timeslotIds = is_array($gene->timeslot_ids) ? $gene->timeslot_ids : json_decode($gene->timeslot_ids, true);
//             $studentGroupIds = is_array($gene->student_group_id) ? $gene->student_group_id : json_decode($gene->student_group_id, true);

//             foreach ($timeslotIds as $timeslotId) {
//                 // تحقق من تعارضات الطلاب
//                 if ($studentGroupIds) {
//                     foreach ($studentGroupIds as $groupId) {
//                         if (isset($usageMap['student_groups'][$groupId][$timeslotId])) {
//                             $conflicts++;
//                         }
//                         $usageMap['student_groups'][$groupId][$timeslotId] = true;
//                     }
//                 }

//                 // تحقق من تعارضات المدرسين
//                 if (isset($usageMap['instructors'][$gene->instructor_id][$timeslotId])) {
//                     $conflicts++;
//                 }
//                 $usageMap['instructors'][$gene->instructor_id][$timeslotId] = true;

//                 // تحقق من تعارضات القاعات
//                 if (isset($usageMap['rooms'][$gene->room_id][$timeslotId])) {
//                     $conflicts++;
//                 }
//                 $usageMap['rooms'][$gene->room_id][$timeslotId] = true;
//             }
//         }

//         return $conflicts;
//     }

//     //======================================================================
//     // Helper Functions - دوال مساعدة
//     //======================================================================

//     // private function isGeneConflictingWithRest(object $targetGene, array $allOtherGenes)
//     // {
//     //     $otherGenes = array_filter($allOtherGenes, fn($g) => $g && $g->lecture_unique_id != $targetGene->lecture_unique_id);
//     //     $studentGroupIds = $targetGene->student_group_id ?? [];

//     //     foreach ($targetGene->timeslot_ids as $timeslotId) {
//     //         foreach ($otherGenes as $otherGene) {
//     //             if (in_array($timeslotId, $otherGene->timeslot_ids)) {
//     //                 if ($otherGene->instructor_id == $targetGene->instructor_id) return true;
//     //                 if ($otherGene->room_id == $targetGene->room_id) return true;

//     //                 $otherStudentGroupIds = $otherGene->student_group_id ?? [];
//     //                 if (!empty($studentGroupIds) && !empty($otherStudentGroupIds) && count(array_intersect($studentGroupIds, $otherStudentGroupIds)) > 0) return true;
//     //             }
//     //         }
//     //     }
//     //     return false;
//     // }
//     private function isGeneConflictingWithRest($targetGene, array $allOtherGenes): bool
//     {
//         // تحويل targetGene لكائن إذا كان مصفوفة
//         if (is_array($targetGene)) {
//             $targetGene = (object) $targetGene;
//         }

//         // فلترة الجينات الأخرى - استبعاد الجين المستهدف
//         $otherGenes = array_filter($allOtherGenes, function ($g) use ($targetGene) {
//             if (!$g) return false;

//             // تحويل لكائن إذا كان مصفوفة
//             if (is_array($g)) {
//                 $g = (object) $g;
//             }

//             return $g->lecture_unique_id != $targetGene->lecture_unique_id;
//         });

//         // استخراج student group IDs مع التعامل مع JSON
//         $studentGroupIds = $targetGene->student_group_id ?? [];
//         if (is_string($studentGroupIds)) {
//             $studentGroupIds = json_decode($studentGroupIds, true) ?? [];
//         }

//         // استخراج timeslot IDs مع التعامل مع JSON
//         $targetTimeslots = $targetGene->timeslot_ids ?? [];
//         if (is_string($targetTimeslots)) {
//             $targetTimeslots = json_decode($targetTimeslots, true) ?? [];
//         }

//         foreach ($targetTimeslots as $timeslotId) {
//             foreach ($otherGenes as $otherGene) {
//                 // تحويل لكائن إذا كان مصفوفة
//                 if (is_array($otherGene)) {
//                     $otherGene = (object) $otherGene;
//                 }

//                 // استخراج timeslots للجين الآخر
//                 $otherTimeslots = $otherGene->timeslot_ids ?? [];
//                 if (is_string($otherTimeslots)) {
//                     $otherTimeslots = json_decode($otherTimeslots, true) ?? [];
//                 }

//                 if (in_array($timeslotId, $otherTimeslots)) {
//                     // تعارض المدرس
//                     if ($otherGene->instructor_id == $targetGene->instructor_id) {
//                         return true;
//                     }

//                     // تعارض القاعة
//                     if ($otherGene->room_id == $targetGene->room_id) {
//                         return true;
//                     }

//                     // تعارض الطلاب
//                     $otherStudentGroupIds = $otherGene->student_group_id ?? [];
//                     if (is_string($otherStudentGroupIds)) {
//                         $otherStudentGroupIds = json_decode($otherStudentGroupIds, true) ?? [];
//                     }

//                     if (!empty($studentGroupIds) && !empty($otherStudentGroupIds)) {
//                         if (count(array_intersect($studentGroupIds, $otherStudentGroupIds)) > 0) {
//                             return true;
//                         }
//                     }
//                 }
//             }
//         }

//         return false;
//     }
//     // private function saveChildChromosome(array $genes, int $generationNumber, Population $populationRun): Chromosome
//     // {
//     //     $chromosome = Chromosome::create([
//     //         'population_id' => $populationRun->population_id,
//     //         'penalty_value' => -1,
//     //         'generation_number' => $generationNumber
//     //     ]);

//     //     $genesToInsert = [];
//     //     foreach ($genes as $gene) {
//     //         if (is_null($gene)) continue;
//     //         // dd([
//     //         //     '$gene' => $gene,
//     //         // ]);
//     //         $genesToInsert[] = [
//     //             'chromosome_id' => $chromosome->chromosome_id,
//     //             'lecture_unique_id' => $gene->lecture_unique_id,
//     //             'section_id' => $gene->section_id,
//     //             'instructor_id' => $gene->instructor_id,
//     //             'room_id' => $gene->room_id,
//     //             'timeslot_ids' => is_string($gene->timeslot_ids) ? $gene->timeslot_ids : json_encode($gene->timeslot_ids),
//     //             'student_group_id' => is_string($gene->student_group_id) ? $gene->student_group_id : json_encode($gene->student_group_id),
//     //             'block_type' => $gene->block_type,
//     //             'block_duration' => $gene->block_duration,
//     //         ];
//     //     }
//     //     if (!empty($genesToInsert)) Gene::insert($genesToInsert);
//     //     return $chromosome;
//     // }
//     private function saveChildChromosome(array $genes, int $generationNumber, Population $populationRun): Chromosome
//     {
//         $chromosome = Chromosome::create([
//             'population_id'     => $populationRun->population_id,
//             'penalty_value'     => -1,
//             'generation_number' => $generationNumber
//         ]);

//         $genesToInsert = [];
//         foreach ($genes as $gene) {
//             if (is_null($gene)) continue;

//             $genesToInsert[] = [
//                 'chromosome_id'    => $chromosome->chromosome_id,
//                 'lecture_unique_id' => $gene['lecture_unique_id'],
//                 'section_id'       => $gene['section_id'],
//                 'instructor_id'    => $gene['instructor_id'],
//                 'room_id'          => $gene['room_id'],
//                 'timeslot_ids'     => is_string($gene['timeslot_ids'])
//                     ? $gene['timeslot_ids']
//                     : json_encode($gene['timeslot_ids']),
//                 'student_group_id' => is_string($gene['student_group_id'])
//                     ? $gene['student_group_id']
//                     : json_encode($gene['student_group_id']),
//                 'block_type'       => $gene['block_type'],
//                 'block_duration'   => $gene['block_duration'],
//             ];
//         }

//         if (!empty($genesToInsert)) {
//             Gene::insert($genesToInsert);
//         }

//         return $chromosome;
//     }


//     private function getPossibleStartSlots(int $slotsNeeded): Collection
//     {
//         if ($slotsNeeded <= 0) return collect();
//         return $this->timeslots->filter(function ($slot) use ($slotsNeeded) {
//             return isset($this->consecutiveTimeslotsMap[$slot->id]) && (count($this->consecutiveTimeslotsMap[$slot->id]) + 1) >= $slotsNeeded;
//         })->mapWithKeys(function ($slot) use ($slotsNeeded) {
//             return [$slot->id => array_merge([$slot->id], array_slice($this->consecutiveTimeslotsMap[$slot->id], 0, $slotsNeeded - 1))];
//         });
//     }

//     private function findRandomConsecutiveTimeslots(int $slotsNeeded): array
//     {
//         if ($slotsNeeded <= 0) return [];
//         if ($slotsNeeded == 1) return [$this->timeslots->random()->id];

//         $possibleSlots = $this->getPossibleStartSlots($slotsNeeded);
//         return $possibleSlots->isNotEmpty() ? $possibleSlots->random() : [$this->timeslots->random()->id];
//     }

//     private function getRandomRoomForBlock(\stdClass $lectureBlock)
//     {
//         $section = $lectureBlock->section;
//         $roomsSource = ($lectureBlock->block_type === 'practical') ? $this->practicalRooms : $this->theoryRooms;
//         if ($roomsSource->isEmpty()) {
//             $roomsSource = ($lectureBlock->block_type === 'practical') ? $this->theoryRooms : $this->practicalRooms;
//             if ($roomsSource->isEmpty()) return Room::all()->random();
//         }

//         $suitableRooms = $roomsSource->where('room_size', '>=', $section->student_count);
//         return $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $roomsSource->random();
//     }
// }


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

    // private function loadAndPrepareData()
    // {
    //     Log::info("Loading data for context -> Year: {$this->settings['academic_year']}, Semester: {$this->settings['semester']}");

    //     $sections = Section::with(['planSubject.subject', 'instructors'])
    //         ->where('academic_year', $this->settings['academic_year'])
    //         ->where('semester', $this->settings['semester'])
    //         ->get();
    //     if ($sections->isEmpty()) throw new Exception("No sections found for the selected context.");

    //     // جلب البيانات الأساسية مرة واحدة
    //     $this->instructors = Instructor::with('subjects')->get();
    //     $this->theoryRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'not like', '%Lab%')->where('room_type_name', 'not like', '%مختبر%'))->get();
    //     $this->practicalRooms = Room::whereHas('roomType', fn($q) => $q->where('room_type_name', 'like', '%Lab%')->orWhere('room_type_name', 'like', '%مختبر%'))->get();
    //     $this->timeslots = Timeslot::orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")->orderBy('start_time')->get();

    //     // جلب أنواع العمليات وتخزينها
    //     $this->loadedCrossoverTypes = CrossoverType::where('is_active', true)->get();
    //     $this->loadedSelectionTypes = SelectionType::where('is_active', true)->get();
    //     $this->loadedMutationTypes = MutationType::where('is_active', true)->get();

    //     // بناء الخرائط المساعدة
    //     $this->buildConsecutiveTimeslotsMap();
    //     $this->buildStudentGroupMap($sections);
    //     $this->generateLectureBlocksAndAssignInstructors($sections);
    //     dd([
    //         '$this->lectureBlocksToSchedule' => $this->lectureBlocksToSchedule,
    //     ]);

    //     if ($this->lectureBlocksToSchedule->isEmpty()) throw new Exception("No lecture blocks to schedule were found after processing credit hours.");
    //     Log::info("Data loaded: " . $this->lectureBlocksToSchedule->count() . " lecture blocks will be scheduled.");
    // }
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
    // private function splitPracticalBlocks(Section $section, Instructor $instructor, array &$instructorLoad)
    // {
    //     $totalSlotsNeeded = $section->planSubject->subject->practical_hours * ($this->settings['practical_credit_to_slots']);
    //     if ($totalSlotsNeeded <= 0) return;

    //     $uniqueId = "{$section->id}-practical-block1";
    //     $this->lectureBlocksToSchedule->push((object)[
    //         'unique_id' => $uniqueId,
    //         'section' => $section,
    //         'instructor_id' => $instructor->id,
    //         'student_group_id' => $this->studentGroupMap[$section->id] ?? [],
    //         'block_type' => 'practical',
    //         'slots_needed' => $totalSlotsNeeded,
    //         'block_duration' => $totalSlotsNeeded * 50,
    //     ]);
    //     $instructorLoad[$instructor->id] += $totalSlotsNeeded;
    // }
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

    /*
    private function generateLectureBlocksAndAssignInstructors(Collection $sections)
    {
        $this->lectureBlocksToSchedule = collect();
        $this->instructorAssignmentMap = [];
        $instructorLoad = $this->instructors->mapWithKeys(fn($inst) => [$inst->id => 0])->toArray();
        $sectionsBySubject = $sections->groupBy('planSubject.subject.id');

        foreach ($sectionsBySubject as $subjectId => $subjectSections) {
            $firstSection = $subjectSections->first();
            $subject = optional(optional($firstSection)->planSubject)->subject;
            if (!$subject) continue;

            $assignedInstructor = $this->getLeastLoadedInstructorForSubject($subject, $instructorLoad);

            if ($subject->theoretical_hours > 0) {
                $theorySection = $subjectSections->firstWhere('activity_type', 'Theory');
                if ($theorySection) {
                    $totalTheorySlots = $subject->theoretical_hours * ($this->settings['theory_credit_to_slots'] ?? 1);
                    $this->splitAndAssignBlocks($theorySection, 'theory', $totalTheorySlots, $assignedInstructor, $instructorLoad);
                }
            }
            // if ($subject->practical_hours > 0) {
            //     $practicalSections = $subjectSections->where('activity_type', 'Practical');
            //     foreach ($practicalSections as $practicalSection) {
            //         $totalPracticalSlots = $subject->practical_hours * ($this->settings['practical_credit_to_slots'] ?? 1);
            //         // **(تصحيح)**: العملي بلوك واحد
            //         if ($totalPracticalSlots > 0) {
            //             $uniqueId = "{$practicalSection->id}-practical-block1";
            //             $this->lectureBlocksToSchedule->push((object)[
            //                 'section' => $practicalSection,
            //                 'block_type' => 'practical',
            //                 'slots_needed' => $totalPracticalSlots,
            //                 'block_duration' => $totalPracticalSlots * 50,
            //                 'unique_id' => $uniqueId
            //             ]);
            //             $this->instructorAssignmentMap[$uniqueId] = $assignedInstructor->id;
            //             $instructorLoad[$assignedInstructor->id] += $totalPracticalSlots;
            //         }
            //     }
            // }
            // 🟢 العملي
            if ($subject->practical_hours > 0) {
                $practicalSections = $subjectSections->where('activity_type', 'Practical');
                foreach ($practicalSections as $practicalSection) {
                    $totalPracticalSlots = $subject->practical_hours * ($this->settings['practical_credit_to_slots'] ?? 1);
                    if ($totalPracticalSlots > 0) {
                        $this->assignPracticalBlock($practicalSection, $totalPracticalSlots, $assignedInstructor, $instructorLoad);
                    }
                }
            }
        }
    }

    private function splitAndAssignBlocks(Section $section, string $type, int $totalSlots, Instructor $instructor, array &$instructorLoad)
    {
        $remainingSlots = $totalSlots;
        $blockCounter = 1;
        $strategy = ($type == 'theory') ? [2, 1] : [$totalSlots]; // العملي بلوك واحد

        while ($remainingSlots > 0) {
            $blockSlots = 0;
            foreach ($strategy as $size) {
                if ($remainingSlots >= $size) {
                    $blockSlots = $size;
                    break;
                }
            }
            if ($blockSlots == 0) $blockSlots = $remainingSlots;

            $uniqueId = "{$section->id}-{$type}-block{$blockCounter}";
            $this->lectureBlocksToSchedule->push((object)[
                'section' => $section,
                'block_type' => $type,
                'slots_needed' => $blockSlots,
                'block_duration' => $blockSlots * 50,
                'unique_id' => $uniqueId
            ]);
            $this->instructorAssignmentMap[$uniqueId] = $instructor->id;
            $instructorLoad[$instructor->id] += $blockSlots;
            $remainingSlots -= $blockSlots;
            $blockCounter++;
        }
    }

    private function assignPracticalBlock(Section $section, int $totalSlots, Instructor $instructor, array &$instructorLoad)
    {
        $remainingSlots = $totalSlots;
        $blockCounter = 1;

        // الإستراتيجية: إذا أقل أو يساوي 4 = بلوك واحد، إذا أكثر = 4 + الباقي
        $strategy = ($totalSlots <= 4) ? [$totalSlots] : [4, $totalSlots - 4];

        foreach ($strategy as $blockSlots) {
            if ($blockSlots <= 0) continue;

            $uniqueId = "{$section->id}-practical-block{$blockCounter}";
            $this->lectureBlocksToSchedule->push((object)[
                'section' => $section,
                'block_type' => 'practical',
                'slots_needed' => $blockSlots,
                'block_duration' => $blockSlots * 50,
                'unique_id' => $uniqueId
            ]);
            $this->instructorAssignmentMap[$uniqueId] = $instructor->id;
            $instructorLoad[$instructor->id] += $blockSlots;
            $remainingSlots -= $blockSlots;
            $blockCounter++;
        }
    }

*/
    private function getLeastLoadedInstructorForSubject(\App\Models\Subject $subject, array $instructorLoad)
    {
        $suitableInstructors = $this->instructors->filter(fn($inst) => $inst->subjects->contains($subject->id));
        if ($suitableInstructors->isEmpty()) return $this->instructors->random();
        return $suitableInstructors->sortBy(fn($inst) => $instructorLoad[$inst->id] ?? 0)->first();
    }

    // private function buildStudentGroupMap(Collection $sections)
    // { /* ... نفس الكود من الرسالة السابقة ... */
    // }
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

    // private function buildConsecutiveTimeslotsMap()
    // { /* ... نفس الكود من الرسالة السابقة ... */
    // }
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

    // private function createInitialPopulation(int $generationNumber, Population $populationRun): Collection
    // {
    //     Log::info("Creating intelligent initial population (Generation #{$generationNumber})");
    //     $createdChromosomes = collect();
    //     for ($i = 0; $i < $this->settings['population_size']; $i++) {
    //         $chromosome = Chromosome::create([
    //             'population_id' => $populationRun->population_id,
    //             'penalty_value' => -1,
    //             'generation_number' => $generationNumber,
    //             'fitness_value' => 0 // قيمة ابتدائية
    //         ]);
    //         $this->generateGenesForChromosome($chromosome);
    //         $createdChromosomes->push($chromosome);
    //     }
    //     return $createdChromosomes;
    // }

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

    // private function generateGenesForChromosome(Chromosome $chromosome)
    // { /* ... نفس الكود من الرسالة السابقة ... */
    // }
    // private function generateGenesForChromosome(Chromosome $chromosome)
    // {
    //     // استخدم cache مشترك لكل الجينات في الكروموسوم
    //     $this->resourceUsageCache = [];
    //     $genesToInsert = [];

    //     // ترتيب البلوكات حسب الأولوية (مثلاً: العملي أولاً لأنه أصعب في الجدولة)
    //     $sortedBlocks = $this->lectureBlocksToSchedule->sortByDesc(function ($block) {
    //         return $block->block_type === 'practical' ? 1 : 0;
    //     });

    //     foreach ($sortedBlocks as $lectureBlock) {
    //         $instructorId = $this->instructorAssignmentMap[$lectureBlock->unique_id];
    //         $room = $this->getRandomRoomForBlock($lectureBlock);
    //         $studentGroupIds = $this->studentGroupMap[$lectureBlock->section->id] ?? [];

    //         // البحث عن وقت مناسب مع مراعاة الموارد المستخدمة سابقاً
    //         $foundSlots = $this->findOptimalSlotForBlock($lectureBlock, $instructorId, $room->id, $studentGroupIds);

    //         // تحديث الـ cache فوراً قبل الانتقال للبلوك التالي
    //         $this->updateResourceCache($foundSlots, $instructorId, $room->id, $studentGroupIds);

    //         $genesToInsert[] = [
    //             'chromosome_id' => $chromosome->chromosome_id,
    //             'lecture_unique_id' => $lectureBlock->unique_id,
    //             'section_id' => $lectureBlock->section->id,
    //             'instructor_id' => $instructorId,
    //             'room_id' => $room->id,
    //             'timeslot_ids' => json_encode($foundSlots),
    //             'student_group_id' => json_encode($studentGroupIds),
    //             'block_type' => $lectureBlock->block_type,
    //             'block_duration' => $lectureBlock->block_duration,
    //         ];
    //     }

    //     if (!empty($genesToInsert)) {
    //         Gene::insert($genesToInsert);
    //     }
    // }

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


    // private function findOptimalSlotForBlock($lectureBlock, int $instructorId, int $roomId, array $studentGroupIds): array
    // { /* ... نفس الكود من الرسالة السابقة ... */
    // }
    // private function findOptimalSlotForBlock($lectureBlock, int $instructorId, int $roomId, array $studentGroupIds): array
    // {
    //     $possibleStartSlots = $this->getPossibleStartSlots($lectureBlock->slots_needed);

    //     $shuffledSlots = $possibleStartSlots->shuffle();
    //     foreach ($shuffledSlots as $trialSlots) {
    //         if ($this->countConflictsForSlots($trialSlots, $instructorId, $roomId, $studentGroupIds) == 0) {
    //             return $trialSlots;
    //         }
    //     }

    //     return $possibleStartSlots->isNotEmpty() ? $possibleStartSlots->random() : $this->findRandomConsecutiveTimeslots($lectureBlock->slots_needed);
    // }

    private function findRandomConsecutiveTimeslots(int $slotsNeeded): array
    {
        if ($slotsNeeded <= 0) return [];
        if ($slotsNeeded == 1) return [$this->timeslots->random()->id];

        $possibleSlots = $this->getPossibleStartSlots($slotsNeeded);
        return $possibleSlots->isNotEmpty() ? $possibleSlots->random() : [$this->timeslots->random()->id];
    }
    // private function countConflictsForSlots(array $slotIds, ?int $instructorId, ?int $roomId, ?array $studentGroupIds): int
    // { /* ... نفس الكود من الرسالة السابقة ... */
    // }
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
    // private function updateResourceCache(array $slotIds, int $instructorId, int $roomId, ?array $studentGroupIds): void
    // { /* ... نفس الكود من الرسالة السابقة ... */
    // }
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

    // private function evaluateFitness(Collection $chromosomes)
    // {
    //     DB::transaction(function () use ($chromosomes) {
    //         foreach ($chromosomes as $chromosome) {
    //             $genes = $chromosome->genes()->get();
    //             if ($genes->isEmpty()) {
    //                 $this->updateChromosomeFitness($chromosome, ['empty_chromosome' => 99999]);
    //                 continue;
    //             }
    //             $penalties = [];
    //             $resourceUsageMap = [];
    //             $penalties['student_conflict_penalty'] = $this->calculateStudentConflicts($genes, $resourceUsageMap);
    //             $penalties['teacher_conflict_penalty'] = $this->calculateTeacherConflicts($genes, $resourceUsageMap);
    //             $penalties['room_conflict_penalty'] = $this->calculateRoomConflicts($genes, $resourceUsageMap);
    //             $penalties['capacity_conflict_penalty'] = $this->calculateCapacityConflicts($genes);
    //             $penalties['room_type_conflict_penalty'] = $this->calculateRoomTypeConflicts($genes);
    //             $penalties['teacher_eligibility_conflict_penalty'] = $this->calculateTeacherEligibilityConflicts($genes);
    //             $this->updateChromosomeFitness($chromosome, $penalties);
    //         }
    //     });
    // }
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

    // private function calculateStudentConflicts(Collection $genes, array &$usageMap): int
    // { /* ... نفس الكود من الرسالة السابقة ... */
    // }
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
    // private function calculateTeacherConflicts(Collection $genes, array &$usageMap): int
    // { /* ... نفس الكود من الرسالة السابقة ... */
    // }
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
    // private function calculateRoomConflicts(Collection $genes, array &$usageMap): int
    // { /* ... نفس الكود من الرسالة السابقة ... */
    // }
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
    // private function calculateCapacityConflicts(Collection $genes): int
    // { /* ... نفس الكود من الرسالة السابقة ... */
    // }
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
    // private function calculateRoomTypeConflicts(Collection $genes): int
    // { /* ... نفس الكود من الرسالة السابقة ... */
    // }
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
    // private function calculateTeacherEligibilityConflicts(Collection $genes): int
    // { /* ... نفس الكود من الرسالة السابقة ... */
    // }
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
    // private function updateChromosomeFitness(Chromosome $chromosome, array $penalties)
    // { /* ... نفس الكود من الرسالة السابقة ... */
    // }
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

    // private function createNewGeneration(array $parents, int $nextGenerationNumber, Population $populationRun): Collection
    // {
    //     Log::info("Creating new generation #{$nextGenerationNumber} using Hybrid approach");
    //     $newPopulation = [];
    //     $parentPool = array_filter($parents);

    //     if (empty($parentPool)) {
    //         Log::warning("Parent pool is empty for generation {$nextGenerationNumber}. Cannot create new generation.");
    //         return collect();
    //     }

    //     // **(المنطق الهجين الجديد)**
    //     foreach ($parentPool as $parent1) {
    //         if (count($newPopulation) >= $this->settings['population_size']) break;

    //         // اختيار الأب الثاني
    //         $parent2 = $this->selectParents(collect($parentPool))[0]; // نختار أباً واحداً ممتازاً

    //         // التزاوج
    //         if (lcg_value() < ($this->settings['crossover_rate'] ?? 0.95)) {
    //             $childGenes = $this->performCrossover($parent1, $parent2);
    //         } else {
    //             $childGenes = $parent1->genes; // الابن نسخة من الأب الأفضل
    //         }

    //         // الطفرة
    //         $mutatedChildGenes = $this->performMutation($childGenes->all());

    //         // حفظ الابن الجديد
    //         $newPopulation[] = $this->saveChildChromosome($mutatedChildGenes, $nextGenerationNumber, $populationRun);
    //     }

    //     return collect($newPopulation);
    // }

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

    // private function smartSwapMutation(array $genes): array
    // { /* ... نفس دالة الطفرة الذكية السابقة ... */
    // }
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
    // private function isGeneConflictingWithRest(Gene $targetGene, array $allOtherGenes): bool
    // { /* ... نفس الكود من الرسالة السابقة ... */
    // }
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

    // ... باقي الدوال المساعدة getPossibleStartSlots, findRandomConsecutiveTimeslots, getRandomRoomForBlock تبقى كما هي ...
}
