@extends('dashboard.layout')

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h1 class="data-entry-header mb-0">Instructor Subject Assignments</h1>
             {{-- يمكن إضافة زر لإضافة مدرس جديد إذا أردت --}}
             {{-- <a href="{{ route('data-entry.instructors.index') }}" class="btn btn-outline-primary">Manage Instructors</a> --}}
        </div>

        @include('dashboard.data-entry.partials._status_messages')

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
                                    <span class="badge bg-info">{{ $instructor->subjects_count }}</span>
                                    {{-- يمكنك إضافة tooltip لعرض أسماء المواد إذا كان العدد قليلاً --}}
                                </td>
                                <td>
                                    {{-- زر للانتقال لصفحة تعديل التعيينات --}}
                                    <a href="{{ route('data-entry.instructor-subject.edit', $instructor->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Subject Assignments">
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
