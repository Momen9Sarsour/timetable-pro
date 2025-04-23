{{-- // Modal لإضافة دور جديد --}}
@if (!$role)
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addRoleModalLabel">Add New Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('data-entry.roles.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_role_name" class="form-label">System Name (Code) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name', 'store') is-invalid @enderror" id="add_role_name" name="name" value="{{ old('name') }}" required placeholder="e.g., supervisor, librarian (lowercase, no spaces)">
                        <small class="text-muted">Use lowercase letters, numbers, dashes, underscores only.</small>
                        @error('name', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                         <label for="add_role_display_name" class="form-label">Display Name <span class="text-danger">*</span></label>
                         <input type="text" class="form-control @error('display_name', 'store') is-invalid @enderror" id="add_role_display_name" name="display_name" value="{{ old('display_name') }}" required placeholder="e.g., Supervisor, Librarian">
                         @error('display_name', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                     </div>
                    <div class="mb-3">
                        <label for="add_role_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control @error('description', 'store') is-invalid @enderror" id="add_role_description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Role</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- // Modal لتعديل دور موجود --}}
@if ($role)
{{-- @php $isCoreRole = in_array($role->name, ['admin', 'hod', 'instructor', 'student']); @endphp --}}
<div class="modal fade" id="editRoleModal-{{ $role->id }}" tabindex="-1" aria-labelledby="editRoleModalLabel-{{ $role->id }}" aria-hidden="true">
     <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRoleModalLabel-{{ $role->id }}">Edit Role: {{ $role->display_name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             {{-- // لا نسمح بتعديل الأدوار الأساسية --}}
             {{-- @if($isCoreRole) --}}
                 {{-- <div class="modal-body">
                     <div class="alert alert-warning">Core system roles cannot be modified.</div>
                 </div>
                  <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                 </div> --}}
             {{-- @else --}}
                 <form action="{{ route('data-entry.roles.update', $role->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                         <div class="mb-3">
                            <label for="edit_role_name_{{ $role->id }}" class="form-label">System Name (Code) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name', 'update_'.$role->id) is-invalid @enderror" id="edit_role_name_{{ $role->id }}" name="name" value="{{ old('name', $role->name) }}" required>
                            <small class="text-muted">Use lowercase letters, numbers, dashes, underscores only.</small>
                            @error('name', 'update_'.$role->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                             <label for="edit_role_display_name_{{ $role->id }}" class="form-label">Display Name <span class="text-danger">*</span></label>
                             <input type="text" class="form-control @error('display_name', 'update_'.$role->id) is-invalid @enderror" id="edit_role_display_name_{{ $role->id }}" name="display_name" value="{{ old('display_name', $role->display_name) }}" required>
                             @error('display_name', 'update_'.$role->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                         </div>
                        <div class="mb-3">
                            <label for="edit_role_description_{{ $role->id }}" class="form-label">Description (Optional)</label>
                            <textarea class="form-control @error('description', 'update_'.$role->id) is-invalid @enderror" id="edit_role_description_{{ $role->id }}" name="description" rows="3">{{ old('description', $role->description) }}</textarea>
                            @error('description', 'update_'.$role->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Role</button>
                    </div>
                </form>
             {{-- @endif --}}
        </div>
    </div>
</div>

{{-- // Modal لتأكيد الحذف --}}
{{-- // لا نحتاج لعرضه إذا كان الزر معطلاً، ولكن نضيفه للكمال --}}
@if ((!$isCoreRole && $role->users()->count() == 0))
<div class="modal fade" id="deleteRoleModal-{{ $role->id }}" tabindex="-1" aria-labelledby="deleteRoleModalLabel-{{ $role->id }}" aria-hidden="true">
     <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteRoleModalLabel-{{ $role->id }}">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form action="{{ route('data-entry.roles.destroy', $role->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p>Are you sure you want to delete the role: <strong>{{ $role->display_name }} (<code>{{ $role->name }}</code>)</strong>?</p>
                    <p class="text-danger small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete Role</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endif
