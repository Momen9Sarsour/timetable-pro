{{-- ===================================================== --}}
{{-- مودال إضافة شعبة جديدة (في سياق محدد) --}}
{{-- ===================================================== --}}
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSectionModalLabel">Add New Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addSectionForm" action="#" method="POST"> {{-- Action يتغير بـ JS --}}
                @csrf
                <input type="hidden" name="plan_subject_id_from_modal" id="add_modal_plan_subject_id" value="{{ old('plan_subject_id_from_modal') }}">
                <input type="hidden" name="activity_type_from_modal" id="add_modal_activity_type" value="{{ old('activity_type_from_modal') }}">
                {{-- expectedCountId يستخدم فقط لتحديث الـ action في JS --}}

                <div class="modal-body">
                    {{-- عرض أخطاء الـ validation المحددة للحقول --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_section_number" class="form-label">Section Number <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('section_number') is-invalid @enderror" id="add_section_number" name="section_number" value="{{ old('section_number', 1) }}" required min="1">
                            @error('section_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @error('section_unique') <div class="text-danger small mt-1">{{ $message }}</div> @enderror {{-- لخطأ التفرد --}}
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_student_count" class="form-label">Student Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('student_count') is-invalid @enderror" id="add_student_count" name="student_count" value="{{ old('student_count', 0) }}" required min="0">
                            @error('student_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_section_gender" class="form-label">Section Gender <span class="text-danger">*</span></label>
                            <select class="form-select @error('section_gender') is-invalid @enderror" id="add_section_gender" name="section_gender" required>
                                <option value="Mixed" {{ old('section_gender', 'Mixed') == 'Mixed' ? 'selected' : '' }}>Mixed</option>
                                <option value="Male" {{ old('section_gender') == 'Male' ? 'selected' : '' }}>Male Only</option>
                                <option value="Female" {{ old('section_gender') == 'Female' ? 'selected' : '' }}>Female Only</option>
                            </select>
                            @error('section_gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="add_branch_display_modal" class="form-label">Branch (from context)</label>
                            <input type="text" class="form-control" id="add_branch_display_modal" readonly>
                            {{-- القيمة الفعلية للفرع ستؤخذ من $expectedCount في الكنترولر --}}
                        </div>
                    </div>
                     {{-- عرض الأخطاء التي ليست مرتبطة بحقل معين --}}
                     @if ($errors->any() && !$errors->has('section_number') && !$errors->has('student_count') && !$errors->has('section_gender') && !$errors->has('plan_subject_id_from_modal') && !$errors->has('activity_type_from_modal'))
                        <div class="alert alert-danger small p-2">
                            Please review the following errors:
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                     @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===================================================== --}}
{{-- مودال تعديل شعبة (واحد مشترك) --}}
{{-- ===================================================== --}}
<div class="modal fade" id="editSectionModal" tabindex="-1" aria-labelledby="editSectionModalLabel" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSectionModalLabel">Edit Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form id="editSectionForm" action="#" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                     <div class="alert alert-secondary small p-2 mb-3" id="edit_context_info_modal_text">
                         <strong>Context:</strong> <span>Loading...</span>
                     </div>
                     {{-- عرض أخطاء الـ validation المحددة للحقول --}}
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_modal_section_number" class="form-label">Section Number <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('section_number') is-invalid @enderror" id="edit_modal_section_number" name="section_number" value="" required min="1">
                            @error('section_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @error('section_unique') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_modal_student_count" class="form-label">Student Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('student_count') is-invalid @enderror" id="edit_modal_student_count" name="student_count" value="" required min="0">
                            @error('student_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_modal_section_gender" class="form-label">Section Gender <span class="text-danger">*</span></label>
                            <select class="form-select @error('section_gender') is-invalid @enderror" id="edit_modal_section_gender" name="section_gender" required>
                                <option value="Mixed">Mixed</option>
                                <option value="Male">Male Only</option>
                                <option value="Female">Female Only</option>
                            </select>
                            @error('section_gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="edit_modal_branch_display" class="form-label">Branch (from context)</label>
                            <input type="text" class="form-control" id="edit_modal_branch_display" readonly>
                        </div>
                    </div>
                     {{-- عرض الأخطاء العامة --}}
                      @if ($errors->any() && !$errors->has('section_number') && !$errors->has('student_count') && !$errors->has('section_gender'))
                        <div class="alert alert-danger small p-2">
                            Please review the following errors:
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                     @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===================================================== --}}
{{-- مودال تأكيد الحذف (واحد مشترك) --}}
{{-- ===================================================== --}}
<div class="modal fade" id="deleteSectionModal" tabindex="-1" aria-labelledby="deleteSectionModalLabel" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSectionModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form id="deleteSectionForm" action="#" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                     <p>Are you sure you want to delete <strong id="deleteSectionInfoText">this section</strong>?</p>
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
