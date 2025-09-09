@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-chalkboard-teacher text-primary me-2"></i>
                        Instructor Subject Assignments
                    </h4>
                    <p class="text-muted mb-0">Manage teaching loads and subject assignments for instructors</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <form action="{{ route('data-entry.instructor-load.generateMissingSections') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit"
                                class="btn btn-info btn-sm"
                                onclick="return confirm('This will generate sections for any plan context that has expected students but no sections yet. Continue?');"
                                title="Generate Missing Sections">
                            <i class="fas fa-magic me-1"></i>
                            <span class="d-none d-sm-inline">Generate Missing</span>
                        </button>
                    </form>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#importInstructorLoadsModal">
                        <i class="fas fa-file-excel me-1"></i>
                        <span class="d-none d-sm-inline">Import Loads</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    @if (session('skipped_details'))
        <div class="alert alert-warning d-flex align-items-start mb-4">
            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
            <div>
                <strong class="d-block mb-1">Import Process - Skipped Rows:</strong>
                <ul class="mb-0 small" style="max-height: 200px; overflow-y: auto;">
                    @foreach (session('skipped_details') as $detail)
                        <li>{{ $detail }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Main Content Card -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list text-muted me-2"></i>
                            Instructors & Assignment Overview
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $instructors->total() }} Instructors</span>
                    </div>
                </div>

                <div class="card-body pt-3">
                    @if($instructors->count() > 0)
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 text-center" style="width: 50px;">#</th>
                                        <th class="border-0">Instructor No</th>
                                        <th class="border-0">Instructor Name</th>
                                        <th class="border-0">Department</th>
                                        <th class="border-0 text-center">Assigned Subjects</th>
                                        <th class="border-0 text-center" style="width: 140px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($instructors as $index => $instructor)
                                        <tr class="border-bottom">
                                            <td class="text-center text-muted">
                                                <small>{{ $instructors->firstItem() + $index }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark font-monospace">{{ $instructor->instructor_no }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                                        {{ strtoupper(substr($instructor->instructor_name ?? optional($instructor->user)->name, 0, 2)) }}
                                                    </div>
                                                    <span class="fw-medium">{{ $instructor->instructor_name ?? optional($instructor->user)->name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info bg-opacity-10 text-info">
                                                    {{ optional($instructor->department)->department_name ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $instructor->sections_count > 0 ? 'success' : 'secondary' }} bg-opacity-15 text-{{ $instructor->sections_count > 0 ? 'light' : 'light' }} px-3 py-2">
                                                    {{ $instructor->sections_count }}
                                                    <small>{{ $instructor->sections_count == 1 ? 'subject' : 'subjects' }}</small>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('data-entry.instructor-section.edit', $instructor->id) }}"
                                                   class="btn btn-outline-primary btn-sm"
                                                   title="Edit Section Assignments">
                                                    <i class="fas fa-edit me-1"></i>Edit Assignments
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="d-md-none">
                            @foreach ($instructors as $index => $instructor)
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-start flex-grow-1">
                                                <div class="user-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 0.875rem;">
                                                    {{ strtoupper(substr($instructor->instructor_name ?? optional($instructor->user)->name, 0, 2)) }}
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="card-title mb-1">{{ $instructor->instructor_name ?? optional($instructor->user)->name }}</h6>
                                                    <div class="d-flex flex-wrap gap-1 mb-2">
                                                        <span class="badge bg-light text-dark font-monospace">{{ $instructor->instructor_no }}</span>
                                                        @if($instructor->department)
                                                            <span class="badge bg-info bg-opacity-10 text-info">{{ $instructor->department->department_name }}</span>
                                                        @endif
                                                        <span class="badge bg-{{ $instructor->sections_count > 0 ? 'success' : 'secondary' }} bg-opacity-15 text-{{ $instructor->sections_count > 0 ? 'light' : 'light' }}">
                                                            {{ $instructor->sections_count }} subjects
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small">#{{ $instructors->firstItem() + $index }}</span>
                                            <a href="{{ route('data-entry.instructor-section.edit', $instructor->id) }}"
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-edit me-1"></i>Edit Assignments
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($instructors->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $instructors->links('pagination::bootstrap-5') }}
                            </div>
                        @endif

                    @else
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-chalkboard-teacher text-muted opacity-50" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 text-muted">No Instructors Found</h5>
                                <p class="text-muted mb-4">Add instructors first before managing their subject assignments.</p>
                                <a href="{{ route('data-entry.instructors.index') }}" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Manage Instructors
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Instructor Loads Modal -->
<div class="modal fade" id="importInstructorLoadsModal" tabindex="-1" aria-labelledby="importInstructorLoadsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="importInstructorLoadsModalLabel">
                    <i class="fas fa-file-excel me-2"></i>
                    Import Instructor Loads from Excel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.instructor-load.importExcel') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-4">
                        <!-- Target Semester Selection -->
                        <div class="col-12">
                            <label for="target_semester_import" class="form-label fw-medium">
                                <i class="fas fa-calendar-alt text-muted me-1"></i>
                                Target Semester <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('target_semester') is-invalid @enderror"
                                    id="target_semester_import"
                                    name="target_semester"
                                    required>
                                <option value="" selected disabled>Select semester...</option>
                                <option value="1" {{ old('target_semester') == '1' ? 'selected' : '' }}>First Semester</option>
                                <option value="2" {{ old('target_semester') == '2' ? 'selected' : '' }}>Second Semester</option>
                                <option value="3" {{ old('target_semester') == '3' ? 'selected' : '' }}>Summer Semester</option>
                            </select>
                            @error('target_semester')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Select the academic semester for load assignments</div>
                            @enderror
                        </div>

                        <!-- File Upload Section -->
                        <div class="col-12">
                            <label for="instructor_loads_excel_file_input" class="form-label fw-medium">
                                <i class="fas fa-cloud-upload-alt text-muted me-1"></i>
                                Select Excel File <span class="text-danger">*</span>
                            </label>
                            <div class="upload-zone border border-2 border-dashed rounded-3 p-4 text-center cursor-pointer">
                                <i class="fas fa-file-excel text-success mb-2" style="font-size: 2rem;"></i>
                                <input class="form-control @error('instructor_loads_excel_file') is-invalid @enderror"
                                       type="file"
                                       id="instructor_loads_excel_file_input"
                                       name="instructor_loads_excel_file"
                                       accept=".xlsx,.xls,.csv"
                                       required>
                                @error('instructor_loads_excel_file')
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
                                            <span>Required headers: <strong>instructor_name, subject_name,  program_level,  theory_sections، practical_sections, branch</strong></span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                            <span>Ensure "program_level" format is like "CSE2" (Code + Level Number)</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-sync-alt text-info me-2 mt-1 flex-shrink-0"></i>
                                            <span>System will assign available sections to instructors based on counts, branch, and selected semester</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <i class="fas fa-filter text-warning me-2 mt-1 flex-shrink-0"></i>
                                            <span>Empty rows will be automatically skipped during processing</span>
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
                                                    <th style="font-size: 0.7rem;">instructor_name</th>
                                                    <th style="font-size: 0.7rem;">subject_name</th>
                                                    <th style="font-size: 0.7rem;">program_level</th>
                                                    <th style="font-size: 0.7rem;">theory_sections</th>
                                                    <th style="font-size: 0.7rem;">practical_sections</th>
                                                    <th style="font-size: 0.7rem;">branch</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="small">Dr. Ahmad Mohammad</td>
                                                    <td class="small">Computer Programming</td>
                                                    <td><code class="small">CSE1</code></td>
                                                    <td class="small text-center">2</td>
                                                    <td class="small text-center">1</td>
                                                    <td class="small">Gaza</td>
                                                </tr>
                                                <tr>
                                                    <td class="small">Dr. Fatima Ali</td>
                                                    <td class="small">Database Systems</td>
                                                    <td><code class="small">CSE3</code></td>
                                                    <td class="small text-center">1</td>
                                                    <td class="small text-center">2</td>
                                                    <td class="small">Gaza</td>
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
                        <i class="fas fa-upload me-1"></i>Upload and Assign Loads
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

/* User Avatar Improvements */
.user-avatar {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border: 2px solid rgba(255, 255, 255, 0.2);
    transition: all 0.15s ease;
}

/* Badge Enhancements */
.badge {
    font-weight: 500;
    letter-spacing: 0.025em;
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

    .btn {
        font-size: 0.8125rem;
        padding: 0.375rem 0.75rem;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
    }

    .table th,
    .table td {
        font-size: 0.75rem;
        padding: 0.5rem 0.25rem;
    }
}

/* Card Hover Effects */
.card {
    transition: all 0.15s ease;
}

.card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Empty State Styling */
.empty-state i {
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload enhancement
    const fileInput = document.getElementById('instructor_loads_excel_file_input');
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

    // Modal reset on close
    $('#importInstructorLoadsModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $(this).find('.is-invalid').removeClass('is-invalid');
        $(this).find('.invalid-feedback').text('');

        // Reset upload zone
        const icon = uploadZone?.querySelector('i');
        const text = uploadZone?.querySelector('.form-text');
        if (icon) {
            icon.className = 'fas fa-file-excel text-success mb-2';
            icon.style.fontSize = '2rem';
        }
        if (text) {
            text.innerHTML = '<small>Supported formats: .xlsx, .xls, .csv (Max: 5MB)</small>';
        }
    });

    // Form validation
    document.querySelector('#importInstructorLoadsModal form').addEventListener('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        this.classList.add('was-validated');
    });
});
</script>
@endsection
