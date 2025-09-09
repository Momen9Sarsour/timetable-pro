{{-- Add Subject Type Modal - Compact Design --}}
@if (!$subjectType)
<div class="modal fade" id="addSubjectTypeModal" tabindex="-1" aria-labelledby="addSubjectTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0 py-2">
                <h6 class="modal-title d-flex align-items-center mb-0" id="addSubjectTypeModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>
                    Add New Subject Type
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.subject-types.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label for="add_subject_type_name" class="form-label fw-medium small">
                            <i class="fas fa-tag text-muted me-1"></i>
                            Subject Type Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control form-control-sm @error('subject_type_name', 'store') is-invalid @enderror"
                               id="add_subject_type_name"
                               name="subject_type_name"
                               value="{{ old('subject_type_name') }}"
                               placeholder="e.g., Core, Elective, Practical"
                               required>
                        @error('subject_type_name', 'store')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            <div class="form-text small">Enter a descriptive name for this subject type</div>
                        @enderror
                    </div>

                    <!-- Subject Type Examples - Compact -->
                    <div class="card bg-light border-0 mt-2">
                        <div class="card-body p-2">
                            <h6 class="card-title text-primary mb-1 small">
                                <i class="fas fa-lightbulb me-1"></i>
                                Common Types
                            </h6>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary clickable-badge" data-type="Core">Core</span>
                                <span class="badge bg-info bg-opacity-10 text-info clickable-badge" data-type="Elective">Elective</span>
                                <span class="badge bg-success bg-opacity-10 text-success clickable-badge" data-type="Practical">Practical</span>
                                <span class="badge bg-warning bg-opacity-10 text-warning clickable-badge" data-type="Theory">Theory</span>
                                <span class="badge bg-danger bg-opacity-10 text-danger clickable-badge" data-type="Lab">Lab</span>
                                <span class="badge bg-primary bg-opacity-10 text-primary clickable-badge" data-type="Seminar">Seminar</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save me-1"></i>Save Type
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Edit Subject Type Modal - Compact Design --}}
@if ($subjectType)
@php
    $subjectCount = $subjectType->subjects()->count();
@endphp
<div class="modal fade" id="editSubjectTypeModal-{{ $subjectType->id }}" tabindex="-1" aria-labelledby="editSubjectTypeModalLabel-{{ $subjectType->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0 py-2">
                <h6 class="modal-title d-flex align-items-center mb-0" id="editSubjectTypeModalLabel-{{ $subjectType->id }}">
                    <i class="fas fa-edit me-2"></i>
                    Edit Subject Type
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.subject-types.update', $subjectType->id) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body p-3">
                    <!-- Type Info Alert - Compact -->
                    <div class="alert alert-info p-2 mb-2" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-2"></i>
                            <div>
                                <small>Editing: <strong>{{ $subjectType->subject_type_name }}</strong></small>
                                @if($subjectCount > 0)
                                    <div class="badge bg-info bg-opacity-10 text-info ms-2">
                                        <i class="fas fa-book me-1"></i>{{ $subjectCount }} subjects
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="edit_subject_type_name_{{ $subjectType->id }}" class="form-label fw-medium small">
                            <i class="fas fa-tag text-muted me-1"></i>
                            Subject Type Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control form-control-sm @error('subject_type_name', 'update_'.$subjectType->id) is-invalid @enderror"
                               id="edit_subject_type_name_{{ $subjectType->id }}"
                               name="subject_type_name"
                               value="{{ old('subject_type_name', $subjectType->subject_type_name) }}"
                               required>
                        @error('subject_type_name', 'update_'.$subjectType->id)
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($subjectCount > 0)
                        <div class="alert alert-warning p-2 mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <small><strong>Note:</strong> Changing this type name will affect {{ $subjectCount }} existing subject(s).</small>
                        </div>
                    @endif
                </div>

                <div class="modal-footer bg-light border-0 p-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="fas fa-sync-alt me-1"></i>Update Type
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal - Compact Design --}}
@if ($subjectCount == 0)
<div class="modal fade" id="deleteSubjectTypeModal-{{ $subjectType->id }}" tabindex="-1" aria-labelledby="deleteSubjectTypeModalLabel-{{ $subjectType->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0 py-2">
                <h6 class="modal-title d-flex align-items-center mb-0" id="deleteSubjectTypeModalLabel-{{ $subjectType->id }}">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Deletion
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.subject-types.destroy', $subjectType->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-3">
                    <div class="text-center mb-2">
                        <i class="fas fa-tag text-danger" style="font-size: 2.5rem; opacity: 0.3;"></i>
                    </div>

                    <div class="alert alert-danger p-2 mb-2" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <h6 class="alert-heading mb-1 small">Are you sure?</h6>
                                <p class="mb-0 small">You are about to delete:</p>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light border-danger">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1 small">{{ $subjectType->subject_type_name }}</h6>
                                    <small class="text-muted">Type ID: {{ $subjectType->id }}</small>
                                </div>
                                <span class="badge bg-danger">Will be deleted</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning p-2 mt-2 mb-0" role="alert">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <small><strong>Warning:</strong> This action cannot be undone. Make sure no subjects are assigned to this type.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash me-1"></i>Yes, Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endif

{{-- Bulk Upload Modal - Compact Design --}}
<div class="modal fade" id="bulkUploadSubjectTypesModal" tabindex="-1" aria-labelledby="bulkUploadSubjectTypesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0 py-2">
                <h6 class="modal-title d-flex align-items-center mb-0" id="bulkUploadSubjectTypesModalLabel">
                    <i class="fas fa-file-excel me-2"></i>
                    Bulk Upload Subject Types
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.subject-types.bulkUpload') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-3" style="max-height: 60vh; overflow-y: auto;">
                    <div class="row g-2">
                        <!-- File Upload Section - Compact -->
                        <div class="col-12">
                            <label for="subject_type_excel_file" class="form-label fw-medium small">
                                <i class="fas fa-cloud-upload-alt text-muted me-1"></i>
                                Select Excel File <span class="text-danger">*</span>
                            </label>
                            <div class="upload-zone border border-2 border-dashed rounded-3 p-3 text-center cursor-pointer">
                                <i class="fas fa-file-excel text-success mb-2" style="font-size: 1.5rem;"></i>
                                <input class="form-control form-control-sm @error('subject_type_excel_file', 'bulkUploadSubjectTypes') is-invalid @enderror"
                                       type="file"
                                       id="subject_type_excel_file"
                                       name="subject_type_excel_file"
                                       accept=".xlsx,.xls,.csv"
                                       required>
                                @error('subject_type_excel_file', 'bulkUploadSubjectTypes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text mt-1">
                                    <small>Supported: .xlsx, .xls, .csv (Max: 5MB)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Instructions Section - Compact -->
                        <div class="col-12 mt-2">
                            <div class="card bg-light border-0">
                                <div class="card-header bg-transparent border-0 pb-0">
                                    <h6 class="card-title text-primary mb-0 small">
                                        <i class="fas fa-info-circle me-1"></i>
                                        File Format Instructions
                                    </h6>
                                </div>
                                <div class="card-body pt-1 pb-2">
                                    <ul class="list-unstyled mb-0" style="font-size: 0.75rem;">
                                        <li class="d-flex align-items-start mb-1">
                                            <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                            <span>First row: headers (subject_type_id, subject_type_name)</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-1">
                                            <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                            <span>Required: <code>subject_type_name</code> column</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-1">
                                            <i class="fas fa-sync-alt text-info me-2 mt-1 flex-shrink-0"></i>
                                            <span>Existing types will be skipped</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <i class="fas fa-filter text-warning me-2 mt-1 flex-shrink-0"></i>
                                            <span>Empty rows will be skipped</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Sample Format - Compact -->
                        <div class="col-12 mt-2">
                            <div class="card border-primary">
                                <div class="card-header bg-primary bg-opacity-10 border-primary py-1">
                                    <h6 class="card-title text-primary mb-0 small">
                                        <i class="fas fa-table me-1"></i>
                                        Sample Format
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0" style="font-size: 0.75rem;">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th>subject_type_id</th>
                                                    <th>subject_type_name</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><code>1</code></td>
                                                    <td>Core</td>
                                                </tr>
                                                <tr>
                                                    <td><code>2</code></td>
                                                    <td>Elective</td>
                                                </tr>
                                                <tr>
                                                    <td><code>3</code></td>
                                                    <td>Practical</td>
                                                </tr>
                                                <tr>
                                                    <td><code>4</code></td>
                                                    <td>Theory</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-upload me-1"></i>Upload & Process
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Compact modal styling */
.modal-content {
    font-size: 0.875rem;
}

.modal-header {
    padding: 0.5rem 0.75rem;
}

.modal-body {
    padding: 0.75rem;
}

.modal-footer {
    padding: 0.5rem 0.75rem;
}

/* Compact form controls */
.form-control-sm {
    font-size: 0.8125rem;
    padding: 0.375rem 0.5rem;
}

.form-label {
    margin-bottom: 0.25rem;
    font-size: 0.8125rem;
}

.form-text {
    font-size: 0.75rem;
    margin-top: 0.125rem;
}

/* Compact alerts and cards */
.alert {
    font-size: 0.8125rem;
    margin-bottom: 0.5rem;
}

.card-body {
    padding: 0.5rem;
}

.card-header {
    padding: 0.375rem 0.5rem;
}

/* Clickable badges for quick selection */
.clickable-badge {
    cursor: pointer;
    transition: all 0.15s ease;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.clickable-badge:hover {
    transform: scale(1.05);
    opacity: 0.8;
}

/* Upload zone compact */
.upload-zone {
    border-color: #dee2e6 !important;
    transition: all 0.15s ease;
    cursor: pointer;
    padding: 1rem !important;
}

.upload-zone:hover {
    border-color: var(--primary-color) !important;
    background-color: rgba(59, 130, 246, 0.05);
}

.upload-zone input[type="file"] {
    border: none;
    background: transparent;
    padding: 0.25rem 0;
    font-size: 0.8125rem;
}

/* Compact badges */
.badge {
    font-size: 0.7rem;
    font-weight: 500;
}

/* Compact buttons */
.btn-sm {
    font-size: 0.8125rem;
    padding: 0.25rem 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .modal-lg {
        max-width: 95%;
    }

    .modal-body {
        max-height: 50vh !important;
        padding: 0.5rem !important;
    }

    .upload-zone {
        padding: 0.75rem !important;
    }

    .upload-zone i {
        font-size: 1.25rem !important;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
    }

    .modal-content {
        font-size: 0.8125rem;
    }
}

/* Type icon styling */
.type-icon {
    transition: all 0.15s ease;
}

.type-icon:hover {
    transform: scale(1.05);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload enhancement for bulk upload
    const fileInput = document.getElementById('subject_type_excel_file');
    const uploadZone = document.querySelector('.upload-zone');

    if (fileInput && uploadZone) {
        // Click to open file dialog
        uploadZone.addEventListener('click', function() {
            fileInput.click();
        });

        // File change handler
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const icon = uploadZone.querySelector('i');
                const text = uploadZone.querySelector('.form-text');

                if (icon) {
                    icon.className = 'fas fa-file-check text-success mb-2';
                    icon.style.fontSize = '1.5rem';
                }

                if (text) {
                    text.innerHTML = `<small class="text-success fw-medium">✓ Selected: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</small>`;
                }
            }
        });

        // Drag & Drop functionality
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#3b82f6';
            this.style.backgroundColor = 'rgba(59, 130, 246, 0.05)';
        });

        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.backgroundColor = '';
        });

        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.backgroundColor = '';

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                const event = new Event('change', { bubbles: true });
                fileInput.dispatchEvent(event);
            }
        });
    }

    // Quick type selection from badges
    const clickableBadges = document.querySelectorAll('.clickable-badge');
    clickableBadges.forEach(badge => {
        badge.addEventListener('click', function() {
            const typeName = this.getAttribute('data-type');
            const input = document.getElementById('add_subject_type_name');

            if (input && !input.value.trim()) {
                input.value = typeName;
                input.focus();
            }
        });
    });

    // Auto-capitalize subject type names
    const typeNameInputs = document.querySelectorAll('input[name="subject_type_name"]');
    typeNameInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Auto-capitalize first letter of each word
            this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
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

    // Prevent duplicate subject type names (optional client-side validation)
    const existingSubjectTypes = @json($subjectTypes->pluck('subject_type_name')->toArray() ?? []);

    typeNameInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const inputValue = this.value.trim().toLowerCase();
            const isDuplicate = existingSubjectTypes.some(type =>
                type.toLowerCase() === inputValue &&
                this.id !== `edit_subject_type_name_${this.dataset.subjectTypeId}`
            );

            if (isDuplicate) {
                this.setCustomValidity('This subject type already exists');
                this.classList.add('is-invalid');

                // Add or update invalid feedback
                let feedback = this.parentNode.querySelector('.duplicate-feedback');
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback duplicate-feedback';
                    this.parentNode.appendChild(feedback);
                }
                feedback.textContent = 'This subject type already exists';
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');

                // Remove duplicate feedback
                const feedback = this.parentNode.querySelector('.duplicate-feedback');
                if (feedback) {
                    feedback.remove();
                }
            }
        });
    });
});
</script>
