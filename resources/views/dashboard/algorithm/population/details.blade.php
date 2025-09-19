@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-microscope text-primary me-2"></i>
                        Population #{{ $population->population_id }} Details
                    </h4>
                    <p class="text-muted mb-0">
                        View chromosomes and genetic analysis for this population
                        @if($population->parent)
                            <span class="badge bg-info bg-opacity-10 text-info ms-2">
                                <i class="fas fa-arrow-up me-1"></i>Child of #{{ $population->parent->population_id }}
                            </span>
                        @endif
                        @if($population->isInitialGeneration())
                            <span class="badge bg-success bg-opacity-10 text-success ms-2">
                                <i class="fas fa-seedling me-1"></i>Initial Generation
                            </span>
                        @endif
                    </p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#continueEvolutionModal-{{ $population->population_id }}">
                        <i class="fas fa-play me-1"></i>
                        <span class="d-none d-sm-inline">Continue Evolution</span>
                    </button>
                    <a href="{{ route('algorithm-control.populations.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>
                        <span class="d-none d-sm-inline">Back to Populations</span>
                        <span class="d-sm-none">Back</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    <!-- Population Overview Cards -->
    <div class="row mb-4">
        <!-- Configuration Card -->
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="card-title text-primary mb-1">
                                <i class="fas fa-cogs me-1"></i>Configuration
                            </h6>
                            <div class="small">
                                <div><strong>Year:</strong> {{ $population->academic_year }}</div>
                                <div><strong>Semester:</strong> {{ $population->semester }}</div>
                                <div><strong>Population Size:</strong> {{ $population->population_size }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Card -->
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card border-0 shadow-sm bg-info bg-opacity-10">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="card-title text-info mb-1">
                                <i class="fas fa-info-circle me-1"></i>Status
                            </h6>
                            <div class="small">
                                <div><strong>Status:</strong>
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
                                <div><strong>Created:</strong> {{ $population->created_at }}</div>
                                @if($population->end_time)
                                    <div><strong>Completed:</strong> {{ $population->end_time }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Card -->
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="card-title text-success mb-1">
                                <i class="fas fa-chart-line me-1"></i>Performance
                            </h6>
                            <div class="small">
                                @if($population->bestChromosome)
                                    <div><strong>Best Fitness:</strong> {{ number_format($population->bestChromosome->fitness_value, 4) }}</div>
                                    <div><strong>Best Penalty:</strong> {{ $population->bestChromosome->penalty_value }}</div>
                                @else
                                    <div class="text-muted">No best chromosome yet</div>
                                @endif
                                <div><strong>Total Chromosomes:</strong> {{ $chromosomes->total() }}</div>
                                <div><strong>Total generations:</strong> {{ $population->max_generations }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Algorithm Settings Card -->
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card border-0 shadow-sm bg-warning bg-opacity-10">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="card-title text-warning mb-1">
                                <i class="fas fa-sliders-h me-1"></i>Algorithm
                            </h6>
                            <div class="small">
                                <div><strong>Crossover:</strong> {{ optional($population->crossover)->name ?? 'N/A' }}</div>
                                <div><strong>Selection:</strong> {{ optional($population->selectionType)->name ?? 'N/A' }}</div>
                                <div><strong>Mutation Rate:</strong> {{ $population->mutation_rate * 100 }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chromosomes Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-dna text-muted me-2"></i>
                            Chromosomes Analysis
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $chromosomes->total() }} Total</span>
                    </div>
                </div>

                <div class="card-body pt-3">
                    @if($chromosomes->count() > 0)
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 text-center" style="width: 50px;">#</th>
                                        <th class="border-0">Chromosome ID</th>
                                        <th class="border-0">Generation</th>
                                        <th class="border-0">Fitness Score</th>
                                        <th class="border-0">Penalty Value</th>
                                        <th class="border-0">Conflict Analysis</th>
                                        <th class="border-0">Genes Count</th>
                                        <th class="border-0">Quality</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($chromosomes as $index => $chromosome)
                                        <tr class="border-bottom {{ $population->best_chromosome_id == $chromosome->chromosome_id ? 'table-success' : '' }}">
                                            <td class="text-center text-muted">
                                                <small>{{ $chromosomes->firstItem() + $index }}</small>
                                                @if($population->best_chromosome_id == $chromosome->chromosome_id)
                                                    <br><span class="badge bg-success bg-opacity-10 text-success" title="Best Chromosome">
                                                        <i class="fas fa-trophy"></i>
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-primary bg-opacity-10 text-primary font-monospace">
                                                    #{{ $chromosome->chromosome_id }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info bg-opacity-10 text-info">
                                                    Gen {{ $chromosome->generation_number }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-medium {{ $chromosome->fitness_value > 0.8 ? 'text-success' : ($chromosome->fitness_value > 0.5 ? 'text-warning' : 'text-danger') }}">
                                                    {{ number_format($chromosome->fitness_value, 4) }}
                                                </div>
                                                <div class="progress mt-1" style="height: 4px;">
                                                    <div class="progress-bar {{ $chromosome->fitness_value > 0.8 ? 'bg-success' : ($chromosome->fitness_value > 0.5 ? 'bg-warning' : 'bg-danger') }}"
                                                         style="width: {{ $chromosome->fitness_value * 100 }}%"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge {{ $chromosome->penalty_value == 0 ? 'bg-success' : ($chromosome->penalty_value < 10 ? 'bg-warning' : 'bg-danger') }}">
                                                    {{ $chromosome->penalty_value }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    @if($chromosome->student_conflict_penalty > 0)
                                                        <div class="text-danger">Students: {{ $chromosome->student_conflict_penalty }}</div>
                                                    @endif
                                                    @if($chromosome->teacher_conflict_penalty > 0)
                                                        <div class="text-warning">Teachers: {{ $chromosome->teacher_conflict_penalty }} + {{ $chromosome->teacher_eligibility_conflict_penalty }}</div>
                                                    @endif
                                                    @if($chromosome->room_conflict_penalty > 0)
                                                        <div class="text-info">Rooms: {{ $chromosome->room_conflict_penalty }}</div>
                                                    @endif
                                                    @if($chromosome->penalty_value == 0)
                                                        <span class="text-success small">No Conflicts</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    {{ $chromosome->genes->count() }} genes
                                                </span>
                                            </td>
                                            <td>
                                                @if($chromosome->penalty_value == 0)
                                                    <span class="badge bg-success">Perfect</span>
                                                @elseif($chromosome->fitness_value > 0.8)
                                                    <span class="badge bg-success bg-opacity-75">Excellent</span>
                                                @elseif($chromosome->fitness_value > 0.6)
                                                    <span class="badge bg-warning">Good</span>
                                                @elseif($chromosome->fitness_value > 0.4)
                                                    <span class="badge bg-warning bg-opacity-75">Fair</span>
                                                @else
                                                    <span class="badge bg-danger">Poor</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="d-md-none">
                            @foreach ($chromosomes as $index => $chromosome)
                                <div class="card mb-3 border {{ $population->best_chromosome_id == $chromosome->chromosome_id ? 'border-success' : '' }}">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="badge bg-primary bg-opacity-10 text-primary font-monospace me-2">
                                                        #{{ $chromosome->chromosome_id }}
                                                    </span>
                                                    @if($population->best_chromosome_id == $chromosome->chromosome_id)
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-trophy me-1"></i>Best
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="small mb-2">
                                                    <div><strong>Fitness:</strong> {{ number_format($chromosome->fitness_value, 4) }} | <strong>Penalty:</strong> {{ $chromosome->penalty_value }}</div>
                                                    <div><strong>Generation:</strong> {{ $chromosome->generation_number }} | <strong>Genes:</strong> {{ $chromosome->genes->count() }}</div>
                                                </div>
                                                <div class="progress mb-2" style="height: 6px;">
                                                    <div class="progress-bar {{ $chromosome->fitness_value > 0.8 ? 'bg-success' : ($chromosome->fitness_value > 0.5 ? 'bg-warning' : 'bg-danger') }}"
                                                         style="width: {{ $chromosome->fitness_value * 100 }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($chromosomes->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $chromosomes->links('pagination::bootstrap-5') }}
                            </div>
                        @endif

                    @else
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-dna text-muted opacity-50" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 text-muted">No Chromosomes Found</h5>
                                <p class="text-muted mb-4">This population doesn't have any chromosomes yet. The genetic algorithm may still be running.</p>
                                @if($population->status === 'running')
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="text-muted mt-2">Generation in progress...</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

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
                                <label class="form-label fw-medium">Academic Year</label>
                                <input type="number" class="form-control" name="academic_year" value="{{ $population->academic_year }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Semester</label>
                                <select class="form-select" name="semester" required>
                                    <option value="1" {{ $population->semester == 1 ? 'selected' : '' }}>First Semester</option>
                                    <option value="2" {{ $population->semester == 2 ? 'selected' : '' }}>Second Semester</option>
                                    <option value="3" {{ $population->semester == 3 ? 'selected' : '' }}>Summer Semester</option>
                                </select>
                            </div>
                        </div>

                        <!-- Algorithm Parameters -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Population Size</label>
                                <input type="number" class="form-control" name="population_size" value="{{ $population->population_size }}" required min="10">
                            </div>
                            <input type="hidden" name="max_generations" value="{{ $population->max_generations }}">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Elite Count</label>
                                <input type="number" class="form-control" name="elitism_count_chromosomes" value="{{ $population->elitism_count }}" required min="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Mutation Rate</label>
                                <input type="number" class="form-control" name="mutation_rate" value="{{ $population->mutation_rate }}" required min="0" max="1" step="0.01">
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
                                <label class="form-label fw-medium">Selection Method</label>
                                <select class="form-select" name="selection_type_id" required>
                                    @foreach (\App\Models\SelectionType::where('is_active', true)->get() as $type)
                                        <option value="{{ $type->selection_type_id }}" {{ $population->selection_id == $type->selection_type_id ? 'selected' : '' }}>
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
                        </div>

                        <!-- Advanced Settings -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Crossover Rate</label>
                                <input type="number" class="form-control" name="crossover_rate" value="{{ $population->crossover_rate }}" step="0.1" min="0" max="1" required>
                            </div>
                            <div class="col-md-6">
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
</div>

@endsection

@push('styles')
<style>
/* Population Details Specific Styles */
.progress {
    background-color: rgba(0,0,0,0.1);
}

.card-title {
    font-size: 0.9rem;
    font-weight: 600;
}

.table-success {
    background-color: rgba(25, 135, 84, 0.1);
}

.badge {
    font-size: 0.75rem;
}

/* Mobile responsive adjustments */
@media (max-width: 768px) {
    .card-body {
        padding: 0.75rem;
    }

    .badge {
        font-size: 0.7rem;
    }

    .small {
        font-size: 0.8rem;
    }
}

/* Animation for running status */
@keyframes pulse-subtle {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.spinner-border {
    animation: pulse-subtle 2s infinite;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh if population is still running
    @if($population->status === 'running')
        setTimeout(function() {
            location.reload();
        }, 30000); // Refresh every 30 seconds
    @endif

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

    console.log('✅ Population details page initialized');
});
</script>
@endpush
