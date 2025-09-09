@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-project-diagram text-primary me-2"></i>
                        Manage Subject Categories
                    </h4>
                    <p class="text-muted mb-0">Create and manage subject classification categories</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSubjectCategoryModal">
                        <i class="fas fa-plus me-1"></i>
                        <span class="d-none d-sm-inline">Add New Category</span>
                    </button>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#bulkUploadSubjectCategoriesModal">
                        <i class="fas fa-file-excel me-1"></i>
                        <span class="d-none d-sm-inline">Bulk Upload</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    @if (session('skipped_details'))
        <div class="alert alert-warning d-flex align-items-start mb-4">
            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
            <div>
                <strong class="d-block mb-1">Skipped Rows During Upload:</strong>
                <ul class="mb-0 small" style="max-height: 200px; overflow-y: auto;">
                    @foreach (session('skipped_details') as $detail)
                        <li>{{ $detail }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Main Content Card -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list text-muted me-2"></i>
                            Subject Categories List
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $subjectCategories->total() }} Total</span>
                    </div>
                </div>

                <div class="card-body pt-3">
                    @if($subjectCategories->count() > 0)
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 text-center" style="width: 50px;">#</th>
                                        <th class="border-0">Category Name</th>
                                        <th class="border-0 text-center">Subject Count</th>
                                        <th class="border-0">Created At</th>
                                        <th class="border-0 text-center" style="width: 120px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subjectCategories as $index => $category)
                                        @php
                                            $subjectCount = $category->subjects()->count();
                                        @endphp
                                        <tr class="border-bottom">
                                            <td class="text-center text-muted">
                                                <small>{{ $subjectCategories->firstItem() + $index }}</small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="category-icon bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                        <i class="fas fa-folder"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-medium">{{ $category->subject_category_name }}</div>
                                                        <small class="text-muted">Category ID: {{ $category->id }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if($subjectCount > 0)
                                                    <span class="badge bg-info bg-opacity-10 text-info">
                                                        <i class="fas fa-book me-1"></i>{{ $subjectCount }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar-alt me-1"></i>
                                                    {{ $category->created_at->format('M d, Y') }}
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button class="btn btn-outline-primary btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editSubjectCategoryModal-{{ $category->id }}"
                                                            title="Edit Category">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteSubjectCategoryModal-{{ $category->id }}"
                                                            title="Delete Category"
                                                            @if($subjectCount > 0) disabled @endif>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="d-md-none">
                            @foreach ($subjectCategories as $index => $category)
                                @php
                                    $subjectCount = $category->subjects()->count();
                                @endphp
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <div class="category-icon bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <i class="fas fa-folder"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="card-title mb-1">{{ $category->subject_category_name }}</h6>
                                                        @if($subjectCount > 0)
                                                            <span class="badge bg-info bg-opacity-10 text-info">
                                                                <i class="fas fa-book me-1"></i>{{ $subjectCount }} subjects
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editSubjectCategoryModal-{{ $category->id }}">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </button>
                                                    </li>
                                                    @if($subjectCount == 0)
                                                        <li>
                                                            <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteSubjectCategoryModal-{{ $category->id }}">
                                                                <i class="fas fa-trash me-2"></i>Delete
                                                            </button>
                                                        </li>
                                                    @else
                                                        <li>
                                                            <span class="dropdown-item-text text-muted">
                                                                <i class="fas fa-lock me-2"></i>Cannot delete (has subjects)
                                                            </span>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted small">
                                            <span>
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                {{ $category->created_at->format('M d, Y') }}
                                            </span>
                                            <span class="text-muted">#{{ $subjectCategories->firstItem() + $index }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($subjectCategories->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $subjectCategories->links() }}
                            </div>
                        @endif

                    @else
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-project-diagram text-muted opacity-50" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 text-muted">No Subject Categories Found</h5>
                                <p class="text-muted mb-4">Start by adding categories to organize your subjects into logical groups.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectCategoryModal">
                                    <i class="fas fa-plus me-2"></i>Add First Category
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals for each category -->
    @foreach($subjectCategories as $subjectCategory)
        @include('dashboard.data-entry.partials._subject_category_modals', ['subjectCategory' => $subjectCategory])
    @endforeach

    <!-- Include Add Category Modal -->
    @include('dashboard.data-entry.partials._subject_category_modals', ['subjectCategory' => null])
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Auto-capitalize category names
    const categoryNameInputs = document.querySelectorAll('input[name="subject_category_name"]');
    categoryNameInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Auto-capitalize first letter of each word
            this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
        });
    });
});
</script>
@endpush
