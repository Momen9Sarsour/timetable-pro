{{-- // Modal لإضافة قاعة جديدة --}}
@if (!$room)
<div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg"> {{-- // جعل المودال أكبر قليلاً --}}
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addRoomModalLabel">Add New Classroom</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('data-entry.rooms.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_room_no" class="form-label">Room Number/Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('room_no') is-invalid @enderror" id="add_room_no" name="room_no" value="{{ old('room_no') }}" required>
                            @error('room_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_room_name" class="form-label">Room Name (Optional)</label>
                            <input type="text" class="form-control @error('room_name') is-invalid @enderror" id="add_room_name" name="room_name" value="{{ old('room_name') }}">
                            @error('room_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_room_type_id" class="form-label">Room Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('room_type_id') is-invalid @enderror" id="add_room_type_id" name="room_type_id" required>
                                <option value="" selected disabled>Select type...</option>
                                @foreach ($roomTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('room_type_id') == $type->id ? 'selected' : '' }}>{{ $type->room_type_name }}</option>
                                @endforeach
                            </select>
                            @error('room_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="add_room_size" class="form-label">Capacity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('room_size') is-invalid @enderror" id="add_room_size" name="room_size" value="{{ old('room_size') }}" required min="1">
                            @error('room_size') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_room_gender" class="form-label">Gender Allocation <span class="text-danger">*</span></label>
                            <select class="form-select @error('room_gender') is-invalid @enderror" id="add_room_gender" name="room_gender" required>
                                <option value="Mixed" {{ old('room_gender', 'Mixed') == 'Mixed' ? 'selected' : '' }}>Mixed</option>
                                <option value="Male" {{ old('room_gender') == 'Male' ? 'selected' : '' }}>Male Only</option>
                                <option value="Female" {{ old('room_gender') == 'Female' ? 'selected' : '' }}>Female Only</option>
                            </select>
                            @error('room_gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="add_room_branch" class="form-label">Branch (Optional)</label>
                            <input type="text" class="form-control @error('room_branch') is-invalid @enderror" id="add_room_branch" name="room_branch" value="{{ old('room_branch') }}">
                            @error('room_branch') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- <div class="mb-3">
                         <label class="form-label">Available Equipment</label>
                         <div class="d-flex flex-wrap">
                             @php $allEquipment = ['projector', 'whiteboard', 'smart board', 'sound system', 'computers', 'lan network', 'lab tools']; @endphp
                             @foreach($allEquipment as $equip)
                                 <div class="form-check form-check-inline me-3 mb-2">
                                     <input class="form-check-input" type="checkbox" id="add_equip_{{ str_replace(' ', '_', $equip) }}" name="equipment[]" value="{{ $equip }}" {{ (is_array(old('equipment')) && in_array($equip, old('equipment'))) ? 'checked' : '' }}>
                                     <label class="form-check-label" for="add_equip_{{ str_replace(' ', '_', $equip) }}">{{ ucfirst($equip) }}</label>
                                 </div>
                             @endforeach
                         </div>
                          @error('equipment') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                          @error('equipment.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror {{-- // للتحقق من كل عنصر في المصفوفة --}}
                     {{-- </div> --}}

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Classroom</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- // Modal لتعديل قاعة موجودة --}}
@if ($room)
<div class="modal fade" id="editRoomModal-{{ $room->id }}" tabindex="-1" aria-labelledby="editRoomModalLabel-{{ $room->id }}" aria-hidden="true">
     <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRoomModalLabel-{{ $room->id }}">Edit Classroom: {{ $room->room_no }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form action="{{ route('data-entry.rooms.update', $room->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                     {{-- // نفس حقول نموذج الإضافة ولكن مع قيم الـ $room الحالية --}}
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_room_no_{{ $room->id }}" class="form-label">Room Number/Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('room_no', 'update_'.$room->id) is-invalid @enderror" id="edit_room_no_{{ $room->id }}" name="room_no" value="{{ old('room_no', $room->room_no) }}" required>
                            @error('room_no', 'update_'.$room->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_room_name_{{ $room->id }}" class="form-label">Room Name (Optional)</label>
                            <input type="text" class="form-control @error('room_name', 'update_'.$room->id) is-invalid @enderror" id="edit_room_name_{{ $room->id }}" name="room_name" value="{{ old('room_name', $room->room_name) }}">
                            @error('room_name', 'update_'.$room->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_room_type_id_{{ $room->id }}" class="form-label">Room Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('room_type_id', 'update_'.$room->id) is-invalid @enderror" id="edit_room_type_id_{{ $room->id }}" name="room_type_id" required>
                                <option value="" disabled>Select type...</option>
                                @foreach ($roomTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('room_type_id', $room->room_type_id) == $type->id ? 'selected' : '' }}>{{ $type->room_type_name }}</option>
                                @endforeach
                            </select>
                            @error('room_type_id', 'update_'.$room->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="edit_room_size_{{ $room->id }}" class="form-label">Capacity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('room_size', 'update_'.$room->id) is-invalid @enderror" id="edit_room_size_{{ $room->id }}" name="room_size" value="{{ old('room_size', $room->room_size) }}" required min="1">
                            @error('room_size', 'update_'.$room->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_room_gender_{{ $room->id }}" class="form-label">Gender Allocation <span class="text-danger">*</span></label>
                            <select class="form-select @error('room_gender', 'update_'.$room->id) is-invalid @enderror" id="edit_room_gender_{{ $room->id }}" name="room_gender" required>
                                <option value="Mixed" {{ old('room_gender', $room->room_gender) == 'Mixed' ? 'selected' : '' }}>Mixed</option>
                                <option value="Male" {{ old('room_gender', $room->room_gender) == 'Male' ? 'selected' : '' }}>Male Only</option>
                                <option value="Female" {{ old('room_gender', $room->room_gender) == 'Female' ? 'selected' : '' }}>Female Only</option>
                            </select>
                            @error('room_gender', 'update_'.$room->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="edit_room_branch_{{ $room->id }}" class="form-label">Branch (Optional)</label>
                            <input type="text" class="form-control @error('room_branch', 'update_'.$room->id) is-invalid @enderror" id="edit_room_branch_{{ $room->id }}" name="room_branch" value="{{ old('room_branch', $room->room_branch) }}">
                            @error('room_branch', 'update_'.$room->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                     {{-- <div class="mb-3">
                         <label class="form-label">Available Equipment</label>
                         <div class="d-flex flex-wrap">
                             @php
                                 $allEquipment = ['projector', 'whiteboard', 'smart board', 'sound system', 'computers', 'lan network', 'lab tools'];
                                 // جلب المعدات الحالية للقاعة (تأكد أنها مصفوفة)
                                 $currentEquipment = is_array($room->equipment) ? $room->equipment : [];
                                 // جلب المعدات القديمة إذا كان هناك خطأ في الـ validation
                                 $oldEquipment = old('equipment', $currentEquipment); // Use old input or current equipment
                                  // تأكد أن oldEquipment مصفوفة
                                 if (!is_array($oldEquipment)) { $oldEquipment = []; }
                             @endphp
                             @foreach($allEquipment as $equip)
                                 <div class="form-check form-check-inline me-3 mb-2">
                                     <input class="form-check-input" type="checkbox" id="edit_equip_{{ str_replace(' ', '_', $equip) }}_{{ $room->id }}" name="equipment[]" value="{{ $equip }}" {{ in_array($equip, $oldEquipment) ? 'checked' : '' }}>
                                     <label class="form-check-label" for="edit_equip_{{ str_replace(' ', '_', $equip) }}_{{ $room->id }}">{{ ucfirst($equip) }}</label>
                                 </div>
                             @endforeach
                         </div>
                          @error('equipment', 'update_'.$room->id) <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                          @error('equipment.*', 'update_'.$room->id) <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                     </div> --}}

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Classroom</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- // Modal لتأكيد الحذف --}}
<div class="modal fade" id="deleteRoomModal-{{ $room->id }}" tabindex="-1" aria-labelledby="deleteRoomModalLabel-{{ $room->id }}" aria-hidden="true">
     <div class="modal-dialog">
        {{-- // نفس تصميم مودال حذف القسم --}}
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteRoomModalLabel-{{ $room->id }}">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form action="{{ route('data-entry.rooms.destroy', $room->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p>Are you sure you want to delete the classroom: <strong>{{ $room->room_no }} ({{ $room->room_name ?? $room->roomType->room_type_name }})</strong>?</p>
                     <p class="text-danger small">This action cannot be undone. Any scheduled classes in this room might be affected.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete Classroom</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
