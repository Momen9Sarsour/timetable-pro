@extends('dashboard.layout')

@push('styles')
<style>
    .subject-list-container { max-height: 60vh; overflow-y: auto; border: 1px solid #dee2e6; padding: 1rem; border-radius: .375rem; background-color: #f8f9fa; }
    .subject-list .form-check { margin-bottom: .5rem; }
    .subject-list .form-check-label { font-size: 0.9rem; cursor: pointer; }
    .subject-search-input { margin-bottom: 1rem; }
</style>
@endpush

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        {{-- Header and Back Button --}}
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="data-entry-header mb-1">Assign Subjects</h1>
                    <h2 class="h5 text-muted">For Instructor: <span class="text-primary">{{ $instructor->instructor_name ?? optional($instructor->user)->name }}</span> ({{ $instructor->instructor_no }})</h2>
                </div>
                 <a href="{{ route('data-entry.instructor-subjects.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Assignments List
                </a>
            </div>
             <p class="mb-0 text-secondary small">Department: {{ optional($instructor->department)->department_name ?? 'N/A' }}</p>
        </div>

         @include('dashboard.data-entry.partials._status_messages')

        <form action="{{ route('data-entry.instructor-subjects.sync', $instructor->id) }}" method="POST">
            @csrf
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Select Assignable Subjects</h5>
                </div>
                <div class="card-body">
                    {{-- حقل البحث --}}
                    <div class="subject-search-input">
                        <input type="text" id="subjectAssignmentSearch" class="form-control" placeholder="Search subjects by name or code..." autofocus>
                    </div>

                     {{-- قائمة المواد مع Checkboxes --}}
                    <div class="subject-list-container">
                        <div class="subject-list row">
                             @if(isset($allSubjects) && $allSubjects->count() > 0)
                                 @foreach ($allSubjects as $subject)
                                 <div class="col-md-6 subject-assignment-item">
                                     <div class="form-check">
                                         <input class="form-check-input" type="checkbox"
                                                name="subject_ids[]"
                                                value="{{ $subject->id }}"
                                                id="subject_assign_{{ $subject->id }}"
                                                {{-- التحقق إذا كانت المادة معينة حالياً لهذا المدرس --}}
                                                {{ in_array($subject->id, $assignedSubjectIds ?? []) ? 'checked' : '' }}>
                                         <label class="form-check-label" for="subject_assign_{{ $subject->id }}">
                                             <strong>{{ $subject->subject_no }}</strong> - {{ $subject->subject_name }}
                                             {{-- استخدام optional() للحماية --}}
                                             <span class="text-muted small">({{ optional($subject->department)->department_name ?? 'N/A' }})</span>
                                         </label>
                                     </div>
                                 </div>
                                 @endforeach
                             @else
                                 <p class="text-muted text-center m-0">No subjects available in the system.</p>
                             @endif
                             <p class="text-muted text-center mt-2 col-12" id="noAssignmentResults" style="display: none;">No subjects match your search.</p>
                         </div>
                    </div>
                    @error('subject_ids') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                     @error('subject_ids.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror

                </div>
                 <div class="card-footer text-end">
                     <a href="{{ route('data-entry.instructor-subjects.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Assignments
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
$(document).ready(function() {
    $('#subjectAssignmentSearch').on('keyup', function() {
         const searchTerm = $(this).val().toLowerCase();
         let resultsFound = false;
         $('.subject-assignment-item').each(function() {
             const labelText = $(this).find('.form-check-label').text().toLowerCase();
             if (labelText.includes(searchTerm)) {
                 $(this).show();
                 resultsFound = true;
             } else {
                 $(this).hide();
             }
         });
         $('#noAssignmentResults').toggle(!resultsFound);
     });
});
</script>
@endpush
