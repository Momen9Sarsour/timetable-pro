@extends('dashboard.layout')

@push('styles')
<style>
    .table th, .table td { vertical-align: middle; font-size: 0.85rem; }
    .modal-dialog-centered { display: flex; align-items: center; min-height: calc(100% - 1rem); }
    @media (min-width: 576px) { .modal-dialog-centered { min-height: calc(100% - 3.5rem); } }
    .card-body .table-responsive { margin-bottom: 0; }
    .card-header h5.mb-0, .card-header h6.mb-0 { font-size: 1rem; } /* تصغير خط العناوين قليلاً */
    .badge.bg-light.text-dark { border: 1px solid #ccc; }
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
                         Plan: <strong>{{ optional($planSubject->plan)->plan_no }}</strong> ({{ optional($planSubject->plan)->plan_name }}) <br>
                         Subject: <strong>{{ optional($planSubject->subject)->subject_no }} - {{ optional($planSubject->subject)->subject_name }}</strong>
                     </p>
                 </div>
                 {{-- زر العودة لصفحة عرض كل الشعب مع تمرير الفلاتر الحالية (إذا أمكن) --}}
                 <a href="{{ route('data-entry.sections.index', [
                        'academic_year' => $academicYear,
                        'semester' => $semesterOfSections,
                        'plan_id' => optional($planSubject->plan)->id,
                        'plan_level' => $planSubject->plan_level,
                        'subject_id' => $planSubject->subject_id,
                        'branch' => $branch
                    ]) }}" class="btn btn-sm btn-outline-secondary align-self-start">
                     <i class="fas fa-arrow-left me-1"></i> Back to All Sections
                 </a>
             </div>
             <hr>
             <div class="row small">
                 <div class="col-md-4"><p class="mb-1"><strong>Academic Year:</strong> {{ $academicYear }}</p></div>
                 <div class="col-md-4"><p class="mb-1"><strong>Term (Sections):</strong> {{ $semesterOfSections == 1 ? 'First' : ($semesterOfSections == 2 ? 'Second' : 'Summer') }}</p></div>
                 <div class="col-md-4"><p class="mb-0"><strong>Branch:</strong> {{ $branch ?? 'Default (All Branches)' }}</p></div>
             </div>
             @if($expectedCount)
             <div class="mt-2"><p class="mb-0"><strong>Total Expected Students for this Context:</strong> <span class="fw-bold fs-5">{{ $expectedCount->male_count + $expectedCount->female_count }}</span></p></div>
             @else
             <div class="alert alert-warning small mt-2 p-2">
                 Expected student count not found for this specific plan/level/semester/year/branch.
                 <a href="{{ route('data-entry.plan-expected-counts.index') }}" class="alert-link">Please add it here.</a>
                 Automatic section generation may not be accurate.
             </div>
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
                <button type="submit" class="btn btn-warning" onclick="return confirm('ATTENTION: This will DELETE existing sections for THIS SUBJECT ONLY ({{ optional($planSubject->subject)->subject_no }}) in this context and REGENERATE them based on expected counts. Manual adjustments will be lost. Are you sure?');">
                    <i class="fas fa-cogs me-1"></i> Regenerate Sections for This Subject
                </button>
            </form>
            @else
                <button type="button" class="btn btn-warning disabled" title="Add expected count first">
                    <i class="fas fa-cogs me-1"></i> Regenerate Sections (Expected Count Missing)
                </button>
            @endif
        </div>

        {{-- 3. عرض الشعب الحالية لهذه المادة (مقسمة نظري وعملي) --}}
        @php
            $subject = $planSubject->subject; // للوصول السهل للمادة
            if ($subject && $subject->subjectCategory) { // التأكد من وجود المادة وفئتها
                $subjectCategoryName = strtolower($subject->subjectCategory->subject_category_name);
                $theorySections = $currentSections->where('activity_type', 'Theory');
                $practicalSections = $currentSections->where('activity_type', 'Practical');
            } else {
                $subjectCategoryName = '';
                $theorySections = collect();
                $practicalSections = collect();
                // يمكنك عرض رسالة خطأ هنا إذا كانت بيانات المادة ناقصة
            }
        @endphp

        {{-- الجزء النظري --}}
        @if (($subject->theoretical_hours ?? 0) > 0 && (Str::contains($subjectCategoryName, ['theory', 'نظري', 'combined', 'مشترك'])))
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #e9ecef;">
                    <h6 class="mb-0 text-dark"><i class="fas fa-chalkboard me-2"></i>Theory Sections</h6>
                    <button class="btn btn-outline-success btn-sm open-add-section-modal"
                            data-bs-toggle="modal" data-bs-target="#addSectionModal" {{-- ID المودال الموحد --}}
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
                    @if($theorySections->isNotEmpty())
                        @include('dashboard.data-entry.sections.partials._section_table_template', ['sections' => $theorySections, 'activityTypeFriendly' => 'Theory'])
                    @else
                        <p class="text-muted text-center p-3 mb-0">No theory sections defined.</p>
                    @endif
                </div>
            </div>
        @endif

        {{-- الجزء العملي --}}
        @if (($subject->practical_hours ?? 0) > 0 && (Str::contains($subjectCategoryName, ['practical', 'عملي', 'combined', 'مشترك'])))
             @if (!($subjectCategoryName == 'theory' || Str::contains($subjectCategoryName, 'نظري')) || (Str::contains($subjectCategoryName, 'combined') || Str::contains($subjectCategoryName, 'مشترك'))) {{-- تجنب العملي إذا كانت نظرية بحتة --}}
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
                        @if($practicalSections->isNotEmpty())
                            @include('dashboard.data-entry.sections.partials._section_table_template', ['sections' => $practicalSections, 'activityTypeFriendly' => 'Practical'])
                       @else
                           <p class="text-muted text-center p-3 mb-0">No practical sections defined.</p>
                       @endif
                    </div>
                </div>
            @endif
        @endif

        {{-- تضمين ملف المودالات الموحد --}}
        @include('dashboard.data-entry.sections.partials._sections_modals', [
            'section_for_modal' => null, // للإضافة، section سيكون null
            // السياق يتم تمريره الآن عبر data-attributes لأزرار الفتح
        ])
        {{-- لا نحتاج لتكرار المودالات هنا لأننا نستخدم مودالات موحدة --}}

   </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
$(document).ready(function() {
    console.log('Manage Sections for Subject Context: Scripts Initialized.');

    const addModal = $('#addSectionModal'); // ID المودال العام للإضافة
    const editModal = $('#editSectionModal'); // ID المودال العام للتعديل
    const deleteModal = $('#deleteSectionModal'); // ID المودال العام للحذف

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
        const formAction = "{{ route('data-entry.sections.store') }}";
        addModal.find('form').attr('action', formAction);

        // تعبئة الحقول المخفية
        addModal.find('input[name="plan_subject_id"]').val(planSubjectId);
        addModal.find('input[name="activity_type"]').val(activityType);
        addModal.find('input[name="academic_year"]').val(academicYear);
        addModal.find('input[name="semester"]').val(semester);
        addModal.find('input[name="branch"]').val(branch);
        addModal.find('#add_modal_branch_display_unified').val(branch || 'Default (Context)');


        // إعادة تعيين باقي الحقول
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

        editModal.find('.modal-title').text('Edit Section'); // يمكن تخصيصه أكثر
        editModal.find('form').attr('action', formAction);
        editModal.find('#edit_context_info_text_unified').text(contextInfo || 'Editing selected section.');

        editModal.find('input[name="section_number"]').val(sectionNumber);
        editModal.find('input[name="student_count"]').val(studentCount);
        editModal.find('select[name="section_gender"]').val(sectionGender);
        editModal.find('#edit_branch_display_unified').val(branch || 'Default');

        editModal.find('.is-invalid').removeClass('is-invalid');
        editModal.find('.invalid-feedback, .text-danger.small, .alert-danger').remove();
        editModal.modal('show');
    });

    // --- Delete Modal ---
    $(document).on('click', '.open-delete-section-modal', function() {
        const button = $(this);
        const formAction = button.data('form-action');
        const sectionInfo = button.data('section-info');

        deleteModal.find('#deleteSectionInfoTextUnified').text(sectionInfo);
        deleteModal.find('form').attr('action', formAction);
        deleteModal.modal('show');
    });

    // لإعادة فتح المودال عند وجود أخطاء validation
     @if($errors->any())
        @if($errors->hasBag('addSectionModalUnified'))
            $('#addSectionModalUnified').modal('show');
        @endif
        @php
            $updateErrorBagKey = $errors->keys()->first(fn($key) => Str::startsWith($key, 'updateSectionModalUnified_'));
        @endphp
        @if($updateErrorBagKey)
            @php
                $sectionIdForError = Str::after($updateErrorBagKey, 'updateSectionModalUnified_');
                // إذا كان الخطأ عاماً وليس مرتبطاً بحقل، قد لا يكون sectionIdForError موجوداً
                // في هذه الحالة، قد يكون من الصعب إعادة فتح المودال الصحيح بدون تخزين ID آخر مودال تم فتحه
                // لكن يمكن محاولة عرض رسالة خطأ عامة بدلاً من ذلك
                // أو إذا كان الخطأ مرتبطاً بحقل، يمكننا محاولة استهداف الزر الذي يفتح هذا المودال
                // لتبسيط الأمر الآن، سنعرض الأخطاء في _status_messages
            @endphp
             console.warn("Validation errors on update. Displaying via status message. ErrorBag: {{ $updateErrorBagKey ?? 'N/A' }}");
        @endif
    @endif

});
</script>
@endpush
