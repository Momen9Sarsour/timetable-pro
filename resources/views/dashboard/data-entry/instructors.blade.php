@extends('dashboard.layout')
{{-- ... (push styles if needed) ... --}}
@section('content')
    <div class="main-content">
        <div class="data-entry-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="data-entry-header mb-0">Manage Instructors</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInstructorModal">
                    <i class="fas fa-plus me-1"></i> Add New Instructor
                </button>
            </div>
            @include('dashboard.data-entry.partials._status_messages')
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Emp. No</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Degree</th>
                                    <th>Department</th>
                                    <th>Max Hours</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($instructors as $index => $instructor)
                                    <tr>
                                        <td>{{ $instructors->firstItem() + $index }}</td>
                                        <td>{{ $instructor->instructor_no }}</td>
                                        <td>{{ $instructor->instructor_name ?? optional($instructor->user)->name }}</td>
                                        <td>{{ optional($instructor->user)->email ?? 'N/A' }}</td>
                                        <td>{{ $instructor->academic_degree ?? '-' }}</td>
                                        <td>{{ optional($instructor->department)->department_name ?? 'N/A' }}</td>
                                        <td>{{ $instructor->max_weekly_hours ?? '-' }}</td>
                                        <td>
                                            <button
                                                class="btn btn-sm btn-outline-primary py-0 px-1 me-1 open-edit-instructor-modal"
                                                data-bs-toggle="modal" data-bs-target="#editInstructorModal"
                                                data-instructor-id="{{ $instructor->id }}"
                                                data-action="{{ route('data-entry.instructors.update', $instructor->id) }}"
                                                data-instructor='@json($instructor->load('user'))' {{-- تمرير بيانات المدرس واليوزر كـ JSON --}}
                                                data-departments='@json($departments)'
                                                data-roles='@json($instructorRoles)'>
                                                Edit
                                            </button>
                                            <button
                                                class="btn btn-sm btn-outline-danger py-0 px-1 open-delete-instructor-modal"
                                                data-bs-toggle="modal" data-bs-target="#deleteInstructorModal"
                                                data-action="{{ route('data-entry.instructors.destroy', $instructor->id) }}"
                                                data-instructor-name="{{ $instructor->instructor_name ?? optional($instructor->user)->name }}">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No instructors found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-center"> {{ $instructors->links() }} </div>
                </div>
            </div>

            {{-- تضمين ملف المودالات --}}
            @include('dashboard.data-entry.partials._instructor_modals', [
                'instructor_for_edit' => null, // لا نحتاجه للمودال العام
                'departments' => $departments, // تمرير الأقسام لمودال الإضافة/التعديل
                'instructorRoles' => $instructorRoles, // تمرير الأدوار لمودال الإضافة/التعديل
            ])
        </div>
    </div>
@endsection

@push('scripts')
    {{-- JavaScript لتعبئة مودال التعديل وإعادة الفتح عند الخطأ --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // --- Add Modal ---
            $('#addInstructorModal').on('show.bs.modal', function() {
                // مسح الأخطاء والقيم القديمة
                $(this).find('form')[0].reset(); // إعادة تعيين الفورم
                $(this).find('.is-invalid').removeClass('is-invalid');
                $(this).find('.invalid-feedback').text('');
                $(this).find('.alert-danger.validation-errors-container').html('');
            });

            // --- Edit Modal ---
            const editModal = $('#editInstructorModal');
            $(document).on('click', '.open-edit-instructor-modal', function() {
                const button = $(this);
                const instructorData = button.data('instructor'); // بيانات المدرس واليوزر
                const departmentsData = button.data('departments');
                const rolesData = button.data('roles');

                editModal.find('form').attr('action', button.data('action'));
                editModal.find('.modal-title').text('Edit Instructor: ' + (instructorData.instructor_name ||
                    instructorData.user.name));

                // تعبئة حقول اليوزر
                editModal.find('input[name="name"]').val(instructorData.user.name);
                editModal.find('input[name="email"]').val(instructorData.user.email);
                // Role (تحديد الخيار الصحيح)
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


                // تعبئة حقول المدرس
                editModal.find('input[name="instructor_no"]').val(instructorData.instructor_no);
                editModal.find('input[name="academic_degree"]').val(instructorData.academic_degree || '');
                // Department (تحديد الخيار الصحيح)
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
                editModal.find('input[name="office_location"]').val(instructorData.office_location || '');
                editModal.find('input[name="office_hours"]').val(instructorData.office_hours || '');
                editModal.find('textarea[name="availability_preferences"]').val(instructorData
                    .availability_preferences || '');

                editModal.find('.is-invalid').removeClass('is-invalid');
                editModal.find('.invalid-feedback').text('');
                editModal.find('.alert-danger.validation-errors-container').html('');
                editModal.modal('show');
            });

            // --- Delete Modal ---
            const deleteModal = $('#deleteInstructorModal');
            $(document).on('click', '.open-delete-instructor-modal', function() {
                const button = $(this);
                deleteModal.find('form').attr('action', button.data('action'));
                deleteModal.find('#deleteInstructorName').text(button.data('instructor-name'));
                deleteModal.modal('show');
            });

            // --- لإعادة فتح المودال عند وجود أخطاء validation ---
            @if ($errors->any())
                @if ($errors->hasBag('addInstructorModal'))
                    $('#addInstructorModal').modal('show');
                @endif
                @php
                    $editErrorBagName = null;
                    $editingInstructorIdOnError = session('error_instructor_id_on_edit'); // ID من الكنترولر
                    if ($editingInstructorIdOnError) {
                        $editErrorBagName = 'editInstructorModal_' . $editingInstructorIdOnError;
                    }
                @endphp
                @if ($editErrorBagName && $errors->hasBag($editErrorBagName))
                    console.warn(
                        "Reopening edit modal for instructor due to validation errors. Bag: {{ $editErrorBagName }}"
                        );
                    // هنا نحتاج لإعادة فتح المودال مع البيانات القديمة.
                    // الـ JavaScript الحالي سيعبئ بالبيانات من الزر، ليس بالـ old()
                    // هذا يتطلب إما:
                    // 1. أن يقوم الـ Blade في المودال بعرض old() إذا كان الخطأ من هذا المودال.
                    // 2. أو أن الـ JS يجلب بيانات الـ old() ويعبئها.
                    // للتبسيط، سنفتح المودال فقط، والمستخدم قد يحتاج لإعادة إدخال بعض البيانات.
                    $('#editInstructorModal').modal('show');
                    // ملء حقول مودال التعديل بالـ old() input
                    $('#editInstructorModal').find('input[name="name"]').val("{{ old('name') }}");
                    $('#editInstructorModal').find('input[name="email"]').val("{{ old('email') }}");
                    $('#editInstructorModal').find('select[name="role_id_for_instructor"]').val(
                        "{{ old('role_id_for_instructor') }}");
                    $('#editInstructorModal').find('input[name="instructor_no"]').val(
                    "{{ old('instructor_no') }}");
                    // ... أكمل باقي الحقول
                @endif
            @endif
        });
    </script>
@endpush
