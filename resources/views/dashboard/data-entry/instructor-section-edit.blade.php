@extends('dashboard.layout')

@push('styles')
    <style>
        .subject-assignment-container {
            margin-bottom: 1.5rem;
            padding: 1rem;
            border: 1px solid #eee;
            border-radius: .25rem;
            background-color: #f9f9f9;
        }

        .subject-assignment-container h5 {
            border-bottom: 1px solid #ddd;
            padding-bottom: .5rem;
            margin-bottom: .75rem;
            font-size: 1.1rem;
        }

        .section-list {
            list-style: none;
            padding-left: 0;
        }

        .section-list li {
            margin-bottom: .25rem;
            display: flex;
            align-items: center;
        }

        .section-list .form-check-input {
            margin-top: .1em;
            margin-right: .5em;
        }

        /* RTL: margin-left */
        .section-info {
            font-size: 0.85rem;
            color: #6c757d;
            margin-left: auto;
            /* Push to the right */
        }

        .section-list .form-check-label {
            cursor: pointer;
        }

        #subjectSearch {
            margin-bottom: 1rem;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="data-entry-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="data-entry-header mb-1">Assign Sections to Instructor</h1>
                    <h2 class="h5 text-muted">Instructor: <span
                            class="text-primary">{{ $instructor->instructor_name ?? optional($instructor->user)->name }}</span>
                        ({{ $instructor->instructor_no }})</h2>
                    <p class="mb-0 text-secondary small">Department:
                        {{ optional($instructor->department)->department_name ?? 'N/A' }}</p>
                </div>
                <a href="{{ route('data-entry.instructor-section.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Instructors List
                </a>
            </div>

            @include('dashboard.data-entry.partials._status_messages')

            <form action="{{ route('data-entry.instructor-section.sync', $instructor->id) }}" method="POST">
                @csrf
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">Select Subjects and Their Sections</h5>
                    </div>
                    <div class="card-body">
                        {{-- حقل البحث عن المواد --}}
                        <div class="mb-3">
                            <input type="text" id="subjectSearch" class="form-control form-control-sm"
                                placeholder="Search subjects by name or code...">
                        </div>

                        {{-- <div id="subjectsListContainer" style="max-height: 70vh; overflow-y: auto; padding-right: 5px;">
                        @php $displayedSubjectsCount = 0; @endphp
                        @forelse ($allSubjectsWithSections as $subject)
                            @php
                                // فلترة الشعب المتاحة (التي لم يأخذها مدرس آخر أو أخذها المدرس الحالي)
                                $availableSectionsForThisSubject = collect();
                                if ($subject->planSubjectEntries->isNotEmpty()) {
                                    foreach($subject->planSubjectEntries as $planSubEntry) {
                                        foreach($planSubEntry->sections as $section) {
                                            // اعرض الشعبة إذا:
                                            // 1. لم يتم تعيينها لأي مدرس بعد (instructors->isEmpty())
                                            // 2. أو تم تعيينها لهذا المدرس المحدد (isAssignedToThisInstructor)
                                            $isAssignedToThisInstructor = in_array($section->id, $assignedSectionIds);
                                            $assignedToOtherInstructor = !$section->instructors->isEmpty() && !$isAssignedToThisInstructor;

                                            if (!$assignedToOtherInstructor) {
                                                $section->is_assigned_to_current = $isAssignedToThisInstructor;
                                                $section->subject_name_display = $subject->subject_name; // إضافة اسم المادة للشعبة
                                                $section->subject_no_display = $subject->subject_no;
                                                $availableSectionsForThisSubject->push($section);
                                            }
                                        }
                                    }
                                }
                            @endphp

                            {{-- إذا لم تكن هناك أي شعب متاحة لهذه المادة، لا تعرض المادة أصلاً --}}
                        {{-- @if ($availableSectionsForThisSubject->isNotEmpty())
                                @php $displayedSubjectsCount++; @endphp
                                <div class="subject-assignment-container mb-3" data-subject-name="{{ strtolower($subject->subject_name) }}" data-subject-code="{{ strtolower($subject->subject_no) }}">
                                    <h5>
                                        <div class="form-check">
                                            <input class="form-check-input subject-master-checkbox" type="checkbox" value="{{ $subject->id }}" id="subject_{{ $subject->id }}_master_checkbox">
                                            <label class="form-check-label" for="subject_{{ $subject->id }}_master_checkbox">
                                                {{ $subject->subject_no }} - {{ $subject->subject_name }}
                                                <small class="text-muted">({{ optional($subject->subjectCategory)->subject_category_name }})</small>
                                            </label>
                                        </div>
                                    </h5>
                                    <ul class="section-list ps-4"> {{-- padding start لمسافة بادئة --}}
                        {{--   @foreach ($availableSectionsForThisSubject as $section)
                                            <li>
                                                <div class="form-check">
                                                    <input class="form-check-input section-checkbox" type="checkbox"
                                                           name="section_ids[]"
                                                           value="{{ $section->id }}"
                                                           id="section_{{ $section->id }}"
                                                           data-subject-master-id="{{ $subject->id }}"
                                                           {{ $section->is_assigned_to_current ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="section_{{ $section->id }}">
                                                        Section #{{ $section->section_number }} ({{ $section->activity_type }})
                                                        <span class="section-info ms-2">
                                                            - {{ $section->student_count }} students
                                                            @if ($section->instructors->isNotEmpty() && !$section->is_assigned_to_current)
                                                                {{-- هذا الشرط لن يتحقق بسبب الفلترة أعلاه، لكن نتركه للحماية --}}
                        {{--                         <span class="badge bg-warning text-dark">Assigned to: {{ $section->instructors->first()->instructor_name ?? 'Other' }}</span>
                                                            @endif
                                                        </span>
                                                    </label>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @empty
                            <p class="text-muted">No subjects available in the system.</p>
                        @endforelse

                        @if ($displayedSubjectsCount === 0 && $allSubjectsWithSections->isNotEmpty())
                             <p class="text-muted text-center" id="noAvailableSubjectsMessage">All subjects/sections are either fully assigned or have no available sections for this instructor.</p>
                        @elseif(!$allSubjectsWithSections->isNotEmpty())
                             <p class="text-muted text-center" id="noSubjectsSystemMessage">No subjects found in the system to assign.</p>
                        @endif
                         <p class="text-muted text-center mt-2" id="noAssignmentResultsFilter" style="display: none;">No subjects/sections match your search criteria.</p>
                    </div> --}}
                        <div id="subjectsListContainer" style="max-height: 50vh; overflow-y: auto; padding-right: 5px;">
                            @php $displayedSubjectsCount = 0; @endphp
                            @forelse ($allSubjectsWithSections as $subject)
                                @php
                                    $sectionsForThisSubject = collect(); // كل شعب المادة
                                    $allSectionsAreTakenByOthers = true; // افتراض أن كل الشعب مأخوذة من آخرين
                                    $atLeastOneSectionIsAvailableOrAssignedToCurrent = false; // لتتبع إذا كانت هناك شعبة يمكن للمدرس الحالي التفاعل معها

                                    if ($subject->planSubjectEntries->isNotEmpty()) {
                                        foreach ($subject->planSubjectEntries as $planSubEntry) {
                                            foreach ($planSubEntry->sections as $section) {
                                                $isAssignedToCurrentInstructor = in_array(
                                                    $section->id,
                                                    $assignedSectionIds ?? [],
                                                );
                                                $assignedToOtherInstructor = false;
                                                $assignedInstructorName = null;

                                                if (!$section->instructors->isEmpty()) {
                                                    // إذا كانت الشعبة معينة لمدرس ما
                                                    if (!$isAssignedToCurrentInstructor) {
                                                        // معينة لمدرس آخر
                                                        $assignedToOtherInstructor = true;
                                                        $assignedInstructorName =
                                                            optional($section->instructors->first())->instructor_name ??
                                                            optional(optional($section->instructors->first())->user)
                                                                ->name;
                                                    }
                                                    // إذا كانت معينة للمدرس الحالي، isAssignedToCurrentInstructor ستكون true
                                                }

                                                // إضافة معلومات إضافية للشعبة للعرض
                                                $section->is_assigned_to_current_instructor = $isAssignedToCurrentInstructor;
                                                $section->assigned_instructor_name_for_display = $assignedToOtherInstructor
                                                    ? $assignedInstructorName
                                                    : null;
                                                $section->is_disabled_for_current_instructor = $assignedToOtherInstructor;

                                                $sectionsForThisSubject->push($section); // أضف كل شعب المادة

                                                // التحقق إذا كانت هناك شعبة واحدة على الأقل متاحة أو معينة للمدرس الحالي
                                                if (!$section->is_disabled_for_current_instructor) {
                                                    $atLeastOneSectionIsAvailableOrAssignedToCurrent = true;
                                                }
                                            }
                                        }
                                    }

                                    // **الشرط الجديد: لا تعرض المادة إذا لم يكن هناك أي شعبة متاحة أو معينة للمدرس الحالي**
                                    if (
                                        !$atLeastOneSectionIsAvailableOrAssignedToCurrent &&
                                        $sectionsForThisSubject->isNotEmpty()
                                    ) {
                                        // إذا كانت كل الشعب مأخوذة من قبل مدرسين آخرين، تخطى عرض هذه المادة
                                        continue;
                                    }
                                @endphp

                                {{-- اعرض المادة فقط إذا كان لها شعب أصلاً أو إذا كانت هناك شعب متاحة/معينة للمدرس الحالي --}}
                                @if ($sectionsForThisSubject->isNotEmpty())
                                    @php $displayedSubjectsCount++; @endphp
                                    <div class="subject-assignment-container mb-3"
                                        data-subject-name="{{ strtolower($subject->subject_name) }}"
                                        data-subject-code="{{ strtolower($subject->subject_no) }}">
                                        <h5>
                                            <div class="form-check">
                                                <input class="form-check-input subject-master-checkbox" type="checkbox"
                                                    value="{{ $subject->id }}"
                                                    id="subject_{{ $subject->id }}_master_checkbox" {{-- تعطيل الماستر إذا لم تكن هناك شعب متاحة --}}
                                                    {{ !$sectionsForThisSubject->contains(fn($sec) => !$sec->is_disabled_for_current_instructor) ? 'disabled' : '' }}>
                                                <label class="form-check-label"
                                                    for="subject_{{ $subject->id }}_master_checkbox">
                                                    {{ $subject->subject_no }} - {{ $subject->subject_name }}
                                                    <small
                                                        class="text-muted">({{ optional($subject->subjectCategory)->subject_category_name }})</small>
                                                </label>
                                            </div>
                                        </h5>
                                        <ul class="section-list ps-4">
                                            @foreach ($sectionsForThisSubject->sortBy(['activity_type', 'section_number']) as $section)
                                                <li>
                                                    <div class="form-check">
                                                        <input class="form-check-input section-checkbox" type="checkbox"
                                                            name="section_ids[]" value="{{ $section->id }}"
                                                            id="section_{{ $section->id }}"
                                                            data-subject-master-id="{{ $subject->id }}"
                                                            {{ $section->is_assigned_to_current_instructor ? 'checked' : '' }}
                                                            {{ $section->is_disabled_for_current_instructor ? 'disabled' : '' }}>
                                                        <label class="form-check-label" for="section_{{ $section->id }}"
                                                            title="{{ $section->is_disabled_for_current_instructor ? 'Assigned to: ' . $section->assigned_instructor_name_for_display : '' }}">
                                                            Section #{{ $section->section_number }}
                                                            ({{ $section->activity_type }})
                                                            <span class="section-info ms-2">
                                                                - {{ $section->student_count }} students
                                                                @if ($section->assigned_instructor_name_for_display)
                                                                    <span class="badge bg-warning text-dark ms-1">Assigned:
                                                                        {{ Str::limit($section->assigned_instructor_name_for_display, 15) }}</span>
                                                                @endif
                                                            </span>
                                                        </label>
                                                    </div>
                                                </li>
                                            @endforeach
                                            @if ($sectionsForThisSubject->isEmpty())
                                                {{-- هذا الشرط لن يتحقق الآن بسبب الفلترة أعلاه --}}
                                                <li><small class="text-muted">No sections found for this subject.</small>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                @endif
                            @empty
                                <p class="text-muted">No subjects available in the system.</p>
                            @endforelse

                            @if ($displayedSubjectsCount === 0 && $allSubjectsWithSections->isNotEmpty())
                                <p class="text-muted text-center" id="noAvailableSubjectsMessage">All subjects/sections are
                                    either fully assigned or have no available sections for this instructor.</p>
                            @elseif(!$allSubjectsWithSections->isNotEmpty())
                                <p class="text-muted text-center" id="noSubjectsSystemMessage">No subjects found in the
                                    system to assign.</p>
                            @endif
                            <p class="text-muted text-center mt-2" id="noAssignmentResultsFilter" style="display: none;">No
                                subjects/sections match your search criteria.</p>
                        </div>
                        @error('section_ids')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('data-entry.instructor-section.index') }}"
                            class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Assignments
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // --- التركيز التلقائي على حقل البحث ---
            $('#subjectSearch').focus();

            // --- البحث في قائمة المواد ---
            $('#subjectSearch').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                let resultsFoundOverall = false;
                $('#subjectsListContainer .subject-assignment-container').each(function() {
                    const subjectContainer = $(this);
                    const subjectName = subjectContainer.data('subject-name');
                    const subjectCode = subjectContainer.data('subject-code');
                    let subjectMatches = false;

                    if (subjectName.includes(searchTerm) || subjectCode.includes(searchTerm)) {
                        subjectMatches = true;
                        subjectContainer.show();
                        resultsFoundOverall = true;
                    } else {
                        subjectContainer.hide();
                    }
                });
                $('#noAssignmentResultsFilter').toggle(!resultsFoundOverall);
            });

            // --- تحديد/إلغاء تحديد كل شعب المادة ---
            $(document).on('change', '.subject-master-checkbox', function() {
                const subjectId = $(this).val();
                const isChecked = $(this).is(':checked');
                // حدد فقط الـ checkboxes التابعة لهذه المادة والتي ليست disabled
                $('.section-checkbox[data-subject-master-id="' + subjectId + '"]:not(:disabled)').prop(
                    'checked', isChecked);
            });

            // --- تحديث الـ checkbox الرئيسي للمادة عند تغيير الشعب الفرعية ---
            $(document).on('change', '.section-checkbox', function() {
                const subjectId = $(this).data('subject-master-id');
                const allSectionsForSubject = $('.section-checkbox[data-subject-master-id="' + subjectId +
                    '"]:not(:disabled)');
                const checkedSectionsForSubject = $('.section-checkbox[data-subject-master-id="' +
                    subjectId + '"]:not(:disabled):checked');
                const masterCheckbox = $('#subject_' + subjectId + '_master_checkbox');

                if (checkedSectionsForSubject.length === allSectionsForSubject.length &&
                    allSectionsForSubject.length > 0) {
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

            // --- تهيئة حالة الـ checkbox الرئيسي للمادة عند تحميل الصفحة ---
            $('.subject-master-checkbox').each(function() {
                const subjectId = $(this).val();
                const allSectionsForSubject = $('.section-checkbox[data-subject-master-id="' + subjectId +
                    '"]:not(:disabled)');
                const checkedSectionsForSubject = $('.section-checkbox[data-subject-master-id="' +
                    subjectId + '"]:not(:disabled):checked');

                if (allSectionsForSubject.length > 0) { // فقط إذا كان هناك شعب متاحة
                    if (checkedSectionsForSubject.length === allSectionsForSubject.length) {
                        $(this).prop('checked', true);
                    } else if (checkedSectionsForSubject.length > 0) {
                        $(this).prop('indeterminate', true);
                    }
                } else {
                    // إذا لم تكن هناك شعب متاحة، عطل الـ checkbox الرئيسي
                    $(this).prop('disabled', true);
                }
            });

        });
    </script>
@endpush
