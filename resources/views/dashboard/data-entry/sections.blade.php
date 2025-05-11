@extends('dashboard.layout')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .select2-container--bootstrap-5 .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; font-size: 0.875rem; }
</style>
@endpush

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h1 class="data-entry-header mb-0">View All Sections</h1>
             {{-- لا زر إضافة عام هنا --}}
        </div>

        @include('dashboard.data-entry.partials._status_messages')

        {{-- قسم الفلاتر --}}
                {{-- قسم الفلاتر --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form action="{{ route('data-entry.sections.index') }}" method="GET" class="row g-3 align-items-end">
                            {{-- فلتر السنة --}}
                            <div class="col-md-2">
                                <label for="filter_academic_year" class="form-label form-label-sm">Year</label>
                                <select class="form-select form-select-sm" id="filter_academic_year" name="academic_year">
                                    <option value="">All</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year }}" {{ $request->academic_year == $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                             {{-- فلتر الفصل --}}
                             <div class="col-md-2">
                                 <label for="filter_semester" class="form-label form-label-sm">Semester</label>
                                 <select class="form-select form-select-sm" id="filter_semester" name="semester">
                                     <option value="">All</option>
                                      @foreach($semesters as $key => $value)
                                         <option value="{{ $key }}" {{ $request->semester == $key ? 'selected' : '' }}>{{ $value }}</option>
                                     @endforeach
                                 </select>
                             </div>
                             {{-- فلتر القسم --}}
                            <div class="col-md-3">
                                 <label for="filter_department_id" class="form-label form-label-sm">Department</label>
                                 <select class="form-select form-select-sm select2-filter" id="filter_department_id" name="department_id" data-placeholder="All Departments">
                                     <option value=""></option>
                                     @foreach($departments as $department)
                                         <option value="{{ $department->id }}" {{ $request->department_id == $department->id ? 'selected' : '' }}>{{ $department->department_name }}</option>
                                     @endforeach
                                 </select>
                            </div>
                            {{-- فلتر الخطة (يمكن تحديثه بـ AJAX بناءً على القسم) --}}
                             <div class="col-md-3">
                                 <label for="filter_plan_id" class="form-label form-label-sm">Plan</label>
                                 <select class="form-select form-select-sm select2-filter" id="filter_plan_id" name="plan_id" data-placeholder="All Plans">
                                      <option value=""></option>
                                      @foreach($plans as $plan)
                                         <option value="{{ $plan->id }}" {{ $request->plan_id == $plan->id ? 'selected' : '' }}>{{ $plan->plan_no }} - {{ $plan->plan_name }}</option>
                                      @endforeach
                                 </select>
                             </div>
                             {{-- فلتر المستوى --}}
                             <div class="col-md-1">
                                  <label for="filter_plan_level" class="form-label form-label-sm">Level</label>
                                  <select class="form-select form-select-sm" id="filter_plan_level" name="plan_level">
                                      <option value="">All</option>
                                       @foreach($levels as $level)
                                          <option value="{{ $level }}" {{ $request->plan_level == $level ? 'selected' : '' }}>{{ $level }}</option>
                                      @endforeach
                                  </select>
                              </div>
                               {{-- زر تطبيق الفلاتر --}}
                              <div class="col-md-1">
                                  <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                              </div>

                              {{-- يمكن إضافة فلاتر أخرى (المادة، الفرع) هنا --}}

                        </form>
                    </div>
                </div>

        {{-- جدول عرض الشعب --}}
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Year</th>
                                <th>Term</th>
                                <th>Plan</th>
                                <th>Level</th>
                                <th>Subject</th>
                                <th>Sec #</th>
                                <th>Gender</th>
                                <th>Branch</th>
                                <th>Count</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sections as $index => $section)
                            <tr>
                                <td>{{ $sections->firstItem() + $index }}</td>
                                <td>{{ $section->academic_year }}</td>
                                <td>{{ $section->semester }}</td>
                                <td title="{{ optional(optional($section->planSubject)->plan)->plan_name ?? '' }}">
                                    {{ optional(optional($section->planSubject)->plan)->plan_no ?? 'N/A' }}
                                </td>
                                <td>{{ optional($section->planSubject)->plan_level ?? 'N/A' }}</td>
                                <td title="">
                                     {{ optional(optional($section->planSubject)->subject)->subject_no ?? 'N/A' }} -
                                     {{ optional(optional($section->planSubject)->subject)->subject_name ?? '' }}
                                </td>
                                <td>{{ $section->section_number }}</td>
                                <td>{{ $section->section_gender }}</td>
                                <td>{{ $section->branch ?? '-' }}</td>
                                <td>{{ $section->student_count }}</td>
                                <td>
                                    {{-- زر الانتقال لصفحة التحكم التفصيلي --}}
                                    <a href="{{ route('data-entry.sections.manage', [
                                                'plan_id' => optional(optional($section->planSubject)->plan)->id, // **تمرير plan_id**
                                                'plan_level' => optional($section->planSubject)->plan_level,
                                                'plan_semester' => optional($section->planSubject)->plan_semester, // فصل الخطة
                                                'academic_year' => $section->academic_year,
                                                'semester_of_sections' => $section->semester, // فصل الشعبة
                                                'branch' => $section->branch
                                            ]) }}"
                                       class="btn btn-sm btn-outline-info"
                                       title="Manage sections for this plan/level/semester/year context">
                                        <i class="fas fa-cog"></i> Manage
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted">No sections match the current filters. Try adjusting filters or add expected counts to generate sections.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                 <div class="mt-3 d-flex justify-content-center">
                     {{ $sections->appends(request()->query())->links('pagination::bootstrap-5') }}
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
        $('.select2-filter').select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: $(this).data('placeholder') || 'Select...',
            //width: 'resolve' // or '100%'
        });
    });
</script>
@endpush
