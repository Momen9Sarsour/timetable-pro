@extends('dashboard.layout')

@push('styles')
    <style>
        .table th,
        .table td {
            vertical-align: middle;
            font-size: 0.85rem;
        }

        .modal-dialog-centered {
            display: flex;
            align-items: center;
            min-height: calc(100% - 1rem);
        }

        @media (min-width: 576px) {
            .modal-dialog-centered {
                min-height: calc(100% - 3.5rem);
            }
        }

        .card-body .table-responsive {
            margin-bottom: 0;
        }

        .card-header h5.mb-0 {
            font-size: 1.1rem;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="data-entry-container">
            {{-- 1. عرض معلومات السياق (نفس الكود السابق) --}}
            <div class="mb-4 p-3 border rounded bg-light shadow-sm">
                {{-- ... Header, Back Button, Context Info ... --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h4 class="data-entry-header mb-1">Manage Sections for Context</h4>
                        <p class="mb-1 text-muted small">Modifying sections for Plan:
                            <strong>{{ optional($expectedCount->plan)->plan_no }}</strong></p>
                    </div>
                    <a href="{{ route('data-entry.plan-expected-counts.index') }}"
                        class="btn btn-sm btn-outline-secondary align-self-start">
                        <i class="fas fa-arrow-left me-1"></i> Back to Expected Counts
                    </a>
                </div>
                <hr>
                <div class="row small">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Plan Name:</strong> {{ optional($expectedCount->plan)->plan_name }}</p>
                        <p class="mb-1"><strong>Department:</strong>
                            {{ optional(optional($expectedCount->plan)->department)->department_name }}</p>
                        <p class="mb-0"><strong>Academic Year:</strong> {{ $expectedCount->academic_year }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Level:</strong> {{ $expectedCount->plan_level }}</p>
                        <p class="mb-1"><strong>Semester (Plan):</strong> {{ $expectedCount->plan_semester }}</p>
                        <p class="mb-0"><strong>Branch:</strong> {{ $expectedCount->branch ?? 'Default' }}</p>
                    </div>
                </div>
                <div class="mt-2">
                    <p class="mb-0"><strong>Total Expected:</strong> <span
                            class="fw-bold fs-5">{{ $expectedCount->male_count + $expectedCount->female_count }}</span>
                        <small>({{ $expectedCount->male_count }} M, {{ $expectedCount->female_count }} F)</small></p>
                </div>
            </div>

            @include('dashboard.data-entry.partials._status_messages')

            {{-- 2. زر إنشاء/تحديث الشعب تلقائياً (نفس الكود السابق) --}}
            <div class="mb-3 text-end">
                @if ($expectedCount)
                    <form action="{{ route('data-entry.sections.generateForContext', $expectedCount->id) }}" method="POST"
                        class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning"
                            onclick="return confirm('ATTENTION: This will DELETE existing sections for ALL subjects in this context and REGENERATE them. Manual adjustments will be lost. Are you sure?');">
                            <i class="fas fa-cogs me-1"></i> Regenerate All Sections
                        </button>
                    </form>
                @endif
            </div>

            {{-- 3. عرض الشعب لكل مادة --}}
            @if (!isset($planSubjectsForContext) || $planSubjectsForContext->isEmpty())
                <div class="alert alert-info">No subjects found in this plan/level/semester.</div>
            @else
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

                    <h5 class="mt-4 mb-2 pb-2 border-bottom">
                        Subject: <span class="text-primary">{{ $subject->subject_no }} -
                            {{ $subject->subject_name }}</span>
                        <small class="badge bg-secondary fw-normal">{{ $subjectCategoryName }}</small>
                    </h5>

                    {{-- الجزء النظري --}}
                    @if (
                        ($subject->theoretical_hours ?? 0) > 0 &&
                            Str::contains($subjectCategoryName, ['theory', 'نظري', 'combined', 'مشترك']))
                        <div class="card shadow-sm mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center"
                                style="background-color: #e9ecef;">
                                <h6 class="mb-0 text-dark"><i class="fas fa-chalkboard me-2"></i>Theory Sections</h6>
                                <button class="btn btn-outline-success btn-sm open-add-section-modal" data-bs-toggle="modal"
                                    data-bs-target="#addSectionModal" data-plan-subject-id="{{ $ps->id }}"
                                    data-activity-type="Theory"
                                    data-subject-name-modal="{{ $subject->subject_name }} (Theory)">
                                    <i class="fas fa-plus me-1"></i> Add Theory Section
                                </button>
                            </div>
                            <div class="card-body p-0">
                                @if ($theorySections->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Sec.No.</th>
                                                    <th>Gender</th>
                                                    <th>Branch</th>
                                                    <th>Students</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($theorySections as $index => $section)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>{{ $section->section_number }}</td>
                                                        <td>{{ $section->section_gender }}</td>
                                                        <td>{{ $section->branch ?? '-' }}</td>
                                                        <td>{{ $section->student_count }}</td>
                                                        <td>
                                                            <button
                                                                class="btn btn-sm btn-outline-primary py-0 px-1 me-1 open-edit-section-modal"
                                                                data-bs-toggle="modal" data-bs-target="#editSectionModal"
                                                                data-section-id="{{ $section->id }}"
                                                                data-section-number="{{ $section->section_number }}"
                                                                data-student-count="{{ $section->student_count }}"
                                                                data-section-gender="{{ $section->section_gender }}"
                                                                data-branch="{{ $section->branch }}"
                                                                data-activity-type="{{ $section->activity_type }}"
                                                                data-form-action="{{ route('data-entry.sections.updateInContext', $section->id) }}"
                                                                data-context-info="Sec #{{ $section->section_number }} for {{ $subject->subject_no }} (Theory)">Edit</button>
                                                            <button
                                                                class="btn btn-sm btn-outline-danger py-0 px-1 open-delete-section-modal"
                                                                data-bs-toggle="modal" data-bs-target="#deleteSectionModal"
                                                                data-form-action="{{ route('data-entry.sections.destroyInContext', $section->id) }}"
                                                                data-section-info="Section #{{ $section->section_number }} (Theory) for {{ $subject->subject_no }}">Delete</button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-muted text-center p-3 mb-0">No theory sections defined.</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- الجزء العملي --}}
                    @if (
                        ($subject->practical_hours ?? 0) > 0 &&
                            Str::contains($subjectCategoryName, ['practical', 'عملي', 'combined', 'مشترك']))
                        <div class="card shadow-sm mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center"
                                style="background-color: #f8f9fa;">
                                <h6 class="mb-0 text-dark"><i class="fas fa-flask me-2"></i>Practical Sections</h6>
                                <button class="btn btn-outline-success btn-sm open-add-section-modal" data-bs-toggle="modal"
                                    data-bs-target="#addSectionModal" data-plan-subject-id="{{ $ps->id }}"
                                    data-activity-type="Practical"
                                    data-subject-name-modal="{{ $subject->subject_name }} (Practical)"
                                    data-expected-count-id="{{ $expectedCount->id }}">
                                    <i class="fas fa-plus me-1"></i> Add Practical Section
                                </button>
                            </div>
                            <div class="card-body p-0">
                                @if ($practicalSections->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Sec.No.</th>
                                                    <th>Gender</th>
                                                    <th>Branch</th>
                                                    <th>Students</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($practicalSections as $index => $section)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>{{ $section->section_number }}</td>
                                                        <td>{{ $section->section_gender }}</td>
                                                        <td>{{ $section->branch ?? '-' }}</td>
                                                        <td>{{ $section->student_count }}</td>
                                                        <td>
                                                            <button
                                                                class="btn btn-sm btn-outline-primary py-0 px-1 me-1 open-edit-section-modal"
                                                                data-bs-toggle="modal" data-bs-target="#editSectionModal"
                                                                data-section-id="{{ $section->id }}"
                                                                data-section-number="{{ $section->section_number }}"
                                                                data-student-count="{{ $section->student_count }}"
                                                                data-section-gender="{{ $section->section_gender }}"
                                                                data-branch="{{ $section->branch }}"
                                                                data-activity-type="{{ $section->activity_type }}"
                                                                data-form-action="{{ route('data-entry.sections.updateInContext', $section->id) }}"
                                                                data-context-info="Sec #{{ $section->section_number }} for {{ $subject->subject_no }} (Practical)">Edit</button>
                                                            <button
                                                                class="btn btn-sm btn-outline-danger py-0 px-1 open-delete-section-modal"
                                                                data-bs-toggle="modal" data-bs-target="#deleteSectionModal"
                                                                data-form-action="{{ route('data-entry.sections.destroyInContext', $section->id) }}"
                                                                data-section-info="Section #{{ $section->section_number }} (Practical) for {{ $subject->subject_no }}">Delete</button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-muted text-center p-3 mb-0">No practical sections defined.</p>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
            {{-- *** تضمين ملف المودالات الموحد *** --}}
            @include('dashboard.data-entry.partials._manage_section_modals', [
                'section' => null, // للإضافة (سيتم ملء التفاصيل بـ JS)
                'expectedCountId_for_add' => $expectedCount->id, // لتمريرها لمودال الإضافة إذا احتجت
            ])
        </div>
    </div>
@endsection

@push('scripts')
    {{-- JavaScript (الكود من الرد السابق للتعامل مع المودالات الثلاثة يجب أن يعمل) --}}
    {{-- تأكد أن الـ JS يستخدم IDs المودالات الجديدة والفورمات إذا غيرتها --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            console.log('Manage Sections for Context: Scripts Ready.');

            // --- مودال الإضافة ---
            const addModalEl = $('#addSectionModal'); // ID المودال العام للإضافة
            const addFormEl = $('#addSectionForm'); // ID الفورم داخل مودال الإضافة
            const addModalTitleEl = $('#addSectionModalLabel');

            $(document).on('click', '.open-add-section-modal', function() {
                const button = $(this);
                const planSubjectId = button.data('plan-subject-id');
                const activityType = button.data('activity-type');
                const subjectName = button.data('subject-name-modal');
                const expectedCountId = button.data('expected-count-id');
                const branchFromContext = button.data('branch') !== undefined ? button.data('branch') :
                ''; // جلب الفرع من الزر

                addModalTitleEl.text('Add ' + activityType + ' Section for: ' + subjectName);
                const formAction = "{{ url('dashboard/data-entry/sections/store-in-context') }}/" +
                    expectedCountId;
                addFormEl.attr('action', formAction);

                addFormEl.find('input[name="plan_subject_id_from_modal"]').val(planSubjectId);
                addFormEl.find('input[name="activity_type_from_modal"]').val(activityType);
                addFormEl.find('#add_branch_display_modal').val(branchFromContext); // عرض الفرع للقراءة

                // إعادة تعيين باقي الحقول
                addFormEl.find('input[name="section_number"]').val('1');
                addFormEl.find('input[name="student_count"]').val('0');
                addFormEl.find('select[name="section_gender"]').val('Mixed');
                addFormEl.find('.is-invalid').removeClass('is-invalid');
                addFormEl.find('.invalid-feedback, .text-danger.small, .alert-danger').remove();
                console.log("Add Modal opened. Action:", formAction, "PS_ID:", planSubjectId, "Activity:",
                    activityType);
            });

            // --- مودال التعديل ---
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
                editFormEl.find('#edit_form_errors_placeholder').empty(); // مسح أخطاء سابقة
                console.log("Edit Modal configured. Action:", formAction);
            });


            // --- مودال الحذف ---
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

            // --- عرض أخطاء الـ Validation في المودال الصحيح بعد إعادة التحميل ---
            @if ($errors->hasBag('storeSectionModal'))
                var addModalInstance = new bootstrap.Modal(document.getElementById('addSectionModal'));
                addModalInstance.show();
                // يمكنك إضافة كود هنا لملء حقول مودال الإضافة بالـ old input إذا أردت
                // وعرض الأخطاء داخل المودال (تم إضافته في HTML المودال)
                console.log("Errors found for Add Section Modal, showing modal.");
            @endif

            @foreach ($errors->getBags() as $bagName => $bagErrors)
                @if (Str::startsWith($bagName, 'updateSectionModal_'))
                    @php $sectionIdForError = Str::after($bagName, 'updateSectionModal_'); @endphp
                    var editModalInstance_{{ $sectionIdForError }} = new bootstrap.Modal(document.getElementById(
                        'editSectionModal')); // نفترض ID واحد للمودال
                    // قد تحتاج لتمرير بيانات الشعبة مرة أخرى للمودال أو استخدام old()
                    // ولإظهار المودال الصحيح، ستحتاج لتحديد أي مودال تعديل يجب فتحه
                    // هذا الجزء قد يكون معقداً إذا كان لديك مودالات تعديل كثيرة بنفس الـ ID.
                    // الأسهل أن يتم إعادة فتح المودال الذي كان مفتوحاً، أو عرض الأخطاء في _status_messages.
                    // للتبسيط الآن، سنركز على عرض الأخطاء في _status_messages إذا لم يكن المودال مفتوحاً.
                    console.log("Errors found for Edit Section Modal ({{ $bagName }}), consider showing it.");
                    //$('#editSectionModal').modal('show'); // هذا سيفتح دائماً أول مودال
                @endif
            @endforeach


        });
    </script>
@endpush
