@php
    $daysOfWeek = \App\Http\Controllers\DataEntry\TimeslotController::DAYS_OF_WEEK;
@endphp

{{-- Generate Standard Timeslots Modal --}}
<div class="modal fade" id="generateStandardTimeslotsModal" tabindex="-1" aria-labelledby="generateStandardTimeslotsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title d-flex align-items-center" id="generateStandardTimeslotsModalLabel">
                    <i class="fas fa-cogs me-2"></i>
                    Generate Standard Weekly Timeslots
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.timeslots.generateStandard') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                    <!-- Warning Alert -->
                    <div class="alert alert-warning d-flex align-items-start mb-4" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                        <div>
                            <strong class="d-block mb-1">Warning!</strong>
                            <small>This will delete all existing timeslots and generate new ones based on your settings. This action cannot be undone.</small>
                        </div>
                    </div>

                    <!-- Validation Errors -->
                    @if ($errors->hasBag('generateStandardModal'))
                        <div class="alert alert-danger d-flex align-items-start mb-3" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <strong class="d-block mb-1">Please correct the errors:</strong>
                                <ul class="mb-0 small">
                                    @foreach ($errors->getBag('generateStandardModal')->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <!-- Working Days Selection -->
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h6 class="card-title text-primary mb-0">
                                <i class="fas fa-calendar-week me-1"></i>
                                Working Days Selection
                            </h6>
                        </div>
                        <div class="card-body pt-2">
                            <label class="form-label fw-medium mb-3">
                                Select Working Days <span class="text-danger">*</span>
                            </label>
                            <div class="row g-2">
                                @foreach($daysOfWeek as $day)
                                    <div class="col-md-3 col-sm-4 col-6">
                                        <div class="day-checkbox">
                                            <input class="form-check-input @error('working_days.'.$loop->index, 'generateStandardModal') is-invalid @enderror"
                                                   type="checkbox"
                                                   name="working_days[]"
                                                   value="{{ $day }}"
                                                   id="day_{{ $loop->index }}"
                                                   {{ is_array(old('working_days')) && in_array($day, old('working_days', ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'])) ? 'checked' : (!is_array(old('working_days')) && in_array($day, ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']) && !old('_token') ? 'checked' : '') }}>
                                            <label class="form-check-label w-100 p-2 border rounded text-center cursor-pointer" for="day_{{ $loop->index }}">
                                                <i class="fas fa-calendar-day d-block mb-1"></i>
                                                <small class="fw-medium">{{ $day }}</small>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('working_days', 'generateStandardModal')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Time Settings -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary bg-opacity-10 border-primary">
                            <h6 class="card-title text-primary mb-0">
                                <i class="fas fa-clock me-1"></i>
                                Time Settings
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="gen_overall_start_time" class="form-label fw-medium">
                                        <i class="fas fa-play text-success me-1"></i>
                                        Overall Start Time <span class="text-danger">*</span>
                                    </label>
                                    <input type="time"
                                           class="form-control @error('overall_start_time', 'generateStandardModal') is-invalid @enderror"
                                           id="gen_overall_start_time"
                                           name="overall_start_time"
                                           value="{{ old('overall_start_time', '08:00') }}"
                                           required>
                                    @error('overall_start_time', 'generateStandardModal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="form-text">When the first class should start</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="gen_overall_end_time" class="form-label fw-medium">
                                        <i class="fas fa-stop text-danger me-1"></i>
                                        Overall End Time <span class="text-danger">*</span>
                                    </label>
                                    <input type="time"
                                           class="form-control @error('overall_end_time', 'generateStandardModal') is-invalid @enderror"
                                           id="gen_overall_end_time"
                                           name="overall_end_time"
                                           value="{{ old('overall_end_time', '16:00') }}"
                                           required>
                                    @error('overall_end_time', 'generateStandardModal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="form-text">When the last class should end</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="gen_lecture_duration" class="form-label fw-medium">
                                        <i class="fas fa-hourglass-half text-info me-1"></i>
                                        Lecture Duration (minutes) <span class="text-danger">*</span>
                                    </label>
                                    <input type="number"
                                           class="form-control @error('lecture_duration', 'generateStandardModal') is-invalid @enderror"
                                           id="gen_lecture_duration"
                                           name="lecture_duration"
                                           value="{{ old('lecture_duration', 50) }}"
                                           required
                                           min="15"
                                           max="180">
                                    @error('lecture_duration', 'generateStandardModal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="form-text">Duration of each class period</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="gen_break_duration" class="form-label fw-medium">
                                        <i class="fas fa-coffee text-warning me-1"></i>
                                        Break Duration (minutes) <span class="text-danger">*</span>
                                    </label>
                                    <input type="number"
                                           class="form-control @error('break_duration', 'generateStandardModal') is-invalid @enderror"
                                           id="gen_break_duration"
                                           name="break_duration"
                                           value="{{ old('break_duration', 10) }}"
                                           required
                                           min="0"
                                           max="60">
                                    @error('break_duration', 'generateStandardModal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="form-text">Break time between classes</div>
                                    @enderror
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
                        <i class="fas fa-magic me-1"></i>Generate Timeslots
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add New Timeslot Modal --}}
@if (!isset($timeslot_to_edit))
<div class="modal fade" id="addTimeslotModal" tabindex="-1" aria-labelledby="addTimeslotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="addTimeslotModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>
                    Add New Timeslot
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.timeslots.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body p-4">
                    <!-- Validation Errors -->
                    @if ($errors->hasBag('addTimeslotModal'))
                        <div class="alert alert-danger d-flex align-items-start mb-3" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <strong class="d-block mb-1">Please correct the errors:</strong>
                                <ul class="mb-0 small">
                                    @foreach ($errors->getBag('addTimeslotModal')->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="add_day" class="form-label fw-medium">
                                <i class="fas fa-calendar-day text-muted me-1"></i>
                                Day <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('day', 'addTimeslotModal') is-invalid @enderror"
                                    id="add_day"
                                    name="day"
                                    required>
                                <option value="" selected disabled>Select day...</option>
                                @foreach ($daysOfWeek as $day)
                                    <option value="{{ $day }}" {{ old('day') == $day ? 'selected' : '' }}>{{ $day }}</option>
                                @endforeach
                            </select>
                            @error('day', 'addTimeslotModal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Choose the day of the week for this timeslot</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="add_start_time" class="form-label fw-medium">
                                <i class="fas fa-play text-success me-1"></i>
                                Start Time <span class="text-danger">*</span>
                            </label>
                            <input type="time"
                                   class="form-control @error('start_time', 'addTimeslotModal') is-invalid @enderror"
                                   id="add_start_time"
                                   name="start_time"
                                   value="{{ old('start_time') }}"
                                   required>
                            @error('start_time', 'addTimeslotModal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">When this period starts</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="add_end_time" class="form-label fw-medium">
                                <i class="fas fa-stop text-danger me-1"></i>
                                End Time <span class="text-danger">*</span>
                            </label>
                            <input type="time"
                                   class="form-control @error('end_time', 'addTimeslotModal') is-invalid @enderror"
                                   id="add_end_time"
                                   name="end_time"
                                   value="{{ old('end_time') }}"
                                   required>
                            @error('end_time', 'addTimeslotModal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">When this period ends</div>
                            @enderror
                            @error('time_unique', 'addTimeslotModal')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            @error('time_overlap', 'addTimeslotModal')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Timeslot
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Edit & Delete Modals for Individual Timeslots --}}
@if (isset($timeslot_to_edit))
@php $timeslot = $timeslot_to_edit; @endphp

{{-- Edit Timeslot Modal --}}
<div class="modal fade" id="editTimeslotModal-{{ $timeslot->id }}" tabindex="-1" aria-labelledby="editTimeslotModalLabel-{{ $timeslot->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title d-flex align-items-center" id="editTimeslotModalLabel-{{ $timeslot->id }}">
                    <i class="fas fa-edit me-2"></i>
                    Edit Timeslot
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.timeslots.update', $timeslot->id) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    @php $editErrorBag = 'editTimeslotModal_'.$timeslot->id; @endphp

                    <!-- Info Alert -->
                    <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Editing timeslot: <strong>{{ $timeslot->day }} {{ \Carbon\Carbon::parse($timeslot->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($timeslot->end_time)->format('h:i A') }}</strong></small>
                    </div>

                    <!-- Validation Errors -->
                    @if ($errors->hasBag($editErrorBag))
                        <div class="alert alert-danger d-flex align-items-start mb-3" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <strong class="d-block mb-1">Please correct the errors:</strong>
                                <ul class="mb-0 small">
                                    @foreach ($errors->getBag($editErrorBag)->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="edit_day_{{ $timeslot->id }}" class="form-label fw-medium">
                                <i class="fas fa-calendar-day text-muted me-1"></i>
                                Day <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('day', $editErrorBag) is-invalid @enderror"
                                    id="edit_day_{{ $timeslot->id }}"
                                    name="day"
                                    required>
                                @foreach ($daysOfWeek as $day)
                                    <option value="{{ $day }}" {{ old('day', $timeslot->day) == $day ? 'selected' : '' }}>{{ $day }}</option>
                                @endforeach
                            </select>
                            @error('day', $editErrorBag)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="edit_start_time_{{ $timeslot->id }}" class="form-label fw-medium">
                                <i class="fas fa-play text-success me-1"></i>
                                Start Time <span class="text-danger">*</span>
                            </label>
                            <input type="time"
                                   class="form-control @error('start_time', $editErrorBag) is-invalid @enderror"
                                   id="edit_start_time_{{ $timeslot->id }}"
                                   name="start_time"
                                   value="{{ old('start_time', \Carbon\Carbon::parse($timeslot->start_time)->format('H:i')) }}"
                                   required>
                            @error('start_time', $editErrorBag)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="edit_end_time_{{ $timeslot->id }}" class="form-label fw-medium">
                                <i class="fas fa-stop text-danger me-1"></i>
                                End Time <span class="text-danger">*</span>
                            </label>
                            <input type="time"
                                   class="form-control @error('end_time', $editErrorBag) is-invalid @enderror"
                                   id="edit_end_time_{{ $timeslot->id }}"
                                   name="end_time"
                                   value="{{ old('end_time', \Carbon\Carbon::parse($timeslot->end_time)->format('H:i')) }}"
                                   required>
                            @error('end_time', $editErrorBag)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('time_unique', $editErrorBag)
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            @error('time_overlap', $editErrorBag)
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-sync-alt me-1"></i>Update Timeslot
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteTimeslotModal-{{ $timeslot->id }}" tabindex="-1" aria-labelledby="deleteTimeslotModalLabel-{{ $timeslot->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="deleteTimeslotModalLabel-{{ $timeslot->id }}">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('data-entry.timeslots.destroy', $timeslot->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-clock text-danger" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>

                    <div class="alert alert-danger d-flex align-items-start" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Are you sure?</h6>
                            <p class="mb-0 small">You are about to delete this timeslot:</p>
                        </div>
                    </div>

                    <div class="card bg-light border-danger">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">{{ $timeslot->day }}</h6>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($timeslot->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($timeslot->end_time)->format('h:i A') }}</small>
                                </div>
                                <span class="badge bg-danger">Will be deleted</span>
                            </div>
                        </div>
                    </div>

                    @if($timeslot->schedule_entries_count > 0)
                        <div class="alert alert-warning mt-3 mb-0" role="alert">
                            <div class="d-flex">
                                <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                                <div>
                                    <small><strong>Cannot Delete:</strong> This timeslot is used by {{ $timeslot->schedule_entries_count }} scheduled classes.</small>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning mt-3 mb-0" role="alert">
                            <div class="d-flex">
                                <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
                                <div>
                                    <small><strong>Warning:</strong> This action cannot be undone.</small>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit"
                            class="btn btn-danger"
                            {{ $timeslot->schedule_entries_count > 0 ? 'disabled' : '' }}>
                        <i class="fas fa-trash me-1"></i>Yes, Delete Timeslot
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<style>
/* Day Checkbox Styling */
.day-checkbox {
    position: relative;
}

.day-checkbox input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.day-checkbox label {
    transition: all 0.15s ease;
    border-color: #dee2e6 !important;
    background-color: #fff;
}

.day-checkbox input[type="checkbox"]:checked + label {
    background-color: rgba(59, 130, 246, 0.1);
    border-color: var(--primary-color) !important;
    color: var(--primary-color);
}

.day-checkbox label:hover {
    border-color: var(--primary-color) !important;
    background-color: rgba(59, 130, 246, 0.05);
}

body.dark-mode .day-checkbox label {
    background-color: var(--dark-bg-secondary);
    border-color: var(--dark-border) !important;
    color: var(--dark-text-secondary);
}

body.dark-mode .day-checkbox input[type="checkbox"]:checked + label {
    background-color: rgba(59, 130, 246, 0.15);
    color: var(--primary-light);
}

/* Time Input Enhancements */
input[type="time"] {
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
}

/* Modal Responsive */
@media (max-width: 1200px) {
    .modal-xl {
        max-width: 95%;
    }
}

@media (max-width: 768px) {
    .modal-xl, .modal-lg {
        max-width: 95%;
        margin: 0.5rem;
    }

    .modal-body {
        max-height: 60vh !important;
        padding: 1rem !important;
    }

    .day-checkbox label {
        padding: 1rem 0.5rem !important;
        font-size: 0.8rem;
    }

    .day-checkbox i {
        font-size: 0.8rem;
    }

    .card-body .row .col-md-6,
    .card-body .row .col-md-3 {
        margin-bottom: 1rem;
    }

    .btn {
        font-size: 0.8125rem;
        padding: 0.375rem 0.75rem;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.25rem;
    }

    .modal-header h5 {
        font-size: 1rem;
    }

    .day-checkbox label {
        padding: 0.75rem 0.25rem !important;
        font-size: 0.75rem;
    }

    .btn {
        font-size: 0.75rem;
        padding: 0.5rem 0.75rem;
    }

    .form-label {
        font-size: 0.875rem;
    }

    .form-control,
    .form-select {
        font-size: 0.875rem;
    }
}

/* Form Enhancement */
.cursor-pointer {
    cursor: pointer;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Enhanced badges */
.badge {
    font-weight: 500;
    letter-spacing: 0.025em;
}

/* Animation for alerts */
.alert {
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Enhanced card styling */
.card {
    transition: all 0.15s ease;
}

.card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

body.dark-mode .card {
    background: var(--dark-bg-secondary);
    border-color: var(--dark-border);
}

body.dark-mode .card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

/* Loading state for buttons */
.btn-loading {
    position: relative;
    pointer-events: none;
}

.btn-loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}

/* Enhanced form validation */
.was-validated .form-control:valid,
.was-validated .form-select:valid {
    border-color: #22c55e;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2322c55e' d='m2.3 6.73.4.4c.2.2.6.2.8 0L7.7 3.3a.6.6 0 0 0-.8-.8L3.7 5.7l-1.1-1.1a.6.6 0 0 0-.8.8l.5.3z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(.375em + .1875rem) center;
    background-size: calc(.75em + .375rem) calc(.75em + .375rem);
}

.was-validated .form-control:invalid,
.was-validated .form-select:invalid {
    border-color: #ef4444;
}

/* Enhanced modal backdrop */
.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.6);
}

body.dark-mode .modal-backdrop {
    background-color: rgba(0, 0, 0, 0.8);
}

/* Focus management */
.modal.show .modal-dialog {
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Enhanced tooltip styling */
[title] {
    position: relative;
}

/* Print styles */
@media print {
    .modal,
    .modal-backdrop {
        display: none !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced form validation
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

    // Day selection enhancement for generate modal
    const dayCheckboxes = document.querySelectorAll('input[name="working_days[]"]');
    dayCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const label = this.nextElementSibling;
            if (this.checked) {
                label.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    label.style.transform = 'scale(1)';
                }, 150);
            }
        });
    });

    // Time validation
    function validateTimeInputs(startInput, endInput) {
        if (startInput.value && endInput.value) {
            const start = new Date(`2000-01-01T${startInput.value}`);
            const end = new Date(`2000-01-01T${endInput.value}`);

            if (start >= end) {
                endInput.setCustomValidity('End time must be after start time');
            } else {
                endInput.setCustomValidity('');
            }
        }
    }

    // Apply time validation to all time inputs
    const timeInputPairs = [
        ['#gen_overall_start_time', '#gen_overall_end_time'],
        ['#add_start_time', '#add_end_time']
    ];

    timeInputPairs.forEach(([startSelector, endSelector]) => {
        const startInput = document.querySelector(startSelector);
        const endInput = document.querySelector(endSelector);

        if (startInput && endInput) {
            [startInput, endInput].forEach(input => {
                input.addEventListener('change', () => {
                    validateTimeInputs(startInput, endInput);
                });
            });
        }
    });

    // Dynamic time validation for edit modals
    document.querySelectorAll('[id^="edit_start_time_"], [id^="edit_end_time_"]').forEach(input => {
        input.addEventListener('change', function() {
            const id = this.id.split('_').pop();
            const startInput = document.querySelector(`#edit_start_time_${id}`);
            const endInput = document.querySelector(`#edit_end_time_${id}`);

            if (startInput && endInput) {
                validateTimeInputs(startInput, endInput);
            }
        });
    });

    // Modal reset functionality
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            const form = this.querySelector('form');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');

                // Clear custom validation messages
                form.querySelectorAll('input, select, textarea').forEach(input => {
                    input.setCustomValidity('');
                });
            }
        });
    });

    // Auto-calculate duration preview
    function updateDurationPreview() {
        const startTime = document.querySelector('#gen_overall_start_time')?.value;
        const endTime = document.querySelector('#gen_overall_end_time')?.value;
        const lectureDuration = parseInt(document.querySelector('#gen_lecture_duration')?.value) || 0;
        const breakDuration = parseInt(document.querySelector('#gen_break_duration')?.value) || 0;

        if (startTime && endTime && lectureDuration) {
            const start = new Date(`2000-01-01T${startTime}`);
            const end = new Date(`2000-01-01T${endTime}`);
            const totalMinutes = (end - start) / (1000 * 60);
            const slotDuration = lectureDuration + breakDuration;
            const possibleSlots = Math.floor(totalMinutes / slotDuration);

            // Create or update preview
            let preview = document.querySelector('#duration-preview');
            if (!preview) {
                preview = document.createElement('div');
                preview.id = 'duration-preview';
                preview.className = 'alert alert-info mt-2';
                document.querySelector('#gen_break_duration').closest('.col-md-6').appendChild(preview);
            }

            if (possibleSlots > 0) {
                preview.innerHTML = `
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        This will generate approximately <strong>${possibleSlots} timeslots</strong> per day
                        (${totalMinutes} minutes total ÷ ${slotDuration} minutes per slot)
                    </small>
                `;
                preview.className = 'alert alert-info mt-2';
            } else {
                preview.innerHTML = `
                    <small>
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Not enough time to create any complete timeslots with current settings
                    </small>
                `;
                preview.className = 'alert alert-warning mt-2';
            }
        }
    }

    // Attach duration calculation to relevant inputs
    ['#gen_overall_start_time', '#gen_overall_end_time', '#gen_lecture_duration', '#gen_break_duration'].forEach(selector => {
        const input = document.querySelector(selector);
        if (input) {
            input.addEventListener('input', updateDurationPreview);
            input.addEventListener('change', updateDurationPreview);
        }
    });

    // Loading state for form submissions
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && this.checkValidity()) {
                submitBtn.classList.add('btn-loading');
                submitBtn.disabled = true;

                // Re-enable after 10 seconds as fallback
                setTimeout(() => {
                    submitBtn.classList.remove('btn-loading');
                    submitBtn.disabled = false;
                }, 10000);
            }
        });
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Escape key closes modals
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                const modal = bootstrap.Modal.getInstance(openModal);
                modal?.hide();
            }
        }

        // Ctrl+Enter submits forms in modals
        if (e.ctrlKey && e.key === 'Enter') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                const form = openModal.querySelector('form');
                if (form && form.checkValidity()) {
                    form.submit();
                }
            }
        }
    });

    console.log('✅ Timeslot modals initialized successfully');
});
</script>
