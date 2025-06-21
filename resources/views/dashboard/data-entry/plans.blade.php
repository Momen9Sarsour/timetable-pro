@extends('dashboard.layout')

@section('content')
    <div class="main-content">
        <div class="data-entry-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="data-entry-header mb-0">Manage Academic Plans</h1>
                <div class="d-flex">
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addPlanModal">
                        <i class="fas fa-plus me-1"></i> Add New Plan
                    </button>
                    {{-- *** زر الرفع بالأكسل للخطط *** --}}
                    <button class="btn btn-outline-success me-2" data-bs-toggle="modal"
                        data-bs-target="#bulkUploadPlansModal">
                        <i class="fas fa-file-excel me-1"></i> Bulk Upload Plans
                    </button>
                </div>
            </div>

            @include('dashboard.data-entry.partials._status_messages')
            @if (session('skipped_details'))
                <div class="alert alert-warning mt-3">
                    <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Skipped Rows During Upload:</h5>
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
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Plan Code</th>
                                    <th scope="col" class="w-15 col-3">Plan Name</th>
                                    <th scope="col" class="w-20 col-1">Year</th>
                                    <th scope="col" class="w-5 col-3">Department</th>
                                    <th scope="col" class="w-5 col-1">Total Hours</th>
                                    <th scope="col" class="w-5 col-1">Total Subject</th>
                                    <th scope="col" class="w-5 col-1">Status</th>
                                    <th scope="col" class="w-5 col-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($plans as $index => $plan)
                                    <tr>
                                        <td>{{ $plans->firstItem() + $index }}</td>
                                        <td>{{ $plan->plan_no }}</td>
                                        <td>{{ $plan->plan_name }}</td>
                                        <td>{{ $plan->year }}</td>
                                        <td>{{ $plan->department->department_name ?? 'N/A' }}</td>
                                        <td>{{ $plan->plan_hours }}</td>
                                        <td>{{ $plan->planSubjectEntries()->count() }}</td>
                                        <td>
                                            @if ($plan->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{-- // زر إدارة المواد --}}
                                            <a href="{{ route('data-entry.plans.manageSubjects', $plan->id) }}"
                                                class="btn btn-sm btn-outline-info me-1" title="Manage Subjects">
                                                <i class="fas fa-tasks"></i> Subjects
                                            </a>
                                            {{-- // زر التعديل --}}
                                            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal"
                                                data-bs-target="#editPlanModal-{{ $plan->id }}"
                                                title="Edit Plan Details">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            {{-- // زر الحذف --}}
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                                data-bs-target="#deletePlanModal-{{ $plan->id }}" title="Delete Plan"
                                                {{ $plan->planSubjectEntries()->count() > 0 ? '' : '' }}>
                                                <i class="fas fa-trash"></i>
                                            </button>

                                            {{-- // تضمين Modals (للتعديل والحذف فقط) --}}
                                            @include('dashboard.data-entry.partials._plan_modals', [
                                                'plan' => $plan,
                                                'departments' => $departments,
                                            ])

                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No academic plans found. Click
                                            'Add New Plan' to create one.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $plans->links() }}
                    </div>
                </div>
            </div>

            {{-- // Modal لإضافة خطة جديدة --}}
            @include('dashboard.data-entry.partials._plan_modals', [
                'plan' => null,
                'departments' => $departments,
            ])

        </div>
    </div>
@endsection

@push('scripts')
    {{-- JS خاص بالصفحة إذا لزم الأمر --}}
@endpush
