@extends('dashboard.layout')

{{-- لا نحتاج ستايلات Select2 الآن --}}
@push('styles')
<style>
    /* فقط الستايلات الأساسية */
    .list-group-item.empty-list { background-color: #f8f9fa; }
    .modal-dialog { display: flex; align-items: center; min-height: calc(100% - 1rem); }
    @media (min-width: 576px) { .modal-dialog { min-height: calc(100% - 3.5rem); } }
</style>
@endpush

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        {{-- Header and Back Button --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="data-entry-header mb-0">Manage Subjects for Plan: <span class="text-primary">{{ $plan->plan_name }}</span> ({{ $plan->plan_no }})</h1>
            <a href="{{ route('data-entry.plans.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Plans List
            </a>
        </div>

        {{-- Status Messages --}}
        @include('dashboard.partials._status_messages')

        @php
            // Prepare Data
            $planSubjectsGrouped = $plan->planSubjectEntries()->with('subject:id,subject_no,subject_name')->get()->groupBy(['plan_level', 'plan_semester']);
            $addedSubjectIds = $plan->planSubjectEntries()->pluck('subject_id')->toArray();
            $allSubjects = $allSubjects ?? \App\Models\Subject::orderBy('subject_name')->get(['id', 'subject_no', 'subject_name']);
            $maxLevelToDisplay = max(4, $planSubjectsGrouped->keys()->max() ?: 1);
            $maxSemesterToDisplay = max(2, $planSubjectsGrouped->map(fn($level) => is_iterable($level) ? $level->keys()->max() : 0)->max() ?: 1);
        @endphp

        {{-- Accordion for Levels --}}
        <div class="accordion" id="planLevelsAccordion">
            @for ($level = 1; $level <= $maxLevelToDisplay; $level++)
                <div class="accordion-item mb-3 shadow-sm">
                    <h2 class="accordion-header" id="headingLevel{{ $level }}">
                        <button class="accordion-button {{ $level > 1 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLevel{{ $level }}" aria-expanded="{{ $level == 1 ? 'true' : 'false' }}" aria-controls="collapseLevel{{ $level }}">
                            Level {{ $level }}
                        </button>
                    </h2>
                    <div id="collapseLevel{{ $level }}" class="accordion-collapse collapse {{ $level == 1 ? 'show' : '' }}" aria-labelledby="headingLevel{{ $level }}" data-bs-parent="#planLevelsAccordion">
                        <div class="accordion-body">
                            <div class="row">
                                @for ($semester = 1; $semester <= $maxSemesterToDisplay; $semester++)
                                    <div class="col-lg-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-header d-flex justify-content-between align-items-center"> {{-- إعادة الـ d-flex --}}
                                                <h6 class="mb-0">Semester {{ $semester }}</h6>
                                                {{-- *** زر فتح مودال الإضافة *** --}}
                                                <button type="button" class="btn btn-sm btn-outline-primary open-add-subject-modal-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#addSubjectToPlanModal"
                                                        data-level="{{ $level }}"
                                                        data-semester="{{ $semester }}">
                                                    <i class="fas fa-plus"></i> Add Subject
                                                </button>
                                            </div>
                                            <div class="card-body p-3">
                                                {{-- Display Added Subjects --}}
                                                <ul class="list-group list-group-flush">
                                                    @forelse($planSubjectsGrouped[$level][$semester] ?? [] as $planSubject)
                                                        <li class="list-group-item d-flex justify-content-between align-items-center py-1 px-0">
                                                            <span class="small" title="{{ optional($planSubject->subject)->subject_name ?? 'N/A' }}">
                                                                <strong class="text-muted">{{ optional($planSubject->subject)->subject_no ?? '???' }}</strong> - {{ Str::limit(optional($planSubject->subject)->subject_name ?? 'N/A', 40) }}
                                                            </span>
                                                            {{-- زر فتح مودال الحذف --}}
                                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 p-0 px-1 open-delete-modal-btn"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#deletePlanSubjectModal"
                                                                    data-form-action="{{ route('data-entry.plans.removeSubject', ['plan' => $plan->id, 'planSubject' => $planSubject->id]) }}"
                                                                    data-subject-name="{{ optional($planSubject->subject)->subject_name ?? 'this subject' }}"
                                                                    title="Remove Subject">
                                                                <i class="fas fa-times fa-xs"></i>
                                                            </button>
                                                        </li>
                                                    @empty
                                                        <li class="list-group-item text-center text-muted small py-1 px-0 empty-list">No subjects added yet.</li>
                                                    @endforelse
                                                </ul>
                                                 {{-- تم إزالة فورم الإضافة المباشر من هنا --}}
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


        {{-- ================================= --}}
        {{-- *** المودالات مدمجة هنا *** --}}
        {{-- ================================= --}}

        {{-- 1. Modal لإضافة مادة للخطة --}}
        <div class="modal fade" id="addSubjectToPlanModal" tabindex="-1" aria-labelledby="addSubjectToPlanModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered"> {{-- توسيط --}}
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSubjectToPlanModalLabel">Add Subject to Plan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addSubjectToPlanForm" action="{{ route('data-entry.plans.addSubject', $plan->id) }}" method="POST">
                        @csrf
                        {{-- الحقول المخفية --}}
                        <input type="hidden" name="plan_level" id="modal_plan_level" value="">
                        <input type="hidden" name="plan_semester" id="modal_plan_semester" value="">

                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="modal_subject_id" class="form-label">Select Subject <span class="text-danger">*</span></label>
                                {{-- استخدام select عادي الآن --}}
                                <select class="form-select @error('subject_id', 'addSubject') is-invalid @enderror" id="modal_subject_id" name="subject_id" required>
                                    <option value="" selected disabled>-- Select Subject --</option>
                                    @isset($allSubjects)
                                        @foreach ($allSubjects as $subject)
                                            {{-- إخفاء المواد المضافة بالفعل في كل الخطة --}}
                                            @if(!in_array($subject->id, $addedSubjectIds ?? []))
                                                <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                                    {{ $subject->subject_no }} - {{ $subject->subject_name }}
                                                </option>
                                            @endif
                                        @endforeach
                                    @endisset
                                </select>
                                {{-- عرض الأخطاء الخاصة بمودال الإضافة --}}
                                @error('subject_id', 'addSubject') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                @error('plan_level', 'addSubject') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                @error('plan_semester', 'addSubject') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                @if($errors->hasBag('addSubject') && !$errors->getBag('addSubject')->has('subject_id') && !$errors->getBag('addSubject')->has('plan_level') && !$errors->getBag('addSubject')->has('plan_semester'))
                                    <div class="text-danger small mt-1">{{ $errors->getBag('addSubject')->first('msg') ?? 'Could not add subject.' }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add Subject to Plan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        {{-- نهاية مودال الإضافة --}}


        {{-- 2. Modal لتأكيد حذف مادة من الخطة --}}
        <div class="modal fade" id="deletePlanSubjectModal" tabindex="-1" aria-labelledby="deletePlanSubjectModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
               <div class="modal-content">
                   <div class="modal-header bg-danger text-white">
                       <h5 class="modal-title" id="deletePlanSubjectModalLabel">Confirm Subject Removal</h5>
                       <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                   </div>
                    <form id="deletePlanSubjectForm" action="#" method="POST"> {{-- Action يتحدث بـ JS --}}
                       @csrf
                       @method('DELETE')
                       <div class="modal-body">
                           <p>Are you sure you want to remove the subject <strong id="subjectNameToDelete">this subject</strong> from the plan?</p>
                           <p class="text-danger small">This action cannot be undone.</p>
                       </div>
                       <div class="modal-footer">
                           <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                           <button type="submit" class="btn btn-danger">Yes, Remove Subject</button>
                       </div>
                   </form>
               </div>
           </div>
        </div>
        {{-- نهاية مودال الحذف --}}


    </div> {{-- نهاية data-entry-container --}}
</div> {{-- نهاية main-content --}}
@endsection

{{-- *** الكود JavaScript النهائي للتعامل مع المودالات *** --}}
@push('scripts')
{{-- لا نحتاج jQuery أو Select2 الآن --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded. Initializing modal listeners...');

        // --- التعامل مع مودال الإضافة ---
        const addSubjectModalEl = document.getElementById('addSubjectToPlanModal');
        if (addSubjectModalEl) {
            const modalLevelInput = addSubjectModalEl.querySelector('#modal_plan_level');
            const modalSemesterInput = addSubjectModalEl.querySelector('#modal_plan_semester');
            const modalTitle = addSubjectModalEl.querySelector('.modal-title');
            const subjectSelect = addSubjectModalEl.querySelector('#modal_subject_id'); // Select العادي

            addSubjectModalEl.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // الزر الذي فتح المودال

                if (button && button.hasAttribute('data-level') && button.hasAttribute('data-semester')) {
                    const level = button.getAttribute('data-level');
                    const semester = button.getAttribute('data-semester');

                    // تعيين القيم للحقول المخفية - تأكد من وجود العناصر
                    if(modalLevelInput) modalLevelInput.value = level; else console.error('#modal_plan_level missing!');
                    if(modalSemesterInput) modalSemesterInput.value = semester; else console.error('#modal_plan_semester missing!');
                    if(modalTitle) modalTitle.textContent = `Add Subject to Level ${level}, Semester ${semester}`;

                    // إعادة تعيين قيمة الـ select
                    if(subjectSelect) subjectSelect.value = '';

                    console.log(`Add modal opened for L${level} S${semester}. Hidden fields set.`);

                } else {
                     console.error("Add modal trigger button or data attributes missing!", button);
                     // يمكنك منع فتح المودال هنا إذا أردت
                     // event.preventDefault();
                }
            });
             console.log('Add modal listener attached.');
        } else {
            console.error('#addSubjectToPlanModal not found.');
        }


        // --- التعامل مع مودال الحذف ---
        const deleteModalEl = document.getElementById('deletePlanSubjectModal');
        if (deleteModalEl) {
            const deleteForm = deleteModalEl.querySelector('#deletePlanSubjectForm'); // الفورم
            const subjectNameSpan = deleteModalEl.querySelector('#subjectNameToDelete'); // الـ span

             if(!deleteForm) console.error('#deletePlanSubjectForm missing!');
             if(!subjectNameSpan) console.error('#subjectNameToDelete missing!');

            deleteModalEl.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                if (button && deleteForm && subjectNameSpan) {
                    const formAction = button.getAttribute('data-form-action');
                    const subjectName = button.getAttribute('data-subject-name');

                    console.log('[Delete Modal] Opening. Action:', formAction, 'Subject:', subjectName);

                    // **تحديث الـ action واسم المادة**
                    if (formAction) {
                        deleteForm.action = formAction;
                        console.log('Delete form action updated to:', deleteForm.action);
                    } else {
                         console.error("Action URL is missing from data-form-action!");
                         // منع الإرسال إذا لم يوجد رابط
                         $(deleteForm).find('button[type="submit"]').prop('disabled', true);
                    }

                    subjectNameSpan.textContent = subjectName || 'this subject';
                     $(deleteForm).find('button[type="submit"]').prop('disabled', false); // تفعيل الزر

                } else {
                    console.error('Delete modal trigger button or essential modal elements missing.');
                     if(deleteForm) $(deleteForm).find('button[type="submit"]').prop('disabled', true); // تعطيل زر الحذف كإجراء وقائي
                }
            });
             console.log('Delete modal listener attached.');
        } else {
            console.error('#deletePlanSubjectModal not found.');
        }

    }); // نهاية DOMContentLoaded
</script>
@endpush
