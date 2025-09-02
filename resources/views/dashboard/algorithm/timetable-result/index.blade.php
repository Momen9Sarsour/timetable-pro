@extends('dashboard.layout')

@section('content')
    <div class="main-content">
        <div class="data-entry-container p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-4">
                <h1 class="data-entry-header mb-2 mb-md-0">📋 Timetable Generation Results</h1>
                <a href="{{ route('dashboard.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>

            @include('dashboard.data-entry.partials._status_messages')

            @if ($latestSuccessfulRun)
                <!-- Run Info Card -->
                <div class="alert alert-light border mb-4 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1 text-primary">Last Successful Run</h5>
                            <p class="mb-1 text-muted small">
                                <strong>ID:</strong> #{{ $latestSuccessfulRun->population_id }} |
                                <strong>Completed:</strong> {{ \Carbon\Carbon::parse($latestSuccessfulRun->end_time)->format('Y-m-d h:i A') }}
                            </p>
                            <p class="mb-0 text-muted small">
                                <strong>Settings:</strong>
                                Population Size: {{ $latestSuccessfulRun->population_size }} |
                                Generations Count: {{ $latestSuccessfulRun->generations_count }} |
                                Mutation Rate: {{ $latestSuccessfulRun->mutation_rate * 100 }}%
                            </p>
                        </div>
                        <span class="badge bg-success py-2 px-3">Completed</span>
                    </div>
                </div>

                <!-- Top 5 Chromosomes -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 pb-0">
                        <h5 class="mb-3">🏆 Top 5 Solutions</h5>
                        <p class="text-muted small mb-0">Ranked by penalty (lowest first), then by creation time (newest
                            first).</p>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60px;">Rank</th>
                                        <th>Chromosome</th>
                                        <th>Gen #</th>
                                        <th>Penalty</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($topChromosomes as $index => $chromosome)
                                        @php
                                            $isBest =
                                                $chromosome->chromosome_id == $latestSuccessfulRun->best_chromosome_id;
                                            $badgeColor = $isBest ? 'success' : 'secondary';
                                            $icon = $isBest ? 'fa-star' : 'fa-regular fa-star';
                                        @endphp
                                        <tr class="{{ $isBest ? 'table-success' : '' }} align-middle">
                                            <td><strong>#{{ $index + 1 }}</strong></td>
                                            <td>
                                                <code
                                                    class="text-muted">CHR-{{ str_pad($chromosome->chromosome_id, STR_PAD_LEFT) }}</code>
                                            </td>
                                            <td><span
                                                    class="badge bg-light text-dark">{{ $chromosome->generation_number }}</span>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-warning text-dark px-3 py-2 fs-6">{{ $chromosome->penalty_value }}</span>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $badgeColor }} d-flex align-items-center gap-1 w-fit px-2 py-1">
                                                    <i class="fas {{ $icon }} fa-xs"></i>
                                                    {{ $isBest ? 'Best' : 'Candidate' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="{{ route('algorithm-control.timetable.result.show', $chromosome) }}"
                                                        class="btn btn-sm btn-outline-primary px-3">
                                                        <i class="fas fa-eye me-1"></i> View
                                                    </a>

                                                    @if (!$isBest)
                                                        <form
                                                            action="{{ route('algorithm-control.timetable.result.set-best', $latestSuccessfulRun) }}"
                                                            method="POST" class="d-inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="chromosome_id"
                                                                value="{{ $chromosome->chromosome_id }}">
                                                            <button type="submit"
                                                                class="btn btn-sm btn-outline-success px-3">
                                                                <i class="fas fa-thumbs-up me-1"></i> Set as Best
                                                            </button>
                                                        </form>
                                                    @else
                                                        <button class="btn btn-sm btn-success px-3" disabled>
                                                            <i class="fas fa-check me-1"></i> Selected
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-lg mb-2"></i><br>
                                                No solutions found. The run might have failed or is still processing.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Info Note -->
                <div class="text-center mt-3">
                    <small class="text-muted">
                        💡 You can change the "Best Chromosome" to influence which timetable is displayed elsewhere.
                    </small>
                </div>
            @else
                <!-- No Runs Found -->
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Completed Runs Found</h4>
                    <p class="text-muted">Please run the timetable generation process from the dashboard.</p>
                    <a href="{{ route('dashboard.index') }}" class="btn btn-primary">
                        <i class="fas fa-cogs me-1"></i> Start Generation
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .w-fit {
            width: max-content;
        }

        .data-entry-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .table th,
        .table td {
            vertical-align: middle;
            text-align: center;
        }

        .badge {
            font-weight: 500;
        }

        .table-success {
            background-color: #d1e7dd !important;
        }

        @media (max-width: 576px) {
            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 0.5rem;
            }

            .table td {
                display: flex;
                justify-content: space-between;
                padding: 0.25rem 0;
                border: none;
            }
        }
    </style>
@endpush
