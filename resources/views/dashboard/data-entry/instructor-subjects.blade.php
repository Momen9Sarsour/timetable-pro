@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-user-graduate text-primary me-2"></i>
                        Instructor Subject Assignments
                    </h4>
                    <p class="text-muted mb-0">Manage subject assignments for instructors</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('data-entry.instructors.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-user-tie me-1"></i>
                        <span class="d-none d-sm-inline">Manage Instructors</span>
                    </a>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#importAssignmentsModal">
                        <i class="fas fa-file-excel me-1"></i>
                        <span class="d-none d-sm-inline">Import Assignments</span>
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
                <strong class="d-block mb-1">Skipped Entries Details:</strong>
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
                            Instructors & Subject Assignments
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $instructors->total() }} Instructors</span>
                    </div>
                </div>

                <div class="card-body pt-3">
                    @if($instructors->count() > 0)
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 text-center" style="width: 50px;">#</th>
                                        <th class="border-0" style="width: 25%;">Instructor</th>
                                        <th class="border-0" style="width: 20%;">Department</th>
                                        <th class="border-0" style="width: 45%;">Assigned Subjects</th>
                                        <th class="border-0 text-center" style="width: 10%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($instructors as $index => $instructor)
                                        <tr class="border-bottom">
                                            <td class="text-center text-muted">
                                                <small>{{ $instructors->firstItem() + $index }}</small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                                        {{ strtoupper(substr($instructor->instructor_name ?? optional($instructor->user)->name, 0, 2)) }}
                                                    </div>
                                                    <div>
                                                        <div class="fw-medium">{{ $instructor->instructor_name ?? optional($instructor->user)->name }}</div>
                                                        <small class="text-muted">{{ $instructor->instructor_no }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info bg-opacity-10 text-info">
                                                    {{ optional($instructor->department)->department_name ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="subjects-list">
                                                    @forelse ($instructor->subjects as $subject)
                                                        <span class="badge bg-light text-dark border me-1 mb-1" title="{{ $subject->subject_name }}">
                                                            {{ $subject->subject_no }} - {{ Str::limit($subject->subject_name, 20) }}
                                                        </span>
                                                    @empty
                                                        <span class="badge bg-secondary">No subjects assigned</span>
                                                    @endforelse
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('data-entry.instructor-subjects.edit', $instructor->id) }}"
                                                   class="btn btn-outline-primary btn-sm"
                                                   title="Edit Subject Assignments">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile/Tablet Cards -->
                        <div class="d-lg-none">
                            @foreach ($instructors as $index => $instructor)
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
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
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="{{ route('data-entry.instructor-subjects.edit', $instructor->id) }}"
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </a>
                                        </div>

                                        <div class="subjects-section">
                                            <h6 class="small text-muted mb-2">
                                                <i class="fas fa-book me-1"></i>Assigned Subjects:
                                            </h6>
                                            <div class="subjects-list">
                                                @forelse ($instructor->subjects as $subject)
                                                    <span class="badge bg-light text-dark border me-1 mb-1" title="{{ $subject->subject_name }}">
                                                        {{ $subject->subject_no }} - {{ Str::limit($subject->subject_name, 25) }}
                                                    </span>
                                                @empty
                                                    <span class="badge bg-secondary">No subjects assigned</span>
                                                @endforelse
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <span class="text-muted small">#{{ $instructors->firstItem() + $index }}</span>
                                            <span class="badge bg-primary bg-opacity-10 text-primary">
                                                {{ $instructor->subjects->count() }} {{ $instructor->subjects->count() == 1 ? 'subject' : 'subjects' }}
                                            </span>
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
                                <i class="fas fa-user-graduate text-muted opacity-50" style="font-size: 4rem;"></i>
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

<!-- Import Assignments Modal -->
<div class="modal fade" id="importAssignmentsModal" tabindex="-1" aria-labelledby="importAssignmentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="importAssignmentsModalLabel">
                    <i class="fas fa-file-excel me-2"></i>
                    Import Instructor-Subject Assignments
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.instructor-subject.importExcel') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-4">
                        <!-- File Upload Section -->
                        <div class="col-12">
                            <label for="assignments_excel_file_input" class="form-label fw-medium">
                                <i class="fas fa-cloud-upload-alt text-muted me-1"></i>
                                Select Excel File <span class="text-danger">*</span>
                            </label>
                            <div class="upload-zone border border-2 border-dashed rounded-3 p-4 text-center cursor-pointer">
                                <i class="fas fa-file-excel text-success mb-2" style="font-size: 2rem;"></i>
                                <input class="form-control @error('assignments_excel_file') is-invalid @enderror"
                                       type="file"
                                       id="assignments_excel_file_input"
                                       name="assignments_excel_file"
                                       accept=".xlsx,.xls,.csv"
                                       required>
                                @error('assignments_excel_file')
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
                                            <span>Required headers: <strong>instructor_name</strong> (or ID), <strong>subject_no</strong> (or ID/Name)</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                            <span>Multiple subjects can be separated by comma (,) in the subject column</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-sync-alt text-info me-2 mt-1 flex-shrink-0"></i>
                                            <span>Existing assignments will not be removed - this only adds new assignments</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <i class="fas fa-filter text-warning me-2 mt-1 flex-shrink-0"></i>
                                            <span>Rows with missing instructor or subject data will be skipped</span>
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
                                    <!-- Responsive Table with Horizontal Scroll -->
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0" style="min-width: 500px;">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th style="font-size: 0.75rem; white-space: nowrap;">instructor_name</th>
                                                    <th style="font-size: 0.75rem; white-space: nowrap;">subject_no</th>
                                                    <th style="font-size: 0.75rem; white-space: nowrap;">notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="small" style="white-space: nowrap;">Dr. Ahmad Mohammad</td>
                                                    <td class="small" style="white-space: nowrap;"><code>CS101, CS102</code></td>
                                                    <td class="small">Multiple subjects</td>
                                                </tr>
                                                <tr>
                                                    <td class="small" style="white-space: nowrap;">Dr. Fatima Ali</td>
                                                    <td class="small" style="white-space: nowrap;"><code>MATH201</code></td>
                                                    <td class="small">Single subject</td>
                                                </tr>
                                                <tr>
                                                    <td class="small" style="white-space: nowrap;">Prof. Sarah Johnson</td>
                                                    <td class="small" style="white-space: nowrap;"><code>ENG101, ENG102, ENG201</code></td>
                                                    <td class="small">Multiple subjects</td>
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

/* User Avatar */
.user-avatar {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border: 2px solid rgba(255, 255, 255, 0.2);
    transition: all 0.15s ease;
}

/* Subjects List */
.subjects-list {
    max-height: 100px;
    overflow-y: auto;
}

.subjects-list .badge {
    font-size: 0.75rem;
    font-weight: 500;
    word-break: break-word;
}

/* Subjects Section for Mobile */
.subjects-section {
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    padding-top: 1rem;
}

/* Badge Improvements */
.badge {
    font-weight: 500;
    letter-spacing: 0.025em;
}

/* Empty State */
.empty-state i {
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
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

    /* Table in modal - horizontal scroll */
    .table-responsive {
        -webkit-overflow-scrolling: touch;
    }

    .table-responsive table {
        font-size: 0.7rem;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
    }

    .subjects-list {
        max-height: 80px;
    }

    .subjects-list .badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        margin-bottom: 0.25rem;
    }

    .card-body {
        padding: 1rem;
    }

    .user-avatar {
        width: 35px !important;
        height: 35px !important;
        font-size: 0.75rem;
    }

    /* Enhanced mobile table scroll */
    .table-responsive {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
    }

    .table-responsive::-webkit-scrollbar {
        height: 6px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
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

/* Form Enhancements */
.form-control:focus,
.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload enhancement
    const fileInput = document.getElementById('assignments_excel_file_input');
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
    $('#importAssignmentsModal').on('hidden.bs.modal', function() {
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
    document.querySelector('#importAssignmentsModal form').addEventListener('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        this.classList.add('was-validated');
    });
});
</script>
@endsection
