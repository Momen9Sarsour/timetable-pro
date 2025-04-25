{{-- // ============================ --}}
{{-- // Modal لإضافة نوع مادة جديد --}}
{{-- // ============================ --}}
{{-- // تغيير المتغير الشرطي إلى !$subjectType --}}
@if (!$subjectType)
<div class="modal fade" id="addSubjectTypeModal" tabindex="-1" aria-labelledby="addSubjectTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                {{-- // تغيير العنوان --}}
                <h5 class="modal-title" id="addSubjectTypeModalLabel">Add New Subject Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- // تغيير الـ route --}}
            <form action="{{ route('data-entry.subject-types.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        {{-- // تغيير الحقول --}}
                        <label for="add_subject_type_name" class="form-label">Subject Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('subject_type_name', 'store') is-invalid @enderror" id="add_subject_type_name" name="subject_type_name" value="{{ old('subject_type_name') }}" required>
                        @error('subject_type_name', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    {{-- // لا يوجد حقول أخرى لهذا الموديل --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Subject Type</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- // ============================ --}}
{{-- // Modals التعديل والحذف --}}
{{-- // ============================ --}}
{{-- // تغيير المتغير الشرطي إلى $subjectType --}}
@if ($subjectType)

{{-- // Modal لتعديل نوع مادة موجود --}}
<div class="modal fade" id="editSubjectTypeModal-{{ $subjectType->id }}" tabindex="-1" aria-labelledby="editSubjectTypeModalLabel-{{ $subjectType->id }}" aria-hidden="true">
     <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                 {{-- // تغيير العناوين والمتغيرات --}}
                <h5 class="modal-title" id="editSubjectTypeModalLabel-{{ $subjectType->id }}">Edit Subject Type: {{ $subjectType->subject_type_name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             {{-- // تغيير الـ route والمتغير --}}
             <form action="{{ route('data-entry.subject-types.update', $subjectType->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                     <div class="mb-3">
                         {{-- // تغيير الحقول والمتغيرات --}}
                        <label for="edit_subject_type_name_{{ $subjectType->id }}" class="form-label">Subject Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('subject_type_name', 'update_'.$subjectType->id) is-invalid @enderror" id="edit_subject_type_name_{{ $subjectType->id }}" name="subject_type_name" value="{{ old('subject_type_name', $subjectType->subject_type_name) }}" required>
                        @error('subject_type_name', 'update_'.$subjectType->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Subject Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- // Modal لتأكيد الحذف --}}
{{-- // تغيير الشرط ليعكس التحقق من وجود مواد مرتبطة --}}
@if ($subjectType->subjects()->count() == 0)
<div class="modal fade" id="deleteSubjectTypeModal-{{ $subjectType->id }}" tabindex="-1" aria-labelledby="deleteSubjectTypeModalLabel-{{ $subjectType->id }}" aria-hidden="true">
     <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSubjectTypeModalLabel-{{ $subjectType->id }}">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             {{-- // تغيير الـ route والمتغير --}}
             <form action="{{ route('data-entry.subject-types.destroy', $subjectType->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    {{-- // تغيير النص والمتغير --}}
                    <p>Are you sure you want to delete the subject type: <strong>{{ $subjectType->subject_type_name }}</strong>?</p>
                    <p class="text-danger small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete Subject Type</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif {{-- // نهاية شرط عدم وجود مواد مرتبطة --}}

@endif {{-- // نهاية الشرط if ($subjectType) --}}
