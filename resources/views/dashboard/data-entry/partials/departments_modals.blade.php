{{-- Add Department Modal --}}
@if (!$department)
<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="addDepartmentModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>
                    Add New Department
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.departments.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="add_department_no" class="form-label fw-medium">
                                <i class="fas fa-hashtag text-muted me-1"></i>
                                Department Number/Code <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('department_no') is-invalid @enderror"
                                   id="add_department_no"
                                   name="department_no"
                                   value="{{ old('department_no') }}"
                                   placeholder="e.g., CS01, IT02"
                                   required>
                            @error('department_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Enter a unique code for this department</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="add_department_name" class="form-label fw-medium">
                                <i class="fas fa-building text-muted me-1"></i>
                                Department Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('department_name') is-invalid @enderror"
                                   id="add_department_name"
                                   name="department_name"
                                   value="{{ old('department_name') }}"
                                   placeholder="e.g., Computer Science"
                                   required>
                            @error('department_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Enter the full name of the department</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Edit Department Modal --}}
@if ($department)
<div class="modal fade" id="editDepartmentModal-{{ $department->id }}" tabindex="-1" aria-labelledby="editDepartmentModalLabel-{{ $department->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title d-flex align-items-center" id="editDepartmentModalLabel-{{ $department->id }}">
                    <i class="fas fa-edit me-2"></i>
                    Edit Department
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.departments.update', $department->id) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <!-- Department Info Card -->
                    <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Editing: <strong>{{ $department->department_name }}</strong></small>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="edit_department_no_{{ $department->id }}" class="form-label fw-medium">
                                <i class="fas fa-hashtag text-muted me-1"></i>
                                Department Number/Code <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('department_no', 'update_'.$department->id) is-invalid @enderror"
                                   id="edit_department_no_{{ $department->id }}"
                                   name="department_no"
                                   value="{{ old('department_no', $department->department_no) }}"
                                   required>
                            @error('department_no', 'update_'.$department->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="edit_department_name_{{ $department->id }}" class="form-label fw-medium">
                                <i class="fas fa-building text-muted me-1"></i>
                                Department Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('department_name', 'update_'.$department->id) is-invalid @enderror"
                                   id="edit_department_name_{{ $department->id }}"
                                   name="department_name"
                                   value="{{ old('department_name', $department->department_name) }}"
                                   required>
                            @error('department_name', 'update_'.$department->id)
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
                        <i class="fas fa-sync-alt me-1"></i>Update Department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteDepartmentModal-{{ $department->id }}" tabindex="-1" aria-labelledby="deleteDepartmentModalLabel-{{ $department->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="deleteDepartmentModalLabel-{{ $department->id }}">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.departments.destroy', $department->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-building text-danger" style="font-size: 3rem; opacity: 0.3;"></i>
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
                                    <h6 class="card-title mb-1">{{ $department->department_name }}</h6>
                                    <small class="text-muted">Code: {{ $department->department_no }}</small>
                                </div>
                                <span class="badge bg-danger">Will be deleted</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <small><strong>Warning:</strong> This action cannot be undone. Associated instructors, subjects, or plans might be affected.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Yes, Delete Department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Bulk Upload Modal --}}
<div class="modal fade" id="bulkUploadDepartmentsModal" tabindex="-1" aria-labelledby="bulkUploadDepartmentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="bulkUploadDepartmentsModalLabel">
                    <i class="fas fa-file-excel me-2"></i>
                    Bulk Upload Departments
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.departments.bulkUpload') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-4">
                        <!-- File Upload Section -->
                        <div class="col-12">
                            <label for="department_excel_file" class="form-label fw-medium">
                                <i class="fas fa-cloud-upload-alt text-muted me-1"></i>
                                Select Excel File <span class="text-danger">*</span>
                            </label>
                            <div class="upload-zone border border-2 border-dashed rounded-3 p-4 text-center cursor-pointer">
                                <i class="fas fa-file-excel text-success mb-2" style="font-size: 2rem;"></i>
                                <input class="form-control @error('department_excel_file', 'bulkUploadDepartments') is-invalid @enderror"
                                       type="file"
                                       id="department_excel_file"
                                       name="department_excel_file"
                                       accept=".xlsx,.xls,.csv"
                                       required>
                                @error('department_excel_file', 'bulkUploadDepartments')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text mt-2">
                                    <small>Supported formats: .xlsx, .xls, .csv (Max: 5MB)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Instructions Section -->
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-header bg-transparent border-0 pb-0">
                                    <h6 class="card-title text-primary mb-0">
                                        <i class="fas fa-info-circle me-1"></i>
                                        File Format Instructions
                                    </h6>
                                </div>
                                <div class="card-body pt-2">
                                    <ul class="list-unstyled mb-0 small">
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                            <span>The first row should be headers (e.g., department_no, department_name)</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                            <span>Required columns: <code>department_no</code> and <code>department_name</code></span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-sync-alt text-info me-2 mt-1 flex-shrink-0"></i>
                                            <span>Existing departments will be updated, new ones will be created</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <i class="fas fa-filter text-warning me-2 mt-1 flex-shrink-0"></i>
                                            <span>Empty rows will be automatically skipped</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Sample Format -->
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-header bg-primary bg-opacity-10 border-primary">
                                    <h6 class="card-title text-primary mb-0">
                                        <i class="fas fa-table me-1"></i>
                                        Sample Format
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th style="font-size: 0.75rem;">department_no</th>
                                                    <th style="font-size: 0.75rem;">department_name</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><code class="small">CS01</code></td>
                                                    <td class="small">Computer Science</td>
                                                </tr>
                                                <tr>
                                                    <td><code class="small">IT02</code></td>
                                                    <td class="small">Information Technology</td>
                                                </tr>
                                                <tr>
                                                    <td><code class="small">EE03</code></td>
                                                    <td class="small">Electrical Engineering</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i>Upload and Process
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Upload Zone Styles */
.upload-zone {
    border-color: #dee2e6 !important;
    transition: all 0.15s ease;
    cursor: pointer;
}

.upload-zone:hover {
    border-color: var(--primary-color) !important;
    background-color: rgba(59, 130, 246, 0.05);
}

.upload-zone input[type="file"] {
    border: none;
    background: transparent;
    padding: 0.5rem 0;
}

/* Modal Responsive */
@media (max-width: 768px) {
    .modal-lg {
        max-width: 95%;
    }

    .modal-body {
        max-height: 60vh !important;
        padding: 1rem !important;
    }

    .upload-zone {
        padding: 2rem 1rem !important;
    }

    .upload-zone i {
        font-size: 1.5rem !important;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload enhancement
    const fileInput = document.getElementById('department_excel_file');
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
                    icon.style.fontSize = '2rem';
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
});
</script>
