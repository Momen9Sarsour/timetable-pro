@extends('dashboard.layout')

{{-- Styles for Select2 --}}
@push('styles')
@endpush

@section('content')
    <div class="main-content">
        <div class="data-entry-container">
            {{-- Header and Back Button --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="data-entry-header mb-0">Manage Subjects for Plan: <span
                        class="text-primary">{{ $plan->plan_name }}</span> ({{ $plan->plan_no }})</h1>
                <a href="{{ route('data-entry.plans.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Plans List
                </a>
            </div>

            {{-- Status Messages --}}
            @include('dashboard.data-entry.partials._status_messages')

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

            {{-- Accordion for Levels --}}
            <div class="accordion" id="planLevelsAccordion">
                @for ($level = 1; $level <= $maxLevelToDisplay; $level++)
                    <div class="accordion-item mb-3 shadow-sm">
                        <h2 class="accordion-header" id="headingLevel{{ $level }}">
                            <button class="accordion-button {{ $level > 1 ? 'collapsed' : '' }}" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapseLevel{{ $level }}"
                                aria-expanded="{{ $level == 1 ? 'true' : 'false' }}"
                                aria-controls="collapseLevel{{ $level }}">
                                Level {{ $level }}
                            </button>
                        </h2>
                        <div id="collapseLevel{{ $level }}"
                            class="accordion-collapse collapse {{ $level == 1 ? 'show' : '' }}"
                            aria-labelledby="headingLevel{{ $level }}" data-bs-parent="#planLevelsAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    @for ($semester = 1; $semester <= $maxSemesterToDisplay; $semester++)
                                        <div class="col-lg-6 mb-3">
                                            <div class="card h-100">
                                                <div class="card-header">
                                                    <h6 class="mb-0">Semester {{ $semester }}</h6>
                                                </div>
                                                <div class="card-body p-3">
                                                    {{-- Display Added Subjects --}}
                                                    <ul class="list-group list-group-flush mb-3">
                                                        @forelse($planSubjectsGrouped[$level][$semester] ?? [] as $planSubject)
                                                            <li
                                                                class="list-group-item d-flex justify-content-between align-items-center py-1 px-0">
                                                                <span class="small"
                                                                    title="{{ optional($planSubject->subject)->subject_name ?? 'N/A' }}">
                                                                    <strong
                                                                        class="text-muted">{{ optional($planSubject->subject)->subject_no ?? '???' }}</strong>
                                                                    -
                                                                    {{ Str::limit(optional($planSubject->subject)->subject_name ?? 'N/A', 40) }}
                                                                </span>
                                                                {{-- *** فورم الحذف المباشر *** --}}
                                                                <form
                                                                    action="{{ route('data-entry.plans.removeSubject', ['plan' => $plan->id, 'planSubject' => $planSubject->id]) }}"
                                                                    method="POST"
                                                                    onsubmit="return confirm('Are you sure you want to remove this subject?');"
                                                                    style="display: inline;">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit"
                                                                        class="btn btn-sm btn-outline-danger border-0 p-0 px-1"
                                                                        title="Remove Subject">
                                                                        <i class="fas fa-times fa-xs"></i>
                                                                    </button>
                                                                </form>
                                                                {{-- *** نهاية فورم الحذف المباشر *** --}}
                                                            </li>
                                                        @empty
                                                            <li
                                                                class="list-group-item text-center text-muted small py-1 px-0 empty-list">
                                                                No subjects added yet.</li>
                                                        @endforelse
                                                    </ul>

                                                    {{-- زر إظهار/إخفاء فورم الإضافة --}}
                                                    <button
                                                        class="btn btn-sm btn-outline-primary w-100 toggle-add-form-btn mb-2">
                                                        <i class="fas fa-plus"></i> Add Subject
                                                    </button>

                                                    {{-- فورم الإضافة الخاص بهذا الفصل/المستوى --}}
                                                    <form
                                                        action="{{ route('data-entry.plans.addSubject', ['plan' => $plan->id, 'level' => $level, 'semester' => $semester]) }}"
                                                        method="POST" class="add-subject-form border p-3 rounded bg-light">
                                                        @csrf
                                                        {{-- <div class="mb-2">
                                                            <label
                                                                for="subject_id_{{ $level }}_{{ $semester }}"
                                                                class="form-label visually-hidden">Select Subject</label>
                                                            <select
                                                                class="form-select form-select-sm @error('subject_id') is-invalid @enderror"
                                                                id="subject_id_{{ $level }}_{{ $semester }}"
                                                                name="subject_id" required>
                                                                <option value="" selected hidden>-- Select Subject --</option>
                                                                @foreach ($allSubjects as $subject)
                                                                    @if (!in_array($subject->id, $addedSubjectIds ?? []))
                                                                        <option value="{{ $subject->id }}">
                                                                            {{ $subject->subject_no }} -
                                                                            {{ $subject->subject_name }}
                                                                        </option>
                                                                    @endif
                                                                @endforeach
                                                            </select>

                                                            @error('subject_id')
                                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                                            @enderror
                                                            @if ($errors->any() && !$errors->has('subject_id'))
                                                                <div class="text-danger small mt-1">Could not add subject.
                                                                    Please try again.</div>
                                                            @endif
                                                        </div> --}}

                                                        <div class="mb-2">
                                                            <label
                                                                for="subject_id_{{ $level }}_{{ $semester }}"
                                                                class="form-label visually-hidden">Select Subject</label>

                                                            <!-- Dropdown Component -->
                                                            <div class="dropdown filterable-select">
                                                                <button
                                                                    class="form-select form-select-sm @error('subject_id') is-invalid @enderror w-100 text-start"
                                                                    type="button"
                                                                    id="subject_id_{{ $level }}_{{ $semester }}"
                                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                                    <span
                                                                        id="selectedOptionText_{{ $level }}_{{ $semester }}">--
                                                                        Select Subject --</span>
                                                                </button>

                                                                <ul class="dropdown-menu w-100"
                                                                    aria-labelledby="subject_id_{{ $level }}_{{ $semester }}">
                                                                    <li class="px-2 pt-2">
                                                                        <input type="search"
                                                                            class="form-control form-control-sm"
                                                                            id="selectSearchInput_{{ $level }}_{{ $semester }}"
                                                                            placeholder="Search ..." autocomplete="off">
                                                                    </li>
                                                                    <li>
                                                                        <hr class="dropdown-divider">
                                                                    </li>

                                                                    <div id="optionsListContainer_{{ $level }}_{{ $semester }}"
                                                                        style="max-height: 200px; overflow-y: auto;">
                                                                        @foreach ($allSubjects as $subject)
                                                                            @if (!in_array($subject->id, $addedSubjectIds ?? []))
                                                                                <li><a href="#" class="text-decoration-none">
                                                                                        <span
                                                                                            class="dropdown-item filterable-option"
                                                                                            data-value="{{ $subject->id }}">
                                                                                            {{ $subject->subject_no }} -
                                                                                            {{ $subject->subject_name }}
                                                                                        </span>
                                                                                    </a>
                                                                                </li>
                                                                            @endif
                                                                        @endforeach
                                                                    </div>

                                                                    <li><span class="dropdown-item text-muted d-none"
                                                                            id="noResultsMessage_{{ $level }}_{{ $semester }}">No
                                                                            subject </span></li>
                                                                </ul>

                                                                <!-- Hidden input to send value -->
                                                                <input type="hidden" name="subject_id"
                                                                    id="selectedValueInput_{{ $level }}_{{ $semester }}">
                                                            </div>

                                                            @error('subject_id')
                                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                                            @enderror
                                                            @if ($errors->any() && !$errors->has('subject_id'))
                                                                <div class="text-danger small mt-1">Could not add subject.
                                                                    Please try again.</div>
                                                            @endif
                                                        </div>
                                                        <script>
                                                            document.addEventListener('DOMContentLoaded', () => {
                                                                const level = "{{ $level }}", semester = "{{ $semester }}";
                                                                const elements = {
                                                                    searchInput: document.getElementById(`selectSearchInput_${level}_${semester}`),
                                                                    optionsContainer: document.getElementById(`optionsListContainer_${level}_${semester}`),
                                                                    selectedText: document.getElementById(`selectedOptionText_${level}_${semester}`),
                                                                    selectedValue: document.getElementById(`selectedValueInput_${level}_${semester}`),
                                                                    dropdownButton: document.getElementById(`subject_id_${level}_${semester}`)
                                                                };

                                                                let currentFocus = -1, visibleOptions = [];

                                                                // الأحداث الرئيسية
                                                                elements.dropdownButton.addEventListener('shown.bs.dropdown', () => {
                                                                    elements.searchInput.focus();
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
                                                                    if (actions[e.key]) { e.preventDefault(); actions[e.key]() }
                                                                });

                                                                elements.optionsContainer.addEventListener('click', e => {
                                                                    if (e.target.classList.contains('filterable-option')) selectItem(e.target);
                                                                });

                                                                // الدوال المساعدة
                                                                function updateOptions(filter = '') {
                                                                    visibleOptions = [];
                                                                    elements.optionsContainer.querySelectorAll('.filterable-option').forEach(option => {
                                                                        const show = option.textContent.toLowerCase().includes(filter);
                                                                        option.closest('li').classList.toggle('d-none', !show);
                                                                        if (show) visibleOptions.push(option);
                                                                    });
                                                                }

                                                                function move(direction) {
                                                                    if (!visibleOptions.length) return;
                                                                    currentFocus = Math.max(0, Math.min(visibleOptions.length - 1, currentFocus + direction));
                                                                    highlightOption();
                                                                }

                                                                function highlightOption() {
                                                                    visibleOptions.forEach(o => o.classList.remove('active'));
                                                                    if (currentFocus > -1) {
                                                                        visibleOptions[currentFocus].classList.add('active');
                                                                        visibleOptions[currentFocus].scrollIntoView({block: 'nearest'});
                                                                    }
                                                                }

                                                                function selectItem(element = visibleOptions[currentFocus]) {
                                                                    if (!element) return;
                                                                    elements.selectedText.textContent = element.textContent;
                                                                    elements.selectedValue.value = element.dataset.value;
                                                                    elements.searchInput.value = '';
                                                                    bootstrap.Dropdown.getInstance(elements.dropdownButton).hide();
                                                                }
                                                            });
                                                            </script>
                                                        {{-- <script>
                                                            document.addEventListener('DOMContentLoaded', function() {
                                                                const level = "{{ $level }}";
                                                                const semester = "{{ $semester }}";
                                                                const searchInput = document.getElementById(`selectSearchInput_${level}_${semester}`);
                                                                const optionsContainer = document.getElementById(`optionsListContainer_${level}_${semester}`);
                                                                const selectedText = document.getElementById(`selectedOptionText_${level}_${semester}`);
                                                                const selectedValue = document.getElementById(`selectedValueInput_${level}_${semester}`);
                                                                const noResultsMessage = document.getElementById(`noResultsMessage_${level}_${semester}`);
                                                                const dropdownButton = document.getElementById(`subject_id_${level}_${semester}`);

                                                                dropdownButton.addEventListener('shown.bs.dropdown', function() {
                                                                    setTimeout(() => {
                                                                        searchInput.focus();
                                                                    }, 10);
                                                                });

                                                                searchInput.addEventListener('keyup', function() {
                                                                    const filter = searchInput.value.toLowerCase();
                                                                    let found = false;
                                                                    const options = optionsContainer.querySelectorAll('.filterable-option');

                                                                    options.forEach(option => {
                                                                        const text = option.textContent.toLowerCase();
                                                                        const li = option.closest('li');
                                                                        if (text.includes(filter)) {
                                                                            li.classList.remove('d-none');
                                                                            found = true;
                                                                        } else {
                                                                            li.classList.add('d-none');
                                                                        }
                                                                    });

                                                                    noResultsMessage.classList.toggle('d-none', found);
                                                                });

                                                                optionsContainer.addEventListener('click', function(event) {
                                                                    if (event.target.classList.contains('filterable-option')) {
                                                                        event.preventDefault();
                                                                        selectedText.textContent = event.target.textContent;
                                                                        selectedValue.value = event.target.getAttribute('data-value');
                                                                        const dropdownInstance = bootstrap.Dropdown.getInstance(dropdownButton);
                                                                        if (dropdownInstance) dropdownInstance.hide();
                                                                        searchInput.value = '';
                                                                        searchInput.dispatchEvent(new Event('keyup'));
                                                                    }
                                                                });

                                                                searchInput.addEventListener('click', e => e.stopPropagation());
                                                            });
                                                        </script> --}}


                                                        <div class="d-flex justify-content-end">
                                                            <button type="button"
                                                                class="btn btn-sm btn-secondary me-2 cancel-add-btn">Cancel</button>
                                                            <button type="submit" class="btn btn-sm btn-success"><i
                                                                    class="fas fa-check"></i> Save</button>
                                                        </div>
                                                    </form>
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
        </div>
    </div>
@endsection

@push('scripts')
@endpush
