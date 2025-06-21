@extends('dashboard.layout')

@section('content')
    <div class="main-content">
        <div class="data-entry-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                {{-- // تغيير العنوان --}}
                <h1 class="data-entry-header mb-0">Manage Subject Types</h1>
                <div class="d-flex">
                    {{-- // زر لإظهار Modal الإضافة --}}
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addSubjectTypeModal">
                        <i class="fas fa-plus me-1"></i> Add New Subject Type
                    </button>
                    {{-- *** زر الرفع بالأكسل *** --}}
                    <button class="btn btn-outline-success me-2" data-bs-toggle="modal"
                        data-bs-target="#bulkUploadSubjectTypesModal">
                        <i class="fas fa-file-excel me-1"></i> Bulk Upload Subject Types
                    </button>
                </div>
            </div>

            {{-- // عرض رسائل الحالة --}}
            @include('dashboard.data-entry.partials._status_messages')
            @if (session('skipped_details'))
                <div class="alert alert-warning mt-3">
                    <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Skipped Rows During Upload:</h5>
                    <ul class="mb-0 small" style="max-height: 200px; overflow-y: auto;">
                        @foreach (session('skipped_details') as $detail)
                            <li>{{ $detail }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Type Name</th> {{-- // تغيير العمود --}}
                                    <th scope="col">Subject Count</th> {{-- // عدد المواد بالنوع --}}
                                    <th scope="col">Created At</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- // تغيير اسم المتغير إلى $subjectTypes --}}
                                @forelse ($subjectTypes as $index => $type)
                                    <tr>
                                        {{-- // عرض رقم الصف بناءً على الـ pagination --}}
                                        <td>{{ $subjectTypes->firstItem() + $index }}</td>
                                        <td>{{ $type->subject_type_name }}</td>
                                        <td>{{ $type->subjects()->count() }}</td> {{-- // عرض عدد المواد --}}
                                        <td>{{ $type->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            {{-- // زر التعديل --}}
                                            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal"
                                                data-bs-target="#editSubjectTypeModal-{{ $type->id }}" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            {{-- // زر الحذف (معطل إذا كان عدد المواد > 0) --}}
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                                data-bs-target="#deleteSubjectTypeModal-{{ $type->id }}" title="Delete"
                                                {{ $type->subjects()->count() > 0 ? 'disabled' : '' }}>
                                                <i class="fas fa-trash"></i>
                                            </button>

                                            {{-- // تضمين Modals --}}
                                            {{-- // تغيير اسم الـ partial واسم المتغير --}}
                                            @include('dashboard.data-entry.partials._subject_type_modals', [
                                                'subjectType' => $type,
                                            ])

                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        {{-- // تعديل الرسالة --}}
                                        <td colspan="5" class="text-center text-muted">No subject types found. Click 'Add
                                            New Subject Type' to create one.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{-- // إضافة روابط الـ Pagination --}}
                    <div class="mt-3 d-flex justify-content-center">
                        {{-- // تغيير اسم المتغير --}}
                        {{ $subjectTypes->links() }}
                    </div>
                </div>
            </div>

            {{-- // Modal لإضافة نوع جديد --}}
            {{-- // تغيير اسم الـ partial واسم المتغير --}}
            @include('dashboard.data-entry.partials._subject_type_modals', ['subjectType' => null])

        </div>
    </div>
@endsection

@push('scripts')
    {{-- // يمكن إضافة JavaScript خاص بهذه الصفحة هنا --}}
@endpush
