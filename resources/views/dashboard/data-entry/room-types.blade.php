@extends('dashboard.layout')

@section('content')
    <div class="main-content">
        <div class="data-entry-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                {{-- // تغيير العنوان --}}
                <h1 class="data-entry-header mb-0">Manage Room Types</h1>
                <div class="d-flex">
                    {{-- // تغيير الـ target للـ Modal --}}
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addRoomTypeModal">
                        <i class="fas fa-plus me-1"></i> Add New Room Type
                    </button>
                    {{-- *** زر الرفع بالأكسل *** --}}
                    <button class="btn btn-outline-success me-2" data-bs-toggle="modal"
                        data-bs-target="#bulkUploadRoomTypesModal">
                        <i class="fas fa-file-excel me-2"></i> Bulk Upload Room Types
                    </button>
                </div>
            </div>

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
                                    <th scope="col">Room Count</th> {{-- // عدد القاعات بالنوع --}}
                                    <th scope="col">Created At</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- // تغيير اسم المتغير إلى $roomTypes --}}
                                @forelse ($roomTypes as $index => $type)
                                    <tr>
                                        {{-- // تغيير المتغير --}}
                                        <td>{{ $roomTypes->firstItem() + $index }}</td>
                                        <td>{{ $type->room_type_name }}</td>
                                        <td>{{ $type->rooms()->count() }}</td> {{-- // عرض عدد القاعات --}}
                                        <td>{{ $type->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            {{-- // تغيير الـ target والمتغير --}}
                                            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal"
                                                data-bs-target="#editRoomTypeModal-{{ $type->id }}" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            {{-- // تغيير الـ target والمتغير والشرط --}}
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                                data-bs-target="#deleteRoomTypeModal-{{ $type->id }}" title="Delete"
                                                {{ $type->rooms()->count() > 0 ? 'disabled' : '' }}>
                                                <i class="fas fa-trash"></i>
                                            </button>

                                            {{-- // تغيير اسم الـ partial والمتغير --}}
                                            @include('dashboard.data-entry.partials._room_type_modals', [
                                                'roomType' => $type,
                                            ])

                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        {{-- // تغيير الرسالة --}}
                                        <td colspan="5" class="text-center text-muted">No room types found. Click 'Add
                                            New Room Type' to create one.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-center">
                        {{-- // تغيير اسم المتغير --}}
                        {{ $roomTypes->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>

            {{-- // تغيير اسم الـ partial والمتغير --}}
            @include('dashboard.data-entry.partials._room_type_modals', ['roomType' => null])

        </div>
    </div>
@endsection

@push('scripts')
    {{-- JS خاص بالصفحة إذا لزم الأمر --}}
@endpush
