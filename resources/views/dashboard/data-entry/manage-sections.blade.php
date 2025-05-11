@extends('dashboard.layout')
{{-- {{ dd('lkjjkjkjk') }} --}}
@push('styles')
{{-- No specific styles needed here if modals are simple, or add Select2 if used in modals --}}

@endpush

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        {{-- عرض معلومات السياق --}}
        <div class="mb-4 border-bottom pb-3">
             <div class="d-flex justify-content-between align-items-center">
                 <div>
                     <h1 class="data-entry-header mb-1">Manage Sections</h1>
                     <p class="mb-1">
                         <strong>Plan:</strong> {{ optional($plan)->plan_name }} ({{ optional($plan)->plan_no }})
                         | <strong>Department:</strong> {{ optional(optional($plan)->department)->department_name }}
                     </p>
                     <p class="mb-0">
                         <strong>Academic Year:</strong> {{ $academicYear }} |
                         <strong>Plan Level:</strong> {{ $planLevel }} |
                         <strong>Plan Semester (of Plan):</strong> {{ $planSemester }} |
                         {{-- <strong>Sections Term:</strong> {{ $semesterOfSections == 1 ? 'First' : ($semesterOfSections == 2 ? 'Second' : 'Summer') }} | --}}
                         <strong>Branch:</strong> {{ $branch ?? 'Default (No Branch)' }}
                     </p>
                 </div>
                 <a href="{{ route('data-entry.sections.index', request()->only(['academic_year', 'semester', 'department_id', 'plan_id', 'plan_level', 'subject_id', 'branch'])) }}" class="btn btn-sm btn-outline-secondary align-self-start">
                     <i class="fas fa-list me-1"></i> View All Sections (Filtered)
                 </a>
             </div>
        </div>

        {{-- عرض العدد المتوقع ومجموع الطلاب الحالي --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="card bg-light shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title text-muted">Expected Students (for this context)</h6>
                        @if($expectedCount)
                            <p class="card-text display-6">{{ $expectedCount->male_count + $expectedCount->female_count }}</p>
                            <small class="text-muted">({{ $expectedCount->male_count }} Male, {{ $expectedCount->female_count }} Female)</small>
                        @else
                            <p class="card-text display-6 text-warning">N/A</p>
                            <small class="text-warning">Expected count not entered for this specific context.</small>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                 <div class="card bg-light shadow-sm h-100">
                     <div class="card-body text-center">
                          <h6 class="card-title text-muted">Current Allocated Students (in displayed sections)</h6>
                          @php
                              $totalAllocated = 0;
                              if(isset($currentSectionsBySubject)) {
                                  foreach($currentSectionsBySubject as $sectionsForOneSubject) {
                                      $totalAllocated += $sectionsForOneSubject->sum('student_count');
                                  }
                              }
                          @endphp
                          <p class="card-text display-6
                             @if($expectedCount && $totalAllocated > ($expectedCount->male_count + $expectedCount->female_count)) text-danger
                             @elseif($expectedCount && $totalAllocated < ($expectedCount->male_count + $expectedCount->female_count) && $totalAllocated > 0) text-warning
                             @elseif($totalAllocated > 0) text-success
                             @endif">
                             {{ $totalAllocated }}
                          </p>
                          @if($expectedCount)
                            <small class="text-muted">out of {{ $expectedCount->male_count + $expectedCount->female_count }} expected</small>
                          @endif
                     </div>
                 </div>
            </div>
        </div>

        @include('dashboard.data-entry.partials._status_messages')

        {{-- زر إنشاء/تحديث الشعب تلقائياً --}}
        <div class="mb-3 text-end">
            @if($expectedCount) {{-- فقط إذا كان هناك عدد متوقع --}}
            <form action="{{ route('data-entry.sections.generateFromButton') }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                <input type="hidden" name="plan_level" value="{{ $planLevel }}">
                <input type="hidden" name="plan_semester" value="{{ $planSemester }}">
                <input type="hidden" name="academic_year" value="{{ $academicYear }}">
                @if($branch) <input type="hidden" name="branch" value="{{ $branch }}"> @endif
                <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('This will regenerate sections based on expected counts for ALL subjects in this context (Plan Level {{ $planLevel }}, Plan Semester {{ $planSemester }}, Year {{ $academicYear }}). Existing sections for these subjects in this context will be deleted. Are you sure?');">
                    <i class="fas fa-cogs me-1"></i> Generate/Update All Sections for Context
                </button>
            </form>
            @else
            <p class="text-danger text-end small">Cannot generate sections: Expected student count is missing for this context.</p>
            @endif
        </div>


        {{-- جدول الشعب الحالية لكل مادة في هذا السياق --}}
        @if(!isset($planSubjectsForContext) || $planSubjectsForContext->isEmpty())
            <div class="alert alert-info">No subjects found in Plan: {{ $plan->plan_no }} / Level: {{ $planLevel }} / Plan Semester: {{ $planSemester }} to manage sections for.</div>
        @else
            @foreach($planSubjectsForContext as $ps) {{-- $ps هو PlanSubject --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            Subject: <strong class="text-primary">{{ optional($ps->subject)->subject_no }} - {{ optional($ps->subject)->subject_name }}</strong>
                            <small class="text-muted">({{ optional(optional($ps->subject)->subjectCategory)->subject_category_name }})</small>
                        </h5>
                        <button class="btn btn-primary btn-sm open-add-section-modal-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#addSectionModal"
                                data-plan-subject-id="{{ $ps->id }}"
                                data-academic-year="{{ $academicYear }}"
                                data-semester="{{ $planSemester }}" {{-- فصل الشعبة سيكون نفس فصل الخطة --}}
                                data-branch="{{ $branch }}"
                                data-subject-name="{{ optional($ps->subject)->subject_name }}">
                            <i class="fas fa-plus me-1"></i> Add Section
                        </button>
                    </div>
                    <div class="card-body">
                        @if(isset($currentSectionsBySubject[$ps->subject_id]) && $currentSectionsBySubject[$ps->subject_id]->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-sm">
                                    <thead class="table-light"><tr><th>#</th><th>Section No.</th><th>Gender</th><th>Branch</th><th>Students</th><th>Actions</th></tr></thead>
                                    <tbody>
                                        @foreach($currentSectionsBySubject[$ps->subject_id] as $index => $section)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $section->section_number }}</td>
                                                <td>{{ $section->section_gender }}</td>
                                                <td>{{ $section->branch ?? '-' }}</td>
                                                <td>{{ $section->student_count }}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editSectionModal-{{ $section->id }}" title="Edit">Edit</button>
                                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteSectionModal-{{ $section->id }}" title="Delete">Delete</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center">No sections defined yet for this subject in this context.</p>
                        @endif
                    </div>
                </div>
                 {{-- تضمين مودالات التعديل والحذف (لكل الشعب المعروضة) --}}
                 @if(isset($currentSectionsBySubject[$ps->subject_id]))
                    @foreach($currentSectionsBySubject[$ps->subject_id] as $section)
                        @include('dashboard.data-entry.partials._manage_section_modals', ['section' => $section])
                    @endforeach
                 @endif
            @endforeach
        @endif


         {{-- مودال إضافة شعبة جديدة (يستخدم لكافة المواد في هذه الصفحة) --}}
         @include('dashboard.data-entry.partials._manage_section_modals', [
             'section' => null,
             // القيم المخفية سيتم تعبئتها بواسطة JavaScript
             // 'planSubjectId_modal_add' => null, // سيستخدم JS data-plan-subject-id
             // 'academicYear_modal_add' => $academicYear, // يمكن تمريرها أو أخذها من data-attribute
             // 'semester_modal_add' => $semester, // يمكن تمريرها أو أخذها من data-attribute
             // 'branch_modal_add' => $branch
         ])
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
$(document).ready(function() {
    // --- التعامل مع مودال إضافة شعبة جديدة ---
    $('#addSectionModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        // استخراج البيانات من data attributes للزر
        var planSubjectId = button.data('plan-subject-id');
        var academicYear = button.data('academic-year');
        var semester = button.data('semester');
        var branch = button.data('branch') !== undefined ? button.data('branch') : ''; // Handle undefined branch
        var subjectName = button.data('subject-name');

        var modal = $(this);
        // تحديث عنوان المودال
        modal.find('.modal-title').text('Add New Section for: ' + (subjectName || 'Selected Subject'));

        // تعبئة الحقول المخفية في الفورم
        modal.find('input[name="plan_subject_id"]').val(planSubjectId);
        modal.find('input[name="academic_year"]').val(academicYear);
        modal.find('input[name="semester"]').val(semester);
        modal.find('input[name="branch"]').val(branch);

        // جعل حقل الفرع للقراءة فقط إذا كان الفرع محدداً بالسياق من الزر
        var branchInput = modal.find('input[name="branch"]');
        if (branch !== '' && branch !== undefined) {
            branchInput.prop('readonly', true);
        } else {
            branchInput.prop('readonly', false).attr('placeholder', 'Leave blank for default/main');
        }

        // مسح أي قيم أو أخطاء سابقة من الحقول الأخرى
        modal.find('input[name="section_number"]').val('1'); // قيمة افتراضية
        modal.find('input[name="student_count"]').val('0'); // قيمة افتراضية
        modal.find('select[name="section_gender"]').val('Mixed'); // قيمة افتراضية
        modal.find('.is-invalid').removeClass('is-invalid');
        modal.find('.invalid-feedback, .text-danger.small').remove();
        modal.find('.alert-danger').remove();
    });

});
</script>
@endpush
