@extends('dashboard.layout')

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="data-entry-header mb-0">Manage Crossover Methods</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCrossoverModal">
                <i class="fas fa-plus me-1"></i> Add New Crossover Method
            </button>
        </div>

        @include('dashboard.data-entry.partials._status_messages')

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($crossoverTypes as $index => $type)
                            <tr>
                                <td>{{ $crossoverTypes->firstItem() + $index }}</td>
                                <td><strong>{{ $type->name }}</strong></td>
                                <td><strong>{{ $type->slug }}</strong></td>
                                <td>{{ Str::limit($type->description, 80) }}</td>
                                <td>
                                    @if($type->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary py-0 px-1 me-1" data-bs-toggle="modal" data-bs-target="#editCrossoverModal-{{ $type->crossover_id }}">Edit</button>
                                    <button class="btn btn-sm btn-outline-danger py-0 px-1" data-bs-toggle="modal" data-bs-target="#deleteCrossoverModal-{{ $type->crossover_id }}">Delete</button>
                                    {{-- تضمين المودالات لكل صف --}}
                                    @include('dashboard.algorithm.partials._crossover_types_modals', ['type' => $type])
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted">No crossover methods found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex justify-content-center">
                    {{ $crossoverTypes->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
        {{-- مودال الإضافة العام --}}
        @include('dashboard.algorithm.partials._crossover_types_modals', ['type' => null])
    </div>
</div>
@endsection
