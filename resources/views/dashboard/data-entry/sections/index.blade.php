@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-users-cog text-primary me-2"></i>
                        Academic Sections
                    </h4>
                    <p class="text-muted mb-0">View and manage all academic sections across departments and plans</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-filter text-primary me-2"></i>
                        Filter Sections
                    </h6>
                </div>
                <div class="card-body pt-3">
                    <form action="{{ route('data-entry.sections.index') }}" method="GET">
                        <!-- First Row - 4 columns -->
                        <div class="row g-3 mb-3">
                            <div class="col-lg-3 col-md-6">
                                <label for="filter_academic_year" class="form-label fw-medium">
                                    <i class="fas fa-calendar-alt text-muted me-1"></i>Academic Year
                                </label>
                                <select class="form-select" id="filter_academic_year" name="academic_year">
                                    <option value="">All Years</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year }}" {{ $request->academic_year == $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label for="filter_semester" class="form-label fw-medium">
                                    <i class="fas fa-calendar text-muted me-1"></i>Term
                                </label>
                                <select class="form-select" id="filter_semester" name="semester">
                                    <option value="">All Terms</option>
                                    @foreach($semesters as $key => $value)
                                        <option value="{{ $key }}" {{ $request->semester == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label for="filter_department_id" class="form-label fw-medium">
                                    <i class="fas fa-building text-muted me-1"></i>Department
                                </label>
                                <select class="form-select select2-searchable" id="filter_department_id" name="department_id" data-placeholder="All Departments">
                                    <option value=""></option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ $request->department_id == $department->id ? 'selected' : '' }}>
                                            {{ $department->department_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label for="filter_plan_id" class="form-label fw-medium">
                                    <i class="fas fa-clipboard-list text-muted me-1"></i>Academic Plan
                                </label>
                                <select class="form-select select2-searchable" id="filter_plan_id" name="plan_id" data-placeholder="All Plans">
                                    <option value=""></option>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}" {{ $request->plan_id == $plan->id ? 'selected' : '' }}>
                                            {{ $plan->plan_no }} - {{ $plan->plan_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Second Row - 4 columns -->
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-3 col-md-6">
                                <label for="filter_plan_level" class="form-label fw-medium">
                                    <i class="fas fa-layer-group text-muted me-1"></i>Level
                                </label>
                                <select class="form-select" id="filter_plan_level" name="plan_level">
                                    <option value="">All Levels</option>
                                    @foreach($levels as $level_option)
                                        <option value="{{ $level_option }}" {{ $request->plan_level == $level_option ? 'selected' : '' }}>
                                            Level {{ $level_option }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label for="filter_subject_id" class="form-label fw-medium">
                                    <i class="fas fa-book text-muted me-1"></i>Subject
                                </label>
                                <select class="form-select select2-searchable" id="filter_subject_id" name="subject_id" data-placeholder="All Subjects">
                                    <option value=""></option>
                                    @foreach($subjectsForFilter as $subject_filter)
                                        <option value="{{ $subject_filter->id }}" {{ $request->subject_id == $subject_filter->id ? 'selected' : '' }}>
                                            {{ $subject_filter->subject_no }} - {{ $subject_filter->subject_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label for="filter_branch" class="form-label fw-medium">
                                    <i class="fas fa-map-marker-alt text-muted me-1"></i>Branch
                                </label>
                                <input type="text" class="form-control" id="filter_branch" name="branch"
                                       value="{{ $request->branch }}" placeholder="e.g., Main or 'none'">
                                {{-- <div class="form-text">Type 'none' for default branch</div> --}}
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('data-entry.sections.index') }}" class="btn btn-outline-secondary flex-fill">
                                        <i class="fas fa-undo me-1"></i>Reset
                                    </a>
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        <i class="fas fa-search me-1"></i>Filter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list text-muted me-2"></i>
                            Sections List
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $sections->total() }} Total</span>
                    </div>
                </div>

                <div class="card-body pt-3">
                    @if($sections->count() > 0)
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 text-center" style="width: 50px;">#</th>
                                        <th class="border-0">Year</th>
                                        <th class="border-0 text-center">Term</th>
                                        <th class="border-0">Plan</th>
                                        <th class="border-0 text-center">Lvl</th>
                                        <th class="border-0" style="min-width: 200px;">Subject</th>
                                        <th class="border-0 text-center">Activity</th>
                                        <th class="border-0 text-center">Sec#</th>
                                        <th class="border-0 text-center">Gender</th>
                                        <th class="border-0">Branch</th>
                                        <th class="border-0 text-center">Count</th>
                                        <th class="border-0 text-center" style="width: 120px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sections as $index => $section)
                                        <tr class="border-bottom">
                                            <td class="text-center text-muted">
                                                <small>{{ $sections->firstItem() + $index }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info bg-opacity-20">{{ $section->academic_year }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success bg-opacity-20">{{ $section->semester }}</span>
                                            </td>
                                            <td title="{{ optional(optional($section->planSubject)->plan)->plan_name ?? '' }}">
                                                <span class="badge bg-light text-dark font-monospace">{{ optional(optional($section->planSubject)->plan)->plan_no ?? 'N/A' }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary bg-opacity-20 text-light">L{{ optional($section->planSubject)->plan_level ?? 'N/A' }}</span>
                                            </td>
                                            <td title="{{ optional(optional($section->planSubject)->subject)->subject_name ?? '' }}">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-light text-dark font-monospace small">
                                                        {{ Str::limit(optional(optional($section->planSubject)->subject)->subject_no ?? 'N/A', 10) }}
                                                    </span>
                                                    <span class="text-truncate" style="max-width: 150px;">
                                                        {{ Str::limit(optional(optional($section->planSubject)->subject)->subject_name ?? 'N/A', 20) }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $section->activity_type == 'Theory' ? 'primary' : 'success' }}">
                                                    {{ $section->activity_type }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-bold">{{ $section->section_number }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary bg-opacity-20 text-light small">{{ $section->section_gender }}</span>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $section->branch ?? '-' }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-warning bg-opacity-20 text-light">{{ $section->student_count }}</span>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('data-entry.sections.manageSubjectContext', [
                                                            'plan_subject_id' => $section->plan_subject_id,
                                                            'academic_year' => $section->academic_year,
                                                            'semester_of_sections' => $section->semester,
                                                            'branch' => $section->branch,
                                                        ]) }}"
                                                   class="btn btn-outline-primary btn-sm"
                                                   title="Manage Sections">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Tablet View (Medium screens) -->
                        <div class="d-none d-md-block d-lg-none">
                            @foreach ($sections as $index => $section)
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <div class="row align-items-center">
                                            <div class="col-8">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <span class="badge bg-info bg-opacity-20 small">{{ $section->academic_year }}</span>
                                                    <span class="badge bg-success bg-opacity-20 small">S{{ $section->semester }}</span>
                                                    <span class="badge bg-{{ $section->activity_type == 'Theory' ? 'primary' : 'success' }} small">{{ $section->activity_type }}</span>
                                                </div>
                                                <h6 class="card-title mb-1">{{ Str::limit(optional(optional($section->planSubject)->subject)->subject_name ?? 'N/A', 35) }}</h6>
                                                <div class="row text-muted small">
                                                    <div class="col-6">
                                                        <i class="fas fa-hashtag me-1"></i>{{ optional(optional($section->planSubject)->subject)->subject_no ?? 'N/A' }}
                                                    </div>
                                                    <div class="col-6">
                                                        <i class="fas fa-clipboard me-1"></i>{{ optional(optional($section->planSubject)->plan)->plan_no ?? 'N/A' }}
                                                    </div>
                                                </div>
                                                <div class="row text-muted small mt-1">
                                                    <div class="col-6">
                                                        <i class="fas fa-layer-group me-1"></i>Level {{ optional($section->planSubject)->plan_level ?? 'N/A' }}
                                                    </div>
                                                    <div class="col-6">
                                                        <i class="fas fa-users me-1"></i>{{ $section->student_count }} Students
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4 text-end">
                                                <div class="text-center mb-2">
                                                    <div class="fw-bold fs-4 text-primary">{{ $section->section_number }}</div>
                                                    <small class="text-muted">Section</small>
                                                </div>
                                                <a href="{{ route('data-entry.sections.manageSubjectContext', [
                                                            'plan_subject_id' => $section->plan_subject_id,
                                                            'academic_year' => $section->academic_year,
                                                            'semester_of_sections' => $section->semester,
                                                            'branch' => $section->branch,
                                                        ]) }}"
                                                   class="btn btn-outline-primary btn-sm w-100">
                                                    <i class="fas fa-edit me-1"></i>Manage
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Mobile Cards -->
                        <div class="d-md-none">
                            @foreach ($sections as $index => $section)
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="badge bg-info bg-opacity-20 text-light small">{{ $section->academic_year }}</span>
                                                    <span class="badge bg-success bg-opacity-20 text-light small">S{{ $section->semester }}</span>
                                                    <span class="badge bg-{{ $section->activity_type == 'Theory' ? 'primary' : 'success' }} small">{{ $section->activity_type }}</span>
                                                </div>
                                                <h6 class="card-title mb-1">{{ Str::limit(optional(optional($section->planSubject)->subject)->subject_name ?? 'N/A', 25) }}</h6>
                                                <div class="text-muted small mb-2">
                                                    <span class="badge bg-light text-dark font-monospace me-1">{{ optional(optional($section->planSubject)->subject)->subject_no ?? 'N/A' }}</span>
                                                    Plan: {{ optional(optional($section->planSubject)->plan)->plan_no ?? 'N/A' }}
                                                </div>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('data-entry.sections.manageSubjectContext', [
                                                                    'plan_subject_id' => $section->plan_subject_id,
                                                                    'academic_year' => $section->academic_year,
                                                                    'semester_of_sections' => $section->semester,
                                                                    'branch' => $section->branch,
                                                                ]) }}">
                                                            <i class="fas fa-edit me-2"></i>Manage Section
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="row text-center small">
                                            <div class="col-3">
                                                <div class="fw-bold">L{{ optional($section->planSubject)->plan_level ?? 'N/A' }}</div>
                                                <div class="text-muted">Level</div>
                                            </div>
                                            <div class="col-3">
                                                <div class="fw-bold">{{ $section->section_number }}</div>
                                                <div class="text-muted">Section</div>
                                            </div>
                                            <div class="col-3">
                                                <div class="fw-bold">{{ $section->student_count }}</div>
                                                <div class="text-muted">Students</div>
                                            </div>
                                            <div class="col-3">
                                                <div class="fw-bold">{{ $section->section_gender }}</div>
                                                <div class="text-muted">Gender</div>
                                            </div>
                                        </div>
                                        <div class="text-end mt-2">
                                            <small class="text-muted">
                                                Branch: {{ $section->branch ?? 'Default' }} | #{{ $sections->firstItem() + $index }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($sections->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $sections->appends(request()->query())->links('pagination::bootstrap-5') }}
                            </div>
                        @endif

                    @else
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-users-cog text-muted opacity-50" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 text-muted">No Sections Found</h5>
                                <p class="text-muted mb-4">No sections match your current filter criteria. Try adjusting your filters or create new sections.</p>
                                <a href="{{ route('data-entry.sections.index') }}" class="btn btn-primary">
                                    <i class="fas fa-undo me-2"></i>Reset Filters
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Select2 Bootstrap 5 Theme Customization */
.select2-container--bootstrap-5 .select2-selection--single {
    height: calc(1.5em + .75rem + 2px);
    padding: .375rem .75rem;
    font-size: 0.875rem;
    border-color: #dee2e6;
}

.select2-container--bootstrap-5 .select2-selection--single:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.select2-dropdown {
    border-radius: 0.5rem;
    border-color: #dee2e6;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.select2-search--dropdown .select2-search__field {
    border-radius: 0.375rem;
    border-color: #dee2e6;
    padding: 0.5rem;
}

.select2-results__option {
    padding: 0.5rem 1rem;
    transition: all 0.15s ease;
}

.select2-results__option--highlighted {
    background-color: var(--primary-color) !important;
    color: white !important;
}

/* Enhanced form styling */
.form-control:focus,
.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Badge improvements */
.badge {
    font-weight: 500;
}

/* Card hover effects */
.card:hover {
    transform: translateY(-2px);
    transition: all 0.15s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .card-title {
        font-size: 0.9rem;
    }

    .page-title {
        font-size: 1.25rem;
    }

    .form-label {
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
}

@media (max-width: 576px) {
    .badge.small {
        font-size: 0.6rem;
        padding: 0.2rem 0.4rem;
    }

    .card-body {
        padding: 0.75rem !important;
    }

    .btn-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}

/* Filter responsive improvements */
@media (max-width: 991px) {
    .col-lg-3 {
        margin-bottom: 1rem;
    }
}

@media (max-width: 767px) {
    .col-md-6:not(:last-child) {
        margin-bottom: 1rem;
    }

    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem !important;
    }

    .flex-fill {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 with enhanced focus
    function initSelect2() {
        document.querySelectorAll('.select2-searchable').forEach(function(element) {
            if (element.nextElementSibling && element.nextElementSibling.classList.contains('select2')) {
                return; // Already initialized
            }

            const placeholder = element.dataset.placeholder || 'Select option...';

            // Create Select2 instance
            $(element).select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: placeholder,
                width: '100%',
                dropdownAutoWidth: false,
                language: {
                    noResults: function() {
                        return "No results found";
                    },
                    searching: function() {
                        return "Searching...";
                    }
                },
                escapeMarkup: function(markup) {
                    return markup;
                }
            });

            // Enhanced focus on search input when dropdown opens
            $(element).on('select2:open', function() {
                setTimeout(function() {
                    const searchField = document.querySelector('.select2-search__field');
                    if (searchField) {
                        searchField.focus();
                        searchField.select(); // Select any existing text
                    }
                }, 50);
            });
        });
    }

    // Initialize on page load
    if (typeof $ !== 'undefined' && $.fn.select2) {
        initSelect2();
    } else {
        // Wait for jQuery and Select2 to load
        const checkLibraries = setInterval(function() {
            if (typeof $ !== 'undefined' && $.fn.select2) {
                clearInterval(checkLibraries);
                initSelect2();
            }
        }, 100);
    }

    // Enhanced form submission handling
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;

                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Filtering...';
                submitBtn.disabled = true;

                // Re-enable after 3 seconds as fallback
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    });

    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
            if (bootstrap.Alert) {
                const alertInstance = bootstrap.Alert.getOrCreateInstance(alert);
                alertInstance.close();
            }
        });
    }, 5000);

    console.log('✅ Academic Sections page initialized with enhanced filtering and responsive design');
});
</script>

<!-- Required Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection
