@extends('dashboard.layout')

{{-- سنبقي Select2 للبحث في المواد --}}
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    /* يمكن إزالة ستايل z-index إذا لم نعد نستخدم مودال */
    /* .select2-container--bootstrap-5 .select2-dropdown { z-index: 1060; } */
    .select2-container--bootstrap-5 .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height: 1.5; }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow { height: calc(1.5em + .75rem); }
    .list-group-item.empty-list { background-color: #f8f9fa; }
    /* لإظهار الفورم عند الحاجة */
    .add-subject-form { display: none; margin-top: 10px; }
</style>
@endpush

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="data-entry-header mb-0">Manage Subjects for Plan: <span class="text-primary">{{ $plan->plan_name }}</span> ({{ $plan->plan_no }})</h1>
            <a href="{{ route('data-entry.plans.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Plans List
            </a>
        </div>

        @include('dashboard.data-entry.partials._status_messages')

        @php
            $planSubjectsGrouped = $plan->planSubjectEntries()->with('subject:id,subject_no,subject_name')->get()->groupBy(['plan_level', 'plan_semester']);
            $addedSubjectIds = $plan->planSubjectEntries()->pluck('subject_id')->toArray(); // IDs المواد المضافة للخطة
            $allSubjects = $allSubjects ?? \App\Models\Subject::orderBy('subject_name')->get(['id', 'subject_no', 'subject_name']); // جلب المواد إذا لم يتم تمريرها
            $levelsInPlan = $planSubjectsGrouped->keys()->max() ?: 1;
            $semestersInPlan = $planSubjectsGrouped->map(fn($level) => is_iterable($level) ? $level->keys()->max() : 0)->max() ?: 1;
            $maxLevelToDisplay = max(4, $levelsInPlan);
            $maxSemesterToDisplay = max(2, $semestersInPlan);
            // ddd
        #fff
        /*sscs*/
        @endphp

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
                                            <div class="card-header">
                                                <h6 class="mb-0">Semester {{ $semester }}</h6>
                                            </div>
                                            <div class="card-body p-2">
                                                {{-- عرض المواد المضافة --}}
                                                <ul class="list-group list-group-flush mb-2">
                                                    @forelse($planSubjectsGrouped[$level][$semester] ?? [] as $planSubject)
                                                        <li class="list-group-item d-flex justify-content-between align-items-center py-1 px-2">
                                                            <span class="small" title="{{ optional($planSubject->subject)->subject_name ?? 'N/A' }}">
                                                                <strong class="text-muted">{{ optional($planSubject->subject)->subject_no ?? '???' }}</strong> - {{ Str::limit(optional($planSubject->subject)->subject_name ?? 'N/A', 40) }}
                                                            </span>
                                                            <form action="{{ route('data-entry.plans.removeSubject', ['plan' => $plan->id, 'planSubject' => $planSubject->id]) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-0 px-1" title="Remove Subject">
                                                                    <i class="fas fa-times fa-xs"></i>
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @empty
                                                        <li class="list-group-item text-center text-muted small py-1 px-2 empty-list">No subjects added yet.</li>
                                                    @endforelse
                                                </ul>

                                                {{-- زر إظهار/إخفاء فورم الإضافة --}}
                                                <button class="btn btn-sm btn-outline-primary w-100 toggle-add-form-btn">
                                                    <i class="fas fa-plus"></i> Add Subject
                                                </button>

                                                {{-- فورم الإضافة الخاص بهذا الفصل/المستوى (مخفي افتراضياً) --}}
                                                <form action="{{ route('data-entry.plans.addSubject', $plan->id) }}" method="POST" class="add-subject-form border p-2 rounded bg-light">
                                                    @csrf
                                                    {{-- الحقول المخفية بالقيم الصحيحة مباشرة --}}
                                                    <input type="hidden" name="plan_level" value="{{ $level }}">
                                                    <input type="hidden" name="plan_semester" value="{{ $semester }}">

                                                    <div class="mb-2">
                                                        <label for="subject_id_{{$level}}_{{$semester}}" class="form-label visually-hidden">Select Subject</label>
                                                        {{-- استخدام Select2 هنا --}}
                                                        <select class="form-select form-select-sm select2-subjects @error('subject_id', 'addSubject_'.$level.'_'.$semester) is-invalid @enderror" id="subject_id_{{$level}}_{{$semester}}" name="subject_id" required style="width: 100%;" data-placeholder="Select subject to add...">
                                                            <option value=""></option> {{-- خيار فارغ للـ placeholder --}}
                                                            @foreach ($allSubjects as $subject)
                                                                {{-- إخفاء المواد المضافة بالفعل في كل الخطة --}}
                                                                @if(!in_array($subject->id, $addedSubjectIds ?? []))
                                                                    <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                                                        {{ $subject->subject_no }} - {{ $subject->subject_name }}
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                         {{-- عرض الأخطاء الخاصة بهذا الفورم --}}
                                                        @error('subject_id', 'addSubject_'.$level.'_'.$semester) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                                        @error('plan_level', 'addSubject_'.$level.'_'.$semester) <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                                        @error('plan_semester', 'addSubject_'.$level.'_'.$semester) <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                                         @if($errors->hasBag('addSubject_'.$level.'_'.$semester) && !$errors->getBag('addSubject_'.$level.'_'.$semester)->has('subject_id'))
                                                            <div class="text-danger small mt-1">An unexpected error occurred for this form.</div>
                                                         @endif
                                                    </div>
                                                    <div class="text-end">
                                                         <button type="submit" class="btn btn-sm btn-success">
                                                             <i class="fas fa-check"></i> Save
                                                         </button>
                                                         <button type="button" class="btn btn-sm btn-secondary cancel-add-btn">
                                                            Cancel
                                                         </button>
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
        {{-- لم نعد بحاجة لمودال منفصل للإضافة --}}
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // تهيئة كل قوائم Select2 في الصفحة
        $('.select2-subjects').select2({
            theme: 'bootstrap-5',
            // لا نحتاج dropdownParent لأن القائمة ليست داخل مودال الآن
            // dropdownParent: $('#addSubjectToPlanModal')
        });

        // عند الضغط على زر "Add Subject"
        $('.toggle-add-form-btn').on('click', function() {
            // إخفاء كل فورمات الإضافة الأخرى في نفس المستوى/الفصل (إذا وجدت)
             $(this).closest('.card-body').find('.add-subject-form').slideUp();
            // إظهار الفورم الخاص بهذا الزر
             $(this).next('.add-subject-form').slideDown();
             // إعادة تهيئة Select2 داخل الفورم الذي تم إظهاره (مهم ليعمل البحث)
             $(this).next('.add-subject-form').find('.select2-subjects').select2({
                 theme: 'bootstrap-5'
             });
             // إخفاء زر الإضافة نفسه
             $(this).hide();
        });

        // عند الضغط على زر "Cancel"
        $('.cancel-add-btn').on('click', function() {
            // إخفاء الفورم
             $(this).closest('.add-subject-form').slideUp();
             // إعادة إظهار زر الإضافة
             $(this).closest('.card-body').find('.toggle-add-form-btn').show();
        });

         // إذا كان هناك خطأ validation في أحد الفورمات، قم بإظهاره تلقائياً عند تحميل الصفحة
         $('.add-subject-form').each(function() {
            if ($(this).find('.is-invalid').length > 0 || $(this).find('.text-danger').length > 0) {
                $(this).show();
                 $(this).closest('.card-body').find('.toggle-add-form-btn').hide();
                 // تهيئة Select2 للفورم الذي به خطأ
                 $(this).find('.select2-subjects').select2({
                    theme: 'bootstrap-5'
                 });
            }
         });

    });
</script>
@endpush
