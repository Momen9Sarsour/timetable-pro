@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Back Button -->
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('data-entry.plan-expected-counts.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back to Expected Counts
            </a>
        </div>
    </div>

    <!-- Context Information Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body p-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                        <div class="flex-grow-1">
                            <h4 class="page-title mb-2">
                                <i class="fas fa-users-class text-primary me-2"></i>
                                Manage Sections for Context
                            </h4>
                            <p class="text-muted mb-2">
                                Modifying sections for Plan:
                                <strong class="text-primary">{{ optional($expectedCount->plan)->plan_no }}</strong>
                            </p>
                        </div>
                        <form action="{{ route('data-entry.sections.generateForContext', $expectedCount->id) }}"
                              method="POST"
                              class="d-inline">
                            @csrf
                            <button type="submit"
                                    class="btn btn-warning btn-sm"
                                    onclick="return confirm('ATTENTION: This will DELETE existing sections for ALL subjects in this context and REGENERATE them. Manual adjustments will be lost. Are you sure?');">
                                <i class="fas fa-cogs me-1"></i>
                                <span class="d-none d-sm-inline">Regenerate All Sections</span>
                            </button>
                        </form>
                    </div>

                    <!-- Context Details -->
                    <div class="context-details mt-3 pt-3 border-top">
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-3">
                                <div class="detail-item">
                                    <label class="small text-muted">Plan Name:</label>
                                    <div class="fw-medium">{{ optional($expectedCount->plan)->plan_name }}</div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="detail-item">
                                    <label class="small text-muted">Department:</label>
                                    <div class="fw-medium">{{ optional(optional($expectedCount->plan)->department)->department_name }}</div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="detail-item">
                                    <label class="small text-muted">Academic Year:</label>
                                    <div class="fw-medium">{{ $expectedCount->academic_year }}</div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="detail-item">
                                    <label class="small text-muted">Level & Semester:</label>
                                    <div class="fw-medium">{{ $expectedCount->plan_level }} - {{ $expectedCount->plan_semester }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <label class="small text-muted">Branch:</label>
                                    <div class="fw-medium">{{ $expectedCount->branch ?? 'Default' }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <label class="small text-muted">Total Expected Students:</label>
                                    <div class="fw-bold text-primary fs-5">
                                        {{ $expectedCount->male_count + $expectedCount->female_count }}
                                        <small class="text-muted fw-normal">({{ $expectedCount->male_count }}M, {{ $expectedCount->female_count }}F)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    <!-- Subjects and Sections -->
    @if (!isset($planSubjectsForContext) || $planSubjectsForContext->isEmpty())
        <div class="row">
            <div class="col-12">
                <div class="empty-state text-center py-5">
                    <i class="fas fa-book text-muted opacity-50" style="font-size: 4rem;"></i>
                    <h5 class="mt-3 text-muted">No Subjects Found</h5>
                    <p class="text-muted">No subjects found in this plan/level/semester.</p>
                </div>
            </div>
        </div>
    @else
        <div class="subjects-container">
            @foreach ($planSubjectsForContext as $ps)
                @php
                    $subject = $ps->subject;
                    if (!$subject || !$subject->subjectCategory) {
                        continue;
                    }
                    $subjectCategoryName = strtolower($subject->subjectCategory->subject_category_name);
                    $sectionsForThisSubject = $currentSectionsBySubjectAndActivity[$subject->id] ?? collect();
                    $theorySections = $sectionsForThisSubject->get('Theory', collect());
                    $practicalSections = $sectionsForThisSubject->get('Practical', collect());
                @endphp

                <div class="subject-section mb-4">
                    <!-- Subject Header -->
                    <div class="subject-header mb-3">
                        <h5 class="mb-0 pb-2 border-bottom d-flex align-items-center">
                            <i class="fas fa-book text-primary me-2"></i>
                            <span class="subject-code badge bg-primary me-2">{{ $subject->subject_no }}</span>
                            <span class="subject-name">{{ $subject->subject_name }}</span>
                            <span class="badge bg-secondary ms-auto">{{ $subjectCategoryName }}</span>
                        </h5>
                    </div>

                    <div class="sections-content">
                        <!-- Theory Sections -->
                        {{-- @if (($subject->theoretical_hours ?? 0) > 0 && Str::contains($subjectCategoryName, ['theory', 'نظري', 'combined', 'مشترك'])) --}}
                        @if ($subject->subject_hours > 0 && Str::contains($subjectCategoryName, ['theory', 'نظري', 'combined', 'مشترك']))
                            <div class="section-type-card mb-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-info bg-opacity-10 border-0 d-flex justify-content-between align-items-center py-3">
                                        <h6 class="mb-0 text-info d-flex align-items-center">
                                            <i class="fas fa-chalkboard me-2"></i>
                                            Theory Sections
                                        </h6>
                                        <button class="btn btn-outline-success btn-sm open-add-section-modal"
                                                data-bs-toggle="modal"
                                                data-bs-target="#addSectionModal"
                                                data-plan-subject-id="{{ $ps->id }}"
                                                data-activity-type="Theory"
                                                data-subject-name-modal="{{ $subject->subject_name }} (Theory)"
                                                data-expected-count-id="{{ $expectedCount->id }}"
                                                data-academic-year="{{ $expectedCount->academic_year }}"
                                                data-semester="{{ $expectedCount->plan_semester }}"
                                                data-branch="{{ $expectedCount->branch ?? '' }}"
                                                data-form-action="{{ route('data-entry.sections.storeInContext', $expectedCount->id) }}">
                                            <i class="fas fa-plus me-1"></i>
                                            <span class="d-none d-sm-inline">Add Theory</span>
                                        </button>
                                    </div>
                                    <div class="card-body p-0">
                                        @if ($theorySections->isNotEmpty())
                                            <!-- Desktop Table -->
                                            <div class="table-responsive d-none d-md-block">
                                                <table class="table table-hover mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th class="border-0 text-center" style="width: 50px;">#</th>
                                                            <th class="border-0">Section No.</th>
                                                            <th class="border-0">Instructor</th>
                                                            <th class="border-0">Gender</th>
                                                            <th class="border-0">Branch</th>
                                                            <th class="border-0">Students</th>
                                                            <th class="border-0 text-center" style="width: 120px;">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($theorySections as $index => $section)
                                                            <tr>
                                                                <td class="text-center text-muted small">{{ $index + 1 }}</td>
                                                                <td>
                                                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                                                        {{ $section->section_number }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    @if($section->instructor)
                                                                        <span class="badge bg-info bg-opacity-10 text-info">
                                                                            {{ $section->instructor->instructor_name }}
                                                                        </span>
                                                                    @else
                                                                        <span class="text-muted small">Not assigned</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <span class="badge bg-{{ $section->section_gender == 'Mixed' ? 'info' : ($section->section_gender == 'Male' ? 'primary' : 'danger') }} bg-opacity-10 text-{{ $section->section_gender == 'Mixed' ? 'info' : ($section->section_gender == 'Male' ? 'primary' : 'danger') }}">
                                                                        {{ $section->section_gender }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $section->branch ?? '-' }}</td>
                                                                <td>
                                                                    <span class="badge bg-success bg-opacity-10 text-success">
                                                                        {{ $section->student_count }} students
                                                                    </span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <div class="btn-group btn-group-sm">
                                                                        <button class="btn btn-outline-primary btn-sm open-edit-section-modal"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#editSectionModal"
                                                                                data-section-id="{{ $section->id }}"
                                                                                data-section-number="{{ $section->section_number }}"
                                                                                data-student-count="{{ $section->student_count }}"
                                                                                data-section-gender="{{ $section->section_gender }}"
                                                                                data-branch="{{ $section->branch }}"
                                                                                data-activity-type="{{ $section->activity_type }}"
                                                                                data-form-action="{{ route('data-entry.sections.updateInContext', $section->id) }}"
                                                                                data-context-info="Sec #{{ $section->section_number }} for {{ $subject->subject_no }} (Theory)"
                                                                                title="Edit Section">
                                                                            <i class="fas fa-edit"></i>
                                                                        </button>
                                                                        <button class="btn btn-outline-danger btn-sm open-delete-section-modal"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#deleteSectionModal"
                                                                                data-form-action="{{ route('data-entry.sections.destroyInContext', $section->id) }}"
                                                                                data-section-info="Section #{{ $section->section_number }} (Theory) for {{ $subject->subject_no }}"
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
                                            <div class="d-md-none p-3">
                                                @foreach ($theorySections as $index => $section)
                                                    <div class="section-card mb-2 p-3 border rounded">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div class="section-info flex-grow-1">
                                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                                                        Section {{ $section->section_number }}
                                                                    </span>
                                                                    <span class="badge bg-{{ $section->section_gender == 'Mixed' ? 'info' : ($section->section_gender == 'Male' ? 'primary' : 'danger') }} bg-opacity-10 text-{{ $section->section_gender == 'Mixed' ? 'info' : ($section->section_gender == 'Male' ? 'primary' : 'danger') }}">
                                                                        {{ $section->section_gender }}
                                                                    </span>
                                                                </div>
                                                                <div class="section-details small text-muted">
                                                                    <div><i class="fas fa-users me-1"></i>{{ $section->student_count }} students</div>
                                                                    <div><i class="fas fa-map-marker-alt me-1"></i>{{ $section->branch ?? 'Default' }}</div>
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
                                                                                data-section-id="{{ $section->id }}"
                                                                                data-section-number="{{ $section->section_number }}"
                                                                                data-student-count="{{ $section->student_count }}"
                                                                                data-section-gender="{{ $section->section_gender }}"
                                                                                data-branch="{{ $section->branch }}"
                                                                                data-activity-type="{{ $section->activity_type }}"
                                                                                data-form-action="{{ route('data-entry.sections.updateInContext', $section->id) }}"
                                                                                data-context-info="Sec #{{ $section->section_number }} for {{ $subject->subject_no }} (Theory)">
                                                                            <i class="fas fa-edit me-2"></i>Edit
                                                                        </button>
                                                                    </li>
                                                                    <li>
                                                                        <button class="dropdown-item text-danger open-delete-section-modal"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#deleteSectionModal"
                                                                                data-form-action="{{ route('data-entry.sections.destroyInContext', $section->id) }}"
                                                                                data-section-info="Section #{{ $section->section_number }} (Theory) for {{ $subject->subject_no }}">
                                                                            <i class="fas fa-trash me-2"></i>Delete
                                                                        </button>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="empty-sections text-center py-4">
                                                <i class="fas fa-chalkboard text-muted opacity-50" style="font-size: 2rem;"></i>
                                                <p class="text-muted mb-0 mt-2">No theory sections defined.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Practical Sections -->
                        {{-- @if (($subject->practical_hours ?? 0) > 0 && Str::contains($subjectCategoryName, ['practical', 'عملي', 'combined', 'مشترك'])) --}}
                        @if ($subject->subject_hours > 0 && Str::contains($subjectCategoryName, ['practical', 'عملي', 'combined', 'مشترك']))
                            <div class="section-type-card mb-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-success bg-opacity-10 border-0 d-flex justify-content-between align-items-center py-3">
                                        <h6 class="mb-0 text-success d-flex align-items-center">
                                            <i class="fas fa-flask me-2"></i>
                                            Practical Sections
                                        </h6>
                                        <button class="btn btn-outline-success btn-sm open-add-section-modal"
                                                data-bs-toggle="modal"
                                                data-bs-target="#addSectionModal"
                                                data-plan-subject-id="{{ $ps->id }}"
                                                data-activity-type="Practical"
                                                data-expected-count-id="{{ $expectedCount->id }}"
                                                data-academic-year="{{ $expectedCount->academic_year }}"
                                                data-semester="{{ $expectedCount->plan_semester }}"
                                                data-branch="{{ $expectedCount->branch ?? '' }}"
                                                data-subject-name-modal="{{ $subject->subject_name }} (Practical)"
                                                data-form-action="{{ route('data-entry.sections.storeInContext', $expectedCount->id) }}">
                                            <i class="fas fa-plus me-1"></i>
                                            <span class="d-none d-sm-inline">Add Practical</span>
                                        </button>
                                    </div>
                                    <div class="card-body p-0">
                                        @if ($practicalSections->isNotEmpty())
                                            <!-- Desktop Table -->
                                            <div class="table-responsive d-none d-md-block">
                                                <table class="table table-hover mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th class="border-0 text-center" style="width: 50px;">#</th>
                                                            <th class="border-0">Section No.</th>
                                                            <th class="border-0">Instructor</th>
                                                            <th class="border-0">Gender</th>
                                                            <th class="border-0">Branch</th>
                                                            <th class="border-0">Students</th>
                                                            <th class="border-0 text-center" style="width: 120px;">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($practicalSections as $index => $section)
                                                            <tr>
                                                                <td class="text-center text-muted small">{{ $index + 1 }}</td>
                                                                <td>
                                                                    <span class="badge bg-success bg-opacity-10 text-success">
                                                                        {{ $section->section_number }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    @if($section->instructor)
                                                                        <span class="badge bg-info bg-opacity-10 text-info">
                                                                            {{ $section->instructor->instructor_name }}
                                                                        </span>
                                                                    @else
                                                                        <span class="text-muted small">Not assigned</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <span class="badge bg-{{ $section->section_gender == 'Mixed' ? 'info' : ($section->section_gender == 'Male' ? 'primary' : 'danger') }} bg-opacity-10 text-{{ $section->section_gender == 'Mixed' ? 'info' : ($section->section_gender == 'Male' ? 'primary' : 'danger') }}">
                                                                        {{ $section->section_gender }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $section->branch ?? '-' }}</td>
                                                                <td>
                                                                    <span class="badge bg-warning bg-opacity-10 text-warning">
                                                                        {{ $section->student_count }} students
                                                                    </span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <div class="btn-group btn-group-sm">
                                                                        <button class="btn btn-outline-primary btn-sm open-edit-section-modal"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#editSectionModal"
                                                                                data-section-id="{{ $section->id }}"
                                                                                data-section-number="{{ $section->section_number }}"
                                                                                data-student-count="{{ $section->student_count }}"
                                                                                data-section-gender="{{ $section->section_gender }}"
                                                                                data-branch="{{ $section->branch }}"
                                                                                data-activity-type="{{ $section->activity_type }}"
                                                                                data-form-action="{{ route('data-entry.sections.updateInContext', $section->id) }}"
                                                                                data-context-info="Sec #{{ $section->section_number }} for {{ $subject->subject_no }} (Practical)"
                                                                                title="Edit Section">
                                                                            <i class="fas fa-edit"></i>
                                                                        </button>
                                                                        <button class="btn btn-outline-danger btn-sm open-delete-section-modal"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#deleteSectionModal"
                                                                                data-form-action="{{ route('data-entry.sections.destroyInContext', $section->id) }}"
                                                                                data-section-info="Section #{{ $section->section_number }} (Practical) for {{ $subject->subject_no }}"
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
                                            <div class="d-md-none p-3">
                                                @foreach ($practicalSections as $index => $section)
                                                    <div class="section-card mb-2 p-3 border rounded">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div class="section-info flex-grow-1">
                                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                                    <span class="badge bg-success bg-opacity-10 text-success">
                                                                        Section {{ $section->section_number }}
                                                                    </span>
                                                                    <span class="badge bg-{{ $section->section_gender == 'Mixed' ? 'info' : ($section->section_gender == 'Male' ? 'primary' : 'danger') }} bg-opacity-10 text-{{ $section->section_gender == 'Mixed' ? 'info' : ($section->section_gender == 'Male' ? 'primary' : 'danger') }}">
                                                                        {{ $section->section_gender }}
                                                                    </span>
                                                                </div>
                                                                <div class="section-details small text-muted">
                                                                    <div><i class="fas fa-users me-1"></i>{{ $section->student_count }} students</div>
                                                                    <div><i class="fas fa-map-marker-alt me-1"></i>{{ $section->branch ?? 'Default' }}</div>
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
                                                                                data-section-id="{{ $section->id }}"
                                                                                data-section-number="{{ $section->section_number }}"
                                                                                data-student-count="{{ $section->student_count }}"
                                                                                data-section-gender="{{ $section->section_gender }}"
                                                                                data-branch="{{ $section->branch }}"
                                                                                data-activity-type="{{ $section->activity_type }}"
                                                                                data-form-action="{{ route('data-entry.sections.updateInContext', $section->id) }}"
                                                                                data-context-info="Sec #{{ $section->section_number }} for {{ $subject->subject_no }} (Practical)">
                                                                            <i class="fas fa-edit me-2"></i>Edit
                                                                        </button>
                                                                    </li>
                                                                    <li>
                                                                        <button class="dropdown-item text-danger open-delete-section-modal"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#deleteSectionModal"
                                                                                data-form-action="{{ route('data-entry.sections.destroyInContext', $section->id) }}"
                                                                                data-section-info="Section #{{ $section->section_number }} (Practical) for {{ $subject->subject_no }}">
                                                                            <i class="fas fa-trash me-2"></i>Delete
                                                                        </button>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="empty-sections text-center py-4">
                                                <i class="fas fa-flask text-muted opacity-50" style="font-size: 2rem;"></i>
                                                <p class="text-muted mb-0 mt-2">No practical sections defined.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Include Modals -->
@include('dashboard.data-entry.partials._manage_section_modals', [
    'section' => null,
    'expectedCountId_for_add' => $expectedCount->id,
])

<style>
/* Context Information */
.context-details .detail-item label {
    font-weight: 500;
    margin-bottom: 0.25rem;
    display: block;
}

.context-details .detail-item {
    padding: 0.5rem 0;
}

/* Subject Sections */
.subject-code {
    font-family: 'Courier New', monospace;
    font-weight: 600;
}

.subject-name {
    font-weight: 500;
}

/* Section Cards */
.section-card {
    transition: all 0.15s ease;
    background: var(--light-bg-secondary);
}

.section-card:hover {
    background: rgba(59, 130, 246, 0.05);
    border-color: var(--primary-color) !important;
}

body.dark-mode .section-card {
    background: var(--dark-bg-secondary);
    border-color: var(--dark-border) !important;
}

body.dark-mode .section-card:hover {
    background: rgba(59, 130, 246, 0.1);
}

/* Empty States */
.empty-state i,
.empty-sections i {
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

/* Badge Enhancements */
.badge {
    font-weight: 500;
    letter-spacing: 0.025em;
}

/* Table Enhancements */
.table th,
.table td {
    vertical-align: middle;
    font-size: 0.875rem;
}

/* Card Hover Effects */
.card {
    transition: all 0.15s ease;
}

.card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

body.dark-mode .card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

/* Button Group Enhancements */
.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container-fluid {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .page-title {
        font-size: 1.25rem;
    }

    .context-details {
        text-align: center;
    }

    .context-details .row {
        justify-content: center;
    }

    .detail-item {
        text-align: center;
        padding: 0.75rem 0;
    }

    .subject-header h5 {
        font-size: 1.1rem;
        flex-direction: column;
        align-items: flex-start !important;
        gap: 0.5rem;
    }

    .subject-header .badge {
        align-self: flex-start;
    }

    .section-details {
        font-size: 0.8rem;
    }

    .btn {
        font-size: 0.8125rem;
        padding: 0.375rem 0.75rem;
    }

    .card-header h6 {
        font-size: 1rem;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    .page-title {
        font-size: 1.1rem;
    }

    .card-body {
        padding: 1rem;
    }

    .subject-header h5 {
        font-size: 1rem;
    }

    .subject-code {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }

    .subject-name {
        font-size: 0.9rem;
    }

    .section-details {
        font-size: 0.75rem;
    }

    .badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }

    .btn {
        font-size: 0.75rem;
        padding: 0.5rem 0.75rem;
    }

    .card-header {
        padding: 0.75rem;
    }

    .empty-sections,
    .empty-state {
        padding: 2rem 1rem !important;
    }

    .empty-sections i,
    .empty-state i {
        font-size: 1.5rem !important;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 1rem;
    }

    .detail-item {
        padding: 0.5rem 0;
    }

    .subject-header h5 {
        font-size: 0.95rem;
    }

    .section-card {
        padding: 0.75rem !important;
    }

    .btn {
        font-size: 0.7rem;
        padding: 0.375rem 0.5rem;
    }
}

/* Enhanced Form Controls */
.form-control:focus,
.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Enhanced Dropdown */
.dropdown-menu {
    border: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-radius: 0.5rem;
}

body.dark-mode .dropdown-menu {
    background: var(--dark-bg-secondary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.dropdown-item {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    transition: all 0.15s ease;
}

.dropdown-item:hover {
    background-color: rgba(59, 130, 246, 0.1);
}

body.dark-mode .dropdown-item:hover {
    background-color: rgba(59, 130, 246, 0.15);
}

/* Performance Optimizations */
.section-card,
.card,
.btn {
    will-change: transform;
}

.subjects-container {
    contain: layout style;
}

/* Accessibility Improvements */
.btn:focus,
.dropdown-toggle:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* Print Styles */
@media print {
    .btn,
    .dropdown {
        display: none !important;
    }

    .card {
        break-inside: avoid;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
$(document).ready(function() {
    console.log('Manage Sections for Context: Scripts Ready.');

    // Add Modal Handler
    const addModalEl = $('#addSectionModal');
    const addFormEl = $('#addSectionForm');
    const addModalTitleEl = $('#addSectionModalLabel');

    $(document).on('click', '.open-add-section-modal', function() {
        const button = $(this);

        // Get data from button
        const formAction = button.data('form-action');
        const planSubjectId = button.data('plan-subject-id');
        const activityType = button.data('activity-type');
        const subjectName = button.data('subject-name-modal');
        const branchFromContext = button.data('branch') || '';
        const expectedCountId = button.data('expected-count-id');
        const academicYear = button.data('academic-year') || '{{ $expectedCount->academic_year }}';
        const semester = button.data('semester') || '{{ $expectedCount->plan_semester }}';

        // Validate required data
        if (!formAction || !planSubjectId) {
            console.error("Missing required data attributes!");
            return;
        }

        // Set form properties
        addModalTitleEl.text('Add ' + activityType + ' Section for: ' + subjectName);
        addFormEl.attr('action', formAction);
        addFormEl.attr('method', 'POST');

        // Fill hidden fields
        addFormEl.find('input[name="academic_year"]').val(academicYear);
        addFormEl.find('input[name="semester"]').val(semester);
        addFormEl.find('input[name="plan_subject_id_from_modal"]').val(planSubjectId);
        addFormEl.find('input[name="activity_type_from_modal"]').val(activityType);
        addFormEl.find('input[name="branch"]').val(branchFromContext);
        addFormEl.find('#add_branch_display_modal').val(branchFromContext || 'Default');

        // Reset form fields
        addFormEl.find('input[name="section_number"]').val('1');
        addFormEl.find('input[name="student_count"]').val('0');
        addFormEl.find('select[name="section_gender"]').val('Mixed');

        // Clear previous errors
        addFormEl.find('.is-invalid').removeClass('is-invalid');
        addFormEl.find('.invalid-feedback, .text-danger.small, .alert-danger').remove();

        console.log("Add Modal configured. Action:", formAction, "PS_ID:", planSubjectId, "Activity:", activityType);
    });

    // Edit Modal Handler
    const editModalEl = $('#editSectionModal');
    const editFormEl = $('#editSectionForm');
    const editModalTitleEl = $('#editSectionModalLabel');
    const editContextInfoSpanEl = $('#edit_context_info_modal_text span');

    $(document).on('click', '.open-edit-section-modal', function() {
        const button = $(this);
        const formAction = button.data('form-action');
        const sectionNumber = button.data('section-number');
        const studentCount = button.data('student-count');
        const sectionGender = button.data('section-gender');
        const branch = button.data('branch') !== undefined ? button.data('branch') : '';
        const contextInfo = button.data('context-info');

        editModalTitleEl.text('Edit Section #' + sectionNumber);
        editFormEl.attr('action', formAction);
        if (editContextInfoSpanEl.length) editContextInfoSpanEl.text(contextInfo);

        editFormEl.find('input[name="section_number"]').val(sectionNumber);
        editFormEl.find('input[name="student_count"]').val(studentCount);
        editFormEl.find('select[name="section_gender"]').val(sectionGender);
        editFormEl.find('#edit_modal_branch_display').val(branch);

        editFormEl.find('.is-invalid').removeClass('is-invalid');
        editFormEl.find('.invalid-feedback, .text-danger.small, .alert-danger').remove();
        editFormEl.find('#edit_form_errors_placeholder').empty();

        console.log("Edit Modal configured. Action:", formAction);
    });

    // Delete Modal Handler
    const deleteModalEl = $('#deleteSectionModal');
    const deleteFormEl = $('#deleteSectionForm');
    const deleteSectionInfoSpanEl = $('#deleteSectionInfoText');

    $(document).on('click', '.open-delete-section-modal', function() {
        const button = $(this);
        const formAction = button.data('form-action');
        const sectionInfo = button.data('section-info');

        if (deleteFormEl.length && formAction) {
            deleteFormEl.attr('action', formAction);
        } else {
            console.error("Delete form or action missing!");
        }

        if (deleteSectionInfoSpanEl.length) {
            deleteSectionInfoSpanEl.text(sectionInfo);
        } else {
            console.error("Delete info span missing!");
        }

        console.log("Delete Modal configured. Action:", formAction);
    });

    // Handle validation errors - reopen appropriate modal
    @if ($errors->hasBag('storeSectionModal'))
        var addModalInstance = new bootstrap.Modal(document.getElementById('addSectionModal'));
        addModalInstance.show();
        console.log("Errors found for Add Section Modal, showing modal.");
    @endif

    @foreach ($errors->getBags() as $bagName => $bagErrors)
        @if (Str::startsWith($bagName, 'updateSectionModal_'))
            console.log("Errors found for Edit Section Modal ({{ $bagName }})");
        @endif
    @endforeach

    // Enhanced form submission with loading states
    $('form').on('submit', function() {
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();

        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
        submitBtn.prop('disabled', true);

        // Re-enable after 5 seconds as fallback
        setTimeout(() => {
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        }, 5000);
    });

    // Enhanced table responsiveness
    function optimizeTableDisplay() {
        const tables = $('.table-responsive');
        tables.each(function() {
            const table = $(this);
            const tableWidth = table[0].scrollWidth;
            const containerWidth = table.width();

            if (tableWidth > containerWidth) {
                table.addClass('has-scroll');
            } else {
                table.removeClass('has-scroll');
            }
        });
    }

    // Call on load and resize
    optimizeTableDisplay();
    $(window).on('resize', optimizeTableDisplay);

    // Smooth scrolling for long pages
    $('html').css('scroll-behavior', 'smooth');

    // Auto-dismiss alerts
    setTimeout(() => {
        $('.alert:not(.alert-permanent)').fadeOut();
    }, 5000);

    // Enhanced keyboard navigation
    $(document).on('keydown', function(e) {
        // Escape key closes modals
        if (e.key === 'Escape') {
            const openModal = $('.modal.show');
            if (openModal.length) {
                openModal.modal('hide');
            }
        }

        // Ctrl+Enter submits forms in modals
        if (e.ctrlKey && e.key === 'Enter') {
            const openModal = $('.modal.show');
            if (openModal.length) {
                const form = openModal.find('form');
                if (form.length && form[0].checkValidity()) {
                    form.submit();
                }
            }
        }
    });

    // Enhanced dropdown behavior
    $('.dropdown-toggle').on('click', function(e) {
        e.stopPropagation();
    });

    // Performance: Lazy load section cards on scroll
    const observerOptions = {
        root: null,
        rootMargin: '50px',
        threshold: 0.1
    };

    const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);

    // Observe section cards
    $('.section-card').each(function() {
        sectionObserver.observe(this);
    });

    console.log('✅ Sections management page initialized successfully');
});
</script>
@endsection
