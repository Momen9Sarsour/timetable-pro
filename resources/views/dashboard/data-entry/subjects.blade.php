@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-book text-primary me-2"></i>
                        Manage Subjects
                    </h4>
                    <p class="text-muted mb-0">Create and manage academic subjects with detailed specifications</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="fas fa-plus me-1"></i>
                        <span class="d-none d-sm-inline">Add New Subject</span>
                    </button>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#bulkUploadSubjectsModal">
                        <i class="fas fa-file-excel me-1"></i>
                        <span class="d-none d-sm-inline">Bulk Upload</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    @if (session('import_errors'))
        <div class="alert alert-danger d-flex align-items-start mb-4">
            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
            <div>
                <strong class="d-block mb-1">Import Validation Errors!</strong>
                <p class="mb-2 small">The following errors were found in the uploaded file:</p>
                <ul class="mb-0 small" style="max-height: 150px; overflow-y: auto;">
                    @foreach (session('import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if (session('skipped_details'))
        <div class="alert alert-warning d-flex align-items-start mb-4">
            <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
            <div>
                <strong class="d-block mb-1">Skipped Rows During Upload:</strong>
                <ul class="mb-0 small" style="max-height: 150px; overflow-y: auto;">
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
                            Subjects List
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $subjects->total() }} Total</span>
                    </div>
                </div>

                <div class="card-body pt-3">
                    @if($subjects->count() > 0)
                        <!-- Desktop Table - Compact Design -->
                        <div class="table-responsive d-none d-xl-block">
                            <table class="table table-hover align-middle mb-0 table-sm subjects-table">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 text-center" style="width: 40px;">#</th>
                                        <th class="border-0" style="width: 100px;">Code</th>
                                        <th class="border-0">Subject Name</th>
                                        <th class="border-0 text-center" style="width: 60px;">Load</th>
                                        <th class="border-0 text-center" style="width: 60px;">Load hours</th>
                                        {{-- <th class="border-0 text-center" style="width: 60px;">Theo</th>
                                        <th class="border-0 text-center" style="width: 60px;">Prac</th>
                                        <th class="border-0 text-center" style="width: 80px;">T.Cap</th>
                                        <th class="border-0 text-center" style="width: 80px;">P.Cap</th> --}}
                                        <th class="border-0" style="width: 100px;">Type</th>
                                        <th class="border-0" style="width: 100px;">Category</th>
                                        <th class="border-0" style="width: 100px;">Department</th>
                                        <th class="border-0 text-center" style="width: 100px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subjects as $index => $subject)
                                        <tr class="border-bottom">
                                            <td class="text-center text-muted">
                                                <small>{{ $subjects->firstItem() + $index }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark font-monospace">{{ $subject->subject_no }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-medium">{{ $subject->subject_name }}</div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info bg-opacity-10 text-info">{{ $subject->subject_load }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info bg-opacity-10 text-info">{{ $subject->subject_hours }}</span>
                                            </td>
                                            {{-- <td class="text-center">
                                                <small>{{ $subject->theoretical_hours }}h</small>
                                            </td>
                                            <td class="text-center">
                                                <small>{{ $subject->practical_hours }}h</small>
                                            </td>
                                            <td class="text-center">
                                                <small>{{ $subject->load_theoretical_section ?? '-' }}</small>
                                            </td>
                                            <td class="text-center">
                                                <small>{{ $subject->load_practical_section ?? '-' }}</small>
                                            </td> --}}
                                            <td>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary small">
                                                    {{ $subject->subjectType->subject_type_name ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary bg-opacity-10 text-primary small">
                                                    {{ $subject->subjectCategory->subject_category_name ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success bg-opacity-10 text-success small">
                                                    {{ $subject->department->department_name ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button class="btn btn-outline-primary btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editSubjectModal-{{ $subject->id }}"
                                                            title="Edit Subject">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteSubjectModal-{{ $subject->id }}"
                                                            title="Delete Subject">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Tablet Table - Medium Screen -->
                        <div class="table-responsive d-none d-lg-block d-xl-none">
                            <table class="table table-hover align-middle mb-0 table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0">#</th>
                                        <th class="border-0">Subject</th>
                                        <th class="border-0">Hours</th>
                                        <th class="border-0">Type/Category</th>
                                        <th class="border-0">Department</th>
                                        <th class="border-0">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subjects as $index => $subject)
                                        <tr class="border-bottom">
                                            <td><small>{{ $subjects->firstItem() + $index }}</small></td>
                                            <td>
                                                <div>
                                                    <span class="badge bg-light text-dark font-monospace">{{ $subject->subject_no }}</span>
                                                    <div class="fw-medium small">{{ $subject->subject_name }}</div>
                                                </div>
                                            </td>
                                            <td>
                                                <small>
                                                    <span class="badge bg-info bg-opacity-10 text-info">{{ $subject->subject_load }} cr</span><br>
                                                    <span class="badge bg-info bg-opacity-10 text-info">{{ $subject->subject_hours }} cr</span><br>
                                                    {{-- T:{{ $subject->theoretical_hours }}h P:{{ $subject->practical_hours }}h --}}
                                                </small>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary small">{{ $subject->subjectType->subject_type_name ?? 'N/A' }}</span>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary small">{{ $subject->subjectCategory->subject_category_name ?? 'N/A' }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-success bg-opacity-10 text-success small">{{ $subject->department->department_name ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editSubjectModal-{{ $subject->id }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteSubjectModal-{{ $subject->id }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="d-lg-none">
                            @foreach ($subjects as $index => $subject)
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <div class="subject-icon bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <i class="fas fa-book"></i>
                                                    </div>
                                                    <div>
                                                        <span class="badge bg-light text-dark font-monospace mb-1">{{ $subject->subject_no }}</span>
                                                        <h6 class="card-title mb-1">{{ $subject->subject_name }}</h6>
                                                    </div>
                                                </div>

                                                <div class="d-flex flex-wrap gap-1 mb-2">
                                                    <span class="badge bg-info bg-opacity-10 text-info">{{ $subject->subject_load }} credits</span>
                                                    <span class="badge bg-info bg-opacity-10 text-info">{{ $subject->subject_hours }} credits</span>
                                                    {{-- <span class="badge bg-warning bg-opacity-10 text-warning">T:{{ $subject->theoretical_hours }}h</span>
                                                    <span class="badge bg-success bg-opacity-10 text-success">P:{{ $subject->practical_hours }}h</span> --}}
                                                </div>

                                                <div class="d-flex flex-wrap gap-1">
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $subject->subjectType->subject_type_name ?? 'N/A' }}</span>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary">{{ $subject->subjectCategory->subject_category_name ?? 'N/A' }}</span>
                                                    <span class="badge bg-danger bg-opacity-10 text-danger">{{ $subject->department->department_name ?? 'N/A' }}</span>
                                                </div>
                                            </div>

                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editSubjectModal-{{ $subject->id }}">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteSubjectModal-{{ $subject->id }}">
                                                            <i class="fas fa-trash me-2"></i>Delete
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between text-muted small">
                                            <span>
                                                @if($subject->load_theoretical_section || $subject->load_practical_section)
                                                    Cap: T:{{ $subject->load_theoretical_section ?? '-' }} P:{{ $subject->load_practical_section ?? '-' }}
                                                @endif
                                            </span>
                                            <span class="text-muted">#{{ $subjects->firstItem() + $index }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($subjects->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $subjects->links('pagination::bootstrap-5') }}
                            </div>
                        @endif

                    @else
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-book text-muted opacity-50" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 text-muted">No Subjects Found</h5>
                                <p class="text-muted mb-4">Start by adding subjects to build your academic curriculum.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                                    <i class="fas fa-plus me-2"></i>Add First Subject
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals for each subject -->
    @foreach($subjects as $subject)
        @include('dashboard.data-entry.partials._subject_modals', [
            'subject' => $subject,
            'subjectTypes' => $subjectTypes,
            'subjectCategories' => $subjectCategories,
            'departments' => $departments,
        ])
    @endforeach

    <!-- Include Add Subject Modal -->
    @include('dashboard.data-entry.partials._subject_modals', [
        'subject' => null,
        'subjectTypes' => $subjectTypes,
        'subjectCategories' => $subjectCategories,
        'departments' => $departments,
    ])
</div>

<style>
/* Compact table styling */
.subjects-table {
    font-size: 0.8125rem;
}

.subjects-table th {
    font-size: 0.75rem;
    padding: 0.5rem 0.25rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.subjects-table td {
    padding: 0.5rem 0.25rem;
    vertical-align: middle;
}

.subjects-table .badge {
    font-size: 0.65rem;
    padding: 0.2rem 0.4rem;
}

.subjects-table .btn-sm {
    padding: 0.2rem 0.4rem;
    font-size: 0.7rem;
}

/* Subject icon styling */
.subject-icon {
    transition: all 0.15s ease;
}

.subject-icon:hover {
    transform: scale(1.05);
}

/* Responsive badge sizing */
@media (max-width: 1199px) {
    .badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
}

@media (max-width: 991px) {
    .table-sm th,
    .table-sm td {
        padding: 0.4rem 0.2rem;
        font-size: 0.8rem;
    }
}

/* Custom scrollbar for table */
.table-responsive::-webkit-scrollbar {
    height: 6px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 3px;
}
</style>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
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

    // Auto-calculate total hours validation
    document.querySelectorAll('form').forEach(form => {
        const theoreticalInput = form.querySelector('input[name="theoretical_hours"]');
        const practicalInput = form.querySelector('input[name="practical_hours"]');
        const loadInput = form.querySelector('input[name="subject_load"]');

        if (theoreticalInput && practicalInput && loadInput) {
            function validateHours() {
                const theoretical = parseInt(theoreticalInput.value) || 0;
                const practical = parseInt(practicalInput.value) || 0;
                const load = parseInt(loadInput.value) || 0;

                // Basic validation: total hours should make sense with credit load
                if (theoretical + practical === 0 && load > 0) {
                    theoreticalInput.setCustomValidity('At least one type of hours must be greater than 0');
                    practicalInput.setCustomValidity('At least one type of hours must be greater than 0');
                } else {
                    theoreticalInput.setCustomValidity('');
                    practicalInput.setCustomValidity('');
                }
            }

            theoreticalInput.addEventListener('input', validateHours);
            practicalInput.addEventListener('input', validateHours);
            loadInput.addEventListener('input', validateHours);
        }
    });

    // Auto-format subject codes
    const subjectCodeInputs = document.querySelectorAll('input[name="subject_no"]');
    subjectCodeInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Convert to uppercase and remove spaces
            this.value = this.value.toUpperCase().replace(/\s+/g, '');
        });
    });
});
</script>
@endpush
