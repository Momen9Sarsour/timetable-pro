{{-- ===================================================== --}}
{{-- Modal لإضافة شعبة جديدة (في سياق محدد) --}}
{{-- ===================================================== --}}
@if (!$section) {{-- يُستخدم فقط عند استدعائه للإضافة --}}
<div class="modal fade" id="addSectionForContextModal" tabindex="-1" aria-labelledby="addSectionForContextModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSectionForContextModalLabel">Add New Section</h5> {{-- العنوان يتغير بـ JS --}}
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- الفورم يرسل لـ storeSectionInContext ويتوقع expectedCountId في الروت --}}
            <form action="#" method="POST"> {{-- الـ Action يتحدث بـ JS --}}
                @csrf
                {{-- الحقول المخفية لتحديد السياق (تملأ بـ JS من زر الفتح) --}}
                <input type="hidden" name="plan_subject_id_from_modal" value=""> {{-- هذا هو الـ ID للمادة في الخطة --}}
                {{-- لا نحتاج academic_year, semester, branch هنا لأنها ستؤخذ من expectedCount في الكنترولر --}}

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_modal_section_number_context" class="form-label">Section Number <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('section_number', 'storeSectionModal') is-invalid @enderror" id="add_modal_section_number_context" name="section_number" value="{{ old('section_number', 1) }}" required min="1">
                            @error('section_number', 'storeSectionModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @error('section_unique', 'storeSectionModal') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_modal_student_count_context" class="form-label">Student Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('student_count', 'storeSectionModal') is-invalid @enderror" id="add_modal_student_count_context" name="student_count" value="{{ old('student_count', 0) }}" required min="0">
                            @error('student_count', 'storeSectionModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_modal_section_gender_context" class="form-label">Section Gender <span class="text-danger">*</span></label>
                            <select class="form-select @error('section_gender', 'storeSectionModal') is-invalid @enderror" id="add_modal_section_gender_context" name="section_gender" required>
                                <option value="Mixed" {{ old('section_gender', 'Mixed') == 'Mixed' ? 'selected' : '' }}>Mixed</option>
                                <option value="Male" {{ old('section_gender') == 'Male' ? 'selected' : '' }}>Male Only</option>
                                <option value="Female" {{ old('section_gender') == 'Female' ? 'selected' : '' }}>Female Only</option>
                            </select>
                            @error('section_gender', 'storeSectionModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         {{-- الفرع يُعرض كمعلومة إذا كان السياق محدداً بفرع (من JS) --}}
                         <div class="col-md-6 mb-3">
                            <label for="add_modal_branch_context" class="form-label">Branch</label>
                            <input type="text" class="form-control" id="add_modal_branch_context" name="branch_from_modal" readonly placeholder="From context">
                            {{-- هذا الحقل للقراءة فقط، القيمة الفعلية للفرع ستؤخذ من expectedCount --}}
                        </div>
                    </div>
                     @error('msg', 'storeSectionModal') <div class="alert alert-danger small p-2">{{ $message }}</div> @enderror
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

{{-- ===================================================== --}}
{{-- Modals التعديل والحذف (لسياق محدد) --}}
{{-- ===================================================== --}}
@if ($section) {{-- هذا الجزء يُعرض فقط عندما يتم تمرير $section (داخل حلقة الشعب في manage-sections-for-context) --}}

{{-- Modal لتعديل شعبة --}}
<div class="modal fade" id="editSectionInContextModal-{{ $section->id }}" tabindex="-1" aria-labelledby="editSectionInContextModalLabel-{{ $section->id }}" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSectionInContextModalLabel-{{ $section->id }}">Edit Section #{{ $section->section_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form action="{{ route('data-entry.sections.updateInContext', $section->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                     <div class="alert alert-secondary small p-2 mb-3">
                         <strong>Context:</strong> {{ optional(optional($section->planSubject)->subject)->subject_no }} |
                         Yr: {{ $section->academic_year }} | Sem: {{ $section->semester }} |
                         Branch: {{ $section->branch ?? 'Default' }}
                     </div>
                     {{-- حقول مخفية للـ validation (إذا احتجتها في الكنترولر للتحقق من التفرد) --}}
                    {{-- <input type="hidden" name="plan_subject_id" value="{{ $section->plan_subject_id }}">
                    <input type="hidden" name="academic_year" value="{{ $section->academic_year }}">
                    <input type="hidden" name="semester" value="{{ $section->semester }}">
                    @if($section->branch) <input type="hidden" name="branch" value="{{ $section->branch }}"> @endif --}}


                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_context_section_number_{{ $section->id }}" class="form-label">Section Number <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('section_number', 'updateSectionModal_'.$section->id) is-invalid @enderror" id="edit_context_section_number_{{ $section->id }}" name="section_number" value="{{ old('section_number', $section->section_number) }}" required min="1">
                             @error('section_number', 'updateSectionModal_'.$section->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                              @error('section_unique', 'updateSectionModal_'.$section->id) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_context_student_count_{{ $section->id }}" class="form-label">Student Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('student_count', 'updateSectionModal_'.$section->id) is-invalid @enderror" id="edit_context_student_count_{{ $section->id }}" name="student_count" value="{{ old('student_count', $section->student_count) }}" required min="0">
                             @error('student_count', 'updateSectionModal_'.$section->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_context_section_gender_{{ $section->id }}" class="form-label">Section Gender <span class="text-danger">*</span></label>
                            <select class="form-select @error('section_gender', 'updateSectionModal_'.$section->id) is-invalid @enderror" id="edit_context_section_gender_{{ $section->id }}" name="section_gender" required>
                                <option value="Mixed" {{ old('section_gender', $section->section_gender) == 'Mixed' ? 'selected' : '' }}>Mixed</option>
                                <option value="Male" {{ old('section_gender', $section->section_gender) == 'Male' ? 'selected' : '' }}>Male Only</option>
                                <option value="Female" {{ old('section_gender', $section->section_gender) == 'Female' ? 'selected' : '' }}>Female Only</option>
                            </select>
                             @error('section_gender', 'updateSectionModal_'.$section->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         {{-- الفرع للقراءة فقط لأنه جزء من السياق --}}
                         <div class="col-md-6 mb-3">
                            <label for="edit_context_branch_{{ $section->id }}" class="form-label">Branch</label>
                            <input type="text" class="form-control" id="edit_context_branch_{{ $section->id }}" name="branch_display" value="{{ $section->branch ?? 'Default' }}" readonly>
                        </div>
                    </div>
                     @error('update_error', 'updateSectionModal_'.$section->id) <div class="alert alert-danger small p-2">{{ $message }}</div> @enderror
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
<div class="modal fade" id="deleteSectionInContextModal-{{ $section->id }}" tabindex="-1" aria-labelledby="deleteSectionInContextModalLabel-{{ $section->id }}" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSectionInContextModalLabel-{{ $section->id }}">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             {{-- الـ action يرسل لـ destroySectionInContext --}}
             <form action="{{ route('data-entry.sections.destroyInContext', $section->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                     <p>Are you sure you want to delete Section #{{ $section->section_number }}?</p>
                     <p class="small text-muted">For Subject: {{ optional(optional($section->planSubject)->subject)->subject_no }} in Year: {{ $section->academic_year }} / Term: {{ $section->semester }}</p>
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
