{{-- // Modal لإضافة قسم جديد --}}
@if (!$department)
<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDepartmentModalLabel">Add New Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('data-entry.departments.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_department_no" class="form-label">Department Number/Code</label>
                        <input type="text" class="form-control @error('department_no') is-invalid @enderror" id="add_department_no" name="department_no" value="{{ old('department_no') }}" required>
                        @error('department_no')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="add_department_name" class="form-label">Department Name</label>
                        <input type="text" class="form-control @error('department_name') is-invalid @enderror" id="add_department_name" name="department_name" value="{{ old('department_name') }}" required>
                         @error('department_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Department</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- // Modal لتعديل قسم موجود --}}
@if ($department)
<div class="modal fade" id="editDepartmentModal-{{ $department->id }}" tabindex="-1" aria-labelledby="editDepartmentModalLabel-{{ $department->id }}" aria-hidden="true">
     <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDepartmentModalLabel-{{ $department->id }}">Edit Department: {{ $department->department_name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form action="{{ route('data-entry.departments.update', $department->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                     <div class="mb-3">
                        <label for="edit_department_no_{{ $department->id }}" class="form-label">Department Number/Code</label>
                        <input type="text" class="form-control @error('department_no', 'update_'.$department->id) is-invalid @enderror" id="edit_department_no_{{ $department->id }}" name="department_no" value="{{ old('department_no', $department->department_no) }}" required>
                        @error('department_no', 'update_'.$department->id)
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit_department_name_{{ $department->id }}" class="form-label">Department Name</label>
                        <input type="text" class="form-control @error('department_name', 'update_'.$department->id) is-invalid @enderror" id="edit_department_name_{{ $department->id }}" name="department_name" value="{{ old('department_name', $department->department_name) }}" required>
                         @error('department_name', 'update_'.$department->id)
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- // Modal لتأكيد الحذف --}}
<div class="modal fade" id="deleteDepartmentModal-{{ $department->id }}" tabindex="-1" aria-labelledby="deleteDepartmentModalLabel-{{ $department->id }}" aria-hidden="true">
     <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteDepartmentModalLabel-{{ $department->id }}">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form action="{{ route('data-entry.departments.destroy', $department->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p>Are you sure you want to delete the department: <strong>{{ $department->department_name }} ({{ $department->department_no }})</strong>?</p>
                    <p class="text-danger small">This action cannot be undone. Associated instructors, subjects, or plans might be affected depending on deletion constraints.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete Department</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
