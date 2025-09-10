@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                <div>
                    <h5 class="page-title mb-1 d-flex align-items-center">
                        <i class="fas fa-calendar-alt text-primary me-2" style="font-size: 1.1rem;"></i>
                        Timetable Result - Chromosome #{{ $chromosome->chromosome_id }}
                    </h5>
                    <div class="text-muted small">
                        <span class="badge bg-light text-dark me-2">Penalty: {{ $chromosome->penalty_value }}</span>
                        <span class="badge bg-light text-dark me-2">Generation: {{ $chromosome->generation_number }}</span>
                        <span class="badge bg-light text-dark">Run ID: {{ $chromosome->population->population_id }}</span>
                    </div>
                </div>

                <div class="d-flex gap-1 flex-wrap">
                    <a href="{{ route('algorithm-control.timetable.results.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1" style="font-size: 0.75rem;"></i>
                        <span class="d-none d-sm-inline">Back</span>
                    </a>
                    <button class="btn btn-warning btn-sm" id="undoPendingBtn" disabled>
                        <i class="fas fa-undo me-1" style="font-size: 0.75rem;"></i>
                        <span class="d-none d-sm-inline">Undo</span>
                    </button>
                    <button class="btn btn-primary btn-sm" id="saveChangesBtn">
                        <i class="fas fa-save me-1" style="font-size: 0.75rem;"></i>
                        <span class="d-none d-sm-inline">Save</span>
                    </button>
                    <button class="btn btn-success btn-sm" id="exportPdfBtn">
                        <i class="fas fa-file-pdf me-1" style="font-size: 0.75rem;"></i>
                        <span class="d-none d-sm-inline">PDF</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Conflicts Detection Card -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-1">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle text-warning me-2" style="font-size: 0.9rem;"></i>
                            <h6 class="card-title mb-0" style="font-size: 0.9rem;">Conflicts Detection</h6>
                        </div>
                        <span class="badge {{ count($conflicts) > 0 ? 'bg-danger' : 'bg-success' }} px-2 py-1" style="font-size: 0.75rem;">
                            {{ count($conflicts) }} Conflicts
                        </span>
                    </div>
                </div>

                <div class="card-body pt-2 pb-3">
                    <div class="row g-2">
                        <!-- Conflicts List -->
                        <div class="col-lg-8">
                            <div class="conflicts-container" style="max-height: 150px; overflow-y: auto;">
                                @if (!empty($conflicts))
                                    <div class="list-group list-group-flush">
                                        @foreach ($conflicts as $c)
                                            <div class="list-group-item border-start border-3 py-2 px-3 mb-1"
                                                 style="border-start-color: {{ $c['color'] }} !important; background-color: {{ $c['color'] }}15; font-size: 0.8rem;">
                                                <div class="d-flex align-items-start">
                                                    <i class="fas fa-exclamation-circle me-2 mt-1" style="color: {{ $c['color'] }}; font-size: 0.7rem;"></i>
                                                    <div>
                                                        <strong style="font-size: 0.8rem;">{{ $c['type'] }}:</strong>
                                                        <span class="text-muted" style="font-size: 0.75rem;">{{ $c['description'] }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-2">
                                        <i class="fas fa-check-circle text-success mb-1" style="font-size: 1.5rem;"></i>
                                        <h6 class="text-success mb-0" style="font-size: 0.85rem;">No Conflicts Found</h6>
                                        <small class="text-muted" style="font-size: 0.7rem;">All schedule blocks are properly arranged</small>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="col-lg-4">
                            <div class="legend-card bg-light rounded p-2">
                                <h6 class="mb-2" style="font-size: 0.8rem;">
                                    <i class="fas fa-palette me-1" style="font-size: 0.7rem;"></i>
                                    Color Legend
                                </h6>
                                <div class="legend-items">
                                    <div class="legend-item d-flex align-items-center mb-1">
                                        <div class="legend-color me-2" style="width: 12px; height: 12px; background: #0d6efd; border-radius: 2px;"></div>
                                        <small style="font-size: 0.7rem;">No Conflict</small>
                                    </div>
                                    <div class="legend-item d-flex align-items-center mb-1">
                                        <div class="legend-color me-2" style="width: 12px; height: 12px; background: #dc3545; border-radius: 2px;"></div>
                                        <small style="font-size: 0.7rem;">Time/Instructor Conflict</small>
                                    </div>
                                    <div class="legend-item d-flex align-items-center mb-1">
                                        <div class="legend-color me-2" style="width: 12px; height: 12px; background: #fd7e14; border-radius: 2px;"></div>
                                        <small style="font-size: 0.7rem;">Room Conflict</small>
                                    </div>
                                    <div class="legend-item d-flex align-items-center">
                                        <div class="legend-color me-2" style="width: 12px; height: 12px; background: #6f42c1; border-radius: 2px;"></div>
                                        <small style="font-size: 0.7rem;">Qualification Issue</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Timetable Schedule Card -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-1">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0" style="font-size: 0.9rem;">
                            <i class="fas fa-calendar-week text-primary me-2" style="font-size: 0.8rem;"></i>
                            Weekly Schedule
                        </h6>
                        <div class="d-flex gap-1">
                            <button class="btn btn-outline-primary btn-sm" id="zoomInBtn" title="Zoom In" style="padding: 0.25rem 0.4rem;">
                                <i class="fas fa-search-plus" style="font-size: 0.7rem;"></i>
                            </button>
                            <button class="btn btn-outline-primary btn-sm" id="zoomOutBtn" title="Zoom Out" style="padding: 0.25rem 0.4rem;">
                                <i class="fas fa-search-minus" style="font-size: 0.7rem;"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" id="resetZoomBtn" title="Reset Zoom" style="padding: 0.25rem 0.4rem;">
                                <i class="fas fa-expand-arrows-alt" style="font-size: 0.7rem;"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="timetable-wrapper" style="height: 75vh; overflow: auto; position: relative;">
                        <div class="timetable-container" id="timetableContainer" style="transform-origin: top left;">
                            <table class="timetable table-bordered mb-0">
                                <thead class="sticky-top">
                                    <tr>
                                        <th class="group-header sticky-start">Student Group</th>
                                        @foreach ($timeslotsByDay as $day => $daySlots)
                                            <th class="day-header text-center" colspan="{{ $daySlots->count() }}">{{ $day }}</th>
                                        @endforeach
                                    </tr>
                                    <tr>
                                        <th class="group-header sticky-start"></th>
                                        @foreach (collect($timeslotsByDay)->flatten() as $timeslot)
                                            <th class="time-header text-center">
                                                {{ \Carbon\Carbon::parse($timeslot->start_time)->format('H:i') }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $sortedGroups = collect($scheduleByGroup)->sortBy(fn($g) => explode('|', $g['name'])[0]); @endphp
                                    @foreach ($sortedGroups as $groupId => $group)
                                        <tr>
                                            <th class="group-header sticky-start">{{ $group['name'] }}</th>
                                            <td class="group-row position-relative drop-zone" colspan="{{ $totalColumnsOverall }}" data-group-id="{{ $groupId }}">
                                                <!-- Grid Background -->
                                                <div class="grid-background d-flex position-absolute w-100 h-100" style="top: 0; left: 0; pointer-events: none; z-index: 1;">
                                                    @for ($i = 0; $i < $totalColumnsOverall; $i++)
                                                        <div class="grid-column flex-fill border-end" style="border-color: rgba(0,0,0,0.08);"></div>
                                                    @endfor
                                                </div>

                                                <!-- Schedule Blocks -->
                                                @php $slotsForGroup = []; @endphp
                                                @foreach (collect($group['blocks'])->unique('gene_id') as $block)
                                                    @php
                                                        $timeslotIds = $block->timeslot_ids;
                                                        if (empty($timeslotIds)) continue;

                                                        $startSlotId = $timeslotIds[0];
                                                        if (!isset($slotPositions[$startSlotId])) continue;

                                                        $startColumn = $slotPositions[$startSlotId];
                                                        $span = count($timeslotIds);
                                                        $stackLevel = 0;

                                                        while (
                                                            isset($slotsForGroup[$stackLevel]) &&
                                                            collect($slotsForGroup[$stackLevel])->some(
                                                                fn($o) => $startColumn < $o['start'] + $o['span'] &&
                                                                    $startColumn + $span > $o['start']
                                                            )
                                                        ) {
                                                            $stackLevel++;
                                                        }

                                                        $slotsForGroup[$stackLevel][] = [
                                                            'start' => $startColumn,
                                                            'span' => $span
                                                        ];

                                                        $left = ($startColumn / $totalColumnsOverall) * 100;
                                                        $width = ($span / $totalColumnsOverall) * 100;
                                                        $height = 85;
                                                        $top = $stackLevel * ($height + 8) + 5;

                                                        $conflictType = $conflictChecker->getGeneConflictType($block->gene_id);
                                                        $borderColor = $conflictChecker->getGeneConflictColor($block->gene_id);
                                                        $bgColor = match ($conflictType) {
                                                            'Time Overlap', 'Instructor Conflict' => '#fff0f1',
                                                            'Room Conflict', 'Room Capacity', 'Room Type' => '#fff4e6',
                                                            'Instructor Qualification' => '#f8f3fc',
                                                            default => '#ffffff',
                                                        };
                                                    @endphp

                                                    <div class="event-block draggable-block"
                                                         draggable="true"
                                                         data-gene-id="{{ $block->gene_id }}"
                                                         data-original-left="{{ $left }}"
                                                         data-original-top="{{ $top }}"
                                                         data-group-id="{{ $groupId }}"
                                                         style="
                                                            position: absolute;
                                                            top: {{ $top }}px;
                                                            left: {{ $left }}%;
                                                            width: calc({{ $width }}% - 6px);
                                                            height: {{ $height }}px;
                                                            background: {{ $bgColor }};
                                                            border: 2px solid {{ $borderColor }};
                                                            border-radius: 6px;
                                                            padding: 6px;
                                                            cursor: move;
                                                            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                                                            transition: all 0.2s ease;
                                                            z-index: 10;
                                                            touch-action: none;
                                                         ">

                                                        <div class="event-content d-flex flex-column h-100">
                                                            <div class="event-subject fw-bold mb-1 text-truncate" style="font-size: 0.7rem; line-height: 1.1;">
                                                                {{ optional($block->section->planSubject->subject)->subject_no }} -
                                                                {{ Str::limit(optional($block->section->planSubject->subject)->subject_name, 25) }}
                                                            </div>

                                                            <div class="event-instructor editable-field text-truncate mb-1"
                                                                 data-field="instructor"
                                                                 data-gene-id="{{ $block->gene_id }}"
                                                                 data-subject-id="{{ optional($block->section->planSubject->subject)->id }}"
                                                                 style="font-size: 0.65rem; cursor: pointer; padding: 1px 3px; border-radius: 2px; background: rgba(13,110,253,0.05);">
                                                                <i class="fas fa-user-tie me-1" style="font-size: 0.6rem;"></i>
                                                                {{ Str::limit(optional($block->instructor->user)->name, 20) }}
                                                            </div>

                                                            <div class="event-room editable-field text-truncate mb-1"
                                                                 data-field="room"
                                                                 data-gene-id="{{ $block->gene_id }}"
                                                                 data-timeslot-ids='@json($block->timeslot_ids)'
                                                                 style="font-size: 0.65rem; cursor: pointer; padding: 1px 3px; border-radius: 2px; background: rgba(13,110,253,0.05);">
                                                                <i class="fas fa-door-open me-1" style="font-size: 0.6rem;"></i>
                                                                {{ optional($block->room)->room_name }}
                                                            </div>

                                                            <div class="event-type text-muted mt-auto" style="font-size: 0.6rem; line-height: 1;">
                                                                <i class="fas fa-clock me-1" style="font-size: 0.55rem;"></i>
                                                                {{ ucfirst($block->block_type) }} | {{ $block->block_duration }}h
                                                            </div>
                                                        </div>

                                                        <!-- Resize Handle -->
                                                        <div class="resize-handle position-absolute"
                                                             style="bottom: 1px; right: 1px; width: 8px; height: 8px; cursor: se-resize; background: {{ $borderColor }}; opacity: 0.7; border-radius: 1px;"></div>
                                                    </div>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Drop Zone Overlay -->
<div id="dropZoneOverlay" class="position-fixed w-100 h-100 d-none" style="top: 0; left: 0; background: rgba(0,123,255,0.1); z-index: 9999; border: 3px dashed #007bff;">
    <div class="d-flex align-items-center justify-content-center h-100">
        <div class="text-center">
            <i class="fas fa-arrows-alt fa-2x text-primary mb-2"></i>
            <h5 class="text-primary">Drop to Reschedule</h5>
            <p class="text-muted mb-0">Release to place the block in new time slot</p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* Timetable Styling */
.timetable {
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.75rem;
    min-width: 3200px;
    width: max-content;
    background: white;
}

.timetable th,
.timetable td {
    border: 1px solid #dee2e6;
    padding: 0;
    vertical-align: top;
}

.group-header {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    font-weight: 600;
    padding: 8px 10px;
    width: 280px;
    min-width: 280px;
    font-size: 0.75rem;
    border-right: 2px solid #dee2e6 !important;
    position: sticky;
    left: 0;
    z-index: 20;
    box-shadow: 2px 0 4px rgba(0,0,0,0.1);
}

body.dark-mode .group-header {
    background: linear-gradient(135deg, var(--dark-bg-secondary), var(--dark-bg));
    color: var(--dark-text-secondary);
    border-right-color: var(--dark-border) !important;
}

.day-header {
    background: linear-gradient(135deg, #0d6efd, #0056b3);
    color: white;
    font-weight: 700;
    padding: 8px;
    font-size: 0.75rem;
    border-bottom: 2px solid #0056b3 !important;
    position: sticky;
    top: 0;
    z-index: 18;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.time-header {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    color: #6c757d;
    font-size: 0.65rem;
    font-weight: 500;
    padding: 6px 3px;
    width: 120px;
    min-width: 120px;
    border-bottom: 1px solid #dee2e6 !important;
    position: sticky;
    top: 41px;
    z-index: 17;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

body.dark-mode .time-header {
    background: linear-gradient(135deg, var(--dark-bg), var(--dark-bg-secondary));
    color: var(--dark-text-secondary);
    border-bottom-color: var(--dark-border) !important;
}

.group-row {
    height: 180px;
    min-height: 180px;
    position: relative;
    background: #fafbfc;
    border: 1px solid #e9ecef;
}

body.dark-mode .group-row {
    background: var(--dark-bg);
    border-color: var(--dark-border);
}

.sticky-start {
    position: sticky;
    left: 0;
    z-index: 15;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 20;
}

/* Event Block Enhancements */
.event-block {
    transition: all 0.2s ease;
    overflow: hidden;
    user-select: none;
}

.event-block:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.15) !important;
    transform: translateY(-1px);
    z-index: 25 !important;
}

.event-block.dragging {
    opacity: 0.8;
    transform: rotate(2deg) scale(1.03);
    z-index: 1000 !important;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3) !important;
    cursor: grabbing !important;
}

.editable-field:hover {
    background: rgba(13, 110, 253, 0.12) !important;
    transform: scale(1.01);
}

/* Grid Background Enhancement */
.grid-column {
    border-right: 1px solid rgba(0,0,0,0.04);
}

.grid-column:nth-child(5n) {
    border-right: 1px solid rgba(0,0,0,0.08);
}

body.dark-mode .grid-column {
    border-right-color: rgba(255,255,255,0.08);
}

body.dark-mode .grid-column:nth-child(5n) {
    border-right-color: rgba(255,255,255,0.15);
}

/* Drag and Drop Styling */
.drop-zone {
    transition: all 0.2s ease;
}

.drop-zone.drag-over {
    background: rgba(0,123,255,0.08) !important;
    border: 2px dashed #007bff !important;
}

/* Zoom Controls */
.timetable-wrapper {
    position: relative;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
}

body.dark-mode .timetable-wrapper {
    border-color: var(--dark-border);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .group-header {
        width: 220px;
        min-width: 220px;
        font-size: 0.7rem;
        padding: 6px 8px;
    }

    .time-header {
        width: 90px;
        min-width: 90px;
        font-size: 0.6rem;
    }

    .event-block {
        min-height: 75px;
        padding: 4px;
    }

    .event-content {
        font-size: 0.6rem;
    }

    .timetable {
        min-width: 2400px;
    }
}

/* Loading State */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.95);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    border-radius: 0.5rem;
}

body.dark-mode .loading-overlay {
    background: rgba(0,0,0,0.8);
}

/* Conflict Indicators */
.conflict-indicator {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    animation: conflictPulse 2s infinite;
}

@keyframes conflictPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.2); }
}

/* Select2 Overrides */
.select2-container {
    z-index: 9999 !important;
}

.select2-dropdown {
    border: 1px solid #0d6efd;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    font-size: 0.75rem;
}

/* Resize Handle */
.resize-handle {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.event-block:hover .resize-handle {
    opacity: 0.8;
}

.resize-handle:hover {
    opacity: 1 !important;
    transform: scale(1.2);
}

/* Touch Support */
@media (pointer: coarse) {
    .event-block {
        cursor: grab;
    }

    .event-block:active {
        cursor: grabbing;
    }
}

/* Print Styles */
@media print {
    .timetable-wrapper {
        height: auto !important;
        overflow: visible !important;
    }

    .sticky-start,
    .sticky-top {
        position: relative !important;
    }

    .event-block {
        break-inside: avoid;
    }

    .card-header,
    .btn,
    .conflicts-container {
        display: none !important;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Server Data
    const allRooms = @json($allRooms);
    const allInstructors = @json($allInstructors);
    const timeSlotUsage = @json($timeSlotUsage);

    let currentZoom = 1;
    let pendingChanges = [];
    let draggedElement = null;
    let isDragging = false;

    // Initialize all functionalities
    initializeDragAndDrop();
    initializeZoomControls();
    initializeEditableFields();
    initializeButtons();

    function initializeDragAndDrop() {
        const draggableBlocks = document.querySelectorAll('.draggable-block');
        const dropZones = document.querySelectorAll('.drop-zone');
        const dropZoneOverlay = document.getElementById('dropZoneOverlay');

        // Enhanced drag and drop with better touch support
        draggableBlocks.forEach(block => {
            // Mouse events
            block.addEventListener('dragstart', handleDragStart);
            block.addEventListener('dragend', handleDragEnd);

            // Touch events for mobile
            block.addEventListener('touchstart', handleTouchStart, { passive: false });
            block.addEventListener('touchmove', handleTouchMove, { passive: false });
            block.addEventListener('touchend', handleTouchEnd, { passive: false });
        });

        dropZones.forEach(zone => {
            zone.addEventListener('dragover', handleDragOver);
            zone.addEventListener('dragenter', handleDragEnter);
            zone.addEventListener('dragleave', handleDragLeave);
            zone.addEventListener('drop', handleDrop);
        });

        function handleDragStart(e) {
            isDragging = true;
            draggedElement = this;
            this.classList.add('dragging');
            dropZoneOverlay.classList.remove('d-none');

            // Set drag data
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.dataset.geneId);

            // Create ghost image
            const rect = this.getBoundingClientRect();
            e.dataTransfer.setDragImage(this, rect.width / 2, rect.height / 2);
        }

        function handleDragEnd(e) {
            isDragging = false;
            this.classList.remove('dragging');
            dropZoneOverlay.classList.add('d-none');

            // Clean up all drop zones
            document.querySelectorAll('.drop-zone').forEach(zone => {
                zone.classList.remove('drag-over');
            });

            draggedElement = null;
        }

        function handleDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        }

        function handleDragEnter(e) {
            e.preventDefault();
            if (isDragging && draggedElement) {
                this.classList.add('drag-over');
            }
        }

        function handleDragLeave(e) {
            if (!this.contains(e.relatedTarget)) {
                this.classList.remove('drag-over');
            }
        }

        function handleDrop(e) {
            e.preventDefault();
            this.classList.remove('drag-over');

            const geneId = e.dataTransfer.getData('text/plain');
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            handleBlockDrop(geneId, x, y, this);
        }

        // Touch support functions
        let touchStartX, touchStartY;
        let originalPosition = {};

        function handleTouchStart(e) {
            const touch = e.touches[0];
            touchStartX = touch.clientX;
            touchStartY = touch.clientY;

            draggedElement = this;
            originalPosition = {
                left: this.style.left,
                top: this.style.top
            };

            this.classList.add('dragging');
            dropZoneOverlay.classList.remove('d-none');
        }

        function handleTouchMove(e) {
            e.preventDefault();
            if (!draggedElement) return;

            const touch = e.touches[0];
            const deltaX = touch.clientX - touchStartX;
            const deltaY = touch.clientY - touchStartY;

            const rect = draggedElement.getBoundingClientRect();
            draggedElement.style.position = 'fixed';
            draggedElement.style.left = (rect.left + deltaX) + 'px';
            draggedElement.style.top = (rect.top + deltaY) + 'px';
            draggedElement.style.zIndex = '1000';

            // Find drop zone under touch
            const elementBelow = document.elementFromPoint(touch.clientX, touch.clientY);
            const dropZone = elementBelow?.closest('.drop-zone');

            document.querySelectorAll('.drop-zone').forEach(zone => {
                zone.classList.remove('drag-over');
            });

            if (dropZone) {
                dropZone.classList.add('drag-over');
            }
        }

        function handleTouchEnd(e) {
            if (!draggedElement) return;

            const touch = e.changedTouches[0];
            const elementBelow = document.elementFromPoint(touch.clientX, touch.clientY);
            const dropZone = elementBelow?.closest('.drop-zone');

            draggedElement.classList.remove('dragging');
            dropZoneOverlay.classList.add('d-none');

            document.querySelectorAll('.drop-zone').forEach(zone => {
                zone.classList.remove('drag-over');
            });

            if (dropZone) {
                const rect = dropZone.getBoundingClientRect();
                const x = touch.clientX - rect.left;
                const y = touch.clientY - rect.top;

                // Reset position first
                draggedElement.style.position = 'absolute';
                draggedElement.style.zIndex = '10';

                handleBlockDrop(draggedElement.dataset.geneId, x, y, dropZone);
            } else {
                // Return to original position
                draggedElement.style.position = 'absolute';
                draggedElement.style.left = originalPosition.left;
                draggedElement.style.top = originalPosition.top;
                draggedElement.style.zIndex = '10';
            }

            draggedElement = null;
        }
    }

    function handleBlockDrop(geneId, x, y, container) {
        const block = document.querySelector(`[data-gene-id="${geneId}"]`);
        if (!block) return;

        const containerWidth = container.offsetWidth;
        const newLeft = Math.max(0, Math.min(95, (x / containerWidth) * 100));

        // Snap to grid - improved grid snapping
        const gridSize = 100 / {{ $totalColumnsOverall }};
        const snappedLeft = Math.round(newLeft / gridSize) * gridSize;

        // Calculate proper vertical position with stacking
        const newTop = Math.max(5, Math.min(150, y - 40));
        const stackHeight = 93; // Height + margin
        const stackLevel = Math.floor(newTop / stackHeight);
        const finalTop = stackLevel * stackHeight + 5;

        // Update block position
        block.style.position = 'absolute';
        block.style.left = snappedLeft + '%';
        block.style.top = finalTop + 'px';
        block.style.zIndex = '10';

        // Update group if moved to different row
        const targetGroupId = container.dataset.groupId;
        const currentGroupId = block.dataset.groupId;

        if (targetGroupId && targetGroupId !== currentGroupId) {
            block.dataset.groupId = targetGroupId;
            container.appendChild(block);
        }

        // Add to pending changes
        pendingChanges.push({
            type: 'move',
            geneId: geneId,
            newPosition: { left: snappedLeft, top: finalTop },
            newGroupId: targetGroupId,
            oldGroupId: currentGroupId
        });

        updateChangesIndicator();
        showToast('Block moved successfully', 'info');
    }

    function initializeZoomControls() {
        const container = document.getElementById('timetableContainer');

        document.getElementById('zoomInBtn').addEventListener('click', () => {
            currentZoom = Math.min(2, currentZoom + 0.15);
            updateZoom();
        });

        document.getElementById('zoomOutBtn').addEventListener('click', () => {
            currentZoom = Math.max(0.5, currentZoom - 0.15);
            updateZoom();
        });

        document.getElementById('resetZoomBtn').addEventListener('click', () => {
            currentZoom = 1;
            updateZoom();
        });

        function updateZoom() {
            container.style.transform = `scale(${currentZoom})`;
            container.style.width = `${100 / currentZoom}%`;
            container.style.height = `${100 / currentZoom}%`;
        }
    }

    function initializeEditableFields() {
        document.addEventListener('click', function(e) {
            const field = e.target.closest('.editable-field');
            if (!field || isDragging) return;

            const fieldType = field.dataset.field;
            const geneId = field.dataset.geneId;
            const currentValue = field.textContent.trim().replace(/^[^\s]*\s/, ''); // Remove icon

            let options = [];
            if (fieldType === 'instructor') {
                const subjectId = field.dataset.subjectId;
                options = getInstructorsForSubject(parseInt(subjectId));
            } else if (fieldType === 'room') {
                const timeslotIds = JSON.parse(field.dataset.timeslotIds);
                const blockType = field.closest('.event-block').querySelector('.event-type').textContent.includes('Practical') ? 'practical' : 'theory';
                options = getAvailableRoomsForTimeslots(timeslotIds, blockType, field.dataset.subjectId);
            }

            createEditSelect(field, fieldType, geneId, currentValue, options);
        });
    }

    function createEditSelect(field, fieldType, geneId, currentValue, options) {
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm';
        select.style.fontSize = '0.65rem';
        select.dataset.field = fieldType;
        select.dataset.geneId = geneId;

        // Copy data attributes
        if (fieldType === 'instructor') {
            select.dataset.subjectId = field.dataset.subjectId;
        } else if (fieldType === 'room') {
            select.dataset.timeslotIds = field.dataset.timeslotIds;
        }

        options.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            if (item.name === currentValue) option.selected = true;
            select.appendChild(option);
        });

        field.replaceWith(select);
        $(select).select2({
            width: '100%',
            dropdownAutoWidth: true,
            placeholder: `Select ${fieldType}...`,
            minimumResultsForSearch: 5
        }).focus();

        $(select).on('select2:close', function() {
            const selectedText = $(this).find(':selected').text();
            updateFieldValue(this, fieldType, geneId, selectedText);
        });
    }

    function updateFieldValue(select, fieldType, geneId, newValue) {
        const icon = fieldType === 'instructor' ? 'fa-user-tie' : 'fa-door-open';

        const div = document.createElement('div');
        div.className = `event-${fieldType} editable-field text-truncate mb-1`;
        div.dataset.field = fieldType;
        div.dataset.geneId = geneId;
        div.style.cssText = 'font-size: 0.65rem; cursor: pointer; padding: 1px 3px; border-radius: 2px; background: rgba(13,110,253,0.05);';
        div.innerHTML = `<i class="fas ${icon} me-1" style="font-size: 0.6rem;"></i>${newValue}`;

        // Copy additional data attributes
        if (fieldType === 'instructor') {
            div.dataset.subjectId = select.dataset.subjectId;
        } else if (fieldType === 'room') {
            div.dataset.timeslotIds = select.dataset.timeslotIds;
        }

        $(select).replaceWith(div);

        // Add to pending changes
        pendingChanges.push({
            type: 'edit',
            geneId: geneId,
            field: fieldType,
            newValue: newValue,
            oldValue: select.value
        });

        updateChangesIndicator();
        updateBlockConflictStatus(div.closest('.event-block'), fieldType, newValue);
        showToast(`${fieldType.charAt(0).toUpperCase() + fieldType.slice(1)} updated`, 'success');
    }

    function updateBlockConflictStatus(block, fieldType, newValue) {
        if (fieldType === 'room') {
            const timeslotIds = JSON.parse(block.querySelector('.event-room').dataset.timeslotIds);
            const hasConflict = checkRoomConflict(newValue, timeslotIds);

            if (hasConflict) {
                block.style.borderColor = '#dc3545';
                block.style.backgroundColor = '#fff0f1';
                addConflictIndicator(block, 'room');
            } else {
                block.style.borderColor = '#0d6efd';
                block.style.backgroundColor = '#ffffff';
                removeConflictIndicator(block);
            }
        }
    }

    function addConflictIndicator(block, type) {
        let indicator = block.querySelector('.conflict-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'conflict-indicator';
            block.appendChild(indicator);
        }

        const colors = {
            room: '#fd7e14',
            instructor: '#dc3545',
            qualification: '#6f42c1'
        };

        indicator.style.backgroundColor = colors[type] || '#dc3545';
    }

    function removeConflictIndicator(block) {
        const indicator = block.querySelector('.conflict-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    function initializeButtons() {
        // Save Changes Button
        document.getElementById('saveChangesBtn').addEventListener('click', function() {
            if (pendingChanges.length === 0) {
                showToast('No changes to save', 'info');
                return;
            }

            const saveBtn = this;
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
            saveBtn.disabled = true;

            // Add loading overlay
            showLoadingOverlay();

            // Simulate API call
            setTimeout(() => {
                console.log('Saving changes:', pendingChanges);

                pendingChanges = [];
                updateChangesIndicator();

                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
                hideLoadingOverlay();
                showToast('Changes saved successfully', 'success');
            }, 1500);
        });

        // Undo Changes Button
        document.getElementById('undoPendingBtn').addEventListener('click', function() {
            if (pendingChanges.length === 0) {
                showToast('No changes to undo', 'info');
                return;
            }

            // Revert all pending changes
            pendingChanges.forEach(change => {
                if (change.type === 'move') {
                    const block = document.querySelector(`[data-gene-id="${change.geneId}"]`);
                    if (block) {
                        block.style.left = block.dataset.originalLeft + '%';
                        block.style.top = block.dataset.originalTop + 'px';

                        // Restore original group if moved
                        if (change.oldGroupId && change.oldGroupId !== change.newGroupId) {
                            const originalContainer = document.querySelector(`[data-group-id="${change.oldGroupId}"]`);
                            if (originalContainer) {
                                originalContainer.appendChild(block);
                                block.dataset.groupId = change.oldGroupId;
                            }
                        }
                    }
                } else if (change.type === 'edit') {
                    // For edit changes, we could implement reverting to original values
                    console.log(`Reverting ${change.field} for gene ${change.geneId}`);
                }
            });

            pendingChanges = [];
            updateChangesIndicator();
            showToast('All changes undone', 'warning');
        });

        // Export PDF Button
        document.getElementById('exportPdfBtn').addEventListener('click', generatePDF);
    }

    function updateChangesIndicator() {
        const saveBtn = document.getElementById('saveChangesBtn');
        const undoBtn = document.getElementById('undoPendingBtn');

        if (pendingChanges.length > 0) {
            saveBtn.classList.remove('btn-primary');
            saveBtn.classList.add('btn-warning');
            saveBtn.innerHTML = `<i class="fas fa-save me-1" style="font-size: 0.75rem;"></i><span class="d-none d-sm-inline">Save (${pendingChanges.length})</span><span class="d-sm-none">Save</span>`;

            undoBtn.disabled = false;
        } else {
            saveBtn.classList.remove('btn-warning');
            saveBtn.classList.add('btn-primary');
            saveBtn.innerHTML = '<i class="fas fa-save me-1" style="font-size: 0.75rem;"></i><span class="d-none d-sm-inline">Save</span>';

            undoBtn.disabled = true;
        }
    }

    function showLoadingOverlay() {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary mb-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="small text-muted">Saving changes...</div>
            </div>
        `;
        document.querySelector('.timetable-wrapper').appendChild(overlay);
    }

    function hideLoadingOverlay() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    // Helper Functions
    function getInstructorsForSubject(subjectId) {
        return allInstructors.filter(i => i.subject_ids.includes(subjectId));
    }

    function getAvailableRoomsForTimeslots(timeslotIds, blockType, subjectId) {
        let filteredRooms = allRooms.filter(room => {
            if (blockType === 'practical') {
                return room.type.toLowerCase().includes('lab') ||
                       room.type.toLowerCase().includes('مختبر') ||
                       room.type.toLowerCase().includes('workshop') ||
                       room.type.toLowerCase().includes('ورشة');
            } else {
                return !room.type.toLowerCase().includes('lab') &&
                       !room.type.toLowerCase().includes('مختبر') &&
                       !room.type.toLowerCase().includes('workshop') &&
                       !room.type.toLowerCase().includes('ورشة');
            }
        });

        return filteredRooms.filter(room => {
            for (const tsId of timeslotIds) {
                if (timeSlotUsage[tsId] && timeSlotUsage[tsId].rooms) {
                    if (timeSlotUsage[tsId].rooms.includes(room.id)) {
                        return false;
                    }
                }
            }
            return true;
        });
    }

    function checkRoomConflict(roomId, timeslotIds) {
        for (const tsId of timeslotIds) {
            if (timeSlotUsage[tsId] && timeSlotUsage[tsId].rooms) {
                if (timeSlotUsage[tsId].rooms.includes(parseInt(roomId))) {
                    return true;
                }
            }
        }
        return false;
    }

    function showToast(message, type = 'info') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }

        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body" style="font-size: 0.8rem;">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        try {
            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        } catch (error) {
            setTimeout(() => toast.remove(), 3000);
        }
    }

    async function generatePDF() {
        const exportBtn = document.getElementById('exportPdfBtn');
        const originalText = exportBtn.innerHTML;

        exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generating...';
        exportBtn.disabled = true;
        showLoadingOverlay();

        try {
            // Check if libraries are loaded
            if (typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
                throw new Error('PDF libraries not loaded');
            }

            const timetableContainer = document.querySelector('.timetable-container');

            // Temporarily adjust styles for PDF
            const originalOverflow = timetableContainer.style.overflow;
            const originalHeight = timetableContainer.style.height;
            const originalTransform = timetableContainer.style.transform;

            timetableContainer.style.overflow = 'visible';
            timetableContainer.style.height = 'auto';
            timetableContainer.style.transform = 'scale(1)';

            // Generate canvas from HTML
            const canvas = await html2canvas(timetableContainer, {
                scale: 1.5,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                width: timetableContainer.scrollWidth,
                height: timetableContainer.scrollHeight,
                logging: false
            });

            // Restore original styles
            timetableContainer.style.overflow = originalOverflow;
            timetableContainer.style.height = originalHeight;
            timetableContainer.style.transform = originalTransform;

            // Create PDF
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({
                orientation: 'landscape',
                unit: 'mm',
                format: 'a3'
            });

            const imgData = canvas.toDataURL('image/png', 0.95);
            const imgWidth = 380;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            // Add title page
            pdf.setFontSize(18);
            pdf.text('Timetable Schedule', 20, 25);
            pdf.setFontSize(12);
            pdf.text(`Chromosome ID: {{ $chromosome->chromosome_id }}`, 20, 35);
            pdf.text(`Penalty: {{ $chromosome->penalty_value }}`, 20, 43);
            pdf.text(`Generation: {{ $chromosome->generation_number }}`, 20, 51);
            pdf.text(`Generated: ${new Date().toLocaleDateString()}`, 20, 59);

            // Add the timetable image
            if (imgHeight > 250) {
                // Split into multiple pages if too large
                let currentY = 0;
                const pageHeight = 250;

                while (currentY < imgHeight) {
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight, '', 'FAST', -currentY);
                    currentY += pageHeight;
                }
            } else {
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
            }

            // Save the PDF
            pdf.save(`timetable-chromosome-{{ $chromosome->chromosome_id }}.pdf`);
            showToast('PDF exported successfully', 'success');

        } catch (error) {
            console.error('Error generating PDF:', error);
            showToast('Error generating PDF: ' + error.message, 'danger');
        } finally {
            exportBtn.innerHTML = originalText;
            exportBtn.disabled = false;
            hideLoadingOverlay();
        }
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 's':
                    e.preventDefault();
                    document.getElementById('saveChangesBtn').click();
                    break;
                case 'z':
                    e.preventDefault();
                    document.getElementById('undoPendingBtn').click();
                    break;
                case 'e':
                    e.preventDefault();
                    document.getElementById('exportPdfBtn').click();
                    break;
            }
        }
    });

    console.log('✅ Enhanced timetable viewer initialized with improved drag & drop');
});
</script>
@endpush
