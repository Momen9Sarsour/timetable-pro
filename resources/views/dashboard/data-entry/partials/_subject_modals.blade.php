{{-- Add Subject Modal - Compact Design --}}
@if (!$subject)
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0 py-2">
                <h6 class="modal-title d-flex align-items-center mb-0" id="addSubjectModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>
                    Add New Subject
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.subjects.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-3" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-2">
                        <!-- Basic Information -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-1 mb-2 small">
                                <i class="fas fa-info-circle me-1"></i>Basic Information
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="add_subject_no" class="form-label fw-medium small">
                                <i class="fas fa-hashtag text-muted me-1"></i>
                                Subject Code <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control form-control-sm @error('subject_no') is-invalid @enderror"
                                   id="add_subject_no"
                                   name="subject_no"
                                   value="{{ old('subject_no') }}"
                                   placeholder="e.g., CS101, MATH201"
                                   required>
                            @error('subject_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="add_subject_name" class="form-label fw-medium small">
                                <i class="fas fa-book text-muted me-1"></i>
                                Subject Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control form-control-sm @error('subject_name') is-invalid @enderror"
                                   id="add_subject_name"
                                   name="subject_name"
                                   value="{{ old('subject_name') }}"
                                   placeholder="e.g., Introduction to Programming"
                                   required>
                            @error('subject_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Academic Load -->
                        <div class="col-12 mt-2">
                            <h6 class="text-primary border-bottom pb-1 mb-2 small">
                                <i class="fas fa-clock me-1"></i>Academic Load
                            </h6>
                        </div>

                        <div class="col-md-4">
                            <label for="add_subject_load" class="form-label fw-medium small">
                                Credit Hours <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control form-control-sm @error('subject_load') is-invalid @enderror"
                                   id="add_subject_load"
                                   name="subject_load"
                                   value="{{ old('subject_load') }}"
                                   min="0"
                                   max="10"
                                   required>
                            @error('subject_load')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="add_theoretical_hours" class="form-label fw-medium small">
                                Theory Hours/Week <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control form-control-sm @error('theoretical_hours') is-invalid @enderror"
                                   id="add_theoretical_hours"
                                   name="theoretical_hours"
                                   value="{{ old('theoretical_hours', 0) }}"
                                   min="0"
                                   max="20"
                                   required>
                            @error('theoretical_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="add_practical_hours" class="form-label fw-medium small">
                                Practical Hours/Week <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control form-control-sm @error('practical_hours') is-invalid @enderror"
                                   id="add_practical_hours"
                                   name="practical_hours"
                                   value="{{ old('practical_hours', 0) }}"
                                   min="0"
                                   max="20"
                                   required>
                            @error('practical_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Section Capacity -->
                        <div class="col-12 mt-2">
                            <h6 class="text-primary border-bottom pb-1 mb-2 small">
                                <i class="fas fa-users me-1"></i>Section Capacity
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="add_load_theoretical_section" class="form-label fw-medium small">
                                Theory Section Capacity
                            </label>
                            <input type="number"
                                   class="form-control form-control-sm @error('load_theoretical_section', 'store') is-invalid @enderror"
                                   id="add_load_theoretical_section"
                                   name="load_theoretical_section"
                                   value="{{ old('load_theoretical_section', 50) }}"
                                   min="1"
                                   placeholder="Default: 50">
                            <div class="form-text small">Max students per theory section</div>
                            @error('load_theoretical_section', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="add_load_practical_section" class="form-label fw-medium small">
                                Practical Section Capacity
                            </label>
                            <input type="number"
                                   class="form-control form-control-sm @error('load_practical_section', 'store') is-invalid @enderror"
                                   id="add_load_practical_section"
                                   name="load_practical_section"
                                   value="{{ old('load_practical_section', 25) }}"
                                   min="1"
                                   placeholder="Default: 25">
                            <div class="form-text small">Max students per lab section</div>
                            @error('load_practical_section', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Classification -->
                        <div class="col-12 mt-2">
                            <h6 class="text-primary border-bottom pb-1 mb-2 small">
                                <i class="fas fa-tags me-1"></i>Subject Classification
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="add_subject_type_id" class="form-label fw-medium small">
                                Subject Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm @error('subject_type_id') is-invalid @enderror"
                                    id="add_subject_type_id"
                                    name="subject_type_id"
                                    required>
                                <option value="" selected disabled>Choose type...</option>
                                @foreach ($subjectTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('subject_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->subject_type_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('subject_type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="add_subject_category_id" class="form-label fw-medium small">
                                Subject Category <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm @error('subject_category_id') is-invalid @enderror"
                                    id="add_subject_category_id"
                                    name="subject_category_id"
                                    required>
                                <option value="" selected disabled>Choose category...</option>
                                @foreach ($subjectCategories as $category)
                                    <option value="{{ $category->id }}" {{ old('subject_category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->subject_category_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('subject_category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="add_department_id" class="form-label fw-medium small">
                                Primary Department <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm @error('department_id') is-invalid @enderror"
                                    id="add_department_id"
                                    name="department_id"
                                    required>
                                <option value="" selected disabled>Choose department...</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->department_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save me-1"></i>Save Subject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Edit Subject Modal - Compact Design --}}
@if ($subject)
<div class="modal fade" id="editSubjectModal-{{ $subject->id }}" tabindex="-1" aria-labelledby="editSubjectModalLabel-{{ $subject->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0 py-2">
                <h6 class="modal-title d-flex align-items-center mb-0" id="editSubjectModalLabel-{{ $subject->id }}">
                    <i class="fas fa-edit me-2"></i>
                    Edit Subject
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.subjects.update', $subject->id) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body p-3" style="max-height: 70vh; overflow-y: auto;">
                    <!-- Subject Info Alert -->
                    <div class="alert alert-info p-2 mb-2" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-2"></i>
                            <div>
                                <small>Editing: <strong>{{ $subject->subject_name }}</strong></small>
                                <div class="badge bg-light text-dark font-monospace ms-2">{{ $subject->subject_no }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2">
                        <!-- Basic Information -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-1 mb-2 small">
                                <i class="fas fa-info-circle me-1"></i>Basic Information
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_subject_no_{{ $subject->id }}" class="form-label fw-medium small">
                                Subject Code <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control form-control-sm @error('subject_no', 'update_'.$subject->id) is-invalid @enderror"
                                   id="edit_subject_no_{{ $subject->id }}"
                                   name="subject_no"
                                   value="{{ old('subject_no', $subject->subject_no) }}"
                                   required>
                            @error('subject_no', 'update_'.$subject->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="edit_subject_name_{{ $subject->id }}" class="form-label fw-medium small">
                                Subject Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control form-control-sm @error('subject_name', 'update_'.$subject->id) is-invalid @enderror"
                                   id="edit_subject_name_{{ $subject->id }}"
                                   name="subject_name"
                                   value="{{ old('subject_name', $subject->subject_name) }}"
                                   required>
                            @error('subject_name', 'update_'.$subject->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Academic Load -->
                        <div class="col-12 mt-2">
                            <h6 class="text-primary border-bottom pb-1 mb-2 small">
                                <i class="fas fa-clock me-1"></i>Academic Load
                            </h6>
                        </div>

                        <div class="col-md-4">
                            <label for="edit_subject_load_{{ $subject->id }}" class="form-label fw-medium small">
                                Credit Hours <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control form-control-sm @error('subject_load', 'update_'.$subject->id) is-invalid @enderror"
                                   id="edit_subject_load_{{ $subject->id }}"
                                   name="subject_load"
                                   value="{{ old('subject_load', $subject->subject_load) }}"
                                   min="0"
                                   required>
                            @error('subject_load', 'update_'.$subject->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="edit_theoretical_hours_{{ $subject->id }}" class="form-label fw-medium small">
                                Theory Hours/Week <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control form-control-sm @error('theoretical_hours', 'update_'.$subject->id) is-invalid @enderror"
                                   id="edit_theoretical_hours_{{ $subject->id }}"
                                   name="theoretical_hours"
                                   value="{{ old('theoretical_hours', $subject->theoretical_hours) }}"
                                   min="0"
                                   required>
                            @error('theoretical_hours', 'update_'.$subject->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="edit_practical_hours_{{ $subject->id }}" class="form-label fw-medium small">
                                Practical Hours/Week <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control form-control-sm @error('practical_hours', 'update_'.$subject->id) is-invalid @enderror"
                                   id="edit_practical_hours_{{ $subject->id }}"
                                   name="practical_hours"
                                   value="{{ old('practical_hours', $subject->practical_hours) }}"
                                   min="0"
                                   required>
                            @error('practical_hours', 'update_'.$subject->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Section Capacity -->
                        <div class="col-12 mt-2">
                            <h6 class="text-primary border-bottom pb-1 mb-2 small">
                                <i class="fas fa-users me-1"></i>Section Capacity
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_load_theoretical_section_{{ $subject->id }}" class="form-label fw-medium small">
                                Theory Section Capacity
                            </label>
                            <input type="number"
                                   class="form-control form-control-sm @error('load_theoretical_section', 'update_'.$subject->id) is-invalid @enderror"
                                   id="edit_load_theoretical_section_{{ $subject->id }}"
                                   name="load_theoretical_section"
                                   value="{{ old('load_theoretical_section', $subject->load_theoretical_section) }}"
                                   min="1">
                            @error('load_theoretical_section', 'update_'.$subject->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="edit_load_practical_section_{{ $subject->id }}" class="form-label fw-medium small">
                                Practical Section Capacity
                            </label>
                            <input type="number"
                                   class="form-control form-control-sm @error('load_practical_section', 'update_'.$subject->id) is-invalid @enderror"
                                   id="edit_load_practical_section_{{ $subject->id }}"
                                   name="load_practical_section"
                                   value="{{ old('load_practical_section', $subject->load_practical_section) }}"
                                   min="1">
                            @error('load_practical_section', 'update_'.$subject->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Classification -->
                        <div class="col-12 mt-2">
                            <h6 class="text-primary border-bottom pb-1 mb-2 small">
                                <i class="fas fa-tags me-1"></i>Subject Classification
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_subject_type_id_{{ $subject->id }}" class="form-label fw-medium small">
                                Subject Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm @error('subject_type_id', 'update_'.$subject->id) is-invalid @enderror"
                                    id="edit_subject_type_id_{{ $subject->id }}"
                                    name="subject_type_id"
                                    required>
                                <option value="" disabled>Select type...</option>
                                @foreach ($subjectTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('subject_type_id', $subject->subject_type_id) == $type->id ? 'selected' : '' }}>
                                        {{ $type->subject_type_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('subject_type_id', 'update_'.$subject->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="edit_subject_category_id_{{ $subject->id }}" class="form-label fw-medium small">
                                Subject Category <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm @error('subject_category_id', 'update_'.$subject->id) is-invalid @enderror"
                                    id="edit_subject_category_id_{{ $subject->id }}"
                                    name="subject_category_id"
                                    required>
                                <option value="" disabled>Select category...</option>
                                @foreach ($subjectCategories as $category)
                                    <option value="{{ $category->id }}" {{ old('subject_category_id', $subject->subject_category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->subject_category_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('subject_category_id', 'update_'.$subject->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="edit_department_id_{{ $subject->id }}" class="form-label fw-medium small">
                                Primary Department <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm @error('department_id', 'update_'.$subject->id) is-invalid @enderror"
                                    id="edit_department_id_{{ $subject->id }}"
                                    name="department_id"
                                    required>
                                <option value="" disabled>Select department...</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $subject->department_id) == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->department_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id', 'update_'.$subject->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="fas fa-sync-alt me-1"></i>Update Subject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal - Compact Design --}}
<div class="modal fade" id="deleteSubjectModal-{{ $subject->id }}" tabindex="-1" aria-labelledby="deleteSubjectModalLabel-{{ $subject->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0 py-2">
                <h6 class="modal-title d-flex align-items-center mb-0" id="deleteSubjectModalLabel-{{ $subject->id }}">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Deletion
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.subjects.destroy', $subject->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-3">
                    <div class="text-center mb-2">
                        <i class="fas fa-book text-danger" style="font-size: 2.5rem; opacity: 0.3;"></i>
                    </div>

                    <div class="alert alert-danger p-2 mb-2" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <h6 class="alert-heading mb-1 small">Are you sure?</h6>
                                <p class="mb-0 small">You are about to delete:</p>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light border-danger">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1 small">{{ $subject->subject_name }}</h6>
                                    <small class="text-muted">Code: {{ $subject->subject_no }}</small>
                                    <div class="d-flex gap-1 mt-1">
                                        <span class="badge bg-info bg-opacity-20 text-info">{{ $subject->subject_load }} credits</span>
                                        <span class="badge bg-secondary bg-opacity-20 text-secondary">{{ $subject->theoretical_hours }}h/{{ $subject->practical_hours }}h</span>
                                    </div>
                                </div>
                                <span class="badge bg-danger">Will be deleted</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning p-2 mt-2 mb-0" role="alert">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <small><strong>Warning:</strong> This action cannot be undone. It might affect academic plans and generated schedules.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash me-1"></i>Yes, Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Bulk Upload Modal - Compact Design --}}
<div class="modal fade" id="bulkUploadSubjectsModal" tabindex="-1" aria-labelledby="bulkUploadSubjectsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0 py-2">
                <h6 class="modal-title d-flex align-items-center mb-0" id="bulkUploadSubjectsModalLabel">
                    <i class="fas fa-file-excel me-2"></i>
                    Bulk Upload Subjects
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.subjects.bulkUpload') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-3" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-2">
                        <!-- File Upload Section -->
                        <div class="col-12">
                            <label for="subject_excel_file" class="form-label fw-medium small">
                                <i class="fas fa-cloud-upload-alt text-muted me-1"></i>
                                Select Excel File <span class="text-danger">*</span>
                            </label>
                            <div class="upload-zone border border-2 border-dashed rounded-3 p-3 text-center cursor-pointer">
                                <i class="fas fa-file-excel text-success mb-2" style="font-size: 1.5rem;"></i>
                                <input class="form-control form-control-sm @error('subject_excel_file', 'bulkUploadSubjects') is-invalid @enderror"
                                       type="file"
                                       id="subject_excel_file"
                                       name="subject_excel_file"
                                       accept=".xlsx,.xls,.csv"
                                       required>
                                @error('subject_excel_file', 'bulkUploadSubjects')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text mt-1">
                                    <small>Supported: .xlsx, .xls, .csv (Max: 10MB)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Instructions Section -->
                        <div class="col-12 mt-2">
                            <div class="card bg-light border-0">
                                <div class="card-header bg-transparent border-0 pb-0">
                                    <h6 class="card-title text-primary mb-0 small">
                                        <i class="fas fa-info-circle me-1"></i>
                                        File Format Instructions
                                    </h6>
                                </div>
                                <div class="card-body pt-1 pb-2">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul class="list-unstyled mb-0" style="font-size: 0.75rem;">
                                                <li class="d-flex align-items-start mb-1">
                                                    <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                                    <span>Required headers: <code>subject_no</code>, <code>subject_name</code>, <code>subject_load</code></span>
                                                </li>
                                                <li class="d-flex align-items-start mb-1">
                                                    <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                                    <span>Hours: <code>theoretical_hours</code>, <code>practical_hours</code></span>
                                                </li>
                                                <li class="d-flex align-items-start mb-1">
                                                    <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                                    <span>Optional: <code>load_theoretical_section</code>, <code>load_practical_section</code></span>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <ul class="list-unstyled mb-0" style="font-size: 0.75rem;">
                                                <li class="d-flex align-items-start mb-1">
                                                    <i class="fas fa-link text-info me-2 mt-1 flex-shrink-0"></i>
                                                    <span>Relations: <code>subject_type_id</code>, <code>subject_category_id</code>, <code>department_id</code></span>
                                                </li>
                                                <li class="d-flex align-items-start mb-1">
                                                    <i class="fas fa-sync-alt text-warning me-2 mt-1 flex-shrink-0"></i>
                                                    <span>Use exact IDs or names (case-insensitive)</span>
                                                </li>
                                                <li class="d-flex align-items-start">
                                                    <i class="fas fa-filter text-warning me-2 mt-1 flex-shrink-0"></i>
                                                    <span>Updates existing subjects by code</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sample Format -->
                        <div class="col-12 mt-2">
                            <div class="card border-primary">
                                <div class="card-header bg-primary bg-opacity-10 border-primary py-1">
                                    <h6 class="card-title text-primary mb-0 small">
                                        <i class="fas fa-table me-1"></i>
                                        Sample Format
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0" style="font-size: 0.7rem;">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th>subject_no</th>
                                                    <th>subject_name</th>
                                                    <th>subject_load</th>
                                                    <th>theoretical_hours</th>
                                                    <th>practical_hours</th>
                                                    <th>subject_type_id</th>
                                                    <th>subject_category_id</th>
                                                    <th>department_id</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><code>CS101</code></td>
                                                    <td>Programming I</td>
                                                    <td>3</td>
                                                    <td>2</td>
                                                    <td>2</td>
                                                    <td>1</td>
                                                    <td>1</td>
                                                    <td>1</td>
                                                </tr>
                                                <tr>
                                                    <td><code>MATH201</code></td>
                                                    <td>Calculus II</td>
                                                    <td>3</td>
                                                    <td>3</td>
                                                    <td>0</td>
                                                    <td>2</td>
                                                    <td>2</td>
                                                    <td>2</td>
                                                </tr>
                                                <tr>
                                                    <td><code>PHYS301</code></td>
                                                    <td>Advanced Physics</td>
                                                    <td>4</td>
                                                    <td>3</td>
                                                    <td>3</td>
                                                    <td>1</td>
                                                    <td>3</td>
                                                    <td>3</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-upload me-1"></i>Upload & Process
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Compact subject modal styling */
.modal-content {
    font-size: 0.875rem;
}

.modal-header {
    padding: 0.5rem 0.75rem;
}

.modal-body {
    padding: 0.75rem;
}

.modal-footer {
    padding: 0.5rem 0.75rem;
}

/* Form controls */
.form-control-sm, .form-select-sm {
    font-size: 0.8125rem;
    padding: 0.375rem 0.5rem;
}

.form-label {
    margin-bottom: 0.25rem;
    font-size: 0.8125rem;
}

.form-text {
    font-size: 0.7rem;
    margin-top: 0.125rem;
}

/* Section headers */
.modal-body h6.border-bottom {
    margin-bottom: 0.75rem !important;
    padding-bottom: 0.25rem !important;
    border-bottom-width: 1px !important;
}

/* Upload zone */
.upload-zone {
    border-color: #dee2e6 !important;
    transition: all 0.15s ease;
    cursor: pointer;
    padding: 1rem !important;
}

.upload-zone:hover {
    border-color: var(--primary-color) !important;
    background-color: rgba(59, 130, 246, 0.05);
}

.upload-zone input[type="file"] {
    border: none;
    background: transparent;
    padding: 0.25rem 0;
    font-size: 0.8125rem;
}

/* Badges */
.badge {
    font-size: 0.7rem;
    font-weight: 500;
}

/* Buttons */
.btn-sm {
    font-size: 0.8125rem;
    padding: 0.25rem 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 1199px) {
    .modal-xl {
        max-width: 95%;
    }
}

@media (max-width: 768px) {
    .modal-lg, .modal-xl {
        max-width: 95%;
    }

    .modal-body {
        max-height: 60vh !important;
        padding: 0.5rem !important;
    }

    .upload-zone {
        padding: 0.75rem !important;
    }

    .table-responsive {
        font-size: 0.75rem;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
    }

    .modal-content {
        font-size: 0.8125rem;
    }

    .row.g-2 {
        --bs-gutter-x: 0.5rem;
        --bs-gutter-y: 0.5rem;
    }
}

/* Subject icon styling */
.subject-icon {
    transition: all 0.15s ease;
}

.subject-icon:hover {
    transform: scale(1.05);
}

/* Table improvements */
.table-sm th,
.table-sm td {
    padding: 0.25rem 0.125rem;
    font-size: 0.75rem;
}

/* Alert compact styling */
.alert {
    font-size: 0.8125rem;
    margin-bottom: 0.5rem;
}

.alert .badge {
    font-size: 0.65rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload enhancement for bulk upload
    const fileInput = document.getElementById('subject_excel_file');
    const uploadZone = document.querySelector('.upload-zone');

    if (fileInput && uploadZone) {
        // Click to open file dialog
        uploadZone.addEventListener('click', function() {
            fileInput.click();
        });

        // File change handler
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const icon = uploadZone.querySelector('i');
                const text = uploadZone.querySelector('.form-text');

                if (icon) {
                    icon.className = 'fas fa-file-check text-success mb-2';
                    icon.style.fontSize = '1.5rem';
                }

                if (text) {
                    text.innerHTML = `<small class="text-success fw-medium">✓ Selected: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</small>`;
                }
            }
        });

        // Drag & Drop functionality
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#3b82f6';
            this.style.backgroundColor = 'rgba(59, 130, 246, 0.05)';
        });

        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.backgroundColor = '';
        });

        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.backgroundColor = '';

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                const event = new Event('change', { bubbles: true });
                fileInput.dispatchEvent(event);
            }
        });
    }

    // Auto-calculate and validate hours
    document.querySelectorAll('form').forEach(form => {
        const theoreticalInput = form.querySelector('input[name="theoretical_hours"]');
        const practicalInput = form.querySelector('input[name="practical_hours"]');
        const loadInput = form.querySelector('input[name="subject_load"]');

        if (theoreticalInput && practicalInput && loadInput) {
            function validateHours() {
                const theoretical = parseInt(theoreticalInput.value) || 0;
                const practical = parseInt(practicalInput.value) || 0;
                const load = parseInt(loadInput.value) || 0;

                // Clear previous custom validity
                theoreticalInput.setCustomValidity('');
                practicalInput.setCustomValidity('');

                // Validation: at least one type of hours if load > 0
                if (theoretical + practical === 0 && load > 0) {
                    theoreticalInput.setCustomValidity('At least one type of hours must be greater than 0');
                    practicalInput.setCustomValidity('At least one type of hours must be greater than 0');
                }

                // Visual feedback for load vs hours relationship
                const totalHours = theoretical + practical;
                const loadElement = loadInput.parentNode;
                let feedback = loadElement.querySelector('.load-feedback');

                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'load-feedback form-text small';
                    loadElement.appendChild(feedback);
                }

                if (load > 0 && totalHours > 0) {
                    if (totalHours > load) {
                        feedback.className = 'load-feedback form-text small text-warning';
                        feedback.textContent = `Total hours (${totalHours}) > credits (${load})`;
                    } else if (totalHours === load) {
                        feedback.className = 'load-feedback form-text small text-success';
                        feedback.textContent = `Hours and credits balanced (${totalHours}:${load})`;
                    } else {
                        feedback.className = 'load-feedback form-text small text-info';
                        feedback.textContent = `Total hours: ${totalHours}, Credits: ${load}`;
                    }
                } else {
                    feedback.textContent = '';
                }
            }

            theoreticalInput.addEventListener('input', validateHours);
            practicalInput.addEventListener('input', validateHours);
            loadInput.addEventListener('input', validateHours);
        }
    });

    // Auto-format subject codes
    const subjectCodeInputs = document.querySelectorAll('input[name="subject_no"]');
    subjectCodeInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Convert to uppercase and remove spaces
            this.value = this.value.toUpperCase().replace(/\s+/g, '');
        });
    });

    // Capacity validation
    const capacityInputs = document.querySelectorAll('input[name*="load_"][name*="_section"]');
    capacityInputs.forEach(input => {
        input.addEventListener('input', function() {
            const value = parseInt(this.value);
            const isTheoretical = this.name.includes('theoretical');

            if (value && value < 5) {
                this.setCustomValidity('Section capacity should be at least 5 students');
            } else if (value && value > 200) {
                this.setCustomValidity('Section capacity seems unusually large');
            } else {
                this.setCustomValidity('');
            }

            // Visual feedback
            const parent = this.parentNode;
            let feedback = parent.querySelector('.capacity-feedback');

            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'capacity-feedback form-text small';
                parent.appendChild(feedback);
            }

            if (value) {
                const type = isTheoretical ? 'theory' : 'practical';
                feedback.className = 'capacity-feedback form-text small text-success';
                feedback.textContent = `${value} students per ${type} section`;
            } else {
                feedback.textContent = '';
            }
        });
    });

    // Form validation enhancement
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();

                // Focus on first invalid field
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();

                    // Scroll to the field if it's in a modal
                    const modal = form.closest('.modal');
                    if (modal) {
                        const modalBody = modal.querySelector('.modal-body');
                        const fieldTop = firstInvalid.getBoundingClientRect().top;
                        const modalTop = modalBody.getBoundingClientRect().top;

                        if (fieldTop < modalTop || fieldTop > modalTop + modalBody.clientHeight) {
                            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                }
            }
            form.classList.add('was-validated');
        });
    });

    // Auto-suggest subject names based on codes (optional enhancement)
    subjectCodeInputs.forEach(codeInput => {
        const nameInput = codeInput.closest('form').querySelector('input[name="subject_name"]');

        if (nameInput) {
            codeInput.addEventListener('input', function() {
                const code = this.value.toUpperCase();

                // Simple suggestions based on common prefixes
                const suggestions = {
                    'CS': 'Computer Science',
                    'MATH': 'Mathematics',
                    'PHYS': 'Physics',
                    'CHEM': 'Chemistry',
                    'ENG': 'Engineering',
                    'BIO': 'Biology',
                    'HIST': 'History',
                    'PSYC': 'Psychology'
                };

                if (!nameInput.value.trim() && code.length >= 2) {
                    const prefix = Object.keys(suggestions).find(p => code.startsWith(p));
                    if (prefix) {
                        nameInput.placeholder = `e.g., ${suggestions[prefix]} course`;
                    }
                }
            });
        }
    });
});
</script>
