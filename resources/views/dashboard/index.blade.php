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
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#generateScheduleModal">
                        <i class="fas fa-sync-alt me-1"></i>
                        <span class="d-none d-sm-inline">Generate Schedule</span>
                        <span class="d-sm-none">Generate</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm stats-card bg-primary bg-opacity-10">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-20 text-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-book fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stats-number text-primary fw-bold mb-1">324</div>
                            <div class="stats-label text-muted small">Total Courses</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm stats-card bg-success bg-opacity-10">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-20 text-success rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-chalkboard-teacher fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stats-number text-success fw-bold mb-1">48</div>
                            <div class="stats-label text-muted small">Active Instructors</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm stats-card bg-info bg-opacity-10">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-20 text-info rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-door-open fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stats-number text-info fw-bold mb-1">12</div>
                            <div class="stats-label text-muted small">Available Rooms</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm stats-card bg-danger bg-opacity-10">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-danger bg-opacity-20 text-danger rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-exclamation-triangle fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stats-number text-danger fw-bold mb-1">3</div>
                            <div class="stats-label text-muted small">Current Conflicts</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Generation Progress Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-code-branch text-primary me-2"></i>
                            <h6 class="card-title mb-0">Generation Progress</h6>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-outline-warning btn-sm" disabled>
                                <i class="fas fa-pause me-1"></i>
                                <span class="d-none d-sm-inline">Pause</span>
                            </button>
                            <button class="btn btn-outline-danger btn-sm" disabled>
                                <i class="fas fa-stop me-1"></i>
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
                                        <i class="fas fa-chart-line me-1"></i>
                                        Fitness Score: 0.89
                                    </span>
                                </div>

                                <div class="progress mb-2" style="height: 10px;">
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
                                        <i class="fas fa-sync-alt fa-spin text-primary fa-2x"></i>
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
    <div class="row">
        <!-- Weekly Schedule Preview -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-calendar-week text-primary me-2"></i>
                            Weekly Schedule Preview
                        </h6>
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
                                        <div class="course-block bg-primary bg-opacity-10 border-start border-primary border-3">
                                            <div class="course-code fw-bold text-primary">CS101</div>
                                            <div class="course-room small text-muted">Room 301</div>
                                            <div class="course-instructor small">Dr. Smith</div>
                                        </div>
                                    </td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                </tr>
                                <tr>
                                    <td class="text-center fw-medium text-muted">10:00 AM</td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell">
                                        <div class="course-block bg-success bg-opacity-10 border-start border-success border-3">
                                            <div class="course-code fw-bold text-success">MATH201</div>
                                            <div class="course-room small text-muted">Room 205</div>
                                            <div class="course-instructor small">Dr. Johnson</div>
                                        </div>
                                    </td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                </tr>
                                <tr>
                                    <td class="text-center fw-medium text-muted">11:00 AM</td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell">
                                        <div class="course-block bg-info bg-opacity-10 border-start border-info border-3">
                                            <div class="course-code fw-bold text-info">PHYS301</div>
                                            <div class="course-room small text-muted">Lab 101</div>
                                            <div class="course-instructor small">Dr. Brown</div>
                                        </div>
                                    </td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                </tr>
                                <tr>
                                    <td class="text-center fw-medium text-muted">12:00 PM</td>
                                    <td class="schedule-cell" colspan="5">
                                        <div class="text-center text-muted py-2">
                                            <i class="fas fa-utensils me-1"></i>
                                            Lunch Break
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center fw-medium text-muted">1:00 PM</td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell">
                                        <div class="course-block bg-warning bg-opacity-10 border-start border-warning border-3">
                                            <div class="course-code fw-bold text-warning">ENG401</div>
                                            <div class="course-room small text-muted">Room 102</div>
                                            <div class="course-instructor small">Dr. Wilson</div>
                                        </div>
                                    </td>
                                    <td class="schedule-cell"></td>
                                </tr>
                                <tr>
                                    <td class="text-center fw-medium text-muted">2:00 PM</td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                    <td class="schedule-cell"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conflicts and Info Panel -->
        <div class="col-lg-4">
            <!-- Current Conflicts Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            Current Conflicts
                        </h6>
                        <span class="badge bg-danger bg-opacity-10 text-danger">3 Issues</span>
                    </div>
                </div>

                <div class="card-body">
                    <div class="conflict-list">
                        <div class="conflict-item d-flex align-items-start p-3 mb-2 bg-danger bg-opacity-5 rounded border-start border-danger border-3">
                            <div class="conflict-icon me-3">
                                <i class="fas fa-door-open text-danger"></i>
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
                                <i class="fas fa-user-tie text-warning"></i>
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
                                <i class="fas fa-clock text-info"></i>
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
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-bolt text-primary me-2"></i>
                        Quick Actions
                    </h6>
                </div>

                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('algorithm-control.timetable.results.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-2"></i>View Results
                        </a>
                        <a href="{{ route('data-entry.subjects.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-book me-2"></i>Manage Subjects
                        </a>
                        <a href="{{ route('data-entry.instructors.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Manage Instructors
                        </a>
                        <a href="{{ route('data-entry.rooms.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-door-open me-2"></i>Manage Rooms
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
                    <i class="fas fa-cogs me-2"></i>
                    Timetable Generation Settings
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('algorithm-control.timetable.generate.start') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="alert alert-info border-0 mb-4">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle me-2 mt-1"></i>
                            <div>
                                <small>Configure the parameters for this generation run. Different settings can affect the speed and quality of the result.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Settings -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="academic_year" class="form-label fw-medium">
                                <i class="fas fa-calendar-alt text-muted me-1"></i>
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
                                <i class="fas fa-graduation-cap text-muted me-1"></i>
                                Semester <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('semester') is-invalid @enderror" id="semester" name="semester" required>
                                {{-- <option value="" disabled selected>Select a semester...</option> --}}
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
                        <i class="fas fa-sliders-h text-primary me-1"></i>
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
                        {{-- <div class="col-md-4"> --}}
                            <label hidden for="setting_max_generations" class="form-label fw-medium">Max Generations</label>
                            <input hidden type="number" class="form-control" id="setting_max_generations"
                                name="max_generations" value="{{ config('algorithm.settings.max_generations', 10) }}"
                                required min="10" step="10">
                            {{-- <div class="form-text">When to stop if no solution found</div> --}}
                        {{-- </div> --}}
                        <div class="col-md-4">
                            <label for="setting_elitism_chromosomes" class="form-label fw-medium">Elitism_chromosomes </label>
                            <input type="number" class="form-control" id="setting_elitism_chromosomes"
                                name="elitism_chromosomes" value="{{ config('algorithm.settings.elitism_chromosomes', 5) }}"
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
                        <i class="fas fa-clock text-primary me-1"></i>
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
                        <i class="fas fa-cogs text-primary me-1"></i>
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
                                @foreach (\App\Models\mutationType::where('is_active', true)->get() as $type)
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
                            <i class="fas fa-flag-checkered text-success me-1"></i>
                            Stop at First Valid Solution
                        </label>
                        <div class="form-text">Stops when finding a schedule with zero hard-constraint violations</div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-play me-1"></i>Start Generation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Dashboard Specific Styles */
.stats-card {
    transition: all 0.2s ease;
    border: 1px solid rgba(0,0,0,0.05);
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.1) !important;
}

.stats-icon {
    width: 50px;
    height: 50px;
}

.stats-number {
    font-size: 1.5rem;
    line-height: 1;
}

.stats-label {
    font-size: 0.8rem;
    font-weight: 500;
}

/* Generation Progress Styles */
.generation-info {
    position: relative;
}

.status-indicator {
    width: 60px;
    height: 60px;
}

.progress {
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    background: linear-gradient(45deg, var(--primary-color), var(--primary-light)) !important;
}

/* Schedule Table Styles */
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
}

.course-block {
    padding: 6px 8px;
    border-radius: 6px;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    transition: all 0.2s ease;
}

.course-block:hover {
    transform: scale(1.02);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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

/* Responsive Adjustments */
@media (max-width: 768px) {
    .stats-number {
        font-size: 1.1rem;
    }

    .stats-icon {
        width: 36px;
        height: 36px;
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
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
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

                // Update remaining time
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
            // Add click effect
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    // Enhanced tooltips for method selections
    const selects = document.querySelectorAll('select[title]');
    selects.forEach(select => {
        select.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.title) {
                // You could show a tooltip or description here
                console.log('Selected:', selectedOption.text, '-', selectedOption.title);
            }
        });
    });

    // Real-time conflict updates (demo)
    function updateConflicts() {
        const conflictCount = document.querySelector('.stats-number.text-danger');
        const conflictBadge = document.querySelector('.badge.bg-danger');

        if (conflictCount && conflictBadge) {
            const currentCount = parseInt(conflictCount.textContent);
            const newCount = Math.max(0, currentCount + (Math.random() > 0.7 ? -1 : 0));

            conflictCount.textContent = newCount;
            conflictBadge.textContent = `${newCount} Issues`;

            // Update badge color based on count
            if (newCount === 0) {
                conflictBadge.className = 'badge bg-success bg-opacity-10 text-success';
                conflictBadge.textContent = 'No Issues';
            }
        }
    }

    // Update conflicts every 10 seconds for demo
    setInterval(updateConflicts, 10000);

    console.log('✅ Dashboard initialized with real-time updates');
});
</script>
