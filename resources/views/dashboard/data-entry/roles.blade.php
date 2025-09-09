@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-user-shield text-primary me-2"></i>
                        Manage Roles
                    </h4>
                    <p class="text-muted mb-0">Create and manage user roles and permissions</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                        <i class="fas fa-plus me-1"></i>
                        <span class="d-none d-sm-inline">Add New Role</span>
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
                            Roles List
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $roles->total() }} Total</span>
                    </div>
                </div>

                <div class="card-body pt-3">
                    @if($roles->count() > 0)
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 text-center" style="width: 50px;">#</th>
                                        <th class="border-0">System Name (Code)</th>
                                        <th class="border-0">Display Name</th>
                                        <th class="border-0">Description</th>
                                        <th class="border-0 text-center">User Count</th>
                                        <th class="border-0 text-center" style="width: 120px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($roles as $index => $role)
                                        @php
                                            $isCoreRole = in_array($role->name, ['admin', 'hod', 'instructor', 'student']);
                                            $userCount = $role->users()->count();
                                        @endphp
                                        <tr class="border-bottom">
                                            <td class="text-center text-muted">
                                                <small>{{ $roles->firstItem() + $index }}</small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-light text-dark font-monospace me-2">{{ $role->name }}</span>
                                                    @if($isCoreRole)
                                                        <span class="badge bg-warning bg-opacity-10 text-warning" title="Core System Role">
                                                            <i class="fas fa-shield-alt"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="fw-medium">{{ $role->display_name }}</td>
                                            <td>
                                                <span class="text-muted">
                                                    {{ $role->description ?: 'No description provided' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @if($userCount > 0)
                                                    <span class="badge bg-info bg-opacity-10 text-info">
                                                        <i class="fas fa-users me-1"></i>{{ $userCount }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button class="btn btn-outline-primary btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editRoleModal-{{ $role->id }}"
                                                            title="Edit Role">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteRoleModal-{{ $role->id }}"
                                                            title="Delete Role"
                                                            @if($isCoreRole || $userCount > 0) disabled @endif>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="d-md-none">
                            @foreach ($roles as $index => $role)
                                @php
                                    $isCoreRole = in_array($role->name, ['admin', 'hod', 'instructor', 'student']);
                                    $userCount = $role->users()->count();
                                @endphp
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <h6 class="card-title mb-0">{{ $role->display_name }}</h6>
                                                    @if($isCoreRole)
                                                        <span class="badge bg-warning bg-opacity-10 text-warning" title="Core System Role">
                                                            <i class="fas fa-shield-alt"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                                <span class="badge bg-light text-dark font-monospace mb-1">{{ $role->name }}</span>
                                                @if($role->description)
                                                    <p class="text-muted small mb-1">{{ $role->description }}</p>
                                                @endif
                                                @if($userCount > 0)
                                                    <span class="badge bg-info bg-opacity-10 text-info">
                                                        <i class="fas fa-users me-1"></i>{{ $userCount }} users
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editRoleModal-{{ $role->id }}">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </button>
                                                    </li>
                                                    @if(!$isCoreRole && $userCount == 0)
                                                        <li>
                                                            <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteRoleModal-{{ $role->id }}">
                                                                <i class="fas fa-trash me-2"></i>Delete
                                                            </button>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted small">
                                            <span>Role ID: {{ $role->id }}</span>
                                            <span class="text-muted">#{{ $roles->firstItem() + $index }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($roles->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $roles->links('pagination::bootstrap-5') }}
                            </div>
                        @endif

                    @else
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-user-shield text-muted opacity-50" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 text-muted">No Roles Found</h5>
                                <p class="text-muted mb-4">Start by adding roles to manage user permissions and access levels.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                                    <i class="fas fa-plus me-2"></i>Add First Role
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals for each role -->
    @foreach($roles as $role)
        @include('dashboard.data-entry.partials._role_modals', ['role' => $role])
    @endforeach

    <!-- Include Add Role Modal -->
    @include('dashboard.data-entry.partials._role_modals', ['role' => null])
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation enhancement
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Auto-generate display name from system name
    const systemNameInput = document.getElementById('add_role_name');
    const displayNameInput = document.getElementById('add_role_display_name');

    if (systemNameInput && displayNameInput) {
        systemNameInput.addEventListener('input', function() {
            if (!displayNameInput.value) {
                const systemName = this.value;
                const displayName = systemName.charAt(0).toUpperCase() + systemName.slice(1).replace(/[-_]/g, ' ');
                displayNameInput.value = displayName;
            }
        });
    }
});
</script>
@endpush
