{{-- ========================== --}}
{{-- Modal لإضافة شعبة جديدة --}}
{{-- ========================== --}}
@if (!$section)
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSectionModalLabel">Add New Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('data-entry.sections.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info small p-2">
                        Define a new section (group of students) for a specific subject within an academic plan, level, and semester.
                    </div>
                     <div class="row">
                         <div class="col-md-6 mb-3">
                            <label for="add_academic_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('academic_year', 'store') is-invalid @enderror" id="add_academic_year" name="academic_year" value="{{ old('academic_year', date('Y')) }}" required placeholder="YYYY" min="2020" max="{{ date('Y') + 5 }}">
                            @error('academic_year', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="add_semester" class="form-label">Semester <span class="text-danger">*</span></label>
                            <select class="form-select @error('semester', 'store') is-invalid @enderror" id="add_semester" name="semester" required>
                                <option value="" selected disabled>Select semester...</option>
                                <option value="1" {{ old('semester') == '1' ? 'selected' : '' }}>First</option>
                                <option value="2" {{ old('semester') == '2' ? 'selected' : '' }}>Second</option>
                                <option value="3" {{ old('semester') == '3' ? 'selected' : '' }}>Summer</option>
                            </select>
                            @error('semester', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        {{-- اختيار المادة في الخطة (الأكثر تعقيداً) --}}
                        <label for="add_plan_subject_id" class="form-label">Subject in Plan (Plan / Level / Subject) <span class="text-danger">*</span></label>
                        <select class="form-select @error('plan_subject_id', 'store') is-invalid @enderror" id="add_plan_subject_id" name="plan_subject_id" required data-placeholder="Select subject from a plan...">
                             <option value=""></option> {{-- للخيار الافتراضي لـ Select2 --}}
                             @if(isset($planSubjects))
                                 @php
                                     // تجميع حسب الخطة ثم المستوى لسهولة العرض
                                     $groupedPlanSubjects = $planSubjects->groupBy(['plan.plan_name', 'plan_level']);
                                 @endphp
                                 @foreach($groupedPlanSubjects as $planName => $levels)
                                     <optgroup label="Plan: {{ $planName }}">
                                         @foreach($levels as $level => $subjects)
                                             <optgroup label="  Level: {{ $level }}">
                                                 @foreach($subjects as $ps)
                                                     <option value="{{ $ps->id }}" {{ old('plan_subject_id') == $ps->id ? 'selected' : '' }}>
                                                             Sem:{{$ps->plan_semester}} - {{ optional($ps->subject)->subject_no }} - {{ optional($ps->subject)->subject_name }}
                                                     </option>
                                                 @endforeach
                                             </optgroup>
                                         @endforeach
                                     </optgroup>
                                 @endforeach
                             @endif
                        </select>
                         @error('plan_subject_id', 'store') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                         {{-- يمكن إضافة رابط لإنشاء خطة/مادة إذا لم تكن موجودة --}}
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_section_number" class="form-label">Section Number <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('section_number', 'store') is-invalid @enderror" id="add_section_number" name="section_number" value="{{ old('section_number', 1) }}" required min="1">
                            <small class="text-muted">Usually 1, 2, 3...</small>
                            @error('section_number', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                             @error('section_unique', 'store') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_student_count" class="form-label">Allocated Students <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('student_count', 'store') is-invalid @enderror" id="add_student_count" name="student_count" value="{{ old('student_count', 0) }}" required min="0">
                            @error('student_count', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_section_gender" class="form-label">Section Gender <span class="text-danger">*</span></label>
                            <select class="form-select @error('section_gender', 'store') is-invalid @enderror" id="add_section_gender" name="section_gender" required>
                                <option value="Mixed" {{ old('section_gender', 'Mixed') == 'Mixed' ? 'selected' : '' }}>Mixed</option>
                                <option value="Male" {{ old('section_gender') == 'Male' ? 'selected' : '' }}>Male Only</option>
                                <option value="Female" {{ old('section_gender') == 'Female' ? 'selected' : '' }}>Female Only</option>
                            </select>
                            @error('section_gender', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="add_branch" class="form-label">Branch (Optional)</label>
                            <input type="text" class="form-control @error('branch', 'store') is-invalid @enderror" id="add_branch" name="branch" value="{{ old('branch') }}" placeholder="e.g., Main, North">
                            @error('branch', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Section</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ========================== --}}
{{-- Modals التعديل والحذف --}}
{{-- ========================== --}}
@if ($section)

{{-- Modal لتعديل شعبة --}}
<div class="modal fade" id="editSectionModal-{{ $section->id }}" tabindex="-1" aria-labelledby="editSectionModalLabel-{{ $section->id }}" aria-hidden="true">
     <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSectionModalLabel-{{ $section->id }}">Edit Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form action="{{ route('data-entry.sections.update', $section->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    {{-- نفس حقول الإضافة مع القيم الحالية --}}
                    {{-- قد تحتاج لجعل اختيار plan_subject_id للقراءة فقط أو استخدام طريقة أخرى --}}
                    <div class="alert alert-secondary small p-2">
                        Editing Section #{{ $section->section_number }} for Subject:
                        <strong>{{ optional(optional($section->planSubject)->subject)->subject_no }} - {{ optional(optional($section->planSubject)->subject)->subject_name }}</strong>
                        (Plan: {{ optional(optional($section->planSubject)->plan)->plan_no }},
                         Level: {{ optional($section->planSubject)->plan_level }},
                         Semester: {{ optional($section->planSubject)->plan_semester }})
                         <br>Academic Year: {{ $section->academic_year }} / Semester: {{ $section->semester }}
                    </div>
                     {{-- حقل مخفي لتمرير plan_subject_id الأصلي (إذا لم نسمح بتغييره) --}}
                    <input type="hidden" name="plan_subject_id" value="{{ $section->plan_subject_id }}">
                    {{-- حقول السنة والفصل (يمكن جعلها للقراءة فقط أيضاً) --}}
                    <input type="hidden" name="academic_year" value="{{ $section->academic_year }}">
                    <input type="hidden" name="semester" value="{{ $section->semester }}">


                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_section_number_{{ $section->id }}" class="form-label">Section Number <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('section_number', 'update_'.$section->id) is-invalid @enderror" id="edit_section_number_{{ $section->id }}" name="section_number" value="{{ old('section_number', $section->section_number) }}" required min="1">
                             @error('section_number', 'update_'.$section->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                              @error('section_unique', 'update_'.$section->id) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_student_count_{{ $section->id }}" class="form-label">Allocated Students <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('student_count', 'update_'.$section->id) is-invalid @enderror" id="edit_student_count_{{ $section->id }}" name="student_count" value="{{ old('student_count', $section->student_count) }}" required min="0">
                             @error('student_count', 'update_'.$section->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_section_gender_{{ $section->id }}" class="form-label">Section Gender <span class="text-danger">*</span></label>
                            <select class="form-select @error('section_gender', 'update_'.$section->id) is-invalid @enderror" id="edit_section_gender_{{ $section->id }}" name="section_gender" required>
                                <option value="Mixed" {{ old('section_gender', $section->section_gender) == 'Mixed' ? 'selected' : '' }}>Mixed</option>
                                <option value="Male" {{ old('section_gender', $section->section_gender) == 'Male' ? 'selected' : '' }}>Male Only</option>
                                <option value="Female" {{ old('section_gender', $section->section_gender) == 'Female' ? 'selected' : '' }}>Female Only</option>
                            </select>
                             @error('section_gender', 'update_'.$section->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="edit_branch_{{ $section->id }}" class="form-label">Branch (Optional)</label>
                            <input type="text" class="form-control @error('branch', 'update_'.$section->id) is-invalid @enderror" id="edit_branch_{{ $section->id }}" name="branch" value="{{ old('branch', $section->branch) }}">
                             @error('branch', 'update_'.$section->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    {{-- عرض خطأ عام للتحديث --}}
                     @error('update_error', 'update_'.$section->id) <div class="alert alert-danger small p-2">{{ $message }}</div> @enderror

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal لتأكيد الحذف --}}
{{-- @if ($section->scheduleEntries()->count() == 0) --}} {{-- يمكنك إضافة هذا الشرط لاحقاً --}}
<div class="modal fade" id="deleteSectionModal-{{ $section->id }}" tabindex="-1" aria-labelledby="deleteSectionModalLabel-{{ $section->id }}" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSectionModalLabel-{{ $section->id }}">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form action="{{ route('data-entry.sections.destroy', $section->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p>Are you sure you want to delete Section #{{ $section->section_number }} for:</p>
                     <p><strong>Subject:</strong> {{ optional(optional($section->planSubject)->subject)->subject_no }}<br>
                        <strong>Plan:</strong> {{ optional(optional($section->planSubject)->plan)->plan_no }}<br>
                        <strong>Year:</strong> {{ $section->academic_year }} / <strong>Semester:</strong> {{ $section->semester }} / <strong>Level:</strong> {{ optional($section->planSubject)->plan_level }}
                     </p>
                    <p class="text-danger small">This action cannot be undone. Associated schedule entries might be affected.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete Section</button>
                </div>
            </form>
        </div>
    </div>
</div>
{{-- @endif --}}

@endif
