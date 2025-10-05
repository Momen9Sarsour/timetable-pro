@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-chalkboard-teacher text-success me-2"></i>
                        Instructors Schedules
                    </h4>
                    <p class="text-muted mb-0">View weekly schedules for instructors</p>
                    @if(isset($bestChromosome))
                        <small class="text-muted">
                            <i class="fas fa-dna me-1"></i>
                            Chromosome: #{{ $bestChromosome->chromosome_id }}
                            | Fitness: {{ number_format($bestChromosome->fitness_value, 4) }}
                            | Penalty: {{ $bestChromosome->penalty_value }}
                        </small>
                    @endif
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('new-algorithm.schedules.groups') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-users me-1"></i>
                        <span class="d-none d-sm-inline">Groups</span>
                    </a>
                    <a href="{{ route('new-algorithm.schedules.rooms') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-door-open me-1"></i>
                        <span class="d-none d-sm-inline">Rooms</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @if(session('error') || isset($error))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') ?? $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h6 class="mb-0">
                        <i class="fas fa-filter text-muted me-2"></i>
                        Filters
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('new-algorithm.schedules.instructors') }}" id="filterForm">
                        <div class="row g-3">
                            <!-- Population -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small text-muted">Population</label>
                                <select name="population_id" class="form-select form-select-sm" id="populationSelect">
                                    <option value="">Latest Population</option>
                                    @if(isset($populations))
                                        @foreach($populations as $pop)
                                            <option value="{{ $pop->population_id }}"
                                                {{ request('population_id') == $pop->population_id ? 'selected' : '' }}>
                                                #{{ $pop->population_id }} - {{ $pop->created_at->format('M d, Y H:i') }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <!-- Instructor -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small text-muted">Instructor</label>
                                <select name="instructor_id" class="form-select form-select-sm" id="instructorSelect">
                                    <option value="">All Instructors</option>
                                    @if(isset($instructors))
                                        @foreach($instructors as $instructor)
                                            <option value="{{ $instructor->id }}"
                                                {{ request('instructor_id') == $instructor->id ? 'selected' : '' }}>
                                                {{ $instructor->instructor_name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <!-- Action Buttons -->
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-search me-1"></i>
                                        Apply Filters
                                    </button>
                                    <a href="{{ route('new-algorithm.schedules.instructors') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-redo me-1"></i>
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(isset($instructorSchedules) && $instructorSchedules->count() > 0)
        <!-- Schedules -->
        @foreach($instructorSchedules as $instructorId => $sessions)
            @php
                $first = $sessions->first();

                // Build time slots from 08:00 to 16:00 (every 30 min)
                $timeSlots = [];
                for ($h = 8; $h <= 15; $h++) {
                    for ($m = 0; $m < 60; $m += 30) {
                        $timeSlots[] = sprintf('%02d:%02d', $h, $m);
                    }
                }
                $timeSlots[] = '16:00';

                // Organize sessions by day
                $schedule = [];
                $dayNames = [1 => 'Sunday', 2 => 'Monday', 3 => 'Tuesday', 4 => 'Wednesday', 5 => 'Thursday'];

                foreach ($sessions as $session) {
                    $day = $session->timeslot_day;
                    $start = substr($session->start_time, 0, 5);
                    $end = substr($session->end_time, 0, 5);

                    if (!isset($schedule[$day])) {
                        $schedule[$day] = [];
                    }

                    // Calculate column span based on duration
                    $startIdx = array_search($start, $timeSlots);
                    $endIdx = array_search($end, $timeSlots);
                    $colspan = $endIdx - $startIdx;

                    $schedule[$day][] = [
                        'start' => $start,
                        'end' => $end,
                        'startIdx' => $startIdx,
                        'colspan' => $colspan,
                        'plan' => $session->plan_name,
                        'level' => $session->plan_level,
                        'semester' => $session->plan_semester,
                        'group' => $session->group_numbers ?? 'N/A',
                        'subject' => $session->subject_name,
                        'room' => $session->room_name ?? $session->room_no,
                        'type' => $session->activity_type,
                        'duration' => $session->duration_hours
                    ];
                }
            @endphp

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <!-- Instructor Header -->
                        <div class="card-header bg-success bg-opacity-10 border-bottom">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h6 class="mb-0 text-success">
                                    <i class="fas fa-chalkboard-teacher me-2"></i>
                                    {{ $first->instructor_name }}
                                </h6>
                                <span class="badge bg-success">
                                    {{ $sessions->count() }} Sessions
                                </span>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <!-- Desktop/Tablet Table View -->
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0 schedule-table">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th class="text-center bg-light" style="width: 100px; min-width: 100px;">Day</th>
                                            @foreach($timeSlots as $idx => $time)
                                                @if($idx < count($timeSlots) - 1)
                                                    <th class="text-center small" style="min-width: 120px;">
                                                        {{ $time }}<br>{{ $timeSlots[$idx + 1] }}
                                                    </th>
                                                @endif
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach([1,2,3,4,5] as $dayNum)
                                            <tr>
                                                <td class="text-center align-middle fw-bold bg-light">
                                                    {{ $dayNames[$dayNum] }}
                                                </td>
                                                @php
                                                    $filled = array_fill(0, count($timeSlots) - 1, false);
                                                @endphp

                                                @foreach($timeSlots as $idx => $time)
                                                    @if($idx < count($timeSlots) - 1)
                                                        @if(!$filled[$idx])
                                                            @php
                                                                $sessionFound = false;
                                                                if(isset($schedule[$dayNum])) {
                                                                    foreach($schedule[$dayNum] as $session) {
                                                                        if($session['startIdx'] == $idx) {
                                                                            $sessionFound = $session;
                                                                            // Mark columns as filled
                                                                            for($i = $idx; $i < $idx + $session['colspan']; $i++) {
                                                                                if($i < count($filled)) {
                                                                                    $filled[$i] = true;
                                                                                }
                                                                            }
                                                                            break;
                                                                        }
                                                                    }
                                                                }
                                                            @endphp

                                                            @if($sessionFound)
                                                                <td colspan="{{ $sessionFound['colspan'] }}" class="p-2">
                                                                    <div class="schedule-block {{ $sessionFound['type'] == 'Theory' ? 'bg-info' : 'bg-warning' }} bg-opacity-10 border border-2 border-{{ $sessionFound['type'] == 'Theory' ? 'info' : 'warning' }} rounded p-2 h-100">
                                                                        <div class="small fw-bold text-{{ $sessionFound['type'] == 'Theory' ? 'info' : 'warning' }} mb-1">
                                                                            {{ $sessionFound['plan'] }}
                                                                        </div>
                                                                        <div class="x-small text-muted mb-1">
                                                                            L{{ $sessionFound['level'] }} - S{{ $sessionFound['semester'] }} - G{{ $sessionFound['group'] }}
                                                                        </div>
                                                                        <div class="x-small fw-bold text-dark mb-1">
                                                                            {{ $sessionFound['subject'] }}
                                                                        </div>
                                                                        <div class="x-small text-muted mb-1">
                                                                            <i class="fas fa-door-open me-1"></i>{{ $sessionFound['room'] }}
                                                                        </div>
                                                                        <div class="x-small">
                                                                            <span class="badge badge-sm bg-{{ $sessionFound['type'] == 'Theory' ? 'info' : 'warning' }}">
                                                                                {{ $sessionFound['start'] }} - {{ $sessionFound['end'] }}
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            @else
                                                                <td class="bg-white"></td>
                                                            @endif
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile View -->
                            <div class="d-md-none p-3">
                                @foreach($dayNames as $dayNum => $dayName)
                                    @if(isset($schedule[$dayNum]) && count($schedule[$dayNum]) > 0)
                                        <div class="mb-4">
                                            <h6 class="text-success border-bottom pb-2 mb-3">
                                                <i class="fas fa-calendar-day me-2"></i>{{ $dayName }}
                                            </h6>
                                            @foreach($schedule[$dayNum] as $session)
                                                <div class="card mb-2 border-{{ $session['type'] == 'Theory' ? 'info' : 'warning' }}">
                                                    <div class="card-body p-3">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div>
                                                                <h6 class="card-title mb-1 text-{{ $session['type'] == 'Theory' ? 'info' : 'warning' }}">
                                                                    {{ $session['plan'] }}
                                                                </h6>
                                                                <small class="text-muted">
                                                                    Level {{ $session['level'] }} - Semester {{ $session['semester'] }} - Group {{ $session['group'] }}
                                                                </small>
                                                            </div>
                                                            <span class="badge bg-{{ $session['type'] == 'Theory' ? 'info' : 'warning' }}">
                                                                {{ $session['type'] }}
                                                            </span>
                                                        </div>
                                                        <div class="fw-bold mb-2">
                                                            {{ $session['subject'] }}
                                                        </div>
                                                        <div class="small text-muted mb-1">
                                                            <i class="fas fa-clock me-1"></i>
                                                            {{ $session['start'] }} - {{ $session['end'] }}
                                                        </div>
                                                        <div class="small text-muted">
                                                            <i class="fas fa-door-open me-1"></i>
                                                            {{ $session['room'] }}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <!-- Empty State -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-chalkboard-teacher text-muted opacity-50" style="font-size: 4rem;"></i>
                        <h5 class="mt-3 text-muted">No Schedule Data Found</h5>
                        <p class="text-muted mb-4">
                            @if(request('instructor_id'))
                                Try selecting a different instructor or reset filters.
                            @else
                                Please generate a population first to view schedules.
                            @endif
                        </p>
                        @if(request('instructor_id'))
                            <a href="{{ route('new-algorithm.schedules.instructors') }}" class="btn btn-success">
                                <i class="fas fa-redo me-2"></i>Reset Filters
                            </a>
                        @else
                            <a href="{{ route('new-algorithm.populations.create') }}" class="btn btn-success">
                                <i class="fas fa-plus me-2"></i>Generate Population
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
.x-small {
    font-size: 0.7rem;
}
.schedule-block {
    min-height: 80px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.schedule-table {
    min-width: 1400px;
}
.schedule-table th,
.schedule-table td {
    white-space: nowrap;
    vertical-align: middle;
}
.table-responsive {
    overflow-x: auto;
}
</style>
@endsection
