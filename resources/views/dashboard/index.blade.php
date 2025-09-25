@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
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
                    <a href="{{ route('algorithm-control.timetable.results.index') }}" class="btn btn-outline-info btn-sm">
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
                        <div class="stats-icon bg-primary bg-opacity-20 text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stats-number text-primary fw-bold mb-1">{{ $stats['total_subjects'] }}</div>
                            <div class="stats-label text-muted small">Subjects</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm stats-card bg-success bg-opacity-10">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-20 text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stats-number text-success fw-bold mb-1">{{ $stats['total_plans'] }}</div>
                            <div class="stats-label text-muted small">Plans</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm stats-card bg-info bg-opacity-10">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-20 text-white rounded-circle d-flex align-items-center justify-content-center me-3">
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
                        <div class="stats-icon bg-warning bg-opacity-20 text-white rounded-circle d-flex align-items-center justify-content-center me-3">
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
                        <div class="stats-icon bg-danger bg-opacity-20 text-white rounded-circle d-flex align-items-center justify-content-center me-3">
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
                        <div class="stats-icon bg-dark bg-opacity-20 text-white rounded-circle d-flex align-items-center justify-content-center me-3">
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
                        <div class="stats-icon bg-info bg-opacity-20 text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-code-branch"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stats-number text-white fw-bold mb-1">{{ $stats['total_populations'] }}</div>
                            <div class="stats-label text-muted small">Generations</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-6 mb-3">
            <div class="card border-0 shadow-sm stats-card bg-teal bg-opacity-10">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-20 text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stats-number text-white fw-bold mb-1">{{ $stats['completed_populations'] }}</div>
                            <div class="stats-label text-muted small">Completed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-6 mb-3">
            <div class="card border-0 shadow-sm stats-card bg-indigo bg-opacity-10">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-20 text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-dna"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stats-number text-white fw-bold mb-1">{{ $stats['total_chromosomes'] }}</div>
                            <div class="stats-label text-muted small">Solutions</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Data Tables - Row 1: Subjects & Plans -->
    <div class="row mb-4 row-uniform-tables">
        <div class="col-lg-6 data-table-container">
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
                    @if($recentData['subjects']->count() > 0)
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
                                    @foreach($recentData['subjects'] as $subject)
                                        <tr>
                                            <td class="text-center">
                                                <span class="badge bg-primary">{{ $subject->subject_no }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-medium">{{ $subject->subject_name }}</div>
                                                @if($subject->subjectType)
                                                    <small class="text-muted">{{ $subject->subjectType->subject_type_name }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ $subject->subject_load }}h</span>
                                            </td>
                                            <td class="text-center">
                                                @if($subject->department)
                                                    <small class="text-muted">{{ $subject->department->department_name }}</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-book text-muted"></i>
                            <p class="text-muted mb-0">No subjects added yet</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6 data-table-container">
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
                    @if($recentData['plans']->count() > 0)
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
                                    @foreach($recentData['plans'] as $plan)
                                        <tr>
                                            <td class="text-center">
                                                <span class="badge bg-success">{{ $plan->plan_no }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-medium">{{ $plan->plan_name }}</div>
                                                @if($plan->department)
                                                    <small class="text-muted">{{ $plan->department->department_name }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ $plan->year }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if($plan->is_active)
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
                        <div class="empty-state">
                            <i class="fas fa-graduation-cap text-muted"></i>
                            <p class="text-muted mb-0">No plans added yet</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Data Tables - Row 2: Instructors & Rooms -->
    <div class="row mb-4 row-uniform-tables">
        <div class="col-lg-6 data-table-container">
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
                    @if($recentData['instructors']->count() > 0)
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
                                    @foreach($recentData['instructors'] as $instructor)
                                        <tr>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ $instructor->instructor_no }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-medium">
                                                    @if($instructor->user)
                                                        {{ $instructor->user->name }}
                                                    @else
                                                        {{ $instructor->instructor_name }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <small class="text-muted">{{ $instructor->academic_degree ?? 'N/A' }}</small>
                                            </td>
                                            <td class="text-center">
                                                @if($instructor->department)
                                                    <small class="text-muted">{{ $instructor->department->department_name }}</small>
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
                    @if($recentData['rooms']->count() > 0)
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
                                    @foreach($recentData['rooms'] as $room)
                                        <tr>
                                            <td class="text-center">
                                                <span class="badge bg-warning">{{ $room->room_no }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-medium">{{ $room->room_name }}</div>
                                                @if($room->room_branch)
                                                    <small class="text-muted">{{ $room->room_branch }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ $room->room_size }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if($room->roomType)
                                                    <small class="text-muted">{{ $room->roomType->room_type_name }}</small>
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
                        <a href="{{ route('algorithm-control.populations.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i> View All
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($recentData['populations']->count() > 0)
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
                                    @foreach($recentData['populations'] as $population)
                                        <tr>
                                            <td class="text-center">
                                                <span class="badge bg-primary">{{ $population->population_id }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-medium">{{ $population->academic_year }}</div>
                                                <small class="text-muted">
                                                    @if($population->crossover)
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
                                                        'stopped' => 'bg-secondary'
                                                    ];
                                                    $statusClass = $statusClasses[$population->status] ?? 'bg-secondary';
                                                @endphp
                                                <span class="badge {{ $statusClass }}">{{ ucfirst($population->status) }}</span>
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
                        <a href="{{ route('algorithm-control.timetable.results.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i> View All
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($recentData['chromosomes']->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-center" style="width: 60px;">ID</th>
                                        <th>Generation</th>
                                        <th class="text-center">Fitness</th>
                                        <th class="text-center">Penalty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentData['chromosomes'] as $chromosome)
                                        <tr>
                                            <td class="text-center">
                                                <span class="badge bg-indigo">{{ $chromosome->chromosome_id }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-medium">Gen #{{ $chromosome->generation_number }}</div>
                                                @if($chromosome->is_best_of_generation)
                                                    <small class="text-success">
                                                        <i class="fas fa-crown me-1"></i>Best of Gen
                                                    </small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($chromosome->fitness_value)
                                                    <span class="badge bg-success">{{ number_format($chromosome->fitness_value, 3) }}</span>
                                                @else
                                                    <span class="badge bg-secondary">N/A</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($chromosome->penalty_value)
                                                    <span class="badge bg-danger">{{ $chromosome->penalty_value }}</span>
                                                @else
                                                    <span class="badge bg-success">0</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-dna text-muted mb-2" style="font-size: 2rem;"></i>
                            <p class="text-muted mb-0">No solutions generated yet</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Data Tables - Row 4: Sections & Departments -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-users text-danger me-2"></i>
                            Recent Sections
                        </h6>
                        <a href="{{ route('data-entry.sections.index') }}" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-eye me-1"></i> View All
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($recentData['sections']->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-center" style="width: 60px;">No.</th>
                                        <th>Subject</th>
                                        <th class="text-center">Students</th>
                                        <th class="text-center">Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentData['sections'] as $section)
                                        <tr>
                                            <td class="text-center">
                                                <span class="badge bg-danger">{{ $section->section_number }}</span>
                                            </td>
                                            <td>
                                                @if($section->planSubject && $section->planSubject->subject)
                                                    <div class="fw-medium">{{ $section->planSubject->subject->subject_name }}</div>
                                                    <small class="text-muted">{{ $section->planSubject->subject->subject_no }}</small>
                                                @else
                                                    <span class="text-muted">Unknown Subject</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ $section->student_count }}</span>
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $typeColors = [
                                                        'theory' => 'bg-primary',
                                                        'practical' => 'bg-warning',
                                                        'both' => 'bg-success'
                                                    ];
                                                    $typeColor = $typeColors[$section->activity_type] ?? 'bg-secondary';
                                                @endphp
                                                <span class="badge {{ $typeColor }}">{{ ucfirst($section->activity_type) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-users text-muted mb-2" style="font-size: 2rem;"></i>
                            <p class="text-muted mb-0">No sections created yet</p>
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
                            <i class="fas fa-building text-dark me-2"></i>
                            Recent Departments
                        </h6>
                        <a href="{{ route('data-entry.departments.index') }}" class="btn btn-outline-dark btn-sm">
                            <i class="fas fa-eye me-1"></i> View All
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($recentData['departments']->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-center" style="width: 80px;">No.</th>
                                        <th>Name</th>
                                        <th class="text-center">Instructors</th>
                                        <th class="text-center">Subjects</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentData['departments'] as $department)
                                        <tr>
                                            <td class="text-center">
                                                <span class="badge bg-dark">{{ $department->department_no }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-medium">{{ $department->department_name }}</div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ $department->instructors_count }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary">{{ $department->subjects_count }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-building text-muted mb-2" style="font-size: 2rem;"></i>
                            <p class="text-muted mb-0">No departments added yet</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Last Generation Status -->
    @if($lastSuccessfulRun || $bestChromosome)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom-0 pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-trophy text-warning me-2"></i>
                                Latest Generation Results
                            </h6>
                            @if($bestChromosome)
                                <a href="{{ route('algorithm-control.timetable.result.show', $bestChromosome->chromosome_id) }}" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-eye me-1"></i> View Best Solution
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                @if($lastSuccessfulRun)
                                    <div class="mb-2">
                                        <strong>Generation #{{ $lastSuccessfulRun->population_id }}</strong>
                                        <span class="badge bg-success ms-2">{{ ucfirst($lastSuccessfulRun->status) }}</span>
                                    </div>
                                    <div class="row text-center">
                                        <div class="col-6 col-md-3">
                                            <div class="fw-bold text-primary">{{ $lastSuccessfulRun->academic_year }}</div>
                                            <small class="text-muted">Academic Year</small>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="fw-bold text-success">Semester {{ $lastSuccessfulRun->semester }}</div>
                                            <small class="text-muted">Semester</small>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="fw-bold text-info">{{ $lastSuccessfulRun->population_size }}</div>
                                            <small class="text-muted">Population Size</small>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            @if($lastSuccessfulRun->start_time && $lastSuccessfulRun->end_time)
                                                @php
                                                    $duration = \Carbon\Carbon::parse($lastSuccessfulRun->start_time)->diffForHumans(\Carbon\Carbon::parse($lastSuccessfulRun->end_time), true);
                                                @endphp
                                                <div class="fw-bold text-warning">{{ $duration }}</div>
                                                <small class="text-muted">Duration</small>
                                            @else
                                                <div class="fw-bold text-muted">N/A</div>
                                                <small class="text-muted">Duration</small>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-4 text-center">
                                @if($bestChromosome)
                                    <div class="mb-2">
                                        <div class="display-6 fw-bold text-success mb-1">
                                            @if($bestChromosome->fitness_value)
                                                {{ number_format($bestChromosome->fitness_value, 3) }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                        <div class="text-muted">Best Fitness Score</div>
                                    </div>
                                    @if($bestChromosome->penalty_value !== null)
                                        <div class="mt-2">
                                            <span class="badge {{ $bestChromosome->penalty_value == 0 ? 'bg-success' : 'bg-danger' }} fs-6">
                                                Penalty: {{ $bestChromosome->penalty_value }}
                                            </span>
                                        </div>
                                    @endif
                                @else
                                    <div class="text-muted">
                                        <i class="fas fa-question-circle mb-2" style="font-size: 2rem;"></i>
                                        <div>No best solution available</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
/* Custom color classes for additional variety */
.bg-purple { background-color: #6f42c1 !important; }
.text-purple { color: #6f42c1 !important; }
.bg-teal { background-color: #20c997 !important; }
.text-teal { color: #20c997 !important; }
.bg-indigo { background-color: #6610f2 !important; }
.text-indigo { color: #6610f2 !important; }

.stats-card {
    transition: all 0.2s ease;
    border: 1px solid rgba(0,0,0,0.05);
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.1) !important;
}

.stats-icon {
    width: 38px;
    height: 38px;
}

.stats-number {
    font-size: 1.3rem;
    line-height: 1;
}

.stats-label {
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 1.1;
}

/* Table container improvements for uniform height */
.data-table-container {
    min-height: 400px;
    display: flex;
    flex-direction: column;
}

.data-table-container .card {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.data-table-container .card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.data-table-container .table-responsive {
    flex: 1;
}

/* Ensure tables have uniform height on desktop */
@media (min-width: 992px) {
    .row-uniform-tables .col-lg-6 {
        display: flex;
        flex-direction: column;
    }
    
    .row-uniform-tables .card {
        height: 100%;
        min-height: 420px;
    }
}

/* Table improvements */
.table th {
    font-weight: 600;
    font-size: 0.82rem;
    padding: 10px 8px;
}

.table td {
    vertical-align: middle;
    padding: 9px 8px;
    font-size: 0.85rem;
}

.badge {
    font-size: 0.68rem;
    font-weight: 500;
    padding: 0.3rem 0.6rem;
}

/* Card hover effects */
.card {
    transition: all 0.2s ease;
}

.card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
}

/* Button improvements */
.btn-sm {
    font-size: 0.8rem;
    padding: 0.375rem 0.75rem;
}

.btn:hover {
    transform: translateY(-1px);
}

/* Empty state improvements */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 200px;
    text-align: center;
}

.empty-state i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .stats-number {
        font-size: 1.1rem;
    }

    .stats-icon {
        width: 32px;
        height: 32px;
    }
    
    .stats-label {
        font-size: 0.7rem;
    }

    .table th, .table td {
        padding: 8px 6px;
        font-size: 0.8rem;
    }

    .card-body {
        padding: 1rem;
    }
    
    .data-table-container {
        min-height: auto;
    }
    
    .row-uniform-tables .card {
        min-height: auto;
        margin-bottom: 1rem;
    }
}

@media (max-width: 576px) {
    .stats-card .card-body {
        padding: 0.75rem;
    }

    .badge {
        font-size: 0.65rem;
    }
    
    .stats-label {
        font-size: 0.68rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to table rows
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(0,123,255,0.05)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });

    // Add click animation to stats cards
    const statsCards = document.querySelectorAll('.stats-card');
    statsCards.forEach(card => {
        card.addEventListener('click', function() {
            this.style.transform = 'translateY(-2px) scale(0.98)';
            setTimeout(() => {
                this.style.transform = 'translateY(-2px)';
            }, 150);
        });
    });

    console.log('✅ Enhanced Dashboard with data tables initialized');
});
</script>
@endpush