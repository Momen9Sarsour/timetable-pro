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
                        <span class="badge bg-light text-dark me-1" style="font-size: 0.7rem;">Total Penalty: {{ $conflictStats['total_penalty'] }}</span>
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

    <!-- Enhanced Conflicts Detection Card -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle text-warning me-2" style="font-size: 0.75rem;"></i>
                            <h6 class="card-title mb-0" style="font-size: 0.8rem;">Conflicts Detection</h6>
                        </div>
                        <span class="badge {{ $conflictStats['total_conflicts'] > 0 ? 'bg-danger' : 'bg-success' }} bg-opacity-10 text-{{ $conflictStats['total_conflicts'] > 0 ? 'danger' : 'success' }}" style="font-size: 0.65rem; padding: 0.2rem 0.4rem;">
                            {{ $conflictStats['total_conflicts'] }} Conflicts Found
                        </span>
                    </div>
                </div>

                <div class="card-body py-2">
                    <div class="row g-2">
                        <!-- Penalty Breakdown -->
                        <div class="col-lg-8">
                            <div class="row g-2">
                                <div class="col-md-2">
                                    <div class="text-center p-2 border rounded" style="background: #e83e8c10;">
                                        <div class="fw-bold" style="font-size: 0.8rem; color: #e83e8c;">{{ $conflictStats['student_conflicts'] }}</div>
                                        <small style="font-size: 0.6rem;">Students</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center p-2 border rounded" style="background: #dc354510;">
                                        <div class="fw-bold" style="font-size: 0.8rem; color: #dc3545;">{{ $conflictStats['teacher_conflicts'] }}</div>
                                        <small style="font-size: 0.6rem;">Teachers</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center p-2 border rounded" style="background: #fd7e1410;">
                                        <div class="fw-bold" style="font-size: 0.8rem; color: #fd7e14;">{{ $conflictStats['room_conflicts'] }}</div>
                                        <small style="font-size: 0.6rem;">Rooms</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center p-2 border rounded" style="background: #ffc10710;">
                                        <div class="fw-bold" style="font-size: 0.8rem; color: #ffc107;">{{ $conflictStats['capacity_conflicts'] }}</div>
                                        <small style="font-size: 0.6rem;">Capacity</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center p-2 border rounded" style="background: #ffc10710;">
                                        <div class="fw-bold" style="font-size: 0.8rem; color: #ffc107;">{{ $conflictStats['room_type_conflicts'] }}</div>
                                        <small style="font-size: 0.6rem;">Room Type</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center p-2 border rounded" style="background: #6f42c110;">
                                        <div class="fw-bold" style="font-size: 0.8rem; color: #6f42c1;">{{ $conflictStats['teacher_eligibility_conflicts'] }}</div>
                                        <small style="font-size: 0.6rem;">Eligibility</small>
                                    </div>
                                </div>
                            </div>

                            <div class="conflicts-container mt-2" style="max-height: 80px; overflow-y: auto;">
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
                                        <h6 class="text-success mb-0" style="font-size: 0.7rem;">Perfect Schedule!</h6>
                                        <small class="text-muted" style="font-size: 0.55rem;">No conflicts detected</small>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Enhanced Legend -->
                        <div class="col-lg-4">
                            <div class="card bg-light border-0" style="font-size: 0.6rem;">
                                <div class="card-header bg-transparent border-0 py-1">
                                    <h6 class="mb-0" style="font-size: 0.65rem;">
                                        <i class="fas fa-palette me-1 text-primary" style="font-size: 0.55rem;"></i>
                                        Color Legend
                                    </h6>
                                </div>
                                <div class="card-body py-1">
                                    <div class="legend-items">
                                        <div class="legend-item d-flex align-items-center mb-1">
                                            <div class="legend-color me-1" style="width: 8px; height: 8px; background: #0d6efd; border-radius: 2px;"></div>
                                            <small style="font-size: 0.55rem;">No Conflict</small>
                                        </div>
                                        <div class="legend-item d-flex align-items-center mb-1">
                                            <div class="legend-color me-1" style="width: 8px; height: 8px; background: #dc3545; border-radius: 2px;"></div>
                                            <small style="font-size: 0.55rem;">Instructor Conflict</small>
                                        </div>
                                        <div class="legend-item d-flex align-items-center mb-1">
                                            <div class="legend-color me-1" style="width: 8px; height: 8px; background: #fd7e14; border-radius: 2px;"></div>
                                            <small style="font-size: 0.55rem;">Room Conflict</small>
                                        </div>
                                        <div class="legend-item d-flex align-items-center mb-1">
                                            <div class="legend-color me-1" style="width: 8px; height: 8px; background: #e83e8c; border-radius: 2px;"></div>
                                            <small style="font-size: 0.55rem;">Student Conflict</small>
                                        </div>
                                        <div class="legend-item d-flex align-items-center mb-1">
                                            <div class="legend-color me-1" style="width: 8px; height: 8px; background: #ffc107; border-radius: 2px;"></div>
                                            <small style="font-size: 0.55rem;">Room Type/Capacity</small>
                                        </div>
                                        <div class="legend-item d-flex align-items-center">
                                            <div class="legend-color me-1" style="width: 8px; height: 8px; background: #6f42c1; border-radius: 2px;"></div>
                                            <small style="font-size: 0.55rem;">Teacher Qualification</small>
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

    <!-- Enhanced Timetable Schedule Card -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0" style="font-size: 0.8rem;">
                            <i class="fas fa-calendar-week text-primary me-2" style="font-size: 0.7rem;"></i>
                            Enhanced Interactive Schedule
                            <span class="badge bg-primary bg-opacity-10 text-primary ms-2" style="font-size: 0.6rem;">
                                <i class="fas fa-hand-rock me-1" style="font-size: 0.5rem;"></i>
                                Drag & Drop • Edit Fields
                            </span>
                        </h6>
                        <div class="d-flex gap-1">
                            <button class="btn btn-outline-primary btn-sm" id="zoomInBtn" title="Zoom In (Ctrl + +)" style="padding: 0.2rem 0.35rem;">
                                <i class="fas fa-search-plus" style="font-size: 0.6rem;"></i>
                            </button>
                            <button class="btn btn-outline-primary btn-sm" id="zoomOutBtn" title="Zoom Out (Ctrl + -)" style="padding: 0.2rem 0.35rem;">
                                <i class="fas fa-search-minus" style="font-size: 0.6rem;"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" id="resetZoomBtn" title="Reset Zoom (Ctrl + 0)" style="padding: 0.2rem 0.35rem;">
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
                                                <!-- Enhanced Grid Background -->
                                                <div class="grid-background d-flex position-absolute w-100 h-100" style="top: 0; left: 0; pointer-events: none; z-index: 1;">
                                                    @for ($i = 0; $i < $totalColumnsOverall; $i++)
                                                        <div class="grid-column flex-fill border-end drop-slot" 
                                                             data-slot="{{ $i }}" 
                                                             style="border-color: rgba(0,0,0,0.04); min-height: 100%; position: relative;">
                                                            <div class="drop-indicator" style="
                                                                position: absolute; 
                                                                top: 50%; 
                                                                left: 50%; 
                                                                transform: translate(-50%, -50%);
                                                                width: 16px; 
                                                                height: 16px; 
                                                                border: 2px dashed var(--primary-color);
                                                                border-radius: 50%;
                                                                background: rgba(13, 110, 253, 0.1);
                                                                opacity: 0;
                                                                transition: all 0.3s ease;
                                                                display: flex;
                                                                align-items: center;
                                                                justify-content: center;
                                                            ">
                                                                <i class="fas fa-plus" style="font-size: 8px; color: var(--primary-color);"></i>
                                                            </div>
                                                        </div>
                                                    @endfor
                                                </div>

                                                <!-- Enhanced Schedule Blocks -->
                                                @php $slotsForGroup = []; @endphp
                                                @foreach (collect($group['blocks'])->unique('gene_id') as $block)
                                                    @php
                                                        $timeslotIds = is_string($block->timeslot_ids) ? json_decode($block->timeslot_ids, true) : $block->timeslot_ids;
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
                                                        $height = 70;
                                                        $top = $stackLevel * ($height + 5) + 3;

                                                        // تحديد لون البلوك حسب نوع التعارض
                                                        $conflictType = $conflictChecker->getGeneConflictType($block->gene_id);
                                                        $borderColor = $conflictChecker->getGeneConflictColor($block->gene_id);
                                                        $bgColor = match ($conflictType) {
                                                            'Instructor Conflict' => '#fff0f1',
                                                            'Room Conflict' => '#fff4e6',  
                                                            'Student Conflict' => '#fce8f3',
                                                            'Room Capacity', 'Room Type' => '#fffaeb',
                                                            'Instructor Qualification' => '#f8f3fc',
                                                            default => '#ffffff',
                                                        };
                                                    @endphp

                                                    <div class="event-block draggable-course enhanced-block"
                                                         draggable="true"
                                                         data-gene-id="{{ $block->gene_id }}"
                                                         data-original-left="{{ $left }}"
                                                         data-original-top="{{ $top }}"
                                                         data-group-id="{{ $groupId }}"
                                                         data-course="{{ $block->gene_id }}"
                                                         data-start-column="{{ $startColumn }}"
                                                         data-span="{{ $span }}"
                                                         data-conflict-type="{{ $conflictType }}"
                                                         style="
                                                            position: absolute;
                                                            top: {{ $top }}px;
                                                            left: {{ $left }}%;
                                                            width: calc({{ $width }}% - 3px);
                                                            height: {{ $height }}px;
                                                            background: {{ $bgColor }};
                                                            border: 2px solid {{ $borderColor }};
                                                            border-radius: 6px;
                                                            padding: 6px;
                                                            cursor: grab;
                                                            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                                                            transition: all 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                                                            z-index: 10;
                                                            touch-action: none;
                                                            user-select: none;
                                                            overflow: hidden;
                                                         ">

                                                        <!-- Enhanced Drag Handle -->
                                                        <div class="drag-handle" title="Drag to move" style="
                                                            position: absolute;
                                                            top: 3px;
                                                            right: 3px;
                                                            width: 12px;
                                                            height: 12px;
                                                            background: linear-gradient(45deg, {{ $borderColor }}, {{ $borderColor }}dd);
                                                            border-radius: 3px;
                                                            opacity: 0.7;
                                                            cursor: grab;
                                                            transition: all 0.2s ease;
                                                            display: flex;
                                                            align-items: center;
                                                            justify-content: center;
                                                        ">
                                                            <i class="fas fa-grip-vertical" style="font-size: 6px; color: white;"></i>
                                                        </div>

                                                        <!-- Conflict Status Indicator -->
                                                        @if ($conflictType)
                                                            <div class="conflict-badge" style="
                                                                position: absolute;
                                                                top: 3px;
                                                                left: 3px;
                                                                width: 8px;
                                                                height: 8px;
                                                                background: {{ $borderColor }};
                                                                border-radius: 50%;
                                                                border: 1px solid white;
                                                                animation: conflictPulse 2s infinite;
                                                                z-index: 15;
                                                            " title="{{ $conflictType }}"></div>
                                                        @endif

                                                        <div class="event-content d-flex flex-column h-100">
                                                            <div class="course-code fw-bold mb-1 text-truncate" style="font-size: 0.65rem; line-height: 1.2; color: {{ $borderColor }};">
                                                                {{ optional($block->section->planSubject->subject)->subject_no }} -
                                                                {{ Str::limit(optional($block->section->planSubject->subject)->subject_name, 20) }}
                                                            </div>

                                                            <!-- Enhanced Editable Instructor -->
                                                            <div class="course-instructor editable-field text-truncate mb-1"
                                                                 data-field="instructor"
                                                                 data-gene-id="{{ $block->gene_id }}"
                                                                 data-subject-id="{{ optional($block->section->planSubject->subject)->id }}"
                                                                 data-current-value="{{ $block->instructor_id }}"
                                                                 title="Click to change instructor"
                                                                 style="font-size: 0.55rem; cursor: pointer; padding: 2px 4px; border-radius: 3px; background: rgba(13,110,253,0.08); border: 1px solid transparent; transition: all 0.2s ease;">
                                                                <i class="fas fa-user-tie me-1" style="font-size: 0.5rem;"></i>
                                                                {{ Str::limit(optional($block->instructor->user)->name, 14) }}
                                                                <i class="fas fa-edit ms-1" style="font-size: 0.45rem; opacity: 0.6;"></i>
                                                            </div>

                                                            <!-- Enhanced Editable Room -->
                                                            <div class="course-room editable-field text-truncate mb-1"
                                                                 data-field="room"
                                                                 data-gene-id="{{ $block->gene_id }}"
                                                                 data-timeslot-ids='@json($timeslotIds)'
                                                                 data-current-value="{{ $block->room_id }}"
                                                                 data-block-type="{{ $block->block_type }}"
                                                                 title="Click to change room"
                                                                 style="font-size: 0.55rem; cursor: pointer; padding: 2px 4px; border-radius: 3px; background: rgba(13,110,253,0.08); border: 1px solid transparent; transition: all 0.2s ease;">
                                                                <i class="fas fa-door-open me-1" style="font-size: 0.5rem;"></i>
                                                                {{ optional($block->room)->room_name }}
                                                                <i class="fas fa-edit ms-1" style="font-size: 0.45rem; opacity: 0.6;"></i>
                                                            </div>

                                                            <div class="course-type text-muted mt-auto d-flex justify-content-between align-items-center" style="font-size: 0.45rem; line-height: 1;">
                                                                <span>
                                                                    <i class="fas fa-clock me-1" style="font-size: 0.4rem;"></i>
                                                                    {{ ucfirst($block->block_type) }} | {{ $block->block_duration }}min
                                                                </span>
                                                                <span class="timeslot-count text-primary" style="font-size: 0.4rem;">
                                                                    {{ count($timeslotIds) }} slots
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Enhanced Drop Zone Indicator -->
                        <div id="dropIndicator" class="position-absolute d-none" style="
                            background: linear-gradient(135deg, rgba(13, 110, 253, 0.2), rgba(13, 110, 253, 0.1)); 
                            border: 3px dashed #0d6efd; 
                            border-radius: 8px; 
                            z-index: 9999; 
                            pointer-events: none;
                            backdrop-filter: blur(2px);
                            box-shadow: 0 4px 16px rgba(13, 110, 253, 0.3);
                        ">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <span class="text-primary fw-bold" style="font-size: 0.7rem;">
                                    <i class="fas fa-arrows-alt me-2 animate-bounce" style="font-size: 0.6rem;"></i>
                                    Drop here to move
                                </span>
                            </div>
                        </div>

                        <!-- Zoom Indicator -->
                        <div class="zoom-indicator position-absolute" style="
                            top: 15px; 
                            right: 15px; 
                            background: rgba(0,0,0,0.8); 
                            color: white; 
                            padding: 4px 8px; 
                            border-radius: 4px; 
                            font-size: 0.6rem; 
                            z-index: 100; 
                            opacity: 0; 
                            transition: opacity 0.3s ease;
                        ">80%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Changes Indicator -->
<div id="changesIndicator" class="position-fixed bottom-0 start-50 translate-middle-x mb-3 d-none" style="z-index: 1050;">
    <div class="toast show align-items-center text-white bg-gradient border-0 shadow-lg" style="background: linear-gradient(135deg, #ff6b6b, #ffa726);" role="alert">
        <div class="d-flex align-items-center">
            <div class="toast-body fw-bold">
                <i class="fas fa-edit me-2"></i>
                <span id="changesText">You have unsaved changes</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" id="dismissChanges"></button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background: rgba(255,255,255,0.95); z-index: 10000;">
    <div class="d-flex align-items-center justify-content-center h-100 flex-column">
        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
        <h5 class="text-muted">Processing Changes...</h5>
        <p class="text-muted mb-0">Please wait while we save your modifications</p>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* Enhanced Timetable Styling */
.timetable {
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.65rem;
    min-width: 2800px;
    width: max-content;
    background: var(--light-bg-secondary);
    transform: scale(0.8);
    transform-origin: top left;
}

.timetable th,
.timetable td {
    border: 1px solid var(--light-border);
    padding: 0;
    vertical-align: top;
}

.group-header {
    background: linear-gradient(135deg, var(--light-bg), #e9ecef);
    font-weight: 600;
    padding: 8px 10px;
    width: 220px;
    min-width: 220px;
    font-size: 0.65rem;
    border-right: 2px solid var(--primary-color) !important;
    position: sticky;
    left: 0;
    z-index: 20;
    box-shadow: 2px 0 6px rgba(0,0,0,0.08);
    color: var(--light-text);
}

.day-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    font-weight: 700;
    padding: 8px;
    font-size: 0.65rem;
    position: sticky;
    top: 0;
    z-index: 18;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.time-header {
    background: linear-gradient(135deg, var(--light-bg), #e9ecef);
    color: var(--light-text-secondary);
    font-size: 0.55rem;
    font-weight: 500;
    padding: 6px 4px;
    width: 90px;
    min-width: 90px;
    position: sticky;
    top: 33px;
    z-index: 17;
}

.group-row {
    height: 160px;
    min-height: 160px;
    position: relative;
    background: var(--light-bg);
    transition: all 0.3s ease;
}

/* Enhanced Event Block Styling */
.enhanced-block {
    transition: all 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    overflow: hidden;
    user-select: none;
    cursor: grab;
    border-radius: 6px !important;
    backdrop-filter: blur(1px);
}

.enhanced-block:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.15) !important;
    transform: translateY(-2px) scale(1.02);
    z-index: 25 !important;
}

.enhanced-block:hover .drag-handle {
    opacity: 1;
    transform: scale(1.1);
}

.enhanced-block:hover .editable-field {
    background: rgba(13,110,253,0.15) !important;
    border-color: rgba(13,110,253,0.4);
}

.enhanced-block.dragging {
    opacity: 0.9;
    transform: rotate(3deg) scale(1.08);
    z-index: 1000 !important;
    box-shadow: 0 12px 40px rgba(0,0,0,0.25) !important;
    cursor: grabbing !important;
}

.enhanced-block:active {
    cursor: grabbing;
}

/* Enhanced Grid System */
.grid-column {
    border-right: 1px solid rgba(0,0,0,0.02);
    transition: all 0.2s ease;
    position: relative;
}

.grid-column:nth-child(5n) {
    border-right: 1px solid rgba(0,0,0,0.06);
}

.grid-column.drag-over {
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.08), rgba(13, 110, 253, 0.04)) !important;
    border-color: var(--primary-color) !important;
    box-shadow: inset 0 0 8px rgba(13, 110, 253, 0.2);
}

.grid-column.drag-over .drop-indicator {
    opacity: 1 !important;
    transform: translate(-50%, -50%) scale(1.2);
    animation: dropBounce 0.6s infinite alternate;
}

@keyframes dropBounce {
    0% { transform: translate(-50%, -50%) scale(1.2); }
    100% { transform: translate(-50%, -50%) scale(1.4); }
}

@keyframes conflictPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.3); }
}

@keyframes animate-bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}

.animate-bounce {
    animation: animate-bounce 1s infinite;
}

/* Enhanced Editable Fields */
.editable-field {
    transition: all 0.2s ease;
    border: 1px solid transparent;
    border-radius: 3px;
    position: relative;
}

.editable-field:hover {
    background: rgba(13, 110, 253, 0.15) !important;
    transform: scale(1.05);
    border-color: rgba(13, 110, 253, 0.4);
    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2);
}

.editable-field:hover .fa-edit {
    opacity: 1 !important;
}

/* Enhanced Drag Handle */
.drag-handle {
    opacity: 0.6;
    transition: all 0.2s ease;
}

.drag-handle:hover {
    opacity: 1;
    transform: scale(1.2);
    cursor: grab;
}

.drag-handle:active {
    cursor: grabbing;
}

/* Enhanced Zoom Controls */
.timetable-wrapper {
    position: relative;
    border: 1px solid var(--light-border);
    border-radius: 8px;
    background: var(--light-bg-secondary);
}

/* Enhanced Drop Zones */
.schedule-drop-zone {
    transition: all 0.2s ease;
}

.schedule-drop-zone.drag-over {
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.06), rgba(13, 110, 253, 0.02)) !important;
    box-shadow: inset 0 0 16px rgba(13, 110, 253, 0.2);
}

/* Enhanced Select2 Styling */
.select2-container {
    z-index: 9999 !important;
}

.select2-container .select2-selection--single {
    height: 32px !important;
    border: 1px solid var(--primary-color) !important;
    border-radius: 4px !important;
}

.select2-container .select2-selection--single .select2-selection__rendered {
    line-height: 30px !important;
    font-size: 0.6rem !important;
    padding-left: 8px !important;
}

.select2-dropdown {
    border: 2px solid var(--primary-color);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    font-size: 0.65rem;
    border-radius: 6px;
}

.select2-results__option {
    font-size: 0.65rem;
    padding: 0.5rem;
    transition: all 0.15s ease;
}

.select2-results__option--highlighted {
    background: var(--primary-color) !important;
    transform: translateX(3px);
}

/* Success/Error States */
.enhanced-block.success {
    border-color: var(--success-color) !important;
    box-shadow: 0 0 12px rgba(34, 197, 94, 0.4) !important;
    animation: successPulse 0.6s ease-out;
}

.enhanced-block.error {
    border-color: var(--danger-color) !important;
    box-shadow: 0 0 12px rgba(239, 68, 68, 0.4) !important;
    animation: errorShake 0.6s ease-in-out;
}

@keyframes successPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

@keyframes errorShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-3px); }
    75% { transform: translateX(3px); }
}

/* Loading Animation */
@keyframes saveSpinner {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-spinner {
    animation: saveSpinner 1s linear infinite;
}

/* Enhanced Responsive Design */
@media (max-width: 768px) {
    .timetable {
        transform: scale(0.6);
        min-width: 2400px;
    }

    .group-header {
        width: 180px;
        min-width: 180px;
        font-size: 0.6rem;
        padding: 6px 8px;
    }

    .time-header {
        width: 70px;
        min-width: 70px;
        font-size: 0.5rem;
    }

    .enhanced-block {
        min-height: 60px;
        padding: 4px;
    }

    .group-row {
        height: 140px;
        min-height: 140px;
    }
}

/* Enhanced Accessibility */
.enhanced-block:focus {
    outline: 3px solid var(--primary-color);
    outline-offset: 2px;
}

.editable-field:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 1px;
}

/* Enhanced Visual Feedback */
.zoom-indicator.show {
    opacity: 1;
}

/* Enhanced Scrollbars */
.timetable-wrapper::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.timetable-wrapper::-webkit-scrollbar-track {
    background: var(--light-bg);
    border-radius: 5px;
}

.timetable-wrapper::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 5px;
    transition: background 0.15s ease;
}

.timetable-wrapper::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
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
    const chromosomeId = {{ $chromosome->chromosome_id }};
    const allRooms = @json($allRooms);
    const allInstructors = @json($allInstructors);
    const timeSlotUsage = @json($timeSlotUsage);
    const totalColumns = {{ $totalColumnsOverall }};
    const saveUrl = "{{ route('algorithm-control.timetable.results.saveEdits') }}";
    const csrfToken = "{{ csrf_token() }}";

    // Enhanced State Management
    let currentZoom = 0.8;
    let pendingChanges = [];
    let draggedElement = null;
    let isDragging = false;
    let originalPositions = new Map();
    let dropIndicator = null;
    let loadingOverlay = null;

    // Initialize all functionalities
    initializeElements();
    initializeDragAndDrop();
    initializeZoomControls();
    initializeEditableFields();
    initializeButtons();
    initializeKeyboardShortcuts();
    
    updateZoom();

    function initializeElements() {
        dropIndicator = document.getElementById('dropIndicator');
        loadingOverlay = document.getElementById('loadingOverlay');
        
        // Store original positions for all course blocks
        document.querySelectorAll('.draggable-course').forEach(course => {
            originalPositions.set(course.dataset.geneId, {
                left: course.style.left,
                top: course.style.top,
                groupId: course.dataset.groupId
            });
        });
    }

    function initializeDragAndDrop() {
        const draggableCourses = document.querySelectorAll('.draggable-course');
        const dropZones = document.querySelectorAll('.schedule-drop-zone');

        // Enhanced drag and drop functionality
        draggableCourses.forEach(course => {
            // Mouse/Touch events
            course.addEventListener('dragstart', handleCourseDragStart);
            course.addEventListener('dragend', handleCourseDragEnd);
            
            // Enhanced visual feedback
            course.addEventListener('mouseenter', () => {
                if (!isDragging) {
                    course.style.transform = 'translateY(-2px) scale(1.02)';
                    course.style.zIndex = '15';
                }
            });

            course.addEventListener('mouseleave', () => {
                if (!isDragging) {
                    course.style.transform = '';
                    course.style.zIndex = '10';
                }
            });

            // Accessibility
            course.setAttribute('aria-grabbed', 'false');
            course.setAttribute('role', 'button');
            course.setAttribute('tabindex', '0');
        });

        // Enhanced drop zones
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', handleScheduleDragOver);
            zone.addEventListener('dragenter', handleScheduleDragEnter);
            zone.addEventListener('dragleave', handleScheduleDragLeave);
            zone.addEventListener('drop', handleScheduleDrop);

            // Enhanced grid columns
            const gridColumns = zone.querySelectorAll('.grid-column');
            gridColumns.forEach((column, index) => {
                column.addEventListener('dragover', (e) => handleColumnDragOver(e, index));
                column.addEventListener('dragenter', (e) => handleColumnDragEnter(e, index));
                column.addEventListener('dragleave', (e) => handleColumnDragLeave(e, index));
            });
        });

        function handleCourseDragStart(e) {
            isDragging = true;
            draggedElement = this;
            this.classList.add('dragging');
            this.setAttribute('aria-grabbed', 'true');

            const currentPos = {
                left: this.style.left,
                top: this.style.top,
                groupId: this.dataset.groupId
            };

            const currentGroupId = this.dataset.groupId;
            const sameRowDropZone = document.querySelector(`[data-group-id="${currentGroupId}"]`);
            if (sameRowDropZone) {
                sameRowDropZone.setAttribute('aria-dropeffect', 'move');
                sameRowDropZone.classList.add('drag-target');
            }

            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('application/json', JSON.stringify({
                courseId: this.dataset.course,
                geneId: this.dataset.geneId,
                groupId: currentGroupId,
                startColumn: parseInt(this.dataset.startColumn),
                span: parseInt(this.dataset.span),
                currentPosition: currentPos
            }));

            showToast('Drag to reposition within the same row', 'info');
        }

        function handleCourseDragEnd(e) {
            isDragging = false;
            this.classList.remove('dragging');
            this.setAttribute('aria-grabbed', 'false');

            // Clean up visual indicators
            document.querySelectorAll('.schedule-drop-zone').forEach(zone => {
                zone.classList.remove('drag-target', 'drag-over');
                zone.setAttribute('aria-dropeffect', 'none');
                
                zone.querySelectorAll('.grid-column').forEach(col => {
                    col.classList.remove('drag-over');
                });
            });

            if (dropIndicator) {
                dropIndicator.classList.add('d-none');
            }

            draggedElement = null;
        }

        function handleScheduleDragOver(e) {
            if (!isDragging || !draggedElement) return;

            const currentGroupId = draggedElement.dataset.groupId;
            const dropZoneGroupId = this.dataset.groupId;

            if (currentGroupId === dropZoneGroupId) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                updateDropIndicator(e, this);
            } else {
                e.dataTransfer.dropEffect = 'none';
            }
        }

        function handleScheduleDragEnter(e) {
            if (!isDragging || !draggedElement) return;

            const currentGroupId = draggedElement.dataset.groupId;
            const dropZoneGroupId = this.dataset.groupId;

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

            } catch (error) {
                console.error('Drop error:', error);
                showToast('Failed to move course block', 'danger');
            }
        }
    }

    function updateDropIndicator(e, container) {
        if (!dropIndicator) return;

        const rect = container.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const columnWidth = rect.width / totalColumns;
        const snapColumn = Math.floor(x / columnWidth);
        const snapX = snapColumn * columnWidth;

        dropIndicator.style.left = (rect.left + snapX) + 'px';
        dropIndicator.style.top = (rect.top + y - 35) + 'px';
        dropIndicator.style.width = (columnWidth * (draggedElement?.dataset.span || 1)) + 'px';
        dropIndicator.style.height = '70px';
        dropIndicator.classList.remove('d-none');
    }

    function handleCourseBlockDrop(dragData, x, y, container) {
        const course = document.querySelector(`[data-gene-id="${dragData.geneId}"]`);
        if (!course) return;

        const containerWidth = container.offsetWidth;
        const newLeft = Math.max(0, Math.min(95, (x / containerWidth) * 100));

        // Enhanced grid snapping
        const gridSize = 100 / totalColumns;
        const spanWidth = gridSize * dragData.span;
        const maxLeft = 100 - spanWidth;
        let snappedLeft = Math.round(newLeft / gridSize) * gridSize;
        snappedLeft = Math.min(snappedLeft, maxLeft);

        // Smart vertical positioning
        const newTop = Math.max(3, Math.min(130, y - 35));
        const stackHeight = 75;
        const stackLevel = Math.floor(newTop / stackHeight);
        const finalTop = stackLevel * stackHeight + 3;

        // Overlap detection and resolution
        const finalPosition = checkAndResolveOverlap(course, snappedLeft, finalTop, container);

        // Smooth animation
        course.style.transition = 'all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        course.style.left = finalPosition.left + '%';
        course.style.top = finalPosition.top + 'px';

        // Visual feedback
        course.classList.add('success');
        setTimeout(() => {
            course.classList.remove('success');
            course.style.transition = 'all 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        }, 800);

        // Update data and add to pending changes
        const startColumn = Math.round(finalPosition.left / gridSize);
        const newTimeslotIds = calculateNewTimeslotIds(startColumn, dragData.span);
        
        course.dataset.startColumn = startColumn;

        pendingChanges.push({
            type: 'move',
            gene_id: dragData.geneId,
            new_timeslot_ids: newTimeslotIds,
            oldPosition: dragData.currentPosition,
            newPosition: { 
                left: finalPosition.left, 
                top: finalPosition.top,
                column: startColumn
            },
            timestamp: Date.now()
        });

        updateChangesIndicator();
        showToast('Course repositioned successfully!', 'success');

        if (dropIndicator) {
            dropIndicator.classList.add('d-none');
        }
    }

    function calculateNewTimeslotIds(startColumn, span) {
        // Convert column positions back to timeslot IDs
        const timeslotArray = @json(collect($timeslotsByDay)->flatten()->pluck('id'));
        const newTimeslotIds = [];
        
        for (let i = 0; i < span; i++) {
            const columnIndex = startColumn + i;
            if (columnIndex < timeslotArray.length) {
                newTimeslotIds.push(timeslotArray[columnIndex]);
            }
        }
        
        return newTimeslotIds;
    }

    function checkAndResolveOverlap(movingElement, targetLeft, targetTop, container) {
        const movingSpan = parseInt(movingElement.dataset.span) || 1;
        const gridSize = 100 / totalColumns;
        const movingRight = targetLeft + (movingSpan * gridSize);

        const otherBlocks = container.querySelectorAll('.event-block:not([data-gene-id="' + movingElement.dataset.geneId + '"])');
        
        let finalLeft = targetLeft;
        let finalTop = targetTop;
        
        for (const block of otherBlocks) {
            const blockLeft = parseFloat(block.style.left);
            const blockSpan = parseInt(block.dataset.span) || 1;
            const blockRight = blockLeft + (blockSpan * gridSize);
            const blockTop = parseFloat(block.style.top);
            
            if (Math.abs(blockTop - finalTop) < 70) {
                if (finalLeft < blockRight && movingRight > blockLeft) {
                    finalTop += 75;
                }
            }
        }

        return { left: finalLeft, top: finalTop };
    }

    function initializeZoomControls() {
        const container = document.getElementById('timetableContainer');
        const zoomIndicator = document.querySelector('.zoom-indicator');

        document.getElementById('zoomInBtn').addEventListener('click', () => {
            currentZoom = Math.min(1.2, currentZoom + 0.1);
            updateZoom();
            showZoomIndicator();
        });

        document.getElementById('zoomOutBtn').addEventListener('click', () => {
            currentZoom = Math.max(0.5, currentZoom - 0.1);
            updateZoom();
            showZoomIndicator();
        });

        document.getElementById('resetZoomBtn').addEventListener('click', () => {
            currentZoom = 1;
            updateZoom();
            showZoomIndicator();
        });

        function updateZoom() {
            container.style.transform = `scale(${currentZoom})`;
            container.style.width = `${100 / currentZoom}%`;
            container.style.height = `${100 / currentZoom}%`;

            document.getElementById('zoomInBtn').disabled = currentZoom >= 1.2;
            document.getElementById('zoomOutBtn').disabled = currentZoom <= 0.5;

            if (zoomIndicator) {
                zoomIndicator.textContent = Math.round(currentZoom * 100) + '%';
            }
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
            const currentValue = field.dataset.currentValue;
            const currentText = field.textContent.trim().replace(/^[^\s]*\s/, '').replace(/\s*[^\s]*$/, '');

            let options = [];
            if (fieldType === 'instructor') {
                const subjectId = field.dataset.subjectId;
                options = getEligibleInstructorsForSubject(parseInt(subjectId));
            } else if (fieldType === 'room') {
                const timeslotIds = JSON.parse(field.dataset.timeslotIds);
                const blockType = field.dataset.blockType;
                options = getAvailableRoomsForTimeslots(timeslotIds, blockType);
            }

            createEditSelect(field, fieldType, geneId, currentValue, currentText, options);
        });
    }

    function createEditSelect(field, fieldType, geneId, currentValue, currentText, options) {
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm';
        select.style.fontSize = '0.55rem';
        select.style.width = '100%';
        select.dataset.field = fieldType;
        select.dataset.geneId = geneId;
        select.dataset.originalValue = currentValue;
        select.dataset.originalText = currentText;

        // Copy data attributes
        Object.keys(field.dataset).forEach(key => {
            select.dataset[key] = field.dataset[key];
        });

        // Add options
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = `Select ${fieldType}...`;
        select.appendChild(defaultOption);

        options.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            if (item.id == currentValue) option.selected = true;
            select.appendChild(option);
        });

        field.replaceWith(select);
        
        // Initialize Select2
        $(select).select2({
            width: '100%',
            dropdownAutoWidth: true,
            placeholder: `Select ${fieldType}...`,
            minimumResultsForSearch: 3,
            dropdownParent: $(select.closest('.enhanced-block')),
            templateResult: function(option) {
                if (!option.id) return option.text;
                return $(`<span style="font-size: 0.65rem;">${option.text}</span>`);
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
                revertToOriginalField(this, fieldType);
            }
        });
    }

    function updateFieldValue(select, fieldType, geneId, newText, newValue) {
        const oldValue = select.dataset.originalValue;
        const oldText = select.dataset.originalText;

        const div = createFieldDiv(fieldType, geneId, newText, select.dataset);

        $(select).replaceWith(div);

        // Add to pending changes
        pendingChanges.push({
            type: 'edit',
            gene_id: geneId,
            field: fieldType,
            new_value_id: newValue,
            newText: newText,
            oldValue: oldValue,
            oldText: oldText,
            timestamp: Date.now()
        });

        updateChangesIndicator();
        updateBlockConflictStatus(div.closest('.enhanced-block'), fieldType, newValue);
        showToast(`${fieldType.charAt(0).toUpperCase() + fieldType.slice(1)} updated successfully`, 'success');
    }

    function createFieldDiv(fieldType, geneId, text, dataAttrs) {
        const icon = fieldType === 'instructor' ? 'fa-user-tie' : 'fa-door-open';
        const div = document.createElement('div');
        div.className = `course-${fieldType} editable-field text-truncate mb-1`;
        div.dataset.field = fieldType;
        div.dataset.geneId = geneId;
        div.dataset.currentValue = dataAttrs.currentValue;
        div.title = `Click to change ${fieldType}`;
        div.style.cssText = 'font-size: 0.55rem; cursor: pointer; padding: 2px 4px; border-radius: 3px; background: rgba(13,110,253,0.08); border: 1px solid transparent; transition: all 0.2s ease;';
        div.innerHTML = `<i class="fas ${icon} me-1" style="font-size: 0.5rem;"></i>${text}<i class="fas fa-edit ms-1" style="font-size: 0.45rem; opacity: 0.6;"></i>`;

        // Copy additional data attributes
        Object.keys(dataAttrs).forEach(key => {
            if (key !== 'field' && key !== 'geneId') {
                div.dataset[key] = dataAttrs[key];
            }
        });

        return div;
    }

    function revertToOriginalField(select, fieldType) {
        const div = createFieldDiv(fieldType, select.dataset.geneId, select.dataset.originalText, select.dataset);
        $(select).replaceWith(div);
    }

    function updateBlockConflictStatus(block, fieldType, newValue) {
        if (fieldType === 'room') {
            const roomField = block.querySelector('.course-room');
            const timeslotIds = JSON.parse(roomField.dataset.timeslotIds);
            const hasConflict = checkRoomConflict(newValue, timeslotIds);

            if (hasConflict) {
                block.style.borderColor = '#fd7e14';
                block.style.backgroundColor = '#fff4e6';
                block.dataset.conflictType = 'Room Conflict';
                addConflictIndicator(block, 'room');
                block.classList.add('error');
                setTimeout(() => block.classList.remove('error'), 1000);
            } else {
                block.style.borderColor = '#0d6efd';
                block.style.backgroundColor = '#ffffff';
                block.dataset.conflictType = '';
                removeConflictIndicator(block);
                block.classList.add('success');
                setTimeout(() => block.classList.remove('success'), 1000);
            }
        }
    }

    function addConflictIndicator(block, type) {
        let indicator = block.querySelector('.conflict-badge');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'conflict-badge';
            indicator.style.cssText = `
                position: absolute;
                top: 3px;
                left: 3px;
                width: 8px;
                height: 8px;
                border-radius: 50%;
                border: 1px solid white;
                animation: conflictPulse 2s infinite;
                z-index: 15;
            `;
            block.appendChild(indicator);
        }

        const colors = {
            room: '#fd7e14',
            instructor: '#dc3545',
            qualification: '#6f42c1'
        };

        indicator.style.backgroundColor = colors[type] || '#dc3545';
        indicator.title = `${type} conflict detected`;
    }

    function removeConflictIndicator(block) {
        const indicator = block.querySelector('.conflict-badge');
        if (indicator) {
            indicator.remove();
        }
    }

    function initializeButtons() {
        // Enhanced Save Changes Button
        document.getElementById('saveChangesBtn').addEventListener('click', function() {
            if (pendingChanges.length === 0) {
                showToast('No changes to save', 'info');
                return;
            }

            performSaveChanges();
        });

        // Enhanced Undo Changes Button
        document.getElementById('undoPendingBtn').addEventListener('click', function() {
            if (pendingChanges.length === 0) {
                showToast('No changes to undo', 'info');
                return;
            }

            performUndoChanges();
        });

        // Export PDF Button
        document.getElementById('exportPdfBtn').addEventListener('click', generatePDF);
    }

    async function performSaveChanges() {
        const saveBtn = document.getElementById('saveChangesBtn');
        const originalText = saveBtn.innerHTML;
        const changesCount = pendingChanges.length;

        // Show loading state
        saveBtn.innerHTML = '<i class="fas fa-spinner loading-spinner me-1"></i><span class="d-none d-lg-inline">Saving...</span>';
        saveBtn.disabled = true;
        showLoadingOverlay('Saving changes...');

        try {
            // Prepare data for server
            const saveData = {
                chromosome_id: chromosomeId,
                edits: pendingChanges.filter(c => c.type === 'edit').map(c => ({
                    gene_id: c.gene_id,
                    field: c.field,
                    new_value_id: c.new_value_id
                })),
                moves: pendingChanges.filter(c => c.type === 'move').map(c => ({
                    gene_id: c.gene_id,
                    new_timeslot_ids: c.new_timeslot_ids
                }))
            };

            // Send to server
            const response = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(saveData)
            });

            const result = await response.json();

            if (result.success) {
                // Reset state
                pendingChanges = [];
                updateChangesIndicator();

                // Success feedback
                saveBtn.innerHTML = '<i class="fas fa-check me-1"></i><span class="d-none d-lg-inline">Saved!</span>';
                saveBtn.classList.add('btn-success');
                
                setTimeout(() => {
                    saveBtn.innerHTML = originalText;
                    saveBtn.classList.remove('btn-success');
                    saveBtn.classList.add('btn-primary');
                    saveBtn.disabled = false;
                }, 2000);

                showToast(`${result.edits_saved + result.moves_saved} changes saved successfully!`, 'success');

                // Update UI with server response if needed
                if (result.updated_genes && result.updated_genes.length > 0) {
                    updateUIFromServer(result.updated_genes);
                }

            } else {
                throw new Error(result.message || 'Save failed');
            }

        } catch (error) {
            console.error('Save error:', error);
            
            // Error feedback
            saveBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i><span class="d-none d-lg-inline">Error!</span>';
            saveBtn.classList.add('btn-danger');
            
            setTimeout(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.classList.remove('btn-danger');
                saveBtn.classList.add('btn-primary');
                saveBtn.disabled = false;
            }, 3000);

            showToast('Failed to save changes: ' + error.message, 'danger');
        } finally {
            hideLoadingOverlay();
        }
    }

    function performUndoChanges() {
        const undoCount = pendingChanges.length;
        let undoneCount = 0;

        const undoInterval = setInterval(() => {
            if (pendingChanges.length === 0) {
                clearInterval(undoInterval);
                updateChangesIndicator();
                showToast(`${undoneCount} changes undone`, 'warning');
                return;
            }

            const change = pendingChanges.pop();
            undoneCount++;

            if (change.type === 'move') {
                const course = document.querySelector(`[data-gene-id="${change.gene_id}"]`);
                if (course) {
                    course.style.transition = 'all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                    course.style.left = change.oldPosition.left;
                    course.style.top = change.oldPosition.top;

                    setTimeout(() => {
                        course.style.transition = 'all 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                    }, 400);
                }
            } else if (change.type === 'edit') {
                const block = document.querySelector(`[data-gene-id="${change.gene_id}"]`);
                if (block) {
                    const field = block.querySelector(`[data-field="${change.field}"]`);
                    if (field) {
                        const div = createFieldDiv(change.field, change.gene_id, change.oldText, {
                            currentValue: change.oldValue,
                            ...field.dataset
                        });
                        field.replaceWith(div);
                    }
                }
            }

            updateChangesIndicator();
        }, 150);
    }

    function updateUIFromServer(updatedGenes) {
        updatedGenes.forEach(gene => {
            const block = document.querySelector(`[data-gene-id="${gene.gene_id}"]`);
            if (block) {
                // Update instructor field if changed
                if (gene.instructor) {
                    const instructorField = block.querySelector('.course-instructor');
                    if (instructorField) {
                        instructorField.innerHTML = `<i class="fas fa-user-tie me-1" style="font-size: 0.5rem;"></i>${gene.instructor.user.name}<i class="fas fa-edit ms-1" style="font-size: 0.45rem; opacity: 0.6;"></i>`;
                        instructorField.dataset.currentValue = gene.instructor_id;
                    }
                }

                // Update room field if changed
                if (gene.room) {
                    const roomField = block.querySelector('.course-room');
                    if (roomField) {
                        roomField.innerHTML = `<i class="fas fa-door-open me-1" style="font-size: 0.5rem;"></i>${gene.room.room_name}<i class="fas fa-edit ms-1" style="font-size: 0.45rem; opacity: 0.6;"></i>`;
                        roomField.dataset.currentValue = gene.room_id;
                    }
                }
            }
        });
    }

    function updateChangesIndicator() {
        const saveBtn = document.getElementById('saveChangesBtn');
        const undoBtn = document.getElementById('undoPendingBtn');
        const undoCount = document.getElementById('undoCount');
        const undoCountMobile = document.getElementById('undoCountMobile');
        const changesIndicator = document.getElementById('changesIndicator');

        if (undoCount) undoCount.textContent = pendingChanges.length;
        if (undoCountMobile) undoCountMobile.textContent = pendingChanges.length;

        if (pendingChanges.length > 0) {
            saveBtn.classList.remove('btn-primary');
            saveBtn.classList.add('btn-warning');
            saveBtn.innerHTML = `<i class="fas fa-save me-1" style="font-size: 0.65rem;"></i><span class="d-none d-lg-inline">Save (${pendingChanges.length})</span><span class="d-lg-none">${pendingChanges.length}</span>`;

            undoBtn.disabled = false;
            undoBtn.classList.remove('btn-warning');
            undoBtn.classList.add('btn-danger');

            if (changesIndicator) {
                changesIndicator.classList.remove('d-none');
                const changesText = document.getElementById('changesText');
                if (changesText) {
                    const editCount = pendingChanges.filter(c => c.type === 'edit').length;
                    const moveCount = pendingChanges.filter(c => c.type === 'move').length;
                    changesText.textContent = `${pendingChanges.length} unsaved changes (${editCount} edits, ${moveCount} moves)`;
                }
            }
        } else {
            saveBtn.classList.remove('btn-warning');
            saveBtn.classList.add('btn-primary');
            saveBtn.innerHTML = '<i class="fas fa-save me-1" style="font-size: 0.65rem;"></i><span class="d-none d-lg-inline">Save</span>';

            undoBtn.disabled = true;
            undoBtn.classList.remove('btn-danger');
            undoBtn.classList.add('btn-warning');

            if (changesIndicator) {
                changesIndicator.classList.add('d-none');
            }
        }
    }

    function initializeKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
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

            if (e.key === 'Escape') {
                if (isDragging && draggedElement) {
                    draggedElement.classList.remove('dragging');
                    draggedElement.setAttribute('aria-grabbed', 'false');
                    isDragging = false;
                    draggedElement = null;
                    showToast('Drag operation cancelled', 'info');
                }

                $('.select2-container--open').each(function() {
                    $(this).prev().select2('close');
                });
            }
        });
    }

    // Helper Functions
    function getEligibleInstructorsForSubject(subjectId) {
        if (!subjectId || !allInstructors) return [];
        
        // Get instructors who can teach this subject
        const eligibleInstructors = allInstructors.filter(i => 
            Array.isArray(i.subject_ids) && i.subject_ids.includes(subjectId)
        ).map(i => ({
            id: i.id,
            name: i.name
        }));

        // Also include all instructors as options (for flexibility)
        const allOptions = allInstructors.map(i => ({
            id: i.id,
            name: i.name
        }));

        // Return eligible first, then others
        return [...eligibleInstructors, ...allOptions.filter(opt => !eligibleInstructors.find(elig => elig.id === opt.id))];
    }

    function getAvailableRoomsForTimeslots(timeslotIds, blockType) {
        if (!timeslotIds || !Array.isArray(timeslotIds) || !allRooms) return [];

        // Filter rooms by type first
        let filteredRooms = allRooms.filter(room => {
            const roomType = (room.type || '').toLowerCase();
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

        // If no rooms match the type, return all rooms
        if (filteredRooms.length === 0) {
            filteredRooms = allRooms;
        }

        // Filter by availability (check conflicts)
        const availableRooms = filteredRooms.filter(room => {
            for (const tsId of timeslotIds) {
                if (timeSlotUsage[tsId] && timeSlotUsage[tsId].rooms) {
                    if (timeSlotUsage[tsId].rooms.includes(room.id)) {
                        return false; // Room is occupied
                    }
                }
            }
            return true; // Room is available
        });

        // Return available rooms first, then occupied rooms (for flexibility)
        const occupiedRooms = filteredRooms.filter(room => !availableRooms.includes(room));
        
        return [
            ...availableRooms.map(room => ({ id: room.id, name: room.name })),
            ...occupiedRooms.map(room => ({ id: room.id, name: room.name + ' (Occupied)' }))
        ];
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

    function showLoadingOverlay(message = 'Loading...') {
        if (loadingOverlay) {
            loadingOverlay.querySelector('h5').textContent = message;
            loadingOverlay.classList.remove('d-none');
        }
    }

    function hideLoadingOverlay() {
        if (loadingOverlay) {
            loadingOverlay.classList.add('d-none');
        }
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
        showLoadingOverlay('Generating PDF...');

        try {
            const timetableContainer = document.getElementById('timetableContainer');
            const originalTransform = timetableContainer.style.transform;
            timetableContainer.style.transform = 'scale(0.7)';

            const canvas = await html2canvas(timetableContainer, {
                scale: 2,
                useCORS: true,
                allowTaint: false,
                backgroundColor: '#ffffff',
                width: timetableContainer.scrollWidth,
                height: timetableContainer.scrollHeight
            });

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
            
            const imgWidth = pdfWidth - 20;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            let heightLeft = imgHeight;
            let position = 10;

            pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
            heightLeft -= pdfHeight;

            while (heightLeft >= 0) {
                position = heightLeft - imgHeight + 10;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
                heightLeft -= pdfHeight;
            }

            const filename = `timetable_chromosome_${chromosomeId}_${new Date().toISOString().split('T')[0]}.pdf`;
            pdf.save(filename);

            showToast('PDF exported successfully!', 'success');

        } catch (error) {
            console.error('Error generating PDF:', error);
            showToast('Error generating PDF. Please try again.', 'danger');
        } finally {
            exportBtn.innerHTML = originalText;
            exportBtn.disabled = false;
            hideLoadingOverlay();
        }
    }

    // Auto-save warning
    window.addEventListener('beforeunload', function(e) {
        if (pendingChanges.length > 0) {
            const confirmationMessage = `You have ${pendingChanges.length} unsaved changes. Are you sure you want to leave?`;
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

    // Initialize changes indicator dismiss
    const dismissChanges = document.getElementById('dismissChanges');
    if (dismissChanges) {
        dismissChanges.addEventListener('click', function() {
            document.getElementById('changesIndicator').classList.add('d-none');
        });
    }

    console.log('✅ Enhanced Interactive Timetable initialized successfully');
    console.log(`📊 Zoom: ${Math.round(currentZoom * 100)}% | Blocks: ${document.querySelectorAll('.draggable-course').length}`);
    console.log('🎯 Features: Drag & Drop, Field Editing, Auto-save, PDF Export, Keyboard Shortcuts');
});
</script>
@endpush