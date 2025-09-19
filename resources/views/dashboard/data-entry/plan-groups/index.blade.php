@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-users text-primary me-2"></i>
                        Student Groups Management
                    </h4>
                    <p class="text-muted mb-0">View and analyze student groups distribution across plans and subjects</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    <!-- Filters Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-2">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-filter text-muted me-2"></i>
                        Filters
                    </h6>
                </div>
                <div class="card-body pt-2">
                    <form method="GET" id="filterForm">
                        <div class="row g-3">
                            <!-- Department Filter -->
                            <div class="col-md-3">
                                <label for="department_id" class="form-label small fw-medium">Department</label>
                                <select class="form-select form-select-sm" id="department_id" name="department_id">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ $request->department_id == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->department_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Plan Filter -->
                            <div class="col-md-3">
                                <label for="plan_id" class="form-label small fw-medium">Plan</label>
                                <select class="form-select form-select-sm" id="plan_id" name="plan_id">
                                    <option value="">All Plans</option>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}" 
                                                data-department="{{ $plan->department_id }}"
                                                {{ $request->plan_id == $plan->id ? 'selected' : '' }}>
                                            {{ $plan->plan_no }} - {{ $plan->plan_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Academic Year Filter -->
                            <div class="col-md-2">
                                <label for="academic_year" class="form-label small fw-medium">Academic Year</label>
                                <select class="form-select form-select-sm" id="academic_year" name="academic_year">
                                    <option value="">All Years</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year }}" {{ $request->academic_year == $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Semester Filter -->
                            <div class="col-md-2">
                                <label for="semester" class="form-label small fw-medium">Semester</label>
                                <select class="form-select form-select-sm" id="semester" name="semester">
                                    <option value="">All Semesters</option>
                                    <option value="1" {{ $request->semester == 1 ? 'selected' : '' }}>First</option>
                                    <option value="2" {{ $request->semester == 2 ? 'selected' : '' }}>Second</option>
                                    <option value="3" {{ $request->semester == 3 ? 'selected' : '' }}>Summer</option>
                                </select>
                            </div>

                            <!-- Level Filter -->
                            <div class="col-md-2">
                                <label for="plan_level" class="form-label small fw-medium">Level</label>
                                <select class="form-select form-select-sm" id="plan_level" name="plan_level">
                                    <option value="">All Levels</option>
                                    @foreach($levels as $level)
                                        <option value="{{ $level }}" {{ $request->plan_level == $level ? 'selected' : '' }}>
                                            Level {{ $level }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-sm me-2">
                                    <i class="fas fa-search me-1"></i>Apply Filters
                                </button>
                                <a href="{{ route('data-entry.plan-groups.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Groups Data -->
    <div class="row">
        <div class="col-12">
            @if($groupedData->isEmpty())
                <!-- Empty State -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-users text-muted opacity-50" style="font-size: 4rem;"></i>
                        <h5 class="mt-3 text-muted">No Groups Found</h5>
                        <p class="text-muted mb-4">No student groups match your current filter criteria.</p>
                    </div>
                </div>
            @else
                @foreach($groupedData as $contextData)
                    <div class="card border-0 shadow-sm mb-4">
                        <!-- Context Header -->
                        <div class="card-header bg-primary text-white border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">
                                        <i class="fas fa-graduation-cap me-2"></i>
                                        {{ $contextData['context']['plan']->department->department_name }}
                                    </h6>
                                    <small class="opacity-75">
                                        Plan: {{ $contextData['context']['plan']->plan_no }} - {{ $contextData['context']['plan']->plan_name }} |
                                        Level {{ $contextData['context']['plan_level'] }} |
                                        Year {{ $contextData['context']['academic_year'] }} |
                                        Semester {{ $contextData['context']['semester'] }}
                                        @if($contextData['context']['branch'])
                                            | Branch: {{ $contextData['context']['branch'] }}
                                        @endif
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-white text-primary">
                                        {{ $contextData['context']['total_groups'] }} Groups Total
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Subjects Table -->
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0 ps-4">Subject</th>
                                            <th class="border-0 text-center">Total Students</th>
                                            <th class="border-0 text-center">Theory Groups</th>
                                            <th class="border-0 text-center">Practical Groups</th>
                                            <th class="border-0 text-center">Distribution Type</th>
                                            <th class="border-0 text-center">Sections</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($contextData['subjects'] as $subjectData)
                                            <tr>
                                                <!-- Subject Info -->
                                                <td class="ps-4">
                                                    <div>
                                                        <div class="fw-medium text-dark">
                                                            {{ $subjectData['subject']->subject_no }}
                                                        </div>
                                                        <div class="small text-muted">
                                                            {{ $subjectData['subject']->subject_name }}
                                                        </div>
                                                        <div class="small">
                                                            @if($subjectData['subject']->theoretical_hours > 0)
                                                                <span class="badge bg-info bg-opacity-10 text-info me-1">
                                                                    {{ $subjectData['subject']->theoretical_hours }}h Theory
                                                                </span>
                                                            @endif
                                                            @if($subjectData['subject']->practical_hours > 0)
                                                                <span class="badge bg-warning bg-opacity-10 text-warning">
                                                                    {{ $subjectData['subject']->practical_hours }}h Practical
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- Total Students -->
                                                <td class="text-center">
                                                    <span class="badge bg-dark">{{ $subjectData['total_students'] }}</span>
                                                </td>

                                                <!-- Theory Groups -->
                                                <td class="text-center">
                                                    @if($subjectData['theory_groups']->isEmpty())
                                                        <span class="text-muted">-</span>
                                                    @else
                                                        <div class="d-flex justify-content-center gap-1 flex-wrap">
                                                            @foreach($subjectData['theory_groups'] as $groupNo)
                                                                <span class="badge bg-info">{{ $groupNo }}</span>
                                                            @endforeach
                                                        </div>
                                                        @if($subjectData['theory_groups']->count() > 1)
                                                            <small class="text-info d-block mt-1">Shared</small>
                                                        @endif
                                                    @endif
                                                </td>

                                                <!-- Practical Groups -->
                                                <td class="text-center">
                                                    @if($subjectData['practical_groups']->isEmpty())
                                                        <span class="text-muted">-</span>
                                                    @else
                                                        <div class="d-flex justify-content-center gap-1 flex-wrap">
                                                            @foreach($subjectData['practical_groups'] as $groupNo)
                                                                <span class="badge bg-warning">{{ $groupNo }}</span>
                                                            @endforeach
                                                        </div>
                                                        @if($subjectData['practical_groups']->count() > 1)
                                                            <small class="text-warning d-block mt-1">Separate</small>
                                                        @endif
                                                    @endif
                                                </td>

                                                <!-- Distribution Type -->
                                                <td class="text-center">
                                                    @switch($subjectData['distribution_type'])
                                                        @case('mixed')
                                                            <span class="badge bg-success">Mixed</span>
                                                            <small class="d-block text-success">Theory Shared + Practical Separate</small>
                                                            @break
                                                        @case('combined')
                                                            <span class="badge bg-primary">Combined</span>
                                                            <small class="d-block text-primary">Theory + Practical</small>
                                                            @break
                                                        @case('theory_shared')
                                                            <span class="badge bg-info">Theory Shared</span>
                                                            <small class="d-block text-info">All Groups</small>
                                                            @break
                                                        @case('theory_single')
                                                            <span class="badge bg-info bg-opacity-50">Theory Single</span>
                                                            <small class="d-block text-info">One Group</small>
                                                            @break
                                                        @case('practical_separate')
                                                            <span class="badge bg-warning">Practical Separate</span>
                                                            <small class="d-block text-warning">Per Group</small>
                                                            @break
                                                        @case('practical_single')
                                                            <span class="badge bg-warning bg-opacity-50">Practical Single</span>
                                                            <small class="d-block text-warning">One Group</small>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">Unknown</span>
                                                    @endswitch
                                                </td>

                                                <!-- Sections Count -->
                                                <td class="text-center">
                                                    <div class="small">
                                                        @if($subjectData['theory_sections_count'] > 0)
                                                            <div class="text-info">
                                                                <i class="fas fa-chalkboard-teacher me-1"></i>{{ $subjectData['theory_sections_count'] }} Theory
                                                            </div>
                                                        @endif
                                                        @if($subjectData['practical_sections_count'] > 0)
                                                            <div class="text-warning">
                                                                <i class="fas fa-flask me-1"></i>{{ $subjectData['practical_sections_count'] }} Practical
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter Plans by Department
    const departmentSelect = document.getElementById('department_id');
    const planSelect = document.getElementById('plan_id');
    
    if (departmentSelect && planSelect) {
        departmentSelect.addEventListener('change', function() {
            const selectedDepartment = this.value;
            const planOptions = planSelect.querySelectorAll('option');
            
            planOptions.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                } else {
                    const planDepartment = option.getAttribute('data-department');
                    option.style.display = (!selectedDepartment || planDepartment === selectedDepartment) ? 'block' : 'none';
                }
            });
            
            // Reset plan selection if current selection is not visible
            if (planSelect.value && planSelect.selectedOptions[0].style.display === 'none') {
                planSelect.value = '';
            }
        });
    }

    console.log('✅ Plan Groups page initialized');
});
</script>
@endpush

@push('styles')
<style>
.badge {
    font-size: 0.75rem;
}

.table td {
    vertical-align: middle;
}

.card-header.bg-primary {
    background: linear-gradient(45deg, #007bff, #0056b3) !important;
}

.empty-state {
    padding: 2rem;
}

.form-select-sm, .btn-sm {
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .badge {
        font-size: 0.7rem;
    }
}
</style>
@endpush