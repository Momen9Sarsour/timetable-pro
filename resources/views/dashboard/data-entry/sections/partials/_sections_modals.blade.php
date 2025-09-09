{{-- Add Section Modal --}}
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="addSectionModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>
                    <span class="d-none d-sm-inline">Add New Section</span>
                    <span class="d-sm-none">Add Section</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.sections.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <!-- Hidden context fields -->
                <input type="hidden" name="plan_subject_id" value="{{ old('plan_subject_id') }}">
                <input type="hidden" name="activity_type" value="{{ old('activity_type') }}">
                <input type="hidden" name="academic_year" value="{{ old('academic_year') }}">
                <input type="hidden" name="semester" value="{{ old('semester') }}">
                <input type="hidden" name="branch" value="{{ old('branch') }}">

                <div class="modal-body p-3 p-md-4">
                    <!-- Validation Errors -->
                    @if ($errors->hasBag('addSectionModal'))
                        <div class="alert alert-danger d-flex align-items-start validation-errors" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <strong class="d-block mb-1">Please correct the errors:</strong>
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->getBag('addSectionModal')->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label for="add_section_number" class="form-label fw-medium">
                                <i class="fas fa-hashtag text-muted me-1"></i>
                                Section Number <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('section_number', 'addSectionModal') is-invalid @enderror"
                                   id="add_section_number"
                                   name="section_number"
                                   value="{{ old('section_number', 1) }}"
                                   required
                                   min="1"
                                   placeholder="e.g., 1, 2, 3">
                            @error('section_number', 'addSectionModal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('section_unique', 'addSectionModal')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="add_student_count" class="form-label fw-medium">
                                <i class="fas fa-users text-muted me-1"></i>
                                Student Count <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('student_count', 'addSectionModal') is-invalid @enderror"
                                   id="add_student_count"
                                   name="student_count"
                                   value="{{ old('student_count', 0) }}"
                                   required
                                   min="0"
                                   placeholder="e.g., 25, 30">
                            @error('student_count', 'addSectionModal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('student_count_total', 'addSectionModal')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="add_section_gender" class="form-label fw-medium">
                                <i class="fas fa-venus-mars text-muted me-1"></i>
                                Section Gender <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('section_gender', 'addSectionModal') is-invalid @enderror"
                                    id="add_section_gender"
                                    name="section_gender"
                                    required>
                                <option value="Mixed" {{ old('section_gender', 'Mixed') == 'Mixed' ? 'selected' : '' }}>Mixed</option>
                                <option value="Male" {{ old('section_gender') == 'Male' ? 'selected' : '' }}>Male Only</option>
                                <option value="Female" {{ old('section_gender') == 'Female' ? 'selected' : '' }}>Female Only</option>
                            </select>
                            @error('section_gender', 'addSectionModal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="add_branch_display" class="form-label fw-medium">
                                <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                Branch
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="add_branch_display"
                                   readonly
                                   placeholder="From context">
                            <div class="form-text">Branch is inherited from the context</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <div class="d-flex flex-column flex-sm-row gap-2 w-100">
                        <button type="button" class="btn btn-outline-secondary flex-fill order-2 order-sm-1" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary flex-fill order-1 order-sm-2">
                            <i class="fas fa-save me-1"></i>Save Section
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Section Modal --}}
<div class="modal fade" id="editSectionModal" tabindex="-1" aria-labelledby="editSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title d-flex align-items-center" id="editSectionModalLabel">
                    <i class="fas fa-edit me-2"></i>
                    <span class="d-none d-sm-inline">Edit Section</span>
                    <span class="d-sm-none">Edit</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="#" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body p-3 p-md-4">
                    <!-- Context Info -->
                    <div class="alert alert-info d-flex align-items-center mb-3" role="alert" id="edit_context_info_text">
                        <i class="fas fa-info-circle me-2"></i>
                        <small><strong>Context:</strong> <span>Loading...</span></small>
                    </div>

                    <!-- Validation Errors -->
                    @php
                        $editErrorBagName = null;
                        $sectionIdForModal = session('editSectionId') ?? null;
                        if ($sectionIdForModal && $errors->hasBag('editSectionModal_'.$sectionIdForModal)) {
                            $editErrorBagName = 'editSectionModal_'.$sectionIdForModal;
                        }
                    @endphp

                    @if($editErrorBagName && $errors->$editErrorBagName->any())
                        <div class="alert alert-danger d-flex align-items-start validation-errors" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <strong class="d-block mb-1">Please correct the errors:</strong>
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->$editErrorBagName->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label for="edit_section_number" class="form-label fw-medium">
                                <i class="fas fa-hashtag text-muted me-1"></i>
                                Section Number <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @if($editErrorBagName) @error('section_number', $editErrorBagName) is-invalid @enderror @endif"
                                   id="edit_section_number"
                                   name="section_number"
                                   value="{{ old('section_number', $sectionForModal->section_number ?? '') }}"
                                   required
                                   min="1">
                            @if($editErrorBagName)
                                @error('section_number', $editErrorBagName)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @error('section_unique', $editErrorBagName)
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="edit_student_count" class="form-label fw-medium">
                                <i class="fas fa-users text-muted me-1"></i>
                                Student Count <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @if($editErrorBagName) @error('student_count', $editErrorBagName) is-invalid @enderror @endif"
                                   id="edit_student_count"
                                   name="student_count"
                                   value="{{ old('student_count', $sectionForModal->student_count ?? '') }}"
                                   required
                                   min="0">
                            @if($editErrorBagName)
                                @error('student_count', $editErrorBagName)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @error('student_count_total', $editErrorBagName)
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="edit_section_gender" class="form-label fw-medium">
                                <i class="fas fa-venus-mars text-muted me-1"></i>
                                Section Gender <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @if($editErrorBagName) @error('section_gender', $editErrorBagName) is-invalid @enderror @endif"
                                    id="edit_section_gender"
                                    name="section_gender"
                                    required>
                                <option value="Mixed" @if(old('section_gender', $sectionForModal->section_gender ?? '') == 'Mixed') selected @endif>Mixed</option>
                                <option value="Male" @if(old('section_gender', $sectionForModal->section_gender ?? '') == 'Male') selected @endif>Male Only</option>
                                <option value="Female" @if(old('section_gender', $sectionForModal->section_gender ?? '') == 'Female') selected @endif>Female Only</option>
                            </select>
                            @if($editErrorBagName)
                                @error('section_gender', $editErrorBagName)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="edit_branch_display" class="form-label fw-medium">
                                <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                Branch
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="edit_branch_display"
                                   readonly
                                   placeholder="From context">
                            <div class="form-text">Branch is inherited from the context</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <div class="d-flex flex-column flex-sm-row gap-2 w-100">
                        <button type="button" class="btn btn-outline-secondary flex-fill order-2 order-sm-1" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-warning flex-fill order-1 order-sm-2">
                            <i class="fas fa-sync-alt me-1"></i>Update Section
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Section Modal --}}
<div class="modal fade" id="deleteSectionModal" tabindex="-1" aria-labelledby="deleteSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="deleteSectionModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span class="d-none d-sm-inline">Confirm Deletion</span>
                    <span class="d-sm-none">Delete?</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="#" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-3 p-md-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-users-slash text-danger" style="font-size: 3rem; opacity: 0.3;"></i>
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
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1" id="delete_section_info_text">Loading section info...</h6>
                                </div>
                                <span class="badge bg-danger">Will be deleted</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <small><strong>Warning:</strong> This action cannot be undone. Any schedules or assignments for this section will be affected.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <div class="d-flex flex-column flex-sm-row gap-2 w-100">
                        <button type="button" class="btn btn-outline-secondary flex-fill order-2 order-sm-1" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-danger flex-fill order-1 order-sm-2">
                            <i class="fas fa-trash me-1"></i>Yes, Delete
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Enhanced form styling */
.modal-body .form-control:focus,
.modal-body .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Context info styling */
#edit_context_info_text {
    background-color: rgba(13, 202, 240, 0.1);
    border-color: rgba(13, 202, 240, 0.2);
    color: #0c63e4;
}

/* Validation error styling */
.validation-errors {
    border-left: 4px solid #dc3545;
}

/* Modal responsive enhancements */
.modal-dialog {
    max-width: 500px;
}

@media (max-width: 768px) {
    .modal-dialog {
        max-width: 95%;
        margin: 1rem 0.5rem;
    }

    .modal-body {
        padding: 1rem !important;
    }

    .modal-header {
        padding: 0.75rem 1rem;
    }

    .modal-footer {
        padding: 0.75rem 1rem !important;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
    }

    .modal-title {
        font-size: 1rem;
    }

    .form-label {
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }

    .btn {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
    }

    /* Stack buttons vertically on mobile with proper order */
    .modal-footer .d-flex {
        flex-direction: column;
    }

    .modal-footer .order-1 {
        order: 1;
    }

    .modal-footer .order-2 {
        order: 2;
    }
}

/* Button enhancements */
.btn {
    transition: all 0.15s ease;
}

.btn:hover:not([disabled]) {
    transform: translateY(-1px);
}

/* Alert enhancements */
.alert {
    border-radius: 0.5rem;
}

.alert-info {
    border-left: 4px solid #0dcaf0;
}

.alert-danger {
    border-left: 4px solid #dc3545;
}

.alert-warning {
    border-left: 4px solid #ffc107;
}

/* Enhanced input styling */
.form-control,
.form-select {
    transition: all 0.15s ease;
}

.form-control:hover,
.form-select:hover {
    border-color: #ced4da;
}

/* Loading state styling */
.btn[disabled] {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Card responsive improvements */
@media (max-width: 575px) {
    .card-body {
        padding: 1rem !important;
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}

/* Form group spacing */
.row.g-3 > * {
    margin-bottom: 0.5rem;
}

@media (min-width: 576px) {
    .row.g-3 > * {
        margin-bottom: 1rem;
    }
}

/* Improved touch targets for mobile */
@media (max-width: 767px) {
    .btn {
        min-height: 44px;
        padding: 0.75rem 1rem;
    }

    .form-control,
    .form-select {
        min-height: 44px;
        padding: 0.75rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation enhancement
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Enhanced number input validation
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('input', function() {
            if (this.value < 0) {
                this.value = 0;
            }
        });

        // Prevent negative values on keydown
        input.addEventListener('keydown', function(e) {
            if (e.key === '-' || e.key === 'e' || e.key === 'E') {
                e.preventDefault();
            }
        });
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
            if (bootstrap.Alert) {
                try {
                    const alertInstance = bootstrap.Alert.getOrCreateInstance(alert);
                    alertInstance.close();
                } catch (error) {
                    alert.style.display = 'none';
                }
            }
        });
    }, 5000);

    // Enhanced modal keyboard navigation
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('shown.bs.modal', function() {
            const firstInput = this.querySelector('input:not([type="hidden"]):not([readonly]), select, textarea');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        });
    });

    // Improved form reset on modal hide
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            const form = this.querySelector('form');
            if (form) {
                form.classList.remove('was-validated');
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
            }
        });
    });

    console.log('✅ Section modals initialized with full responsive design');
});
</script>
