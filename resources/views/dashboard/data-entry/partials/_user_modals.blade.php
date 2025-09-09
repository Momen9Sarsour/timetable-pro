{{-- Add User Modal --}}
@if (!$user)
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="addUserModalLabel">
                    <i class="fas fa-user-plus me-2"></i>
                    Add New User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.users.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-3">
                        <!-- Personal Information Section -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-user me-1"></i>Personal Information
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="add_user_name" class="form-label fw-medium">
                                <i class="fas fa-user text-muted me-1"></i>
                                Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name', 'store') is-invalid @enderror"
                                   id="add_user_name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   placeholder="Enter full name"
                                   required>
                            @error('name', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="add_user_email" class="form-label fw-medium">
                                <i class="fas fa-envelope text-muted me-1"></i>
                                Email Address <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   class="form-control @error('email', 'store') is-invalid @enderror"
                                   id="add_user_email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   placeholder="user@example.com"
                                   required>
                            @error('email', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Security Section -->
                        <div class="col-12 mt-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-lock me-1"></i>Security & Access
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="add_user_password" class="form-label fw-medium">
                                <i class="fas fa-key text-muted me-1"></i>
                                Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password"
                                       class="form-control @error('password', 'store') is-invalid @enderror"
                                       id="add_user_password"
                                       name="password"
                                       minlength="8"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('add_user_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('password', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Minimum 8 characters required</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="add_user_password_confirmation" class="form-label fw-medium">
                                <i class="fas fa-key text-muted me-1"></i>
                                Confirm Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password"
                                       class="form-control"
                                       id="add_user_password_confirmation"
                                       name="password_confirmation"
                                       minlength="8"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('add_user_password_confirmation')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="add_user_role_id" class="form-label fw-medium">
                                <i class="fas fa-user-tag text-muted me-1"></i>
                                Assign Role <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('role_id', 'store') is-invalid @enderror"
                                    id="add_user_role_id"
                                    name="role_id"
                                    required>
                                <option value="" selected disabled>Choose a role...</option>
                                @if(isset($roles))
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}"
                                                {{ old('role_id') == $role->id ? 'selected' : '' }}
                                                data-description="{{ $role->description }}">
                                            {{ $role->display_name }}
                                            @if(in_array($role->name, ['admin', 'hod', 'instructor', 'student']))
                                                (Core Role)
                                            @endif
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('role_id', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Select the appropriate role for this user</div>
                            @enderror
                        </div>

                        <!-- Account Settings -->
                        <div class="col-12 mt-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-cog me-1"></i>Account Settings
                            </h6>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="add_user_verify_email"
                                       name="verify_email"
                                       value="1"
                                       {{ old('verify_email') ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="add_user_verify_email">
                                    <i class="fas fa-check-circle text-success me-1"></i>
                                    Mark email as verified immediately
                                </label>
                                <div class="form-text">User won't need to verify their email address</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Edit User Modal --}}
@if ($user)
@php
    $isPrimaryAdmin = $user->id === 1;
@endphp
<div class="modal fade" id="editUserModal-{{ $user->id }}" tabindex="-1" aria-labelledby="editUserModalLabel-{{ $user->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title d-flex align-items-center" id="editUserModalLabel-{{ $user->id }}">
                    <i class="fas fa-user-edit me-2"></i>
                    Edit User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.users.update', $user->id) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <!-- User Info Alert -->
                    <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            <small>Editing: <strong>{{ $user->name }}</strong></small>
                            @if($isPrimaryAdmin)
                                <div class="badge bg-warning bg-opacity-10 text-warning mt-1">
                                    <i class="fas fa-crown me-1"></i>Primary Administrator
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Personal Information Section -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-user me-1"></i>Personal Information
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_user_name_{{ $user->id }}" class="form-label fw-medium">
                                <i class="fas fa-user text-muted me-1"></i>
                                Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name', 'update_'.$user->id) is-invalid @enderror"
                                   id="edit_user_name_{{ $user->id }}"
                                   name="name"
                                   value="{{ old('name', $user->name) }}"
                                   required>
                            @error('name', 'update_'.$user->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="edit_user_email_{{ $user->id }}" class="form-label fw-medium">
                                <i class="fas fa-envelope text-muted me-1"></i>
                                Email Address <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   class="form-control @error('email', 'update_'.$user->id) is-invalid @enderror"
                                   id="edit_user_email_{{ $user->id }}"
                                   name="email"
                                   value="{{ old('email', $user->email) }}"
                                   required>
                            @error('email', 'update_'.$user->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Role Assignment -->
                        <div class="col-12 mt-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-user-tag me-1"></i>Role Assignment
                            </h6>
                        </div>

                        <div class="col-12">
                            <label for="edit_user_role_id_{{ $user->id }}" class="form-label fw-medium">
                                <i class="fas fa-user-tag text-muted me-1"></i>
                                Assign Role <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('role_id', 'update_'.$user->id) is-invalid @enderror"
                                    id="edit_user_role_id_{{ $user->id }}"
                                    name="role_id"
                                    @if($isPrimaryAdmin && optional($user->role)->name === 'admin') disabled @endif
                                    required>
                                <option value="" disabled>Select role...</option>
                                @if(isset($roles))
                                    @foreach ($roles as $role)
                                        @if($isPrimaryAdmin && optional($user->role)->name === 'admin' && $role->name !== 'admin')
                                            @continue
                                        @endif
                                        <option value="{{ $role->id }}"
                                                {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                            {{ $role->display_name }}
                                            @if(in_array($role->name, ['admin', 'hod', 'instructor', 'student']))
                                                (Core Role)
                                            @endif
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @if($isPrimaryAdmin && optional($user->role)->name === 'admin')
                                <input type="hidden" name="role_id" value="{{ $user->role_id }}">
                                <div class="form-text text-warning">
                                    <i class="fas fa-lock me-1"></i>Primary admin role cannot be changed
                                </div>
                            @endif
                            @error('role_id', 'update_'.$user->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password Update -->
                        <div class="col-12 mt-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-lock me-1"></i>Password Update
                            </h6>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <small>Leave password fields blank if you don't want to change the password</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_user_password_{{ $user->id }}" class="form-label fw-medium">
                                <i class="fas fa-key text-muted me-1"></i>
                                New Password
                            </label>
                            <div class="input-group">
                                <input type="password"
                                       class="form-control @error('password', 'update_'.$user->id) is-invalid @enderror"
                                       id="edit_user_password_{{ $user->id }}"
                                       name="password"
                                       minlength="8">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('edit_user_password_{{ $user->id }}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('password', 'update_'.$user->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="edit_user_password_confirmation_{{ $user->id }}" class="form-label fw-medium">
                                <i class="fas fa-key text-muted me-1"></i>
                                Confirm New Password
                            </label>
                            <div class="input-group">
                                <input type="password"
                                       class="form-control"
                                       id="edit_user_password_confirmation_{{ $user->id }}"
                                       name="password_confirmation"
                                       minlength="8">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('edit_user_password_confirmation_{{ $user->id }}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Account Settings -->
                        <div class="col-12 mt-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-cog me-1"></i>Account Settings
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="edit_user_verify_email_{{ $user->id }}"
                                       name="verify_email"
                                       value="1"
                                       {{ old('verify_email', $user->email_verified_at ? true : false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="edit_user_verify_email_{{ $user->id }}">
                                    <i class="fas fa-check-circle text-success me-1"></i>
                                    Mark email as verified
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="edit_user_unverify_email_{{ $user->id }}"
                                       name="unverify_email"
                                       value="1"
                                       {{ old('unverify_email') ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="edit_user_unverify_email_{{ $user->id }}">
                                    <i class="fas fa-times-circle text-danger me-1"></i>
                                    Mark email as unverified
                                </label>
                                <div class="form-text">Overrides verification if both are selected</div>
                            </div>
                        </div>

                        @if($user->instructor)
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <i class="fas fa-link me-2"></i>
                                    <small><strong>Note:</strong> This user is linked to instructor record: {{ optional($user->instructor)->instructor_no ?? 'N/A' }}</small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-sync-alt me-1"></i>Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
@if ($user->id !== 1)
<div class="modal fade" id="deleteUserModal-{{ $user->id }}" tabindex="-1" aria-labelledby="deleteUserModalLabel-{{ $user->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="deleteUserModalLabel-{{ $user->id }}">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm User Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.users.destroy', $user->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-user-times text-danger" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>

                    <div class="alert alert-danger d-flex align-items-start" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Are you sure?</h6>
                            <p class="mb-0 small">You are about to permanently delete:</p>
                        </div>
                    </div>

                    <div class="card bg-light border-danger">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="user-avatar bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">{{ $user->name }}</h6>
                                    <small class="text-muted d-block">{{ $user->email }}</small>
                                    <span class="badge bg-{{ match($user->role?->name) { 'admin' => 'danger', 'hod' => 'warning', 'instructor' => 'info', 'student' => 'success', default => 'secondary' } }} bg-opacity-10 text-{{ match($user->role?->name) { 'admin' => 'danger', 'hod' => 'warning', 'instructor' => 'info', 'student' => 'success', default => 'secondary' } }}">
                                        {{ $user->role?->display_name ?? 'No Role' }}
                                    </span>
                                </div>
                                <span class="badge bg-danger">Will be deleted</span>
                            </div>
                        </div>
                    </div>

                    @if($user->instructor)
                        <div class="alert alert-warning mt-3" role="alert">
                            <div class="d-flex">
                                <i class="fas fa-link me-2 mt-1 flex-shrink-0"></i>
                                <div>
                                    <small><strong>Warning:</strong> This user is linked to instructor record ({{ optional($user->instructor)->instructor_no ?? 'N/A' }}). Deleting the user will set the instructor's user_id to NULL.</small>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <small><strong>This action cannot be undone.</strong> All user data and associated records will be permanently removed.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Yes, Delete User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endif

<style>
/* User-specific styling */
.user-avatar {
    transition: all 0.15s ease;
}

.user-avatar:hover {
    transform: scale(1.05);
}

/* Password toggle button */
.input-group .btn-outline-secondary {
    border-color: #ced4da;
}

.input-group .btn-outline-secondary:hover {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

/* Form switches */
.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

/* Role badges in modals */
.modal .badge {
    font-size: 0.75rem;
}

/* Modal sections */
.modal-body h6.border-bottom {
    margin-bottom: 1rem !important;
    padding-bottom: 0.5rem !important;
}

/* Responsive modal */
@media (max-width: 768px) {
    .modal-lg {
        max-width: 95%;
    }

    .modal-body {
        max-height: 60vh !important;
        padding: 1rem !important;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
    }
}
</style>

<script>
// Password visibility toggle function
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Enhanced password confirmation validation
    document.querySelectorAll('form').forEach(form => {
        const passwordInput = form.querySelector('input[name="password"]');
        const confirmInput = form.querySelector('input[name="password_confirmation"]');

        if (passwordInput && confirmInput) {
            function validatePasswords() {
                if (passwordInput.value && confirmInput.value) {
                    if (passwordInput.value !== confirmInput.value) {
                        confirmInput.setCustomValidity('Passwords do not match');
                        confirmInput.classList.add('is-invalid');
                    } else {
                        confirmInput.setCustomValidity('');
                        confirmInput.classList.remove('is-invalid');
                    }
                } else {
                    confirmInput.setCustomValidity('');
                    confirmInput.classList.remove('is-invalid');
                }
            }

            passwordInput.addEventListener('input', validatePasswords);
            confirmInput.addEventListener('input', validatePasswords);
        }
    });

    // Enhanced email verification checkboxes logic
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

    // Role description tooltip (optional enhancement)
    const roleSelects = document.querySelectorAll('select[name="role_id"]');
    roleSelects.forEach(select => {
        select.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const description = selectedOption.getAttribute('data-description');

            // Remove any existing description
            const existingDescription = this.parentNode.querySelector('.role-description');
            if (existingDescription) {
                existingDescription.remove();
            }

            // Add new description if available
            if (description && description.trim()) {
                const descriptionElement = document.createElement('div');
                descriptionElement.className = 'form-text role-description mt-1';
                descriptionElement.innerHTML = `<i class="fas fa-info-circle me-1"></i>${description}`;
                this.parentNode.appendChild(descriptionElement);
            }
        });
    });

    // Form validation enhancement
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();

                // Focus on first invalid field
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
            form.classList.add('was-validated');
        });
    });
});
</script>
