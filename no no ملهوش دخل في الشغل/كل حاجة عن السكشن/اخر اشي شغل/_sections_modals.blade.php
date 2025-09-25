{{-- ===================================================== --}}
{{-- مودال إضافة شعبة (جديد ومبسط) --}}
{{-- ===================================================== --}}
<div class="modal fade" id="universalAddSectionModal" tabindex="-1" aria-labelledby="universalAddSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="universalAddSectionModalLabel">Add New Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- الفورم يرسل لـ sections.store العام --}}
            <form id="universalAddSectionForm" action="{{ route('data-entry.sections.store') }}" method="POST">
                @csrf
                {{-- حقول السياق المخفية (يتم تعبئتها بـ JS) --}}
                <input type="hidden" name="plan_subject_id" id="add_modal_plan_subject_id_input" value="{{ old('plan_subject_id') }}">
                <input type="hidden" name="activity_type" id="add_modal_activity_type_input" value="{{ old('activity_type') }}">
                <input type="hidden" name="academic_year" id="add_modal_academic_year_input" value="{{ old('academic_year') }}">
                <input type="hidden" name="semester" id="add_modal_semester_input" value="{{ old('semester') }}">
                <input type="hidden" name="branch" id="add_modal_branch_input" value="{{ old('branch') }}">

                <div class="modal-body">
                     @if ($errors->hasBag('addSectionModal')) {{-- اسم الـ Error Bag --}}
                        <div class="alert alert-danger small p-2 mb-3">
                            <strong>Please correct the errors:</strong>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->getBag('addSectionModal')->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                     @endif
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_modal_section_number_input" class="form-label">Section Number <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('section_number', 'addSectionModal') is-invalid @enderror" id="add_modal_section_number_input" name="section_number" value="{{ old('section_number', 1) }}" required min="1">
                            @error('section_number', 'addSectionModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_modal_student_count_input" class="form-label">Student Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('student_count', 'addSectionModal') is-invalid @enderror" id="add_modal_student_count_input" name="student_count" value="{{ old('student_count', 0) }}" required min="0">
                            @error('student_count', 'addSectionModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_modal_section_gender_select" class="form-label">Section Gender <span class="text-danger">*</span></label>
                            <select class="form-select @error('section_gender', 'addSectionModal') is-invalid @enderror" id="add_modal_section_gender_select" name="section_gender" required>
                                <option value="Mixed" {{ old('section_gender', 'Mixed') == 'Mixed' ? 'selected' : '' }}>Mixed</option>
                                <option value="Male" {{ old('section_gender') == 'Male' ? 'selected' : '' }}>Male Only</option>
                                <option value="Female" {{ old('section_gender') == 'Female' ? 'selected' : '' }}>Female Only</option>
                            </select>
                            @error('section_gender', 'addSectionModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="add_modal_branch_display_input" class="form-label">Branch (from context)</label>
                            <input type="text" class="form-control" id="add_modal_branch_display_input" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===================================================== --}}
{{-- مودال تعديل شعبة (واحد مشترك) --}}
{{-- ===================================================== --}}
<div class="modal fade" id="universalEditSectionModal" tabindex="-1" aria-labelledby="universalEditSectionModalLabel" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="universalEditSectionModalLabel">Edit Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form id="universalEditSectionForm" action="#" method="POST"> {{-- Action يتغير بـ JS --}}
                @csrf
                @method('PUT')
                <div class="modal-body">
                     <div class="alert alert-secondary small p-2 mb-3" id="edit_modal_context_info_display">
                         <strong>Context:</strong> <span>Loading...</span>
                     </div>
                     <div id="edit_modal_errors_placeholder" class="mb-2">
                         @if ($errors->any() && $errors->hasBag('editSectionModal'))
                             <div class="alert alert-danger small p-2">
                                 <ul class="mb-0 ps-3">
                                     @foreach ($errors->getBag('editSectionModal')->all() as $error)
                                         <li>{{ $error }}</li>
                                     @endforeach
                                 </ul>
                             </div>
                          @endif
                     </div>
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_modal_section_number_input" class="form-label">Section Number <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('section_number', 'editSectionModal') is-invalid @enderror" id="edit_modal_section_number_input" name="section_number" value="" required min="1">
                             @error('section_number', 'editSectionModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_modal_student_count_input" class="form-label">Student Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('student_count', 'editSectionModal') is-invalid @enderror" id="edit_modal_student_count_input" name="student_count" value="" required min="0">
                             @error('student_count', 'editSectionModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_modal_section_gender_select" class="form-label">Section Gender <span class="text-danger">*</span></label>
                            <select class="form-select @error('section_gender', 'editSectionModal') is-invalid @enderror" id="edit_modal_section_gender_select" name="section_gender" required>
                                <option value="Mixed">Mixed</option> <option value="Male">Male Only</option> <option value="Female">Female Only</option>
                            </select>
                            @error('section_gender', 'editSectionModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="edit_modal_branch_display_input" class="form-label">Branch (from context)</label>
                            <input type="text" class="form-control" id="edit_modal_branch_display_input" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===================================================== --}}
{{-- مودال تأكيد الحذف (واحد مشترك) --}}
{{-- ===================================================== --}}
<div class="modal fade" id="universalDeleteSectionModal" tabindex="-1" aria-labelledby="universalDeleteSectionModalLabel" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="universalDeleteSectionModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form id="universalDeleteSectionForm" action="#" method="POST"> {{-- Action يتغير بـ JS --}}
                @csrf
                @method('DELETE')
                <div class="modal-body">
                     <p>Are you sure you want to delete <strong id="delete_modal_section_info_text">this section</strong>?</p>
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
