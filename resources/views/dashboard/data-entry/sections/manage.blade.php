@extends('dashboard.layout')

@push('styles')
    <style>
        .table th,
        .table td {
            vertical-align: middle;
            font-size: 0.85rem;
            padding: 0.5rem;
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

        .card-header h5.mb-0,
        .card-header h6.mb-0 {
            font-size: 1rem;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="data-entry-container">
            {{-- 1. عرض معلومات السياق --}}
            <div class="mb-4 p-3 border rounded bg-light shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h4 class="data-entry-header mb-1">Manage Sections for Subject</h4>
                        <p class="mb-1 text-muted small">
                            Plan: <strong>{{ optional($planSubject->plan)->plan_no }}</strong>
                            ({{ optional($planSubject->plan)->plan_name }}) <br>
                            Subject: <strong>{{ optional($planSubject->subject)->subject_no }} -
                                {{ optional($planSubject->subject)->subject_name }}</strong>
                            <span
                                class="badge bg-secondary fw-normal">{{ optional(optional($planSubject->subject)->subjectCategory)->subject_category_name }}</span>
                        </p>
                    </div>
                    <a href="{{ route('data-entry.sections.index', $request->query()) }}"
                        class="btn btn-sm btn-outline-secondary align-self-start">
                        <i class="fas fa-arrow-left me-1"></i> Back to All Sections
                    </a>
                </div>
                <hr>
                <div class="row small">
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Academic Year:</strong> {{ $academicYear }}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Term (Sections):</strong>
                            {{ $semesterOfSections == 1 ? 'First' : ($semesterOfSections == 2 ? 'Second' : 'Summer') }}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-0"><strong>Branch:</strong> {{ $branch ?? 'Default' }}</p>
                    </div>
                </div>
                @if ($expectedCount)
                    <div class="mt-2">
                        <p class="mb-0"><strong>Total Expected Students for Context:</strong> <span
                                class="fw-bold fs-5">{{ $expectedCount->male_count + $expectedCount->female_count }}</span>
                        </p>
                    </div>
                @else
                    <div class="alert alert-warning small mt-2 p-2">Expected student count not found. <a
                            href="{{ route('data-entry.plan-expected-counts.index') }}" class="alert-link">Add it here.</a>
                    </div>
                @endif
            </div>

            @include('dashboard.data-entry.partials._status_messages')

            {{-- 2. زر إنشاء/تحديث الشعب لهذه المادة فقط --}}
            <div class="mb-3 text-end">
                @if ($expectedCount)
                    <form action="{{ route('data-entry.sections.generateForSubject') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="plan_subject_id" value="{{ $planSubject->id }}">
                        <input type="hidden" name="expected_count_id" value="{{ $expectedCount->id }}">
                        <button type="submit" class="btn btn-warning"
                            onclick="return confirm('ATTENTION: This will DELETE existing sections for THIS SUBJECT ONLY and REGENERATE them. Manual adjustments will be lost. Are you sure?');">
                            <i class="fas fa-cogs me-1"></i> Regenerate Sections for This Subject
                        </button>
                    </form>
                @endif
            </div>

            {{-- 3. عرض الشعب الحالية لهذه المادة --}}
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

            {{-- الجزء النظري --}}
            @if (
                ($subject->theoretical_hours ?? 0) > 0 &&
                    Str::contains($subjectCategoryName, ['theory', 'نظري', 'combined', 'مشترك']))
                <div class="card shadow-sm mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center"
                        style="background-color: #e9ecef;">
                        <h6 class="mb-0 text-dark"><i class="fas fa-chalkboard me-2"></i>Theory Sections</h6>
                        <button class="btn btn-outline-success btn-sm open-add-section-modal" data-bs-toggle="modal"
                            data-bs-target="#addSectionModal" data-plan-subject-id="{{ $planSubject->id }}"
                            data-activity-type="Theory" data-subject-name-modal="{{ $subject->subject_name }} (Theory)"
                            data-academic-year="{{ $academicYear }}" data-semester="{{ $semesterOfSections }}"
                            data-branch="{{ $branch ?? '' }}">
                            <i class="fas fa-plus me-1"></i> Add Theory Section
                        </button>
                    </div>
                    <div class="card-body p-0">
                        @if ($theorySections->isNotEmpty())
                            {{-- *** جدول الشعب النظرية مدمج هنا *** --}}
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
                                                        data-form-action="{{ route('data-entry.sections.update', $section->id) }}"
                                                        data-section-number="{{ $section->section_number }}"
                                                        data-student-count="{{ $section->student_count }}"
                                                        data-section-gender="{{ $section->section_gender }}"
                                                        data-branch="{{ $section->branch ?? '' }}"
                                                        data-activity-type="{{ $section->activity_type }}"
                                                        data-context-info="Sec #{{ $section->section_number }} for {{ optional($planSubject->subject)->subject_no }} (Theory)">Edit</button>

                                                    <button
                                                        class="btn btn-sm btn-outline-danger py-0 px-1 open-delete-section-modal"
                                                        data-bs-toggle="modal" data-bs-target="#deleteSectionModal"
                                                        data-form-action="{{ route('data-entry.sections.destroy', $section->id) }}"
                                                        data-section-info="Section #{{ $section->section_number }} (Theory) for {{ optional($planSubject->subject)->subject_no }}">Delete</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            {{-- *** نهاية الجدول المدمج *** --}}
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
                @if (
                    $subjectCategoryName == 'theory' ||
                        Str::contains($subjectCategoryName, 'نظري') ||
                        (Str::contains($subjectCategoryName, 'combined') || Str::contains($subjectCategoryName, 'مشترك')))
                    <div class="card shadow-sm mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center"
                            style="background-color: #f8f9fa;">
                            <h6 class="mb-0 text-dark"><i class="fas fa-flask me-2"></i>Practical Sections</h6>
                            <button class="btn btn-outline-success btn-sm open-add-section-modal" data-bs-toggle="modal"
                                data-bs-target="#addSectionModal" data-plan-subject-id="{{ $planSubject->id }}"
                                data-activity-type="Practical"
                                data-subject-name-modal="{{ $subject->subject_name }} (Practical)"
                                data-academic-year="{{ $academicYear }}" data-semester="{{ $semesterOfSections }}"
                                data-branch="{{ $branch ?? '' }}">
                                <i class="fas fa-plus me-1"></i> Add Practical Section
                            </button>
                        </div>
                        <div class="card-body p-0">
                            @if ($practicalSections->isNotEmpty())
                                {{-- *** جدول الشعب العملية مدمج هنا *** --}}
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
                                                            class="btn btn-sm btn-outline-primary ... open-edit-section-modal"
                                                            data-bs-toggle="modal" data-bs-target="#editSectionModal"
                                                            {{-- **ID المودال العام** --}}
                                                            data-form-action="{{ route('data-entry.sections.update', $section->id) }}"
                                                            data-section-number="{{ $section->section_number }}"
                                                            data-student-count="{{ $section->student_count }}"
                                                            data-section-gender="{{ $section->section_gender }}"
                                                            data-branch="{{ $section->branch ?? '' }}"
                                                            data-context-info="Sec #{{ $section->section_number }} for {{ optional($planSubject->subject)->subject_no }} ({{ $section->activity_type }})">Edit</button>
                                                        {{-- <button class="btn btn-sm btn-outline-primary py-0 px-1 me-1 open-edit-section-modal"
                                                            data-bs-toggle="modal" data-bs-target="#editSectionModal"
                                                            data-form-action="{{ route('data-entry.sections.update', $section->id) }}"
                                                            data-section-number="{{ $section->section_number }}"
                                                            data-student-count="{{ $section->student_count }}"
                                                            data-section-gender="{{ $section->section_gender }}"
                                                            data-branch="{{ $section->branch ?? '' }}"
                                                            data-activity-type="{{ $section->activity_type }}"
                                                            data-context-info="Sec #{{$section->section_number}} for {{ $subject->subject_no }} (Practical)">Edit</button> --}}

                                                        <button
                                                            class="btn btn-sm btn-outline-danger py-0 px-1 open-delete-section-modal"
                                                            data-bs-toggle="modal" data-bs-target="#deleteSectionModal"
                                                            data-form-action="{{ route('data-entry.sections.destroy', $section->id) }}"
                                                            data-section-info="Section #{{ $section->section_number }} (Practical) for {{ $subject->subject_no }}">Delete</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                {{-- *** نهاية الجدول المدمج *** --}}
                            @else
                                <p class="text-muted text-center p-3 mb-0">No practical sections defined.</p>
                            @endif
                        </div>
                    </div>
                @endif
            @endif

            {{-- تضمين ملف المودالات الموحد (ملف واحد للمودالات) --}}
            @include('dashboard.data-entry.sections.partials._sections_modals')

        </div>
    </div>
@endsection

@push('scripts')
    {{-- نفس كود JavaScript من الردود السابقة للتعامل مع المودالات الثلاثة الموحدة --}}
    {{-- مع التأكيد على استهداف الـ IDs الصحيحة للمودالات والفورمات --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            console.log('Manage Sections Page: Scripts Ready.');

            const addModal = $('#addSectionModal'); // ID مودال الإضافة من الـ partial
            const editModal = $('#editSectionModal'); // ID مودال التعديل من الـ partial
            const deleteModal = $('#deleteSectionModal'); // ID مودال الحذف من الـ partial

            // --- Add Modal ---
            $(document).on('click', '.open-add-section-modal', function() {
                const button = $(this);
                addModal.find('.modal-title').text('Add ' + button.data('activity-type') +
                    ' Section for: ' + button.data('subject-name-modal'));
                addModal.find('form').attr('action', "{{ route('data-entry.sections.store') }}");

                addModal.find('input[name="plan_subject_id"]').val(button.data('plan-subject-id'));
                addModal.find('input[name="activity_type"]').val(button.data('activity-type'));
                addModal.find('input[name="academic_year"]').val(button.data('academic-year'));
                addModal.find('input[name="semester"]').val(button.data('semester'));
                addModal.find('input[name="branch"]').val(button.data('branch') || '');
                addModal.find('#add_branch_display').val(button.data('branch') || 'Default');


                addModal.find('#add_section_number_input').val('1'); // استخدام ID الحقل من المودال
                addModal.find('#add_student_count_input').val('0'); // استخدام ID الحقل من المودال
                addModal.find('#add_section_gender_select').val('Mixed'); // استخدام ID الحقل من المودال
                addModal.find('.is-invalid').removeClass('is-invalid');
                addModal.find('.invalid-feedback').text('');
                addModal.find('.alert-danger.validation-errors').remove();
                // تأكد من أن Bootstrap Modal مهيأ بشكل صحيح قبل استدعاء show
                var modalInstance = bootstrap.Modal.getInstance(addModal[0]);
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(addModal[0]);
                }
                modalInstance.show();
            });

            // --- Edit Modal ---
            // $(document).on('click', '.open-edit-section-modal', function() {
            //     const button = $(this);
            //     editModal.find('.modal-title').text('Edit Section (' + button.data('context-info') + ')');
            //     editModal.find('form').attr('action', button.data('form-action'));
            //     editModal.find('#edit_context_info_display span').text(button.data('context-info')); // استخدام ID الـ span

            //     editModal.find('#edit_section_number_input').val(button.data('section-number'));
            //     editModal.find('#edit_student_count_input').val(button.data('student-count'));
            //     editModal.find('#edit_section_gender_select').val(button.data('section-gender'));
            //     editModal.find('#edit_branch_display_input').val(button.data('branch') || 'Default');


            //     editModal.find('.is-invalid').removeClass('is-invalid');
            //     editModal.find('.invalid-feedback').text('');
            //     editModal.find('.alert-danger.validation-errors').remove();
            //     var modalInstance = bootstrap.Modal.getInstance(editModal[0]);
            //     if (!modalInstance) { modalInstance = new bootstrap.Modal(editModal[0]); }
            //     modalInstance.show();
            // });
            // --- Edit Modal ---
            // --- Edit Modal ---
            $(document).on('click', '.open-edit-section-modal', function() {
                const button = $(this);
                const editModal = $('#editSectionModal');

                // تعبئة بيانات النموذج
                editModal.find('.modal-title').text('Edit Section (' + button.data('context-info') + ')');
                editModal.find('form').attr('action', button.data('form-action'));
                editModal.find('#edit_context_info_text span').text(button.data('context-info'));

                // تعبئة الحقول من data attributes
                editModal.find('#edit_section_number').val(button.data('section-number'));
                editModal.find('#edit_student_count').val(button.data('student-count'));
                editModal.find('#edit_section_gender').val(button.data('section-gender'));
                editModal.find('#edit_branch_display').val(button.data('branch') || 'Default');

                // مسح الأخطاء السابقة
                editModal.find('.is-invalid').removeClass('is-invalid');
                editModal.find('.invalid-feedback').text('');
                editModal.find('.validation-errors').remove();

                editModal.modal('show');
            });

            // إعادة فتح المودال عند وجود أخطاء
            @if (session('editSectionId') && ($errors->hasBag('editSectionModal_' . session('editSectionId')) || $errors->any()))
                $(document).ready(function() {
                    const editModal = $('#editSectionModal');
                    const sectionId = "{{ session('editSectionId') }}";

                    // الحصول على زر التعديل المناسب
                    const editButton = $(`.open-edit-section-modal[data-form-action*="/${sectionId}"]`);

                    if (editButton.length) {
                        // تعبئة البيانات من الزر
                        editModal.find('.modal-title').text('Edit Section (' + editButton.data(
                            'context-info') + ')');
                        editModal.find('form').attr('action', editButton.data('form-action'));
                        editModal.find('#edit_context_info_text span').text(editButton.data(
                        'context-info'));

                        // تعبئة الحقول من old() أو من البيانات الأصلية
                        editModal.find('#edit_section_number').val(
                            "{{ old('section_number', session('sectionForModal')->section_number ?? '') }}"
                            );
                        editModal.find('#edit_student_count').val(
                            "{{ old('student_count', session('sectionForModal')->student_count ?? '') }}"
                            );
                        editModal.find('#edit_section_gender').val(
                            "{{ old('section_gender', session('sectionForModal')->section_gender ?? '') }}"
                            );
                        editModal.find('#edit_branch_display').val(editButton.data('branch') || 'Default');

                        editModal.modal('show');
                    }
                });
            @endif

            // --- Delete Modal ---
            $(document).on('click', '.open-delete-section-modal', function() {
                const button = $(this);
                deleteModal.find('#delete_section_info_text').text(button.data(
                    'section-info')); // استخدام ID الـ strong
                deleteModal.find('form').attr('action', button.data('form-action'));
                var modalInstance = bootstrap.Modal.getInstance(deleteModal[0]);
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(deleteModal[0]);
                }
                modalInstance.show();
            });

            // لإعادة فتح المودال عند وجود أخطاء validation
            @if ($errors->any())
                @if ($errors->hasBag('addSectionModal'))
                    console.log("Reopening Add Modal due to validation errors (Bag: addSectionModal).");
                    var addModalInstance = bootstrap.Modal.getInstance(document.getElementById('addSectionModal'));
                    if (!addModalInstance) {
                        addModalInstance = new bootstrap.Modal(document.getElementById('addSectionModal'));
                    }
                    addModalInstance.show();
                @endif
                {{-- هذا الجزء يحتاج لتحديد دقيق لمودال التعديل إذا كان هناك أخطاء --}}
                @php
                    $editErrorBagName = null;
                    foreach ($errors->getBags() as $bagNameKey => $bagErrors) {
                        if (Str::startsWith($bagNameKey, 'editSectionModal_')) {
                            // اسم الـ error bag من الكنترولر
                            $editErrorBagName = $bagNameKey;
                            break;
                        }
                    }
                @endphp
                @if ($editErrorBagName)
                    console.warn(
                        "Validation errors on update modal. ErrorBag: {{ $editErrorBagName }}. Reopening generic edit modal."
                    );
                    var editModalInstance = bootstrap.Modal.getInstance(document.getElementById(
                        'editSectionModal'));
                    if (!editModalInstance) {
                        editModalInstance = new bootstrap.Modal(document.getElementById('editSectionModal'));
                    }
                    editModalInstance.show();
                    // قد تحتاج لإعادة ملء حقول مودال التعديل بالـ old() input هنا
                    $('#editSectionModal').find('#edit_section_number_input').val("{{ old('section_number') }}");
                    $('#editSectionModal').find('#edit_student_count_input').val("{{ old('student_count') }}");
                    // ...
                @endif
            @endif
        });
    </script>
@endpush
