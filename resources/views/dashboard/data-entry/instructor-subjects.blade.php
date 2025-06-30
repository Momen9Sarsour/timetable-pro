@extends('dashboard.layout')

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h1 class="data-entry-header mb-0">Instructor Subject Assignments</h1>
             {{-- يمكنك إضافة زر لإدارة المدرسين من هنا --}}
             <a href="{{ route('data-entry.instructors.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-user-tie me-1"></i> Manage Instructors
             </a>
        </div>

        @include('dashboard.data-entry.partials._status_messages')

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
                                    <div>{{ $instructor->instructor_name ?? optional($instructor->user)->name }}</div>
                                    <small class="text-muted">{{ $instructor->instructor_no }}</small>
                                </td>
                                <td>{{ optional($instructor->department)->department_name ?? 'N/A' }}</td>
                                <td>
                                    @forelse ($instructor->subjects as $subject)
                                        {{-- <span class="badge bg-light text-dark border me-1 mb-1">{{ $subject->subject_no }}</span> --}}
                                        <span class="badge bg-light text-dark border me-1 mb-1">{{ $subject->subject_no }} - {{ $subject->subject_name }}</span>
                                        {{-- <span class="badge bg-light text-dark border me-1 mb-1">{{ $subject->subject_name }}</span> --}}
                                    @empty
                                        <span class="badge bg-secondary">None</span>
                                    @endforelse
                                </td>
                                <td>
                                    <a href="{{ route('data-entry.instructor-subjects.edit', $instructor->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Subject Assignments">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted">No instructors found.</td></tr>
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
