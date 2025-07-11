{{-- مودال الإضافة --}}
@if (!$type)
<div class="modal fade" id="addSelectionModal" tabindex="-1" aria-labelledby="addSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="addSelectionModalLabel">Add New Selection Method</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form action="{{ route('algorithm-control.selection-types.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_selection_name" class="form-label">Method Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="add_selection_name" name="name" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="add_selection_description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="add_selection_description" name="description" rows="3">{{ old('description') }}</textarea>
                         @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-check form-switch">
                       <input class="form-check-input" type="checkbox" role="switch" id="add_selection_is_active" name="is_active" value="1" checked>
                       <label class="form-check-label" for="add_selection_is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Method</button></div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- مودالات التعديل والحذف --}}
@if ($type)
{{-- مودال التعديل --}}
<div class="modal fade" id="editSelectionModal-{{ $type->selection_type_id }}" tabindex="-1" aria-labelledby="editSelectionModalLabel-{{ $type->selection_type_id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
             <div class="modal-header"><h5 class="modal-title">Edit: {{ $type->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
             <form action="{{ route('algorithm-control.selection-types.update', $type->selection_type_id) }}" method="POST">
                 @csrf @method('PUT')
                 <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_selection_name_{{ $type->selection_type_id }}" class="form-label">Method Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_selection_name_{{ $type->selection_type_id }}" name="name" value="{{ old('name', $type->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_selection_description_{{ $type->selection_type_id }}" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_selection_description_{{ $type->selection_type_id }}" name="description" rows="3">{{ old('description', $type->description) }}</textarea>
                    </div>
                    <div class="form-check form-switch">
                       <input class="form-check-input" type="checkbox" role="switch" id="edit_selection_is_active_{{ $type->selection_type_id }}" name="is_active" value="1" {{ old('is_active', $type->is_active) ? 'checked' : '' }}>
                       <label class="form-check-label" for="edit_selection_is_active_{{ $type->selection_type_id }}">Active</label>
                    </div>
                 </div>
                 <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Update Method</button></div>
             </form>
        </div>
    </div>
</div>
{{-- مودال الحذف --}}
<div class="modal fade" id="deleteSelectionModal-{{ $type->selection_type_id }}" tabindex="-1" aria-labelledby="deleteSelectionModalLabel-{{ $type->selection_type_id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white"><h5 class="modal-title">Confirm Deletion</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form action="{{ route('algorithm-control.selection-types.destroy', $type->selection_type_id) }}" method="POST">
                @csrf @method('DELETE')
                <div class="modal-body">
                     <p>Are you sure you want to delete the selection method: <strong>{{ $type->name }}</strong>?</p>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Yes, Delete</button></div>
            </form>
        </div>
    </div>
</div>
@endif
