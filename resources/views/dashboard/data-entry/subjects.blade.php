@extends('dashboard.layout')

@section('content')
    <div class="main-content">
        <div class="data-entry-container">

            {{-- داخل resources/views/dashboard/data-entry/subjects.blade.php --}}
            {{-- ضع هذا الكود بعد @include('dashboard.partials._status_messages') --}}

            @if (session('import_errors'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h5 class="alert-heading">Import Validation Errors!</h5>
                    <p>The following errors were found in the uploaded file. Please correct them and try again.</p>
                    <ul class="mb-0 small">
                        @foreach (session('import_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="data-entry-header mb-0">Manage Subjects</h1>
                <div>
                    {{-- // زر لإظهار Modal الرفع بالجملة --}}
                    <button class="btn btn-outline-success me-2" data-bs-toggle="modal"
                        data-bs-target="#bulkUploadSubjectModal">
                        <i class="fas fa-file-excel me-1"></i> Bulk Upload
                    </button>
                    {{-- // زر لإظهار Modal الإضافة الفردية --}}
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="fas fa-plus me-1"></i> Add New Subject
                    </button>
                </div>
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
                                    <th scope="col">Code</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Load</th>
                                    <th scope="col">Theo (h)</th> {{-- // ساعات نظري --}}
                                    <th scope="col">Prac (h)</th> {{-- // ساعات عملي --}}
                                    <th scope="col">Type</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Department</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($subjects as $index => $subject)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $subject->subject_no }}</td>
                                        <td>{{ $subject->subject_name }}</td>
                                        <td>{{ $subject->subject_load }}</td>
                                        <td>{{ $subject->theoretical_hours }}</td>
                                        <td>{{ $subject->practical_hours }}</td>
                                        {{-- // عرض البيانات من العلاقات --}}
                                        <td>{{ $subject->subjectType->subject_type_name ?? 'N/A' }}</td>
                                        <td>{{ $subject->subjectCategory->subject_category_name ?? 'N/A' }}</td>
                                        <td>{{ $subject->department->department_name ?? 'N/A' }}</td>
                                        <td>
                                            {{-- // زر التعديل --}}
                                            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal"
                                                data-bs-target="#editSubjectModal-{{ $subject->id }}" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            {{-- // زر الحذف --}}
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                                data-bs-target="#deleteSubjectModal-{{ $subject->id }}" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                            {{-- // تضمين Modals --}}
                                            @include('dashboard.data-entry.partials._subject_modals', [
                                                'subject' => $subject,
                                                'subjectTypes' => $subjectTypes,
                                                'subjectCategories' => $subjectCategories,
                                                'departments' => $departments,
                                            ])
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">No subjects found. Click 'Add New
                                            Subject' or 'Bulk Upload' to create some.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{-- // Pagination إذا لزم الأمر --}}
                    <div class="mt-3 d-flex justify-content-center">
                        {{-- // {{ $subjects->links() }} --}}
                        {{ $subjects->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>

            {{-- // Modal لإضافة مادة جديدة --}}
            @include('dashboard.data-entry.partials._subject_modals', [
                'subject' => null,
                'subjectTypes' => $subjectTypes,
                'subjectCategories' => $subjectCategories,
                'departments' => $departments,
            ])

            {{-- // Modal للرفع بالجملة --}}
            @include('dashboard.data-entry.partials._subject_bulk_upload_modal')

        </div>
    </div>
@endsection

@push('scripts')
    {{-- // JavaScript للتحكم بمنطقة الرفع بالجملة (Drag and Drop) --}}
    <script>
        // Add JS code here later to handle drag & drop for bulk upload if needed
        // Or just use a simple file input inside the modal
    </script>
@endpush
