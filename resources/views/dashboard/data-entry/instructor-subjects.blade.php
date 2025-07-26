@extends('dashboard.layout')

@section('content')
    <div class="main-content">
        <div class="data-entry-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="data-entry-header mb-0">Instructor Subject Assignments</h1>
                {{-- يمكنك إضافة زر لإدارة المدرسين من هنا --}}
                <div class="d-flex">
                    <a href="{{ route('data-entry.instructors.index') }}" class="btn btn-outline-primary me-2">
                        <i class="fas fa-user-tie me-1"></i> Manage Instructors
                    </a>
                    <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#importAssignmentsModal">
                        <i class="fas fa-file-excel me-1"></i> Import Assignments
                    </button>
                </div>
            </div>

            @include('dashboard.data-entry.partials._status_messages')
            @if (session('skipped_details') && count(session('skipped_details')) > 0)
                <div class="alert alert-warning mt-3">
                    <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Skipped Entries Details:</h5>
                    <ul class="mb-0 small" style="max-height: 200px; overflow-y: auto;">
                        @foreach (session('skipped_details') as $detail)
                            <li>{{ $detail }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 25%;">Instructor</th>
                                    <th style="width: 25%;">Department</th>
                                    <th style="width: 35%;">Assigned Subjects ({{-- $instructor->subjects_count --}})</th>
                                    <th style="width: 10%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($instructors as $index => $instructor)
                                    <tr>
                                        <td>{{ $instructors->firstItem() + $index }}</td>
                                        <td>
                                            <div>{{ $instructor->instructor_name ?? optional($instructor->user)->name }}
                                            </div>
                                            <small class="text-muted">{{ $instructor->instructor_no }}</small>
                                        </td>
                                        <td>{{ optional($instructor->department)->department_name ?? 'N/A' }}</td>
                                        <td>
                                            @forelse ($instructor->subjects as $subject)
                                                {{-- <span class="badge bg-light text-dark border me-1 mb-1">{{ $subject->subject_no }}</span> --}}
                                                <span
                                                    class="badge bg-light text-dark border me-1 mb-1">{{ $subject->subject_no }}
                                                    - {{ $subject->subject_name }}</span>
                                                {{-- <span class="badge bg-light text-dark border me-1 mb-1">{{ $subject->subject_name }}</span> --}}
                                            @empty
                                                <span class="badge bg-secondary">None</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            <a href="{{ route('data-entry.instructor-subjects.edit', $instructor->id) }}"
                                                class="btn btn-sm btn-outline-primary" title="Edit Subject Assignments">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No instructors found.</td>
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


{{-- *** مودال رفع ملف الإكسل للتعيينات *** --}}
<div class="modal fade" id="importAssignmentsModal" tabindex="-1" aria-labelledby="importAssignmentsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importAssignmentsModalLabel">Import Instructor-Subject Assignments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('data-entry.instructor-subject.importExcel') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="assignments_excel_file_input" class="form-label">Select Excel File <span
                                class="text-danger">*</span></label>
                        <input class="form-control" type="file" id="assignments_excel_file_input"
                            name="assignments_excel_file" accept=".xlsx, .xls, .csv" required>
                    </div>
                    <div class="alert alert-info small p-2">
                        <strong>Instructions:</strong><br>
                        - File must contain headers like: <code>instructor_name</code> (or ID), <code>subject_no</code>
                        (or ID/Name).<br>
                        - The 'subject' column can contain multiple subject codes/names separated by a comma
                        (<code>,</code>).<br>
                        - Existing assignments will not be removed. This process only adds new assignments.<br>
                        - Rows with missing instructor or subject data will be skipped.
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
