@extends('dashboard.layout')

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div
                    class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <h4 class="page-title mb-1">
                            <i class="fas fa-tachometer-alt text-primary me-2"></i>
                            Dashboard Overview
                        </h4>
                        <p class="text-muted mb-0">Monitor and control your timetable generation system</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('algorithm-control.populations.index') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-sync-alt me-1"></i>
                            <span class="d-none d-sm-inline">Generate Schedule</span>
                            <span class="d-sm-none">Generate</span>
                        </a>
                        <a href="{{ route('algorithm-control.timetable.results.index') }}"
                            class="btn btn-outline-info btn-sm">
                            <i class="fas fa-eye me-1"></i>
                            <span class="d-none d-sm-inline">View Results</span>
                            <span class="d-sm-none">Results</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Messages -->
        @include('dashboard.data-entry.partials._status_messages')

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm stats-card bg-primary bg-opacity-10">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div
                                class="stats-icon bg-primary bg-opacity-20 text-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="stats-number text-primary fw-bold mb-1">{{ $stats['total_subjects'] }}</div>
                                <div class="stats-label text-muted small">Total Subjects</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm stats-card bg-success bg-opacity-10">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div
                                class="stats-icon bg-success bg-opacity-20 text-success rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="stats-number text-success fw-bold mb-1">{{ $stats['total_plans'] }}</div>
                                <div class="stats-label text-muted small">Study Plans</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm stats-card bg-info bg-opacity-10">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div
                                class="stats-icon bg-info bg-opacity-20 text-info rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="stats-number text-info fw-bold mb-1">{{ $stats['total_instructors'] }}</div>
                                <div class="stats-label text-muted small">Instructors</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm stats-card bg-warning bg-opacity-10">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div
                                class="stats-icon bg-warning bg-opacity-20 text-warning rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="stats-number text-warning fw-bold mb-1">{{ $stats['total_rooms'] }}</div>
                                <div class="stats-label text-muted small">Rooms</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm stats-card bg-danger bg-opacity-10">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div
                                class="stats-icon bg-danger bg-opacity-20 text-danger rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="stats-number text-danger fw-bold mb-1">{{ $stats['total_sections'] }}</div>
                                <div class="stats-label text-muted small">Sections</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm stats-card bg-dark bg-opacity-10">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div
                                class="stats-icon bg-dark bg-opacity-20 text-dark rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="stats-number text-dark fw-bold mb-1">{{ $stats['total_departments'] }}</div>
                                <div class="stats-label text-muted small">Departments</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Algorithm Statistics -->
        <div class="row mb-4">
            <div class="col-xl-4 col-lg-6 mb-3">
                <div class="card border-0 shadow-sm stats-card bg-purple bg-opacity-10">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div
                                class="stats-icon bg-purple bg-opacity-20 text-purple rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="fas fa-code-branch"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="stats-number text-purple fw-bold mb-1">{{ $stats['total_populations'] }}</div>
                                <div class="stats-label text-muted small">Total Generations</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-6 mb-3">
                <div class="card border-0 shadow-sm stats-card bg-teal bg-opacity-10">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div
                                class="stats-icon bg-teal bg-opacity-20 text-teal rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="stats-number text-teal fw-bold mb-1">{{ $stats['completed_populations'] }}</div>
                                <div class="stats-label text-muted small">Completed Runs</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-6 mb-3">
                <div class="card border-0 shadow-sm stats-card bg-indigo bg-opacity-10">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div
                                class="stats-icon bg-indigo bg-opacity-20 text-indigo rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="fas fa-dna"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="stats-number text-indigo fw-bold mb-1">{{ $stats['total_chromosomes'] }}</div>
                                <div class="stats-label text-muted small">Total Solutions</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Data Tables - Row 1: Subjects & Plans -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom-0 pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-book text-primary me-2"></i>
                                Recent Subjects
                            </h6>
                            <a href="{{ route('data-entry.subjects.index') }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye me-1"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if ($recentData['subjects']->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-center" style="width: 80px;">Code</th>
                                            <th>Name</th>
                                            <th class="text-center">Hours</th>
                                            <th class="text-center">Department</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentData['subjects'] as $subject)
                                            <tr>
                                                <td class="text-center">
                                                    <span class="badge bg-primary">{{ $subject->subject_no }}</span>
                                                </td>
                                                <td>
                                                    <div class="fw-medium">{{ $subject->subject_name }}</div>
                                                    @if ($subject->subjectType)
                                                        <small
                                                            class="text-muted">{{ $subject->subjectType->subject_type_name }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info">{{ $subject->subject_load }}h</span>
                                                </td>
                                                <td class="text-center">
                                                    @if ($subject->department)
                                                        <small
                                                            class="text-muted">{{ $subject->department->department_name }}</small>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-book text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="text-muted mb-0">No subjects added yet</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom-0 pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-graduation-cap text-success me-2"></i>
                                Recent Study Plans
                            </h6>
                            <a href="{{ route('data-entry.plans.index') }}" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-eye me-1"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if ($recentData['plans']->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-center" style="width: 80px;">Code</th>
                                            <th>Name</th>
                                            <th class="text-center">Year</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentData['plans'] as $plan)
                                            <tr>
                                                <td class="text-center">
                                                    <span class="badge bg-success">{{ $plan->plan_no }}</span>
                                                </td>
                                                <td>
                                                    <div class="fw-medium">{{ $plan->plan_name }}</div>
                                                    @if ($plan->department)
                                                        <small
                                                            class="text-muted">{{ $plan->department->department_name }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info">{{ $plan->year }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if ($plan->is_active)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-graduation-cap text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="text-muted mb-0">No plans added yet</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Data Tables - Row 2: Instructors & Rooms -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom-0 pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-chalkboard-teacher text-info me-2"></i>
                                Recent Instructors
                            </h6>
                            <a href="{{ route('data-entry.instructors.index') }}" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-eye me-1"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if ($recentData['instructors']->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-center" style="width: 80px;">No.</th>
                                            <th>Name</th>
                                            <th class="text-center">Degree</th>
                                            <th class="text-center">Department</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentData['instructors'] as $instructor)
                                            <tr>
                                                <td class="text-center">
                                                    <span class="badge bg-info">{{ $instructor->instructor_no }}</span>
                                                </td>
                                                <td>
                                                    <div class="fw-medium">
                                                        @if ($instructor->user)
                                                            {{ $instructor->user->name }}
                                                        @else
                                                            {{ $instructor->instructor_name }}
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <small
                                                        class="text-muted">{{ $instructor->academic_degree ?? 'N/A' }}</small>
                                                </td>
                                                <td class="text-center">
                                                    @if ($instructor->department)
                                                        <small
                                                            class="text-muted">{{ $instructor->department->department_name }}</small>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-chalkboard-teacher text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="text-muted mb-0">No instructors added yet</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom-0 pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-door-open text-warning me-2"></i>
                                Recent Rooms
                            </h6>
                            <a href="{{ route('data-entry.rooms.index') }}" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-eye me-1"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if ($recentData['rooms']->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-center" style="width: 80px;">No.</th>
                                            <th>Name</th>
                                            <th class="text-center">Capacity</th>
                                            <th class="text-center">Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentData['rooms'] as $room)
                                            <tr>
                                                <td class="text-center">
                                                    <span class="badge bg-warning">{{ $room->room_no }}</span>
                                                </td>
                                                <td>
                                                    <div class="fw-medium">{{ $room->room_name }}</div>
                                                    @if ($room->room_branch)
                                                        <small class="text-muted">{{ $room->room_branch }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info">{{ $room->room_size }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if ($room->roomType)
                                                        <small
                                                            class="text-muted">{{ $room->roomType->room_type_name }}</small>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-door-open text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="text-muted mb-0">No rooms added yet</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Data Tables - Row 3: Populations & Chromosomes -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom-0 pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-code-branch text-purple me-2"></i>
                                Recent Generations
                            </h6>
                            <a href="{{ route('algorithm-control.populations.index') }}"
                                class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye me-1"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if ($recentData['populations']->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-center" style="width: 60px;">ID</th>
                                            <th>Academic Year</th>
                                            <th class="text-center">Semester</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentData['populations'] as $population)
                                            <tr>
                                                <td class="text-center">
                                                    <span class="badge bg-primary">{{ $population->population_id }}</span>
                                                </td>
                                                <td>
                                                    <div class="fw-medium">{{ $population->academic_year }}</div>
                                                    <small class="text-muted">
                                                        @if ($population->crossover)
                                                            {{ $population->crossover->name }}
                                                        @endif
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info">{{ $population->semester }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $statusClasses = [
                                                            'running' => 'bg-warning',
                                                            'completed' => 'bg-success',
                                                            'failed' => 'bg-danger',
                                                            'stopped' => 'bg-secondary',
                                                        ];
                                                        $statusClass =
                                                            $statusClasses[$population->status] ?? 'bg-secondary';
                                                    @endphp
                                                    <span
                                                        class="badge {{ $statusClass }}">{{ ucfirst($population->status) }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-code-branch text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="text-muted mb-0">No generations run yet</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom-0 pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-dna text-indigo me-2"></i>
                                Recent Solutions
                            </h6>
                            {{-- <a href="{{ route('algorithm-control.t --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
