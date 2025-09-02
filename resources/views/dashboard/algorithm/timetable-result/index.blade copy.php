@extends('dashboard.layout')

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h1 class="data-entry-header mb-0">Timetable Generation Results</h1>
             {{-- يمكنك إضافة زر للعودة للداشبورد هنا --}}
             <a href="{{ route('dashboard.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
             </a>
        </div>

        @include('dashboard.data-entry.partials._status_messages')

        {{-- عرض أفضل 5 حلول من آخر عملية تشغيل ناجحة --}}
        @if($latestSuccessfulRun)
            {{-- معلومات عن عملية التشغيل التي يتم عرض نتائجها --}}
            <div class="alert alert-info mb-4">
                <h5 class="alert-heading">Displaying Top 5 Solutions from Last Successful Run</h5>
                <hr>
                <p class="mb-1 small">
                    <strong>Run ID:</strong> {{ $latestSuccessfulRun->population_id }} |
                    <strong>Status:</strong> <span class="badge bg-success">{{ $latestSuccessfulRun->status }}</span> |
                    <strong>Completed At:</strong> {{ \Carbon\Carbon::parse($latestSuccessfulRun->end_time)->format('Y-m-d h:i A') }}
                </p>
                 <p class="mb-0 small">
                    <strong>Settings Used:</strong>
                    Population: {{ $latestSuccessfulRun->population_size }} |
                    Max Generations: {{ $latestSuccessfulRun->generations_count }} |
                    Mutation Rate: {{ $latestSuccessfulRun->mutation_rate * 100 }}%
                </p>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Top 5 Solutions (Ranked by Best Fitness)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Rank</th>
                                    <th>Chromosome ID</th>
                                    <th>Generation No.</th>
                                    <th>Penalty Score (Lower is Better)</th>
                                    <th>Is Best of Gen.</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topChromosomes as $index => $chromosome)
                                <tr>
                                    <td><strong>#{{ $index + 1 }}</strong></td>
                                    <td>{{ $chromosome->chromosome_id }}</td>
                                    <td>{{ $chromosome->generation_number }}</td>
                                    <td><span class="badge bg-warning text-dark fs-6">{{ $chromosome->penalty_value }}</span></td>
                                    <td>
                                        @if($chromosome->is_best_of_generation)
                                            <i class="fas fa-check-circle text-success" title="Best solution of its generation"></i>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('algorithm-control.timetable.result.show', $chromosome->chromosome_id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted">No solutions found for this run. It might have failed or is still processing.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            {{-- رسالة تظهر إذا لم تكن هناك أي عمليات تشغيل مكتملة --}}
            <div class="alert alert-secondary text-center">
                <h4>No Completed Generation Runs Found</h4>
                <p>Please run the timetable generation process from the dashboard to see results here.</p>
            </div>
        @endif

    </div>
</div>
@endsection
