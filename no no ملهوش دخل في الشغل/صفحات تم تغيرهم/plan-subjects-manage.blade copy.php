@extends('dashboard.layout')

{{-- Styles for Select2 --}}
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + .75rem);
        }

        .list-group-item.empty-list {
            background-color: #f8f9fa;
        }

        .add-subject-form {
            display: none;
            margin-top: 1rem;
        }

        .list-group-flush .list-group-item:last-child {
            margin-bottom: 0;
            border-bottom: 0;
        }
    </style>
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
                                                        <div class="mb-2">
                                                            <label
                                                                for="subject_id_{{ $level }}_{{ $semester }}"
                                                                class="form-label visually-hidden">Select Subject</label>
                                                            <select data-mdb-filter="true"
                                                                class="form-select form-select-sm select2-subjects @error('subject_id') is-invalid @enderror"
                                                                id="subject_id_{{ $level }}_{{ $semester }}"
                                                                name="subject_id" required style="width: 100%;"
                                                                data-placeholder="Search & Select subject...">
                                                                <option value="">-- Select Subject -- </option>
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
                                                        </div>

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
            </div> {{-- نهاية الأكورديون --}}

        </div> {{-- نهاية data-entry-container --}}
    </div> {{-- نهاية main-content --}}
@endsection

{{-- JavaScript (تم إزالة الجزء الخاص بمودال الحذف) --}}
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    {{-- <script>
        $(document).ready(function() {
            function initializeSelect2(selector) {
                try {
                    $(selector).select2({
                        theme: 'bootstrap-5',
                        width: '100%'
                    });
                } catch (e) {
                    console.error('Error initializing Select2:', e);
                }
            }
            $('.add-subject-form').each(function() {
                if ($(this).find('.is-invalid').length > 0 || $(this).find('.text-danger').length > 0) {
                    $(this).show();
                    $(this).prev('.toggle-add-form-btn').hide();
                    initializeSelect2($(this).find('.select2-subjects'));
                }
            });
            $('#planLevelsAccordion').on('click', '.toggle-add-form-btn', function() {
                const form = $(this).next('.add-subject-form');
                $('.add-subject-form').not(form).slideUp(150);
                $('.toggle-add-form-btn').not(this).show();
                form.slideToggle(150);
                $(this).toggle(!form.is(':visible'));
                if (form.is(':visible')) {
                    initializeSelect2(form.find('.select2-subjects'));
                    form.find('.select2-subjects').val(null).trigger('change');
                }
            });
            $('#planLevelsAccordion').on('click', '.cancel-add-btn', function() {
                const form = $(this).closest('.add-subject-form');
                const addButton = form.prev('.toggle-add-form-btn');
                form.slideUp(150);
                addButton.show();
            });
        });
    </script> --}}
@endpush
