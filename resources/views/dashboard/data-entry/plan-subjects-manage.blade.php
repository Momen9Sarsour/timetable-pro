@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <a href="{{ route('data-entry.plans.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>
                            <span class="d-none d-sm-inline">Back to Plans</span>
                        </a>
                        <span class="text-muted">|</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $plan->plan_no }} - {{ $plan->plan_name }}</span>
                    </div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-tasks text-primary me-2"></i>
                        Manage Plan Subjects
                    </h4>
                    <p class="text-muted mb-0">
                        <strong class="text-primary">{{ $plan->plan_name }}</strong>
                        - Add and organize subjects by level and semester
                    </p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#importPlanSubjectsModal">
                        <i class="fas fa-file-excel me-1"></i>
                        <span class="d-none d-sm-inline">Import Subjects</span>
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
                <strong class="d-block mb-1">Skipped Rows During Upload:</strong>
                <ul class="mb-0 small">
                    @foreach (session('skipped_details') as $detail)
                        <li>{{ $detail }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @php
        // Prepare Data
        $planSubjectsGrouped = $plan
            ->planSubjectEntries()
            ->with('subject:id,subject_no,subject_name')
            ->get()
            ->groupBy(['plan_level', 'plan_semester']);
        $addedSubjectIds = $plan->planSubjectEntries()->pluck('subject_id')->toArray();
        $allSubjects =
            $allSubjects ??
            \App\Models\Subject::orderBy('subject_name')->get(['id', 'subject_no', 'subject_name']);
        $maxLevelToDisplay = max(4, $planSubjectsGrouped->keys()->max() ?: 1);
        $maxSemesterToDisplay = max(
            2,
            $planSubjectsGrouped->map(fn($level) => is_iterable($level) ? $level->keys()->max() : 0)->max() ?:
            1,
        );
    @endphp

    <!-- Plan Overview Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="row text-center">
                        <div class="col-6 col-md-3">
                            <div class="text-primary fw-bold fs-4">{{ $plan->planSubjectEntries()->count() }}</div>
                            <small class="text-muted">Total Subjects</small>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-success fw-bold fs-4">{{ $plan->plan_hours }}</div>
                            <small class="text-muted">Credit Hours</small>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-info fw-bold fs-4">{{ $maxLevelToDisplay }}</div>
                            <small class="text-muted">Academic Levels</small>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-warning fw-bold fs-4">{{ $maxSemesterToDisplay }}</div>
                            <small class="text-muted">Semesters/Level</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Levels Accordion -->
    <div class="accordion" id="planLevelsAccordion">
        @for ($level = 1; $level <= $maxLevelToDisplay; $level++)
            <div class="accordion-item mb-3 border-0 shadow-sm">
                <h2 class="accordion-header" id="headingLevel{{ $level }}">
                    <button class="accordion-button {{ $level > 1 ? 'collapsed' : '' }} fw-medium"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapseLevel{{ $level }}"
                            aria-expanded="{{ $level == 1 ? 'true' : 'false' }}"
                            aria-controls="collapseLevel{{ $level }}">
                        <i class="fas fa-layer-group text-primary me-2"></i>
                        Level {{ $level }}
                        <span class="badge bg-primary bg-opacity-20 text-secondary ms-2">
                            {{ collect($planSubjectsGrouped[$level] ?? [])->flatten()->count() }} subjects
                        </span>
                    </button>
                </h2>
                <div id="collapseLevel{{ $level }}"
                     class="accordion-collapse collapse {{ $level == 1 ? 'show' : '' }}"
                     aria-labelledby="headingLevel{{ $level }}"
                     data-bs-parent="#planLevelsAccordion">
                    <div class="accordion-body p-4">
                        <div class="row g-3">
                            @for ($semester = 1; $semester <= $maxSemesterToDisplay; $semester++)
                                <div class="col-lg-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-header bg-light border-0 py-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0 fw-medium">
                                                    <i class="fas fa-calendar text-muted me-2"></i>
                                                    Semester {{ $semester }}
                                                </h6>
                                                <span class="badge bg-secondary bg-opacity-20 text-info">
                                                    {{ count($planSubjectsGrouped[$level][$semester] ?? []) }} subjects
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-body p-3">
                                            <!-- Subjects List -->
                                            <div class="subjects-list mb-3">
                                                @forelse($planSubjectsGrouped[$level][$semester] ?? [] as $planSubject)
                                                    <div class="d-flex justify-content-between align-items-center py-2 px-3 mb-2 bg-light rounded">
                                                        <div class="flex-grow-1">
                                                            <span class="fw-medium text-primary small">
                                                                {{ optional($planSubject->subject)->subject_no ?? '???' }}
                                                            </span> -
                                                            <span class="text-muted small" title="{{ optional($planSubject->subject)->subject_name ?? 'N/A' }}">
                                                                {{ Str::limit(optional($planSubject->subject)->subject_name ?? 'N/A', 35) }}
                                                            </span>
                                                        </div>
                                                        <form action="{{ route('data-entry.plans.removeSubject', ['plan' => $plan->id, 'planSubject' => $planSubject->id]) }}"
                                                              method="POST"
                                                              onsubmit="return confirm('Are you sure you want to remove this subject?');"
                                                              style="display: inline;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="btn btn-outline-danger btn-sm"
                                                                    title="Remove Subject">
                                                                <i class="fas fa-times fa-xs"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @empty
                                                    <div class="text-center text-muted py-4">
                                                        <i class="fas fa-book-open opacity-50 mb-2" style="font-size: 2rem;"></i>
                                                        <p class="small mb-0">No subjects added yet</p>
                                                    </div>
                                                @endforelse
                                            </div>

                                            <!-- Add Subject Button -->
                                            <button class="btn btn-outline-primary btn-sm w-100 toggle-add-form-btn mb-3">
                                                <i class="fas fa-plus me-1"></i>Add Subject
                                            </button>

                                            <!-- Add Subject Form -->
                                            <div class="add-subject-form border rounded p-3 bg-light" style="display: none;">
                                                <form action="{{ route('data-entry.plans.addSubject', ['plan' => $plan->id, 'level' => $level, 'semester' => $semester]) }}"
                                                      method="POST">
                                                    @csrf
                                                    <div class="mb-3">
                                                        <label for="subject_id_{{ $level }}_{{ $semester }}" class="form-label small fw-medium">
                                                            <i class="fas fa-search text-muted me-1"></i>Select Subject
                                                        </label>

                                                        <!-- Custom Searchable Dropdown -->
                                                        <div class="dropdown filterable-select">
                                                            <button class="form-select form-select-sm @error('subject_id') is-invalid @enderror w-100 text-start"
                                                                    type="button"
                                                                    id="subject_id_{{ $level }}_{{ $semester }}"
                                                                    data-bs-toggle="dropdown"
                                                                    aria-expanded="false">
                                                                <span id="selectedOptionText_{{ $level }}_{{ $semester }}">
                                                                    <i class="fas fa-search text-muted me-2"></i>Search and select subject...
                                                                </span>
                                                            </button>

                                                            <ul class="dropdown-menu w-100 shadow border-0"
                                                                aria-labelledby="subject_id_{{ $level }}_{{ $semester }}">
                                                                <li class="px-3 pt-2">
                                                                    <div class="input-group input-group-sm">
                                                                        <span class="input-group-text border-0 bg-light">
                                                                            <i class="fas fa-search text-muted"></i>
                                                                        </span>
                                                                        <input type="search"
                                                                               class="form-control form-control-sm border-0 bg-light"
                                                                               id="selectSearchInput_{{ $level }}_{{ $semester }}"
                                                                               placeholder="Type to search subjects..."
                                                                               autocomplete="off">
                                                                    </div>
                                                                </li>
                                                                <li><hr class="dropdown-divider my-2"></li>

                                                                <div id="optionsListContainer_{{ $level }}_{{ $semester }}"
                                                                     style="max-height: 250px; overflow-y: auto;">
                                                                    @foreach ($allSubjects as $subject)
                                                                        @if (!in_array($subject->id, $addedSubjectIds ?? []))
                                                                            <li>
                                                                                <a href="#" class="text-decoration-none">
                                                                                    <span class="dropdown-item filterable-option py-2"
                                                                                          data-value="{{ $subject->id }}">
                                                                                        <div class="d-flex align-items-center">
                                                                                            <span class="badge bg-light text-dark me-2 font-monospace small">
                                                                                                {{ $subject->subject_no }}
                                                                                            </span>
                                                                                            <span class="flex-grow-1">{{ $subject->subject_name }}</span>
                                                                                        </div>
                                                                                    </span>
                                                                                </a>
                                                                            </li>
                                                                        @endif
                                                                    @endforeach
                                                                </div>

                                                                <li class="px-3 py-2">
                                                                    <span class="text-muted small d-none" id="noResultsMessage_{{ $level }}_{{ $semester }}">
                                                                        <i class="fas fa-search me-1"></i>No subjects found
                                                                    </span>
                                                                </li>
                                                            </ul>

                                                            <!-- Hidden input to send value -->
                                                            <input type="hidden" name="subject_id" id="selectedValueInput_{{ $level }}_{{ $semester }}">
                                                        </div>

                                                        @error('subject_id')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>

                                                    <div class="d-flex justify-content-end gap-2">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm cancel-add-btn">
                                                            <i class="fas fa-times me-1"></i>Cancel
                                                        </button>
                                                        <button type="submit" class="btn btn-success btn-sm">
                                                            <i class="fas fa-check me-1"></i>Add Subject
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        @endfor
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importPlanSubjectsModal" tabindex="-1" aria-labelledby="importPlanSubjectsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title d-flex align-items-center" id="importPlanSubjectsModalLabel">
                        <i class="fas fa-file-excel me-2"></i>
                        Import Subjects for Plan: {{ $plan->plan_no }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="{{ route('data-entry.plans.importSubjectsExcel', $plan->id) }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row g-4">
                            <!-- File Upload Section -->
                            <div class="col-12">
                                <label for="plan_subjects_excel_file_input" class="form-label fw-medium">
                                    <i class="fas fa-cloud-upload-alt text-muted me-1"></i>
                                    Select Excel File <span class="text-danger">*</span>
                                </label>
                                <div class="upload-zone border border-2 border-dashed rounded-3 p-4 text-center cursor-pointer">
                                    <i class="fas fa-file-excel text-success mb-2" style="font-size: 2rem;"></i>
                                    <input class="form-control @error('plan_subjects_excel_file') is-invalid @enderror"
                                           type="file"
                                           id="plan_subjects_excel_file_input"
                                           name="plan_subjects_excel_file"
                                           accept=".xlsx,.xls,.csv"
                                           required>
                                    @error('plan_subjects_excel_file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text mt-2">
                                        <small>Supported formats: .xlsx, .xls, .csv (Max: 5MB)</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Instructions -->
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
                                                <span>Required headers: <code>plan_id</code>, <code>plan_level</code>, <code>plan_semester</code>, <code>subject_id</code></span>
                                            </li>
                                            <li class="d-flex align-items-start mb-2">
                                                <i class="fas fa-info-circle text-info me-2 mt-1 flex-shrink-0"></i>
                                                <span>Subject matching: by ID, Code, or Name (case-insensitive)</span>
                                            </li>
                                            <li class="d-flex align-items-start mb-2">
                                                <i class="fas fa-sync-alt text-warning me-2 mt-1 flex-shrink-0"></i>
                                                <span>Level/Semester conversion: "First", "1", "سنة أولى" → 1</span>
                                            </li>
                                            <li class="d-flex align-items-start">
                                                <i class="fas fa-filter text-secondary me-2 mt-1 flex-shrink-0"></i>
                                                <span>Duplicates and unmatched rows will be skipped</span>
                                            </li>
                                        </ul>
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

/* Searchable Dropdown Enhancements */
.filterable-select .dropdown-menu {
    border-radius: 0.5rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.filterable-select .dropdown-item {
    transition: all 0.15s ease;
    border-radius: 0.25rem;
    margin: 0 0.5rem;
}

.filterable-select .dropdown-item:hover,
.filterable-select .dropdown-item.active {
    background-color: var(--primary-color);
    color: white;
    transform: translateX(2px);
}

.filterable-select .dropdown-item:hover .badge,
.filterable-select .dropdown-item.active .badge {
    background-color: rgba(255, 255, 255, 0.2) !important;
    color: white !important;
}

/* Subject Cards */
.subjects-list .bg-light {
    transition: all 0.15s ease;
    border: 1px solid transparent;
}

.subjects-list .bg-light:hover {
    background-color: rgba(59, 130, 246, 0.05) !important;
    border-color: var(--primary-color);
    transform: translateX(2px);
}

/* Add Form Animation */
.add-subject-form {
    transition: all 0.3s ease;
    opacity: 0;
    transform: translateY(-10px);
}

.add-subject-form.show {
    opacity: 1;
    transform: translateY(0);
    display: block !important;
}

/* Accordion Enhancements */
.accordion-button {
    border-radius: 0.5rem !important;
    border: none !important;
}

.accordion-button:not(.collapsed) {
    background-color: rgba(59, 130, 246, 0.05);
    color: var(--primary-color);
}

.accordion-button:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Responsive Improvements */
@media (max-width: 768px) {
    .accordion-button {
        font-size: 0.875rem;
        padding: 0.75rem;
    }

    .card-body {
        padding: 1rem !important;
    }

    .subjects-list .bg-light {
        padding: 0.75rem !important;
    }
}

@media (max-width: 576px) {
    .filterable-select .dropdown-menu {
        max-width: 95vw;
    }

    .upload-zone {
        padding: 2rem 1rem !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced searchable dropdown functionality
    function initializeSearchableDropdown(level, semester) {
        const elements = {
            searchInput: document.getElementById(`selectSearchInput_${level}_${semester}`),
            optionsContainer: document.getElementById(`optionsListContainer_${level}_${semester}`),
            selectedText: document.getElementById(`selectedOptionText_${level}_${semester}`),
            selectedValue: document.getElementById(`selectedValueInput_${level}_${semester}`),
            dropdownButton: document.getElementById(`subject_id_${level}_${semester}`),
            noResultsMessage: document.getElementById(`noResultsMessage_${level}_${semester}`)
        };

        if (!elements.searchInput) return; // Skip if elements don't exist

        let currentFocus = -1;
        let visibleOptions = [];

        // Events
        elements.dropdownButton.addEventListener('shown.bs.dropdown', () => {
            setTimeout(() => elements.searchInput.focus(), 100);
            currentFocus = -1;
            updateOptions();
        });

        elements.searchInput.addEventListener('input', e => {
            updateOptions(e.target.value.toLowerCase());
            currentFocus = visibleOptions.length ? 0 : -1;
            highlightOption();
        });

        elements.searchInput.addEventListener('keydown', e => {
            const actions = {
                'ArrowDown': () => move(1),
                'ArrowUp': () => move(-1),
                'Enter': () => selectItem(),
                'Escape': () => bootstrap.Dropdown.getInstance(elements.dropdownButton).hide()
            };
            if (actions[e.key]) {
                e.preventDefault();
                actions[e.key]();
            }
        });

        elements.optionsContainer.addEventListener('click', e => {
            if (e.target.closest('.filterable-option')) {
                selectItem(e.target.closest('.filterable-option'));
            }
        });

        // Helper functions
        function updateOptions(filter = '') {
            visibleOptions = [];
            elements.optionsContainer.querySelectorAll('.filterable-option').forEach(option => {
                const show = option.textContent.toLowerCase().includes(filter);
                option.closest('li').classList.toggle('d-none', !show);
                if (show) visibleOptions.push(option);
            });

            elements.noResultsMessage.classList.toggle('d-none', visibleOptions.length > 0);
        }

        function move(direction) {
            if (!visibleOptions.length) return;

            // Remove previous highlight
            if (currentFocus >= 0) {
                visibleOptions[currentFocus].classList.remove('active');
            }

            currentFocus = Math.max(0, Math.min(visibleOptions.length - 1, currentFocus + direction));
            highlightOption();
        }

        function highlightOption() {
            visibleOptions.forEach(o => o.classList.remove('active'));
            if (currentFocus >= 0 && visibleOptions[currentFocus]) {
                visibleOptions[currentFocus].classList.add('active');
                visibleOptions[currentFocus].scrollIntoView({
                    block: 'nearest',
                    behavior: 'smooth'
                });
            }
        }

        function selectItem(element = visibleOptions[currentFocus]) {
            if (!element) return;

            elements.selectedText.innerHTML = `<i class="fas fa-check text-success me-2"></i>${element.textContent}`;
            elements.selectedValue.value = element.dataset.value;
            elements.searchInput.value = '';

            const dropdownInstance = bootstrap.Dropdown.getInstance(elements.dropdownButton);
            if (dropdownInstance) dropdownInstance.hide();

            updateOptions(); // Reset filter
        }

        // Prevent dropdown close on search input click
        elements.searchInput.addEventListener('click', e => e.stopPropagation());
    }

    // Initialize all searchable dropdowns
    @for ($level = 1; $level <= $maxLevelToDisplay; $level++)
        @for ($semester = 1; $semester <= $maxSemesterToDisplay; $semester++)
            initializeSearchableDropdown({{ $level }}, {{ $semester }});
        @endfor
    @endfor

    // Toggle add form functionality
    document.querySelectorAll('.toggle-add-form-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const form = this.nextElementSibling;
            const isVisible = form.style.display !== 'none';

            if (isVisible) {
                form.style.display = 'none';
                form.classList.remove('show');
                this.innerHTML = '<i class="fas fa-plus me-1"></i>Add Subject';
            } else {
                form.style.display = 'block';
                setTimeout(() => form.classList.add('show'), 10);
                this.innerHTML = '<i class="fas fa-minus me-1"></i>Cancel';
            }
        });
    });

    // Cancel add form
    document.querySelectorAll('.cancel-add-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const form = this.closest('.add-subject-form');
            const toggleBtn = form.previousElementSibling;

            form.style.display = 'none';
            form.classList.remove('show');
            toggleBtn.innerHTML = '<i class="fas fa-plus me-1"></i>Add Subject';

            // Reset form
            const select = form.querySelector('[name="subject_id"]');
            const selectedText = form.querySelector('[id^="selectedOptionText"]');
            if (select) select.value = '';
            if (selectedText) selectedText.innerHTML = '<i class="fas fa-search text-muted me-2"></i>Search and select subject...';
        });
    });

    // File upload enhancement
    const fileInput = document.getElementById('plan_subjects_excel_file_input');
    const uploadZone = document.querySelector('.upload-zone');

    if (fileInput && uploadZone) {
        uploadZone.addEventListener('click', () => fileInput.click());

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

        // Drag & Drop
        ['dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                if (eventName === 'dragover') {
                    this.style.borderColor = '#3b82f6';
                    this.style.backgroundColor = 'rgba(59, 130, 246, 0.05)';
                } else if (eventName === 'dragleave') {
                    this.style.borderColor = '';
                    this.style.backgroundColor = '';
                } else if (eventName === 'drop') {
                    this.style.borderColor = '';
                    this.style.backgroundColor = '';

                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        fileInput.files = files;
                        const event = new Event('change', { bubbles: true });
                        fileInput.dispatchEvent(event);
                    }
                }
            });
        });
    }

    // Enhanced form animations
    document.querySelectorAll('.add-subject-form').forEach(form => {
        form.style.display = 'none';
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
            const alertInstance = bootstrap.Alert.getOrCreateInstance(alert);
            alertInstance.close();
        });
    }, 5000);

    console.log('✅ Plan Subjects Management initialized successfully');
});
</script>
@endsection
