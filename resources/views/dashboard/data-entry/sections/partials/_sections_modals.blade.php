{{-- ===================================================== --}}
{{-- مودال إضافة شعبة (واحد مشترك) --}}
{{-- ===================================================== --}}
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSectionModalLabel">Add New Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addSectionForm" action="{{ route('data-entry.sections.store') }}" method="POST">
                @csrf
                {{-- حقول السياق المخفية (يتم تعبئتها بـ JS) --}}
                <input type="hidden" name="plan_subject_id" value="{{ old('plan_subject_id') }}">
                <input type="hidden" name="activity_type" value="{{ old('activity_type') }}">
                <input type="hidden" name="academic_year" value="{{ old('academic_year') }}">
                <input type="hidden" name="semester" value="{{ old('semester') }}">
                <input type="hidden" name="branch" value="{{ old('branch') }}">

                <div class="modal-body">
                     {{-- عرض أخطاء الـ validation العامة من هذا الفورم --}}
                     @if ($errors->hasBag('addSectionModal'))
                        <div class="alert alert-danger small p-2 mb-3 validation-errors">
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
                            <label for="add_section_number" class="form-label">Section Number <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('section_number', 'addSectionModal') is-invalid @enderror" id="add_section_number" name="section_number" value="{{ old('section_number', 1) }}" required min="1">
                            @error('section_number', 'addSectionModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            {{-- خطأ التفرد من الكنترولر (إذا استخدم نفس الـ bag) --}}
                            @error('section_unique', 'addSectionModal') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_student_count" class="form-label">Student Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('student_count', 'addSectionModal') is-invalid @enderror" id="add_student_count" name="student_count" value="{{ old('student_count', 0) }}" required min="0">
                            @error('student_count', 'addSectionModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            {{-- خطأ تجاوز العدد الإجمالي --}}
                            @error('student_count_total', 'addSectionModal') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_section_gender" class="form-label">Section Gender <span class="text-danger">*</span></label>
                            <select class="form-select @error('section_gender', 'addSectionModal') is-invalid @enderror" id="add_section_gender" name="section_gender" required>
                                <option value="Mixed" {{ old('section_gender', 'Mixed') == 'Mixed' ? 'selected' : '' }}>Mixed</option>
                                <option value="Male" {{ old('section_gender') == 'Male' ? 'selected' : '' }}>Male Only</option>
                                <option value="Female" {{ old('section_gender') == 'Female' ? 'selected' : '' }}>Female Only</option>
                            </select>
                            @error('section_gender', 'addSectionModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="add_branch_display" class="form-label">Branch (from context)</label>
                            <input type="text" class="form-control" id="add_branch_display" readonly>
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

{{-- مودال التعديل (واحد مشترك) --}}
{{-- مودال التعديل (واحد مشترك) --}}
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
                    <div class="alert alert-secondary small p-2 mb-3" id="edit_context_info_text">
                        <strong>Context:</strong> <span>Loading...</span>
                    </div>

                    {{-- عرض أخطاء التحقق --}}
                    @php
                        $editErrorBagName = null;
                        $sectionIdForModal = session('editSectionId') ?? null;
                        if ($sectionIdForModal && $errors->hasBag('editSectionModal_'.$sectionIdForModal)) {
                            $editErrorBagName = 'editSectionModal_'.$sectionIdForModal;
                        }
                    @endphp

                    @if($editErrorBagName && $errors->$editErrorBagName->any())
                        <div class="alert alert-danger small p-2 validation-errors">
                            <strong>Please correct the errors:</strong>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->$editErrorBagName->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_section_number" class="form-label">Section Number <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @if($editErrorBagName) @error('section_number', $editErrorBagName) is-invalid @enderror @endif"
                                   id="edit_section_number" name="section_number"
                                   value="{{ old('section_number', $sectionForModal->section_number ?? '') }}" required min="1">
                            @if($editErrorBagName)
                                @error('section_number', $editErrorBagName)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @error('section_unique', $editErrorBagName)
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_student_count" class="form-label">Student Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @if($editErrorBagName) @error('student_count', $editErrorBagName) is-invalid @enderror @endif"
                                   id="edit_student_count" name="student_count"
                                   value="{{ old('student_count', $sectionForModal->student_count ?? '') }}" required min="0">
                            @if($editErrorBagName)
                                @error('student_count', $editErrorBagName)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @error('student_count_total', $editErrorBagName)
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_section_gender" class="form-label">Section Gender <span class="text-danger">*</span></label>
                            <select class="form-select @if($editErrorBagName) @error('section_gender', $editErrorBagName) is-invalid @enderror @endif"
                                    id="edit_section_gender" name="section_gender" required>
                                <option value="Mixed" @if(old('section_gender', $sectionForModal->section_gender ?? '') == 'Mixed') selected @endif>Mixed</option>
                                <option value="Male" @if(old('section_gender', $sectionForModal->section_gender ?? '') == 'Male') selected @endif>Male Only</option>
                                <option value="Female" @if(old('section_gender', $sectionForModal->section_gender ?? '') == 'Female') selected @endif>Female Only</option>
                            </select>
                            @if($editErrorBagName)
                                @error('section_gender', $editErrorBagName)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_branch_display" class="form-label">Branch (from context)</label>
                            <input type="text" class="form-control" id="edit_branch_display" readonly>
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

{{-- مودال الحذف (واحد مشترك) --}}
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
