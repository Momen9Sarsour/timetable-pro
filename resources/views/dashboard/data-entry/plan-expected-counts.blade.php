@extends('dashboard.layout')

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h1 class="data-entry-header mb-0">Manage Expected Student Counts</h1>
             <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCountModal">
                 <i class="fas fa-plus me-1"></i> Add Expected Count
             </button>
        </div>

        @include('dashboard.data-entry.partials._status_messages')

        {{-- TODO: Add Filters (by Academic Year, Plan, Department?) --}}
        {{-- <div class="card shadow-sm mb-3"> <div class="card-body"> ... Filters ... </div> </div> --}}


        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Academic Year</th>
                                <th scope="col">Plan</th>
                                <th scope="col">Level</th>
                                <th scope="col">Semester</th>
                                <th scope="col">Branch</th>
                                <th scope="col">Male Count</th>
                                <th scope="col">Female Count</th>
                                <th scope="col">Total</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expectedCounts as $index => $count)
                            <tr>
                                <td>{{ $expectedCounts->firstItem() + $index }}</td>
                                <td>{{ $count->academic_year }}</td>
                                <td>
                                    <span title="{{ optional($count->plan)->plan_name }}">
                                        {{ optional($count->plan)->plan_no ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>{{ $count->plan_level }}</td>
                                <td>{{ $count->plan_semester }}</td>
                                <td>{{ $count->branch ?? '-' }}</td>
                                <td>{{ $count->male_count }}</td>
                                <td>{{ $count->female_count }}</td>
                                <td>{{ $count->male_count + $count->female_count }}</td>
                                <td>
                                    {{-- زر التعديل --}}
                                    <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editCountModal-{{ $count->id }}" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    {{-- زر الحذف --}}
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteCountModal-{{ $count->id }}" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                    {{-- تضمين Modals --}}
                                    @include('dashboard.data-entry.partials._plan_expected_count_modals', ['count' => $count, 'plans' => $plans])

                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">No expected counts found. Click 'Add Expected Count' to create one.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                 <div class="mt-3 d-flex justify-content-center">
                     {{ $expectedCounts->links('pagination::bootstrap-5') }}
                 </div>
            </div>
        </div>

         {{-- مودال الإضافة --}}
         @include('dashboard.data-entry.partials._plan_expected_count_modals', ['count' => null, 'plans' => $plans])

    </div>
</div>
@endsection

@push('scripts')
{{-- JS خاص بالصفحة إذا لزم الأمر (مثلاً للـ Filters) --}}
@endpush
