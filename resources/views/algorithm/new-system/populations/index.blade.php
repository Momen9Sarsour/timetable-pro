@extends('dashboard.layout')

@section('title', 'Populations Management - New GA System')

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-dna text-primary me-2"></i>
                            Populations Management
                        </h1>
                        <p class="text-muted mb-0">Manage and execute genetic algorithm populations for schedule optimization
                        </p>
                    </div>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPopulationModal">
                            <i class="fas fa-plus me-2"></i>
                            Create New Population
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Breadcrumb -->
        <div class="row mb-3">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                        <li class="breadcrumb-item active">Populations</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Alert Messages -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Quick Statistics -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Populations
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $populations->total() }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dna fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Completed
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $populations->where('status', 'completed')->count() }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Running
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $populations->where('status', 'running')->count() }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-spinner fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Failed
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $populations->where('status', 'failed')->count() }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Populations Table -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-table me-2"></i>
                    All Populations
                </h6>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshTable()">
                        <i class="fas fa-sync me-1"></i>
                        Refresh
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="populationsTable">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Year/Semester</th>
                                <th>Size</th>
                                <th>Generations</th>
                                <th>Status</th>
                                <th>Best Fitness</th>
                                <th>Configuration</th>
                                <th>Created</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($populations as $population)
                                <tr>
                                    <td>
                                        <strong class="text-primary">#{{ $population->population_id }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $population->academic_year }}</span>
                                        <span class="badge bg-info">S{{ $population->semester }}</span>
                                    </td>
                                    <td>
                                        <i class="fas fa-users text-muted me-1"></i>
                                        {{ $population->population_size }}
                                    </td>
                                    <td>
                                        <i class="fas fa-layer-group text-muted me-1"></i>
                                        {{ $population->max_generations }}
                                    </td>
                                    <td>
                                        @if ($population->status === 'completed')
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Completed
                                            </span>
                                        @elseif($population->status === 'running')
                                            <span class="badge bg-info">
                                                <i class="fas fa-spinner fa-spin me-1"></i>Running
                                            </span>
                                        @elseif($population->status === 'failed')
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times me-1"></i>Failed
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">{{ $population->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($population->bestChromosome)
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" role="progressbar">
                                                    {{-- style="width: {{ $population->bestChromosome->fitness_value * 100 }}%"> --}}
                                                    {{-- {{ number_format($population->bestChromosome->fitness_value, 4) }} --}}
                                                    {{ number_format($population->best_chromosome_id) }}
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>
                                            <div><strong>Crossover:</strong>
                                                {{ optional($population->crossoverType)->name ?? 'N/A' }}</div>
                                            <div><strong>Selection:</strong>
                                                {{ optional($population->selectionType)->name ?? 'N/A' }}</div>
                                            <div><strong>Mutation:</strong> {{ $population->mutation_rate }}</div>
                                        </small>
                                    </td>
                                    <td>
                                        <small>{{ $population->created_at }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('new-algorithm.populations.best-chromosomes', $population->population_id) }}"
                                                class="btn btn-sm btn-info" title="View Best Chromosomes">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            @if ($population->status !== 'running')
                                                <button class="btn btn-sm btn-success"
                                                    onclick="runGA({{ $population->population_id }})" title="Run GA">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            @endif

                                            <button class="btn btn-sm btn-warning"
                                                onclick="clonePopulation({{ $population->population_id }})"
                                                title="Clone">
                                                <i class="fas fa-clone"></i>
                                            </button>

                                            @if ($population->status !== 'running')
                                                <button class="btn btn-sm btn-danger"
                                                    onclick="deletePopulation({{ $population->population_id }})"
                                                    title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                        <p>No populations found. Create your first population to get started.</p>
                                        <a href="{{ route('new-algorithm.populations.create') }}"
                                            class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>
                                            Create Population
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($populations->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $populations->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Run GA Confirmation Modal -->
    <div class="modal fade" id="runGAModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-play me-2"></i>
                        Run Genetic Algorithm
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to run the genetic algorithm on population <strong id="runGAPopId"></strong>?
                    </p>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="runInBackground" checked>
                        <label class="form-check-label" for="runInBackground">
                            Run in background (recommended for large populations)
                        </label>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            This process may take several minutes depending on population size and max generations.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="confirmRunGA()">
                        <i class="fas fa-play me-2"></i>
                        Start Algorithm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Clone Population Modal -->
    <div class="modal fade" id="cloneModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-clone me-2"></i>
                        Clone Population
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="cloneForm">
                    @csrf
                    <div class="modal-body">
                        <p>Clone population <strong id="clonePopId"></strong> with custom parameters:</p>

                        <div class="mb-3">
                            <label class="form-label">Max Generations (optional)</label>
                            <input type="number" class="form-control" name="new_max_generations"
                                placeholder="Leave empty to keep original">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mutation Rate (optional)</label>
                            <input type="number" step="0.01" min="0" max="1" class="form-control"
                                name="new_mutation_rate" placeholder="Leave empty to keep original">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Crossover Rate (optional)</label>
                            <input type="number" step="0.01" min="0" max="1" class="form-control"
                                name="new_crossover_rate" placeholder="Leave empty to keep original">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-warning" onclick="confirmClone()">
                            <i class="fas fa-clone me-2"></i>
                            Clone Population
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete population <strong id="deletePopId"></strong>?</p>
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        This action cannot be undone. All chromosomes, genes, and timeslots will be permanently deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash me-2"></i>
                        Delete Population
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Population Modal -->
    <div class="modal fade" id="createPopulationModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>
                        Create New Population
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('new-algorithm.populations.generate') }}" method="POST"
                    id="createPopulationForm">
                    @csrf
                    <div class="modal-body">
                        <!-- Validation Alert -->
                        <div id="validationAlert" class="alert alert-warning d-none">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span id="validationMessage"></span>
                        </div>

                        <div class="row">
                            <!-- Left Column: Basic Settings -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 text-primary">
                                            <i class="fas fa-cog me-2"></i>Basic Settings
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Academic Year <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="academic_year"
                                                value="{{ date('Y') }}" min="2020" max="2030" required>
                                            <small class="text-muted">The academic year for scheduling</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Semester <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" name="semester" required>
                                                <option value="1">First Semester</option>
                                                <option value="2">Second Semester</option>
                                            </select>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Theory Slots <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="theory_credit_to_slots"
                                                    value="1" min="1" max="5" required>
                                                <small class="text-muted">Slots per theory credit</small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Practical Slots <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" class="form-control"
                                                    name="practical_credit_to_slots" value="2" min="1"
                                                    max="8" required>
                                                <small class="text-muted">Slots per practical credit</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 text-primary">
                                            <i class="fas fa-sliders-h me-2"></i>GA Operators
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Crossover Type <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" name="crossover_id" required>
                                                {{-- <option value="">Select crossover type...</option> --}}
                                                @foreach ($crossoverTypes ?? [] as $type)
                                                    <option value="{{ $type->crossover_id }}">{{ $type->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Selection Type <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" name="selection_id" required>
                                                {{-- <option value="">Select selection type...</option> --}}
                                                @foreach ($selectionTypes ?? [] as $type)
                                                    <option value="{{ $type->selection_type_id }}">{{ $type->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Mutation Type <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" name="mutation_id" required>
                                                {{-- <option value="">Select mutation type...</option> --}}
                                                @foreach ($mutationTypes ?? [] as $type)
                                                    <option value="{{ $type->mutation_id }}">{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: GA Parameters -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 text-primary">
                                            <i class="fas fa-dna me-2"></i>Population Parameters
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Population Size <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="population_size"
                                                value="10" min="10" max="1000" required id="popSize">
                                            <small class="text-muted">Number of chromosomes (schedules) in
                                                population</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Max Generations <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="max_generations"
                                                value="10" min="1" max="1000" required>
                                            <small class="text-muted">Maximum number of evolution cycles</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Elitism Count <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="elitism_count"
                                                value="5" min="1" max="50" required>
                                            <small class="text-muted">Best chromosomes preserved each generation</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Selection Size <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="selection_size"
                                                value="5" min="2" max="20" required>
                                            <small class="text-muted">Tournament selection pool size</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 text-primary">
                                            <i class="fas fa-percent me-2"></i>Rate Parameters
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Crossover Rate <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" step="0.01" class="form-control"
                                                name="crossover_rate" value="0.95" min="0" max="1"
                                                required>
                                            <small class="text-muted">Probability of crossover (0.0 - 1.0)</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Mutation Rate <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" step="0.01" class="form-control"
                                                name="mutation_rate" value="0.05" min="0" max="1"
                                                required>
                                            <small class="text-muted">Probability of mutation (0.0 - 1.0)</small>
                                        </div>

                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="stop_at_first_valid"
                                                value="1" id="stopAtValid" checked>
                                            <label class="form-check-label" for="stopAtValid">
                                                <i class="fas fa-stop-circle text-success me-1"></i>
                                                Stop at first valid solution
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="run_in_background"
                                                value="1" id="runBackground">
                                            <label class="form-check-label" for="runBackground">
                                                <i class="fas fa-cloud text-info me-1"></i>
                                                Run generation in background
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Preview -->
                        <div class="card border-0 shadow-sm mt-3 bg-light">
                            <div class="card-body">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Estimated Statistics
                                </h6>
                                <div class="row text-center" id="estimatedStats">
                                    <div class="col-md-3">
                                        <div class="fw-bold text-primary">Total Genes</div>
                                        <div class="h5" id="estGenes">-</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="fw-bold text-success">Timeslots</div>
                                        <div class="h5" id="estTimeslots">-</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="fw-bold text-info">Sections</div>
                                        <div class="h5" id="estSections">-</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="fw-bold text-warning">Est. Time</div>
                                        <div class="h5" id="estTime">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-info" onclick="validateParameters()">
                            <i class="fas fa-check-circle me-2"></i>Validate Parameters
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-play me-2"></i>Generate Population
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }

        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }

        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }

        .border-left-danger {
            border-left: 0.25rem solid #e74a3b !important;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
    </style>
@endpush

@push('scripts')
    <script>
        let currentPopulationId = null;

        $(document).ready(function() {
            // Load crossover, selection, mutation types if modal data is empty
            loadOperatorTypes();

            // Calculate estimated stats when population size changes
            $('#popSize').on('input', function() {
                calculateEstimatedStats();
            });

            // Form submission
            $('#createPopulationForm').on('submit', function(e) {
                e.preventDefault();
                if (confirm(
                        'Are you sure you want to generate this population? This may take several minutes.'
                    )) {
                    this.submit();
                }
            });
        });

        function loadOperatorTypes() {
            // This would be populated from backend, just placeholder
            console.log('Operator types loaded');
        }

        function calculateEstimatedStats() {
            const popSize = parseInt($('#popSize').val()) || 50;

            $.post('{{ route('new-algorithm.generation-stats') }}', {
                    population_size: popSize,
                    _token: '{{ csrf_token() }}'
                })
                .done(function(response) {
                    if (response.success) {
                        $('#estGenes').text(response.data.total_genes.toLocaleString());
                        $('#estTimeslots').text(response.data.estimated_timeslots.toLocaleString());
                        $('#estSections').text(response.data.sections_count.toLocaleString());

                        // Estimate time (rough calculation)
                        const estMinutes = Math.ceil((response.data.total_genes / 1000) * 2);
                        $('#estTime').text(estMinutes + ' min');
                    }
                });
        }

        function validateParameters() {
            const popSize = parseInt($('input[name="population_size"]').val());
            const maxGen = parseInt($('input[name="max_generations"]').val());

            $.post('{{ route('new-algorithm.validate-params') }}', {
                    population_size: popSize,
                    max_generations: maxGen,
                    _token: '{{ csrf_token() }}'
                })
                .done(function(response) {
                    if (response.success) {
                        $('#validationAlert').removeClass('alert-warning alert-danger').addClass('alert-success');
                        $('#validationMessage').html(
                            '<i class="fas fa-check-circle me-2"></i>All parameters are valid!');
                        $('#validationAlert').removeClass('d-none');
                        $('#submitBtn').prop('disabled', false);
                    } else {
                        $('#validationAlert').removeClass('alert-success').addClass('alert-danger');
                        let errorMsg = '<strong>Validation errors:</strong><ul class="mb-0">';
                        response.data.errors.forEach(function(error) {
                            errorMsg += '<li>' + error + '</li>';
                        });
                        errorMsg += '</ul>';
                        $('#validationMessage').html(errorMsg);
                        $('#validationAlert').removeClass('d-none');
                        $('#submitBtn').prop('disabled', true);
                    }
                });
        }

        function refreshTable() {
            location.reload();
        }

        function runGA(popId) {
            currentPopulationId = popId;
            $('#runGAPopId').text('#' + popId);
            $('#runGAModal').modal('show');
        }

        function confirmRunGA() {
            const runInBackground = $('#runInBackground').is(':checked');
            const form = $('<form>', {
                method: 'POST',
                action: '{{ url('new-algorithm/populations') }}/' + currentPopulationId + '/run-ga'
            });

            form.append($('<input>', {
                type: 'hidden',
                name: '_token',
                value: '{{ csrf_token() }}'
            }));
            form.append($('<input>', {
                type: 'hidden',
                name: 'run_in_background',
                value: runInBackground ? '1' : '0'
            }));

            $('body').append(form);
            form.submit();
        }

        function clonePopulation(popId) {
            currentPopulationId = popId;
            $('#clonePopId').text('#' + popId);
            $('#cloneForm')[0].reset();
            $('#cloneModal').modal('show');
        }

        function confirmClone() {
            const formData = new FormData($('#cloneForm')[0]);

            $.ajax({
                    url: '{{ url('new-algorithm/populations') }}/' + currentPopulationId + '/clone',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .done(function(response) {
                    $('#cloneModal').modal('hide');
                    toastr.success('Population cloned successfully');
                    setTimeout(() => location.reload(), 1500);
                })
                .fail(function(xhr) {
                    toastr.error('Failed to clone population: ' + (xhr.responseJSON?.message || 'Unknown error'));
                });
        }

        function deletePopulation(popId) {
            currentPopulationId = popId;
            $('#deletePopId').text('#' + popId);
            $('#deleteModal').modal('show');
        }

        function confirmDelete() {
            $.ajax({
                    url: '{{ url('new-algorithm/populations') }}/' + currentPopulationId,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .done(function(response) {
                    $('#deleteModal').modal('hide');
                    toastr.success('Population deleted successfully');
                    setTimeout(() => location.reload(), 1500);
                })
                .fail(function(xhr) {
                    toastr.error('Failed to delete population: ' + (xhr.responseJSON?.message || 'Unknown error'));
                });
        }
    </script>
@endpush
