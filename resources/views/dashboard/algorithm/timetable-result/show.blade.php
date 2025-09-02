@extends('dashboard.layout')

@push('styles')
    <style>
        .main-section {
            margin-bottom: 1rem;
            padding: 1rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
        }

        .conflicts-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 0.5rem;
        }

        .legend-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 0.5rem;
            font-size: 0.85rem;
            width: 180px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.8rem;
        }

        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 3px;
            margin-right: 8px;
        }

        .timetable-container {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            height: 70vh;
        }

        .timetable {
            border-collapse: collapse;
            min-width: 3200px;
            table-layout: fixed;
        }

        .timetable th,
        .timetable td {
            border: 1px solid #e9ecef;
            text-align: center;
            padding: 0;
            height: 90px;
        }

        .timetable .group-header {
            width: 300px;
            font-weight: 600;
            background-color: #f8f9fa;
            position: sticky;
            left: 0;
            z-index: 10;
        }

        .timetable .day-header {
            padding: 0.5rem;
            font-size: 1.1rem;
            font-weight: 700;
            border-bottom: 2px solid #adb5bd;
            position: sticky;
            top: 0;
            z-index: 9;
            background: white;
        }

        .timetable .time-header {
            font-size: 0.8rem;
            color: #6c757d;
            padding: 0.5rem 0;
            width: 180px;
            position: sticky;
            top: 50px;
            z-index: 8;
            background: white;
        }

        .timetable .group-row {
            position: relative;
            height: 200px;
            background-color: #fff;
        }

        .grid-columns-container {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            pointer-events: none;
        }

        .grid-column {
            flex-grow: 1;
            border-right: 1px solid #f1f3f5;
        }

        .event-block {
            background-color: #ffffff;
            border: 1px solid #d0d7de;
            border-left: 5px solid #0d6efd;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
            font-size: 0.8rem;
            text-align: left;
            padding: 8px 10px;
            cursor: default;
            position: absolute;
            z-index: 5;
            overflow: hidden;
            color: #212529;
            height: 90px;
            top: 10px;
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .event-subject,
        .event-instructor,
        .event-room,
        .event-type {
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
            height: 1.2em;
        }

        .event-subject {
            font-weight: 700;
        }

        .event-instructor,
        .event-room {
            cursor: pointer;
        }

        .event-instructor:hover,
        .event-room:hover {
            background-color: #f8f9fa;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="data-entry-container p-2">

            <!-- القسم 1: معلومات + أزرار -->
            <div class="main-section" style="max-height: 10vh;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">📋 Timetable Result - Chromosome #{{ $chromosome->chromosome_id }}</h5>
                    <div class="d-flex">
                        <a href="{{ route('algorithm-control.timetable.results.index') }}"
                            class="btn btn-outline-secondary btn-sm me-2">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                        <button class="btn btn-primary btn-sm me-2" id="saveChangesBtn">
                            <i class="fas fa-save me-1"></i> Save
                        </button>
                        <button class="btn btn-warning btn-sm me-2" id="undoPendingBtn">
                            <i class="fas fa-undo me-1"></i> Undo All
                        </button>
                        <button class="btn btn-success btn-sm" id="exportPdfBtn">
                            <i class="fas fa-file-export me-1"></i> Export PDF
                        </button>
                    </div>
                </div>
                <p class="text-muted small mb-0">
                    <strong>Penalty:</strong> {{ $chromosome->penalty_value }} |
                    <strong>Generation:</strong> {{ $chromosome->generation_number }} |
                    <strong>Run ID:</strong> {{ $chromosome->population->population_id }}
                </p>
            </div>

            <!-- القسم 2: التعارضات -->
            <div class="main-section" style="max-height: 30vh;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">⚠️ Conflicts ({{ count($conflicts) }})</h6>
                    <span class="badge {{ count($conflicts) ? 'bg-danger' : 'bg-success' }}">{{ count($conflicts) }}</span>
                </div>

                <div class="d-flex gap-3">
                    <div class="conflicts-list flex-grow-1">
                        @if (!empty($conflicts))
                            <ul class="list-group list-group-flush">
                                @foreach ($conflicts as $c)
                                    <li class="list-group-item small"
                                        style="border-left: 4px solid {{ $c['color'] }}; background-color: {{ $c['color'] }}10; color: {{ $c['color'] }};">
                                        <strong>{{ $c['type'] }}:</strong> {{ $c['description'] }}
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center text-success py-2">No conflicts found.</div>
                        @endif
                    </div>

                    <div class="legend-container">
                        <h6>Legend</h6>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #0d6efd;"></div> No Conflict
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #dc3545;"></div> Time/Instructor
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #fd7e14;"></div> Room
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #6f42c1;"></div> Qualification
                        </div>
                    </div>
                </div>
            </div>

            <!-- القسم 3: الجدول -->
            <div class="main-section">
                <h6 class="mb-2">📅 Schedule</h6>
                <div class="timetable-container">
                    <table class="timetable">
                        <thead>
                            <tr>
                                <th class="group-header">Student Group</th>
                                @foreach ($timeslotsByDay as $day => $daySlots)
                                    <th class="day-header" colspan="{{ $daySlots->count() }}">{{ $day }}</th>
                                @endforeach
                            </tr>
                            <tr>
                                <th class="group-header"></th>
                                @foreach (collect($timeslotsByDay)->flatten() as $timeslot)
                                    <th class="time-header">
                                        {{ \Carbon\Carbon::parse($timeslot->start_time)->format('H:i') }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php $sortedGroups = collect($scheduleByGroup)->sortBy(fn($g) => explode('|', $g['name'])[0]); @endphp
                            @foreach ($sortedGroups as $groupId => $group)
                                <tr>
                                    <th class="group-header">{{ $group['name'] }}</th>
                                    <td class="group-row" colspan="{{ $totalColumnsOverall }}">
                                        <div class="grid-columns-container">
                                            @for ($i = 0; $i < $totalColumnsOverall; $i++)
                                                <div class="grid-column"></div>
                                            @endfor
                                        </div>

                                        @php $slotsForGroup = []; @endphp
                                        @foreach (collect($group['blocks'])->unique('gene_id') as $block)
                                            @php
                                                $timeslotIds = $block->timeslot_ids;
                                                if (empty($timeslotIds)) {
                                                    continue;
                                                }
                                                $startSlotId = $timeslotIds[0];
                                                if (!isset($slotPositions[$startSlotId])) {
                                                    continue;
                                                }

                                                $startColumn = $slotPositions[$startSlotId];
                                                $span = count($timeslotIds);
                                                $stackLevel = 0;
                                                while (
                                                    isset($slotsForGroup[$stackLevel]) &&
                                                    collect($slotsForGroup[$stackLevel])->some(
                                                        fn($o) => $startColumn < $o['start'] + $o['span'] &&
                                                            $startColumn + $span > $o['start'],
                                                    )
                                                ) {
                                                    $stackLevel++;
                                                }
                                                $slotsForGroup[$stackLevel][] = [
                                                    'start' => $startColumn,
                                                    'span' => $span,
                                                ];
                                                $left = ($startColumn / $totalColumnsOverall) * 100;
                                                $width = ($span / $totalColumnsOverall) * 100;
                                                $height = 90;
                                                $top = $stackLevel * ($height + 5);

                                                $conflictType = $conflictChecker->getGeneConflictType($block->gene_id);
                                                $borderColor = $conflictChecker->getGeneConflictColor($block->gene_id);
                                                $bgColor = match ($conflictType) {
                                                    'Time Overlap', 'Instructor Conflict' => '#fff0f1',
                                                    'Room Conflict', 'Room Capacity', 'Room Type' => '#fff4e6',
                                                    'Instructor Qualification' => '#f8f3fc',
                                                    default => '#ffffff',
                                                };
                                            @endphp
                                            <div class="event-block"
                                                style="top: {{ $top }}px; left: {{ $left }}%; width: calc({{ $width }}% - 4px); height: {{ $height }}px; border-left-color: {{ $borderColor }}; background-color: {{ $bgColor }};">

                                                <div class="event-subject">
                                                    {{ optional($block->section->planSubject->subject)->subject_no }} -
                                                    {{ optional($block->section->planSubject->subject)->subject_name }}
                                                </div>

                                                <div class="event-instructor" data-field="instructor"
                                                    data-gene-id="{{ $block->gene_id }}"
                                                    data-subject-id="{{ optional($block->section->planSubject->subject)->id }}">
                                                    {{ Str::limit(optional($block->instructor->user)->name, 30) }}
                                                </div>

                                                <div class="event-room" data-field="room"
                                                    data-gene-id="{{ $block->gene_id }}"
                                                    data-timeslot-ids='@json($block->timeslot_ids)'>
                                                    {{ optional($block->room)->room_name }}
                                                </div>

                                                <div class="event-type">
                                                    {{ ucfirst($block->block_type) }} | {{ $block->block_duration }}h
                                                </div>
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

    {{-- @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

        <script>
            ///////////////////////////////////////////////////////////////////////////////////////
            // --- البيانات من الخادم ---
            const allRooms = @json($allRooms);
            const allInstructors = @json($allInstructors);
            const timeSlotUsage = @json($timeSlotUsage);

            console.log("All Rooms:", allRooms);
            console.log("All Instructors:", allInstructors);
            console.log("Time Slot Usage:", timeSlotUsage);

            // --- دالة: جلب المدرسين المؤهلين لمادة معينة ---
            function getInstructorsForSubject(subjectId) {
                console.log("Searching for instructors for subject ID:", subjectId);
                return allInstructors.filter(i => i.subject_ids.includes(subjectId));
            }

            // --- دالة: جلب القاعات المتاحة في الفترات المحددة ---
            function getAvailableRoomsForTimeslots(timeslotIds) {
                console.log("Checking rooms for timeslots:", timeslotIds);
                const usedRoomIds = new Set();
                timeslotIds.forEach(tsId => {
                    if (timeSlotUsage[tsId] && timeSlotUsage[tsId].rooms) {
                        timeSlotUsage[tsId].rooms.forEach(id => usedRoomIds.add(id));
                    }
                });
                console.log("Used rooms:", Array.from(usedRoomIds));

                const availableRooms = allRooms.filter(room => !usedRoomIds.has(room.id));
                console.log("Available rooms:", availableRooms);
                return availableRooms;
            }

            // --- دالة: إنشاء السلكت ---
            function createSelectElement(fieldData, geneId, currentValue, options) {
                const select = document.createElement('select');
                select.className = 'form-select';
                select.style.fontSize = '0.75rem';
                select.style.padding = '0.1rem';
                select.dataset.field = fieldData;
                select.dataset.geneId = geneId;

                options.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name;
                    if (item.name === currentValue) option.selected = true;
                    select.appendChild(option);
                });

                return select;
            }

            // --- دالة: فلترة القاعات حسب نوع المادة وتوفرها ---
            function getAvailableRoomsForTimeslots(timeslotIds, blockType, subjectId) {
                console.log("Filtering rooms for:", {
                    timeslotIds,
                    blockType,
                    subjectId
                });

                // 1. تصفية القاعات حسب النوع
                let filteredRooms = allRooms.filter(room => {
                    if (blockType === 'practical') {
                        // للعملي، نحتاج قاعات مختبرات
                        return room.type.toLowerCase().includes('lab') ||
                            room.type.toLowerCase().includes('مختبر') ||
                            room.type.toLowerCase().includes('workshop') ||
                            room.type.toLowerCase().includes('ورشة');
                    } else {
                        // للنظري، نحتاج قاعات نظرية
                        return !room.type.toLowerCase().includes('lab') &&
                            !room.type.toLowerCase().includes('مختبر') &&
                            !room.type.toLowerCase().includes('workshop') &&
                            !room.type.toLowerCase().includes('ورشة');
                    }
                });

                console.log("Rooms after type filter:", filteredRooms);

                // 2. تصفية القاعات المتاحة في جميع الفترات
                const availableRooms = filteredRooms.filter(room => {
                    // نتحقق من أن القاعة لا تستخدم في أي من هذه الفترات
                    for (const tsId of timeslotIds) {
                        if (timeSlotUsage[tsId] && timeSlotUsage[tsId].rooms) {
                            if (timeSlotUsage[tsId].rooms.includes(room.id)) {
                                return false; // القاعة مستخدمة في هذه الفترة
                            }
                        }
                    }
                    return true; // القاعة متاحة في جميع الفترات
                });

                console.log("Available rooms:", availableRooms);
                return availableRooms;
            }

            // --- تفعيل النقر على الحقول القابلة للتعديل ---
            document.addEventListener('click', function(e) {
                console.log("Click event on:", e.target);
                const field = e.target;
                if (!field.classList.contains('event-instructor') && !field.classList.contains('event-room')) {
                    return;
                }

                const fieldData = field.dataset.field;
                const geneId = field.dataset.geneId;
                const currentValue = field.textContent.trim();

                console.log("Editing field:", fieldData, "Gene ID:", geneId);

                // --- جلب الخيارات ---
                let options = [];
                if (fieldData === 'instructor') {
                    const subjectId = field.dataset.subjectId;
                    console.log("Getting instructors for subject:", subjectId);
                    options = getInstructorsForSubject(parseInt(subjectId));
                } else if (fieldData === 'room') {
                    const timeslotIds = JSON.parse(field.dataset.timeslotIds);
                    const blockType = field.closest('.event-block').querySelector('.event-type').textContent.includes(
                        'Practical') ? 'practical' : 'theory';
                    console.log("Getting rooms for timeslots:", timeslotIds, "Block Type:", blockType);
                    options = getAvailableRoomsForTimeslots(timeslotIds, blockType, field.dataset.subjectId);
                }
                // } else if (fieldData === 'room') {
                //     const timeslotIds = JSON.parse(field.dataset.timeslotIds);
                //     console.log("Getting rooms for timeslots:", timeslotIds);
                //     options = getAvailableRoomsForTimeslots(timeslotIds);
                // }

                console.log("Options to display:", options);

                // --- إنشاء السلكت ---
                const select = createSelectElement(fieldData, geneId, currentValue, options);
                field.replaceWith(select);
                $(select).select2({
                    width: '100%',
                    dropdownAutoWidth: true
                });
                $(select).focus();

                // --- عند الإغلاق، احفظ التعديل ---
                $(select).on('select2:close', function() {
                    const selectedId = $(this).val();
                    const selectedText = $(this).find(':selected').text();
                    const oldValue = $(this).find('option[selected]').text() || selectedText;

                    const div = document.createElement('div');
                    div.className = fieldData === 'instructor' ? 'event-instructor' : 'event-room';
                    div.dataset.field = fieldData;
                    div.dataset.geneId = geneId;
                    if (fieldData === 'instructor') div.dataset.subjectId = this.dataset.subjectId;
                    if (fieldData === 'room') div.dataset.timeslotIds = this.dataset.timeslotIds;
                    div.textContent = selectedText;
                    $(this).replaceWith(div);
                });
            });

            // --- زر الاستخراج ---
            document.getElementById('exportPdfBtn').addEventListener('click', function() {
                alert('Export to PDF is under development.');
            });
        </script>
    @endpush --}}

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

        <script>
            // --- البيانات من الخادم ---
            const allRooms = @json($allRooms);
            const allInstructors = @json($allInstructors);
            const timeSlotUsage = @json($timeSlotUsage);

            console.log("All Rooms:", allRooms);
            console.log("All Instructors:", allInstructors);
            console.log("Time Slot Usage:", timeSlotUsage);

            // --- دالة: جلب المدرسين المؤهلين لمادة معينة ---
            function getInstructorsForSubject(subjectId) {
                console.log("Searching for instructors for subject ID:", subjectId);
                return allInstructors.filter(i => i.subject_ids.includes(subjectId));
            }

            // --- دالة: فلترة القاعات حسب نوع المادة وتوفرها ---
            function getAvailableRoomsForTimeslots(timeslotIds, blockType, subjectId) {
                console.log("Filtering rooms for:", {
                    timeslotIds,
                    blockType,
                    subjectId
                });

                // 1. تصفية القاعات حسب النوع
                let filteredRooms = allRooms.filter(room => {
                    if (blockType === 'practical') {
                        // للعملي، نحتاج قاعات مختبرات
                        return room.type.toLowerCase().includes('lab') ||
                            room.type.toLowerCase().includes('مختبر') ||
                            room.type.toLowerCase().includes('workshop') ||
                            room.type.toLowerCase().includes('ورشة');
                    } else {
                        // للنظري، نحتاج قاعات نظرية
                        return !room.type.toLowerCase().includes('lab') &&
                            !room.type.toLowerCase().includes('مختبر') &&
                            !room.type.toLowerCase().includes('workshop') &&
                            !room.type.toLowerCase().includes('ورشة');
                    }
                });

                console.log("Rooms after type filter:", filteredRooms);

                // 2. تصفية القاعات المتاحة في جميع الفترات
                const availableRooms = filteredRooms.filter(room => {
                    // نتحقق من أن القاعة لا تستخدم في أي من هذه الفترات
                    for (const tsId of timeslotIds) {
                        if (timeSlotUsage[tsId] && timeSlotUsage[tsId].rooms) {
                            if (timeSlotUsage[tsId].rooms.includes(room.id)) {
                                return false; // القاعة مستخدمة في هذه الفترة
                            }
                        }
                    }
                    return true; // القاعة متاحة في جميع الفترات
                });

                console.log("Available rooms:", availableRooms);
                return availableRooms;
            }

            // --- دالة: إنشاء السلكت ---
            function createSelectElement(fieldData, geneId, currentValue, options) {
                const select = document.createElement('select');
                select.className = 'form-select';
                select.style.fontSize = '0.75rem';
                select.style.padding = '0.1rem';
                select.dataset.field = fieldData;
                select.dataset.geneId = geneId;

                options.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name;
                    if (item.name === currentValue) option.selected = true;
                    select.appendChild(option);
                });

                return select;
            }

            // --- تفعيل النقر على الحقول القابلة للتعديل ---
            document.addEventListener('click', function(e) {
                console.log("Click event on:", e.target);
                const field = e.target;
                if (!field.classList.contains('event-instructor') && !field.classList.contains('event-room')) {
                    return;
                }

                const fieldData = field.dataset.field;
                const geneId = field.dataset.geneId;
                const currentValue = field.textContent.trim();

                console.log("Editing field:", fieldData, "Gene ID:", geneId);

                // --- جلب الخيارات ---
                let options = [];
                if (fieldData === 'instructor') {
                    const subjectId = field.dataset.subjectId;
                    console.log("Getting instructors for subject:", subjectId);
                    options = getInstructorsForSubject(parseInt(subjectId));
                } else if (fieldData === 'room') {
                    const timeslotIds = JSON.parse(field.dataset.timeslotIds);
                    // الحصول على نوع البلوك من النص الموجود في الحقل
                    const blockType = field.closest('.event-block').querySelector('.event-type').textContent.includes(
                        'Practical') ? 'practical' : 'theory';
                    console.log("Getting rooms for timeslots:", timeslotIds, "Block Type:", blockType);
                    options = getAvailableRoomsForTimeslots(timeslotIds, blockType, field.dataset.subjectId);
                }

                console.log("Options to display:", options);

                // --- إنشاء السلكت ---
                const select = createSelectElement(fieldData, geneId, currentValue, options);
                field.replaceWith(select);
                $(select).select2({
                    width: '100%',
                    dropdownAutoWidth: true
                });
                $(select).focus();

                // --- عند الإغلاق، احفظ التعديل ---
                $(select).on('select2:close', function() {
                    const selectedId = $(this).val();
                    const selectedText = $(this).find(':selected').text();
                    const oldValue = $(this).find('option[selected]').text() || selectedText;

                    // --- تحديث البلوك بعد التعديل ---
                    const eventBlock = this.closest('.event-block');
                    const newRoomId = selectedId;

                    // تغيير لون البلوك حسب وجود التعارض
                    const hasConflict = checkRoomConflict(newRoomId, JSON.parse(this.dataset.timeslotIds));
                    if (hasConflict) {
                        eventBlock.style.borderLeftColor = '#dc3545'; // أحمر (تعارض)
                        eventBlock.style.backgroundColor = '#fff0f1'; // وردي فاتح
                    } else {
                        eventBlock.style.borderLeftColor = '#0d6efd'; // أزرق (لا تعارض)
                        eventBlock.style.backgroundColor = '#ffffff'; // أبيض
                    }

                    const div = document.createElement('div');
                    div.className = fieldData === 'instructor' ? 'event-instructor' : 'event-room';
                    div.dataset.field = fieldData;
                    div.dataset.geneId = geneId;
                    if (fieldData === 'instructor') div.dataset.subjectId = this.dataset.subjectId;
                    if (fieldData === 'room') div.dataset.timeslotIds = this.dataset.timeslotIds;
                    div.textContent = selectedText;
                    $(this).replaceWith(div);
                });
            });

            // --- دالة: فحص التعارض في القاعة ---
            function checkRoomConflict(roomId, timeslotIds) {
                for (const tsId of timeslotIds) {
                    if (timeSlotUsage[tsId] && timeSlotUsage[tsId].rooms) {
                        if (timeSlotUsage[tsId].rooms.includes(roomId)) {
                            return true; // يوجد تعارض
                        }
                    }
                }
                return false; // لا يوجد تعارض
            }

            // --- زر الاستخراج ---
            document.getElementById('exportPdfBtn').addEventListener('click', function() {
                alert('Export to PDF is under development.');
            });
        </script>
    @endpush
@endsection
