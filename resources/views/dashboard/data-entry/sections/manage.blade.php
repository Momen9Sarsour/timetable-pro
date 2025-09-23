@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header with Context Info -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-3">
                        <div class="flex-grow-1">
                            <h4 class="page-title mb-2">
                                <i class="fas fa-users-cog text-primary me-2"></i>
                                Manage Sections for Subject
                            </h4>
                            <div class="subject-info">
                                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2 mb-2">
                                    <span class="badge bg-primary bg-opacity-20 text-light">{{ optional($planSubject->plan)->plan_no }}</span>
                                    <span class="text-muted d-none d-sm-inline">•</span>
                                    <h6 class="mb-0 text-dark">{{ optional($planSubject->plan)->plan_name }}</h6>
                                </div>
                                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2">
                                    <span class="badge bg-light text-dark font-monospace">{{ optional($planSubject->subject)->subject_no }}</span>
                                    <span class="fw-medium">{{ optional($planSubject->subject)->subject_name }}</span>
                                    <span class="badge bg-secondary bg-opacity-20 text-light">{{ optional(optional($planSubject->subject)->subjectCategory)->subject_category_name }}</span>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('data-entry.sections.index', $request->query()) }}"
                           class="btn btn-outline-secondary btn-sm w-100 w-md-auto">
                            <i class="fas fa-arrow-left me-1"></i>
                            Back to All Sections
                        </a>
                    </div>

                    <hr class="my-3">

                    <!-- Statistics Row - Responsive -->
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <div class="text-info fw-bold fs-5">{{ $academicYear }}</div>
                                <small class="text-muted">Academic Year</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <div class="text-success fw-bold fs-5">
                                    {{ $semesterOfSections == 1 ? 'First' : ($semesterOfSections == 2 ? 'Second' : 'Summer') }}
                                </div>
                                <small class="text-muted">Term</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <div class="text-warning fw-bold fs-5">{{ $branch ?? 'Default' }}</div>
                                <small class="text-muted">Branch</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                @if ($expectedCount)
                                    <div class="text-primary fw-bold fs-5">{{ $expectedCount->male_count + $expectedCount->female_count }}</div>
                                    <small class="text-muted">Expected Students</small>
                                @else
                                    <div class="text-danger fw-bold fs-5">N/A</div>
                                    <small class="text-muted">Expected Students</small>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if (!$expectedCount)
                        <div class="alert alert-warning d-flex align-items-start mt-3 mb-0" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                Expected student count not found.
                                <a href="{{ route('data-entry.plan-expected-counts.index') }}" class="alert-link fw-medium">Add it here.</a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    <!-- Regenerate Sections Action -->
    @if ($expectedCount)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-body p-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-1 text-warning">
                                    <i class="fas fa-cogs me-2"></i>Regenerate Sections
                                </h6>
                                <p class="text-muted small mb-0">This will delete existing sections for this subject and recreate them based on expected counts.</p>
                            </div>
                            <form action="{{ route('data-entry.sections.generateForSubject') }}" method="POST" class="w-100 w-md-auto">
                                @csrf
                                <input type="hidden" name="plan_subject_id" value="{{ $planSubject->id }}">
                                <input type="hidden" name="expected_count_id" value="{{ $expectedCount->id }}">
                                <button type="submit"
                                        class="btn btn-warning w-100"
                                        onclick="return confirm('ATTENTION: This will DELETE existing sections for THIS SUBJECT ONLY and REGENERATE them. Manual adjustments will be lost. Are you sure?');">
                                    <i class="fas fa-sync-alt me-1"></i>Regenerate Sections
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @php
        $subject = $planSubject->subject;
        if ($subject && $subject->subjectCategory) {
            $subjectCategoryName = strtolower($subject->subjectCategory->subject_category_name);
            $theorySections = $currentSections->where('activity_type', 'Theory');
            $practicalSections = $currentSections->where('activity_type', 'Practical');
        } else {
            $subjectCategoryName = '';
            $theorySections = collect();
            $practicalSections = collect();
        }
    @endphp

    <!-- Theory Sections -->
    @if (($subject->subject_hours ?? 0) > 0 && Str::contains($subjectCategoryName, ['theory', 'نظري', 'combined', 'مشترك']))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary bg-opacity-10 border-0 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 py-3">
                        <h6 class="mb-0 text-primary">
                            <i class="fas fa-chalkboard me-2"></i>Theory Sections
                            <span class="badge bg-primary bg-opacity-20 text-light ms-2">{{ $theorySections->count() }} sections</span>
                        </h6>
                        <button class="btn btn-primary btn-sm w-100 w-md-auto open-add-section-modal"
                                data-bs-toggle="modal"
                                data-bs-target="#addSectionModal"
                                data-plan-subject-id="{{ $planSubject->id }}"
                                data-activity-type="Theory"
                                data-subject-name-modal="{{ $subject->subject_name }} (Theory)"
                                data-academic-year="{{ $academicYear }}"
                                data-semester="{{ $semesterOfSections }}"
                                data-branch="{{ $branch ?? '' }}">
                            <i class="fas fa-plus me-1"></i>Add Theory Section
                        </button>
                    </div>
                    <div class="card-body p-0">
                        @if ($theorySections->isNotEmpty())
                            <!-- Desktop Table -->
                            <div class="table-responsive d-none d-md-block">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0 text-center" style="width: 50px;">#</th>
                                            <th class="border-0 text-center">Section No.</th>
                                            <th class="border-0 text-center">Gender</th>
                                            <th class="border-0">Branch</th>
                                            <th class="border-0 text-center">Students</th>
                                            <th class="border-0 text-center" style="width: 120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($theorySections as $index => $section)
                                            <tr class="border-bottom">
                                                <td class="text-center text-muted">
                                                    <small>{{ $index + 1 }}</small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary bg-opacity-20 text-light fw-bold">{{ $section->section_number }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary bg-opacity-20 text-light">{{ $section->section_gender }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-muted">{{ $section->branch ?? '-' }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-warning bg-opacity-20 text-light">{{ $section->student_count }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button class="btn btn-outline-primary btn-sm open-edit-section-modal"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editSectionModal"
                                                                data-form-action="{{ route('data-entry.sections.update', $section->id) }}"
                                                                data-section-number="{{ $section->section_number }}"
                                                                data-student-count="{{ $section->student_count }}"
                                                                data-section-gender="{{ $section->section_gender }}"
                                                                data-branch="{{ $section->branch ?? '' }}"
                                                                data-activity-type="{{ $section->activity_type }}"
                                                                data-context-info="Sec #{{ $section->section_number }} for {{ optional($planSubject->subject)->subject_no }} (Theory)"
                                                                title="Edit Section">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm open-delete-section-modal"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteSectionModal"
                                                                data-form-action="{{ route('data-entry.sections.destroy', $section->id) }}"
                                                                data-section-info="Section #{{ $section->section_number }} (Theory) for {{ optional($planSubject->subject)->subject_no }}"
                                                                title="Delete Section">
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
                            <div class="d-md-none">
                                @foreach ($theorySections as $index => $section)
                                    <div class="card-body border-bottom">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="badge bg-primary bg-opacity-20 text-light fw-bold">Section {{ $section->section_number }}</span>
                                                    <span class="badge bg-secondary bg-opacity-20 text-light small">{{ $section->section_gender }}</span>
                                                </div>
                                                <div class="text-muted small">
                                                    Branch: {{ $section->branch ?? 'Default' }}
                                                </div>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item open-edit-section-modal"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editSectionModal"
                                                                data-form-action="{{ route('data-entry.sections.update', $section->id) }}"
                                                                data-section-number="{{ $section->section_number }}"
                                                                data-student-count="{{ $section->student_count }}"
                                                                data-section-gender="{{ $section->section_gender }}"
                                                                data-branch="{{ $section->branch ?? '' }}"
                                                                data-context-info="Sec #{{ $section->section_number }} for {{ optional($planSubject->subject)->subject_no }} (Theory)">
                                                            <i class="fas fa-edit me-2"></i>Edit Section
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger open-delete-section-modal"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteSectionModal"
                                                                data-form-action="{{ route('data-entry.sections.destroy', $section->id) }}"
                                                                data-section-info="Section #{{ $section->section_number }} (Theory) for {{ optional($planSubject->subject)->subject_no }}">
                                                            <i class="fas fa-trash me-2"></i>Delete Section
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <div class="fw-bold text-warning fs-5">{{ $section->student_count }}</div>
                                            <small class="text-muted">Students</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-chalkboard text-muted opacity-50 mb-3" style="font-size: 3rem;"></i>
                                <h6 class="text-muted">No Theory Sections</h6>
                                <p class="text-muted mb-3">No theory sections have been created yet.</p>
                                <button class="btn btn-primary btn-sm open-add-section-modal"
                                        data-bs-toggle="modal"
                                        data-bs-target="#addSectionModal"
                                        data-plan-subject-id="{{ $planSubject->id }}"
                                        data-activity-type="Theory"
                                        data-subject-name-modal="{{ $subject->subject_name }} (Theory)"
                                        data-academic-year="{{ $academicYear }}"
                                        data-semester="{{ $semesterOfSections }}"
                                        data-branch="{{ $branch ?? '' }}">
                                    <i class="fas fa-plus me-1"></i>Add First Theory Section
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Practical Sections -->
    @if (($subject->subject_hours ?? 0) > 0 && Str::contains($subjectCategoryName, ['practical', 'عملي', 'combined', 'مشترك']))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success bg-opacity-10 border-0 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 py-3">
                        <h6 class="mb-0 text-success">
                            <i class="fas fa-flask me-2"></i>Practical Sections
                            <span class="badge bg-success bg-opacity-20 text-light ms-2">{{ $practicalSections->count() }} sections</span>
                        </h6>
                        <button class="btn btn-success btn-sm w-100 w-md-auto open-add-section-modal"
                                data-bs-toggle="modal"
                                data-bs-target="#addSectionModal"
                                data-plan-subject-id="{{ $planSubject->id }}"
                                data-activity-type="Practical"
                                data-subject-name-modal="{{ $subject->subject_name }} (Practical)"
                                data-academic-year="{{ $academicYear }}"
                                data-semester="{{ $semesterOfSections }}"
                                data-branch="{{ $branch ?? '' }}">
                            <i class="fas fa-plus me-1"></i>Add Practical Section
                        </button>
                    </div>
                    <div class="card-body p-0">
                        @if ($practicalSections->isNotEmpty())
                            <!-- Desktop Table -->
                            <div class="table-responsive d-none d-md-block">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0 text-center" style="width: 50px;">#</th>
                                            <th class="border-0 text-center">Section No.</th>
                                            <th class="border-0 text-center">Gender</th>
                                            <th class="border-0">Branch</th>
                                            <th class="border-0 text-center">Students</th>
                                            <th class="border-0 text-center" style="width: 120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($practicalSections as $index => $section)
                                            <tr class="border-bottom">
                                                <td class="text-center text-muted">
                                                    <small>{{ $index + 1 }}</small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success bg-opacity-20 text-light fw-bold">{{ $section->section_number }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary bg-opacity-20 text-light">{{ $section->section_gender }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-muted">{{ $section->branch ?? '-' }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-warning bg-opacity-20 text-light">{{ $section->student_count }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button class="btn btn-outline-primary btn-sm open-edit-section-modal"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editSectionModal"
                                                                data-form-action="{{ route('data-entry.sections.update', $section->id) }}"
                                                                data-section-number="{{ $section->section_number }}"
                                                                data-student-count="{{ $section->student_count }}"
                                                                data-section-gender="{{ $section->section_gender }}"
                                                                data-branch="{{ $section->branch ?? '' }}"
                                                                data-activity-type="{{ $section->activity_type }}"
                                                                data-context-info="Sec #{{ $section->section_number }} for {{ optional($planSubject->subject)->subject_no }} (Practical)"
                                                                title="Edit Section">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm open-delete-section-modal"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteSectionModal"
                                                                data-form-action="{{ route('data-entry.sections.destroy', $section->id) }}"
                                                                data-section-info="Section #{{ $section->section_number }} (Practical) for {{ optional($planSubject->subject)->subject_no }}"
                                                                title="Delete Section">
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
                            <div class="d-md-none">
                                @foreach ($practicalSections as $index => $section)
                                    <div class="card-body border-bottom">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="badge bg-success bg-opacity-20 text-light fw-bold">Section {{ $section->section_number }}</span>
                                                    <span class="badge bg-secondary bg-opacity-20 text-light small">{{ $section->section_gender }}</span>
                                                </div>
                                                <div class="text-muted small">
                                                    Branch: {{ $section->branch ?? 'Default' }}
                                                </div>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item open-edit-section-modal"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editSectionModal"
                                                                data-form-action="{{ route('data-entry.sections.update', $section->id) }}"
                                                                data-section-number="{{ $section->section_number }}"
                                                                data-student-count="{{ $section->student_count }}"
                                                                data-section-gender="{{ $section->section_gender }}"
                                                                data-branch="{{ $section->branch ?? '' }}"
                                                                data-context-info="Sec #{{ $section->section_number }} for {{ optional($planSubject->subject)->subject_no }} (Practical)">
                                                            <i class="fas fa-edit me-2"></i>Edit Section
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger open-delete-section-modal"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteSectionModal"
                                                                data-form-action="{{ route('data-entry.sections.destroy', $section->id) }}"
                                                                data-section-info="Section #{{ $section->section_number }} (Practical) for {{ optional($planSubject->subject)->subject_no }}">
                                                            <i class="fas fa-trash me-2"></i>Delete Section
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <div class="fw-bold text-warning fs-5">{{ $section->student_count }}</div>
                                            <small class="text-muted">Students</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-flask text-muted opacity-50 mb-3" style="font-size: 3rem;"></i>
                                <h6 class="text-muted">No Practical Sections</h6>
                                <p class="text-muted mb-3">No practical sections have been created yet.</p>
                                <button class="btn btn-success btn-sm open-add-section-modal"
                                        data-bs-toggle="modal"
                                        data-bs-target="#addSectionModal"
                                        data-plan-subject-id="{{ $planSubject->id }}"
                                        data-activity-type="Practical"
                                        data-subject-name-modal="{{ $subject->subject_name }} (Practical)"
                                        data-academic-year="{{ $academicYear }}"
                                        data-semester="{{ $semesterOfSections }}"
                                        data-branch="{{ $branch ?? '' }}">
                                    <i class="fas fa-plus me-1"></i>Add First Practical Section
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Include Modals -->
    @include('dashboard.data-entry.sections.partials._sections_modals')
</div>

<style>
/* Subject info styling */
.subject-info {
    background: rgba(255, 255, 255, 0.7);
    padding: 1rem;
    border-radius: 0.5rem;
    border: 1px solid rgba(59, 130, 246, 0.1);
}

/* Card enhancements */
.card {
    transition: all 0.15s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
}

/* Badge improvements */
.badge {
    font-weight: 500;
}

/* Button group enhancements */
.btn-group .btn {
    transition: all 0.15s ease;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
}

/* Responsive improvements */
@media (max-width: 768px) {
    .subject-info {
        padding: 0.75rem;
    }

    .page-title {
        font-size: 1.25rem;
    }

    .card-header {
        padding: 1rem !important;
    }

    .card-body {
        padding: 1rem !important;
    }

    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    /* Mobile specific adjustments */
    .subject-info .d-flex {
        flex-direction: column;
        align-items: flex-start !important;
    }

    .subject-info .badge {
        font-size: 0.75rem;
    }

    /* Statistics row mobile layout */
    .statistics-row .col-6 {
        margin-bottom: 1rem;
    }
}

@media (max-width: 576px) {
    .card-header {
        flex-direction: column !important;
        gap: 0.75rem !important;
        align-items: flex-start !important;
    }

    .card-header .btn {
        width: 100% !important;
    }

    .card-body.border-bottom {
        padding: 0.75rem !important;
    }

    .dropdown-menu {
        min-width: 150px;
    }

    .dropdown-item {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }

    /* Page title adjustments */
    .page-title {
        font-size: 1.1rem;
    }

    .page-title i {
        font-size: 1rem;
    }

    /* Form responsive */
    .w-100.w-md-auto {
        width: 100% !important;
    }

    /* Alert responsive */
    .alert {
        font-size: 0.875rem;
    }

    .alert i {
        font-size: 0.875rem;
    }
}

/* Dark mode support */
body.dark-mode .subject-info {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
}

/* Empty state styling */
.empty-state i {
    opacity: 0.3;
}

/* Enhanced button styling */
.btn {
    transition: all 0.15s ease;
}

.btn:hover:not([disabled]) {
    transform: translateY(-1px);
}

/* Utility classes for responsive width */
@media (min-width: 768px) {
    .w-md-auto {
        width: auto !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Section Management Page: Scripts Ready.');

    const addModal = document.getElementById('addSectionModal');
    const editModal = document.getElementById('editSectionModal');
    const deleteModal = document.getElementById('deleteSectionModal');

    // Add Modal Handler
    document.addEventListener('click', function(e) {
        if (e.target.closest('.open-add-section-modal')) {
            const button = e.target.closest('.open-add-section-modal');

            addModal.querySelector('.modal-title').textContent = 'Add ' + button.dataset.activityType + ' Section for: ' + button.dataset.subjectNameModal;
            addModal.querySelector('form').action = "{{ route('data-entry.sections.store') }}";

            // Fill hidden fields
            addModal.querySelector('input[name="plan_subject_id"]').value = button.dataset.planSubjectId;
            addModal.querySelector('input[name="activity_type"]').value = button.dataset.activityType;
            addModal.querySelector('input[name="academic_year"]').value = button.dataset.academicYear;
            addModal.querySelector('input[name="semester"]').value = button.dataset.semester;
            addModal.querySelector('input[name="branch"]').value = button.dataset.branch || '';

            // Reset form fields
            addModal.querySelector('#add_section_number').value = '1';
            addModal.querySelector('#add_student_count').value = '0';
            addModal.querySelector('#add_section_gender').value = 'Mixed';
            addModal.querySelector('#add_branch_display').value = button.dataset.branch || 'Default';

            // Clear validation errors
            addModal.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            addModal.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
            addModal.querySelectorAll('.validation-errors').forEach(el => el.remove());
        }
    });

    // Edit Modal Handler
    document.addEventListener('click', function(e) {
        if (e.target.closest('.open-edit-section-modal')) {
            const button = e.target.closest('.open-edit-section-modal');

            editModal.querySelector('.modal-title').textContent = 'Edit Section (' + button.dataset.contextInfo + ')';
            editModal.querySelector('form').action = button.dataset.formAction;
            editModal.querySelector('#edit_context_info_text span').textContent = button.dataset.contextInfo;

            // Fill form fields
            editModal.querySelector('#edit_section_number').value = button.dataset.sectionNumber;
            editModal.querySelector('#edit_student_count').value = button.dataset.studentCount;
            editModal.querySelector('#edit_section_gender').value = button.dataset.sectionGender;
            editModal.querySelector('#edit_branch_display').value = button.dataset.branch || 'Default';

            // Clear validation errors
            editModal.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            editModal.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
            editModal.querySelectorAll('.validation-errors').forEach(el => el.remove());
        }
    });

    // Delete Modal Handler
    document.addEventListener('click', function(e) {
        if (e.target.closest('.open-delete-section-modal')) {
            const button = e.target.closest('.open-delete-section-modal');

            deleteModal.querySelector('#delete_section_info_text').textContent = button.dataset.sectionInfo;
            deleteModal.querySelector('form').action = button.dataset.formAction;
        }
    });

    // Reopen modals on validation errors
    @if ($errors->hasBag('addSectionModal'))
        console.log("Reopening Add Modal due to validation errors.");
        const addModalInstance = new bootstrap.Modal(addModal);
        addModalInstance.show();
    @endif

    @if (session('editSectionId') && ($errors->hasBag('editSectionModal_' . session('editSectionId')) || $errors->any()))
        console.log("Reopening Edit Modal due to validation errors.");
        const editModalInstance = new bootstrap.Modal(editModal);
        editModalInstance.show();

        // Restore form data
        const sectionId = "{{ session('editSectionId') }}";
        const editButton = document.querySelector(`.open-edit-section-modal[data-form-action*="/${sectionId}"]`);

        if (editButton) {
            editModal.querySelector('.modal-title').textContent = 'Edit Section (' + editButton.dataset.contextInfo + ')';
            editModal.querySelector('form').action = editButton.dataset.formAction;
            editModal.querySelector('#edit_context_info_text span').textContent = editButton.dataset.contextInfo;

            editModal.querySelector('#edit_section_number').value = "{{ old('section_number', session('sectionForModal')->section_number ?? '') }}";
            editModal.querySelector('#edit_student_count').value = "{{ old('student_count', session('sectionForModal')->student_count ?? '') }}";
            editModal.querySelector('#edit_section_gender').value = "{{ old('section_gender', session('sectionForModal')->section_gender ?? '') }}";
            editModal.querySelector('#edit_branch_display').value = editButton.dataset.branch || 'Default';
        }
    @endif

    // Enhanced responsive behavior
    function handleResponsiveChanges() {
        const isMobile = window.innerWidth < 768;

        // Adjust modal behavior for mobile
        document.querySelectorAll('.modal-dialog').forEach(modal => {
            if (isMobile) {
                modal.style.margin = '0.5rem';
                modal.style.maxWidth = 'calc(100% - 1rem)';
            } else {
                modal.style.margin = '';
                modal.style.maxWidth = '';
            }
        });
    }

    // Run on load and resize
    handleResponsiveChanges();
    window.addEventListener('resize', handleResponsiveChanges);

    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
            if (bootstrap.Alert) {
                const alertInstance = bootstrap.Alert.getOrCreateInstance(alert);
                alertInstance.close();
            }
        });
    }, 5000);

    console.log('✅ Section Management initialized successfully with full responsive support');
});
</script>
@endsection
