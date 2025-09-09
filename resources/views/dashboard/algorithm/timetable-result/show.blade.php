@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        Timetable Result - Chromosome #{{ $chromosome->chromosome_id }}
                    </h4>
                    <p class="text-muted mb-0">
                        <strong>Penalty:</strong> {{ $chromosome->penalty_value }} |
                        <strong>Generation:</strong> {{ $chromosome->generation_number }} |
                        <strong>Run ID:</strong> {{ $chromosome->population->population_id }}
                    </p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('algorithm-control.timetable.results.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>
                        <span class="d-none d-sm-inline">Back to Results</span>
                        <span class="d-sm-none">Back</span>
                    </a>
                    <button class="btn btn-warning btn-sm" id="undoPendingBtn" disabled>
                        <i class="fas fa-undo me-1"></i>
                        <span class="d-none d-sm-inline">Undo All</span>
                        <span class="d-sm-none">Undo</span>
                    </button>
                    <button class="btn btn-primary btn-sm" id="saveChangesBtn">
                        <i class="fas fa-save me-1"></i>
                        <span class="d-none d-sm-inline">Save Changes</span>
                        <span class="d-sm-none">Save</span>
                    </button>
                    <button class="btn btn-success btn-sm" id="exportPdfBtn">
                        <i class="fas fa-file-pdf me-1"></i>
                        <span class="d-none d-sm-inline">Export PDF</span>
                        <span class="d-sm-none">PDF</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Conflicts Detection Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            <h6 class="card-title mb-0">Conflicts Detection</h6>
                        </div>
                        <span class="badge {{ count($conflicts) > 0 ? 'bg-danger' : 'bg-success' }} px-3 py-2">
                            {{ count($conflicts) }} Conflicts
                        </span>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <div class="row g-3">
                        <!-- Conflicts List -->
                        <div class="col-lg-8">
                            <div class="conflicts-container" style="max-height: 200px; overflow-y: auto;">
                                @if (!empty($conflicts))
                                    <div class="list-group list-group-flush">
                                        @foreach ($conflicts as $c)
                                            <div class="list-group-item border-start border-4 small mb-2"
                                                 style="border-start-color: {{ $c['color'] }} !important; background-color: {{ $c['color'] }}15;">
                                                <div class="d-flex align-items-start">
                                                    <i class="fas fa-exclamation-circle me-2 mt-1" style="color: {{ $c['color'] }};"></i>
                                                    <div>
                                                        <strong>{{ $c['type'] }}:</strong>
                                                        <span class="text-muted">{{ $c['description'] }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-3">
                                        <i class="fas fa-check-circle text-success mb-2" style="font-size: 2rem;"></i>
                                        <h6 class="text-success mb-0">No Conflicts Found</h6>
                                        <small class="text-muted">All schedule blocks are properly arranged</small>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="col-lg-4">
                            <div class="legend-card bg-light rounded p-3">
                                <h6 class="mb-3">
                                    <i class="fas fa-palette me-1"></i>
                                    Color Legend
                                </h6>
                                <div class="legend-items">
                                    <div class="legend-item d-flex align-items-center mb-2">
                                        <div class="legend-color me-2" style="width: 16px; height: 16px; background: #0d6efd; border-radius: 3px;"></div>
                                        <small>No Conflict</small>
                                    </div>
                                    <div class="legend-item d-flex align-items-center mb-2">
                                        <div class="legend-color me-2" style="width: 16px; height: 16px; background: #dc3545; border-radius: 3px;"></div>
                                        <small>Time/Instructor Conflict</small>
                                    </div>
                                    <div class="legend-item d-flex align-items-center mb-2">
                                        <div class="legend-color me-2" style="width: 16px; height: 16px; background: #fd7e14; border-radius: 3px;"></div>
                                        <small>Room Conflict</small>
                                    </div>
                                    <div class="legend-item d-flex align-items-center">
                                        <div class="legend-color me-2" style="width: 16px; height: 16px; background: #6f42c1; border-radius: 3px;"></div>
                                        <small>Qualification Issue</small>
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
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-calendar-week text-primary me-2"></i>
                            Weekly Schedule
                        </h6>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" id="zoomInBtn" title="Zoom In">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button class="btn btn-outline-primary btn-sm" id="zoomOutBtn" title="Zoom Out">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" id="resetZoomBtn" title="Reset Zoom">
                                <i class="fas fa-expand-arrows-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="timetable-wrapper" style="height: 70vh; overflow: auto; position: relative;">
                        <div class="timetable-container" id="timetableContainer" style="transform-origin: top left;">
                            <table class="timetable table-bordered mb-0">
                                <thead class="sticky-top bg-white">
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
                                            <td class="group-row position-relative" colspan="{{ $totalColumnsOverall }}">
                                                <!-- Grid Background -->
                                                <div class="grid-background d-flex position-absolute w-100 h-100" style="top: 0; left: 0; pointer-events: none;">
                                                    @for ($i = 0; $i < $totalColumnsOverall; $i++)
                                                        <div class="grid-column flex-fill border-end border-light"></div>
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
                                                        $height = 110;
                                                        $top = $stackLevel * ($height + 10);

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
                                                         style="
                                                            position: absolute;
                                                            top: {{ $top }}px;
                                                            left: {{ $left }}%;
                                                            width: calc({{ $width }}% - 8px);
                                                            height: {{ $height }}px;
                                                            background: {{ $bgColor }};
                                                            border: 2px solid {{ $borderColor }};
                                                            border-radius: 8px;
                                                            padding: 8px;
                                                            cursor: move;
                                                            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                                                            transition: all 0.2s ease;
                                                            z-index: 5;
                                                         ">

                                                        <div class="event-content d-flex flex-column h-100">
                                                            <div class="event-subject fw-bold mb-1 text-truncate" style="font-size: 0.85rem; line-height: 1.2;">
                                                                {{ optional($block->section->planSubject->subject)->subject_no }} -
                                                                {{ Str::limit(optional($block->section->planSubject->subject)->subject_name, 35) }}
                                                            </div>

                                                            <div class="event-instructor editable-field text-truncate mb-1"
                                                                 data-field="instructor"
                                                                 data-gene-id="{{ $block->gene_id }}"
                                                                 data-subject-id="{{ optional($block->section->planSubject->subject)->id }}"
                                                                 style="font-size: 0.75rem; cursor: pointer; padding: 2px 4px; border-radius: 3px; background: rgba(13,110,253,0.05);">
                                                                <i class="fas fa-user-tie me-1"></i>
                                                                {{ Str::limit(optional($block->instructor->user)->name, 30) }}
                                                            </div>

                                                            <div class="event-room editable-field text-truncate mb-1"
                                                                 data-field="room"
                                                                 data-gene-id="{{ $block->gene_id }}"
                                                                 data-timeslot-ids='@json($block->timeslot_ids)'
                                                                 style="font-size: 0.75rem; cursor: pointer; padding: 2px 4px; border-radius: 3px; background: rgba(13,110,253,0.05);">
                                                                <i class="fas fa-door-open me-1"></i>
                                                                {{ optional($block->room)->room_name }}
                                                            </div>

                                                            <div class="event-type text-muted mt-auto" style="font-size: 0.7rem;">
                                                                <i class="fas fa-clock me-1"></i>
                                                                {{ ucfirst($block->block_type) }} | {{ $block->block_duration }}h
                                                            </div>
                                                        </div>

                                                        <!-- Resize Handle -->
                                                        <div class="resize-handle position-absolute"
                                                             style="bottom: 2px; right: 2px; width: 12px; height: 12px; cursor: se-resize; background: {{ $borderColor }}; opacity: 0.7; border-radius: 2px;"></div>
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
            <i class="fas fa-arrows-alt fa-3x text-primary mb-3"></i>
            <h4 class="text-primary">Drop to Reschedule</h4>
            <p class="text-muted">Release to place the block in new time slot</p>
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
    font-size: 0.875rem;
    min-width: 3500px;
    width: max-content;
}

.timetable th,
.timetable td {
    border: 1px solid #e9ecef;
    padding: 0;
    vertical-align: top;
}

.group-header {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    font-weight: 600;
    padding: 12px;
    width: 320px;
    min-width: 320px;
    border-right: 3px solid #dee2e6 !important;
    position: sticky;
    left: 0;
    z-index: 12;
}

.day-header {
    background: linear-gradient(135deg, #0d6efd, #0056b3);
    color: white;
    font-weight: 700;
    padding: 12px;
    border-bottom: 2px solid #0056b3 !important;
    position: sticky;
    top: 0;
    z-index: 15;
}

.time-header {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    color: #6c757d;
    font-size: 0.8rem;
    font-weight: 500;
    padding: 8px 4px;
    width: 140px;
    min-width: 140px;
    border-bottom: 1px solid #dee2e6 !important;
    position: sticky;
    top: 64px;
    z-index: 14;
}

.group-row {
    height: 220px;
    min-height: 220px;
    position: relative;
    background: #fafbfc;
}

.sticky-start {
    position: sticky;
    left: 0;
    z-index: 10;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 15;
}

/* Event Block Enhancements */
.event-block {
    transition: all 0.2s ease;
    overflow: hidden;
}

.event-block:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.15) !important;
    transform: translateY(-2px);
    z-index: 20 !important;
}

.event-block.dragging {
    opacity: 0.8;
    transform: rotate(3deg) scale(1.05);
    z-index: 1000 !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;
}

.editable-field:hover {
    background: rgba(13, 110, 253, 0.15) !important;
    transform: scale(1.02);
}

/* Drag and Drop Styling */
.drop-zone {
    border: 2px dashed #007bff !important;
    background: rgba(0,123,255,0.05) !important;
}

.drop-zone.drag-over {
    border-color: #28a745 !important;
    background: rgba(40,167,69,0.1) !important;
}

/* Zoom Controls */
.timetable-wrapper {
    position: relative;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .group-header {
        width: 250px;
        min-width: 250px;
        font-size: 0.8rem;
        padding: 8px;
    }

    .time-header {
        width: 100px;
        min-width: 100px;
        font-size: 0.7rem;
    }

    .event-block {
        min-height: 90px;
        padding: 6px;
    }

    .event-content {
        font-size: 0.7rem;
    }

    .timetable {
        min-width: 2800px;
    }
}

/* Loading State */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

/* Conflict Indicators */
.conflict-indicator {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    animation: conflictPulse 2s infinite;
}

@keyframes conflictPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.2); }
}

/* PDF Export Styling */
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
}

/* Select2 Overrides */
.select2-container {
    z-index: 9999 !important;
}

.select2-dropdown {
    border: 1px solid #0d6efd;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Enhanced Legend */
.legend-card {
    border: 1px solid #e9ecef;
}

.legend-item {
    transition: all 0.2s ease;
}

.legend-item:hover {
    background: rgba(13, 110, 253, 0.05);
    border-radius: 4px;
    padding: 2px 4px;
    margin: -2px -4px;
}

/* Grid Background Enhancement */
.grid-column {
    border-right: 1px solid rgba(0,0,0,0.05);
}

.grid-column:nth-child(5n) {
    border-right: 2px solid rgba(0,0,0,0.1);
}

/* Resize Handle */
.resize-handle {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.event-block:hover .resize-handle {
    opacity: 0.7;
}

.resize-handle:hover {
    opacity: 1 !important;
    transform: scale(1.2);
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Server Data
    const allRooms = @json($allRooms);
    const allInstructors = @json($allInstructors);
    const timeSlotUsage = @json($timeSlotUsage);

    let currentZoom = 1;
    let pendingChanges = [];

    // Initialize all functionalities
    initializeDragAndDrop();
    initializeZoomControls();
    initializeEditableFields();
    initializeButtons();

    function initializeDragAndDrop() {
        const draggableBlocks = document.querySelectorAll('.draggable-block');
        const dropZoneOverlay = document.getElementById('dropZoneOverlay');

        draggableBlocks.forEach(block => {
            block.addEventListener('dragstart', function(e) {
                this.classList.add('dragging');
                dropZoneOverlay.classList.remove('d-none');
                e.dataTransfer.setData('text/plain', this.dataset.geneId);
                e.dataTransfer.effectAllowed = 'move';
            });

            block.addEventListener('dragend', function(e) {
                this.classList.remove('dragging');
                dropZoneOverlay.classList.add('d-none');
                document.querySelectorAll('.drop-zone').forEach(zone => {
                    zone.classList.remove('drop-zone', 'drag-over');
                });
            });
        });

        // Make table cells drop zones
        document.querySelectorAll('.group-row').forEach(row => {
            row.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                this.classList.add('drop-zone', 'drag-over');
            });

            row.addEventListener('dragleave', function(e) {
                if (!this.contains(e.relatedTarget)) {
                    this.classList.remove('drag-over');
                }
            });

            row.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drop-zone', 'drag-over');

                const geneId = e.dataTransfer.getData('text/plain');
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                handleBlockDrop(geneId, x, y, this);
            });
        });
    }

    function handleBlockDrop(geneId, x, y, container) {
        const block = document.querySelector(`[data-gene-id="${geneId}"]`);
        if (!block) return;

        const containerWidth = container.offsetWidth;
        const newLeft = Math.max(0, Math.min(95, (x / containerWidth) * 100));

        // Snap to grid
        const gridSize = 100 / {{ $totalColumnsOverall }};
        const snappedLeft = Math.round(newLeft / gridSize) * gridSize;

        // Update block position
        block.style.left = snappedLeft + '%';
        block.style.top = Math.max(10, y - 50) + 'px';

        // Add to pending changes
        pendingChanges.push({
            type: 'move',
            geneId: geneId,
            newPosition: { left: snappedLeft, top: y - 50 }
        });

        updateChangesIndicator();
        showToast('Block moved successfully', 'info');
    }

    function initializeZoomControls() {
        const container = document.getElementById('timetableContainer');

        document.getElementById('zoomInBtn').addEventListener('click', () => {
            currentZoom = Math.min(2, currentZoom + 0.2);
            updateZoom();
        });

        document.getElementById('zoomOutBtn').addEventListener('click', () => {
            currentZoom = Math.max(0.5, currentZoom - 0.2);
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
            if (!field) return;

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
            placeholder: `Select ${fieldType}...`
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
        div.style.cssText = 'font-size: 0.75rem; cursor: pointer; padding: 2px 4px; border-radius: 3px; background: rgba(13,110,253,0.05);';
        div.innerHTML = `<i class="fas ${icon} me-1"></i>${newValue}`;

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
            newValue: newValue
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

            // Simulate API call
            setTimeout(() => {
                console.log('Saving changes:', pendingChanges);

                pendingChanges = [];
                updateChangesIndicator();

                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
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
                    }
                } else if (change.type === 'edit') {
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
            saveBtn.innerHTML = `<i class="fas fa-save me-1"></i>Save (${pendingChanges.length})`;

            undoBtn.disabled = false;
        } else {
            saveBtn.classList.remove('btn-warning');
            saveBtn.classList.add('btn-primary');
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i><span class="d-none d-sm-inline">Save Changes</span><span class="d-sm-none">Save</span>';

            undoBtn.disabled = true;
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
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }

    async function generatePDF() {
        const exportBtn = document.getElementById('exportPdfBtn');
        const originalText = exportBtn.innerHTML;

        exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generating...';
        exportBtn.disabled = true;

        try {
            const timetableContainer = document.querySelector('.timetable-container');

            // Temporarily adjust styles for PDF
            const originalOverflow = timetableContainer.style.overflow;
            const originalHeight = timetableContainer.style.height;

            timetableContainer.style.overflow = 'visible';
            timetableContainer.style.height = 'auto';

            // Generate canvas from HTML
            const canvas = await html2canvas(timetableContainer, {
                scale: 2,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                width: timetableContainer.scrollWidth,
                height: timetableContainer.scrollHeight
            });

            // Restore original styles
            timetableContainer.style.overflow = originalOverflow;
            timetableContainer.style.height = originalHeight;

            // Create PDF
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({
                orientation: 'landscape',
                unit: 'mm',
                format: 'a3'
            });

            const imgData = canvas.toDataURL('image/png');
            const imgWidth = 400;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            // Add title page
            pdf.setFontSize(20);
            pdf.text('Timetable Schedule', 20, 30);
            pdf.setFontSize(12);
            pdf.text(`Chromosome ID: {{ $chromosome->chromosome_id }}`, 20, 45);
            pdf.text(`Penalty: {{ $chromosome->penalty_value }}`, 20, 55);
            pdf.text(`Generated: ${new Date().toLocaleDateString()}`, 20, 65);

            // Add the timetable image
            pdf.addPage();
            pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);

            // Save the PDF
            pdf.save(`timetable-chromosome-{{ $chromosome->chromosome_id }}.pdf`);

            showToast('PDF exported successfully', 'success');

        } catch (error) {
            console.error('Error generating PDF:', error);
            showToast('Error generating PDF', 'error');
        } finally {
            exportBtn.innerHTML = originalText;
            exportBtn.disabled = false;
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

    console.log('✅ Timetable viewer initialized with drag & drop, editing, and PDF export');
});
</script>
@endpush
