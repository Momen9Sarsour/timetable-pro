@extends('dashboard.layout')

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h1 class="data-entry-header mb-0">Manage Academic Plans</h1>
             <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPlanModal">
                 <i class="fas fa-plus me-1"></i> Add New Plan
             </button>
        </div>

        @include('dashboard.data-entry.partials._status_messages')

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Plan Code</th>
                                <th scope="col">Plan Name</th>
                                <th scope="col">Year</th>
                                <th scope="col">Department</th>
                                <th scope="col">Total Hours</th>
                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
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
                                <td>
                                    @if($plan->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    {{-- // زر إدارة المواد --}}
                                    <a href="{{ route('data-entry.plans.manageSubjects', $plan->id) }}" class="btn btn-sm btn-outline-info me-1" title="Manage Subjects">
                                        <i class="fas fa-tasks"></i> Subjects
                                    </a>
                                    {{-- // زر التعديل --}}
                                    <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editPlanModal-{{ $plan->id }}" title="Edit Plan Details">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    {{-- // زر الحذف --}}
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deletePlanModal-{{ $plan->id }}" title="Delete Plan" {{ $plan->planSubjectEntries()->count() > 0 ? 'disabled' : '' }}>
                                        <i class="fas fa-trash"></i>
                                    </button>

                                    {{-- // تضمين Modals (للتعديل والحذف فقط) --}}
                                    @include('dashboard.data-entry.partials._plan_modals', ['plan' => $plan, 'departments' => $departments])

                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No academic plans found. Click 'Add New Plan' to create one.</td>
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
         @include('dashboard.data-entry.partials._plan_modals', ['plan' => null, 'departments' => $departments])

    </div>
</div>
@endsection

@push('scripts')
{{-- JS خاص بالصفحة إذا لزم الأمر --}}
@endpush
