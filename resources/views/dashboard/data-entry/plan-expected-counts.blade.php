@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-users text-primary me-2"></i>
                        Expected Student Counts
                    </h4>
                    <p class="text-muted mb-0">Manage expected enrollment numbers by plan, level, and semester</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCountModal">
                        <i class="fas fa-plus me-1"></i>
                        <span class="d-none d-sm-inline">Add Expected Count</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    <!-- Main Content Card -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list text-muted me-2"></i>
                            Expected Counts List
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $expectedCounts->total() }} Total</span>
                    </div>
                </div>

                <div class="card-body pt-3">
                    @if($expectedCounts->count() > 0)
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 text-center" style="width: 50px;">#</th>
                                        <th class="border-0">Academic Year</th>
                                        <th class="border-0">Plan</th>
                                        <th class="border-0 text-center">Level</th>
                                        <th class="border-0 text-center">Semester</th>
                                        <th class="border-0">Branch</th>
                                        <th class="border-0 text-center">Male</th>
                                        <th class="border-0 text-center">Female</th>
                                        <th class="border-0 text-center">Total</th>
                                        <th class="border-0 text-center" style="width: 200px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($expectedCounts as $index => $count)
                                        <tr class="border-bottom">
                                            <td class="text-center text-muted">
                                                <small>{{ $expectedCounts->firstItem() + $index }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info bg-opacity-20 text-secondary">{{ $count->academic_year }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-light text-dark font-monospace small">{{ optional($count->plan)->plan_no ?? 'N/A' }}</span>
                                                    <span class="text-truncate" style="max-width: 150px;" title="{{ optional($count->plan)->plan_name }}">
                                                        {{ optional($count->plan)->plan_name ?? 'N/A' }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary bg-opacity-20">L{{ $count->plan_level }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success bg-opacity-20">S{{ $count->plan_semester }}</span>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $count->branch ?? '-' }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-blue bg-opacity-20">{{ $count->male_count }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-pink bg-opacity-20">{{ $count->female_count }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-bold text-dark">{{ $count->male_count + $count->female_count }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('data-entry.sections.manageContext', $count->id) }}"
                                                       class="btn btn-outline-info btn-sm" title="Manage Sections">
                                                        <i class="fas fa-users-cog"></i>
                                                    </a>
                                                    <button class="btn btn-outline-primary btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editCountModal-{{ $count->id }}"
                                                            title="Edit Count">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteCountModal-{{ $count->id }}"
                                                            title="Delete Count">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Tablet View (Medium screens) -->
                        <div class="d-none d-md-block d-lg-none">
                            @foreach ($expectedCounts as $index => $count)
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <div class="row align-items-center">
                                            <div class="col-8">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <span class="badge bg-info bg-opacity-20">{{ $count->academic_year }}</span>
                                                    <span class="badge bg-light text-dark font-monospace">{{ optional($count->plan)->plan_no ?? 'N/A' }}</span>
                                                </div>
                                                <h6 class="card-title mb-1">{{ optional($count->plan)->plan_name ?? 'N/A' }}</h6>
                                                <div class="row text-muted small">
                                                    <div class="col-6">
                                                        <i class="fas fa-layer-group me-1"></i>Level {{ $count->plan_level }}
                                                    </div>
                                                    <div class="col-6">
                                                        <i class="fas fa-calendar me-1"></i>Semester {{ $count->plan_semester }}
                                                    </div>
                                                </div>
                                                <div class="row text-muted small mt-1">
                                                    <div class="col-6">
                                                        <i class="fas fa-map-marker-alt me-1"></i>{{ $count->branch ?? 'Main' }}
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="badge bg-blue bg-opacity-20 me-1">{{ $count->male_count }}M</span>
                                                        <span class="badge bg-pink bg-opacity-20">{{ $count->female_count }}F</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4 text-end">
                                                <div class="text-center mb-2">
                                                    <div class="fw-bold fs-4 text-primary">{{ $count->male_count + $count->female_count }}</div>
                                                    <small class="text-muted">Total</small>
                                                </div>
                                                <div class="btn-group-vertical btn-group-sm" role="group">
                                                    <a href="{{ route('data-entry.sections.manageContext', $count->id) }}"
                                                       class="btn btn-outline-info btn-sm mb-1">
                                                        <i class="fas fa-users-cog me-1"></i>Sections
                                                    </a>
                                                    <button class="btn btn-outline-primary btn-sm mb-1"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editCountModal-{{ $count->id }}">
                                                        <i class="fas fa-edit me-1"></i>Edit
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteCountModal-{{ $count->id }}">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Mobile Cards -->
                        <div class="d-md-none">
                            @foreach ($expectedCounts as $index => $count)
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="badge bg-info bg-opacity-20 small">{{ $count->academic_year }}</span>
                                                    <span class="badge bg-light text-dark font-monospace small">{{ optional($count->plan)->plan_no ?? 'N/A' }}</span>
                                                </div>
                                                <h6 class="card-title mb-1">{{ Str::limit(optional($count->plan)->plan_name ?? 'N/A', 30) }}</h6>
                                                <p class="text-muted small mb-2">
                                                    Level {{ $count->plan_level }} • Semester {{ $count->plan_semester }}
                                                    @if($count->branch) • {{ $count->branch }} @endif
                                                </p>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('data-entry.sections.manageContext', $count->id) }}">
                                                            <i class="fas fa-users-cog me-2"></i>Manage Sections
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editCountModal-{{ $count->id }}">
                                                            <i class="fas fa-edit me-2"></i>Edit Count
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteCountModal-{{ $count->id }}">
                                                            <i class="fas fa-trash me-2"></i>Delete Count
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="badge bg-blue bg-opacity-20 w-100">{{ $count->male_count }} Male</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="badge bg-pink bg-opacity-20 w-100">{{ $count->female_count }} Female</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="badge bg-dark bg-opacity-20 w-100 fw-bold">{{ $count->male_count + $count->female_count }} Total</div>
                                            </div>
                                        </div>
                                        <div class="text-end mt-2">
                                            <small class="text-muted">#{{ $expectedCounts->firstItem() + $index }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($expectedCounts->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $expectedCounts->links('pagination::bootstrap-5') }}
                            </div>
                        @endif

                    @else
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-users text-muted opacity-50" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 text-muted">No Expected Counts Found</h5>
                                <p class="text-muted mb-4">Start by adding expected student enrollment numbers for your academic plans.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCountModal">
                                    <i class="fas fa-plus me-2"></i>Add First Expected Count
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals for each count -->
    @foreach($expectedCounts as $count)
        @include('dashboard.data-entry.partials._plan_expected_count_modals', ['count' => $count, 'plans' => $plans])
    @endforeach

    <!-- Include Add Count Modal -->
    @include('dashboard.data-entry.partials._plan_expected_count_modals', ['count' => null, 'plans' => $plans])
</div>

<style>
/* Custom badge colors for gender */
.bg-blue {
    background-color: #3b82f6 !important;
}

.bg-pink {
    background-color: #ec4899 !important;
}

/* Enhanced card hover effects */
.card:hover {
    transform: translateY(-2px);
    transition: all 0.15s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
}

/* Badge enhancements */
.badge {
    font-weight: 500;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .btn-group-vertical .btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }

    .card-title {
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .badge.w-100 {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
}
</style>
@endsection

@push('scripts')
    {{-- JS خاص بالصفحة إذا لزم الأمر (مثلاً للـ Filters) --}}
@endpush
