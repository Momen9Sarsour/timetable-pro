@extends('dashboard.layout')

@section('title', 'Best Chromosomes - Population #' . $population->population_id)

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <h1 class="h3 mb-2 text-gray-800 d-flex align-items-center">
                            <i class="fas fa-trophy text-warning me-2"></i>
                            <span>Best Chromosomes</span>
                        </h1>
                        <p class="text-muted mb-0 small">
                            Population #{{ $population->population_id }} - Top 10 highest fitness values
                        </p>
                    </div>
                    <a href="{{ route('new-algorithm.populations.index') }}" class="btn btn-secondary  w-md-auto">
                        <i class="fas fa-arrow-left me-2"></i>Back Population 
                    </a>
                </div>
            </div>
        </div>

        <!-- Population Info Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs text-uppercase text-muted mb-1 fw-bold">Size</div>
                                <div class="h4 mb-0 fw-bold">{{ $population->population_size }}</div>
                            </div>
                            <div class="text-primary opacity-75">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-primary bg-opacity-10 border-0 p-2">
                        <small class="text-primary fw-semibold"><i class="fas fa-database me-1"></i>Population</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="w-100">
                                <div class="text-xs text-uppercase text-muted mb-1 fw-bold">Status</div>
                                <span class="badge bg-{{ $population->status === 'completed' ? 'success' : ($population->status === 'running' ? 'info' : 'secondary') }}">
                                    <i class="fas fa-{{ $population->status === 'completed' ? 'check' : ($population->status === 'running' ? 'spinner fa-spin' : 'clock') }} me-1"></i>
                                    {{ ucfirst($population->status) }}
                                </span>
                            </div>
                            <div class="text-success opacity-75">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-success bg-opacity-10 border-0 p-2">
                        <small class="text-success fw-semibold"><i class="fas fa-flag me-1"></i>Current Status</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs text-uppercase text-muted mb-1 fw-bold">Max Gen</div>
                                <div class="h4 mb-0 fw-bold text-info">{{ $population->max_generations }}</div>
                            </div>
                            <div class="text-info opacity-75">
                                <i class="fas fa-layer-group fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-info bg-opacity-10 border-0 p-2">
                        <small class="text-info fw-semibold"><i class="fas fa-infinity me-1"></i>Generations</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs text-uppercase text-muted mb-1 fw-bold">Year</div>
                                <div class="h5 mb-0 fw-bold text-warning">
                                    {{ $population->academic_year }}
                                    <small class="text-muted">S{{ $population->semester }}</small>
                                </div>
                            </div>
                            <div class="text-warning opacity-75">
                                <i class="fas fa-calendar-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-warning bg-opacity-10 border-0 p-2">
                        <small class="text-warning fw-semibold"><i class="fas fa-graduation-cap me-1"></i>Academic</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Best Chromosomes Table -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 fw-bold text-primary d-flex align-items-center">
                    <i class="fas fa-list me-2"></i>
                    Top 10 Chromosomes
                </h6>
            </div>
            <div class="card-body p-0">
                <!-- Desktop/Tablet Table View -->
                <div class="table-responsive d-none d-lg-block">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">Rank</th>
                                <th>Chromosome ID</th>
                                <th>Fitness Value</th>
                                <th>Penalty</th>
                                <th>Generation</th>
                                <th class="text-center">Best</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bestChromosomes as $index => $chromosome)
                                <tr>
                                    <td class="px-3">
                                        @if($index === 0)
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-crown me-1"></i>#{{ $index + 1 }}
                                            </span>
                                        @elseif($index < 3)
                                            <span class="badge bg-info">
                                                <i class="fas fa-medal me-1"></i>#{{ $index + 1 }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                #{{ $index + 1 }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="text-primary">#{{ $chromosome->chromosome_id }}</strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 25px; min-width: 120px;">
                                                <div class="progress-bar bg-success" role="progressbar"
                                                    style="width: {{ min($chromosome->fitness_value * 100, 100) }}%">
                                                    {{ number_format($chromosome->fitness_value, 4) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($chromosome->penalty_value == 0)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>0
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i>{{ $chromosome->penalty_value }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">Gen {{ $chromosome->generation_number }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($chromosome->is_best_of_generation)
                                            <i class="fas fa-star text-warning fa-lg" title="Best of Generation"></i>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('new-algorithm.populations.chromosome-schedule', [$population->population_id, $chromosome->chromosome_id]) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-calendar-alt me-1"></i>View Schedule
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted">No chromosomes found for this population</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Mobile/Tablet Card View -->
                <div class="d-lg-none p-3">
                    @forelse($bestChromosomes as $index => $chromosome)
                        <div class="card mb-3 shadow-sm border">
                            <div class="card-body p-3">
                                <!-- Header with Rank -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="mb-1 text-primary fw-bold">
                                            #{{ $chromosome->chromosome_id }}
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-layer-group me-1"></i>
                                            Generation {{ $chromosome->generation_number }}
                                        </small>
                                    </div>
                                    <div>
                                        @if($index === 0)
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-crown me-1"></i>Rank #{{ $index + 1 }}
                                            </span>
                                        @elseif($index < 3)
                                            <span class="badge bg-info">
                                                <i class="fas fa-medal me-1"></i>Rank #{{ $index + 1 }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                Rank #{{ $index + 1 }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Fitness Value -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted fw-semibold">Fitness Value</small>
                                        <small class="fw-bold text-success">{{ number_format($chromosome->fitness_value, 4) }}</small>
                                    </div>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" role="progressbar"
                                            style="width: {{ min($chromosome->fitness_value * 100, 100) }}%">
                                        </div>
                                    </div>
                                </div>

                                <!-- Info Grid -->
                                <div class="row g-2 mb-3 small">
                                    <div class="col-6">
                                        <div class="text-muted">Penalty Value</div>
                                        <div>
                                            @if($chromosome->penalty_value == 0)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>0
                                                </span>
                                            @else
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>{{ $chromosome->penalty_value }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Best of Gen</div>
                                        <div>
                                            @if($chromosome->is_best_of_generation)
                                                <i class="fas fa-star text-warning"></i>
                                                <span class="text-warning fw-semibold">Yes</span>
                                            @else
                                                <span class="text-muted">No</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Button -->
                                <a href="{{ route('new-algorithm.populations.chromosome-schedule', [$population->population_id, $chromosome->chromosome_id]) }}"
                                    class="btn btn-primary w-100 btn-sm">
                                    <i class="fas fa-calendar-alt me-1"></i>View Schedule
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted">No chromosomes found</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* تحسينات إضافية */
        .text-xs {
            font-size: 0.75rem;
        }

        /* تحسين Progress Bar */
        .progress {
            background-color: rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
        }

        body.dark-mode .progress {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .progress-bar {
            transition: width 0.3s ease;
        }

        /* تحسين البادجات */
        .badge {
            font-size: 0.7rem;
            padding: 0.35em 0.6em;
        }

        /* تحسين الكروت في الموبايل */
        @media (max-width: 576px) {
            .card-body {
                padding: 0.75rem;
            }

            .btn-sm {
                font-size: 0.8125rem;
                padding: 0.375rem 0.75rem;
            }
        }

        /* تحسين الجدول */
        .table thead th {
            white-space: nowrap;
            font-size: 0.8125rem;
            padding: 0.75rem;
        }

        .table tbody td {
            vertical-align: middle;
            padding: 0.75rem;
        }

        /* تحسين أيقونة التاج والميدالية */
        .fa-crown, .fa-medal {
            animation: shine 2s infinite;
        }

        @keyframes shine {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        /* تحسين النجمة */
        .fa-star {
            filter: drop-shadow(0 0 3px rgba(255, 193, 7, 0.5));
        }

        /* تحسين الأزرار */
        .btn {
            transition: all 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        /* تحسين الكروت الإحصائية */
        .card-footer {
            font-size: 0.75rem;
        }

        /* تحسين المسافات في الموبايل */
        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Add animation to progress bars on page load
            $('.progress-bar').each(function() {
                const width = $(this).css('width');
                $(this).css('width', '0');
                setTimeout(() => {
                    $(this).css('width', width);
                }, 100);
            });

            // Add tooltip to best of generation stars
            $('[title]').tooltip();
        });
    </script>
@endpush
