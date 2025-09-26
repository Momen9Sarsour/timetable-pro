{{-- Continue Evolution Modal --}}
<div class="modal fade" id="continueEvolutionModal-{{ $population->population_id }}" tabindex="-1" aria-labelledby="continueEvolutionModalLabel-{{ $population->population_id }}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
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
                                <small>Created: {{ $population->created_at->format('M d, Y H:i') }} |
                                Status: {{ ucfirst($population->status) }} |
                                Size: {{ $population->population_size }} chromosomes</small>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Settings -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="continue_academic_year_{{ $population->population_id }}" class="form-label fw-medium">
                                <i class="fas fa-calendar-alt text-muted me-1"></i>
                                Academic Year <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control"
                                id="continue_academic_year_{{ $population->population_id }}" name="academic_year"
                                value="{{ $population->academic_year }}"
                                required placeholder="e.g., 2025">
                            <div class="form-text">Current academic year</div>
                        </div>
                        <div class="col-md-6">
                            <label for="continue_semester_{{ $population->population_id }}" class="form-label fw-medium">
                                <i class="fas fa-graduation-cap text-muted me-1"></i>
                                Semester <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="continue_semester_{{ $population->population_id }}" name="semester" required>
                                <option value="1" {{ $population->semester == 1 ? 'selected' : '' }}>First Semester</option>
                                <option value="2" {{ $population->semester == 2 ? 'selected' : '' }}>Second Semester</option>
                                <option value="3" {{ $population->semester == 3 ? 'selected' : '' }}>Summer Semester</option>
                            </select>
                        </div>
                    </div>

                    <!-- Algorithm Parameters -->
                    <h6 class="mb-3">
                        <i class="fas fa-sliders-h text-primary me-1"></i>
                        Algorithm Parameters
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="continue_population_size_{{ $population->population_id }}" class="form-label fw-medium">Population Size</label>
                            <input type="number" class="form-control"
                                id="continue_population_size_{{ $population->population_id }}"
                                name="population_size" value="{{ $population->population_size }}"
                                required min="10" step="10">
                            <div class="form-text">Number of schedules per generation</div>
                        </div>
                        <div class="col-md-4">
                            <label for="continue_max_generations_{{ $population->population_id }}" class="form-label fw-medium">Max Generations</label>
                            <input type="number" class="form-control"
                                id="continue_max_generations_{{ $population->population_id }}"
                                name="max_generations" value="{{ $population->max_generations }}"
                                required min="10" step="10">
                            <div class="form-text">Maximum evolution cycles</div>
                        </div>
                        <div class="col-md-4">
                            <label for="continue_elitism_count_{{ $population->population_id }}" class="form-label fw-medium">Elite Count</label>
                            <input type="number" class="form-control"
                                id="continue_elitism_count_{{ $population->population_id }}"
                                name="elitism_count_chromosomes" value="{{ $population->elitism_count }}"
                                required min="1" step="1">
                            <div class="form-text">Best chromosomes to preserve</div>
                        </div>
                    </div>

                    <!-- Rates -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="continue_mutation_rate_{{ $population->population_id }}" class="form-label fw-medium">Mutation Rate</label>
                            <input type="number" class="form-control"
                                id="continue_mutation_rate_{{ $population->population_id }}"
                                name="mutation_rate" value="{{ $population->mutation_rate }}"
                                required min="0" max="1" step="0.01">
                            <div class="form-text">e.g., 0.05 for 5%</div>
                        </div>
                        <div class="col-md-4">
                            <label for="continue_crossover_rate_{{ $population->population_id }}" class="form-label fw-medium">Crossover Rate</label>
                            <input type="number" class="form-control"
                                id="continue_crossover_rate_{{ $population->population_id }}" name="crossover_rate"
                                value="{{ $population->crossover_rate }}"
                                step="0.1" min="0" max="1" required>
                            <div class="form-text">Probability of crossover</div>
                        </div>
                        <div class="col-md-4">
                            <label for="continue_selection_size_{{ $population->population_id }}" class="form-label fw-medium">Tournament Size</label>
                            <input type="number" class="form-control"
                                id="continue_selection_size_{{ $population->population_id }}"
                                name="selection_size" value="{{ $population->selection_size }}"
                                required min="2" max="10">
                            <div class="form-text">Competitors per tournament</div>
                        </div>
                    </div>

                    <!-- Duration Settings -->
                    <h6 class="mb-3">
                        <i class="fas fa-clock text-primary me-1"></i>
                        Lecture Duration Settings
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="continue_theory_credit_{{ $population->population_id }}" class="form-label fw-medium">Theory Credit Slots</label>
                            <input type="number" class="form-control"
                                id="continue_theory_credit_{{ $population->population_id }}" name="theory_credit_to_slots"
                                value="{{ $population->theory_credit_to_slots }}" required min="1" max="4">
                            <div class="form-text">Consecutive timeslots per theoretical credit hour</div>
                        </div>
                        <div class="col-md-6">
                            <label for="continue_practical_credit_{{ $population->population_id }}" class="form-label fw-medium">Practical Credit Slots</label>
                            <input type="number" class="form-control"
                                id="continue_practical_credit_{{ $population->population_id }}" name="practical_credit_to_slots"
                                value="{{ $population->practical_credit_to_slots }}" required min="1" max="4">
                            <div class="form-text">Consecutive timeslots per practical credit hour</div>
                        </div>
                    </div>

                    <!-- Method Selection -->
                    <h6 class="mb-3">
                        <i class="fas fa-cogs text-primary me-1"></i>
                        Algorithm Methods
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="continue_crossover_type_{{ $population->population_id }}" class="form-label fw-medium">Crossover Method</label>
                            <select class="form-select" id="continue_crossover_type_{{ $population->population_id }}" name="crossover_type_id" required>
                                @foreach (\App\Models\CrossoverType::where('is_active', true)->get() as $type)
                                    <option value="{{ $type->crossover_id }}" title="{{ $type->description }}"
                                        {{ $population->crossover_id == $type->crossover_id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="continue_selection_type_{{ $population->population_id }}" class="form-label fw-medium">Selection Method</label>
                            <select class="form-select" id="continue_selection_type_{{ $population->population_id }}" name="selection_type_id" required>
                                @foreach (\App\Models\SelectionType::where('is_active', true)->get() as $type)
                                    <option value="{{ $type->selection_type_id }}" title="{{ $type->description }}"
                                        {{ $population->selection_id == $type->selection_type_id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="continue_mutation_type_{{ $population->population_id }}" class="form-label fw-medium">Mutation Type</label>
                            <select class="form-select" id="continue_mutation_type_{{ $population->population_id }}" name="mutation_type_id" required>
                                @foreach (\App\Models\MutationType::where('is_active', true)->get() as $type)
                                    <option value="{{ $type->mutation_id }}" title="{{ $type->description }}"
                                        {{ $population->mutation_id == $type->mutation_id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Stop Condition -->
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                            id="continue_stop_at_first_valid_{{ $population->population_id }}" name="stop_at_first_valid" value="1"
                            {{ $population->stop_at_first_valid ? 'checked' : '' }}>
                        <label class="form-check-label fw-medium" for="continue_stop_at_first_valid_{{ $population->population_id }}">
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
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-play me-1"></i>Continue Evolution
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Population Modal --}}
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
                            <p class="mb-0 small">This action will permanently delete:</p>
                        </div>
                    </div>

                    <div class="card bg-light border-danger">
                        <div class="card-body p-3">
                            <h6 class="card-title mb-2">Population #{{ $population->population_id }}</h6>
                            <div class="row text-sm">
                                <div class="col-6">
                                    <strong>Academic Context:</strong><br>
                                    Year: {{ $population->academic_year }}<br>
                                    Semester: {{ $population->semester }}
                                </div>
                                <div class="col-6">
                                    <strong>Configuration:</strong><br>
                                    Size: {{ $population->population_size }}<br>
                                    Status: {{ ucfirst($population->status) }}
                                </div>
                            </div>
                            <div class="mt-2">
                                <strong>Chromosomes:</strong> {{ $population->chromosomes()->count() }}<br>
                                <strong>Total Genes:</strong> {{ $population->chromosomes()->withCount('genes')->get()->sum('genes_count') }}
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <small><strong>Warning:</strong> This will delete the population, all chromosomes, and all genes. This action cannot be undone.</small>
                                @if($population->hasChildren())
                                    <br><small><strong>Note:</strong> This population has {{ $population->children->count() }} child populations that will become orphaned.</small>
                                @endif
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
