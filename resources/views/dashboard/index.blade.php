/* Responsive Adjustments */
@media (max-width: 768px) {
    .stats-number {
        font-size: 1rem;
    }

    .stats-icon {
        width: 26px;
        height: 26px;
    }

    .schedule-table {
        font-size: 0.7rem;
    }

    .course-@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-tachometer-alt text-primary me-2" style="font-size: 1.2rem;"></i>
                        Dashboard Overview
                    </h4>
                    <p class="text-muted mb-0">Monitor and control your timetable generation system</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#generateScheduleModal">
                        <i class="fas fa-sync-alt me-1" style="font-size: 0.8rem;"></i>
                        <span class="d-none d-sm-inline">Generate Schedule</span>
                        <span class="d-sm-none">Generate</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    <!-- Statistics Cards - Draggable -->
    <div class="row mb-4" id="statsContainer">
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm stats-card bg-primary bg-opacity-10 draggable-widget" draggable="true" data-widget="courses">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-20 text-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-book" style="font-size: 1rem;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stats-number text-primary fw-bold mb-1">324</div>
                            <div class="stats-label text-muted small">Total Courses</div>
                        </div>
                        <div class="drag-handle text-muted opacity-50">
                            <i class="fas fa-grip-vertical" style="font-size: 0.7rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm stats-card bg-success bg-opacity-10 draggable-widget" draggable="true" data-widget="instructors">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-20 text-success rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-chalkboard-teacher" style="font-size: 1rem;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stats-number text-success fw-bold mb-1">48</div>
                            <div class="stats-label text-muted small">Active Instructors</div>
                        </div>
                        <div class="drag-handle text-muted opacity-50">
                            <i class="fas fa-grip-vertical" style="font-size: 0.7rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm stats-card bg-info bg-opacity-10 draggable-widget" draggable="true" data-widget="rooms">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-20 text-info rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-door-open" style="font-size: 1rem;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stats-number text-info fw-bold mb-1">12</div>
                            <div class="stats-label text-muted small">Available Rooms</div>
                        </div>
                        <div class="drag-handle text-muted opacity-50">
                            <i class="fas fa-grip-vertical" style="font-size: 0.7rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm stats-card bg-warning bg-opacity-10 draggable-widget" draggable="true" data-widget="conflicts">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-20 text-warning rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-exclamation-triangle" style="font-size: 1rem;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stats-number text-warning fw-bold mb-1">3</div>
                            <div class="stats-label text-muted small">Current Conflicts</div>
                        </div>
                        <div class="drag-handle text-muted opacity-50">
                            <i class="fas fa-grip-vertical" style="font-size: 0.7rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Generation Progress Card - Draggable -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm draggable-widget" draggable="true" data-widget="progress">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-code-branch text-primary me-2" style="font-size: 0.9rem;"></i>
                            <h6 class="card-title mb-0">Generation Progress</h6>
                            <div class="drag-handle text-muted opacity-50 ms-2">
                                <i class="fas fa-grip-vertical" style="font-size: 0.7rem;"></i>
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-outline-warning btn-sm" disabled>
                                <i class="fas fa-pause me-1" style="font-size: 0.8rem;"></i>
                                <span class="d-none d-sm-inline">Pause</span>
                            </button>
                            <button class="btn btn-outline-danger btn-sm" disabled>
                                <i class="fas fa-stop me-1" style="font-size: 0.8rem;"></i>
                                <span class="d-none d-sm-inline">Stop</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="generation-info mb-3">
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-2">
                                    <span class="fw-medium">Generation #45</span>
                                    <span class="badge bg-success bg-opacity-10 text-success">
                                        <i class="fas fa-chart-line me-1" style="font-size: 0.7rem;"></i>
                                        Fitness Score: 0.89
                                    </span>
                                </div>

                                <div class="progress mb-2" style="height: 8px;">
                                    <div class="progress-bar bg-gradient progress-bar-striped progress-bar-animated"
                                         role="progressbar" style="width: 89%" aria-valuenow="89" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between text-muted small">
                                    <span>Progress: 89%</span>
                                    <span>Time remaining: 2m 15s</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="generation-status">
                                    <div class="status-indicator mx-auto mb-2">
                                        <i class="fas fa-sync-alt fa-spin text-primary" style="font-size: 1.5rem;"></i>
                                    </div>
                                    <h6 class="text-primary mb-0">Processing...</h6>
                                    <small class="text-muted">Optimizing schedule</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row" id="mainContentRow">
        <!-- Weekly Schedule Preview - Draggable -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm draggable-widget" draggable="true" data-widget="schedule">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-calendar-week text-primary me-2" style="font-size: 0.9rem;"></i>
                            <h6 class="card-title mb-0">Weekly Schedule Preview</h6>
                            <div class="drag-handle text-muted opacity-50 ms-2">
                                <i class="fas fa-grip-vertical" style="font-size: 0.7rem;"></i>
                            </div>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary">Live Preview</span>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 schedule-table">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center" style="width: 100px;">Time</th>
                                    <th class="text-center">Monday</th>
                                    <th class="text-center">Tuesday</th>
                                    <th class="text-center">Wednesday</th>
                                    <th class="text-center">Thursday</th>
                                    <th class="text-center">Friday</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-center fw-medium text-muted">8:00 AM</td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                </tr>
                                <tr>
                                    <td class="text-center fw-medium text-muted">9:00 AM</td>
                                    <td class="schedule-cell">
                                        <div class="course-block bg-primary bg-opacity-10 border-start border-primary border-3 draggable-course" draggable="true" data-course="CS101">
                                            <div class="course-code fw-bold text-primary">CS101</div>
                                            <div class="course-room small text-muted">Room 301</div>
                                            <div class="course-instructor small">Dr. Smith</div>
                                        </div>
                                    </td>
                                    <td class="schedule-cell drop-zone"></td>
                                    <td class="schedule-cell drop-zone"></td>
                                    <td class="schedule-cell drop-zone"></td>
                                    <td class="schedule-cell drop-zone"></td>
                                </tr>
                                <tr>
                                    <td class="text-center fw-medium text-muted">10:00 AM</td>
                                    <td class="schedule-cell drop-zone"></td>
                                    <td class="schedule-cell">
                                        <div class="course-block bg-success bg-opacity-10 border-start border-success border-3 draggable-course" draggable="true" data-course="MATH201">
                                            <div class="course-code fw-bold text-success">MATH201</div>
                                            <div class="course-room small text-muted">Room 205</div>
                                            <div class="course-instructor small">Dr. Johnson</div>
                                        </div>
                                    </td>
                                    <td class="schedule-cell drop-zone"></td>
                                    <td class="schedule-cell drop-zone"></td>
                                    <td class="schedule-cell drop-zone"></td>
                                </tr>
                                <tr>
                                    <td class="text-center fw-medium text-muted">11:00 AM</td>
                                    <td class="schedule-cell drop-zone"></td>
                                    <td class="schedule-cell drop-zone"></td>
                                    <td class="schedule-cell">
                                        <div class="course-block bg-info bg-opacity-10 border-start border-info border-3 draggable-course" draggable="true" data-course="PHYS301">
                                            <div class="course-code fw-bold text-info">PHYS301</div>
                                            <div class="course-room small text-muted">Lab 101</div>
                                            <div class="course-instructor small">Dr. Brown</div>
                                        </div>
                                    </td>
                                    <td class="schedule-cell drop-zone"></td>
                                    <td class="schedule-cell drop-zone"></td>
                                </tr>
                                <tr>
                                    <td class="text-center fw-medium text-muted">12:00 PM</td>
                                    <td class="schedule-cell" colspan="5">
                                        <div class="text-center text-muted py-2">
                                            <i class="fas fa-utensils me-1" style="font-size: 0.8rem;"></i>
                                            Lunch Break
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center fw-medium text-muted">1:00 PM</td>
                                    <td class="schedule-cell drop-zone"></td>
                                    <td class="schedule-cell drop-zone"></td>
                                    <td class="schedule-cell drop-zone"></td>
                                    <td class="schedule-cell">
                                        <div class="course-block bg-warning bg-opacity-10 border-start border-warning border-3 draggable-course" draggable="true" data-course="ENG401">
                                            <div class="course-code fw-bold text-warning">ENG401</div>
                                            <div class="course-room small text-muted">Room 102</div>
                                            <div class="course-instructor small">Dr. Wilson</div>
                                        </div>
                                    </td>
                                    <td class="schedule-cell drop-zone"></td>
                                </tr>
                                <tr>
                                    <td class="text-center fw-medium text-muted">2:00 PM</td>
                                    <td class="schedule-cell drop-zone"></td>
                                    <td class="schedule-cell drop-zone"></td>
                                    <td class="schedule-cell drop-zone"></td>
                                    <td class="schedule-cell drop-zone"></td>
                                    <td class="schedule-cell drop-zone"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar - Draggable Widgets -->
        <div class="col-lg-4" id="rightSidebar">
            <!-- Current Conflicts Card -->
            <div class="card border-0 shadow-sm mb-4 draggable-widget" draggable="true" data-widget="conflicts-detail">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle text-warning me-2" style="font-size: 0.9rem;"></i>
                            <h6 class="card-title mb-0">Current Conflicts</h6>
                            <div class="drag-handle text-muted opacity-50 ms-2">
                                <i class="fas fa-grip-vertical" style="font-size: 0.7rem;"></i>
                            </div>
                        </div>
                        <span class="badge bg-danger bg-opacity-10 text-danger">3 Issues</span>
                    </div>
                </div>

                <div class="card-body">
                    <div class="conflict-list">
                        <div class="conflict-item d-flex align-items-start p-3 mb-2 bg-danger bg-opacity-5 rounded border-start border-danger border-3">
                            <div class="conflict-icon me-3">
                                <i class="fas fa-door-open text-danger" style="font-size: 0.8rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="conflict-title fw-medium mb-1">Room Conflict</div>
                                <div class="conflict-description text-muted small">
                                    CS101 and MATH201 scheduled in Room 301 at the same time
                                </div>
                            </div>
                        </div>

                        <div class="conflict-item d-flex align-items-start p-3 mb-2 bg-warning bg-opacity-5 rounded border-start border-warning border-3">
                            <div class="conflict-icon me-3">
                                <i class="fas fa-user-tie text-warning" style="font-size: 0.8rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="conflict-title fw-medium mb-1">Instructor Availability</div>
                                <div class="conflict-description text-muted small">
                                    Dr. Smith is scheduled for two classes simultaneously
                                </div>
                            </div>
                        </div>

                        <div class="conflict-item d-flex align-items-start p-3 mb-0 bg-info bg-opacity-5 rounded border-start border-info border-3">
                            <div class="conflict-icon me-3">
                                <i class="fas fa-clock text-info" style="font-size: 0.8rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="conflict-title fw-medium mb-1">Time Constraint</div>
                                <div class="conflict-description text-muted small">
                                    DHYS101 scheduled outside of allowed time slot
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="card border-0 shadow-sm draggable-widget" draggable="true" data-widget="quick-actions">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-bolt text-primary me-2" style="font-size: 0.9rem;"></i>
                        <h6 class="card-title mb-0">Quick Actions</h6>
                        <div class="drag-handle text-muted opacity-50 ms-2">
                            <i class="fas fa-grip-vertical" style="font-size: 0.7rem;"></i>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('algorithm-control.timetable.results.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-2" style="font-size: 0.8rem;"></i>View Results
                        </a>
                        <a href="{{ route('data-entry.subjects.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-book me-2" style="font-size: 0.8rem;"></i>Manage Subjects
                        </a>
                        <a href="{{ route('data-entry.instructors.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-chalkboard-teacher me-2" style="font-size: 0.8rem;"></i>Manage Instructors
                        </a>
                        <a href="{{ route('data-entry.rooms.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-door-open me-2" style="font-size: 0.8rem;"></i>Manage Rooms
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Generate Schedule Modal -->
<div class="modal fade" id="generateScheduleModal" tabindex="-1" aria-labelledby="generateScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="generateScheduleModalLabel">
                    <i class="fas fa-cogs me-2" style="font-size: 1rem;"></i>
                    Timetable Generation Settings
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('algorithm-control.timetable.generate.start') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="alert alert-info border-0 mb-4">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle me-2 mt-1" style="font-size: 0.8rem;"></i>
                            <div>
                                <small>Configure the parameters for this generation run. Different settings can affect the speed and quality of the result.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Settings -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="academic_year" class="form-label fw-medium">
                                <i class="fas fa-calendar-alt text-muted me-1" style="font-size: 0.8rem;"></i>
                                Academic Year <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control @error('academic_year') is-invalid @enderror"
                                id="academic_year" name="academic_year" value="{{ old('academic_year', date('Y')) }}"
                                required placeholder="e.g., 2025">
                            @error('academic_year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Current academic year</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="semester" class="form-label fw-medium">
                                <i class="fas fa-graduation-cap text-muted me-1" style="font-size: 0.8rem;"></i>
                                Semester <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('semester') is-invalid @enderror" id="semester" name="semester" required>
                                <option value="1" {{ old('semester') == 1 ? 'selected' : '' }}>First Semester</option>
                                <option value="2" {{ old('semester') == 2 ? 'selected' : '' }}>Second Semester</option>
                                <option value="3" {{ old('semester') == 3 ? 'selected' : '' }}>Summer Semester</option>
                            </select>
                            @error('semester')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Algorithm Parameters -->
                    <h6 class="mb-3">
                        <i class="fas fa-sliders-h text-primary me-1" style="font-size: 0.9rem;"></i>
                        Algorithm Parameters
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="setting_population_size" class="form-label fw-medium">Population Size</label>
                            <input type="number" class="form-control" id="setting_population_size"
                                name="population_size" value="{{ config('algorithm.settings.population_size', 10) }}"
                                required min="10" step="10">
                            <div class="form-text">Number of schedules per generation</div>
                        </div>
                        <input hidden type="number" class="form-control" id="setting_max_generations"
                            name="max_generations" value="{{ config('algorithm.settings.max_generations', 200) }}"
                            required min="10" step="10">
                        <div class="col-md-4">
                            <label for="setting_elitism_count_chromosomes" class="form-label fw-medium">Elitism count chromosomes </label>
                            <input type="number" class="form-control" id="setting_elitism_count_chromosomes"
                                name="elitism_count_chromosomes" value="{{ config('algorithm.settings.elitism_count_chromosomes', 5) }}"
                                required min="1" step="1">
                            <div class="form-text">When to stop if no solution found</div>
                        </div>
                        <div class="col-md-4">
                            <label for="setting_mutation_rate" class="form-label fw-medium">Mutation Rate</label>
                            <input type="number" class="form-control" id="setting_mutation_rate"
                                name="mutation_rate" value="{{ config('algorithm.settings.mutation_rate', 0.05) }}"
                                required min="0" max="1" step="0.01">
                            <div class="form-text">e.g., 0.01 for 1%</div>
                        </div>
                    </div>

                    <!-- Duration Settings -->
                    <h6 class="mb-3">
                        <i class="fas fa-clock text-primary me-1" style="font-size: 0.9rem;"></i>
                        Lecture Duration Settings
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="setting_theory_credit_to_slots" class="form-label fw-medium">Theory Credit Slots</label>
                            <input type="number" class="form-control @error('theory_credit_to_slots') is-invalid @enderror"
                                id="setting_theory_credit_to_slots" name="theory_credit_to_slots"
                                value="{{ old('theory_credit_to_slots', 1) }}" required min="1" max="4">
                            <div class="form-text">Consecutive timeslots per theoretical credit hour</div>
                            @error('theory_credit_to_slots')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="setting_practical_credit_to_slots" class="form-label fw-medium">Practical Credit Slots</label>
                            <input type="number" class="form-control @error('practical_credit_to_slots') is-invalid @enderror"
                                id="setting_practical_credit_to_slots" name="practical_credit_to_slots"
                                value="{{ old('practical_credit_to_slots', 2) }}" required min="1" max="4">
                            <div class="form-text">Consecutive timeslots per practical credit hour</div>
                            @error('practical_credit_to_slots')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Method Selection -->
                    <h6 class="mb-3">
                        <i class="fas fa-cogs text-primary me-1" style="font-size: 0.9rem;"></i>
                        Algorithm Methods
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="setting_crossover_type_id" class="form-label fw-medium">Crossover Method</label>
                            <select class="form-select" id="setting_crossover_type_id" name="crossover_type_id" required>
                                @foreach (\App\Models\CrossoverType::where('is_active', true)->get() as $type)
                                    <option value="{{ $type->crossover_id }}" title="{{ $type->description }}"
                                        {{ config('algorithm.settings.crossover_type_id') == $type->crossover_id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="setting_selection_type_id" class="form-label fw-medium">Selection Method</label>
                            <select class="form-select" id="setting_selection_type_id" name="selection_type_id" required>
                                @foreach (\App\Models\SelectionType::where('is_active', true)->get() as $type)
                                    <option value="{{ $type->selection_type_id }}" title="{{ $type->description }}"
                                        {{ config('algorithm.settings.selection_type_id') == $type->selection_type_id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Advanced Settings -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="crossover_rate" class="form-label fw-medium">Crossover Rate</label>
                            <input type="number" class="form-control" id="crossover_rate" name="crossover_rate"
                                value="{{ config('algorithm.settings.crossover_rate', 0.8) }}"
                                step="0.1" min="0" max="1" required>
                            <div class="form-text">Probability of crossover</div>
                        </div>
                        <div class="col-md-4">
                            <label for="setting_mutation_type_id" class="form-label fw-medium">Mutation Type</label>
                            <select class="form-select" id="setting_mutation_type_id" name="mutation_type_id" required>
                                @foreach (\App\Models\MutationType::where('is_active', true)->get() as $type)
                                    <option value="{{ $type->mutation_id }}" title="{{ $type->description }}"
                                        {{ config('algorithm.settings.mutation_type_id') == $type->mutation_id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="setting_selection_size" class="form-label fw-medium">Tournament Size</label>
                            <input type="number" class="form-control" id="setting_selection_size"
                                name="selection_size" value="{{ config('algorithm.settings.selection_size', 3) }}"
                                required min="2" max="10">
                            <div class="form-text">Competitors per tournament</div>
                        </div>
                    </div>

                    <!-- Stop Condition -->
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                            id="setting_stop_at_first_valid" name="stop_at_first_valid" value="1"
                            {{ config('algorithm.settings.stop_at_first_valid', true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-medium" for="setting_stop_at_first_valid">
                            <i class="fas fa-flag-checkered text-success me-1" style="font-size: 0.8rem;"></i>
                            Stop at First Valid Solution
                        </label>
                        <div class="form-text">Stops when finding a schedule with zero hard-constraint violations</div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1" style="font-size: 0.8rem;"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-play me-1" style="font-size: 0.8rem;"></i>Start Generation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Drop Zone Indicator -->
<div id="dropIndicator" class="position-fixed d-none" style="background: rgba(13, 110, 253, 0.1); border: 2px dashed #0d6efd; border-radius: 8px; z-index: 9999; pointer-events: none;">
    <div class="d-flex align-items-center justify-content-center h-100">
        <span class="text-primary fw-medium">
            <i class="fas fa-arrows-alt me-1"></i>
            Drop here to rearrange
        </span>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Dashboard Specific Styles with Smaller Icons */
.stats-card {
    transition: all 0.2s ease;
    border: 1px solid rgba(0,0,0,0.05);
    cursor: grab;
}

.stats-card:active {
    cursor: grabbing;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.1) !important;
}

.stats-card.dragging {
    opacity: 0.7;
    transform: rotate(3deg) scale(1.02);
    z-index: 1000;
}

.stats-icon {
    width: 40px;
    height: 40px;
}

.stats-number {
    font-size: 1.4rem;
    line-height: 1;
}

.stats-label {
    font-size: 0.8rem;
    font-weight: 500;
}

/* Drag Handle Styling */
.drag-handle {
    cursor: grab;
    transition: opacity 0.2s ease;
}

.drag-handle:hover {
    opacity: 1 !important;
}

.draggable-widget {
    cursor: grab;
    transition: all 0.2s ease;
}

.draggable-widget:active {
    cursor: grabbing;
}

.draggable-widget.dragging {
    opacity: 0.7;
    transform: rotate(2deg) scale(1.01);
    z-index: 1000;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2) !important;
}

/* Generation Progress Styles */
.generation-info {
    position: relative;
}

.status-indicator {
    width: 50px;
    height: 50px;
}

.progress {
    border-radius: 8px;
    overflow: hidden;
}

.progress-bar {
    background: linear-gradient(45deg, var(--primary-color), var(--primary-light)) !important;
}

/* Schedule Table Styles with Drag & Drop */
.schedule-table {
    font-size: 0.875rem;
}

.schedule-table th {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    font-weight: 600;
    border: 1px solid #dee2e6;
    padding: 12px 8px;
}

.schedule-cell {
    height: 60px;
    padding: 4px;
    vertical-align: top;
    border: 1px solid #e9ecef;
    position: relative;
    transition: all 0.2s ease;
}

.schedule-cell.drop-zone {
    background: rgba(13, 110, 253, 0.05);
}

.schedule-cell.drag-over {
    background: rgba(13, 110, 253, 0.15);
    border-color: #0d6efd;
    box-shadow: inset 0 0 8px rgba(13, 110, 253, 0.3);
}

.course-block {
    padding: 6px 8px;
    border-radius: 6px;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    transition: all 0.2s ease;
    cursor: grab;
}

.course-block:active {
    cursor: grabbing;
}

.course-block:hover {
    transform: scale(1.02);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.course-block.dragging {
    opacity: 0.8;
    transform: rotate(5deg) scale(1.05);
    z-index: 1000;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}

.draggable-course {
    cursor: grab;
}

.draggable-course:active {
    cursor: grabbing;
}

.course-code {
    font-size: 0.8rem;
    line-height: 1.2;
}

.course-room,
.course-instructor {
    font-size: 0.7rem;
    line-height: 1;
}

/* Conflict Styles */
.conflict-item {
    transition: all 0.2s ease;
}

.conflict-item:hover {
    transform: translateX(2px);
}

.conflict-icon {
    width: 20px;
    text-align: center;
}

.conflict-title {
    font-size: 0.85rem;
}

.conflict-description {
    font-size: 0.8rem;
    line-height: 1.3;
}

/* Modal Enhancements */
.modal-content {
    border-radius: 12px;
}

.modal-header.bg-primary {
    border-radius: 12px 12px 0 0;
}

.form-check-input:checked {
    background-color: var(--success-color);
    border-color: var(--success-color);
}

/* Quick Actions */
.btn-outline-primary:hover,
.btn-outline-secondary:hover {
    transform: translateY(-1px);
}

/* Drop Zone Styling */
.drop-zone-active {
    border: 2px dashed #0d6efd !important;
    background: rgba(13, 110, 253, 0.1) !important;
}

/* Sortable placeholder */
.sortable-placeholder {
    background: rgba(13, 110, 253, 0.1);
    border: 2px dashed #0d6efd;
    border-radius: 8px;
    margin: 5px 0;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .stats-number {
        font-size: 1.1rem;
    }

    .stats-icon {
        width: 32px;
        height: 32px;
    }

    .schedule-table {
        font-size: 0.75rem;
    }

    .course-block {
        padding: 4px 6px;
    }

    .course-code {
        font-size: 0.7rem;
    }

    .course-room,
    .course-instructor {
        font-size: 0.65rem;
    }

    .conflict-item {
        padding: 0.75rem !important;
        margin-bottom: 0.5rem !important;
    }

    .drag-handle {
        font-size: 0.6rem !important;
    }
}

@media (max-width: 576px) {
    .stats-card .card-body {
        padding: 0.75rem;
    }

    .schedule-cell {
        height: 50px;
        padding: 2px;
    }

    .modal-body {
        max-height: 60vh !important;
        padding: 1rem !important;
    }
}

/* Animation for generation status */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.status-indicator .fa-sync-alt {
    animation: pulse 2s infinite;
}

/* Enhanced form styling */
.form-control:focus,
.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(var(--primary-color), 0.1);
}

.form-check-input:focus {
    box-shadow: 0 0 0 3px rgba(var(--success-color), 0.1);
}

/* Better spacing for form elements */
.form-text {
    margin-top: 0.25rem;
    font-size: 0.8rem;
}

/* Enhanced alert styling */
.alert {
    border-radius: 8px;
}

.alert-info {
    background-color: rgba(13, 202, 240, 0.1);
    border-color: rgba(13, 202, 240, 0.2);
    color: #0dcaf0;
}

/* Card hover effects */
.card {
    transition: all 0.2s ease;
}

.card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
}

/* Better badge styling */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.375rem 0.75rem;
}

/* Progress bar animation */
.progress-bar-animated {
    animation: progress-bar-stripes 1s linear infinite;
}

@keyframes progress-bar-stripes {
    0% { background-position: 1rem 0; }
    100% { background-position: 0 0; }
}

/* Ghost element for drag & drop */
.drag-ghost {
    background: rgba(13, 110, 253, 0.1);
    border: 2px dashed #0d6efd;
    border-radius: 8px;
    opacity: 0.7;
}

/* Improved visual feedback */
.draggable-widget:hover .drag-handle {
    opacity: 0.8;
}

.stats-card:hover .drag-handle {
    opacity: 1;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize drag and drop functionality
    initializeDragAndDrop();
    initializeCourseDragDrop();

    // Form validation for the modal
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Initialize other dashboard features
    initializeDashboardUpdates();

    function initializeDragAndDrop() {
        const draggableWidgets = document.querySelectorAll('.draggable-widget');
        let draggedElement = null;
        let placeholder = null;

        draggableWidgets.forEach(widget => {
            widget.addEventListener('dragstart', function(e) {
                draggedElement = this;
                this.classList.add('dragging');

                // Create placeholder
                placeholder = document.createElement('div');
                placeholder.className = 'sortable-placeholder';
                placeholder.style.height = this.offsetHeight + 'px';

                // Set drag effect
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', this.outerHTML);

                setTimeout(() => {
                    this.style.opacity = '0.5';
                }, 0);
            });

            widget.addEventListener('dragend', function(e) {
                this.classList.remove('dragging');
                this.style.opacity = '';

                if (placeholder && placeholder.parentNode) {
                    placeholder.parentNode.removeChild(placeholder);
                }

                // Clean up any remaining drop indicators
                document.querySelectorAll('.drop-zone-active').forEach(zone => {
                    zone.classList.remove('drop-zone-active');
                });
            });

            widget.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';

                if (this !== draggedElement) {
                    const afterElement = getDragAfterElement(this.parentNode, e.clientY);
                    if (afterElement == null) {
                        this.parentNode.appendChild(placeholder);
                    } else {
                        this.parentNode.insertBefore(placeholder, afterElement);
                    }
                }
            });

            widget.addEventListener('drop', function(e) {
                e.preventDefault();

                if (this !== draggedElement && placeholder.parentNode) {
                    placeholder.parentNode.insertBefore(draggedElement, placeholder);

                    // Save new layout to localStorage
                    saveLayoutState();

                    showToast('Widget moved successfully', 'success');
                }
            });
        });

        // Handle container drop zones
        const containers = document.querySelectorAll('#statsContainer, #rightSidebar, #mainContentRow');
        containers.forEach(container => {
            container.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('drop-zone-active');
            });

            container.addEventListener('dragleave', function(e) {
                if (!this.contains(e.relatedTarget)) {
                    this.classList.remove('drop-zone-active');
                }
            });

            container.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drop-zone-active');
            });
        });
    }

    function initializeCourseDragDrop() {
        const draggableCourses = document.querySelectorAll('.draggable-course');
        const dropZones = document.querySelectorAll('.drop-zone');
        let draggedCourse = null;

        draggableCourses.forEach(course => {
            course.addEventListener('dragstart', function(e) {
                draggedCourse = this;
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', this.dataset.course);
            });

            course.addEventListener('dragend', function(e) {
                this.classList.remove('dragging');

                // Clean up drop zones
                dropZones.forEach(zone => {
                    zone.classList.remove('drag-over');
                });
            });
        });

        dropZones.forEach(zone => {
            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                this.classList.add('drag-over');
            });

            zone.addEventListener('dragleave', function(e) {
                if (!this.contains(e.relatedTarget)) {
                    this.classList.remove('drag-over');
                }
            });

            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');

                if (draggedCourse && !this.querySelector('.course-block')) {
                    // Move the course to new time slot
                    const originalParent = draggedCourse.parentNode;
                    this.appendChild(draggedCourse);

                    // Clear original cell if it was a drop zone
                    if (originalParent.classList.contains('drop-zone')) {
                        originalParent.innerHTML = '';
                        originalParent.classList.add('drop-zone');
                    }

                    // Remove drop-zone class from new parent
                    this.classList.remove('drop-zone');

                    showToast(`${draggedCourse.dataset.course} moved to new time slot`, 'info');
                }
            });
        });
    }

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.draggable-widget:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function saveLayoutState() {
        const layout = {
            stats: Array.from(document.querySelectorAll('#statsContainer .draggable-widget')).map(el => el.dataset.widget),
            sidebar: Array.from(document.querySelectorAll('#rightSidebar .draggable-widget')).map(el => el.dataset.widget)
        };

        localStorage.setItem('dashboardLayout', JSON.stringify(layout));
    }

    function initializeDashboardUpdates() {
        // Simulate real-time updates for demo
        function updateProgress() {
            const progressBar = document.querySelector('.progress-bar');
            const progressText = document.querySelector('.d-flex.justify-content-between small:first-child');
            const timeText = document.querySelector('.d-flex.justify-content-between small:last-child');

            if (progressBar) {
                let currentProgress = parseInt(progressBar.style.width) || 89;
                if (currentProgress < 100) {
                    currentProgress += Math.random() * 2;
                    progressBar.style.width = currentProgress + '%';
                    progressBar.setAttribute('aria-valuenow', currentProgress);

                    if (progressText) {
                        progressText.textContent = `Progress: ${Math.round(currentProgress)}%`;
                    }

                    const remainingMinutes = Math.max(0, Math.round((100 - currentProgress) / 10));
                    const remainingSeconds = Math.round(Math.random() * 59);
                    if (timeText) {
                        timeText.textContent = `Time remaining: ${remainingMinutes}m ${remainingSeconds}s`;
                    }
                }
            }
        }

        // Update progress every 3 seconds for demo
        setInterval(updateProgress, 3000);

        // Add click handlers for stats cards
        document.querySelectorAll('.stats-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });

        // Real-time conflict updates (demo)
        function updateConflicts() {
            const conflictCount = document.querySelector('.stats-number.text-warning');
            const conflictBadge = document.querySelector('.badge.bg-danger');

            if (conflictCount && conflictBadge) {
                const currentCount = parseInt(conflictCount.textContent);
                const newCount = Math.max(0, currentCount + (Math.random() > 0.7 ? -1 : 0));

                conflictCount.textContent = newCount;
                conflictBadge.textContent = `${newCount} Issues`;

                if (newCount === 0) {
                    conflictBadge.className = 'badge bg-success bg-opacity-10 text-success';
                    conflictBadge.textContent = 'No Issues';
                }
            }
        }

        // Update conflicts every 10 seconds for demo
        setInterval(updateConflicts, 10000);
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
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }

    console.log('✅ Enhanced Dashboard initialized with drag & drop functionality');
});
</script>
@endpush
