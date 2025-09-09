@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-chalkboard-teacher text-primary me-2"></i>
                        Manage Instructors
                    </h4>
                    <p class="text-muted mb-0">Create and manage instructor accounts and profiles</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addInstructorModal">
                        <i class="fas fa-plus me-1"></i>
                        <span class="d-none d-sm-inline">Add New</span>
                    </button>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#importInstructorsModal">
                        <i class="fas fa-file-excel me-1"></i>
                        <span class="d-none d-sm-inline">Import Excel</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    @if (session('skipped_details'))
        <div class="alert alert-warning d-flex align-items-start mb-4">
            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
            <div>
                <strong class="d-block mb-1">Import Process - Skipped Rows:</strong>
                <ul class="mb-0 small" style="max-height: 200px; overflow-y: auto;">
                    @foreach (session('skipped_details') as $detail)
                        <li>{{ $detail }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Main Content Card -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list text-muted me-2"></i>
                            Instructors List
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $instructors->total() }} Total</span>
                    </div>
                </div>

                <div class="card-body pt-3">
                    @if($instructors->count() > 0)
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 text-center" style="width: 50px;">#</th>
                                        <th class="border-0">Emp. No</th>
                                        <th class="border-0">Name</th>
                                        <th class="border-0">Email</th>
                                        <th class="border-0">Department</th>
                                        <th class="border-0">Degree</th>
                                        <th class="border-0">Max Hours</th>
                                        <th class="border-0 text-center" style="width: 120px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($instructors as $index => $instructor)
                                        <tr class="border-bottom">
                                            <td class="text-center text-muted">
                                                <small>{{ $instructors->firstItem() + $index }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark font-monospace">{{ $instructor->instructor_no }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                                        {{ strtoupper(substr($instructor->instructor_name ?? optional($instructor->user)->name, 0, 2)) }}
                                                    </div>
                                                    <span class="fw-medium">{{ $instructor->instructor_name ?? optional($instructor->user)->name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    {{ optional($instructor->user)->email ?? 'N/A' }}
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info bg-opacity-10 text-info">
                                                    {{ optional($instructor->department)->department_name ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $instructor->academic_degree ?? '-' }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success bg-opacity-10 text-success">
                                                    {{ $instructor->max_weekly_hours ?? '-' }} hrs
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button class="btn btn-outline-primary btn-sm open-edit-instructor-modal"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editInstructorModal"
                                                            data-instructor-id="{{ $instructor->id }}"
                                                            data-action="{{ route('data-entry.instructors.update', $instructor->id) }}"
                                                            data-instructor='@json($instructor->load('user'))'
                                                            data-departments='@json($departments)'
                                                            data-roles='@json($instructorRoles)'
                                                            title="Edit Instructor">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm open-delete-instructor-modal"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteInstructorModal"
                                                            data-action="{{ route('data-entry.instructors.destroy', $instructor->id) }}"
                                                            data-instructor-name="{{ $instructor->instructor_name ?? optional($instructor->user)->name }}"
                                                            title="Delete Instructor">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Tablet/Mobile Cards -->
                        <div class="d-lg-none">
                            @foreach ($instructors as $index => $instructor)
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-start flex-grow-1">
                                                <div class="user-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 0.875rem;">
                                                    {{ strtoupper(substr($instructor->instructor_name ?? optional($instructor->user)->name, 0, 2)) }}
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="card-title mb-1">{{ $instructor->instructor_name ?? optional($instructor->user)->name }}</h6>
                                                    <div class="d-flex flex-wrap gap-1 mb-2">
                                                        <span class="badge bg-light text-dark font-monospace">{{ $instructor->instructor_no }}</span>
                                                        @if($instructor->department)
                                                            <span class="badge bg-info bg-opacity-10 text-info">{{ $instructor->department->department_name }}</span>
                                                        @endif
                                                        @if($instructor->max_weekly_hours)
                                                            <span class="badge bg-success bg-opacity-10 text-success">{{ $instructor->max_weekly_hours }} hrs</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-muted small">
                                                        <div><i class="fas fa-envelope me-1"></i>{{ optional($instructor->user)->email ?? 'N/A' }}</div>
                                                        @if($instructor->academic_degree)
                                                            <div><i class="fas fa-graduation-cap me-1"></i>{{ $instructor->academic_degree }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item open-edit-instructor-modal"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editInstructorModal"
                                                                data-instructor-id="{{ $instructor->id }}"
                                                                data-action="{{ route('data-entry.instructors.update', $instructor->id) }}"
                                                                data-instructor='@json($instructor->load('user'))'
                                                                data-departments='@json($departments)'
                                                                data-roles='@json($instructorRoles)'>
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger open-delete-instructor-modal"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteInstructorModal"
                                                                data-action="{{ route('data-entry.instructors.destroy', $instructor->id) }}"
                                                                data-instructor-name="{{ $instructor->instructor_name ?? optional($instructor->user)->name }}">
                                                            <i class="fas fa-trash me-2"></i>Delete
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted small">
                                            <span class="text-muted">#{{ $instructors->firstItem() + $index }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($instructors->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $instructors->links('pagination::bootstrap-5') }}
                            </div>
                        @endif

                    @else
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-chalkboard-teacher text-muted opacity-50" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 text-muted">No Instructors Found</h5>
                                <p class="text-muted mb-4">Start by adding your first instructor to build your teaching staff.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInstructorModal">
                                    <i class="fas fa-plus me-2"></i>Add First Instructor
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals -->
    @include('dashboard.data-entry.partials._instructor_modals', [
        'instructor_for_edit' => null,
        'departments' => $departments,
        'instructorRoles' => $instructorRoles,
    ])
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
$(document).ready(function() {
    // Add Modal Reset
    $('#addInstructorModal').on('show.bs.modal', function() {
        $(this).find('form')[0].reset();
        $(this).find('.is-invalid').removeClass('is-invalid');
        $(this).find('.invalid-feedback').text('');
        $(this).find('.alert-danger.validation-errors-container').html('');
    });

    // Edit Modal Handler
    const editModal = $('#editInstructorModal');
    $(document).on('click', '.open-edit-instructor-modal', function() {
        const button = $(this);
        const instructorData = button.data('instructor');
        const departmentsData = button.data('departments');
        const rolesData = button.data('roles');

        editModal.find('form').attr('action', button.data('action'));
        editModal.find('.modal-title').text('Edit Instructor: ' + (instructorData.instructor_name || instructorData.user.name));

        // Fill user fields
        editModal.find('input[name="name"]').val(instructorData.user.name);
        editModal.find('input[name="email"]').val(instructorData.user.email);

        // Fill role select
        const roleSelectEdit = editModal.find('select[name="role_id_for_instructor"]');
        roleSelectEdit.empty().append($('<option>', {
            value: '',
            text: 'Select role...',
            disabled: true
        }));
        $.each(rolesData, function(key, role) {
            roleSelectEdit.append($('<option>', {
                value: role.id,
                text: role.display_name,
                selected: role.id == instructorData.user.role_id
            }));
        });

        // Fill instructor fields
        editModal.find('input[name="instructor_no"]').val(instructorData.instructor_no);
        editModal.find('input[name="academic_degree"]').val(instructorData.academic_degree || '');

        // Fill department select
        const deptSelectEdit = editModal.find('select[name="department_id"]');
        deptSelectEdit.empty().append($('<option>', {
            value: '',
            text: 'Select department...',
            disabled: true
        }));
        $.each(departmentsData, function(key, dept) {
            deptSelectEdit.append($('<option>', {
                value: dept.id,
                text: dept.department_name,
                selected: dept.id == instructorData.department_id
            }));
        });

        editModal.find('input[name="max_weekly_hours"]').val(instructorData.max_weekly_hours || '');
        editModal.find('textarea[name="availability_preferences"]').val(instructorData.availability_preferences || '');

        // Clear validation errors
        editModal.find('.is-invalid').removeClass('is-invalid');
        editModal.find('.invalid-feedback').text('');
        editModal.find('.alert-danger.validation-errors-container').html('');
    });

    // Delete Modal Handler
    const deleteModal = $('#deleteInstructorModal');
    $(document).on('click', '.open-delete-instructor-modal', function() {
        const button = $(this);
        deleteModal.find('form').attr('action', button.data('action'));
        deleteModal.find('#deleteInstructorName').text(button.data('instructor-name'));
    });

    // Reopen modals on validation errors
    @if ($errors->any())
        @if ($errors->hasBag('addInstructorModal'))
            $('#addInstructorModal').modal('show');
        @endif

        @php
            $editErrorBagName = null;
            $editingInstructorIdOnError = session('error_instructor_id_on_edit');
            if ($editingInstructorIdOnError) {
                $editErrorBagName = 'editInstructorModal_' . $editingInstructorIdOnError;
            }
        @endphp

        @if ($editErrorBagName && $errors->hasBag($editErrorBagName))
            $('#editInstructorModal').modal('show');
            $('#editInstructorModal').find('input[name="name"]').val("{{ old('name') }}");
            $('#editInstructorModal').find('input[name="email"]').val("{{ old('email') }}");
            $('#editInstructorModal').find('select[name="role_id_for_instructor"]').val("{{ old('role_id_for_instructor') }}");
            $('#editInstructorModal').find('input[name="instructor_no"]').val("{{ old('instructor_no') }}");
            $('#editInstructorModal').find('input[name="academic_degree"]').val("{{ old('academic_degree') }}");
            $('#editInstructorModal').find('select[name="department_id"]').val("{{ old('department_id') }}");
            $('#editInstructorModal').find('input[name="max_weekly_hours"]').val("{{ old('max_weekly_hours') }}");
            $('#editInstructorModal').find('textarea[name="availability_preferences"]').val("{{ old('availability_preferences') }}");
        @endif
    @endif
});
</script>
@endpush
