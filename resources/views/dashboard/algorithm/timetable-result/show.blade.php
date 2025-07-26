@extends('dashboard.layout')

@push('styles')
    <style>
        .timetable {
            border-collapse: collapse;
            width: 100%;
        }

        .timetable th,
        .timetable td {
            border: 1px solid #dee2e6;
            padding: 0.5rem;
            text-align: center;
            vertical-align: top;
        }

        .timetable th {
            background-color: #f8f9fa;
        }

        .time-col {
            width: 100px;
            font-weight: bold;
        }

        .slot-cell {
            height: 80px;
        }

        .event-block {
            background-color: #e9f5ff;
            border: 1px solid #b3d7ff;
            border-radius: 5px;
            padding: 5px;
            font-size: 0.8rem;
            margin-bottom: 5px;
            text-align: left;
            cursor: grab;
            /* مؤشر للسحب */
        }

        .event-block.conflict {
            /* *** تلوين التعارضات *** */
            background-color: #f8d7da;
            border-color: #f5c2c7;
        }

        .event-subject {
            font-weight: bold;
        }

        .event-details {
            font-size: 0.75rem;
            color: #555;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="data-entry-container">
            {{-- عرض معلومات عملية التشغيل --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="data-entry-header mb-0">Timetable Result</h1>
                <div class="text-end">
                    <p class="mb-0 small text-muted">Run ID: {{ $chromosome->population->population_id }} | Status:
                        {{ $chromosome->population->status }}</p>
                    <p class="mb-0 small text-muted">Best Fitness (Penalty): {{ $chromosome->penalty_value }}</p>
                    <a href="{{ route('algorithm-control.timetable.results.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Timetables
                    </a>
                </div>
            </div>

            @include('dashboard.data-entry.partials._status_messages')

            {{-- عرض التعارضات --}}
            @if (!empty($conflicts))
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>{{ count($conflicts) }} Conflicts
                            Found</h5>
                    </div>
                    <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                        <ul class="list-group list-group-flush">
                            @foreach ($conflicts as $conflict)
                                <li class="list-group-item list-group-item-danger small">
                                    <strong>{{ $conflict['type'] }}:</strong> {{ $conflict['description'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @else
                <div class="alert alert-success">
                    <strong>Congratulations!</strong> No hard conflicts were found in this schedule.
                </div>
            @endif

            {{-- عرض الجدول --}}
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Generated Schedule</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="timetable">
                        <thead>
                            <tr>
                                <th class="time-col">Time</th>
                                @foreach ($timeslots->keys() as $day)
                                    <th>{{ $day }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // استخراج أوقات البدء الفريدة وترتيبها
                                $uniqueStartTimes = collect($scheduleData)
                                    ->flatMap(fn($daySlots) => array_keys($daySlots))
                                    ->unique()
                                    ->sort();
                            @endphp
                            @foreach ($uniqueStartTimes as $startTime)
                                <tr>
                                    <th class="time-col">{{ \Carbon\Carbon::parse($startTime)->format('h:i A') }}</th>
                                    @foreach ($timeslots->keys() as $day)
                                        <td class="slot-cell">
                                            @if (isset($scheduleData[$day][$startTime]))
                                                @foreach ($scheduleData[$day][$startTime] as $gene)
                                                    @php
                                                        $isConflict = in_array($gene->gene_id, $conflictingGeneIds);
                                                    @endphp
                                                    <div class="event-block {{ $isConflict ? 'conflict' : '' }}">
                                                        <div class="event-subject">
                                                            {{ optional($gene->section->planSubject->plan)->plan_no }}
                                                            {{ optional($gene->section->planSubject)->plan_level }} - semester: 
                                                            {{ optional($gene->section->planSubject)->plan_semester }}
                                                        </div>
                                                        <div class="event-subject">
                                                            {{ optional($gene->section->planSubject->subject)->subject_no }}
                                                             - {{ optional($gene->section->planSubject->subject)->subject_name }}
                                                        </div>
                                                        <div class="event-details">
                                                            {{ optional($gene->instructor->user)->name }}<br>
                                                            Room: {{ optional($gene->room)->room_name }} | Sec:
                                                            {{ optional($gene->section)->section_number }}({{ optional($gene->section)->activity_type }})
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    {{-- سنضيف هنا JavaScript للسحب والإفلات لاحقاً --}}
@endpush
