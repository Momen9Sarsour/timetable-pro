{{-- Add Instructor Modal --}}
<div class="modal fade" id="addInstructorModal" tabindex="-1" aria-labelledby="addInstructorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="addInstructorModalLabel">
                    <i class="fas fa-user-plus me-2"></i>
                    Add New Instructor & User Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.instructors.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    {{-- Validation Errors --}}
                    @if ($errors->hasBag('addInstructorModal'))
                        <div class="alert alert-danger d-flex align-items-start mb-3" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <h6 class="alert-heading mb-1">Please correct the errors:</h6>
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->getBag('addInstructorModal')->all() as $error)
                                        <li class="small">{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <!-- User Account Section -->
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-header bg-transparent border-0 pb-1">
                            <h6 class="card-title text-primary mb-0">
                                <i class="fas fa-user me-1"></i>
                                User Account Information
                            </h6>
                        </div>
                        <div class="card-body pt-2">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="add_user_name" class="form-label fw-medium">
                                        <i class="fas fa-user text-muted me-1"></i>
                                        Full Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control @error('name', 'addInstructorModal') is-invalid @enderror"
                                           id="add_user_name"
                                           name="name"
                                           value="{{ old('name') }}"
                                           placeholder="e.g., Dr. Ahmed Ali"
                                           required>
                                    @error('name', 'addInstructorModal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="add_user_email" class="form-label fw-medium">
                                        <i class="fas fa-envelope text-muted me-1"></i>
                                        Email Address <span class="text-danger">*</span>
                                    </label>
                                    <input type="email"
                                           class="form-control @error('email', 'addInstructorModal') is-invalid @enderror"
                                           id="add_user_email"
                                           name="email"
                                           value="{{ old('email') }}"
                                           placeholder="ahmed.ali@college.edu"
                                           required>
                                    @error('email', 'addInstructorModal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="add_user_password" class="form-label fw-medium">
                                        <i class="fas fa-lock text-muted me-1"></i>
                                        Password <span class="text-danger">*</span>
                                    </label>
                                    <input type="password"
                                           class="form-control @error('password', 'addInstructorModal') is-invalid @enderror"
                                           id="add_user_password"
                                           name="password"
                                           required>
                                    @error('password', 'addInstructorModal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="add_user_password_confirmation" class="form-label fw-medium">
                                        <i class="fas fa-lock text-muted me-1"></i>
                                        Confirm Password <span class="text-danger">*</span>
                                    </label>
                                    <input type="password"
                                           class="form-control"
                                           id="add_user_password_confirmation"
                                           name="password_confirmation"
                                           required>
                                </div>
                                <div class="col-12">
                                    <label for="add_role_id_for_instructor" class="form-label fw-medium">
                                        <i class="fas fa-user-shield text-muted me-1"></i>
                                        Assign Role <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('role_id_for_instructor', 'addInstructorModal') is-invalid @enderror"
                                            id="add_role_id_for_instructor"
                                            name="role_id_for_instructor"
                                            required>
                                        <option value="" selected disabled>Select role...</option>
                                        @foreach ($instructorRoles as $role)
                                            <option value="{{ $role->id }}"
                                                    {{ old('role_id_for_instructor') == $role->id ? 'selected' : '' }}>
                                                {{ $role->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role_id_for_instructor', 'addInstructorModal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Instructor Information Section -->
                    <div class="card bg-light border-0">
                        <div class="card-header bg-transparent border-0 pb-1">
                            <h6 class="card-title text-primary mb-0">
                                <i class="fas fa-chalkboard-teacher me-1"></i>
                                Instructor Specific Information
                            </h6>
                        </div>
                        <div class="card-body pt-2">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="add_instructor_no" class="form-label fw-medium">
                                        <i class="fas fa-hashtag text-muted me-1"></i>
                                        Employee Number <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control @error('instructor_no', 'addInstructorModal') is-invalid @enderror"
                                           id="add_instructor_no"
                                           name="instructor_no"
                                           value="{{ old('instructor_no') }}"
                                           placeholder="e.g., EMP001"
                                           required>
                                    @error('instructor_no', 'addInstructorModal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="add_academic_degree" class="form-label fw-medium">
                                        <i class="fas fa-graduation-cap text-muted me-1"></i>
                                        Academic Degree
                                    </label>
                                    <input type="text"
                                           class="form-control @error('academic_degree', 'addInstructorModal') is-invalid @enderror"
                                           id="add_academic_degree"
                                           name="academic_degree"
                                           value="{{ old('academic_degree') }}"
                                           placeholder="e.g., PhD, Master">
                                    @error('academic_degree', 'addInstructorModal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="add_department_id_instructor" class="form-label fw-medium">
                                        <i class="fas fa-building text-muted me-1"></i>
                                        Department <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('department_id', 'addInstructorModal') is-invalid @enderror"
                                            id="add_department_id_instructor"
                                            name="department_id"
                                            required>
                                        <option value="" selected disabled>Select department...</option>
                                        @foreach ($departments as $dept)
                                            <option value="{{ $dept->id }}"
                                                    {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                                {{ $dept->department_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_id', 'addInstructorModal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="add_max_weekly_hours" class="form-label fw-medium">
                                        <i class="fas fa-clock text-muted me-1"></i>
                                        Max Weekly Hours
                                    </label>
                                    <input type="number"
                                           class="form-control @error('max_weekly_hours', 'addInstructorModal') is-invalid @enderror"
                                           id="add_max_weekly_hours"
                                           name="max_weekly_hours"
                                           value="{{ old('max_weekly_hours') }}"
                                           min="0"
                                           placeholder="e.g., 20">
                                    @error('max_weekly_hours', 'addInstructorModal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="add_availability_preferences" class="form-label fw-medium">
                                        <i class="fas fa-calendar-check text-muted me-1"></i>
                                        Availability Preferences
                                    </label>
                                    <textarea class="form-control @error('availability_preferences', 'addInstructorModal') is-invalid @enderror"
                                              id="add_availability_preferences"
                                              name="availability_preferences"
                                              rows="2"
                                              placeholder="e.g., Prefers morning classes, not available on Fridays">{{ old('availability_preferences') }}</textarea>
                                    @error('availability_preferences', 'addInstructorModal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Instructor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Instructor Modal --}}
<div class="modal fade" id="editInstructorModal" tabindex="-1" aria-labelledby="editInstructorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title d-flex align-items-center" id="editInstructorModalLabel">
                    <i class="fas fa-edit me-2"></i>
                    Edit Instructor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="#" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    {{-- Validation Errors --}}
                    @if ($errors->any() && session('error_instructor_id_on_edit'))
                        @php $currentErrorBagName = 'editInstructorModal_' . session('error_instructor_id_on_edit'); @endphp
                        @if ($errors->hasBag($currentErrorBagName))
                            <div class="alert alert-danger d-flex align-items-start mb-3" role="alert">
                                <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                                <div>
                                    <h6 class="alert-heading mb-1">Please correct the errors:</h6>
                                    <ul class="mb-0 ps-3">
                                        @foreach ($errors->getBag($currentErrorBagName)->all() as $error)
                                            <li class="small">{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                    @endif

                    <!-- User Account Section -->
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-header bg-transparent border-0 pb-1">
                            <h6 class="card-title text-primary mb-0">
                                <i class="fas fa-user me-1"></i>
                                User Account Information
                            </h6>
                        </div>
                        <div class="card-body pt-2">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="edit_user_name" class="form-label fw-medium">
                                        <i class="fas fa-user text-muted me-1"></i>
                                        Full Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="edit_user_name"
                                           name="name"
                                           value="{{ old('name') }}"
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_user_email" class="form-label fw-medium">
                                        <i class="fas fa-envelope text-muted me-1"></i>
                                        Email Address <span class="text-danger">*</span>
                                    </label>
                                    <input type="email"
                                           class="form-control"
                                           id="edit_user_email"
                                           name="email"
                                           value="{{ old('email') }}"
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_user_password" class="form-label fw-medium">
                                        <i class="fas fa-lock text-muted me-1"></i>
                                        New Password <small class="text-muted">(leave blank to keep current)</small>
                                    </label>
                                    <input type="password"
                                           class="form-control"
                                           id="edit_user_password"
                                           name="password">
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_user_password_confirmation" class="form-label fw-medium">
                                        <i class="fas fa-lock text-muted me-1"></i>
                                        Confirm New Password
                                    </label>
                                    <input type="password"
                                           class="form-control"
                                           id="edit_user_password_confirmation"
                                           name="password_confirmation">
                                </div>
                                <div class="col-12">
                                    <label for="edit_role_id_for_instructor" class="form-label fw-medium">
                                        <i class="fas fa-user-shield text-muted me-1"></i>
                                        Assign Role <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select"
                                            id="edit_role_id_for_instructor"
                                            name="role_id_for_instructor"
                                            required>
                                        <option value="" disabled>Select role...</option>
                                        @foreach ($instructorRoles as $role)
                                            <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Instructor Information Section -->
                    <div class="card bg-light border-0">
                        <div class="card-header bg-transparent border-0 pb-1">
                            <h6 class="card-title text-primary mb-0">
                                <i class="fas fa-chalkboard-teacher me-1"></i>
                                Instructor Specific Information
                            </h6>
                        </div>
                        <div class="card-body pt-2">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="edit_instructor_no" class="form-label fw-medium">
                                        <i class="fas fa-hashtag text-muted me-1"></i>
                                        Employee Number <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="edit_instructor_no"
                                           name="instructor_no"
                                           value="{{ old('instructor_no') }}"
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_academic_degree" class="form-label fw-medium">
                                        <i class="fas fa-graduation-cap text-muted me-1"></i>
                                        Academic Degree
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="edit_academic_degree"
                                           name="academic_degree"
                                           value="{{ old('academic_degree') }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_department_id_instructor" class="form-label fw-medium">
                                        <i class="fas fa-building text-muted me-1"></i>
                                        Department <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select"
                                            id="edit_department_id_instructor"
                                            name="department_id"
                                            required>
                                        <option value="" disabled>Select department...</option>
                                        @foreach ($departments as $dept)
                                            <option value="{{ $dept->id }}">{{ $dept->department_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_max_weekly_hours" class="form-label fw-medium">
                                        <i class="fas fa-clock text-muted me-1"></i>
                                        Max Weekly Hours
                                    </label>
                                    <input type="number"
                                           class="form-control"
                                           id="edit_max_weekly_hours"
                                           name="max_weekly_hours"
                                           value="{{ old('max_weekly_hours') }}"
                                           min="0">
                                </div>
                                <div class="col-12">
                                    <label for="edit_availability_preferences" class="form-label fw-medium">
                                        <i class="fas fa-calendar-check text-muted me-1"></i>
                                        Availability Preferences
                                    </label>
                                    <textarea class="form-control"
                                              id="edit_availability_preferences"
                                              name="availability_preferences"
                                              rows="2">{{ old('availability_preferences') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-sync-alt me-1"></i>Update Instructor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Instructor Modal --}}
<div class="modal fade" id="deleteInstructorModal" tabindex="-1" aria-labelledby="deleteInstructorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="deleteInstructorModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="#" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-chalkboard-teacher text-danger" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>

                    <div class="alert alert-danger d-flex align-items-start" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Are you sure?</h6>
                            <p class="mb-0 small">You are about to delete the instructor and their user account:</p>
                        </div>
                    </div>

                    <div class="card bg-light border-danger">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1" id="deleteInstructorName">Instructor Name</h6>
                                    <small class="text-muted">Including user login access</small>
                                </div>
                                <span class="badge bg-danger">Will be deleted</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <small><strong>Warning:</strong> This action cannot be undone and will also delete the user login. Associated teaching assignments might be affected.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Yes, Delete Instructor & User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Import Excel Modal --}}
<div class="modal fade" id="importInstructorsModal" tabindex="-1" aria-labelledby="importInstructorsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="importInstructorsModalLabel">
                    <i class="fas fa-file-excel me-2"></i>
                    Import Instructors from Excel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.instructors.importExcel') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-4">
                        <!-- File Upload Section -->
                        <div class="col-12">
                            <label for="instructor_excel_file_input" class="form-label fw-medium">
                                <i class="fas fa-cloud-upload-alt text-muted me-1"></i>
                                Select Excel File <span class="text-danger">*</span>
                            </label>
                            <div class="upload-zone border border-2 border-dashed rounded-3 p-4 text-center cursor-pointer">
                                <i class="fas fa-file-excel text-success mb-2" style="font-size: 2rem;"></i>
                                <input class="form-control @error('instructor_excel_file') is-invalid @enderror"
                                       type="file"
                                       id="instructor_excel_file_input"
                                       name="instructor_excel_file"
                                       accept=".xlsx,.xls,.csv"
                                       required>
                                @error('instructor_excel_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text mt-2">
                                    <small>Supported formats: .xlsx, .xls, .csv (Max: 5MB)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Instructions Section -->
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-header bg-transparent border-0 pb-0">
                                    <h6 class="card-title text-primary mb-0">
                                        <i class="fas fa-info-circle me-1"></i>
                                        File Format Instructions
                                    </h6>
                                </div>
                                <div class="card-body pt-2">
                                    <ul class="list-unstyled mb-0 small">
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                            <span>Required columns: <code>instructor_no</code>, <code>instructor_name</code>, <code>department_id</code></span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                            <span>Optional columns: <code>email</code>, <code>academic_degree</code>, <code>max_weekly_hours</code></span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-sync-alt text-info me-2 mt-1 flex-shrink-0"></i>
                                            <span>For <code>department_id</code>, you can use Department ID, Name, or Code</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <i class="fas fa-filter text-warning me-2 mt-1 flex-shrink-0"></i>
                                            <span>Existing instructors will be updated, new ones will be created</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Sample Format -->
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-header bg-primary bg-opacity-10 border-primary">
                                    <h6 class="card-title text-primary mb-0">
                                        <i class="fas fa-table me-1"></i>
                                        Sample Format
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th style="font-size: 0.75rem;">instructor_no</th>
                                                    <th style="font-size: 0.75rem;">instructor_name</th>
                                                    <th style="font-size: 0.75rem;">email</th>
                                                    <th style="font-size: 0.75rem;">department_id</th>
                                                    <th style="font-size: 0.75rem;">academic_degree</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><code class="small">EMP001</code></td>
                                                    <td class="small">Dr. Ahmed Ali</td>
                                                    <td class="small">ahmed@college.edu</td>
                                                    <td class="small">CS01</td>
                                                    <td class="small">PhD</td>
                                                </tr>
                                                <tr>
                                                    <td><code class="small">EMP002</code></td>
                                                    <td class="small">Prof. Sara Hassan</td>
                                                    <td class="small">sara@college.edu</td>
                                                    <td class="small">IT02</td>
                                                    <td class="small">Master</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i>Upload and Process
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Upload Zone Styles */
.upload-zone {
    border-color: #dee2e6 !important;
    transition: all 0.15s ease;
    cursor: pointer;
}

.upload-zone:hover {
    border-color: var(--primary-color) !important;
    background-color: rgba(59, 130, 246, 0.05);
}

.upload-zone input[type="file"] {
    border: none;
    background: transparent;
    padding: 0.5rem 0;
}

/* Modal Responsive Improvements */
@media (max-width: 992px) {
    .modal-lg {
        max-width: 90%;
    }
}

@media (max-width: 768px) {
    .modal-lg {
        max-width: 95%;
        margin: 0.5rem;
    }

    .modal-body {
        max-height: 65vh !important;
        padding: 1rem !important;
    }

    .modal-header {
        padding: 0.75rem 1rem;
    }

    .modal-footer {
        padding: 0.75rem 1rem;
    }

    .modal-title {
        font-size: 1rem;
    }

    .card-header {
        padding: 0.5rem 0.75rem;
    }

    .card-body {
        padding: 0.75rem;
    }

    .upload-zone {
        padding: 2rem 1rem !important;
    }

    .upload-zone i {
        font-size: 1.5rem !important;
    }

    .btn {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
    }

    .form-label {
        font-size: 0.875rem;
    }

    .form-control, .form-select {
        font-size: 0.875rem;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.25rem;
    }

    .modal-lg {
        max-width: 98%;
    }

    .modal-body {
        max-height: 70vh !important;
        padding: 0.75rem !important;
    }

    .modal-header {
        padding: 0.5rem 0.75rem;
    }

    .modal-footer {
        padding: 0.5rem 0.75rem;
        flex-direction: column;
        gap: 0.5rem;
    }

    .modal-footer .btn {
        width: 100%;
        margin: 0;
    }

    .modal-title {
        font-size: 0.9rem;
    }

    .row.g-3 {
        --bs-gutter-x: 0.75rem;
        --bs-gutter-y: 0.75rem;
    }

    .card-body {
        padding: 0.5rem;
    }
}

/* Table Responsive Fixes */
@media (max-width: 1199px) {
    .table-responsive {
        border: none;
    }

    .table {
        font-size: 0.8rem;
    }

    .btn-group-sm > .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}

/* Mobile Card Improvements */
@media (max-width: 991px) {
    .card-body {
        padding: 1rem;
    }

    .card-title {
        font-size: 1rem;
    }

    .dropdown-menu {
        min-width: 160px;
    }

    .dropdown-item {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
}

/* Prevent horizontal scroll */
body {
    overflow-x: hidden;
}

.container-fluid {
    padding-left: 1rem;
    padding-right: 1rem;
}

@media (max-width: 576px) {
    .container-fluid {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .page-title {
        font-size: 1.25rem;
    }

    .btn-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload enhancement
    const fileInput = document.getElementById('instructor_excel_file_input');
    const uploadZone = document.querySelector('#importInstructorsModal .upload-zone');

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
                    icon.style.fontSize = '2rem';
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
});
</script>
