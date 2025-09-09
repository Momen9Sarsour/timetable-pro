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
                        Manage Users & Roles
                    </h4>
                    <p class="text-muted mb-0">Create and manage user accounts with role assignments</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-user-plus me-1"></i>
                        <span class="d-none d-sm-inline">Add New User</span>
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
                            Users List
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $users->total() }} Total</span>
                    </div>
                </div>

                <div class="card-body pt-3">
                    @if($users->count() > 0)
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 text-center" style="width: 50px;">#</th>
                                        <th class="border-0">Name</th>
                                        <th class="border-0">Email</th>
                                        <th class="border-0">Role</th>
                                        <th class="border-0 text-center">Email Verified</th>
                                        <th class="border-0">Joined At</th>
                                        <th class="border-0 text-center" style="width: 120px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $index => $user)
                                        @php
                                            $isPrimaryAdmin = $user->id === 1;
                                            $roleColor = match($user->role?->name) {
                                                'admin' => 'danger',
                                                'hod' => 'warning',
                                                'instructor' => 'info',
                                                'student' => 'success',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <tr class="border-bottom">
                                            <td class="text-center text-muted">
                                                <small>{{ $users->firstItem() + $index }}</small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-medium">{{ $user->name }}</div>
                                                        @if($isPrimaryAdmin)
                                                            <span class="badge bg-warning bg-opacity-10 text-warning" title="Primary Administrator">
                                                                <i class="fas fa-crown me-1"></i>Primary Admin
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $user->email }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $roleColor }} bg-opacity-10 text-{{ $roleColor }}">
                                                    {{ $user->role?->display_name ?? 'No Role' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @if($user->email_verified_at)
                                                    <span class="badge bg-success bg-opacity-10 text-success" title="{{ $user->email_verified_at->format('M d, Y H:i') }}">
                                                        <i class="fas fa-check-circle me-1"></i>Verified
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger bg-opacity-10 text-danger">
                                                        <i class="fas fa-times-circle me-1"></i>Unverified
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar-alt me-1"></i>
                                                    {{ $user->created_at->format('M d, Y') }}
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button class="btn btn-outline-primary btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editUserModal-{{ $user->id }}"
                                                            title="Edit User">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    @if(!$isPrimaryAdmin)
                                                        <button class="btn btn-outline-danger btn-sm"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteUserModal-{{ $user->id }}"
                                                                title="Delete User">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @else
                                                        <button class="btn btn-outline-secondary btn-sm"
                                                                disabled
                                                                title="Cannot delete primary admin">
                                                            <i class="fas fa-shield-alt"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="d-md-none">
                            @foreach ($users as $index => $user)
                                @php
                                    $isPrimaryAdmin = $user->id === 1;
                                    $roleColor = match($user->role?->name) {
                                        'admin' => 'danger',
                                        'hod' => 'warning',
                                        'instructor' => 'info',
                                        'student' => 'success',
                                        default => 'secondary'
                                    };
                                @endphp
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <div class="user-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="card-title mb-1">{{ $user->name }}</h6>
                                                        @if($isPrimaryAdmin)
                                                            <span class="badge bg-warning bg-opacity-10 text-warning">
                                                                <i class="fas fa-crown me-1"></i>Primary Admin
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="mb-2">
                                                    <small class="text-muted d-block">{{ $user->email }}</small>
                                                    <span class="badge bg-{{ $roleColor }} bg-opacity-10 text-{{ $roleColor }}">
                                                        {{ $user->role?->display_name ?? 'No Role' }}
                                                    </span>
                                                    @if($user->email_verified_at)
                                                        <span class="badge bg-success bg-opacity-10 text-success ms-1">
                                                            <i class="fas fa-check-circle me-1"></i>Verified
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger bg-opacity-10 text-danger ms-1">
                                                            <i class="fas fa-times-circle me-1"></i>Unverified
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editUserModal-{{ $user->id }}">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </button>
                                                    </li>
                                                    @if(!$isPrimaryAdmin)
                                                        <li>
                                                            <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal-{{ $user->id }}">
                                                                <i class="fas fa-trash me-2"></i>Delete
                                                            </button>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted small">
                                            <span>
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                {{ $user->created_at->format('M d, Y') }}
                                            </span>
                                            <span class="text-muted">#{{ $users->firstItem() + $index }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($users->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $users->links('pagination::bootstrap-5') }}
                            </div>
                        @endif

                    @else
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-users text-muted opacity-50" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 text-muted">No Users Found</h5>
                                <p class="text-muted mb-4">Start by adding users to manage access to your system.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    <i class="fas fa-user-plus me-2"></i>Add First User
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals for each user -->
    @foreach($users as $user)
        @include('dashboard.data-entry.partials._user_modals', ['user' => $user, 'roles' => $roles])
    @endforeach

    <!-- Include Add User Modal -->
    @include('dashboard.data-entry.partials._user_modals', ['user' => null, 'roles' => $roles])
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password confirmation validation
    document.querySelectorAll('form').forEach(form => {
        const passwordInput = form.querySelector('input[name="password"]');
        const confirmInput = form.querySelector('input[name="password_confirmation"]');

        if (passwordInput && confirmInput) {
            confirmInput.addEventListener('input', function() {
                if (this.value !== passwordInput.value) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });

            passwordInput.addEventListener('input', function() {
                if (confirmInput.value && confirmInput.value !== this.value) {
                    confirmInput.setCustomValidity('Passwords do not match');
                } else {
                    confirmInput.setCustomValidity('');
                }
            });
        }
    });

    // Email verification checkboxes logic
    document.querySelectorAll('form').forEach(form => {
        const verifyCheckbox = form.querySelector('input[name="verify_email"]');
        const unverifyCheckbox = form.querySelector('input[name="unverify_email"]');

        if (verifyCheckbox && unverifyCheckbox) {
            verifyCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    unverifyCheckbox.checked = false;
                }
            });

            unverifyCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    verifyCheckbox.checked = false;
                }
            });
        }
    });

    // Auto-generate email from name (optional enhancement)
    const nameInput = document.getElementById('add_user_name');
    const emailInput = document.getElementById('add_user_email');

    if (nameInput && emailInput) {
        nameInput.addEventListener('input', function() {
            if (!emailInput.value.trim()) {
                const name = this.value.toLowerCase().replace(/\s+/g, '.');
                if (name) {
                    emailInput.placeholder = `e.g., ${name}@example.com`;
                }
            }
        });
    }

    // Form validation
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
});
</script>
@endpush
