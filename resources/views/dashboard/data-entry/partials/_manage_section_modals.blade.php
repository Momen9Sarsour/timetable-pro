{{-- Add Section Modal --}}
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="addSectionModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>
                    Add New Section
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="addSectionForm" action="#" method="POST" class="needs-validation" novalidate>
                @csrf
                <input type="hidden" name="plan_subject_id_from_modal" id="add_modal_plan_subject_id" value="{{ old('plan_subject_id_from_modal') }}">
                <input type="hidden" name="activity_type_from_modal" id="add_modal_activity_type" value="{{ old('activity_type_from_modal') }}">
                <input type="hidden" name="academic_year" id="add_modal_academic_year" value="{{ old('academic_year') }}">
                <input type="hidden" name="semester" id="add_modal_semester" value="{{ old('semester') }}">
                <input type="hidden" name="branch" id="add_modal_branch" value="{{ old('branch') }}">

                <div class="modal-body p-4">
                    <!-- Validation Errors -->
                    @if ($errors->any() && !$errors->has('section_number') && !$errors->has('student_count') && !$errors->has('section_gender') && !$errors->has('plan_subject_id_from_modal') && !$errors->has('activity_type_from_modal'))
                        <div class="alert alert-danger d-flex align-items-start mb-3" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <strong class="d-block mb-1">Please review the following errors:</strong>
                                <ul class="mb-0 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="add_section_number" class="form-label fw-medium">
                                <i class="fas fa-hashtag text-muted me-1"></i>
                                Section Number <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('section_number') is-invalid @enderror"
                                   id="add_section_number"
                                   name="section_number"
                                   value="{{ old('section_number', 1) }}"
                                   required
                                   min="1"
                                   max="999">
                            @error('section_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Unique number for this section</div>
                            @enderror
                            @error('section_unique')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="add_student_count" class="form-label fw-medium">
                                <i class="fas fa-users text-muted me-1"></i>
                                Student Count <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('student_count') is-invalid @enderror"
                                   id="add_student_count"
                                   name="student_count"
                                   value="{{ old('student_count', 0) }}"
                                   required
                                   min="0"
                                   max="1000">
                            @error('student_count')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Number of students in this section</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="add_section_gender" class="form-label fw-medium">
                                <i class="fas fa-users text-muted me-1"></i>
                                Section Gender <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('section_gender') is-invalid @enderror"
                                    id="add_section_gender"
                                    name="section_gender"
                                    required>
                                <option value="Mixed" {{ old('section_gender', 'Mixed') == 'Mixed' ? 'selected' : '' }}>
                                    <i class="fas fa-users"></i> Mixed
                                </option>
                                <option value="Male" {{ old('section_gender') == 'Male' ? 'selected' : '' }}>
                                    <i class="fas fa-mars"></i> Male Only
                                </option>
                                <option value="Female" {{ old('section_gender') == 'Female' ? 'selected' : '' }}>
                                    <i class="fas fa-venus"></i> Female Only
                                </option>
                            </select>
                            @error('section_gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Gender composition of the section</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="add_branch_display_modal" class="form-label fw-medium">
                                <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                Branch
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="add_branch_display_modal"
                                   readonly>
                            <div class="form-text">Branch is determined by the context</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Section
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Section Modal --}}
<div class="modal fade" id="editSectionModal" tabindex="-1" aria-labelledby="editSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title d-flex align-items-center" id="editSectionModalLabel">
                    <i class="fas fa-edit me-2"></i>
                    Edit Section
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="editSectionForm" action="#" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <!-- Context Info -->
                    <div class="alert alert-info d-flex align-items-center mb-3" role="alert" id="edit_context_info_modal_text">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            <strong>Context:</strong> <span>Loading...</span>
                        </div>
                    </div>

                    <!-- Validation Errors -->
                    @if ($errors->any() && !$errors->has('section_number') && !$errors->has('student_count') && !$errors->has('section_gender'))
                        <div class="alert alert-danger d-flex align-items-start mb-3" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <strong class="d-block mb-1">Please review the following errors:</strong>
                                <ul class="mb-0 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_modal_section_number" class="form-label fw-medium">
                                <i class="fas fa-hashtag text-muted me-1"></i>
                                Section Number <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('section_number') is-invalid @enderror"
                                   id="edit_modal_section_number"
                                   name="section_number"
                                   value=""
                                   required
                                   min="1"
                                   max="999">
                            @error('section_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('section_unique')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="edit_modal_student_count" class="form-label fw-medium">
                                <i class="fas fa-users text-muted me-1"></i>
                                Student Count <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('student_count') is-invalid @enderror"
                                   id="edit_modal_student_count"
                                   name="student_count"
                                   value=""
                                   required
                                   min="0"
                                   max="1000">
                            @error('student_count')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="edit_modal_section_gender" class="form-label fw-medium">
                                <i class="fas fa-users text-muted me-1"></i>
                                Section Gender <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('section_gender') is-invalid @enderror"
                                    id="edit_modal_section_gender"
                                    name="section_gender"
                                    required>
                                <option value="Mixed">Mixed</option>
                                <option value="Male">Male Only</option>
                                <option value="Female">Female Only</option>
                            </select>
                            @error('section_gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="edit_modal_branch_display" class="form-label fw-medium">
                                <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                Branch
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="edit_modal_branch_display"
                                   readonly>
                            <div class="form-text">Branch is determined by the context</div>
                        </div>
                    </div>

                    <div id="edit_form_errors_placeholder"></div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-sync-alt me-1"></i>Update Section
                    </button>
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
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="deleteSectionForm" action="#" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-users-class text-danger" style="font-size: 3rem; opacity: 0.3;"></i>
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
                                    <h6 class="card-title mb-1" id="deleteSectionInfoText">This section</h6>
                                    <small class="text-muted">This action cannot be undone</small>
                                </div>
                                <span class="badge bg-danger">Will be deleted</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <small><strong>Warning:</strong> This action cannot be undone. Any associated schedules or assignments will be affected.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Yes, Delete Section
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Modal Responsive Enhancements */
@media (max-width: 768px) {
    .modal-lg {
        max-width: 95%;
        margin: 0.5rem;
    }

    .modal-body {
        padding: 1rem !important;
    }

    .row .col-md-6 {
        margin-bottom: 1rem;
    }

    .btn {
        font-size: 0.8125rem;
        padding: 0.375rem 0.75rem;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.25rem;
    }

    .modal-header h5 {
        font-size: 1rem;
    }

    .btn {
        font-size: 0.75rem;
        padding: 0.5rem 0.75rem;
    }

    .form-label {
        font-size: 0.875rem;
    }

    .form-control,
    .form-select {
        font-size: 0.875rem;
    }

    .modal-footer {
        flex-direction: column;
        gap: 0.5rem;
    }

    .modal-footer .btn {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .modal-header h5 {
        font-size: 0.9rem;
    }

    .card-title {
        font-size: 0.9rem;
    }

    .btn {
        font-size: 0.7rem;
        padding: 0.375rem 0.5rem;
    }
}

/* Form Enhancements */
.form-control:focus,
.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.was-validated .form-control:valid,
.was-validated .form-select:valid {
    border-color: #22c55e;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2322c55e' d='m2.3 6.73.4.4c.2.2.6.2.8 0L7.7 3.3a.6.6 0 0 0-.8-.8L3.7 5.7l-1.1-1.1a.6.6 0 0 0-.8.8l.5.3z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(.375em + .1875rem) center;
    background-size: calc(.75em + .375rem) calc(.75em + .375rem);
}

.was-validated .form-control:invalid,
.was-validated .form-select:invalid {
    border-color: #ef4444;
}

/* Enhanced badge styling */
.badge {
    font-weight: 500;
    letter-spacing: 0.025em;
}

/* Loading state for buttons */
.btn-loading {
    position: relative;
    pointer-events: none;
}

.btn-loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}

/* Enhanced modal backdrop */
.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.6);
}

body.dark-mode .modal-backdrop {
    background-color: rgba(0, 0, 0, 0.8);
}

/* Modal slide animation */
.modal.show .modal-dialog {
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Dark mode support */
body.dark-mode .modal-content {
    background: var(--dark-bg-secondary);
    color: var(--dark-text-secondary);
}

body.dark-mode .modal-header {
    border-bottom-color: var(--dark-border);
}

body.dark-mode .modal-footer {
    border-top-color: var(--dark-border);
}

body.dark-mode .form-control,
body.dark-mode .form-select {
    background: var(--dark-bg);
    border-color: var(--dark-border);
    color: var(--dark-text-secondary);
}

body.dark-mode .form-control:focus,
body.dark-mode .form-select:focus {
    background: var(--dark-bg);
    border-color: var(--primary-color);
}

/* Enhanced accessibility */
.btn:focus,
.form-control:focus,
.form-select:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* Print styles */
@media print {
    .modal,
    .modal-backdrop {
        display: none !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced form validation
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

    // Input validation enhancements
    const numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(input => {
        input.addEventListener('input', function() {
            const value = parseInt(this.value);
            const min = parseInt(this.getAttribute('min'));
            const max = parseInt(this.getAttribute('max'));

            if (value < min) {
                this.setCustomValidity(`Minimum value is ${min}`);
            } else if (value > max) {
                this.setCustomValidity(`Maximum value is ${max}`);
            } else {
                this.setCustomValidity('');
            }
        });
    });

    // Modal reset functionality
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            const form = this.querySelector('form');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');

                // Clear custom validation messages
                form.querySelectorAll('input, select, textarea').forEach(input => {
                    input.setCustomValidity('');
                });

                // Clear error displays
                form.querySelectorAll('.is-invalid').forEach(element => {
                    element.classList.remove('is-invalid');
                });

                form.querySelectorAll('.invalid-feedback, .text-danger').forEach(element => {
                    element.remove();
                });
            }
        });
    });

    // Enhanced loading states
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && this.checkValidity()) {
                submitBtn.classList.add('btn-loading');
                submitBtn.disabled = true;

                // Re-enable after 10 seconds as fallback
                setTimeout(() => {
                    submitBtn.classList.remove('btn-loading');
                    submitBtn.disabled = false;
                }, 10000);
            }
        });
    });

    // Auto-focus first input in modals
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('shown.bs.modal', function() {
            const firstInput = this.querySelector('input, select, textarea');
            if (firstInput && !firstInput.hasAttribute('readonly')) {
                firstInput.focus();
            }
        });
    });

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        // Escape key closes modals
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                const modal = bootstrap.Modal.getInstance(openModal);
                modal?.hide();
            }
        }

        // Ctrl+Enter submits forms in modals
        if (e.ctrlKey && e.key === 'Enter') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                const form = openModal.querySelector('form');
                if (form && form.checkValidity()) {
                    form.submit();
                }
            }
        }
    });

    // Enhanced error handling
    function displayFormErrors(form, errors) {
        // Clear existing errors
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        // Display new errors
        Object.keys(errors).forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.classList.add('is-invalid');

                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = errors[fieldName][0];
                field.parentNode.appendChild(feedback);
            }
        });
    }

    // Dynamic form field updates
    const sectionNumberInputs = document.querySelectorAll('input[name="section_number"]');
    sectionNumberInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Auto-suggest next available section number
            const currentValue = parseInt(this.value);
            if (currentValue && currentValue > 0) {
                // Logic could be added here to check for conflicts
                this.setCustomValidity('');
            }
        });
    });

    // Gender selection enhancement
    const genderSelects = document.querySelectorAll('select[name="section_gender"]');
    genderSelects.forEach(select => {
        select.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];

            // Add visual feedback based on selection
            this.className = this.className.replace(/bg-\w+-opacity-10/, '');

            switch(this.value) {
                case 'Mixed':
                    this.classList.add('bg-info-opacity-10');
                    break;
                case 'Male':
                    this.classList.add('bg-primary-opacity-10');
                    break;
                case 'Female':
                    this.classList.add('bg-danger-opacity-10');
                    break;
            }
        });
    });

    // Student count validation and suggestions
    const studentCountInputs = document.querySelectorAll('input[name="student_count"]');
    studentCountInputs.forEach(input => {
        input.addEventListener('input', function() {
            const value = parseInt(this.value);

            // Provide feedback on typical class sizes
            let feedback = '';
            if (value === 0) {
                feedback = 'Empty section';
            } else if (value < 10) {
                feedback = 'Small class size';
            } else if (value <= 30) {
                feedback = 'Normal class size';
            } else if (value <= 50) {
                feedback = 'Large class size';
            } else {
                feedback = 'Very large class size';
            }

            // Update form text if exists
            const formText = this.parentNode.querySelector('.form-text');
            if (formText && value > 0) {
                formText.textContent = `${feedback} (${value} students)`;
            }
        });
    });

    console.log('✅ Section modals initialized successfully');
});
</script>
