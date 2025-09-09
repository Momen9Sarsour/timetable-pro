{{-- Add Room Modal --}}
@if (!$room)
<div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="addRoomModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>
                    Add New Classroom
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.rooms.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-3">
                        <!-- Basic Information Section -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-info-circle me-1"></i>Basic Information
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="add_room_no" class="form-label fw-medium">
                                <i class="fas fa-hashtag text-muted me-1"></i>
                                Room Number/Code <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('room_no') is-invalid @enderror"
                                   id="add_room_no"
                                   name="room_no"
                                   value="{{ old('room_no') }}"
                                   placeholder="e.g., A101, LAB-01"
                                   required>
                            @error('room_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Enter a unique identifier for this room</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="add_room_name" class="form-label fw-medium">
                                <i class="fas fa-tag text-muted me-1"></i>
                                Room Name <span class="text-muted">(Optional)</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('room_name') is-invalid @enderror"
                                   id="add_room_name"
                                   name="room_name"
                                   value="{{ old('room_name') }}"
                                   placeholder="e.g., Main Auditorium, Physics Lab">
                            @error('room_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Room Specifications Section -->
                        <div class="col-12 mt-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-cogs me-1"></i>Room Specifications
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="add_room_type_id" class="form-label fw-medium">
                                <i class="fas fa-door-closed text-muted me-1"></i>
                                Room Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('room_type_id') is-invalid @enderror"
                                    id="add_room_type_id"
                                    name="room_type_id"
                                    required>
                                <option value="" selected disabled>Choose room type...</option>
                                @foreach ($roomTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('room_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->room_type_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('room_type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Select the type that best describes this room</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="add_room_size" class="form-label fw-medium">
                                <i class="fas fa-users text-muted me-1"></i>
                                Capacity <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('room_size') is-invalid @enderror"
                                   id="add_room_size"
                                   name="room_size"
                                   value="{{ old('room_size') }}"
                                   min="1"
                                   max="1000"
                                   placeholder="e.g., 30"
                                   required>
                            @error('room_size')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Maximum number of students this room can accommodate</div>
                            @enderror
                        </div>

                        <!-- Access & Location Section -->
                        <div class="col-12 mt-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-map-marker-alt me-1"></i>Access & Location
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="add_room_gender" class="form-label fw-medium">
                                <i class="fas fa-venus-mars text-muted me-1"></i>
                                Gender Allocation <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('room_gender') is-invalid @enderror"
                                    id="add_room_gender"
                                    name="room_gender"
                                    required>
                                <option value="Mixed" {{ old('room_gender', 'Mixed') == 'Mixed' ? 'selected' : '' }}>
                                    <i class="fas fa-venus-mars"></i> Mixed (Default)
                                </option>
                                <option value="Male" {{ old('room_gender') == 'Male' ? 'selected' : '' }}>
                                    <i class="fas fa-mars"></i> Male Only
                                </option>
                                <option value="Female" {{ old('room_gender') == 'Female' ? 'selected' : '' }}>
                                    <i class="fas fa-venus"></i> Female Only
                                </option>
                            </select>
                            @error('room_gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Specify if this room has gender restrictions</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="add_room_branch" class="form-label fw-medium">
                                <i class="fas fa-building text-muted me-1"></i>
                                Branch/Building <span class="text-muted">(Optional)</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('room_branch') is-invalid @enderror"
                                   id="add_room_branch"
                                   name="room_branch"
                                   value="{{ old('room_branch') }}"
                                   placeholder="e.g., Main Building, Block A">
                            @error('room_branch')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Specify the building or branch location</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Create Classroom
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Edit Room Modal --}}
@if ($room)
<div class="modal fade" id="editRoomModal-{{ $room->id }}" tabindex="-1" aria-labelledby="editRoomModalLabel-{{ $room->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title d-flex align-items-center" id="editRoomModalLabel-{{ $room->id }}">
                    <i class="fas fa-edit me-2"></i>
                    Edit Classroom
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.rooms.update', $room->id) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <!-- Room Info Alert -->
                    <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            <small>Editing: <strong>{{ $room->room_no }}</strong></small>
                            @if($room->room_name)
                                <small class="d-block text-muted">{{ $room->room_name }}</small>
                            @endif
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Basic Information Section -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-info-circle me-1"></i>Basic Information
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_room_no_{{ $room->id }}" class="form-label fw-medium">
                                <i class="fas fa-hashtag text-muted me-1"></i>
                                Room Number/Code <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('room_no', 'update_'.$room->id) is-invalid @enderror"
                                   id="edit_room_no_{{ $room->id }}"
                                   name="room_no"
                                   value="{{ old('room_no', $room->room_no) }}"
                                   required>
                            @error('room_no', 'update_'.$room->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="edit_room_name_{{ $room->id }}" class="form-label fw-medium">
                                <i class="fas fa-tag text-muted me-1"></i>
                                Room Name <span class="text-muted">(Optional)</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('room_name', 'update_'.$room->id) is-invalid @enderror"
                                   id="edit_room_name_{{ $room->id }}"
                                   name="room_name"
                                   value="{{ old('room_name', $room->room_name) }}">
                            @error('room_name', 'update_'.$room->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Room Specifications Section -->
                        <div class="col-12 mt-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-cogs me-1"></i>Room Specifications
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_room_type_id" class="form-label fw-medium">
                                <i class="fas fa-door-closed text-muted me-1"></i>
                                Room Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('room_type_id') is-invalid @enderror"
                                    id="edit_room_type_id"
                                    name="room_type_id"
                                    required>
                                <option value="" disabled>Choose room type...</option>
                                @foreach ($roomTypes as $type)
                                    <option value="{{ $type->id }}"
                                        {{ (old('room_type_id', $room->room_type_id) == $type->id) ? 'selected' : '' }}>
                                        {{ $type->room_type_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('room_type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Select the type that best describes this room</div>
                            @enderror
                        </div>


                        <div class="col-md-6">
                            <label for="edit_room_size_{{ $room->id }}" class="form-label fw-medium">
                                <i class="fas fa-users text-muted me-1"></i>
                                Capacity <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('room_size', 'update_'.$room->id) is-invalid @enderror"
                                   id="edit_room_size_{{ $room->id }}"
                                   name="room_size"
                                   value="{{ old('room_size', $room->room_size) }}"
                                   min="1"
                                   max="1000"
                                   required>
                            @error('room_size', 'update_'.$room->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Access & Location Section -->
                        <div class="col-12 mt-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-map-marker-alt me-1"></i>Access & Location
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_room_gender_{{ $room->id }}" class="form-label fw-medium">
                                <i class="fas fa-venus-mars text-muted me-1"></i>
                                Gender Allocation <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('room_gender', 'update_'.$room->id) is-invalid @enderror"
                                    id="edit_room_gender_{{ $room->id }}"
                                    name="room_gender"
                                    required>
                                <option value="Mixed" {{ old('room_gender', $room->room_gender) == 'Mixed' ? 'selected' : '' }}>Mixed</option>
                                <option value="Male" {{ old('room_gender', $room->room_gender) == 'Male' ? 'selected' : '' }}>Male Only</option>
                                <option value="Female" {{ old('room_gender', $room->room_gender) == 'Female' ? 'selected' : '' }}>Female Only</option>
                            </select>
                            @error('room_gender', 'update_'.$room->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="edit_room_branch_{{ $room->id }}" class="form-label fw-medium">
                                <i class="fas fa-building text-muted me-1"></i>
                                Branch/Building <span class="text-muted">(Optional)</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('room_branch', 'update_'.$room->id) is-invalid @enderror"
                                   id="edit_room_branch_{{ $room->id }}"
                                   name="room_branch"
                                   value="{{ old('room_branch', $room->room_branch) }}">
                            @error('room_branch', 'update_'.$room->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-sync-alt me-1"></i>Update Classroom
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteRoomModal-{{ $room->id }}" tabindex="-1" aria-labelledby="deleteRoomModalLabel-{{ $room->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="deleteRoomModalLabel-{{ $room->id }}">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.rooms.destroy', $room->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-door-open text-danger" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>

                    <div class="alert alert-danger d-flex align-items-start" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Are you sure?</h6>
                            <p class="mb-0 small">You are about to delete:</p>
                        </div>
                    </div>

                    <div class="card bg-light border-danger">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="room-icon bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="fas fa-door-open"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">{{ $room->room_no }}</h6>
                                    @if($room->room_name)
                                        <small class="text-muted d-block">{{ $room->room_name }}</small>
                                    @endif
                                    <div class="d-flex gap-2 mt-1">
                                        <span class="badge bg-secondary bg-opacity-20 text-secondary">
                                            {{ $room->roomType->room_type_name ?? 'N/A' }}
                                        </span>
                                        <span class="badge bg-info bg-opacity-20 text-info">
                                            {{ $room->room_size }} capacity
                                        </span>
                                    </div>
                                </div>
                                <span class="badge bg-danger">Will be deleted</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <small><strong>Warning:</strong> This action cannot be undone. Any scheduled classes in this room might be affected.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Yes, Delete Classroom
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Bulk Upload Modal --}}
<div class="modal fade" id="bulkUploadRoomsModal" tabindex="-1" aria-labelledby="bulkUploadRoomsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="bulkUploadRoomsModalLabel">
                    <i class="fas fa-file-excel me-2"></i>
                    Bulk Upload Classrooms
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.rooms.bulkUpload') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-4">
                        <!-- File Upload Section -->
                        <div class="col-12">
                            <label for="room_excel_file" class="form-label fw-medium">
                                <i class="fas fa-cloud-upload-alt text-muted me-1"></i>
                                Select Excel File <span class="text-danger">*</span>
                            </label>
                            <div class="upload-zone border border-2 border-dashed rounded-3 p-4 text-center cursor-pointer">
                                <i class="fas fa-file-excel text-success mb-2" style="font-size: 2rem;"></i>
                                <input class="form-control @error('room_excel_file', 'bulkUploadRooms') is-invalid @enderror"
                                       type="file"
                                       id="room_excel_file"
                                       name="room_excel_file"
                                       accept=".xlsx,.xls,.csv"
                                       required>
                                @error('room_excel_file', 'bulkUploadRooms')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text mt-2">
                                    <small>Supported formats: .xlsx, .xls, .csv (Max: 5MB)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Instructions Section -->
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-header bg-transparent border-0 pb-0">
                                    <h6 class="card-title text-primary mb-0">
                                        <i class="fas fa-info-circle me-1"></i>
                                        File Format Instructions
                                    </h6>
                                </div>
                                <div class="card-body pt-2">
                                    <ul class="list-unstyled mb-0 small">
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                            <span>First row should be headers: <code>room_no</code>, <code>room_name</code>, <code>room_size</code>, <code>room_gender</code>, <code>room_branch</code>, <code>room_type_id</code></span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                            <span>Required columns: <code>room_no</code>, <code>room_size</code>, <code>room_gender</code>, <code>room_type_id</code></span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-sync-alt text-info me-2 mt-1 flex-shrink-0"></i>
                                            <span>If room_no exists, data will be updated; otherwise, new room is created</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-exclamation-triangle text-warning me-2 mt-1 flex-shrink-0"></i>
                                            <span>Ensure room_type_id corresponds to existing room types</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <i class="fas fa-filter text-warning me-2 mt-1 flex-shrink-0"></i>
                                            <span>Empty rows or rows with missing room_no will be skipped</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Gender Options -->
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-header bg-info bg-opacity-10 border-info">
                                    <h6 class="card-title text-info mb-0">
                                        <i class="fas fa-venus-mars me-1"></i>
                                        Valid Gender Values
                                    </h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-md-4 text-center">
                                            <span class="badge bg-success bg-opacity-10 text-success p-2">
                                                <i class="fas fa-venus-mars me-1"></i>Mixed
                                            </span>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <span class="badge bg-info bg-opacity-10 text-info p-2">
                                                <i class="fas fa-mars me-1"></i>Male
                                            </span>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <span class="badge bg-danger bg-opacity-10 text-danger p-2">
                                                <i class="fas fa-venus me-1"></i>Female
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sample Format -->
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-header bg-primary bg-opacity-10 border-primary">
                                    <h6 class="card-title text-primary mb-0">
                                        <i class="fas fa-table me-1"></i>
                                        Sample Format
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead class="table-primary">
                                                <tr style="font-size: 0.75rem;">
                                                    <th>room_no</th>
                                                    <th>room_name</th>
                                                    <th>room_size</th>
                                                    <th>room_gender</th>
                                                    <th>room_branch</th>
                                                    <th>room_type_id</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><code class="small">A101</code></td>
                                                    <td class="small">Main Classroom</td>
                                                    <td class="small">30</td>
                                                    <td class="small">Mixed</td>
                                                    <td class="small">Main Building</td>
                                                    <td class="small">1</td>
                                                </tr>
                                                <tr>
                                                    <td><code class="small">LAB-01</code></td>
                                                    <td class="small">Physics Lab</td>
                                                    <td class="small">25</td>
                                                    <td class="small">Mixed</td>
                                                    <td class="small">Science Block</td>
                                                    <td class="small">2</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i>Upload and Process
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Room-specific styling */
.room-icon {
    transition: all 0.15s ease;
}

.room-icon:hover {
    transform: scale(1.05);
}

/* Upload Zone Styles */
.upload-zone {
    border-color: #dee2e6 !important;
    transition: all 0.15s ease;
    cursor: pointer;
}

.upload-zone:hover {
    border-color: var(--primary-color) !important;
    background-color: rgba(59, 130, 246, 0.05);
}

.upload-zone input[type="file"] {
    border: none;
    background: transparent;
    padding: 0.5rem 0;
}

/* Gender badge styling */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
}

/* Modal responsive design */
@media (max-width: 768px) {
    .modal-lg {
        max-width: 95%;
    }

    .modal-body {
        max-height: 60vh !important;
        padding: 1rem !important;
    }

    .upload-zone {
        padding: 2rem 1rem !important;
    }

    .upload-zone i {
        font-size: 1.5rem !important;
    }

    .table-responsive {
        font-size: 0.875rem;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
    }
}

/* Form sections */
.modal-body h6.border-bottom {
    margin-bottom: 1rem !important;
    padding-bottom: 0.5rem !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload enhancement for bulk upload
    const fileInput = document.getElementById('room_excel_file');
    const uploadZone = document.querySelector('.upload-zone');

    if (fileInput && uploadZone) {
        // Click to open file dialog
        uploadZone.addEventListener('click', function() {
            fileInput.click();
        });

        // File change handler
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const icon = uploadZone.querySelector('i');
                const text = uploadZone.querySelector('.form-text');

                if (icon) {
                    icon.className = 'fas fa-file-check text-success mb-2';
                    icon.style.fontSize = '2rem';
                }

                if (text) {
                    text.innerHTML = `<small class="text-success fw-medium">✓ Selected: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</small>`;
                }
            }
        });

        // Drag & Drop functionality
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#3b82f6';
            this.style.backgroundColor = 'rgba(59, 130, 246, 0.05)';
        });

        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.backgroundColor = '';
        });

        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.backgroundColor = '';

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                const event = new Event('change', { bubbles: true });
                fileInput.dispatchEvent(event);
            }
        });
    }

    // Auto-format room numbers and validation
    const roomNoInputs = document.querySelectorAll('input[name="room_no"]');
    roomNoInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Convert to uppercase and clean format
            this.value = this.value.toUpperCase().replace(/\s+/g, '');
        });
    });

    // Capacity validation with visual feedback
    const capacityInputs = document.querySelectorAll('input[name="room_size"]');
    capacityInputs.forEach(input => {
        input.addEventListener('input', function() {
            const value = parseInt(this.value);
            const feedback = this.parentNode.querySelector('.capacity-feedback') ||
                           document.createElement('div');

            if (value < 1) {
                this.setCustomValidity('Capacity must be at least 1');
                feedback.className = 'capacity-feedback form-text text-danger';
                feedback.textContent = 'Minimum capacity is 1 student';
            } else if (value > 1000) {
                this.setCustomValidity('Capacity seems unusually large');
                feedback.className = 'capacity-feedback form-text text-warning';
                feedback.textContent = 'Large capacity - please verify this is correct';
            } else {
                this.setCustomValidity('');
                feedback.className = 'capacity-feedback form-text text-success';
                feedback.textContent = `Can accommodate ${value} students`;
            }

            if (!this.parentNode.querySelector('.capacity-feedback')) {
                this.parentNode.appendChild(feedback);
            }
        });
    });

    // Form validation enhancement
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();

                // Focus on first invalid field
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
            form.classList.add('was-validated');
        });
    });
});
</script>
