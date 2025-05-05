@extends('dashboard.layout')

{{-- يمكن استخدام Select2 هنا أيضاً --}}
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

        /* ستايل لقائمة المواد */
        .subject-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #eee;
            padding: 10px;
            border-radius: 5px;
        }

        .subject-list .form-check {
            margin-bottom: 5px;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="data-entry-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="data-entry-header mb-0">Assign Subjects to Instructors</h1>
                {{-- يمكن إضافة زر للعودة أو تركه --}}
            </div>

            @include('dashboard.data-entry.partials._status_messages')

            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('data-entry.instructor-subject.sync') }}" method="POST">
                        @csrf
                        <div class="row">
                            {{-- اختيار المدرس --}}
                            <div class="col-md-5 mb-3">
                                <label for="instructor_id" class="form-label">Select Instructor <span
                                        class="text-danger">*</span></label>
                                <select class="form-select select2-instructors" id="instructor_id" name="instructor_id"
                                    required data-placeholder="Select an instructor...">
                                    <option value=""></option>
                                    @foreach ($instructors as $instructor)
                                        {{-- عرض اسم المدرس من اليوزر أو سجل المدرس --}}
                                        <option value="{{ $instructor->id }}"
                                            {{ old('instructor_id', $selectedInstructorId ?? '') == $instructor->id ? 'selected' : '' }}>
                                            {{ $instructor->instructor_no }} -
                                            {{ $instructor->instructor_name ?? optional($instructor->user)->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('instructor_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- عرض المواد (تظهر بعد اختيار المدرس) --}}
                            <div class="col-md-7 mb-3" id="subjects_section"
                                style="{{ isset($subjects) ? '' : 'display: none;' }}"> {{-- إخفاء مبدئي --}}
                                <label class="form-label">Assignable Subjects</label>
                                {{-- حقل بحث للمواد (اختياري) --}}
                                <input type="text" id="subjectSearchInput" class="form-control form-control-sm mb-2"
                                    placeholder="Search subjects...">
                                {{-- قائمة المواد مع checkboxes --}}
                                <div class="subject-list border p-2 rounded" style="max-height: 300px; overflow-y: auto;">
                                    @if (isset($subjects))
                                        @foreach ($subjects as $subject)
                                            <div class="form-check subject-item">
                                                <input class="form-check-input" type="checkbox" name="subject_ids[]"
                                                    {{-- اسم الحقل كمصفوفة --}} value="{{ $subject->id }}"
                                                    id="subject_{{ $subject->id }}" {{-- تحديد المربع إذا كانت المادة مرتبطة بالمدرس المختار --}}
                                                    {{ in_array($subject->id, $assignedSubjectIds ?? []) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="subject_{{ $subject->id }}">
                                                    {{ $subject->subject_no }} - {{ $subject->subject_name }}
                                                    ({{ optional($subject->department)->department_name ?? 'N/A' }})
                                                    {{-- عرض قسم المادة --}}
                                                </label>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted text-center" id="subjectPlaceholder">Please select an
                                            instructor first.</p>
                                    @endif
                                    <p class="text-muted text-center mt-2" id="noSubjectResults" style="display: none;">No
                                        subjects match your search.</p>
                                </div>
                                @error('subject_ids')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary" id="saveButton"
                                {{ isset($subjects) ? '' : 'disabled' }}> {{-- تعطيل الزر مبدئياً --}}
                                <i class="fas fa-save me-1"></i> Save Assignments
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // تهيئة Select2 للمدرسين
            $('.select2-instructors').select2({
                theme: 'bootstrap-5',
                // لا يحتاج dropdownParent لأنه ليس داخل مودال
            });

            // عند تغيير المدرس المختار
            $('#instructor_id').on('change', function() {
                const instructorId = $(this).val();
                const subjectsSection = $('#subjects_section');
                const subjectListDiv = subjectsSection.find('.subject-list');
                const placeholder = $('#subjectPlaceholder');
                const saveButton = $('#saveButton');
                const searchInput = $('#subjectSearchInput');

                if (instructorId) {
                    // إذا تم اختيار مدرس، أظهر قسم المواد (حتى لو فارغاً مؤقتاً)
                    subjectsSection.show();
                    saveButton.prop('disabled', false); // تفعيل زر الحفظ
                    placeholder.text('Loading subjects...').show(); // رسالة تحميل
                    subjectListDiv.empty(); // إفراغ القائمة القديمة
                    searchInput.val(''); // إفراغ البحث

                    // ----- طلب AJAX لجلب المواد المخصصة لهذا المدرس -----
                    // سنحتاج لروت ودالة كنترولر جديدة لهذا (مثلاً: /instructor-subject/get-subjects/{instructorId})
                    // في الوقت الحالي، سنفترض أن $subjects و $assignedSubjectIds يتم تمريرها دائماً
                    // أو يمكن إعادة تحميل الصفحة مع instructorId في الـ URL
                    // للتبسيط الآن، سنعتمد على إعادة تحميل الصفحة
                    window.location.href =
                        "{{ route('data-entry.instructor-subject.index') }}?instructor_id=" + instructorId;
                    // ------------------------------------------------------

                } else {
                    // إذا لم يتم اختيار مدرس، أخف القسم وعطل الزر
                    subjectsSection.hide();
                    saveButton.prop('disabled', true);
                    subjectListDiv.empty();
                    placeholder.text('Please select an instructor first.').show();
                }
            });

            // البحث في قائمة المواد
            $('#subjectSearchInput').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                let resultsFound = false;
                $('.subject-item').each(function() {
                    const labelText = $(this).find('.form-check-label').text().toLowerCase();
                    if (labelText.includes(searchTerm)) {
                        $(this).show();
                        resultsFound = true;
                    } else {
                        $(this).hide();
                    }
                });
                $('#noSubjectResults').toggle(!resultsFound);
            });

        });
    </script>
@endpush
