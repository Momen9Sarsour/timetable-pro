<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class ConflictCheckerService
{
    private Collection $genes;
    public array $conflicts = [];
    private array $conflictingGeneIds = [];

    public function __construct(Collection $genes)
    {
        $this->genes = $genes;
        $this->findConflicts();
    }

    private function findConflicts(): void
    {
        $timeslotsMap = $this->genes->pluck('timeslot')->flatten()->keyBy('id');

        // 1. تعارض الوقت (Overlapping)
        $this->checkForOverlappingBlocks($timeslotsMap);

        // 2. تعارض المدرس (Instructor)
        $this->checkForResourceConflict('instructor', $timeslotsMap);

        // 3. تعارض القاعة (Room)
        $this->checkForResourceConflict('room', $timeslotsMap);

        // 4. تعارض القاعة (نوع أو سعة)
        foreach ($this->genes as $gene) {
            if ($gene->section && $gene->room && $gene->section->student_count > $gene->room->room_size) {
                $this->addConflict(
                    'Room Capacity',
                    "Section #{$gene->section->section_number} ({$gene->section->student_count} students) exceeds room {$gene->room->room_no} capacity ({$gene->room->room_size}).",
                    [$gene->gene_id],
                    '#fd7e14'
                );
            }

            if ($gene->section && $gene->room && $gene->room->roomType && $gene->section->planSubject && $gene->section->planSubject->subject) {
                $activityType = $gene->section->activity_type;
                $roomTypeName = strtolower($gene->room->roomType->room_type_name);
                // if ($activityType == 'Practical' && !str_contains($roomTypeName, ['lab', 'مختبر'])) {
                if ($activityType == 'Practical' && !Str::contains($roomTypeName, ['lab', 'مختبر', 'workshop', 'ورشة'])) {
                    $this->addConflict(
                        'Room Type',
                        "Practical section in non-lab room ({$gene->room->room_no}).",
                        [$gene->gene_id],
                        '#fd7e14'
                    );
                }
            }

            if ($gene->instructor && $gene->section && $gene->section->planSubject && $gene->section->planSubject->subject) {
                if (!$gene->instructor->canTeach($gene->section->planSubject->subject)) {
                    $this->addConflict(
                        'Instructor Qualification',
                        "Instructor {$gene->instructor->user->name} not qualified to teach {$gene->section->planSubject->subject->subject_no}.",
                        [$gene->gene_id],
                        '#6f42c1'
                    );
                }
            }
        }
    }

    private function checkForResourceConflict(string $resourceType, $timeslotsMap): void
    {
        $groups = $this->genes->groupBy('timeslot_id_single');
        foreach ($groups as $genesInSlot) {
            if ($genesInSlot->count() <= 1) continue;

            $duplicates = $genesInSlot->pluck("{$resourceType}_id")->duplicates();
            foreach ($duplicates as $id => $count) {
                $conflicting = $genesInSlot->where("{$resourceType}_id", $id);
                $overlapping = $this->getOverlappingGenes($conflicting, $timeslotsMap);
                if ($overlapping->isNotEmpty()) {
                    $this->addConflict(
                        ucfirst($resourceType) . ' Conflict',
                        ucfirst($resourceType) . " #{$id} has overlapping classes.",
                        $overlapping->pluck('gene_id')->toArray(),
                        $resourceType === 'instructor' ? '#dc3545' : '#fd7e14'
                    );
                }
            }
        }
    }

    private function getOverlappingGenes(Collection $genes, $timeslotsMap): Collection
    {
        return $genes->filter(function ($gene) use ($genes, $timeslotsMap) {
            $timeslotIds = $gene->timeslot_ids; // تحويل إلى متغير محلي
            if (empty($timeslotIds)) return false;

            $startSlot = $timeslotsMap->get($timeslotIds[0]);
            $lastId = end($timeslotIds);
            $endSlot = $timeslotsMap->get($lastId);

            if (!$startSlot || !$endSlot) return false;

            $start1 = Carbon::parse($startSlot->start_time);
            $end1 = Carbon::parse($endSlot->end_time);

            foreach ($genes as $other) {
                if ($other->gene_id == $gene->gene_id) continue;
                $otherIds = $other->timeslot_ids;
                if (empty($otherIds)) continue;

                $s = $timeslotsMap->get($otherIds[0]);
                $e = $timeslotsMap->get(end($otherIds));
                if (!$s || !$e) continue;

                $start2 = Carbon::parse($s->start_time);
                $end2 = Carbon::parse($e->end_time);

                if ($start1->lt($end2) && $end1->gt($start2)) return true;
            }
            return false;
        });
    }

    private function checkForOverlappingBlocks($timeslotsMap): void
    {
        $allBlocks = $this->genes->map(function ($gene) use ($timeslotsMap) {
            $timeslotIds = $gene->timeslot_ids;
            if (empty($timeslotIds)) return null;

            $startSlot = $timeslotsMap->get($timeslotIds[0]);
            $lastId = end($timeslotIds);
            $endSlot = $timeslotsMap->get($lastId);

            if (!$startSlot || !$endSlot) return null;

            return [
                'gene' => $gene,
                'day' => $startSlot->day,
                'start' => Carbon::parse($startSlot->start_time),
                'end' => Carbon::parse($endSlot->end_time),
            ];
        })->filter();

        foreach ($allBlocks as $b1) {
            foreach ($allBlocks as $b2) {
                if ($b1['gene']->gene_id >= $b2['gene']->gene_id) continue;
                if ($b1['day'] !== $b2['day']) continue;
                if ($b1['start']->lt($b2['end']) && $b1['end']->gt($b2['start'])) {
                    $this->addConflict(
                        'Time Overlap',
                        "Classes for {$b1['gene']->section->planSubject->subject->subject_no} and {$b2['gene']->section->planSubject->subject->subject_no} overlap in time.",
                        [$b1['gene']->gene_id, $b2['gene']->gene_id],
                        '#dc3545'
                    );
                }
            }
        }
    }

    private function addConflict(string $type, string $description, array $geneIds, string $color): void
    {
        $this->conflicts[] = compact('type', 'description', 'geneIds', 'color');
        foreach ($geneIds as $id) {
            $this->conflictingGeneIds[$id] = $type;
        }
    }

    public function getConflicts(): array
    {
        return $this->conflicts;
    }

    public function getConflictingGeneIds(): array
    {
        return array_keys($this->conflictingGeneIds);
    }

    public function getGeneConflictType(int $geneId): ?string
    {
        return $this->conflictingGeneIds[$geneId] ?? null;
    }

    public function getGeneConflictColor(int $geneId): string
    {
        return $this->getConflictColor($this->getGeneConflictType($geneId));
    }

    private function getConflictColor(?string $type): string
    {
        return match ($type) {
            'Time Overlap' => '#dc3545',
            'Instructor Conflict' => '#dc3545',
            'Room Conflict', 'Room Capacity', 'Room Type' => '#fd7e14',
            'Instructor Qualification' => '#6f42c1',
            default => '#0d6efd'
        };
    }
}

// namespace App\Services;

// use Illuminate\Support\Str;
// use Illuminate\Support\Collection;

// class ConflictCheckerService
// {
//     private Collection $genes;
//     public array $conflicts = [];
//     private array $conflictingGeneIds = [];

//     public function __construct(Collection $genes)
//     {
//         $this->genes = $genes;
//         $this->findConflicts();
//     }

//     private function findConflicts(): void
//     {
//         $timeslotsMap = $this->genes->pluck('timeslot')->flatten()->keyBy('id');

//         $this->checkForResourceConflict('instructor', $timeslotsMap);
//         $this->checkForResourceConflict('room', $timeslotsMap);
//         $this->checkForResourceConflict('section', $timeslotsMap);
//         $this->checkForOverlappingBlocks($timeslotsMap);

//         foreach ($this->genes as $gene) {
//             if ($gene->section && $gene->room && $gene->section->student_count > $gene->room->room_size) {
//                 $this->addConflict('Room Capacity', "Section #{$gene->section->section_number} exceeds room {$gene->room->room_no} capacity.", [$gene->gene_id]);
//             }

//             if ($gene->section && $gene->room && $gene->room->roomType && $gene->section->planSubject && $gene->section->planSubject->subject) {
//                 $activityType = $gene->section->activity_type;
//                 $roomTypeName = strtolower($gene->room->roomType->room_type_name);
//                 // if ($activityType == 'Practical' && !str_contains($roomTypeName, ['lab', 'مختبر'])) {
//                 if ($activityType == 'Practical' && !Str::contains($roomTypeName, ['lab', 'مختبر', 'workshop', 'ورشة'])) {
//                     $this->addConflict('Room Type', "Practical section in non-lab room ({$gene->room->room_no}).", [$gene->gene_id]);
//                 }
//             }

//             if ($gene->instructor && $gene->section && $gene->section->planSubject && $gene->section->planSubject->subject) {
//                 if (!$gene->instructor->canTeach($gene->section->planSubject->subject)) {
//                     $this->addConflict('Instructor Qualification', "Instructor {$gene->instructor->user->name} not qualified.", [$gene->gene_id]);
//                 }
//             }
//         }
//     }

//     private function checkForResourceConflict(string $resourceType, $timeslotsMap): void
//     {
//         $groups = $this->genes->groupBy('timeslot_id_single');
//         foreach ($groups as $genesInSlot) {
//             if ($genesInSlot->count() <= 1) continue;

//             $duplicates = $genesInSlot->pluck("{$resourceType}_id")->duplicates();
//             foreach ($duplicates as $id => $count) {
//                 $conflicting = $genesInSlot->where("{$resourceType}_id", $id);
//                 $overlapping = $this->getOverlappingGenes($conflicting, $timeslotsMap);
//                 if ($overlapping->isNotEmpty()) {
//                     $this->addConflict(
//                         ucfirst($resourceType) . ' Conflict',
//                         ucfirst($resourceType) . " #{$id} has overlapping classes.",
//                         $overlapping->pluck('gene_id')->toArray()
//                     );
//                 }
//             }
//         }
//     }

//     private function getOverlappingGenes(Collection $genes, $timeslotsMap): Collection
//     {
//         return $genes->filter(function ($gene) use ($genes, $timeslotsMap) {
//             $startSlot = $timeslotsMap->get($gene->timeslot_ids[0]);
//             // $endSlot = $timeslotsMap->get(end($gene->timeslot_ids));
//             // $endSlot = $timeslotsMap->get(end($gene->timeslot_ids));
//             $timeslotIds = $gene->timeslot_ids; // هذا يُفعّل cast تلقائياً إلى مصفوفة
//             $lastTimeslotId = end($timeslotIds); // الآن نستخدم end على متغير محلي
//             $endSlot = $timeslotsMap->get($lastTimeslotId);



//             if (!$startSlot || !$endSlot) return false;

//             $start1 = \Carbon\Carbon::parse($startSlot->start_time);
//             $end1 = \Carbon\Carbon::parse($endSlot->end_time);

//             foreach ($genes as $other) {
//                 if ($other->gene_id == $gene->gene_id) continue;
//                 $s = $timeslotsMap->get($other->timeslot_ids[0]);
//                 // $e = $timeslotsMap->get(end($other->timeslot_ids));
//                 $timeslotIds = $other->timeslot_ids; // هذا يُفعّل cast تلقائياً إلى
//                 $lastTimeslotId = end($timeslotIds); // الآن نستخدم end على
//                 $e = $timeslotsMap->get($lastTimeslotId);
//                 if (!$s || !$e) continue;

//                 $start2 = \Carbon\Carbon::parse($s->start_time);
//                 $end2 = \Carbon\Carbon::parse($e->end_time);

//                 if ($start1->lt($end2) && $end1->gt($start2)) return true;
//             }
//             return false;
//         });
//     }

//     private function checkForOverlappingBlocks($timeslotsMap): void
//     {
//         $allBlocks = $this->genes->map(function ($gene) use ($timeslotsMap) {
//             $timeslotIds = $gene->timeslot_ids;
//             if (empty($timeslotIds)) return null;

//             $startSlot = $timeslotsMap->get($timeslotIds[0]);
//             $lastId = end($timeslotIds);
//             $endSlot = $timeslotsMap->get($lastId);

//             if (!$startSlot || !$endSlot) return null;

//             return [
//                 'gene' => $gene,
//                 'day' => $startSlot->day,
//                 'start' => \Carbon\Carbon::parse($startSlot->start_time),
//                 'end' => \Carbon\Carbon::parse($endSlot->end_time),
//             ];
//         })->filter();

//         foreach ($allBlocks as $b1) {
//             foreach ($allBlocks as $b2) {
//                 if ($b1['gene']->gene_id >= $b2['gene']->gene_id) continue;
//                 if ($b1['day'] !== $b2['day']) continue;
//                 if ($b1['start']->lt($b2['end']) && $b1['end']->gt($b2['start'])) {
//                     $this->addConflict('Time Overlap', "Classes overlap in time.", [$b1['gene']->gene_id, $b2['gene']->gene_id]);
//                 }
//             }
//         }
//     }

//     private function addConflict(string $type, string $description, array $geneIds): void
//     {
//         $color = match ($type) {
//             'Time Overlap', 'Instructor Conflict' => '#dc3545',
//             'Room Conflict', 'Room Capacity', 'Room Type' => '#fd7e14',
//             'Instructor Qualification' => '#6f42c1',
//             default => '#6c757d'
//         };

//         $this->conflicts[] = compact('type', 'description', 'geneIds', 'color');
//         foreach ($geneIds as $id) {
//             $this->conflictingGeneIds[$id] = $type;
//         }
//     }

//     public function getConflicts(): array
//     {
//         return $this->conflicts;
//     }

//     public function getConflictingGeneIds(): array
//     {
//         return array_keys($this->conflictingGeneIds);
//     }

//     public function getGeneConflictType(int $geneId): ?string
//     {
//         return $this->conflictingGeneIds[$geneId] ?? null;
//     }
// }

// namespace App\Services;

// use App\Models\Gene;
// use Illuminate\Support\Str;
// use Illuminate\Support\Collection;

// class ConflictCheckerService
// {
//     private Collection $genes;
//     private array $conflicts = [];
//     private array $conflictingGeneIds = [];

//     public function __construct(Collection $genes)
//     {
//         $this->genes = $genes;
//         $this->findConflicts();
//     }

//     /**
//      * البحث عن جميع أنواع التعارضات
//      */
//     // app/Services/ConflictCheckerService.php

//     private function findConflicts(): void
//     {
//         // 1. تحويل الفترات إلى كائنات زمنية
//         $timeslotsMap = $this->genes->pluck('timeslot')->flatten()->keyBy('id');

//         // 2. فحص التداخل الزمني (باستخدام start_time و end_time)
//         $genesByTimeslot = $this->genes->groupBy('timeslot_id_single');
//         foreach ($genesByTimeslot as $timeslotId => $genesInSlot) {
//             if ($genesInSlot->count() <= 1) continue;

//             $this->checkForResourceConflict($genesInSlot, 'instructor', $timeslotsMap);
//             $this->checkForResourceConflict($genesInSlot, 'room', $timeslotsMap);
//             $this->checkForResourceConflict($genesInSlot, 'section', $timeslotsMap);
//         }

//         // 3. فحص التداخل بين البلوكات (حتى لو ما تشاركوا نفس الفترات)
//         $this->checkForOverlappingBlocks($timeslotsMap);

//         // 4. فحص سعة القاعة، نوع القاعة، أهلية المدرس (كما هو)
//         foreach ($this->genes as $gene) {
//             // ... (كما كان)
//         }
//     }

//     private function checkForOverlappingBlocks($timeslotsMap): void
//     {
//         $allBlocks = $this->genes->map(function ($gene) use ($timeslotsMap) {
//             $startSlot = $timeslotsMap->get($gene->timeslot_ids[0]);
//             // $endSlot = $timeslotsMap->get(end($gene->timeslot_ids));
//             $timeslotIds = $gene->timeslot_ids; // هذا يُفعّل cast تلقائياً إلى
//             $lastTimeslotId = end($timeslotIds); // الآن نستخدم end على
//             $endSlot = $timeslotsMap->get($lastTimeslotId);

//             if (!$startSlot || !$endSlot) return null;

//             $startTime = \Carbon\Carbon::parse($startSlot->start_time);
//             $endTime = \Carbon\Carbon::parse($endSlot->end_time);

//             return [
//                 'gene' => $gene,
//                 'day' => $startSlot->day,
//                 'start' => $startTime,
//                 'end' => $endTime,
//             ];
//         })->filter();

//         // مقارنة كل بلوك مع الآخر
//         foreach ($allBlocks as $block1) {
//             foreach ($allBlocks as $block2) {
//                 if ($block1['gene']->gene_id >= $block2['gene']->gene_id) continue;
//                 if ($block1['day'] !== $block2['day']) continue;

//                 if ($block1['start']->lt($block2['end']) && $block1['end']->gt($block2['start'])) {
//                     // تداخل زمني
//                     $this->addConflict(
//                         'Time Overlap',
//                         "Classes for {$block1['gene']->section->planSubject->subject->subject_no} and {$block2['gene']->section->planSubject->subject->subject_no} overlap in time.",
//                         [$block1['gene']->gene_id, $block2['gene']->gene_id]
//                     );
//                 }
//             }
//         }
//     }

//     private function checkForResourceConflict(Collection $genesInSlot, string $resourceType, $timeslotsMap): void
//     {
//         $resourceIds = $genesInSlot->pluck("{$resourceType}_id");
//         $duplicates = $resourceIds->duplicates();

//         foreach ($duplicates as $id => $count) {
//             $conflictingGenes = $genesInSlot->where("{$resourceType}_id", $id);

//             // تحقق من التداخل الزمني الحقيقي
//             $overlappingGenes = $conflictingGenes->filter(function ($gene) use ($conflictingGenes, $timeslotsMap) {
//                 $startSlot = $timeslotsMap->get($gene->timeslot_ids[0]);
//                 // $endSlot = $timeslotsMap->get(end($gene->timeslot_ids));
//                 $timeslotIds = $gene->timeslot_ids; // هذا يُفعّل cast تلقائياً إلى مصفوفة
//                 $lastTimeslotId = end($timeslotIds); // الآن نستخدم end على متغير محلي
//                 $endSlot = $timeslotsMap->get($lastTimeslotId);
//                 if (!$startSlot || !$endSlot) return false;

//                 $start1 = \Carbon\Carbon::parse($startSlot->start_time);
//                 $end1 = \Carbon\Carbon::parse($endSlot->end_time);

//                 foreach ($conflictingGenes as $other) {
//                     if ($other->gene_id == $gene->gene_id) continue;
//                     $startSlot2 = $timeslotsMap->get($other->timeslot_ids[0]);
//                     // $endSlot2 = $timeslotsMap->get(end($other->timeslot_ids));

//                     $timeslotIds2 = $other->timeslot_ids; // هذا يُفعّل
//                     $lastTimeslotId2 = end($timeslotIds2); // الآن نستخدم
//                     $endSlot2 = $timeslotsMap->get($lastTimeslotId2);

//                     if (!$startSlot2 || !$endSlot2) continue;

//                     $start2 = \Carbon\Carbon::parse($startSlot2->start_time);
//                     $end2 = \Carbon\Carbon::parse($endSlot2->end_time);

//                     if ($start1->lt($end2) && $end1->gt($start2)) {
//                         return true;
//                     }
//                 }
//                 return false;
//             });

//             if ($overlappingGenes->isNotEmpty()) {
//                 $this->addConflict(
//                     ucfirst($resourceType) . ' Conflict',
//                     ucfirst($resourceType) . " #{$id} has overlapping classes.",
//                     $overlappingGenes->pluck('gene_id')->toArray()
//                 );
//             }
//         }
//     }
//     // private function findConflicts(): void
//     // {
//     //     // 1. تعارض الوقت (مدرس، قاعة، طلاب)
//     //     $genesByTimeslot = $this->genes->groupBy('timeslot_id_single');

//     //     foreach ($genesByTimeslot as $timeslotId => $genesInSlot) {
//     //         if ($genesInSlot->count() <= 1) continue;

//     //         $this->checkForResourceConflict($genesInSlot, 'instructor');
//     //         $this->checkForResourceConflict($genesInSlot, 'room');
//     //         $this->checkForResourceConflict($genesInSlot, 'section');
//     //     }

//     //     // 2. تعارض سعة القاعة
//     //     foreach ($this->genes as $gene) {
//     //         // تأكد أن $gene كائن وليس مصفوفة
//     //         if (!$gene instanceof Gene) continue;

//     //         if ($gene->section && $gene->room && $gene->section->student_count > $gene->room->room_size) {
//     //             $this->addConflict(
//     //                 'Room Capacity',
//     //                 "Section #{$gene->section->section_number} ({$gene->section->student_count} students) exceeds room {$gene->room->room_no} capacity ({$gene->room->room_size}).",
//     //                 [$gene->gene_id]
//     //             );
//     //         }

//     //         // 3. تعارض نوع القاعة (نظرية/عملية)
//     //         if ($gene->section && $gene->room && $gene->room->roomType && $gene->section->planSubject && $gene->section->planSubject->subject) {
//     //             $activityType = $gene->section->activity_type;
//     //             $roomTypeName = strtolower($gene->room->roomType->room_type_name);

//     //              if ($activityType == 'Practical' && !Str::contains($roomTypeName, ['lab', 'مختبر', 'workshop', 'ورشة'])) {
//     //             // if ($activityType == 'Practical' && !str_contains($roomTypeName, ['lab', 'مختبر', 'workshop', 'ورشة'])) {
//     //                 $this->addConflict(
//     //                     'Room Type',
//     //                     "Practical section for {$gene->section->planSubject->subject->subject_no} is scheduled in a non-lab room ({$gene->room->room_no}).",
//     //                     [$gene->gene_id]
//     //                 );
//     //             }
//     //         }

//     //         // 4. تعارض أهلية المدرس
//     //         if ($gene->instructor && $gene->section && $gene->section->planSubject && $gene->section->planSubject->subject) {
//     //             if (!$gene->instructor->canTeach($gene->section->planSubject->subject)) {
//     //                 $this->addConflict(
//     //                     'Instructor Qualification',
//     //                     "Instructor {$gene->instructor->user->name} is not qualified to teach {$gene->section->planSubject->subject->subject_no}.",
//     //                     [$gene->gene_id]
//     //                 );
//     //             }
//     //         }
//     //     }
//     // }

//     /**
//      * فحص تعارض مورد معين (مدرس، قاعة، شعبة)
//      */
//     // private function checkForResourceConflict(Collection $genesInSlot, string $resourceType): void
//     // {
//     //     $resourceIds = $genesInSlot->pluck("{$resourceType}_id");
//     //     $duplicates = $resourceIds->duplicates();

//     //     foreach ($duplicates as $id => $count) {
//     //         $conflictingGenes = $genesInSlot->where("{$resourceType}_id", $id);
//     //         $geneIds = $conflictingGenes->pluck('gene_id')->unique()->toArray();

//     //         $this->addConflict(
//     //             ucfirst($resourceType) . ' Conflict',
//     //             ucfirst($resourceType) . " #{$id} is used in " . $conflictingGenes->count() . " overlapping classes.",
//     //             $geneIds
//     //         );
//     //     }
//     // }

//     /**
//      * إضافة تعارض جديد
//      */
//     private function addConflict(string $type, string $description, array $geneIds): void
//     {
//         $this->conflicts[] = [
//             'type' => $type,
//             'description' => $description,
//             'gene_ids' => $geneIds,
//             'color' => $this->getConflictColor($type)
//         ];

//         foreach ($geneIds as $id) {
//             $this->conflictingGeneIds[$id] = $type;
//         }
//     }

//     /**
//      * الحصول على لون التعارض حسب النوع
//      */
//     private function getConflictColor(string $type): string
//     {
//         return match ($type) {
//             'Time Conflict', 'Instructor Conflict' => '#dc3545',
//             'Room Conflict', 'Room Capacity', 'Room Type' => '#fd7e14',
//             'Instructor Qualification' => '#6f42c1',
//             default => '#6c757d'
//         };
//     }

//     /**
//      * [الدالة المطلوبة] إرجاع جميع التعارضات
//      */
//     public function getConflicts(): array
//     {
//         return $this->conflicts;
//     }

//     /**
//      * [الدالة المطلوبة] إرجاع معرفات الجينات المتعارضة
//      */
//     public function getConflictingGeneIds(): array
//     {
//         return array_keys($this->conflictingGeneIds);
//     }

//     /**
//      * الحصول على نوع تعارض جين معين
//      */
//     public function getGeneConflictType(int $geneId): ?string
//     {
//         return $this->conflictingGeneIds[$geneId] ?? null;
//     }
// }



// **************************************** الكود القديم الاساسي يعمري ****************************

// namespace App\Services;

// use Illuminate\Support\Collection;
// use Illuminate\Support\Str;

// class ConflictCheckerService
// {
//     private Collection $genes;
//     private array $conflicts = [];
//     private array $conflictingGeneIds = [];

//     public function __construct(Collection $genes)
//     {
//         $this->genes = $genes;
//         $this->findConflicts();
//     }

//     private function findConflicts(): void
//     {
//         // 1. فحص تعارض الوقت (مدرس، قاعة، شعبة)
//         $genesByTimeslot = $this->genes->groupBy('timeslot_id');
//         foreach ($genesByTimeslot as $genesInSlot) {
//             if ($genesInSlot->count() <= 1) continue;

//             $this->checkForResourceConflict($genesInSlot, 'instructor');
//             $this->checkForResourceConflict($genesInSlot, 'room');
//             $this->checkForResourceConflict($genesInSlot, 'section');
//         }

//         // 2. فحص سعة القاعة ونوعها
//         foreach ($this->genes as $gene) {
//             if ($gene->section && $gene->room && $gene->section->student_count > $gene->room->room_size) {
//                 $this->addConflict(
//                     'Room Capacity',
//                     "Section #{$gene->section->section_number} for {$gene->section->planSubject->subject->subject_no} ({$gene->section->student_count} students) exceeds room {$gene->room->room_no} capacity ({$gene->room->room_size}).",
//                     [$gene->gene_id]
//                 );
//             }

//             if ($gene->section && $gene->room && $gene->room->roomType && $gene->section->planSubject && $gene->section->planSubject->subject) {
//                  $activityType = $gene->section->activity_type;
//                  $roomTypeName = strtolower($gene->room->roomType->room_type_name);
//                  if ($activityType == 'Practical' && !Str::contains($roomTypeName, ['lab', 'مختبر', 'workshop', 'ورشة'])) {
//                      $this->addConflict(
//                          'Room Type',
//                          "Practical section for {$gene->section->planSubject->subject->subject_no} is scheduled in a non-lab room ({$gene->room->room_no}).",
//                          [$gene->gene_id]
//                      );
//                  }
//             }
//         }
//     }

//     private function checkForResourceConflict(Collection $genesInSlot, string $resourceType)
//     {
//         $resourceIds = $genesInSlot->pluck("{$resourceType}_id");
//         $duplicateIds = $resourceIds->duplicates(); // دالة في Collection تجلب القيم المكررة

//         foreach ($duplicateIds as $id => $count) {
//             $conflictingGenes = $genesInSlot->where("{$resourceType}_id", $id);
//             $this->addConflict(
//                 ucfirst($resourceType) . ' Conflict', // e.g., Instructor Conflict
//                 ucfirst($resourceType) . " " . ($conflictingGenes->first()->{$resourceType}->user->name ?? $conflictingGenes->first()->{$resourceType}->{$resourceType . '_no'} ?? "ID:{$id}") . " is scheduled for " . $conflictingGenes->count() . " classes simultaneously.",
//                 $conflictingGenes->pluck('gene_id')->toArray()
//             );
//         }
//     }

//     private function addConflict(string $type, string $description, array $geneIds): void
//     {
//         $this->conflicts[] = ['type' => $type, 'description' => $description];
//         foreach ($geneIds as $id) {
//             $this->conflictingGeneIds[$id] = true; // استخدام مفتاح المصفوفة للسرعة
//         }
//     }

//     public function getConflicts(): array
//     {
//         return $this->conflicts;
//     }

//     public function getConflictingGeneIds(): array
//     {
//         return array_keys($this->conflictingGeneIds);
//     }
// }
