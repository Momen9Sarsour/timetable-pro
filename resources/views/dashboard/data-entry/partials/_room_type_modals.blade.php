{{-- Add Room Type Modal --}}
@if (!$roomType)
<div class="modal fade" id="addRoomTypeModal" tabindex="-1" aria-labelledby="addRoomTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="addRoomTypeModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>
                    Add New Room Type
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.room-types.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="add_room_type_name" class="form-label fw-medium">
                                <i class="fas fa-door-closed text-muted me-1"></i>
                                Room Type Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('room_type_name', 'store') is-invalid @enderror"
                                   id="add_room_type_name"
                                   name="room_type_name"
                                   value="{{ old('room_type_name') }}"
                                   placeholder="e.g., Classroom, Laboratory, Auditorium"
                                   required>
                            @error('room_type_name', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Enter a descriptive name for this room type</div>
                            @enderror
                        </div>

                        <!-- Room Type Examples -->
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-header bg-transparent border-0 pb-0">
                                    <h6 class="card-title text-primary mb-0">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Common Room Types
                                    </h6>
                                </div>
                                <div class="card-body pt-2">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul class="list-unstyled mb-0 small">
                                                <li class="mb-1"><i class="fas fa-chalkboard-teacher text-primary me-1"></i> Classroom</li>
                                                <li class="mb-1"><i class="fas fa-flask text-success me-1"></i> Laboratory</li>
                                                <li class="mb-1"><i class="fas fa-desktop text-info me-1"></i> Computer Lab</li>
                                                <li class="mb-1"><i class="fas fa-users text-warning me-1"></i> Auditorium</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <ul class="list-unstyled mb-0 small">
                                                <li class="mb-1"><i class="fas fa-coffee text-secondary me-1"></i> Office</li>
                                                <li class="mb-1"><i class="fas fa-book text-danger me-1"></i> Library</li>
                                                <li class="mb-1"><i class="fas fa-handshake text-muted me-1"></i> Conference Room</li>
                                                <li class="mb-1"><i class="fas fa-heartbeat text-danger me-1"></i> Medical Room</li>
                                            </ul>
                                        </div>
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
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Room Type
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Edit Room Type Modal --}}
@if ($roomType)
@php
    $roomCount = $roomType->rooms()->count();
@endphp
<div class="modal fade" id="editRoomTypeModal-{{ $roomType->id }}" tabindex="-1" aria-labelledby="editRoomTypeModalLabel-{{ $roomType->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title d-flex align-items-center" id="editRoomTypeModalLabel-{{ $roomType->id }}">
                    <i class="fas fa-edit me-2"></i>
                    Edit Room Type
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.room-types.update', $roomType->id) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <!-- Room Type Info Card -->
                    <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            <small>Editing: <strong>{{ $roomType->room_type_name }}</strong></small>
                            @if($roomCount > 0)
                                <div class="badge bg-info bg-opacity-10 text-info mt-1">
                                    <i class="fas fa-door-open me-1"></i>{{ $roomCount }} rooms using this type
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="edit_room_type_name_{{ $roomType->id }}" class="form-label fw-medium">
                                <i class="fas fa-door-closed text-muted me-1"></i>
                                Room Type Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('room_type_name', 'update_'.$roomType->id) is-invalid @enderror"
                                   id="edit_room_type_name_{{ $roomType->id }}"
                                   name="room_type_name"
                                   value="{{ old('room_type_name', $roomType->room_type_name) }}"
                                   required>
                            @error('room_type_name', 'update_'.$roomType->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($roomCount > 0)
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <small><strong>Note:</strong> Changing this room type name will affect {{ $roomCount }} existing room(s).</small>
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
                        <i class="fas fa-sync-alt me-1"></i>Update Room Type
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
@if ($roomCount == 0)
<div class="modal fade" id="deleteRoomTypeModal-{{ $roomType->id }}" tabindex="-1" aria-labelledby="deleteRoomTypeModalLabel-{{ $roomType->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="deleteRoomTypeModalLabel-{{ $roomType->id }}">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.room-types.destroy', $roomType->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-door-closed text-danger" style="font-size: 3rem; opacity: 0.3;"></i>
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
                                    <h6 class="card-title mb-1">{{ $roomType->room_type_name }}</h6>
                                    <small class="text-muted">Type ID: {{ $roomType->id }}</small>
                                </div>
                                <span class="badge bg-danger">Will be deleted</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <small><strong>Warning:</strong> This action cannot be undone. Make sure no rooms are assigned to this type.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Yes, Delete Room Type
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endif

{{-- Bulk Upload Modal --}}
<div class="modal fade" id="bulkUploadRoomTypesModal" tabindex="-1" aria-labelledby="bulkUploadRoomTypesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="bulkUploadRoomTypesModalLabel">
                    <i class="fas fa-file-excel me-2"></i>
                    Bulk Upload Room Types
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.room-types.bulkUpload') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-4">
                        <!-- File Upload Section -->
                        <div class="col-12">
                            <label for="room_type_excel_file" class="form-label fw-medium">
                                <i class="fas fa-cloud-upload-alt text-muted me-1"></i>
                                Select Excel File <span class="text-danger">*</span>
                            </label>
                            <div class="upload-zone border border-2 border-dashed rounded-3 p-4 text-center cursor-pointer">
                                <i class="fas fa-file-excel text-success mb-2" style="font-size: 2rem;"></i>
                                <input class="form-control @error('room_type_excel_file', 'bulkUploadRoomTypes') is-invalid @enderror"
                                       type="file"
                                       id="room_type_excel_file"
                                       name="room_type_excel_file"
                                       accept=".xlsx,.xls,.csv"
                                       required>
                                @error('room_type_excel_file', 'bulkUploadRoomTypes')
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
                                            <span>The first row should be headers (e.g., room_type_id, room_type_name)</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                            <span>Required column: <code>room_type_name</code></span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-sync-alt text-info me-2 mt-1 flex-shrink-0"></i>
                                            <span>Existing room types will be skipped (no duplicates)</span>
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
                                                    <th style="font-size: 0.75rem;">room_type_id</th>
                                                    <th style="font-size: 0.75rem;">room_type_name</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><code class="small">1</code></td>
                                                    <td class="small">Classroom</td>
                                                </tr>
                                                <tr>
                                                    <td><code class="small">2</code></td>
                                                    <td class="small">Laboratory</td>
                                                </tr>
                                                <tr>
                                                    <td><code class="small">3</code></td>
                                                    <td class="small">Computer Lab</td>
                                                </tr>
                                                <tr>
                                                    <td><code class="small">4</code></td>
                                                    <td class="small">Auditorium</td>
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
/* Room type specific styling */
.type-icon {
    transition: all 0.15s ease;
}

.type-icon:hover {
    transform: scale(1.05);
}

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

/* Room count badges */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
}

/* Modal responsive design */
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

    .table-responsive {
        font-size: 0.875rem;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
    }
}

/* Auto-capitalize enhancement */
.form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
}

/* Common room types list styling */
.card ul li {
    transition: color 0.15s ease;
}

.card ul li:hover {
    color: var(--primary-color) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload enhancement for bulk upload
    const fileInput = document.getElementById('room_type_excel_file');
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

    // Auto-capitalize room type names
    const typeNameInputs = document.querySelectorAll('input[name="room_type_name"]');
    typeNameInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Auto-capitalize first letter of each word
            this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
        });
    });

    // Quick insert common room types (optional enhancement)
    const commonTypes = document.querySelectorAll('.card ul li');
    commonTypes.forEach(typeItem => {
        typeItem.addEventListener('click', function() {
            const typeName = this.textContent.trim();
            const activeInput = document.querySelector('input[name="room_type_name"]:focus') ||
                               document.querySelector('#add_room_type_name');

            if (activeInput && !activeInput.value.trim()) {
                activeInput.value = typeName;
                activeInput.focus();
            }
        });

        // Add cursor pointer
        typeItem.style.cursor = 'pointer';
        typeItem.title = 'Click to use this room type';
    });

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

    // Prevent duplicate room type names (optional client-side validation)
    const existingRoomTypes = @json($roomTypes->pluck('room_type_name')->toArray() ?? []);

    typeNameInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const inputValue = this.value.trim().toLowerCase();
            const isDuplicate = existingRoomTypes.some(type =>
                type.toLowerCase() === inputValue &&
                this.id !== `edit_room_type_name_${this.dataset.roomTypeId}`
            );

            if (isDuplicate) {
                this.setCustomValidity('This room type already exists');
                this.classList.add('is-invalid');

                // Add or update invalid feedback
                let feedback = this.parentNode.querySelector('.duplicate-feedback');
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback duplicate-feedback';
                    this.parentNode.appendChild(feedback);
                }
                feedback.textContent = 'This room type already exists';
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
