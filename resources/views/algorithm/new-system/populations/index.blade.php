@extends('dashboard.layout')

@section('title', 'Populations Management - New GA System')

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <h1 class="h3 mb-2 text-gray-800 d-flex align-items-center">
                            <i class="fas fa-dna text-primary me-2"></i>
                            <span>Populations Management</span>
                        </h1>
                        <p class="text-muted mb-0 small">Manage and execute genetic algorithm populations for schedule optimization</p>
                    </div>
                    <div class="flex-shrink-0">
                        <button class="btn btn-primary w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#createPopulationModal">
                            <i class="fas fa-plus me-2"></i>
                            <span class="d-none d-sm-inline">Create New </span>Population
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Breadcrumb -->
        <div class="row mb-3">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 small">
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
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs text-uppercase text-muted mb-1 fw-bold">Total</div>
                                <div class="h4 mb-0 fw-bold">{{ $populations->total() }}</div>
                            </div>
                            <div class="text-primary opacity-75">
                                <i class="fas fa-dna fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-primary bg-opacity-10 border-0 p-2">
                        <small class="text-primary fw-semibold"><i class="fas fa-database me-1"></i>Populations</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs text-uppercase text-muted mb-1 fw-bold">Completed</div>
                                <div class="h4 mb-0 fw-bold text-success">{{ $populations->where('status', 'completed')->count() }}</div>
                            </div>
                            <div class="text-success opacity-75">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-success bg-opacity-10 border-0 p-2">
                        <small class="text-success fw-semibold"><i class="fas fa-check me-1"></i>Success</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs text-uppercase text-muted mb-1 fw-bold">Running</div>
                                <div class="h4 mb-0 fw-bold text-info">{{ $populations->where('status', 'running')->count() }}</div>
                            </div>
                            <div class="text-info opacity-75">
                                <i class="fas fa-spinner fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-info bg-opacity-10 border-0 p-2">
                        <small class="text-info fw-semibold"><i class="fas fa-sync me-1"></i>In Progress</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs text-uppercase text-muted mb-1 fw-bold">Failed</div>
                                <div class="h4 mb-0 fw-bold text-danger">{{ $populations->where('status', 'failed')->count() }}</div>
                            </div>
                            <div class="text-danger opacity-75">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-danger bg-opacity-10 border-0 p-2">
                        <small class="text-danger fw-semibold"><i class="fas fa-times me-1"></i>Errors</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Populations Table -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                    <h6 class="m-0 fw-bold text-primary d-flex align-items-center">
                        <i class="fas fa-table me-2"></i>
                        All Populations
                    </h6>
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshTable()">
                        <i class="fas fa-sync me-1"></i>
                        Refresh
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Desktop/Tablet Table View -->
                <div class="table-responsive d-none d-lg-block">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">ID</th>
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
                                    <td class="px-3">
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
                                            <div>
                                                <div class="progress mb-1" style="height: 20px; min-width: 100px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%">
                                                        #{{ number_format($population->best_chromosome_id) }}
                                                    </div>
                                                </div>
                                                <small class="text-muted d-flex align-items-center">
                                                    <i class="fas fa-chart-line me-1 text-success"></i>
                                                    Fitness: <strong class="text-success ms-1">{{ number_format($population->bestChromosome->fitness_value, 2) }}</strong>
                                                </small>
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>
                                            <div><strong>Crossover:</strong> {{ optional($population->crossoverType)->name ?? 'N/A' }}</div>
                                            <div><strong>Selection:</strong> {{ optional($population->selectionType)->name ?? 'N/A' }}</div>
                                            <div><strong>Mutation:</strong> {{ $population->mutation_rate }}</div>
                                        </small>
                                    </td>
                                    <td>
                                        <small>{{ $population->created_at->format('Y-m-d') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('new-algorithm.populations.best-chromosomes', $population->population_id) }}"
                                                class="btn btn-sm btn-info" title="View Best Chromosomes">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if ($population->status !== 'running')
                                                <button class="btn btn-sm btn-success"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#runGAModal"
                                                    onclick="prepareRunGA({{ $population->population_id }})"
                                                    title="Run GA">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            @endif
                                            {{-- <button class="btn btn-sm btn-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#cloneModal"
                                                onclick="prepareClone({{ $population->population_id }})"
                                                title="Clone">
                                                <i class="fas fa-clone"></i>
                                            </button> --}}
                                            @if ($population->status !== 'running')
                                                <button class="btn btn-sm btn-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal"
                                                    onclick="prepareDelete({{ $population->population_id }})"
                                                    title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted mb-3">No populations found. Create your first population to get started.</p>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPopulationModal">
                                            <i class="fas fa-plus me-2"></i>Create Population
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Mobile/Tablet Card View -->
                <div class="d-lg-none p-3">
                    @forelse($populations as $population)
                        <div class="card mb-3 shadow-sm border">
                            <div class="card-body p-3">
                                <!-- Header -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="mb-1 text-primary fw-bold">#{{ $population->population_id }}</h6>
                                        <div>
                                            <span class="badge bg-secondary me-1">{{ $population->academic_year }}</span>
                                            <span class="badge bg-info">S{{ $population->semester }}</span>
                                        </div>
                                    </div>
                                    <div>
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
                                    </div>
                                </div>

                                <!-- Info Grid -->
                                <div class="row g-2 mb-3 small">
                                    <div class="col-6">
                                        <div class="text-muted">Size</div>
                                        <div class="fw-semibold">
                                            <i class="fas fa-users text-primary me-1"></i>{{ $population->population_size }}
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Generations</div>
                                        <div class="fw-semibold">
                                            <i class="fas fa-layer-group text-primary me-1"></i>{{ $population->max_generations }}
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Crossover</div>
                                        <div class="fw-semibold text-truncate">{{ optional($population->crossoverType)->name ?? 'N/A' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Selection</div>
                                        <div class="fw-semibold text-truncate">{{ optional($population->selectionType)->name ?? 'N/A' }}</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="text-muted">Created</div>
                                        <div class="fw-semibold">{{ $population->created_at->format('Y-m-d H:i') }}</div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="d-grid gap-2">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('new-algorithm.populations.best-chromosomes', $population->population_id) }}"
                                            class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                        @if ($population->status !== 'running')
                                            <button class="btn btn-sm btn-outline-success"
                                                data-bs-toggle="modal"
                                                data-bs-target="#runGAModal"
                                                onclick="prepareRunGA({{ $population->population_id }})">
                                                <i class="fas fa-play me-1"></i>Run
                                            </button>
                                        @endif
                                        {{-- <button class="btn btn-sm btn-outline-warning"
                                            data-bs-toggle="modal"
                                            data-bs-target="#cloneModal"
                                            onclick="prepareClone({{ $population->population_id }})">
                                            <i class="fas fa-clone me-1"></i>Clone
                                        </button> --}}
                                        @if ($population->status !== 'running')
                                            <button class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal"
                                                onclick="prepareDelete({{ $population->population_id }})">
                                                <i class="fas fa-trash me-1"></i>Delete
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted mb-3">No populations found</p>
                            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#createPopulationModal">
                                <i class="fas fa-plus me-2"></i>Create Population
                            </button>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if ($populations->hasPages())
                    <div class="d-flex justify-content-center p-3 border-top">
                        {{ $populations->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Run GA Modal -->
    <div class="modal fade" id="runGAModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="runGAForm" method="POST" action="">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-play me-2"></i>Run Genetic Algorithm
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to run the genetic algorithm on population <strong id="runGAPopId"></strong>?</p>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="run_in_background" value="1" id="runInBackground" checked>
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
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-play me-2"></i>Start Algorithm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Clone Population Modal -->
    <div class="modal fade" id="cloneModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="cloneForm" method="POST" action="">
                    @csrf
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">
                            <i class="fas fa-clone me-2"></i>Clone Population
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
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
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-clone me-2"></i>Clone Population
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="deleteForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Population
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Population Modal -->
    <div class="modal fade" id="createPopulationModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Create New Population
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('new-algorithm.populations.generate') }}" method="POST" id="createPopulationForm">
                    @csrf
                    <div class="modal-body">
                        <!-- Validation Alert -->
                        <div id="validationAlert" class="alert alert-warning d-none">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span id="validationMessage"></span>
                        </div>

                        <div class="row g-3">
                            <!-- Left Column: Basic Settings -->
                            <div class="col-lg-6">
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 text-primary">
                                            <i class="fas fa-cog me-2"></i>Basic Settings
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Academic Year <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="academic_year"
                                                value="{{ date('Y') }}" min="2020" max="2030" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Semester <span class="text-danger">*</span></label>
                                            <select class="form-select" name="semester" required>
                                                <option value="1">First Semester</option>
                                                <option value="2">Second Semester</option>
                                            </select>
                                        </div>

                                        <div class="row">
                                            <div class="col-6 mb-3">
                                                <label class="form-label fw-bold">Theory Slots</label>
                                                <input type="number" class="form-control" name="theory_credit_to_slots"
                                                    value="1" min="1" max="5" required>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label class="form-label fw-bold">Practical Slots</label>
                                                <input type="number" class="form-control" name="practical_credit_to_slots"
                                                    value="2" min="1" max="8" required>
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
                                            <label class="form-label fw-bold">Crossover Type</label>
                                            <select class="form-select" name="crossover_id" required>
                                                @foreach ($crossoverTypes ?? [] as $type)
                                                    <option value="{{ $type->crossover_id }}">{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Selection Type</label>
                                            <select class="form-select" name="selection_id" required>
                                                @foreach ($selectionTypes ?? [] as $type)
                                                    <option value="{{ $type->selection_type_id }}">{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Mutation Type</label>
                                            <select class="form-select" name="mutation_id" required>
                                                @foreach ($mutationTypes ?? [] as $type)
                                                    <option value="{{ $type->mutation_id }}">{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: GA Parameters -->
                            <div class="col-lg-6">
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 text-primary">
                                            <i class="fas fa-dna me-2"></i>Population Parameters
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Population Size <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="population_size"
                                                value="10" min="10" max="1000" required id="popSize">
                                            <small class="text-muted">Number of chromosomes (schedules)</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Max Generations <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="max_generations"
                                                value="10" min="1" max="1000" required>
                                            <small class="text-muted">Maximum evolution cycles</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Elitism Count <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="elitism_count"
                                                value="5" min="1" max="50" required>
                                            <small class="text-muted">Best chromosomes preserved</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Selection Size <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="selection_size"
                                                value="5" min="2" max="20" required>
                                            <small class="text-muted">Tournament pool size</small>
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
                                            <label class="form-label fw-bold">Crossover Rate <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" class="form-control"
                                                name="crossover_rate" value="0.95" min="0" max="1" required>
                                            <small class="text-muted">Probability (0.0 - 1.0)</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Mutation Rate <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" class="form-control"
                                                name="mutation_rate" value="0.05" min="0" max="1" required>
                                            <small class="text-muted">Probability (0.0 - 1.0)</small>
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
                                                Run in background
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
                                <div class="row text-center g-3" id="estimatedStats">
                                    <div class="col-6 col-md-3">
                                        <div class="fw-bold text-primary small">Total Genes</div>
                                        <div class="h5 mb-0" id="estGenes">-</div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="fw-bold text-success small">Timeslots</div>
                                        <div class="h5 mb-0" id="estTimeslots">-</div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="fw-bold text-info small">Sections</div>
                                        <div class="h5 mb-0" id="estSections">-</div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="fw-bold text-warning small">Est. Time</div>
                                        <div class="h5 mb-0" id="estTime">-</div>
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
                            <i class="fas fa-check-circle me-2"></i>Validate
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-play me-2"></i>Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        /* تحسينات إضافية للاستجابة */
        .text-xs {
            font-size: 0.75rem;
        }

        /* تحسين الكروت في الموبايل */
        @media (max-width: 576px) {
            .card-body {
                padding: 0.75rem;
            }

            .btn-group {
                display: flex;
                flex-wrap: nowrap;
            }

            .btn-group .btn {
                flex: 1;
                font-size: 0.75rem;
                padding: 0.375rem 0.5rem;
            }
        }

        /* تحسين المودال في الموبايل */
        @media (max-width: 768px) {
            .modal-xl {
                margin: 0.5rem;
            }

            .modal-dialog-scrollable .modal-body {
                max-height: calc(100vh - 200px);
            }
        }

        /* تحسين الجدول */
        .table thead th {
            white-space: nowrap;
            font-size: 0.8125rem;
        }

        .table tbody td {
            vertical-align: middle;
        }

        /* تحسين البادجات */
        .badge {
            font-size: 0.7rem;
            padding: 0.35em 0.6em;
        }

        /* تحسين الأزرار */
        .btn-sm {
            font-size: 0.8125rem;
            padding: 0.375rem 0.75rem;
        }

        /* تحسين Progress Bar */
        .progress {
            background-color: rgba(0, 0, 0, 0.05);
        }

        body.dark-mode .progress {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* تحسين الكروت الإحصائية */
        .card-footer {
            padding: 0.5rem 1rem;
        }

        /* تحسين عرض النصوص الطويلة */
        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* تحسين الأيقونات */
        .fa-spin {
            animation: fa-spin 1s infinite linear;
        }

        /* تحسين المسافات في الموبايل */
        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .row.g-3 {
                --bs-gutter-x: 0.75rem;
                --bs-gutter-y: 0.75rem;
            }
        }

        /* تحسين الـ Scrollbar في المودال */
        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Calculate stats on population size change
            $('#popSize').on('input', function() {
                calculateEstimatedStats();
            });

            // Form submission handlers
            $('#createPopulationForm').on('submit', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to generate this population?')) {
                    this.submit();
                }
            });

            $('#runGAForm, #cloneForm, #deleteForm').on('submit', function(e) {
                e.preventDefault();
                this.submit();
            });
        });

        // Prepare modals
        function prepareRunGA(popId) {
            const actionUrl = '{{ url("dashboard/new-algorithm/populations") }}/' + popId + '/run-ga';
            document.getElementById('runGAPopId').textContent = '#' + popId;
            document.getElementById('runGAForm').setAttribute('action', actionUrl);
        }

        function prepareClone(popId) {
            const actionUrl = '{{ url("dashboard/new-algorithm/populations") }}/' + popId + '/clone';
            document.getElementById('clonePopId').textContent = '#' + popId;
            document.getElementById('cloneForm').setAttribute('action', actionUrl);
            document.getElementById('cloneForm').reset();
        }

        function prepareDelete(popId) {
            const actionUrl = '{{ url("dashboard/new-algorithm/populations") }}/' + popId;
            document.getElementById('deletePopId').textContent = '#' + popId;
            document.getElementById('deleteForm').setAttribute('action', actionUrl);
        }

        // Calculate estimated statistics
        function calculateEstimatedStats() {
            const popSize = parseInt($('#popSize').val()) || 50;

            $.post('{{ route("new-algorithm.generation-stats") }}', {
                population_size: popSize,
                _token: '{{ csrf_token() }}'
            })
            .done(function(response) {
                if (response.success) {
                    $('#estGenes').text(response.data.total_genes.toLocaleString());
                    $('#estTimeslots').text(response.data.estimated_timeslots.toLocaleString());
                    $('#estSections').text(response.data.sections_count.toLocaleString());
                    const estMinutes = Math.ceil((response.data.total_genes / 1000) * 2);
                    $('#estTime').text(estMinutes + ' min');
                }
            })
            .fail(function() {
                console.log('Failed to calculate stats');
            });
        }

        // Validate parameters
        function validateParameters() {
            const popSize = parseInt($('input[name="population_size"]').val());
            const maxGen = parseInt($('input[name="max_generations"]').val());

            $.post('{{ route("new-algorithm.validate-params") }}', {
                population_size: popSize,
                max_generations: maxGen,
                _token: '{{ csrf_token() }}'
            })
            .done(function(response) {
                const alert = $('#validationAlert');
                const message = $('#validationMessage');

                if (response.success) {
                    alert.removeClass('alert-warning alert-danger').addClass('alert-success');
                    message.html('<i class="fas fa-check-circle me-2"></i>All parameters are valid!');
                    alert.removeClass('d-none');
                    $('#submitBtn').prop('disabled', false);
                } else {
                    alert.removeClass('alert-success').addClass('alert-danger');
                    let errorMsg = '<strong>Validation errors:</strong><ul class="mb-0 ps-3">';
                    response.data.errors.forEach(function(error) {
                        errorMsg += '<li>' + error + '</li>';
                    });
                    errorMsg += '</ul>';
                    message.html(errorMsg);
                    alert.removeClass('d-none');
                    $('#submitBtn').prop('disabled', true);
                }
            })
            .fail(function() {
                const alert = $('#validationAlert');
                alert.removeClass('alert-success').addClass('alert-danger');
                $('#validationMessage').html('<i class="fas fa-times-circle me-2"></i>Failed to validate');
                alert.removeClass('d-none');
            });
        }

        // Refresh table
        function refreshTable() {
            location.reload();
        }
    </script>
@endpush
