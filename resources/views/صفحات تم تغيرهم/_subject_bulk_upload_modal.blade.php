<div class="modal fade" id="bulkUploadSubjectModal" tabindex="-1" aria-labelledby="bulkUploadSubjectModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkUploadSubjectModalLabel">Bulk Upload Subjects</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- // تأكد من إضافة enctype="multipart/form-data" للنموذج --}}
            <form action="{{ route('data-entry.subjects.bulkUpload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="subject_file" class="form-label">Select Excel File (.xlsx, .xls, .csv)</label>
                        {{-- // حقل رفع الملف --}}
                        <input class="form-control @error('subject_file') is-invalid @enderror" type="file"
                            id="subject_file" name="subject_file" accept=".xlsx, .xls, .csv" required>
                        @error('subject_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <p class="small text-muted">
                            The Excel file should contain columns matching the subject data fields:
                            <br>
                            `subject_no`, `subject_name`, `subject_load`, `theoretical_hours`, `practical_hours`,
                            `subject_type_name` (Exact name from types), `subject_category_name` (Exact name from
                            categories), `department_no` (Code of the primary department).
                        </p>
                        // داخل _subject_bulk_upload_modal.blade.php
                        <p class="small text-muted">
                            The Excel file should contain columns matching the subject data fields:
                            <br>
                            `subject_no`, `subject_name`, `subject_load`, `theoretical_hours`, `practical_hours`,
                            `subject_type_id` (ID of the type), `subject_category_id` (ID of the category),
                            `department_id` (ID of the primary department).
                            <br>
                            <strong class="text-danger">Note: You need to provide the exact IDs for type, category, and
                                department.</strong>
                        </p>

                        {{-- // يمكنك إضافة رابط لتنزيل ملف قالب هنا --}}
                        {{-- <a href="/path/to/template.xlsx" download>Download Template</a> --}}
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
