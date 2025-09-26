@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-dna text-primary me-2"></i>
                        Population Management
                    </h4>
                    <p class="text-muted mb-0">Manage genetic algorithm populations and evolution processes</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#generateInitialModal">
                        <i class="fas fa-plus me-1"></i>
                        <span class="d-none d-sm-inline">Generate Initial Population</span>
                        <span class="d-sm-none">Generate</span>
                    </button>
                    <a href="{{ route('dashboard.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>
                        <span class="d-none d-sm-inline">Back to Dashboard</span>
                        <span class="d-sm-none">Back</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    <!-- Main Content Card -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list text-muted me-2"></i>
                            Populations List
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $populations->total() }} Total</span>
                    </div>
                </div>

                <div class="card-body pt-3">
                    @if($populations->count() > 0)
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 text-center" style="width: 50px;">#</th>
                                        <th class="border-0">Population ID</th>
                                        <th class="border-0">Academic Context</th>
                                        <th class="border-0">Configuration</th>
                                        <th class="border-0">Status</th>
                                        <th class="border-0">Best Fitness</th>
                                        <th class="border-0">Created</th>
                                        <th class="border-0 text-center" style="width: 200px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($populations as $index => $population)
                                        <tr class="border-bottom">
                                            <td class="text-center text-muted">
                                                <small>{{ $populations->firstItem() + $index }}</small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-primary bg-opacity-10 text-primary font-monospace me-2">
                                                        #{{ $population->population_id }}
                                                    </span>
                                                    @if($population->isInitialGeneration())
                                                        <span class="badge bg-success bg-opacity-10 text-success" title="Initial Generation">
                                                            <i class="fas fa-seedling"></i>
                                                        </span>
                                                    @else
                                                        <span class="badge bg-info bg-opacity-10 text-info" title="Evolved Generation">
                                                            <i class="fas fa-code-branch"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <div class="fw-medium">Year: {{ $population->academic_year }}</div>
                                                    <div class="text-muted">
                                                        Semester: {{ $population->semester }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <div>Size: <span class="fw-medium">{{ $population->population_size }}</span></div>
                                                    <div class="text-muted">
                                                        Mutation: {{ $population->mutation_rate * 100 }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @switch($population->status)
                                                    @case('running')
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-sync-alt fa-spin me-1"></i>Running
                                                        </span>
                                                        @break
                                                    @case('completed')
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i>Completed
                                                        </span>
                                                        @break
                                                    @case('failed')
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times me-1"></i>Failed
                                                        </span>
                                                        @break
                                                    @case('stopped')
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-stop me-1"></i>Stopped
                                                        </span>
                                                        @break
                                                @endswitch
                                            </td>
                                            <td>
                                                @if($population->bestChromosome)
                                                    <div class="small">
                                                        <div class="fw-medium text-success">{{ number_format($population->bestChromosome->fitness_value, 4) }}</div>
                                                        <div class="text-muted">Penalty: {{ $population->bestChromosome->penalty_value }}</div>
                                                    </div>
                                                @else
                                                    <span class="text-muted small">No data</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar-alt me-1"></i>
                                                    {{ $population->created_at->format('M d, Y') }}
                                                    <div>{{ $population->created_at->format('H:i') }}</div>
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button class="btn btn-outline-primary btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#continueEvolutionModal-{{ $population->population_id }}"
                                                            title="Continue Evolution">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                    <a href="{{ route('algorithm-control.populations.details', $population->population_id) }}"
                                                       class="btn btn-outline-info btn-sm"
                                                       title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button class="btn btn-outline-danger btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deletePopulationModal-{{ $population->population_id }}"
                                                            title="Delete Population">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="d-md-none">
                            @foreach ($populations as $index => $population)
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="badge bg-primary bg-opacity-10 text-primary font-monospace me-2">
                                                        #{{ $population->population_id }}
                                                    </span>
                                                    @if($population->isInitialGeneration())
                                                        <span class="badge bg-success bg-opacity-10 text-success">
                                                            <i class="fas fa-seedling me-1"></i>Initial
                                                        </span>
                                                    @else
                                                        <span class="badge bg-info bg-opacity-10 text-info">
                                                            <i class="fas fa-code-branch me-1"></i>Evolved
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="small mb-2">
                                                    <div><strong>Year:</strong> {{ $population->academic_year }} | <strong>Semester:</strong> {{ $population->semester }}</div>
                                                    <div><strong>Size:</strong> {{ $population->population_size }} | <strong>Status:</strong>
                                                        @switch($population->status)
                                                            @case('running')
                                                                <span class="text-warning">Running</span>
                                                                @break
                                                            @case('completed')
                                                                <span class="text-success">Completed</span>
                                                                @break
                                                            @case('failed')
                                                                <span class="text-danger">Failed</span>
                                                                @break
                                                            @case('stopped')
                                                                <span class="text-secondary">Stopped</span>
                                                                @break
                                                        @endswitch
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#continueEvolutionModal-{{ $population->population_id }}">
                                                            <i class="fas fa-play me-2"></i>Continue Evolution
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('algorithm-control.populations.details', $population->population_id) }}">
                                                            <i class="fas fa-eye me-2"></i>View Details
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deletePopulationModal-{{ $population->population_id }}">
                                                            <i class="fas fa-trash me-2"></i>Delete
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted small">
                                            <span>
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                {{ $population->created_at->format('M d, Y H:i') }}
                                            </span>
                                            <span class="text-muted">#{{ $populations->firstItem() + $index }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($populations->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $populations->links('pagination::bootstrap-5') }}
                            </div>
                        @endif

                    @else
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-dna text-muted opacity-50" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 text-muted">No Populations Found</h5>
                                <p class="text-muted mb-4">Start by generating your first population to begin the genetic algorithm process.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateInitialModal">
                                    <i class="fas fa-plus me-2"></i>Generate First Population
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Generate Initial Population Modal -->
<div class="modal fade" id="generateInitialModal" tabindex="-1" aria-labelledby="generateInitialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="generateInitialModalLabel">
                    <i class="fas fa-cogs me-2"></i>
                    Generate Initial Population
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('algorithm-control.populations.generate-initial') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="alert alert-info border-0 mb-4">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle me-2 mt-1"></i>
                            <div>
                                <small>This will create the first generation only and stop. You can then choose to continue evolution or analyze results.</small>
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
                                <option value="1" {{ old('semester') == 1 ? 'selected' : '' }}>First Semester</option>
                                <option value="2" {{ old('semester') == 2 ? 'selected' : '' }}>Second Semester</option>
                                <option value="3" {{ old('semester') == 3 ? 'selected' : '' }}>Summer Semester</option>
                            </select>
                            @error('semester')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Duration Settings -->
                    <h6 class="mb-3">
                        <i class="fas fa-clock text-primary me-1"></i>
                        Lecture Duration Settings
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="theory_credit_to_slots" class="form-label fw-medium">Theory Credit Slots</label>
                            <input type="number" class="form-control @error('theory_credit_to_slots') is-invalid @enderror"
                                id="theory_credit_to_slots" name="theory_credit_to_slots"
                                value="{{ old('theory_credit_to_slots', 1) }}" required min="1" max="4">
                            <div class="form-text">Consecutive timeslots per theoretical credit hour</div>
                            @error('theory_credit_to_slots')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="practical_credit_to_slots" class="form-label fw-medium">Practical Credit Slots</label>
                            <input type="number" class="form-control @error('practical_credit_to_slots') is-invalid @enderror"
                                id="practical_credit_to_slots" name="practical_credit_to_slots"
                                value="{{ old('practical_credit_to_slots', 2) }}" required min="1" max="4">
                            <div class="form-text">Consecutive timeslots per practical credit hour</div>
                            @error('practical_credit_to_slots')
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
                            <label for="population_size" class="form-label fw-medium">Population Size</label>
                            <input type="number" class="form-control" id="population_size"
                                name="population_size" value="{{ config('algorithm.settings.population_size', 10) }}"
                                required min="10" step="10">
                            <div class="form-text">Number of schedules per generation</div>
                        </div>
                        {{-- <input type="number" class="form-control" id="max_generations"
                            name="max_generations" value="{{ config('algorithm.settings.max_generations', 10) }}"
                            required min="10" step="10"> --}}

                        <div class="col-md-4">
                        <label for="max_generations" class="form-label fw-medium">Max Generations</label>
                        <input type="number" class="form-control" id="max_generations"
                            name="max_generations" value="{{ config('algorithm.settings.max_generations', 10) }}"
                            required min="10" step="10">
                        <div class="form-text">Maximum evolution cycles</div>
                        </div>


                        <div class="col-md-4">
                            <label for="elitism_count_chromosomes" class="form-label fw-medium">Elite Count</label>
                            <input type="number" class="form-control" id="elitism_count_chromosomes"
                                name="elitism_count_chromosomes" value="{{ config('algorithm.settings.elitism_count_chromosomes', 5) }}"
                                required min="1" step="1">
                            <div class="form-text">Best chromosomes to preserve</div>
                        </div>


                    </div>

                    <!-- Method Selection -->
                    <h6 class="mb-3">
                        <i class="fas fa-cogs text-primary me-1"></i>
                        Algorithm Methods
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="crossover_type_id" class="form-label fw-medium">Crossover Method</label>
                            <select class="form-select" id="crossover_type_id" name="crossover_type_id" required>
                                @foreach (\App\Models\CrossoverType::where('is_active', true)->get() as $type)
                                    <option value="{{ $type->crossover_id }}" title="{{ $type->description }}"
                                        {{ config('algorithm.settings.crossover_type_id') == $type->crossover_id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="mutation_type_id" class="form-label fw-medium">Mutation Type</label>
                            <select class="form-select" id="mutation_type_id" name="mutation_type_id" required>
                                @foreach (\App\Models\MutationType::where('is_active', true)->get() as $type)
                                    <option value="{{ $type->mutation_id }}" title="{{ $type->description }}"
                                        {{ config('algorithm.settings.mutation_type_id') == $type->mutation_id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="selection_type_id" class="form-label fw-medium">Selection Method</label>
                            <select class="form-select" id="selection_type_id" name="selection_type_id" required>
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
                            <label for="mutation_rate" class="form-label fw-medium">Mutation Rate</label>
                            <input type="number" class="form-control" id="mutation_rate"
                                name="mutation_rate" value="{{ config('algorithm.settings.mutation_rate', 0.05) }}"
                                required min="0" max="1" step="0.01">
                            <div class="form-text">e.g., 0.05 for 5%</div>
                        </div>
                        <div class="col-md-4">
                            <label for="selection_size" class="form-label fw-medium">Tournament Size</label>
                            <input type="number" class="form-control" id="selection_size"
                                name="selection_size" value="{{ config('algorithm.settings.selection_size', 3) }}"
                                required min="2" max="10">
                            <div class="form-text">Competitors per tournament</div>
                        </div>
                    </div>

                    <!-- Stop Condition -->
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                            id="stop_at_first_valid" name="stop_at_first_valid" value="1"
                            {{ config('algorithm.settings.stop_at_first_valid', true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-medium" for="stop_at_first_valid">
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
                        <i class="fas fa-play me-1"></i>Generate Initial Population
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Continue Evolution & Delete Modals for each population -->
@foreach($populations as $population)
    <!-- Continue Evolution Modal -->
    <div class="modal fade" id="continueEvolutionModal-{{ $population->population_id }}" tabindex="-1" aria-labelledby="continueEvolutionModalLabel-{{ $population->population_id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title d-flex align-items-center" id="continueEvolutionModalLabel-{{ $population->population_id }}">
                        <i class="fas fa-play me-2"></i>
                        Continue Evolution - Population #{{ $population->population_id }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="{{ route('algorithm-control.populations.continue', $population->population_id) }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                        <!-- Parent Population Info -->
                        <div class="alert alert-success border-0 mb-4">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-info-circle me-2 mt-1"></i>
                                <div>
                                    <strong>Continuing from Population #{{ $population->population_id }}</strong><br>
                                    <small>Created: {{ $population->created_at->format('M d, Y H:i') }} | Status: {{ ucfirst($population->status) }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Academic Settings -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="continue_academic_year_{{ $population->population_id }}" class="form-label fw-medium">Academic Year</label>
                                <input type="number" class="form-control" id="continue_academic_year_{{ $population->population_id }}"
                                       name="academic_year" value="{{ $population->academic_year }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="continue_semester_{{ $population->population_id }}" class="form-label fw-medium">Semester</label>
                                <select class="form-select" id="continue_semester_{{ $population->population_id }}" name="semester" required>
                                    <option value="1" {{ $population->semester == 1 ? 'selected' : '' }}>First Semester</option>
                                    <option value="2" {{ $population->semester == 2 ? 'selected' : '' }}>Second Semester</option>
                                    <option value="3" {{ $population->semester == 3 ? 'selected' : '' }}>Summer Semester</option>
                                </select>
                            </div>
                        </div>

                        <!-- Duration Settings -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Theory Credit Slots</label>
                                <input type="number" class="form-control" name="theory_credit_to_slots" value="{{ $population->theory_credit_to_slots }}" required min="1" max="4">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Practical Credit Slots</label>
                                <input type="number" class="form-control" name="practical_credit_to_slots" value="{{ $population->practical_credit_to_slots }}" required min="1" max="4">
                            </div>
                        </div>

                        <!-- Algorithm Parameters -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Population Size</label>
                                <input type="number" class="form-control" name="population_size" value="{{ $population->population_size }}" required min="10">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Max Generations</label>
                                <input type="number" class="form-control" name="max_generations" value="{{ $population->max_generations }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Elite Count</label>
                                <input type="number" class="form-control" name="elitism_count_chromosomes" value="{{ $population->elitism_count }}" required min="1">
                            </div>

                        </div>

                        <!-- Method Selection -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Crossover Method</label>
                                <select class="form-select" name="crossover_type_id" required>
                                    @foreach (\App\Models\CrossoverType::where('is_active', true)->get() as $type)
                                        <option value="{{ $type->crossover_id }}" {{ $population->crossover_id == $type->crossover_id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Mutation Type</label>
                                <select class="form-select" name="mutation_type_id" required>
                                    @foreach (\App\Models\MutationType::where('is_active', true)->get() as $type)
                                        <option value="{{ $type->mutation_id }}" {{ $population->mutation_id == $type->mutation_id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-medium">Selection Method</label>
                                    <select class="form-select" name="selection_type_id" required>
                                        @foreach (\App\Models\SelectionType::where('is_active', true)->get() as $type)
                                        <option value="{{ $type->selection_type_id }}" {{ $population->selection_id == $type->selection_type_id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                        <!-- Advanced Settings -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Crossover Rate</label>
                                <input type="number" class="form-control" name="crossover_rate" value="{{ $population->crossover_rate }}" step="0.1" min="0" max="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Mutation Rate</label>
                                <input type="number" class="form-control" name="mutation_rate" value="{{ $population->mutation_rate }}" required min="0" max="1" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Tournament Size</label>
                                <input type="number" class="form-control" name="selection_size" value="{{ $population->selection_size }}" required min="2" max="10">
                            </div>
                        </div>

                        <!-- Stop Condition -->
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="stop_at_first_valid" value="1" {{ $population->stop_at_first_valid ? 'checked' : '' }}>
                            <label class="form-check-label fw-medium">
                                <i class="fas fa-flag-checkered text-success me-1"></i>
                                Stop at First Valid Solution
                            </label>
                        </div>
                    </div>

                    <div class="modal-footer bg-light border-0 p-3">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-play me-1"></i>Continue Evolution
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Population Modal -->
    <div class="modal fade" id="deletePopulationModal-{{ $population->population_id }}" tabindex="-1" aria-labelledby="deletePopulationModalLabel-{{ $population->population_id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title d-flex align-items-center" id="deletePopulationModalLabel-{{ $population->population_id }}">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="{{ route('algorithm-control.populations.destroy', $population->population_id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body p-4">
                        <div class="text-center mb-3">
                            <i class="fas fa-dna text-danger" style="font-size: 3rem; opacity: 0.3;"></i>
                        </div>

                        <div class="alert alert-danger d-flex align-items-start" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <h6 class="alert-heading mb-1">Are you sure?</h6>
                                <p class="mb-0 small">This will permanently delete Population #{{ $population->population_id }} and all related data.</p>
                            </div>
                        </div>

                        <div class="card bg-light border-danger">
                            <div class="card-body p-3">
                                <h6 class="card-title mb-2">Population #{{ $population->population_id }}</h6>
                                <div class="small">
                                    <div><strong>Academic Context:</strong> Year {{ $population->academic_year }}, Semester {{ $population->semester }}</div>
                                    <div><strong>Configuration:</strong> Size {{ $population->population_size }}, Status {{ ucfirst($population->status) }}</div>
                                    <div><strong>Chromosomes:</strong> {{ $population->chromosomes()->count() }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-3 mb-0" role="alert">
                            <div class="d-flex">
                                <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
                                <div>
                                    <small><strong>Warning:</strong> This action cannot be undone. All chromosomes and genes will be deleted.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light border-0 p-3">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Yes, Delete Population
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

@endsection
