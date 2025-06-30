@extends('dashboard.layout')

@section('content')
    <div class="main-content">
        <div class="data-entry-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="data-entry-header mb-0">Instructor Subject Assignments</h1>
                {{-- يمكن إضافة زر لإضافة مدرس جديد إذا أردت --}}
                <div class="d-flex">
                    {{-- *** زر توليد الشعب الناقصة *** --}}
                    <form action="{{ route('data-entry.instructor-load.generateMissingSections') }}" method="POST"
                        class="d-inline me-2">
                        @csrf
                        {{-- يمكنك إضافة فلاتر هنا إذا أردت تحديد سنة وفصل معين للتوليد --}}
                        <button type="submit" class="btn btn-info btn-sm"
                            onclick="return confirm('This will generate sections for any plan context that has expected students but no sections yet. Continue?');">
                            <i class="fas fa-magic me-1"></i> Generate Missing Sections
                        </button>
                    </form>

                    {{-- *** زر رفع ملف الإكسل لتوزيع الأحمال *** --}}
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                        data-bs-target="#importInstructorLoadsModal">
                        <i class="fas fa-file-excel me-1"></i> Import Instructor Loads
                    </button>
                </div>
                {{-- <a href="{{ route('data-entry.instructors.index') }}" class="btn btn-outline-primary">Manage Instructors</a> --}}
            </div>

            @include('dashboard.data-entry.partials._status_messages')
            @if (session('skipped_details') && count(session('skipped_details')) > 0)
                <div class="alert alert-warning mt-3">
                    <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Import Skipped Rows:</h5>
                    <ul class="mb-0 small" style="max-height: 200px; overflow-y: auto;">
                        @foreach (session('skipped_details') as $detail)
                            <li>{{ $detail }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- TODO: Add Filters (by Department) --}}

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Instructor No</th>
                                    <th scope="col">Instructor Name</th>
                                    <th scope="col">Department</th>
                                    <th scope="col">Assigned Subjects Count</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($instructors as $index => $instructor)
                                    <tr>
                                        <td>{{ $instructors->firstItem() + $index }}</td>
                                        <td>{{ $instructor->instructor_no }}</td>
                                        <td>{{ $instructor->instructor_name ?? optional($instructor->user)->name }}</td>
                                        <td>{{ optional($instructor->department)->department_name ?? 'N/A' }}</td>
                                        <td>
                                            {{-- عرض عدد المواد المعينة --}}
                                            <span class="badge bg-info">{{ $instructor->sections_count }}</span>
                                            {{-- يمكنك إضافة tooltip لعرض أسماء المواد إذا كان العدد قليلاً --}}
                                        </td>
                                        <td>
                                            {{-- زر للانتقال لصفحة تعديل التعيينات --}}
                                            <a href="{{ route('data-entry.instructor-section.edit', $instructor->id) }}"
                                                class="btn btn-sm btn-outline-primary" title="Edit Section Assignments">
                                                <i class="fas fa-edit me-1"></i> Edit Assignments
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No instructors found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $instructors->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection


{{-- *** مودال رفع ملف الإكسل لتوزيع الأحمال *** --}}
<div class="modal fade" id="importInstructorLoadsModal" tabindex="-1" aria-labelledby="importInstructorLoadsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importInstructorLoadsModalLabel">Import Instructor Loads from Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('data-entry.instructor-load.importExcel') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="target_semester_import" class="form-label">Target Semester <span
                                class="text-danger">*</span></label>
                        <select class="form-select @error('target_semester') is-invalid @enderror"
                            id="target_semester_import" name="target_semester" required>
                            <option value="" selected disabled>Select semester...</option>
                            <option value="1" {{ old('target_semester') == '1' ? 'selected' : '' }}>First Semester
                            </option>
                            <option value="2" {{ old('target_semester') == '2' ? 'selected' : '' }}>Second
                                Semester</option>
                            <option value="3" {{ old('target_semester') == '3' ? 'selected' : '' }}>Summer
                                Semester</option>
                        </select>
                        @error('target_semester')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- <div class="mb-3">
                        <label for="target_academic_year_import" class="form-label">Target academic year<span
                                class="text-danger">*</span></label>
                        <select class="form-select @error('target_academic_year') is-invalid @enderror"
                            id="target_academic_year_import" name="target_academic_year" required>
                            <option value="" selected disabled>Select semester...</option>
                            <option value="2025" {{ old('target_academic_year') == '2025' ? 'selected' : '' }}>First Semester
                            </option>
                            <option value="2" {{ old('target_academic_year') == '2' ? 'selected' : '' }}>Second
                                Semester</option>
                            <option value="3" {{ old('target_academic_year') == '3' ? 'selected' : '' }}>Summer
                                Semester</option>
                        </select>
                        @error('target_academic_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div> --}}
                    {{-- (اختياري) يمكنك إضافة حقل لاختيار السنة الأكاديمية المستهدفة هنا --}}
                    {{-- <input type="number" name="target_academic_year" placeholder="YYYY"> --}}

                    <div class="mb-3">
                        <label for="instructor_loads_excel_file_input" class="form-label">Select Excel File <span
                                class="text-danger">*</span></label>
                        <input class="form-control @error('instructor_loads_excel_file') is-invalid @enderror"
                            type="file" id="instructor_loads_excel_file_input" name="instructor_loads_excel_file"
                            accept=".xlsx, .xls, .csv" required>
                        @error('instructor_loads_excel_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="alert alert-info small p-2">
                        <strong>File Format Instructions:</strong><br>
                        - Headers: اسم المدرس, اسم المساق, التخصص والمستوى, عدد الشعب نظري, عدد الشعب عملي, الفرع.<br>
                        - Ensure "التخصص والمستوى" is like "CSE2" (Code + Level Number).<br>
                        - The system will assign available sections to the instructor based on counts, branch, and the
                        selected Target Semester.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i> Upload and Assign Loads
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
