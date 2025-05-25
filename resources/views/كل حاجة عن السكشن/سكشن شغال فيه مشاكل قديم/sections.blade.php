@extends('dashboard.layout')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .select2-container--bootstrap-5 .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; font-size: 0.875rem; }
    .table th, .table td { vertical-align: middle; font-size: 0.85rem; }
</style>
@endpush

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h1 class="data-entry-header mb-0">View All Academic Sections</h1>
        </div>

        @include('dashboard.data-entry.partials._status_messages')

        {{-- قسم الفلاتر --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="fas fa-filter me-2"></i>Filter Sections</h5>
                <form action="{{ route('data-entry.sections.index') }}" method="GET">
                    <div class="row g-3 align-items-end">
                        {{-- Year --}}
                        <div class="col-md-2"><label for="filter_academic_year" class="form-label form-label-sm">Year</label><select class="form-select form-select-sm" id="filter_academic_year" name="academic_year"><option value="">All</option>@foreach($academicYears as $year)<option value="{{ $year }}" {{ $request->academic_year == $year ? 'selected' : '' }}>{{ $year }}</option>@endforeach</select></div>
                        {{-- Term --}}
                        <div class="col-md-2"><label for="filter_semester" class="form-label form-label-sm">Term</label><select class="form-select form-select-sm" id="filter_semester" name="semester"><option value="">All</option>@foreach($semesters as $key => $value)<option value="{{ $key }}" {{ $request->semester == $key ? 'selected' : '' }}>{{ $value }}</option>@endforeach</select></div>
                        {{-- Department --}}
                        <div class="col-md-3"><label for="filter_department_id" class="form-label form-label-sm">Department</label><select class="form-select form-select-sm select2-filter" id="filter_department_id" name="department_id" data-placeholder="All Departments"><option value=""></option>@foreach($departments as $department)<option value="{{ $department->id }}" {{ $request->department_id == $department->id ? 'selected' : '' }}>{{ $department->department_name }}</option>@endforeach</select></div>
                        {{-- Plan --}}
                        <div class="col-md-3"><label for="filter_plan_id" class="form-label form-label-sm">Plan</label><select class="form-select form-select-sm select2-filter" id="filter_plan_id" name="plan_id" data-placeholder="All Plans"><option value=""></option>@foreach($plans as $plan_filter_item)<option value="{{ $plan_filter_item->id }}" {{ $request->plan_id == $plan_filter_item->id ? 'selected' : '' }}>{{ $plan_filter_item->plan_no }} - {{ $plan_filter_item->plan_name }}</option>@endforeach</select></div>
                        {{-- Level --}}
                        <div class="col-md-2"><label for="filter_plan_level" class="form-label form-label-sm">Level</label><select class="form-select form-select-sm" id="filter_plan_level" name="plan_level"><option value="">All</option>@foreach($levels as $level_option)<option value="{{ $level_option }}" {{ $request->plan_level == $level_option ? 'selected' : '' }}>{{ $level_option }}</option>@endforeach</select></div>
                    </div>
                    <div class="row g-3 mt-1 align-items-end">
                        {{-- Subject --}}
                         <div class="col-md-4"><label for="filter_subject_id" class="form-label form-label-sm">Subject</label><select class="form-select form-select-sm select2-filter" id="filter_subject_id" name="subject_id" data-placeholder="All Subjects"><option value=""></option>@foreach($subjectsForFilter as $subject_filter)<option value="{{ $subject_filter->id }}" {{ $request->subject_id == $subject_filter->id ? 'selected' : '' }}>{{ $subject_filter->subject_no }} - {{ $subject_filter->subject_name }}</option>@endforeach</select></div>
                         {{-- Branch --}}
                          <div class="col-md-3"><label for="filter_branch" class="form-label form-label-sm">Branch</label><input type="text" class="form-control form-control-sm" id="filter_branch" name="branch" value="{{ $request->branch }}" placeholder="e.g., Main or none"><small class="text-muted">Type 'none' for default.</small></div>
                          {{-- Buttons --}}
                          <div class="col-md-auto ms-auto"><a href="{{ route('data-entry.sections.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a><button type="submit" class="btn btn-sm btn-primary ms-2">Filter</button></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="table-light">
                            <tr><th>#</th><th>Year</th><th>Term</th><th>Plan</th><th>Lvl</th><th>Subject</th><th>Activity</th><th>Sec#</th><th>Gender</th><th>Branch</th><th>Count</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($sections as $index => $section)
                            <tr>
                                <td>{{ $sections->firstItem() + $index }}</td>
                                <td>{{ $section->academic_year }}</td>
                                <td>{{ $section->semester }}</td>
                                <td title="{{ optional(optional($section->planSubject)->plan)->plan_name ?? '' }}">{{ optional(optional($section->planSubject)->plan)->plan_no ?? 'N/A' }}</td>
                                <td>{{ optional($section->planSubject)->plan_level ?? 'N/A' }}</td>
                                <td title="{{ optional(optional($section->planSubject)->subject)->subject_name ?? '' }}">{{ optional(optional($section->planSubject)->subject)->subject_no ?? 'N/A' }}</td>
                                <td><span class="badge bg-{{ $section->activity_type == 'Theory' ? 'primary' : 'success' }}">{{ $section->activity_type }}</span></td>
                                <td>{{ $section->section_number }}</td>
                                <td>{{ $section->section_gender }}</td>
                                <td>{{ $section->branch ?? '-' }}</td>
                                <td>{{ $section->student_count }}</td>
                                <td>
                                    <a href="{{ route('data-entry.sections.manageSubjectContext', [
                                                'plan_subject_id' => $section->plan_subject_id,
                                                'academic_year' => $section->academic_year,
                                                'semester_of_sections' => $section->semester,
                                                'branch' => $section->branch,
                                            ]) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Manage all sections for: {{ optional(optional($section->planSubject)->subject)->subject_no }} in this context">
                                        <i class="fas fa-edit"></i> Manage
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="12" class="text-center text-muted">No sections found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                 <div class="mt-3 d-flex justify-content-center">
                     {{ $sections->appends(request()->query())->links() }}
                 </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2-filter').select2({ theme: 'bootstrap-5', allowClear: true, placeholder: $(this).data('placeholder') });
    });
</script>
@endpush
