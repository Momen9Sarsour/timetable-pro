@extends('dashboard.layout')

@section('content')
    <div class="main-content">
        <div class="data-entry-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="data-entry-header mb-0">Manage Classrooms</h1>
                <div class="d-flex">
                    {{-- // زر لإظهار Modal الإضافة --}}
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                        <i class="fas fa-plus me-1"></i> Add New Classroom
                    </button>
                    {{-- *** زر الرفع بالأكسل *** --}}
                    <button class="btn btn-outline-success me-2" data-bs-toggle="modal"
                        data-bs-target="#bulkUploadRoomsModal">
                        <i class="fas fa-file-excel me-1"></i> Bulk Upload Classrooms
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
                                    <th scope="col">Room No</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Capacity</th>
                                    <th scope="col">Gender</th>
                                    <th scope="col">Branch</th>
                                    {{-- <th scope="col">Equipment</th> --}}
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rooms as $index => $room)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $room->room_no }}</td>
                                        <td>{{ $room->room_name ?? '-' }}</td> {{-- // عرض الاسم أو شرطة إذا كان فارغاً --}}
                                        <td>{{ $room->roomType->room_type_name ?? 'N/A' }}</td> {{-- // عرض اسم النوع من العلاقة --}}
                                        <td>{{ $room->room_size }}</td>
                                        <td>{{ $room->room_gender }}</td>
                                        <td>{{ $room->room_branch ?? '-' }}</td>
                                        {{-- <td> --}}
                                        {{-- // عرض المعدات (حقل JSON) --}}
                                        {{-- @if ($room->equipment && is_array($room->equipment))
                                        @foreach ($room->equipment as $item)
                                            <span class="badge bg-secondary me-1">{{ $item }}</span>
                                        @endforeach
                                    @else
                                        -
                                    @endif
                                </td> --}}
                                        <td>
                                            {{-- // زر التعديل --}}
                                            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal"
                                                data-bs-target="#editRoomModal-{{ $room->id }}" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            {{-- // زر الحذف --}}
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                                data-bs-target="#deleteRoomModal-{{ $room->id }}" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                            {{-- // تضمين Modals التعديل والحذف الخاصة بهذه القاعة --}}
                                            @include('dashboard.data-entry.partials._room_modals', [
                                                'room' => $room,
                                                'roomTypes' => $roomTypes,
                                            ]) {{-- // نمرر أنواع القاعات أيضاً --}}

                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">No classrooms found. Click 'Add
                                            New Classroom' to create one.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-center">
                        {{-- // Pagination إذا لزم الأمر --}}
                        {{ $rooms->links('pagination::bootstrap-5') }}
                        {{-- {{ $rooms->links() }} --}}
                    </div>
                </div>
            </div>

            {{-- // Modal لإضافة قاعة جديدة --}}
            @include('dashboard.data-entry.partials._room_modals', [
                'room' => null,
                'roomTypes' => $roomTypes,
            ])

        </div>
    </div>
@endsection

@push('scripts')
    {{-- // يمكن إضافة JavaScript خاص بهذه الصفحة هنا --}}
@endpush
