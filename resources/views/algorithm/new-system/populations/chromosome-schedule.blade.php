@extends('dashboard.layout')

@section('title', 'Chromosome Schedule - #' . $chromosome->chromosome_id)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="flex-grow-1">
                    <h1 class="h3 mb-2 text-gray-800 d-flex align-items-center flex-wrap">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        <span>Chromosome #{{ $chromosome->chromosome_id }}</span>
                    </h1>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-success">
                            <i class="fas fa-chart-line me-1"></i>
                            Fitness: {{ number_format($chromosome->fitness_value, 4) }}
                        </span>
                        <span class="badge bg-{{ $chromosome->penalty_value == 0 ? 'success' : 'danger' }}">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Penalty: {{ $chromosome->penalty_value }}
                        </span>
                        <span class="badge bg-info">
                            <i class="fas fa-layer-group me-1"></i>
                            Gen: {{ $chromosome->generation_number }}
                        </span>
                    </div>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2 flex-shrink-0">
                    <button class="btn btn-warning btn-sm" id="undoPendingBtn" disabled>
                        <i class="fas fa-undo me-1"></i>
                        Undo (<span id="undoCount">0</span>)
                    </button>
                    <button class="btn btn-primary btn-sm" id="saveChangesBtn">
                        <i class="fas fa-save me-1"></i>
                        Save
                    </button>
                    <button onclick="window.print()" class="btn btn-success btn-sm">
                        <i class="fas fa-print me-1"></i>
                        Print
                    </button>
                    <a href="{{ route('new-algorithm.populations.results', $population->population_id) }}"
                       class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Container -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="schedule-wrapper" style="overflow: auto; max-height: 90vh;">
                <!-- Header: Days and Times -->
                <div class="schedule-header">
                    <div class="group-header-cell">
                        <span>Group / Time</span>
                    </div>
                    @foreach($days as $dayIndex => $dayName)
                        <div class="day-column">
                            <div class="day-name">{{ $dayName }}</div>
                            <div class="time-grid">
                                @for($hour = 8; $hour <= 16; $hour++)
                                    @foreach([0, 30] as $minute)
                                        <div class="time-marker">
                                            {{ sprintf("%02d:%02d", $hour, $minute) }}
                                        </div>
                                    @endforeach
                                @endfor
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Body: Groups and Sessions -->
                <div class="schedule-body">
                    @foreach($groups as $groupKey => $group)
                        <div class="group-row" data-group-key="{{ $groupKey }}">
                            <div class="group-name-cell">
                                <strong>{{ $group['name'] }}</strong>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-users me-1"></i>{{ $group['students'] ?? 0 }} Students
                                </small>
                            </div>

                            @foreach($days as $dayIndex => $dayName)
                                <div class="day-sessions-container schedule-drop-zone"
                                     data-day="{{ $dayIndex }}"
                                     data-group-key="{{ $groupKey }}">
                                    @php
                                        $daySessions = $group['sessions']->where('timeslot_day', $dayIndex);
                                    @endphp

                                    @foreach($daySessions as $session)
                                        @php
                                            $startTime = strtotime($session->start_time);
                                            $endTime = strtotime($session->end_time);
                                            $startHour = (int)date('H', $startTime);
                                            $startMinute = (int)date('i', $startTime);
                                            $endHour = (int)date('H', $endTime);
                                            $endMinute = (int)date('i', $endTime);

                                            $startOffset = ($startHour - 8) * 60 + $startMinute;
                                            $endOffset = ($endHour - 8) * 60 + $endMinute;
                                            $duration = $endOffset - $startOffset;

                                            $leftPosition = $startOffset / 30;
                                            $width = $duration / 30;
                                        @endphp

                                        <div class="session-block draggable-course {{ $session->activity_type === 'Theory' ? 'theory' : 'practical' }}"
                                             draggable="true"
                                             style="left: {{ $leftPosition * 60 }}px; width: {{ $width * 60 }}px;"
                                             data-gene-id="{{ $session->gene_id }}"
                                             data-group-key="{{ $groupKey }}"
                                             data-start="{{ $startOffset }}"
                                             data-end="{{ $endOffset }}"
                                             data-subject="{{ $session->subject_name }}"
                                             data-activity-type="{{ $session->activity_type }}">

                                            <!-- Drag Handle -->
                                            <div class="drag-handle" title="Drag to move">
                                                <i class="fas fa-grip-vertical"></i>
                                            </div>

                                            <div class="session-subject" title="{{ $session->subject_name }}">
                                                {{ $session->subject_name }}
                                            </div>
                                            <div class="session-info">
                                                <!-- Editable Instructor -->
                                                <div class="editable-field"
                                                     data-field="instructor"
                                                     data-gene-id="{{ $session->gene_id }}"
                                                     data-current-value="{{ $session->instructor_id }}"
                                                     title="Click to change instructor">
                                                    <i class="fas fa-user"></i> {{ $session->instructor_name }}
                                                    <i class="fas fa-edit"></i>
                                                </div>

                                                <!-- Editable Room -->
                                                <div class="editable-field"
                                                     data-field="room"
                                                     data-gene-id="{{ $session->gene_id }}"
                                                     data-current-value="{{ $session->room_id }}"
                                                     data-activity-type="{{ $session->activity_type }}"
                                                     title="Click to change room">
                                                    <i class="fas fa-door-open"></i> {{ $session->room_name }}
                                                    <i class="fas fa-edit"></i>
                                                </div>

                                                <div><i class="fas fa-clock"></i> {{ $session->start_time }} - {{ $session->end_time }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Changes Indicator -->
<div id="changesIndicator" class="position-fixed bottom-0 start-50 translate-middle-x mb-3 d-none" style="z-index: 1050;">
    <div class="toast show align-items-center text-white bg-warning border-0 shadow-lg">
        <div class="d-flex">
            <div class="toast-body fw-bold">
                <i class="fas fa-edit me-2"></i>
                <span id="changesText">You have unsaved changes</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2" id="dismissChanges"></button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background: rgba(255,255,255,0.95); z-index: 10000;">
    <div class="d-flex align-items-center justify-content-center h-100 flex-column">
        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
        <h5 class="text-muted">Processing Changes...</h5>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* Container */
.schedule-wrapper {
    overflow-x: auto;
    overflow-y: visible;
    max-width: 100%;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
}

/* Header */
.schedule-header {
    display: flex;
    background: white;
    border-bottom: 2px solid #495057;
    position: sticky;
    top: 0;
    z-index: 100;
}

.group-header-cell {
    min-width: 280px;
    width: 280px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: bold;
    padding: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-right: 2px solid #495057;
    position: sticky;
    left: 0;
    z-index: 101;
}

.day-column {
    min-width: 1080px;
    border-right: 1px solid #dee2e6;
}

.day-name {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-align: center;
    padding: 10px;
    font-weight: bold;
}

.time-grid {
    display: flex;
    background: #f8f9fa;
}

.time-marker {
    width: 60px;
    text-align: center;
    padding: 5px 2px;
    font-size: 10px;
    border-right: 1px solid #dee2e6;
    color: #6c757d;
}

/* Body */
.schedule-body {
    background: white;
}

.group-row {
    display: flex;
    border-bottom: 1px solid #dee2e6;
    min-height: 120px;
}

.group-name-cell {
    min-width: 280px;
    width: 280px;
    padding: 15px;
    background: #f8f9fa;
    border-right: 2px solid #495057;
    position: sticky;
    left: 0;
    z-index: 10;
}

.day-sessions-container {
    min-width: 1080px;
    position: relative;
    border-right: 1px solid #dee2e6;
    background: repeating-linear-gradient(
        to right,
        transparent,
        transparent 59px,
        #e9ecef 59px,
        #e9ecef 60px
    );
}

/* Session Blocks */
.session-block {
    position: absolute;
    height: auto;
    min-height: 100px;
    padding: 10px;
    border-radius: 6px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    cursor: grab;
    transition: all 0.3s ease;
    z-index: 1;
}

.session-block:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 8px 16px rgba(0,0,0,0.25);
    z-index: 50;
}

.session-block.dragging {
    opacity: 0.9;
    transform: rotate(3deg) scale(1.08);
    z-index: 1000 !important;
    cursor: grabbing !important;
}

.session-block.theory {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-left: 5px solid #4c51bf;
}

.session-block.practical {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border-left: 5px solid #e53e3e;
}

.drag-handle {
    position: absolute;
    top: 3px;
    right: 3px;
    width: 12px;
    height: 12px;
    background: rgba(255,255,255,0.3);
    border-radius: 3px;
    cursor: grab;
    display: flex;
    align-items: center;
    justify-content: center;
}

.drag-handle i {
    font-size: 6px;
}

.session-subject {
    font-weight: bold;
    font-size: 13px;
    margin-bottom: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.session-info {
    font-size: 10px;
    line-height: 1.6;
}

.session-info div {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}

/* Editable Fields */
.editable-field {
    cursor: pointer;
    padding: 2px 4px;
    border-radius: 3px;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.editable-field:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.4);
}

.editable-field .fa-edit {
    font-size: 8px;
    opacity: 0.6;
    margin-left: 4px;
}

/* Drop Zones */
.schedule-drop-zone.drag-over {
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.08), rgba(13, 110, 253, 0.04)) !important;
    box-shadow: inset 0 0 16px rgba(13, 110, 253, 0.2);
}

/* Select2 */
.select2-container {
    z-index: 9999 !important;
}

.select2-dropdown {
    border: 2px solid #0d6efd;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    font-size: 11px;
}

@media print {
    .btn {
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
    const chromosomeId = {{ $chromosome->chromosome_id }};
    const populationId = {{ $population->population_id }};
    const saveUrl = "{{ route('new-algorithm.schedule.save-edits') }}"; // يجب إنشاء هذا الـ route
    const csrfToken = "{{ csrf_token() }}";

    // Server data from Controller (pass from backend)
    const allRooms = @json($allRooms ?? []);
    const allInstructors = @json($allInstructors ?? []);

    // State
    let pendingChanges = [];
    let draggedElement = null;
    let isDragging = false;

    // Initialize
    initializeDragAndDrop();
    initializeEditableFields();
    initializeButtons();

    // ===== DRAG & DROP =====
    function initializeDragAndDrop() {
        const draggableCourses = document.querySelectorAll('.draggable-course');
        const dropZones = document.querySelectorAll('.schedule-drop-zone');

        draggableCourses.forEach(course => {
            course.addEventListener('dragstart', handleDragStart);
            course.addEventListener('dragend', handleDragEnd);
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

            const currentGroup = this.dataset.groupKey;

            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('application/json', JSON.stringify({
                geneId: this.dataset.geneId,
                groupKey: currentGroup,
                start: parseInt(this.dataset.start),
                end: parseInt(this.dataset.end)
            }));
        }

        function handleDragEnd(e) {
            isDragging = false;
            this.classList.remove('dragging');

            document.querySelectorAll('.schedule-drop-zone').forEach(zone => {
                zone.classList.remove('drag-over');
            });

            draggedElement = null;
        }

        function handleDragOver(e) {
            if (!isDragging || !draggedElement) return;

            const currentGroup = draggedElement.dataset.groupKey;
            const dropZoneGroup = this.dataset.groupKey;

            if (currentGroup === dropZoneGroup) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            }
        }

        function handleDragEnter(e) {
            if (!isDragging || !draggedElement) return;

            const currentGroup = draggedElement.dataset.groupKey;
            const dropZoneGroup = this.dataset.groupKey;

            if (currentGroup === dropZoneGroup) {
                this.classList.add('drag-over');
            }
        }

        function handleDragLeave(e) {
            if (!this.contains(e.relatedTarget)) {
                this.classList.remove('drag-over');
            }
        }

        function handleDrop(e) {
            if (!isDragging || !draggedElement) return;

            try {
                const dragData = JSON.parse(e.dataTransfer.getData('application/json'));
                const currentGroup = dragData.groupKey;
                const dropZoneGroup = this.dataset.groupKey;

                if (currentGroup !== dropZoneGroup) {
                    showToast('Can only move within the same row', 'warning');
                    return;
                }

                e.preventDefault();
                this.classList.remove('drag-over');

                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;

                handleBlockDrop(dragData, x, this);

            } catch (error) {
                console.error('Drop error:', error);
                showToast('Failed to move block', 'danger');
            }
        }
    }

    function handleBlockDrop(dragData, x, container) {
        const block = document.querySelector(`[data-gene-id="${dragData.geneId}"]`);
        if (!block) return;

        // Calculate new position (snap to 30min intervals)
        const newStart = Math.round(x / 60) * 30;
        const duration = dragData.end - dragData.start;
        const newEnd = newStart + duration;

        // Update block position
        block.style.left = (newStart / 30) * 60 + 'px';
        block.dataset.start = newStart;
        block.dataset.end = newEnd;

        // Add to pending changes
        pendingChanges.push({
            type: 'move',
            gene_id: dragData.geneId,
            new_start: newStart,
            new_end: newEnd,
            timestamp: Date.now()
        });

        updateChangesIndicator();
        showToast('Block repositioned', 'success');
    }

    // ===== EDITABLE FIELDS =====
    function initializeEditableFields() {
        document.addEventListener('click', function(e) {
            const field = e.target.closest('.editable-field');
            if (!field || isDragging) return;

            const fieldType = field.dataset.field;
            const geneId = field.dataset.geneId;
            const currentValue = field.dataset.currentValue;
            const currentText = field.textContent.trim().replace(/[^\w\s]/gi, '').trim();

            let options = [];
            if (fieldType === 'instructor') {
                options = getEligibleInstructors();
            } else if (fieldType === 'room') {
                const activityType = field.dataset.activityType;
                options = getAvailableRooms(activityType);
            }

            createEditSelect(field, fieldType, geneId, currentValue, currentText, options);
        });
    }

    function createEditSelect(field, fieldType, geneId, currentValue, currentText, options) {
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm';
        select.style.fontSize = '10px';
        select.dataset.field = fieldType;
        select.dataset.geneId = geneId;
        select.dataset.originalValue = currentValue;
        select.dataset.originalText = currentText;

        Object.keys(field.dataset).forEach(key => {
            select.dataset[key] = field.dataset[key];
        });

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

        $(select).select2({
            width: '100%',
            placeholder: `Select ${fieldType}...`,
            dropdownParent: $(select.closest('.session-block'))
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

        pendingChanges.push({
            type: 'edit',
            gene_id: geneId,
            field: fieldType,
            new_value_id: newValue,
            oldValue: oldValue,
            timestamp: Date.now()
        });

        updateChangesIndicator();
        showToast(`${fieldType} updated`, 'success');
    }

    function createFieldDiv(fieldType, geneId, text, dataAttrs) {
        const icon = fieldType === 'instructor' ? 'fa-user' : 'fa-door-open';
        const div = document.createElement('div');
        div.className = 'editable-field';
        div.dataset.field = fieldType;
        div.dataset.geneId = geneId;
        div.dataset.currentValue = dataAttrs.newValueId || dataAttrs.currentValue;
        div.title = `Click to change ${fieldType}`;
        div.innerHTML = `<i class="fas ${icon}"></i> ${text}<i class="fas fa-edit"></i>`;

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

    // ===== HELPERS =====
    function getEligibleInstructors() {
        return allInstructors.map(i => ({ id: i.id, name: i.name }));
    }

    function getAvailableRooms(activityType) {
        return allRooms.filter(r => {
            if (activityType === 'Theory') {
                return r.category_id == 1; // نظري
            } else {
                return r.category_id == 2; // عملي
            }
        }).map(r => ({ id: r.id, name: r.name }));
    }

    // ===== BUTTONS =====
    function initializeButtons() {
        document.getElementById('saveChangesBtn').addEventListener('click', performSave);
        document.getElementById('undoPendingBtn').addEventListener('click', performUndo);

        const dismissBtn = document.getElementById('dismissChanges');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', () => {
                document.getElementById('changesIndicator').classList.add('d-none');
            });
        }
    }

    async function performSave() {
        if (pendingChanges.length === 0) {
            showToast('No changes to save', 'info');
            return;
        }

        const saveBtn = document.getElementById('saveChangesBtn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

        try {
            const response = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    chromosome_id: chromosomeId,
                    changes: pendingChanges
                })
            });

            const result = await response.json();

            if (result.success) {
                pendingChanges = [];
                updateChangesIndicator();
                showToast('Changes saved successfully!', 'success');

                saveBtn.innerHTML = '<i class="fas fa-check me-1"></i>Saved!';
                setTimeout(() => {
                    saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save';
                    saveBtn.disabled = false;
                }, 2000);
            } else {
                throw new Error(result.message || 'Save failed');
            }
        } catch (error) {
            console.error('Save error:', error);
            showToast('Failed to save: ' + error.message, 'danger');
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save';
            saveBtn.disabled = false;
        }
    }

    function performUndo() {
        if (pendingChanges.length === 0) return;

        const change = pendingChanges.pop();

        if (change.type === 'move') {
            const block = document.querySelector(`[data-gene-id="${change.gene_id}"]`);
            if (block && change.oldStart !== undefined) {
                block.style.left = (change.oldStart / 30) * 60 + 'px';
                block.dataset.start = change.oldStart;
                block.dataset.end = change.oldEnd;
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
        showToast('Change undone', 'warning');
    }

    function updateChangesIndicator() {
        const saveBtn = document.getElementById('saveChangesBtn');
        const undoBtn = document.getElementById('undoPendingBtn');
        const undoCount = document.getElementById('undoCount');
        const changesIndicator = document.getElementById('changesIndicator');
        const changesText = document.getElementById('changesText');

        if (undoCount) undoCount.textContent = pendingChanges.length;

        if (pendingChanges.length > 0) {
            saveBtn.classList.add('btn-warning');
            saveBtn.classList.remove('btn-primary');
            undoBtn.disabled = false;

            if (changesIndicator) {
                changesIndicator.classList.remove('d-none');
                if (changesText) {
                    changesText.textContent = `You have ${pendingChanges.length} unsaved changes`;
                }
            }
        } else {
            saveBtn.classList.remove('btn-warning');
            saveBtn.classList.add('btn-primary');
            undoBtn.disabled = true;

            if (changesIndicator) {
                changesIndicator.classList.add('d-none');
            }
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

        const iconMap = {
            success: 'fa-check-circle',
            danger: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas ${iconMap[type] || iconMap.info} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        setTimeout(() => toast.remove(), 4000);
    }

    // Keyboard Shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            if (e.key === 's') {
                e.preventDefault();
                document.getElementById('saveChangesBtn').click();
            } else if (e.key === 'z') {
                e.preventDefault();
                document.getElementById('undoPendingBtn').click();
            }
        }
    });

    // Auto-save warning
    window.addEventListener('beforeunload', function(e) {
        if (pendingChanges.length > 0) {
            const confirmationMessage = `You have ${pendingChanges.length} unsaved changes. Are you sure you want to leave?`;
            e.returnValue = confirmationMessage;
            return confirmationMessage;
        }
    });

    console.log('✅ Enhanced Chromosome Schedule initialized');
    console.log(`📊 Blocks: ${document.querySelectorAll('.draggable-course').length}`);
});
</script>
@endpush
