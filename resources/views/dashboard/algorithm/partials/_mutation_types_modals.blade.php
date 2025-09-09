{{-- Add Mutation Method Modal --}}
@if (!$type)
<div class="modal fade" id="addmutationModal" tabindex="-1" aria-labelledby="addmutationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="addmutationModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>
                    Add New Mutation Method
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('algorithm-control.mutation-types.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="add_mutation_name" class="form-label fw-medium">
                                <i class="fas fa-tag text-muted me-1"></i>
                                Method Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="add_mutation_name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   placeholder="e.g., Bit Flip Mutation"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Enter a descriptive name for this mutation method</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="add_mutation_slug" class="form-label fw-medium">
                                <i class="fas fa-code text-muted me-1"></i>
                                Slug <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('slug') is-invalid @enderror"
                                   id="add_mutation_slug"
                                   name="slug"
                                   value="{{ old('slug') }}"
                                   placeholder="e.g., bit-flip, gaussian-mutation"
                                   required>
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Enter a unique identifier (lowercase, hyphen-separated)</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="add_mutation_description" class="form-label fw-medium">
                                <i class="fas fa-align-left text-muted me-1"></i>
                                Description
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="add_mutation_description"
                                      name="description"
                                      rows="3"
                                      placeholder="Describe how this mutation method works...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Optional description of the mutation technique</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       role="switch"
                                       id="add_mutation_is_active"
                                       name="is_active"
                                       value="1"
                                       checked>
                                <label class="form-check-label fw-medium" for="add_mutation_is_active">
                                    <i class="fas fa-toggle-on text-success me-1"></i>
                                    Active Method
                                </label>
                                <div class="form-text">Enable this method for use in genetic algorithms</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Method
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Edit Mutation Method Modal --}}
@if ($type)
<div class="modal fade" id="editmutationModal-{{ $type->mutation_id }}" tabindex="-1" aria-labelledby="editmutationModalLabel-{{ $type->mutation_id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title d-flex align-items-center" id="editmutationModalLabel-{{ $type->mutation_id }}">
                    <i class="fas fa-edit me-2"></i>
                    Edit Mutation Method
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('algorithm-control.mutation-types.update', $type->mutation_id) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <!-- Method Info Card -->
                    <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Editing: <strong>{{ $type->name }}</strong></small>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="edit_mutation_name_{{ $type->mutation_id }}" class="form-label fw-medium">
                                <i class="fas fa-tag text-muted me-1"></i>
                                Method Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name', 'update_'.$type->mutation_id) is-invalid @enderror"
                                   id="edit_mutation_name_{{ $type->mutation_id }}"
                                   name="name"
                                   value="{{ old('name', $type->name) }}"
                                   required>
                            @error('name', 'update_'.$type->mutation_id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="edit_mutation_slug_{{ $type->mutation_id }}" class="form-label fw-medium">
                                <i class="fas fa-code text-muted me-1"></i>
                                Slug <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('slug', 'update_'.$type->mutation_id) is-invalid @enderror"
                                   id="edit_mutation_slug_{{ $type->mutation_id }}"
                                   name="slug"
                                   value="{{ old('slug', $type->slug) }}"
                                   required>
                            @error('slug', 'update_'.$type->mutation_id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="edit_mutation_description_{{ $type->mutation_id }}" class="form-label fw-medium">
                                <i class="fas fa-align-left text-muted me-1"></i>
                                Description
                            </label>
                            <textarea class="form-control @error('description', 'update_'.$type->mutation_id) is-invalid @enderror"
                                      id="edit_mutation_description_{{ $type->mutation_id }}"
                                      name="description"
                                      rows="3">{{ old('description', $type->description) }}</textarea>
                            @error('description', 'update_'.$type->mutation_id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       role="switch"
                                       id="edit_mutation_is_active_{{ $type->mutation_id }}"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', $type->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="edit_mutation_is_active_{{ $type->mutation_id }}">
                                    <i class="fas fa-toggle-on text-success me-1"></i>
                                    Active Method
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-sync-alt me-1"></i>Update Method
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deletemutationModal-{{ $type->mutation_id }}" tabindex="-1" aria-labelledby="deletemutationModalLabel-{{ $type->mutation_id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="deletemutationModalLabel-{{ $type->mutation_id }}">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('algorithm-control.mutation-types.destroy', $type->mutation_id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-random text-danger" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>

                    <div class="alert alert-danger d-flex align-items-start" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Are you sure?</h6>
                            <p class="mb-0 small">You are about to delete this mutation method:</p>
                        </div>
                    </div>

                    <div class="card bg-light border-danger">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">{{ $type->name }}</h6>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <small class="text-muted">Slug: <code>{{ $type->slug }}</code></small>
                                        @if($type->is_active)
                                            <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary">Inactive</span>
                                        @endif
                                    </div>
                                    @if($type->description)
                                        <small class="text-muted">{{ Str::limit($type->description, 60) }}</small>
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
                                <small><strong>Warning:</strong> This action cannot be undone. Any genetic algorithms using this method may be affected.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Yes, Delete Method
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<style>
/* Custom styles for mutation methods page */
.form-check-input:checked {
    background-color: var(--success-color);
    border-color: var(--success-color);
}

/* Status badge improvements */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
}

/* Better spacing for form switches */
.form-check {
    padding-left: 2.5rem;
}

.form-check-input {
    width: 2rem;
    height: 1rem;
    margin-left: -2.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .card-title {
        font-size: 0.95rem;
    }

    .badge {
        font-size: 0.7rem;
    }
}

@media (max-width: 576px) {
    .modal-body {
        padding: 1rem !important;
    }

    .alert {
        padding: 0.75rem;
        font-size: 0.875rem;
    }
}

/* Enhanced empty state */
.empty-state i {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Better code styling */
code {
    font-size: 0.8rem;
    padding: 0.2rem 0.4rem;
    background: rgba(var(--primary-color), 0.1);
    border-radius: 0.25rem;
}

body.dark-mode code {
    background: rgba(255, 255, 255, 0.1);
}

/* Mutation-specific icon animations */
.fa-random {
    animation: mutationPulse 2s infinite ease-in-out;
}

@keyframes mutationPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Hover effects for mutation cards */
.card:hover .fa-random {
    color: var(--primary-color);
    transform: rotate(180deg);
    transition: all 0.3s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate slug from name
    const nameInputs = document.querySelectorAll('input[name="name"]');
    nameInputs.forEach(nameInput => {
        const slugInput = nameInput.closest('form').querySelector('input[name="slug"]');
        if (slugInput && !slugInput.value) {
            nameInput.addEventListener('input', function(e) {
                const slug = e.target.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim('-');
                slugInput.value = slug;
            });
        }
    });

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

    // Mutation-specific validation for common mutation types
    const mutationNameInputs = document.querySelectorAll('input[name="name"]');
    mutationNameInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const value = this.value.toLowerCase();
            if (value.includes('mutation') && !value.includes('method')) {
                // Suggest adding 'method' to the name
                this.setCustomValidity('Consider adding "Method" to the name for consistency');
            } else {
                this.setCustomValidity('');
            }
        });
    });

    // Enhanced slug validation for mutation methods
    const slugInputs = document.querySelectorAll('input[name="slug"]');
    slugInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const value = this.value;
            if (value && !value.match(/^[a-z0-9-]+$/)) {
                this.setCustomValidity('Slug should only contain lowercase letters, numbers, and hyphens');
            } else {
                this.setCustomValidity('');
            }
        });
    });

    console.log('✅ Mutation Methods page initialized with responsive design and validation');
});
</script>
