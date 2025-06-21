{{-- // Modal لإضافة مادة جديدة --}}
@if (!$subject)
    <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSubjectModalLabel">Add New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('data-entry.subjects.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_subject_no" class="form-label">Subject Code <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('subject_no') is-invalid @enderror"
                                    id="add_subject_no" name="subject_no" value="{{ old('subject_no') }}" required>
                                @error('subject_no')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add_subject_name" class="form-label">Subject Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('subject_name') is-invalid @enderror"
                                    id="add_subject_name" name="subject_name" value="{{ old('subject_name') }}"
                                    required>
                                @error('subject_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="add_subject_load" class="form-label">Credit Hours <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('subject_load') is-invalid @enderror"
                                    id="add_subject_load" name="subject_load" value="{{ old('subject_load') }}" required
                                    min="0">
                                @error('subject_load')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="add_theoretical_hours" class="form-label">Weekly Theoretical Hours <span
                                        class="text-danger">*</span></label>
                                <input type="number"
                                    class="form-control @error('theoretical_hours') is-invalid @enderror"
                                    id="add_theoretical_hours" name="theoretical_hours"
                                    value="{{ old('theoretical_hours', 0) }}" required min="0">
                                @error('theoretical_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="add_practical_hours" class="form-label">Weekly Practical Hours <span
                                        class="text-danger">*</span></label>
                                <input type="number"
                                    class="form-control @error('practical_hours') is-invalid @enderror"
                                    id="add_practical_hours" name="practical_hours"
                                    value="{{ old('practical_hours', 0) }}" required min="0">
                                @error('practical_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_load_theoretical_section" class="form-label">Theoretical Section
                                    Capacity</label>
                                <input type="number"
                                    class="form-control @error('load_theoretical_section', 'store') is-invalid @enderror"
                                    id="add_load_theoretical_section" name="load_theoretical_section"
                                    value="{{ old('load_theoretical_section', 50) }}" min="1"
                                    placeholder="Default: 50">
                                <small class="text-muted">Max students per theory section. Leave blank for default
                                    (50).</small>
                                @error('load_theoretical_section', 'store')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add_load_practical_section" class="form-label">Practical Section
                                    Capacity</label>
                                <input type="number"
                                    class="form-control @error('load_practical_section', 'store') is-invalid @enderror"
                                    id="add_load_practical_section" name="load_practical_section"
                                    value="{{ old('load_practical_section', 25) }}" min="1"
                                    placeholder="Default: 25">
                                <small class="text-muted">Max students per lab section. Leave blank for default
                                    (25).</small>
                                @error('load_practical_section', 'store')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_subject_type_id" class="form-label">Subject Type <span
                                        class="text-danger">*</span></label>
                                <select class="form-select @error('subject_type_id') is-invalid @enderror"
                                    id="add_subject_type_id" name="subject_type_id" required>
                                    <option value="" selected disabled>Select type...</option>
                                    @foreach ($subjectTypes as $type)
                                        <option value="{{ $type->id }}"
                                            {{ old('subject_type_id') == $type->id ? 'selected' : '' }}>
                                            {{ $type->subject_type_name }}</option>
                                    @endforeach
                                </select>
                                @error('subject_type_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add_subject_category_id" class="form-label">Subject Category <span
                                        class="text-danger">*</span></label>
                                <select class="form-select @error('subject_category_id') is-invalid @enderror"
                                    id="add_subject_category_id" name="subject_category_id" required>
                                    <option value="" selected disabled>Select category...</option>
                                    @foreach ($subjectCategories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ old('subject_category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->subject_category_name }}</option>
                                    @endforeach
                                </select>
                                @error('subject_category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="add_department_id" class="form-label">Primary Department <span
                                    class="text-danger">*</span></label>
                            <select class="form-select @error('department_id') is-invalid @enderror"
                                id="add_department_id" name="department_id" required>
                                <option value="" selected disabled>Select department...</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->department_name }}</option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- // Modal لتعديل مادة موجودة --}}
@if ($subject)
    <div class="modal fade" id="editSubjectModal-{{ $subject->id }}" tabindex="-1"
        aria-labelledby="editSubjectModalLabel-{{ $subject->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSubjectModalLabel-{{ $subject->id }}">Edit Subject:
                        {{ $subject->subject_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('data-entry.subjects.update', $subject->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        {{-- // نفس حقول نموذج الإضافة ولكن مع قيم المادة الحالية --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_subject_no_{{ $subject->id }}" class="form-label">Subject Code
                                    <span class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control @error('subject_no', 'update_' . $subject->id) is-invalid @enderror"
                                    id="edit_subject_no_{{ $subject->id }}" name="subject_no"
                                    value="{{ old('subject_no', $subject->subject_no) }}" required>
                                @error('subject_no', 'update_' . $subject->id)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_subject_name_{{ $subject->id }}" class="form-label">Subject Name
                                    <span class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control @error('subject_name', 'update_' . $subject->id) is-invalid @enderror"
                                    id="edit_subject_name_{{ $subject->id }}" name="subject_name"
                                    value="{{ old('subject_name', $subject->subject_name) }}" required>
                                @error('subject_name', 'update_' . $subject->id)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="edit_subject_load_{{ $subject->id }}" class="form-label">Credit Hours
                                    <span class="text-danger">*</span></label>
                                <input type="number"
                                    class="form-control @error('subject_load', 'update_' . $subject->id) is-invalid @enderror"
                                    id="edit_subject_load_{{ $subject->id }}" name="subject_load"
                                    value="{{ old('subject_load', $subject->subject_load) }}" required
                                    min="0">
                                @error('subject_load', 'update_' . $subject->id)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_theoretical_hours_{{ $subject->id }}" class="form-label">Weekly
                                    Theoretical Hours <span class="text-danger">*</span></label>
                                <input type="number"
                                    class="form-control @error('theoretical_hours', 'update_' . $subject->id) is-invalid @enderror"
                                    id="edit_theoretical_hours_{{ $subject->id }}" name="theoretical_hours"
                                    value="{{ old('theoretical_hours', $subject->theoretical_hours) }}" required
                                    min="0">
                                @error('theoretical_hours', 'update_' . $subject->id)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_practical_hours_{{ $subject->id }}" class="form-label">Weekly
                                    Practical Hours <span class="text-danger">*</span></label>
                                <input type="number"
                                    class="form-control @error('practical_hours', 'update_' . $subject->id) is-invalid @enderror"
                                    id="edit_practical_hours_{{ $subject->id }}" name="practical_hours"
                                    value="{{ old('practical_hours', $subject->practical_hours) }}" required
                                    min="0">
                                @error('practical_hours', 'update_' . $subject->id)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_load_theoretical_section_{{ $subject->id }}"
                                    class="form-label">Theoretical Section Capacity</label>
                                <input type="number"
                                    class="form-control @error('load_theoretical_section', 'update_' . $subject->id) is-invalid @enderror"
                                    id="edit_load_theoretical_section_{{ $subject->id }}"
                                    name="load_theoretical_section"
                                    value="{{ old('load_theoretical_section', $subject->load_theoretical_section) }}"
                                    min="1" placeholder="Default: 50">
                                @error('load_theoretical_section', 'update_' . $subject->id)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_load_practical_section_{{ $subject->id }}"
                                    class="form-label">Practical Section Capacity</label>
                                <input type="number"
                                    class="form-control @error('load_practical_section', 'update_' . $subject->id) is-invalid @enderror"
                                    id="edit_load_practical_section_{{ $subject->id }}"
                                    name="load_practical_section"
                                    value="{{ old('load_practical_section', $subject->load_practical_section) }}"
                                    min="1" placeholder="Default: 25">
                                @error('load_practical_section', 'update_' . $subject->id)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_subject_type_id_{{ $subject->id }}" class="form-label">Subject Type
                                    <span class="text-danger">*</span></label>
                                <select
                                    class="form-select @error('subject_type_id', 'update_' . $subject->id) is-invalid @enderror"
                                    id="edit_subject_type_id_{{ $subject->id }}" name="subject_type_id" required>
                                    <option value="" disabled>Select type...</option>
                                    @foreach ($subjectTypes as $type)
                                        <option value="{{ $type->id }}"
                                            {{ old('subject_type_id', $subject->subject_type_id) == $type->id ? 'selected' : '' }}>
                                            {{ $type->subject_type_name }}</option>
                                    @endforeach
                                </select>
                                @error('subject_type_id', 'update_' . $subject->id)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_subject_category_id_{{ $subject->id }}" class="form-label">Subject
                                    Category <span class="text-danger">*</span></label>
                                <select
                                    class="form-select @error('subject_category_id', 'update_' . $subject->id) is-invalid @enderror"
                                    id="edit_subject_category_id_{{ $subject->id }}" name="subject_category_id"
                                    required>
                                    <option value="" disabled>Select category...</option>
                                    @foreach ($subjectCategories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ old('subject_category_id', $subject->subject_category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->subject_category_name }}</option>
                                    @endforeach
                                </select>
                                @error('subject_category_id', 'update_' . $subject->id)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_department_id_{{ $subject->id }}" class="form-label">Primary Department
                                <span class="text-danger">*</span></label>
                            <select
                                class="form-select @error('department_id', 'update_' . $subject->id) is-invalid @enderror"
                                id="edit_department_id_{{ $subject->id }}" name="department_id" required>
                                <option value="" disabled>Select department...</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ old('department_id', $subject->department_id) == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->department_name }}</option>
                                @endforeach
                            </select>
                            @error('department_id', 'update_' . $subject->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- // Modal لتأكيد الحذف --}}
    <div class="modal fade" id="deleteSubjectModal-{{ $subject->id }}" tabindex="-1"
        aria-labelledby="deleteSubjectModalLabel-{{ $subject->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteSubjectModalLabel-{{ $subject->id }}">Confirm Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="{{ route('data-entry.subjects.destroy', $subject->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body">
                        <p>Are you sure you want to delete the subject: <strong>{{ $subject->subject_name }}
                                ({{ $subject->subject_no }})</strong>?</p>
                        <p class="text-danger small">This action cannot be undone. It might affect academic plans and
                            generated schedules.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Yes, Delete Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif


{{-- *** مودال الرفع بالأكسل للمواد (جديد) *** --}}
<div class="modal fade" id="bulkUploadSubjectsModal" tabindex="-1" aria-labelledby="bulkUploadSubjectsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"> {{-- modal-lg ليكون أعرض --}}
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkUploadSubjectsModalLabel">Bulk Upload Subjects from Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('data-entry.subjects.bulkUpload') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="subject_excel_file" class="form-label">Select Excel File <span
                                class="text-danger">*</span></label>
                        <input
                            class="form-control @error('subject_excel_file', 'bulkUploadSubjects') is-invalid @enderror"
                            type="file" id="subject_excel_file" name="subject_excel_file"
                            accept=".xlsx, .xls, .csv" required>
                        @error('subject_excel_file', 'bulkUploadSubjects')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="alert alert-info small p-2">
                        <p class="mb-1"><strong>File Format Instructions:</strong></p>
                        <ul class="mb-0 ps-3">
                            <li>First row must be headers: <code>subject_no</code>, <code>subject_name</code>,
                                <code>subject_load</code>, <code>theoretical_hours</code>, <code>practical_hours</code>,
                                <code>subject_type_id</code> (or name), <code>subject_category_id</code> (or name),
                                <code>department_id</code> (or name/code), <code>capacity_theoretical_section</code>
                                (optional), <code>capacity_practical_section</code> (optional).</li>
                            <li>For type, category, and department: you can use existing IDs OR exact names
                                (case-insensitive, Arabic hamzas normalized).</li>
                            <li>If 'subject_no' exists, its data will be updated; otherwise, a new subject is created.
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i> Upload and Process
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
