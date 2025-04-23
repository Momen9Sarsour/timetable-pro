@extends('dashboard.layout')

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h1 class="data-entry-header mb-0">Manage Instructors</h1>
             {{-- // زر لإظهار Modal الإضافة --}}
             <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInstructorModal">
                 <i class="fas fa-plus me-1"></i> Add New Instructor
             </button>
        </div>

        {{-- // عرض رسائل الحالة --}}
        @include('dashboard.data-entry.partials._status_messages')

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Instructor No</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Degree</th>
                                <th scope="col">Department</th>
                                <th scope="col">Max Hours</th>
                                {{-- <th scope="col">Office</th> --}}
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($instructors as $index => $instructor)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $instructor->instructor_no }}</td>
                                {{-- // عرض اسم المدرس من سجل المدرس أو من اليوزر كاحتياط --}}
                                <td>{{ $instructor->instructor_name ?? ($instructor->user->name ?? 'N/A') }}</td>
                                {{-- // عرض الإيميل من اليوزر المرتبط --}}
                                <td>{{ $instructor->user->email ?? 'N/A' }}</td>
                                <td>{{ $instructor->academic_degree ?? '-' }}</td>
                                {{-- // عرض اسم القسم من العلاقة --}}
                                <td>{{ $instructor->department->department_name ?? 'N/A' }}</td>
                                <td>{{ $instructor->max_weekly_hours ?? '-' }}</td>
                                {{-- <td>{{ $instructor->office_location ?? '-' }}</td> --}}
                                <td>
                                    {{-- // زر التعديل --}}
                                    <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editInstructorModal-{{ $instructor->id }}" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    {{-- // زر الحذف --}}
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteInstructorModal-{{ $instructor->id }}" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                    {{-- // تضمين Modals --}}
                                    {{-- // نمرر المدرس الحالي، المستخدمين المتاحين (لنموذج الإضافة)، والأقسام --}}
                                    @include('dashboard.data-entry.partials._instructor_modals', [
                                        'instructor' => $instructor,
                                        'availableUsers' => $availableUsers,
                                        'departments' => $departments
                                    ])

                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No instructors found. Click 'Add New Instructor' to create one.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                 {{-- // Pagination إذا لزم الأمر --}}
                 <div class="mt-3 d-flex justify-content-center">
                    {{-- // Pagination إذا لزم الأمر --}}
                  {{ $instructors->links('pagination::bootstrap-5') }}
                  {{-- {{ $rooms->links() }} --}}
                </div>
                 {{-- // {{ $instructors->links() }} --}}
            </div>
        </div>

         {{-- // Modal لإضافة مدرس جديد --}}
         @include('dashboard.data-entry.partials._instructor_modals', [
             'instructor' => null,
             'availableUsers' => $availableUsers,
             'departments' => $departments
         ])

    </div>
</div>
@endsection

@push('scripts')
{{-- // يمكن إضافة JavaScript خاص بهذه الصفحة هنا --}}
@endpush
