@extends('dashboard.layout')

@push('styles')
{{-- No specific styles needed unless you add complex elements --}}
<style>
    .table th, .table td { vertical-align: middle; }
    .modal-dialog-centered { display: flex; align-items: center; min-height: calc(100% - 1rem); }
    @media (min-width: 576px) { .modal-dialog-centered { min-height: calc(100% - 3.5rem); } }
</style>
@endpush

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        {{-- 1. عرض معلومات السياق --}}
        <div class="mb-4 p-3 border rounded bg-light shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h4 class="data-entry-header mb-1">Manage Sections for Context</h4>
                    <p class="mb-1 text-muted small">
                        Modifying sections for a specific academic context.
                    </p>
                </div>
                <a href="{{ route('data-entry.plan-expected-counts.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Expected Counts
                </a>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Plan:</strong> {{ optional($expectedCount->plan)->plan_name }} ({{ optional($expectedCount->plan)->plan_no }})</p>
                    <p class="mb-1"><strong>Department:</strong> {{ optional(optional($expectedCount->plan)->department)->department_name }}</p>
                    <p class="mb-0"><strong>Academic Year:</strong> {{ $expectedCount->academic_year }}</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Level:</strong> {{ $expectedCount->plan_level }}</p>
                    <p class="mb-1"><strong>Semester (of Plan):</strong> {{ $expectedCount->plan_semester }}</p>
                    <p class="mb-0"><strong>Branch:</strong> {{ $expectedCount->branch ?? 'Default (No Branch)' }}</p>
                </div>
            </div>
            <div class="mt-2">
                <p class="mb-0"><strong>Total Expected Students:</strong>
                    <span class="fw-bold fs-5">{{ $expectedCount->male_count + $expectedCount->female_count }}</span>
                    <small class="text-muted"> ({{ $expectedCount->male_count }} Male, {{ $expectedCount->female_count }} Female)</small>
                </p>
            </div>
        </div>

        @include('dashboard.data-entry.partials._status_messages')

        {{-- 2. زر إنشاء/تحديث الشعب تلقائياً --}}
        <div class="mb-3 text-end">
            <form action="{{ route('data-entry.sections.generateForContext', $expectedCount->id) }}" method="POST" class="d-inline">
                @csrf
                {{-- لا نحتاج لتمرير بارامترات هنا لأنها في الروت --}}
                <button type="submit" class="btn btn-warning" onclick="return confirm('This will delete existing sections for ALL subjects in this context (Plan: {{ optional($expectedCount->plan)->plan_no }}, Level: {{ $expectedCount->plan_level }}, Plan Semester: {{ $expectedCount->plan_semester }}, Year: {{ $expectedCount->academic_year }}) and regenerate them based on expected counts. Are you sure?');">
                    <i class="fas fa-cogs me-1"></i> Regenerate All Sections for Context
                </button>
            </form>
        </div>

        {{-- 3. جدول الشعب الحالية لكل مادة في هذا السياق --}}
        @if(!isset($planSubjectsForContext) || $planSubjectsForContext->isEmpty())
            <div class="alert alert-info">
                No subjects found in Plan: {{ optional($expectedCount->plan)->plan_no }} /
                Level: {{ $expectedCount->plan_level }} /
                Semester (Plan): {{ $expectedCount->plan_semester }}.
                <br>Please add subjects to this part of the plan first.
            </div>
        @else
            @foreach($planSubjectsForContext as $ps) {{-- $ps هو PlanSubject --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            Subject: <strong class="text-primary">{{ optional($ps->subject)->subject_no }} - {{ optional($ps->subject)->subject_name }}</strong>
                            <small class="text-muted">({{ optional(optional($ps->subject)->subjectCategory)->subject_category_name }})</small>
                        </h5>
                        {{-- زر إضافة شعبة يدوياً لهذه المادة --}}
                        <button class="btn btn-primary btn-sm open-add-section-modal-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#addSectionForContextModal"
                                data-plan-subject-id="{{ $ps->id }}"
                                data-academic-year="{{ $expectedCount->academic_year }}"
                                data-semester="{{ $expectedCount->plan_semester }}" {{-- فصل الشعبة سيكون نفس فصل الخطة/العدد المتوقع --}}
                                data-branch="{{ $expectedCount->branch }}"
                                data-subject-name-modal="{{ optional($ps->subject)->subject_name }}"
                                data-expected-count-id="{{ $expectedCount->id }}"> {{-- لتسهيل الـ redirect --}}
                            <i class="fas fa-plus me-1"></i> Add Section
                        </button>
                    </div>
                    <div class="card-body p-0"> {{-- إزالة padding من body --}}
                        @if(isset($currentSectionsBySubject[$ps->subject_id]) && $currentSectionsBySubject[$ps->subject_id]->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-sm mb-0"> {{-- إزالة margin bottom --}}
                                    <thead class="table-light"><tr><th>#</th><th>Sec.No.</th><th>Gender</th><th>Branch</th><th>Students</th><th>Actions</th></tr></thead>
                                    <tbody>
                                        @foreach($currentSectionsBySubject[$ps->subject_id] as $index => $section)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $section->section_number }}</td>
                                                <td>{{ $section->section_gender }}</td>
                                                <td>{{ $section->branch ?? '-' }}</td>
                                                <td>{{ $section->student_count }}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editSectionInContextModal-{{ $section->id }}" title="Edit">Edit</button>
                                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteSectionInContextModal-{{ $section->id }}" title="Delete">Delete</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center p-3">No sections defined yet for this subject in this context.</p>
                        @endif
                    </div>
                </div>
                 {{-- تضمين مودالات التعديل والحذف (لكل الشعب المعروضة لهذه المادة) --}}
                 @if(isset($currentSectionsBySubject[$ps->subject_id]))
                    @foreach($currentSectionsBySubject[$ps->subject_id] as $section)
                        @include('dashboard.data-entry.partials._section_modals_context', ['section' => $section, 'expectedCountId' => $expectedCount->id])
                    @endforeach
                 @endif
            @endforeach
        @endif


         {{-- مودال إضافة شعبة جديدة (مدمج أو partial) --}}
         @include('dashboard.data-entry.partials._section_modals_context', [
             'section' => null, // للإضافة
             'expectedCountId' => $expectedCount->id, // لتسهيل الـ redirect
             // القيم الأخرى مثل plan_subject_id, academic_year, semester, branch ستُمرر عبر data attributes و JS
         ])
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
$(document).ready(function() {
    console.log('Manage Sections for Context: Document ready.');

    // --- التعامل مع مودال إضافة شعبة جديدة (في هذا السياق) ---
    $('#addSectionForContextModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var planSubjectId = button.data('plan-subject-id');
        var academicYear = button.data('academic-year');
        var semester = button.data('semester');
        var branch = button.data('branch') !== undefined ? button.data('branch') : '';
        var subjectName = button.data('subject-name-modal');
        var expectedCountId = button.data('expected-count-id');

        var modal = $(this);
        modal.find('.modal-title').text('Add New Section for: ' + (subjectName || 'Subject'));

        // تعبئة الحقول المخفية في الفورم
        // الفورم الآن يرسل لـ storeSectionInContext الذي يتوقع expectedCountId في الروت
        modal.find('form').attr('action', "{{ url('dashboard/data-entry/sections/store-in-context') }}/" + expectedCountId);
        modal.find('input[name="plan_subject_id_from_modal"]').val(planSubjectId); // حقل جديد لـ plan_subject_id
        // لا نحتاج لتمرير academic_year, semester, branch لأن الكنترولر سيأخذها من expectedCount

        var branchInput = modal.find('input[name="branch_from_modal"]'); // حقل جديد للفرع
        branchInput.val(branch);
        if (branch !== '' && branch !== undefined) {
            // branchInput.prop('readonly', true); // يمكن جعله للقراءة فقط
        } else {
            // branchInput.prop('readonly', false).attr('placeholder', 'Leave blank if no specific branch');
        }

        modal.find('input[name="section_number"]').val('1');
        modal.find('input[name="student_count"]').val('0');
        modal.find('select[name="section_gender"]').val('Mixed');
        modal.find('.is-invalid').removeClass('is-invalid');
        modal.find('.invalid-feedback, .text-danger.small, .alert-danger').remove(); // مسح الأخطاء
         console.log("Add Section Modal ready for context:", {planSubjectId, academicYear, semester, branch});
    });


    // --- (اختياري) يمكنك إضافة JS مشابه لمودالات التعديل والحذف ---
    // لتمرير أي بيانات إضافية إذا احتجت، لكن الـ Route Model Binding يعتني بالأمر عادةً
    // مثال لمودال التعديل
    $('[id^=editSectionInContextModal-]').on('show.bs.modal', function(event) {
        // var button = $(event.relatedTarget);
        var modal = $(this);
        // يمكنك مسح الأخطاء القديمة هنا إذا أردت
        // modal.find('.is-invalid').removeClass('is-invalid');
        // modal.find('.invalid-feedback, .text-danger.small, .alert-danger').remove();
        console.log("Edit Section Modal opening for section ID: " + modal.attr('id').split('-')[1]);
    });

});
</script>
@endpush
