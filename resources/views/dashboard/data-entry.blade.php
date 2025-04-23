{{-- @extends('dashboard.layout')

@section('content')

<!-- Main Content -->
<div class="main-content">
    <div class="data-entry-container">
        <h1 class="data-entry-header">Data Entry</h1>
        <p class="data-entry-subheader">Manage courses, classrooms, and faculty information</p>

        <ul class="nav nav-tabs" id="dataEntryTabs">
            <li class="nav-item">
                <a class="nav-link active" id="courses-tab" data-bs-toggle="tab" href="#courses">Courses</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="classrooms-tab" data-bs-toggle="tab" href="#classrooms">Classrooms</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="faculty-tab" data-bs-toggle="tab" href="#faculty">Faculty Members</a>
            </li>
        </ul>

        <div class="tab-content" id="dataEntryTabContent">
            <!-- Courses Tab -->
            <div class="tab-pane fade show active" id="courses">
                <div class="form-section">
                    <h3 class="form-section-header">
                        <i class="fas fa-info-circle"></i> Course Information
                    </h3>

                    <div class="form-group">
                        <label class="form-label">Course Name</label>
                        <input type="text" class="form-control" id="courseName" placeholder="Enter course name">
                    </div>

                    <div class="form-group">
                        <label class="form-label">College</label>
                        <select class="form-select" id="college">
                            <option selected disabled>Select college</option>
                            <option>College of Engineering</option>
                            <option>College of Science</option>
                            <option>College of Arts</option>
                            <option>College of Business</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Student Count</label>
                        <input type="number" class="form-control" id="studentCount" placeholder="Enter student count">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select class="form-select" id="department">
                            <option selected disabled>Select department</option>
                            <option>Computer Science</option>
                            <option>Electrical Engineering</option>
                            <option>Mechanical Engineering</option>
                            <option>Mathematics</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Special Requirements</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="computerLab">
                            <label class="form-check-label" for="computerLab">Computer Lab</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="projector">
                            <label class="form-check-label" for="projector">Projector</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="smartBoard">
                            <label class="form-check-label" for="smartBoard">Smart Board</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="audioSystem">
                            <label class="form-check-label" for="audioSystem">Audio System</label>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-header">
                        <i class="fas fa-upload"></i> Bulk Upload
                    </h3>

                    <div class="upload-area" id="uploadArea">
                        <div class="upload-icon">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <div class="upload-text">
                            Drag and drop your Excel file here, or click to browse
                        </div>
                        <div class="upload-hint">
                            Supported formats: xlsx, xls
                        </div>
                        <button class="btn btn-primary" id="uploadBtn">
                            <i class="fas fa-upload"></i> Upload File
                        </button>
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-outline-secondary" id="resetBtn">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <button class="btn btn-primary" id="saveBtn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>

                <div class="status-message success" id="successMessage">
                    <i class="fas fa-check-circle"></i> Changes saved successfully
                </div>
                <div class="status-message error" id="errorMessage">
                    <i class="fas fa-exclamation-circle"></i> Please fill in all required fields
                </div>
            </div>

            <!-- Classrooms Tab -->
            <div class="tab-pane fade" id="classrooms">
                <div class="form-section">
                    <h3 class="form-section-header">
                        <i class="fas fa-door-open"></i> Classroom Information
                    </h3>

                    <div class="form-group">
                        <label class="form-label">Room Number</label>
                        <input type="text" class="form-control" id="roomNumber" placeholder="Enter room number">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Building</label>
                        <select class="form-select" id="building">
                            <option selected disabled>Select building</option>
                            <option>Engineering Building</option>
                            <option>Science Building</option>
                            <option>Main Building</option>
                            <option>Library Building</option>
                        </select>
                    </div>

                    <div class="capacity-row">
                        <div class="form-group">
                            <label class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="capacity" placeholder="Enter capacity">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Room Type</label>
                            <select class="form-select" id="roomType">
                                <option selected disabled>Select room type</option>
                                <option>Lecture Hall</option>
                                <option>Classroom</option>
                                <option>Laboratory</option>
                                <option>Seminar Room</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Available Equipment</label>
                        <div class="equipment-list">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="projectorRoom">
                                <label class="form-check-label" for="projectorRoom">Projector</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="whiteboard">
                                <label class="form-check-label" for="whiteboard">Whiteboard</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="smartBoardRoom">
                                <label class="form-check-label" for="smartBoardRoom">Smart Board</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="soundSystem">
                                <label class="form-check-label" for="soundSystem">Sound System</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="computers">
                                <label class="form-check-label" for="computers">Computers</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="microscope">
                                <label class="form-check-label" for="microscope">Microscope</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-outline-secondary" id="resetClassroomBtn">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <button class="btn btn-primary" id="saveClassroomBtn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>

                <div class="status-message success" id="classroomSuccessMessage">
                    <i class="fas fa-check-circle"></i> Classroom information saved successfully
                </div>
                <div class="status-message error" id="classroomErrorMessage">
                    <i class="fas fa-exclamation-circle"></i> Please fill in all required fields
                </div>
            </div>

            <!-- Faculty Members Tab -->
            <div class="tab-pane fade" id="faculty">
                <div class="form-section">
                    <h3 class="form-section-header">
                        <i class="fas fa-user-tie"></i> Faculty Information
                    </h3>

                    <div class="faculty-row">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" placeholder="Enter first name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" placeholder="Enter last name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="facultyEmail" placeholder="Enter email">
                    </div>

                    <div class="faculty-row">
                        <div class="form-group">
                            <label class="form-label">Department</label>
                            <select class="form-select" id="facultyDepartment">
                                <option selected disabled>Select department</option>
                                <option>Computer Science</option>
                                <option>Electrical Engineering</option>
                                <option>Mechanical Engineering</option>
                                <option>Mathematics</option>
                                <option>Physics</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Position</label>
                            <select class="form-select" id="facultyPosition">
                                <option selected disabled>Select position</option>
                                <option>Professor</option>
                                <option>Associate Professor</option>
                                <option>Assistant Professor</option>
                                <option>Lecturer</option>
                                <option>Instructor</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Office Location</label>
                        <input type="text" class="form-control" id="officeLocation" placeholder="Enter office location">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Office Hours</label>
                        <textarea class="form-control" id="officeHours" rows="3" placeholder="Enter office hours"></textarea>
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-outline-secondary" id="resetFacultyBtn">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <button class="btn btn-primary" id="saveFacultyBtn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>

                <div class="status-message success" id="facultySuccessMessage">
                    <i class="fas fa-check-circle"></i> Faculty information saved successfully
                </div>
                <div class="status-message error" id="facultyErrorMessage">
                    <i class="fas fa-exclamation-circle"></i> Please fill in all required fields
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
 --}}

 @extends('dashboard.layout')

@section('content')

<!-- Main Content -->
<div class="main-content">
    <div class="data-entry-container">
        <h1 class="data-entry-header">Data Entry</h1>
        <p class="data-entry-subheader">Manage subjects, rooms, and faculty information</p>

        {{-- Tabs Navigation --}}
        <ul class="nav nav-tabs" id="dataEntryTabs" role="tablist">
            <li class="nav-item" role="presentation">
                {{-- Changed href and aria-controls to match new ID --}}
                <button class="nav-link active" id="subjects-tab" data-bs-toggle="tab" data-bs-target="#subjects" type="button" role="tab" aria-controls="subjects" aria-selected="true">Subjects</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rooms-tab" data-bs-toggle="tab" data-bs-target="#rooms" type="button" role="tab" aria-controls="rooms" aria-selected="false">Rooms</button>
            </li>
            <li class="nav-item" role="presentation">
                 {{-- Changed text and IDs --}}
                <button class="nav-link" id="instructors-tab" data-bs-toggle="tab" data-bs-target="#instructors" type="button" role="tab" aria-controls="instructors" aria-selected="false">Faculty / Instructors</button>
            </li>
        </ul>

        <div class="tab-content" id="dataEntryTabContent">
            <!-- ####################### Subjects Tab ####################### -->
            {{-- Changed ID to 'subjects' --}}
            <div class="tab-pane fade show active" id="subjects" role="tabpanel" aria-labelledby="subjects-tab">
                <form action="{{-- route('subjects.store') --}}" method="POST" enctype="multipart/form-data"> {{-- Add action route later --}}
                    @csrf
                    <div class="form-section">
                        <h3 class="form-section-header">
                            <i class="fas fa-book"></i> Subject Information
                        </h3>

                        {{-- Added Subject Number/Code --}}
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label for="subject_no" class="form-label">Subject Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="subject_no" name="subject_no" placeholder="e.g., CS101" required>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label for="subject_name" class="form-label">Subject Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="subject_name" name="subject_name" placeholder="Enter subject name" required>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Added Subject Load (Credits) --}}
                            <div class="col-md-4 form-group mb-3">
                                <label for="subject_load" class="form-label">Credit Hours <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="subject_load" name="subject_load" placeholder="Total credits" required min="0">
                            </div>
                             {{-- Added Theoretical Hours --}}
                            <div class="col-md-4 form-group mb-3">
                                <label for="theoretical_hours" class="form-label">Weekly Theoretical Hours <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="theoretical_hours" name="theoretical_hours" placeholder="e.g., 2" required min="0" value="0">
                            </div>
                             {{-- Added Practical Hours --}}
                            <div class="col-md-4 form-group mb-3">
                                <label for="practical_hours" class="form-label">Weekly Practical Hours <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="practical_hours" name="practical_hours" placeholder="e.g., 1" required min="0" value="0">
                            </div>
                        </div>

                        <div class="row">
                            {{-- Changed from College to Subject Type --}}
                            <div class="col-md-4 form-group mb-3">
                                <label for="subject_type_id" class="form-label">Subject Type</label>
                                <select class="form-select" id="subject_type_id" name="subject_type_id">
                                    <option value="" selected>Select type...</option>
                                    {{-- Options will be populated from subjects_types table by Controller --}}
                                    {{-- Example: <option value="1">Compulsory</option> --}}
                                </select>
                            </div>
                            {{-- Added Subject Category --}}
                            <div class="col-md-4 form-group mb-3">
                                <label for="subject_category_id" class="form-label">Subject Category</label>
                                <select class="form-select" id="subject_category_id" name="subject_category_id">
                                    <option value="" selected>Select category...</option>
                                     {{-- Options will be populated from subjects_categories table by Controller --}}
                                    {{-- Example: <option value="1">Theoretical</option> --}}
                                </select>
                            </div>
                            {{-- Kept Department (Owning Department) --}}
                            <div class="col-md-4 form-group mb-3">
                                <label for="department_id" class="form-label">Owning Department</label>
                                <select class="form-select" id="department_id" name="department_id">
                                    <option value="" selected>Select department...</option>
                                     {{-- Options will be populated from departments table by Controller --}}
                                    {{-- Example: <option value="1">Computer Science</option> --}}
                                </select>
                            </div>
                        </div>

                        {{-- Removed 'Student Count' - Handled later in planning/sectioning --}}
                        {{-- Removed 'Special Requirements' checkboxes - Room type/category handles this --}}

                    </div>

                    {{-- Bulk Upload Section - Kept as is --}}
                    <div class="form-section">
                        <h3 class="form-section-header">
                            <i class="fas fa-upload"></i> Bulk Upload Subjects
                        </h3>
                        <div class="upload-area" id="subjectUploadArea">
                           {{-- Input file for upload --}}
                           <input type="file" name="subject_excel_file" id="subject_excel_file" accept=".xlsx, .xls" style="display: none;">
                            <div class="upload-icon"> <i class="fas fa-file-excel"></i> </div>
                            <div class="upload-text"> Drag and drop your Excel file here, or click to browse </div>
                            <div class="upload-hint"> Supported formats: xlsx, xls </div>
                             {{-- Button to trigger file input click --}}
                            <button type="button" class="btn btn-secondary mt-2" onclick="document.getElementById('subject_excel_file').click();">
                                <i class="fas fa-folder-open"></i> Browse File
                            </button>
                            <div id="subject_file_name" class="mt-2"></div> {{-- To show selected file name --}}
                        </div>
                    </div>

                    {{-- Action Buttons - Kept as is --}}
                    <div class="action-buttons mt-4">
                        <button type="reset" class="btn btn-outline-secondary" id="resetSubjectBtn">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary" id="saveSubjectBtn">
                            <i class="fas fa-save"></i> Save Subject
                        </button>
                    </div>

                    {{-- Status Messages - Kept as is --}}
                    <div class="status-message success mt-3" id="subjectSuccessMessage" style="display: none;">
                        <i class="fas fa-check-circle"></i> Subject saved successfully
                    </div>
                    <div class="status-message error mt-3" id="subjectErrorMessage" style="display: none;">
                        <i class="fas fa-exclamation-circle"></i> Please fill in all required fields correctly.
                    </div>
                </form>
            </div>

            <!-- ####################### Rooms Tab ####################### -->
             {{-- Changed ID to 'rooms' --}}
            <div class="tab-pane fade" id="rooms" role="tabpanel" aria-labelledby="rooms-tab">
                 <form action="{{-- route('rooms.store') --}}" method="POST"> {{-- Add action route later --}}
                     @csrf
                    <div class="form-section">
                        <h3 class="form-section-header">
                            <i class="fas fa-door-open"></i> Room Information
                        </h3>

                        <div class="row">
                            {{-- Kept Room Number --}}
                            <div class="col-md-6 form-group mb-3">
                                <label for="room_no" class="form-label">Room Number / Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="room_no" name="room_no" placeholder="Enter room number (e.g., R101, L2)" required>
                            </div>
                            {{-- Added Room Name (Optional) --}}
                            <div class="col-md-6 form-group mb-3">
                                <label for="room_name" class="form-label">Room Name (Optional)</label>
                                <input type="text" class="form-control" id="room_name" name="room_name" placeholder="e.g., Main Lecture Hall">
                            </div>
                        </div>

                        <div class="row">
                            {{-- Kept Capacity, Changed ID/Name --}}
                            <div class="col-md-4 form-group mb-3">
                                <label for="room_size" class="form-label">Capacity (Seats) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="room_size" name="room_size" placeholder="Enter capacity" required min="1">
                            </div>
                             {{-- Kept Room Type, Changed ID/Name --}}
                            <div class="col-md-4 form-group mb-3">
                                <label for="room_type_id" class="form-label">Room Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="room_type_id" name="room_type_id" required>
                                    <option value="" selected disabled>Select room type...</option>
                                    {{-- Options populated from rooms_types table --}}
                                    {{-- Example: <option value="1">Lecture Hall</option> --}}
                                </select>
                            </div>
                            {{-- Added Room Gender --}}
                             <div class="col-md-4 form-group mb-3">
                                <label for="room_gender" class="form-label">Gender Allocation <span class="text-danger">*</span></label>
                                <select class="form-select" id="room_gender" name="room_gender" required>
                                    <option value="Mixed" selected>Mixed</option>
                                    <option value="Male">Male Only</option>
                                    <option value="Female">Female Only</option>
                                </select>
                            </div>
                        </div>

                         {{-- Changed Building to Branch (Optional) --}}
                        <div class="form-group mb-3">
                            <label for="room_branch" class="form-label">Branch (Optional)</label>
                            <input type="text" class="form-control" id="room_branch" name="room_branch" placeholder="Enter branch name if applicable">
                        </div>

                        {{-- Updated Available Equipment (will be stored as JSON) --}}
                        <div class="form-group mb-3">
                            <label class="form-label">Available Equipment</label>
                            <div class="equipment-list">
                                {{-- Use name="available_equipment[]" to collect as array for backend JSON encoding --}}
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="eq_projector" name="available_equipment[]" value="Projector">
                                    <label class="form-check-label" for="eq_projector">Projector</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="eq_whiteboard" name="available_equipment[]" value="Whiteboard">
                                    <label class="form-check-label" for="eq_whiteboard">Whiteboard</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="eq_smartboard" name="available_equipment[]" value="Smart Board">
                                    <label class="form-check-label" for="eq_smartboard">Smart Board</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="eq_sound" name="available_equipment[]" value="Sound System">
                                    <label class="form-check-label" for="eq_sound">Sound System</label>
                                </div>
                                 <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="eq_computers" name="available_equipment[]" value="Computers">
                                    <label class="form-check-label" for="eq_computers">Computers</label>
                                </div>
                                {{-- Add other relevant equipment --}}
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons - Kept as is, updated IDs --}}
                    <div class="action-buttons mt-4">
                        <button type="reset" class="btn btn-outline-secondary" id="resetRoomBtn">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary" id="saveRoomBtn">
                            <i class="fas fa-save"></i> Save Room
                        </button>
                    </div>

                     {{-- Status Messages - Kept as is, updated IDs --}}
                    <div class="status-message success mt-3" id="roomSuccessMessage" style="display: none;">
                        <i class="fas fa-check-circle"></i> Room information saved successfully
                    </div>
                    <div class="status-message error mt-3" id="roomErrorMessage" style="display: none;">
                        <i class="fas fa-exclamation-circle"></i> Please fill in all required fields correctly.
                    </div>
                </form>
            </div>

            <!-- ####################### Faculty / Instructors Tab ####################### -->
             {{-- Changed ID to 'instructors' --}}
            <div class="tab-pane fade" id="instructors" role="tabpanel" aria-labelledby="instructors-tab">
                 <form action="{{-- route('instructors.store') --}}" method="POST"> {{-- Add action route later --}}
                    @csrf
                    <div class="form-section">
                        <h3 class="form-section-header">
                            <i class="fas fa-user-tie"></i> Instructor Information (User & Profile)
                        </h3>

                        {{-- Combined First/Last name into Full Name (maps to users.name) --}}
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label for="user_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="user_name" name="name" placeholder="Enter full name" required>
                            </div>
                            {{-- Kept Email (maps to users.email) --}}
                            <div class="col-md-6 form-group mb-3">
                                <label for="user_email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="user_email" name="email" placeholder="Enter email" required>
                            </div>
                        </div>

                         {{-- Added Password Fields (for creating NEW users) --}}
                         {{-- Might hide these when editing an existing user --}}
                         <div class="row" id="password_section">
                            <div class="col-md-6 form-group mb-3">
                                <label for="user_password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="user_password" name="password" placeholder="Enter password" required> {{-- Make 'required' conditional in JS/Controller if editing --}}
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label for="user_password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="user_password_confirmation" name="password_confirmation" placeholder="Confirm password" required> {{-- Make 'required' conditional --}}
                            </div>
                        </div>

                        {{-- Added Instructor Number (maps to instructors.instructor_no) --}}
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label for="instructor_no" class="form-label">Instructor ID / Employee No <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="instructor_no" name="instructor_no" placeholder="Enter unique instructor number" required>
                            </div>
                           {{-- Changed Position to Academic Degree (maps to instructors.academic_degree) --}}
                            <div class="col-md-6 form-group mb-3">
                                <label for="academic_degree" class="form-label">Academic Degree / Position <span class="text-danger">*</span></label>
                                {{-- Using select for consistency, can be text --}}
                                <select class="form-select" id="academic_degree" name="academic_degree" required>
                                    <option value="" selected disabled>Select degree...</option>
                                    <option value="Instructor">Instructor</option>
                                    <option value="Lecturer">Lecturer</option>
                                    <option value="Assistant Professor">Assistant Professor</option>
                                    <option value="Associate Professor">Associate Professor</option>
                                    <option value="Professor">Professor</option>
                                    <option value="Other">Other</option> {{-- Add others if needed --}}
                                </select>
                            </div>
                        </div>

                        <div class="row">
                           {{-- Kept Department (maps to instructors.department_id) --}}
                            <div class="col-md-6 form-group mb-3">
                                <label for="instructor_department_id" class="form-label">Department <span class="text-danger">*</span></label>
                                <select class="form-select" id="instructor_department_id" name="department_id" required>
                                    <option value="" selected disabled>Select department...</option>
                                     {{-- Options populated from departments table --}}
                                </select>
                            </div>
                             {{-- Added Max Weekly Hours (maps to instructors.max_weekly_hours) --}}
                            <div class="col-md-6 form-group mb-3">
                                <label for="max_weekly_hours" class="form-label">Max Weekly Hours (Optional)</label>
                                <input type="number" class="form-control" id="max_weekly_hours" name="max_weekly_hours" placeholder="Leave blank for default" min="0">
                            </div>
                        </div>

                        {{-- Kept Office Location (maps to instructors.office_location) --}}
                        <div class="form-group mb-3">
                            <label for="office_location" class="form-label">Office Location (Optional)</label>
                            <input type="text" class="form-control" id="office_location" name="office_location" placeholder="Enter office location">
                        </div>

                        {{-- Kept Office Hours (maps to instructors.office_hours) --}}
                        <div class="form-group mb-3">
                            <label for="office_hours" class="form-label">Office Hours (Optional)</label>
                            <textarea class="form-control" id="office_hours" name="office_hours" rows="2" placeholder="e.g., Mon 10-12, Wed 1-3"></textarea>
                        </div>

                         {{-- Hidden input for Role ID (Set automatically in backend for instructors) --}}
                         <input type="hidden" name="role_id" value="{{-- Get Instructor Role ID from backend --}}">
                    </div>

                     {{-- Action Buttons - Kept as is, updated IDs --}}
                    <div class="action-buttons mt-4">
                        <button type="reset" class="btn btn-outline-secondary" id="resetInstructorBtn">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary" id="saveInstructorBtn">
                            <i class="fas fa-save"></i> Save Instructor
                        </button>
                    </div>

                    {{-- Status Messages - Kept as is, updated IDs --}}
                    <div class="status-message success mt-3" id="instructorSuccessMessage" style="display: none;">
                        <i class="fas fa-check-circle"></i> Instructor information saved successfully
                    </div>
                    <div class="status-message error mt-3" id="instructorErrorMessage" style="display: none;">
                        <i class="fas fa-exclamation-circle"></i> Please fill in all required fields correctly.
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Simple script to show selected file name for subject upload --}}
<script>
    document.getElementById('subject_excel_file')?.addEventListener('change', function(e){
        var fileName = e.target.files[0]?.name || 'No file selected';
        document.getElementById('subject_file_name').textContent = 'Selected file: ' + fileName;
    });
     // Add similar scripts for other potential file uploads if needed
</script>

@endsection
