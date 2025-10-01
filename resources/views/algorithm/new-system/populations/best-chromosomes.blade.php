@extends('dashboard.layout')

@section('title', 'Best Chromosomes - Population #' . $population->population_id)

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-trophy text-warning me-2"></i>
                            Best Chromosomes - Population #{{ $population->population_id }}
                        </h1>
                        <p class="text-muted mb-0">Top 10 chromosomes with highest fitness values</p>
                    </div>
                    <a href="{{ route('new-algorithm.populations.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Populations
                    </a>
                </div>
            </div>
        </div>

        <!-- Population Info -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Population Size</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $population->population_size }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Status</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <span class="badge bg-{{ $population->status === 'completed' ? 'success' : 'info' }}">
                                {{ ucfirst($population->status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Max Generations</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $population->max_generations }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Academic Year</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $population->academic_year }} -
                            S{{ $population->semester }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Best Chromosomes Table -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-list me-2"></i>
                    Top 10 Chromosomes
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Rank</th>
                                <th>Chromosome ID</th>
                                <th>Fitness Value</th>
                                <th>Penalty Value</th>
                                <th>Generation</th>
                                <th>Is Best</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bestChromosomes as $index => $chromosome)
                                <tr>
                                    <td>
                                        <span
                                            class="badge bg-{{ $index === 0 ? 'warning' : ($index < 3 ? 'info' : 'secondary') }}">
                                            #{{ $index + 1 }}
                                        </span>
                                    </td>
                                    <td><strong class="text-primary">#{{ $chromosome->chromosome_id }}</strong></td>
                                    <td>
                                        <div class="progress" style="height: 25px; min-width: 150px;">
                                            <div class="progress-bar bg-success" role="progressbar"
                                                style="width: {{ $chromosome->fitness_value * 100 }}%">
                                                {{ number_format($chromosome->fitness_value, 4) }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-{{ $chromosome->penalty_value == 0 ? 'success' : 'danger' }}">
                                            {{ $chromosome->penalty_value }}
                                        </span>
                                    </td>
                                    <td>{{ $chromosome->generation_number }}</td>
                                    <td>
                                        @if ($chromosome->is_best_of_generation)
                                            <i class="fas fa-star text-warning" title="Best of Generation"></i>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('new-algorithm.populations.chromosome-schedule', [$population->population_id, $chromosome->chromosome_id]) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            View Schedule
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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

        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }
    </style>
@endpush
