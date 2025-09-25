@extends('dashboard.layout')

@push('styles')
{{-- نفس الـ styles من الرد السابق --}}
<style>
    .table th, .table td { vertical-align: middle; font-size: 0.85rem; }
    .modal-dialog-centered { display: flex; align-items: center; min-height: calc(100% - 1rem); }
    @media (min-width: 576px) { .modal-dialog-centered { min-height: calc(100% - 3.5rem); } }
    .card-body .table-responsive { margin-bottom: 0; }
    .card-header h5.mb-0, .card-header h6.mb-0 { font-size: 1rem; }
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
                         Plan: <strong>{{ optional($planSubject->plan)->plan_no }}</strong> |
                         Subject: <strong>{{ optional($planSubject->subject)->subject_no }} - {{ optional($planSubject->subject)->subject_name }}</strong>
                     </p>
                 </div>
                 <a href="{{ route('data-entry.sections.index', request()->only(['academic_year', 'semester', 'department_id', 'plan_id', 'plan_level', 'subject_id', 'branch'])) }}" class="btn btn-sm btn-outline-secondary align-self-start">
                     <i class="fas fa-arrow-left me-1"></i> Back to All Sections
                 </a>
             </div>
             <hr>
             <div class="row small">
                 <div class="col-md-4"><p class="mb-1"><strong>Academic Year:</strong> {{ $academicYear }}</p></div>
                 <div class="col-md-4"><p class="mb-1"><strong>Term (Sections):</strong> {{ $semesterOfSections == 1 ? 'First' : ($semesterOfSections == 2 ? 'Second' : 'Summer') }}</p></div>
                 <div class="col-md-4"><p class="mb-0"><strong>Branch:</strong> {{ $branch ?? 'Default' }}</p></div>
             </div>
             @if($expectedCount)
             <div class="mt-2"><p class="mb-0"><strong>Total Expected for Context:</strong> <span class="fw-bold fs-5">{{ $expectedCount->male_count + $expectedCount->female_count }}</span></p></div>
             @else
             <div class="alert alert-warning small mt-2 p-2">Expected student count not found. <a href="{{ route('data-entry.plan-expected-counts.index') }}" class="alert-link">Add it here.</a></div>
             @endif
        </div>

        @include('dashboard.data-entry.partials._status_messages')

        {{-- 2. زر إنشاء/تحديث الشعب لهذه المادة فقط --}}
        <div class="mb-3 text-end">
            @if($expectedCount)
            <form action="{{ route('data-entry.sections.generateForSubject') }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="plan_subject_id" value="{{ $planSubject->id }}">
                <input type="hidden" name="expected_count_id" value="{{ $expectedCount->id }}">
                <button type="submit" class="btn btn-warning" onclick="return confirm('This will regenerate sections for THIS SUBJECT ONLY. Existing sections for this subject in this context will be deleted. Are you sure?');">
                    <i class="fas fa-cogs me-1"></i> Regenerate Sections for This Subject
                </button>
            </form>
            @endif
        </div>

        {{-- 3. عرض الشعب الحالية لهذه المادة (مقسمة نظري وعملي) --}}
        @php
            $subject = $planSubject->subject;
            if ($subject && $subject->subjectCategory) {
                $subjectCategoryName = strtolower($subject->subjectCategory->subject_category_name);
                $theorySections = $currentSections->where('activity_type', 'Theory');
                $practicalSections = $currentSections->where('activity_type', 'Practical');
            } else { /* ... default values ... */ }
        @endphp

        {{-- الجزء النظري --}}
        @if (($subject->theoretical_hours ?? 0) > 0 && (Str::contains($subjectCategoryName, ['theory', 'نظري', 'combined', 'مشترك'])))
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #e9ecef;">
                    <h6 class="mb-0 text-dark"><i class="fas fa-chalkboard me-2"></i>Theory Sections</h6>
                    <button class="btn btn-outline-success btn-sm open-add-section-modal"
                            data-bs-toggle="modal" data-bs-target="#addSectionModal"
                            data-plan-subject-id="{{ $planSubject->id }}"
                            data-activity-type="Theory"
                            data-subject-name-modal="{{ $subject->subject_name }} (Theory)"
                            data-academic-year="{{ $academicYear }}"
                            data-semester="{{ $semesterOfSections }}"
                            data-branch="{{ $branch }}">
                        <i class="fas fa-plus me-1"></i> Add Theory Section
                    </button>
                </div>
                <div class="card-body p-0">
                    @include('dashboard.data-entry.sections.partials._section_table_template', ['sections' => $theorySections, 'activityTypeFriendly' => 'Theory'])
                </div>
            </div>
        @endif

        {{-- الجزء العملي --}}
        @if (($subject->practical_hours ?? 0) > 0 && (Str::contains($subjectCategoryName, ['practical', 'عملي', 'combined', 'مشترك'])))
             @if (!($subjectCategoryName == 'theory' || Str::contains($subjectCategoryName, 'نظري')) || (Str::contains($subjectCategoryName, 'combined') || Str::contains($subjectCategoryName, 'مشترك')))
                <div class="card shadow-sm mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f8f9fa;">
                        <h6 class="mb-0 text-dark"><i class="fas fa-flask me-2"></i>Practical Sections</h6>
                        <button class="btn btn-outline-success btn-sm open-add-section-modal"
                                data-bs-toggle="modal" data-bs-target="#addSectionModal"
                                data-plan-subject-id="{{ $planSubject->id }}"
                                data-activity-type="Practical"
                                data-subject-name-modal="{{ $subject->subject_name }} (Practical)"
                                data-academic-year="{{ $academicYear }}"
                                data-semester="{{ $semesterOfSections }}"
                                data-branch="{{ $branch }}">
                            <i class="fas fa-plus me-1"></i> Add Practical Section
                        </button>
                    </div>
                    <div class="card-body p-0">
                         @include('dashboard.data-entry.sections.partials._section_table_template', ['sections' => $practicalSections, 'activityTypeFriendly' => 'Practical'])
                    </div>
                </div>
            @endif
        @endif

        {{-- تضمين ملف المودالات الموحد (واحد للإضافة، واحد للتعديل، واحد للحذف) --}}
        @include('dashboard.data-entry.sections.partials._sections_modals', [
            'section_for_modal' => null, // لا نحتاج لتمرير section هنا للمودال العام للإضافة
            // السياق العام متوفر في الصفحة ويمكن للـ JS جلبه من الأزرار
        ])

   </div>
</div>
@endsection

@push('scripts')
{{-- JavaScript للتعامل مع المودالات الثلاثة الموحدة --}}
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
$(document).ready(function() {
    const addModal = $('#addSectionModal');
    const editModal = $('#editSectionModal');
    const deleteModal = $('#deleteSectionModal');

    // --- Add Modal ---
    $(document).on('click', '.open-add-section-modal', function() {
        const button = $(this);
        const planSubjectId = button.data('plan-subject-id');
        const activityType = button.data('activity-type');
        const subjectName = button.data('subject-name-modal');
        const academicYear = button.data('academic-year');
        const semester = button.data('semester');
        const branch = button.data('branch') !== undefined ? button.data('branch') : '';

        addModal.find('.modal-title').text('Add ' + activityType + ' Section for: ' + subjectName);
        addModal.find('form').attr('action', "{{ route('data-entry.sections.store') }}");

        addModal.find('input[name="plan_subject_id"]').val(planSubjectId);
        addModal.find('input[name="activity_type"]').val(activityType);
        addModal.find('input[name="academic_year"]').val(academicYear);
        addModal.find('input[name="semester"]').val(semester);
        addModal.find('input[name="branch"]').val(branch);
        addModal.find('#add_branch_display_in_modal').val(branch || 'Default (Context)');


        addModal.find('input[name="section_number"]').val('1');
        addModal.find('input[name="student_count"]').val('0');
        addModal.find('select[name="section_gender"]').val('Mixed');
        addModal.find('.is-invalid').removeClass('is-invalid');
        addModal.find('.invalid-feedback, .text-danger.small, .alert-danger').remove();
        addModal.modal('show');
    });

    // --- Edit Modal ---
    $(document).on('click', '.open-edit-section-modal', function() {
        const button = $(this);
        const formAction = button.data('form-action');
        const sectionNumber = button.data('section-number');
        const studentCount = button.data('student-count');
        const sectionGender = button.data('section-gender');
        const branch = button.data('branch') !== undefined ? button.data('branch') : '';
        const contextInfo = button.data('context-info');
        // الـ activity_type الأصلي للشعبة (مهم إذا كان الـ unique constraint يعتمد عليه)
        const originalActivityType = button.data('activity-type');


        editModal.find('.modal-title').text('Edit Section (' + contextInfo + ')');
        editModal.find('form').attr('action', formAction);
        editModal.find('#edit_context_info_text_in_modal span').text(contextInfo);


        editModal.find('input[name="section_number"]').val(sectionNumber);
        editModal.find('input[name="student_count"]').val(studentCount);
        editModal.find('select[name="section_gender"]').val(sectionGender);
        editModal.find('#edit_branch_display_in_modal').val(branch || 'Default');
        // حقل مخفي للـ activity_type الأصلي إذا احتجته في الـ validation للـ update
        editModal.find('input[name="original_activity_type"]').val(originalActivityType);


        editModal.find('.is-invalid').removeClass('is-invalid');
        editModal.find('.invalid-feedback, .text-danger.small, .alert-danger').remove();
        editModal.modal('show');
    });

    // --- Delete Modal ---
    $(document).on('click', '.open-delete-section-modal', function() {
        const button = $(this);
        const formAction = button.data('form-action');
        const sectionInfo = button.data('section-info');

        deleteModal.find('#deleteSectionInfoText').text(sectionInfo);
        deleteModal.find('form').attr('action', formAction);
        deleteModal.modal('show');
    });

    // لإعادة فتح المودال عند وجود أخطاء validation
     @if($errors->any())
        @if($errors->hasBag('addSectionModal'))
            $('#addSectionModal').modal('show');
        @endif
        // يمكنك إضافة منطق مشابه لمودال التعديل إذا استخدمت error bags مميزة
        // أو إذا كان الـ ID للشعبة موجوداً في old input
         @php
            $updateErrorSectionId = old('error_on_section_id'); // افترض أنك ترسل هذا إذا فشل التحديث
         @endphp
         @if($updateErrorSectionId)
            // ابحث عن الزر الذي يفتح مودال التعديل لهذا الـ ID وقم بمحاكاة الضغط عليه
             // هذا يتطلب أن يكون لكل زر تعديل ID فريد أو طريقة للوصول إليه
             // أو ببساطة:
             // if ($errors->hasBag('editSectionModal_'.$updateErrorSectionId)) {
             //    $('#editSectionModal').modal('show');
             //    // ثم تحتاج لتعبئة الحقول بالـ old input
             // }
             console.warn("Validation errors on update. Re-opening specific edit modal requires more advanced JS or server-side flags.");
         @endif
    @endif
});
</script>
@endpush
