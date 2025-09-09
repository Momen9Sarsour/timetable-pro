@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Back Button - Always visible on top left -->
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('data-entry.instructor-subjects.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back to Assignments List
            </a>
        </div>
    </div>

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header">
                <h4 class="page-title mb-2">
                    <i class="fas fa-user-graduate text-primary me-2"></i>
                    Assign Subjects
                </h4>
                <div class="instructor-details">
                    <div class="instructor-info d-flex align-items-center mb-2">
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
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    <!-- Main Content -->
    <form action="{{ route('data-entry.instructor-subjects.sync', $instructor->id) }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list-check text-muted me-2"></i>
                            Select Assignable Subjects
                        </h6>
                        <span class="badge bg-info bg-opacity-10 text-info d-none d-sm-inline">
                            {{ isset($allSubjects) ? $allSubjects->count() : 0 }} Available
                        </span>
                    </div>

                    <div class="card-body">
                        <!-- Search Bar -->
                        <div class="search-section mb-3">
                            <div class="search-wrapper position-relative">
                                <input type="text"
                                       id="subjectAssignmentSearch"
                                       class="form-control search-input"
                                       placeholder="Search subjects by name or code..."
                                       autocomplete="off"
                                       autofocus>
                                <button class="search-btn position-absolute end-0 top-50 translate-middle-y border-0 bg-transparent" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Subjects List Container -->
                        <div class="subjects-container">
                            @if(isset($allSubjects) && $allSubjects->count() > 0)
                                <div class="subjects-grid row g-3">
                                    @foreach ($allSubjects as $subject)
                                        <div class="col-md-6 col-lg-4 subject-assignment-item">
                                            <div class="subject-card">
                                                <div class="form-check p-3 border rounded">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           name="subject_ids[]"
                                                           value="{{ $subject->id }}"
                                                           id="subject_assign_{{ $subject->id }}"
                                                           {{ in_array($subject->id, $assignedSubjectIds ?? []) ? 'checked' : '' }}>
                                                    <label class="form-check-label w-100 cursor-pointer" for="subject_assign_{{ $subject->id }}">
                                                        <div class="subject-info">
                                                            <div class="subject-header d-flex align-items-start justify-content-between">
                                                                <span class="subject-code badge bg-primary bg-opacity-10 text-primary">{{ $subject->subject_no }}</span>
                                                                @if(in_array($subject->id, $assignedSubjectIds ?? []))
                                                                    <span class="badge bg-success bg-opacity-10 text-success">
                                                                        <i class="fas fa-check"></i>
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <div class="subject-name mt-2 mb-1 fw-medium">{{ $subject->subject_name }}</div>
                                                            <div class="subject-department small text-muted">
                                                                <i class="fas fa-building me-1"></i>
                                                                {{ optional($subject->department)->department_name ?? 'N/A' }}
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- No Search Results Message -->
                                <div class="empty-state text-center py-5" id="noAssignmentResults" style="display: none;">
                                    <i class="fas fa-search text-muted opacity-50" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3 text-muted">No Search Results</h5>
                                    <p class="text-muted">No subjects match your search criteria.</p>
                                </div>
                            @else
                                <!-- Empty State -->
                                <div class="empty-state text-center py-5">
                                    <i class="fas fa-book text-muted opacity-50" style="font-size: 4rem;"></i>
                                    <h5 class="mt-3 text-muted">No Subjects Available</h5>
                                    <p class="text-muted">No subjects are available in the system for assignment.</p>
                                </div>
                            @endif
                        </div>

                        @error('subject_ids')
                            <div class="alert alert-danger mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>{{ $message }}
                            </div>
                        @enderror
                        @error('subject_ids.*')
                            <div class="alert alert-danger mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="card-footer bg-light border-0 d-flex justify-content-between align-items-center flex-wrap">
                        <div class="text-muted small d-none d-md-block">
                            <i class="fas fa-info-circle me-1"></i>
                            Select subjects to assign to this instructor
                        </div>
                        <div class="action-buttons d-flex gap-2 w-100 w-md-auto">
                            <a href="{{ route('data-entry.instructor-subjects.index') }}" class="btn btn-outline-secondary flex-fill flex-md-grow-0">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary flex-fill flex-md-grow-0">
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
/* Page Header */
.page-header {
    margin-bottom: 1.5rem;
}

.instructor-info {
    transition: none;
}

.user-avatar {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border: 2px solid rgba(255, 255, 255, 0.2);
    font-size: 0.875rem;
    font-weight: 600;
}

/* Search Section */
.search-section {
    margin-bottom: 1rem;
}

.search-input {
    padding: 0.75rem 3rem 0.75rem 1rem;
    border: 1px solid var(--light-border);
    border-radius: 0.5rem;
    background: var(--light-bg-secondary);
    transition: border-color 0.15s ease;
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
    transition: color 0.15s ease;
}

.search-btn:hover {
    color: var(--primary-color);
}

/* Subjects Container */
.subjects-container {
    max-height: 60vh;
    overflow-y: auto;
    padding: 0.5rem;
    border: 1px solid var(--light-border);
    border-radius: 0.5rem;
    background: var(--light-bg);
}

body.dark-mode .subjects-container {
    background: var(--dark-bg);
    border-color: var(--dark-border);
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
    height: 100%;
    transition: all 0.15s ease;
}

.subject-card:hover {
    transform: translateY(-2px);
}

.subject-card .form-check {
    height: 100%;
    transition: all 0.15s ease;
    cursor: pointer;
}

.subject-card .form-check:hover {
    border-color: var(--primary-color) !important;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
}

.subject-card .form-check input:checked + label {
    background-color: rgba(59, 130, 246, 0.05);
}

body.dark-mode .subject-card .form-check input:checked + label {
    background-color: rgba(59, 130, 246, 0.1);
}

.subject-code {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    font-size: 0.75rem;
}

.subject-name {
    font-size: 0.875rem;
    line-height: 1.4;
    color: var(--light-text);
}

body.dark-mode .subject-name {
    color: var(--dark-text-secondary);
}

.subject-department {
    font-size: 0.75rem;
}

/* Form Controls */
.form-check-input {
    margin-top: 0.2em;
}

.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.form-check-input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
}

.cursor-pointer {
    cursor: pointer;
}

/* Action Buttons */
.action-buttons {
    gap: 0.5rem;
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
@media (min-width: 576px) {
    .action-buttons {
        width: auto;
    }

    .action-buttons .btn {
        flex: 0 0 auto;
    }
}

@media (min-width: 768px) {
    .instructor-details {
        text-align: left;
    }

    .instructor-info {
        flex-direction: row;
        justify-content: flex-start;
    }

    .subjects-container {
        max-height: 55vh;
    }
}

@media (min-width: 992px) {
    .subjects-container {
        max-height: 60vh;
    }
}

/* Mobile Styles */
@media (max-width: 767px) {
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

    .instructor-info {
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }

    .user-avatar {
        margin-right: 0 !important;
        margin-bottom: 0.5rem;
        width: 35px !important;
        height: 35px !important;
        font-size: 0.75rem;
    }

    .subjects-container {
        max-height: 45vh;
        padding: 0.25rem;
    }

    .subjects-grid {
        gap: 0.75rem !important;
    }

    .subject-card .form-check {
        padding: 1rem;
    }

    .subject-name {
        font-size: 0.8rem;
    }

    .subject-department {
        font-size: 0.7rem;
    }

    .card-footer {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
        padding: 1rem;
    }

    .action-buttons {
        width: 100%;
        flex-direction: column;
    }

    .action-buttons .btn {
        width: 100%;
        padding: 0.75rem;
    }

    .search-input {
        padding: 0.75rem 2.5rem 0.75rem 1rem;
        font-size: 1rem;
    }

    .search-btn {
        right: 0.75rem;
    }
}

/* Small Mobile */
@media (max-width: 575px) {
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

    .card-header,
    .card-body {
        padding: 0.75rem;
    }

    .subjects-container {
        max-height: 40vh;
    }

    .subject-card .form-check {
        padding: 0.75rem;
    }

    .subject-code {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }

    .subject-name {
        font-size: 0.75rem;
    }

    .subject-department {
        font-size: 0.65rem;
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
        padding: 2rem 1rem;
    }

    .empty-state i {
        font-size: 2rem;
    }

    .empty-state h5 {
        font-size: 1rem;
    }

    .empty-state p {
        font-size: 0.875rem;
    }

    .subjects-grid .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

/* Extra Small Mobile */
@media (max-width: 480px) {
    .container-fluid {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .page-title {
        font-size: 1rem;
    }

    .instructor-details h5 {
        font-size: 0.9rem;
    }

    .subjects-container {
        max-height: 35vh;
    }

    .subject-card .form-check {
        padding: 0.5rem;
    }

    .subject-name {
        font-size: 0.7rem;
    }

    .subject-department {
        font-size: 0.6rem;
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

/* Very Small devices */
@media (max-width: 360px) {
    .page-title {
        font-size: 0.95rem;
    }

    .subjects-container {
        max-height: 30vh;
    }

    .subject-card .form-check {
        padding: 0.375rem;
    }

    .subject-name {
        font-size: 0.65rem;
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
    $('#subjectAssignmentSearch').focus();

    // Search functionality
    $('#subjectAssignmentSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        let resultsFound = false;

        $('.subject-assignment-item').each(function() {
            const labelText = $(this).find('.form-check-label').text().toLowerCase();
            if (labelText.includes(searchTerm)) {
                $(this).show();
                resultsFound = true;
            } else {
                $(this).hide();
            }
        });

        $('#noAssignmentResults').toggle(!resultsFound);
    });

    // Enhanced checkbox interaction
    $('.subject-card .form-check').on('click', function(e) {
        if (e.target.type !== 'checkbox') {
            const checkbox = $(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        }
    });

    // Visual feedback for checked items
    $('input[type="checkbox"]').on('change', function() {
        const card = $(this).closest('.form-check');
        if ($(this).is(':checked')) {
            card.addClass('border-primary');
        } else {
            card.removeClass('border-primary');
        }
    });

    // Initialize checked states
    $('input[type="checkbox"]:checked').each(function() {
        $(this).closest('.form-check').addClass('border-primary');
    });

    // Form submission validation
    $('form').on('submit', function(e) {
        const checkedSubjects = $('input[name="subject_ids[]"]:checked').length;
        if (checkedSubjects === 0) {
            e.preventDefault();
            alert('Please select at least one subject to assign.');
            return false;
        }
    });

    // Smooth scroll for long lists
    $('.subjects-container').on('scroll', function() {
        const scrollTop = $(this).scrollTop();
        const scrollHeight = $(this)[0].scrollHeight;
        const clientHeight = $(this)[0].clientHeight;

        if (scrollTop + clientHeight >= scrollHeight - 5) {
            $(this).addClass('scrolled-bottom');
        } else {
            $(this).removeClass('scrolled-bottom');
        }
    });
});
</script>
@endsection
