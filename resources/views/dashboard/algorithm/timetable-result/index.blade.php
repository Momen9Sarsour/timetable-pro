@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-calendar-check text-primary me-2"></i>
                        Timetable Generation Results
                    </h4>
                    <p class="text-muted mb-0">Review and manage generated timetable solutions</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('dashboard.index') }}" class="btn btn-outline-secondary btn-sm">
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

    @if ($latestSuccessfulRun)
        <!-- Run Info Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-success bg-opacity-10">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <h5 class="mb-0 text-success">Last Successful Run</h5>
                                </div>

                                <!-- Run Details - Desktop -->
                                <div class="d-none d-md-block">
                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6 col-lg-3">
                                            <small class="text-muted d-block">Run ID</small>
                                            <span class="fw-medium">#{{ $latestSuccessfulRun->population_id }}</span>
                                        </div>
                                        <div class="col-md-6 col-lg-3">
                                            <small class="text-muted d-block">Completed</small>
                                            <span class="fw-medium">{{ \Carbon\Carbon::parse($latestSuccessfulRun->end_time)->format('M d, Y h:i A') }}</span>
                                        </div>
                                        <div class="col-md-6 col-lg-3">
                                            <small class="text-muted d-block">Population Size</small>
                                            <span class="fw-medium">{{ $latestSuccessfulRun->population_size }}</span>
                                        </div>
                                        <div class="col-md-6 col-lg-3">
                                            <small class="text-muted d-block">Generations</small>
                                            <span class="fw-medium">{{ $latestSuccessfulRun->generations_count }}</span>
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6 col-lg-3">
                                            <small class="text-muted d-block">Mutation Rate</small>
                                            <span class="fw-medium">{{ $latestSuccessfulRun->mutation_rate * 100 }}%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Run Details - Mobile -->
                                <div class="d-md-none">
                                    <div class="small text-muted mb-1">
                                        <strong>ID:</strong> #{{ $latestSuccessfulRun->population_id }} |
                                        <strong>Completed:</strong> {{ \Carbon\Carbon::parse($latestSuccessfulRun->end_time)->format('M d, h:i A') }}
                                    </div>
                                    <div class="small text-muted">
                                        <strong>Population:</strong> {{ $latestSuccessfulRun->population_size }} |
                                        <strong>Generations:</strong> {{ $latestSuccessfulRun->generations_count }} |
                                        <strong>Mutation:</strong> {{ $latestSuccessfulRun->mutation_rate * 100 }}%
                                    </div>
                                </div>
                            </div>
                            <span class="badge bg-success px-3 py-2">
                                <i class="fas fa-check me-1"></i>Completed
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Solutions Card -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom-0 pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">
                                    <i class="fas fa-trophy text-warning me-2"></i>
                                    Top 5 Solutions
                                </h6>
                                <p class="text-muted small mb-0">Ranked by penalty (lowest first), then by creation time (newest first)</p>
                            </div>
                            <span class="badge bg-warning bg-opacity-10 text-warning">{{ count($topChromosomes) }} Solutions</span>
                        </div>
                    </div>

                    <div class="card-body pt-3">
                        @if($topChromosomes->count() > 0)
                            <!-- Desktop Table -->
                            <div class="table-responsive d-none d-lg-block">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0 text-center" style="width: 60px;">Rank</th>
                                            <th class="border-0">Chromosome ID</th>
                                            <th class="border-0 text-center" style="width: 100px;">Generation</th>
                                            <th class="border-0 text-center" style="width: 120px;">Penalty</th>
                                            <th class="border-0 text-center" style="width: 120px;">Status</th>
                                            <th class="border-0 text-center" style="width: 200px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($topChromosomes as $index => $chromosome)
                                            @php
                                                $isBest = $chromosome->chromosome_id == $latestSuccessfulRun->best_chromosome_id;
                                                $rankColors = ['primary', 'success', 'info', 'warning', 'secondary'];
                                                $rankColor = $rankColors[$index] ?? 'secondary';
                                            @endphp
                                            <tr class="{{ $isBest ? 'table-success' : '' }}">
                                                <td class="text-center">
                                                    <div class="d-flex align-items-center justify-content-center">
                                                        @if($index === 0)
                                                            <i class="fas fa-crown text-warning me-1"></i>
                                                        @endif
                                                        <span class="badge bg-{{ $rankColor }} bg-opacity-10 text-{{ $rankColor }} px-2 py-1">
                                                            #{{ $index + 1 }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-dna text-muted me-2"></i>
                                                        <code class="text-muted">CHR-{{ str_pad($chromosome->chromosome_id, 4, '0', STR_PAD_LEFT) }}</code>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark">Gen {{ $chromosome->generation_number }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 fw-bold">
                                                        {{ $chromosome->penalty_value }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    @if($isBest)
                                                        <span class="badge bg-success bg-opacity-10 text-success">
                                                            <i class="fas fa-star me-1"></i>Best Solution
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                            <i class="far fa-star me-1"></i>Candidate
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="{{ route('algorithm-control.timetable.result.show', $chromosome) }}"
                                                           class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        @if (!$isBest)
                                                            <form action="{{ route('algorithm-control.timetable.result.set-best', $latestSuccessfulRun) }}"
                                                                  method="POST" class="d-inline">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="chromosome_id" value="{{ $chromosome->chromosome_id }}">
                                                                <button type="submit" class="btn btn-outline-success btn-sm">
                                                                    <i class="fas fa-thumbs-up"></i>
                                                                </button>
                                                            </form>
                                                        @else
                                                            <button class="btn btn-success btn-sm" disabled>
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="fas fa-inbox text-muted mb-2" style="font-size: 2rem;"></i>
                                                    <p class="text-muted mb-0">No solutions found</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile/Tablet Cards -->
                            <div class="d-lg-none">
                                @forelse ($topChromosomes as $index => $chromosome)
                                    @php
                                        $isBest = $chromosome->chromosome_id == $latestSuccessfulRun->best_chromosome_id;
                                        $rankColors = ['primary', 'success', 'info', 'warning', 'secondary'];
                                        $rankColor = $rankColors[$index] ?? 'secondary';
                                    @endphp
                                    <div class="card mb-3 {{ $isBest ? 'border-success' : 'border' }}">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="d-flex align-items-center">
                                                    @if($index === 0)
                                                        <i class="fas fa-crown text-warning me-2"></i>
                                                    @endif
                                                    <span class="badge bg-{{ $rankColor }} bg-opacity-10 text-{{ $rankColor }} me-2">
                                                        #{{ $index + 1 }}
                                                    </span>
                                                    <div>
                                                        <h6 class="card-title mb-1">
                                                            <i class="fas fa-dna text-muted me-1"></i>
                                                            CHR-{{ str_pad($chromosome->chromosome_id, 4, '0', STR_PAD_LEFT) }}
                                                        </h6>
                                                        <small class="text-muted">Generation {{ $chromosome->generation_number }}</small>
                                                    </div>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('algorithm-control.timetable.result.show', $chromosome) }}">
                                                                <i class="fas fa-eye me-2"></i>View Details
                                                            </a>
                                                        </li>
                                                        @if (!$isBest)
                                                            <li>
                                                                <form action="{{ route('algorithm-control.timetable.result.set-best', $latestSuccessfulRun) }}"
                                                                      method="POST" class="d-inline w-100">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="chromosome_id" value="{{ $chromosome->chromosome_id }}">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-thumbs-up me-2"></i>Set as Best
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-warning bg-opacity-10 text-warning">
                                                        Penalty: {{ $chromosome->penalty_value }}
                                                    </span>
                                                    @if($isBest)
                                                        <span class="badge bg-success bg-opacity-10 text-success">
                                                            <i class="fas fa-star me-1"></i>Best Solution
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                            <i class="far fa-star me-1"></i>Candidate
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-4">
                                        <i class="fas fa-inbox text-muted mb-2" style="font-size: 2rem;"></i>
                                        <p class="text-muted mb-0">No solutions found</p>
                                    </div>
                                @endforelse
                            </div>

                        @else
                            <!-- Empty State -->
                            <div class="text-center py-5">
                                <i class="fas fa-inbox text-muted opacity-50" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 text-muted">No Solutions Available</h5>
                                <p class="text-muted mb-0">The run might have failed or is still processing.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Note -->
        @if($topChromosomes->count() > 0)
            <div class="row mt-4">
                <div class="col-12">
                    <div class="alert alert-info border-0">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle me-2 mt-1"></i>
                            <div>
                                <small class="mb-0">
                                    <strong>Pro Tip:</strong> You can change the "Best Chromosome" to influence which timetable is displayed elsewhere in the system.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    @else
        <!-- No Runs Found -->
        <div class="row">
            <div class="col-12">
                <div class="text-center py-5">
                    <div class="empty-state">
                        <i class="fas fa-calendar-times text-muted opacity-50" style="font-size: 4rem;"></i>
                        <h5 class="mt-3 text-muted">No Completed Runs Found</h5>
                        <p class="text-muted mb-4">Please run the timetable generation process from the dashboard to see results here.</p>
                        <a href="{{ route('dashboard.index') }}" class="btn btn-primary">
                            <i class="fas fa-cogs me-2"></i>Start Generation
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
/* Custom styles for timetable results page */
.table-success {
    background-color: rgba(25, 135, 84, 0.1) !important;
    border-color: rgba(25, 135, 84, 0.2) !important;
}

.card.border-success {
    border-color: var(--bs-success) !important;
    border-width: 2px;
}

/* Rank badges with special styling */
.badge.bg-primary.bg-opacity-10 { border: 1px solid var(--bs-primary); }
.badge.bg-success.bg-opacity-10 { border: 1px solid var(--bs-success); }
.badge.bg-info.bg-opacity-10 { border: 1px solid var(--bs-info); }
.badge.bg-warning.bg-opacity-10 { border: 1px solid var(--bs-warning); }
.badge.bg-secondary.bg-opacity-10 { border: 1px solid var(--bs-secondary); }

/* Crown animation for #1 rank */
@keyframes crownGlow {
    0%, 100% { transform: scale(1); filter: brightness(1); }
    50% { transform: scale(1.1); filter: brightness(1.2); }
}

.fa-crown {
    animation: crownGlow 2s infinite ease-in-out;
}

/* Enhanced code styling */
code {
    font-size: 0.85rem;
    padding: 0.3rem 0.5rem;
    background: rgba(var(--bs-primary-rgb), 0.1);
    border-radius: 0.25rem;
    border: 1px solid rgba(var(--bs-primary-rgb), 0.2);
}

/* Penalty value emphasis */
.badge.bg-warning.bg-opacity-10.text-warning.fw-bold {
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--bs-warning);
}

/* Success run card styling */
.card.bg-success.bg-opacity-10 {
    border: 1px solid rgba(25, 135, 84, 0.3) !important;
}

/* Mobile enhancements */
@media (max-width: 768px) {
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
    }

    .badge {
        font-size: 0.7rem;
    }

    .card-title {
        font-size: 0.95rem;
    }
}

/* Empty state enhancements */
.empty-state i {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Form submission in dropdown */
.dropdown-menu form {
    margin: 0;
}

.dropdown-menu form button {
    width: 100%;
    text-align: left;
    border: none;
    background: none;
    padding: 0.25rem 1rem;
}

.dropdown-menu form button:hover {
    background-color: var(--bs-dropdown-link-hover-bg);
}

/* DNA icon animation */
.fa-dna {
    animation: dnaRotate 3s infinite linear;
}

@keyframes dnaRotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Hover effects for solution cards */
.card:hover .fa-dna {
    color: var(--bs-primary) !important;
}

.table tbody tr:hover .fa-dna {
    color: var(--bs-primary) !important;
    transition: color 0.3s ease;
}
</style>
@endpush
