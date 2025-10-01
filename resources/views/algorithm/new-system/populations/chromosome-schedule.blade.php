@extends('dashboard.layout')

@section('title', 'Chromosome Schedule - #' . $chromosome->chromosome_id)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        Chromosome #{{ $chromosome->chromosome_id }} - Complete Schedule
                    </h1>
                    <p class="text-muted mb-0">
                        Fitness: <span class="badge bg-success">{{ number_format($chromosome->fitness_value, 4) }}</span> |
                        Penalty: <span class="badge bg-{{ $chromosome->penalty_value == 0 ? 'success' : 'danger' }}">{{ $chromosome->penalty_value }}</span> |
                        Generation: <span class="badge bg-info">{{ $chromosome->generation_number }}</span>
                    </p>
                </div>
                <div>
                    <button onclick="window.print()" class="btn btn-success me-2">
                        <i class="fas fa-print me-2"></i>Print Schedule
                    </button>
                    <a href="{{ route('new-algorithm.populations.best-chromosomes', $population->population_id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Conflict Summary -->
    <div id="conflictSummary" class="alert alert-info d-none">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Conflicts Detected:</strong> <span id="conflictCount">0</span> time slot conflicts found in this schedule.
    </div>

    <!-- Schedule Container -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="schedule-wrapper">
                <!-- Header: Days and Times -->
                <div class="schedule-header">
                    <div class="group-header-cell">Group / Time</div>
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
                                <small class="text-muted d-block">{{ $group['students'] }} Students</small>
                                <span class="conflict-indicator badge bg-danger d-none mt-2">
                                    <i class="fas fa-exclamation-triangle"></i> Has Conflicts
                                </span>
                            </div>

                            @foreach($days as $dayIndex => $dayName)
                                <div class="day-sessions-container" data-day="{{ $dayIndex }}">
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

                                        <div class="session-block {{ $session->activity_type === 'Theory' ? 'theory' : 'practical' }}"
                                             style="left: {{ $leftPosition * 60 }}px; width: {{ $width * 60 }}px;"
                                             data-gene-id="{{ $session->gene_id }}"
                                             data-start="{{ $startOffset }}"
                                             data-end="{{ $endOffset }}"
                                             data-subject="{{ $session->subject_name }}">
                                            <div class="session-subject" title="{{ $session->subject_name }}">
                                                {{ $session->subject_name }}
                                            </div>
                                            <div class="session-info">
                                                <div><i class="fas fa-user"></i> {{ $session->instructor_name }}</div>
                                                <div><i class="fas fa-door-open"></i> {{ $session->room_no }}</div>
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

    <!-- Legend -->
    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <h6 class="text-primary mb-3">
                <i class="fas fa-info-circle me-2"></i>Legend & Information
            </h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="legend-box theory me-2"></div>
                        <span>Theory Lectures</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="legend-box practical me-2"></div>
                        <span>Practical/Lab Sessions</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-warning mb-0 py-2">
                        <small>
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <strong>Conflict Detection:</strong> Overlapping sessions are displayed vertically (one below the other)
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Container */
.schedule-wrapper {
    overflow-x: auto;
    overflow-y: visible;
    max-width: 100%;
    border: 1px solid #dee2e6;
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
    font-size: 14px;
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
    transition: min-height 0.3s ease;
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
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.group-name-cell strong {
    color: #2c3e50;
    font-size: 13px;
    display: block;
    margin-bottom: 5px;
}

.conflict-indicator {
    font-size: 10px;
}

.day-sessions-container {
    min-width: 1080px;
    position: relative;
    border-right: 1px solid #dee2e6;
    background:
        repeating-linear-gradient(
            to right,
            transparent,
            transparent 59px,
            #e9ecef 59px,
            #e9ecef 60px
        ),
        repeating-linear-gradient(
            to right,
            transparent,
            transparent 119px,
            #dee2e6 119px,
            #dee2e6 120px
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
    cursor: pointer;
    transition: all 0.3s ease;
    overflow: hidden;
    z-index: 1;
}

.session-block:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 8px 16px rgba(0,0,0,0.25);
    z-index: 50;
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

.session-block.has-conflict {
    border: 2px solid #ffc107;
    box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
}

.session-subject {
    font-weight: bold;
    font-size: 13px;
    margin-bottom: 6px;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.session-info {
    font-size: 10px;
    line-height: 1.6;
    opacity: 0.95;
}

.session-info div {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}

.session-info i {
    width: 14px;
    margin-right: 3px;
}

/* Legend */
.legend-box {
    width: 35px;
    height: 22px;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.legend-box.theory {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.legend-box.practical {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

/* Scrollbar */
.schedule-wrapper::-webkit-scrollbar {
    height: 12px;
}

.schedule-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 6px;
}

.schedule-wrapper::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 6px;
}

.schedule-wrapper::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

/* Print Styles */
@media print {
    .btn, .breadcrumb, .card-header, .legend-box {
        display: none !important;
    }

    .schedule-wrapper {
        overflow: visible;
    }

    .group-header-cell,
    .group-name-cell {
        position: static;
    }

    .session-block {
        page-break-inside: avoid;
    }
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let totalConflicts = 0;

    // معالجة كل يوم في كل قروب
    $('.day-sessions-container').each(function() {
        const container = $(this);
        const sessions = container.find('.session-block').toArray();

        if (sessions.length === 0) return;

        // ترتيب الجلسات حسب وقت البداية
        sessions.sort((a, b) => {
            return parseInt($(a).data('start')) - parseInt($(b).data('start'));
        });

        // كشف التعارضات وترتيب الجلسات عمودياً
        let layers = []; // كل layer يحتوي على جلسات لا تتعارض

        sessions.forEach(session => {
            const $session = $(session);
            const start = parseInt($session.data('start'));
            const end = parseInt($session.data('end'));

            // البحث عن layer مناسب (لا يوجد تعارض)
            let placed = false;
            for (let i = 0; i < layers.length; i++) {
                let hasConflict = false;

                for (let existingSession of layers[i]) {
                    const exStart = parseInt($(existingSession).data('start'));
                    const exEnd = parseInt($(existingSession).data('end'));

                    // فحص التداخل
                    if (start < exEnd && end > exStart) {
                        hasConflict = true;
                        break;
                    }
                }

                if (!hasConflict) {
                    layers[i].push(session);
                    $session.css('top', (i * 120 + 5) + 'px');
                    placed = true;
                    break;
                }
            }

            // إذا لم نجد layer مناسب، نضيف layer جديد
            if (!placed) {
                const newLayerIndex = layers.length;
                layers.push([session]);
                $session.css('top', (newLayerIndex * 120 + 5) + 'px');

                // تمييز كـ conflict
                $session.addClass('has-conflict');
                totalConflicts++;
            }
        });

        // تعديل ارتفاع الـ container حسب عدد الـ layers
        if (layers.length > 1) {
            const newHeight = layers.length * 120 + 10;
            container.css('min-height', newHeight + 'px');
            container.closest('.group-row').css('min-height', newHeight + 'px');

            // إظهار مؤشر التعارض
            container.closest('.group-row').find('.conflict-indicator').removeClass('d-none');
        }
    });

    // عرض ملخص التعارضات
    if (totalConflicts > 0) {
        $('#conflictCount').text(totalConflicts);
        $('#conflictSummary').removeClass('d-none').addClass('alert-danger').removeClass('alert-info');
    }

    // Tooltip للجلسات
    $('.session-block').hover(
        function() {
            $(this).css('z-index', '100');
        },
        function() {
            $(this).css('z-index', '1');
        }
    );

    // Click للتفاصيل
    $('.session-block').click(function() {
        const subject = $(this).data('subject');
        const info = $(this).find('.session-info').html();

        alert('Subject: ' + subject + '\n\n' + $(this).find('.session-info').text());
    });
});
</script>
@endpush
