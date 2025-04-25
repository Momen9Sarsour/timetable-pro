{{-- // =============================== --}}
{{-- // Modal لإضافة فئة مادة جديدة --}}
{{-- // =============================== --}}
@if (!$subjectCategory)
<div class="modal fade" id="addSubjectCategoryModal" tabindex="-1" aria-labelledby="addSubjectCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSubjectCategoryModalLabel">Add New Subject Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- // تغيير الـ route --}}
            <form action="{{ route('data-entry.subject-categories.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                         {{-- // تغيير الحقول --}}
                        <label for="add_subject_category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('subject_category_name', 'store') is-invalid @enderror" id="add_subject_category_name" name="subject_category_name" value="{{ old('subject_category_name') }}" required>
                        @error('subject_category_name', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Subject Category</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- // =============================== --}}
{{-- // Modals التعديل والحذف --}}
{{-- // =============================== --}}
@if ($subjectCategory)

{{-- // Modal لتعديل فئة مادة موجودة --}}
<div class="modal fade" id="editSubjectCategoryModal-{{ $subjectCategory->id }}" tabindex="-1" aria-labelledby="editSubjectCategoryModalLabel-{{ $subjectCategory->id }}" aria-hidden="true">
     <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSubjectCategoryModalLabel-{{ $subjectCategory->id }}">Edit Subject Category: {{ $subjectCategory->subject_category_name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             {{-- // تغيير الـ route والمتغير --}}
             <form action="{{ route('data-entry.subject-categories.update', $subjectCategory->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                     <div class="mb-3">
                        {{-- // تغيير الحقول والمتغيرات --}}
                        <label for="edit_subject_category_name_{{ $subjectCategory->id }}" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('subject_category_name', 'update_'.$subjectCategory->id) is-invalid @enderror" id="edit_subject_category_name_{{ $subjectCategory->id }}" name="subject_category_name" value="{{ old('subject_category_name', $subjectCategory->subject_category_name) }}" required>
                        @error('subject_category_name', 'update_'.$subjectCategory->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Subject Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- // Modal لتأكيد الحذف --}}
@if ($subjectCategory->subjects()->count() == 0)
<div class="modal fade" id="deleteSubjectCategoryModal-{{ $subjectCategory->id }}" tabindex="-1" aria-labelledby="deleteSubjectCategoryModalLabel-{{ $subjectCategory->id }}" aria-hidden="true">
     <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSubjectCategoryModalLabel-{{ $subjectCategory->id }}">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             {{-- // تغيير الـ route والمتغير --}}
             <form action="{{ route('data-entry.subject-categories.destroy', $subjectCategory->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    {{-- // تغيير النص والمتغير --}}
                    <p>Are you sure you want to delete the subject category: <strong>{{ $subjectCategory->subject_category_name }}</strong>?</p>
                    <p class="text-danger small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete Subject Category</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endif
