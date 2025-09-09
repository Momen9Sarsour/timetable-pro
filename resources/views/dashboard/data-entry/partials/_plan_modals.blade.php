{{-- Add Academic Plan Modal --}}
@if (!$plan)
<div class="modal fade" id="addPlanModal" tabindex="-1" aria-labelledby="addPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="addPlanModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>
                    Add New Academic Plan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.plans.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="add_plan_no" class="form-label fw-medium">
                                <i class="fas fa-hashtag text-muted me-1"></i>
                                Plan Code <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('plan_no', 'store') is-invalid @enderror"
                                   id="add_plan_no"
                                   name="plan_no"
                                   value="{{ old('plan_no') }}"
                                   placeholder="e.g., CS2024, IT2023"
                                   required>
                            @error('plan_no', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Enter a unique code for this plan</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="add_plan_name" class="form-label fw-medium">
                                <i class="fas fa-clipboard-list text-muted me-1"></i>
                                Plan Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('plan_name', 'store') is-invalid @enderror"
                                   id="add_plan_name"
                                   name="plan_name"
                                   value="{{ old('plan_name') }}"
                                   placeholder="e.g., Computer Science Plan 2024"
                                   required>
                            @error('plan_name', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Enter the full name of the academic plan</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="add_year" class="form-label fw-medium">
                                <i class="fas fa-calendar text-muted me-1"></i>
                                Adoption Year <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('year', 'store') is-invalid @enderror"
                                   id="add_year"
                                   name="year"
                                   value="{{ old('year', date('Y')) }}"
                                   placeholder="YYYY"
                                   min="2000"
                                   max="{{ date('Y') + 5 }}"
                                   required>
                            @error('year', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Year when this plan was adopted</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="add_plan_hours" class="form-label fw-medium">
                                <i class="fas fa-clock text-muted me-1"></i>
                                Total Credit Hours <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('plan_hours', 'store') is-invalid @enderror"
                                   id="add_plan_hours"
                                   name="plan_hours"
                                   value="{{ old('plan_hours') }}"
                                   placeholder="e.g., 132"
                                   min="1"
                                   required>
                            @error('plan_hours', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Total credit hours for graduation</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="add_department_id" class="form-label fw-medium">
                                <i class="fas fa-building text-muted me-1"></i>
                                Associated Department <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('department_id', 'store') is-invalid @enderror"
                                    id="add_department_id"
                                    name="department_id"
                                    required>
                                <option value="" selected disabled>Select department...</option>
                                @if (isset($departments))
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}"
                                                {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->department_name }} ({{ $dept->department_no }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('department_id', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Select the department this plan belongs to</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="add_is_active"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="add_is_active">
                                    <i class="fas fa-toggle-on text-success me-1"></i>
                                    Mark plan as active
                                </label>
                                <div class="form-text">Active plans are available for student enrollment</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Edit Academic Plan Modal --}}
@if ($plan)
<div class="modal fade" id="editPlanModal-{{ $plan->id }}" tabindex="-1" aria-labelledby="editPlanModalLabel-{{ $plan->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title d-flex align-items-center" id="editPlanModalLabel-{{ $plan->id }}">
                    <i class="fas fa-edit me-2"></i>
                    Edit Academic Plan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.plans.update', $plan->id) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <!-- Plan Info Card -->
                    <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Editing: <strong>{{ $plan->plan_name }}</strong> ({{ $plan->plan_no }})</small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_plan_no_{{ $plan->id }}" class="form-label fw-medium">
                                <i class="fas fa-hashtag text-muted me-1"></i>
                                Plan Code <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('plan_no', 'update_' . $plan->id) is-invalid @enderror"
                                   id="edit_plan_no_{{ $plan->id }}"
                                   name="plan_no"
                                   value="{{ old('plan_no', $plan->plan_no) }}"
                                   required>
                            @error('plan_no', 'update_' . $plan->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="edit_plan_name_{{ $plan->id }}" class="form-label fw-medium">
                                <i class="fas fa-clipboard-list text-muted me-1"></i>
                                Plan Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('plan_name', 'update_' . $plan->id) is-invalid @enderror"
                                   id="edit_plan_name_{{ $plan->id }}"
                                   name="plan_name"
                                   value="{{ old('plan_name', $plan->plan_name) }}"
                                   required>
                            @error('plan_name', 'update_' . $plan->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="edit_year_{{ $plan->id }}" class="form-label fw-medium">
                                <i class="fas fa-calendar text-muted me-1"></i>
                                Adoption Year <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('year', 'update_' . $plan->id) is-invalid @enderror"
                                   id="edit_year_{{ $plan->id }}"
                                   name="year"
                                   value="{{ old('year', $plan->year) }}"
                                   placeholder="YYYY"
                                   min="2000"
                                   max="{{ date('Y') + 5 }}"
                                   required>
                            @error('year', 'update_' . $plan->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="edit_plan_hours_{{ $plan->id }}" class="form-label fw-medium">
                                <i class="fas fa-clock text-muted me-1"></i>
                                Total Credit Hours <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('plan_hours', 'update_' . $plan->id) is-invalid @enderror"
                                   id="edit_plan_hours_{{ $plan->id }}"
                                   name="plan_hours"
                                   value="{{ old('plan_hours', $plan->plan_hours) }}"
                                   min="1"
                                   required>
                            @error('plan_hours', 'update_' . $plan->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="edit_department_id_{{ $plan->id }}" class="form-label fw-medium">
                                <i class="fas fa-building text-muted me-1"></i>
                                Associated Department <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('department_id', 'update_' . $plan->id) is-invalid @enderror"
                                    id="edit_department_id_{{ $plan->id }}"
                                    name="department_id"
                                    required>
                                <option value="" disabled>Select department...</option>
                                @if (isset($departments))
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}"
                                                {{ old('department_id', $plan->department_id) == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->department_name }} ({{ $dept->department_no }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('department_id', 'update_' . $plan->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="edit_is_active_{{ $plan->id }}"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="edit_is_active_{{ $plan->id }}">
                                    <i class="fas fa-toggle-on text-success me-1"></i>
                                    Mark plan as active
                                </label>
                            </div>
                        </div>

                        {{-- Plan Statistics --}}
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-body p-3">
                                    <h6 class="card-title text-muted mb-2">
                                        <i class="fas fa-chart-line me-1"></i>Plan Statistics
                                    </h6>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="text-primary fw-bold fs-5">{{ $plan->planSubjectEntries()->count() }}</div>
                                            <small class="text-muted">Subjects</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-success fw-bold fs-5">{{ $plan->plan_hours }}</div>
                                            <small class="text-muted">Hours</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-info fw-bold fs-5">{{ $plan->year }}</div>
                                            <small class="text-muted">Year</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-sync-alt me-1"></i>Update Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deletePlanModal-{{ $plan->id }}" tabindex="-1" aria-labelledby="deletePlanModalLabel-{{ $plan->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="deletePlanModalLabel-{{ $plan->id }}">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.plans.destroy', $plan->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-clipboard-list text-danger" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>

                    @if ($plan->planSubjectEntries()->count() > 0)
                        <div class="alert alert-danger d-flex align-items-start" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <h6 class="alert-heading mb-1">Warning: Plan has associated subjects!</h6>
                                <p class="mb-0 small">This plan contains {{ $plan->planSubjectEntries()->count() }} subject(s). Deleting it will also remove all subject associations.</p>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-danger d-flex align-items-start" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <h6 class="alert-heading mb-1">Are you sure?</h6>
                                <p class="mb-0 small">You are about to delete this academic plan:</p>
                            </div>
                        </div>
                    @endif

                    <div class="card bg-light border-danger">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <h6 class="card-title mb-1">{{ $plan->plan_name }}</h6>
                                    <small class="text-muted">Code: {{ $plan->plan_no }} | Year: {{ $plan->year }}</small>
                                </div>
                                <span class="badge bg-danger">Will be deleted</span>
                            </div>
                            <div class="row text-muted small">
                                <div class="col-6">
                                    <i class="fas fa-building me-1"></i>{{ $plan->department->department_name ?? 'N/A' }}
                                </div>
                                <div class="col-6">
                                    <i class="fas fa-clock me-1"></i>{{ $plan->plan_hours }} Hours
                                </div>
                            </div>
                            @if ($plan->planSubjectEntries()->count() > 0)
                                <div class="mt-2">
                                    <span class="badge bg-warning text-dark">{{ $plan->planSubjectEntries()->count() }} subjects will be unlinked</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <small><strong>Note:</strong> This action cannot be undone.
                                @if ($plan->planSubjectEntries()->count() > 0)
                                    Subject associations will be removed but the subjects themselves remain in the system.
                                @else
                                    Make sure this plan is not referenced elsewhere.
                                @endif
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Yes, Delete Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Bulk Upload Modal --}}
<div class="modal fade" id="bulkUploadPlansModal" tabindex="-1" aria-labelledby="bulkUploadPlansModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="bulkUploadPlansModalLabel">
                    <i class="fas fa-file-excel me-2"></i>
                    Bulk Upload Academic Plans
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.plans.bulkUpload') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-4">
                        <!-- File Upload Section -->
                        <div class="col-12">
                            <label for="plan_excel_file" class="form-label fw-medium">
                                <i class="fas fa-cloud-upload-alt text-muted me-1"></i>
                                Select Excel File <span class="text-danger">*</span>
                            </label>
                            <div class="upload-zone border border-2 border-dashed rounded-3 p-4 text-center cursor-pointer">
                                <i class="fas fa-file-excel text-success mb-2" style="font-size: 2rem;"></i>
                                <input class="form-control @error('plan_excel_file', 'bulkUploadPlans') is-invalid @enderror"
                                       type="file"
                                       id="plan_excel_file"
                                       name="plan_excel_file"
                                       accept=".xlsx,.xls,.csv"
                                       required>
                                @error('plan_excel_file', 'bulkUploadPlans')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text mt-2">
                                    <small>Supported formats: .xlsx, .xls, .csv (Max: 5MB)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Instructions Section -->
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-header bg-transparent border-0 pb-0">
                                    <h6 class="card-title text-primary mb-0">
                                        <i class="fas fa-info-circle me-1"></i>
                                        File Format Instructions
                                    </h6>
                                </div>
                                <div class="card-body pt-2">
                                    <ul class="list-unstyled mb-0 small">
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                            <span>Required columns: <code>plan_no</code>, <code>plan_name</code>, <code>year</code>, <code>plan_hours</code>, <code>department_id</code></span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1 flex-shrink-0"></i>
                                            <span>Optional columns: <code>is_active</code> (1 for active, 0 for inactive)</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-info-circle text-info me-2 mt-1 flex-shrink-0"></i>
                                            <span>Department ID can be: Department ID number, Department Name, or Department Code</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fas fa-sync-alt text-warning me-2 mt-1 flex-shrink-0"></i>
                                            <span>Existing plans (same plan_no) will be updated, new ones will be created</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <i class="fas fa-filter text-secondary me-2 mt-1 flex-shrink-0"></i>
                                            <span>Empty rows or rows missing required fields will be skipped</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Sample Format -->
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-header bg-primary bg-opacity-10 border-primary">
                                    <h6 class="card-title text-primary mb-0">
                                        <i class="fas fa-table me-1"></i>
                                        Sample Format
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th style="font-size: 0.75rem;">plan_no</th>
                                                    <th style="font-size: 0.75rem;">plan_name</th>
                                                    <th style="font-size: 0.75rem;">year</th>
                                                    <th style="font-size: 0.75rem;">plan_hours</th>
                                                    <th style="font-size: 0.75rem;">department_id</th>
                                                    <th style="font-size: 0.75rem;">is_active</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><code class="small">CS2024</code></td>
                                                    <td class="small">Computer Science Plan 2024</td>
                                                    <td class="small">2024</td>
                                                    <td class="small">132</td>
                                                    <td class="small">Computer Science</td>
                                                    <td class="small">1</td>
                                                </tr>
                                                <tr>
                                                    <td><code class="small">IT2023</code></td>
                                                    <td class="small">Information Technology Plan</td>
                                                    <td class="small">2023</td>
                                                    <td class="small">128</td>
                                                    <td class="small">IT02</td>
                                                    <td class="small">1</td>
                                                </tr>
                                                <tr>
                                                    <td><code class="small">EE2024</code></td>
                                                    <td class="small">Electrical Engineering Plan</td>
                                                    <td class="small">2024</td>
                                                    <td class="small">140</td>
                                                    <td class="small">3</td>
                                                    <td class="small">0</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i>Upload and Process
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Upload Zone Styles */
.upload-zone {
    border-color: #dee2e6 !important;
    transition: all 0.15s ease;
    cursor: pointer;
}

.upload-zone:hover {
    border-color: var(--primary-color) !important;
    background-color: rgba(59, 130, 246, 0.05);
}

.upload-zone input[type="file"] {
    border: none;
    background: transparent;
    padding: 0.5rem 0;
}

/* Modal Responsive */
@media (max-width: 768px) {
    .modal-lg {
        max-width: 95%;
    }

    .modal-body {
        max-height: 60vh !important;
        padding: 1rem !important;
    }

    .upload-zone {
        padding: 2rem 1rem !important;
    }

    .upload-zone i {
        font-size: 1.5rem !important;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
    }

    .btn-group-vertical .btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload enhancement for plans
    const fileInput = document.getElementById('plan_excel_file');
    const uploadZone = document.querySelector('.upload-zone');

    if (fileInput && uploadZone) {
        // Click to open file dialog
        uploadZone.addEventListener('click', function() {
            fileInput.click();
        });

        // File change handler
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const icon = uploadZone.querySelector('i');
                const text = uploadZone.querySelector('.form-text');

                if (icon) {
                    icon.className = 'fas fa-file-check text-success mb-2';
                    icon.style.fontSize = '2rem';
                }

                if (text) {
                    text.innerHTML = `<small class="text-success fw-medium">✓ Selected: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</small>`;
                }
            }
        });

        // Drag & Drop functionality
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#3b82f6';
            this.style.backgroundColor = 'rgba(59, 130, 246, 0.05)';
        });

        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.backgroundColor = '';
        });

        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.backgroundColor = '';

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                const event = new Event('change', { bubbles: true });
                fileInput.dispatchEvent(event);
            }
        });
    }
});
</script>
