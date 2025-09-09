{{-- Add Role Modal --}}
@if (!$role)
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="addRoleModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>
                    Add New Role
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.roles.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="add_role_name" class="form-label fw-medium">
                                <i class="fas fa-code text-muted me-1"></i>
                                System Name (Code) <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name', 'store') is-invalid @enderror"
                                   id="add_role_name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   placeholder="e.g., supervisor, librarian"
                                   pattern="^[a-z0-9_-]+$"
                                   required>
                            @error('name', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Use lowercase letters, numbers, dashes, underscores only</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="add_role_display_name" class="form-label fw-medium">
                                <i class="fas fa-tag text-muted me-1"></i>
                                Display Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('display_name', 'store') is-invalid @enderror"
                                   id="add_role_display_name"
                                   name="display_name"
                                   value="{{ old('display_name') }}"
                                   placeholder="e.g., Supervisor, Librarian"
                                   required>
                            @error('display_name', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">This name will be displayed to users</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="add_role_description" class="form-label fw-medium">
                                <i class="fas fa-align-left text-muted me-1"></i>
                                Description <span class="text-muted">(Optional)</span>
                            </label>
                            <textarea class="form-control @error('description', 'store') is-invalid @enderror"
                                      id="add_role_description"
                                      name="description"
                                      rows="3"
                                      placeholder="Brief description of this role and its responsibilities">{{ old('description') }}</textarea>
                            @error('description', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Edit Role Modal --}}
@if ($role)
@php
    $isCoreRole = in_array($role->name, ['admin', 'hod', 'instructor', 'student']);
    $userCount = $role->users()->count();
@endphp
<div class="modal fade" id="editRoleModal-{{ $role->id }}" tabindex="-1" aria-labelledby="editRoleModalLabel-{{ $role->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title d-flex align-items-center" id="editRoleModalLabel-{{ $role->id }}">
                    <i class="fas fa-edit me-2"></i>
                    Edit Role
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.roles.update', $role->id) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <!-- Role Info Card -->
                    <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            <small>Editing: <strong>{{ $role->display_name }}</strong></small>
                            @if($isCoreRole)
                                <div class="badge bg-warning bg-opacity-10 text-warning mt-1">
                                    <i class="fas fa-shield-alt me-1"></i>Core System Role
                                </div>
                            @endif
                            @if($userCount > 0)
                                <div class="badge bg-info bg-opacity-10 text-info mt-1">
                                    <i class="fas fa-users me-1"></i>{{ $userCount }} assigned users
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="edit_role_name_{{ $role->id }}" class="form-label fw-medium">
                                <i class="fas fa-code text-muted me-1"></i>
                                System Name (Code) <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name', 'update_'.$role->id) is-invalid @enderror"
                                   id="edit_role_name_{{ $role->id }}"
                                   name="name"
                                   value="{{ old('name', $role->name) }}"
                                   pattern="^[a-z0-9_-]+$"
                                   @if($isCoreRole) readonly title="Core system role cannot be renamed" @endif
                                   required>
                            @error('name', 'update_'.$role->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @endif
                            @if($isCoreRole)
                                <div class="form-text text-warning">
                                    <i class="fas fa-lock me-1"></i>Core system role name cannot be changed
                                </div>
                            @else
                                <div class="form-text">Use lowercase letters, numbers, dashes, underscores only</div>
                            @endif
                        </div>

                        <div class="col-12">
                            <label for="edit_role_display_name_{{ $role->id }}" class="form-label fw-medium">
                                <i class="fas fa-tag text-muted me-1"></i>
                                Display Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('display_name', 'update_'.$role->id) is-invalid @enderror"
                                   id="edit_role_display_name_{{ $role->id }}"
                                   name="display_name"
                                   value="{{ old('display_name', $role->display_name) }}"
                                   required>
                            @error('display_name', 'update_'.$role->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="edit_role_description_{{ $role->id }}" class="form-label fw-medium">
                                <i class="fas fa-align-left text-muted me-1"></i>
                                Description <span class="text-muted">(Optional)</span>
                            </label>
                            <textarea class="form-control @error('description', 'update_'.$role->id) is-invalid @enderror"
                                      id="edit_role_description_{{ $role->id }}"
                                      name="description"
                                      rows="3"
                                      placeholder="Brief description of this role and its responsibilities">{{ old('description', $role->description) }}</textarea>
                            @error('description', 'update_'.$role->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-sync-alt me-1"></i>Update Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
@if (!$isCoreRole && $userCount == 0)
<div class="modal fade" id="deleteRoleModal-{{ $role->id }}" tabindex="-1" aria-labelledby="deleteRoleModalLabel-{{ $role->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="deleteRoleModalLabel-{{ $role->id }}">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.roles.destroy', $role->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-user-shield text-danger" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>

                    <div class="alert alert-danger d-flex align-items-start" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Are you sure?</h6>
                            <p class="mb-0 small">You are about to delete:</p>
                        </div>
                    </div>

                    <div class="card bg-light border-danger">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">{{ $role->display_name }}</h6>
                                    <small class="text-muted">Code: <code>{{ $role->name }}</code></small>
                                    @if($role->description)
                                        <p class="text-muted small mb-0 mt-1">{{ $role->description }}</p>
                                    @endif
                                </div>
                                <span class="badge bg-danger">Will be deleted</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <small><strong>Warning:</strong> This action cannot be undone. Users previously assigned to this role will lose these permissions.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Yes, Delete Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endif

<style>
/* Role-specific styling */
.role-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.core-role-indicator {
    position: relative;
    top: -1px;
}

/* Form enhancements */
.form-control[readonly] {
    background-color: #f8f9fa;
    opacity: 0.8;
}

body.dark-mode .form-control[readonly] {
    background-color: rgba(255, 255, 255, 0.05);
}

/* Mobile responsiveness */
@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
    }

    .modal-body {
        padding: 1rem !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate display name from system name in add modal
    const systemNameInput = document.getElementById('add_role_name');
    const displayNameInput = document.getElementById('add_role_display_name');

    if (systemNameInput && displayNameInput) {
        systemNameInput.addEventListener('input', function() {
            // Only auto-generate if display name is empty
            if (!displayNameInput.value.trim()) {
                const systemName = this.value;
                // Convert snake_case or kebab-case to Title Case
                const displayName = systemName
                    .split(/[-_]/)
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                    .join(' ');
                displayNameInput.value = displayName;
            }
        });
    }

    // Prevent form submission if validation fails
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Real-time validation for system name pattern
    document.querySelectorAll('input[name="name"]').forEach(input => {
        input.addEventListener('input', function() {
            const pattern = /^[a-z0-9_-]+$/;
            if (this.value && !pattern.test(this.value)) {
                this.setCustomValidity('Only lowercase letters, numbers, dashes, and underscores are allowed');
            } else {
                this.setCustomValidity('');
            }
        });
    });
});
</script>
