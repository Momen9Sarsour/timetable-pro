@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-check-double text-primary me-2"></i>
                        Manage Selection Methods
                    </h4>
                    <p class="text-muted mb-0">Configure genetic algorithm selection techniques</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSelectionModal">
                        <i class="fas fa-plus me-1"></i>
                        <span class="d-none d-sm-inline">Add New Method</span>
                        <span class="d-sm-none">Add</span>
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
                            Selection Methods List
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $selectionTypes->total() }} Total</span>
                    </div>
                </div>

                <div class="card-body pt-3">
                    @if($selectionTypes->count() > 0)
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 text-center" style="width: 50px;">#</th>
                                        <th class="border-0">Method Name</th>
                                        <th class="border-0">Slug</th>
                                        <th class="border-0" style="max-width: 200px;">Description</th>
                                        <th class="border-0 text-center" style="width: 100px;">Status</th>
                                        <th class="border-0 text-center" style="width: 140px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($selectionTypes as $index => $type)
                                        <tr class="border-bottom">
                                            <td class="text-center text-muted">
                                                <small>{{ $selectionTypes->firstItem() + $index }}</small>
                                            </td>
                                            <td class="fw-medium">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-check-double text-primary me-2"></i>
                                                    {{ $type->name }}
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark font-monospace">{{ $type->slug }}</span>
                                            </td>
                                            <td>
                                                <span class="text-muted small" title="{{ $type->description }}">
                                                    {{ Str::limit($type->description, 50) }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @if($type->is_active)
                                                    <span class="badge bg-success bg-opacity-10 text-success">
                                                        <i class="fas fa-check-circle me-1"></i>Active
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                        <i class="fas fa-pause-circle me-1"></i>Inactive
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button class="btn btn-outline-primary btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editSelectionModal-{{ $type->selection_type_id }}"
                                                            title="Edit Method">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteSelectionModal-{{ $type->selection_type_id }}"
                                                            title="Delete Method">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Tablet View -->
                        <div class="d-none d-md-block d-lg-none">
                            @foreach ($selectionTypes as $index => $type)
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <h6 class="card-title mb-1 d-flex align-items-center">
                                                    <i class="fas fa-check-double text-primary me-2"></i>
                                                    {{ $type->name }}
                                                </h6>
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <span class="badge bg-light text-dark font-monospace">{{ $type->slug }}</span>
                                                    @if($type->is_active)
                                                        <span class="badge bg-success bg-opacity-10 text-success">
                                                            <i class="fas fa-check-circle me-1"></i>Active
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                            <i class="fas fa-pause-circle me-1"></i>Inactive
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($type->description)
                                                    <p class="text-muted small mb-0">{{ Str::limit($type->description, 80) }}</p>
                                                @endif
                                            </div>
                                            <div class="btn-group btn-group-sm ms-2" role="group">
                                                <button class="btn btn-outline-primary btn-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editSelectionModal-{{ $type->selection_type_id }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteSelectionModal-{{ $type->selection_type_id }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted small">
                                            <span>Method #{{ $selectionTypes->firstItem() + $index }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Mobile Cards -->
                        <div class="d-md-none">
                            @foreach ($selectionTypes as $index => $type)
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <h6 class="card-title mb-1">
                                                    <i class="fas fa-check-double text-primary me-1"></i>
                                                    {{ $type->name }}
                                                </h6>
                                                <span class="badge bg-light text-dark font-monospace mb-1">{{ $type->slug }}</span>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editSelectionModal-{{ $type->selection_type_id }}">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteSelectionModal-{{ $type->selection_type_id }}">
                                                            <i class="fas fa-trash me-2"></i>Delete
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="mb-2">
                                            @if($type->is_active)
                                                <span class="badge bg-success bg-opacity-10 text-success">
                                                    <i class="fas fa-check-circle me-1"></i>Active
                                                </span>
                                            @else
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                    <i class="fas fa-pause-circle me-1"></i>Inactive
                                                </span>
                                            @endif
                                        </div>

                                        @if($type->description)
                                            <p class="text-muted small mb-2">{{ Str::limit($type->description, 60) }}</p>
                                        @endif

                                        <div class="d-flex justify-content-between text-muted small">
                                            <span>Method #{{ $selectionTypes->firstItem() + $index }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($selectionTypes->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $selectionTypes->links('pagination::bootstrap-5') }}
                            </div>
                        @endif

                    @else
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-check-double text-muted opacity-50" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 text-muted">No Selection Methods Found</h5>
                                <p class="text-muted mb-4">Start by adding selection methods to configure your genetic algorithm.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSelectionModal">
                                    <i class="fas fa-plus me-2"></i>Add First Method
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals for each method -->
    @foreach($selectionTypes as $type)
        @include('dashboard.algorithm.partials._selection_types_modals', ['type' => $type])
    @endforeach

    <!-- Include Add Method Modal -->
    @include('dashboard.algorithm.partials._selection_types_modals', ['type' => null])
</div>
@endsection
