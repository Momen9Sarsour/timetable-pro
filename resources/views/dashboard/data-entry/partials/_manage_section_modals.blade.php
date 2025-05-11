{{-- ========================== --}}
{{-- Modal لإضافة شعبة جديدة --}}
{{-- ========================== --}}
@if (!$section) {{-- هذا المودال يُستخدم فقط للإضافة في سياق محدد --}}
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSectionModalLabel">Add New Section</h5> {{-- العنوان يتغير بـ JS --}}
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('data-entry.sections.store') }}" method="POST">
                @csrf
                {{-- حقول مخفية لتحديد السياق (تملأ بـ JS) --}}
                <input type="hidden" name="plan_subject_id" value="">
                <input type="hidden" name="academic_year" value="">
                <input type="hidden" name="semester" value=""> {{-- فصل الشعبة --}}
                <input type="hidden" name="branch" value=""> {{-- قد يكون فارغاً --}}

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_modal_section_number" class="form-label">Section Number <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('section_number', 'storeSection') is-invalid @enderror" id="add_modal_section_number" name="section_number" value="{{ old('section_number', 1) }}" required min="1">
                            @error('section_number', 'storeSection') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @error('section_unique', 'storeSection') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_modal_student_count" class="form-label">Student Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('student_count', 'storeSection') is-invalid @enderror" id="add_modal_student_count" name="student_count" value="{{ old('student_count', 0) }}" required min="0">
                            @error('student_count', 'storeSection') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_modal_section_gender" class="form-label">Section Gender <span class="text-danger">*</span></label>
                            <select class="form-select @error('section_gender', 'storeSection') is-invalid @enderror" id="add_modal_section_gender" name="section_gender" required>
                                <option value="Mixed" {{ old('section_gender', 'Mixed') == 'Mixed' ? 'selected' : '' }}>Mixed</option>
                                <option value="Male" {{ old('section_gender') == 'Male' ? 'selected' : '' }}>Male Only</option>
                                <option value="Female" {{ old('section_gender') == 'Female' ? 'selected' : '' }}>Female Only</option>
                            </select>
                            @error('section_gender', 'storeSection') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         {{-- الفرع يكون للقراءة فقط إذا كان السياق محدداً بفرع --}}
                         <div class="col-md-6 mb-3">
                            <label for="add_modal_branch" class="form-label">Branch</label>
                            <input type="text" class="form-control @error('branch', 'storeSection') is-invalid @enderror" id="add_modal_branch" name="branch" value="{{ old('branch') }}" readonly placeholder="From context or leave blank">
                            @error('branch', 'storeSection') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                     @error('msg', 'storeSection') <div class="alert alert-danger small p-2">{{ $message }}</div> @enderror
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
@if ($section) {{-- هذا الجزء يُعرض فقط عندما يتم تمرير $section (داخل حلقة الشعب) --}}

{{-- Modal لتعديل شعبة --}}
<div class="modal fade" id="editSectionModal-{{ $section->id }}" tabindex="-1" aria-labelledby="editSectionModalLabel-{{ $section->id }}" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSectionModalLabel-{{ $section->id }}">Edit Section #{{ $section->section_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form action="{{ route('data-entry.sections.update', $section->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                     <div class="alert alert-secondary small p-2 mb-3">
                         <strong>Context:</strong> {{ optional(optional($section->planSubject)->subject)->subject_no }} |
                         Yr: {{ $section->academic_year }} | Sem: {{ $section->semester }}
                     </div>
                    {{-- حقول مخفية للـ validation في الكنترولر --}}
                    <input type="hidden" name="plan_subject_id" value="{{ $section->plan_subject_id }}">
                    <input type="hidden" name="academic_year" value="{{ $section->academic_year }}">
                    <input type="hidden" name="semester" value="{{ $section->semester }}">

                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_section_number_{{ $section->id }}" class="form-label">Section Number <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('section_number', 'updateSection_'.$section->id) is-invalid @enderror" id="edit_section_number_{{ $section->id }}" name="section_number" value="{{ old('section_number', $section->section_number) }}" required min="1">
                             @error('section_number', 'updateSection_'.$section->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                              @error('section_unique', 'updateSection_'.$section->id) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_student_count_{{ $section->id }}" class="form-label">Student Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('student_count', 'updateSection_'.$section->id) is-invalid @enderror" id="edit_student_count_{{ $section->id }}" name="student_count" value="{{ old('student_count', $section->student_count) }}" required min="0">
                             @error('student_count', 'updateSection_'.$section->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_section_gender_{{ $section->id }}" class="form-label">Section Gender <span class="text-danger">*</span></label>
                            <select class="form-select @error('section_gender', 'updateSection_'.$section->id) is-invalid @enderror" id="edit_section_gender_{{ $section->id }}" name="section_gender" required>
                                <option value="Mixed" {{ old('section_gender', $section->section_gender) == 'Mixed' ? 'selected' : '' }}>Mixed</option>
                                <option value="Male" {{ old('section_gender', $section->section_gender) == 'Male' ? 'selected' : '' }}>Male Only</option>
                                <option value="Female" {{ old('section_gender', $section->section_gender) == 'Female' ? 'selected' : '' }}>Female Only</option>
                            </select>
                             @error('section_gender', 'updateSection_'.$section->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="edit_branch_{{ $section->id }}" class="form-label">Branch</label>
                            <input type="text" class="form-control @error('branch', 'updateSection_'.$section->id) is-invalid @enderror" id="edit_branch_{{ $section->id }}" name="branch" value="{{ old('branch', $section->branch) }}" {{-- الفرع لا يتم تعديله إذا كان جزءاً من السياق الأساسي --}} {{ $section->branch ? 'readonly' : '' }}>
                             @error('branch', 'updateSection_'.$section->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                     @error('update_error', 'updateSection_'.$section->id) <div class="alert alert-danger small p-2">{{ $message }}</div> @enderror
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
                     <p>Are you sure you want to delete Section #{{ $section->section_number }}?</p>
                    <p class="text-danger small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete Section</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
