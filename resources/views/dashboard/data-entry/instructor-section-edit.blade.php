@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-user-edit text-primary me-2"></i>
                        Assign Sections to Instructor
                    </h4>
                    <div class="instructor-details mt-2">
                        <div class="d-flex align-items-center mb-1">
                            <div class="user-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                {{ strtoupper(substr($instructor->instructor_name ?? optional($instructor->user)->name, 0, 2)) }}
                            </div>
                            <div>
                                <h5 class="mb-0 text-primary">{{ $instructor->instructor_name ?? optional($instructor->user)->name }}</h5>
                                <small class="text-muted">Employee No: {{ $instructor->instructor_no }}</small>
                            </div>
                        </div>
                        <p class="mb-0 text-secondary small">
                            <i class="fas fa-building me-1"></i>
                            Department: {{ optional($instructor->department)->department_name ?? 'N/A' }}
                        </p>
                    </div>
                </div>

                <a href="{{ route('data-entry.instructor-section.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Instructors List
                </a>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    <!-- Main Content -->
    <form action="{{ route('data-entry.instructor-section.sync', $instructor->id) }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list-check text-muted me-2"></i>
                            Select Subjects and Their Sections
                        </h6>
                        <span class="badge bg-info bg-opacity-10 text-info">{{ $displayedSubjectsCount ?? 0 }} Available Subjects</span>
                    </div>

                    <div class="card-body">
                        <!-- Search Bar -->
                        <div class="search-section mb-4">
                            <div class="search-wrapper position-relative">
                                <input type="text"
                                       id="subjectSearch"
                                       class="form-control search-input"
                                       placeholder="Search subjects by name or code..."
                                       autocomplete="off">
                                <button class="search-btn position-absolute end-0 top-50 translate-middle-y border-0 bg-transparent" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Subjects List Container -->
                        <div id="subjectsListContainer" class="subjects-container">
                            @php $displayedSubjectsCount = 0; @endphp
                            @forelse ($allSubjectsWithSections as $subject)
                                @php
                                    $sectionsForThisSubject = collect();
                                    $atLeastOneSectionIsAvailableOrAssignedToCurrent = false;

                                    if ($subject->planSubjectEntries->isNotEmpty()) {
                                        foreach ($subject->planSubjectEntries as $planSubEntry) {
                                            foreach ($planSubEntry->sections as $section) {
                                                $isAssignedToCurrentInstructor = in_array($section->id, $assignedSectionIds ?? []);
                                                $assignedToOtherInstructor = false;
                                                $assignedInstructorName = null;

                                                if (!$section->instructors->isEmpty()) {
                                                    if (!$isAssignedToCurrentInstructor) {
                                                        $assignedToOtherInstructor = true;
                                                        $assignedInstructorName = optional($section->instructors->first())->instructor_name ??
                                                                                   optional(optional($section->instructors->first())->user)->name;
                                                    }
                                                }

                                                $section->is_assigned_to_current_instructor = $isAssignedToCurrentInstructor;
                                                $section->assigned_instructor_name_for_display = $assignedToOtherInstructor ? $assignedInstructorName : null;
                                                $section->is_disabled_for_current_instructor = $assignedToOtherInstructor;

                                                $sectionsForThisSubject->push($section);

                                                if (!$section->is_disabled_for_current_instructor) {
                                                    $atLeastOneSectionIsAvailableOrAssignedToCurrent = true;
                                                }
                                            }
                                        }
                                    }

                                    if (!$atLeastOneSectionIsAvailableOrAssignedToCurrent && $sectionsForThisSubject->isNotEmpty()) {
                                        continue;
                                    }
                                @endphp

                                @if ($sectionsForThisSubject->isNotEmpty())
                                    @php $displayedSubjectsCount++; @endphp
                                    <div class="subject-card mb-3"
                                         data-subject-name="{{ strtolower($subject->subject_name) }}"
                                         data-subject-code="{{ strtolower($subject->subject_no) }}">
                                        <div class="card border">
                                            <div class="card-header bg-light border-0 py-3">
                                                <div class="form-check">
                                                    <input class="form-check-input subject-master-checkbox"
                                                           type="checkbox"
                                                           value="{{ $subject->id }}"
                                                           id="subject_{{ $subject->id }}_master_checkbox"
                                                           {{ !$sectionsForThisSubject->contains(fn($sec) => !$sec->is_disabled_for_current_instructor) ? 'disabled' : '' }}>
                                                    <label class="form-check-label fw-medium" for="subject_{{ $subject->id }}_master_checkbox">
                                                        <span class="subject-code badge bg-primary bg-opacity-10 text-primary me-2">{{ $subject->subject_no }}</span>
                                                        {{ $subject->subject_name }}
                                                        @if($subject->subjectCategory)
                                                            <small class="text-muted ms-2">({{ $subject->subjectCategory->subject_category_name }})</small>
                                                        @endif
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="sections-grid">
                                                    @foreach ($sectionsForThisSubject->sortBy(['activity_type', 'section_number']) as $section)
                                                        <div class="section-item d-flex align-items-center p-2 border-bottom">
                                                            <div class="form-check flex-grow-1">
                                                                <input class="form-check-input section-checkbox"
                                                                       type="checkbox"
                                                                       name="section_ids[]"
                                                                       value="{{ $section->id }}"
                                                                       id="section_{{ $section->id }}"
                                                                       data-subject-master-id="{{ $subject->id }}"
                                                                       {{ $section->is_assigned_to_current_instructor ? 'checked' : '' }}
                                                                       {{ $section->is_disabled_for_current_instructor ? 'disabled' : '' }}>
                                                                <label class="form-check-label d-flex align-items-center w-100"
                                                                       for="section_{{ $section->id }}"
                                                                       title="{{ $section->is_disabled_for_current_instructor ? 'Assigned to: ' . $section->assigned_instructor_name_for_display : '' }}">
                                                                    <div class="section-info flex-grow-1">
                                                                        <div class="section-title">
                                                                            <span class="badge bg-{{ $section->activity_type == 'theory' ? 'info' : 'success' }} bg-opacity-10 text-{{ $section->activity_type == 'theory' ? 'info' : 'success' }} me-2">
                                                                                Section #{{ $section->section_number }}
                                                                            </span>
                                                                            <span class="activity-type badge bg-light text-dark">{{ ucfirst($section->activity_type) }}</span>
                                                                        </div>
                                                                        <div class="section-details small text-muted mt-1">
                                                                            <i class="fas fa-users me-1"></i>{{ $section->student_count }} students
                                                                            @if ($section->assigned_instructor_name_for_display)
                                                                                <span class="badge bg-warning text-dark ms-2">
                                                                                    <i class="fas fa-user me-1"></i>{{ Str::limit($section->assigned_instructor_name_for_display, 15) }}
                                                                                </span>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @if ($sectionsForThisSubject->isEmpty())
                                                    <div class="text-center py-3">
                                                        <small class="text-muted">No sections found for this subject.</small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <div class="empty-state text-center py-5">
                                    <i class="fas fa-book text-muted opacity-50" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3 text-muted">No Subjects Available</h5>
                                    <p class="text-muted">No subjects are available in the system.</p>
                                </div>
                            @endforelse

                            @if ($displayedSubjectsCount === 0 && $allSubjectsWithSections->isNotEmpty())
                                <div class="empty-state text-center py-5" id="noAvailableSubjectsMessage">
                                    <i class="fas fa-exclamation-circle text-warning" style="font-size: 3rem; opacity: 0.5;"></i>
                                    <h5 class="mt-3 text-muted">No Available Assignments</h5>
                                    <p class="text-muted">All subjects/sections are either fully assigned or have no available sections for this instructor.</p>
                                </div>
                            @elseif(!$allSubjectsWithSections->isNotEmpty())
                                <div class="empty-state text-center py-5" id="noSubjectsSystemMessage">
                                    <i class="fas fa-book text-muted opacity-50" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3 text-muted">No Subjects Found</h5>
                                    <p class="text-muted">No subjects found in the system to assign.</p>
                                </div>
                            @endif

                            <div class="empty-state text-center py-5" id="noAssignmentResultsFilter" style="display: none;">
                                <i class="fas fa-search text-muted opacity-50" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 text-muted">No Search Results</h5>
                                <p class="text-muted">No subjects/sections match your search criteria.</p>
                            </div>
                        </div>

                        @error('section_ids')
                            <div class="alert alert-danger mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="card-footer bg-light border-0 d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            Select subjects and sections to assign to this instructor
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('data-entry.instructor-section.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save Assignments
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
/* Search Section */
.search-input {
    padding-left: 1rem;
    padding-right: 3rem;
    border: 1px solid var(--light-border);
    border-radius: 0.5rem;
    background: var(--light-bg-secondary);
    transition: all var(--transition-speed);
}

body.dark-mode .search-input {
    background: var(--dark-bg);
    border-color: var(--dark-border);
    color: var(--dark-text-secondary);
}

.search-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-btn {
    color: var(--light-text-secondary);
    transition: color var(--transition-speed);
}

.search-btn:hover {
    color: var(--primary-color);
}

/* Subjects Container */
.subjects-container {
    max-height: 60vh;
    overflow-y: auto;
    padding-right: 5px;
}

.subjects-container::-webkit-scrollbar {
    width: 6px;
}

.subjects-container::-webkit-scrollbar-track {
    background: var(--light-bg);
}

.subjects-container::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 3px;
}

/* Subject Cards */
.subject-card {
    transition: all var(--transition-speed);
}

.subject-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.subject-code {
    font-family: 'Courier New', monospace;
    font-weight: 600;
}

/* Sections Grid */
.sections-grid {
    max-height: 300px;
    overflow-y: auto;
}

.section-item {
    transition: background-color var(--transition-speed);
    border-radius: 0.25rem;
    margin-bottom: 0.25rem;
}

.section-item:hover {
    background-color: rgba(59, 130, 246, 0.05);
}

.section-item:last-child {
    border-bottom: none;
}

/* User Avatar */
.user-avatar {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border: 2px solid rgba(255, 255, 255, 0.2);
    font-size: 0.875rem;
    font-weight: 600;
}

/* Form Controls */
.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.form-check-input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
}

.form-check-input:disabled {
    opacity: 0.5;
}

.form-check-label {
    cursor: pointer;
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

/* Responsive Design */
@media (max-width: 1200px) {
    .subjects-container {
        max-height: 55vh;
    }

    .section-item {
        padding: 0.625rem;
    }

    .search-input {
        font-size: 0.875rem;
    }
}

@media (max-width: 992px) {
    .page-title {
        font-size: 1.5rem;
    }

    .user-avatar {
        width: 35px !important;
        height: 35px !important;
        font-size: 0.75rem;
    }

    .instructor-details h5 {
        font-size: 1.1rem;
    }

    .subjects-container {
        max-height: 50vh;
    }

    .section-item {
        padding: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .section-title .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}

@media (max-width: 768px) {
    .container-fluid {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .page-title {
        font-size: 1.25rem;
    }

    .instructor-details {
        text-align: center;
        margin-top: 1rem;
    }

    .instructor-details .d-flex {
        justify-content: center;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }

    .instructor-details .user-avatar {
        margin-right: 0 !important;
        margin-bottom: 0.5rem;
    }

    .subjects-container {
        max-height: 45vh;
        padding-right: 0;
    }

    .section-item {
        padding: 0.75rem 0.5rem;
        flex-direction: column;
        align-items: flex-start !important;
    }

    .section-item .form-check {
        width: 100%;
    }

    .section-item .form-check-label {
        width: 100%;
    }

    .section-info {
        margin-top: 0.5rem;
    }

    .section-title {
        margin-bottom: 0.5rem;
    }

    .section-title .badge {
        display: inline-block;
        margin-bottom: 0.25rem;
    }

    .card-footer {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch !important;
        padding: 1rem;
    }

    .card-footer .d-flex {
        justify-content: stretch;
        flex-direction: column;
        gap: 0.75rem;
    }

    .card-footer .btn {
        width: 100%;
        padding: 0.75rem;
        font-size: 0.875rem;
    }

    .search-wrapper {
        margin-bottom: 1rem;
    }

    .search-input {
        padding: 0.75rem 3rem 0.75rem 1rem;
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

    .instructor-details h5 {
        font-size: 1rem;
    }

    .instructor-details small {
        font-size: 0.75rem;
    }

    .user-avatar {
        width: 30px !important;
        height: 30px !important;
        font-size: 0.7rem;
    }

    .card {
        margin-bottom: 1rem;
        border-radius: 0.5rem;
    }

    .card-header {
        padding: 0.75rem;
    }

    .card-body {
        padding: 0.75rem;
    }

    .subjects-container {
        max-height: 40vh;
    }

    .subject-card .card {
        margin-bottom: 0.75rem;
    }

    .subject-card .card-header {
        padding: 0.5rem 0.75rem;
    }

    .subject-card .card-body {
        padding: 0.5rem 0.75rem;
    }

    .form-check-label {
        font-size: 0.875rem;
        line-height: 1.4;
    }

    .subject-code {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }

    .section-item {
        padding: 0.5rem;
        margin-bottom: 0.25rem;
        border-radius: 0.375rem;
    }

    .section-details {
        font-size: 0.75rem !important;
        margin-top: 0.25rem;
    }

    .section-details .badge {
        font-size: 0.65rem;
        padding: 0.15rem 0.3rem;
        margin-left: 0.25rem;
    }

    .badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }

    .activity-type {
        font-size: 0.65rem !important;
        padding: 0.15rem 0.35rem;
    }

    .search-input {
        padding: 0.625rem 2.5rem 0.625rem 0.875rem;
        font-size: 0.875rem;
    }

    .search-btn {
        right: 0.5rem;
    }

    .card-footer {
        padding: 0.75rem;
    }

    .card-footer .btn {
        padding: 0.625rem 1rem;
        font-size: 0.8rem;
    }

    .empty-state {
        padding: 2rem 1rem !important;
    }

    .empty-state i {
        font-size: 2rem !important;
    }

    .empty-state h5 {
        font-size: 1rem;
    }

    .empty-state p {
        font-size: 0.875rem;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 1rem;
    }

    .instructor-details h5 {
        font-size: 0.9rem;
    }

    .container-fluid {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .subjects-container {
        max-height: 35vh;
    }

    .section-item {
        padding: 0.375rem;
    }

    .form-check-label {
        font-size: 0.8rem;
    }

    .section-details {
        font-size: 0.7rem !important;
    }

    .section-details .badge {
        font-size: 0.6rem;
        padding: 0.1rem 0.25rem;
    }

    .badge {
        font-size: 0.65rem;
        padding: 0.15rem 0.3rem;
    }

    .search-input {
        padding: 0.5rem 2rem 0.5rem 0.75rem;
        font-size: 0.8rem;
    }

    .card-footer .btn {
        padding: 0.5rem 0.75rem;
        font-size: 0.75rem;
    }
}

@media (max-width: 360px) {
    .page-title {
        font-size: 0.95rem;
    }

    .subjects-container {
        max-height: 30vh;
    }

    .section-item {
        padding: 0.25rem;
    }

    .form-check-label {
        font-size: 0.75rem;
    }

    .section-details {
        font-size: 0.65rem !important;
    }

    .badge {
        font-size: 0.6rem;
        padding: 0.1rem 0.25rem;
    }

    .search-input {
        font-size: 0.75rem;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
$(document).ready(function() {
    // Auto-focus on search field
    $('#subjectSearch').focus();

    // Search functionality
    $('#subjectSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        let resultsFoundOverall = false;

        $('#subjectsListContainer .subject-card').each(function() {
            const subjectContainer = $(this);
            const subjectName = subjectContainer.data('subject-name');
            const subjectCode = subjectContainer.data('subject-code');

            if (subjectName.includes(searchTerm) || subjectCode.includes(searchTerm)) {
                subjectContainer.show();
                resultsFoundOverall = true;
            } else {
                subjectContainer.hide();
            }
        });

        $('#noAssignmentResultsFilter').toggle(!resultsFoundOverall);
    });

    // Master checkbox functionality
    $(document).on('change', '.subject-master-checkbox', function() {
        const subjectId = $(this).val();
        const isChecked = $(this).is(':checked');
        $('.section-checkbox[data-subject-master-id="' + subjectId + '"]:not(:disabled)').prop('checked', isChecked);
    });

    // Update master checkbox when sections change
    $(document).on('change', '.section-checkbox', function() {
        const subjectId = $(this).data('subject-master-id');
        const allSectionsForSubject = $('.section-checkbox[data-subject-master-id="' + subjectId + '"]:not(:disabled)');
        const checkedSectionsForSubject = $('.section-checkbox[data-subject-master-id="' + subjectId + '"]:not(:disabled):checked');
        const masterCheckbox = $('#subject_' + subjectId + '_master_checkbox');

        if (checkedSectionsForSubject.length === allSectionsForSubject.length && allSectionsForSubject.length > 0) {
            masterCheckbox.prop('checked', true);
            masterCheckbox.prop('indeterminate', false);
        } else if (checkedSectionsForSubject.length > 0) {
            masterCheckbox.prop('checked', false);
            masterCheckbox.prop('indeterminate', true);
        } else {
            masterCheckbox.prop('checked', false);
            masterCheckbox.prop('indeterminate', false);
        }
    });

    // Initialize master checkbox states on page load
    $('.subject-master-checkbox').each(function() {
        const subjectId = $(this).val();
        const allSectionsForSubject = $('.section-checkbox[data-subject-master-id="' + subjectId + '"]:not(:disabled)');
        const checkedSectionsForSubject = $('.section-checkbox[data-subject-master-id="' + subjectId + '"]:not(:disabled):checked');

        if (allSectionsForSubject.length > 0) {
            if (checkedSectionsForSubject.length === allSectionsForSubject.length) {
                $(this).prop('checked', true);
            } else if (checkedSectionsForSubject.length > 0) {
                $(this).prop('indeterminate', true);
            }
        } else {
            $(this).prop('disabled', true);
        }
    });

    // Smooth scroll for long lists
    $('.subjects-container').on('scroll', function() {
        const scrollTop = $(this).scrollTop();
        const scrollHeight = $(this)[0].scrollHeight;
        const clientHeight = $(this)[0].clientHeight;

        if (scrollTop + clientHeight >= scrollHeight - 5) {
            // Near bottom
            $(this).addClass('scrolled-bottom');
        } else {
            $(this).removeClass('scrolled-bottom');
        }
    });

    // Form submission validation
    $('form').on('submit', function(e) {
        const checkedSections = $('.section-checkbox:checked').length;
        if (checkedSections === 0) {
            e.preventDefault();
            alert('Please select at least one section to assign.');
            return false;
        }
    });
});
</script>
@endsection
