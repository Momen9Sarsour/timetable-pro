@extends('dashboard.layout')

@push('styles')
    {{-- ** ستايل الجدول الموحد ** --}}
    <style>
        .timetable-wrapper {
            border: 2px solid #333;
            margin-bottom: 2rem;
            padding: 0.5rem;
            border-radius: 5px;
        }

        .timetable-title {
            font-family: Arial, sans-serif;
            font-size: 1.8rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 1rem;
        }

        .timetable-header-row {
            background-color: #f2f2f2;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
        }

        .timetable {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.8rem;
        }

        .timetable th,
        .timetable td {
            border: 1px solid #ccc;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
            min-height: 70px;
        }

        .timetable .day-col {
            font-weight: bold;
            font-size: 1.1rem;
            width: 8%;
            background-color: #f2f2f2;
        }

        .timetable .time-slot-header {
            font-size: 0.7rem;
            font-weight: normal;
            border-bottom: 1px solid #aaa;
            padding-bottom: 2px;
            margin-bottom: 2px;
        }

        .timetable .time-slot-number {
            font-weight: bold;
        }

        .timetable .event-block {
            background-color: #e9fff2;
            border-radius: 0;
            border: none;
            font-size: 0.75rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2px;
        }

        .event-subject {
            font-weight: bold;
            font-size: 0.8rem;
            margin-bottom: 2px;
        }

        .event-details {
            color: #555555;
            line-height: 1.2;
        }

        .event-room {
            font-style: italic;
        }
    </style>
    {{-- Select2 styles if needed for filters --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')
    <div class="main-content">
        <div class="data-entry-container">
            <h1 class="data-entry-header mb-4">Section Timetables</h1>
            @include('dashboard.data-entry.partials._status_messages')

            {{-- قسم الفلاتر --}}
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="fas fa-filter me-2"></i>Filter Section Timetables</h5>
                    <form action="{{ route('dashboard.timetables.sections') }}" method="GET">
                        <div class="row align-items-end">
                            <div class="col-md-5">
                                <label for="filter_plan_id" class="form-label form-label">Academic Plan</label>
                                <select class="form-select form-select select2-filter" id="filter_plan_id" name="plan_id"
                                    data-placeholder="Select a Plan...">
                                    <option value=""></option> {{-- For placeholder --}}
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}"
                                            {{ $request->plan_id == $plan->id ? 'selected' : '' }}>
                                            {{ $plan->plan_no }} - {{ $plan->plan_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="filter_plan_level" class="form-label form-label-sm">Level</label>
                                {{-- <input type="number" class="form-control form-control-sm" name="plan_level"
                                    value="{{ $request->plan_level }}" placeholder="e.g., 1"> --}}
                                <select class="form-select form-select-sm" id="filter_plan_level" name="plan_level">
                                    <option value="">All</option>
                                    @foreach ($levels as $level_option)
                                        {{-- $levels من الكنترولر --}}
                                        <option value="{{ $level_option }}"
                                            {{ $request->plan_level == $level_option ? 'selected' : '' }}>
                                            {{ $level_option }}</option>
                                    @endforeach
                                </select>

                            </div>
                            <div class="col-md-2">
                                <label for="filter_plan_semester" class="form-label form-label-sm">Semester</label>
                                {{-- <input type="number" class="form-control form-control-sm" name="plan_semester"
                                    value="{{ $request->plan_semester }}" placeholder="e.g., 1"> --}}
                                <select class="form-select form-select-sm" id="filter_semester" name="plan_semester">
                                    <option value="">All</option>
                                    @foreach ($semesters as $key => $value)
                                        {{-- $semesters من الكنترولر --}}
                                        <option value="{{ $key }}"
                                            {{ $request->plan_semester == $key ? 'selected' : '' }}>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="filter_academic_year" class="form-label form-label-sm">Year</label>
                                {{-- <input type="number" class="form-control form-control-sm" name="academic_year"
                                    value="{{ $request->academic_year ?? date('Y') }}"
                                    placeholder="e.g., {{ date('Y') }}"> --}}
                                <select class="form-select form-select-sm" id="filter_academic_year" name="academic_year">
                                    <option value="">All</option>
                                    @foreach ($academicYears as $year)
                                        {{-- $academicYears من الكنترولر --}}
                                        <option value="{{ $year }}"
                                            {{ $request->academic_year == $year ? 'selected' : '' }}>{{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1 d-flex">
                                <a href="{{ route('dashboard.timetables.sections') }}"
                                    class="btn btn-outline-secondary btn-sm w-100 mb-1 me-2">Reset</a>
                                <button type="submit" class="btn btn-primary btn-sm w-100">View</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- عرض معلومات السياق --}}
            @if (isset($contextInfo))
                <div class="alert alert-info">{{ $contextInfo }}</div>
            @endif

            {{-- عرض الجداول --}}
            @forelse($timetablesByContext as $contextData)
                @php
                    $contextInfo = $contextData['info'];
                    $timetables = $contextData['timetables'];
                @endphp
                <div class="alert alert-secondary mt-4">
                    <strong>Displaying sections for:</strong>
                    Plan: {{ optional($contextInfo['plan'])->plan_no }} |
                    Level: {{ $contextInfo['level'] }} |
                    Semester: {{ $contextInfo['semester'] }} |
                    Year: {{ $contextInfo['year'] }}
                </div>

                @foreach ($timetables as $timetableData)
                    @php
                        $title = $timetableData['title'];
                        $schedule = $timetableData['schedule'];
                    @endphp
                    <div class="timetable-wrapper">
                        <div class="timetable-title">
                            {{ optional($contextInfo['plan'])->plan_no }} {{ optional($contextInfo['plan'])->plan_name }}
                            - {{ $contextInfo['level'] }} -
                            {{ $title }}
                        </div>
                        {{-- <div class="timetable-title">
                            {{ optional($contextInfo['plan'])->plan_name }} - {{ $title }}
                        </div> --}}
                        @include('dashboard.timetables.partials._timetable_grid', [
                            'schedule' => $schedule,
                            'timeslots' => $timeslots,
                        ])
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="text-muted small">Generated: {{ now()->format('Y-m-d') }}</div>
                            <a href="#" class="btn btn-sm btn-outline-success"><i
                                    class="fas fa-file-download me-1"></i> Export {{ $title }}</a>
                        </div>
                    </div>
                @endforeach

            @empty
                @if (request()->hasAny(['plan_id', 'plan_level', 'plan_semester', 'academic_year']))
                    <div class="alert alert-warning">No generated timetable found for the selected criteria.</div>
                @else
                    <div class="alert alert-info">Please select filter criteria to display timetables. If you want to see
                        all timetables, this might take a moment to load.</div>
                @endif
            @endforelse

        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2-filter').select2({
                theme: 'bootstrap-5',
                placeholder: $(this).data('placeholder')
            });
        });
    </script>
@endpush
