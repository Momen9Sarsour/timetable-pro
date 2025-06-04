{{-- // ========================= --}}
{{-- // Modal لإضافة نوع قاعة جديد --}}
{{-- // ========================= --}}
@if (!$roomType)
    <div class="modal fade" id="addRoomTypeModal" tabindex="-1" aria-labelledby="addRoomTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRoomTypeModalLabel">Add New Room Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                {{-- // تغيير الـ route --}}
                <form action="{{ route('data-entry.room-types.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            {{-- // تغيير الحقول --}}
                            <label for="add_room_type_name" class="form-label">Room Type Name <span
                                    class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control @error('room_type_name', 'store') is-invalid @enderror"
                                id="add_room_type_name" name="room_type_name" value="{{ old('room_type_name') }}"
                                required>
                            @error('room_type_name', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Room Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- // ========================= --}}
{{-- // Modals التعديل والحذف --}}
{{-- // ========================= --}}
@if ($roomType)
    {{-- // Modal لتعديل نوع قاعة موجود --}}
    <div class="modal fade" id="editRoomTypeModal-{{ $roomType->id }}" tabindex="-1"
        aria-labelledby="editRoomTypeModalLabel-{{ $roomType->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRoomTypeModalLabel-{{ $roomType->id }}">Edit Room Type:
                        {{ $roomType->room_type_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                {{-- // تغيير الـ route والمتغير --}}
                <form action="{{ route('data-entry.room-types.update', $roomType->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            {{-- // تغيير الحقول والمتغيرات --}}
                            <label for="edit_room_type_name_{{ $roomType->id }}" class="form-label">Room Type Name <span
                                    class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control @error('room_type_name', 'update_' . $roomType->id) is-invalid @enderror"
                                id="edit_room_type_name_{{ $roomType->id }}" name="room_type_name"
                                value="{{ old('room_type_name', $roomType->room_type_name) }}" required>
                            @error('room_type_name', 'update_' . $roomType->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Room Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- // Modal لتأكيد الحذف --}}
    @if ($roomType->rooms()->count() == 0)
        <div class="modal fade" id="deleteRoomTypeModal-{{ $roomType->id }}" tabindex="-1"
            aria-labelledby="deleteRoomTypeModalLabel-{{ $roomType->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteRoomTypeModalLabel-{{ $roomType->id }}">Confirm Deletion</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    {{-- // تغيير الـ route والمتغير --}}
                    <form action="{{ route('data-entry.room-types.destroy', $roomType->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="modal-body">
                            {{-- // تغيير النص والمتغير --}}
                            <p>Are you sure you want to delete the room type:
                                <strong>{{ $roomType->room_type_name }}</strong>?
                            </p>
                            <p class="text-danger small">This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Yes, Delete Room Type</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endif


{{-- *** مودال الرفع بالأكسل (جديد) *** --}}
<div class="modal fade" id="bulkUploadRoomTypesModal" tabindex="-1" aria-labelledby="bulkUploadRoomTypesModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkUploadRoomTypesModalLabel">Bulk Upload Room Types from Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('data-entry.room-types.bulkUpload') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="room_type_excel_file" class="form-label">Select Excel File <span
                                class="text-danger">*</span></label>
                        <input
                            class="form-control @error('room_type_excel_file', 'bulkUploadRoomTypes') is-invalid @enderror"
                            type="file" id="room_type_excel_file" name="room_type_excel_file"
                            accept=".xlsx, .xls, .csv" required>
                        @error('room_type_excel_file', 'bulkUploadRoomTypes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="alert alert-info small p-2">
                        <p class="mb-1"><strong>File Format Instructions:</strong></p>
                        <ul class="mb-0 ps-3">
                            <li>The first row should be headers (e.g., room_type_id, room_type_name).</li>
                            <li>The system will primarily use the 'room_type_name' column.</li>
                            <li>If a room_type_name from the file already exists, the row will be skipped.</li>
                            <li>Empty rows will be skipped.</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i> Upload and Process
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
