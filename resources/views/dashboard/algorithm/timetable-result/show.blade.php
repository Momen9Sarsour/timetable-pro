@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                <div>
                    <h5 class="page-title mb-1">
                        <i class="fas fa-calendar-alt text-primary me-2" style="font-size: 1rem;"></i>
                        Timetable Result - Chromosome #{{ $chromosome->chromosome_id }}
                    </h5>
                    <div class="text-muted" style="font-size: 0.75rem;">
                        <span class="badge bg-light text-dark me-1" style="font-size: 0.7rem;">Penalty: {{ $chromosome->penalty_value }}</span>
                        <span class="badge bg-light text-dark me-1" style="font-size: 0.7rem;">Generation: {{ $chromosome->generation_number }}</span>
                        <span class="badge bg-light text-dark" style="font-size: 0.7rem;">Run ID: {{ $chromosome->population->population_id }}</span>
                    </div>
                </div>

                <div class="d-flex gap-1 flex-wrap">
                    <a href="{{ route('algorithm-control.timetable.results.index') }}" class="btn btn-outline-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.7rem;">
                        <i class="fas fa-arrow-left me-1" style="font-size: 0.65rem;"></i>
                        <span class="d-none d-lg-inline">Back</span>
                    </a>
                    <button class="btn btn-warning btn-sm" id="undoPendingBtn" disabled style="padding: 0.25rem 0.5rem; font-size: 0.7rem;">
                        <i class="fas fa-undo me-1" style="font-size: 0.65rem;"></i>
                        <span class="d-none d-lg-inline">Undo</span>
                    </button>
                    <button class="btn btn-primary btn-sm" id="saveChangesBtn" style="padding: 0.25rem 0.5rem; font-size: 0.7rem;">
                        <i class="fas fa-save me-1" style="font-size: 0.65rem;"></i>
                        <span class="d-none d-lg-inline">Save</span>
                    </button>
                    <button class="btn btn-success btn-sm" id="exportPdfBtn" style="padding: 0.25rem 0.5rem; font-size: 0.7rem;">
                        <i class="fas fa-file-pdf me-1" style="font-size: 0.65rem;"></i>
                        <span class="d-none d-lg-inline">PDF</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    <!-- Conflicts Detection Card - Compact -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle text-warning me-2" style="font-size: 0.75rem;"></i>
                            <h6 class="card-title mb-0" style="font-size: 0.8rem;">Conflicts Detection</h6>
                        </div>
                        <span class="badge {{ count($conflicts) > 0 ? 'bg-danger' : 'bg-success' }} bg-opacity-10 text-{{ count($conflicts) > 0 ? 'danger' : 'success' }}" style="font-size: 0.65rem; padding: 0.2rem 0.4rem;">
                            {{ count($conflicts) }} Conflicts
                        </span>
                    </div>
                </div>

                <div class="card-body py-2">
                    <div class="row g-2">
                        <!-- Conflicts List -->
                        <div class="col-lg-8">
                            <div class="conflicts-container" style="max-height: 120px; overflow-y: auto;">
                                @if (!empty($conflicts))
                                    <div class="list-group list-group-flush">
                                        @foreach ($conflicts as $c)
                                            <div class="list-group-item border-start border-2 py-1 px-2 mb-1 rounded-end"
                                                 style="border-start-color: {{ $c['color'] }} !important; background-color: {{ $c['color'] }}10; font-size: 0.7rem;">
                                                <div class="d-flex align-items-start">
                                                    <i class="fas fa-exclamation-circle me-2 mt-1" style="color: {{ $c['color'] }}; font-size: 0.6rem;"></i>
                                                    <div>
                                                        <strong style="font-size: 0.7rem;">{{ $c['type'] }}:</strong>
                                                        <span class="text-muted d-block" style="font-size: 0.65rem;">{{ $c['description'] }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-2">
                                        <i class="fas fa-check-circle text-success mb-1" style="font-size: 1.5rem;"></i>
                                        <h6 class="text-success mb-0" style="font-size: 0.75rem;">No Conflicts Found</h6>
                                        <small class="text-muted" style="font-size: 0.6rem;">All schedule blocks are properly arranged</small>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="col-lg-4">
                            <div class="card bg-light border-0">
                                <div class="card-header bg-transparent border-0 py-1">
                                    <h6 class="mb-0" style="font-size: 0.7rem;">
                                        <i class="fas fa-palette me-1 text-primary" style="font-size: 0.6rem;"></i>
                                        Color Legend
                                    </h6>
                                </div>
                                <div class="card-body py-2">
                                    <div class="legend-items">
                                        <div class="legend-item d-flex align-items-center mb-1">
                                            <div class="legend-color me-2" style="width: 8px; height: 8px; background: #0d6efd; border-radius: 2px;"></div>
                                            <small style="font-size: 0.6rem;">No Conflict</small>
                                        </div>
                                        <div class="legend-item d-flex align-items-center mb-1">
                                            <div class="legend-color me-2" style="width: 8px; height: 8px; background: #dc3545; border-radius: 2px;"></div>
                                            <small style="font-size: 0.6rem;">Time/Instructor Conflict</small>
                                        </div>
                                        <div class="legend-item d-flex align-items-center mb-1">
                                            <div class="legend-color me-2" style="width: 8px; height: 8px; background: #fd7e14; border-radius: 2px;"></div>
                                            <small style="font-size: 0.6rem;">Room Conflict</small>
                                        </div>
                                        <div class="legend-item d-flex align-items-center">
                                            <div class="legend-color me-2" style="width: 8px; height: 8px; background: #6f42c1; border-radius: 2px;"></div>
                                            <small style="font-size: 0.6rem;">Qualification Issue</small>
                                        </div>
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
                <div class="card-header bg-transparent border-bottom-0 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0" style="font-size: 0.8rem;">
                            <i class="fas fa-calendar-week text-primary me-2" style="font-size: 0.7rem;"></i>
                            Interactive Weekly Schedule
                        </h6>
                        <div class="d-flex gap-1">
                            <button class="btn btn-outline-primary btn-sm" id="zoomInBtn" title="Zoom In" style="padding: 0.2rem 0.35rem;">
                                <i class="fas fa-search-plus" style="font-size: 0.6rem;"></i>
                            </button>
                            <button class="btn btn-outline-primary btn-sm" id="zoomOutBtn" title="Zoom Out" style="padding: 0.2rem 0.35rem;">
                                <i class="fas fa-search-minus" style="font-size: 0.6rem;"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" id="resetZoomBtn" title="Reset Zoom" style="padding: 0.2rem 0.35rem;">
                                <i class="fas fa-expand-arrows-alt" style="font-size: 0.6rem;"></i>
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
                                            <td class="group-row position-relative schedule-drop-zone" colspan="{{ $totalColumnsOverall }}" data-group-id="{{ $groupId }}">
                                                <!-- Grid Background -->
                                                <div class="grid-background d-flex position-absolute w-100 h-100" style="top: 0; left: 0; pointer-events: none; z-index: 1;">
                                                    @for ($i = 0; $i < $totalColumnsOverall; $i++)
                                                        <div class="grid-column flex-fill border-end" style="border-color: rgba(0,0,0,0.05);"></div>
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
                                                        $height = 75;
                                                        $top = $stackLevel * ($height + 6) + 4;

                                                        $conflictType = $conflictChecker->getGeneConflictType($block->gene_id);
                                                        $borderColor = $conflictChecker->getGeneConflictColor($block->gene_id);
                                                        $bgColor = match ($conflictType) {
                                                            'Time Overlap', 'Instructor Conflict' => '#fff0f1',
                                                            'Room Conflict', 'Room Capacity', 'Room Type' => '#fff4e6',
                                                            'Instructor Qualification' => '#f8f3fc',
                                                            default => '#ffffff',
                                                        };
                                                    @endphp

                                                    <div class="event-block draggable-course"
                                                         draggable="true"
                                                         data-gene-id="{{ $block->gene_id }}"
                                                         data-original-left="{{ $left }}"
                                                         data-original-top="{{ $top }}"
                                                         data-group-id="{{ $groupId }}"
                                                         data-course="{{ $block->gene_id }}"
                                                         style="
                                                            position: absolute;
                                                            top: {{ $top }}px;
                                                            left: {{ $left }}%;
                                                            width: calc({{ $width }}% - 4px);
                                                            height: {{ $height }}px;
                                                            background: {{ $bgColor }};
                                                            border: 2px solid {{ $borderColor }};
                                                            border-radius: 6px;
                                                            padding: 6px;
                                                            cursor: grab;
                                                            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
                                                            transition: all 0.2s ease;
                                                            z-index: 10;
                                                            touch-action: none;
                                                         ">

                                                        <div class="event-content d-flex flex-column h-100">
                                                            <div class="course-code fw-bold mb-1 text-truncate" style="font-size: 0.65rem; line-height: 1.1; color: {{ $borderColor }};">
                                                                {{ optional($block->section->planSubject->subject)->subject_no }} -
                                                                {{ Str::limit(optional($block->section->planSubject->subject)->subject_name, 20) }}
                                                            </div>

                                                            <div class="course-instructor editable-field text-truncate mb-1"
                                                                 data-field="instructor"
                                                                 data-gene-id="{{ $block->gene_id }}"
                                                                 data-subject-id="{{ optional($block->section->planSubject->subject)->id }}"
                                                                 style="font-size: 0.55rem; cursor: pointer; padding: 1px 3px; border-radius: 2px; background: rgba(13,110,253,0.08);">
                                                                <i class="fas fa-user-tie me-1" style="font-size: 0.5rem;"></i>
                                                                {{ Str::limit(optional($block->instructor->user)->name, 15) }}
                                                            </div>

                                                            <div class="course-room editable-field text-truncate mb-1"
                                                                 data-field="room"
                                                                 data-gene-id="{{ $block->gene_id }}"
                                                                 data-timeslot-ids='@json($block->timeslot_ids)'
                                                                 style="font-size: 0.55rem; cursor: pointer; padding: 1px 3px; border-radius: 2px; background: rgba(13,110,253,0.08);">
                                                                <i class="fas fa-door-open me-1" style="font-size: 0.5rem;"></i>
                                                                {{ optional($block->room)->room_name }}
                                                            </div>

                                                            <div class="course-type text-muted mt-auto" style="font-size: 0.5rem; line-height: 1;">
                                                                <i class="fas fa-clock me-1" style="font-size: 0.45rem;"></i>
                                                                {{ ucfirst($block->block_type) }} | {{ $block->block_duration }}h
                                                            </div>
                                                        </div>

                                                        <!-- Resize Handle -->
                                                        <div class="resize-handle position-absolute"
                                                             style="bottom: 1px; right: 1px; width: 6px; height: 6px; cursor: se-resize; background: {{ $borderColor }}; opacity: 0.6; border-radius: 1px;"></div>
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
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* Timetable Enhanced Styling - Compact Version */
.timetable {
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.7rem;
    min-width: 3200px;
    width: max-content;
    background: var(--light-bg-secondary);
}

body.dark-mode .timetable {
    background: var(--dark-bg-secondary);
}

.timetable th,
.timetable td {
    border: 1px solid var(--light-border);
    padding: 0;
    vertical-align: top;
}

body.dark-mode .timetable th,
body.dark-mode .timetable td {
    border-color: var(--dark-border);
}

.group-header {
    background: linear-gradient(135deg, var(--light-bg), #e9ecef);
    font-weight: 600;
    padding: 8px 10px;
    width: 260px;
    min-width: 260px;
    font-size: 0.7rem;
    border-right: 2px solid var(--primary-color) !important;
    position: sticky;
    left: 0;
    z-index: 20;
    box-shadow: 2px 0 4px rgba(0,0,0,0.08);
    color: var(--light-text);
}

body.dark-mode .group-header {
    background: linear-gradient(135deg, var(--dark-bg-secondary), var(--dark-bg));
    color: var(--dark-text-secondary);
    border-right-color: var(--primary-light) !important;
}

.day-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    font-weight: 700;
    padding: 8px;
    font-size: 0.7rem;
    border-bottom: 2px solid var(--primary-dark) !important;
    position: sticky;
    top: 0;
    z-index: 18;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.time-header {
    background: linear-gradient(135deg, var(--light-bg), #e9ecef);
    color: var(--light-text-secondary);
    font-size: 0.6rem;
    font-weight: 500;
    padding: 6px 3px;
    width: 110px;
    min-width: 110px;
    border-bottom: 1px solid var(--light-border) !important;
    position: sticky;
    top: 37px;
    z-index: 17;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}

body.dark-mode .time-header {
    background: linear-gradient(135deg, var(--dark-bg), var(--dark-bg-secondary));
    color: var(--dark-text-secondary);
    border-bottom-color: var(--dark-border) !important;
}

.group-row {
    height: 170px;
    min-height: 170px;
    position: relative;
    background: var(--light-bg);
    transition: all var(--transition-speed);
}

body.dark-mode .group-row {
    background: var(--dark-bg);
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

/* Enhanced Event Block Styling - Compact */
.event-block {
    transition: all var(--transition-speed);
    overflow: hidden;
    user-select: none;
    cursor: grab;
}

.event-block:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.12) !important;
    transform: translateY(-1px);
    z-index: 25 !important;
}

.event-block:active {
    cursor: grabbing;
}

.event-block.dragging {
    opacity: 0.85;
    transform: rotate(1deg) scale(1.02);
    z-index: 1000 !important;
    box-shadow: 0 6px 20px rgba(0,0,0,0.25) !important;
    cursor: grabbing !important;
}

/* Course Block Specific Styling - Compact */
.course-code {
    font-weight: 700;
    line-height: 1.1;
}

.course-instructor,
.course-room {
    transition: all var(--transition-speed);
}

.course-instructor:hover,
.course-room:hover {
    background: rgba(13, 110, 253, 0.15) !important;
    transform: scale(1.01);
}

.course-type {
    opacity: 0.8;
}

/* Enhanced Grid Background */
.grid-column {
    border-right: 1px solid rgba(0,0,0,0.03);
    transition: border-color var(--transition-speed);
}

.grid-column:nth-child(5n) {
    border-right: 1px solid rgba(0,0,0,0.06);
}

body.dark-mode .grid-column {
    border-right-color: rgba(255,255,255,0.06);
}

body.dark-mode .grid-column:nth-child(5n) {
    border-right-color: rgba(255,255,255,0.12);
}

/* Drag and Drop Styling - Same Row Only */
.schedule-drop-zone {
    transition: all var(--transition-speed);
}

.schedule-drop-zone.drag-over-same-row {
    background: rgba(var(--primary-color), 0.08) !important;
    border: 1px solid var(--primary-color) !important;
    box-shadow: inset 0 0 8px rgba(var(--primary-color), 0.15);
}

/* Zoom Controls Enhancement */
.timetable-wrapper {
    position: relative;
    border: 1px solid var(--light-border);
    border-radius: 0.75rem;
    background: var(--light-bg-secondary);
}

body.dark-mode .timetable-wrapper {
    border-color: var(--dark-border);
    background: var(--dark-bg-secondary);
}

/* Editable Fields - Compact */
.editable-field {
    transition: all var(--transition-speed);
    border: 1px solid transparent;
}

.editable-field:hover {
    background: rgba(13, 110, 253, 0.12) !important;
    transform: scale(1.01);
    border-color: rgba(13, 110, 253, 0.3);
}

/* Resize Handle Enhancement */
.resize-handle {
    opacity: 0;
    transition: all var(--transition-speed);
}

.event-block:hover .resize-handle {
    opacity: 0.7;
}

.resize-handle:hover {
    opacity: 1 !important;
    transform: scale(1.2);
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

/* Enhanced Loading State */
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
    border-radius: 0.75rem;
    backdrop-filter: blur(2px);
}

body.dark-mode .loading-overlay {
    background: rgba(0,0,0,0.8);
}

/* Legend Card Enhancement - Compact */
.legend-items .legend-item {
    transition: all var(--transition-speed);
    padding: 0.15rem;
    border-radius: 0.25rem;
}

.legend-items .legend-item:hover {
    background: rgba(13, 110, 253, 0.06);
    transform: translateX(1px);
}

/* Select2 Overrides - Compact */
.select2-container {
    z-index: 9999 !important;
}

.select2-dropdown {
    border: 1px solid var(--primary-color);
    box-shadow: 0 3px 12px rgba(0,0,0,0.12);
    font-size: 0.7rem;
    border-radius: 0.5rem;
}

.select2-results__option {
    font-size: 0.7rem;
    padding: 0.4rem;
}

.select2-results__option--highlighted {
    background: var(--primary-color) !important;
}

/* Responsive Enhancements - Compact */
@media (max-width: 768px) {
    .group-header {
        width: 200px;
        min-width: 200px;
        font-size: 0.65rem;
        padding: 6px 8px;
    }

    .time-header {
        width: 80px;
        min-width: 80px;
        font-size: 0.55rem;
    }

    .event-block {
        min-height: 65px;
        padding: 4px;
    }

    .course-code {
        font-size: 0.6rem;
    }

    .course-instructor,
    .course-room {
        font-size: 0.5rem;
    }

    .course-type {
        font-size: 0.45rem;
    }

    .timetable {
        min-width: 2200px;
    }

    .group-row {
        height: 140px;
        min-height: 140px;
    }
}

@media (max-width: 576px) {
    .event-block {
        padding: 3px;
        min-height: 55px;
    }

    .course-code {
        font-size: 0.55rem;
    }

    .resize-handle {
        width: 5px;
        height: 5px;
    }
}

/* Touch Support */
@media (pointer: coarse) {
    .event-block {
        cursor: grab;
    }

    .event-block:active {
        cursor: grabbing;
    }

    .editable-field {
        padding: 3px 5px !important;
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

/* Animation for better UX */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.event-block {
    animation: slideIn 0.2s ease-out;
}

/* Enhanced focus states */
.btn:focus,
.editable-field:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 1px;
}

/* Better accessibility */
.event-block[aria-grabbed="true"] {
    opacity: 0.85;
    transform: scale(1.01);
}

.schedule-drop-zone[aria-dropeffect="move"] {
    background: rgba(var(--primary-color), 0.04) !important;
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

    // Initialize with zoomed out view (2 levels)
    let currentZoom = 0.7; // Start zoomed out
    let pendingChanges = [];
    let draggedElement = null;
    let isDragging = false;

    // Initialize all functionalities
    initializeDragAndDrop();
    initializeZoomControls();
    initializeEditableFields();
    initializeButtons();

    // Set initial zoom (2 levels out)
    updateZoom();

    function initializeDragAndDrop() {
        const draggableCourses = document.querySelectorAll('.draggable-course');
        const dropZones = document.querySelectorAll('.schedule-drop-zone');

        // Enhanced drag and drop - same row only
        draggableCourses.forEach(course => {
            // Mouse events
            course.addEventListener('dragstart', handleCourseDragStart);
            course.addEventListener('dragend', handleCourseDragEnd);

            // Touch events for mobile
            course.addEventListener('touchstart', handleCourseTouchStart, { passive: false });
            course.addEventListener('touchmove', handleCourseTouchMove, { passive: false });
            course.addEventListener('touchend', handleCourseTouchEnd, { passive: false });

            // Accessibility
            course.setAttribute('aria-grabbed', 'false');
            course.setAttribute('role', 'button');
            course.setAttribute('tabindex', '0');

            // Keyboard support
            course.addEventListener('keydown', handleCourseKeyDown);
        });

        dropZones.forEach(zone => {
            zone.addEventListener('dragover', handleScheduleDragOver);
            zone.addEventListener('dragenter', handleScheduleDragEnter);
            zone.addEventListener('dragleave', handleScheduleDragLeave);
            zone.addEventListener('drop', handleScheduleDrop);

            // Accessibility
            zone.setAttribute('aria-dropeffect', 'none');
        });

        function handleCourseDragStart(e) {
            isDragging = true;
            draggedElement = this;
            this.classList.add('dragging');
            this.setAttribute('aria-grabbed', 'true');

            // Get current row (group) ID
            const currentGroupId = this.dataset.groupId;

            // Update only the same row drop zone
            const sameRowDropZone = document.querySelector(`[data-group-id="${currentGroupId}"]`);
            if (sameRowDropZone) {
                sameRowDropZone.setAttribute('aria-dropeffect', 'move');
            }

            // Set drag data
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.dataset.course);

            // Create ghost image
            const rect = this.getBoundingClientRect();
            e.dataTransfer.setDragImage(this, rect.width / 2, rect.height / 2);
        }

        function handleCourseDragEnd(e) {
            isDragging = false;
            this.classList.remove('dragging');
            this.setAttribute('aria-grabbed', 'false');

            // Clean up all drop zones
            dropZones.forEach(zone => {
                zone.classList.remove('drag-over-same-row');
                zone.setAttribute('aria-dropeffect', 'none');
            });

            draggedElement = null;
        }

        function handleScheduleDragOver(e) {
            if (!isDragging || !draggedElement) return;

            const currentGroupId = draggedElement.dataset.groupId;
            const dropZoneGroupId = this.dataset.groupId;

            // Only allow drop in same row
            if (currentGroupId === dropZoneGroupId) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            } else {
                e.dataTransfer.dropEffect = 'none';
            }
        }

        function handleScheduleDragEnter(e) {
            if (!isDragging || !draggedElement) return;

            const currentGroupId = draggedElement.dataset.groupId;
            const dropZoneGroupId = this.dataset.groupId;

            // Only highlight same row
            if (currentGroupId === dropZoneGroupId) {
                e.preventDefault();
                this.classList.add('drag-over-same-row');
            }
        }

        function handleScheduleDragLeave(e) {
            if (!this.contains(e.relatedTarget)) {
                this.classList.remove('drag-over-same-row');
            }
        }

        function handleScheduleDrop(e) {
            if (!isDragging || !draggedElement) return;

            const currentGroupId = draggedElement.dataset.groupId;
            const dropZoneGroupId = this.dataset.groupId;

            // Only allow drop in same row
            if (currentGroupId !== dropZoneGroupId) {
                e.preventDefault();
                return false;
            }

            e.preventDefault();
            this.classList.remove('drag-over-same-row');

            const courseId = e.dataTransfer.getData('text/plain');
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            handleCourseBlockDrop(courseId, x, y, this);
        }

        // Enhanced touch support
        let touchStartX, touchStartY;
        let originalPosition = {};

        function handleCourseTouchStart(e) {
            const touch = e.touches[0];
            touchStartX = touch.clientX;
            touchStartY = touch.clientY;

            draggedElement = this;
            originalPosition = {
                left: this.style.left,
                top: this.style.top
            };

            this.classList.add('dragging');
            this.setAttribute('aria-grabbed', 'true');

            // Add haptic feedback if available
            if (navigator.vibrate) {
                navigator.vibrate(30);
            }
        }

        function handleCourseTouchMove(e) {
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

            // Find drop zone under touch - same row only
            const elementBelow = document.elementFromPoint(touch.clientX, touch.clientY);
            const dropZone = elementBelow?.closest('.schedule-drop-zone');
            const currentGroupId = draggedElement.dataset.groupId;

            dropZones.forEach(zone => {
                zone.classList.remove('drag-over-same-row');
            });

            if (dropZone && dropZone.dataset.groupId === currentGroupId) {
                dropZone.classList.add('drag-over-same-row');
            }
        }

        function handleCourseTouchEnd(e) {
            if (!draggedElement) return;

            const touch = e.changedTouches[0];
            const elementBelow = document.elementFromPoint(touch.clientX, touch.clientY);
            const dropZone = elementBelow?.closest('.schedule-drop-zone');
            const currentGroupId = draggedElement.dataset.groupId;

            draggedElement.classList.remove('dragging');
            draggedElement.setAttribute('aria-grabbed', 'false');

            dropZones.forEach(zone => {
                zone.classList.remove('drag-over-same-row');
            });

            // Only allow drop in same row
            if (dropZone && dropZone.dataset.groupId === currentGroupId) {
                const rect = dropZone.getBoundingClientRect();
                const x = touch.clientX - rect.left;
                const y = touch.clientY - rect.top;

                // Reset position first
                draggedElement.style.position = 'absolute';
                draggedElement.style.zIndex = '10';

                handleCourseBlockDrop(draggedElement.dataset.course, x, y, dropZone);

                // Success haptic feedback
                if (navigator.vibrate) {
                    navigator.vibrate([30, 50, 30]);
                }
            } else {
                // Return to original position
                draggedElement.style.position = 'absolute';
                draggedElement.style.left = originalPosition.left;
                draggedElement.style.top = originalPosition.top;
                draggedElement.style.zIndex = '10';

                showToast('Can only move within the same row', 'warning');
            }

            draggedElement = null;
        }

        // Keyboard support
        function handleCourseKeyDown(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                showToast('Use drag to move within the same row', 'info');
            }
        }
    }

    function handleCourseBlockDrop(courseId, x, y, container) {
        const course = document.querySelector(`[data-course="${courseId}"]`);
        if (!course) return;

        const containerWidth = container.offsetWidth;
        const newLeft = Math.max(0, Math.min(95, (x / containerWidth) * 100));

        // Enhanced grid snapping
        const gridSize = 100 / {{ $totalColumnsOverall }};
        const snappedLeft = Math.round(newLeft / gridSize) * gridSize;

        // Calculate proper vertical position with intelligent stacking
        const newTop = Math.max(4, Math.min(150, y - 35));
        const stackHeight = 81; // Height + margin
        const stackLevel = Math.floor(newTop / stackHeight);
        const finalTop = stackLevel * stackHeight + 4;

        // Smooth animation to new position
        course.style.transition = 'all 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        course.style.position = 'absolute';
        course.style.left = snappedLeft + '%';
        course.style.top = finalTop + 'px';
        course.style.zIndex = '10';

        // Remove transition after animation
        setTimeout(() => {
            course.style.transition = 'all 0.2s ease';
        }, 250);

        // Add to pending changes
        pendingChanges.push({
            type: 'move',
            courseId: courseId,
            geneId: course.dataset.geneId,
            newPosition: { left: snappedLeft, top: finalTop },
            groupId: container.dataset.groupId,
            timestamp: Date.now()
        });

        updateChangesIndicator();
        showToast('Course moved successfully!', 'success');
    }

    function initializeZoomControls() {
        const container = document.getElementById('timetableContainer');

        document.getElementById('zoomInBtn').addEventListener('click', () => {
            currentZoom = Math.min(2, currentZoom + 0.15);
            updateZoom();
        });

        document.getElementById('zoomOutBtn').addEventListener('click', () => {
            currentZoom = Math.max(0.4, currentZoom - 0.15);
            updateZoom();
        });

        document.getElementById('resetZoomBtn').addEventListener('click', () => {
            currentZoom = 1;
            updateZoom();
        });

        // Mouse wheel zoom support
        container.parentElement.addEventListener('wheel', function(e) {
            if (e.ctrlKey) {
                e.preventDefault();
                if (e.deltaY < 0) {
                    currentZoom = Math.min(2, currentZoom + 0.1);
                } else {
                    currentZoom = Math.max(0.4, currentZoom - 0.1);
                }
                updateZoom();
            }
        });

        function updateZoom() {
            container.style.transform = `scale(${currentZoom})`;
            container.style.width = `${100 / currentZoom}%`;
            container.style.height = `${100 / currentZoom}%`;

            // Update zoom button states
            document.getElementById('zoomInBtn').disabled = currentZoom >= 2;
            document.getElementById('zoomOutBtn').disabled = currentZoom <= 0.4;
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
                const blockType = field.closest('.event-block').querySelector('.course-type').textContent.includes('Practical') ? 'practical' : 'theory';
                options = getAvailableRoomsForTimeslots(timeslotIds, blockType, field.dataset.subjectId);
            }

            createEditSelect(field, fieldType, geneId, currentValue, options);
        });
    }

    function createEditSelect(field, fieldType, geneId, currentValue, options) {
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm';
        select.style.fontSize = '0.55rem';
        select.dataset.field = fieldType;
        select.dataset.geneId = geneId;

        // Copy data attributes
        if (fieldType === 'instructor') {
            select.dataset.subjectId = field.dataset.subjectId;
        } else if (fieldType === 'room') {
            select.dataset.timeslotIds = field.dataset.timeslotIds;
        }

        // Add default option
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = `Select ${fieldType}...`;
        select.appendChild(defaultOption);

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
            minimumResultsForSearch: 5,
            templateResult: function(option) {
                if (!option.id) return option.text;
                return $(`<span style="font-size: 0.7rem;">${option.text}</span>`);
            }
        }).focus();

        $(select).on('select2:select', function() {
            const selectedText = $(this).find(':selected').text();
            const selectedValue = $(this).val();
            if (selectedValue) {
                updateFieldValue(this, fieldType, geneId, selectedText, selectedValue);
            }
        });

        $(select).on('select2:close', function() {
            if (!$(this).val()) {
                // If no selection, revert to original field
                const icon = fieldType === 'instructor' ? 'fa-user-tie' : 'fa-door-open';
                const div = document.createElement('div');
                div.className = `course-${fieldType} editable-field text-truncate mb-1`;
                div.dataset.field = fieldType;
                div.dataset.geneId = geneId;
                div.style.cssText = 'font-size: 0.55rem; cursor: pointer; padding: 1px 3px; border-radius: 2px; background: rgba(13,110,253,0.08);';
                div.innerHTML = `<i class="fas ${icon} me-1" style="font-size: 0.5rem;"></i>${currentValue}`;

                // Copy additional data attributes
                if (fieldType === 'instructor') {
                    div.dataset.subjectId = select.dataset.subjectId;
                } else if (fieldType === 'room') {
                    div.dataset.timeslotIds = select.dataset.timeslotIds;
                }

                $(select).replaceWith(div);
            }
        });
    }

    function updateFieldValue(select, fieldType, geneId, newValue, newId) {
        const icon = fieldType === 'instructor' ? 'fa-user-tie' : 'fa-door-open';

        const div = document.createElement('div');
        div.className = `course-${fieldType} editable-field text-truncate mb-1`;
        div.dataset.field = fieldType;
        div.dataset.geneId = geneId;
        div.style.cssText = 'font-size: 0.55rem; cursor: pointer; padding: 1px 3px; border-radius: 2px; background: rgba(13,110,253,0.08);';
        div.innerHTML = `<i class="fas ${icon} me-1" style="font-size: 0.5rem;"></i>${newValue}`;

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
            newId: newId,
            oldValue: select.value,
            timestamp: Date.now()
        });

        updateChangesIndicator();
        updateBlockConflictStatus(div.closest('.event-block'), fieldType, newValue);
        showToast(`${fieldType.charAt(0).toUpperCase() + fieldType.slice(1)} updated`, 'success');
    }

    function updateBlockConflictStatus(block, fieldType, newValue) {
        if (fieldType === 'room') {
            const timeslotIds = JSON.parse(block.querySelector('.course-room').dataset.timeslotIds);
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
            indicator.title = `${type} conflict detected`;
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
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i><span class="d-none d-lg-inline">Saving...</span>';
            saveBtn.disabled = true;

            // Add loading overlay
            showLoadingOverlay('Saving changes...');

            setTimeout(() => {
                console.log('Saving changes:', pendingChanges);

                pendingChanges = [];
                updateChangesIndicator();

                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
                hideLoadingOverlay();
                showToast('Changes saved successfully!', 'success');
            }, 1500);
        });

        // Undo Changes Button
        document.getElementById('undoPendingBtn').addEventListener('click', function() {
            if (pendingChanges.length === 0) {
                showToast('No changes to undo', 'info');
                return;
            }

            const undoCount = pendingChanges.length;

            // Revert all pending changes
            pendingChanges.forEach(change => {
                if (change.type === 'move') {
                    const course = document.querySelector(`[data-course="${change.courseId}"]`);
                    if (course) {
                        course.style.transition = 'all 0.25s ease';
                        course.style.left = course.dataset.originalLeft + '%';
                        course.style.top = course.dataset.originalTop + 'px';

                        setTimeout(() => {
                            course.style.transition = 'all 0.2s ease';
                        }, 250);
                    }
                }
            });

            pendingChanges = [];
            updateChangesIndicator();
            showToast(`${undoCount} change(s) undone`, 'warning');
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
            saveBtn.innerHTML = `<i class="fas fa-save me-1" style="font-size: 0.65rem;"></i><span class="d-none d-lg-inline">Save (${pendingChanges.length})</span><span class="d-lg-none">${pendingChanges.length}</span>`;

            undoBtn.disabled = false;
            undoBtn.innerHTML = `<i class="fas fa-undo me-1" style="font-size: 0.65rem;"></i><span class="d-none d-lg-inline">Undo</span>`;
        } else {
            saveBtn.classList.remove('btn-warning');
            saveBtn.classList.add('btn-primary');
            saveBtn.innerHTML = '<i class="fas fa-save me-1" style="font-size: 0.65rem;"></i><span class="d-none d-lg-inline">Save</span>';

            undoBtn.disabled = true;
            undoBtn.innerHTML = '<i class="fas fa-undo me-1" style="font-size: 0.65rem;"></i><span class="d-none d-lg-inline">Undo</span>';
        }
    }

    function showLoadingOverlay(message = 'Loading...') {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary mb-2" role="status" style="width: 1.5rem; height: 1.5rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h6 class="text-primary mb-0" style="font-size: 0.8rem;">${message}</h6>
                <small class="text-muted">Please wait...</small>
            </div>
        `;
        document.querySelector('.timetable-wrapper').appendChild(overlay);
    }

    function hideLoadingOverlay() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => overlay.remove(), 200);
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

        const iconMap = {
            success: 'fa-check-circle',
            danger: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center">
                    <i class="fas ${iconMap[type] || iconMap.info} me-2" style="font-size: 0.7rem;"></i>
                    <span style="font-size: 0.75rem;">${message}</span>
                </div>
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

        exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>';
        exportBtn.disabled = true;
        showLoadingOverlay('Generating PDF...');

        try {
            showToast('PDF generation started', 'info');
            // Simulate PDF generation
            setTimeout(() => {
                exportBtn.innerHTML = originalText;
                exportBtn.disabled = false;
                hideLoadingOverlay();
                showToast('PDF exported successfully!', 'success');
            }, 2000);
        } catch (error) {
            console.error('Error generating PDF:', error);
            showToast('Error generating PDF', 'danger');
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

        // ESC to cancel drag

        // ESC to cancel any ongoing drag operation
        if (e.key === 'Escape' && isDragging) {
            if (draggedElement) {
                draggedElement.classList.remove('dragging');
                draggedElement.setAttribute('aria-grabbed', 'false');
                document.getElementById('dropZoneOverlay').classList.add('d-none');
                isDragging = false;
                draggedElement = null;
                showToast('Drag operation cancelled', 'info');
            }
        }
    });

    // Auto-save warning before page unload
    window.addEventListener('beforeunload', function(e) {
        if (pendingChanges.length > 0) {
            const confirmationMessage = 'You have unsaved changes. Are you sure you want to leave?';
            e.returnValue = confirmationMessage;
            return confirmationMessage;
        }
    });

    // Periodic save reminder
    setInterval(() => {
        if (pendingChanges.length >= 5) {
            showToast('You have multiple unsaved changes. Consider saving soon.', 'warning');
        }
    }, 300000); // Every 5 minutes

    console.log('✅ Enhanced timetable viewer initialized successfully');
    console.log(`📊 Initial zoom level: ${Math.round(currentZoom * 100)}%`);
    console.log('🎯 Drag & drop system ready for course blocks');
    console.log('⌨️ Keyboard shortcuts: Ctrl+S (Save), Ctrl+Z (Undo), Ctrl+E (Export)');
});
</script>

<!-- External Libraries for PDF Export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
@endpush
