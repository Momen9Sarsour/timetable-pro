<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ConflictCheckerService
{
    private Collection $genes;
    private array $conflicts = [];
    private array $conflictingGeneIds = [];

    public function __construct(Collection $genes)
    {
        $this->genes = $genes;
        $this->findConflicts();
    }

    private function findConflicts(): void
    {
        // 1. فحص تعارض الوقت (مدرس، قاعة، شعبة)
        $genesByTimeslot = $this->genes->groupBy('timeslot_id');
        foreach ($genesByTimeslot as $genesInSlot) {
            if ($genesInSlot->count() <= 1) continue;

            $this->checkForResourceConflict($genesInSlot, 'instructor');
            $this->checkForResourceConflict($genesInSlot, 'room');
            $this->checkForResourceConflict($genesInSlot, 'section');
        }

        // 2. فحص سعة القاعة ونوعها
        foreach ($this->genes as $gene) {
            if ($gene->section && $gene->room && $gene->section->student_count > $gene->room->room_size) {
                $this->addConflict(
                    'Room Capacity',
                    "Section #{$gene->section->section_number} for {$gene->section->planSubject->subject->subject_no} ({$gene->section->student_count} students) exceeds room {$gene->room->room_no} capacity ({$gene->room->room_size}).",
                    [$gene->gene_id]
                );
            }

            if ($gene->section && $gene->room && $gene->room->roomType && $gene->section->planSubject && $gene->section->planSubject->subject) {
                 $activityType = $gene->section->activity_type;
                 $roomTypeName = strtolower($gene->room->roomType->room_type_name);
                 if ($activityType == 'Practical' && !Str::contains($roomTypeName, ['lab', 'مختبر', 'workshop', 'ورشة'])) {
                     $this->addConflict(
                         'Room Type',
                         "Practical section for {$gene->section->planSubject->subject->subject_no} is scheduled in a non-lab room ({$gene->room->room_no}).",
                         [$gene->gene_id]
                     );
                 }
            }
        }
    }

    private function checkForResourceConflict(Collection $genesInSlot, string $resourceType)
    {
        $resourceIds = $genesInSlot->pluck("{$resourceType}_id");
        $duplicateIds = $resourceIds->duplicates(); // دالة في Collection تجلب القيم المكررة

        foreach ($duplicateIds as $id => $count) {
            $conflictingGenes = $genesInSlot->where("{$resourceType}_id", $id);
            $this->addConflict(
                ucfirst($resourceType) . ' Conflict', // e.g., Instructor Conflict
                ucfirst($resourceType) . " " . ($conflictingGenes->first()->{$resourceType}->user->name ?? $conflictingGenes->first()->{$resourceType}->{$resourceType . '_no'} ?? "ID:{$id}") . " is scheduled for " . $conflictingGenes->count() . " classes simultaneously.",
                $conflictingGenes->pluck('gene_id')->toArray()
            );
        }
    }

    private function addConflict(string $type, string $description, array $geneIds): void
    {
        $this->conflicts[] = ['type' => $type, 'description' => $description];
        foreach ($geneIds as $id) {
            $this->conflictingGeneIds[$id] = true; // استخدام مفتاح المصفوفة للسرعة
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
}