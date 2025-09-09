{{-- Add Expected Count Modal --}}
@if (!$count)
<div class="modal fade" id="addCountModal" tabindex="-1" aria-labelledby="addCountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="addCountModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>
                    Add New Expected Count
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.plan-expected-counts.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="add_academic_year" class="form-label fw-medium">
                                <i class="fas fa-calendar-alt text-muted me-1"></i>
                                Academic Year <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('academic_year', 'store') is-invalid @enderror"
                                   id="add_academic_year"
                                   name="academic_year"
                                   value="{{ old('academic_year', date('Y')) }}"
                                   placeholder="YYYY"
                                   min="2020"
                                   max="{{ date('Y') + 5 }}"
                                   required>
                            @error('academic_year', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Enter the academic year for this count</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="add_plan_id" class="form-label fw-medium">
                                <i class="fas fa-clipboard-list text-muted me-1"></i>
                                Academic Plan <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('plan_id', 'store') is-invalid @enderror"
                                    id="add_plan_id"
                                    name="plan_id"
                                    required>
                                <option value="" selected disabled>Select plan...</option>
                                @if(isset($plans))
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}"
                                                {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                            {{ $plan->plan_name }} ({{ $plan->plan_no }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('plan_id', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Select the academic plan</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="add_plan_level" class="form-label fw-medium">
                                <i class="fas fa-layer-group text-muted me-1"></i>
                                Level <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('plan_level', 'store') is-invalid @enderror"
                                    id="add_plan_level"
                                    name="plan_level"
                                    required>
                                <option value="" selected disabled>Select level...</option>
                                <option value="1" {{ old('plan_level') == '1' ? 'selected' : '' }}>Level 1 (First Year)</option>
                                <option value="2" {{ old('plan_level') == '2' ? 'selected' : '' }}>Level 2 (Second Year)</option>
                                <option value="3" {{ old('plan_level') == '3' ? 'selected' : '' }}>Level 3 (Third Year)</option>
                                <option value="4" {{ old('plan_level') == '4' ? 'selected' : '' }}>Level 4 (Fourth Year)</option>
                                <option value="5" {{ old('plan_level') == '5' ? 'selected' : '' }}>Level 5 (Fifth Year)</option>
                                {{-- <option value="6" {{ old('plan_level') == '6' ? 'selected' : '' }}>Level 6 (Sixth Year)</option> --}}
                            </select>
                            @error('plan_level', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Select the academic level</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="add_plan_semester" class="form-label fw-medium">
                                <i class="fas fa-calendar text-muted me-1"></i>
                                Semester <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('plan_semester', 'store') is-invalid @enderror"
                                    id="add_plan_semester"
                                    name="plan_semester"
                                    required>
                                <option value="" selected disabled>Select semester...</option>
                                <option value="1" {{ old('plan_semester') == '1' ? 'selected' : '' }}>Semester 1 (Fall)</option>
                                <option value="2" {{ old('plan_semester') == '2' ? 'selected' : '' }}>Semester 2 (Spring)</option>
                                <option value="3" {{ old('plan_semester') == '3' ? 'selected' : '' }}>Semester 3 (Summer)</option>
                            </select>
                            @error('plan_semester', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Select the semester</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="add_male_count" class="form-label fw-medium">
                                <i class="fas fa-male text-primary me-1"></i>
                                Male Count <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('male_count', 'store') is-invalid @enderror"
                                   id="add_male_count"
                                   name="male_count"
                                   value="{{ old('male_count', 0) }}"
                                   min="0"
                                   required>
                            @error('male_count', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <label for="add_female_count" class="form-label fw-medium">
                                <i class="fas fa-female text-danger me-1"></i>
                                Female Count <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('female_count', 'store') is-invalid @enderror"
                                   id="add_female_count"
                                   name="female_count"
                                   value="{{ old('female_count', 0) }}"
                                   min="0"
                                   required>
                            @error('female_count', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <label for="add_branch" class="form-label fw-medium">
                                <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                Branch
                            </label>
                            <input type="text"
                                   class="form-control @error('branch', 'store') is-invalid @enderror"
                                   id="add_branch"
                                   name="branch"
                                   value="{{ old('branch') }}"
                                   placeholder="e.g., Main, North">
                            @error('branch', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Optional branch identifier</div>
                            @enderror
                        </div>

                        <!-- Total Display -->
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-body p-3">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="text-primary fw-bold" id="maleDisplay">0</div>
                                            <small class="text-muted">Male Students</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-danger fw-bold" id="femaleDisplay">0</div>
                                            <small class="text-muted">Female Students</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-dark fw-bold fs-5" id="totalDisplay">0</div>
                                            <small class="text-muted">Total Expected</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @error('count_unique', 'store')
                            <div class="col-12">
                                <div class="alert alert-danger d-flex align-items-center" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    {{ $message }}
                                </div>
                            </div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Count
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Edit Expected Count Modal --}}
@if ($count)
<div class="modal fade" id="editCountModal-{{ $count->id }}" tabindex="-1" aria-labelledby="editCountModalLabel-{{ $count->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title d-flex align-items-center" id="editCountModalLabel-{{ $count->id }}">
                    <i class="fas fa-edit me-2"></i>
                    Edit Expected Count
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.plan-expected-counts.update', $count->id) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <!-- Count Info Card -->
                    <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Editing count for: <strong>{{ optional($count->plan)->plan_name ?? 'N/A' }}</strong> - {{ $count->academic_year }}</small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_academic_year_{{ $count->id }}" class="form-label fw-medium">
                                <i class="fas fa-calendar-alt text-muted me-1"></i>
                                Academic Year <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('academic_year', 'update_'.$count->id) is-invalid @enderror"
                                   id="edit_academic_year_{{ $count->id }}"
                                   name="academic_year"
                                   value="{{ old('academic_year', $count->academic_year) }}"
                                   placeholder="YYYY"
                                   min="2020"
                                   max="{{ date('Y') + 5 }}"
                                   required>
                            @error('academic_year', 'update_'.$count->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="edit_plan_id_{{ $count->id }}" class="form-label fw-medium">
                                <i class="fas fa-clipboard-list text-muted me-1"></i>
                                Academic Plan <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('plan_id', 'update_'.$count->id) is-invalid @enderror"
                                    id="edit_plan_id_{{ $count->id }}"
                                    name="plan_id"
                                    required>
                                <option value="" disabled>Select plan...</option>
                                @if(isset($plans))
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}"
                                                {{ old('plan_id', $count->plan_id) == $plan->id ? 'selected' : '' }}>
                                            {{ $plan->plan_name }} ({{ $plan->plan_no }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('plan_id', 'update_'.$count->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="edit_plan_level_{{ $count->id }}" class="form-label fw-medium">
                                <i class="fas fa-layer-group text-muted me-1"></i>
                                Level <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('plan_level', 'update_'.$count->id) is-invalid @enderror"
                                    id="edit_plan_level_{{ $count->id }}"
                                    name="plan_level"
                                    required>
                                <option value="" disabled>Select level...</option>
                                <option value="1" {{ old('plan_level', $count->plan_level) == '1' ? 'selected' : '' }}>Level 1 (First Year)</option>
                                <option value="2" {{ old('plan_level', $count->plan_level) == '2' ? 'selected' : '' }}>Level 2 (Second Year)</option>
                                <option value="3" {{ old('plan_level', $count->plan_level) == '3' ? 'selected' : '' }}>Level 3 (Third Year)</option>
                                <option value="4" {{ old('plan_level', $count->plan_level) == '4' ? 'selected' : '' }}>Level 4 (Fourth Year)</option>
                                <option value="5" {{ old('plan_level', $count->plan_level) == '5' ? 'selected' : '' }}>Level 5 (Fifth Year)</option>
                                {{-- <option value="6" {{ old('plan_level', $count->plan_level) == '6' ? 'selected' : '' }}>Level 6 (Sixth Year)</option> --}}
                            </select>
                            @error('plan_level', 'update_'.$count->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="edit_plan_semester_{{ $count->id }}" class="form-label fw-medium">
                                <i class="fas fa-calendar text-muted me-1"></i>
                                Semester <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('plan_semester', 'update_'.$count->id) is-invalid @enderror"
                                    id="edit_plan_semester_{{ $count->id }}"
                                    name="plan_semester"
                                    required>
                                <option value="" disabled>Select semester...</option>
                                <option value="1" {{ old('plan_semester', $count->plan_semester) == '1' ? 'selected' : '' }}>Semester 1 (Fall)</option>
                                <option value="2" {{ old('plan_semester', $count->plan_semester) == '2' ? 'selected' : '' }}>Semester 2 (Spring)</option>
                                <option value="3" {{ old('plan_semester', $count->plan_semester) == '3' ? 'selected' : '' }}>Semester 3 (Summer)</option>
                            </select>
                            @error('plan_semester', 'update_'.$count->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="edit_male_count_{{ $count->id }}" class="form-label fw-medium">
                                <i class="fas fa-male text-primary me-1"></i>
                                Male Count <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('male_count', 'update_'.$count->id) is-invalid @enderror"
                                   id="edit_male_count_{{ $count->id }}"
                                   name="male_count"
                                   value="{{ old('male_count', $count->male_count) }}"
                                   min="0"
                                   required>
                            @error('male_count', 'update_'.$count->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="edit_female_count_{{ $count->id }}" class="form-label fw-medium">
                                <i class="fas fa-female text-danger me-1"></i>
                                Female Count <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('female_count', 'update_'.$count->id) is-invalid @enderror"
                                   id="edit_female_count_{{ $count->id }}"
                                   name="female_count"
                                   value="{{ old('female_count', $count->female_count) }}"
                                   min="0"
                                   required>
                            @error('female_count', 'update_'.$count->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="edit_branch_{{ $count->id }}" class="form-label fw-medium">
                                <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                Branch
                            </label>
                            <input type="text"
                                   class="form-control @error('branch', 'update_'.$count->id) is-invalid @enderror"
                                   id="edit_branch_{{ $count->id }}"
                                   name="branch"
                                   value="{{ old('branch', $count->branch) }}"
                                   placeholder="e.g., Main, North">
                            @error('branch', 'update_'.$count->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Current Statistics -->
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-body p-3">
                                    <h6 class="card-title text-muted mb-2">
                                        <i class="fas fa-chart-line me-1"></i>Current Count
                                    </h6>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="text-primary fw-bold fs-5">{{ $count->male_count }}</div>
                                            <small class="text-muted">Male Students</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-danger fw-bold fs-5">{{ $count->female_count }}</div>
                                            <small class="text-muted">Female Students</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-dark fw-bold fs-5">{{ $count->male_count + $count->female_count }}</div>
                                            <small class="text-muted">Total Expected</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @error('count_unique', 'update_'.$count->id)
                            <div class="col-12">
                                <div class="alert alert-danger d-flex align-items-center" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    {{ $message }}
                                </div>
                            </div>
                        @enderror

                        @error('update_error', 'update_'.$count->id)
                            <div class="col-12">
                                <div class="alert alert-danger d-flex align-items-center" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    {{ $message }}
                                </div>
                            </div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-sync-alt me-1"></i>Update Count
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteCountModal-{{ $count->id }}" tabindex="-1" aria-labelledby="deleteCountModalLabel-{{ $count->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="deleteCountModalLabel-{{ $count->id }}">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.plan-expected-counts.destroy', $count->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-users text-danger" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>

                    <div class="alert alert-danger d-flex align-items-start" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Are you sure?</h6>
                            <p class="mb-0 small">You are about to delete this expected count record:</p>
                        </div>
                    </div>

                    <div class="card bg-light border-danger">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <h6 class="card-title mb-1">{{ optional($count->plan)->plan_name ?? 'N/A' }}</h6>
                                    <small class="text-muted">
                                        Plan: {{ optional($count->plan)->plan_no ?? 'N/A' }} |
                                        Year: {{ $count->academic_year }}
                                    </small>
                                </div>
                                <span class="badge bg-danger">Will be deleted</span>
                            </div>
                            <div class="row text-muted small">
                                <div class="col-6">
                                    <i class="fas fa-layer-group me-1"></i>Level {{ $count->plan_level }}
                                </div>
                                <div class="col-6">
                                    <i class="fas fa-calendar me-1"></i>Semester {{ $count->plan_semester }}
                                </div>
                            </div>
                            <div class="row text-muted small mt-1">
                                <div class="col-6">
                                    <i class="fas fa-map-marker-alt me-1"></i>{{ $count->branch ?? 'Default' }}
                                </div>
                                <div class="col-6">
                                    <span class="badge bg-primary me-1">{{ $count->male_count }}M</span>
                                    <span class="badge bg-danger">{{ $count->female_count }}F</span>
                                </div>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-dark">Total: {{ $count->male_count + $count->female_count }} students</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        <div class="d-flex">
                            <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <small><strong>Warning:</strong> This action cannot be undone. Any sections or timetables based on this count may be affected.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Yes, Delete Count
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<style>
/* Enhanced form styling */
.modal-body .form-control:focus,
.modal-body .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Real-time calculation display */
.calculation-display {
    transition: all 0.3s ease;
}

.calculation-display.updated {
    background-color: rgba(59, 130, 246, 0.1) !important;
    border-color: var(--primary-color) !important;
}

/* Gender-specific styling */
.form-label .fa-male {
    color: #3b82f6 !important;
}

.form-label .fa-female {
    color: #ef4444 !important;
}

/* Modal responsive enhancements */
@media (max-width: 768px) {
    .modal-lg {
        max-width: 95%;
    }

    .modal-body {
        padding: 1rem !important;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
    }

    .card-body .row .col-4 {
        margin-bottom: 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Real-time calculation for add modal
    const maleInput = document.getElementById('add_male_count');
    const femaleInput = document.getElementById('add_female_count');
    const maleDisplay = document.getElementById('maleDisplay');
    const femaleDisplay = document.getElementById('femaleDisplay');
    const totalDisplay = document.getElementById('totalDisplay');

    function updateCalculation() {
        const maleCount = parseInt(maleInput?.value || 0);
        const femaleCount = parseInt(femaleInput?.value || 0);
        const total = maleCount + femaleCount;

        if (maleDisplay) maleDisplay.textContent = maleCount;
        if (femaleDisplay) femaleDisplay.textContent = femaleCount;
        if (totalDisplay) {
            totalDisplay.textContent = total;

            // Add visual feedback
            totalDisplay.parentElement.parentElement.parentElement.classList.add('updated');
            setTimeout(() => {
                totalDisplay.parentElement.parentElement.parentElement.classList.remove('updated');
            }, 1000);
        }
    }

    if (maleInput && femaleInput) {
        maleInput.addEventListener('input', updateCalculation);
        femaleInput.addEventListener('input', updateCalculation);

        // Initial calculation
        updateCalculation();
    }

    // Form validation enhancements
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

    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
            const alertInstance = bootstrap.Alert.getOrCreateInstance(alert);
            alertInstance.close();
        });
    }, 5000);

    console.log('✅ Expected Counts modals initialized successfully');
});
</script>
