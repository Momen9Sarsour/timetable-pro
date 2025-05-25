{{-- ===================================================== --}}
{{-- مودال إضافة/تعديل شعبة (موحد) --}}
{{-- ===================================================== --}}
{{-- مودال الإضافة --}}
<div class="modal fade" id="addSectionModalUnified" tabindex="-1" aria-labelledby="addSectionModalLabelUnified" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSectionModalLabelUnified">Add New Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- الفورم يرسل لـ sections.store --}}
            <form id="addSectionFormUnified" action="{{ route('data-entry.sections.store') }}" method="POST">
                @csrf
                {{-- حقول مخفية للسياق --}}
                <input type="hidden" name="plan_subject_id" value=""> {{-- يملأ بـ JS --}}
                <input type="hidden" name="activity_type" value="">   {{-- يملأ بـ JS --}}
                <input type="hidden" name="academic_year" value=""> {{-- يملأ بـ JS --}}
                <input type="hidden" name="semester" value="">      {{-- يملأ بـ JS --}}
                <input type="hidden" name="branch" value="">        {{-- يملأ بـ JS --}}

                <div class="modal-body">
                    <div id="add_form_errors_placeholder_unified" class="mb-2"></div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_section_number_unified" class="form-label">Section Number <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('section_number', 'storeSectionModalUnified') is-invalid @enderror" id="add_section_number_unified" name="section_number" value="{{ old('section_number', 1) }}" required min="1">
                            @error('section_number', 'storeSectionModalUnified') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @error('section_unique', 'storeSectionModalUnified') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_student_count_unified" class="form-label">Student Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('student_count', 'storeSectionModalUnified') is-invalid @enderror" id="add_student_count_unified" name="student_count" value="{{ old('student_count', 0) }}" required min="0">
                            @error('student_count', 'storeSectionModalUnified') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_section_gender_unified" class="form-label">Section Gender <span class="text-danger">*</span></label>
                            <select class="form-select @error('section_gender', 'storeSectionModalUnified') is-invalid @enderror" id="add_section_gender_unified" name="section_gender" required>
                                <option value="Mixed" {{ old('section_gender', 'Mixed') == 'Mixed' ? 'selected' : '' }}>Mixed</option>
                                <option value="Male" {{ old('section_gender') == 'Male' ? 'selected' : '' }}>Male Only</option>
                                <option value="Female" {{ old('section_gender') == 'Female' ? 'selected' : '' }}>Female Only</option>
                            </select>
                            @error('section_gender', 'storeSectionModalUnified') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="add_branch_display_unified" class="form-label">Branch (from context)</label>
                            <input type="text" class="form-control" id="add_branch_display_unified" readonly>
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

{{-- مودال التعديل (واحد مشترك) --}}
<div class="modal fade" id="editSectionModalUnified" tabindex="-1" aria-labelledby="editSectionModalLabelUnified" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSectionModalLabelUnified">Edit Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form id="editSectionFormUnified" action="#" method="POST"> {{-- Action يتغير بـ JS --}}
                @csrf
                @method('PUT')
                <div class="modal-body">
                     <div class="alert alert-secondary small p-2 mb-3" id="edit_context_info_text_unified">
                         <strong>Context:</strong> <span>Loading...</span>
                     </div>
                     <div id="edit_form_errors_placeholder_unified" class="mb-2"></div>
                     {{-- الحقول القابلة للتعديل --}}
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_section_number_unified" class="form-label">Section Number <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_section_number_unified" name="section_number" value="" required min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_student_count_unified" class="form-label">Student Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_student_count_unified" name="student_count" value="" required min="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_section_gender_unified" class="form-label">Section Gender <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_section_gender_unified" name="section_gender" required>
                                <option value="Mixed">Mixed</option>
                                <option value="Male">Male Only</option>
                                <option value="Female">Female Only</option>
                            </select>
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="edit_branch_display_unified" class="form-label">Branch (from context)</label>
                            <input type="text" class="form-control" id="edit_branch_display_unified" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- مودال الحذف (واحد مشترك) --}}
<div class="modal fade" id="deleteSectionModalUnified" tabindex="-1" aria-labelledby="deleteSectionModalLabelUnified" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSectionModalLabelUnified">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form id="deleteSectionFormUnified" action="#" method="POST"> {{-- Action يتغير بـ JS --}}
                @csrf
                @method('DELETE')
                <div class="modal-body">
                     <p>Are you sure you want to delete <strong id="deleteSectionInfoTextUnified">this section</strong>?</p>
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
