{{-- ======================================= --}}
{{-- مودال إضافة مدرس جديد --}}
{{-- ======================================= --}}
<div class="modal fade" id="addInstructorModal" tabindex="-1" aria-labelledby="addInstructorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addInstructorModalLabel">Add New Instructor & User Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addInstructorForm" action="{{ route('data-entry.instructors.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    {{-- عرض أخطاء الـ validation --}}
                    @if ($errors->hasBag('addInstructorModal'))
                        <div class="alert alert-danger small p-2 mb-3 validation-errors-container">
                            <strong>Please correct the errors:</strong>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->getBag('addInstructorModal')->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <h6>User Account Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-1">
                            <label for="add_user_name" class="form-label">Full Name <span
                                    class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control @error('name', 'addInstructorModal') is-invalid @enderror"
                                id="add_user_name" name="name" value="{{ old('name') }}" required>
                            @error('name', 'addInstructorModal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-1">
                            <label for="add_user_email" class="form-label">Email Address <span
                                    class="text-danger">*</span></label>
                            <input type="email"
                                class="form-control @error('email', 'addInstructorModal') is-invalid @enderror"
                                id="add_user_email" name="email" value="{{ old('email') }}" required>
                            @error('email', 'addInstructorModal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-1">
                            <label for="add_user_password" class="form-label">Password <span
                                    class="text-danger">*</span></label>
                            <input type="password"
                                class="form-control @error('password', 'addInstructorModal') is-invalid @enderror"
                                id="add_user_password" name="password" required>
                            @error('password', 'addInstructorModal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-1">
                            <label for="add_user_password_confirmation" class="form-label">Confirm Password <span
                                    class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="add_user_password_confirmation"
                                name="password_confirmation" required>
                        </div>
                    </div>
                    <div class="mb-1">
                        <label for="add_role_id_for_instructor" class="form-label">Assign Role <span
                                class="text-danger">*</span></label>
                        <select
                            class="form-select @error('role_id_for_instructor', 'addInstructorModal') is-invalid @enderror"
                            id="add_role_id_for_instructor" name="role_id_for_instructor" required>
                            <option value="" selected disabled>Select role...</option>
                            @foreach ($instructorRoles as $role)
                                {{-- $instructorRoles من الكنترولر --}}
                                <option value="{{ $role->id }}"
                                    {{ old('role_id_for_instructor') == $role->id ? 'selected' : '' }}>
                                    {{ $role->display_name }}</option>
                            @endforeach
                        </select>
                        @error('role_id_for_instructor', 'addInstructorModal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr class="my-4">
                    <h6>Instructor Specific Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-1">
                            <label for="add_instructor_no" class="form-label">Employee Number <span
                                    class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control @error('instructor_no', 'addInstructorModal') is-invalid @enderror"
                                id="add_instructor_no" name="instructor_no" value="{{ old('instructor_no') }}"
                                required>
                            @error('instructor_no', 'addInstructorModal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-1">
                            <label for="add_academic_degree" class="form-label">Academic Degree</label>
                            <input type="text"
                                class="form-control @error('academic_degree', 'addInstructorModal') is-invalid @enderror"
                                id="add_academic_degree" name="academic_degree" value="{{ old('academic_degree') }}">
                            @error('academic_degree', 'addInstructorModal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-1">
                            <label for="add_department_id_instructor" class="form-label">Department <span
                                    class="text-danger">*</span></label>
                            <select
                                class="form-select @error('department_id', 'addInstructorModal') is-invalid @enderror"
                                id="add_department_id_instructor" name="department_id" required>
                                <option value="" selected disabled>Select department...</option>
                                @foreach ($departments as $dept)
                                    {{-- $departments من الكنترولر --}}
                                    <option value="{{ $dept->id }}"
                                        {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->department_name }}</option>
                                @endforeach
                            </select>
                            @error('department_id', 'addInstructorModal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-1">
                            <label for="add_max_weekly_hours" class="form-label">Max Weekly Hours</label>
                            <input type="number"
                                class="form-control @error('max_weekly_hours', 'addInstructorModal') is-invalid @enderror"
                                id="add_max_weekly_hours" name="max_weekly_hours"
                                value="{{ old('max_weekly_hours') }}" min="0">
                            @error('max_weekly_hours', 'addInstructorModal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    {{-- <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_office_location" class="form-label">Office Location</label>
                            <input type="text" class="form-control @error('office_location', 'addInstructorModal') is-invalid @enderror" id="add_office_location" name="office_location" value="{{ old('office_location') }}">
                            @error('office_location', 'addInstructorModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_office_hours" class="form-label">Office Hours</label>
                            <input type="text" class="form-control @error('office_hours', 'addInstructorModal') is-invalid @enderror" id="add_office_hours" name="office_hours" value="{{ old('office_hours') }}">
                            @error('office_hours', 'addInstructorModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div> --}}
                    <div class="mb-3">
                        <label for="add_availability_preferences" class="form-label">Availability Preferences</label>
                        <textarea class="form-control @error('availability_preferences', 'addInstructorModal') is-invalid @enderror"
                            id="add_availability_preferences" name="availability_preferences" rows="2">{{ old('availability_preferences') }}</textarea>
                        @error('availability_preferences', 'addInstructorModal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Instructor</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ======================================= --}}
{{-- مودال تعديل مدرس --}}
{{-- ======================================= --}}
<div class="modal fade" id="editInstructorModal" tabindex="-1" aria-labelledby="editInstructorModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editInstructorModalLabel">Edit Instructor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editInstructorForm" action="#" method="POST"> {{-- Action يتغير بـ JS --}}
                @csrf
                @method('PUT')
                <div class="modal-body">
                    {{-- لعرض أخطاء الـ validation --}}
                    @if ($errors->any() && session('error_instructor_id_on_edit'))
                        @php $currentErrorBagName = 'editInstructorModal_' . session('error_instructor_id_on_edit'); @endphp
                        @if ($errors->hasBag($currentErrorBagName))
                            <div class="alert alert-danger small p-2 mb-3 validation-errors-container">
                                <strong>Please correct the errors:</strong>
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->getBag($currentErrorBagName)->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif

                    <h6>User Account Information</h6>
                    {{-- حقول اليوزر (مع old() input) --}}
                    <div class="row">
                        <div class="col-md-6 mb-1">
                            <label for="edit_user_name" class="form-label">Full Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_user_name" name="name"
                                value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-6 mb-1">
                            <label for="edit_user_email" class="form-label">Email Address <span
                                    class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="edit_user_email" name="email"
                                value="{{ old('email') }}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-1">
                            <label for="edit_user_password" class="form-label">New Password (leave blank to keep
                                current)</label>
                            <input type="password" class="form-control" id="edit_user_password" name="password"
                                value="">
                        </div>
                        <div class="col-md-6 mb-1">
                            <label for="edit_user_password_confirmation" class="form-label">Confirm New
                                Password</label>
                            <input type="password" class="form-control" id="edit_user_password_confirmation"
                                name="password_confirmation">
                        </div>
                    </div>
                    <div class="mb-1">
                        <label for="edit_role_id_for_instructor" class="form-label">Assign Role <span
                                class="text-danger">*</span></label>
                        <select class="form-select" id="edit_role_id_for_instructor" name="role_id_for_instructor"
                            required>
                            <option value="" disabled>Select role...</option>
                            {{-- $instructorRoles يتم تمريرها من الكنترولر --}}
                            @foreach ($instructorRoles as $role)
                                <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <hr class="my-4">
                    <h6>Instructor Specific Information</h6>
                    {{-- حقول المدرس (مع old() input) --}}
                    <div class="row">
                        <div class="col-md-6 mb-1">
                            <label for="edit_instructor_no" class="form-label">Employee Number <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_instructor_no" name="instructor_no"
                                value="{{ old('instructor_no') }}" required>
                        </div>
                        <div class="col-md-6 mb-1">
                            <label for="edit_academic_degree" class="form-label">Academic Degree</label>
                            <input type="text" class="form-control" id="edit_academic_degree"
                                name="academic_degree" value="{{ old('academic_degree') }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-1">
                            <label for="edit_department_id_instructor" class="form-label">Department <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="edit_department_id_instructor" name="department_id"
                                required>
                                <option value="" disabled>Select department...</option>
                                {{-- $departments يتم تمريرها من الكنترولر --}}
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->department_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-1">
                            <label for="edit_max_weekly_hours" class="form-label">Max Weekly Hours</label>
                            <input type="number" class="form-control" id="edit_max_weekly_hours"
                                name="max_weekly_hours" value="{{ old('max_weekly_hours') }}" min="0">
                        </div>
                    </div>
                    <div class="mb-1">
                        <label for="add_availability_preferences" class="form-label">Availability Preferences</label>
                        <textarea class="form-control @error('availability_preferences', 'addInstructorModal') is-invalid @enderror"
                            id="add_availability_preferences" name="availability_preferences" rows="2">{{ old('availability_preferences') }}</textarea>
                        @error('availability_preferences', 'addInstructorModal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- ... باقي حقول المدرس (office_location, office_hours, availability_preferences) بنفس الطريقة ... --}}

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Instructor</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ======================================= --}}
{{-- مودال حذف مدرس --}}
{{-- ======================================= --}}
<div class="modal fade" id="deleteInstructorModal" tabindex="-1" aria-labelledby="deleteInstructorModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteInstructorModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="deleteInstructorForm" action="#" method="POST"> {{-- Action يتغير بـ JS --}}
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p>Are you sure you want to delete the instructor <strong id="deleteInstructorName">this
                            instructor</strong> and their associated user account?</p>
                    <p class="text-danger small">This action cannot be undone and will also delete the user login.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete Instructor & User</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- *** مودال رفع ملف الإكسل للمدرسين *** --}}
<div class="modal fade" id="importInstructorsModal" tabindex="-1" aria-labelledby="importInstructorsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importInstructorsModalLabel">Import Instructors from Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('data-entry.instructors.importExcel') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="instructor_excel_file_input" class="form-label">Select Excel File <span
                                class="text-danger">*</span></label>
                        <input class="form-control @error('instructor_excel_file') is-invalid @enderror"
                            type="file" id="instructor_excel_file_input" name="instructor_excel_file"
                            accept=".xlsx, .xls, .csv" required>
                        @error('instructor_excel_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="alert alert-info small p-2">
                        <strong>File Format Instructions:</strong><br>
                        - First row should be headers (e.g., <code>instructor_no</code>, <code>instructor_name</code>,
                        <code>department_id</code>, <code>email</code>, <code>academic_degree</code>,
                        <code>max_weekly_hours</code>).<br>
                        - <code>instructor_no</code>, <code>instructor_name</code>, <code>department_id</code> are
                        highly recommended.<br>
                        - <code>email</code> is optional (will be generated if missing). <code>academic_degree</code>
                        can be in name or its column.<br>
                        - For <code>department_id</code>, you can use Department ID, Name, or Code.<br>
                        - If <code>instructor_no</code> exists, record will be updated. Otherwise, a new instructor and
                        user will be created.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i> Upload and Process File
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
