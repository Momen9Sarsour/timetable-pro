@extends('dashboard.layout')

@section('title', 'GA Results - Population #' . $population->population_id)

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-chart-line text-success me-2"></i>
                GA Results - Population #{{ $population->population_id }}
            </h1>
            <p class="text-muted">Genetic Algorithm execution results and statistics</p>
        </div>
    </div>

    <!-- Population Info -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Population Information</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>Status:</strong>
                    <span class="badge bg-{{ $population->status === 'completed' ? 'success' : 'info' }}">
                        {{ ucfirst($population->status) }}
                    </span>
                </div>
                <div class="col-md-3">
                    <strong>Population Size:</strong> {{ $population->population_size }}
                </div>
                <div class="col-md-3">
                    <strong>Max Generations:</strong> {{ $population->max_generations }}
                </div>
                <div class="col-md-3">
                    <strong>Best Fitness:</strong>
                    {{ $population->bestChromosome ? number_format($population->bestChromosome->fitness_value, 4) : 'N/A' }}
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-4">
                    <strong>Started:</strong> {{ $population->start_time ?? 'N/A' }}
                </div>
                <div class="col-md-4">
                    <strong>Ended:</strong> {{ $population->end_time ?? 'N/A' }}
                </div>
                <div class="col-md-4">
                    <strong>Duration:</strong>
                    {{ $population->start_time && $population->end_time
                        ? \Carbon\Carbon::parse($population->start_time)->diffForHumans($population->end_time, true)
                        : 'N/A' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Best Chromosome -->
    @if($population->bestChromosome)
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">Best Chromosome Details</h6>
        </div>
        <div class="card-body">
            <p><strong>Chromosome ID:</strong> #{{ $population->bestChromosome->chromosome_id }}</p>
            <p><strong>Fitness Value:</strong> {{ number_format($population->bestChromosome->fitness_value, 6) }}</p>
            <a href="{{ route('new-algorithm.populations.chromosome-schedule', [$population->population_id, $population->bestChromosome->chromosome_id]) }}"
               class="btn btn-primary">
                <i class="fas fa-calendar me-2"></i>
                View Full Schedule
            </a>
        </div>
    </div>
    @endif

    <!-- Actions -->
    <div class="card shadow">
        <div class="card-body text-center">
            <a href="{{ route('new-algorithm.populations.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Back to Populations
            </a>
            <a href="{{ route('new-algorithm.populations.best-chromosomes', $population->population_id) }}"
               class="btn btn-info">
                <i class="fas fa-dna me-2"></i>
                View All Chromosomes
            </a>
        </div>
    </div>
</div>
@endsection
