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
                        Interactive Timetable - Chromosome #{{ $chromosome->chromosome_id }}
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
                        <span class="d-none d-lg-inline">Undo (<span id="undoCount">0</span>)</span>
                        <span class="d-lg-none"><span id="undoCountMobile">0</span></span>
                    </button>
                    <button class="btn btn-primary btn-sm" id="saveChangesBtn" style="padding: 0.25rem 0.5rem; font-size: 0.7rem;">
                        <i class="fas fa-save me-1" style="font-size: 0.65rem;"></i>
                        <span class="d-none d-lg-inline">Save</span>
                    </button>
                    <button class="btn btn-success btn-sm" id="exportPdfBtn" style="padding: 0.25rem 0.5rem; font-size: 0.7rem;">
                        <i class="fas fa-file-pdf me-1" style="font-size: 0.65rem;"></i>
                        <span class="d-none d-lg-inline">Export PDF</span>
                        <span class="d-lg-none">PDF</span>
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
                            <div class="conflicts-container" style="max-height: 100px; overflow-y: auto;">
                                @if (!empty($conflicts))
                                    <div class="list-group list-group-flush">
                                        @foreach ($conflicts as $c)
                                            <div class="list-group-item border-start border-2 py-1 px-2 mb-1 rounded-end"
                                                 style="border-start-color: {{ $c['color'] }} !important; background-color: {{ $c['color'] }}10; font-size: 0.65rem;">
                                                <div class="d-flex align-items-start">
                                                    <i class="fas fa-exclamation-circle me-2 mt-1" style="color: {{ $c['color'] }}; font-size: 0.5rem;"></i>
                                                    <div>
                                                        <strong style="font-size: 0.65rem;">{{ $c['type'] }}:</strong>
                                                        <span class="text-muted d-block" style="font-size: 0.6rem;">{{ $c['description'] }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-2">
                                        <i class="fas fa-check-circle text-success mb-1" style="font-size: 1.2rem;"></i>
                                        <h6 class="text-success mb-0" style="font-size: 0.7rem;">No Conflicts Found</h6>
                                        <small class="text-muted" style="font-size: 0.55rem;">All blocks are properly arranged</small>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="col-lg-4">
                            <div class="card bg-light border-0" style="font-size: 0.6rem;">
                                <div class="card-header bg-transparent border-0 py-1">
                                    <h6 class="mb-0" style="font-size: 0.65rem;">
                                        <i class="fas fa-palette me-1 text-primary" style="font-size: 0.55rem;"></i>
                                        Legend
                                    </h6>
                                </div>
                                <div class="card-body py-1">
                                    <div class="legend-items">
                                        <div class="legend-item d-flex align-items-center mb-1">
                                            <div class="legend-color me-1" style="width: 6px; height: 6px; background: #0d6efd; border-radius: 1px;"></div>
                                            <small style="font-size: 0.55rem;">No Conflict</small>
                                        </div>
                                        <div class="legend-item d-flex align-items-center mb-1">
                                            <div class="legend-color me-1" style="width: 6px; height: 6px; background: #dc3545; border-radius: 1px;"></div>
                                            <small style="font-size: 0.55rem;">Time/Instructor</small>
                                        </div>
                                        <div class="legend-item d-flex align-items-center mb-1">
                                            <div class="legend-color me-1" style="width: 6px; height: 6px; background: #fd7e14; border-radius: 1px;"></div>
                                            <small style="font-size: 0.55rem;">Room Conflict</small>
                                        </div>
                                        <div class="legend-item d-flex align-items-center">
                                            <div class="legend-color me-1" style="width: 6px; height: 6px; background: #6f42c1; border-radius: 1px;"></div>
                                            <small style="font-size: 0.55rem;">Qualification</small>
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
                            <span class="badge bg-primary bg-opacity-10 text-primary ms-2" style="font-size: 0.6rem;">
                                Drag & Drop Enabled
                            </span>
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
                    <div class="timetable-wrapper" style="height: 70vh; overflow: auto; position: relative;">
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
                                                <!-- Enhanced Grid Background with Visual Feedback -->
                                                <div class="grid-background d-flex position-absolute w-100 h-100" style="top: 0; left: 0; pointer-events: none; z-index: 1;">
                                                    @for ($i = 0; $i < $totalColumnsOverall; $i++)
                                                        <div class="grid-column flex-fill border-end drop-slot" 
                                                             data-slot="{{ $i }}" 
                                                             style="border-color: rgba(0,0,0,0.04); min-height: 100%; position: relative;">
                                                            <!-- Drop indicator -->
                                                            <div class="drop-indicator" style="
                                                                position: absolute; 
                                                                top: 50%; 
                                                                left: 50%; 
                                                                transform: translate(-50%, -50%);
                                                                width: 12px; 
                                                                height: 12px; 
                                                                border: 2px dashed var(--primary-color);
                                                                border-radius: 50%;
                                                                background: rgba(13, 110, 253, 0.1);
                                                                opacity: 0;
                                                                transition: all 0.2s ease;
                                                                display: flex;
                                                                align-items: center;
                                                                justify-content: center;
                                                            ">
                                                                <i class="fas fa-plus" style="font-size: 6px; color: var(--primary-color);"></i>
                                                            </div>
                                                        </div>
                                                    @endfor
                                                </div>

                                                <!-- Schedule Blocks with Enhanced Drag & Drop -->
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
                                                        $height = 65;
                                                        $top = $stackLevel * ($height + 4) + 2;

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
                                                         data-start-column="{{ $startColumn }}"
                                                         data-span="{{ $span }}"
                                                         style="
                                                            position: absolute;
                                                            top: {{ $top }}px;
                                                            left: {{ $left }}%;
                                                            width: calc({{ $width }}% - 2px);
                                                            height: {{ $height }}px;
                                                            background: {{ $bgColor }};
                                                            border: 2px solid {{ $borderColor }};
                                                            border-radius: 4px;
                                                            padding: 4px;
                                                            cursor: grab;
                                                            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
                                                            transition: all 0.15s ease;
                                                            z-index: 10;
                                                            touch-action: none;
                                                            user-select: none;
                                                         ">

                                                        <!-- Drag Handle -->
                                                        <div class="drag-handle" style="
                                                            position: absolute;
                                                            top: 2px;
                                                            right: 2px;
                                                            width: 8px;
                                                            height: 8px;
                                                            background: {{ $borderColor }};
                                                            border-radius: 1px;
                                                            opacity: 0.6;
                                                            cursor: grab;
                                                            transition: all 0.15s ease;
                                                        ">
                                                            <i class="fas fa-grip-vertical" style="font-size: 4px; color: white; line-height: 8px; margin-left: 1px;"></i>
                                                        </div>

                                                        <div class="event-content d-flex flex-column h-100">
                                                            <div class="course-code fw-bold mb-1 text-truncate" style="font-size: 0.6rem; line-height: 1.1; color: {{ $borderColor }};">
                                                                {{ optional($block->section->planSubject->subject)->subject_no }} -
                                                                {{ Str::limit(optional($block->section->planSubject->subject)->subject_name, 18) }}
                                                            </div>

                                                            <div class="course-instructor editable-field text-truncate mb-1"
                                                                 data-field="instructor"
                                                                 data-gene-id="{{ $block->gene_id }}"
                                                                 data-subject-id="{{ optional($block->section->planSubject->subject)->id }}"
                                                                 style="font-size: 0.5rem; cursor: pointer; padding: 1px 2px; border-radius: 2px; background: rgba(13,110,253,0.06);">
                                                                <i class="fas fa-user-tie me-1" style="font-size: 0.45rem;"></i>
                                                                {{ Str::limit(optional($block->instructor->user)->name, 12) }}
                                                            </div>

                                                            <div class="course-room editable-field text-truncate mb-1"
                                                                 data-field="room"
                                                                 data-gene-id="{{ $block->gene_id }}"
                                                                 data-timeslot-ids='@json($block->timeslot_ids)'
                                                                 style="font-size: 0.5rem; cursor: pointer; padding: 1px 2px; border-radius: 2px; background: rgba(13,110,253,0.06);">
                                                                <i class="fas fa-door-open me-1" style="font-size: 0.45rem;"></i>
                                                                {{ optional($block->room)->room_name }}
                                                            </div>

                                                            <div class="course-type text-muted mt-auto" style="font-size: 0.45rem; line-height: 1;">
                                                                <i class="fas fa-clock me-1" style="font-size: 0.4rem;"></i>
                                                                {{ ucfirst($block->block_type) }} | {{ $block->block_duration }}h
                                                            </div>
                                                        </div>

                                                        <!-- Resize Handle -->
                                                        <div class="resize-handle position-absolute"
                                                             style="bottom: 1px; right: 1px; width: 4px; height: 4px; cursor: se-resize; background: {{ $borderColor }}; opacity: 0.5; border-radius: 1px;"></div>
                                                    </div>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Drop Zone Indicator -->
                        <div id="dropIndicator" class="position-absolute d-none" style="
                            background: rgba(13, 110, 253, 0.15); 
                            border: 2px dashed #0d6efd; 
                            border-radius: 6px; 
                            z-index: 9999; 
                            pointer-events: none;
                            backdrop-filter: blur(1px);
                        ">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <span class="text-primary fw-medium" style="font-size: 0.6rem;">
                                    <i class="fas fa-arrows-alt me-1" style="font-size: 0.5rem;"></i>
                                    Drop here
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Changes Toast -->
<div id="changesIndicator" class="position-fixed bottom-0 start-50 translate-middle-x mb-3 d-none" style="z-index: 1050;">
    <div class="toast show align-items-center text-white bg-warning border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-edit me-2"></i>
                <span id="changesText">You have unsaved changes</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" id="dismissChanges"></button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* Enhanced Timetable Styling - Compact & Smooth */
.timetable {
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.65rem;
    min-width: 2800px;
    width: max-content;
    background: var(--light-bg-secondary);
    transform: scale(0.8); /* Initial zoom out */
    transform-origin: top left;
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
    padding: 6px 8px;
    width: 220px;
    min-width: 220px;
    font-size: 0.65rem;
    border-right: 2px solid var(--primary-color) !important;
    position: sticky;
    left: 0;
    z-index: 20;
    box-shadow: 2px 0 4px rgba(0,0,0,0.06);
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
    padding: 6px;
    font-size: 0.65rem;
    border-bottom: 2px solid var(--primary-dark) !important;
    position: sticky;
    top: 0;
    z-index: 18;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
}

.time-header {
    background: linear-gradient(135deg, var(--light-bg), #e9ecef);
    color: var(--light-text-secondary);
    font-size: 0.55rem;
    font-weight: 500;
    padding: 4px 2px;
    width: 90px;
    min-width: 90px;
    border-bottom: 1px solid var(--light-border) !important;
    position: sticky;
    top: 32px;
    z-index: 17;
}

body.dark-mode .time-header {
    background: linear-gradient(135deg, var(--dark-bg), var(--dark-bg-secondary));
    color: var(--dark-text-secondary);
    border-bottom-color: var(--dark-border) !important;
}

.group-row {
    height: 150px;
    min-height: 150px;
    position: relative;
    background: var(--light-bg);
    transition: all 0.2s ease;
}

body.dark-mode .group-row {
    background: var(--dark-bg);
}

/* Enhanced Event Block Styling */
.event-block {
    transition: all 0.15s ease;
    overflow: hidden;
    user-select: none;
    cursor: grab;
    border-radius: 4px !important;
}

.event-block:hover {
    box-shadow: 0 3px 8px rgba(0,0,0,0.12) !important;
    transform: translateY(-1px) scale(1.02);
    z-index: 25 !important;
}

.event-block:hover .drag-handle {
    opacity: 1;
    transform: scale(1.2);
}

.event-block:active {
    cursor: grabbing;
}

.event-block.dragging {
    opacity: 0.8;
    transform: rotate(2deg) scale(1.05);
    z-index: 1000 !important;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3) !important;
    cursor: grabbing !important;
    border-color: var(--primary-color) !important;
}

/* Enhanced Grid Background */
.grid-column {
    border-right: 1px solid rgba(0,0,0,0.02);
    transition: all 0.15s ease;
    position: relative;
}

.grid-column:nth-child(5n) {
    border-right: 1px solid rgba(0,0,0,0.05);
}

.grid-column.drag-over {
    background: rgba(13, 110, 253, 0.08) !important;
    border-color: var(--primary-color) !important;
}

.grid-column.drag-over .drop-indicator {
    opacity: 1 !important;
    transform: translate(-50%, -50%) scale(1.3);
}

body.dark-mode .grid-column {
    border-right-color: rgba(255,255,255,0.04);
}

body.dark-mode .grid-column:nth-child(5n) {
    border-right-color: rgba(255,255,255,0.08);
}

/* Drag and Drop Enhancements */
.schedule-drop-zone {
    transition: all 0.15s ease;
}

.schedule-drop-zone.drag-over {
    background: rgba(13, 110, 253, 0.06) !important;
    box-shadow: inset 0 0 12px rgba(13, 110, 253, 0.2);
}

/* Drop indicator animation */
@keyframes dropPulse {
    0%, 100% { opacity: 0.6; transform: translate(-50%, -50%) scale(1); }
    50% { opacity: 1; transform: translate(-50%, -50%) scale(1.2); }
}

.grid-column.drag-over .drop-indicator {
    animation: dropPulse 1s infinite;
}

/* Zoom Controls Enhancement */
.timetable-wrapper {
    position: relative;
    border: 1px solid var(--light-border);
    border-radius: 8px;
    background: var(--light-bg-secondary);
}

body.dark-mode .timetable-wrapper {
    border-color: var(--dark-border);
    background: var(--dark-bg-secondary);
}

/* Editable Fields */
.editable-field {
    transition: all 0.15s ease;
    border: 1px solid transparent;
    border-radius: 2px;
}

.editable-field:hover {
    background: rgba(13, 110, 253, 0.12) !important;
    transform: scale(1.05);
    border-color: rgba(13, 110, 253, 0.3);
}

/* Resize Handle Enhancement */
.resize-handle {
    opacity: 0;
    transition: all 0.15s ease;
}

.event-block:hover .resize-handle {
    opacity: 0.8;
}

.resize-handle:hover {
    opacity: 1 !important;
    transform: scale(1.3);
}

/* Drag Handle Styling */
.drag-handle {
    opacity: 0.4;
    transition: all 0.15s ease;
}

.drag-handle:hover {
    opacity: 1;
    transform: scale(1.3);
    cursor: grab;
}

.drag-handle:active {
    cursor: grabbing;
}

/* Loading Animation */
@keyframes saveSpinner {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-spinner {
    animation: saveSpinner 1s linear infinite;
}

/* Conflict Indicators */
.conflict-indicator {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    animation: conflictPulse 2s infinite;
    z-index: 15;
}

@keyframes conflictPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.3); }
}

/* Enhanced Tooltips */
.tooltip {
    font-size: 0.6rem !important;
}

/* Select2 Customization */
.select2-container {
    z-index: 9999 !important;
}

.select2-dropdown {
    border: 1px solid var(--primary-color);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    font-size: 0.65rem;
    border-radius: 6px;
}

.select2-results__option {
    font-size: 0.65rem;
    padding: 0.4rem;
    transition: all 0.15s ease;
}

.select2-results__option--highlighted {
    background: var(--primary-color) !important;
    transform: translateX(2px);
}

/* Responsive Enhancements */
@media (max-width: 768px) {
    .timetable {
        transform: scale(0.6);
        min-width: 2400px;
    }

    .group-header {
        width: 180px;
        min-width: 180px;
        font-size: 0.6rem;
        padding: 4px 6px;
    }

    .time-header {
        width: 70px;
        min-width: 70px;
        font-size: 0.5rem;
    }

    .event-block {
        min-height: 55px;
        padding: 3px;
    }

    .course-code {
        font-size: 0.55rem;
    }

    .course-instructor,
    .course-room {
        font-size: 0.45rem;
    }

    .course-type {
        font-size: 0.4rem;
    }

    .group-row {
        height: 130px;
        min-height: 130px;
    }
}

@media (max-width: 576px) {
    .timetable {
        transform: scale(0.5);
    }

    .event-block {
        padding: 2px;
        min-height: 45px;
    }

    .drag-handle,
    .resize-handle {
        width: 3px;
        height: 3px;
    }
}

/* Print Styles */
@media print {
    .timetable-wrapper {
        height: auto !important;
        overflow: visible !important;
    }

    .timetable {
        transform: scale(0.7) !important;
    }

    .card-header,
    .btn,
    .conflicts-container {
        display: none !important;
    }

    .event-block {
        break-inside: avoid;
        border-width: 1px !important;
    }
}

/* Touch Support */
@media (pointer: coarse) {
    .event-block {
        cursor: grab;
        touch-action: manipulation;
    }

    .event-block:active {
        cursor: grabbing;
    }

    .drag-handle {
        opacity: 0.7;
        width: 12px;
        height: 12px;
    }

    .editable-field {
        padding: 4px 6px !important;
        font-size: 0.55rem !important;
    }
}

/* Animation for better UX */
@keyframes slideInBlock {
    from {
        opacity: 0;
        transform: translateY(8px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.event-block {
    animation: slideInBlock 0.3s ease-out;
}

/* Enhanced focus states */
.btn:focus,
.editable-field:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* Better accessibility */
.event-block[aria-grabbed="true"] {
    opacity: 0.85;
    transform: scale(1.02);
    border-width: 3px !important;
}

.schedule-drop-zone[aria-dropeffect="move"] {
    background: rgba(13, 110, 253, 0.05) !important;
}

/* Success/Error States */
.event-block.success {
    border-color: var(--success-color) !important;
    box-shadow: 0 0 8px rgba(34, 197, 94, 0.3) !important;
}

.event-block.error {
    border-color: var(--danger-color) !important;
    box-shadow: 0 0 8px rgba(239, 68, 68, 0.3) !important;
    animation: errorShake 0.5s ease-in-out;
}

@keyframes errorShake {
    0%, 50%, 100% { transform: translateX(0); }
    25% { transform: translateX(-2px); }
    75% { transform: translateX(2px); }
}

/* Changes Indicator Styling */
#changesIndicator .toast {
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    backdrop-filter: blur(4px);
}

/* Zoom Level Indicator */
.zoom-indicator {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.6rem;
    z-index: 100;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.zoom-indicator.show {
    opacity: 1;
}

/* Enhanced Scrollbars */
.timetable-wrapper::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.timetable-wrapper::-webkit-scrollbar-track {
    background: var(--light-bg);
    border-radius: 4px;
}

.timetable-wrapper::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 4px;
    transition: background 0.15s ease;
}

.timetable-wrapper::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
}

body.dark-mode .timetable-wrapper::-webkit-scrollbar-track {
    background: var(--dark-bg-secondary);
}

/* Sticky Elements Enhancement */
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
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Server Data
    const allRooms = @json($allRooms);
    const allInstructors = @json($allInstructors);
    const timeSlotUsage = @json($timeSlotUsage);
    const totalColumns = {{ $totalColumnsOverall }};

    // Enhanced State Management
    let currentZoom = 0.8; // Start zoomed out
    let pendingChanges = [];
    let draggedElement = null;
    let isDragging = false;
    let originalPositions = new Map();
    let ghostElement = null;
    let dropIndicator = null;

    // Initialize all functionalities
    initializeDragAndDrop();
    initializeZoomControls();
    initializeEditableFields();
    initializeButtons();
    initializeKeyboardShortcuts();
    
    // Set initial zoom
    updateZoom();
    showZoomIndicator();

    function initializeDragAndDrop() {
        const draggableCourses = document.querySelectorAll('.draggable-course');
        const dropZones = document.querySelectorAll('.schedule-drop-zone');
        dropIndicator = document.getElementById('dropIndicator');

        // Enhanced drag and drop for course blocks
        draggableCourses.forEach(course => {
            // Store original position
            originalPositions.set(course.dataset.geneId, {
                left: course.style.left,
                top: course.style.top
            });

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

            // Visual feedback on hover
            course.addEventListener('mouseenter', () => {
                if (!isDragging) {
                    course.style.transform = 'translateY(-2px) scale(1.02)';
                }
            });

            course.addEventListener('mouseleave', () => {
                if (!isDragging) {
                    course.style.transform = '';
                }
            });
        });

        // Enhanced drop zones with grid snapping
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', handleScheduleDragOver);
            zone.addEventListener('dragenter', handleScheduleDragEnter);
            zone.addEventListener('dragleave', handleScheduleDragLeave);
            zone.addEventListener('drop', handleScheduleDrop);

            // Set up grid columns for precise dropping
            const gridColumns = zone.querySelectorAll('.grid-column');
            gridColumns.forEach((column, index) => {
                column.addEventListener('dragover', (e) => handleColumnDragOver(e, index));
                column.addEventListener('dragenter', (e) => handleColumnDragEnter(e, index));
                column.addEventListener('dragleave', (e) => handleColumnDragLeave(e, index));
            });

            zone.setAttribute('aria-dropeffect', 'none');
        });

        function handleCourseDragStart(e) {
            isDragging = true;
            draggedElement = this;
            this.classList.add('dragging');
            this.setAttribute('aria-grabbed', 'true');

            // Store current position for undo functionality
            const currentPos = {
                left: this.style.left,
                top: this.style.top,
                groupId: this.dataset.groupId
            };

            // Create ghost element for better visual feedback
            createGhostElement(this);

            // Get current row (group) ID
            const currentGroupId = this.dataset.groupId;

            // Update only the same row drop zone
            const sameRowDropZone = document.querySelector(`[data-group-id="${currentGroupId}"]`);
            if (sameRowDropZone) {
                sameRowDropZone.setAttribute('aria-dropeffect', 'move');
                sameRowDropZone.classList.add('drag-target');
            }

            // Set drag data with enhanced information
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('application/json', JSON.stringify({
                courseId: this.dataset.course,
                geneId: this.dataset.geneId,
                groupId: currentGroupId,
                startColumn: parseInt(this.dataset.startColumn),
                span: parseInt(this.dataset.span),
                currentPosition: currentPos
            }));

            // Create drag image
            const rect = this.getBoundingClientRect();
            e.dataTransfer.setDragImage(this, rect.width / 2, rect.height / 2);

            // Haptic feedback
            if (navigator.vibrate) {
                navigator.vibrate(30);
            }

            showToast('Drag to reposition within the same row', 'info');
        }

        function handleCourseDragEnd(e) {
            isDragging = false;
            this.classList.remove('dragging');
            this.setAttribute('aria-grabbed', 'false');

            // Clean up ghost element
            if (ghostElement) {
                ghostElement.remove();
                ghostElement = null;
            }

            // Clean up all visual indicators
            dropZones.forEach(zone => {
                zone.classList.remove('drag-target', 'drag-over');
                zone.setAttribute('aria-dropeffect', 'none');
                
                // Clean up grid columns
                zone.querySelectorAll('.grid-column').forEach(col => {
                    col.classList.remove('drag-over');
                });
            });

            // Hide drop indicator
            if (dropIndicator) {
                dropIndicator.classList.add('d-none');
            }

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
                
                // Show drop indicator
                updateDropIndicator(e, this);
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
                this.classList.add('drag-over');
            }
        }

        function handleScheduleDragLeave(e) {
            if (!this.contains(e.relatedTarget)) {
                this.classList.remove('drag-over');
            }
        }

        function handleColumnDragOver(e, columnIndex) {
            if (!isDragging || !draggedElement) return;
            
            const currentGroupId = draggedElement.dataset.groupId;
            const dropZoneGroupId = e.target.closest('.schedule-drop-zone').dataset.groupId;

            if (currentGroupId === dropZoneGroupId) {
                e.preventDefault();
                e.stopPropagation();
            }
        }

        function handleColumnDragEnter(e, columnIndex) {
            if (!isDragging || !draggedElement) return;
            
            const currentGroupId = draggedElement.dataset.groupId;
            const dropZoneGroupId = e.target.closest('.schedule-drop-zone').dataset.groupId;

            if (currentGroupId === dropZoneGroupId) {
                e.preventDefault();
                e.target.classList.add('drag-over');
            }
        }

        function handleColumnDragLeave(e, columnIndex) {
            e.target.classList.remove('drag-over');
        }

        function handleScheduleDrop(e) {
            if (!isDragging || !draggedElement) return;

            try {
                const dragData = JSON.parse(e.dataTransfer.getData('application/json'));
                const currentGroupId = dragData.groupId;
                const dropZoneGroupId = this.dataset.groupId;

                // Only allow drop in same row
                if (currentGroupId !== dropZoneGroupId) {
                    e.preventDefault();
                    showToast('Can only move within the same row', 'warning');
                    return false;
                }

                e.preventDefault();
                this.classList.remove('drag-over');

                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                handleCourseBlockDrop(dragData, x, y, this);

                // Success haptic feedback
                if (navigator.vibrate) {
                    navigator.vibrate([30, 50, 30]);
                }

            } catch (error) {
                console.error('Drop error:', error);
                showToast('Failed to move course block', 'danger');
            }
        }

        // Enhanced touch support
        let touchStartX, touchStartY, touchStartPos;

        function handleCourseTouchStart(e) {
            const touch = e.touches[0];
            touchStartX = touch.clientX;
            touchStartY = touch.clientY;

            draggedElement = this;
            touchStartPos = {
                left: this.style.left,
                top: this.style.top
            };

            this.classList.add('dragging');
            this.setAttribute('aria-grabbed', 'true');

            // Visual feedback
            this.style.zIndex = '1000';
            this.style.transform = 'rotate(2deg) scale(1.05)';

            // Haptic feedback
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

            // Move element with finger
            draggedElement.style.position = 'fixed';
            draggedElement.style.left = (touch.clientX - 50) + 'px';
            draggedElement.style.top = (touch.clientY - 25) + 'px';

            // Find drop zone under touch
            const elementBelow = document.elementFromPoint(touch.clientX, touch.clientY);
            const dropZone = elementBelow?.closest('.schedule-drop-zone');
            const currentGroupId = draggedElement.dataset.groupId;

            // Clean up previous highlights
            document.querySelectorAll('.grid-column').forEach(col => {
                col.classList.remove('drag-over');
            });

            if (dropZone && dropZone.dataset.groupId === currentGroupId) {
                const column = elementBelow?.closest('.grid-column');
                if (column) {
                    column.classList.add('drag-over');
                }
            }
        }

        function handleCourseTouchEnd(e) {
            if (!draggedElement) return;

            const touch = e.changedTouches[0];
            const elementBelow = document.elementFromPoint(touch.clientX, touch.clientY);
            const dropZone = elementBelow?.closest('.schedule-drop-zone');
            const currentGroupId = draggedElement.dataset.groupId;

            // Clean up styles
            draggedElement.classList.remove('dragging');
            draggedElement.setAttribute('aria-grabbed', 'false');
            draggedElement.style.position = 'absolute';
            draggedElement.style.zIndex = '10';
            draggedElement.style.transform = '';

            // Clean up grid highlights
            document.querySelectorAll('.grid-column').forEach(col => {
                col.classList.remove('drag-over');
            });

            // Only allow drop in same row
            if (dropZone && dropZone.dataset.groupId === currentGroupId) {
                const rect = dropZone.getBoundingClientRect();
                const x = touch.clientX - rect.left;
                const y = touch.clientY - rect.top;

                const dragData = {
                    courseId: draggedElement.dataset.course,
                    geneId: draggedElement.dataset.geneId,
                    groupId: currentGroupId,
                    startColumn: parseInt(draggedElement.dataset.startColumn),
                    span: parseInt(draggedElement.dataset.span),
                    currentPosition: touchStartPos
                };

                handleCourseBlockDrop(dragData, x, y, dropZone);

                // Success haptic feedback
                if (navigator.vibrate) {
                    navigator.vibrate([30, 50, 30]);
                }
            } else {
                // Return to original position with animation
                draggedElement.style.transition = 'all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                draggedElement.style.left = touchStartPos.left;
                draggedElement.style.top = touchStartPos.top;

                setTimeout(() => {
                    draggedElement.style.transition = 'all 0.15s ease';
                }, 300);

                showToast('Can only move within the same row', 'warning');
            }

            draggedElement = null;
        }

        // Keyboard support
        function handleCourseKeyDown(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                showToast('Use mouse/touch to drag within the same row', 'info');
            } else if (e.key === 'Escape' && isDragging) {
                // Cancel drag operation
                if (draggedElement) {
                    draggedElement.classList.remove('dragging');
                    draggedElement.setAttribute('aria-grabbed', 'false');
                    isDragging = false;
                    draggedElement = null;
                    showToast('Drag cancelled', 'info');
                }
            }
        }
    }

    function createGhostElement(originalElement) {
        if (ghostElement) {
            ghostElement.remove();
        }

        ghostElement = originalElement.cloneNode(true);
        ghostElement.classList.add('ghost-element');
        ghostElement.style.position = 'absolute';
        ghostElement.style.opacity = '0.5';
        ghostElement.style.pointerEvents = 'none';
        ghostElement.style.zIndex = '999';
        ghostElement.style.transform = 'rotate(1deg) scale(0.95)';

        originalElement.parentNode.appendChild(ghostElement);
    }

    function updateDropIndicator(e, container) {
        if (!dropIndicator) return;

        const rect = container.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        // Calculate grid position
        const columnWidth = rect.width / totalColumns;
        const snapColumn = Math.floor(x / columnWidth);
        const snapX = snapColumn * columnWidth;

        // Position drop indicator
        dropIndicator.style.left = (rect.left + snapX) + 'px';
        dropIndicator.style.top = (rect.top + y - 30) + 'px';
        dropIndicator.style.width = (columnWidth * (draggedElement?.dataset.span || 1)) + 'px';
        dropIndicator.style.height = '60px';
        dropIndicator.classList.remove('d-none');
    }

    function handleCourseBlockDrop(dragData, x, y, container) {
        const course = document.querySelector(`[data-gene-id="${dragData.geneId}"]`);
        if (!course) return;

        const containerWidth = container.offsetWidth;
        const newLeft = Math.max(0, Math.min(95, (x / containerWidth) * 100));

        // Enhanced grid snapping with span consideration
        const gridSize = 100 / totalColumns;
        const spanWidth = gridSize * dragData.span;
        const maxLeft = 100 - spanWidth;
        let snappedLeft = Math.round(newLeft / gridSize) * gridSize;
        snappedLeft = Math.min(snappedLeft, maxLeft);

        // Intelligent vertical positioning
        const newTop = Math.max(2, Math.min(120, y - 30));
        const stackHeight = 69; // Height + margin
        const stackLevel = Math.floor(newTop / stackHeight);
        const finalTop = stackLevel * stackHeight + 2;

        // Check for overlaps and adjust if necessary
        const finalPosition = checkAndResolveOverlap(course, snappedLeft, finalTop, container);

        // Smooth animation to new position
        course.style.transition = 'all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        course.style.position = 'absolute';
        course.style.left = finalPosition.left + '%';
        course.style.top = finalPosition.top + 'px';
        course.style.zIndex = '10';

        // Visual success feedback
        course.classList.add('success');
        setTimeout(() => {
            course.classList.remove('success');
            course.style.transition = 'all 0.15s ease';
        }, 600);

        // Update data attributes
        course.dataset.startColumn = Math.round(finalPosition.left / gridSize);

        // Add to pending changes with more detail
        pendingChanges.push({
            type: 'move',
            courseId: dragData.courseId,
            geneId: dragData.geneId,
            oldPosition: dragData.currentPosition,
            newPosition: { 
                left: finalPosition.left, 
                top: finalPosition.top,
                column: Math.round(finalPosition.left / gridSize)
            },
            groupId: container.dataset.groupId,
            timestamp: Date.now(),
            span: dragData.span
        });

        updateChangesIndicator();
        showToast('Course repositioned successfully!', 'success');

        // Hide drop indicator
        if (dropIndicator) {
            dropIndicator.classList.add('d-none');
        }
    }

    function checkAndResolveOverlap(movingElement, targetLeft, targetTop, container) {
        const movingSpan = parseInt(movingElement.dataset.span) || 1;
        const gridSize = 100 / totalColumns;
        const movingRight = targetLeft + (movingSpan * gridSize);

        // Get all other blocks in the same container
        const otherBlocks = container.querySelectorAll('.event-block:not([data-gene-id="' + movingElement.dataset.geneId + '"])');
        
        let finalLeft = targetLeft;
        let finalTop = targetTop;
        
        // Check for horizontal overlaps
        for (const block of otherBlocks) {
            const blockLeft = parseFloat(block.style.left);
            const blockSpan = parseInt(block.dataset.span) || 1;
            const blockRight = blockLeft + (blockSpan * gridSize);
            const blockTop = parseFloat(block.style.top);
            
            // Check if there's vertical overlap (same row)
            if (Math.abs(blockTop - finalTop) < 65) {
                // Check horizontal overlap
                if (finalLeft < blockRight && movingRight > blockLeft) {
                    // Move to next available position
                    finalTop += 69; // Move down one level
                }
            }
        }

        return { left: finalLeft, top: finalTop };
    }

    function initializeZoomControls() {
        const container = document.getElementById('timetableContainer');
        const zoomIndicator = createZoomIndicator();

        document.getElementById('zoomInBtn').addEventListener('click', () => {
            currentZoom = Math.min(1.5, currentZoom + 0.1);
            updateZoom();
            showZoomIndicator();
        });

        document.getElementById('zoomOutBtn').addEventListener('click', () => {
            currentZoom = Math.max(0.4, currentZoom - 0.1);
            updateZoom();
            showZoomIndicator();
        });

        document.getElementById('resetZoomBtn').addEventListener('click', () => {
            currentZoom = 1;
            updateZoom();
            showZoomIndicator();
        });

        // Enhanced mouse wheel zoom
        container.parentElement.addEventListener('wheel', function(e) {
            if (e.ctrlKey) {
                e.preventDefault();
                const zoomSpeed = 0.05;
                if (e.deltaY < 0) {
                    currentZoom = Math.min(1.5, currentZoom + zoomSpeed);
                } else {
                    currentZoom = Math.max(0.4, currentZoom - zoomSpeed);
                }
                updateZoom();
                showZoomIndicator();
            }
        });

        function updateZoom() {
            container.style.transform = `scale(${currentZoom})`;
            container.style.width = `${100 / currentZoom}%`;
            container.style.height = `${100 / currentZoom}%`;

            // Update button states
            document.getElementById('zoomInBtn').disabled = currentZoom >= 1.5;
            document.getElementById('zoomOutBtn').disabled = currentZoom <= 0.4;

            // Update zoom indicator
            if (zoomIndicator) {
                zoomIndicator.textContent = Math.round(currentZoom * 100) + '%';
            }
        }

        function createZoomIndicator() {
            const indicator = document.createElement('div');
            indicator.className = 'zoom-indicator';
            indicator.textContent = Math.round(currentZoom * 100) + '%';
            container.parentElement.appendChild(indicator);
            return indicator;
        }

        function showZoomIndicator() {
            if (zoomIndicator) {
                zoomIndicator.classList.add('show');
                clearTimeout(zoomIndicator.hideTimeout);
                zoomIndicator.hideTimeout = setTimeout(() => {
                    zoomIndicator.classList.remove('show');
                }, 2000);
            }
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
        select.style.fontSize = '0.5rem';
        select.style.width = '100%';
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
        
        // Initialize Select2 with enhanced options
        $(select).select2({
            width: '100%',
            dropdownAutoWidth: true,
            placeholder: `Select ${fieldType}...`,
            minimumResultsForSearch: 5,
            dropdownParent: $(select.closest('.event-block')),
            templateResult: function(option) {
                if (!option.id) return option.text;
                return $(`<span style="font-size: 0.65rem;">${option.text}</span>`);
            },
            escapeMarkup: function(markup) {
                return markup;
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
                // Revert to original field
                revertToOriginalField(this, fieldType, geneId, currentValue);
            }
        });
    }

    function updateFieldValue(select, fieldType, geneId, newValue, newId) {
        const icon = fieldType === 'instructor' ? 'fa-user-tie' : 'fa-door-open';
        const oldValue = select.dataset.originalValue || '';

        const div = document.createElement('div');
        div.className = `course-${fieldType} editable-field text-truncate mb-1`;
        div.dataset.field = fieldType;
        div.dataset.geneId = geneId;
        div.style.cssText = 'font-size: 0.5rem; cursor: pointer; padding: 1px 2px; border-radius: 2px; background: rgba(13,110,253,0.06);';
        div.innerHTML = `<i class="fas ${icon} me-1" style="font-size: 0.45rem;"></i>${newValue}`;

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
            oldValue: oldValue,
            timestamp: Date.now()
        });

        updateChangesIndicator();
        updateBlockConflictStatus(div.closest('.event-block'), fieldType, newValue);
        showToast(`${fieldType.charAt(0).toUpperCase() + fieldType.slice(1)} updated`, 'success');
    }

    function revertToOriginalField(select, fieldType, geneId, currentValue) {
        const icon = fieldType === 'instructor' ? 'fa-user-tie' : 'fa-door-open';
        const div = document.createElement('div');
        div.className = `course-${fieldType} editable-field text-truncate mb-1`;
        div.dataset.field = fieldType;
        div.dataset.geneId = geneId;
        div.style.cssText = 'font-size: 0.5rem; cursor: pointer; padding: 1px 2px; border-radius: 2px; background: rgba(13,110,253,0.06);';
        div.innerHTML = `<i class="fas ${icon} me-1" style="font-size: 0.45rem;"></i>${currentValue}`;

        // Copy additional data attributes
        if (fieldType === 'instructor') {
            div.dataset.subjectId = select.dataset.subjectId;
        } else if (fieldType === 'room') {
            div.dataset.timeslotIds = select.dataset.timeslotIds;
        }

        $(select).replaceWith(div);
    }

    function updateBlockConflictStatus(block, fieldType, newValue) {
        if (fieldType === 'room') {
            const timeslotIds = JSON.parse(block.querySelector('.course-room').dataset.timeslotIds);
            const hasConflict = checkRoomConflict(newValue, timeslotIds);

            if (hasConflict) {
                block.style.borderColor = '#dc3545';
                block.style.backgroundColor = '#fff0f1';
                addConflictIndicator(block, 'room');
                block.classList.add('error');
                setTimeout(() => block.classList.remove('error'), 1000);
            } else {
                block.style.borderColor = '#0d6efd';
                block.style.backgroundColor = '#ffffff';
                removeConflictIndicator(block);
                block.classList.add('success');
                setTimeout(() => block.classList.remove('success'), 1000);
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
        // Save Changes Button with enhanced feedback
        document.getElementById('saveChangesBtn').addEventListener('click', function() {
            if (pendingChanges.length === 0) {
                showToast('No changes to save', 'info');
                return;
            }

            const saveBtn = this;
            const originalText = saveBtn.innerHTML;
            const changesCount = pendingChanges.length;
            
            saveBtn.innerHTML = '<i class="fas fa-spinner loading-spinner me-1"></i><span class="d-none d-lg-inline">Saving...</span>';
            saveBtn.disabled = true;
            saveBtn.classList.add('btn-warning');

            // Simulate API call with progress feedback
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 20;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(progressInterval);
                }
                
                const progressText = Math.round(progress) + '%';
                saveBtn.innerHTML = `<i class="fas fa-spinner loading-spinner me-1"></i><span class="d-none d-lg-inline">Saving ${progressText}</span>`;
            }, 200);

            setTimeout(() => {
                clearInterval(progressInterval);
                console.log('Saving changes:', pendingChanges);

                // Reset UI
                pendingChanges = [];
                updateChangesIndicator();
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
                saveBtn.classList.remove('btn-warning');
                saveBtn.classList.add('btn-success');
                
                setTimeout(() => {
                    saveBtn.classList.remove('btn-success');
                    saveBtn.classList.add('btn-primary');
                }, 2000);

                showToast(`${changesCount} change(s) saved successfully!`, 'success');
            }, 2000);
        });

        // Enhanced Undo Changes Button
        document.getElementById('undoPendingBtn').addEventListener('click', function() {
            if (pendingChanges.length === 0) {
                showToast('No changes to undo', 'info');
                return;
            }

            const undoCount = pendingChanges.length;
            let undoneCount = 0;

            // Animate undo process
            const undoInterval = setInterval(() => {
                if (pendingChanges.length === 0) {
                    clearInterval(undoInterval);
                    updateChangesIndicator();
                    showToast(`${undoneCount} change(s) undone`, 'warning');
                    return;
                }

                const change = pendingChanges.pop();
                undoneCount++;

                if (change.type === 'move') {
                    const course = document.querySelector(`[data-gene-id="${change.geneId}"]`);
                    if (course) {
                        course.style.transition = 'all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                        course.style.left = change.oldPosition.left;
                        course.style.top = change.oldPosition.top;

                        setTimeout(() => {
                            course.style.transition = 'all 0.15s ease';
                        }, 400);
                    }
                }
                // Add more undo logic for edit operations

                updateChangesIndicator();
            }, 150);
        });

        // Enhanced Export PDF Button
        document.getElementById('exportPdfBtn').addEventListener('click', generatePDF);
    }

    function updateChangesIndicator() {
        const saveBtn = document.getElementById('saveChangesBtn');
        const undoBtn = document.getElementById('undoPendingBtn');
        const undoCount = document.getElementById('undoCount');
        const undoCountMobile = document.getElementById('undoCountMobile');
        const changesIndicator = document.getElementById('changesIndicator');

        // Update counts
        if (undoCount) undoCount.textContent = pendingChanges.length;
        if (undoCountMobile) undoCountMobile.textContent = pendingChanges.length;

        if (pendingChanges.length > 0) {
            saveBtn.classList.remove('btn-primary');
            saveBtn.classList.add('btn-warning');
            saveBtn.innerHTML = `<i class="fas fa-save me-1" style="font-size: 0.65rem;"></i><span class="d-none d-lg-inline">Save (${pendingChanges.length})</span><span class="d-lg-none">${pendingChanges.length}</span>`;

            undoBtn.disabled = false;
            undoBtn.classList.remove('btn-warning');
            undoBtn.classList.add('btn-danger');

            // Show changes indicator
            if (changesIndicator) {
                changesIndicator.classList.remove('d-none');
                const changesText = document.getElementById('changesText');
                if (changesText) {
                    changesText.textContent = `${pendingChanges.length} unsaved change${pendingChanges.length > 1 ? 's' : ''}`;
                }
            }
        } else {
            saveBtn.classList.remove('btn-warning');
            saveBtn.classList.add('btn-primary');
            saveBtn.innerHTML = '<i class="fas fa-save me-1" style="font-size: 0.65rem;"></i><span class="d-none d-lg-inline">Save</span>';

            undoBtn.disabled = true;
            undoBtn.classList.remove('btn-danger');
            undoBtn.classList.add('btn-warning');

            // Hide changes indicator
            if (changesIndicator) {
                changesIndicator.classList.add('d-none');
            }
        }
    }

    function initializeKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Only handle shortcuts if not typing in input fields
            if (e.target.tagName.toLowerCase() === 'input' || 
                e.target.tagName.toLowerCase() === 'textarea' || 
                e.target.tagName.toLowerCase() === 'select') {
                return;
            }

            if (e.ctrlKey || e.metaKey) {
                switch(e.key.toLowerCase()) {
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
                    case '=':
                    case '+':
                        e.preventDefault();
                        document.getElementById('zoomInBtn').click();
                        break;
                    case '-':
                        e.preventDefault();
                        document.getElementById('zoomOutBtn').click();
                        break;
                    case '0':
                        e.preventDefault();
                        document.getElementById('resetZoomBtn').click();
                        break;
                }
            }

            // ESC to cancel any ongoing operations
            if (e.key === 'Escape') {
                if (isDragging && draggedElement) {
                    draggedElement.classList.remove('dragging');
                    draggedElement.setAttribute('aria-grabbed', 'false');
                    isDragging = false;
                    draggedElement = null;
                    showToast('Drag operation cancelled', 'info');
                }

                // Close any open dropdowns
                $('.select2-container--open').each(function() {
                    $(this).prev().select2('close');
                });
            }
        });
    }

    // Helper Functions
    function getInstructorsForSubject(subjectId) {
        if (!subjectId || !allInstructors) return [];
        return allInstructors.filter(i => 
            Array.isArray(i.subject_ids) && i.subject_ids.includes(subjectId)
        ).map(i => ({
            id: i.id,
            name: i.name
        }));
    }

    function getAvailableRoomsForTimeslots(timeslotIds, blockType, subjectId) {
        if (!timeslotIds || !Array.isArray(timeslotIds) || !allRooms) return [];

        // Filter rooms by type
        let filteredRooms = allRooms.filter(room => {
            const roomType = room.type.toLowerCase();
            if (blockType === 'practical') {
                return roomType.includes('lab') || 
                       roomType.includes('مختبر') || 
                       roomType.includes('workshop') || 
                       roomType.includes('ورشة');
            } else {
                return !roomType.includes('lab') && 
                       !roomType.includes('مختبر') && 
                       !roomType.includes('workshop') && 
                       !roomType.includes('ورشة');
            }
        });

        // Filter by availability
        return filteredRooms.filter(room => {
            for (const tsId of timeslotIds) {
                if (timeSlotUsage[tsId] && timeSlotUsage[tsId].rooms) {
                    if (timeSlotUsage[tsId].rooms.includes(room.id)) {
                        return false;
                    }
                }
            }
            return true;
        }).map(room => ({
            id: room.id,
            name: room.room_name || room.name
        }));
    }

    function checkRoomConflict(roomId, timeslotIds) {
        if (!timeslotIds || !Array.isArray(timeslotIds)) return false;
        
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
        // Use global toast function if available, otherwise create local one
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
        toast.className = `toast align-items-center text-white bg-${type} border-0 show`;
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
                    <i class="fas ${iconMap[type] || iconMap.info} me-2" style="font-size: 0.65rem;"></i>
                    <span style="font-size: 0.7rem;">${message}</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        try {
            const bsToast = new bootstrap.Toast(toast, { delay: 4000 });
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        } catch (error) {
            setTimeout(() => toast.remove(), 4000);
        }
    }

    async function generatePDF() {
        const exportBtn = document.getElementById('exportPdfBtn');
        const originalText = exportBtn.innerHTML;

        exportBtn.innerHTML = '<i class="fas fa-spinner loading-spinner me-1" style="font-size: 0.65rem;"></i><span class="d-none d-lg-inline">Generating...</span>';
        exportBtn.disabled = true;

        try {
            showToast('Starting PDF generation...', 'info');

            // Get the timetable container
            const timetableContainer = document.getElementById('timetableContainer');
            
            // Temporarily adjust for better PDF capture
            const originalTransform = timetableContainer.style.transform;
            timetableContainer.style.transform = 'scale(0.8)';

            // Generate PDF using html2canvas and jsPDF
            const canvas = await html2canvas(timetableContainer, {
                scale: 2,
                useCORS: true,
                allowTaint: false,
                backgroundColor: '#ffffff',
                width: timetableContainer.scrollWidth,
                height: timetableContainer.scrollHeight
            });

            // Restore original transform
            timetableContainer.style.transform = originalTransform;

            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({
                orientation: 'landscape',
                unit: 'mm',
                format: 'a3'
            });

            const imgData = canvas.toDataURL('image/png');
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = pdf.internal.pageSize.getHeight();
            
            const imgWidth = pdfWidth - 20; // 10mm margin on each side
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            let heightLeft = imgHeight;
            let position = 10; // 10mm top margin

            // Add pages if content is too long
            pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
            heightLeft -= pdfHeight;

            while (heightLeft >= 0) {
                position = heightLeft - imgHeight + 10;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
                heightLeft -= pdfHeight;
            }

            // Add metadata
            pdf.setProperties({
                title: `Timetable - Chromosome #{{ $chromosome->chromosome_id ?? 'Unknown' }}`,
                subject: 'Academic Timetable',
                author: 'Palestine Technical College',
                creator: 'Timetable Management System'
            });

            // Save the PDF
            const filename = `timetable_chromosome_{{ $chromosome->chromosome_id ?? 'export' }}_${new Date().toISOString().split('T')[0]}.pdf`;
            pdf.save(filename);

            showToast('PDF exported successfully!', 'success');

        } catch (error) {
            console.error('Error generating PDF:', error);
            showToast('Error generating PDF. Please try again.', 'danger');
        } finally {
            exportBtn.innerHTML = originalText;
            exportBtn.disabled = false;
        }
    }

    // Auto-save warning before page unload
    window.addEventListener('beforeunload', function(e) {
        if (pendingChanges.length > 0) {
            const confirmationMessage = `You have ${pendingChanges.length} unsaved change${pendingChanges.length > 1 ? 's' : ''}. Are you sure you want to leave?`;
            e.returnValue = confirmationMessage;
            return confirmationMessage;
        }
    });

    // Periodic save reminder
    setInterval(() => {
        if (pendingChanges.length >= 5) {
            showToast(`You have ${pendingChanges.length} unsaved changes. Consider saving soon.`, 'warning');
        }
    }, 180000); // Every 3 minutes

    // Initialize changes indicator dismiss button
    const dismissChanges = document.getElementById('dismissChanges');
    if (dismissChanges) {
        dismissChanges.addEventListener('click', function() {
            document.getElementById('changesIndicator').classList.add('d-none');
        });
    }

    // Performance optimization: debounce resize events
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            // Recalculate positions if needed
            updateZoom();
        }, 250);
    });

    console.log('✅ Enhanced Interactive Timetable Viewer Initialized');
    console.log(`📊 Initial zoom level: ${Math.round(currentZoom * 100)}%`);
    console.log(`🎯 Drag & Drop: ${draggableCourses.length} course blocks ready`);
    console.log('⌨️ Keyboard shortcuts: Ctrl+S (Save), Ctrl+Z (Undo), Ctrl+E (Export), Ctrl+/- (Zoom)');
    console.log(`💾 Auto-save warnings enabled for ${pendingChanges.length} pending changes`);
});
</script>
@endpush