@extends('dashboard.layout')

@section('content')
    <div class="main-content">
        <div class="data-entry-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="data-entry-header mb-0">Manage Departments</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                    <i class="fas fa-plus me-1"></i> Add New Department
                </button>
            </div>

            {{-- // رسائل النجاح أو الخطأ --}}
            {{-- @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                 Please check the form below for errors.
            </div>
        @endif --}}
            @include('dashboard.data-entry.partials._status_messages')


            {{-- // جدول لعرض الأقسام --}}
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Department No</th>
                            <th>Department Name</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($departments as $index => $department)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $department->department_no }}</td>
                                <td>{{ $department->department_name }}</td>
                                <td>{{ $department->created_at->format('Y-m-d H:i') }}</td>
                                <td>

                                    <button class="btn btn-sm btn-info me-1" data-bs-toggle="modal"
                                        data-bs-target="#editDepartmentModal-{{ $department->id }}">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                        data-bs-target="#deleteDepartmentModal-{{ $department->id }}">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>

                                    {{-- // التعديل والحذف هنا لكل صف --}}
                                    @include('dashboard.data-entry.partials.departments_modals', [
                                        'department' => $department,
                                    ])

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No departments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-center">
                {{-- // Pagination إذا لزم الأمر --}}
                {{ $departments->links('pagination::bootstrap-5') }}
                {{-- {{ $rooms->links() }} --}}
            </div>
            {{-- // لإضافة قسم جديد --}}
            @include('dashboard.data-entry.partials.departments_modals', ['department' => null])

        </div>
    </div>
@endsection

@push('scripts')
    {{-- // يمكن إضافة JavaScript خاص بهذه الصفحة هنا إذا لزم الأمر --}}
    <script>
        // Example: Handle modal events if needed
    </script>
@endpush
